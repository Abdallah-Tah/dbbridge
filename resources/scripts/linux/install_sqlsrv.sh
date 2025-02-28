#!/bin/bash

echo "DBBridge SQL Server Extension Installer for Linux"
echo "================================================"
echo

# Check if running as root
if [ "$EUID" -ne 0 ]; then
  echo "This script requires root privileges. Please run with sudo."
  exit 1
fi

# Get PHP information
PHP_VERSION=$(php -r "echo PHP_VERSION;")
PHP_MAJOR_MINOR=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
PHP_INI=$(php -r "echo php_ini_loaded_file();")
EXT_DIR=$(php -r "echo ini_get('extension_dir');")

echo "PHP Version: $PHP_VERSION"
echo "PHP INI: $PHP_INI"
echo "Extension Directory: $EXT_DIR"
echo

# Detect distribution
if [ -f /etc/os-release ]; then
    . /etc/os-release
    DISTRO=$ID
    DISTRO_VERSION=$VERSION_ID
    echo "Detected distribution: $PRETTY_NAME"
elif [ -f /etc/lsb-release ]; then
    . /etc/lsb-release
    DISTRO=$DISTRIB_ID
    DISTRO_VERSION=$DISTRIB_RELEASE
    echo "Detected distribution: $DISTRIB_DESCRIPTION"
else
    echo "Could not detect Linux distribution."
    exit 1
fi

# Install based on distribution
if [[ "$DISTRO" == "ubuntu" || "$DISTRO" == "debian" ]]; then
    echo "Installing for Debian-based distribution..."
    
    # Add Microsoft repository
    curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add -
    curl https://packages.microsoft.com/config/ubuntu/$DISTRO_VERSION/prod.list > /etc/apt/sources.list.d/mssql-release.list
    
    # Update and install dependencies
    apt-get update
    ACCEPT_EULA=Y apt-get install -y msodbcsql17 unixodbc-dev
    
    # Install PHP extensions
    if ! command -v pecl &> /dev/null; then
        apt-get install -y php-pear php$PHP_MAJOR_MINOR-dev
    fi
    
    pecl install sqlsrv pdo_sqlsrv
    
    # Configure PHP
    echo "extension=sqlsrv.so" > /etc/php/$PHP_MAJOR_MINOR/mods-available/sqlsrv.ini
    echo "extension=pdo_sqlsrv.so" > /etc/php/$PHP_MAJOR_MINOR/mods-available/pdo_sqlsrv.ini
    
    # Enable extensions
    phpenmod -v $PHP_MAJOR_MINOR sqlsrv pdo_sqlsrv
    
elif [[ "$DISTRO" == "centos" || "$DISTRO" == "rhel" || "$DISTRO" == "fedora" ]]; then
    echo "Installing for RHEL-based distribution..."
    
    # Add Microsoft repository
    curl https://packages.microsoft.com/config/rhel/8/prod.repo > /etc/yum.repos.d/mssql-release.repo
    
    # Install dependencies
    ACCEPT_EULA=Y yum install -y msodbcsql17 unixODBC-devel
    yum install -y php-pear php-devel
    
    # Install PHP extensions
    pecl install sqlsrv pdo_sqlsrv
    
    # Configure PHP
    echo "extension=sqlsrv.so" > /etc/php.d/30-sqlsrv.ini
    echo "extension=pdo_sqlsrv.so" > /etc/php.d/35-pdo_sqlsrv.ini
    
else
    echo "Unsupported distribution: $DISTRO"
    echo "Please install SQL Server extensions manually."
    exit 1
fi

echo
echo "Installation completed successfully!"
echo "Please restart your web server for the changes to take effect." 