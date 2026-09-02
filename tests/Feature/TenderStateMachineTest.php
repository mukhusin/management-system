<?php

namespace Tests\Feature;

use App\Enums\TenderState;
use App\Enums\UserRole;
use App\Exceptions\InvalidTransitionException;
use App\Models\AuditLog;
use App\Models\Tender;
use App\Models\User;
use App\Services\TenderStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenderStateMachineTest extends TestCase
{
    use RefreshDatabase;

    private function machine(): TenderStateMachine
    {
        return app(TenderStateMachine::class);
    }

    public function test_legal_path_draft_to_won(): void
    {
        $actor = User::factory()->role(UserRole::TenderOfficer)->create();
        $tender = Tender::factory()->create(['state' => TenderState::Draft]);

        foreach ([TenderState::UnderReview, TenderState::Submitted, TenderState::Won] as $to) {
            $this->machine()->apply($tender, $to, $actor);
        }

        $this->assertSame(TenderState::Won, $tender->fresh()->state);
        $this->assertSame(3, AuditLog::where('event', 'state_changed')->where('auditable_id', $tender->id)->count());
    }

    public function test_illegal_transition_is_rejected(): void
    {
        $actor = User::factory()->admin()->create();
        $tender = Tender::factory()->create(['state' => TenderState::Draft]);

        $this->expectException(InvalidTransitionException::class);
        $this->machine()->apply($tender, TenderState::Won, $actor);
    }

    public function test_each_transition_records_from_and_to(): void
    {
        $actor = User::factory()->admin()->create();
        $tender = Tender::factory()->create(['state' => TenderState::Draft]);

        $this->machine()->apply($tender, TenderState::UnderReview, $actor);

        $log = AuditLog::where('event', 'state_changed')->latest('id')->first();
        $this->assertSame('draft', $log->old_values['state']);
        $this->assertSame('under_review', $log->new_values['state']);
    }

    public function test_transition_endpoint_respects_permission(): void
    {
        $tender = Tender::factory()->create(['state' => TenderState::Draft]);

        $this->actingAs(User::factory()->role(UserRole::DevMember)->create())
            ->patch("/tenders/{$tender->id}/transition", ['state' => 'under_review'])
            ->assertForbidden();

        $this->assertSame(TenderState::Draft, $tender->fresh()->state);
    }
}
