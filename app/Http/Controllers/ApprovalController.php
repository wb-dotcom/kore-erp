<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Approval;
use App\Models\ExpenseRequest;
use App\Models\TimeOffRequest;
use App\Models\Timesheet;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function index()
    {
        $pendingTimesheets = Timesheet::where('status', 'submitted')->count();
        $pendingTimeOff    = TimeOffRequest::where('status', 'pending')->count();
        $pendingExpenses   = ExpenseRequest::where('status', 'pending')->count();

        return view('approvals.index', compact('pendingTimesheets', 'pendingTimeOff', 'pendingExpenses'));
    }

    public function timesheets(Request $request)
    {
        $query = Timesheet::with(['user', 'period'])
            ->where('status', 'submitted')
            ->orderBy('submitted_at');

        if ($search = $request->input('search')) {
            $query->whereHas('user', fn($q) => $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%"));
        }

        $timesheets = $query->paginate(25)->withQueryString();

        return view('approvals.timesheets', compact('timesheets'));
    }

    public function timeOff(Request $request)
    {
        $query = TimeOffRequest::with('user')
            ->where('status', 'pending')
            ->orderBy('created_at');

        $requests = $query->paginate(25)->withQueryString();

        return view('approvals.time-off', compact('requests'));
    }

    public function expenses(Request $request)
    {
        $query = ExpenseRequest::with(['user', 'project'])
            ->where('status', 'pending')
            ->orderBy('created_at');

        $expenses = $query->paginate(25)->withQueryString();

        return view('approvals.expenses', compact('expenses'));
    }

    public function approve(Request $request, string $type, int $id)
    {
        $comments = $request->input('comments');

        match($type) {
            'timesheet' => $this->approveTimesheet($id, $comments),
            'time-off'  => $this->approveTimeOff($id, $comments),
            'expense'   => $this->approveExpense($id, $comments),
            default     => abort(404),
        };

        return back()->with('success', ucfirst(str_replace('-', ' ', $type)).' approved.');
    }

    public function reject(Request $request, string $type, int $id)
    {
        $data = $request->validate(['comments' => ['nullable', 'string']]);

        match($type) {
            'timesheet' => $this->rejectTimesheet($id, $data['comments'] ?? null),
            'time-off'  => $this->rejectTimeOff($id, $data['comments'] ?? null),
            'expense'   => $this->rejectExpense($id, $data['comments'] ?? null),
            default     => abort(404),
        };

        return back()->with('success', ucfirst(str_replace('-', ' ', $type)).' rejected.');
    }

    // ─── Private Helpers ─────────────────────────────────────────────────────

    private function approveTimesheet(int $id, ?string $comments): void
    {
        $ts = Timesheet::findOrFail($id);
        $ts->update(['status' => 'approved']);
        $this->recordApproval('timesheet', $id, 'approved', $comments);
        ActivityLog::record('Approved timesheet', 'timesheets', $id, $ts->user?->full_name);
    }

    private function rejectTimesheet(int $id, ?string $comments): void
    {
        $ts = Timesheet::findOrFail($id);
        $ts->update(['status' => 'rejected']);
        $this->recordApproval('timesheet', $id, 'rejected', $comments);
        ActivityLog::record('Rejected timesheet', 'timesheets', $id, $ts->user?->full_name);
    }

    private function approveTimeOff(int $id, ?string $comments): void
    {
        $req = TimeOffRequest::findOrFail($id);
        $req->update(['status' => 'approved', 'approved_by' => auth()->id()]);
        $this->recordApproval('time_off', $id, 'approved', $comments);
        ActivityLog::record('Approved time-off request', 'time_off_requests', $id, $req->user?->full_name);
    }

    private function rejectTimeOff(int $id, ?string $comments): void
    {
        $req = TimeOffRequest::findOrFail($id);
        $req->update(['status' => 'rejected', 'approved_by' => auth()->id()]);
        $this->recordApproval('time_off', $id, 'rejected', $comments);
        ActivityLog::record('Rejected time-off request', 'time_off_requests', $id, $req->user?->full_name);
    }

    private function approveExpense(int $id, ?string $comments): void
    {
        $exp = ExpenseRequest::findOrFail($id);
        $exp->update(['status' => 'approved', 'approved_by' => auth()->id()]);
        $this->recordApproval('expense', $id, 'approved', $comments);
        ActivityLog::record('Approved expense request', 'expense_requests', $id, $exp->user?->full_name);
    }

    private function rejectExpense(int $id, ?string $comments): void
    {
        $exp = ExpenseRequest::findOrFail($id);
        $exp->update(['status' => 'rejected', 'approved_by' => auth()->id()]);
        $this->recordApproval('expense', $id, 'rejected', $comments);
        ActivityLog::record('Rejected expense request', 'expense_requests', $id, $exp->user?->full_name);
    }

    private function recordApproval(string $type, int $refId, string $status, ?string $comments): void
    {
        Approval::create([
            'approval_type' => $type,
            'reference_id'  => $refId,
            'approver_id'   => auth()->id(),
            'status'        => $status,
            'comments'      => $comments,
            'actioned_at'   => now(),
            'created_at'    => now(),
        ]);
    }
}
