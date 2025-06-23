<?php

class AppException extends Exception
{
    protected $errorType;
    protected $httpCode;
    protected $details;

    public function __construct(
        string $message = "",
        int $code = 0,
        string $errorType = 'SERVER',
        int $httpCode = 500,
        array $details = null,
        Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorType = $errorType;
        $this->httpCode = $httpCode;
        $this->details = $details;
    }

    public function getErrorType(): string
    {
        return $this->errorType;
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    public function getDetails(): ?array
    {
        return $this->details;
    }
}