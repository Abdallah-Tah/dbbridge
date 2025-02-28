<?php

// Simple test to verify PHP environment
echo "PHP Version: " . PHP_VERSION . PHP_EOL;
echo "Operating System: " . PHP_OS_FAMILY . PHP_EOL;
echo "OS Details: " . php_uname() . PHP_EOL;

// Check loaded extensions
echo "Loaded Extensions: " . PHP_EOL;
$extensions = get_loaded_extensions();
sort($extensions);
foreach ($extensions as $extension) {
    echo "  - $extension" . PHP_EOL;
}

// Check database-related extensions
$dbExtensions = [
    'mysqli', 'pdo_mysql', 'sqlsrv', 'pdo_sqlsrv', 'oci8', 'pdo_oci', 'pgsql', 'pdo_pgsql', 'sqlite3', 'pdo_sqlite'
];

echo "Database Extensions: " . PHP_EOL;
foreach ($dbExtensions as $extension) {
    if (extension_loaded($extension)) {
        echo "  ✅ $extension: Installed" . PHP_EOL;
    } else {
        echo "  ❌ $extension: Not installed" . PHP_EOL;
    }
}

// Check PHP configuration
echo "PHP Configuration: " . PHP_EOL;
echo "  php.ini location: " . php_ini_loaded_file() . PHP_EOL;
echo "  extension_dir: " . ini_get('extension_dir') . PHP_EOL;

echo PHP_EOL . "Test completed successfully!" . PHP_EOL; 