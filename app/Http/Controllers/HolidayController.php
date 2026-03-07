<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function index()
    {
        $year     = request('year', now()->year);
        $holidays = Holiday::where('year', $year)->orderBy('holiday_date')->get();
        $years    = Holiday::select('year')->distinct()->orderByDesc('year')->pluck('year');

        return view('admin.holidays', compact('holidays', 'year', 'years'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:100'],
            'holiday_date' => ['required', 'date'],
        ]);

        $data['year'] = (int) date('Y', strtotime($data['holiday_date']));

        Holiday::create($data);

        return back()->with('success', 'Holiday added.');
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete();
        return back()->with('success', 'Holiday removed.');
    }
}
