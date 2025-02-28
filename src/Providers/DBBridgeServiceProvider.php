<?php

namespace DBBridge\Providers;

use DBBridge\Commands\CheckEnvironmentCommand;
use DBBridge\Commands\InstallExtensionsCommand;
use DBBridge\Commands\TestConnectionCommand;
use Illuminate\Support\ServiceProvider;

class DBBridgeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckEnvironmentCommand::class,
                InstallExtensionsCommand::class,
                TestConnectionCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../../config/dbbridge.php' => config_path('dbbridge.php'),
            ], 'config');

            $this->publishes([
                __DIR__ . '/../../resources/scripts' => resource_path('vendor/dbbridge/scripts'),
            ], 'scripts');
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/dbbridge.php', 'dbbridge'
        );
    }
}