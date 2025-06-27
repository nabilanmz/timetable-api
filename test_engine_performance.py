#!/usr/bin/env python3

"""
Simple performance test that measures just the Python engine
"""

import json
import time
import subprocess
import sys
import os

def main():
    print("=== PYTHON ENGINE PERFORMANCE TEST ===\n")
    
    # Create a minimal test input
    test_input = {
        "classes": [
            {
                "code": "LMPU3192",
                "section": "FCI1", 
                "activity": "Lecture",
                "subject": "Philosophy",
                "days": "Thursday",
                "start_time": "10:00:00",
                "end_time": "12:00:00",
                "lecturer": "Prof A",
                "venue": "Room A",
                "tied_to": []
            },
            {
                "code": "LDCW6123",
                "section": "FCI1",
                "activity": "Lecture",
                "subject": "Programming", 
                "days": "Monday",
                "start_time": "15:00:00",
                "end_time": "17:00:00",
                "lecturer": "Prof B",
                "venue": "Room B",
                "tied_to": []
            },
            {
                "code": "LDCW6123",
                "section": "FCIA",
                "activity": "Tutorial",
                "subject": "Programming",
                "days": "Monday", 
                "start_time": "17:00:00",
                "end_time": "18:00:00",
                "lecturer": "Prof B",
                "venue": "Room C",
                "tied_to": []
            }
        ],
        "preferences": {
            "style": "compact",
            "enforce_ties": False,
            "subjects": ["Philosophy", "Programming"],
            "lecturers": [],
            "days": [],
            "times": []
        }
    }
    
    print(f"Testing with {len(test_input['classes'])} classes")
    print(f"Subjects: {test_input['preferences']['subjects']}")
    print(f"Enforce ties: {test_input['preferences']['enforce_ties']}")
    print()
    
    # Change to the TimetableEngine directory
    os.chdir('app/Http/Controllers/TimetableEngine')
    
    # Time the execution
    start_time = time.time()
    
    try:
        # Run the Python engine directly
        result = subprocess.run(
            ['python', 'main.py'],
            input=json.dumps(test_input),
            text=True,
            capture_output=True,
            timeout=30
        )
        
        execution_time = time.time() - start_time
        
        print(f"⏱️  Execution time: {execution_time:.3f} seconds")
        print(f"Exit code: {result.returncode}")
        
        if result.returncode == 0:
            try:
                output = json.loads(result.stdout)
                if output.get('status') == 'success':
                    print("✅ Timetable generated successfully!")
                    timetable = output.get('timetable', {})
                    total_classes = sum(len(day_schedule) for day_schedule in timetable.values())
                    print(f"Generated {total_classes} class sessions")
                    
                    # Show a sample of the timetable
                    for day, classes in list(timetable.items())[:2]:
                        if classes:
                            print(f"  {day}: {len(classes)} classes")
                else:
                    print(f"❌ Generation failed: {output.get('message', 'Unknown error')}")
            except json.JSONDecodeError:
                print("❌ Could not parse output as JSON")
                print(f"Raw output: {result.stdout[:200]}...")
        else:
            print(f"❌ Process failed with exit code {result.returncode}")
            if result.stderr:
                print(f"STDERR: {result.stderr}")
                
    except subprocess.TimeoutExpired:
        execution_time = time.time() - start_time
        print(f"❌ Process timed out after {execution_time:.3f} seconds")
    except Exception as e:
        execution_time = time.time() - start_time
        print(f"❌ Error: {e}")

if __name__ == "__main__":
    main()
