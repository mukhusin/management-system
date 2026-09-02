<?php

namespace Tests\Feature;

use App\Exceptions\StaleModelException;
use App\Models\Tender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OptimisticLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_second_writer_with_a_stale_version_is_rejected(): void
    {
        $tender = Tender::factory()->create(['title' => 'Original']);
        $version = $tender->lock_version;

        // First editor wins.
        $tender->updateWithLock(['title' => 'First edit'], $version);
        $this->assertSame(1, $tender->fresh()->lock_version);

        // Second editor still holds the old version.
        $stale = Tender::find($tender->id);
        $this->expectException(StaleModelException::class);
        $stale->updateWithLock(['title' => 'Second edit'], $version);
    }

    public function test_rejected_write_does_not_change_the_row(): void
    {
        $tender = Tender::factory()->create(['title' => 'Original']);
        $tender->updateWithLock(['title' => 'First edit'], 0);

        try {
            Tender::find($tender->id)->updateWithLock(['title' => 'Loser'], 0);
        } catch (StaleModelException) {
            // expected
        }

        $this->assertSame('First edit', $tender->fresh()->title);
    }
}
