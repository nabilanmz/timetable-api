<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Lecturer;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Timetable Preferences", description="Endpoints for timetable preference options")
 */
class PreferenceOptionsController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/preference-options",
     *      operationId="getPreferenceOptions",
     *      tags={"Timetable Preferences"},
     *      summary="Get available preference options",
     *      description="Returns available subjects and lecturers for timetable preferences",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(
     *                  property="subjects",
     *                  type="array",
     *                  @OA\Items(
     *                      type="object",
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="name", type="string")
     *                  )
     *              ),
     *              @OA\Property(
     *                  property="lecturers",
     *                  type="array",
     *                  @OA\Items(
     *                      type="object",
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="name", type="string")
     *                  )
     *              )
     *          )
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated"
     *      )
     * )
     */
    public function index()
    {
        $subjects = Subject::whereHas('sections')->get(['id', 'name']);
        $lecturers = Lecturer::all(['id', 'name']);

        return response()->json([
            'subjects' => $subjects,
            'lecturers' => $lecturers,
        ]);
    }
    
    /**
     * @OA\Get(
     *      path="/api/available-timeslots",
     *      operationId="getAvailableTimeSlots",
     *      tags={"Timetable Preferences"},
     *      summary="Get available time slots for selected subjects",
     *      description="Returns time slots that have sections for the specified subjects",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="subject_ids",
     *          in="query",
     *          description="Comma-separated list of subject IDs",
     *          required=true,
     *          @OA\Schema(type="string", example="1,2,3")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="array",
     *              @OA\Items(
     *                  type="object",
     *                  @OA\Property(property="id", type="integer"),
     *                  @OA\Property(property="start_time", type="string", format="time"),
     *                  @OA\Property(property="end_time", type="string", format="time")
     *              )
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad request - subject_ids parameter required"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated"
     *      )
     * )
     */
    public function getAvailableTimeSlots(Request $request)
    {
        // Validate that subject_ids parameter is provided
        $subjectIds = $request->query('subject_ids');
        
        if (empty($subjectIds)) {
            return response()->json([
                'error' => 'subject_ids parameter is required',
                'message' => 'Please provide a comma-separated list of subject IDs'
            ], 400);
        }
        
        // Parse comma-separated subject IDs
        $subjectIdsArray = array_map('trim', explode(',', $subjectIds));
        $subjectIdsArray = array_filter($subjectIdsArray, 'is_numeric');
        
        if (empty($subjectIdsArray)) {
            return response()->json([
                'error' => 'Invalid subject_ids format',
                'message' => 'subject_ids must be a comma-separated list of numeric IDs'
            ], 400);
        }
        
        // Query sections that belong to the specified subjects
        // Get unique time slots (start_time, end_time combinations) from those sections
        $timeSlots = \App\Models\Section::whereIn('subject_id', $subjectIdsArray)
            ->select('start_time', 'end_time')
            ->distinct()
            ->orderBy('start_time')
            ->orderBy('end_time')
            ->get()
            ->map(function ($section, $index) {
                return [
                    'id' => $index + 1, // Generate sequential IDs for frontend compatibility
                    'start_time' => $section->start_time,
                    'end_time' => $section->end_time
                ];
            })
            ->values(); // Reset array keys
        
        return response()->json($timeSlots);
    }
}
