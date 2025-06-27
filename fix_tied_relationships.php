<?php

// Fix tied relationships for subjects with incomplete data

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Subject;
use App\Models\Section;

echo "=== FIXING TIED RELATIONSHIPS ===\n\n";

// Get subjects that have lectures but no proper tied relationships
$subjectsToFix = Subject::with('sections')->whereHas('sections', function($q) {
    $q->where('activity', 'Lecture')
      ->where(function($subQ) {
          $subQ->whereNull('tied_to')
               ->orWhere('tied_to', '[]')
               ->orWhere('tied_to', '');
      });
})->get();

echo "Found " . $subjectsToFix->count() . " subjects that need fixing:\n";

foreach ($subjectsToFix as $subject) {
    echo "\n--- Fixing {$subject->code}: {$subject->name} ---\n";
    
    $lectures = $subject->sections()->where('activity', 'Lecture')->get();
    $tutorials = $subject->sections()->where('activity', 'Tutorial')->get();
    
    echo "  Lectures: " . $lectures->count() . "\n";
    echo "  Tutorials: " . $tutorials->count() . "\n";
    
    if ($lectures->count() > 0 && $tutorials->count() == 0) {
        // Case 1: Has lectures but no tutorials - create tutorials
        echo "  Strategy: Creating tutorials for lectures\n";
        
        foreach ($lectures as $lecture) {
            // Create 2 tutorial sections for each lecture
            for ($i = 1; $i <= 2; $i++) {
                $tutorialNumber = str_replace(['TC', 'FC'], 'TT', $lecture->section_number) . $i;
                
                // Find an available time slot (different from lecture)
                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                $availableDay = collect($days)->filter(function($day) use ($lecture) {
                    return $day !== $lecture->day_of_week;
                })->first();
                
                $tutorial = Section::create([
                    'subject_id' => $subject->id,
                    'section_number' => $tutorialNumber,
                    'activity' => 'Tutorial',
                    'lecturer_id' => $lecture->lecturer_id,
                    'start_time' => '14:00:00', // Different time from most lectures
                    'end_time' => '16:00:00',
                    'day_of_week' => $availableDay ?: 'Wednesday',
                    'venue' => 'Tutorial Room',
                    'capacity' => 25,
                    'tied_to' => json_encode([$lecture->section_number]),
                ]);
                
                echo "    Created tutorial: {$tutorial->section_number}\n";
            }
            
            // Update lecture to point to its tutorials
            $tutorialNames = [];
            for ($i = 1; $i <= 2; $i++) {
                $tutorialNames[] = str_replace(['TC', 'FC'], 'TT', $lecture->section_number) . $i;
            }
            
            $lecture->update([
                'tied_to' => json_encode($tutorialNames)
            ]);
            
            echo "    Updated lecture {$lecture->section_number} tied_to: " . json_encode($tutorialNames) . "\n";
        }
        
    } elseif ($lectures->count() > 0 && $tutorials->count() > 0) {
        // Case 2: Has both but relationships are broken - fix relationships
        echo "  Strategy: Fixing existing relationships\n";
        
        $tutorials = $tutorials->values(); // Reset keys
        
        foreach ($lectures as $index => $lecture) {
            // Assign tutorials to lectures in round-robin fashion
            $assignedTutorials = [];
            
            // Each lecture gets 2-3 tutorials
            $tutorialsPerLecture = max(1, intval($tutorials->count() / $lectures->count()));
            $startIndex = $index * $tutorialsPerLecture;
            
            for ($i = 0; $i < $tutorialsPerLecture && $startIndex + $i < $tutorials->count(); $i++) {
                $tutorial = $tutorials[$startIndex + $i];
                $assignedTutorials[] = $tutorial->section_number;
                
                // Update tutorial to point back to lecture
                $tutorial->update([
                    'tied_to' => json_encode([$lecture->section_number])
                ]);
            }
            
            // Update lecture to point to its tutorials
            $lecture->update([
                'tied_to' => json_encode($assignedTutorials)
            ]);
            
            echo "    Lecture {$lecture->section_number} tied to: " . implode(', ', $assignedTutorials) . "\n";
        }
    }
}

echo "\n=== VERIFICATION ===\n";

// Verify the fixes
$fixedSubjects = Subject::with('sections')->whereHas('sections', function($q) {
    $q->where('activity', 'Lecture');
})->take(5)->get();

foreach ($fixedSubjects as $subject) {
    $lectures = $subject->sections()->where('activity', 'Lecture')->get();
    $tutorials = $subject->sections()->where('activity', 'Tutorial')->get();
    
    echo "\n{$subject->code}:\n";
    echo "  Lectures: " . $lectures->count() . " | Tutorials: " . $tutorials->count() . "\n";
    
    $hasProperTies = true;
    foreach ($lectures as $lecture) {
        $tiedTo = is_string($lecture->tied_to) ? json_decode($lecture->tied_to, true) : $lecture->tied_to;
        if (empty($tiedTo)) {
            $hasProperTies = false;
        }
    }
    
    echo "  Proper ties: " . ($hasProperTies ? "✓" : "✗") . "\n";
}

echo "\n=== FIXING COMPLETE ===\n";
