<?php

namespace App\Http\Controllers;

use App\Models\Region;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    public function index()
    {
        $regions = Region::withCount('companies')->orderBy('name')->get();
        return view('admin.lookups', ['type' => 'regions', 'items' => $regions, 'title' => 'Regions']);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:regions,name']]);
        Region::create($data);
        return back()->with('success', 'Region added.');
    }

    public function destroy(Region $region)
    {
        $region->delete();
        return back()->with('success', 'Region deleted.');
    }
}
