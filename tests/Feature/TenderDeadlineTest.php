<?php

namespace Tests\Feature;

use App\Models\Tender;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TenderDeadlineTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function tenderClosing(?string $date): Tender
    {
        return new Tender(['deadline_date' => $date]);
    }

    public function test_no_deadline_returns_null(): void
    {
        $this->assertNull($this->tenderClosing(null)->deadlineCountdown());
        $this->assertFalse($this->tenderClosing(null)->isClosingSoon());
        $this->assertFalse($this->tenderClosing(null)->isClosed());
    }

    public function test_countdown_is_whole_days(): void
    {
        Carbon::setTestNow('2026-08-30 12:00:00');

        // 03 Sep 23:59:59 minus now → ~4.5 days, shown as a whole number.
        $this->assertSame('4 days left', $this->tenderClosing('2026-09-03')->deadlineCountdown());
        // 31 Aug 23:59:59 minus now → ~36h → 1 day.
        $this->assertSame('1 day left', $this->tenderClosing('2026-08-31')->deadlineCountdown());
    }

    public function test_countdown_uses_hours_within_a_day(): void
    {
        Carbon::setTestNow('2026-08-31 06:00:00');

        // deadline day ends 2026-08-31 23:59:59 → ~18h, under a day.
        $this->assertSame('17h left', $this->tenderClosing('2026-08-31')->deadlineCountdown());
    }

    public function test_past_deadline_is_closed(): void
    {
        Carbon::setTestNow('2026-08-30 12:00:00');

        $tender = $this->tenderClosing('2026-08-28');
        $this->assertSame('closed', $tender->deadlineCountdown());
        $this->assertTrue($tender->isClosed());
        $this->assertFalse($tender->isClosingSoon());
    }

    public function test_deadline_day_itself_is_still_open(): void
    {
        Carbon::setTestNow('2026-08-30 09:00:00');

        $tender = $this->tenderClosing('2026-08-30');
        $this->assertFalse($tender->isClosed());
        $this->assertTrue($tender->isClosingSoon());
        $this->assertStringContainsString('left', $tender->deadlineCountdown());
    }

    public function test_closing_soon_window_is_seven_days(): void
    {
        Carbon::setTestNow('2026-08-30 12:00:00');

        $this->assertTrue($this->tenderClosing('2026-09-05')->isClosingSoon());
        $this->assertFalse($this->tenderClosing('2026-09-30')->isClosingSoon());
    }
}
