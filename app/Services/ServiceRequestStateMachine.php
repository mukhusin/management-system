<?php

namespace App\Services;

use App\Enums\ServiceRequestState;
use App\Exceptions\InvalidTransitionException;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ServiceRequestStateMachine
{
    public function apply(ServiceRequest $request, ServiceRequestState $to, User $actor, ?string $note = null): ServiceRequest
    {
        $from = $request->state ?? ServiceRequestState::New;

        if (! $from->canTransitionTo($to)) {
            throw new InvalidTransitionException($from->label(), $to->label());
        }

        return DB::transaction(function () use ($request, $from, $to, $actor, $note) {
            $request->updateWithLock(['state' => $to->value], (int) $request->lock_version);

            $request->audit('state_changed', ['state' => $from->value], ['state' => $to->value]);

            if (filled($note)) {
                $request->addComment($actor, $note);
            }

            return $request->refresh();
        });
    }
}
