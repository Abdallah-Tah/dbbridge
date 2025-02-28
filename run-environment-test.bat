@echo off
SETLOCAL

REM Change to the directory where the script is located
cd /d "%~dp0"

REM Run the environment detector test
echo Running EnvironmentDetector tests...
call vendor\bin\phpunit tests\Feature\EnvironmentDetectorTest.php --testdox

REM Exit with the same code as the test command
exit /b %ERRORLEVEL% 