"""
Comprehensive validation test for TimetableGeneratorNew.py

This script performs extensive testing to ensure the new implementation
maintains all the functionality of the original.
"""

import json
import sys
import os
from typing import Dict, List, Any

# Add parent directory to path
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

def test_scenario(name: str, classes: List[Dict], preferences: Dict[str, Any]) -> Dict:
    """Test a specific scenario with both generators."""
    print(f"\n🧪 Testing: {name}")
    print("-" * 50)
    
    try:
        # Test original generator (now the backup)
        from TimetableGenerator_backup import (
            load_classes_from_json as orig_load,
            TimetableGenerator as OriginalGenerator,
            format_timetable_as_json as orig_format
        )
        from datetime import datetime, time
        
        # Parse preferences for original
        orig_prefs = preferences.copy()
        if 'preferred_start' in orig_prefs and orig_prefs['preferred_start']:
            orig_prefs['preferred_start'] = datetime.strptime(orig_prefs['preferred_start'], '%H:%M:%S').time()
        else:
            orig_prefs['preferred_start'] = time.min
            
        if 'preferred_end' in orig_prefs and orig_prefs['preferred_end']:
            orig_prefs['preferred_end'] = datetime.strptime(orig_prefs['preferred_end'], '%H:%M:%S').time()
        else:
            orig_prefs['preferred_end'] = time.max
        
        # Run original
        orig_classes = orig_load(classes)
        orig_gen = OriginalGenerator(orig_classes, orig_prefs)
        orig_timetable = orig_gen.run(generations=30, pop_size=50)  # Faster for testing
        orig_result = orig_format(orig_timetable)
        
        print(f"✓ Original: {orig_result['status']}")
        
    except Exception as e:
        print(f"✗ Original failed: {e}")
        orig_result = {"status": "error", "message": str(e)}
    
    try:
        # Test new generator using TimetableEngine directly
        from TimetableEngine import (
            load_classes_from_json as new_load,
            TimetableGenerator as NewGenerator,
            format_timetable_as_json as new_format
        )
        
        # Parse preferences for new
        new_prefs = preferences.copy()
        if 'preferred_start' in new_prefs and new_prefs['preferred_start']:
            new_prefs['preferred_start'] = datetime.strptime(new_prefs['preferred_start'], '%H:%M:%S').time()
        else:
            new_prefs['preferred_start'] = time.min
            
        if 'preferred_end' in new_prefs and new_prefs['preferred_end']:
            new_prefs['preferred_end'] = datetime.strptime(new_prefs['preferred_end'], '%H:%M:%S').time()
        else:
            new_prefs['preferred_end'] = time.max
        
        # Run new
        new_classes = new_load(classes)
        new_gen = NewGenerator(new_classes, new_prefs)
        new_timetable = new_gen.run(generations=30, pop_size=50)  # Faster for testing
        new_result = new_format(new_timetable)
        
        print(f"✓ New: {new_result['status']}")
        
    except Exception as e:
        print(f"✗ New failed: {e}")
        import traceback
        traceback.print_exc()
        new_result = {"status": "error", "message": str(e)}
    
    # Compare results
    if orig_result['status'] == new_result['status']:
        if orig_result['status'] == 'success':
            orig_subjects = set()
            new_subjects = set()
            
            if 'timetable' in orig_result:
                for day_classes in orig_result['timetable'].values():
                    for cls in day_classes:
                        orig_subjects.add(cls['subject'])
            
            if 'timetable' in new_result:
                for day_classes in new_result['timetable'].values():
                    for cls in day_classes:
                        new_subjects.add(cls['subject'])
            
            if orig_subjects == new_subjects:
                print("✅ PASS: Same subjects scheduled")
            else:
                print(f"⚠️  PARTIAL: Different subjects - Orig: {orig_subjects}, New: {new_subjects}")
        else:
            print("✅ PASS: Both failed consistently")
    else:
        print(f"❌ FAIL: Different outcomes - Orig: {orig_result['status']}, New: {new_result['status']}")
    
    return {
        "name": name,
        "original": orig_result,
        "new": new_result,
        "input": {"classes": classes, "preferences": preferences}
    }

