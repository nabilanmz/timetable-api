"""
TimetableEngine Functional Test Suite

Tests the functionality of the current TimetableEngine to ensure it works correctly
for all supported scenarios and edge cases.
"""

import sys
import os
import json
import unittest
from datetime import time, datetime
from typing import Dict, Any, List

# Add the parent directory to the path
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from TimetableEngine import (
    Class, ScheduledClass, Timetable,
    load_classes_from_json, group_classes_by_section,
    TimetableGenerator, ScoreCalculator,
    format_timetable_as_json, format_timetable_as_text,
    constants
)


class TestTimetableEngineCore(unittest.TestCase):
    """Test core TimetableEngine functionality."""

    def setUp(self):
        """Set up test data."""
        self.sample_classes_data = [
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
                "venue": "TR3",
                "tied_to": [],
                "lecturer": "TA Brown"
            }
        ]

        self.basic_preferences = {
            "subjects": ["Computer Science", "Mathematics"],
            "schedule_style": "compact",
            "enforce_ties": True,
            "preferred_days": ["Monday", "Tuesday", "Wednesday", "Thursday"],
            "preferred_lecturers": ["Dr. Smith"],
            "preferred_start": "09:00:00",
            "preferred_end": "17:00:00"
        }

    def test_class_model(self):
        """Test the Class data model."""
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
        
        # Test properties
        self.assertEqual(test_class.duration, 60)  # 1 hour = 60 minutes
        self.assertIsNotNone(test_class.time_tuple)
        self.assertEqual(test_class.code, "TEST101")
        self.assertEqual(test_class.tied_to, ["T1"])

    def test_data_loading(self):
        """Test data loading from JSON."""
        classes = load_classes_from_json(self.sample_classes_data)
        
        self.assertEqual(len(classes), 5)
        self.assertEqual(classes[0].code, "CS101")
        self.assertEqual(classes[0].subject, "Computer Science")
        self.assertEqual(classes[0].activity, "Lecture")
        self.assertEqual(classes[0].tied_to, ["T1", "T2"])

    def test_class_grouping(self):
        """Test grouping classes by section."""
        classes = load_classes_from_json(self.sample_classes_data)
        grouped = group_classes_by_section(classes)
        
        self.assertIn("Computer Science", grouped)
        self.assertIn("Mathematics", grouped)
        self.assertIn("Lecture_A", grouped["Computer Science"])
        self.assertIn("Tutorial_T1", grouped["Computer Science"])
        self.assertIn("Lecture_B", grouped["Mathematics"])

    def test_timetable_operations(self):
        """Test basic timetable operations."""
        timetable = Timetable()
        
        # Create test class
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
        
        # Test adding class
        self.assertTrue(timetable.can_add_section([test_class]))
        timetable.add_section([test_class])
        
        # Test timetable state
        self.assertEqual(timetable.get_utilized_days(), 1)
        self.assertEqual(len(timetable.scheduled_classes), 1)
        self.assertEqual(len(timetable.schedule["Monday"]), 1)
        self.assertIn("Test Subject", timetable.get_scheduled_subjects())

    def test_time_conflict_detection(self):
        """Test that time conflicts are properly detected."""
        timetable = Timetable()
        
        class1 = Class(
            code="TEST101", subject="Test", activity="Lecture", section="A",
            days="Monday", start_time=time(9, 0), end_time=time(10, 0),
            venue="Room 1", tied_to=[], lecturer="Prof A"
        )
        
        class2 = Class(
            code="TEST102", subject="Test", activity="Tutorial", section="T1",
            days="Monday", start_time=time(9, 30), end_time=time(10, 30),  # Overlaps
            venue="Room 2", tied_to=[], lecturer="Prof B"
        )
        
        # First class should be addable
        self.assertTrue(timetable.can_add_section([class1]))
        timetable.add_section([class1])
        
        # Second class should conflict
        self.assertFalse(timetable.can_add_section([class2]))


