<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ApprovalSetting;
use App\Models\FeeSchedule;
use App\Models\PtoPolicy;
use App\Models\ScheduleOfFee;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        $stats = [
            'users'     => User::where('is_active', 1)->count(),
            'total_users' => User::count(),
        ];

        $recentActivity = ActivityLog::with('user')
            ->orderByDesc('created_at')
            ->take(20)
            ->get();

        return view('admin.index', compact('stats', 'recentActivity'));
    }

    public function approvalSettings()
    {
        $settings = ApprovalSetting::with('approver')->get()->groupBy('approval_type');
        $approvers = User::where('is_active', 1)->whereHas('role', fn($q) =>
            $q->whereIn('name', ['Admin', 'Manager'])
        )->orderBy('first_name')->get();

        return view('admin.approval-settings', compact('settings', 'approvers'));
    }

    public function saveApprovalSettings(Request $request)
    {
        $data = $request->validate([
            'settings'              => ['required', 'array'],
            'settings.*.type'       => ['required', 'string'],
            'settings.*.approver'   => ['required', 'exists:users,id'],
        ]);

        ApprovalSetting::truncate();

        foreach ($data['settings'] as $setting) {
            ApprovalSetting::create([
                'approval_type'    => $setting['type'],
                'approver_user_id' => $setting['approver'],
                'level'            => 1,
                'created_at'       => now(),
            ]);
        }

        return back()->with('success', 'Approval settings saved.');
    }

    public function ptoPolicies()
    {
        $users = User::where('is_active', 1)
            ->with('ptoPolicy')
            ->orderBy('first_name')
            ->get();

        return view('admin.pto-policies', compact('users'));
    }

    public function savePtoPolicy(Request $request, User $user)
    {
        $data = $request->validate([
            'annual_pto_hours' => ['required', 'numeric', 'min:0'],
            'carry_over_hours' => ['nullable', 'numeric', 'min:0'],
            'effective_date'   => ['nullable', 'date'],
        ]);

        PtoPolicy::updateOrCreate(
            ['user_id' => $user->id],
            $data
        );

        return back()->with('success', "{$user->full_name}'s PTO policy saved.");
    }

    // ── Fee Schedules (named, multi-schedule) ─────────────────────────────────

    public function feeSchedules()
    {
        $schedules = FeeSchedule::withCount('rates')->orderByDesc('is_default')->orderBy('name')->get();
        return view('admin.fee-schedules.index', compact('schedules'));
    }

    public function createFeeSchedule()
    {
        return view('admin.fee-schedules.create');
    }

    public function storeFeeSchedule(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'is_active'   => ['boolean'],
            'is_default'  => ['boolean'],
        ]);

        if (! empty($data['is_default'])) {
            FeeSchedule::where('is_default', true)->update(['is_default' => false]);
        }

        $schedule = FeeSchedule::create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active'   => $data['is_active'] ?? true,
            'is_default'  => $data['is_default'] ?? false,
        ]);

        ActivityLog::record('Created fee schedule', 'fee_schedules', $schedule->id, $schedule->name);

        return redirect()->route('admin.fee-schedules.edit', $schedule)->with('success', 'Fee schedule created. Now add rates.');
    }

    public function editFeeSchedule(FeeSchedule $feeSchedule)
    {
        $rates = $feeSchedule->rates()->orderBy('role_name')->get();
        return view('admin.fee-schedules.edit', compact('feeSchedule', 'rates'));
    }

    public function updateFeeSchedule(Request $request, FeeSchedule $feeSchedule)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'is_active'   => ['boolean'],
            'is_default'  => ['boolean'],
            'rates'                   => ['nullable', 'array'],
            'rates.*.id'              => ['nullable', 'integer'],
            'rates.*.role_name'       => ['required', 'string', 'max:100'],
            'rates.*.hourly_rate'     => ['required', 'numeric', 'min:0'],
        ]);

        if (! empty($data['is_default'])) {
            FeeSchedule::where('id', '!=', $feeSchedule->id)->where('is_default', true)->update(['is_default' => false]);
        }

        $feeSchedule->update([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active'   => $data['is_active'] ?? false,
            'is_default'  => $data['is_default'] ?? false,
        ]);

        $submittedIds = [];
        foreach ($data['rates'] ?? [] as $row) {
            if (empty(trim($row['role_name'] ?? ''))) continue;

            $fee = isset($row['id']) && $row['id'] ? ScheduleOfFee::find($row['id']) : null;
            if (! $fee) $fee = new ScheduleOfFee();

            $fee->fee_schedule_id = $feeSchedule->id;
            $fee->role_name       = trim($row['role_name']);
            $fee->hourly_rate     = $row['hourly_rate'];
            $fee->save();
            $submittedIds[] = $fee->id;
        }

        // Remove rates deleted from the form
        ScheduleOfFee::where('fee_schedule_id', $feeSchedule->id)
            ->when(! empty($submittedIds), fn($q) => $q->whereNotIn('id', $submittedIds))
            ->delete();

        ActivityLog::record('Updated fee schedule', 'fee_schedules', $feeSchedule->id, $feeSchedule->name);

        return back()->with('success', 'Fee schedule saved successfully.');
    }

    public function destroyFeeSchedule(FeeSchedule $feeSchedule)
    {
        $feeSchedule->delete();
        return redirect()->route('admin.fee-schedules.index')->with('success', 'Fee schedule deleted.');
    }

    // ── Legacy flat schedule (kept for backward compatibility) ─────────────────

    public function scheduleOfFees()
    {
        // Redirect to new named schedules list
        return redirect()->route('admin.fee-schedules.index');
    }

    public function saveScheduleOfFees(Request $request)
    {
        return redirect()->route('admin.fee-schedules.index');
    }

    public function systemSettings()
    {
        $keys = [
            'app_name', 'currency_symbol', 'currency_code',
            'date_format', 'time_format', 'week_start',
            'invoice_prefix', 'fiscal_year_start',
        ];

        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = SystemSetting::get($key, '');
        }

        return view('admin.system-settings', compact('settings'));
    }

    public function saveSystemSettings(Request $request)
    {
        $data = $request->validate([
            'app_name'          => ['required', 'string'],
            'currency_symbol'   => ['required', 'string', 'max:5'],
            'currency_code'     => ['required', 'string', 'max:5'],
            'date_format'       => ['required', 'string'],
            'time_format'       => ['required', 'string'],
            'week_start'        => ['required', 'string'],
            'invoice_prefix'    => ['required', 'string'],
            'fiscal_year_start' => ['required', 'string'],
        ]);

        foreach ($data as $key => $value) {
            SystemSetting::set($key, $value);
        }

        return back()->with('success', 'System settings saved.');
    }

    public function activityLog(Request $request)
    {
        $query = ActivityLog::with('user')->orderByDesc('created_at');

        if ($search = $request->input('search')) {
            $query->where('action', 'like', "%{$search}%");
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        $logs  = $query->paginate(50)->withQueryString();
        $users = User::orderBy('first_name')->get();

        return view('admin.activity-log', compact('logs', 'users'));
    }

    public function templates()
    {
        return view('admin.templates');
    }

    public function saveTemplate(Request $request)
    {
        return back()->with('success', 'Template saved.');
    }
}
