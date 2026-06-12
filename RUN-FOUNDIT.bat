@echo off
echo ============================================================
echo   FoundIt - starting database + backend + frontend
echo ============================================================
echo.

rem --- Start MySQL only if nothing is listening on 3306 yet ---
netstat -an | findstr ":3306" | findstr "LISTENING" >nul
if errorlevel 1 (
  echo Starting MySQL...
  start "" "%~dp0START-MYSQL.bat"
  timeout /t 4 >nul
) else (
  echo MySQL is already running.
)

echo Starting backend  on http://localhost:8081 ...
start "" "%~dp0start-backend.bat"

echo Starting frontend on http://localhost:5173 ...
start "" "%~dp0start-frontend.bat"

echo.
echo Opening the app in your browser in a few seconds...
timeout /t 5 >nul
start "" http://localhost:5173
exit