class TestTimetableGeneration(unittest.TestCase):
    """Test timetable generation functionality."""

    def setUp(self):
        """Set up test data."""
        self.sample_classes_data = [
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
        ]

    def test_basic_generation_tied_mode(self):
        """Test basic timetable generation with tied sections."""
        classes = load_classes_from_json(self.sample_classes_data)
        
        preferences = {
            "subjects": ["Computer Science"],
            "schedule_style": "compact",
            "enforce_ties": True,
            "preferred_days": [],
            "preferred_lecturers": [],
            "preferred_start": time.min,
            "preferred_end": time.max
        }
        
        generator = TimetableGenerator(classes, preferences)
        timetable = generator.run(generations=30, pop_size=50)  # Faster for testing
        
        self.assertIsNotNone(timetable)
        self.assertGreater(len(timetable.scheduled_classes), 0)
        self.assertIn("Computer Science", timetable.get_scheduled_subjects())

    def test_basic_generation_independent_mode(self):
        """Test basic timetable generation with independent sections."""
        classes = load_classes_from_json(self.sample_classes_data)
        
        preferences = {
            "subjects": ["Computer Science"],
            "schedule_style": "compact",
            "enforce_ties": False,
            "preferred_days": [],
            "preferred_lecturers": [],
            "preferred_start": time.min,
            "preferred_end": time.max
        }
        
        generator = TimetableGenerator(classes, preferences)
        timetable = generator.run(generations=30, pop_size=50)
        
        self.assertIsNotNone(timetable)
        self.assertGreater(len(timetable.scheduled_classes), 0)

    def test_impossible_constraints(self):
        """Test handling of impossible constraints."""
        # Create conflicting classes (same time)
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
            "preferred_start": time.min,
            "preferred_end": time.max
        }
        
        generator = TimetableGenerator(classes, preferences)
        timetable = generator.run(generations=10, pop_size=20)  # Small for speed
        
        # Should return None due to impossible constraints
        self.assertIsNone(timetable)


class TestSchedulingStyles(unittest.TestCase):
    """Test different scheduling styles."""

    def setUp(self):
        """Set up test data with multiple options."""
        self.multi_option_classes = [
            # CS Lecture with multiple options
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
            # Math Lecture
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
        ]

    def test_compact_style(self):
        """Test compact scheduling style."""
        classes = load_classes_from_json(self.multi_option_classes)
        
        preferences = {
            "subjects": ["Computer Science", "Mathematics"],
            "schedule_style": "compact",
            "enforce_ties": True,
            "preferred_days": [],
            "preferred_lecturers": [],
            "preferred_start": time.min,
            "preferred_end": time.max
        }
        
        generator = TimetableGenerator(classes, preferences)
        timetable = generator.run(generations=30, pop_size=50)
        
        self.assertIsNotNone(timetable)
        # Compact style should prefer fewer days
        self.assertLessEqual(timetable.get_utilized_days(), 4)

    def test_spaced_out_style(self):
        """Test spaced-out scheduling style."""
        classes = load_classes_from_json(self.multi_option_classes)
        
        preferences = {
            "subjects": ["Computer Science", "Mathematics"],
            "schedule_style": "spaced_out",
            "enforce_ties": True,
            "preferred_days": [],
            "preferred_lecturers": [],
            "preferred_start": time.min,
            "preferred_end": time.max
        }
        
        generator = TimetableGenerator(classes, preferences)
        timetable = generator.run(generations=30, pop_size=50)
        
        self.assertIsNotNone(timetable)
        # Should schedule all required subjects
        subjects = timetable.get_scheduled_subjects()
        self.assertIn("Computer Science", subjects)
        self.assertIn("Mathematics", subjects)


