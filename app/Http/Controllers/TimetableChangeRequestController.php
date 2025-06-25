<?php

namespace App\Http\Controllers;

use App\Models\TimetableChangeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TimetableChangeRequestController extends Controller
{
    /**
     * Display a listing of the resource.
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
     * Store a newly created resource in storage.
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
     * Display the specified resource.
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
     * Update the specified resource in storage.
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
     * Remove the specified resource from storage.
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
