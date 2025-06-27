#!/usr/bin/env python3
"""
Timetable Generator - Updated to use modular structure

This is a compatibility wrapper that maintains the same interface as the original
TimetableGenerator.py while using the new modular TimetableEngine.
"""

import sys
import json
from datetime import datetime, time
from typing import Dict, Any

# Import from the new modular structure
from TimetableEngine import (
    load_classes_from_json,
    TimetableGenerator,
    format_timetable_as_json
)


def parse_time_preferences(user_prefs: Dict[str, Any]) -> Dict[str, Any]:
    """Convert time strings in preferences to time objects."""
    if 'preferred_start' in user_prefs and user_prefs['preferred_start']:
        user_prefs['preferred_start'] = datetime.strptime(
            user_prefs['preferred_start'], '%H:%M:%S'
        ).time()
    else:
        user_prefs['preferred_start'] = time.min

    if 'preferred_end' in user_prefs and user_prefs['preferred_end']:
        user_prefs['preferred_end'] = datetime.strptime(
            user_prefs['preferred_end'], '%H:%M:%S'
        ).time()
    else:
        user_prefs['preferred_end'] = time.max
    
    return user_prefs


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

        # 3. Convert time strings in preferences to time objects
        user_prefs = parse_time_preferences(user_prefs)

        # 4. Generate the timetable
        generator = TimetableGenerator(classes, user_prefs)
        best_timetable = generator.run(generations=150, pop_size=500)

        # 5. Format and print the output
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
