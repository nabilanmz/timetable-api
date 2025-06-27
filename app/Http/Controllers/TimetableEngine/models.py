"""
Data models for the timetable generator.

This module contains the core data structures used throughout the system:
- Class: Represents a single class session
- ScheduledClass: A class placed in a specific time slot
- Timetable: Manages the weekly schedule
"""

from dataclasses import dataclass
from typing import List, Dict, Tuple, Set
from datetime import time, datetime, timedelta
from constants import DAYS


@dataclass
class Class:
    """Represents a single class session with all its properties."""
    code: str
    subject: str
    activity: str  # "Lecture" or "Tutorial"
    section: str
    days: str
    start_time: time
    end_time: time
    venue: str
    tied_to: List[str]  # List of tutorial sections tied to this lecture
    lecturer: str

    @property
    def duration(self) -> int:
        """Calculate duration in minutes."""
        start = datetime.combine(datetime.today(), self.start_time)
        end = datetime.combine(datetime.today(), self.end_time)
        return int((end - start).total_seconds() / 60)

    @property
    def time_tuple(self) -> Tuple[datetime, datetime]:
        """Return start and end as datetime objects for comparison."""
        today = datetime.today()
        return (
            datetime.combine(today, self.start_time),
            datetime.combine(today, self.end_time),
        )


@dataclass
class ScheduledClass:
    """A class that has been placed in a specific time slot."""
    class_obj: Class
    day: str
    start_time: time
    end_time: time


class Timetable:
    """Manages a weekly schedule of classes."""
    
    def __init__(self):
        self.schedule: Dict[str, List[ScheduledClass]] = {day: [] for day in DAYS}
        self.scheduled_classes: List[ScheduledClass] = []

    def can_add_section(self, section_classes: List[Class]) -> bool:
        """Check if we can add all classes in this section without time clashes."""
        for cls in section_classes:
            # Check time conflicts with existing classes on the same day
            for existing in self.schedule[cls.days]:
                # A clash occurs if the new class starts before the existing one ends
                # AND the new class ends after the existing one starts
                if (
                    cls.start_time < existing.end_time
                    and cls.end_time > existing.start_time
                ):
                    return False
        return True

    def add_section(self, section_classes: List[Class]):
        """Add all classes in a section. Assumes can_add_section was checked."""
        for cls in section_classes:
            scheduled_class = ScheduledClass(
                class_obj=cls,
                day=cls.days,
                start_time=cls.start_time,
                end_time=cls.end_time,
            )
            self.schedule[cls.days].append(scheduled_class)
            # Sort the day's schedule by start time after adding
            self.schedule[cls.days].sort(key=lambda x: x.start_time)
            self.scheduled_classes.append(scheduled_class)

    def get_utilized_days(self) -> int:
        """Return the number of days that have at least one class."""
        return sum(1 for day in DAYS if self.schedule[day])

    def get_consecutive_days_score(self) -> float:
        """Calculate score based on consecutive days used."""
        days_used = [day for day in DAYS if self.schedule[day]]
        if not days_used:
            return 0

        # Calculate longest streak of consecutive days
        max_streak = current_streak = 1
        for i in range(1, len(days_used)):
            if DAYS.index(days_used[i]) == DAYS.index(days_used[i - 1]) + 1:
                current_streak += 1
                max_streak = max(max_streak, current_streak)
            else:
                current_streak = 1

        return max_streak / len(DAYS)  # Normalized score

    def get_scheduled_subjects(self) -> Set[str]:
        """Return the set of subjects that are scheduled."""
        return {sc.class_obj.subject for sc in self.scheduled_classes}
