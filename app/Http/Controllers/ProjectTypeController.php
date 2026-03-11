<?php

namespace App\Http\Controllers;

use App\Models\ProjectType;
use Illuminate\Http\Request;

class ProjectTypeController extends Controller
{
    public function index()
    {
        $types = ProjectType::withCount('projects')->orderBy('name')->get();
        return view('admin.lookups', ['type' => 'project-types', 'items' => $types, 'title' => 'Project Types', 'hasIsBillable' => true]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:project_types,name'],
            'is_billable' => ['nullable', 'boolean'],
        ]);
        $data['is_billable'] = $request->boolean('is_billable');
        ProjectType::create($data);
        return back()->with('success', 'Project type added.');
    }

    public function destroy(ProjectType $projectType)
    {
        if ($projectType->projects()->exists()) {
            return back()->with('error', 'Cannot delete — projects are using this type.');
        }
        $projectType->delete();
        return back()->with('success', 'Project type deleted.');
    }
}