class TestOutputFormatting(unittest.TestCase):
    """Test output formatting functionality."""

    def test_json_formatting(self):
        """Test JSON output formatting."""
        timetable = Timetable()
        
        test_class = Class(
            code="TEST101", subject="Test Subject", activity="Lecture", section="A",
            days="Monday", start_time=time(9, 0), end_time=time(10, 0),
            venue="Room 1", tied_to=[], lecturer="Test Lecturer"
        )
        
        timetable.add_section([test_class])
        result = format_timetable_as_json(timetable)
        
        # Check structure
        self.assertEqual(result["status"], "success")
        self.assertIn("timetable", result)
        self.assertIn("summary", result)
        
        # Check content
        self.assertIn("Monday", result["timetable"])
        self.assertEqual(len(result["timetable"]["Monday"]), 1)
        
        class_data = result["timetable"]["Monday"][0]
        self.assertEqual(class_data["code"], "TEST101")
        self.assertEqual(class_data["subject"], "Test Subject")
        self.assertEqual(class_data["start_time"], "09:00:00")

    def test_text_formatting(self):
        """Test text output formatting."""
        timetable = Timetable()
        
        test_class = Class(
            code="TEST101", subject="Test Subject", activity="Lecture", section="A",
            days="Monday", start_time=time(9, 0), end_time=time(10, 0),
            venue="Room 1", tied_to=[], lecturer="Test Lecturer"
        )
        
        timetable.add_section([test_class])
        result = format_timetable_as_text(timetable)
        
        self.assertIn("Generated Timetable", result)
        self.assertIn("Monday:", result)
        self.assertIn("TEST101", result)
        self.assertIn("09:00-10:00", result)

    def test_empty_timetable_formatting(self):
        """Test formatting of empty/None timetable."""
        json_result = format_timetable_as_json(None)
        text_result = format_timetable_as_text(None)
        
        self.assertEqual(json_result["status"], "error")
        self.assertIn("No timetable", text_result)


class TestScoreCalculator(unittest.TestCase):
    """Test scoring functionality."""

    def test_score_calculator_initialization(self):
        """Test ScoreCalculator initialization."""
        preferences = {"schedule_style": "compact"}
        calculator = ScoreCalculator(preferences)
        
        self.assertEqual(calculator.style, "compact")
        self.assertIsNotNone(calculator.scoring_profile)

    def test_preference_bonuses(self):
        """Test preference bonus calculations."""
        preferences = {
            "preferred_lecturers": ["Dr. Smith"],
            "preferred_days": ["Monday"],
            "preferred_start": time(9, 0),
            "preferred_end": time(17, 0)
        }
        
        calculator = ScoreCalculator(preferences)
        timetable = Timetable()
        
        # Add a class that matches preferences
        preferred_class = Class(
            code="TEST101", subject="Test", activity="Lecture", section="A",
            days="Monday", start_time=time(10, 0), end_time=time(11, 0),
            venue="Room 1", tied_to=[], lecturer="Dr. Smith"
        )
        
        timetable.add_section([preferred_class])
        bonus = calculator.calculate_preference_bonuses(timetable)
        
        # Should get bonuses for lecturer and day
        self.assertGreater(bonus, 0)


def run_tests():
    """Run all tests and return results."""
    loader = unittest.TestLoader()
    suite = unittest.TestSuite()
    
    # Add all test classes
    test_classes = [
        TestTimetableEngineCore,
        TestTimetableGeneration,
        TestSchedulingStyles,
        TestOutputFormatting,
        TestScoreCalculator
    ]
    
    for test_class in test_classes:
        suite.addTests(loader.loadTestsFromTestCase(test_class))
    
    # Run tests
    runner = unittest.TextTestRunner(verbosity=2)
    result = runner.run(suite)
    
    return result.wasSuccessful()


if __name__ == "__main__":
    print("🧪 TimetableEngine Functional Test Suite")
    print("=" * 50)
    success = run_tests()
    
    if success:
        print("\n🎉 All tests passed! TimetableEngine is working correctly.")
    else:
        print("\n❌ Some tests failed. Please check the output above.")
    
    sys.exit(0 if success else 1)
