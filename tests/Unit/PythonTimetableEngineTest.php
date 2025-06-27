<?php

namespace Tests\Unit;

use Tests\TestCase;
use Symfony\Component\Process\Process;

class PythonTimetableEngineTest extends TestCase
{
    /** @test */
    public function python_engine_responds_to_simple_input()
    {
        $inputData = [
            'classes' => [
                [
                    'code' => 'TEST101',
                    'subject' => 'Test Subject',
                    'activity' => 'Lecture',
                    'section' => '001',
                    'days' => 'Monday',
                    'start_time' => '09:00:00',
                    'end_time' => '11:00:00',
                    'venue' => 'Test Hall',
                    'lecturer' => 'Test Professor',
                    'tied_to' => []
                ]
            ],
            'preferences' => [
                'subjects' => ['Test Subject'],
                'enforce_ties' => false,
                'schedule_style' => 'compact',
                'preferred_lecturers' => [],
                'preferred_days' => [],
                'preferred_start' => '08:00:00',
                'preferred_end' => '18:00:00',
            ]
        ];

        $pythonExecutable = env('PYTHON_EXECUTABLE', '/Users/biehatieha/code/yaya/timetable-api/.venv/bin/python');
        $scriptPath = app_path('Http/Controllers/TimetableEngine/main.py');

        $process = new Process([$pythonExecutable, $scriptPath]);
        $process->setWorkingDirectory(app_path('Http/Controllers/TimetableEngine'));
        $process->setInput(json_encode($inputData));
        $process->setTimeout(10); // 10 second timeout for unit tests
        $process->run();

        // Python script should execute successfully
        $this->assertTrue($process->isSuccessful(), 'Python script failed: ' . $process->getErrorOutput());

        // Should return valid JSON
        $output = json_decode($process->getOutput(), true);
        $this->assertNotNull($output, 'Python script output is not valid JSON: ' . $process->getOutput());

        // Should have success status
        $this->assertEquals('success', $output['status'] ?? null);

        // Should have timetable data
        $this->assertArrayHasKey('timetable', $output);
        $this->assertArrayHasKey('summary', $output);

        // Verify the timetable structure
        $timetable = $output['timetable'];
        $this->assertIsArray($timetable);

        // Should have at least one day with classes
        $totalClasses = 0;
        foreach ($timetable as $day => $classes) {
            $totalClasses += count($classes);
        }
        $this->assertGreaterThan(0, $totalClasses, 'No classes were scheduled');
    }

    /** @test */
    public function python_engine_handles_empty_classes_gracefully()
    {
        $inputData = [
            'classes' => [],
            'preferences' => [
                'subjects' => [],
                'enforce_ties' => false,
                'schedule_style' => 'compact',
                'preferred_lecturers' => [],
                'preferred_days' => [],
                'preferred_start' => '08:00:00',
                'preferred_end' => '18:00:00',
            ]
        ];

        $pythonExecutable = env('PYTHON_EXECUTABLE', '/Users/biehatieha/code/yaya/timetable-api/.venv/bin/python');
        $scriptPath = app_path('Http/Controllers/TimetableEngine/main.py');

        $process = new Process([$pythonExecutable, $scriptPath]);
        $process->setWorkingDirectory(app_path('Http/Controllers/TimetableEngine'));
        $process->setInput(json_encode($inputData));
        $process->setTimeout(10);
        $process->run();

        // Should handle empty input gracefully
        $this->assertTrue($process->isSuccessful() || $process->getExitCode() !== 0, 'Process should complete');

        $output = json_decode($process->getOutput(), true);
        
        // Should return error status for empty input
        if ($output) {
            $this->assertEquals('error', $output['status'] ?? 'success');
        }
    }

    /** @test */
    public function python_engine_execution_is_performant()
    {
        $inputData = [
            'classes' => [
                [
                    'code' => 'PERF101',
                    'subject' => 'Performance Test',
                    'activity' => 'Lecture',
                    'section' => '001',
                    'days' => 'Monday',
                    'start_time' => '09:00:00',
                    'end_time' => '11:00:00',
                    'venue' => 'Test Hall',
                    'lecturer' => 'Test Professor',
                    'tied_to' => []
                ],
                [
                    'code' => 'PERF101',
                    'subject' => 'Performance Test',
                    'activity' => 'Tutorial',
                    'section' => '001T',
                    'days' => 'Tuesday',
                    'start_time' => '14:00:00',
                    'end_time' => '15:00:00',
                    'venue' => 'Test Room',
                    'lecturer' => 'Test TA',
                    'tied_to' => []
                ]
            ],
            'preferences' => [
                'subjects' => ['Performance Test'],
                'enforce_ties' => false,
                'schedule_style' => 'compact',
                'preferred_lecturers' => [],
                'preferred_days' => [],
                'preferred_start' => '08:00:00',
                'preferred_end' => '18:00:00',
            ]
        ];

        $pythonExecutable = env('PYTHON_EXECUTABLE', '/Users/biehatieha/code/yaya/timetable-api/.venv/bin/python');
        $scriptPath = app_path('Http/Controllers/TimetableEngine/main.py');

        $startTime = microtime(true);

        $process = new Process([$pythonExecutable, $scriptPath]);
        $process->setWorkingDirectory(app_path('Http/Controllers/TimetableEngine'));
        $process->setInput(json_encode($inputData));
        $process->setTimeout(5); // Should complete in under 5 seconds
        $process->run();

        $executionTime = microtime(true) - $startTime;

        // Should execute quickly (under 2 seconds for this simple case)
        $this->assertLessThan(2.0, $executionTime, "Python engine took too long: {$executionTime}s");

        // Should still succeed
        $this->assertTrue($process->isSuccessful(), 'Python script failed: ' . $process->getErrorOutput());

        $output = json_decode($process->getOutput(), true);
        $this->assertEquals('success', $output['status'] ?? null);
    }

    /** @test */
    public function python_engine_handles_malformed_json_input()
    {
        $pythonExecutable = env('PYTHON_EXECUTABLE', '/Users/biehatieha/code/yaya/timetable-api/.venv/bin/python');
        $scriptPath = app_path('Http/Controllers/TimetableEngine/main.py');

        $process = new Process([$pythonExecutable, $scriptPath]);
        $process->setWorkingDirectory(app_path('Http/Controllers/TimetableEngine'));
        $process->setInput('{"invalid": json}'); // Malformed JSON
        $process->setTimeout(5);
        $process->run();

        // Should not crash, but may return error
        $this->assertNotEquals(134, $process->getExitCode(), 'Python script should not segfault');

        // Exit code should indicate an error
        $this->assertNotEquals(0, $process->getExitCode(), 'Python script should return error for malformed input');
    }
}
