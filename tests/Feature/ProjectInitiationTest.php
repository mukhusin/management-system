<?php

namespace Tests\Feature;

use App\Enums\ServiceRequestState;
use App\Enums\TenderState;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\ServiceRequest;
use App\Models\Tender;
use App\Models\User;
use App\Services\ProjectInitiator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProjectInitiationTest extends TestCase
{
    use RefreshDatabase;

    public function test_won_tender_promotes_and_inherits_data(): void
    {
        $pm = User::factory()->role(UserRole::ProjectManager)->create();
        $tender = Tender::factory()->won()->create([
            'client' => 'Ministry of Health',
            'estimated_value' => 120000,
            'currency' => 'USD',
            'scope_statement' => 'Build the thing',
            'owner_id' => $pm->id,
        ]);

        $project = app(ProjectInitiator::class)->fromTender($tender, $pm);

        $this->assertSame($tender->id, $project->tender_id);
        $this->assertSame('Ministry of Health', $project->client);
        $this->assertSame('120000.00', $project->budget);
        $this->assertSame('Build the thing', $project->scope_statement);
        $this->assertDatabaseHas('audit_logs', ['auditable_id' => $tender->id, 'event' => 'project_initiated']);
        $this->assertDatabaseHas('audit_logs', ['auditable_id' => $project->id, 'event' => 'project_initiated']);
    }

    public function test_cannot_promote_a_tender_twice(): void
    {
        $pm = User::factory()->role(UserRole::ProjectManager)->create();
        $tender = Tender::factory()->won()->create();

        app(ProjectInitiator::class)->fromTender($tender, $pm);

        $this->expectException(ValidationException::class);
        app(ProjectInitiator::class)->fromTender($tender->fresh(), $pm);
    }

    public function test_cannot_promote_a_tender_that_is_not_won(): void
    {
        $pm = User::factory()->role(UserRole::ProjectManager)->create();
        $tender = Tender::factory()->create(['state' => TenderState::Submitted]);

        $this->expectException(ValidationException::class);
        app(ProjectInitiator::class)->fromTender($tender, $pm);
    }

    public function test_dev_member_cannot_hit_the_promote_endpoint(): void
    {
        $tender = Tender::factory()->won()->create();

        $this->actingAs(User::factory()->role(UserRole::DevMember)->create())
            ->patch("/tenders/{$tender->id}/promote")
            ->assertForbidden();

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_won_service_request_promotes_and_moves_to_engaged(): void
    {
        $pm = User::factory()->role(UserRole::ProjectManager)->create();
        $request = ServiceRequest::factory()->inState(ServiceRequestState::Won)->create();

        $project = app(ProjectInitiator::class)->fromServiceRequest($request, $pm);

        $this->assertSame($request->id, $project->service_request_id);
        $this->assertSame(ServiceRequestState::Engaged, $request->fresh()->state);
    }
}
