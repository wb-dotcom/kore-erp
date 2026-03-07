<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index()
    {
        // Return events as JSON for FullCalendar
        if (request()->expectsJson() || request()->has('json')) {
            $events = CalendarEvent::where(function ($q) {
                $q->whereNull('user_id')->orWhere('user_id', auth()->id());
            })->get()->map(fn($e) => [
                'id'    => $e->id,
                'title' => $e->title,
                'start' => $e->start_date,
                'end'   => $e->end_date,
                'color' => $e->color ?? '#4c8bf5',
            ]);

            return response()->json($events);
        }

        return view('calendar.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'      => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['nullable', 'date'],
            'color'      => ['nullable', 'string'],
        ]);

        $data['user_id'] = auth()->id();

        CalendarEvent::create($data);

        return response()->json(['success' => true]);
    }
}
