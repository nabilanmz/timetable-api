<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Subject;
use App\Models\Section;
use App\Models\Lecturer;
use Carbon\Carbon;

class RealDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Section::truncate();
        Subject::truncate();
        Lecturer::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $csvFile = database_path('seeders/classes.csv');
        
        if (!file_exists($csvFile)) {
            $this->command->error("CSV file not found at: $csvFile");
            return;
        }

        $this->command->info('Loading real course data from CSV...');

        $handle = fopen($csvFile, 'r');
        $header = fgetcsv($handle); // Skip header row
        
        $subjects = [];
        $lecturers = [];
        $sectionsData = [];

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);
            
            // Create or get subject
            if (!isset($subjects[$data['Code']])) {
                $subject = Subject::firstOrCreate([
                    'code' => $data['Code'],
                    'name' => $data['subject'],
                ]);
                $subjects[$data['Code']] = $subject;
            }

            // Create or get lecturer
            $lecturerId = null;
            if (!empty($data['Lecturer'])) {
                if (!isset($lecturers[$data['Lecturer']])) {
                    $lecturer = Lecturer::firstOrCreate([
                        'name' => $data['Lecturer'],
                        'email' => $this->generateEmail($data['Lecturer']),
                        'department' => 'Computer Science', // Default department
                    ]);
                    $lecturers[$data['Lecturer']] = $lecturer;
                }
                $lecturerId = $lecturers[$data['Lecturer']]->id;
            }

            // Parse times
            $startTime = $this->parseTime($data['Start Time']);
            $endTime = $this->parseTime($data['End Time']);

            // Parse tied sections
            $tiedTo = [];
            if (!empty($data['Tied To'])) {
                $tiedTo = array_map('trim', explode(',', $data['Tied To']));
                $tiedTo = array_filter($tiedTo);
            }

            $sectionsData[] = [
                'id' => \Illuminate\Support\Str::uuid(),
                'subject_id' => $subjects[$data['Code']]->id,
                'section_number' => $data['Section'],
                'activity' => $data['Activity'],
                'lecturer_id' => $lecturerId,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'day_of_week' => $data['Days'],
                'venue' => $data['Venue'] ?: 'TBD',
                'capacity' => rand(30, 50),
                'tied_to' => json_encode($tiedTo),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        fclose($handle);

        // Batch insert sections
        foreach (array_chunk($sectionsData, 100) as $chunk) {
            Section::insert($chunk);
        }

        $this->command->info('Real course data loaded successfully!');
        $this->command->info('Subjects created: ' . count($subjects));
        $this->command->info('Lecturers created: ' . count($lecturers));
        $this->command->info('Sections created: ' . count($sectionsData));
    }

    private function parseTime($timeString): string
    {
        try {
            // Handle "10:00 AM" format
            if (strpos($timeString, 'AM') !== false || strpos($timeString, 'PM') !== false) {
                return Carbon::createFromFormat('g:i A', $timeString)->format('H:i:s');
            }
            // Handle "10:00" format
            return Carbon::createFromFormat('H:i', $timeString)->format('H:i:s');
        } catch (\Exception $e) {
            // Default fallback
            return '09:00:00';
        }
    }

    private function generateEmail($name): string
    {
        $email = strtolower(str_replace(' ', '.', $name));
        $email = preg_replace('/[^a-z0-9.]/', '', $email);
        return $email . '@university.edu';
    }
}
