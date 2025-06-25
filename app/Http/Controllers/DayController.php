<?php

namespace App\Http\Controllers;

use App\Models\Day;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="Day",
 *     type="object",
 *     title="Day",
 *     required={"name"},
 *     @OA\Property(property="id", type="integer", readOnly="true", example="1"),
 *     @OA\Property(property="name", type="string", example="Monday"),
 *     @OA\Property(property="created_at", type="string", format="date-time", readOnly="true"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", readOnly="true")
 * )
 */
class DayController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/days",
     *      operationId="getDaysList",
     *      tags={"Days"},
     *      summary="Get list of days",
     *      description="Returns list of days",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="array",
     *              @OA\Items(ref="#/components/schemas/Day")
     *          )
     *       )
     * )
     */
    public function index()
    {
        return Day::all();
    }
}
