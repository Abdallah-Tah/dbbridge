<?php

namespace DBBridge\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class CheckEnvironmentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dbbridge:check-environment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the PHP environment and installed database extensions';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('DBBridge Environment Check');
        $this->info('==========================');
        $this->newLine();

        // Check PHP version
        $phpVersion = PHP_VERSION;
        $this->info("PHP Version: $phpVersion");
        
        // Check OS
        $os = PHP_OS_FAMILY;
        $this->info("Operating System: $os");
        
        // Get more detailed OS info
        if ($os === 'Windows') {
            $osDetails = php_uname('s') . ' ' . php_uname('r');
            $this->info("OS Details: $osDetails");
        } elseif ($os === 'Linux') {
            $osDetails = $this->getLinuxDistribution();
            $this->info("Linux Distribution: $osDetails");
        } elseif ($os === 'Darwin') {
            $osDetails = $this->getMacOSVersion();
            $this->info("macOS Version: $osDetails");
        }
        
        $this->newLine();
        
        // Check PHP configuration
        $this->info('PHP Configuration:');
        $phpIniPath = php_ini_loaded_file();
        $this->info("Loaded php.ini: $phpIniPath");
        
        $extensionDir = ini_get('extension_dir');
        $this->info("Extension directory: $extensionDir");
        
        $this->newLine();
        
        // Check installed database extensions
        $this->info('Installed Database Extensions:');
        $extensions = get_loaded_extensions();
        
        $dbExtensions = [
            'mysql' => ['mysqli', 'pdo_mysql'],
            'sqlsrv' => ['sqlsrv', 'pdo_sqlsrv'],
            'oracle' => ['oci8', 'pdo_oci'],
            'pgsql' => ['pgsql', 'pdo_pgsql'],
            'sqlite' => ['sqlite3', 'pdo_sqlite']
        ];
        
        $installedExtensions = [];
        $missingExtensions = [];
        
        foreach ($dbExtensions as $db => $exts) {
            $installed = [];
            $missing = [];
            
            foreach ($exts as $ext) {
                if (in_array($ext, $extensions)) {
                    $installed[] = $ext;
                } else {
                    $missing[] = $ext;
                }
            }
            
            if (!empty($installed)) {
                $installedExtensions[$db] = $installed;
            }
            
            if (!empty($missing)) {
                $missingExtensions[$db] = $missing;
            }
        }
        
        if (!empty($installedExtensions)) {
            foreach ($installedExtensions as $db => $exts) {
                $this->info("✅ " . ucfirst($db) . ": " . implode(', ', $exts));
            }
        } else {
            $this->warn('No database extensions detected.');
        }
        
        $this->newLine();
        
        if (!empty($missingExtensions)) {
            $this->info('Missing Database Extensions:');
            foreach ($missingExtensions as $db => $exts) {
                $this->warn("❌ " . ucfirst($db) . ": " . implode(', ', $exts));
            }
            
            $this->newLine();
            $this->info('You can install missing extensions using:');
            $this->info('php artisan dbbridge:install-extensions');
        } else {
            $this->info('All common database extensions are installed! 🎉');
        }
        
        return 0;
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
} 