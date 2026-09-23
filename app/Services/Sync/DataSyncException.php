<?php

namespace App\Services\Sync;

use RuntimeException;

class DataSyncException extends RuntimeException
{
    public function __construct(string $message, public readonly string $errorCode = 'sync_failed')
    {
        parent::__construct($message);
    }
}
