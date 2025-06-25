<?php

namespace App\Http\Controllers;

use App\Models\Timetable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TimetableController extends Controller
{
    public function index()
    {
        return Timetable::with('timetableEntries')->where('created_by', Auth::id())->get();
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'entries' => 'present|array',
            'entries.*.subject_id' => 'required|exists:subjects,id',
            'entries.*.lecturer_id' => 'required|exists:lecturers,id',
            'entries.*.day_id' => 'required|exists:days,id',
            'entries.*.time_slot_id' => 'required|exists:time_slots,id',
        ]);

        $timetable = DB::transaction(function () use ($validatedData, $request) {
            $timetable = Timetable::create([
                'name' => $validatedData['name'],
                'description' => $validatedData['description'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            if (isset($validatedData['entries'])) {
                foreach ($validatedData['entries'] as $entryData) {
                    $timetable->timetableEntries()->create($entryData);
                }
            }
            return $timetable;
        });

        return response()->json($timetable->load('timetableEntries'), 201);
    }

    public function show(Timetable $timetable)
    {
        if ($timetable->created_by !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        return $timetable->load('timetableEntries.subject', 'timetableEntries.lecturer', 'timetableEntries.day', 'timetableEntries.timeSlot');
    }

    public function update(Request $request, Timetable $timetable)
    {
        if ($timetable->created_by !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validatedData = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'entries' => 'present|array',
            'entries.*.subject_id' => 'required|exists:subjects,id',
            'entries.*.lecturer_id' => 'required|exists:lecturers,id',
            'entries.*.day_id' => 'required|exists:days,id',
            'entries.*.time_slot_id' => 'required|exists:time_slots,id',
        ]);

        DB::transaction(function () use ($timetable, $validatedData) {
            $timetable->update($validatedData);

            $timetable->timetableEntries()->delete();

            if (isset($validatedData['entries'])) {
                foreach ($validatedData['entries'] as $entryData) {
                    $timetable->timetableEntries()->create($entryData);
                }
            }
        });

        return response()->json($timetable->load('timetableEntries'));
    }

    public function destroy(Timetable $timetable)
    {
        if ($timetable->created_by !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $timetable->delete();

        return response()->json(null, 204);
    }
}
