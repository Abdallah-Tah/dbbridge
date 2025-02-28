<?php

namespace DBBridge\Services;

use DBBridge\Services\EnvironmentDetector;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class ExtensionInstaller
{
    /**
     * The environment detector instance.
     *
     * @var \DBBridge\Services\EnvironmentDetector
     */
    protected $detector;

    /**
     * Create a new installer instance.
     *
     * @param \DBBridge\Services\EnvironmentDetector $detector
     * @return void
     */
    public function __construct(EnvironmentDetector $detector)
    {
        $this->detector = $detector;
    }

    /**
     * Install a specific extension.
     *
     * @param string $extension
     * @param string $method
     * @return array
     */
    public function install($extension, $method = 'guided')
    {
        $config = config("dbbridge.extensions.$extension");
        
        if (!$config) {
            return [
                'success' => false,
                'message' => "Configuration for $extension not found."
            ];
        }

        // Get OS information
        $osInfo = $this->detector->getOsInfo();
        $osKey = strtolower($osInfo['name']);
        
        if ($osInfo['name'] === 'Darwin') {
            $osKey = 'macos';
        }

        if (!isset($config[$osKey])) {
            return [
                'success' => false,
                'message' => "No installation steps found for $extension on {$osInfo['name']}."
            ];
        }

        if ($method === 'manual') {
            return [
                'success' => true,
                'message' => "Manual installation instructions for $extension on {$osInfo['name']}.",
                'instructions' => $this->getManualInstructions($extension, $config, $osKey)
            ];
        }

        // Get installation commands
        $commands = $this->getInstallationCommands($extension, $config, $osKey, $osInfo);
        
        if (empty($commands)) {
            return [
                'success' => false,
                'message' => "No installation commands available for your OS."
            ];
        }

        // Execute commands based on method
        $results = [];
        
        if ($method === 'guided') {
            foreach ($commands as $command) {
                $results[] = [
                    'command' => $command,
                    'requires_confirmation' => true
                ];
            }
            
            return [
                'success' => true,
                'message' => "Guided installation prepared for $extension on {$osInfo['name']}.",
                'commands' => $results
            ];
        } else {
            // Automatic installation
            $commandResults = [];
            
            foreach ($commands as $command) {
                $result = $this->executeCommand($command);
                $commandResults[] = [
                    'command' => $command,
                    'output' => $result['output'],
                    'success' => $result['success'],
                    'exit_code' => $result['exit_code']
                ];
                
                if (!$result['success']) {
                    return [
                        'success' => false,
                        'message' => "Installation failed during command execution.",
                        'results' => $commandResults
                    ];
                }
            }
            
            return [
                'success' => true,
                'message' => "Automatic installation completed for $extension on {$osInfo['name']}.",
                'results' => $commandResults
            ];
        }
    }

    /**
     * Get manual installation instructions.
     *
     * @param string $extension
     * @param array $config
     * @param string $osKey
     * @return array
     */
    protected function getManualInstructions($extension, $config, $osKey)
    {
        if (isset($config[$osKey]['installation_steps'])) {
            return $config[$osKey]['installation_steps'];
        }
        
        // Default instructions if not specified
        $instructions = [];
        
        if ($extension === 'sqlsrv') {
            $instructions[] = "Visit https://docs.microsoft.com/en-us/sql/connect/php/microsoft-php-driver-for-sql-server";
        } elseif ($extension === 'oci8') {
            $instructions[] = "Visit https://www.php.net/manual/en/oci8.installation.php";
        } elseif ($extension === 'mysql') {
            $instructions[] = "Visit https://www.php.net/manual/en/mysqli.installation.php";
        }
        
        return $instructions;
    }

    /**
     * Get installation commands for the current OS.
     *
     * @param string $extension
     * @param array $config
     * @param string $osKey
     * @param array $osInfo
     * @return array
     */
    protected function getInstallationCommands($extension, $config, $osKey, $osInfo)
    {
        if ($osKey === 'linux') {
            // Determine Linux distribution type
            if ($osInfo['type'] === 'debian' && isset($config[$osKey]['debian_based'])) {
                return $config[$osKey]['debian_based']['commands'];
            } elseif ($osInfo['type'] === 'rhel' && isset($config[$osKey]['rhel_based'])) {
                return $config[$osKey]['rhel_based']['commands'];
            }
            
            return [];
        } elseif (isset($config[$osKey]['commands'])) {
            return $config[$osKey]['commands'];
        }
        
        return [];
    }

    /**
     * Execute a shell command.
     *
     * @param string $command
     * @return array
     */
    public function executeCommand($command)
    {
        // Compatible with PHP 7.4+
        if (version_compare(PHP_VERSION, '8.0', '<')) {
            // For PHP 7.4, we need to use the array constructor
            // This is a simplified approach - in a real implementation,
            // you might need more sophisticated command parsing
            if (PHP_OS_FAMILY === 'Windows') {
                $process = new Process(['cmd', '/c', $command]);
            } else {
                $process = new Process(['sh', '-c', $command]);
            }
        } else {
            $process = Process::fromShellCommandline($command);
        }
        
        $process->setTimeout(null);
        $process->run();
        
        return [
            'success' => $process->isSuccessful(),
            'exit_code' => $process->getExitCode(),
            'output' => $process->getOutput(),
            'error' => $process->getErrorOutput()
        ];
    }

    /**
     * Get the path to the installation script.
     *
     * @param string $extension
     * @return string|null
     */
    public function getInstallationScriptPath($extension)
    {
        $osInfo = $this->detector->getOsInfo();
        $osType = $osInfo['type'];
        
        $scriptPath = resource_path("vendor/dbbridge/scripts/$osType/install_{$extension}");
        
        if ($osType === 'windows') {
            $scriptPath .= '.bat';
        } else {
            $scriptPath .= '.sh';
        }
        
        if (File::exists($scriptPath)) {
            return $scriptPath;
        }
        
        // Check in package resources
        $packageScriptPath = __DIR__ . "/../../resources/scripts/$osType/install_{$extension}";
        
        if ($osType === 'windows') {
            $packageScriptPath .= '.bat';
        } else {
            $packageScriptPath .= '.sh';
        }
        
        if (File::exists($packageScriptPath)) {
            return $packageScriptPath;
        }
        
        return null;
    }

    /**
     * Run the installation script.
     *
     * @param string $extension
     * @return array
     */
    public function runInstallationScript($extension)
    {
        $scriptPath = $this->getInstallationScriptPath($extension);
        
        if (!$scriptPath) {
            return [
                'success' => false,
                'message' => "Installation script for $extension not found."
            ];
        }
        
        $osInfo = $this->detector->getOsInfo();
        
        if ($osInfo['type'] === 'windows') {
            $command = $scriptPath;
        } else {
            // Make script executable
            chmod($scriptPath, 0755);
            $command = "sudo $scriptPath";
        }
        
        return $this->executeCommand($command);
    }
} 