#!/usr/bin/env python3

"""
Simple test to see if the timetable generator works with our real data
"""

import json
import subprocess
import sys

def main():
    print("=== TESTING PYTHON TIMETABLE ENGINE ===\n")
    
    # Read our test input
    with open('test_input_real.json', 'r') as f:
        input_data = json.load(f)
    
    print(f"Input data:")
    print(f"- Classes: {len(input_data['classes'])}")
    print(f"- Subjects: {input_data['preferences']['subjects']}")
    print(f"- Enforce ties: {input_data['preferences']['enforce_ties']}")
    print()
    
    print("Sample classes:")
    for i, cls in enumerate(input_data['classes'][:3]):
        print(f"  {i+1}. {cls['code']} - {cls['activity']} {cls['section']}")
        print(f"     {cls['days']} {cls['start_time']}-{cls['end_time']}")
        print(f"     Tied to: {cls['tied_to']}")
    print()
    
    # Run the actual generator
    print("Running TimetableGenerator.py...")
    try:
        result = subprocess.run(
            ['.venv/bin/python', 'app/Http/Controllers/TimetableGenerator.py'],
            input=json.dumps(input_data),
            text=True,
            capture_output=True,
            timeout=30
        )
        
        print(f"Exit code: {result.returncode}")
        print(f"STDOUT:\n{result.stdout}")
        if result.stderr:
            print(f"STDERR:\n{result.stderr}")
            
        if result.returncode == 0:
            try:
                output = json.loads(result.stdout)
                print("\n=== GENERATION SUCCESSFUL ===")
                if 'timetable' in output:
                    print(f"Generated timetable with {len(output['timetable'])} scheduled classes")
                    for day, classes in output['timetable'].items():
                        if classes:
                            print(f"  {day}: {len(classes)} classes")
                else:
                    print("Output format:", output)
            except json.JSONDecodeError:
                print("Could not parse output as JSON")
        else:
            print("\n=== GENERATION FAILED ===")
            
    except subprocess.TimeoutExpired:
        print("Generation timed out after 30 seconds")
    except Exception as e:
        print(f"Error running generator: {e}")

if __name__ == "__main__":
    main()
