"""
Data loading utilities for the timetable generator.

This module handles loading class data from various sources:
- CSV files (legacy support)
- JSON data (primary method)
"""

import csv
import sys
from typing import List, Dict
from datetime import datetime
from models import Class


def load_classes_from_csv(filename: str) -> List[Class]:
    """Load classes from CSV file, including the 'Tied To' column."""
    classes = []
    with open(filename, mode="r", encoding="utf-8") as file:
        reader = csv.DictReader(file)
        for row in reader:
            try:
                # Parse time fields with flexible format support
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

                # Parse the 'Tied To' column
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
                        tied_to=tied_to_list,
                        lecturer=row["Lecturer"] if row["Lecturer"] else "Not Assigned",
                    )
                )
            except (ValueError, KeyError) as e:
                print(f"Skipping row due to error: {e} in row {row}", file=sys.stderr)
                continue
    return classes


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
    """
    Group classes by subject and section.
    
    Returns:
        Dict[subject][activity_section] -> List[Class]
        Example: {"Math101": {"Lecture_A": [class1, class2], "Tutorial_T1": [class3]}}
    """
    from collections import defaultdict
    
    section_groups: Dict[str, Dict[str, List[Class]]] = defaultdict(lambda: defaultdict(list))
    for cls in classes:
        section_groups[cls.subject][f"{cls.activity}_{cls.section}"].append(cls)
    return section_groups
