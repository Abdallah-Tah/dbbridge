<?php

require_once __DIR__ . '/vendor/autoload.php';

use DBBridge\Services\EnvironmentDetector;

// Create an instance of the environment detector
$detector = new EnvironmentDetector();

// Test the environment detection
echo "PHP Version: " . $detector->getPhpVersion() . PHP_EOL;
echo "Operating System: " . $detector->getOperatingSystem() . PHP_EOL;

// Get OS info
$osInfo = $detector->getOsInfo();
echo "OS Details: " . PHP_EOL;
echo "  Name: " . $osInfo['name'] . PHP_EOL;
echo "  Details: " . $osInfo['details'] . PHP_EOL;
echo "  Type: " . $osInfo['type'] . PHP_EOL;

// Get PHP config
$phpConfig = $detector->getPhpConfig();
echo "PHP Configuration: " . PHP_EOL;
echo "  Version: " . $phpConfig['version'] . PHP_EOL;
echo "  INI Path: " . $phpConfig['ini_path'] . PHP_EOL;
echo "  Extension Dir: " . $phpConfig['extension_dir'] . PHP_EOL;

// Get database extensions
$extensions = $detector->getDatabaseExtensions();
echo "Database Extensions: " . PHP_EOL;
echo "  Installed: " . PHP_EOL;
if (!empty($extensions['installed'])) {
    foreach ($extensions['installed'] as $db => $exts) {
        echo "    " . ucfirst($db) . ": " . implode(', ', $exts) . PHP_EOL;
    }
} else {
    echo "    None" . PHP_EOL;
}

echo "  Missing: " . PHP_EOL;
if (!empty($extensions['missing'])) {
    foreach ($extensions['missing'] as $db => $exts) {
        echo "    " . ucfirst($db) . ": " . implode(', ', $exts) . PHP_EOL;
    }
} else {
    echo "    None" . PHP_EOL;
}

echo PHP_EOL . "Test completed successfully!" . PHP_EOL; 