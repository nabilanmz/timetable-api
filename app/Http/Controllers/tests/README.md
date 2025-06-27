# TimetableEngine Test Suite

This directory contains focused tests for the current TimetableEngine implementation to ensure it works correctly for all supported scenarios and edge cases.

## Test Files

### 1. `performance_test.py` - Performance & Integration Test
**Purpose**: Quick comprehensive test that validates core functionality and performance.

**What it tests**:
- Basic timetable generation
- Different scheduling styles (compact vs spaced-out)
- Constraint modes (tied vs independent sections)
- Edge cases and error handling
- Generation speed and efficiency

**Run with**: `python3 performance_test.py`

### 2. `test_timetable_engine.py` - Detailed Functional Tests
**Purpose**: Comprehensive unit tests for all TimetableEngine components.

**What it tests**:
- **Core Components**: Class model, data loading, timetable operations
- **Generation Logic**: Basic generation, constraint handling
- **Scheduling Styles**: Compact and spaced-out preferences
- **Output Formatting**: JSON and text output formats
- **Scoring System**: Preference bonuses and calculations

**Run with**: `python3 test_timetable_engine.py`

### 3. `run_tests.sh` - Test Runner Script
**Purpose**: Runs all tests in sequence with formatted output.

**Run with**: `./run_tests.sh`

## How to Run the Tests

### Quick Test (Recommended)
```bash
cd tests
./run_tests.sh
```

### Individual Tests
```bash
# Performance test (fastest, most comprehensive)
python3 performance_test.py

# Detailed functional tests
python3 test_timetable_engine.py
```

## What the Tests Verify

### ✅ **Core Functionality**
- Timetable generation works for valid inputs
- Class loading and data processing
- Conflict detection and constraint satisfaction
- Genetic algorithm optimization

### ✅ **Scheduling Features**
- Tied section constraints (lectures with specific tutorials)
- Independent section selection
- Compact vs spaced-out scheduling styles
- User preference integration (time, lecturer, day preferences)

### ✅ **Edge Cases & Error Handling**
- Empty or invalid input handling
- Impossible constraint scenarios
- Time conflict detection
- Graceful failure modes

### ✅ **Performance**
- Reasonable generation times (typically < 5 seconds)
- Consistent results across runs
- Memory efficiency

### ✅ **Output Quality**
- Valid JSON format with proper structure
- Human-readable text format
- Complete timetable data with all required fields

## Understanding Test Results

### Success Indicators
- ✅ **"All tests passed!"** - Core functionality works correctly
- ✅ **"Generation completed in X seconds"** - Performance is acceptable
- ✅ **"Classes scheduled: X"** - Required courses are included
- ✅ **"Correctly handled [edge case]"** - Error handling works

### Warning Indicators
- ⚠️ **"Generated timetable despite conflicts"** - May indicate logic issues
- ⚠️ **"Missing dependencies"** - Install required packages

### Failure Indicators
- ❌ **"Generation failed"** - Core algorithm issues
- ❌ **"Error loading classes"** - Data processing problems
- ❌ **"Import errors"** - Module structure issues

## Test Data

The tests use sample academic data with:
- **Multiple subjects**: Computer Science, Mathematics, Physics
- **Various activities**: Lectures and tutorials
- **Tied sections**: Lectures with associated tutorial options
- **Time constraints**: Different time slots and days
- **Preference scenarios**: Various user preference combinations

## Expected Results

When working correctly, you should see:
```
🎉 All tests passed! TimetableEngine is working correctly.
✓ Basic Functionality
✓ Different Styles  
✓ Constraint Modes
✓ Edge Cases
📈 Summary: 4/4 tests passed
```

## Troubleshooting

### Import Errors
- Ensure `TimetableEngine/` directory exists with all modules
- Check that `__init__.py` files are present
- Verify Python path includes the Controllers directory

### DEAP Errors
- Install required dependencies: `pip install deap numpy`
- Check that DEAP creator classes are properly initialized

### Performance Issues
- Reduce GA parameters for faster testing (generations=30, pop_size=50)
- Check system resources if generation takes too long

### Logic Errors
- Review test output for specific failure details
- Check that TimetableEngine modules are properly implemented
- Verify genetic algorithm setup and evaluation logic

## Benefits of Current Test Suite

### 🎯 **Focused Testing**
- Tests the actual system being used
- No comparison overhead
- Clear pass/fail criteria

### 🚀 **Performance Monitoring**
- Tracks generation speed
- Validates optimization quality
- Ensures scalability

### 🔧 **Easy Debugging**
- Isolated component testing
- Clear error messages
- Specific failure identification

### 📈 **Regression Prevention**
- Catches breaking changes
- Validates new features
- Ensures consistent behavior

The test suite provides confidence that TimetableEngine works correctly and performs well for all supported use cases.
