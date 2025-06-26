<?php

namespace App\Http\Controllers;

use App\Models\Timetable;
use App\Models\Section;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Timetables', description: 'Endpoints for managing timetables')]
class TimetableSectionController extends Controller
{
    #[OA\Post(
        path: '/api/timetables/{timetable}/sections',
        summary: 'Add a section to a timetable',
        tags: ['Timetables'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'timetable',
                in: 'path',
                required: true,
                description: 'ID of the timetable',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['section_id'],
                properties: [
                    new OA\Property(property: 'section_id', type: 'string', format: 'uuid', description: 'ID of the section to add'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Section added successfully'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request, Timetable $timetable)
    {
        $this->authorize('manageSections', $timetable);

        $request->validate([
            'section_id' => 'required|exists:sections,id',
        ]);

        $timetable->sections()->attach($request->section_id);

        return response()->json(['message' => 'Section added to timetable successfully.']);
    }

    #[OA\Delete(
        path: '/api/timetables/{timetable}/sections/{section}',
        summary: 'Remove a section from a timetable',
        tags: ['Timetables'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'timetable',
                in: 'path',
                required: true,
                description: 'ID of the timetable',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'section',
                in: 'path',
                required: true,
                description: 'ID of the section to remove',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Section removed successfully'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function destroy(Timetable $timetable, Section $section)
    {
        $this->authorize('manageSections', $timetable);

        $timetable->sections()->detach($section->id);

        return response()->json(['message' => 'Section removed from timetable successfully.']);
    }
}
