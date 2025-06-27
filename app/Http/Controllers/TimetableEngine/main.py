#!/usr/bin/env python3
"""
Timetable Generator Main Script

This is the main entry point for the timetable generation system.
It reads JSON input from stdin and outputs a generated timetable as JSON to stdout.

Usage:
    echo '{"classes": [...], "preferences": {...}}' | python main.py
"""

import sys
import json
from datetime import datetime, time
from typing import Dict, Any

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


def validate_input(input_data: Dict[str, Any]) -> None:
    """Validate the input data structure."""
    if not input_data.get("classes"):
        raise ValueError("Missing 'classes' in JSON input.")
    
    if not input_data.get("preferences"):
        raise ValueError("Missing 'preferences' in JSON input.")
    
    prefs = input_data["preferences"]
    if not prefs.get("subjects"):
        raise ValueError("Missing 'subjects' in preferences.")


def main():
    """
    Main function to be called when the script is executed.
    Reads JSON from stdin, generates a timetable, and prints JSON to stdout.
    """
    try:
        # 1. Read and validate input data
        input_data = json.load(sys.stdin)
        validate_input(input_data)
        
        classes_data = input_data["classes"]
        user_prefs = input_data["preferences"]

        # 2. Load and process class data
        classes = load_classes_from_json(classes_data)
        if not classes:
            raise ValueError("Could not load any valid classes from the provided data.")

        # 3. Parse time preferences
        user_prefs = parse_time_preferences(user_prefs)

        # 4. Generate the timetable
        generator = TimetableGenerator(classes, user_prefs)
        best_timetable = generator.run()

        # 5. Format and output the result
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
