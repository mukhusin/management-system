<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Project;
use App\Models\ServiceRequest;
use App\Models\Task;
use App\Models\Tender;
use App\Models\TrackerItem;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Maps the {type} route segment used by the polymorphic comment/attachment
 * endpoints to a concrete model.
 */
trait ResolvesSubject
{
    protected array $subjectTypes = [
        'tenders' => Tender::class,
        'service-requests' => ServiceRequest::class,
        'projects' => Project::class,
        'tasks' => Task::class,
        'tracker' => TrackerItem::class,
    ];

    protected function resolveSubject(string $type, int|string $id): Model
    {
        $class = $this->subjectTypes[$type] ?? throw new NotFoundHttpException("Unknown subject: {$type}");

        return $class::findOrFail($id);
    }

    protected function subjectUrl(Model $subject): string
    {
        return match (true) {
            $subject instanceof Tender => route('tenders.show', $subject),
            $subject instanceof ServiceRequest => route('service-requests.show', $subject),
            $subject instanceof Project => route('projects.show', $subject),
            $subject instanceof Task => route('projects.show', $subject->project()),
            $subject instanceof TrackerItem => route('tracker.show', $subject),
            default => url('/'),
        };
    }
}
