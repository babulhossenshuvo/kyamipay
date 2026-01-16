<?php

namespace KPay\LaravelKPayment\Exceptions;

class AuthenticationException extends KPayException
{
    public function __construct(string $message = 'Authentication failed')
    {
        parent::__construct($message, 401);
    }
}
