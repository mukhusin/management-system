<?php

namespace App\Services;

use App\Enums\ProjectPhase;
use App\Enums\ProjectType;
use App\Enums\ServiceRequestState;
use App\Enums\TenderState;
use App\Models\Project;
use App\Models\ServiceLine;
use App\Models\ServiceRequest;
use App\Models\Tender;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * One-click project initiation (SRS PR-1 / PR-2). A won Tender or a won
 * ServiceRequest becomes a Project with its client / scope / budget /
 * deadline inherited from the parent.
 */
class ProjectInitiator
{
    public function __construct(private ServiceRequestStateMachine $serviceRequests)
    {
    }

    public function fromTender(Tender $tender, User $actor): Project
    {
        if ($tender->state !== TenderState::Won) {
            throw ValidationException::withMessages(['state' => 'Only a won tender can be promoted to a project.']);
        }

        if ($tender->project()->exists()) {
            throw ValidationException::withMessages(['project' => 'This tender already has a project.']);
        }

        return DB::transaction(function () use ($tender, $actor) {
            $project = Project::create([
                'tender_id' => $tender->id,
                'service_line_id' => $tender->service_line_id,
                'owner_id' => $tender->owner_id ?? $actor->id,
                'name' => $tender->title,
                'type' => $this->typeFor($tender->serviceLine),
                'description' => $tender->description,
                'scope_statement' => $tender->scope_statement,
                'client' => $tender->client ?? $tender->buyer,
                'budget' => $tender->estimated_value ?? $tender->value,
                'currency' => $tender->currency,
                'target_deadline' => $tender->deadline_date,
                'current_phase' => null,
            ]);

            $this->applyPhaseDefault($project);

            $tender->audit('project_initiated', null, ['project_id' => $project->id]);
            $project->audit('project_initiated', ['origin' => 'tender'], ['tender_id' => $tender->id]);

            return $project;
        });
    }

    public function fromServiceRequest(ServiceRequest $request, User $actor): Project
    {
        if ($request->state !== ServiceRequestState::Won) {
            throw ValidationException::withMessages(['state' => 'Only a won service request can be promoted to a project.']);
        }

        if ($request->project()->exists()) {
            throw ValidationException::withMessages(['project' => 'This request already has a project.']);
        }

        return DB::transaction(function () use ($request, $actor) {
            $project = Project::create([
                'service_request_id' => $request->id,
                'service_line_id' => $request->service_line_id,
                'owner_id' => $request->owner_id ?? $actor->id,
                'name' => $request->summary,
                'type' => $this->typeFor($request->serviceLine),
                'description' => $request->details,
                'scope_statement' => $request->summary,
                'client' => $request->client ?? $request->contact_name,
                'budget' => $request->estimated_value,
                'currency' => $request->currency,
                'current_phase' => null,
            ]);

            $this->applyPhaseDefault($project);

            $request->audit('project_initiated', null, ['project_id' => $project->id]);
            $project->audit('project_initiated', ['origin' => 'service_request'], ['service_request_id' => $request->id]);

            $this->serviceRequests->apply($request, ServiceRequestState::Engaged, $actor);

            return $project;
        });
    }

    private function typeFor(?ServiceLine $line): ProjectType
    {
        return str_contains(strtolower($line->name ?? ''), 'technology')
            || str_contains(strtolower($line->name ?? ''), 'ai')
            ? ProjectType::Sdlc
            : ProjectType::Engagement;
    }

    private function applyPhaseDefault(Project $project): void
    {
        if ($project->type === ProjectType::Sdlc) {
            $project->forceFill(['current_phase' => ProjectPhase::Requirements->value])->save();
        }
    }
}
