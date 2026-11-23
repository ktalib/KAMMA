<?php

namespace App\Http\Middleware;

use App\Support\Preview;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route middleware `kamma.preview:<table>,<table>…` for screens over KAMMA's own tables.
 *
 * When every listed table exists (the migrations have been run, a later approved step), it does nothing. When any
 * is missing, the screen runs in preview: KAMMA's default connection is pointed at a throw-away in-memory SQLite
 * database holding KAMMA's schema (the migration files' up(), run on that SQLite only), so the real controller and
 * view render with empty lists instead of an "Invalid object name" error. Saves (anything but GET/HEAD) are not
 * run at all: they answer "Saved (preview)".
 *
 * Safety: nothing here runs a migration on SQL Server. The migration files are only executed after the default
 * connection has been switched to the in-memory SQLite connection, and that is checked first; KLAES's
 * 'klaes' connection is explicit everywhere and is not touched.
 */
class PreviewWhenNotMigrated
{
    /** @var array<string, bool> per-request cache of table checks */
    private static array $checked = [];

    public function handle(Request $request, Closure $next, string ...$tables): Response
    {
        $missing = array_values(array_filter($tables, fn ($t) => ! $this->hasTable($t)));
        if ($missing === []) {
            return $next($request);
        }

        Preview::activate($missing);

        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            $message = Preview::savedMessage();

            return $request->expectsJson()
                ? new JsonResponse(['ok' => true, 'preview' => true, 'message' => $message])
                : back()->with('status', $message);
        }

        $this->usePreviewSchema();

        return $next($request);
    }

    private function hasTable(string $table): bool
    {
        $key = DB::getDefaultConnection().':'.$table;

        return self::$checked[$key] ??= Schema::hasTable($table);
    }

    private function usePreviewSchema(): void
    {
        $real = DB::getDefaultConnection();
        if ($real === 'kamma_preview') {
            return;
        }
        Preview::rememberRealConnection($real);

        // Sessions, cache and queue keep using the real connection (their tables exist).
        foreach (['session.connection', 'cache.stores.database.connection', 'queue.connections.database.connection'] as $key) {
            if (config($key) === null) {
                config([$key => $real]);
            }
        }

        config(['database.connections.kamma_preview' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => config("database.connections.{$real}.prefix", ''),
            'foreign_key_constraints' => true,
        ]]);
        DB::purge('kamma_preview');
        DB::setDefaultConnection('kamma_preview');

        $preview = DB::connection();
        if ($preview->getDriverName() !== 'sqlite' || $preview->getConfig('database') !== ':memory:') {
            throw new RuntimeException('Preview schema refused: the default connection is not the in-memory SQLite preview.');
        }

        foreach ($this->migrationFiles() as $file) {
            $migration = require $file;
            $migration->up();
        }
    }

    /** @return list<string> KAMMA's own migrations (the starter users/cache/jobs tables are not needed). */
    private function migrationFiles(): array
    {
        $files = glob(database_path('migrations/*.php')) ?: [];
        sort($files);

        return array_values(array_filter($files, fn ($f) => ! str_starts_with(basename($f), '0001_01_01_')));
    }

    /** Tests reset the per-request cache between requests. */
    public static function flush(): void
    {
        self::$checked = [];
    }
}
