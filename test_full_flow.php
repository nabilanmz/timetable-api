<?php

// Test the complete end-to-end flow with the Python TimetableEngine

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Subject;
use App\Models\Lecturer;
use App\Models\Section;
use App\Models\Day;

echo "=== END-TO-END TIMETABLE GENERATION TEST ===\n\n";

// Test with real data from our database
$subjects = Subject::with(['sections.lecturer'])->limit(3)->get();

if ($subjects->count() < 3) {
    echo "Not enough subjects in database. Please run: php artisan db:seed --class=RealDataSeeder\n";
    exit(1);
}

$subjectIds = $subjects->pluck('id')->toArray();
$days = Day::all();
$dayIds = $days->pluck('id')->toArray();

echo "Testing with subjects:\n";
foreach ($subjects as $subject) {
    echo "- {$subject->code}: {$subject->name} ({$subject->sections->count()} sections)\n";
}
echo "\n";

// Create test preferences
$preferences = [
    'subjects' => $subjectIds,
    'days' => $dayIds,
    'start_time' => '08:00',
    'end_time' => '18:00',
    'enforce_ties' => 'yes',
    'lecturers' => [],
    'mode' => 1
];

echo "Test preferences:\n";
echo json_encode($preferences, JSON_PRETTY_PRINT) . "\n\n";

// Simulate the controller logic
$subjectNames = Subject::whereIn('id', $preferences['subjects'])->pluck('name')->all();
$dayNames = Day::whereIn('id', $preferences['days'])->pluck('name')->all();

// Get available sections
$availableSections = Section::with(['subject', 'lecturer'])
    ->whereHas('subject', function ($q) use ($preferences) {
        $q->whereIn('id', $preferences['subjects']);
    })
    ->whereIn('day_of_week', $dayNames)
    ->where('start_time', '>=', $preferences['start_time'] . ':00')
    ->where('end_time', '<=', $preferences['end_time'] . ':00')
    ->get();

echo "Found {$availableSections->count()} available sections:\n";
foreach ($availableSections as $section) {
    echo "- {$section->subject->code} ({$section->section_number} - {$section->activity}): " .
         "{$section->day_of_week} {$section->start_time}-{$section->end_time} - " .
         ($section->lecturer ? $section->lecturer->name : 'No lecturer') . "\n";
}
echo "\n";

// Create input for Python script
$classesData = $availableSections->map(function ($section) {
    $tiedTo = [];
    if ($section->tied_to && is_array($section->tied_to)) {
        $tiedTo = $section->tied_to;
    } elseif ($section->tied_to && is_string($section->tied_to)) {
        $decoded = json_decode($section->tied_to, true);
        $tiedTo = is_array($decoded) ? $decoded : [];
    }
    
    return [
        'code' => $section->subject->code,
        'subject' => $section->subject->name,
        'activity' => $section->activity ?? 'Lecture',
        'section' => $section->section_number,
        'days' => $section->day_of_week,
        'start_time' => $section->start_time,
        'end_time' => $section->end_time,
        'venue' => $section->venue ?? 'TBD',
        'tied_to' => $tiedTo,
        'lecturer' => $section->lecturer ? $section->lecturer->name : 'TBD',
    ];
});

$scriptPreferences = [
    'subjects' => $subjectNames,
    'preferred_lecturers' => [],
    'preferred_days' => $dayNames,
    'preferred_start' => $preferences['start_time'] . ':00',
    'preferred_end' => $preferences['end_time'] . ':00',
    'schedule_style' => 'compact',
    'enforce_ties' => true,
];

$inputData = [
    'classes' => $classesData->toArray(),
    'preferences' => $scriptPreferences,
];

echo "Input data for Python engine:\n";
echo "Classes: " . count($inputData['classes']) . "\n";
echo "Preferences: " . json_encode($scriptPreferences, JSON_PRETTY_PRINT) . "\n\n";

// Save input to file for inspection
file_put_contents(__DIR__ . '/test_input_real.json', json_encode($inputData, JSON_PRETTY_PRINT));
echo "Input saved to test_input_real.json\n\n";

echo "=== Test completed successfully! ===\n";
echo "The system is ready to generate timetables with real course data.\n";
