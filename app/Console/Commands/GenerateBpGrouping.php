<?php

namespace App\Console\Commands;

use App\Models\Bpa\BpFile;
use App\Models\Bpa\BpGrouping;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Fills the BP grouping (kadca_bp_groupings): BP-[Year]-[Serial] numbers for existing BP files, one block per year
 * (user 2026-11-25: "start from 1981 ... for now 10,000 per year"). Safe to run again: only missing numbers are added,
 * so a later run with a bigger --per-year extends every year. Numbers already used by BP files are marked as used.
 * Every number without a tracking ID gets one (BP-TRK-…, user 2026-11-26).
 */
class GenerateBpGrouping extends Command
{
    protected $signature = 'kamma:bp-grouping
        {--from=1981 : first year}
        {--to= : last year (default: the current year)}
        {--per-year=10000 : numbers per year}
        {--commit : write; without it the command only reports what it would add}';

    protected $description = 'Generate the BP file number grouping (BP-Year-Serial) for existing Building Plan files';

    public function handle(): int
    {
        $from = (int) $this->option('from');
        $to = (int) ($this->option('to') ?: now()->format('Y'));
        $per = (int) $this->option('per-year');
        if ($from < 1900 || $to < $from || $to > (int) now()->format('Y') + 1 || $per < 1 || $per > 100000) {
            $this->error('Check --from, --to and --per-year.');

            return self::INVALID;
        }

        $conn = DB::connection();
        $this->line('Database: '.$conn->getDatabaseName().' ('.$conn->getDriverName().'), table '.$conn->getTablePrefix().'bp_groupings');

        $plan = [];
        for ($year = $from; $year <= $to; $year++) {
            $have = BpGrouping::query()->where('year', $year)->where('serial', '<=', $per)->count();
            $plan[$year] = $per - $have;
        }
        $missing = array_sum($plan);
        $this->line("Years {$from}-{$to}, {$per} a year: ".number_format($missing).' numbers to add.');
        $this->line('Grouping numbers without a tracking ID (every year): '.number_format(BpGrouping::query()->whereNull('tracking_id')->count())
            .($missing ? ' (+ the numbers added)' : '').'.');

        if (! $this->option('commit')) {
            $this->warn('Dry run. Add --commit to write.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar(count($plan));
        foreach ($plan as $year => $count) {
            if ($count > 0) {
                $this->fillYear($year, $per);
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        // Numbers already on the register (commissioned through the older application path, or indexed) are marked.
        $marked = 0;
        foreach (BpFile::query()->live()->whereBetween('bp_year', [$from, $to])->get(['id', 'bp_year', 'bp_serial', 'origin']) as $f) {
            $marked += BpGrouping::query()->where('year', $f->bp_year)->where('serial', $f->bp_serial)->where('status', BpGrouping::AVAILABLE)
                ->update(['status' => $f->origin === BpFile::INDEXED ? BpGrouping::INDEXED : BpGrouping::COMMISSIONED, 'bp_file_id' => $f->id, 'used_at' => now()]);
            BpFile::query()->whereKey($f->id)->whereNull('bp_grouping_id')
                ->update(['bp_grouping_id' => BpGrouping::query()->where('year', $f->bp_year)->where('serial', $f->bp_serial)->value('id')]);
        }

        $tracked = $this->fillTrackingIds();

        $this->info('Done. '.number_format(BpGrouping::query()->whereBetween('year', [$from, $to])->count()).' numbers in the grouping for those years; '.$marked.' marked as used; '
            .number_format($tracked).' tracking IDs given.');

        return self::SUCCESS;
    }

    /**
     * Every grouping number without a tracking ID gets one (BP-TRK-XXXXXXXX, user 2026-11-26), unique across the
     * grouping. 500 rows per statement, each its own short write (klas has no snapshot isolation). Safe to run again.
     */
    private function fillTrackingIds(): int
    {
        $table = DB::getTablePrefix().'bp_groupings';
        $used = array_flip(BpGrouping::query()->whereNotNull('tracking_id')->pluck('tracking_id')->all());
        $total = BpGrouping::query()->whereNull('tracking_id')->count();
        if ($total === 0) {
            return 0;
        }

        $this->line('Giving '.number_format($total).' numbers their tracking ID…');
        $bar = $this->output->createProgressBar($total);
        $done = 0;
        $lastId = 0;
        while (true) {
            $ids = BpGrouping::query()->whereNull('tracking_id')->where('id', '>', $lastId)->orderBy('id')->limit(500)->pluck('id')->all();
            if (! $ids) {
                break;
            }
            $case = '';
            $bindings = [];
            foreach ($ids as $id) {
                do {
                    $trk = BpGrouping::randomTrackingId();
                } while (isset($used[$trk]));
                $used[$trk] = true;
                $case .= ' WHEN ? THEN ?';
                array_push($bindings, $id, $trk);
            }
            $in = implode(',', array_fill(0, count($ids), '?'));
            $done += DB::update("UPDATE {$table} SET tracking_id = CASE id{$case} END WHERE tracking_id IS NULL AND id IN ({$in})", [...$bindings, ...$ids]);
            $lastId = end($ids);
            $bar->advance(count($ids));
        }
        $bar->finish();
        $this->newLine();

        return $done;
    }

    /** One year at a time, in short transactions (klas has no snapshot isolation: long writes block readers). */
    private function fillYear(int $year, int $per): void
    {
        $table = DB::getTablePrefix().'bp_groupings';

        if (DB::getDriverName() === 'sqlsrv') {
            // Set-based: a numbers list from a cross join of system rows, only the serials still missing.
            DB::statement("
                WITH n AS (
                    SELECT TOP ({$per}) ROW_NUMBER() OVER (ORDER BY (SELECT NULL)) AS s
                    FROM sys.all_columns a CROSS JOIN sys.all_columns b
                )
                INSERT INTO {$table} (year, serial, bp_file_no, status)
                SELECT ?, n.s, CONCAT('BP-', ?, '-', n.s), 'Available'
                FROM n
                WHERE NOT EXISTS (SELECT 1 FROM {$table} g WHERE g.year = ? AND g.serial = n.s)", [$year, $year, $year]);

            return;
        }

        $existing = array_flip(BpGrouping::query()->where('year', $year)->pluck('serial')->all());
        $rows = [];
        for ($s = 1; $s <= $per; $s++) {
            if (! isset($existing[$s])) {
                $rows[] = ['year' => $year, 'serial' => $s, 'bp_file_no' => "BP-{$year}-{$s}", 'status' => BpGrouping::AVAILABLE];
            }
            if (count($rows) === 500 || ($s === $per && $rows)) {
                DB::table('bp_groupings')->insert($rows);
                $rows = [];
            }
        }
    }
}
