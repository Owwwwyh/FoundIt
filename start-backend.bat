@echo off
title FoundIt Backend (port 8081)
cd /d "%~dp0foundit-api"
echo ============================================================
echo   FoundIt backend  ->  http://localhost:8081/api/items
echo   Keep this window open. Press Ctrl+C to stop.
echo ============================================================
echo.
php -S localhost:8081 -t public
pause
