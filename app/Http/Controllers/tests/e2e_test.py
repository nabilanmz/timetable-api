"""
Simple end-to-end test for both timetable generators

This script tests the command-line interface of both generators
to ensure they produce consistent results when called as scripts.
"""

import json
import subprocess
import sys
import os

def create_test_data():
    """Create test input data."""
    return {
        "classes": [
            {
                "code": "CS101",
                "subject": "Computer Science",
                "activity": "Lecture",
                "section": "A",
                "days": "Monday",
                "start_time": "09:00:00",
                "end_time": "10:00:00",
                "venue": "LT1",
                "tied_to": ["T1"],
                "lecturer": "Dr. Smith"
            },
            {
                "code": "CS101",
                "subject": "Computer Science",
                "activity": "Tutorial",
                "section": "T1",
                "days": "Tuesday",
                "start_time": "14:00:00",
                "end_time": "15:00:00",
                "venue": "TR1",
                "tied_to": [],
                "lecturer": "TA Johnson"
            },
            {
                "code": "MATH201",
                "subject": "Mathematics",
                "activity": "Lecture",
                "section": "B",
                "days": "Wednesday",
                "start_time": "10:00:00",
                "end_time": "11:00:00",
                "venue": "LT2",
                "tied_to": ["T2"],
                "lecturer": "Prof. Wilson"
            },
            {
                "code": "MATH201",
                "subject": "Mathematics",
                "activity": "Tutorial",
                "section": "T2",
                "days": "Thursday",
                "start_time": "15:00:00",
                "end_time": "16:00:00",
                "venue": "TR2",
                "tied_to": [],
                "lecturer": "TA Brown"
            }
        ],
        "preferences": {
            "subjects": ["Computer Science", "Mathematics"],
            "schedule_style": "compact",
            "enforce_ties": True,
            "preferred_days": ["Monday", "Tuesday", "Wednesday", "Thursday"],
            "preferred_lecturers": ["Dr. Smith"],
            "preferred_start": "09:00:00",
            "preferred_end": "17:00:00"
        }
    }

def run_generator(script_path, input_data):
    """Run a timetable generator script."""
    try:
        input_json = json.dumps(input_data)
        
        result = subprocess.run(
            [sys.executable, script_path],
            input=input_json,
            capture_output=True,
            text=True,
            timeout=60
        )
        
        if result.returncode != 0:
            return {
                "success": False,
                "error": f"Script failed with return code {result.returncode}",
                "stderr": result.stderr,
                "stdout": result.stdout
            }
        
        try:
            output = json.loads(result.stdout)
            return {"success": True, "output": output}
        except json.JSONDecodeError as e:
            return {
                "success": False,
                "error": f"Invalid JSON output: {e}",
                "stdout": result.stdout
            }
            
    except subprocess.TimeoutExpired:
        return {"success": False, "error": "Script timed out"}
    except Exception as e:
        return {"success": False, "error": f"Unexpected error: {e}"}

def main():
    """Run the end-to-end test."""
    print("End-to-End Timetable Generator Test")
    print("=" * 40)
    
    # Get script paths
    base_dir = os.path.dirname(os.path.dirname(__file__))
    original_script = os.path.join(base_dir, "TimetableGenerator_backup.py")
    new_script = os.path.join(base_dir, "TimetableGenerator.py")
    
    # Check if scripts exist
    if not os.path.exists(original_script):
        print(f"✗ Original script not found: {original_script}")
        return
    
    if not os.path.exists(new_script):
        print(f"✗ New script not found: {new_script}")
        return
    
    print(f"✓ Original script: {original_script}")
    print(f"✓ New script: {new_script}")
    
    # Create test data
    test_data = create_test_data()
    print(f"✓ Test data created with {len(test_data['classes'])} classes")
    
    # Run original generator
    print("\nTesting original TimetableGenerator.py...")
    original_result = run_generator(original_script, test_data)
    
    if original_result["success"]:
        print("✓ Original generator ran successfully")
        original_output = original_result["output"]
        print(f"  Status: {original_output.get('status', 'unknown')}")
    else:
        print("✗ Original generator failed")
        print(f"  Error: {original_result['error']}")
        if 'stderr' in original_result:
            print(f"  STDERR: {original_result['stderr']}")
    
    # Run new generator
    print("\nTesting new TimetableGeneratorNew.py...")
    new_result = run_generator(new_script, test_data)
    
    if new_result["success"]:
        print("✓ New generator ran successfully")
        new_output = new_result["output"]
        print(f"  Status: {new_output.get('status', 'unknown')}")
    else:
        print("✗ New generator failed")
        print(f"  Error: {new_result['error']}")
        if 'stderr' in new_result:
            print(f"  STDERR: {new_result['stderr']}")
    
    # Compare results
    print("\n" + "=" * 40)
    print("COMPARISON")
    print("=" * 40)
    
    if original_result["success"] and new_result["success"]:
        orig_status = original_result["output"].get("status")
        new_status = new_result["output"].get("status")
        
        if orig_status == new_status:
            print(f"✓ Both generators returned status: {orig_status}")
            
            if orig_status == "success":
                # Compare timetables
                orig_timetable = original_result["output"]["timetable"]
                new_timetable = new_result["output"]["timetable"]
                
                # Count classes
                orig_classes = sum(len(day) for day in orig_timetable.values())
                new_classes = sum(len(day) for day in new_timetable.values())
                
                print(f"  Original classes scheduled: {orig_classes}")
                print(f"  New classes scheduled: {new_classes}")
                
                if orig_classes == new_classes:
                    print("✓ Same number of classes scheduled")
                else:
                    print("⚠ Different number of classes scheduled")
                
                # Check subjects
                orig_subjects = set()
                new_subjects = set()
                
                for day_classes in orig_timetable.values():
                    for cls in day_classes:
                        orig_subjects.add(cls["subject"])
                
                for day_classes in new_timetable.values():
                    for cls in day_classes:
                        new_subjects.add(cls["subject"])
                
                if orig_subjects == new_subjects:
                    print("✓ Same subjects scheduled")
                else:
                    print("⚠ Different subjects scheduled")
                    print(f"  Original: {sorted(orig_subjects)}")
                    print(f"  New: {sorted(new_subjects)}")
        else:
            print(f"✗ Different statuses: Original={orig_status}, New={new_status}")
    
    elif not original_result["success"] and not new_result["success"]:
        print("⚠ Both generators failed")
    
    else:
        print("✗ Inconsistent results: One succeeded, one failed")
    
    # Save detailed results
    results = {
        "test_data": test_data,
        "original_result": original_result,
        "new_result": new_result
    }
    
    with open("e2e_test_results.json", "w") as f:
        json.dump(results, f, indent=2)
    
    print(f"\nDetailed results saved to e2e_test_results.json")

if __name__ == "__main__":
    main()
