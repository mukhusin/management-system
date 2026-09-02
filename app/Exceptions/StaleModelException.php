<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Thrown when an optimistic-lock update is attempted against a row whose
 * lock_version has moved on — i.e. someone else saved first.
 */
class StaleModelException extends RuntimeException
{
    public function __construct(
        public readonly string $model,
        public readonly int|string|null $id = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            'This record was changed by someone else while you were editing. Reload and try again.',
            409,
            $previous,
        );
    }
}
