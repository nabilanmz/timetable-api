<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;

class PreferenceOptionsController extends Controller
{
    public function index()
    {
        $subjects = Subject::whereHas('sections')->get(['id', 'name']);
        $lecturers = User::whereHas('sectionsAsLecturer')->get(['id', 'name']);

        return response()->json([
            'subjects' => $subjects,
            'lecturers' => $lecturers,
        ]);
    }
}
