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

        $success = false;
        if ($method === 'guided') {
            $success = $this->guidedInstallation($extension, $config, $osKey);
        } else {
            $success = $this->automaticInstallation($extension, $config, $osKey);
        }

        // Verify installation
        $this->newLine();
        $isInstalled = $this->verifyInstallation($extension);

        if (!$isInstalled) {
            $this->warn("The $extension extension installation may not be complete.");
            if ($this->confirm("Would you like to attempt cleanup?", false)) {
                $this->cleanupOnFailure($extension);
            }
        }

        // Ensure environment variables persist across sessions
        $this->ensureEnvironmentPersistence($extension);
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
     * @return bool
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
                return false;
            }
        } elseif (isset($config[$osKey]['commands'])) {
            $commands = $config[$osKey]['commands'];
        } else {
            $this->error("No automated installation commands available for your OS.");
            $this->showManualInstructions($extension, $config);
            return false;
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

        return true;
    }

    /**
     * Perform automatic installation.
     *
     * @param string $extension
     * @param array $config
     * @param string $osKey
     * @return bool
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
                return false;
            }
        } elseif (isset($config[$osKey]['commands'])) {
            $commands = $config[$osKey]['commands'];
        } else {
            $this->error("No automated installation commands available for your OS.");
            return false;
        }

        foreach ($commands as $command) {
            $this->info("Executing: $command");
            $result = $this->executeCommand($command);
            if (!$result['success']) {
                $this->error($result['message'] ?? $result['error'] ?? 'Command execution failed.');
            }
        }

        return true;
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

        // Make commands non-interactive where possible
        $command = $this->makeCommandNonInteractive($command);

        $process = Process::fromShellCommandline($command);
        $process->setTimeout(300); // Add timeout for long-running commands
        $process->run(function ($type, $buffer) {
            // Output real-time feedback
            if (Process::ERR === $type) {
                $this->error(trim($buffer));
            } else {
                $this->info(trim($buffer));
            }
        });

        $result = [
            'success' => $process->isSuccessful(),
            'exit_code' => $process->getExitCode(),
            'output' => $process->getOutput(),
            'error' => $process->getErrorOutput(),
            'message' => $process->getErrorOutput() ?: 'Command execution failed.',
        ];

        // Add critical command validation
        if (!$result['success'] && $this->isCriticalCommand($command)) {
            $this->error("Critical command failed. Installation may be incomplete.");
            $this->offerTroubleshooting($command, $result['error']);
        }

        return $result;
    }

    /**
     * Make a command non-interactive.
     *
     * @param string $command
     * @return string
     */
    protected function makeCommandNonInteractive($command)
    {
        // Handle specific commands that need non-interactive options
        if (strpos($command, 'unzip') !== false && strpos($command, '-o') === false) {
            return str_replace('unzip', 'unzip -o', $command); // -o for overwrite without prompting
        }

        if (strpos($command, 'pecl install') !== false) {
            // For pecl, we need to handle the Oracle home directory prompt
            return "echo '\n' | " . $command; // Send newline to accept default
        }

        return $command;
    }

    /**
     * Check if a command is critical for the installation.
     *
     * @param string $command
     * @return bool
     */
    protected function isCriticalCommand($command)
    {
        $criticalPatterns = [
            'apt-get install',
            'pecl install',
            'mkdir -p /opt/oracle',
            'ldconfig'
        ];

        foreach ($criticalPatterns as $pattern) {
            if (strpos($command, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Offer troubleshooting tips based on the command and error.
     *
     * @param string $command
     * @param string $error
     * @return void
     */
    protected function offerTroubleshooting($command, $error)
    {
        // Offer specific troubleshooting based on command and error
        if (strpos($command, 'pecl install oci8') !== false) {
            $this->info("Troubleshooting pecl install:");
            $this->info("1. Make sure PECL is installed: sudo apt-get install php-pear php-dev");
            $this->info("2. Check Oracle Instant Client path: ls -la /opt/oracle/");
            $this->info("3. Verify LD_LIBRARY_PATH: echo \$LD_LIBRARY_PATH");
        } elseif (strpos($command, 'unzip') !== false) {
            $this->info("Troubleshooting unzip:");
            $this->info("1. Check if the zip file exists: ls -la /opt/oracle/");
            $this->info("2. Install unzip if missing: sudo apt-get install unzip");
        } elseif (strpos($command, 'wget') !== false) {
            $this->info("Troubleshooting download:");
            $this->info("1. Check internet connectivity");
            $this->info("2. Verify the URL is accessible");
            $this->info("3. Try downloading manually and uploading to the server");
        }
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

    protected function cleanupOnFailure($extension)
    {
        if ($this->confirm("Would you like to clean up failed installation files?", true)) {
            $this->info("Cleaning up...");

            if ($extension === 'oci8') {
                // Remove Oracle Instant Client files if they exist
                $this->executeCommand("rm -f /opt/oracle/instantclient-basiclite-linuxx64.zip");

                // Remove configuration if it exists
                $this->executeCommand("rm -f /etc/php/*/mods-available/oci8.ini");
            }

            $this->info("Cleanup completed.");
        }
    }

    /**
     * Verify if an extension is successfully installed.
     *
     * @param string $extension
     * @return bool
     */
    protected function verifyInstallation($extension)
    {
        $this->info("Verifying $extension installation...");

        $process = Process::fromShellCommandline("php -m | grep -i $extension");
        $process->run();

        if ($process->isSuccessful()) {
            $this->info("✓ $extension extension is successfully installed and loaded.");
            return true;
        } else {
            $this->error("✗ $extension extension is not loaded.");
            $this->showExtensionTroubleshooting($extension);
            return false;
        }
    }

    /**
     * Show troubleshooting steps for a specific extension.
     *
     * @param string $extension
     * @return void
     */
    protected function showExtensionTroubleshooting($extension)
    {
        $this->info("Troubleshooting steps for $extension:");

        if ($extension === 'oci8') {
            $this->info("1. Check if the extension file exists:");
            $this->info("   ls -la /usr/lib/php/*/oci8.so");

            $this->info("2. Verify Oracle client installation:");
            $this->info("   ls -la /opt/oracle/instantclient_*");

            $this->info("3. Check PHP configuration:");
            $this->info("   php --ini");

            $this->info("4. Ensure LD_LIBRARY_PATH is set:");
            $this->info("   echo \$LD_LIBRARY_PATH");

            $this->info("5. Check for any errors in PHP logs:");
            $this->info("   tail -n 50 /var/log/php*");

            $this->info("6. Make sure the extension is enabled:");
            $this->info("   sudo phpenmod oci8");
            $this->info("   sudo service apache2 restart");
        } elseif ($extension === 'sqlsrv') {
            $this->info("1. Check if the extension files exist:");
            $this->info("   ls -la /usr/lib/php/*/sqlsrv.so");
            $this->info("   ls -la /usr/lib/php/*/pdo_sqlsrv.so");

            $this->info("2. Verify ODBC driver installation:");
            $this->info("   odbcinst -q -d");

            $this->info("3. Check PHP configuration:");
            $this->info("   php --ini");

            $this->info("4. Make sure the extensions are enabled:");
            $this->info("   sudo phpenmod sqlsrv pdo_sqlsrv");
            $this->info("   sudo service apache2 restart");
        }
    }

    /**
     * Ensure environment variables persist across sessions.
     *
     * @param string $extension
     * @return void
     */
    protected function ensureEnvironmentPersistence($extension)
    {
        if ($extension === 'oci8') {
            $this->info("Setting up environment persistence for Oracle...");

            // Add to system-wide profile
            $ldLibraryPathLine = 'export LD_LIBRARY_PATH=/opt/oracle/instantclient_*:$LD_LIBRARY_PATH';

            $files = [
                '/etc/profile.d/oracle.sh',
                '/etc/environment'
            ];

            foreach ($files as $file) {
                $this->info("Adding Oracle environment variables to $file");

                // Create the file if it doesn't exist
                if (!file_exists(dirname($file))) {
                    $this->executeCommand("sudo mkdir -p " . dirname($file));
                }

                // Check if the line already exists
                $checkCommand = "grep -q \"$ldLibraryPathLine\" $file 2>/dev/null || echo 'not found'";
                $process = Process::fromShellCommandline($checkCommand);
                $process->run();

                if (trim($process->getOutput()) === 'not found' || !file_exists($file)) {
                    // Add to file
                    $command = "echo \"$ldLibraryPathLine\" | sudo tee -a $file";
                    $this->executeCommand($command);

                    // Make executable if it's a shell script
                    if (strpos($file, '.sh') !== false) {
                        $this->executeCommand("sudo chmod +x $file");
                    }
                }
            }

            $this->info("Environment variables have been configured to persist across sessions.");
            $this->info("You may need to log out and log back in for these changes to take effect.");
        }
    }
}
