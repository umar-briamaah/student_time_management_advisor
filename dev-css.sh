#!/bin/bash

echo "Starting Tailwind CSS development mode..."
echo "Watching for changes in src/input.css..."
echo "Press Ctrl+C to stop watching"

# Check if node_modules exists
if [ ! -d "node_modules" ]; then
    echo "Installing dependencies..."
    npm install
fi

# Start watching for changes
npx tailwindcss -i ./src/input.css -o ./public/assets/css/styles.css --watch
