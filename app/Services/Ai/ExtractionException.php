<?php

namespace App\Services\Ai;

use RuntimeException;

class ExtractionException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $safeCode = 'extraction_failed'
    ) {
        parent::__construct($message);
    }
}
