<?php

namespace App\Http\Controllers;

use App\Models\Sector;
use Illuminate\Http\Request;

class SectorController extends Controller
{
    public function index()
    {
        $sectors = Sector::withCount('companies')->orderBy('name')->get();
        return view('admin.lookups', ['type' => 'sectors', 'items' => $sectors, 'title' => 'Sectors']);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:sectors,name']]);
        Sector::create($data);
        return back()->with('success', 'Sector added.');
    }

    public function destroy(Sector $sector)
    {
        $sector->delete();
        return back()->with('success', 'Sector deleted.');
    }
}
