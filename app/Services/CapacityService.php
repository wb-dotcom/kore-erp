<?php

namespace App\Services;

use App\Models\Holiday;
use App\Models\ScheduleOfFee;
use App\Models\TimesheetEntry;
use App\Models\TimeOffRequest;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * CapacityService — answers the question:
 *   "For a given employee over a given date range, how many hours are
 *    available vs. already spoken for?"
 *
 * Algorithm:
 *   total_available  = working_days × daily_hours
 *   net_available    = total_available − holiday_hours − approved_pto_hours
 *   logged_hours     = SUM(timesheet_entries.hours) in range
 *   remaining        = net_available − logged_hours
 *   utilization_pct  = (logged_hours / total_available) × 100
 *
 * Usage:
 *   $report = CapacityService::forUser($user, '2025-01-06', '2025-01-10');
 *   $report = CapacityService::forTeam($userIds, now()->startOfWeek(), now()->endOfWeek());
 */
class CapacityService
{
    /**
     * Capacity report for a single employee over a date range.
     *
     * @return array{
     *   user_id: int,
     *   name: string,
     *   period_start: string,
     *   period_end: string,
     *   standard_weekly_hours: int,
     *   daily_hours: float,
     *   working_days: int,
     *   total_available_hours: float,
     *   holiday_hours: float,
     *   approved_pto_hours: float,
     *   net_available_hours: float,
     *   logged_hours: float,
     *   remaining_hours: float,
     *   utilization_pct: float,
     *   billing_rate: float,
     *   labor_cost: float,
     * }
     */
    public static function forUser(User $user, string|Carbon $start, string|Carbon $end): array
    {
        $start = Carbon::parse($start)->startOfDay();
        $end   = Carbon::parse($end)->endOfDay();

        $dailyHours   = $user->standard_weekly_hours / 5;
        $workingDays  = self::countWorkingDays($start, $end);
        $totalAvail   = $workingDays * $dailyHours;

        $holidayHours = self::holidayHours($start, $end, $dailyHours);
        $ptoHours     = self::approvedPtoHours($user->id, $start, $end);

        $netAvail     = max(0, $totalAvail - $holidayHours - $ptoHours);
        $loggedHours  = self::loggedHours($user->id, $start, $end);
        $remaining    = $netAvail - $loggedHours;

        $billingRate  = $user->hourly_cost
            ?? ScheduleOfFee::rateForRole($user->role?->name ?? '');

        return [
            'user_id'               => $user->id,
            'name'                  => $user->first_name . ' ' . $user->last_name,
            'period_start'          => $start->toDateString(),
            'period_end'            => $end->toDateString(),
            'standard_weekly_hours' => $user->standard_weekly_hours,
            'daily_hours'           => $dailyHours,
            'working_days'          => $workingDays,
            'total_available_hours' => round($totalAvail, 2),
            'holiday_hours'         => round($holidayHours, 2),
            'approved_pto_hours'    => round($ptoHours, 2),
            'net_available_hours'   => round($netAvail, 2),
            'logged_hours'          => round($loggedHours, 2),
            'remaining_hours'       => round($remaining, 2),
            'utilization_pct'       => $totalAvail > 0
                ? round(($loggedHours / $totalAvail) * 100, 1)
                : 0,
            'billing_rate'          => round($billingRate, 2),
            'labor_cost'            => round($loggedHours * $billingRate, 2),
        ];
    }

    /**
     * Capacity reports for a list of users (e.g., a whole team or project team).
     * Returns a Collection of individual forUser() reports, plus a team summary.
     *
     * @param  int[]  $userIds
     */
    public static function forTeam(
        array $userIds,
        string|Carbon $start,
        string|Carbon $end
    ): array {
        $users = User::with('role')
            ->whereIn('id', $userIds)
            ->where('is_active', 1)
            ->get();

        $reports = $users->map(fn (User $u) => self::forUser($u, $start, $end));

        return [
            'reports'                 => $reports,
            'team_net_available_hours'=> $reports->sum('net_available_hours'),
            'team_logged_hours'       => $reports->sum('logged_hours'),
            'team_remaining_hours'    => $reports->sum('remaining_hours'),
            'team_utilization_pct'    => $reports->avg('utilization_pct'),
            'team_labor_cost'         => $reports->sum('labor_cost'),
        ];
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    /**
     * Count Monday–Friday days in the range (excludes weekends).
     */
    private static function countWorkingDays(Carbon $start, Carbon $end): int
    {
        $count = 0;
        foreach (CarbonPeriod::create($start, $end) as $day) {
            if ($day->isWeekday()) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Hours lost to firm holidays in the range.
     * Each holiday counts as one full day at the user's daily_hours rate.
     */
    private static function holidayHours(Carbon $start, Carbon $end, float $dailyHours): float
    {
        $count = Holiday::whereBetween('holiday_date', [
            $start->toDateString(),
            $end->toDateString(),
        ])->count();

        return $count * $dailyHours;
    }

    /**
     * Sum of approved PTO hours in the range for this user.
     *
     * Two sources:
     *   1. time_off_requests with approved status (explicit hours or derived from days)
     *   2. timesheet_entries of type 'pto' (already-logged PTO on approved timesheets)
     *
     * We use time_off_requests as the forward-looking source (approved but not yet
     * in a timesheet) and avoid double-counting with submitted entries.
     */
    private static function approvedPtoHours(int $userId, Carbon $start, Carbon $end): float
    {
        return (float) TimeOffRequest::where('user_id', $userId)
            ->where('request_type', 'pto')
            ->where('status', 'approved')
            ->where(function ($q) use ($start, $end) {
                // Requests that overlap the query window
                $q->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                  ->orWhereBetween('end_date', [$start->toDateString(), $end->toDateString()]);
            })
            ->sum('hours');
    }

    /**
     * Total hours logged on timesheets in the range (all entry types).
     */
    private static function loggedHours(int $userId, Carbon $start, Carbon $end): float
    {
        return (float) TimesheetEntry::whereHas('timesheet', fn ($q) => $q->where('user_id', $userId))
            ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->sum('hours');
    }
}
