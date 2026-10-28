@echo off
title Canteen Pre-Order & Queue System - Local Server
echo ====================================================================
echo Starting Canteen Pre-Order & Queue System
echo Server running at: http://localhost:8080/
echo Press Ctrl+C in this terminal window to stop the server.
echo ====================================================================

REM Open the application directly in the user's default browser
start "" "http://localhost:8080/index.php"

REM Run the PHP built-in server on port 8080
.\php\php.exe -S localhost:8080
pause
