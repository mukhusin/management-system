<?php

namespace Tests\Feature;

use App\Enums\TenderState;
use App\Enums\UserRole;
use App\Models\Tender;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenderOpportunitiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_opportunities_and_pipeline_are_separate_lists(): void
    {
        $opp = Tender::factory()->opportunity()->create(['title' => 'Unclaimed WB notice']);
        $adopted = Tender::factory()->create(['title' => 'Pursued bid']);

        $user = User::factory()->role(UserRole::TenderOfficer)->create();

        $this->actingAs($user)->get('/opportunities')
            ->assertOk()->assertSee('Unclaimed WB notice')->assertDontSee('Pursued bid');

        $this->actingAs($user)->get('/tenders')
            ->assertOk()->assertSee('Pursued bid')->assertDontSee('Unclaimed WB notice');
    }

    public function test_pursuing_an_opportunity_moves_it_to_the_pipeline(): void
    {
        $opp = Tender::factory()->opportunity()->create();
        $officer = User::factory()->role(UserRole::TenderOfficer)->create();

        $this->actingAs($officer)
            ->patch("/opportunities/{$opp->id}/pursue")
            ->assertRedirect(route('tenders.show', $opp));

        $opp->refresh();
        $this->assertTrue($opp->isAdopted());
        $this->assertSame($officer->id, $opp->adopted_by);
        $this->assertTrue($opp->owners->contains($officer->id));
        $this->assertDatabaseHas('audit_logs', ['auditable_id' => $opp->id, 'event' => 'adopted']);
    }

    public function test_an_opportunity_cannot_be_transitioned_before_it_is_pursued(): void
    {
        $opp = Tender::factory()->opportunity()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch("/tenders/{$opp->id}/transition", ['state' => TenderState::UnderReview->value])
            ->assertStatus(409);

        $this->assertSame(TenderState::Draft, $opp->fresh()->state);
    }

    public function test_a_pursued_opportunity_cannot_be_pursued_again(): void
    {
        $adopted = Tender::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch("/opportunities/{$adopted->id}/pursue")
            ->assertStatus(409);
    }

    public function test_manually_registered_tenders_land_straight_in_the_pipeline(): void
    {
        $officer = User::factory()->role(UserRole::TenderOfficer)->create();

        $this->actingAs($officer)->post('/tenders', [
            'title' => 'Offline win', 'priority' => 'high',
        ]);

        $tender = Tender::firstOrFail();
        $this->assertTrue($tender->isAdopted());
        $this->assertSame('manual', $tender->source);
    }

    public function test_a_dev_member_cannot_pursue(): void
    {
        $opp = Tender::factory()->opportunity()->create();

        $this->actingAs(User::factory()->role(UserRole::DevMember)->create())
            ->patch("/opportunities/{$opp->id}/pursue")
            ->assertForbidden();
    }
}
