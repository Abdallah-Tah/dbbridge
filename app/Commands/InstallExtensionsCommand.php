<?php

namespace App\Commands;

use DBBridge\Commands\InstallExtensionsCommand as BaseInstallExtensionsCommand;
use Symfony\Component\Process\Process;

class InstallExtensionsCommand extends BaseInstallExtensionsCommand
{
    /**
     * Execute a shell command.
     *
     * @param string $command
     * @return array
     */
    protected function executeCommand($command)
    {
        if (function_exists('posix_geteuid') && posix_geteuid() !== 0) {
            return [
                'success' => false,
                'message' => 'This command requires root privileges. Please run with sudo.',
            ];
        }

        $process = Process::fromShellCommandline($command);
        $process->run();

        return [
            'success' => $process->isSuccessful(),
            'exit_code' => $process->getExitCode(),
            'output' => $process->getOutput(),
            'error' => $process->getErrorOutput(),
            'message' => $process->getErrorOutput() ?: 'Command execution failed.',
        ];
    }

    /**
     * Perform guided installation.
     *
     * @param string $extension
     * @param array $config
     * @param string $osKey
     * @return void
     */
    protected function guidedInstallation($extension, $config, $osKey)
    {
        $this->info("Guided installation for $extension on $this->os:");

        if ($osKey === 'linux') {
            // Determine Linux distribution type
            $isDebianBased = $this->isDebianBased();
            $isRHELBased = $this->isRHELBased();

            if ($isDebianBased && isset($config[$osKey]['debian_based'])) {
                $this->info("Using Debian-based installation steps.");
                $commands = $config[$osKey]['debian_based']['commands'];
            } elseif ($isRHELBased && isset($config[$osKey]['rhel_based'])) {
                $this->info("Using RHEL-based installation steps.");
                $commands = $config[$osKey]['rhel_based']['commands'];
            } else {
                $this->error("No specific installation steps for your Linux distribution.");
                $this->showManualInstructions($extension, $config);
                return;
            }
        } elseif (isset($config[$osKey]['commands'])) {
            $commands = $config[$osKey]['commands'];
        } else {
            $this->error("No automated installation commands available for your OS.");
            $this->showManualInstructions($extension, $config);
            return;
        }

        foreach ($commands as $index => $command) {
            $this->info(($index + 1) . ". Command: $command");

            if ($this->confirm("Execute this command?", false)) {
                $result = $this->executeCommand($command);
                if (!$result['success']) {
                    $this->error($result['message'] ?? $result['error'] ?? 'Command execution failed.');
                }
            } else {
                $this->warn("Command skipped.");
            }
        }
    }

    /**
     * Perform automatic installation.
     *
     * @param string $extension
     * @param array $config
     * @param string $osKey
     * @return void
     */
    protected function automaticInstallation($extension, $config, $osKey)
    {
        $this->info("Automatic installation for $extension on $this->os:");

        if ($osKey === 'linux') {
            // Determine Linux distribution type
            $isDebianBased = $this->isDebianBased();
            $isRHELBased = $this->isRHELBased();

            if ($isDebianBased && isset($config[$osKey]['debian_based'])) {
                $this->info("Using Debian-based installation steps.");
                $commands = $config[$osKey]['debian_based']['commands'];
            } elseif ($isRHELBased && isset($config[$osKey]['rhel_based'])) {
                $this->info("Using RHEL-based installation steps.");
                $commands = $config[$osKey]['rhel_based']['commands'];
            } else {
                $this->error("No specific installation steps for your Linux distribution.");
                return;
            }
        } elseif (isset($config[$osKey]['commands'])) {
            $commands = $config[$osKey]['commands'];
        } else {
            $this->error("No automated installation commands available for your OS.");
            return;
        }

        foreach ($commands as $command) {
            $this->info("Executing: $command");
            $result = $this->executeCommand($command);
            if (!$result['success']) {
                $this->error($result['message'] ?? $result['error'] ?? 'Command execution failed.');
            }
        }
    }
}
