<?php

namespace App\Http\Controllers;

use App\Models\Timetable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Schema(
 *     schema="TimetableEntry",
 *     type="object",
 *     title="Timetable Entry",
 *     required={"subject_id", "lecturer_id", "day_id", "time_slot_id", "activity", "section", "venue"},
 *     @OA\Property(property="id", type="integer", readOnly="true"),
 *     @OA\Property(property="subject_id", type="integer"),
 *     @OA\Property(property="lecturer_id", type="integer"),
 *     @OA\Property(property="day_id", type="integer"),
 *     @OA\Property(property="time_slot_id", type="integer"),
 *     @OA\Property(property="activity", type="string"),
 *     @OA\Property(property="section", type="string"),
 *     @OA\Property(property="venue", type="string"),
 *     @OA\Property(property="subject", ref="#/components/schemas/Subject"),
 *     @OA\Property(property="lecturer", ref="#/components/schemas/Lecturer"),
 *     @OA\Property(property="day", ref="#/components/schemas/Day"),
 *     @OA\Property(property="time_slot", ref="#/components/schemas/TimeSlot"),
 *     @OA\Property(property="created_at", type="string", format="date-time", readOnly="true"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", readOnly="true")
 * )
 */
/**
 * @OA\Schema(
 *     schema="Timetable",
 *     type="object",
 *     title="Timetable",
 *     required={"name"},
 *     @OA\Property(property="id", type="integer", readOnly="true"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="created_by", type="integer", readOnly="true"),
 *     @OA\Property(property="created_at", type="string", format="date-time", readOnly="true"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", readOnly="true"),
 *     @OA\Property(property="timetable_entries", type="array", @OA\Items(ref="#/components/schemas/TimetableEntry"))
 * )
 */
class TimetableController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/timetables",
     *      operationId="getTimetablesList",
     *      tags={"Timetables"},
     *      summary="Get list of timetables for the authenticated user",
     *      description="Returns list of timetables",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="array",
     *              @OA\Items(ref="#/components/schemas/Timetable")
     *          )
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      )
     * )
     */
    public function index()
    {
        return Timetable::with('timetableEntries')->where('created_by', Auth::id())->get();
    }

    /**
     * @OA\Post(
     *      path="/api/timetables",
     *      operationId="storeTimetable",
     *      tags={"Timetables"},
     *      summary="Store new timetable",
     *      description="Returns timetable data",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/Timetable")
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Timetable")
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      )
     * )
     */
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
            'entries.*.activity' => 'required|string',
            'entries.*.section' => 'required|string',
            'entries.*.venue' => 'required|string',
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

    /**
     * @OA\Get(
     *      path="/api/timetables/{id}",
     *      operationId="getTimetableById",
     *      tags={"Timetables"},
     *      summary="Get timetable information",
     *      description="Returns timetable data",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="Timetable id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Timetable")
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Resource Not Found"
     *      )
     * )
     */
    public function show(Timetable $timetable)
    {
        if ($timetable->created_by !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        return $timetable->load('timetableEntries.subject', 'timetableEntries.lecturer', 'timetableEntries.day', 'timetableEntries.timeSlot');
    }

    /**
     * @OA\Put(
     *      path="/api/timetables/{id}",
     *      operationId="updateTimetable",
     *      tags={"Timetables"},
     *      summary="Update existing timetable",
     *      description="Returns updated timetable data",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="Timetable id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/Timetable")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Timetable")
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Resource Not Found"
     *      )
     * )
     */
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
            'entries.*.activity' => 'required|string',
            'entries.*.section' => 'required|string',
            'entries.*.venue' => 'required|string',
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

    /**
     * @OA\Delete(
     *      path="/api/timetables/{id}",
     *      operationId="deleteTimetable",
     *      tags={"Timetables"},
     *      summary="Delete existing timetable",
     *      description="Deletes a record and returns no content",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="Timetable id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=204,
     *          description="Successful operation",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Resource Not Found"
     *      )
     * )
     */
    public function destroy(Timetable $timetable)
    {
        if ($timetable->created_by !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $timetable->delete();

        return response()->json(null, 204);
    }
}
