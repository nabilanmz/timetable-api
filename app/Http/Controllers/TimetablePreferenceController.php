<?php

namespace App\Http\Controllers;

use App\Models\TimetablePreference;
use Illuminate\Http\Request;

class TimetablePreferenceController extends Controller
{
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'preferences' => 'required|array',
        ]);

        $preference = TimetablePreference::updateOrCreate(
            ['user_id' => $request->user()->id],
            ['preferences' => $validatedData['preferences']]
        );

        return response()->json($preference, 201);
    }
}
