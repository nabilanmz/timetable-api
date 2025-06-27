"""
Genetic Algorithm implementation for timetable generation.

This module contains the core genetic algorithm logic that evolves
populations of timetable configurations to find optimal solutions.
"""

import random
from typing import List, Dict, Optional, Tuple
import numpy as np
from deap import base, creator, tools, algorithms

from .models import Class, Timetable
from .data_loader import group_classes_by_section
from .scoring import ScoreCalculator
from .constants import (
    BASE_SCORE, DEFAULT_GENERATIONS, DEFAULT_POPULATION_SIZE,
    CROSSOVER_PROBABILITY, MUTATION_PROBABILITY, TOURNAMENT_SIZE
)

# Initialize DEAP (only create if not already created)
if not hasattr(creator, "FitnessMax"):
    creator.create("FitnessMax", base.Fitness, weights=(1.0,))
if not hasattr(creator, "Individual"):
    creator.create("Individual", list, fitness=creator.FitnessMax)


class TimetableGenerator:
    """Main genetic algorithm engine for timetable generation."""
    
    def __init__(self, classes: List[Class], user_preferences: dict):
        self.classes = classes
        self.user_preferences = user_preferences
        self.enforce_ties = user_preferences.get("enforce_ties", True)
        self.section_groups = group_classes_by_section(classes)
        self.gene_map = []
        self.score_calculator = ScoreCalculator(user_preferences)
        self.setup_deap()

    def setup_deap(self):
        """Set up DEAP toolbox based on whether ties are enforced."""
        self.toolbox = base.Toolbox()
        gene_upper_bounds = []

        if self.enforce_ties:
            self._setup_tied_genes(gene_upper_bounds)
        else:
            self._setup_independent_genes(gene_upper_bounds)

        if not self.gene_map:
            raise ValueError(
                "No valid sections found for the selected subjects with the chosen constraints."
            )

        self._register_genetic_operators(gene_upper_bounds)

    def _setup_tied_genes(self, gene_upper_bounds: List[int]):
        """Set up genes for tied lecture-tutorial pairs."""
        for subject in self.user_preferences["subjects"]:
            if subject not in self.section_groups:
                continue

            subject_lectures = sorted(
                [
                    sc for sk, sc in self.section_groups[subject].items()
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
                    lecture_tutorial_pairs.append((lect_section_group, tied_tutorials))
                    max_tied_tutorials = max(max_tied_tutorials, len(tied_tutorials))

            if lecture_tutorial_pairs:
                self.gene_map.append({
                    "type": "tied_subject",
                    "subject": subject,
                    "pairs": lecture_tutorial_pairs,
                })
                # Gene 1: choice of lecture, Gene 2: choice of tutorial from tied list
                gene_upper_bounds.append(len(lecture_tutorial_pairs) - 1)
                gene_upper_bounds.append(max_tied_tutorials - 1 if max_tied_tutorials > 0 else 0)

    def _setup_independent_genes(self, gene_upper_bounds: List[int]):
        """Set up genes for independent lecture and tutorial choices."""
        for subject in self.user_preferences["subjects"]:
            if subject not in self.section_groups:
                continue

            lectures = [
                sc for sk, sc in self.section_groups[subject].items()
                if sk.startswith("Lecture")
            ]
            tutorials = [
                sc for sk, sc in self.section_groups[subject].items()
                if sk.startswith("Tutorial")
            ]

            if lectures:
                self.gene_map.append({
                    "type": "independent_activity",
                    "activity": "Lecture",
                    "subject": subject,
                    "sections": lectures,
                })
                gene_upper_bounds.append(len(lectures) - 1)
                
            if tutorials:
                self.gene_map.append({
                    "type": "independent_activity",
                    "activity": "Tutorial",
                    "subject": subject,
                    "sections": tutorials,
                })
                gene_upper_bounds.append(len(tutorials) - 1)

    def _register_genetic_operators(self, gene_upper_bounds: List[int]):
        """Register genetic operators with DEAP."""
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
        self.toolbox.register("select", tools.selTournament, tournsize=TOURNAMENT_SIZE)

    def evaluate(self, individual: List[int]) -> Tuple[float,]:
        """Evaluate the fitness of an individual (timetable configuration)."""
        timetable = Timetable()

        # Decode the individual into sections to schedule
        sections_to_schedule = self._decode_individual(individual)

        # Check for hard constraints (time clashes)
        for section in sections_to_schedule:
            if not timetable.can_add_section(section):
                return (0,)  # Invalid timetable
            timetable.add_section(section)

        # Calculate fitness score
        score = BASE_SCORE
        
        # Add component scores
        score += self.score_calculator.calculate_day_utilization_score(timetable)
        score += self.score_calculator.calculate_preference_bonuses(timetable)
        score += self.score_calculator.calculate_gap_scores(timetable)
        score += self.score_calculator.calculate_streak_scores(timetable)

        return (score,)

    def _decode_individual(self, individual: List[int]) -> List[List[Class]]:
        """Decode an individual's genes into actual class sections."""
        sections_to_schedule = []
        
        if self.enforce_ties:
            gene_idx = 0
            for map_item in self.gene_map:
                lecture_choice, tutorial_choice = individual[gene_idx], individual[gene_idx + 1]
                chosen_pair = map_item["pairs"][lecture_choice]
                sections_to_schedule.append(chosen_pair[0])  # Lecture section
                
                tied_tutorials = chosen_pair[1]
                if tied_tutorials:
                    sections_to_schedule.append(
                        tied_tutorials[tutorial_choice % len(tied_tutorials)]
                    )
                gene_idx += 2
        else:
            for i, map_item in enumerate(self.gene_map):
                sections_to_schedule.append(map_item["sections"][individual[i]])

        return sections_to_schedule

    def run(self, generations: int = DEFAULT_GENERATIONS, 
            pop_size: int = DEFAULT_POPULATION_SIZE) -> Optional[Timetable]:
        """Run the genetic algorithm to find the best timetable."""
        if not self.gene_map:
            return None

        # Initialize population and statistics
        pop = self.toolbox.population(n=pop_size)
        hof = tools.HallOfFame(1)
        stats = tools.Statistics(lambda ind: ind.fitness.values[0])
        stats.register("avg", np.mean)
        stats.register("max", np.max)
        stats.register("min", np.min)

        # Run the genetic algorithm
        algorithms.eaSimple(
            pop,
            self.toolbox,
            cxpb=CROSSOVER_PROBABILITY,
            mutpb=MUTATION_PROBABILITY,
            ngen=generations,
            stats=stats,
            halloffame=hof,
            verbose=False,
        )

        # Check if a valid solution was found
        if not hof or hof[0].fitness.values[0] == 0:
            return None

        # Build the best timetable
        return self._build_timetable_from_individual(hof[0])

    def _build_timetable_from_individual(self, individual: List[int]) -> Timetable:
        """Build a complete timetable from the best individual."""
        best_timetable = Timetable()
        sections_to_schedule = self._decode_individual(individual)
        
        for section in sections_to_schedule:
            best_timetable.add_section(section)
        
        return best_timetable