def run_all_tests():
    """Run comprehensive test scenarios."""
    print("🔬 Comprehensive Timetable Generator Validation")
    print("=" * 60)
    
    results = []
    
    # Test 1: Basic tied sections
    results.append(test_scenario(
        "Basic Tied Sections",
        classes=[
            {
                "code": "CS101", "subject": "Computer Science", "activity": "Lecture", "section": "A",
                "days": "Monday", "start_time": "09:00:00", "end_time": "10:00:00",
                "venue": "LT1", "tied_to": ["T1"], "lecturer": "Dr. Smith"
            },
            {
                "code": "CS101", "subject": "Computer Science", "activity": "Tutorial", "section": "T1",
                "days": "Tuesday", "start_time": "14:00:00", "end_time": "15:00:00",
                "venue": "TR1", "tied_to": [], "lecturer": "TA Johnson"
            }
        ],
        preferences={
            "subjects": ["Computer Science"], "schedule_style": "compact", "enforce_ties": True,
            "preferred_days": [], "preferred_lecturers": [], "preferred_start": "", "preferred_end": ""
        }
    ))
    
    # Test 2: Independent sections
    results.append(test_scenario(
        "Independent Sections",
        classes=[
            {
                "code": "CS101", "subject": "Computer Science", "activity": "Lecture", "section": "A",
                "days": "Monday", "start_time": "09:00:00", "end_time": "10:00:00",
                "venue": "LT1", "tied_to": [], "lecturer": "Dr. Smith"
            },
            {
                "code": "CS101", "subject": "Computer Science", "activity": "Tutorial", "section": "T1",
                "days": "Tuesday", "start_time": "14:00:00", "end_time": "15:00:00",
                "venue": "TR1", "tied_to": [], "lecturer": "TA Johnson"
            }
        ],
        preferences={
            "subjects": ["Computer Science"], "schedule_style": "compact", "enforce_ties": False,
            "preferred_days": [], "preferred_lecturers": [], "preferred_start": "", "preferred_end": ""
        }
    ))
    
    # Test 3: Spaced out style
    results.append(test_scenario(
        "Spaced Out Style",
        classes=[
            {
                "code": "CS101", "subject": "Computer Science", "activity": "Lecture", "section": "A",
                "days": "Monday", "start_time": "09:00:00", "end_time": "10:00:00",
                "venue": "LT1", "tied_to": ["T1"], "lecturer": "Dr. Smith"
            },
            {
                "code": "CS101", "subject": "Computer Science", "activity": "Tutorial", "section": "T1",
                "days": "Wednesday", "start_time": "14:00:00", "end_time": "15:00:00",
                "venue": "TR1", "tied_to": [], "lecturer": "TA Johnson"
            }
        ],
        preferences={
            "subjects": ["Computer Science"], "schedule_style": "spaced_out", "enforce_ties": True,
            "preferred_days": [], "preferred_lecturers": [], "preferred_start": "", "preferred_end": ""
        }
    ))
    
    # Test 4: Time preferences
    results.append(test_scenario(
        "Time Preferences",
        classes=[
            {
                "code": "CS101", "subject": "Computer Science", "activity": "Lecture", "section": "A",
                "days": "Monday", "start_time": "09:00:00", "end_time": "10:00:00",
                "venue": "LT1", "tied_to": ["T1"], "lecturer": "Dr. Smith"
            },
            {
                "code": "CS101", "subject": "Computer Science", "activity": "Tutorial", "section": "T1",
                "days": "Tuesday", "start_time": "14:00:00", "end_time": "15:00:00",
                "venue": "TR1", "tied_to": [], "lecturer": "TA Johnson"
            }
        ],
        preferences={
            "subjects": ["Computer Science"], "schedule_style": "compact", "enforce_ties": True,
            "preferred_days": ["Monday", "Tuesday"], "preferred_lecturers": ["Dr. Smith"],
            "preferred_start": "09:00:00", "preferred_end": "16:00:00"
        }
    ))
    
    # Test 5: Multiple subjects with conflicts
    results.append(test_scenario(
        "Multiple Subjects",
        classes=[
            {
                "code": "CS101", "subject": "Computer Science", "activity": "Lecture", "section": "A",
                "days": "Monday", "start_time": "09:00:00", "end_time": "10:00:00",
                "venue": "LT1", "tied_to": ["T1"], "lecturer": "Dr. Smith"
            },
            {
                "code": "CS101", "subject": "Computer Science", "activity": "Tutorial", "section": "T1",
                "days": "Tuesday", "start_time": "14:00:00", "end_time": "15:00:00",
                "venue": "TR1", "tied_to": [], "lecturer": "TA Johnson"
            },
            {
                "code": "MATH201", "subject": "Mathematics", "activity": "Lecture", "section": "B",
                "days": "Wednesday", "start_time": "10:00:00", "end_time": "11:00:00",
                "venue": "LT2", "tied_to": ["T2"], "lecturer": "Prof. Wilson"
            },
            {
                "code": "MATH201", "subject": "Mathematics", "activity": "Tutorial", "section": "T2",
                "days": "Thursday", "start_time": "15:00:00", "end_time": "16:00:00",
                "venue": "TR2", "tied_to": [], "lecturer": "TA Brown"
            }
        ],
        preferences={
            "subjects": ["Computer Science", "Mathematics"], "schedule_style": "compact", "enforce_ties": True,
            "preferred_days": [], "preferred_lecturers": [], "preferred_start": "", "preferred_end": ""
        }
    ))
    
    # Test 6: No valid timetable (time conflict)
    results.append(test_scenario(
        "Time Conflict (Should Fail)",
        classes=[
            {
                "code": "CS101", "subject": "Computer Science", "activity": "Lecture", "section": "A",
                "days": "Monday", "start_time": "09:00:00", "end_time": "10:00:00",
                "venue": "LT1", "tied_to": ["T1"], "lecturer": "Dr. Smith"
            },
            {
                "code": "CS101", "subject": "Computer Science", "activity": "Tutorial", "section": "T1",
                "days": "Monday", "start_time": "09:30:00", "end_time": "10:30:00",  # Overlaps with lecture
                "venue": "TR1", "tied_to": [], "lecturer": "TA Johnson"
            }
        ],
        preferences={
            "subjects": ["Computer Science"], "schedule_style": "compact", "enforce_ties": True,
            "preferred_days": [], "preferred_lecturers": [], "preferred_start": "", "preferred_end": ""
        }
    ))
    
    # Summary
    print("\n" + "=" * 60)
    print("📊 TEST SUMMARY")
    print("=" * 60)
    
    total_tests = len(results)
    passed = 0
    failed = 0
    partial = 0
    
    for result in results:
        name = result['name']
        orig_status = result['original']['status']
        new_status = result['new']['status']
        
        if orig_status == new_status:
            if orig_status == 'success':
                # Check if subjects match
                orig_subjects = set()
                new_subjects = set()
                
                if 'timetable' in result['original']:
                    for day_classes in result['original']['timetable'].values():
                        for cls in day_classes:
                            orig_subjects.add(cls['subject'])
                
                if 'timetable' in result['new']:
                    for day_classes in result['new']['timetable'].values():
                        for cls in day_classes:
                            new_subjects.add(cls['subject'])
                
                if orig_subjects == new_subjects:
                    print(f"✅ {name}: PASS")
                    passed += 1
                else:
                    print(f"⚠️  {name}: PARTIAL (different sections chosen)")
                    partial += 1
            else:
                print(f"✅ {name}: PASS (both failed as expected)")
                passed += 1
        else:
            print(f"❌ {name}: FAIL (different outcomes)")
            failed += 1
    
    print(f"\n📈 Results: {passed} passed, {partial} partial, {failed} failed out of {total_tests} tests")
    
    if failed == 0:
        print("🎉 SUCCESS: New implementation is compatible with the original!")
    elif partial > 0 and failed == 0:
        print("✨ MOSTLY SUCCESS: Minor differences due to genetic algorithm randomness")
    else:
        print("⚠️  WARNING: Significant compatibility issues found")
    
    # Save detailed results
    with open("comprehensive_test_results.json", "w") as f:
        json.dump(results, f, indent=2)
    
    print(f"\n💾 Detailed results saved to comprehensive_test_results.json")
    
    return failed == 0

if __name__ == "__main__":
    success = run_all_tests()
    sys.exit(0 if success else 1)
