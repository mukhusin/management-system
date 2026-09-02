<?php

namespace Tests\Unit;

use App\Enums\Priority;
use App\Enums\ProjectPhase;
use App\Enums\ServiceRequestState;
use App\Enums\TaskStatus;
use App\Enums\TenderState;
use App\Enums\TrackerCategory;
use App\Enums\UserRole;
use PHPUnit\Framework\TestCase;

class EnumTest extends TestCase
{
    public function test_every_enum_case_has_a_label_and_color(): void
    {
        $enums = [Priority::class, TenderState::class, ServiceRequestState::class, TaskStatus::class, TrackerCategory::class, UserRole::class, ProjectPhase::class];

        foreach ($enums as $enum) {
            foreach ($enum::cases() as $case) {
                $this->assertNotEmpty($case->label());
                $this->assertNotEmpty($case->color());
            }
            $this->assertCount(count($enum::cases()), $enum::options());
            $this->assertSame(array_column($enum::cases(), 'value'), $enum::values());
        }
    }

    public function test_tender_state_transitions(): void
    {
        $this->assertTrue(TenderState::Draft->canTransitionTo(TenderState::UnderReview));
        $this->assertFalse(TenderState::Draft->canTransitionTo(TenderState::Won));
        $this->assertTrue(TenderState::Won->isTerminal());
        $this->assertFalse(TenderState::Submitted->isTerminal());
    }

    public function test_project_phase_progression(): void
    {
        $this->assertSame(ProjectPhase::SystemDesign, ProjectPhase::Requirements->next());
        $this->assertNull(ProjectPhase::Deployment->next());
    }

    public function test_task_status_implied_progress(): void
    {
        $this->assertSame(0, TaskStatus::Todo->impliedProgress());
        $this->assertSame(100, TaskStatus::Done->impliedProgress());
        $this->assertNull(TaskStatus::InProgress->impliedProgress());
    }
}
