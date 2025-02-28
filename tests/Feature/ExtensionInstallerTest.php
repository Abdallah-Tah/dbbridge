<?php

namespace DBBridge\Tests\Feature;

use DBBridge\Services\EnvironmentDetector;
use DBBridge\Services\ExtensionInstaller;
use DBBridge\Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Mockery;

class ExtensionInstallerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock configuration
        Config::set('dbbridge.extensions.sqlsrv', [
            'name' => 'SQL Server',
            'php_extension' => 'sqlsrv',
            'pdo_extension' => 'pdo_sqlsrv',
            'windows' => [
                'commands' => [
                    'echo "Test command 1"',
                    'echo "Test command 2"'
                ]
            ],
            'linux' => [
                'debian_based' => [
                    'commands' => [
                        'echo "Debian command 1"',
                        'echo "Debian command 2"'
                    ]
                ],
                'rhel_based' => [
                    'commands' => [
                        'echo "RHEL command 1"',
                        'echo "RHEL command 2"'
                    ]
                ]
            ],
            'macos' => [
                'commands' => [
                    'echo "macOS command 1"',
                    'echo "macOS command 2"'
                ]
            ]
        ]);
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    public function testCanGetManualInstallationInstructions()
    {
        $detector = $this->createMock(EnvironmentDetector::class);
        $detector->method('getOsInfo')->willReturn([
            'name' => 'Windows',
            'details' => 'Windows 10',
            'type' => 'windows'
        ]);
        
        $installer = new ExtensionInstaller($detector);
        $result = $installer->install('sqlsrv', 'manual');
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Manual installation', $result['message']);
        $this->assertArrayHasKey('instructions', $result);
    }

    public function testCanPrepareGuidedInstallationCommands()
    {
        $detector = $this->createMock(EnvironmentDetector::class);
        $detector->method('getOsInfo')->willReturn([
            'name' => 'Windows',
            'details' => 'Windows 10',
            'type' => 'windows'
        ]);
        
        $installer = new ExtensionInstaller($detector);
        $result = $installer->install('sqlsrv', 'guided');
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Guided installation', $result['message']);
        $this->assertArrayHasKey('commands', $result);
        $this->assertIsArray($result['commands']);
        $this->assertCount(2, $result['commands']);
    }

    public function testReturnsErrorForMissingExtensionConfiguration()
    {
        $detector = $this->createMock(EnvironmentDetector::class);
        $installer = new ExtensionInstaller($detector);
        $result = $installer->install('nonexistent_extension');
        
        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Configuration for nonexistent_extension not found', $result['message']);
    }

    public function testReturnsErrorForUnsupportedOS()
    {
        $detector = $this->createMock(EnvironmentDetector::class);
        $detector->method('getOsInfo')->willReturn([
            'name' => 'SomeOS',
            'details' => 'Unknown OS',
            'type' => 'unknown'
        ]);
        
        $installer = new ExtensionInstaller($detector);
        $result = $installer->install('sqlsrv');
        
        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No installation steps found', $result['message']);
    }
} 