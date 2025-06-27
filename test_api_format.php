<?php

// Test the complete API flow with the updated implementation

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Subject;
use App\Models\Day;

// Prepare test data exactly as the API expects
$preferences = [
    'subjects' => [1, 2, 3],
    'days' => [1, 2, 3, 4, 5],
    'start_time' => '08:00',
    'end_time' => '18:00',
    'enforce_ties' => 'yes',
    'lecturers' => [],
    'mode' => 1
];

// Transform for Python script format
$subjectNames = Subject::whereIn('id', $preferences['subjects'])->pluck('name')->all();
$dayNames = Day::whereIn('id', $preferences['days'])->pluck('name')->all();

$scheduleStyle = $preferences['mode'] == 1 ? 'compact' : 'spaced_out';
$enforceTies = $preferences['enforce_ties'] === 'yes';

$scriptPreferences = [
    'subjects' => $subjectNames,
    'preferred_lecturers' => [],
    'preferred_days' => $dayNames,
    'preferred_start' => $preferences['start_time'] . ':00',
    'preferred_end' => $preferences['end_time'] . ':00',
    'schedule_style' => $scheduleStyle,
    'enforce_ties' => $enforceTies,
];

echo "User Input Preferences:\n";
echo json_encode($preferences, JSON_PRETTY_PRINT) . "\n\n";

echo "Transformed for Python Script:\n";
echo json_encode($scriptPreferences, JSON_PRETTY_PRINT) . "\n\n";

echo "Subject Names: " . implode(', ', $subjectNames) . "\n";
echo "Day Names: " . implode(', ', $dayNames) . "\n";
echo "Schedule Style: $scheduleStyle\n";
echo "Enforce Ties: " . ($enforceTies ? 'true' : 'false') . "\n";

// Show the format expected by API
echo "\n=== API Request Format ===\n";
echo "POST /api/generate-timetable\n";
echo "Content-Type: application/json\n";
echo "Authorization: Bearer <token>\n\n";
echo json_encode(['preferences' => $preferences], JSON_PRETTY_PRINT);
