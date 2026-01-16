<?php

namespace KPay\LaravelKPayment\Exceptions;

use Exception;

class KPayException extends Exception
{
    /**
     * Create a new exception instance.
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Report the exception.
     */
    public function report()
    {
        //
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render()
    {
        return response()->json([
            'status' => false,
            'message' => $this->message,
            'code' => $this->code,
        ], 400);
    }
}
