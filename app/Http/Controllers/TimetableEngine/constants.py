"""
Constants and configuration for the timetable generator.
"""

from datetime import timedelta

# Days of the week for scheduling
DAYS = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"]

# Scheduling preferences
MAX_CONSECUTIVE_CLASSES = 2  # Maximum preferred consecutive classes per day
IDEAL_GAP = timedelta(hours=1)  # 1 hour gap is ideal
MAX_GAP = timedelta(hours=2)  # More than 2 hours gap is not preferred

# Genetic Algorithm parameters
DEFAULT_GENERATIONS = 20  # Reduced further for very fast response
DEFAULT_POPULATION_SIZE = 50  # Reduced further for very fast response
CROSSOVER_PROBABILITY = 0.8
MUTATION_PROBABILITY = 0.3  # Increased for more exploration in fewer generations
TOURNAMENT_SIZE = 3

# Early termination threshold
GOOD_FITNESS_THRESHOLD = 4000  # Lowered threshold for faster termination

# Scoring weights for different schedule styles
SCORING_PROFILES = {
    "compact": {
        "days_score_map": {5: -1500, 4: -750, 3: 0, 2: 2000, 1: 3000},
        "gap_score_weight": 400,
        "streak_bonus_2": 150,
        "streak_penalty_1": 150,
        "streak_penalty_3_plus": 650,
    },
    "spaced_out": {
        "days_penalty_per_day": 250,
        "gap_score_weight": 1200,
        "streak_bonus_2": -200,
        "streak_penalty_1": 50,
        "streak_penalty_3_plus": 800,
        "penalty_for_single_class_day": 450,
    }
}

# Base scores
BASE_SCORE = 10000.0
PREFERRED_LECTURER_BONUS = 200
PREFERRED_DAY_BONUS = 50
PREFERRED_TIME_BONUS = 25
