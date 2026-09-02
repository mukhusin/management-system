<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Enums\ServiceRequestState;
use App\Enums\TenderState;
use App\Models\Project;
use App\Models\ServiceLine;
use App\Models\ServiceRequest;
use App\Models\Task;
use App\Models\Tender;
use App\Models\User;

class ReportController extends Controller
{
    public function index()
    {
        $tenderFunnel = $this->funnel(Tender::class, TenderState::cases());
        $requestFunnel = $this->funnel(ServiceRequest::class, ServiceRequestState::cases());

        $tenderWon = Tender::where('state', TenderState::Won->value)->count();
        $tenderLost = Tender::where('state', TenderState::Lost->value)->count();
        $tenderWinRate = ($tenderWon + $tenderLost) ? round($tenderWon / ($tenderWon + $tenderLost) * 100) : null;

        $requestWon = ServiceRequest::whereIn('state', [ServiceRequestState::Won->value, ServiceRequestState::Engaged->value])->count();
        $requestLost = ServiceRequest::where('state', ServiceRequestState::Lost->value)->count();
        $requestWinRate = ($requestWon + $requestLost) ? round($requestWon / ($requestWon + $requestLost) * 100) : null;

        $projectsByStatus = Project::selectRaw('status, count(*) n, round(avg(progress)) avg_progress')
            ->groupBy('status')->get()->keyBy('status');

        $workload = User::withCount(['assignedTasks as open_tasks' => fn ($q) => $q->where('status', '!=', 'done')])
            ->orderByDesc('open_tasks')->get();

        $overdue = Task::open()->whereDate('due_date', '<', now())
            ->with('assignee', 'featureSet.milestone.project')->orderBy('due_date')->get();

        $serviceLines = ServiceLine::withCount(['tenders', 'serviceRequests', 'projects'])
            ->withSum('projects as pipeline_budget', 'budget')->orderBy('position')->get();

        return view('reports.index', compact(
            'tenderFunnel', 'requestFunnel', 'tenderWinRate', 'requestWinRate',
            'projectsByStatus', 'workload', 'overdue', 'serviceLines',
        ) + ['projectStatuses' => ProjectStatus::cases()]);
    }

    private function funnel(string $model, array $states): array
    {
        $counts = $model::selectRaw('state, count(*) n')->groupBy('state')->pluck('n', 'state');

        return collect($states)->map(fn ($s) => [
            'label' => $s->label(),
            'n' => (int) ($counts[$s->value] ?? 0),
        ])->all();
    }
}
