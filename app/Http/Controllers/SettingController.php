<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="Setting",
 *     type="object",
 *     title="Setting",
 *     @OA\Property(property="key", type="string", example="max_subjects_per_day"),
 *     @OA\Property(property="value", type="string", example="5")
 * )
 */
/**
 * @OA\Schema(
 *     schema="SettingsList",
 *     type="object",
 *     title="Settings List",
 *     @OA\Property(property="max_subjects_per_day", type="string", example="5"),
 *     @OA\Property(property="max_concurrent_subjects", type="string", example="3")
 * )
 */
class SettingController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/settings",
     *      operationId="getSettings",
     *      tags={"Settings"},
     *      summary="Get all settings",
     *      description="Returns a key-value pair of all settings",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/SettingsList")
     *       )
     * )
     */
    public function index()
    {
        return Setting::all()->pluck('value', 'key');
    }

    /**
     * @OA\Put(
     *      path="/api/settings",
     *      operationId="updateSettings",
     *      tags={"Settings"},
     *      summary="Update settings",
     *      description="Updates multiple settings at once",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              type="array",
     *              @OA\Items(
     *                  type="object",
     *                  @OA\Property(property="key", type="string"),
     *                  @OA\Property(property="value", type="string")
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/SettingsList")
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      )
     * )
     */
    public function update(Request $request)
    {
        $validatedData = $request->validate([
            '*.key' => 'required|string',
            '*.value' => 'required|string',
        ]);

        foreach ($validatedData as $data) {
            Setting::updateOrCreate(['key' => $data['key']], ['value' => $data['value']]);
        }

        return response()->json(Setting::all()->pluck('value', 'key'));
    }
}
