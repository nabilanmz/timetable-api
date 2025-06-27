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
            'preferences.days' => 'required|array',
            'preferences.days.*' => 'exists:days,id',
            'preferences.start_time' => 'required|date_format:H:i',
            'preferences.end_time' => 'required|date_format:H:i|after:preferences.start_time',
            'preferences.enforce_ties' => 'required|string|in:yes,no',
            'preferences.lecturers' => 'sometimes|array',
            'preferences.lecturers.*' => 'exists:lecturers,id',
            'preferences.mode' => 'required|integer|in:1,2', // 1=compact, 2=spaced_out
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = $request->user();
        $preferences = $validator->validated()['preferences'];

        // 1. Generate available sections based on user criteria
        $availableSections = $this->generateAvailableSections($preferences);

        if (empty($availableSections)) {
            return response()->json(['message' => 'No valid sections can be generated for the selected criteria.'], 422);
        }

        // 2. Prepare subject and lecturer names for preferences
        $subjectNames = Subject::whereIn('id', $preferences['subjects'])->pluck('name')->all();
        $lecturerNames = isset($preferences['lecturers']) ? Lecturer::whereIn('id', $preferences['lecturers'])->pluck('name')->all() : [];
        $dayNames = Day::whereIn('id', $preferences['days'])->pluck('name')->all();

        // 3. Transform preferences into the format expected by the Python script
        $scheduleStyle = $preferences['mode'] == 1 ? 'compact' : 'spaced_out';
        $enforceTies = $preferences['enforce_ties'] === 'yes';
        
        $scriptPreferences = [
            'subjects' => $subjectNames,
            'preferred_lecturers' => $lecturerNames,
            'preferred_days' => $dayNames,
            'preferred_start' => $preferences['start_time'] . ':00',
            'preferred_end' => $preferences['end_time'] . ':00',
            'schedule_style' => $scheduleStyle,
            'enforce_ties' => $enforceTies,
        ];

        // 4. Map class data into the format expected by the Python script
        $classesData = collect($availableSections)->map(function ($section) {
            // Use the activity field from the database
            $activity = $section->activity ?? 'Lecture';
            
            // Get tied sections - use the tied_to field if available
            $tiedTo = [];
            if ($section->tied_to && is_array($section->tied_to)) {
                $tiedTo = $section->tied_to;
            } elseif ($section->tied_to && is_string($section->tied_to)) {
                // Handle comma-separated tied sections
                $tiedTo = array_map('trim', explode(',', $section->tied_to));
                $tiedTo = array_filter($tiedTo); // Remove empty values
            }
            
            return [
                'code' => $section->subject->code,
                'subject' => $section->subject->name,
                'activity' => $activity,
                'section' => $section->section_number,
                'days' => $section->day_of_week,
                'start_time' => $section->start_time,
                'end_time' => $section->end_time,
                'venue' => $section->venue ?? 'TBD',
                'tied_to' => $tiedTo,
                'lecturer' => $section->lecturer ? $section->lecturer->name : 'TBD',
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

    /**
     * Generate all available sections based on user preferences
     * This creates the dynamic sections that will be used for timetable optimization
     */
    private function generateAvailableSections($preferences)
    {
        // Get base query for sections filtered by user criteria
        $query = Section::with(['subject', 'lecturer'])
            ->whereHas('subject', function ($q) use ($preferences) {
                $q->whereIn('id', $preferences['subjects']);
            });

        // Filter by preferred days if specified
        if (!empty($preferences['days'])) {
            $dayNames = Day::whereIn('id', $preferences['days'])->pluck('name')->toArray();
            $query->whereIn('day_of_week', $dayNames);
        }

        // Filter by time range
        $query->where('start_time', '>=', $preferences['start_time'] . ':00')
              ->where('end_time', '<=', $preferences['end_time'] . ':00');

        // Filter by preferred lecturers if specified
        if (!empty($preferences['lecturers'])) {
            $query->whereIn('lecturer_id', $preferences['lecturers']);
        }

        // Get all matching sections
        $sections = $query->get();

        // Group sections by subject to ensure we have complete course offerings
        $sectionsBySubject = $sections->groupBy('subject_id');
        $availableSections = collect();

        foreach ($sectionsBySubject as $subjectId => $subjectSections) {
            // For now, treat all sections as valid options for the genetic algorithm
            // The algorithm will determine the best combination
            $availableSections = $availableSections->merge($subjectSections);
        }

        return $availableSections->unique('id')->values()->all();
    }
}
