import sys
import json
import csv
from dataclasses import dataclass
from typing import List, Dict, Optional, Tuple, Set
from collections import defaultdict
import random
import numpy as np
from deap import base, creator, tools, algorithms
from datetime import time, datetime, timedelta

# Initialize DEAP
creator.create("FitnessMax", base.Fitness, weights=(1.0,))
creator.create("Individual", list, fitness=creator.FitnessMax)

# Constants
DAYS = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"]
MAX_CONSECUTIVE_CLASSES = 2  # Maximum preferred consecutive classes per day
IDEAL_GAP = timedelta(hours=1)  # 1 hour gap is ideal
MAX_GAP = timedelta(hours=2)  # More than 2 hours gap is not preferred


@dataclass
class Class:
    code: str
    subject: str
    activity: str  # "Lecture" or "Tutorial"
    section: str
    days: str
    start_time: time
    end_time: time
    venue: str
    tied_to: List[str]  # ### NEW ###
    lecturer: str

    @property
    def duration(self) -> int:
        """Calculate duration in minutes"""
        start = datetime.combine(datetime.today(), self.start_time)
        end = datetime.combine(datetime.today(), self.end_time)
        return int((end - start).total_seconds() / 60)

    @property
    def time_tuple(self) -> Tuple[datetime, datetime]:
        """Return start and end as datetime objects for comparison"""
        today = datetime.today()
        return (
            datetime.combine(today, self.start_time),
            datetime.combine(today, self.end_time),
        )


def load_classes_from_csv(filename: str) -> List[Class]:
    """Load classes from CSV file, including the new 'Tied To' column."""
    classes = []
    with open(filename, mode="r", encoding="utf-8") as file:
        reader = csv.DictReader(file)
        for row in reader:
            try:
                # (Your existing time parsing logic is good, keep it)
                start_time = (
                    datetime.strptime(row["Start Time"], "%I:%M %p").time()
                    if "AM" in row["Start Time"] or "PM" in row["Start Time"]
                    else datetime.strptime(row["Start Time"], "%H:%M").time()
                )
                end_time = (
                    datetime.strptime(row["End Time"], "%I:%M %p").time()
                    if "AM" in row["End Time"] or "PM" in row["End Time"]
                    else datetime.strptime(row["End Time"], "%H:%M").time()
                )

                # ### NEW ###: Parse the 'Tied To' column
                tied_to_str = row.get("Tied To", "")
                tied_to_list = [s.strip() for s in tied_to_str.split(",") if s.strip()]

                classes.append(
                    Class(
                        code=row["Code"],
                        subject=row["subject"],
                        activity=row["Activity"],
                        section=row["Section"],
                        days=row["Days"],
                        start_time=start_time,
                        end_time=end_time,
                        venue=row["Venue"],
                        tied_to=tied_to_list,  # ### NEW ###
                        lecturer=row["Lecturer"] if row["Lecturer"] else "Not Assigned",
                    )
                )
            except (ValueError, KeyError) as e:
                print(f"Skipping row due to error: {e} in row {row}", file=sys.stderr)
                continue
    return classes


# ### NEW ###: Function to load classes from JSON data
def load_classes_from_json(classes_data: List[Dict]) -> List[Class]:
    """Load classes from a list of dictionaries (from JSON)."""
    classes = []
    for row in classes_data:
        try:
            start_time = datetime.strptime(row["start_time"], "%H:%M:%S").time()
            end_time = datetime.strptime(row["end_time"], "%H:%M:%S").time()

            classes.append(
                Class(
                    code=row["code"],
                    subject=row["subject"],
                    activity=row["activity"],
                    section=row["section"],
                    days=row["days"],
                    start_time=start_time,
                    end_time=end_time,
                    venue=row["venue"],
                    tied_to=row.get("tied_to", []),
                    lecturer=row["lecturer"],
                )
            )
        except (ValueError, KeyError) as e:
            # Log error to stderr for debugging without polluting stdout
            print(f"Skipping row due to error: {e} in row {row}", file=sys.stderr)
            continue
    return classes


