#!/usr/bin/env python3

"""
Debug version of TimetableGenerator to see what's happening with real data
"""

import json
import sys
from TimetableEngine import TimetableGenerator, load_classes_from_json, parse_time_preferences, format_timetable_as_json

def main():
    try:
        # Read input data from file instead of stdin for debugging
        with open('test_input_real.json', 'r') as f:
            input_data = json.load(f)
        
        classes_data = input_data.get("classes")
        user_prefs = input_data.get("preferences")

        print("=== DEBUG TIMETABLE GENERATION ===")
        print(f"Number of classes: {len(classes_data)}")
        print(f"User preferences: {user_prefs}")
        print()

        if not classes_data or not user_prefs:
            raise ValueError("Missing 'classes' or 'preferences' in JSON input.")

        # Load and process class data
        classes = load_classes_from_json(classes_data)
        print(f"Loaded {len(classes)} class objects")
        
        for i, cls in enumerate(classes):
            print(f"  {i+1}. {cls.code} - {cls.activity} {cls.section} ({cls.days} {cls.start_time}-{cls.end_time})")
            print(f"      Tied to: {cls.tied_to}")
        print()

        if not classes:
            raise ValueError("Could not load any valid classes from the provided data.")

        # Convert time strings in preferences to time objects
        user_prefs = parse_time_preferences(user_prefs)
        print(f"Parsed preferences: {user_prefs}")
        print()

        # Generate the timetable
        generator = TimetableGenerator(classes, user_prefs)
        print(f"Gene map created with {len(generator.gene_map)} genes")
        
        for i, gene in enumerate(generator.gene_map):
            print(f"  Gene {i}: {gene['type']} for {gene.get('subject', 'unknown')}")
        print()

        best_timetable = generator.run(generations=50, pop_size=100)  # Smaller for debugging

        if best_timetable:
            print("SUCCESS: Generated timetable!")
            output_json = format_timetable_as_json(best_timetable)
            print(json.dumps(output_json, indent=2))
        else:
            print("FAILED: No valid timetable could be generated")
            print("This might be due to:")
            print("- Conflicting time slots")
            print("- Missing required tied sections")
            print("- Impossible constraints")

    except Exception as e:
        print(f"ERROR: {str(e)}")
        import traceback
        traceback.print_exc()

if __name__ == "__main__":
    main()
