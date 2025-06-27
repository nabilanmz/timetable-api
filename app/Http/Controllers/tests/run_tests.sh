#!/bin/bash

# TimetableEngine Test Runner
# Runs focused tests for the current TimetableEngine implementation

echo "========================================"
echo "TimetableEngine Test Suite"
echo "========================================"

# Change to the tests directory
cd "$(dirname "$0")"

echo "Current directory: $(pwd)"
echo ""

# Check if Python is available
if ! command -v python3 &> /dev/null; then
    echo "✗ Python 3 is required but not found"
    exit 1
fi

echo "✓ Python 3 found: $(python3 --version)"
echo ""

# Check if required dependencies are available
echo "Checking dependencies..."
python3 -c "import deap, numpy; print('✓ Required dependencies found')" 2>/dev/null || {
    echo "⚠️  Missing dependencies. Install with:"
    echo "   pip install deap numpy"
    echo ""
}

# Run the performance test (quick and comprehensive)
echo "1. Running Performance Tests..."
echo "----------------------------------------"
python3 performance_test.py
echo ""

# Run the detailed functional tests
echo "2. Running Functional Tests..."
echo "----------------------------------------"
python3 test_timetable_engine.py
echo ""

echo "========================================"
echo "Test Suite Complete"
echo "========================================"
echo ""
echo "If any tests failed, check:"
echo "1. That all required dependencies are installed (deap, numpy)"
echo "2. That the TimetableEngine directory is properly structured"
echo "3. That TimetableGenerator.py can import from TimetableEngine"
echo ""
echo "For debugging, you can run individual tests:"
echo "- python3 performance_test.py"
echo "- python3 test_timetable_engine.py"