def group_classes_by_section(classes: List[Class]) -> Dict[str, Dict[str, List[Class]]]:
    """Group classes by subject and section"""
    section_groups: Dict[str, Dict[str, List[Class]]] = defaultdict(lambda: defaultdict(list))
    for cls in classes:
        section_groups[cls.subject][f"{cls.activity}_{cls.section}"].append(cls)
    return section_groups


@dataclass
class ScheduledClass:
    class_obj: Class
    day: str
    start_time: time
    end_time: time


class Timetable:
    def __init__(self):
        self.schedule: Dict[str, List[ScheduledClass]] = {day: [] for day in DAYS}
        self.scheduled_classes: List[ScheduledClass] = []

    def can_add_section(self, section_classes: List[Class]) -> bool:
        """Check if we can add all classes in this section without clashes."""
        # This function now only needs to check for time clashes, as the GA
        # structure handles the one-lecture-per-subject logic.
        for cls in section_classes:
            # Check time conflicts
            for existing in self.schedule[cls.days]:
                # A clash occurs if the new class starts before the existing one ends
                # AND the new class ends after the existing one starts.
                if (
                    cls.start_time < existing.end_time
                    and cls.end_time > existing.start_time
                ):
                    return False
        return True

    def add_section(self, section_classes: List[Class]):
        """Add all classes in a section. Assumes can_add_section was checked."""
        for cls in section_classes:
            sc = ScheduledClass(
                class_obj=cls,
                day=cls.days,
                start_time=cls.start_time,
                end_time=cls.end_time,
            )
            self.schedule[cls.days].append(sc)
            # Sort the day's schedule by start time after adding
            self.schedule[cls.days].sort(key=lambda x: x.start_time)
            self.scheduled_classes.append(sc)

    def get_utilized_days(self) -> int:
        return sum(1 for day in DAYS if self.schedule[day])

    def get_consecutive_days_score(self) -> float:
        """Calculate score based on consecutive days used"""
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

    def get_day_gaps_score(self, day: str) -> float:
        """Calculate score based on gaps between classes on a single day"""
        day_classes = sorted(self.schedule[day], key=lambda x: x.start_time)
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

    def get_scheduled_subjects(self) -> Set[str]:
        return {sc.class_obj.subject for sc in self.scheduled_classes}


