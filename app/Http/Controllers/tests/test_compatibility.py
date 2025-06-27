"""
Test suite to ensure TimetableGeneratorNew.py behaves identically to TimetableGenerator.py

This test suite compares the outputs of both implementations to ensure 
backward compatibility and feature parity.
"""

import sys
import os
import json
import subprocess
import unittest
from datetime import time
from typing import Dict, Any

# Add the parent directory to the path so we can import the modules
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))


class TestTimetableGeneratorCompatibility(unittest.TestCase):
    """Test compatibility between original and new timetable generators."""

    def setUp(self):
        """Set up test data and paths."""
        self.original_script = os.path.join(
            os.path.dirname(os.path.dirname(__file__)), "TimetableGenerator_backup.py"
        )
        self.new_script = os.path.join(
            os.path.dirname(os.path.dirname(__file__)), "TimetableGenerator.py"
        )
        
        # Sample test data
        self.sample_classes = [
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
                "code": "MATH201",
                "subject": "Mathematics",
                "activity": "Lecture",
                "section": "B",
                "days": "Wednesday",
                "start_time": "10:00:00",
                "end_time": "11:00:00",
                "venue": "LT2",
                "tied_to": ["T3"],
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
                "venue": "TR2",
                "tied_to": [],
                "lecturer": "TA Brown"
            }
        ]
        
        self.sample_preferences = {
            "subjects": ["Computer Science", "Mathematics"],
            "schedule_style": "compact",
            "enforce_ties": True,
            "preferred_days": ["Monday", "Tuesday", "Wednesday"],
            "preferred_lecturers": ["Dr. Smith"],
            "preferred_start": "09:00:00",
            "preferred_end": "17:00:00"
        }

    def run_script(self, script_path: str, input_data: Dict[str, Any]) -> Dict[str, Any]:
        """Run a timetable generator script with given input data."""
        try:
            # Convert input to JSON string
            input_json = json.dumps(input_data)
            
            # Run the script
            result = subprocess.run(
                [sys.executable, script_path],
                input=input_json,
                capture_output=True,
                text=True,
                timeout=30
            )
            
            if result.returncode != 0:
                print(f"Script error for {script_path}:")
                print(f"STDOUT: {result.stdout}")
                print(f"STDERR: {result.stderr}")
                return {"status": "error", "message": f"Script failed: {result.stderr}"}
            
            # Parse the output
            return json.loads(result.stdout)
            
        except subprocess.TimeoutExpired:
            return {"status": "error", "message": "Script timed out"}
        except json.JSONDecodeError as e:
            return {"status": "error", "message": f"Invalid JSON output: {e}"}
        except Exception as e:
            return {"status": "error", "message": f"Unexpected error: {e}"}

    def test_basic_functionality(self):
        """Test basic timetable generation functionality."""
        input_data = {
            "classes": self.sample_classes,
            "preferences": self.sample_preferences
        }
        
        # Run both scripts
        original_result = self.run_script(self.original_script, input_data)
        new_result = self.run_script(self.new_script, input_data)
        
        # Both should succeed or fail in the same way
        self.assertEqual(original_result["status"], new_result["status"])
        
        if original_result["status"] == "success":
            # Both should generate valid timetables
            self.assertIn("timetable", original_result)
            self.assertIn("timetable", new_result)
            
            # Check that both have the same subjects scheduled
            orig_subjects = self._extract_subjects(original_result["timetable"])
            new_subjects = self._extract_subjects(new_result["timetable"])
            self.assertEqual(orig_subjects, new_subjects)

    def test_compact_vs_spaced_styles(self):
        """Test different scheduling styles."""
        for style in ["compact", "spaced_out"]:
            with self.subTest(style=style):
                prefs = self.sample_preferences.copy()
                prefs["schedule_style"] = style
                
                input_data = {
                    "classes": self.sample_classes,
                    "preferences": prefs
                }
                
                original_result = self.run_script(self.original_script, input_data)
                new_result = self.run_script(self.new_script, input_data)
                
                self.assertEqual(original_result["status"], new_result["status"])
                
                if original_result["status"] == "success":
                    # Verify style-specific characteristics
                    orig_days = self._count_utilized_days(original_result["timetable"])
                    new_days = self._count_utilized_days(new_result["timetable"])
                    
                    # Days used should be similar (genetic algorithm may vary slightly)
                    self.assertLessEqual(abs(orig_days - new_days), 1)

    def test_tied_vs_independent_modes(self):
        """Test tied and independent section selection modes."""
        for enforce_ties in [True, False]:
            with self.subTest(enforce_ties=enforce_ties):
                prefs = self.sample_preferences.copy()
                prefs["enforce_ties"] = enforce_ties
                
                input_data = {
                    "classes": self.sample_classes,
                    "preferences": prefs
                }
                
                original_result = self.run_script(self.original_script, input_data)
                new_result = self.run_script(self.new_script, input_data)
                
                self.assertEqual(original_result["status"], new_result["status"])

    def test_error_handling(self):
        """Test error handling for invalid inputs."""
        test_cases = [
            # Missing classes
            {"preferences": self.sample_preferences},
            # Missing preferences
            {"classes": self.sample_classes},
            # Empty classes
            {"classes": [], "preferences": self.sample_preferences},
            # Invalid time format
            {
                "classes": [{
                    **self.sample_classes[0],
                    "start_time": "invalid_time"
                }],
                "preferences": self.sample_preferences
            }
        ]
        
        for i, test_case in enumerate(test_cases):
            with self.subTest(case=i):
                original_result = self.run_script(self.original_script, test_case)
                new_result = self.run_script(self.new_script, test_case)
                
                # Both should handle errors consistently
                self.assertEqual(original_result["status"], "error")
                self.assertEqual(new_result["status"], "error")

    def test_output_format_consistency(self):
        """Test that output formats are consistent."""
        input_data = {
            "classes": self.sample_classes,
            "preferences": self.sample_preferences
        }
        
        original_result = self.run_script(self.original_script, input_data)
        new_result = self.run_script(self.new_script, input_data)
        
        if original_result["status"] == "success" and new_result["status"] == "success":
            # Check structure
            self.assertIn("timetable", original_result)
            self.assertIn("timetable", new_result)
            
            # Check that days are present in both
            orig_days = set(original_result["timetable"].keys())
            new_days = set(new_result["timetable"].keys())
            self.assertEqual(orig_days, new_days)
            
            # Check class entry format
            for day in orig_days:
                if original_result["timetable"][day]:  # If day has classes
                    orig_class = original_result["timetable"][day][0]
                    if new_result["timetable"][day]:  # If new version also has classes
                        new_class = new_result["timetable"][day][0]
                        
                        # Check required fields
                        required_fields = ["code", "subject", "activity", "section", 
                                         "start_time", "end_time", "venue", "lecturer"]
                        for field in required_fields:
                            self.assertIn(field, orig_class)
                            self.assertIn(field, new_class)

    def test_preference_handling(self):
        """Test that preferences are handled consistently."""
        # Test with different preference combinations
        test_prefs = [
            {**self.sample_preferences, "preferred_lecturers": []},
            {**self.sample_preferences, "preferred_days": []},
            {**self.sample_preferences, "preferred_start": "08:00:00"},
            {**self.sample_preferences, "preferred_end": "18:00:00"},
        ]
        
        for i, prefs in enumerate(test_prefs):
            with self.subTest(pref_case=i):
                input_data = {
                    "classes": self.sample_classes,
                    "preferences": prefs
                }
                
                original_result = self.run_script(self.original_script, input_data)
                new_result = self.run_script(self.new_script, input_data)
                
                self.assertEqual(original_result["status"], new_result["status"])

    def _extract_subjects(self, timetable: Dict) -> set:
        """Extract unique subjects from a timetable."""
        subjects = set()
        for day_schedule in timetable.values():
            for class_entry in day_schedule:
                subjects.add(class_entry["subject"])
        return subjects

    def _count_utilized_days(self, timetable: Dict) -> int:
        """Count the number of days with at least one class."""
        return sum(1 for day_schedule in timetable.values() if day_schedule)


