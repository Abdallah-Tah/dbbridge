#!/bin/bash

# Ensure we're in the project root
cd "$(dirname "$0")"

# Run the version compatibility test
echo "Running Version Compatibility tests..."
vendor/bin/phpunit tests/Feature/VersionCompatibilityTest.php --testdox

# Exit with the same code as the test command
exit $? 