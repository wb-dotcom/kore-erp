<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ApprovalSetting;
use App\Models\PtoPolicy;
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

    public function scheduleOfFees()
    {
        // Placeholder — extend with a schedule_of_fees table if needed
        $fees = collect();
        return view('admin.schedule-of-fees', compact('fees'));
    }

    public function saveScheduleOfFees(Request $request)
    {
        return back()->with('success', 'Schedule of fees saved.');
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
