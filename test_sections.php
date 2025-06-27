<?php

// Test the generateAvailableSections method to ensure it works properly

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Subject;
use App\Models\Section;
use App\Models\Day;

// Test preferences
$preferences = [
    'subjects' => [1, 2, 3],
    'days' => [1, 2, 3, 4, 5],
    'start_time' => '08:00',
    'end_time' => '18:00',
    'enforce_ties' => 'yes',
    'lecturers' => [],
    'mode' => 1
];

echo "Testing generateAvailableSections with preferences:\n";
echo json_encode($preferences, JSON_PRETTY_PRINT) . "\n\n";

// Check what sections exist for these subjects
$sections = Section::with(['subject', 'lecturer'])
    ->whereHas('subject', function ($q) use ($preferences) {
        $q->whereIn('id', $preferences['subjects']);
    })
    ->get();

echo "Found " . $sections->count() . " sections for the selected subjects:\n";
foreach ($sections as $section) {
    echo "- {$section->subject->name} ({$section->section_number}): {$section->day_of_week} {$section->start_time}-{$section->end_time}\n";
}

echo "\nDay names for IDs " . implode(', ', $preferences['days']) . ":\n";
$dayNames = Day::whereIn('id', $preferences['days'])->pluck('name')->all();
foreach ($dayNames as $day) {
    echo "- $day\n";
}
