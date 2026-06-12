@echo off
title FoundIt MySQL (port 3306)
echo ============================================================
echo   Starting MySQL database (XAMPP MariaDB) on port 3306...
echo ============================================================
echo.
echo Keep THIS window open while using FoundIt.
echo (If it shows a "port in use" error, MySQL is already running - that is fine,
echo  you can close this window.)
echo.
"C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini"
pause
