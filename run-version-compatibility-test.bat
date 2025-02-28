@echo off
echo Running Version Compatibility tests...
call vendor\bin\phpunit tests\Feature\VersionCompatibilityTest.php --testdox
exit /b %errorlevel% 