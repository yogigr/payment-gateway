<?php

namespace yogigr\PaymentGateway;

class PaymentGateway
{
    protected $service;

    public function __construct($gateway = null)
    {
        $gateway = $gateway ?: config('payment.default');
        $config = config("payment.gateways.{$gateway}");

        if ($gateway === 'duitku') {
            $this->service = new DuitkuPg($config);
        } elseif ($gateway === 'finpay') {
            $this->service = new FinpayPg($config);
        } else {
            throw new \Exception("Unsupported payment gateway: {$gateway}");
        }
    }

    public function __call($method, $arguments)
    {
        return call_user_func_array([$this->service, $method], $arguments);
    }

    // Metode untuk memeriksa apakah metode tersedia di service
    public function methodExists($method)
    {
        return method_exists($this->service, $method);
    }
}