"""
Timetable Generation Engine

A modular genetic algorithm-based timetable generator that creates optimal
class schedules based on user preferences and constraints.
"""

from .models import Class, ScheduledClass, Timetable
from .data_loader import load_classes_from_csv, load_classes_from_json, group_classes_by_section
from .genetic_algorithm import TimetableGenerator
from .scoring import ScoreCalculator
from .formatter import format_timetable_as_json, format_timetable_as_text
from . import constants

__all__ = [
    'Class',
    'ScheduledClass', 
    'Timetable',
    'load_classes_from_csv',
    'load_classes_from_json',
    'group_classes_by_section',
    'TimetableGenerator',
    'ScoreCalculator',
    'format_timetable_as_json',
    'format_timetable_as_text',
    'constants'
]
