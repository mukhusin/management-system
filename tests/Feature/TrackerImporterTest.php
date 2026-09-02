<?php

namespace Tests\Feature;

use App\Models\TrackerItem;
use App\Models\User;
use App\Services\Import\TrackerCsvImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackerImporterTest extends TestCase
{
    use RefreshDatabase;

    private string $csv;

    protected function setUp(): void
    {
        parent::setUp();

        $this->csv = <<<'CSV'
        ID,Date,Category,Title,Description,Responsible Person,Priority,Status,Progress %,Next Action,Due Date,Remarks / Outcome
        EMREC-001,30.08.2026,Digital Products,Public Notice,Inform the public,Maalim,High,Ongoing,90%,Finish features,06.09.2026,
        EMREC-003,30.08.2026,Agreements & Partnerships,CCRC,China Construction,"Dr. Simba, Dr. Sanga",Medium,Ongoing,5%,Submit letters,Unspecified,
        EMREC-005,30.08.2026,New Advertised Works,,,,,,,,,
        CSV;
    }

    private function write(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'trk').'.csv';
        file_put_contents($path, $this->csv);

        return $path;
    }

    public function test_it_parses_rows_and_skips_blank_titles(): void
    {
        User::factory()->create(['email' => 'maalim@emrec.co.tz', 'name' => 'Maalim']);

        $summary = (new TrackerCsvImporter())->import($this->write())->summary();

        $this->assertSame(2, $summary['created']);
        $this->assertSame(1, $summary['skipped']);

        $one = TrackerItem::where('reference', 'EMREC-001')->first();
        $this->assertSame(90, $one->progress);
        $this->assertSame('2026-09-06', $one->due_date->toDateString());
        $this->assertSame('digital_product', $one->category->value);

        $three = TrackerItem::where('reference', 'EMREC-003')->first();
        $this->assertNull($three->due_date); // "Unspecified"
        $this->assertSame('partnership', $three->category->value);
    }

    public function test_re_running_is_idempotent(): void
    {
        (new TrackerCsvImporter())->import($this->write());
        $summary = (new TrackerCsvImporter())->import($this->write())->summary();

        $this->assertSame(0, $summary['created']);
        $this->assertSame(2, $summary['updated']);
        $this->assertSame(2, TrackerItem::count());
    }

    public function test_unmatched_people_are_reported(): void
    {
        $summary = (new TrackerCsvImporter())->import($this->write())->summary();

        $this->assertContains('Maalim', $summary['unmatched_people']);
    }
}
