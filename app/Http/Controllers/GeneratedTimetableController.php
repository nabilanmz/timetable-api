<?php

namespace App\Http\Controllers;

use App\Models\GeneratedTimetable;
use App\Models\TimetableEntry;
use App\Models\TimetablePreference;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class GeneratedTimetableController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        $preferences = TimetablePreference::where('user_id', $user->id)->firstOrFail();

        // 1. Collect class data from TimetableEntry
        $classes = TimetableEntry::with(['subject', 'lecturer', 'day', 'timeSlot'])->get();

        $classesData = $classes->map(function ($entry) {
            return [
                'code' => $entry->subject->code,
                'course' => $entry->subject->name,
                'activity' => $entry->activity,
                'section' => $entry->section,
                'days' => $entry->day->name,
                'start_time' => $entry->timeSlot->start_time,
                'end_time' => $entry->timeSlot->end_time,
                'venue' => $entry->venue,
                'tied_to' => $entry->tied_to ? explode(',', $entry->tied_to) : [],
                'lecturer' => $entry->lecturer->name,
            ];
        });

        $prefs = $preferences->preferences;
        if (is_string($prefs)) {
            $prefs = json_decode($prefs, true);
        }

        // 2. Prepare data for Python script
        $inputData = [
            'classes' => $classesData,
            'preferences' => $prefs,
        ];

        // 3. Execute Python script
        $pythonExecutable = env('PYTHON_EXECUTABLE', 'python3');

        $process = new Process([
            $pythonExecutable,
            base_path('app/Http/Controllers/TimetableGenerator.py'),
        ]);
        $process->setInput(json_encode($inputData));
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $rawOutput = $process->getOutput();
        $output = json_decode($rawOutput, true);

        if ($output === null && json_last_error() !== JSON_ERROR_NONE) {
            // JSON decoding failed. Throw an exception with detailed info.
            throw new \Exception(
                'Failed to decode JSON from Python script. Error: ' . json_last_error_msg() . 
                ". Raw output: " . $rawOutput
            );
        }

        if (isset($output['status']) && $output['status'] === 'error') {
            return response()->json(['message' => 'Failed to generate timetable: ' . ($output['message'] ?? 'Unknown error')], 500);
        }

        if (!isset($output['timetable'])) {
            throw new \Exception('Python script returned success status but no timetable. Raw output: ' . $rawOutput);
        }

        // 4. Save the generated timetable
        GeneratedTimetable::where('user_id', $user->id)->update(['active' => false]);

        $generatedTimetable = GeneratedTimetable::create([
            'user_id' => $user->id,
            'timetable' => $output['timetable'],
            'active' => true,
        ]);

        return response()->json($generatedTimetable, 201);
    }

    public function show(Request $request)
    {
        $user = $request->user();
        $timetable = GeneratedTimetable::where('user_id', $user->id)->where('active', true)->firstOrFail();

        return response()->json($timetable);
    }
}
