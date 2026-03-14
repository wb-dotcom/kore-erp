<?php

namespace App\Http\Controllers;

use App\Models\ActivityTemplate;
use App\Models\ActivityTemplateDeliverable;
use App\Models\ActivityTemplateActivity;
use App\Models\ActivityTemplateTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ActivityTemplateImportController extends Controller
{
    // ── Show upload form ────────────────────────────────────────────────────

    public function form()
    {
        $workTypes    = \App\Models\WorkType::orderBy('name')->get();
        $billingTypes = ActivityTemplate::BILLING_TYPES;
        $billingCycles= ActivityTemplate::BILLING_CYCLES;

        return view('admin.activity-templates.import', compact('workTypes', 'billingTypes', 'billingCycles'));
    }

    // ── Download sample spreadsheet ─────────────────────────────────────────

    public function sample()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Project Plan');

        // ── Headers ─────────────────────────────────────────────────────────
        $headers = ['ID', 'Type', 'Name', 'Description', 'Parent ID', 'Depends On', 'Budget Hours', 'Assigned Role', 'Start Day', 'End Day'];
        $sheet->fromArray($headers, null, 'A1');

        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '2563EB']]],
        ];
        $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(22);

        // ── Sample data ──────────────────────────────────────────────────────
        $rows = [
            // [ID, Type, Name, Description, Parent ID, Depends On, Budget Hrs, Assigned Role, Start Day, End Day]
            ['D1', 'DELIVERABLE', 'Discovery & Requirements', 'Kickoff and requirements gathering phase', '',   '',    40,  '',                  '',  ''],
            ['M1', 'MILESTONE',   'Kickoff',                  'Project kickoff meeting and setup',         'D1', '',    8,   'Project Manager',   1,   3],
            ['T1', 'TASK',        'Stakeholder interviews',   'Conduct interviews with key stakeholders',  'M1', '',    6,   'Business Analyst',  1,   2],
            ['T2', 'TASK',        'Requirements document',    'Draft and review requirements doc',         'M1', 'T1',  2,   'Business Analyst',  3,   3],
            ['M2', 'MILESTONE',   'Scope Definition',         'Define project scope and sign-off',         'D1', 'M1',  20,  'Project Manager',   4,   10],
            ['T3', 'TASK',        'Scope document draft',     '',                                          'M2', 'T2',  12,  'Business Analyst',  4,   8],
            ['T4', 'TASK',        'Client sign-off',          '',                                          'M2', 'T3',  4,   'Project Manager',   9,   10],
            ['D2', 'DELIVERABLE', 'Design Phase',             'UI/UX and architecture design',             'D1', 'D1',  80,  '',                  '',  ''],
            ['M3', 'MILESTONE',   'Wireframes',               'Low-fidelity wireframes',                   'D2', 'D1',  20,  'Designer',          11,  20],
            ['T5', 'TASK',        'Wireframe key screens',    '',                                          'M3', 'T4',  16,  'Designer',          11,  18],
            ['T6', 'TASK',        'Wireframe review session', '',                                          'M3', 'T5',  4,   'Project Manager',   19,  20],
            ['M4', 'MILESTONE',   'High-Fidelity Design',     'Pixel-perfect design files',                'D2', 'M3',  40,  'Designer',          21,  35],
            ['T7', 'TASK',        'Design all screens',       '',                                          'M4', 'T6',  32,  'Designer',          21,  32],
            ['T8', 'TASK',        'Design QA review',         '',                                          'M4', 'T7',  8,   'Project Manager',   33,  35],
            ['D3', 'DELIVERABLE', 'Development',              'Full development phase',                    'D2', 'D2',  200, '',                  '',  ''],
            ['M5', 'MILESTONE',   'Backend Development',      '',                                          'D3', 'D2',  100, 'Backend Developer', 36,  70],
            ['T9', 'TASK',        'Database schema',          '',                                          'M5', 'T8',  20,  'Backend Developer', 36,  42],
            ['T10','TASK',        'API endpoints',            '',                                          'M5', 'T9',  60,  'Backend Developer', 43,  65],
            ['T11','TASK',        'API testing',              '',                                          'M5', 'T10', 20,  'QA Engineer',       66,  70],
            ['M6', 'MILESTONE',   'Frontend Development',     '',                                          'D3', 'M5',  100, 'Frontend Developer',71,  100],
            ['T12','TASK',        'Component build-out',      '',                                          'M6', 'T9',  70,  'Frontend Developer',71,  95],
            ['T13','TASK',        'Integration & QA',         '',                                          'M6', 'T12', 30,  'QA Engineer',       96,  100],
        ];

        // Row colors by type
        $typeColors = [
            'DELIVERABLE' => ['row' => 'EFF6FF', 'font' => '1E40AF', 'bold' => true],
            'MILESTONE'   => ['row' => 'F0FDF4', 'font' => '166534', 'bold' => true],
            'TASK'        => ['row' => 'FFFFFF', 'font' => '374151', 'bold' => false],
        ];

        foreach ($rows as $i => $row) {
            $rowNum = $i + 2;
            $sheet->fromArray($row, null, 'A' . $rowNum);
            $type = $row[1];
            $style = $typeColors[$type] ?? $typeColors['TASK'];
            $sheet->getStyle("A{$rowNum}:J{$rowNum}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $style['row']]],
                'font' => ['bold' => $style['bold'], 'color' => ['rgb' => $style['font']], 'size' => 9],
            ]);
            $sheet->getRowDimension($rowNum)->setRowHeight(18);
        }

        // ── Column widths ────────────────────────────────────────────────────
        $widths = ['A' => 7, 'B' => 14, 'C' => 32, 'D' => 36, 'E' => 11, 'F' => 16, 'G' => 14, 'H' => 20, 'I' => 11, 'J' => 10];
        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        // ── Instructions sheet ───────────────────────────────────────────────
        $info = $spreadsheet->createSheet();
        $info->setTitle('Instructions');
        $infoRows = [
            ['KORE ERP — Project Template Import Format'],
            [''],
            ['COLUMNS'],
            ['ID',          'Your unique row identifier (e.g. D1, M1, T1). Used for dependencies.'],
            ['Type',        'One of: DELIVERABLE, MILESTONE, or TASK  (exact text, any case)'],
            ['Name',        'Required. The name of the item.'],
            ['Description', 'Optional free-text description.'],
            ['Parent ID',   'MILESTONE → ID of its parent DELIVERABLE.  TASK → ID of its parent MILESTONE (or DELIVERABLE if no milestone).'],
            ['Depends On',  'Comma-separated IDs this item depends on (same type only: deliverable→deliverable, etc.)'],
            ['Budget Hours','Numeric. For MILESTONE = budgeted hours. For TASK = estimated hours. Ignored for DELIVERABLE (computed).'],
            ['Assigned Role','Free text role label, e.g. "Engineer", "Project Manager". Optional.'],
            ['Start Day',   'Relative start day from project start (integer). Used for MILESTONE and TASK only.'],
            ['End Day',     'Relative end day from project start (integer). Used for MILESTONE and TASK only.'],
            [''],
            ['RULES'],
            ['• You MUST import at least one DELIVERABLE row.'],
            ['• Every MILESTONE must reference a valid Parent ID pointing to a DELIVERABLE.'],
            ['• Every TASK must reference a valid Parent ID pointing to a MILESTONE or DELIVERABLE.'],
            ['• Depends On can be left blank. Reference only IDs of the same type.'],
            ['• ID values are only used during import for linking — they are not stored.'],
            ['• Rows with an empty Name column are skipped.'],
            ['• Extra columns beyond column J are ignored.'],
            [''],
            ['GOOGLE SHEETS USERS'],
            ['• File > Download > Microsoft Excel (.xlsx)  — then upload that file.'],
        ];
        foreach ($infoRows as $idx => $infoRow) {
            $info->fromArray($infoRow, null, 'A' . ($idx + 1));
        }
        $info->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => '1E3A5F']]]);
        $info->getStyle('A3')->applyFromArray(['font' => ['bold' => true, 'size' => 10]]);
        $info->getStyle('A15')->applyFromArray(['font' => ['bold' => true, 'size' => 10]]);
        $info->getStyle('A23')->applyFromArray(['font' => ['bold' => true, 'size' => 10]]);
        $info->getColumnDimension('A')->setWidth(22);
        $info->getColumnDimension('B')->setWidth(80);

        // ── Stream download ──────────────────────────────────────────────────
        $writer = new Xlsx($spreadsheet);
        $filename = 'kore-template-import-sample.xlsx';

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    // ── Process uploaded file ───────────────────────────────────────────────

    public function import(Request $request)
    {
        $request->validate([
            'file'         => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'name'         => 'required|string|max:255',
            'billing_type' => 'nullable|string|max:30',
            'billing_cycle'=> 'nullable|string|max:30',
            'work_type_id' => 'nullable|integer|exists:work_types,id',
            'description'  => 'nullable|string|max:1000',
        ]);

        // ── Parse spreadsheet ────────────────────────────────────────────────
        try {
            $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        } catch (\Exception $e) {
            return back()->withErrors(['file' => 'Could not read the file. Make sure it is a valid Excel or CSV file.'])->withInput();
        }

        // Find the "Project Plan" sheet or use the first sheet
        $sheet = null;
        foreach ($spreadsheet->getAllSheets() as $s) {
            if (strtolower(trim($s->getTitle())) === 'project plan') {
                $sheet = $s;
                break;
            }
        }
        $sheet = $sheet ?? $spreadsheet->getActiveSheet();

        $rawRows = $sheet->toArray(null, true, true, false); // 0-indexed
        if (count($rawRows) < 2) {
            return back()->withErrors(['file' => 'The spreadsheet appears to be empty.'])->withInput();
        }

        // Skip header row; collect data rows
        $dataRows = array_slice($rawRows, 1);

        // Normalise: trim strings, map columns by position
        // [0]=ID [1]=Type [2]=Name [3]=Description [4]=ParentID [5]=DependsOn [6]=Hours [7]=Role [8]=StartDay [9]=EndDay
        $parsed = [];
        foreach ($dataRows as $row) {
            $name = trim((string)($row[2] ?? ''));
            if ($name === '') continue; // skip blank rows

            $type = strtoupper(trim((string)($row[1] ?? '')));
            if (!in_array($type, ['DELIVERABLE', 'MILESTONE', 'TASK'])) continue;

            $parsed[] = [
                'uid'        => trim((string)($row[0] ?? '')),
                'type'       => $type,
                'name'       => $name,
                'description'=> trim((string)($row[3] ?? '')),
                'parent_uid' => trim((string)($row[4] ?? '')),
                'depends_on' => array_filter(array_map('trim', explode(',', (string)($row[5] ?? '')))),
                'hours'      => is_numeric($row[6]) ? (float)$row[6] : 0,
                'role'       => trim((string)($row[7] ?? '')),
                'start_day'  => is_numeric($row[8]) ? (int)$row[8] : null,
                'end_day'    => is_numeric($row[9]) ? (int)$row[9] : null,
            ];
        }

        if (empty($parsed)) {
            return back()->withErrors(['file' => 'No valid rows found. Check that your Type column contains DELIVERABLE, MILESTONE, or TASK.'])->withInput();
        }

        $deliverables = array_filter($parsed, fn($r) => $r['type'] === 'DELIVERABLE');
        if (empty($deliverables)) {
            return back()->withErrors(['file' => 'At least one DELIVERABLE row is required.'])->withInput();
        }

        // ── Create template and all rows in a transaction ────────────────────
        try {
            DB::transaction(function () use ($request, $parsed, &$template) {
                $template = ActivityTemplate::create([
                    'name'          => $request->name,
                    'description'   => $request->description,
                    'billing_type'  => $request->billing_type  ?: null,
                    'billing_cycle' => $request->billing_cycle ?: null,
                    'work_type_id'  => $request->work_type_id  ?: null,
                    'created_by'    => auth()->id(),
                ]);

                // Maps: uid → newly created model ID
                $delMap  = []; // uid → ActivityTemplateDeliverable->id
                $actMap  = []; // uid → ActivityTemplateActivity->id
                $taskMap = []; // uid → ActivityTemplateTask->id

                $delSort  = 1;
                $actSort  = [];  // keyed by deliverable DB id
                $taskSort = [];  // keyed by parent DB id (activity or deliverable)

                // ── Pass 1: create rows without dependencies ─────────────────
                foreach ($parsed as $row) {
                    if ($row['type'] === 'DELIVERABLE') {
                        $del = ActivityTemplateDeliverable::create([
                            'activity_template_id' => $template->id,
                            'name'                 => $row['name'],
                            'description'          => $row['description'] ?: null,
                            'max_hours'            => $row['hours'] > 0 ? $row['hours'] : null,
                            'sort_order'           => $delSort++,
                        ]);
                        $delMap[$row['uid']] = $del->id;

                    } elseif ($row['type'] === 'MILESTONE') {
                        $parentDelId = $delMap[$row['parent_uid']] ?? null;
                        if (!$parentDelId) continue; // skip orphan milestones

                        $actSort[$parentDelId] = ($actSort[$parentDelId] ?? 0) + 1;
                        $act = ActivityTemplateActivity::create([
                            'activity_template_deliverable_id' => $parentDelId,
                            'name'                 => $row['name'],
                            'description'          => $row['description'] ?: null,
                            'budgeted_hours'       => $row['hours'],
                            'assigned_role'        => $row['role'] ?: null,
                            'relative_start_day'   => $row['start_day'],
                            'relative_end_day'     => $row['end_day'],
                            'sort_order'           => $actSort[$parentDelId],
                        ]);
                        $actMap[$row['uid']] = $act->id;

                    } elseif ($row['type'] === 'TASK') {
                        // Parent can be a milestone or a deliverable
                        $parentActId = $actMap[$row['parent_uid']] ?? null;
                        $parentDelId = $parentActId ? null : ($delMap[$row['parent_uid']] ?? null);

                        if (!$parentActId && !$parentDelId) continue; // skip orphan tasks

                        $sortKey = $parentActId ? "a{$parentActId}" : "d{$parentDelId}";
                        $taskSort[$sortKey] = ($taskSort[$sortKey] ?? 0) + 1;

                        $task = ActivityTemplateTask::create([
                            'activity_template_activity_id'    => $parentActId,
                            'activity_template_deliverable_id' => $parentDelId,
                            'name'                 => $row['name'],
                            'description'          => $row['description'] ?: null,
                            'estimated_hours'      => $row['hours'],
                            'assigned_role'        => $row['role'] ?: null,
                            'relative_due_day'     => $row['end_day'],
                            'sort_order'           => $taskSort[$sortKey],
                        ]);
                        $taskMap[$row['uid']] = $task->id;
                    }
                }

                // ── Pass 2: wire up dependency IDs ───────────────────────────
                foreach ($parsed as $row) {
                    if (empty($row['depends_on']) || empty($row['uid'])) continue;

                    // Only first dependency used (DB schema is single depends_on_id per row)
                    $depUid = $row['depends_on'][0];

                    if ($row['type'] === 'DELIVERABLE' && isset($delMap[$row['uid']]) && isset($delMap[$depUid])) {
                        ActivityTemplateDeliverable::where('id', $delMap[$row['uid']])
                            ->update(['depends_on_deliverable_id' => $delMap[$depUid]]);

                    } elseif ($row['type'] === 'MILESTONE' && isset($actMap[$row['uid']]) && isset($actMap[$depUid])) {
                        ActivityTemplateActivity::where('id', $actMap[$row['uid']])
                            ->update(['depends_on_activity_id' => $actMap[$depUid]]);

                    } elseif ($row['type'] === 'TASK' && isset($taskMap[$row['uid']]) && isset($taskMap[$depUid])) {
                        ActivityTemplateTask::where('id', $taskMap[$row['uid']])
                            ->update(['depends_on_task_id' => $taskMap[$depUid]]);
                    }
                }

                // ── Recalculate total hours on template ──────────────────────
                $template->recalculateHours();
            });
        } catch (\Exception $e) {
            return back()->withErrors(['file' => 'Import failed: ' . $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.templates.show', $template)
            ->with('success', 'Template imported successfully from spreadsheet.');
    }
}
