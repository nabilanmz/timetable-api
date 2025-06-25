<?php

namespace App\Http\Controllers;

use App\Models\TimeSlot;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="TimeSlot",
 *     type="object",
 *     title="TimeSlot",
 *     required={"start_time", "end_time"},
 *     @OA\Property(property="id", type="integer", readOnly="true", example="1"),
 *     @OA\Property(property="start_time", type="string", format="time", example="09:00:00"),
 *     @OA\Property(property="end_time", type="string", format="time", example="10:00:00"),
 *     @OA\Property(property="created_at", type="string", format="date-time", readOnly="true"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", readOnly="true")
 * )
 */
class TimeSlotController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *      path="/api/time-slots",
     *      operationId="getTimeSlotsList",
     *      tags={"TimeSlots"},
     *      summary="Get list of time slots",
     *      description="Returns list of time slots",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="array",
     *              @OA\Items(ref="#/components/schemas/TimeSlot")
     *          )
     *       )
     * )
     */
    public function index()
    {
        return TimeSlot::all();
    }
}
