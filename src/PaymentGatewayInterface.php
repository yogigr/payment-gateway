<?php

namespace yogigr\PaymentGateway;

use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    public function createInvoice(array $data, $paymentMethod);
    public function checkTransaction($merchantOrderId);
    public function callback(Request $request);
}