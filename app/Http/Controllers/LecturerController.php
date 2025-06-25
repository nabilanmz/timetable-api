<?php

namespace App\Http\Controllers;

use App\Models\Lecturer;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="Lecturer",
 *     type="object",
 *     title="Lecturer",
 *     required={"name", "email"},
 *     @OA\Property(property="id", type="integer", readOnly="true", example="1"),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *     @OA\Property(property="phone", type="string", example="123-456-7890"),
 *     @OA\Property(property="department", type="string", example="Computer Science"),
 *     @OA\Property(property="created_at", type="string", format="date-time", readOnly="true"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", readOnly="true")
 * )
 */
class LecturerController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/lecturers",
     *      operationId="getLecturersList",
     *      tags={"Lecturers"},
     *      summary="Get list of lecturers",
     *      description="Returns list of lecturers",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="array",
     *              @OA\Items(ref="#/components/schemas/Lecturer")
     *          )
     *       )
     * )
     */
    public function index()
    {
        return Lecturer::all();
    }

    /**
     * @OA\Post(
     *      path="/api/lecturers",
     *      operationId="storeLecturer",
     *      tags={"Lecturers"},
     *      summary="Store new lecturer",
     *      description="Returns lecturer data",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/Lecturer")
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Lecturer")
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      )
     * )
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:lecturers',
            'phone' => 'nullable|string|max:20',
            'department' => 'nullable|string|max:255',
        ]);

        $lecturer = Lecturer::create($validatedData);

        return response()->json($lecturer, 201);
    }

    /**
     * @OA\Get(
     *      path="/api/lecturers/{id}",
     *      operationId="getLecturerById",
     *      tags={"Lecturers"},
     *      summary="Get lecturer information",
     *      description="Returns lecturer data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Lecturer id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Lecturer")
     *       ),
     *      @OA\Response(
     *          response=404,
     *          description="Resource Not Found"
     *      )
     * )
     */
    public function show(Lecturer $lecturer)
    {
        return $lecturer;
    }

    /**
     * @OA\Put(
     *      path="/api/lecturers/{id}",
     *      operationId="updateLecturer",
     *      tags={"Lecturers"},
     *      summary="Update existing lecturer",
     *      description="Returns updated lecturer data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Lecturer id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/Lecturer")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Lecturer")
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Resource Not Found"
     *      )
     * )
     */
    public function update(Request $request, Lecturer $lecturer)
    {
        $validatedData = $request->validate([
            'name' => 'string|max:255',
            'email' => 'string|email|max:255|unique:lecturers,email,' . $lecturer->id,
            'phone' => 'nullable|string|max:20',
            'department' => 'nullable|string|max:255',
        ]);

        $lecturer->update($validatedData);

        return response()->json($lecturer);
    }

    /**
     * @OA\Delete(
     *      path="/api/lecturers/{id}",
     *      operationId="deleteLecturer",
     *      tags={"Lecturers"},
     *      summary="Delete existing lecturer",
     *      description="Deletes a record and returns no content",
     *      @OA\Parameter(
     *          name="id",
     *          description="Lecturer id",
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
     *          response=404,
     *          description="Resource Not Found"
     *      )
     * )
     */
    public function destroy(Lecturer $lecturer)
    {
        $lecturer->delete();

        return response()->json(null, 204);
    }
}
