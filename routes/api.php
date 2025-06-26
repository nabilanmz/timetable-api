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
use App\Http\Controllers\SectionController;
use App\Http\Controllers\TimetableSectionController;
use App\Http\Controllers\PreferenceOptionsController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/user', [AuthController::class, 'user']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('lecturers', LecturerController::class);
    Route::apiResource('timetables', TimetableController::class);
    Route::apiResource('timeslots', TimeSlotController::class);
    Route::put('settings', [SettingController::class, 'update']);
    Route::get('timetable-preferences/options', [TimetablePreferenceController::class, 'getOptions']);
    Route::apiResource('timetable-preferences', TimetablePreferenceController::class);

    Route::post('generate-timetable', [GeneratedTimetableController::class, 'generate']);
    Route::get('my-timetable', [GeneratedTimetableController::class, 'show']);

    Route::apiResource('timetable-change-requests', TimetableChangeRequestController::class);
    Route::apiResource('subjects', SubjectController::class);
    Route::apiResource('sections', SectionController::class);

    Route::post('timetables/{timetable}/sections', [TimetableSectionController::class, 'store'])->name('timetables.sections.store');
    Route::delete('timetables/{timetable}/sections/{section}', [TimetableSectionController::class, 'destroy'])->name('timetables.sections.destroy');
});

Route::get('/days', [DayController::class, 'index']);
Route::get('/times', [TimeSlotController::class, 'index']);
Route::get('/settings', [SettingController::class, 'index']);
Route::get('/preference-options', [PreferenceOptionsController::class, 'index']);
