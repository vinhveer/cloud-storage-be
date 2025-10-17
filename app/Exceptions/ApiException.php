<?php

namespace App\Exceptions;

use Exception;

class ApiException extends Exception
{
    public function __construct(
        string $message,
        public readonly int $status = 400,
        public readonly ?string $codeStr = null,
        public readonly ?array $errors = null
    ) {
        parent::__construct($message, $status);
    }
}


