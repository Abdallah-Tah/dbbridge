<?php

namespace DBBridge\Services;

use Symfony\Component\Process\Process;

class EnvironmentDetector
{
    /**
     * Get the current PHP version.
     *
     * @return string
     */
    public function getPhpVersion()
    {
        return PHP_VERSION;
    }

    /**
     * Get the current operating system.
     *
     * @return string
     */
    public function getOperatingSystem()
    {
        return PHP_OS_FAMILY;
    }

    /**
     * Get detailed operating system information.
     *
     * @return array
     */
    public function getOsInfo()
    {
        $os = PHP_OS_FAMILY;
        $osDetails = '';
        $osType = '';

        if ($os === 'Windows') {
            $osDetails = php_uname('s') . ' ' . php_uname('r');
            $osType = 'windows';
        } elseif ($os === 'Linux') {
            $osDetails = $this->getLinuxDistribution();
            $osType = 'linux';
            
            if ($this->isDebianBased()) {
                $osType = 'debian';
            } elseif ($this->isRHELBased()) {
                $osType = 'rhel';
            }
        } elseif ($os === 'Darwin') {
            $osDetails = $this->getMacOSVersion();
            $osType = 'macos';
        }

        return [
            'name' => $os,
            'details' => $osDetails,
            'type' => $osType
        ];
    }

    /**
     * Get the Linux distribution name.
     *
     * @return string
     */
    public function getLinuxDistribution()
    {
        if (file_exists('/etc/os-release')) {
            $osRelease = file_get_contents('/etc/os-release');
            preg_match('/PRETTY_NAME="([^"]+)"/', $osRelease, $matches);
            
            if (isset($matches[1])) {
                return $matches[1];
            }
        }
        
        // Try lsb_release command - compatible with PHP 7.4+
        if (version_compare(PHP_VERSION, '8.0', '<')) {
            $process = new Process(['lsb_release', '-ds']);
        } else {
            $process = Process::fromShellCommandline('lsb_release -ds');
        }
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
    public function getMacOSVersion()
    {
        // Compatible with PHP 7.4+
        if (version_compare(PHP_VERSION, '8.0', '<')) {
            $process = new Process(['sw_vers', '-productVersion']);
        } else {
            $process = Process::fromShellCommandline('sw_vers -productVersion');
        }
        $process->run();
        
        if ($process->isSuccessful()) {
            $version = trim($process->getOutput());
            return "macOS $version";
        }
        
        return 'Unknown macOS Version';
    }

    /**
     * Check if the system is Debian-based.
     *
     * @return bool
     */
    public function isDebianBased()
    {
        $osDetails = $this->getLinuxDistribution();
        
        return (
            strpos($osDetails, 'Debian') !== false ||
            strpos($osDetails, 'Ubuntu') !== false ||
            strpos($osDetails, 'Mint') !== false ||
            file_exists('/etc/debian_version')
        );
    }

    /**
     * Check if the system is RHEL-based.
     *
     * @return bool
     */
    public function isRHELBased()
    {
        $osDetails = $this->getLinuxDistribution();
        
        return (
            strpos($osDetails, 'Red Hat') !== false ||
            strpos($osDetails, 'CentOS') !== false ||
            strpos($osDetails, 'Fedora') !== false ||
            file_exists('/etc/redhat-release')
        );
    }

    /**
     * Get PHP configuration information.
     *
     * @return array
     */
    public function getPhpConfig()
    {
        return [
            'version' => PHP_VERSION,
            'ini_path' => php_ini_loaded_file(),
            'extension_dir' => ini_get('extension_dir'),
            'extensions' => get_loaded_extensions()
        ];
    }

    /**
     * Check if a specific PHP extension is loaded.
     *
     * @param string $extension
     * @return bool
     */
    public function hasExtension($extension)
    {
        return extension_loaded($extension);
    }

    /**
     * Get all installed database extensions.
     *
     * @return array
     */
    public function getDatabaseExtensions()
    {
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
        
        return [
            'installed' => $installedExtensions,
            'missing' => $missingExtensions
        ];
    }
} 