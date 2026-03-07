<?php

namespace App\Http\Controllers;

use App\Models\WorkType;
use Illuminate\Http\Request;

class WorkTypeController extends Controller
{
    public function index()
    {
        $workTypes = WorkType::withCount('proposals')->orderBy('name')->get();
        return view('admin.lookups', ['type' => 'work-types', 'items' => $workTypes, 'title' => 'Work Types']);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:work_types,name']]);
        WorkType::create($data);
        return back()->with('success', 'Work type added.');
    }

    public function destroy(WorkType $workType)
    {
        $workType->delete();
        return back()->with('success', 'Work type deleted.');
    }
}
