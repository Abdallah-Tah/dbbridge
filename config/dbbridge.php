<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supported Database Extensions
    |--------------------------------------------------------------------------
    |
    | This array defines the database extensions that DBBridge can help install
    | and configure. Each extension has configuration for different operating
    | systems and installation methods.
    |
    */
    'extensions' => [
        'sqlsrv' => [
            'name' => 'SQL Server',
            'php_extension' => 'sqlsrv',
            'pdo_extension' => 'pdo_sqlsrv',
            'windows' => [
                'download_url' => 'https://github.com/microsoft/msphpsql/releases/download/v5.10.1/Windows-8.1.zip',
                'installation_steps' => [
                    'Download the SQLSRV drivers from the Microsoft GitHub repository',
                    'Extract the appropriate DLL files to your PHP extensions directory',
                    'Add extension=sqlsrv.dll and extension=pdo_sqlsrv.dll to your php.ini file',
                    'Restart your web server'
                ],
                'requirements' => [
                    'Microsoft ODBC Driver for SQL Server',
                    'Microsoft Visual C++ Redistributable'
                ]
            ],
            'linux' => [
                'debian_based' => [
                    'commands' => [
                        'curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add -',
                        'curl https://packages.microsoft.com/config/ubuntu/$(lsb_release -rs)/prod.list > /etc/apt/sources.list.d/mssql-release.list',
                        'apt-get update',
                        'ACCEPT_EULA=Y apt-get install -y msodbcsql17 unixodbc-dev',
                        'pecl install sqlsrv pdo_sqlsrv',
                        'echo "extension=sqlsrv.so" > /etc/php/$(php -r "echo PHP_MAJOR_VERSION.\".\".PHP_MINOR_VERSION;")/mods-available/sqlsrv.ini',
                        'echo "extension=pdo_sqlsrv.so" > /etc/php/$(php -r "echo PHP_MAJOR_VERSION.\".\".PHP_MINOR_VERSION;")/mods-available/pdo_sqlsrv.ini',
                        'phpenmod -v $(php -r "echo PHP_MAJOR_VERSION.\".\".PHP_MINOR_VERSION;") sqlsrv pdo_sqlsrv'
                    ]
                ],
                'rhel_based' => [
                    'commands' => [
                        'curl https://packages.microsoft.com/config/rhel/8/prod.repo > /etc/yum.repos.d/mssql-release.repo',
                        'ACCEPT_EULA=Y yum install -y msodbcsql17 unixODBC-devel',
                        'yum install -y php-pear php-devel',
                        'pecl install sqlsrv pdo_sqlsrv',
                        'echo "extension=sqlsrv.so" > /etc/php.d/30-sqlsrv.ini',
                        'echo "extension=pdo_sqlsrv.so" > /etc/php.d/35-pdo_sqlsrv.ini'
                    ]
                ]
            ],
            'macos' => [
                'commands' => [
                    'brew tap microsoft/mssql-release https://github.com/Microsoft/homebrew-mssql-release',
                    'brew update',
                    'ACCEPT_EULA=Y brew install msodbcsql17 mssql-tools unixodbc',
                    'pecl install sqlsrv pdo_sqlsrv',
                    'echo "extension=sqlsrv.so" >> $(php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||")',
                    'echo "extension=pdo_sqlsrv.so" >> $(php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||")'
                ]
            ]
        ],
        'oci8' => [
            'name' => 'Oracle',
            'php_extension' => 'oci8',
            'pdo_extension' => 'pdo_oci',
            'windows' => [
                'download_url' => 'https://download.oracle.com/otn_software/nt/instantclient/instantclient-basiclite-windows.zip',
                'installation_steps' => [
                    'Download Oracle Instant Client from Oracle website',
                    'Extract the files to a directory (e.g., C:\\oracle\\instantclient)',
                    'Add the directory to your PATH environment variable',
                    'Install the OCI8 extension using PECL or download pre-compiled DLL',
                    'Add extension=oci8.dll to your php.ini file',
                    'Restart your web server'
                ]
            ],
            'linux' => [
                'debian_based' => [
                    'commands' => [
                        'apt-get update',
                        'apt-get install -y libaio1',
                        'mkdir -p /opt/oracle',
                        'cd /opt/oracle',
                        'wget https://download.oracle.com/otn_software/linux/instantclient/instantclient-basiclite-linuxx64.zip',
                        'unzip instantclient-basiclite-linuxx64.zip',
                        'echo "/opt/oracle/instantclient_*" > /etc/ld.so.conf.d/oracle-instantclient.conf',
                        'ldconfig',
                        'export LD_LIBRARY_PATH=/opt/oracle/instantclient_*:$LD_LIBRARY_PATH',
                        'pecl install oci8',
                        'echo "extension=oci8.so" > /etc/php/$(php -r "echo PHP_MAJOR_VERSION.\".\".PHP_MINOR_VERSION;")/mods-available/oci8.ini',
                        'phpenmod -v $(php -r "echo PHP_MAJOR_VERSION.\".\".PHP_MINOR_VERSION;") oci8'
                    ]
                ],
                'rhel_based' => [
                    'commands' => [
                        'yum install -y libaio',
                        'mkdir -p /opt/oracle',
                        'cd /opt/oracle',
                        'wget https://download.oracle.com/otn_software/linux/instantclient/instantclient-basiclite-linuxx64.zip',
                        'unzip instantclient-basiclite-linuxx64.zip',
                        'echo "/opt/oracle/instantclient_*" > /etc/ld.so.conf.d/oracle-instantclient.conf',
                        'ldconfig',
                        'export LD_LIBRARY_PATH=/opt/oracle/instantclient_*:$LD_LIBRARY_PATH',
                        'pecl install oci8',
                        'echo "extension=oci8.so" > /etc/php.d/20-oci8.ini'
                    ]
                ]
            ],
            'macos' => [
                'commands' => [
                    'mkdir -p /opt/oracle',
                    'cd /opt/oracle',
                    'curl -O https://download.oracle.com/otn_software/mac/instantclient/instantclient-basiclite-macos.zip',
                    'unzip instantclient-basiclite-macos.zip',
                    'export DYLD_LIBRARY_PATH=/opt/oracle/instantclient_*:$DYLD_LIBRARY_PATH',
                    'pecl install oci8',
                    'echo "extension=oci8.so" >> $(php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||")'
                ]
            ]
        ],
        'mysql' => [
            'name' => 'MySQL',
            'php_extension' => 'mysqli',
            'pdo_extension' => 'pdo_mysql',
            'windows' => [
                'installation_steps' => [
                    'MySQL extensions are typically included with PHP by default',
                    'Ensure extension=mysqli and extension=pdo_mysql are uncommented in php.ini',
                    'Restart your web server'
                ]
            ],
            'linux' => [
                'debian_based' => [
                    'commands' => [
                        'apt-get update',
                        'apt-get install -y php-mysql',
                        'phpenmod -v $(php -r "echo PHP_MAJOR_VERSION.\".\".PHP_MINOR_VERSION;") mysqli pdo_mysql'
                    ]
                ],
                'rhel_based' => [
                    'commands' => [
                        'yum install -y php-mysql'
                    ]
                ]
            ],
            'macos' => [
                'commands' => [
                    'brew install php',
                    'echo "extension=mysqli.so" >> $(php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||")',
                    'echo "extension=pdo_mysql.so" >> $(php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||")'
                ]
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | PHP Configuration
    |--------------------------------------------------------------------------
    |
    | Settings related to PHP configuration that may need to be adjusted
    | for database extensions to work properly.
    |
    */
    'php_config' => [
        'extension_dir' => null, // Auto-detected if null
        'php_ini_path' => null, // Auto-detected if null
    ],

    /*
    |--------------------------------------------------------------------------
    | Installation Preferences
    |--------------------------------------------------------------------------
    |
    | Default preferences for installation behavior.
    |
    */
    'preferences' => [
        'auto_restart_server' => false,
        'backup_config_files' => true,
        'installation_method' => 'guided', // 'guided', 'automatic', or 'manual'
    ],
]; 