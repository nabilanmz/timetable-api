# Timetable Generator Engine

A modular genetic algorithm-based timetable generator that creates optimal class schedules based on user preferences and constraints.

## Architecture

The system is organized into several specialized modules:

```
TimetableEngine/
├── __init__.py          # Package initialization and exports
├── main.py             # Main entry point for standalone usage
├── constants.py        # Configuration constants and scoring profiles
├── models.py           # Core data structures (Class, Timetable, etc.)
├── data_loader.py      # Data loading utilities (CSV/JSON)
├── genetic_algorithm.py # GA implementation and evolution logic
├── scoring.py          # Timetable quality evaluation
└── formatter.py        # Output formatting (JSON, text)
```

## How It Works

### 1. **Data Flow**
```
JSON Input → Load Classes → Setup GA → Evolution → Best Timetable → JSON Output
```

### 2. **Core Components**

#### **Models** (`models.py`)
- **Class**: Represents a single class session with all properties
- **ScheduledClass**: A class placed in a specific time slot  
- **Timetable**: Manages the weekly schedule and provides utility methods

#### **Genetic Algorithm** (`genetic_algorithm.py`)
- **TimetableGenerator**: Main GA engine
- Supports two modes:
  - **Tied Mode**: Lectures and tutorials must be from tied sections
  - **Independent Mode**: Lectures and tutorials chosen separately
- Uses DEAP library for evolution operations

#### **Scoring System** (`scoring.py`)
- **ScoreCalculator**: Evaluates timetable quality
- Supports multiple scheduling styles:
  - **Compact**: Prefers fewer days, consecutive classes
  - **Spaced Out**: Prefers spread across days, avoids wasteful single-class days
- Considers gaps, streaks, preferences, and day utilization

#### **Data Loading** (`data_loader.py`)
- Supports CSV (legacy) and JSON (primary) input formats
- Groups classes by subject and section for GA processing
- Handles time parsing and validation

#### **Output Formatting** (`formatter.py`)
- Converts timetable objects to JSON or human-readable text
- Includes summary statistics and metadata

## Usage

### As a Module
```python
from TimetableEngine import TimetableGenerator, load_classes_from_json, format_timetable_as_json

# Load data
classes = load_classes_from_json(classes_data)

# Generate timetable
generator = TimetableGenerator(classes, user_preferences)
timetable = generator.run()

# Format output
result = format_timetable_as_json(timetable)
```

### Standalone Script
```bash
echo '{"classes": [...], "preferences": {...}}' | python main.py
```

## Configuration

All constants and scoring profiles are centralized in `constants.py`:
- Day definitions and time preferences
- GA parameters (population size, generations, etc.)
- Scoring weights for different schedule styles
- Bonus/penalty values

## Key Features

- **Flexible Constraints**: Supports both tied and independent section selection
- **Multiple Scheduling Styles**: Compact vs. spaced-out preferences
- **Conflict Resolution**: Hard constraint checking prevents time clashes
- **Preference Integration**: Considers lecturer, time, and day preferences
- **Extensible Scoring**: Easy to modify scoring criteria and weights
- **Clean Architecture**: Modular design for maintainability and testing
