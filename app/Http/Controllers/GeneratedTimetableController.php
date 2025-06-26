<?php

namespace App\Http\Controllers;

use App\Models\Day;
use App\Models\GeneratedTimetable;
use App\Models\Lecturer;
use App\Models\Section;
use App\Models\Subject;
use App\Models\TimetablePreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * @OA\Schema(
 *     schema="GeneratedTimetable",
 *     type="object",
 *     title="Generated Timetable",
 *     @OA\Property(property="id", type="integer", readOnly="true"),
 *     @OA\Property(property="user_id", type="integer", readOnly="true"),
 *     @OA\Property(property="timetable", type="object"),
 *     @OA\Property(property="active", type="boolean"),
 *     @OA\Property(property="created_at", type="string", format="date-time", readOnly="true"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", readOnly="true")
 * )
 */
class GeneratedTimetableController extends Controller
{
    /**
     * @OA\Post(
     *      path="/api/generate-timetable",
     *      operationId="generateTimetable",
     *      tags={"Generated Timetables"},
     *      summary="Generate a new timetable based on user preferences",
     *      description="Returns a newly generated timetable",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=201,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/GeneratedTimetable")
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated"
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Failed to generate timetable"
     *      )
     * )
     */
    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'preferences' => 'required|array',
            'preferences.subjects' => 'required|array',
            'preferences.subjects.*' => 'exists:subjects,id',
            'preferences.sections' => 'sometimes|array',
            'preferences.sections.*' => 'exists:sections,id',
            'preferences.lecturers' => 'sometimes|array',
            'preferences.lecturers.*' => 'exists:lecturers,id',
            'preferences.days' => 'sometimes|array',
            'preferences.days.*' => 'exists:days,id',
            'preferences.start_time' => 'sometimes|date_format:H:i',
            'preferences.end_time' => 'sometimes|date_format:H:i|after:preferences.start_time',
            'preferences.max_days_per_week' => 'sometimes|integer|min:1|max:7',
            'preferences.schedule_style' => 'sometimes|string|in:compact,spaced_out',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = $request->user();
        $preferences = $validator->validated()['preferences'];

        // 1. Collect class data from Section model, filtering based on user input
        $classQuery = Section::with(['subject', 'lecturer']);

        if (!empty($preferences['sections'])) {
            // If user selected specific sections, use only those
            $classQuery->whereIn('id', $preferences['sections']);
        } else {
            // Otherwise, use all sections from the selected subjects
            $classQuery->whereIn('subject_id', $preferences['subjects']);
        }

        $classes = $classQuery->get();

        if ($classes->isEmpty()) {
            return response()->json(['message' => 'No classes found for the selected criteria.'], 422);
        }

        $subjectNames = Subject::whereIn('id', $preferences['subjects'])->pluck('name')->all();
        $lecturerNames = isset($preferences['lecturers']) ? Lecturer::whereIn('id', $preferences['lecturers'])->pluck('name')->all() : [];
        $dayNames = isset($preferences['days']) ? Day::whereIn('id', $preferences['days'])->pluck('name')->all() : [];

        // If specific sections are chosen, other preferences can create impossible constraints.
        // For example, selecting a section on Monday but preferring Tuesday.
        // We'll clear these conflicting preferences if sections are explicitly selected.
        if (!empty($preferences['sections'])) {
            $subjectNames = []; // Also clear subjects to avoid conflicts
            $lecturerNames = [];
            $dayNames = [];
            $preferences['start_time'] = null;
            $preferences['end_time'] = null;
        }

        // 3. Transform preferences into the format expected by the Python script
        $scriptPreferences = [
            'subjects' => $subjectNames,
            'preferred_lecturers' => $lecturerNames,
            'preferred_days' => $dayNames,
            'preferred_start' => isset($preferences['start_time']) ? $preferences['start_time'] . ':00' : null,
            'preferred_end' => isset($preferences['end_time']) ? $preferences['end_time'] . ':00' : null,
            'max_days' => $preferences['max_days_per_week'] ?? 5,
            'schedule_style' => $preferences['schedule_style'] ?? 'compact',
            'enforce_ties' => true, // Assuming student view, so ties are enforced
        ];

        // 4. Map class data into the format expected by the Python script
        $classesData = $classes->map(function ($section) {
            $activity = 'Lecture';
            if (str_starts_with($section->section_number, 'T')) {
                $activity = 'Tutorial';
            }
            return [
                'id' => $section->id,
                'code' => $section->subject->code,
                'subject' => $section->subject->name,
                'activity' => $activity,
                'section' => $section->section_number,
                'days' => $section->day_of_week,
                'start_time' => $section->start_time,
                'end_time' => $section->end_time,
                'venue' => $section->venue,
                'tied_to' => [],
                'lecturer' => $section->lecturer ? $section->lecturer->name : 'N/A',
            ];
        });

        // 5. Prepare the final input data for the script
        $inputData = [
            'classes' => $classesData->toArray(),
            'preferences' => $scriptPreferences,
        ];

        // 6. Execute Python script
        $pythonExecutable = env('PYTHON_EXECUTABLE', '/Users/biehatieha/code/yaya/timetable-api/.venv/bin/python');
        $scriptPath = app_path('Http/Controllers/TimetableGenerator.py');

        $process = new Process([$pythonExecutable, $scriptPath]);
        $process->setInput(json_encode($inputData));
        $process->run();

        if (!$process->isSuccessful()) {
            $stdout = $process->getOutput();
            $stderr = $process->getErrorOutput();
            $exitCode = $process->getExitCode();

            // Log the detailed error for debugging
            \Log::error('Timetable generator script failed.', [
                'exit_code' => $exitCode,
                'stdout' => $stdout,
                'stderr' => $stderr,
                'input' => $inputData,
            ]);

            // The Python script is expected to send JSON errors to stdout.
            // If stdout has a valid JSON error message, use it.
            if (!empty($stdout)) {
                $output = json_decode($stdout, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($output['message'])) {
                    // Return the script's specific error message.
                    return response()->json(['message' => $output['message'], 'details' => $stderr], 422);
                }
            }

            // If stdout is not a JSON error, it means the script crashed unexpectedly.
            // In this case, we return a generic 500 error with the raw stderr.
            return response()->json([
                'message' => 'The timetable generation process failed unexpectedly.',
                'error_details' => $stderr, // The raw error from the script
                'exit_code' => $exitCode,
            ], 500);
        }

        $rawOutput = $process->getOutput();
        $output = json_decode($rawOutput, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['message' => 'Failed to decode timetable from generator.', 'raw_output' => $rawOutput], 500);
        }

        if (isset($output['status']) && $output['status'] === 'error') {
            return response()->json(['message' => $output['message']], 422);
        }

        // Deactivate any existing active timetables for the user
        GeneratedTimetable::where('user_id', $user->id)->update(['active' => false]);

        // 7. Save the new timetable
        $generatedTimetable = GeneratedTimetable::create([
            'user_id' => $user->id,
            'timetable' => $output['timetable'], // Access the nested timetable data
            'active' => true,
        ]);

        return response()->json($generatedTimetable, 201);
    }

    /**
     * @OA\Get(
     *      path="/api/my-timetable",
     *      operationId="getActiveGeneratedTimetable",
     *      tags={"Generated Timetables"},
     *      summary="Get the active generated timetable for the authenticated user",
     *      description="Returns the active generated timetable",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/GeneratedTimetable")
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="No active timetable found"
     *      )
     * )
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $timetable = GeneratedTimetable::where('user_id', $user->id)->where('active', true)->firstOrFail();

        return response()->json($timetable);
    }
}
