"""
Scoring logic for evaluating timetable quality.

This module contains the scoring algorithms that evaluate how good a timetable is
based on various criteria like gaps between classes, day utilization, and user preferences.
"""

from typing import List
from datetime import datetime, timedelta
from .models import Timetable, ScheduledClass
from .constants import DAYS, IDEAL_GAP, MAX_GAP, MAX_CONSECUTIVE_CLASSES, SCORING_PROFILES


class ScoreCalculator:
    """Handles all scoring logic for timetable evaluation."""
    
    def __init__(self, user_preferences: dict):
        self.user_preferences = user_preferences
        self.style = user_preferences.get("schedule_style", "compact")
        self.scoring_profile = SCORING_PROFILES[self.style]
    
    def calculate_day_gaps_score(self, timetable: Timetable, day: str) -> float:
        """Calculate score based on gaps between classes on a single day."""
        day_classes = sorted(timetable.schedule[day], key=lambda x: x.start_time)
        if len(day_classes) < 2:
            return 1.0  # No gaps if only one class

        total_gap_score = 0
        consecutive_count = 1

        for i in range(1, len(day_classes)):
            prev_end = datetime.combine(datetime.today(), day_classes[i - 1].end_time)
            curr_start = datetime.combine(datetime.today(), day_classes[i].start_time)
            gap = curr_start - prev_end

            if gap <= timedelta(0):
                consecutive_count += 1
                continue  # No gap or overlap
            elif gap <= IDEAL_GAP:
                total_gap_score += 1.0  # Perfect gap
            elif gap <= MAX_GAP:
                total_gap_score += 0.5  # Acceptable gap
            else:
                total_gap_score += 0.1  # Too long gap

            consecutive_count = 1

        # Penalize too many consecutive classes
        if consecutive_count > MAX_CONSECUTIVE_CLASSES:
            total_gap_score *= 0.7  # Reduce score for too many consecutive classes

        return total_gap_score / (len(day_classes) - 1) if len(day_classes) > 1 else 1.0
    
    def calculate_preference_bonuses(self, timetable: Timetable) -> float:
        """Calculate bonuses based on user preferences."""
        from .constants import PREFERRED_LECTURER_BONUS, PREFERRED_DAY_BONUS, PREFERRED_TIME_BONUS
        
        bonus = 0
        preferred_lecturers = self.user_preferences.get("preferred_lecturers", [])
        preferred_days = self.user_preferences.get("preferred_days", [])
        preferred_start = self.user_preferences.get("preferred_start")
        preferred_end = self.user_preferences.get("preferred_end")
        
        for sc in timetable.scheduled_classes:
            if sc.class_obj.lecturer in preferred_lecturers:
                bonus += PREFERRED_LECTURER_BONUS
            if sc.day in preferred_days:
                bonus += PREFERRED_DAY_BONUS
            if (preferred_start and preferred_end and
                preferred_start <= sc.start_time <= preferred_end):
                bonus += PREFERRED_TIME_BONUS
        
        return bonus
    
    def calculate_day_utilization_score(self, timetable: Timetable) -> float:
        """Calculate score based on how days are utilized."""
        utilized_days = timetable.get_utilized_days()
        
        if self.style == "compact":
            days_score_map = self.scoring_profile["days_score_map"]
            return days_score_map.get(utilized_days, -4000)
        else:  # spaced_out
            return -utilized_days * self.scoring_profile["days_penalty_per_day"]
    
    def calculate_streak_scores(self, timetable: Timetable) -> float:
        """Calculate scores based on consecutive class patterns."""
        total_streak_score = 0
        
        for day in DAYS:
            day_classes = timetable.schedule[day]
            if not day_classes:
                continue

            # Handle single-class day case
            if len(day_classes) == 1:
                if self.style == "spaced_out":
                    total_streak_score -= self.scoring_profile["penalty_for_single_class_day"]
                else:  # compact style
                    total_streak_score -= self.scoring_profile["streak_penalty_1"]
                continue

            # Process consecutive streaks for multi-class days
            consecutive_streak = 1
            for i in range(1, len(day_classes)):
                prev_end_dt = datetime.combine(
                    datetime.today(), day_classes[i - 1].end_time
                )
                curr_start_dt = datetime.combine(
                    datetime.today(), day_classes[i].start_time
                )

                if (curr_start_dt - prev_end_dt) <= timedelta(minutes=15):
                    consecutive_streak += 1
                else:
                    total_streak_score += self._score_streak(consecutive_streak)
                    consecutive_streak = 1

            # Score the final streak of the day
            total_streak_score += self._score_streak(consecutive_streak)
        
        return total_streak_score
    
    def _score_streak(self, streak_length: int) -> float:
        """Score a single consecutive streak."""
        if streak_length == 1:
            return -self.scoring_profile["streak_penalty_1"]
        elif streak_length == 2:
            return self.scoring_profile["streak_bonus_2"]
        else:
            return -(streak_length - 2) * self.scoring_profile["streak_penalty_3_plus"]
    
    def calculate_gap_scores(self, timetable: Timetable) -> float:
        """Calculate overall gap scores across all utilized days."""
        utilized_days = [day for day in DAYS if timetable.schedule[day]]
        if not utilized_days:
            return 0
        
        total_gap_score = 0
        for day in utilized_days:
            total_gap_score += self.calculate_day_gaps_score(timetable, day)
        
        average_gap_score = total_gap_score / len(utilized_days)
        return average_gap_score * self.scoring_profile["gap_score_weight"]
