<?php

// Test the updated generateAvailableSections method with actual data

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\GeneratedTimetableController;
use App\Models\Subject;
use App\Models\Section;
use App\Models\Day;

// Test preferences matching the current database
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

// Test the method by reflection since it's private
$controller = new GeneratedTimetableController();
$reflectionClass = new ReflectionClass($controller);
$method = $reflectionClass->getMethod('generateAvailableSections');
$method->setAccessible(true);

try {
    $availableSections = $method->invoke($controller, $preferences);
    
    echo "Generated " . count($availableSections) . " available sections:\n";
    foreach ($availableSections as $section) {
        echo "- {$section->subject->name} (Section {$section->section_number}): {$section->day_of_week} {$section->start_time}-{$section->end_time}\n";
    }
    
    // Test the data mapping
    echo "\nTesting data mapping to Python format:\n";
    $classesData = collect($availableSections)->map(function ($section) {
        $activity = 'Lecture';
        $tiedTo = [];
        if ($section->tied_to && is_array($section->tied_to)) {
            $tiedTo = $section->tied_to;
        } elseif ($section->tied_to && is_string($section->tied_to)) {
            $tiedTo = array_map('trim', explode(',', $section->tied_to));
            $tiedTo = array_filter($tiedTo);
        }
        
        return [
            'code' => $section->subject->code,
            'subject' => $section->subject->name,
            'activity' => $activity,
            'section' => $section->section_number,
            'days' => $section->day_of_week,
            'start_time' => $section->start_time,
            'end_time' => $section->end_time,
            'venue' => $section->venue ?? 'TBD',
            'tied_to' => $tiedTo,
            'lecturer' => $section->lecturer ? $section->lecturer->name : 'TBD',
        ];
    });
    
    echo json_encode($classesData->take(3)->toArray(), JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
