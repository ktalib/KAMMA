<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // This app shares the KLAES SQL Server database (klas). On SQL Server, db:wipe and
        // migrate:fresh/refresh/reset drop EVERY table in the database, whatever the prefix,
        // so one of them would take all of KLAES with it. They (and migrate:rollback) are
        // refused everywhere except the in-memory SQLite test run, where RefreshDatabase needs them.
        $inMemoryTests = $this->app->runningUnitTests()
            && config('database.default') === 'sqlite'
            && config('database.connections.sqlite.database') === ':memory:';

        DB::prohibitDestructiveCommands(! $inMemoryTests);
    }
}
