<?php

namespace App\Support;

/**
 * UI-first review (user 2026-11-23: "we should first finish the UI and frontend ... next we can focus on the
 * backend"). KAMMA's own tables are not created yet: the migrations are written but not run. While a screen's
 * tables are missing, App\Http\Middleware\PreviewWhenNotMigrated serves it from a throw-away in-memory SQLite copy
 * of KAMMA's schema, so the real screen renders with empty lists, and every save answers "Saved (preview)".
 * KLAES data (the read-only 'klaes' connection) is real either way.
 */
class Preview
{
    private static bool $active = false;

    /** @var list<string> */
    private static array $missing = [];

    private static ?string $realConnection = null;

    public static function activate(array $missing): void
    {
        self::$active = true;
        self::$missing = array_values($missing);
    }

    public static function active(): bool
    {
        return self::$active;
    }

    /** @return list<string> */
    public static function missing(): array
    {
        return self::$missing;
    }

    public static function rememberRealConnection(string $name): void
    {
        self::$realConnection ??= $name;
    }

    /** Start of every request: no preview, and the real default connection back (tests run many requests per app). */
    public static function reset(): void
    {
        self::$active = false;
        self::$missing = [];
        if (self::$realConnection !== null && \Illuminate\Support\Facades\DB::getDefaultConnection() === 'kamma_preview') {
            \Illuminate\Support\Facades\DB::setDefaultConnection(self::$realConnection);
        }
    }

    /** The answer every save gives in preview. */
    public static function savedMessage(string $what = 'Your entry'): string
    {
        return "Saved (preview): {$what} was checked but not stored. Saving opens once KAMMA's tables are created.";
    }
}
