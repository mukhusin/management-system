<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Enums\ServiceRequestState;
use App\Enums\TaskStatus;
use App\Enums\TenderState;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\ServiceRequest;
use App\Models\Task;
use App\Models\Tender;
use App\Models\TrackerItem;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $openTenderStates = [TenderState::Draft, TenderState::UnderReview, TenderState::Submitted];

        $kpis = [
            'Tenders in pipeline' => Tender::adopted()->whereIn('state', array_map(fn ($s) => $s->value, $openTenderStates))->count(),
            'Open opportunities' => Tender::opportunities()->open()->count(),
            'Open service requests' => ServiceRequest::whereNotIn('state', [ServiceRequestState::Engaged->value, ServiceRequestState::Declined->value, ServiceRequestState::Lost->value])->count(),
            'Active projects' => Project::where('status', ProjectStatus::Active->value)->count(),
            'Overdue tasks' => Task::open()->whereDate('due_date', '<', now())->count(),
            'My open tasks' => Task::open()->assignedTo($user->id)->count(),
        ];

        $tenderFunnel = Tender::adopted()->selectRaw('state, count(*) as n')->groupBy('state')->pluck('n', 'state');
        $requestFunnel = ServiceRequest::selectRaw('state, count(*) as n')->groupBy('state')->pluck('n', 'state');

        $myTasks = Task::open()->with('featureSet.milestone.project')
            ->assignedTo($user->id)->orderBy('due_date')->limit(10)->get();
        $myProjects = Project::ownedBy($user->id)
            ->whereIn('status', [ProjectStatus::NotStarted->value, ProjectStatus::Active->value, ProjectStatus::OnHold->value])
            ->get();
        $myTenders = Tender::ownedBy($user->id)
            ->whereIn('state', array_map(fn ($s) => $s->value, $openTenderStates))->get();

        $upcoming = $this->upcomingDue();
        $serviceLineBreakdown = $this->serviceLineBreakdown();
        $recentAudit = AuditLog::with(['user', 'auditable'])
            ->whereNotIn('event', ['created'])
            ->latest('id')->limit(12)->get();

        [$opportunitiesByCountry, $topBuyers, $closingByWeek] = $this->opportunitiesIntel();

        return view('dashboard.index', compact(
            'kpis', 'tenderFunnel', 'requestFunnel', 'myTasks', 'myProjects', 'myTenders',
            'upcoming', 'serviceLineBreakdown', 'recentAudit',
            'opportunitiesByCountry', 'topBuyers', 'closingByWeek',
        ));
    }

    /**
     * A quick read of the external feed: where it's concentrated and what's
     * closing soon — helps a tender officer triage without opening the feed.
     */
    private function opportunitiesIntel(): array
    {
        $byCountry = Tender::opportunities()->open()->whereNotNull('country')
            ->selectRaw('country, count(*) as n')->groupBy('country')
            ->orderByDesc('n')->limit(6)->pluck('n', 'country');

        $topBuyers = Tender::opportunities()->open()->whereNotNull('buyer')
            ->selectRaw('buyer, count(*) as n')->groupBy('buyer')
            ->orderByDesc('n')->limit(8)->pluck('n', 'buyer');

        $weeks = 8;
        $counts = array_fill(0, $weeks, 0);
        Tender::opportunities()->whereNotNull('deadline_date')
            ->where('deadline_date', '>=', now()->toDateString())
            ->pluck('deadline_date')
            ->each(function ($date) use (&$counts, $weeks) {
                $week = (int) floor(now()->diffInDays($date) / 7);
                if ($week < $weeks) {
                    $counts[$week]++;
                }
            });
        $closingByWeek = collect($counts)->map(fn ($n, $i) => [
            'label' => $i === 0 ? 'This wk' : "+{$i}w",
            'n' => $n,
        ]);

        return [$byCountry, $topBuyers, $closingByWeek];
    }

    private function upcomingDue()
    {
        $until = now()->addDays(14);

        $tenders = Tender::adopted()->whereBetween('deadline_date', [now(), $until])
            ->get(['id', 'title', 'deadline_date'])
            ->map(fn ($t) => ['type' => 'Tender', 'label' => $t->title, 'date' => $t->deadline_date, 'url' => route('tenders.show', $t)]);

        $projects = Project::whereBetween('target_deadline', [now(), $until])
            ->get(['id', 'name', 'target_deadline'])
            ->map(fn ($p) => ['type' => 'Project', 'label' => $p->name, 'date' => $p->target_deadline, 'url' => route('projects.show', $p)]);

        $tasks = Task::open()->whereBetween('due_date', [now(), $until])
            ->get(['id', 'title', 'due_date', 'feature_set_id'])
            ->map(fn ($t) => ['type' => 'Task', 'label' => $t->title, 'date' => $t->due_date, 'url' => route('tasks.mine')]);

        return $tenders->concat($projects)->concat($tasks)->sortBy('date')->values();
    }

    private function serviceLineBreakdown()
    {
        return \App\Models\ServiceLine::query()
            ->withCount([
                'tenders',
                'serviceRequests',
                'projects',
            ])
            ->orderBy('position')
            ->get();
    }
}
