<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        return Setting::all()->pluck('value', 'key');
    }

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
