#!/bin/bash

# Ensure we're in the project root
cd "$(dirname "$0")"

# Run the environment detector test
echo "Running EnvironmentDetector tests..."
./vendor/bin/phpunit tests/Feature/EnvironmentDetectorTest.php --testdox

# Exit with the same code as the test command
exit $? 