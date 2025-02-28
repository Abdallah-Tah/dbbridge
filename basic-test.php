<?php

require_once __DIR__ . '/vendor/autoload.php';

use DBBridge\Services\EnvironmentDetector;
use Symfony\Component\Process\Process;

// Add the missing Process class import to EnvironmentDetector
class_alias('Symfony\Component\Process\Process', 'Process');

// Create an instance of the environment detector
$detector = new EnvironmentDetector();

// Test the environment detection
echo "PHP Version: " . $detector->getPhpVersion() . PHP_EOL;
echo "Operating System: " . $detector->getOperatingSystem() . PHP_EOL;

// Get OS info
try {
    $osInfo = $detector->getOsInfo();
    echo "OS Details: " . PHP_EOL;
    echo "  Name: " . $osInfo['name'] . PHP_EOL;
    if (isset($osInfo['details'])) {
        echo "  Details: " . $osInfo['details'] . PHP_EOL;
    }
    if (isset($osInfo['type'])) {
        echo "  Type: " . $osInfo['type'] . PHP_EOL;
    }
} catch (Exception $e) {
    echo "Error getting OS info: " . $e->getMessage() . PHP_EOL;
}

// Get PHP config
try {
    $phpConfig = $detector->getPhpConfig();
    echo "PHP Configuration: " . PHP_EOL;
    foreach ($phpConfig as $key => $value) {
        if (is_array($value)) {
            echo "  $key: " . json_encode($value) . PHP_EOL;
        } else {
            echo "  $key: $value" . PHP_EOL;
        }
    }
} catch (Exception $e) {
    echo "Error getting PHP config: " . $e->getMessage() . PHP_EOL;
}

// Get database extensions
try {
    $extensions = $detector->getDatabaseExtensions();
    echo "Database Extensions: " . PHP_EOL;
    foreach ($extensions as $extension => $installed) {
        echo "  $extension: " . ($installed ? "Installed" : "Not installed") . PHP_EOL;
    }
} catch (Exception $e) {
    echo "Error getting database extensions: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "Test completed successfully!" . PHP_EOL; 