### REWRITTEN CLASS ###
class TimetableGenerator:
    def __init__(self, classes: List[Class], user_preferences: dict):
        self.classes = classes
        self.user_preferences = user_preferences
        self.enforce_ties = self.user_preferences.get(
            "enforce_ties", True
        )  # Default to True
        self.section_groups = group_classes_by_section(classes)
        self.gene_map = []
        self.setup_deap()

    def setup_deap(self):
        """Sets up DEAP based on whether ties are enforced."""
        self.toolbox = base.Toolbox()
        gene_upper_bounds = []

        if self.enforce_ties:
            # print("\nSetting up GA with ENFORCED lecture-tutorial ties.")
            # Gene map for tied sections: (subject, [ (lecture_section, [tied_tutorial_1, ...]), ... ])
            for subject in self.user_preferences["subjects"]:
                if subject not in self.section_groups:
                    continue

                subject_lectures = sorted(
                    [
                        sc
                        for sk, sc in self.section_groups[subject].items()
                        if sk.startswith("Lecture")
                    ],
                    key=lambda s: s[0].section,
                )

                if not subject_lectures:
                    continue

                lecture_tutorial_pairs = []
                max_tied_tutorials = 0
                for lect_section_group in subject_lectures:
                    # Find all tutorial sections tied to this lecture
                    tied_tutorial_names = lect_section_group[0].tied_to

                    tied_tutorials = []
                    for tut_name in tied_tutorial_names:
                        tut_key = f"Tutorial_{tut_name}"
                        if tut_key in self.section_groups[subject]:
                            tied_tutorials.append(self.section_groups[subject][tut_key])

                    if tied_tutorials:
                        lecture_tutorial_pairs.append(
                            (lect_section_group, tied_tutorials)
                        )
                        if len(tied_tutorials) > max_tied_tutorials:
                            max_tied_tutorials = len(tied_tutorials)

                if lecture_tutorial_pairs:
                    self.gene_map.append(
                        {
                            "type": "tied_subject",
                            "subject": subject,
                            "pairs": lecture_tutorial_pairs,
                        }
                    )
                    # Gene 1: choice of lecture. Gene 2: choice of tutorial from the tied list.
                    gene_upper_bounds.append(len(lecture_tutorial_pairs) - 1)
                    gene_upper_bounds.append(
                        max_tied_tutorials - 1 if max_tied_tutorials > 0 else 0
                    )

        else:  # Ties are NOT enforced (lecturer view)
            # print("\nSetting up GA with INDEPENDENT lecture and tutorial choices.")
            for subject in self.user_preferences["subjects"]:
                if subject not in self.section_groups:
                    continue

                lectures = [
                    sc
                    for sk, sc in self.section_groups[subject].items()
                    if sk.startswith("Lecture")
                ]
                tutorials = [
                    sc
                    for sk, sc in self.section_groups[subject].items()
                    if sk.startswith("Tutorial")
                ]

                if lectures:
                    self.gene_map.append(
                        {
                            "type": "independent_activity",
                            "activity": "Lecture",
                            "subject": subject,
                            "sections": lectures,
                        }
                    )
                    gene_upper_bounds.append(len(lectures) - 1)
                if tutorials:
                    self.gene_map.append(
                        {
                            "type": "independent_activity",
                            "activity": "Tutorial",
                            "subject": subject,
                            "sections": tutorials,
                        }
                    )
                    gene_upper_bounds.append(len(tutorials) - 1)

        if not self.gene_map:
            raise ValueError(
                "No valid sections found for the selected subjects with the chosen constraints."
            )

        self.toolbox.register(
            "indices",
            lambda bounds: [random.randint(0, b) for b in bounds],
            gene_upper_bounds,
        )
        self.toolbox.register(
            "individual", tools.initIterate, creator.Individual, self.toolbox.indices
        )
        self.toolbox.register(
            "population", tools.initRepeat, list, self.toolbox.individual
        )

        self.toolbox.register("evaluate", self.evaluate)
        self.toolbox.register("mate", tools.cxUniform, indpb=0.5)
        self.toolbox.register(
            "mutate",
            tools.mutUniformInt,
            low=[0] * len(gene_upper_bounds),
            up=gene_upper_bounds,
            indpb=0.1,
        )
        self.toolbox.register("select", tools.selTournament, tournsize=3)

    # In tt.py, replace your existing evaluate method with this one.

    def evaluate(self, individual: List[int]) -> Tuple[float,]:
        # ### THIS IS THE METHOD TO REPLACE ###
        timetable = Timetable()

        # --- Decode the individual (your existing logic is perfect) ---
        sections_to_schedule = []
        if self.enforce_ties:
            gene_idx = 0
            for map_item in self.gene_map:
                lecture_choice, tutorial_choice = (
                    individual[gene_idx],
                    individual[gene_idx + 1],
                )
                chosen_pair = map_item["pairs"][lecture_choice]
                sections_to_schedule.append(chosen_pair[0])
                tied_tutorials = chosen_pair[1]
                if tied_tutorials:
                    sections_to_schedule.append(
                        tied_tutorials[tutorial_choice % len(tied_tutorials)]
                    )
                gene_idx += 2
        else:
            for i, map_item in enumerate(self.gene_map):
                sections_to_schedule.append(map_item["sections"][individual[i]])

        # --- Check for clashes (HARD constraint) ---
        for section in sections_to_schedule:
            if not timetable.can_add_section(section):
                return (0,)
            timetable.add_section(section)

        # --- DYNAMIC SCORING based on user's chosen style ---
        score = 10000.0
        style = self.user_preferences.get("schedule_style", "compact")

        # --- Define Scoring Profiles ---
        if style == "compact":
            days_score_map = {5: -1500, 4: -750, 3: 0, 2: 2000, 1: 3000}
            score += days_score_map.get(timetable.get_utilized_days(), -4000)
            GAP_SCORE_WEIGHT = 400
            STREAK_BONUS_2 = 150
            STREAK_PENALTY_1 = 150
            STREAK_PENALTY_3_PLUS = 650
        else:  # style == 'spaced_out'
            score -= timetable.get_utilized_days() * 250
            GAP_SCORE_WEIGHT = 1200
            STREAK_BONUS_2 = -200
            STREAK_PENALTY_1 = 50
            STREAK_PENALTY_3_PLUS = 800
            # ### NEW: Define the specific penalty for wasteful days ###
            PENALTY_FOR_SINGLE_CLASS_DAY = 450  # A significant penalty

        # --- Apply Universal and Gap Scores ---
        preferred_lecturers = self.user_preferences.get("preferred_lecturers", [])
        for sc in timetable.scheduled_classes:
            if sc.class_obj.lecturer in preferred_lecturers:
                score += 200
            if sc.day in self.user_preferences["preferred_days"]:
                score += 50
            if (
                self.user_preferences["preferred_start"]
                <= sc.start_time
                <= self.user_preferences["preferred_end"]
            ):
                score += 25

        total_gap_score = 0
        utilized_days = [day for day in DAYS if timetable.schedule[day]]
        if utilized_days:
            for day in utilized_days:
                total_gap_score += timetable.get_day_gaps_score(day)
            score += (total_gap_score / len(utilized_days)) * GAP_SCORE_WEIGHT

        # --- Apply Day Structure & Streak Scores (Now with the new penalty) ---
        for day in DAYS:
            day_classes = timetable.schedule[day]
            if not day_classes:
                continue

            # ### MODIFIED LOGIC HERE ###
            # Check for the single-class day case FIRST.
            if len(day_classes) == 1:
                if style == "spaced_out":
                    score -= PENALTY_FOR_SINGLE_CLASS_DAY
                else:  # 'compact' style
                    score -= STREAK_PENALTY_1  # A single class is just a failed streak
                continue  # Done with this day, move to the next

            # If we get here, the day has 2 or more classes, so we check streaks.
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
                    if consecutive_streak == 1:
                        score -= STREAK_PENALTY_1
                    elif consecutive_streak == 2:
                        score += STREAK_BONUS_2
                    else:
                        score -= (consecutive_streak - 2) * STREAK_PENALTY_3_PLUS
                    consecutive_streak = 1

            # Score the final streak of the day
            if consecutive_streak == 1:
                score -= STREAK_PENALTY_1
            elif consecutive_streak == 2:
                score += STREAK_BONUS_2
            else:
                score -= (consecutive_streak - 2) * STREAK_PENALTY_3_PLUS

        return (score,)

    # handles finding the best individual from the Hall of Fame and building
    # the final timetable. You will only need to adjust its decoding logic
    # when building the final timetable.

    def run(self, generations=150, pop_size=500) -> Optional[Timetable]:
        if not self.gene_map:
            # print(
            #     "\nError: No sections available for the selected subjects. Cannot generate a timetable."
            # )
            return None

        pop = self.toolbox.population(n=pop_size)
        hof = tools.HallOfFame(1)
        stats = tools.Statistics(lambda ind: ind.fitness.values[0])
        stats.register("avg", np.mean)
        stats.register("max", np.max)
        stats.register("min", np.min)

        algorithms.eaSimple(
            pop,
            self.toolbox,
            cxpb=0.8,
            mutpb=0.2,
            ngen=generations,
            stats=stats,
            halloffame=hof,
            verbose=False,
        )

        if not hof or hof[0].fitness.values[0] == 0:
            # print("\n" + "=" * 50)
            # print("COULD NOT FIND A VALID, CLASH-FREE TIMETABLE")
            # print(
            #     "This likely means there are unavoidable time clashes between the required sections of your chosen subjects, even with all possible combinations."
            # )
            # print("Please try a different combination of subjects.")
            # print("=" * 50)
            return None

        # Build the best timetable from the best individual
        best_ind = hof[0]
        best_timetable = Timetable()

        # --- Decode the BEST individual to build the final timetable ---
        # This logic mirrors the decoding in evaluate()
        sections_to_schedule = []
        if self.enforce_ties:
            gene_idx = 0
            for map_item in self.gene_map:
                lecture_choice = best_ind[gene_idx]
                tutorial_choice = best_ind[gene_idx + 1]
                chosen_pair = map_item["pairs"][lecture_choice]
                lecture_section = chosen_pair[0]
                sections_to_schedule.append(lecture_section)
                tied_tutorials = chosen_pair[1]
                if tied_tutorials:
                    safe_tutorial_choice = tutorial_choice % len(tied_tutorials)
                    tutorial_section = tied_tutorials[safe_tutorial_choice]
                    sections_to_schedule.append(tutorial_section)
                gene_idx += 2
        else:  # Not enforcing ties
            for i, map_item in enumerate(self.gene_map):
                choice = best_ind[i]
                sections_to_schedule.append(map_item["sections"][choice])

        for section in sections_to_schedule:
            best_timetable.add_section(section)

        return best_timetable


