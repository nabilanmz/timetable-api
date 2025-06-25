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
use App\Http\Controllers\TimetableChangeRequestController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/user', [AuthController::class, 'user']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('subjects', SubjectController::class);
    Route::apiResource('lecturers', LecturerController::class);
    Route::apiResource('timetables', TimetableController::class);
    Route::apiResource('timeslots', TimeSlotController::class);
    Route::put('settings', [SettingController::class, 'update']);
    Route::apiResource('timetable-preferences', TimetablePreferenceController::class);

    Route::post('generate-timetable', [GeneratedTimetableController::class, 'store']);
    Route::get('my-timetable', [GeneratedTimetableController::class, 'show']);

    Route::apiResource('timetable-change-requests', TimetableChangeRequestController::class);
});

Route::get('/days', [DayController::class, 'index']);
Route::get('/times', [TimeSlotController::class, 'index']);
Route::get('/settings', [SettingController::class, 'index']);
