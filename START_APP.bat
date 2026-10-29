@echo off
title Campus Resource Sharing Platform - One-Click Launcher
color 0B
cls
echo =====================================================================
echo                CAMPUS RESOURCE SHARING PLATFORM
echo          Full-Stack BCA Academic Project - Core PHP & MySQL
echo =====================================================================
echo.

:: 1. Check if MySQL is running
echo [1/3] Checking MySQL service...
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo       MySQL is already running.
) else (
    echo       Starting MySQL in the background...
    if exist "C:\xampp\mysql\bin\mysqld.exe" (
        start "" /B "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone
        timeout /t 2 >nul
    ) else (
        echo [!] Warning: Could not locate C:\xampp\mysql\bin\mysqld.exe. Please start MySQL manually in XAMPP.
    )
)

:: 2. Check if Apache is running
echo [2/3] Checking Apache web server...
tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I /N "httpd.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo       Apache is already running.
) else (
    echo       Starting Apache in the background...
    if exist "C:\xampp\apache\bin\httpd.exe" (
        start "" /B "C:\xampp\apache\bin\httpd.exe"
        timeout /t 2 >nul
    ) else (
        echo [!] Warning: Could not locate C:\xampp\apache\bin\httpd.exe. Please start Apache manually in XAMPP.
    )
)

:: 3. Launch the browser
echo [3/3] Opening Campus Resource Sharing in your default browser...
echo.
echo =====================================================================
echo  Platform URL: http://localhost/campus-resource-sharing/
echo.
echo  Quick Demo Credentials:
echo   - Admin:   admin@campus.edu               (Password: Admin@12345)
echo   - Faculty: prof.sharma@campus.edu         (Password: Faculty@12345)
echo   - Student: rahul.sharma@student.campus.edu (Password: Student@12345)
echo =====================================================================
echo.

start "" "http://localhost/campus-resource-sharing/"

echo The application is now open in your browser!
echo You can keep this window open or close it anytime.
pause
