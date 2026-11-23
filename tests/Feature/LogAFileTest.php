<?php

namespace Tests\Feature;

use App\Models\Tracking\FileTracker;
use App\Models\Tracking\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\KammaFiles;
use Tests\Support\KlaesFiles;
use Tests\Support\KlaesUsers;
use Tests\TestCase;

/** Log a File (Create File Tracker), copied from KLAES: log-out / log-in with KLAES's fields, and the File Tracker Log tab. */
class LogAFileTest extends TestCase
{
    use KammaFiles, KlaesFiles, KlaesUsers, RefreshDatabase;

    private User $officer;

    private User $colleague;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->travelTo(now()->setDate(2026, 11, 22));
        $this->officer = $this->klaesUser();
        $this->colleague = $this->klaesUser(['first_name' => 'Musa', 'last_name' => 'Ali', 'username' => 'mali', 'rank' => 'Town Planner II']);
    }

    private function unit(string $name): int
    {
        return Unit::where('name', $name)->value('id');
    }

    private function logFile(array $overrides = [])
    {
        return $this->actingAs($this->officer)->post('/file-tracking/log', array_merge([
            'file_number' => 'RES-2016-452', 'priority' => 'High', 'status_update' => 'log-out', 'purpose' => 'FOR KSI (SITE INSPECTION)',
            'timeline_days' => 7, 'expected_return_on' => '2026-11-29', 'num_pages' => 42, 'to_unit_id' => $this->unit('Zonal Office'),
            'holder_user_id' => $this->colleague->id, 'log_id' => 'LOG-20261122101500-123',
        ], $overrides));
    }

    public function test_the_page_is_the_klaes_create_file_tracker(): void
    {
        $this->actingAs($this->officer)->get('/file-tracking/log?file=RES-2016-452')
            ->assertOk()
            ->assertSeeInOrder(['Request Mode', 'Manual Request', 'Reset Form', 'Preview', 'Create File Tracker', 'File Tracker Log'])
            ->assertSeeInOrder(['File Details', 'File No', 'File Tracking ID', 'File Title', 'File Priority', 'Status Update', 'Request Purpose',
                'Timeline (Days)', 'Expected Return Date', 'Number of Pages', 'Notes/Remarks', 'Office Details', 'Requester Unit', 'File Requester',
                'Sending Officer', 'File Log Session', 'Log ID', 'Log In Time', 'Log Out Date', 'Save File Log'])
            ->assertSee('Current Date &amp; Time', false)->assertSee('Quick Actions')
            ->assertSeeInOrder(['All File Tracker Logs', 'My Files', 'Awaiting Acceptance', "Other Handler&#039;s Files", 'Completed', 'Control Panel', 'File Log Table'], false);
    }

    public function test_a_file_in_transit_needs_the_override_then_is_logged_out_to_the_officer(): void
    {
        $this->intakeFile(); // commissioned: on its way to the MD KAMMA

        $this->logFile()->assertSessionHasErrors('file_number');

        $this->logFile(['override_in_transit' => '1'])->assertSessionHasNoErrors()->assertRedirect(route('tracking.create', ['tab' => 'log']));
        $t = FileTracker::sole();
        $this->assertSame('in_transit', $t->status);
        $this->assertSame($this->unit('Zonal Office'), (int) $t->to_unit_id);
        $this->assertSame('Musa Ali', $t->holder_name);
        $this->assertSame('High', $t->priority);
        $this->assertSame(42, (int) $t->num_pages);
        $this->assertSame('2026-11-29', $t->due_on->toDateString());
        $log = $t->movements()->whereNotNull('log_id')->sole();
        $this->assertSame(['sent', 'LOG-20261122101500-123', 'FOR KSI (SITE INSPECTION)', 7], [$log->action, $log->log_id, $log->purpose, (int) $log->timeline_days]);
    }

    public function test_an_untracked_file_starts_in_its_origin_unit_and_log_in_records_arrival(): void
    {
        $f = $this->untrackedFile();

        $this->logFile(['file_number' => $f->file_number, 'origin_unit_id' => $this->unit('Registry'), 'status_update' => 'log-in', 'to_unit_id' => $this->unit('Department'), 'purpose' => 'OTHER', 'purpose_other' => 'counter check'])
            ->assertSessionHasNoErrors();
        $t = FileTracker::sole();
        $this->assertSame('in_unit', $t->status);
        $this->assertSame($this->unit('Department'), (int) $t->current_unit_id);
        $this->assertSame(['logged', 'received'], $t->movements()->orderBy('id')->pluck('action')->all());
        $this->assertSame('COUNTER CHECK', $t->movements()->latest('id')->first()->purpose);
    }

    public function test_the_form_rejects_missing_or_unknown_values(): void
    {
        $this->intakeFile();

        $this->logFile(['holder_user_id' => '', 'num_pages' => '', 'log_id' => 'bad', 'purpose' => 'NOT A PURPOSE', 'override_in_transit' => '1'])
            ->assertSessionHasErrors(['holder_user_id', 'num_pages', 'log_id']);
        $this->logFile(['purpose' => 'NOT A PURPOSE', 'override_in_transit' => '1'])->assertSessionHasErrors('purpose');
        $this->logFile(['file_number' => 'RES-2099-99'])->assertSessionHasErrors('file_number');
    }

    public function test_the_log_tab_groups_files_by_handler(): void
    {
        $this->intakeFile();
        $this->logFile(['override_in_transit' => '1', 'holder_user_id' => $this->officer->id]); // sent to me: awaiting my acceptance

        $this->actingAs($this->officer)->getJson('/file-tracking/log/list')
            ->assertOk()->assertJson(['counts' => ['awaiting' => 1, 'mine' => 0, 'others' => 0], 'me' => 'Amina Bello'])
            ->assertJsonPath('rows.0.status', 'In-transit')->assertJsonPath('rows.0.timeline', 'green')->assertJsonPath('rows.0.log_id', 'LOG-20261122101500-123');

        $this->actingAs($this->officer)->post(route('tracking.receive', FileTracker::sole()));
        $this->actingAs($this->officer)->getJson('/file-tracking/log/list')->assertJson(['counts' => ['mine' => 1, 'awaiting' => 0]]);
        $this->actingAs($this->colleague)->getJson('/file-tracking/log/list')->assertJson(['counts' => ['others' => 1]]);

        $this->travelTo(now()->setDate(2026, 12, 5));
        $this->actingAs($this->officer)->getJson('/file-tracking/log/list')->assertJsonPath('rows.0.timeline', 'red');
    }

    public function test_officers_come_from_klaes_accounts(): void
    {
        $this->actingAs($this->officer)->getJson('/file-tracking/log/officers?q=mus')
            ->assertOk()->assertJsonPath('officers.0.name', 'Musa Ali')->assertJsonPath('officers.0.rank', 'Town Planner II')->assertJsonCount(1, 'officers');
    }
}
