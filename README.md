# DBBridge: Laravel Database Extension Manager

DBBridge is a Laravel package that simplifies the installation and configuration of PHP database extensions across different operating systems. It provides an interactive CLI that guides users through the process of setting up database drivers for SQL Server, Oracle, MySQL, and more.

## Features

- **Environment Detection**: Automatically detects your PHP environment, OS, and installed extensions
- **Cross-Platform Support**: Works on Windows, Linux (Debian/Ubuntu, RHEL/CentOS), and macOS
- **Multiple Database Support**: Handles SQL Server, Oracle, and MySQL drivers
- **Guided Installation**: Step-by-step instructions for installing required extensions
- **Automated Setup**: Scripts to automate the installation process where possible
- **Connection Testing**: Verify your database connections are working correctly
- **Version Compatibility**: Works across PHP 7.4 to 8.4 with automatic adaptation

## Requirements

- PHP 7.4 or higher (compatible with PHP 7.4, 8.0, 8.1, 8.2, 8.3, and 8.4)
- Laravel 8.0 or higher (compatible with Laravel 8, 9, 10, and 11)
- Composer

## Installation

You can install the package via composer:

```bash
composer require dbbridge/dbbridge
```

After installation, publish the configuration file:

```bash
php artisan vendor:publish --provider="DBBridge\DBBridgeServiceProvider" --tag="config"
```

**Important:** Installation commands require root privileges. Run them with `sudo`:

```bash
sudo php artisan dbbridge:install-extensions --extension=oci8
```

## Usage

### Check Your Environment

To check your current environment and installed database extensions:

```bash
php artisan dbbridge:check-environment
```

This will display information about your PHP version, operating system, and installed database extensions.

### Install Database Extensions

To install database extensions:

```bash
php artisan dbbridge:install-extensions --extension=sqlsrv
```

Available options:
- `--extension`: The extension to install (sqlsrv, oci8, mysqli)
- `--mode`: Installation mode (guided or manual, default: guided)

### Test Database Connection

To test a database connection:

```bash
php artisan dbbridge:test-connection --connection=sqlsrv
```

This will attempt to connect to the specified database and display connection information.

## Supported Database Extensions

- **SQL Server**: sqlsrv and pdo_sqlsrv extensions
- **Oracle**: oci8 and pdo_oci extensions
- **MySQL**: mysqli and pdo_mysql extensions

## OS Support

- **Windows**: Supports installation of pre-compiled DLLs
- **Linux**: Supports Debian-based (Ubuntu, Debian) and RHEL-based (CentOS, Fedora) distributions
- **macOS**: Supports installation via Homebrew and PECL

## PHP Version Compatibility

DBBridge is designed to work seamlessly across multiple PHP versions:

- **PHP 7.4**: Uses array-based Process constructor for compatibility
- **PHP 8.0+**: Uses the more convenient `fromShellCommandline` method
- **PHP 8.1+**: Takes advantage of newer PHP features when available
- **PHP 8.2+**: Fully compatible with newer PHP versions
- **PHP 8.3 and 8.4**: Tested and supported

The package automatically detects your PHP version and adjusts its behavior accordingly, ensuring compatibility across all supported versions.

## Configuration

The configuration file (`config/dbbridge.php`) contains settings for each supported database extension, including installation commands for different operating systems.

## Troubleshooting

### Common Issues

1. **Permission Denied**: When running installation scripts, you may need administrator/root privileges.
   
   Solution: Run the command with `sudo` on Linux/macOS or as Administrator on Windows.

2. **Missing Dependencies**: Some extensions require system libraries to be installed first.
   
   Solution: Follow the guided installation instructions which include all prerequisites.

3. **PHP Version Compatibility**: Ensure your PHP version is compatible with the extensions you're trying to install.
   
   Solution: Check the compatibility matrix in the documentation for each extension.

## Testing

DBBridge comes with a comprehensive test suite. To run the tests:

```bash
composer test
```

Or use the provided test scripts:

```bash
# On Windows
run-environment-test.bat

# On Linux/macOS
./run-environment-test.sh
```

The test suite includes:

- **Unit Tests**: Testing individual components like the EnvironmentDetector and ExtensionInstaller
- **Feature Tests**: Testing the commands and their interactions with the system
- **Integration Tests**: Testing the database connections with different drivers
- **Version Compatibility Tests**: Ensuring the package works across PHP versions

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information. 