<?php

namespace DBBridge\Tests\Feature;

use DBBridge\Services\EnvironmentDetector;
use Orchestra\Testbench\TestCase;

class EnvironmentDetectionTest extends TestCase
{
    /**
     * Test that the environment detector can detect the PHP version.
     *
     * @return void
     */
    public function testCanDetectPhpVersion()
    {
        $detector = new EnvironmentDetector();
        $version = $detector->getPhpVersion();
        
        $this->assertSame(PHP_VERSION, $version);
    }

    /**
     * Test that the environment detector can detect the operating system.
     *
     * @return void
     */
    public function testCanDetectOperatingSystem()
    {
        $detector = new EnvironmentDetector();
        $os = $detector->getOperatingSystem();
        
        $this->assertSame(PHP_OS_FAMILY, $os);
    }

    /**
     * Test that the environment detector can detect OS details.
     *
     * @return void
     */
    public function testCanDetectOsDetails()
    {
        $detector = new EnvironmentDetector();
        $osInfo = $detector->getOsInfo();
        
        $this->assertIsArray($osInfo);
        $this->assertArrayHasKey('name', $osInfo);
        $this->assertArrayHasKey('details', $osInfo);
        $this->assertArrayHasKey('type', $osInfo);
    }

    /**
     * Test that the environment detector can detect PHP configuration.
     *
     * @return void
     */
    public function testCanDetectPhpConfig()
    {
        $detector = new EnvironmentDetector();
        $config = $detector->getPhpConfig();
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('version', $config);
        $this->assertArrayHasKey('ini_path', $config);
        $this->assertArrayHasKey('extension_dir', $config);
        $this->assertArrayHasKey('extensions', $config);
    }

    /**
     * Test that the environment detector can detect database extensions.
     *
     * @return void
     */
    public function testCanDetectDatabaseExtensions()
    {
        $detector = new EnvironmentDetector();
        $extensions = $detector->getDatabaseExtensions();
        
        $this->assertIsArray($extensions);
        $this->assertArrayHasKey('installed', $extensions);
        $this->assertArrayHasKey('missing', $extensions);
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return ['DBBridge\Providers\DBBridgeServiceProvider'];
    }
} 