<?php

namespace DBBridge\Tests\Feature;

use DBBridge\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class CommandsTest extends TestCase
{
    public function testCheckEnvironmentCommand()
    {
        $this->artisan('dbbridge:check-environment')
            ->expectsOutput('Environment Check Results:')
            ->assertExitCode(0);
    }

    public function testInstallExtensionsCommand()
    {
        $this->artisan('dbbridge:install-extensions', ['--extension' => 'sqlsrv'])
            ->expectsOutput('Installing SQL Server extensions...')
            ->assertExitCode(0);
    }

    public function testTestConnectionCommand()
    {
        $this->artisan('dbbridge:test-connection', ['--driver' => 'sqlsrv'])
            ->expectsOutput('Testing connection to SQL Server...')
            ->assertExitCode(0);
    }
} 