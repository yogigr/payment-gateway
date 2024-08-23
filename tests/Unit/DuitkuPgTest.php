<?php

namespace yogigr\PaymentGateway\Tests\Unit;

use Mockery;
use Illuminate\Http\Request;
use yogigr\PaymentGateway\DuitkuPg;
use yogigr\PaymentGateway\Tests\TestCase;

class DuitkuPgTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_invoice_successful()
    {
        $config = [
            'merchant_key' => 'test_merchant_key',
            'merchant_code' => 'test_merchant_code',
            'sandbox_mode' => true,
            'sanitized_mode' => false,
            'log' => false,
        ];

        $mockApi = Mockery::mock('alias:Duitku\Api');
        $mockApi->shouldReceive('createInvoice')
            ->once()
            ->andReturn(json_encode([
                'statusCode' => '00',
                'paymentUrl' => 'https://example.com/payment'
            ]));

        $duitkuPg = new DuitkuPg($config);

        $data = [
            'amount' => 100000,
            'email' => 'test@example.com',
            'phone' => '628123456789',
            'merchant_order_id' => 'ORD-12345',
            'invoice_id' => 1,
            'customer_name' => 'John Doe',
        ];

        $response = $duitkuPg->createInvoice($data, 'credit_card');

        $this->assertEquals('00', $response->statusCode);
        $this->assertEquals('https://example.com/payment', $response->paymentUrl);
    }

    public function test_check_transaction_successful()
    {
        $config = [
            'merchant_key' => 'test_merchant_key',
            'merchant_code' => 'test_merchant_code',
            'sandbox_mode' => true,
            'sanitized_mode' => false,
            'log' => false
        ];

        $mockApi = Mockery::mock('alias:Duitku\Api');
        $mockApi->shouldReceive('transactionStatus')
            ->once()
            ->with('ORD-12345', Mockery::type('Duitku\Config'))
            ->andReturn(json_encode([
                'statusCode' => '00',
                'merchantOrderId' => 'ORD-12345'
            ]));

        $duitkuPg = new DuitkuPg($config);

        // No need to decode as the response is already an object
        $response = $duitkuPg->checkTransaction('ORD-12345');

        $this->assertEquals('00', $response->statusCode);
        $this->assertEquals('ORD-12345', $response->merchantOrderId);
    }

    public function test_callback_successful()
    {
        $config = [
            'merchant_key' => 'test_merchant_key',
            'merchant_code' => 'test_merchant_code',
            'sandbox_mode' => true,
            'sanitized_mode' => false,
            'log' => false
        ];

        // Mock class Duitku\Pop
        $mockApi = Mockery::mock('alias:Duitku\Pop');
        $mockApi->shouldReceive('callback')
            ->once()
            ->andReturn(json_encode([
                'resultCode' => '00',
                'signature' => 'valid_signature'
            ]));

        // Mock metode getPaymentMethodOptions
        $mockDuitkuPg = Mockery::mock(DuitkuPg::class . '[getPaymentMethodOptions]', [$config]);
        $mockDuitkuPg->shouldReceive('getPaymentMethodOptions')
            ->once()
            ->andReturn([
                'credit_card' => 'Credit Card',
                'bank_transfer' => 'Bank Transfer'
            ]);

        // Mock class Request
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('input')
            ->with('signature')
            ->andReturn('valid_signature');
        $request->shouldReceive('input')
            ->with('additionalParam')
            ->andReturn('1');
        $request->shouldReceive('input')
            ->with('amount')
            ->andReturn('100000');
        $request->shouldReceive('input')
            ->with('paymentCode')
            ->andReturn('credit_card');
        $request->shouldReceive('input')
            ->with('reference')
            ->andReturn('REF123');
        $request->shouldReceive('input')
            ->with('merchantCode')
            ->andReturn('test_merchant_code');
        $request->shouldReceive('input')
            ->with('merchantOrderId')
            ->andReturn('ORD-12345');
        $request->shouldReceive('input')
            ->with('publisherOrderId')
            ->andReturn('PUB123');

        // Panggil metode callback
        $response = $mockDuitkuPg->callback($request);

        // Assert response
        $this->assertEquals('00', $response['resultCode']);
    }
}
