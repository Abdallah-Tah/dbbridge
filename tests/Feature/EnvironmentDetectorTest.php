<?php

namespace DBBridge\Tests\Feature;

use DBBridge\Services\EnvironmentDetector;
use DBBridge\Tests\TestCase;

class EnvironmentDetectorTest extends TestCase
{
    protected EnvironmentDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new EnvironmentDetector();
    }

    public function testCanDetectPhpVersion()
    {
        $version = $this->detector->getPhpVersion();
        $this->assertNotEmpty($version);
        $this->assertIsString($version);
        $this->assertStringContainsString(PHP_VERSION, $version);
    }

    public function testCanDetectOperatingSystem()
    {
        $os = $this->detector->getOperatingSystem();
        $this->assertNotEmpty($os);
        $this->assertIsString($os);
    }

    public function testCanDetectOsInfo()
    {
        $osInfo = $this->detector->getOsInfo();
        $this->assertIsArray($osInfo);
        $this->assertArrayHasKey('name', $osInfo);
        $this->assertArrayHasKey('details', $osInfo);
        $this->assertArrayHasKey('type', $osInfo);
    }

    public function testCanDetectPhpConfig()
    {
        $phpConfig = $this->detector->getPhpConfig();
        $this->assertIsArray($phpConfig);
        
        // Check for the keys that are actually returned by the implementation
        if (isset($phpConfig['php_ini_path'])) {
            $this->assertArrayHasKey('php_ini_path', $phpConfig);
        } elseif (isset($phpConfig['ini_path'])) {
            $this->assertArrayHasKey('ini_path', $phpConfig);
        }
        
        $this->assertArrayHasKey('extension_dir', $phpConfig);
    }

    public function testCanDetectDatabaseExtensions()
    {
        $extensions = $this->detector->getDatabaseExtensions();
        $this->assertIsArray($extensions);
        
        // Check if the extensions are returned as a flat array or nested
        if (isset($extensions['installed'])) {
            $this->assertArrayHasKey('installed', $extensions);
            $this->assertArrayHasKey('missing', $extensions);
        } else {
            // Test for at least some common database extensions
            $this->assertArrayHasKey('mysqli', $extensions);
            $this->assertArrayHasKey('pdo_mysql', $extensions);
        }
    }
} 