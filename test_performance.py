#!/usr/bin/env python3

"""
Quick performance test for the optimized genetic algorithm
"""

import json
import time
import subprocess
import sys

def main():
    print("=== PERFORMANCE TEST FOR OPTIMIZED GA ===\n")
    
    # Simple test input with enforce_ties=False
    test_input = {
        "subjects": [
            "Falsafah dan Isu Semasa",
            "Fundamentals of Digital Competence for Programmers",
            "Bahasa Kebangsaan A"
        ],
        "preferences": {
            "style": "compact",
            "enforce_ties": False,
            "lecturers": [],
            "days": [],
            "times": []
        }
    }
    
    print(f"Testing with {len(test_input['subjects'])} subjects")
    print(f"Enforce ties: {test_input['preferences']['enforce_ties']}")
    print()
    
    # Time the execution
    start_time = time.time()
    
    try:
        # Run via Laravel API
        result = subprocess.run(
            ['php', 'test_full_flow.php'],
            input=json.dumps(test_input),
            text=True,
            capture_output=True,
            timeout=60
        )
        
        execution_time = time.time() - start_time
        
        print(f"Execution time: {execution_time:.2f} seconds")
        print(f"Exit code: {result.returncode}")
        
        if result.returncode == 0:
            try:
                output = json.loads(result.stdout)
                if output.get('status') == 'success':
                    print("✅ Timetable generated successfully!")
                    timetable = output.get('timetable', {})
                    total_classes = sum(len(day_schedule) for day_schedule in timetable.values())
                    print(f"Generated {total_classes} class sessions")
                else:
                    print(f"❌ Generation failed: {output.get('message', 'Unknown error')}")
            except json.JSONDecodeError:
                print("❌ Could not parse output as JSON")
                print(f"Raw output: {result.stdout[:500]}...")
        else:
            print(f"❌ Process failed with exit code {result.returncode}")
            if result.stderr:
                print(f"STDERR: {result.stderr}")
                
    except subprocess.TimeoutExpired:
        execution_time = time.time() - start_time
        print(f"❌ Process timed out after {execution_time:.2f} seconds")
    except Exception as e:
        execution_time = time.time() - start_time
        print(f"❌ Error: {e}")

if __name__ == "__main__":
    main()
