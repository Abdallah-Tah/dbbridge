<?php

namespace DBBridge\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class InstallExtensionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dbbridge:install-extensions
                            {--extension= : Specific extension to install (sqlsrv, oci8, mysql)}
                            {--all : Install all available extensions}
                            {--method=guided : Installation method (guided, automatic, manual)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and configure database extensions';

    /**
     * The current operating system.
     *
     * @var string
     */
    protected $os;

    /**
     * The detailed OS information.
     *
     * @var string
     */
    protected $osDetails;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('DBBridge Extension Installer');
        $this->info('============================');
        $this->newLine();

        // Detect environment
        $this->detectEnvironment();

        // Get installation method
        $method = $this->option('method');
        if (!in_array($method, ['guided', 'automatic', 'manual'])) {
            $method = 'guided';
        }

        // Get extensions to install
        $extensions = $this->getExtensionsToInstall();

        if (empty($extensions)) {
            $this->error('No extensions selected for installation.');
            return 1;
        }

        // Install each selected extension
        foreach ($extensions as $extension) {
            $this->installExtension($extension, $method);
        }

        $this->newLine();
        $this->info('Installation process completed.');
        $this->info('Run php artisan dbbridge:check-environment to verify your installation.');

        return 0;
    }

    /**
     * Detect the current environment.
     *
     * @return void
     */
    protected function detectEnvironment()
    {
        // Detect OS
        $this->os = PHP_OS_FAMILY;
        $this->info("Detected Operating System: {$this->os}");

        // Get more detailed OS info
        if ($this->os === 'Windows') {
            $this->osDetails = php_uname('s') . ' ' . php_uname('r');
            $this->info("OS Details: {$this->osDetails}");
        } elseif ($this->os === 'Linux') {
            $this->osDetails = $this->getLinuxDistribution();
            $this->info("Linux Distribution: {$this->osDetails}");
        } elseif ($this->os === 'Darwin') {
            $this->osDetails = $this->getMacOSVersion();
            $this->info("macOS Version: {$this->osDetails}");
        }

        $this->newLine();
    }

    /**
     * Get the Linux distribution name.
     *
     * @return string
     */
    protected function getLinuxDistribution()
    {
        if (file_exists('/etc/os-release')) {
            $osRelease = file_get_contents('/etc/os-release');
            preg_match('/PRETTY_NAME="([^"]+)"/', $osRelease, $matches);

            if (isset($matches[1])) {
                return $matches[1];
            }
        }

        // Try lsb_release command
        $process = Process::fromShellCommandline('lsb_release -ds');
        $process->run();

        if ($process->isSuccessful()) {
            return trim($process->getOutput());
        }

        return 'Unknown Linux Distribution';
    }

    /**
     * Get the macOS version.
     *
     * @return string
     */
    protected function getMacOSVersion()
    {
        $process = Process::fromShellCommandline('sw_vers -productVersion');
        $process->run();

        if ($process->isSuccessful()) {
            $version = trim($process->getOutput());
            return "macOS $version";
        }

        return 'Unknown macOS Version';
    }

    /**
     * Get the extensions to install based on user input.
     *
     * @return array
     */
    protected function getExtensionsToInstall()
    {
        $availableExtensions = ['sqlsrv', 'oci8', 'mysql'];
        $selectedExtensions = [];

        // Check if a specific extension was requested
        $extension = $this->option('extension');
        if ($extension && in_array($extension, $availableExtensions)) {
            return [$extension];
        }

        // Check if all extensions were requested
        if ($this->option('all')) {
            return $availableExtensions;
        }

        // Interactive selection
        $this->info('Available database extensions:');
        foreach ($availableExtensions as $index => $ext) {
            $this->info(($index + 1) . ". " . strtoupper($ext));
        }

        $this->newLine();
        $selected = $this->ask('Which extensions would you like to install? (comma-separated numbers, e.g. 1,3)');

        if (empty($selected)) {
            return [];
        }

        $selectedIndices = explode(',', $selected);
        foreach ($selectedIndices as $index) {
            $index = (int) trim($index) - 1;
            if (isset($availableExtensions[$index])) {
                $selectedExtensions[] = $availableExtensions[$index];
            }
        }

        return $selectedExtensions;
    }

    /**
     * Install a specific extension.
     *
     * @param string $extension
     * @param string $method
     * @return void
     */
    protected function installExtension($extension, $method)
    {
        $this->newLine();
        $this->info("Installing $extension extension...");

        $config = config("dbbridge.extensions.$extension");

        if (!$config) {
            $this->error("Configuration for $extension not found.");
            return;
        }

        if ($method === 'manual') {
            $this->showManualInstructions($extension, $config);
            return;
        }

        // Get OS-specific installation steps
        $osKey = strtolower($this->os);
        if ($this->os === 'Darwin') {
            $osKey = 'macos';
        }

        if (!isset($config[$osKey])) {
            $this->error("No installation steps found for $extension on $this->os.");
            $this->info("Please check the documentation for manual installation instructions.");
            return;
        }

        if ($method === 'guided') {
            $this->guidedInstallation($extension, $config, $osKey);
        } else {
            $this->automaticInstallation($extension, $config, $osKey);
        }
    }

    /**
     * Show manual installation instructions.
     *
     * @param string $extension
     * @param array $config
     * @return void
     */
    protected function showManualInstructions($extension, $config)
    {
        $this->info("Manual installation instructions for $extension:");

        $osKey = strtolower($this->os);
        if ($this->os === 'Darwin') {
            $osKey = 'macos';
        }

        if (isset($config[$osKey]['installation_steps'])) {
            foreach ($config[$osKey]['installation_steps'] as $index => $step) {
                $this->info(($index + 1) . ". $step");
            }
        } else {
            $this->info("No specific instructions available for your OS. Please refer to the official documentation:");

            if ($extension === 'sqlsrv') {
                $this->info("SQL Server: https://docs.microsoft.com/en-us/sql/connect/php/microsoft-php-driver-for-sql-server");
            } elseif ($extension === 'oci8') {
                $this->info("Oracle: https://www.php.net/manual/en/oci8.installation.php");
            } elseif ($extension === 'mysql') {
                $this->info("MySQL: https://www.php.net/manual/en/mysqli.installation.php");
            }
        }
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
     * Check if the system is Debian-based.
     *
     * @return bool
     */
    protected function isDebianBased()
    {
        return (
            strpos($this->osDetails, 'Debian') !== false ||
            strpos($this->osDetails, 'Ubuntu') !== false ||
            strpos($this->osDetails, 'Mint') !== false ||
            file_exists('/etc/debian_version')
        );
    }

    /**
     * Check if the system is RHEL-based.
     *
     * @return bool
     */
    protected function isRHELBased()
    {
        return (
            strpos($this->osDetails, 'Red Hat') !== false ||
            strpos($this->osDetails, 'CentOS') !== false ||
            strpos($this->osDetails, 'Fedora') !== false ||
            file_exists('/etc/redhat-release')
        );
    }
}
