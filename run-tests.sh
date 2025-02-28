#!/bin/bash

# Ensure we're in the project root
cd "$(dirname "$0")"

# Install dependencies if needed
if [ ! -d "vendor" ]; then
    echo "Installing dependencies..."
    composer install
fi

# Run the tests
echo "Running tests..."
./vendor/bin/pest

# Exit with the same code as the test command
exit $? 