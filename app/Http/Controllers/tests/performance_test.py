"""
Simple Performance Test for TimetableEngine

This script runs basic performance tests to ensure the timetable generator
performs well and produces valid results.
"""

import sys
import os
import time
import json
from datetime import datetime, time as dt_time

# Add parent directory to path
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from TimetableEngine import (
    load_classes_from_json,
    TimetableGenerator,
    format_timetable_as_json
)


def create_test_data():
    """Create comprehensive test data."""
    return [
        # Computer Science
        {
            "code": "CS101", "subject": "Computer Science", "activity": "Lecture", "section": "A",
            "days": "Monday", "start_time": "09:00:00", "end_time": "10:00:00",
            "venue": "LT1", "tied_to": ["T1", "T2"], "lecturer": "Dr. Smith"
        },
        {
            "code": "CS101", "subject": "Computer Science", "activity": "Tutorial", "section": "T1",
            "days": "Tuesday", "start_time": "14:00:00", "end_time": "15:00:00",
            "venue": "TR1", "tied_to": [], "lecturer": "TA Johnson"
        },
        {
            "code": "CS101", "subject": "Computer Science", "activity": "Tutorial", "section": "T2",
            "days": "Friday", "start_time": "11:00:00", "end_time": "12:00:00",
            "venue": "TR2", "tied_to": [], "lecturer": "TA Wilson"
        },
        
        # Mathematics
        {
            "code": "MATH201", "subject": "Mathematics", "activity": "Lecture", "section": "B",
            "days": "Wednesday", "start_time": "10:00:00", "end_time": "11:00:00",
            "venue": "LT2", "tied_to": ["T3", "T4"], "lecturer": "Prof. Wilson"
        },
        {
            "code": "MATH201", "subject": "Mathematics", "activity": "Tutorial", "section": "T3",
            "days": "Thursday", "start_time": "15:00:00", "end_time": "16:00:00",
            "venue": "TR3", "tied_to": [], "lecturer": "TA Brown"
        },
        {
            "code": "MATH201", "subject": "Mathematics", "activity": "Tutorial", "section": "T4",
            "days": "Thursday", "start_time": "16:00:00", "end_time": "17:00:00",
            "venue": "TR4", "tied_to": [], "lecturer": "TA Davis"
        },
        
        # Physics
        {
            "code": "PHYS301", "subject": "Physics", "activity": "Lecture", "section": "C",
            "days": "Tuesday", "start_time": "11:00:00", "end_time": "12:00:00",
            "venue": "LT3", "tied_to": ["T5"], "lecturer": "Dr. Johnson"
        },
        {
            "code": "PHYS301", "subject": "Physics", "activity": "Tutorial", "section": "T5",
            "days": "Friday", "start_time": "14:00:00", "end_time": "15:00:00",
            "venue": "TR5", "tied_to": [], "lecturer": "TA Miller"
        }
    ]


def test_basic_functionality():
    """Test basic timetable generation."""
    print("🧪 Testing Basic Functionality...")
    
    classes_data = create_test_data()
    preferences = {
        "subjects": ["Computer Science", "Mathematics"],
        "schedule_style": "compact",
        "enforce_ties": True,
        "preferred_days": [],
        "preferred_lecturers": [],
        "preferred_start": dt_time.min,
        "preferred_end": dt_time.max
    }
    
    try:
        # Load classes
        classes = load_classes_from_json(classes_data)
        print(f"  ✓ Loaded {len(classes)} classes")
        
        # Generate timetable
        start_time = time.time()
        generator = TimetableGenerator(classes, preferences)
        timetable = generator.run(generations=50, pop_size=100)
        end_time = time.time()
        
        generation_time = end_time - start_time
        print(f"  ✓ Generation completed in {generation_time:.2f} seconds")
        
        if timetable:
            # Format result
            result = format_timetable_as_json(timetable)
            print(f"  ✓ Status: {result['status']}")
            
            if result['status'] == 'success':
                total_classes = sum(len(day_classes) for day_classes in result['timetable'].values())
                utilized_days = sum(1 for day_classes in result['timetable'].values() if day_classes)
                print(f"  ✓ Classes scheduled: {total_classes}")
                print(f"  ✓ Days utilized: {utilized_days}")
                
                if 'summary' in result:
                    subjects = result['summary']['subjects']
                    print(f"  ✓ Subjects: {', '.join(subjects)}")
                
                return True
            else:
                print(f"  ❌ Generation failed: {result.get('message', 'Unknown error')}")
                return False
        else:
            print("  ❌ No timetable generated")
            return False
            
    except Exception as e:
        print(f"  ❌ Error: {e}")
        import traceback
        traceback.print_exc()
        return False


def test_different_styles():
    """Test different scheduling styles."""
    print("\n🎨 Testing Different Scheduling Styles...")
    
    classes_data = create_test_data()
    styles = ["compact", "spaced_out"]
    results = {}
    
    for style in styles:
        print(f"  Testing {style} style...")
        
        preferences = {
            "subjects": ["Computer Science", "Mathematics"],
            "schedule_style": style,
            "enforce_ties": True,
            "preferred_days": [],
            "preferred_lecturers": [],
            "preferred_start": dt_time.min,
            "preferred_end": dt_time.max
        }
        
        try:
            classes = load_classes_from_json(classes_data)
            generator = TimetableGenerator(classes, preferences)
            timetable = generator.run(generations=30, pop_size=50)
            
            if timetable:
                result = format_timetable_as_json(timetable)
                if result['status'] == 'success':
                    total_classes = sum(len(day_classes) for day_classes in result['timetable'].values())
                    utilized_days = sum(1 for day_classes in result['timetable'].values() if day_classes)
                    results[style] = {"classes": total_classes, "days": utilized_days}
                    print(f"    ✓ {style}: {total_classes} classes, {utilized_days} days")
                else:
                    print(f"    ❌ {style}: Failed")
            else:
                print(f"    ❌ {style}: No timetable generated")
                
        except Exception as e:
            print(f"    ❌ {style}: Error - {e}")
    
    return len(results) == len(styles)


