<?php

namespace DBBridge\Tests\Unit;

use DBBridge\Services\EnvironmentDetector;
use DBBridge\Tests\TestCase;

class EnvironmentDetectorTest extends TestCase
{
    /** @test */
    public function it_can_detect_php_version()
    {
        $detector = new EnvironmentDetector();
        $version = $detector->getPhpVersion();
        
        $this->assertIsString($version);
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+/', $version);
    }
    
    /** @test */
    public function it_can_detect_operating_system()
    {
        $detector = new EnvironmentDetector();
        $os = $detector->getOperatingSystem();
        
        $this->assertIsString($os);
        $this->assertNotEmpty($os);
    }
    
    /** @test */
    public function it_can_detect_os_info()
    {
        $detector = new EnvironmentDetector();
        $osInfo = $detector->getOsInfo();
        
        $this->assertIsArray($osInfo);
        $this->assertArrayHasKey('name', $osInfo);
        $this->assertArrayHasKey('details', $osInfo);
        $this->assertArrayHasKey('type', $osInfo);
    }
    
    /** @test */
    public function it_can_detect_php_config()
    {
        $detector = new EnvironmentDetector();
        $phpConfig = $detector->getPhpConfig();
        
        $this->assertIsArray($phpConfig);
        $this->assertArrayHasKey('php_ini_path', $phpConfig);
        $this->assertArrayHasKey('extension_dir', $phpConfig);
    }
    
    /** @test */
    public function it_can_detect_database_extensions()
    {
        $detector = new EnvironmentDetector();
        $extensions = $detector->getDatabaseExtensions();
        
        $this->assertIsArray($extensions);
        $this->assertArrayHasKey('mysqli', $extensions);
        $this->assertArrayHasKey('pdo_mysql', $extensions);
        $this->assertArrayHasKey('sqlsrv', $extensions);
        $this->assertArrayHasKey('pdo_sqlsrv', $extensions);
        
        // Each extension should be a boolean indicating if it's installed
        foreach ($extensions as $extension => $installed) {
            $this->assertIsBool($installed);
        }
    }
} 