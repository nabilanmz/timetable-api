<?php

namespace App\Http\Controllers;

use App\Models\TimetableChangeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * @OA\Schema(
 *     schema="TimetableChangeRequest",
 *     type="object",
 *     title="Timetable Change Request",
 *     required={"generated_timetable_id", "message"},
 *     @OA\Property(property="id", type="integer", readOnly="true"),
 *     @OA\Property(property="user_id", type="integer", readOnly="true"),
 *     @OA\Property(property="generated_timetable_id", type="integer"),
 *     @OA\Property(property="message", type="string"),
 *     @OA\Property(property="status", type="string", enum={"pending", "approved", "rejected"}, default="pending"),
 *     @OA\Property(property="admin_response", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", readOnly="true"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", readOnly="true"),
 *     @OA\Property(property="user", ref="#/components/schemas/User"),
 *     @OA\Property(property="generated_timetable", ref="#/components/schemas/GeneratedTimetable")
 * )
 */

class TimetableChangeRequestController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/timetable-change-requests",
     *      operationId="getChangeRequestsList",
     *      tags={"Timetable Change Requests"},
     *      summary="Get list of timetable change requests (Admin only)",
     *      description="Returns list of timetable change requests",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="array",
     *              @OA\Items(ref="#/components/schemas/TimetableChangeRequest")
     *          )
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated"
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     * )
     */
    public function index(Request $request)
    {
        if ($request->user()->is_admin) {
            return TimetableChangeRequest::with(['user', 'generatedTimetable'])->latest()->get();
        }
        return response()->json(['message' => 'Forbidden'], 403);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * @OA\Post(
     *      path="/api/timetable-change-requests",
     *      operationId="storeChangeRequest",
     *      tags={"Timetable Change Requests"},
     *      summary="Store new timetable change request",
     *      description="Returns change request data",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"generated_timetable_id", "message"},
     *              @OA\Property(property="generated_timetable_id", type="integer"),
     *              @OA\Property(property="message", type="string")
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/TimetableChangeRequest")
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
        $request->validate([
            'generated_timetable_id' => 'required|exists:generated_timetables,id',
            'message' => 'required|string',
        ]);

        $changeRequest = TimetableChangeRequest::create([
            'user_id' => $request->user()->id,
            'generated_timetable_id' => $request->generated_timetable_id,
            'message' => $request->message,
        ]);

        return response()->json($changeRequest, 201);
    }

    /**
     * @OA\Get(
     *      path="/api/timetable-change-requests/{id}",
     *      operationId="getChangeRequestById",
     *      tags={"Timetable Change Requests"},
     *      summary="Get change request information (Admin or owner)",
     *      description="Returns change request data",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="Change Request id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/TimetableChangeRequest")
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated"
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
    public function show(Request $request, TimetableChangeRequest $timetableChangeRequest)
    {
        if ($request->user()->is_admin || $request->user()->id === $timetableChangeRequest->user_id) {
            return $timetableChangeRequest->load(['user', 'generatedTimetable']);
        }

        return response()->json(['message' => 'Forbidden'], 403);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TimetableChangeRequest $timetableChangeRequest)
    {
        //
    }

    /**
     * @OA\Put(
     *      path="/api/timetable-change-requests/{id}",
     *      operationId="updateChangeRequest",
     *      tags={"Timetable Change Requests"},
     *      summary="Update existing change request (Admin only)",
     *      description="Returns updated change request data",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="Change Request id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"status"},
     *              @OA\Property(property="status", type="string", enum={"pending", "approved", "rejected"}),
     *              @OA\Property(property="admin_response", type="string", nullable=true)
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/TimetableChangeRequest")
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated"
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
    public function update(Request $request, TimetableChangeRequest $timetableChangeRequest)
    {
        if (!$request->user()->is_admin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'status' => 'required|string|in:pending,approved,rejected',
            'admin_response' => 'nullable|string',
        ]);

        $timetableChangeRequest->update($request->only('status', 'admin_response'));

        return response()->json($timetableChangeRequest);
    }

    /**
     * @OA\Delete(
     *      path="/api/timetable-change-requests/{id}",
     *      operationId="deleteChangeRequest",
     *      tags={"Timetable Change Requests"},
     *      summary="Delete existing change request (Admin only)",
     *      description="Deletes a record and returns no content",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="Change Request id",
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
     *          description="Unauthenticated"
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
    public function destroy(Request $request, TimetableChangeRequest $timetableChangeRequest)
    {
        if (!$request->user()->is_admin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $timetableChangeRequest->delete();

        return response()->json(null, 204);
    }
}
