<?php

// Test script to verify the API endpoint with proper input format
// This simulates a request that would come from the frontend

$apiData = [
    'preferences' => [
        'subjects' => [1, 2, 3], // Use actual subject IDs from database
        'days' => [1, 2, 3, 4, 5], // Monday to Friday
        'start_time' => '08:00',
        'end_time' => '18:00',
        'enforce_ties' => 'yes',
        'lecturers' => [], // Optional - no preference
        'mode' => 1, // 1=compact, 2=spaced_out
    ]
];

echo "Test API Request Data:\n";
echo json_encode($apiData, JSON_PRETTY_PRINT);
echo "\n\nThis would be sent to POST /api/generated-timetables\n";
echo "Headers: Content-Type: application/json, Authorization: Bearer <token>\n";
