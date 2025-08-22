#!/bin/bash

echo "Building Tailwind CSS..."

# Check if node_modules exists
if [ ! -d "node_modules" ]; then
    echo "Installing dependencies..."
    npm install
fi

# Build CSS
echo "Building CSS from src/input.css to public/assets/css/styles.css..."
npx tailwindcss -i ./src/input.css -o ./public/assets/css/styles.css --minify

echo "CSS build complete!"
echo "File size: $(du -h public/assets/css/styles.css | cut -f1)"
