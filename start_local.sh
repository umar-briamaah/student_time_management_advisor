#!/bin/bash

# 🏠 Local Development Server Starter
# Simple script to start your PHP development server

echo "🚀 Starting Local Development Server..."
echo "📍 Your app will be available at: http://localhost:8000"
echo "🛑 Press Ctrl+C to stop the server"
echo ""

# Check if port 8000 is already in use
if lsof -Pi :8000 -sTCP:LISTEN -t >/dev/null ; then
    echo "⚠️  Port 8000 is already in use!"
    echo "🔄 Stopping existing server..."
    lsof -ti:8000 | xargs kill -9
    echo "✅ Port 8000 freed"
    echo ""
fi

# Start the development server
echo "🌐 Starting server..."
php -S localhost:8000 -t public
