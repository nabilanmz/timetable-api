<?php

namespace App\Http\Controllers;

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
}
