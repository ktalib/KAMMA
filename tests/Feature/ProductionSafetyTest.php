<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * This app shares the KLAES SQL Server database (klas). These guard the two stops that keep a
 * drop-all away from it: tests never reach sqlsrv, and destructive artisan commands are refused
 * outside the in-memory SQLite test run. Don't weaken them to make another test pass.
 */
class ProductionSafetyTest extends TestCase
{
    protected function tearDown(): void
    {
        // The prohibition is static; leave it as the next test's fresh boot would set it.
        DB::prohibitDestructiveCommands(false);

        parent::tearDown();
    }

    public function test_tests_run_on_in_memory_sqlite(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
    }

    public function test_tests_cannot_open_the_shared_sql_server_connection(): void
    {
        $this->expectException(RuntimeException::class);

        DB::connection('sqlsrv')->getPdo();
    }

    public function test_destructive_commands_are_refused_outside_the_in_memory_test_run(): void
    {
        // Boot the provider as it would see a real (non-SQLite) connection. --database=sqlite
        // means that even if the stop failed, only the in-memory test DB could be touched.
        config(['database.default' => 'sqlsrv']);
        (new AppServiceProvider($this->app))->boot();
        config(['database.default' => 'sqlite']);

        foreach (['db:wipe', 'migrate:fresh', 'migrate:refresh', 'migrate:reset', 'migrate:rollback'] as $command) {
            $this->artisan($command, ['--database' => 'sqlite', '--force' => true])->assertFailed();
        }
    }
}