class TestTimetableEngineComponents(unittest.TestCase):
    """Test individual components of the TimetableEngine."""

    def test_can_import_modules(self):
        """Test that all TimetableEngine modules can be imported."""
        try:
            from TimetableEngine import (
                Class, ScheduledClass, Timetable,
                load_classes_from_json, 
                TimetableGenerator,
                ScoreCalculator,
                format_timetable_as_json
            )
            # If we get here, imports succeeded
            self.assertTrue(True)
        except ImportError as e:
            self.fail(f"Failed to import TimetableEngine components: {e}")

    def test_class_model(self):
        """Test the Class data model."""
        from TimetableEngine.models import Class
        
        test_class = Class(
            code="TEST101",
            subject="Test Subject",
            activity="Lecture", 
            section="A",
            days="Monday",
            start_time=time(9, 0),
            end_time=time(10, 0),
            venue="Room 1",
            tied_to=["T1"],
            lecturer="Test Lecturer"
        )
        
        self.assertEqual(test_class.duration, 60)  # 1 hour = 60 minutes
        self.assertIsNotNone(test_class.time_tuple)

    def test_data_loading(self):
        """Test data loading functionality."""
        from TimetableEngine.data_loader import load_classes_from_json
        
        test_data = [{
            "code": "TEST101",
            "subject": "Test Subject",
            "activity": "Lecture",
            "section": "A",
            "days": "Monday",
            "start_time": "09:00:00",
            "end_time": "10:00:00",
            "venue": "Room 1",
            "tied_to": ["T1"],
            "lecturer": "Test Lecturer"
        }]
        
        classes = load_classes_from_json(test_data)
        self.assertEqual(len(classes), 1)
        self.assertEqual(classes[0].code, "TEST101")

    def test_timetable_operations(self):
        """Test basic timetable operations."""
        from TimetableEngine.models import Timetable, Class
        
        timetable = Timetable()
        test_class = Class(
            code="TEST101",
            subject="Test Subject",
            activity="Lecture",
            section="A", 
            days="Monday",
            start_time=time(9, 0),
            end_time=time(10, 0),
            venue="Room 1",
            tied_to=[],
            lecturer="Test Lecturer"
        )
        
        # Test adding a section
        self.assertTrue(timetable.can_add_section([test_class]))
        timetable.add_section([test_class])
        self.assertEqual(timetable.get_utilized_days(), 1)
        self.assertEqual(len(timetable.scheduled_classes), 1)


def run_tests():
    """Run all tests and return results."""
    # Create test suite
    loader = unittest.TestLoader()
    suite = unittest.TestSuite()
    
    # Add test classes
    suite.addTests(loader.loadTestsFromTestCase(TestTimetableGeneratorCompatibility))
    suite.addTests(loader.loadTestsFromTestCase(TestTimetableEngineComponents))
    
    # Run tests
    runner = unittest.TextTestRunner(verbosity=2)
    result = runner.run(suite)
    
    return result.wasSuccessful()


if __name__ == "__main__":
    success = run_tests()
    sys.exit(0 if success else 1)
