<?php

namespace App\Http\Controllers;

use App\Models\GeneratedTimetable;
use App\Models\Section; // Add this line
use App\Models\TimetablePreference;
use Illuminate\Http\Request;
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
        $user = $request->user();
        $preferences = $request->input('preferences');

        if (!$preferences) {
            $preferenceModel = TimetablePreference::where('user_id', $user->id)->first();
            if ($preferenceModel) {
                $preferences = $preferenceModel->preferences;
                if (is_string($preferences)) {
                    $preferences = json_decode($preferences, true);
                }
            } else {
                return response()->json(['message' => 'No preferences found for the user.'], 404);
            }
        }

        // 1. Collect class data from Section model
        $classes = Section::with(['subject', 'lecturer'])->get();

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

        // 2. Prepare data for Python script
        $inputData = [
            'classes' => $classesData->toArray(),
            'preferences' => $preferences,
        ];

        // 3. Execute Python script
        $pythonExecutable = env('PYTHON_EXECUTABLE', base_path('.venv/bin/python'));
        $scriptPath = app_path('Http/Controllers/TimetableGenerator.py');

        $process = new Process([$pythonExecutable, $scriptPath]);
        $process->setInput(json_encode($inputData));
        $process->run();

        if (!$process->isSuccessful()) {
            $errorOutput = $process->getOutput(); // Python script writes JSON error to stdout
            if (!empty($errorOutput)) {
                $output = json_decode($errorOutput, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($output['message'])) {
                    return response()->json(['message' => $output['message']], 422);
                }
            }
            throw new ProcessFailedException($process);
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

        // 4. Save the new timetable
        $generatedTimetable = GeneratedTimetable::create([
            'user_id' => $user->id,
            'timetable' => $output,
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
