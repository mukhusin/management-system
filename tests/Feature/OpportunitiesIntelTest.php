<?php

namespace Tests\Feature;

use App\Models\Tender;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunitiesIntelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_save_and_unsave_an_opportunity(): void
    {
        $opp = Tender::factory()->opportunity()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->patch("/opportunities/{$opp->id}/save")->assertRedirect();
        $this->assertTrue($opp->fresh()->isSavedBy($user));

        $this->actingAs($user)->patch("/opportunities/{$opp->id}/save")->assertRedirect();
        $this->assertFalse($opp->fresh()->isSavedBy($user));
    }

    public function test_dismissing_an_opportunity_hides_it_only_for_that_user(): void
    {
        $opp = Tender::factory()->opportunity()->create(['title' => 'Noisy notice']);
        $a = User::factory()->create();
        $b = User::factory()->create();

        $this->actingAs($a)->patch("/opportunities/{$opp->id}/dismiss");

        $this->actingAs($a)->get('/opportunities?open_only=0')->assertDontSee('Noisy notice');
        $this->actingAs($b)->get('/opportunities?open_only=0')->assertSee('Noisy notice');

        // The dismissing user can bring it back with show_hidden.
        $this->actingAs($a)->get('/opportunities?open_only=0&show_hidden=1')->assertSee('Noisy notice');
    }

    public function test_saved_only_filter_shows_just_my_shortlist(): void
    {
        $mine = Tender::factory()->opportunity()->create(['title' => 'Mine']);
        $other = Tender::factory()->opportunity()->create(['title' => 'Not mine']);
        $user = User::factory()->create();
        $mine->toggleSavedBy($user);

        $this->actingAs($user)->get('/opportunities?open_only=0&saved_only=1')
            ->assertSee('Mine')->assertDontSee('Not mine');
    }

    public function test_closing_within_days_filters_the_feed(): void
    {
        Tender::factory()->opportunity()->create(['title' => 'Soon', 'deadline_date' => now()->addDays(5)]);
        Tender::factory()->opportunity()->create(['title' => 'Later', 'deadline_date' => now()->addDays(60)]);

        $this->actingAs(User::factory()->create())
            ->get('/opportunities?open_only=0&closing_within=7')
            ->assertSee('Soon')->assertDontSee('Later');
    }

    public function test_dashboard_renders_with_opportunities_intel(): void
    {
        Tender::factory()->opportunity()->count(3)->create(['country' => 'Tanzania', 'buyer' => 'Ministry of Works']);

        $this->actingAs(User::factory()->create())->get('/')
            ->assertOk()->assertSee('Opportunities by country')->assertSee('Top procuring entities');
    }
}
