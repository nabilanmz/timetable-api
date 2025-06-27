"""
Output formatting utilities for the timetable generator.

This module handles converting timetable objects into various output formats,
primarily JSON for API responses.
"""

from typing import Dict, Optional
from collections import defaultdict
from models import Timetable


def format_timetable_as_json(timetable: Optional[Timetable]) -> Dict:
    """Convert the Timetable object to a JSON-serializable dictionary."""
    if not timetable:
        return {
            "status": "error", 
            "message": "No timetable could be generated."
        }

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
        "timetable": schedule_dict,
        "summary": {
            "total_classes": len(timetable.scheduled_classes),
            "days_utilized": timetable.get_utilized_days(),
            "subjects": list(timetable.get_scheduled_subjects())
        }
    }


def format_timetable_as_text(timetable: Optional[Timetable]) -> str:
    """Convert the Timetable object to a human-readable text format."""
    if not timetable:
        return "No timetable could be generated."
    
    output = []
    output.append("Generated Timetable")
    output.append("=" * 50)
    
    for day in ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"]:
        if timetable.schedule[day]:
            output.append(f"\n{day}:")
            output.append("-" * 20)
            for sc in timetable.schedule[day]:
                output.append(
                    f"  {sc.start_time.strftime('%H:%M')}-{sc.end_time.strftime('%H:%M')} "
                    f"{sc.class_obj.code} {sc.class_obj.activity} ({sc.class_obj.section}) "
                    f"- {sc.class_obj.venue} - {sc.class_obj.lecturer}"
                )
    
    output.append(f"\nSummary:")
    output.append(f"  Total classes: {len(timetable.scheduled_classes)}")
    output.append(f"  Days utilized: {timetable.get_utilized_days()}")
    output.append(f"  Subjects: {', '.join(timetable.get_scheduled_subjects())}")
    
    return "\n".join(output)
