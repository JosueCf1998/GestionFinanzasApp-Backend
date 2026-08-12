<?php

class AuthFlowException extends RuntimeException
{
    public string $errorCode;

    public function __construct(string $message, string $errorCode, int $httpCode = 400)
    {
        parent::__construct($message, $httpCode);
        $this->errorCode = $errorCode;
    }
}
