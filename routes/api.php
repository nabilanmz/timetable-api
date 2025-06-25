<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\LecturerController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\DayController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TimeSlotController;
use App\Http\Controllers\GeneratedTimetableController;
use App\Http\Controllers\TimetablePreferenceController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('subjects', SubjectController::class);
    Route::apiResource('lecturers', LecturerController::class);
    Route::apiResource('timetables', TimetableController::class);
    Route::put('/settings', [SettingController::class, 'update']);
    Route::post('/timetable-preferences', [TimetablePreferenceController::class, 'store']);
    Route::post('/generate-timetable', [GeneratedTimetableController::class, 'store']);
    Route::get('/my-timetable', [GeneratedTimetableController::class, 'show']);
});

Route::get('/days', [DayController::class, 'index']);
Route::get('/times', [TimeSlotController::class, 'index']);
Route::get('/settings', [SettingController::class, 'index']);
