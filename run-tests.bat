@echo off
SETLOCAL

REM Change to the directory where the script is located
cd /d "%~dp0"

REM Check if vendor directory exists
IF NOT EXIST vendor (
    echo Installing dependencies...
    call composer install
)

REM Run the tests
echo Running tests...
call vendor\bin\pest

REM Exit with the same code as the test command
exit /b %ERRORLEVEL% 