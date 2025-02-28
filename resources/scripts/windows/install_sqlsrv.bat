@echo off
setlocal enabledelayedexpansion

echo DBBridge SQL Server Extension Installer for Windows
echo ==================================================
echo.

:: Check if running as administrator
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo This script requires administrator privileges.
    echo Please run this script as an administrator.
    exit /b 1
)

:: Get PHP information
for /f "tokens=*" %%i in ('php -r "echo PHP_VERSION;"') do set PHP_VERSION=%%i
for /f "tokens=*" %%i in ('php -r "echo php_ini_loaded_file();"') do set PHP_INI=%%i
for /f "tokens=*" %%i in ('php -r "echo ini_get(\"extension_dir\");"') do set EXT_DIR=%%i

echo PHP Version: %PHP_VERSION%
echo PHP INI: %PHP_INI%
echo Extension Directory: %EXT_DIR%
echo.

:: Check if extension directory exists
if not exist "%EXT_DIR%" (
    echo Extension directory does not exist. Creating...
    mkdir "%EXT_DIR%"
)

:: Download SQLSRV drivers
echo Downloading SQL Server drivers...
powershell -Command "& {[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; Invoke-WebRequest -Uri 'https://github.com/microsoft/msphpsql/releases/download/v5.10.1/Windows-8.1.zip' -OutFile '%TEMP%\sqlsrv.zip'}"

if %errorLevel% neq 0 (
    echo Failed to download SQL Server drivers.
    exit /b 1
)

:: Extract drivers
echo Extracting drivers...
powershell -Command "& {Expand-Archive -Path '%TEMP%\sqlsrv.zip' -DestinationPath '%TEMP%\sqlsrv' -Force}"

:: Determine PHP version for correct DLLs
set PHP_MAJOR_MINOR=%PHP_VERSION:~0,3%
set PHP_THREAD=nts
php -r "echo PHP_ZTS ? 'ts' : 'nts';" > %TEMP%\php_thread.txt
set /p PHP_THREAD=<%TEMP%\php_thread.txt
del %TEMP%\php_thread.txt

echo PHP Version: %PHP_MAJOR_MINOR% (%PHP_THREAD%)
echo.

:: Copy appropriate DLLs
echo Copying extension files to %EXT_DIR%...
set DLL_PATH=%TEMP%\sqlsrv\Windows-8.1\x64\PHP_%PHP_MAJOR_MINOR%\%PHP_THREAD%

if not exist "%DLL_PATH%" (
    echo Could not find DLLs for PHP %PHP_MAJOR_MINOR% (%PHP_THREAD%).
    echo Please download the appropriate drivers manually.
    exit /b 1
)

copy "%DLL_PATH%\php_sqlsrv.dll" "%EXT_DIR%"
copy "%DLL_PATH%\php_pdo_sqlsrv.dll" "%EXT_DIR%"

if %errorLevel% neq 0 (
    echo Failed to copy extension files.
    exit /b 1
)

:: Update php.ini
echo Updating php.ini...
findstr /c:"extension=sqlsrv" "%PHP_INI%" >nul
if %errorLevel% neq 0 (
    echo extension=sqlsrv >> "%PHP_INI%"
)

findstr /c:"extension=pdo_sqlsrv" "%PHP_INI%" >nul
if %errorLevel% neq 0 (
    echo extension=pdo_sqlsrv >> "%PHP_INI%"
)

:: Clean up
echo Cleaning up temporary files...
rmdir /s /q "%TEMP%\sqlsrv"
del "%TEMP%\sqlsrv.zip"

echo.
echo Installation completed successfully!
echo Please restart your web server for the changes to take effect.

endlocal 