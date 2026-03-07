<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Timesheet;
use App\Models\TimesheetEntry;
use App\Models\TimesheetPeriod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TimesheetController extends Controller
{
    /**
     * Show the weekly timesheet grid for the current (or selected) period.
     */
    public function index(Request $request)
    {
        $userId = auth()->id();

        // Determine period — either from query param or the one covering today
        if ($periodId = $request->input('period_id')) {
            $period = TimesheetPeriod::findOrFail($periodId);
        } else {
            $today  = Carbon::today();
            $period = TimesheetPeriod::where('start_date', '<=', $today)
                          ->where('end_date', '>=', $today)
                          ->first();
        }

        // All periods for the selector (most recent first)
        $periods = TimesheetPeriod::orderByDesc('start_date')->take(26)->get();

        if (! $period) {
            return view('timesheet.index', [
                'period'        => null,
                'periods'       => $periods,
                'timesheet'     => null,
                'weekDays'      => collect(),
                'projects'      => collect(),
                'entriesMatrix' => [],
                'dailyTotals'   => [],
            ]);
        }

        // Get or create the timesheet record
        $timesheet = Timesheet::firstOrCreate(
            ['user_id' => $userId, 'period_id' => $period->id],
            ['status' => 'draft', 'total_hours' => 0]
        );

        // Build array of dates for this period (Mon → end of period)
        $weekDays = $this->periodDays($period);

        // Load all existing entries for this timesheet
        $entries = $timesheet->entries()->with('project')->get();

        // Build matrix: [project_id][Y-m-d] = entry
        $entriesMatrix = [];
        foreach ($entries as $entry) {
            $entriesMatrix[$entry->project_id][$entry->entry_date->format('Y-m-d')] = $entry;
        }

        // Projects the user has worked on OR all active projects (for adding new rows)
        $projectIdsUsed = $entries->pluck('project_id')->unique()->all();
        $usedProjects   = Project::whereIn('id', $projectIdsUsed)->orderBy('title')->get();
        $allProjects    = Project::where('status_id', '!=', null)->orderBy('title')->get();

        // Daily totals
        $dailyTotals = [];
        foreach ($weekDays as $day) {
            $key = $day->format('Y-m-d');
            $dailyTotals[$key] = $entries->where('entry_date', $day->toDateString())
                                         ->sum('hours');
        }

        return view('timesheet.index', compact(
            'period', 'periods', 'timesheet', 'weekDays',
            'usedProjects', 'allProjects', 'entriesMatrix', 'dailyTotals'
        ));
    }

    /**
     * Save (upsert) a single timesheet entry cell.
     */
    public function saveEntry(Request $request)
    {
        $data = $request->validate([
            'period_id'  => ['required', 'exists:timesheet_periods,id'],
            'project_id' => ['required', 'exists:projects,id'],
            'entry_date' => ['required', 'date'],
            'hours'      => ['required', 'numeric', 'min:0', 'max:24'],
            'entry_type' => ['nullable', 'in:billable,non_billable,pto,unpaid,remote_work'],
            'notes'      => ['nullable', 'string'],
        ]);

        $userId = auth()->id();

        $timesheet = Timesheet::firstOrCreate(
            ['user_id' => $userId, 'period_id' => $data['period_id']],
            ['status' => 'draft', 'total_hours' => 0]
        );

        if ($timesheet->isSubmitted()) {
            return response()->json(['error' => 'Timesheet already submitted.'], 422);
        }

        // Upsert the entry
        $entry = TimesheetEntry::updateOrCreate(
            [
                'timesheet_id' => $timesheet->id,
                'project_id'   => $data['project_id'],
                'entry_date'   => $data['entry_date'],
            ],
            [
                'hours'      => $data['hours'],
                'entry_type' => $data['entry_type'] ?? 'billable',
                'notes'      => $data['notes'] ?? null,
            ]
        );

        // Remove zero-hour entries
        if ((float) $data['hours'] === 0.0) {
            $entry->delete();
        }

        // Recalculate total
        $total = $timesheet->entries()->sum('hours');
        $timesheet->update(['total_hours' => $total]);

        return response()->json(['success' => true, 'total' => $total]);
    }

    /**
     * Submit the timesheet for approval.
     */
    public function submit(Request $request)
    {
        $data = $request->validate([
            'period_id' => ['required', 'exists:timesheet_periods,id'],
        ]);

        $userId    = auth()->id();
        $timesheet = Timesheet::where('user_id', $userId)
                        ->where('period_id', $data['period_id'])
                        ->firstOrFail();

        if ($timesheet->isSubmitted()) {
            return back()->with('error', 'Timesheet is already submitted.');
        }

        if ($timesheet->entries()->count() === 0) {
            return back()->with('error', 'Cannot submit an empty timesheet.');
        }

        $timesheet->update([
            'status'       => 'submitted',
            'submitted_at' => now(),
        ]);

        ActivityLog::record('Submitted timesheet', 'timesheets', $timesheet->id,
            $timesheet->period->label);

        return redirect()->route('timesheet.index', ['period_id' => $data['period_id']])
            ->with('success', 'Timesheet submitted for approval.');
    }

    /**
     * View timesheet history (all periods for current user).
     */
    public function history()
    {
        $userId = auth()->id();

        $timesheets = Timesheet::where('user_id', $userId)
            ->with('period')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('timesheet.history', compact('timesheets'));
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function periodDays(TimesheetPeriod $period): Collection
    {
        $days    = collect();
        $current = $period->start_date->copy();

        while ($current->lte($period->end_date)) {
            $days->push($current->copy());
            $current->addDay();
        }

        return $days;
    }
}
