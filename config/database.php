<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework — Database Configuration
 *  @author kangpcode (Dhafa Nazula Permadi) | @license see watermark
 * ------------------------------------------------------------------
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Default Database Connection
    |--------------------------------------------------------------------------
    | Supported: "sqlite", "mysql", "mariadb", "pgsql", "supabase", "mongodb", "sqlsrv"
    | Default is zero-config SQLite for instant out-of-the-box local development.
    */
    'default' => env('DB_CONNECTION', 'sqlite'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    | Papastro supports enterprise multi-database connections similar to Laravel.
    | You may configure and switch between SQLite, MySQL, MariaDB, PostgreSQL,
    | Supabase cloud databases, MongoDB document stores, and SQL Server.
    */
    'connections' => [
        'sqlite' => [
            'driver'   => 'sqlite',
            'url'      => env('DATABASE_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        'mysql' => [
            'driver'    => 'mysql',
            'url'       => env('DATABASE_URL'),
            'host'      => env('DB_HOST', '127.0.0.1'),
            'port'      => env('DB_PORT', '3306'),
            'database'  => env('DB_DATABASE', 'papastro'),
            'username'  => env('DB_USERNAME', 'root'),
            'password'  => env('DB_PASSWORD', ''),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'persistent'=> env('DB_PERSISTENT', false),
        ],

        'mariadb' => [
            'driver'    => 'mariadb',
            'url'       => env('DATABASE_URL'),
            'host'      => env('DB_HOST', '127.0.0.1'),
            'port'      => env('DB_PORT', '3306'),
            'database'  => env('DB_DATABASE', 'papastro'),
            'username'  => env('DB_USERNAME', 'root'),
            'password'  => env('DB_PASSWORD', ''),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'persistent'=> env('DB_PERSISTENT', false),
        ],

        'pgsql' => [
            'driver'   => 'pgsql',
            'url'      => env('DATABASE_URL'),
            'host'     => env('DB_HOST', '127.0.0.1'),
            'port'     => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'papastro'),
            'username' => env('DB_USERNAME', 'postgres'),
            'password' => env('DB_PASSWORD', ''),
            'charset'  => 'utf8',
            'schema'   => env('DB_SCHEMA', 'public'),
            'sslmode'  => env('DB_SSLMODE', 'prefer'),
        ],

        'supabase' => [
            'driver'   => 'pgsql',
            'url'      => env('SUPABASE_DB_URL', env('DATABASE_URL')),
            'host'     => env('SUPABASE_DB_HOST', env('DB_HOST', 'aws-0-ap-southeast-1.pooler.supabase.com')),
            'port'     => env('SUPABASE_DB_PORT', env('DB_PORT', '6543')), // 6543 for pgBouncer pooler, 5432 for direct
            'database' => env('SUPABASE_DB_DATABASE', env('DB_DATABASE', 'postgres')),
            'username' => env('SUPABASE_DB_USERNAME', env('DB_USERNAME', 'postgres.your-project-ref')),
            'password' => env('SUPABASE_DB_PASSWORD', env('DB_PASSWORD', '')),
            'charset'  => 'utf8',
            'schema'   => 'public',
            'sslmode'  => 'require', // Supabase mandates SSL
        ],

        'mongodb' => [
            'driver'   => 'mongodb',
            'dsn'      => env('MONGODB_URI', 'mongodb://127.0.0.1:27017'),
            'database' => env('MONGODB_DATABASE', 'papastro'),
            'options'  => [
                'appname' => 'PapastroFramework',
            ],
        ],

        'sqlsrv' => [
            'driver'   => 'sqlsrv',
            'url'      => env('DATABASE_URL'),
            'host'     => env('DB_HOST', 'localhost'),
            'port'     => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'papastro'),
            'username' => env('DB_USERNAME', 'sa'),
            'password' => env('DB_PASSWORD', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Settings
    |--------------------------------------------------------------------------
    */
    'migrations' => [
        'table' => 'papastro_migrations',
        'path'  => 'database/migrations',
    ],
];
