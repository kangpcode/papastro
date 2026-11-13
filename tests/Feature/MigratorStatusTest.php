<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework v5.0.0
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Papastro\ORM\Connection;
use Papastro\ORM\Migrator;

class MigratorStatusTest extends TestCase
{
    public function test_migrator_get_status(): void
    {
        $app = $this->bootApp();
        $db = $app->make(Connection::class);
        $migrationsPath = $app->databasePath('migrations');

        $migrator = new Migrator($db, $migrationsPath);
        $statuses = $migrator->getStatus();

        $this->assertIsArray($statuses);
        $this->assertNotEmpty($statuses);

        foreach ($statuses as $status) {
            $this->assertArrayHasKey('ran', $status);
            $this->assertArrayHasKey('migration', $status);
            $this->assertArrayHasKey('batch', $status);
            $this->assertArrayHasKey('ran_at', $status);
            $this->assertIsBool($status['ran']);
        }
    }
}
