<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\TimesheetController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ProposalLineItemController;
use App\Http\Controllers\BillingScheduleController;
use App\Http\Controllers\ProposalDeliverableController;
use App\Http\Controllers\WorkloadController;
use App\Http\Controllers\ProjectNoteController;
use App\Http\Controllers\ProjectTypeController;
use App\Http\Controllers\ProjectStatusController;
use App\Http\Controllers\ActivityTemplateAdminController;

/*
|--------------------------------------------------------------------------
| KORE ERP - Web Routes
|--------------------------------------------------------------------------
*/

// ─── AUTHENTICATION ────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/',        [LoginController::class, 'showLogin'])->name('login');
    Route::get('/login',   [LoginController::class, 'showLogin'])->name('login.form');
    Route::post('/login',  [LoginController::class, 'login'])->name('login.post');
    Route::get('/forgot-password',  [LoginController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [LoginController::class, 'sendReset'])->name('password.email');
    Route::get('/reset-password/{token}', [LoginController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password',        [LoginController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ─── GOOGLE OAUTH ───────────────────────────────────────────────────────────
Route::get('/auth/google',          [SocialAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

// ─── PROTECTED ROUTES (require auth) ──────────────────────────────────────
Route::middleware(['auth.kore'])->group(function () {

    // Dashboard
    Route::get('/dashboard',            [DashboardController::class, 'employee'])->name('dashboard');
    Route::get('/dashboard/business',   [DashboardController::class, 'business'])->name('dashboard.business');
    Route::get('/dashboard/financial',  [DashboardController::class, 'financial'])->name('dashboard.financial');
    Route::get('/dashboard/kpi',        [DashboardController::class, 'kpi'])->name('dashboard.kpi');

    // Calendar / Events
    Route::get('/events',               [CalendarController::class, 'index'])->name('events.index');
    Route::post('/events',              [CalendarController::class, 'store'])->name('events.store');

    // Proposals
    Route::resource('proposals', ProposalController::class);
    Route::get('/proposals/{proposal}/line-items',                [ProposalLineItemController::class, 'index'])->name('proposals.line-items.index');
    Route::post('/proposals/{proposal}/line-items',               [ProposalLineItemController::class, 'store'])->name('proposals.line-items.store');
    Route::put('/proposals/{proposal}/line-items/{item}',         [ProposalLineItemController::class, 'update'])->name('proposals.line-items.update');
    Route::delete('/proposals/{proposal}/line-items/{item}',      [ProposalLineItemController::class, 'destroy'])->name('proposals.line-items.destroy');
    Route::get('/proposals/{proposal}/line-items/resolve-rate',   [ProposalLineItemController::class, 'resolveRate'])->name('proposals.line-items.resolve-rate');
    Route::post('/proposals/{proposal}/line-items/export-docs',   [ProposalLineItemController::class, 'exportDocs'])->name('proposals.line-items.export-docs');
    Route::post('/proposals/{proposal}/rate-schedules',            [ProposalLineItemController::class, 'storeRateSchedule'])->name('proposals.rate-schedules.store');
    Route::delete('/proposals/{proposal}/rate-schedules/{rateSchedule}', [ProposalLineItemController::class, 'destroyRateSchedule'])->name('proposals.rate-schedules.destroy');

    // Billing Schedule
    Route::get('/proposals/{proposal}/billing-schedule',                              [BillingScheduleController::class, 'show'])->name('proposals.billing-schedule.show');
    Route::post('/proposals/{proposal}/billing-schedule/generate',                    [BillingScheduleController::class, 'generate'])->name('proposals.billing-schedule.generate');
    Route::post('/proposals/{proposal}/billing-schedule/periods',                     [BillingScheduleController::class, 'storePeriod'])->name('proposals.billing-schedule.periods.store');
    Route::put('/proposals/{proposal}/billing-schedule/periods/{period}',             [BillingScheduleController::class, 'updatePeriod'])->name('proposals.billing-schedule.periods.update');
    Route::delete('/proposals/{proposal}/billing-schedule/periods/{period}',          [BillingScheduleController::class, 'destroyPeriod'])->name('proposals.billing-schedule.periods.destroy');
    Route::post('/proposals/{proposal}/billing-schedule/periods/{period}/generate-invoice', [BillingScheduleController::class, 'generateInvoice'])->name('proposals.billing-schedule.periods.generate-invoice');
    Route::get('/proposals/{proposal}/billing-schedule/periods/{period}/breakdown',         [BillingScheduleController::class, 'periodBreakdown'])->name('proposals.billing-schedule.periods.breakdown');
    Route::post('/proposals/{proposal}/billing-schedule/periods/{period}/compute-fees',     [BillingScheduleController::class, 'computePeriodFees'])->name('proposals.billing-schedule.periods.compute-fees');

    // Proposal Deliverables / Activities / Tasks (work breakdown template)
    Route::get('/proposals/{proposal}/deliverables',                                          [ProposalDeliverableController::class, 'index'])->name('proposals.deliverables.index');
    Route::post('/proposals/{proposal}/deliverables',                                         [ProposalDeliverableController::class, 'storeDeliverable'])->name('proposals.deliverables.store');
    Route::put('/proposals/{proposal}/deliverables/{deliverable}',                            [ProposalDeliverableController::class, 'updateDeliverable'])->name('proposals.deliverables.update');
    Route::delete('/proposals/{proposal}/deliverables/{deliverable}',                         [ProposalDeliverableController::class, 'destroyDeliverable'])->name('proposals.deliverables.destroy');
    Route::post('/proposals/{proposal}/deliverables/copy-template',                           [ProposalDeliverableController::class, 'copyFromTemplate'])->name('proposals.deliverables.copy-template');
    Route::put('/proposals/{proposal}/content', [ProposalController::class, 'saveContent'])->name('proposals.content.save');
    Route::get('/proposals/{proposal}/similar',                                               [ProposalController::class, 'similar'])->name('proposals.similar');
    Route::get('/proposals/{proposal}/deliverables/prior/{source}',                          [ProposalDeliverableController::class, 'priorProposalTree'])->name('proposals.deliverables.prior-tree');
    Route::post('/proposals/{proposal}/deliverables/copy-from-proposal',                     [ProposalDeliverableController::class, 'copyFromProposal'])->name('proposals.deliverables.copy-from-proposal');
    Route::post('/proposals/{proposal}/deliverables/{deliverable}/activities',                [ProposalDeliverableController::class, 'storeActivity'])->name('proposals.activities.store');
    Route::put('/proposals/{proposal}/activities/{activity}',                                 [ProposalDeliverableController::class, 'updateActivity'])->name('proposals.activities.update');
    Route::delete('/proposals/{proposal}/activities/{activity}',                              [ProposalDeliverableController::class, 'destroyActivity'])->name('proposals.activities.destroy');
    Route::post('/proposals/{proposal}/activities/{activity}/tasks',                          [ProposalDeliverableController::class, 'storeTask'])->name('proposals.tasks.store');
    Route::put('/proposals/{proposal}/tasks/{task}',                                          [ProposalDeliverableController::class, 'updateTask'])->name('proposals.tasks.update');
    Route::delete('/proposals/{proposal}/tasks/{task}',                                       [ProposalDeliverableController::class, 'destroyTask'])->name('proposals.tasks.destroy');

    // Documents
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');

    // Projects
    Route::resource('projects', ProjectController::class);
    Route::get('/projects/{project}/deliverables',                             [ProjectController::class, 'deliverables'])->name('projects.deliverables');
    Route::post('/projects/{project}/deliverables',                            [ProjectController::class, 'storeDeliverable'])->name('projects.deliverables.store');
    Route::put('/projects/{project}/deliverables/{deliverable}',               [ProjectController::class, 'updateDeliverable'])->name('projects.deliverables.update');
    Route::delete('/projects/{project}/deliverables/{deliverable}',            [ProjectController::class, 'destroyDeliverable'])->name('projects.deliverables.destroy');
    Route::post('/projects/{project}/deliverables/{deliverable}/milestones',   [ProjectController::class, 'storeMilestone'])->name('projects.milestones.store');
    Route::put('/milestones/{milestone}',                                      [ProjectController::class, 'updateMilestone'])->name('projects.milestones.update');
    Route::delete('/milestones/{milestone}',                                   [ProjectController::class, 'destroyMilestone'])->name('projects.milestones.destroy');
    Route::post('/milestones/{milestone}/tasks',                               [ProjectController::class, 'storeTask'])->name('projects.tasks.store');
    Route::put('/tasks/{task}',                                                [ProjectController::class, 'updateTask'])->name('projects.tasks.update');
    Route::delete('/tasks/{task}',                                             [ProjectController::class, 'destroyTask'])->name('projects.tasks.destroy');
    // Direct task under deliverable (no milestone)
    Route::post('/deliverables/{deliverable}/direct-tasks',                    [ProjectController::class, 'storeDirectTask'])->name('projects.deliverables.direct-tasks.store');
    // Task operations
    Route::post('/tasks/{task}/move',                                          [ProjectController::class, 'moveTask'])->name('projects.tasks.move');
    Route::post('/tasks/{task}/dependencies',                                  [ProjectController::class, 'storeDependency'])->name('projects.tasks.dependencies.store');
    Route::delete('/tasks/{task}/dependencies/{dependsOnId}',                  [ProjectController::class, 'destroyDependency'])->name('projects.tasks.dependencies.destroy');
    Route::post('/tasks/{task}/assignments',                                   [ProjectController::class, 'storeAssignment'])->name('projects.tasks.assignments.store');
    Route::put('/task-assignments/{assignment}',                               [ProjectController::class, 'updateAssignment'])->name('projects.assignments.update');
    Route::delete('/task-assignments/{assignment}',                            [ProjectController::class, 'destroyAssignment'])->name('projects.assignments.destroy');
    // Project-level WBS operations
    Route::post('/projects/{project}/wbs-reorder',                             [ProjectController::class, 'reorderWbs'])->name('projects.wbs.reorder');
    Route::post('/projects/{project}/apply-template',                          [ProjectController::class, 'applyTemplate'])->name('projects.apply-template');
    Route::get('/projects/{project}/gantt-data',                               [ProjectController::class, 'ganttData'])->name('projects.gantt-data');

    // Project Notes / Todos
    Route::post('/projects/{project}/notes',                      [ProjectNoteController::class, 'store'])->name('projects.notes.store');
    Route::put('/projects/{project}/notes/{note}',                [ProjectNoteController::class, 'update'])->name('projects.notes.update');
    Route::patch('/projects/{project}/notes/{note}/toggle',       [ProjectNoteController::class, 'toggle'])->name('projects.notes.toggle');
    Route::delete('/projects/{project}/notes/{note}',             [ProjectNoteController::class, 'destroy'])->name('projects.notes.destroy');

    // Contacts
    Route::resource('contacts', ContactController::class);
    Route::resource('companies', CompanyController::class);

    // Timesheet
    Route::get('/timesheet',                [TimesheetController::class, 'index'])->name('timesheet.index');
    Route::post('/timesheet/entry',         [TimesheetController::class, 'saveEntry'])->name('timesheet.entry.save');
    Route::post('/timesheet/submit',        [TimesheetController::class, 'submit'])->name('timesheet.submit');
    Route::get('/timesheet/history',        [TimesheetController::class, 'history'])->name('timesheet.history');

    // My Tasks
    Route::get('/my-tasks',                 [TaskController::class, 'myTasks'])->name('tasks.mine');
    Route::put('/my-tasks/{assignment}',    [TaskController::class, 'updateAssignment'])->name('tasks.update');

    // Workload Pipeline
    Route::get('/workload',                     [WorkloadController::class, 'index'])->name('workload.index');
    Route::get('/workload/team',                [WorkloadController::class, 'team'])->name('workload.team');
    Route::get('/workload/week-data',           [WorkloadController::class, 'weekData'])->name('workload.week-data');
    Route::post('/workload/schedule',           [WorkloadController::class, 'schedule'])->name('workload.schedule');
    Route::post('/workload/assign',             [WorkloadController::class, 'assign'])->name('workload.assign');
    Route::put('/workload/items/{item}',        [WorkloadController::class, 'update'])->name('workload.items.update');
    Route::delete('/workload/items/{item}',     [WorkloadController::class, 'destroy'])->name('workload.items.destroy');
    Route::post('/workload/reorder',            [WorkloadController::class, 'reorder'])->name('workload.reorder');

    // Approval Center
    Route::get('/approvals',                    [ApprovalController::class, 'index'])->name('approvals.index');
    Route::get('/approvals/timesheets',          [ApprovalController::class, 'timesheets'])->name('approvals.timesheets');
    Route::get('/approvals/time-off',            [ApprovalController::class, 'timeOff'])->name('approvals.time-off');
    Route::get('/approvals/remote-work',         [ApprovalController::class, 'remoteWork'])->name('approvals.remote-work');
    Route::get('/approvals/expenses',            [ApprovalController::class, 'expenses'])->name('approvals.expenses');
    Route::post('/approvals/{type}/{id}/approve', [ApprovalController::class, 'approve'])->name('approvals.approve');
    Route::post('/approvals/{type}/{id}/reject',  [ApprovalController::class, 'reject'])->name('approvals.reject');

    // Project Schedule
    Route::get('/project-schedule',             [ScheduleController::class, 'projectSchedule'])->name('schedule.project');
    Route::get('/project-schedule/employee',    [ScheduleController::class, 'employeeSchedule'])->name('schedule.employee');
    Route::get('/project-schedule/resources',   [ScheduleController::class, 'resourcesDashboard'])->name('schedule.resources');
    Route::get('/project-schedule/data',        [ScheduleController::class, 'ganttData'])->name('schedule.gantt-data');

    // Invoicing — AR/AP control center
    Route::get('/invoices/dashboard',            [InvoiceController::class, 'dashboard'])->name('invoices.dashboard');
    Route::resource('invoices', InvoiceController::class);
    Route::get('/invoices/{invoice}/pdf',        [InvoiceController::class, 'generatePdf'])->name('invoices.pdf');
    Route::get('/invoices/{invoice}/preview',    [InvoiceController::class, 'previewPdf'])->name('invoices.preview');
    Route::post('/invoices/{invoice}/send',      [InvoiceController::class, 'sendEmail'])->name('invoices.send');
    Route::post('/invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');
    Route::post('/invoices/{invoice}/void',      [InvoiceController::class, 'voidInvoice'])->name('invoices.void');
    Route::post('/invoices/{invoice}/payment',   [InvoiceController::class, 'applyPayment'])->name('invoices.payment');

    // Admin routes (Admin only)
    Route::middleware('role:Admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/',                         [AdminController::class, 'index'])->name('index');
        Route::resource('users', UserController::class);
        Route::get('/approval-settings',        [AdminController::class, 'approvalSettings'])->name('approval-settings');
        Route::post('/approval-settings',       [AdminController::class, 'saveApprovalSettings'])->name('approval-settings.save');
        Route::get('/pto-policies',             [AdminController::class, 'ptoPolicies'])->name('pto-policies');
        Route::post('/pto-policies/{user}',     [AdminController::class, 'savePtoPolicy'])->name('pto-policies.save');
        Route::resource('holidays', \App\Http\Controllers\HolidayController::class);
        Route::get('/schedule-of-fees',         [AdminController::class, 'scheduleOfFees'])->name('schedule-of-fees');
        Route::post('/schedule-of-fees',        [AdminController::class, 'saveScheduleOfFees'])->name('schedule-of-fees.save');
        // Named fee schedules
        Route::get('/fee-schedules',                     [AdminController::class, 'feeSchedules'])->name('fee-schedules.index');
        Route::get('/fee-schedules/create',              [AdminController::class, 'createFeeSchedule'])->name('fee-schedules.create');
        Route::post('/fee-schedules',                    [AdminController::class, 'storeFeeSchedule'])->name('fee-schedules.store');
        Route::get('/fee-schedules/{feeSchedule}/edit',  [AdminController::class, 'editFeeSchedule'])->name('fee-schedules.edit');
        Route::put('/fee-schedules/{feeSchedule}',       [AdminController::class, 'updateFeeSchedule'])->name('fee-schedules.update');
        Route::delete('/fee-schedules/{feeSchedule}',    [AdminController::class, 'destroyFeeSchedule'])->name('fee-schedules.destroy');
        Route::resource('sectors',          \App\Http\Controllers\SectorController::class);
        Route::resource('regions',          \App\Http\Controllers\RegionController::class);
        Route::resource('work-types',       \App\Http\Controllers\WorkTypeController::class);
        Route::resource('contact-types',    \App\Http\Controllers\ContactTypeController::class);
        Route::resource('project-types',    ProjectTypeController::class);
        Route::resource('project-statuses', ProjectStatusController::class);
        Route::get('/system-settings',          [AdminController::class, 'systemSettings'])->name('system-settings');
        Route::post('/system-settings',         [AdminController::class, 'saveSystemSettings'])->name('system-settings.save');
        Route::get('/activity-log',             [AdminController::class, 'activityLog'])->name('activity-log');
        Route::get('/templates',                                            [ActivityTemplateAdminController::class, 'index'])->name('templates');
        Route::post('/templates',                                           [ActivityTemplateAdminController::class, 'store'])->name('templates.store');
        Route::get('/templates/{activityTemplate}',                         [ActivityTemplateAdminController::class, 'show'])->name('templates.show');
        Route::put('/templates/{activityTemplate}',                         [ActivityTemplateAdminController::class, 'update'])->name('templates.update');
        Route::delete('/templates/{activityTemplate}',                      [ActivityTemplateAdminController::class, 'destroy'])->name('templates.destroy');
        Route::post('/templates/{activityTemplate}/deliverables',           [ActivityTemplateAdminController::class, 'storeDeliverable'])->name('templates.deliverables.store');
        Route::put('/templates/deliverables/{deliverable}',                 [ActivityTemplateAdminController::class, 'updateDeliverable'])->name('templates.deliverables.update');
        Route::delete('/templates/deliverables/{deliverable}',              [ActivityTemplateAdminController::class, 'destroyDeliverable'])->name('templates.deliverables.destroy');
        Route::post('/templates/deliverables/{deliverable}/activities',     [ActivityTemplateAdminController::class, 'storeActivity'])->name('templates.activities.store');
        Route::put('/templates/activities/{activity}',                      [ActivityTemplateAdminController::class, 'updateActivity'])->name('templates.activities.update');
        Route::delete('/templates/activities/{activity}',                   [ActivityTemplateAdminController::class, 'destroyActivity'])->name('templates.activities.destroy');
        Route::post('/templates/activities/{activity}/tasks',               [ActivityTemplateAdminController::class, 'storeTask'])->name('templates.tasks.store');
        Route::put('/templates/tasks/{task}',                               [ActivityTemplateAdminController::class, 'updateTask'])->name('templates.tasks.update');
        Route::delete('/templates/tasks/{task}',                            [ActivityTemplateAdminController::class, 'destroyTask'])->name('templates.tasks.destroy');
        Route::post('/templates/deliverables/{deliverable}/tasks',          [ActivityTemplateAdminController::class, 'storeDeliverableTask'])->name('templates.deliverables.tasks.store');
    });

    // User profile
    Route::get('/profile',        [UserController::class, 'profile'])->name('profile');
    Route::put('/profile',        [UserController::class, 'updateProfile'])->name('profile.update');

    // ─── Kore AI ───────────────────────────────────────────────────────────────
    Route::prefix('ai')->name('ai.')->group(function () {
        Route::get('/',                            [AiController::class, 'index'])->name('index');
        Route::get('/new',                         [AiController::class, 'create'])->name('new');
        Route::get('/{conversation}',              [AiController::class, 'show'])->name('show');
        Route::post('/{conversation}/chat',        [AiController::class, 'chat'])->name('chat');
        Route::put('/{conversation}',              [AiController::class, 'update'])->name('update');
        Route::delete('/{conversation}',           [AiController::class, 'destroy'])->name('destroy');
    });

    // API endpoints for AJAX
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/projects/{project}/deliverables', [ProjectController::class, 'apiDeliverables'])->name('project.deliverables');
        Route::get('/deliverables/{deliverable}/milestones', [ProjectController::class, 'apiMilestones'])->name('deliverable.milestones');
        Route::get('/milestones/{milestone}/tasks', [ProjectController::class, 'apiTasks'])->name('milestone.tasks');
        Route::get('/company-enrichment', [CompanyController::class, 'nominatimSearch'])->name('company.enrichment');
        Route::get('/companies/{company}/vendor-code', [CompanyController::class, 'getVendorCode'])->name('company.vendor-code');
        Route::get('/dashboard/chart-data', [DashboardController::class, 'chartData'])->name('dashboard.chart');
        Route::get('/schedule/gantt',       [ScheduleController::class, 'ganttData'])->name('schedule.gantt');
        // Kore AI API
        Route::get('/ai/models',  [AiController::class, 'apiModels'])->name('ai.models');
        Route::get('/ai/status',  [AiController::class, 'apiStatus'])->name('ai.status');
    });
});
