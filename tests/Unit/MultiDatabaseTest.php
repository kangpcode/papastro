<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v4.0.0 — created by kangpcode (Dhafa Nazula Permadi)
 *  Free to use & extend. This watermark must remain in source code.
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use Papastro\ORM\DatabaseManager;
use Papastro\ORM\Connection;
use Papastro\ORM\MongoConnection;

class MultiDatabaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->bootApp();
    }

    public function testDatabaseManagerResolvesDefaultAndNamedConnections(): void
    {
        $manager = $this->app->make(DatabaseManager::class);

        $default = $manager->connection();
        $this->assertInstanceOf(Connection::class, $default);
        $this->assertSame('sqlite', $default->getDriverName());

        $sqlite = $manager->connection('sqlite');
        $this->assertSame($default, $sqlite);
    }

    public function testDatabaseManagerResolvesConfiguredDrivers(): void
    {
        $manager = $this->app->make(DatabaseManager::class);

        $mysql = $manager->connection('mysql');
        $this->assertInstanceOf(Connection::class, $mysql);
        $this->assertSame('mysql', $mysql->getDriverName());

        $mariadb = $manager->connection('mariadb');
        $this->assertInstanceOf(Connection::class, $mariadb);
        $this->assertSame('mariadb', $mariadb->getDriverName());

        $pgsql = $manager->connection('pgsql');
        $this->assertInstanceOf(Connection::class, $pgsql);
        $this->assertSame('pgsql', $pgsql->getDriverName());

        $supabase = $manager->connection('supabase');
        $this->assertInstanceOf(Connection::class, $supabase);
        $this->assertSame('pgsql', $supabase->getDriverName());

        $mongo = $manager->connection('mongodb');
        $this->assertInstanceOf(MongoConnection::class, $mongo);
        $this->assertSame('mongodb', $mongo->getDriverName());
    }

    public function testCloudDatabaseUrlParsingForSupabase(): void
    {
        $conn = new Connection([
            'driver' => 'pgsql',
            'url' => 'postgresql://postgres.myref:secretpass@aws-0-ap-southeast-1.pooler.supabase.com:6543/postgres?sslmode=require',
        ]);

        $this->assertSame('pgsql', $conn->getDriverName());
    }

    public function testGlobalDatabaseHelpers(): void
    {
        $this->assertTrue(function_exists('db'));
        $this->assertTrue(function_exists('mongodb'));

        $dbManager = db();
        $this->assertInstanceOf(DatabaseManager::class, $dbManager);

        $sqliteConn = db('sqlite');
        $this->assertInstanceOf(Connection::class, $sqliteConn);

        $mongoConn = db('mongodb');
        $this->assertInstanceOf(MongoConnection::class, $mongoConn);
    }
}

