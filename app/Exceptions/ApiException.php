<?php

namespace App\Exceptions;

use Exception;

class ApiException extends Exception
{
    /**
     * @param  array<string, mixed>  $errors
     */
    public function __construct(
        string $message,
        public readonly int $status = 400,
        public readonly array $errors = [],
    ) {
        parent::__construct($message);
    }
}
