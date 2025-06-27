"""
Integration test runner for Timetable Generators

This script provides a simple way to test both timetable generators
with the same input and compare their outputs.
"""

import json
import sys
import os
from typing import Dict, Any

# Add parent directory to path
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

def create_test_input() -> Dict[str, Any]:
    """Create sample test input data."""
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
                "tied_to": ["T1", "T2"],
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
                "code": "CS101",
                "subject": "Computer Science",
                "activity": "Tutorial",
                "section": "T2",
                "days": "Friday",
                "start_time": "11:00:00",
                "end_time": "12:00:00",
                "venue": "TR2",
                "tied_to": [],
                "lecturer": "TA Wilson"
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
                "tied_to": ["T3", "T4"],
                "lecturer": "Prof. Wilson"
            },
            {
                "code": "MATH201",
                "subject": "Mathematics",
                "activity": "Tutorial",
                "section": "T3",
                "days": "Thursday",
                "start_time": "15:00:00",
                "end_time": "16:00:00",
                "venue": "TR3",
                "tied_to": [],
                "lecturer": "TA Brown"
            },
            {
                "code": "MATH201",
                "subject": "Mathematics",
                "activity": "Tutorial",
                "section": "T4",
                "days": "Thursday",
                "start_time": "16:00:00",
                "end_time": "17:00:00",
                "venue": "TR4",
                "tied_to": [],
                "lecturer": "TA Davis"
            }
        ],
        "preferences": {
            "subjects": ["Computer Science", "Mathematics"],
            "schedule_style": "compact",
            "enforce_ties": True,
            "preferred_days": ["Monday", "Tuesday", "Wednesday", "Thursday"],
            "preferred_lecturers": ["Dr. Smith", "Prof. Wilson"],
            "preferred_start": "09:00:00",
            "preferred_end": "17:00:00"
        }
    }

def test_original_generator():
    """Test the original TimetableGenerator.py directly."""
    print("Testing Original TimetableGenerator...")
    
    try:
        # Import the original generator functions (now from backup)
        from TimetableGenerator_backup import (
            load_classes_from_json,
            TimetableGenerator as OriginalGenerator,
            format_timetable_as_json
        )
        from datetime import datetime, time
        
        # Create test input
        test_input = create_test_input()
        
        # Load classes
        classes = load_classes_from_json(test_input["classes"])
        print(f"✓ Loaded {len(classes)} classes")
        
        # Parse preferences
        prefs = test_input["preferences"].copy()
        if 'preferred_start' in prefs and prefs['preferred_start']:
            prefs['preferred_start'] = datetime.strptime(prefs['preferred_start'], '%H:%M:%S').time()
        else:
            prefs['preferred_start'] = time.min
            
        if 'preferred_end' in prefs and prefs['preferred_end']:
            prefs['preferred_end'] = datetime.strptime(prefs['preferred_end'], '%H:%M:%S').time()
        else:
            prefs['preferred_end'] = time.max
        
        # Generate timetable
        generator = OriginalGenerator(classes, prefs)
        timetable = generator.run(generations=50, pop_size=100)  # Reduced for faster testing
        
        if timetable:
            result = format_timetable_as_json(timetable)
            print("✓ Original generator succeeded")
            print(f"  - Status: {result['status']}")
            if result['status'] == 'success':
                total_classes = sum(len(day_classes) for day_classes in result['timetable'].values())
                utilized_days = sum(1 for day_classes in result['timetable'].values() if day_classes)
                print(f"  - Total classes scheduled: {total_classes}")
                print(f"  - Days utilized: {utilized_days}")
            return result
        else:
            print("✗ Original generator failed to produce a timetable")
            return {"status": "error", "message": "No timetable generated"}
            
    except Exception as e:
        print(f"✗ Original generator error: {e}")
        import traceback
        traceback.print_exc()
        return {"status": "error", "message": str(e)}

