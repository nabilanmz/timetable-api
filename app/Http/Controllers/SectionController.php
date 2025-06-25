<?php

namespace App\Http\Controllers;

use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SectionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/sections",
     *     summary="List all sections",
     *     description="Get a list of all available course sections, with optional search functionality.",
     *     tags={"Sections"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term to filter sections by subject name, code, lecturer, or section number.",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="A list of sections.",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Section"))
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Section::with(['subject', 'lecturer', 'enrollments']);

        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('section_number', 'like', "%{$searchTerm}%")
                    ->orWhereHas('subject', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', "%{$searchTerm}%")
                          ->orWhere('code', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('lecturer', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', "%{$searchTerm}%");
                    });
            });
        }

        $sections = $query->get();

        $sections->transform(function ($section) {
            $section->enrolled_students = $section->enrollments->count();
            $section->status = $section->enrolled_students >= $section->capacity ? 'Full' : 'Available';
            unset($section->enrollments);
            return $section;
        });

        return response()->json($sections);
    }

    /**
     * @OA\Get(
     *     path="/api/sections/{section}",
     *     summary="Get a single section",
     *     description="Retrieve detailed information for a specific section.",
     *     tags={"Sections"},
     *     @OA\Parameter(
     *         name="section",
     *         in="path",
     *         description="The UUID of the section.",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="The section details.",
     *         @OA\JsonContent(ref="#/components/schemas/Section")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Section not found."
     *     )
     * )
     */
    public function show(Section $section)
    {
        $section->load(['subject', 'lecturer', 'enrollments']);
        $section->enrolled_students = $section->enrollments->count();
        $section->status = $section->enrolled_students >= $section->capacity ? 'Full' : 'Available';

        return response()->json($section);
    }

    /**
     * @OA\Post(
     *     path="/api/sections",
     *     summary="Create a new section",
     *     description="Create a new course section. Requires admin privileges.",
     *     tags={"Sections"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Section")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Section created successfully.",
     *         @OA\JsonContent(ref="#/components/schemas/Section")
     *     ),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $this->authorize('create', Section::class);

        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'section_number' => 'required|integer',
            'lecturer_id' => 'nullable|exists:users,id',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s|after:start_time',
            'day_of_week' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'venue' => 'nullable|string|max:255',
            'capacity' => 'required|integer|min:0',
        ]);

        $section = Section::create($validated);

        return response()->json($section, 201);
    }

    /**
     * @OA\Put(
     *     path="/api/sections/{section}",
     *     summary="Update a section",
     *     description="Update an existing course section. Requires admin privileges.",
     *     tags={"Sections"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="section", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Section")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Section updated successfully.",
     *         @OA\JsonContent(ref="#/components/schemas/Section")
     *     ),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Section not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, Section $section)
    {
        $this->authorize('update', $section);

        $validated = $request->validate([
            'subject_id' => 'sometimes|required|exists:subjects,id',
            'section_number' => 'sometimes|required|integer',
            'lecturer_id' => 'nullable|exists:users,id',
            'start_time' => 'sometimes|required|date_format:H:i:s',
            'end_time' => 'sometimes|required|date_format:H:i:s|after:start_time',
            'day_of_week' => 'sometimes|required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'venue' => 'nullable|string|max:255',
            'capacity' => 'sometimes|required|integer|min:0',
        ]);

        $section->update($validated);

        return response()->json($section);
    }

    /**
     * @OA\Delete(
     *     path="/api/sections/{section}",
     *     summary="Delete a section",
     *     description="Delete a course section. Requires admin privileges.",
     *     tags={"Sections"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="section", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=204, description="Section deleted successfully"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Section not found")
     * )
     */
    public function destroy(Section $section)
    {
        $this->authorize('delete', $section);

        $section->delete();

        return response()->noContent();
    }
}
