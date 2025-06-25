<?php

namespace App\Http\Controllers;

use App\Models\Lecturer;
use Illuminate\Http\Request;

class LecturerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Lecturer::all();
    }

    /**
     * Store a newly created resource in storage.
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
     * Display the specified resource.
     */
    public function show(Lecturer $lecturer)
    {
        return $lecturer;
    }

    /**
     * Update the specified resource in storage.
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
     * Remove the specified resource from storage.
     */
    public function destroy(Lecturer $lecturer)
    {
        $lecturer->delete();

        return response()->json(null, 204);
    }
}
