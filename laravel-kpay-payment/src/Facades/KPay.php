<?php

namespace KPay\LaravelKPayment\Facades;

use Illuminate\Support\Facades\Facade;
use KPay\LaravelKPayment\Services\KPayService;

class KPay extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'kpay';
    }
}
