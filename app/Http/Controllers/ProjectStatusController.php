<?php

namespace App\Http\Controllers;

use App\Models\ProjectStatus;
use Illuminate\Http\Request;

class ProjectStatusController extends Controller
{
    public function index()
    {
        $statuses = ProjectStatus::withCount('projects')->orderBy('name')->get();
        return view('admin.lookups', ['type' => 'project-statuses', 'items' => $statuses, 'title' => 'Project Statuses']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:project_statuses,name'],
        ]);
        ProjectStatus::create($data);
        return back()->with('success', 'Project status added.');
    }

    public function destroy(ProjectStatus $projectStatus)
    {
        if ($projectStatus->projects()->exists()) {
            return back()->with('error', 'Cannot delete — projects are using this status.');
        }
        $projectStatus->delete();
        return back()->with('success', 'Project status deleted.');
    }
}
