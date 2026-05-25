<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Domain-level error. Always carries a machine-readable error code (e.g. RETIREMENT_CAP_EXCEEDED),
 * a human-friendly message in Bahasa Indonesia, and optional structured details for the UI.
 */
class BusinessException extends RuntimeException
{
    protected string $errorCode;
    protected array  $details;
    protected int    $httpStatus;

    public function __construct(string $errorCode, string $message, array $details = [], int $httpStatus = 422)
    {
        parent::__construct($message);
        $this->errorCode  = $errorCode;
        $this->details    = $details;
        $this->httpStatus = $httpStatus;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function details(): array
    {
        return $this->details;
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }
}
