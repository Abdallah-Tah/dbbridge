<?php

namespace DBBridge\Tests\Unit;

use DBBridge\Services\EnvironmentDetector;
use DBBridge\Services\ExtensionInstaller;
use DBBridge\Tests\TestCase;
use Mockery;

class ExtensionInstallerTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
    
    /** @test */
    public function it_can_get_manual_installation_instructions_for_windows()
    {
        // Mock the environment detector
        $detector = Mockery::mock(EnvironmentDetector::class);
        $detector->shouldReceive('getOsInfo')->andReturn([
            'name' => 'Windows',
            'details' => 'Windows 10',
            'type' => 'windows'
        ]);
        
        // Configure test data
        $this->app['config']->set('dbbridge.extensions.sqlsrv', [
            'name' => 'SQL Server',
            'php_extension' => 'sqlsrv',
            'pdo_extension' => 'pdo_sqlsrv',
            'windows' => [
                'commands' => [
                    'echo "Test command 1"',
                    'echo "Test command 2"'
                ]
            ]
        ]);
        
        // Create installer with mocked detector
        $installer = new ExtensionInstaller($detector);
        
        // Get manual installation instructions
        $result = $installer->install('sqlsrv', 'manual');
        
        // Assert result structure
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Manual installation', $result['message']);
        $this->assertArrayHasKey('instructions', $result);
        $this->assertIsArray($result['instructions']);
    }
    
    /** @test */
    public function it_can_get_guided_installation_commands_for_linux()
    {
        // Mock the environment detector
        $detector = Mockery::mock(EnvironmentDetector::class);
        $detector->shouldReceive('getOsInfo')->andReturn([
            'name' => 'Ubuntu',
            'details' => 'Ubuntu 20.04',
            'type' => 'linux',
            'distribution' => 'debian_based'
        ]);
        
        // Configure test data
        $this->app['config']->set('dbbridge.extensions.sqlsrv', [
            'name' => 'SQL Server',
            'php_extension' => 'sqlsrv',
            'pdo_extension' => 'pdo_sqlsrv',
            'linux' => [
                'debian_based' => [
                    'commands' => [
                        'echo "Debian command 1"',
                        'echo "Debian command 2"'
                    ]
                ]
            ]
        ]);
        
        // Create installer with mocked detector
        $installer = new ExtensionInstaller($detector);
        
        // Get guided installation commands
        $result = $installer->install('sqlsrv', 'guided');
        
        // Assert result structure
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Guided installation', $result['message']);
        $this->assertArrayHasKey('commands', $result);
        $this->assertIsArray($result['commands']);
        $this->assertCount(2, $result['commands']);
    }
    
    /** @test */
    public function it_returns_error_for_missing_extension_configuration()
    {
        // Mock the environment detector
        $detector = Mockery::mock(EnvironmentDetector::class);
        
        // Create installer with mocked detector
        $installer = new ExtensionInstaller($detector);
        
        // Try to install non-existent extension
        $result = $installer->install('nonexistent_extension');
        
        // Assert error result
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['message']);
    }
    
    /** @test */
    public function it_returns_error_for_unsupported_os()
    {
        // Mock the environment detector
        $detector = Mockery::mock(EnvironmentDetector::class);
        $detector->shouldReceive('getOsInfo')->andReturn([
            'name' => 'SomeOS',
            'details' => 'Unknown OS',
            'type' => 'unknown'
        ]);
        
        // Configure test data
        $this->app['config']->set('dbbridge.extensions.sqlsrv', [
            'name' => 'SQL Server',
            'php_extension' => 'sqlsrv',
            'pdo_extension' => 'pdo_sqlsrv',
            'windows' => [
                'commands' => [
                    'echo "Windows command"'
                ]
            ]
        ]);
        
        // Create installer with mocked detector
        $installer = new ExtensionInstaller($detector);
        
        // Try to install on unsupported OS
        $result = $installer->install('sqlsrv');
        
        // Assert error result
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No installation steps found', $result['message']);
    }
} 