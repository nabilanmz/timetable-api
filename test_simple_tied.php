<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== SIMPLE TIED RELATIONSHIP TEST ===\n\n";

// Use just LDCW6123 which has clean tied relationships
$subject = \App\Models\Subject::find(2); // LDCW6123
$sections = $subject->sections;

echo "Testing subject: {$subject->code} - {$subject->title}\n";
echo "Total sections: {$sections->count()}\n\n";

$preferences = [
    'subjects' => [2],
    'days' => [1, 2, 3, 4, 5],
    'start_time' => '08:00',
    'end_time' => '18:00',
    'enforce_ties' => 'yes',
    'lecturers' => [],
    'mode' => 1
];

// Generate sections for this subject only
$generated_sections = [];
foreach ($sections as $section) {
    $generated_sections[] = [
        'id' => $section->id,
        'subject' => $section->subject->title,
        'section_number' => $section->section_number,
        'activity' => $section->activity,
        'lecturer' => $section->lecturer->name,
        'day' => $section->day_of_week,
        'start_time' => $section->start_time,
        'end_time' => $section->end_time,
        'venue' => $section->venue,
        'tied_to' => $section->tied_to ?: []
    ];
}

echo "Available sections:\n";
foreach ($generated_sections as $section) {
    echo "- {$section['section_number']} ({$section['activity']}): {$section['day']} {$section['start_time']}-{$section['end_time']}\n";
    echo "  Tied to: " . implode(', ', $section['tied_to']) . "\n";
}

$input_data = [
    'classes' => $generated_sections,
    'preferences' => [
        'subjects' => [$subject->title],
        'preferred_lecturers' => [],
        'preferred_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
        'preferred_start' => '08:00:00',
        'preferred_end' => '18:00:00',
        'schedule_style' => 'compact',
        'enforce_ties' => true
    ]
];

// Save input for Python
file_put_contents('test_simple_tied.json', json_encode($input_data, JSON_PRETTY_PRINT));

echo "\nInput saved to test_simple_tied.json\n";
echo "Now testing Python engine...\n\n";

// Run Python engine
$result = shell_exec('python app/Http/Controllers/TimetableGenerator.py test_simple_tied.json 2>&1');
echo "Python output:\n$result\n";

echo "=== Test completed ===\n";
