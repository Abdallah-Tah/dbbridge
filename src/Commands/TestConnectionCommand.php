<?php

namespace DBBridge\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;

class TestConnectionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dbbridge:test-connection
                            {driver : Database driver to test (mysql, sqlsrv, oci)}
                            {--host=127.0.0.1 : Database host}
                            {--port= : Database port}
                            {--database= : Database name}
                            {--username= : Database username}
                            {--password= : Database password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test a database connection using specified driver';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $driver = $this->argument('driver');
        $host = $this->option('host');
        $port = $this->option('port');
        $database = $this->option('database');
        $username = $this->option('username');
        $password = $this->option('password');

        $this->info('DBBridge Connection Tester');
        $this->info('==========================');
        $this->newLine();

        // Validate driver
        if (!in_array($driver, ['mysql', 'sqlsrv', 'oci'])) {
            $this->error("Unsupported driver: $driver");
            $this->info("Supported drivers: mysql, sqlsrv, oci");
            return 1;
        }

        // Prompt for missing connection details
        if (empty($database)) {
            $database = $this->ask('Database name');
        }

        if (empty($username)) {
            $username = $this->ask('Username');
        }

        if (empty($password)) {
            $password = $this->secret('Password');
        }

        if (empty($port)) {
            // Set default port based on driver
            switch ($driver) {
                case 'mysql':
                    $port = 3306;
                    break;
                case 'sqlsrv':
                    $port = 1433;
                    break;
                case 'oci':
                    $port = 1521;
                    break;
            }
        }

        $this->info("Testing connection to $driver database:");
        $this->info("Host: $host");
        $this->info("Port: $port");
        $this->info("Database: $database");
        $this->info("Username: $username");
        $this->newLine();

        try {
            // Test connection using PDO directly
            $this->info("Testing connection using PDO...");
            $pdoConnection = $this->testPDOConnection($driver, $host, $port, $database, $username, $password);
            
            if ($pdoConnection) {
                $this->info("✅ PDO connection successful!");
                $this->info("Server version: " . $pdoConnection->getAttribute(PDO::ATTR_SERVER_VERSION));
                $pdoConnection = null; // Close connection
            }
            
            $this->newLine();
            
            // Test connection using Laravel's DB facade
            $this->info("Testing connection using Laravel DB...");
            $laravelConnection = $this->testLaravelConnection($driver, $host, $port, $database, $username, $password);
            
            if ($laravelConnection) {
                $this->info("✅ Laravel DB connection successful!");
            }
            
            $this->newLine();
            $this->info("All connection tests passed successfully! 🎉");
            return 0;
        } catch (\Exception $e) {
            $this->error("Connection failed: " . $e->getMessage());
            
            // Provide troubleshooting tips
            $this->newLine();
            $this->info("Troubleshooting tips:");
            
            if (strpos($e->getMessage(), 'could not find driver') !== false) {
                $this->info("• The $driver PHP extension is not installed or enabled.");
                $this->info("  Run 'php artisan dbbridge:install-extensions --extension=$driver' to install it.");
            } elseif (strpos($e->getMessage(), 'Access denied') !== false) {
                $this->info("• Check your username and password.");
                $this->info("• Ensure the user has access to the specified database.");
            } elseif (strpos($e->getMessage(), 'Connection refused') !== false) {
                $this->info("• Check if the database server is running.");
                $this->info("• Verify the host and port settings.");
                $this->info("• Check if any firewall is blocking the connection.");
            }
            
            return 1;
        }
    }

    /**
     * Test connection using PDO.
     *
     * @param string $driver
     * @param string $host
     * @param int $port
     * @param string $database
     * @param string $username
     * @param string $password
     * @return PDO|null
     * @throws \Exception
     */
    protected function testPDOConnection($driver, $host, $port, $database, $username, $password)
    {
        $dsn = '';
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5,
        ];

        switch ($driver) {
            case 'mysql':
                $dsn = "mysql:host=$host;port=$port;dbname=$database";
                break;
            case 'sqlsrv':
                $dsn = "sqlsrv:Server=$host,$port;Database=$database";
                break;
            case 'oci':
                $dsn = "oci:dbname=//$host:$port/$database";
                break;
        }

        $this->info("Connecting with DSN: $dsn");
        $pdo = new PDO($dsn, $username, $password, $options);
        
        // Test a simple query
        $stmt = $pdo->query('SELECT 1 AS test');
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (isset($result['test']) && $result['test'] == 1) {
            return $pdo;
        }
        
        return null;
    }

    /**
     * Test connection using Laravel's DB facade.
     *
     * @param string $driver
     * @param string $host
     * @param int $port
     * @param string $database
     * @param string $username
     * @param string $password
     * @return bool
     * @throws \Exception
     */
    protected function testLaravelConnection($driver, $host, $port, $database, $username, $password)
    {
        // Map our driver names to Laravel's
        $laravelDriver = $driver;
        if ($driver === 'oci') {
            $laravelDriver = 'oracle';
        }

        // Create a dynamic connection configuration
        $config = [
            'driver' => $laravelDriver,
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username,
            'password' => $password,
            'charset' => 'utf8',
            'prefix' => '',
        ];

        // Add driver-specific options
        if ($driver === 'sqlsrv') {
            $config['trust_server_certificate'] = true;
        }

        // Create a dynamic connection
        DB::purge('dynamic_test');
        config(['database.connections.dynamic_test' => $config]);

        // Test the connection
        DB::connection('dynamic_test')->getPdo();
        
        // Run a test query
        $result = DB::connection('dynamic_test')->select('SELECT 1 AS test');
        
        if (isset($result[0]->test) && $result[0]->test == 1) {
            return true;
        }
        
        return false;
    }
} 