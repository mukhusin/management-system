<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Tender;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenderCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_officer_registers_a_tender_without_a_url(): void
    {
        $this->actingAs(User::factory()->role(UserRole::TenderOfficer)->create())
            ->post('/tenders', ['title' => 'Offline win', 'priority' => 'high'])
            ->assertRedirect();

        $this->assertDatabaseHas('tenders', ['title' => 'Offline win', 'source' => 'manual']);
    }

    public function test_url_when_present_seeds_a_stable_external_id(): void
    {
        $this->actingAs(User::factory()->role(UserRole::TenderOfficer)->create())
            ->post('/tenders', ['title' => 'With link', 'priority' => 'medium', 'url' => 'https://example.org/notice/1']);

        $this->assertSame(sha1('https://example.org/notice/1'), Tender::firstOrFail()->external_id);
    }

    public function test_updating_a_baseline_field_without_permission_is_blocked(): void
    {
        $tender = Tender::factory()->create(['value' => 100, 'currency' => 'USD']);
        $editor = User::factory()->role(UserRole::TenderOfficer)->create();
        $editor->overrides()->create(['permission' => 'tenders.edit_baseline', 'granted' => false]);

        $this->actingAs($editor)->put("/tenders/{$tender->id}", [
            'title' => $tender->title,
            'priority' => 'medium',
            'value' => 999999,
            'lock_version' => $tender->lock_version,
        ])->assertSessionHas('error');

        $this->assertSame('100.00', $tender->fresh()->value);
    }

    public function test_baseline_edit_is_audited(): void
    {
        $tender = Tender::factory()->create(['value' => 100]);

        $this->actingAs(User::factory()->admin()->create())->put("/tenders/{$tender->id}", [
            'title' => $tender->title,
            'priority' => 'medium',
            'value' => 250,
            'lock_version' => $tender->lock_version,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $tender->id,
            'event' => 'baseline_changed',
        ]);
    }
}