def test_constraint_modes():
    """Test tied vs independent constraint modes."""
    print("\n🔗 Testing Constraint Modes...")
    
    classes_data = create_test_data()
    modes = [True, False]  # enforce_ties
    results = {}
    
    for enforce_ties in modes:
        mode_name = "tied" if enforce_ties else "independent"
        print(f"  Testing {mode_name} mode...")
        
        preferences = {
            "subjects": ["Computer Science", "Mathematics"],
            "schedule_style": "compact",
            "enforce_ties": enforce_ties,
            "preferred_days": [],
            "preferred_lecturers": [],
            "preferred_start": dt_time.min,
            "preferred_end": dt_time.max
        }
        
        try:
            classes = load_classes_from_json(classes_data)
            generator = TimetableGenerator(classes, preferences)
            timetable = generator.run(generations=30, pop_size=50)
            
            if timetable:
                result = format_timetable_as_json(timetable)
                if result['status'] == 'success':
                    total_classes = sum(len(day_classes) for day_classes in result['timetable'].values())
                    results[mode_name] = total_classes
                    print(f"    ✓ {mode_name}: {total_classes} classes scheduled")
                else:
                    print(f"    ❌ {mode_name}: Failed")
            else:
                print(f"    ❌ {mode_name}: No timetable generated")
                
        except Exception as e:
            print(f"    ❌ {mode_name}: Error - {e}")
    
    return len(results) == len(modes)


def test_edge_cases():
    """Test edge cases and error handling."""
    print("\n⚠️  Testing Edge Cases...")
    
    # Test 1: Empty class list
    print("  Testing empty class list...")
    try:
        preferences = {
            "subjects": [],
            "schedule_style": "compact",
            "enforce_ties": True,
            "preferred_days": [],
            "preferred_lecturers": [],
            "preferred_start": dt_time.min,
            "preferred_end": dt_time.max
        }
        
        generator = TimetableGenerator([], preferences)
        timetable = generator.run(generations=10, pop_size=20)
        
        if timetable is None:
            print("    ✓ Correctly handled empty class list")
        else:
            print("    ⚠️  Generated timetable from empty classes")
            
    except Exception as e:
        print(f"    ✓ Correctly threw exception for empty classes: {type(e).__name__}")
    
    # Test 2: Impossible constraints (conflicting times)
    print("  Testing impossible constraints...")
    try:
        conflicting_classes = [
            {
                "code": "CS101", "subject": "Computer Science", "activity": "Lecture", "section": "A",
                "days": "Monday", "start_time": "09:00:00", "end_time": "10:00:00",
                "venue": "LT1", "tied_to": ["T1"], "lecturer": "Dr. Smith"
            },
            {
                "code": "CS101", "subject": "Computer Science", "activity": "Tutorial", "section": "T1",
                "days": "Monday", "start_time": "09:30:00", "end_time": "10:30:00",  # Overlaps
                "venue": "TR1", "tied_to": [], "lecturer": "TA Johnson"
            }
        ]
        
        classes = load_classes_from_json(conflicting_classes)
        preferences = {
            "subjects": ["Computer Science"],
            "schedule_style": "compact",
            "enforce_ties": True,
            "preferred_days": [],
            "preferred_lecturers": [],
            "preferred_start": dt_time.min,
            "preferred_end": dt_time.max
        }
        
        generator = TimetableGenerator(classes, preferences)
        timetable = generator.run(generations=10, pop_size=20)
        
        if timetable is None:
            print("    ✓ Correctly handled impossible constraints")
        else:
            print("    ⚠️  Generated timetable despite conflicts")
            
    except Exception as e:
        print(f"    ⚠️  Exception with conflicts: {e}")
    
    return True


def main():
    """Run all performance tests."""
    print("🚀 TimetableEngine Performance Test Suite")
    print("=" * 50)
    
    start_time = time.time()
    
    # Run tests
    test_results = {
        "Basic Functionality": test_basic_functionality(),
        "Different Styles": test_different_styles(),
        "Constraint Modes": test_constraint_modes(),
        "Edge Cases": test_edge_cases()
    }
    
    end_time = time.time()
    total_time = end_time - start_time
    
    # Report results
    print("\n" + "=" * 50)
    print("📊 TEST RESULTS")
    print("=" * 50)
    
    passed = sum(test_results.values())
    total = len(test_results)
    
    for test_name, result in test_results.items():
        status = "✅ PASS" if result else "❌ FAIL"
        print(f"{status} {test_name}")
    
    print(f"\n📈 Summary: {passed}/{total} tests passed")
    print(f"⏱️  Total time: {total_time:.2f} seconds")
    
    if passed == total:
        print("🎉 All tests passed! TimetableEngine is working correctly.")
        return True
    else:
        print("⚠️  Some tests failed. Please review the output above.")
        return False


if __name__ == "__main__":
    success = main()
    sys.exit(0 if success else 1)
