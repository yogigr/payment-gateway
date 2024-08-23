<?php

namespace yogigr\PaymentGateway\Facades;

use Illuminate\Support\Facades\Facade;

class PaymentGateway extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'paymentgateway';
    }
}