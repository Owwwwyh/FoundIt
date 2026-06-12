@echo off
title FoundIt Frontend (port 5173)
cd /d "%~dp0foundit-app"
echo ============================================================
echo   FoundIt frontend  ->  http://localhost:5173
echo   Keep this window open. Press Ctrl+C to stop.
echo ============================================================
echo.
npm run dev
pause
