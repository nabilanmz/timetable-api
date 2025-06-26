<?php

namespace App\Http\Controllers;

use App\Models\Day;
use App\Models\Lecturer;
use App\Models\Section;
use App\Models\Subject;
use App\Models\TimeSlot;
use App\Models\TimetablePreference;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *      schema="TimetablePreferenceRequest",
 *      type="object",
 *      title="Timetable Preference Request",
 *      required={"preferences"},
 *      @OA\Property(
 *          property="preferences",
 *          type="object",
 *          description="A key-value object of user preferences for timetable generation.",
 *          example={"prefer_morning": true, "max_consecutive_classes": 3}
 *      )
 * )
 */
class TimetablePreferenceController extends Controller
{
    /**
     * @OA\Post(
     *      path="/api/timetable-preferences",
     *      operationId="storeTimetablePreference",
     *      tags={"Timetable Preferences"},
     *      summary="Store or update user's timetable preferences",
     *      description="Returns the created or updated preference data",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/TimetablePreferenceRequest")
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/TimetablePreference")
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated"
     *      )
     * )
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'preferences' => 'required|array',
        ]);

        $preference = TimetablePreference::updateOrCreate(
            ['user_id' => $request->user()->id],
            ['preferences' => $validatedData['preferences']]
        );

        return response()->json($preference, 201);
    }

    /**
     * @OA\Get(
     *      path="/api/timetable-preferences/options",
     *      operationId="getTimetablePreferenceOptions",
     *      tags={"Timetable Preferences"},
     *      summary="Get available options for timetable preferences",
     *      description="Returns available subjects, days, time slots, and lecturers",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated"
     *      )
     * )
     */
    public function getOptions(Request $request)
    {
        $subjectIds = $request->input('subjects');

        if (empty($subjectIds)) {
            $subjects = Subject::all(['id', 'name', 'code']);
            $days = Day::all(['id', 'name']);
            $timeSlots = TimeSlot::all(['id', 'start_time', 'end_time']);
            $lecturers = Lecturer::all(['id', 'name']);

            return response()->json([
                'subjects' => $subjects,
                'days' => $days,
                'timeSlots' => $timeSlots,
                'lecturers' => $lecturers,
            ]);
        }

        $sections = Section::whereIn('subject_id', $subjectIds)->get();

        if ($sections->isEmpty()) {
            return response()->json(['message' => 'No sections found for the selected subjects.'], 404);
        }

        $lecturerIds = $sections->pluck('lecturer_id')->unique()->filter()->values();
        $dayNames = $sections->pluck('day_of_week')->unique()->filter()->values();
        $timeSlotStarts = $sections->pluck('start_time')->unique()->filter()->values();

        $lecturers = Lecturer::whereIn('id', $lecturerIds)->get(['id', 'name']);
        $days = Day::whereIn('name', $dayNames)->get(['id', 'name']);
        $timeSlots = TimeSlot::whereIn('start_time', $timeSlotStarts)->get(['id', 'start_time', 'end_time']);

        return response()->json([
            'lecturers' => $lecturers,
            'days' => $days,
            'timeSlots' => $timeSlots,
        ]);
    }
}
