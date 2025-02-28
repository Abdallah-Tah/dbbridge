<?php

namespace Tests\Feature;

use Tests\TestCase;
use DBBridge\Services\EnvironmentDetector;
use DBBridge\Services\ExtensionInstaller;

class VersionCompatibilityTest extends TestCase
{
    public function testEnvironmentDetectorWorksWithDifferentPhpVersions()
    {
        $detector = new EnvironmentDetector();
        
        // These methods should work regardless of PHP version
        $this->assertIsString($detector->getPhpVersion());
        $this->assertIsString($detector->getOperatingSystem());
        
        $osInfo = $detector->getOsInfo();
        $this->assertIsArray($osInfo);
        $this->assertArrayHasKey('name', $osInfo);
        $this->assertArrayHasKey('details', $osInfo);
        $this->assertArrayHasKey('type', $osInfo);
        
        $phpConfig = $detector->getPhpConfig();
        $this->assertIsArray($phpConfig);
        $this->assertArrayHasKey('version', $phpConfig);
        $this->assertArrayHasKey('extensions', $phpConfig);
        
        $dbExtensions = $detector->getDatabaseExtensions();
        $this->assertIsArray($dbExtensions);
        $this->assertArrayHasKey('installed', $dbExtensions);
        $this->assertArrayHasKey('missing', $dbExtensions);
    }

    public function testExtensionInstallerWorksWithDifferentPhpVersions()
    {
        $detector = new EnvironmentDetector();
        $installer = new ExtensionInstaller($detector);
        
        // Test a simple command that should work on all platforms
        $result = $installer->executeCommand('echo "Hello World"');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('exit_code', $result);
        $this->assertArrayHasKey('output', $result);
        $this->assertArrayHasKey('error', $result);
        
        // The command should succeed
        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['exit_code']);
        $this->assertStringContainsString('Hello World', $result['output']);
    }
} 