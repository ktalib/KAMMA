<?php

namespace Tests;

use Illuminate\Database\Connection;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Hard stop: tests use RefreshDatabase (migrate:fresh), and this app shares the KLAES
     * SQL Server database (klas), where a drop-all removes every KLAES table. Tests run
     * only on in-memory SQLite (phpunit.xml), whatever env file or override is in play,
     * and may not open the sqlsrv connection at all. KLAES lost its database this way
     * on 2026-09-24 (see klas/tests/CreatesApplication.php).
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $default = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$default}.database");

        if ($default !== 'sqlite' || $database !== ':memory:') {
            fwrite(STDERR, "\nFATAL: tests are configured for '{$default}' / '{$database}'. Refusing to run - "
                ."RefreshDatabase would drop every table there. Tests must use in-memory SQLite (phpunit.xml).\n\n");
            exit(1);
        }

        Connection::resolverFor('sqlsrv', function () {
            throw new RuntimeException('Tests must not use the sqlsrv connection: it is the shared KLAES database.');
        });

        // KLAES's tables (the 'klaes' connection, e.g. dbo.users) get their own empty in-memory SQLite;
        // a test creates the KLAES tables it needs (see Tests\Support\KlaesUsers).
        $app['config']->set('database.connections.klaes', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);

        return $app;
    }
}
