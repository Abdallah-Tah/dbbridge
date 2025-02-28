<?php

namespace DBBridge\Tests\Feature;

use DBBridge\Commands\TestConnectionCommand;
use DBBridge\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Mockery;
use PDO;
use Exception;

class DatabaseConnectionTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    public function testCanTestSuccessfulDatabaseConnection()
    {
        // Mock DB facade to return successful connection
        DB::shouldReceive('connection')
            ->once()
            ->with('testdb')
            ->andReturnSelf();
        
        DB::shouldReceive('getPdo')
            ->once()
            ->andReturn(new PDO('sqlite::memory:'));
        
        DB::shouldReceive('getDriverName')
            ->once()
            ->andReturn('sqlite');
        
        // Configure test database connection
        Config::set('database.connections.testdb', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        
        // Create and execute command
        $command = $this->app->make(TestConnectionCommand::class);
        $this->artisan('dbbridge:test-connection', ['connection' => 'testdb'])
            ->expectsOutput('Connection successful')
            ->expectsOutput('Driver: sqlite')
            ->assertExitCode(0);
    }

    public function testHandlesFailedDatabaseConnectionsGracefully()
    {
        // Mock DB facade to throw exception
        DB::shouldReceive('connection')
            ->once()
            ->with('failed_db')
            ->andThrow(new Exception('Connection failed'));
        
        // Execute command
        $this->artisan('dbbridge:test-connection', ['connection' => 'failed_db'])
            ->expectsOutput('Connection failed')
            ->assertExitCode(1);
    }

    public function testDisplaysConnectionDetailsForSuccessfulConnections()
    {
        // Create a mock PDO object with server info
        $mockPdo = $this->createMock(PDO::class);
        $mockPdo->method('getAttribute')
            ->with(PDO::ATTR_SERVER_VERSION)
            ->willReturn('Test Server 1.0');
        
        // Mock DB facade
        DB::shouldReceive('connection')
            ->once()
            ->with('testdb')
            ->andReturnSelf();
        
        DB::shouldReceive('getPdo')
            ->once()
            ->andReturn($mockPdo);
        
        DB::shouldReceive('getDriverName')
            ->once()
            ->andReturn('test_driver');
        
        // Configure test database connection
        Config::set('database.connections.testdb', [
            'driver' => 'test_driver',
            'host' => 'test_host',
            'database' => 'test_db',
        ]);
        
        // Execute command
        $this->artisan('dbbridge:test-connection', ['connection' => 'testdb'])
            ->expectsOutput('Connection successful')
            ->expectsOutput('Driver: test_driver')
            ->expectsOutput('Server version: Test Server 1.0')
            ->assertExitCode(0);
    }
} 