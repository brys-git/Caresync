<?php

namespace App\Services\Import;

use RuntimeException;

/**
 * Thrown when a batch commit cannot proceed — either the batch is in the wrong
 * state or individual records still have blocking issues. Carries a per-record
 * error map so the review screen can highlight exactly what needs fixing.
 */
class CommitException extends RuntimeException
{
    /** @var array<string, list<string>> record label => list of error strings */
    public array $errors;

    /**
     * @param array<string, list<string>> $errors
     */
    public function __construct(string $message, array $errors = [])
    {
        parent::__construct($message);
        $this->errors = $errors;
    }
}
