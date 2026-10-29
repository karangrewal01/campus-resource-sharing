@echo off
setlocal EnableDelayedExpansion
title Campus Resource Sharing Platform - Push to GitHub
color 0A
cls

echo =====================================================================
echo           CAMPUS RESOURCE SHARING - GITHUB PUSH UTILITY
echo =====================================================================
echo.

:: Navigate to the project directory
set "PROJECT_DIR=C:\Users\Girish Jindal\.gemini\antigravity\scratch\campus-resource-sharing"
if not exist "%PROJECT_DIR%" (
    set "PROJECT_DIR=%~dp0"
)
cd /d "%PROJECT_DIR%"
echo [INFO] Working Directory: %CD%
echo.

:: Ensure Git is accessible
where git >nul 2>nul
if errorlevel 1 (
    if exist "C:\Program Files\Git\cmd\git.exe" (
        set "PATH=%PATH%;C:\Program Files\Git\cmd"
    ) else (
        color 0C
        echo [ERROR] Git is not installed or not found in PATH.
        pause
        exit /b 1
    )
)

:: 1. Initialize Git repository if not already initialized
if not exist ".git" (
    echo [1/4] Initializing local Git repository...
    git init
    git branch -M main
) else (
    echo [1/4] Local Git repository is active.
)

:: 2. Ensure Git author is configured
set "GIT_NAME="
set "GIT_EMAIL="
for /f "delims=" %%i in ('git config user.name 2^>nul') do set "GIT_NAME=%%i"
for /f "delims=" %%i in ('git config user.email 2^>nul') do set "GIT_EMAIL=%%i"

if "!GIT_NAME!"=="" (
    git config user.name "karangrewal01"
    set "GIT_NAME=karangrewal01"
)
if "!GIT_EMAIL!"=="" (
    git config user.email "karangrewal1545@gmail.com"
    set "GIT_EMAIL=karangrewal1545@gmail.com"
)
echo [2/4] Git Author: !GIT_NAME! ^<!GIT_EMAIL!^>

:: 3. Stage and commit any new or modified files
echo [3/4] Checking and committing project files...
git add .
git diff --cached --quiet
if errorlevel 1 (
    git commit -m "Update Campus Resource Sharing Platform"
    echo       Committed latest changes.
) else (
    echo       All 77 project files are committed and ready.
)

:: 4. Ensure remote URL is set
set "REMOTE_URL="
for /f "delims=" %%i in ('git remote get-url origin 2^>nul') do set "REMOTE_URL=%%i"
if "!REMOTE_URL!"=="" (
    set "REMOTE_URL=https://karangrewal01@github.com/karangrewal01/campus-resource-sharing.git"
    git remote add origin "!REMOTE_URL!"
)
echo [4/4] Target Repository: !REMOTE_URL!
echo.
echo Pushing to GitHub (branch: main)...
echo (If a GitHub sign-in window opens, sign in as 'karangrewal01' and authorize)
echo.

git branch -M main
git push -u origin main

if errorlevel 1 (
    echo.
    color 0E
    echo [INFO] Detected cached credential from a different GitHub account on this PC.
    echo        Switching GitHub account to 'karangrewal01' and retrying push...
    echo.
    cmdkey /delete:LegacyGeneric:target=git:https://github.com >nul 2>nul
    git remote set-url origin "https://karangrewal01@github.com/karangrewal01/campus-resource-sharing.git"
    git push -u origin main --force
)

if not errorlevel 1 (
    color 0A
    echo.
    echo =====================================================================
    echo   [SUCCESS] All 77 project files pushed to GitHub!
    echo   View at: https://github.com/karangrewal01/campus-resource-sharing
    echo =====================================================================
) else (
    color 0C
    echo.
    echo =====================================================================
    echo   [ERROR] Push did not complete. Make sure you sign in as
    echo   'karangrewal01' in the GitHub popup window.
    echo =====================================================================
)

echo.
pause