# ### NEW ###: Function to format the timetable for JSON output
def format_timetable_as_json(timetable: Optional[Timetable]) -> Dict:
    """Convert the Timetable object to a JSON-serializable dictionary."""
    if not timetable:
        return {"status": "error", "message": "No timetable could be generated."}

    schedule_dict = defaultdict(list)
    for day, scheduled_classes in timetable.schedule.items():
        for sc in scheduled_classes:
            schedule_dict[day].append({
                "code": sc.class_obj.code,
                "subject": sc.class_obj.subject,
                "activity": sc.class_obj.activity,
                "section": sc.class_obj.section,
                "start_time": sc.start_time.strftime('%H:%M:%S'),
                "end_time": sc.end_time.strftime('%H:%M:%S'),
                "venue": sc.class_obj.venue,
                "lecturer": sc.class_obj.lecturer,
            })

    return {
        "status": "success",
        "timetable": schedule_dict
    }


# ### REWRITTEN ###: Main execution block
def main():
    """
    Main function to be called when the script is executed.
    Reads JSON from stdin, generates a timetable, and prints JSON to stdout.
    """
    try:
        # 1. Read input data from stdin
        input_data = json.load(sys.stdin)
        classes_data = input_data.get("classes")
        user_prefs = input_data.get("preferences")

        if not classes_data or not user_prefs:
            raise ValueError("Missing 'classes' or 'preferences' in JSON input.")

        # 2. Load and process class data
        classes = load_classes_from_json(classes_data)
        if not classes:
            raise ValueError("Could not load any valid classes from the provided data.")

        # Convert time strings in preferences to time objects
        if 'preferred_start' in user_prefs and user_prefs['preferred_start']:
            user_prefs['preferred_start'] = datetime.strptime(user_prefs['preferred_start'], '%H:%M:%S').time()
        else:
            user_prefs['preferred_start'] = time.min

        if 'preferred_end' in user_prefs and user_prefs['preferred_end']:
            user_prefs['preferred_end'] = datetime.strptime(user_prefs['preferred_end'], '%H:%M:%S').time()
        else:
            user_prefs['preferred_end'] = time.max


        # 3. Generate the timetable
        generator = TimetableGenerator(classes, user_prefs)
        best_timetable = generator.run(generations=150, pop_size=500) # Use appropriate params

        # 4. Format and print the output
        output_json = format_timetable_as_json(best_timetable)
        json.dump(output_json, sys.stdout, indent=4)

    except (json.JSONDecodeError, ValueError, KeyError) as e:
        # If any error occurs, print an error JSON to stdout
        error_output = {
            "status": "error",
            "message": str(e)
        }
        json.dump(error_output, sys.stdout, indent=4)
        sys.exit(1)


if __name__ == "__main__":
    main()
