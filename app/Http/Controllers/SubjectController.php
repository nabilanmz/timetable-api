<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="Subject",
 *     type="object",
 *     title="Subject",
 *     required={"name", "code"},
 *     @OA\Property(property="id", type="integer", readOnly="true", example="1"),
 *     @OA\Property(property="name", type="string", example="Introduction to Programming"),
 *     @OA\Property(property="code", type="string", example="CS101"),
 *     @OA\Property(property="description", type="string", example="An introductory course on programming fundamentals."),
 *     @OA\Property(property="created_at", type="string", format="date-time", readOnly="true"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", readOnly="true")
 * )
 */
class SubjectController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/subjects",
     *      operationId="getSubjectsList",
     *      tags={"Subjects"},
     *      summary="Get list of subjects",
     *      description="Returns list of subjects",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="array",
     *              @OA\Items(ref="#/components/schemas/Subject")
     *          )
     *       )
     * )
     */
    public function index()
    {
        return Subject::all();
    }

    /**
     * @OA\Post(
     *      path="/api/subjects",
     *      operationId="storeSubject",
     *      tags={"Subjects"},
     *      summary="Store new subject",
     *      description="Returns subject data",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/Subject")
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Subject")
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
            'code' => 'required|string|max:255|unique:subjects',
            'description' => 'nullable|string',
        ]);

        $subject = Subject::create($validatedData);

        return response()->json($subject, 201);
    }

    /**
     * @OA\Get(
     *      path="/api/subjects/{id}",
     *      operationId="getSubjectById",
     *      tags={"Subjects"},
     *      summary="Get subject information",
     *      description="Returns subject data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Subject id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Subject")
     *       ),
     *      @OA\Response(
     *          response=404,
     *          description="Resource Not Found"
     *      )
     * )
     */
    public function show(Subject $subject)
    {
        return $subject;
    }

    /**
     * @OA\Put(
     *      path="/api/subjects/{id}",
     *      operationId="updateSubject",
     *      tags={"Subjects"},
     *      summary="Update existing subject",
     *      description="Returns updated subject data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Subject id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/Subject")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Subject")
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
    public function update(Request $request, Subject $subject)
    {
        $validatedData = $request->validate([
            'name' => 'string|max:255',
            'code' => 'string|max:255|unique:subjects,code,' . $subject->id,
            'description' => 'nullable|string',
        ]);

        $subject->update($validatedData);

        return response()->json($subject);
    }

    /**
     * @OA\Delete(
     *      path="/api/subjects/{id}",
     *      operationId="deleteSubject",
     *      tags={"Subjects"},
     *      summary="Delete existing subject",
     *      description="Deletes a record and returns no content",
     *      @OA\Parameter(
     *          name="id",
     *          description="Subject id",
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
    public function destroy(Subject $subject)
    {
        $subject->delete();

        return response()->json(null, 204);
    }
}
