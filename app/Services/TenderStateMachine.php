<?php

namespace App\Services;

use App\Enums\TenderState;
use App\Exceptions\InvalidTransitionException;
use App\Models\Tender;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Enforces the tender lifecycle (SRS TR-4) and records every move in the
 * immutable audit log (SRS TR-5).
 */
class TenderStateMachine
{
    public function apply(Tender $tender, TenderState $to, User $actor, ?string $note = null): Tender
    {
        $from = $tender->state ?? TenderState::Draft;

        if (! $from->canTransitionTo($to)) {
            throw new InvalidTransitionException($from->label(), $to->label());
        }

        return DB::transaction(function () use ($tender, $from, $to, $actor, $note) {
            $tender->updateWithLock(['state' => $to->value], (int) $tender->lock_version);

            $tender->audit('state_changed', ['state' => $from->value], ['state' => $to->value]);

            if (filled($note)) {
                $tender->addComment($actor, $note);
            }

            return $tender->refresh();
        });
    }
}