def test_new_generator():
    """Test the new modular TimetableEngine."""
    print("\nTesting New TimetableEngine...")
    
    try:
        # Import the new modular components directly
        from TimetableEngine import (
            load_classes_from_json,
            TimetableGenerator as NewGenerator,
            format_timetable_as_json
        )
        from datetime import datetime, time
        
        # Create test input
        test_input = create_test_input()
        
        # Load classes
        classes = load_classes_from_json(test_input["classes"])
        print(f"✓ Loaded {len(classes)} classes")
        
        # Parse preferences
        prefs = test_input["preferences"].copy()
        if 'preferred_start' in prefs and prefs['preferred_start']:
            prefs['preferred_start'] = datetime.strptime(prefs['preferred_start'], '%H:%M:%S').time()
        else:
            prefs['preferred_start'] = time.min
            
        if 'preferred_end' in prefs and prefs['preferred_end']:
            prefs['preferred_end'] = datetime.strptime(prefs['preferred_end'], '%H:%M:%S').time()
        else:
            prefs['preferred_end'] = time.max
        
        # Generate timetable
        generator = NewGenerator(classes, prefs)
        timetable = generator.run(generations=50, pop_size=100)  # Reduced for faster testing
        
        if timetable:
            result = format_timetable_as_json(timetable)
            print("✓ New generator succeeded")
            print(f"  - Status: {result['status']}")
            if result['status'] == 'success':
                total_classes = sum(len(day_classes) for day_classes in result['timetable'].values())
                utilized_days = sum(1 for day_classes in result['timetable'].values() if day_classes)
                print(f"  - Total classes scheduled: {total_classes}")
                print(f"  - Days utilized: {utilized_days}")
                if 'summary' in result:
                    print(f"  - Summary included: ✓")
            return result
        else:
            print("✗ New generator failed to produce a timetable")
            return {"status": "error", "message": "No timetable generated"}
            
    except Exception as e:
        print(f"✗ New generator error: {e}")
        import traceback
        traceback.print_exc()
        return {"status": "error", "message": str(e)}

def compare_results(original_result: Dict, new_result: Dict):
    """Compare the results from both generators."""
    print("\n" + "="*50)
    print("COMPARISON RESULTS")
    print("="*50)
    
    # Status comparison
    print(f"Original Status: {original_result.get('status', 'unknown')}")
    print(f"New Status: {new_result.get('status', 'unknown')}")
    
    if original_result.get('status') == new_result.get('status') == 'success':
        print("✓ Both generators succeeded")
        
        # Extract subjects
        orig_subjects = set()
        new_subjects = set()
        
        for day_classes in original_result['timetable'].values():
            for class_entry in day_classes:
                orig_subjects.add(class_entry['subject'])
                
        for day_classes in new_result['timetable'].values():
            for class_entry in day_classes:
                new_subjects.add(class_entry['subject'])
        
        print(f"Original subjects: {sorted(orig_subjects)}")
        print(f"New subjects: {sorted(new_subjects)}")
        
        if orig_subjects == new_subjects:
            print("✓ Both generators scheduled the same subjects")
        else:
            print("⚠ Different subjects scheduled")
        
        # Count classes and days
        orig_total = sum(len(day_classes) for day_classes in original_result['timetable'].values())
        new_total = sum(len(day_classes) for day_classes in new_result['timetable'].values())
        
        orig_days = sum(1 for day_classes in original_result['timetable'].values() if day_classes)
        new_days = sum(1 for day_classes in new_result['timetable'].values() if day_classes)
        
        print(f"Classes scheduled - Original: {orig_total}, New: {new_total}")
        print(f"Days utilized - Original: {orig_days}, New: {new_days}")
        
        if abs(orig_total - new_total) <= 1 and abs(orig_days - new_days) <= 1:
            print("✓ Similar scheduling performance")
        else:
            print("⚠ Different scheduling performance")
            
    elif original_result.get('status') == new_result.get('status') == 'error':
        print("✓ Both generators failed consistently")
    else:
        print("✗ Inconsistent behavior between generators")
    
    print("\n" + "="*50)

def main():
    """Run the integration test."""
    print("Timetable Generator Integration Test")
    print("="*40)
    
    # Test both generators
    original_result = test_original_generator()
    new_result = test_new_generator()
    
    # Compare results
    compare_results(original_result, new_result)
    
    # Save results for inspection
    results = {
        "original": original_result,
        "new": new_result,
        "test_input": create_test_input()
    }
    
    with open("test_results.json", "w") as f:
        json.dump(results, f, indent=2)
    
    print(f"Detailed results saved to test_results.json")

if __name__ == "__main__":
    main()
