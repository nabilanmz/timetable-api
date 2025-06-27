# Timetable Generator System

## Current Architecture

The timetable generation system uses a modern, modular architecture:

```
TimetableGenerator.py           # Main entry point
├── Uses: TimetableEngine/      # Modular engine
│   ├── models.py              # Data structures
│   ├── genetic_algorithm.py   # GA implementation  
│   ├── scoring.py             # Fitness evaluation
│   ├── data_loader.py         # Input parsing
│   ├── formatter.py           # Output formatting
│   ├── constants.py           # Configuration
│   ├── main.py               # Standalone interface
│   └── README.md             # Detailed documentation
└── tests/                    # Test suite
    ├── test_timetable_engine.py  # Functional tests
    ├── performance_test.py       # Performance tests
    ├── run_tests.sh             # Test runner
    └── README.md               # Test documentation
```

## Features

✅ **Genetic Algorithm Optimization**: Uses DEAP library for evolution-based scheduling  
✅ **Multiple Scheduling Styles**: Compact vs spaced-out preferences  
✅ **Constraint Handling**: Tied and independent section modes  
✅ **User Preferences**: Time, lecturer, and day preferences  
✅ **JSON Interface**: Clean input/output format  
✅ **Comprehensive Testing**: Full test coverage with performance validation  
✅ **Modular Design**: Easy to maintain and extend  

## Usage

### From PHP (Laravel)
The system integrates seamlessly with the Laravel application through `GeneratedTimetableController.php`.

### Command Line
```bash
echo '{"classes": [...], "preferences": {...}}' | python3 TimetableGenerator.py
```

### Standalone
```bash
cd TimetableEngine
python3 main.py input.json
```

## Testing

```bash
cd tests
./run_tests.sh
```

All tests validate the current TimetableEngine implementation for correctness, performance, and edge case handling.

## Development

The modular architecture makes it easy to:
- Add new scheduling algorithms
- Implement additional constraints
- Extend the scoring system
- Add new output formats
- Integrate with different data sources

See `TimetableEngine/README.md` for detailed technical documentation.
