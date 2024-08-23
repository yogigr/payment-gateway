<?php

namespace yogigr\PaymentGateway\Tests\Unit;

use Mockery;
use Illuminate\Http\Request;
use yogigr\PaymentGateway\FinpayPg;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use yogigr\PaymentGateway\Tests\TestCase;

class FinpayPgTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_create_invoice_successful()
    {
        $config = [
            'merchant_code' => 'test_merchant_code',
            'merchant_key' => 'test_merchant_key',
            'payment_url' => 'https://finpay.com/api',
        ];

        Http::fake([
            'https://finpay.com/api/pg/payment/card/initiate' => Http::response([
                'responseCode' => '2000000',
                'redirecturl' => 'https://example.com/payment'
            ], 200)
        ]);

        $finpayPg = new FinpayPg($config);

        $data = [
            'amount' => 100000,
            'email' => 'test@example.com',
            'customer_name' => 'John Doe',
            'merchant_order_id' => 'ORD-12345',
            'invoice_id' => 1,
            'phone' => '628100001111'
        ];

        $response = $finpayPg->createInvoice($data);

        $this->assertEquals('00', $response->statusCode);
        $this->assertEquals('https://example.com/payment', $response->paymentUrl);
    }

    public function test_check_transaction_successful()
    {
        $config = [
            'merchant_code' => 'test_merchant_code',
            'merchant_key' => 'test_merchant_key',
            'payment_url' => 'https://finpay.com/api',
        ];

        Http::fake([
            'https://finpay.com/api/pg/payment/card/check/ORD-12345' => Http::response([
                'responseCode' => '2000000',
                'data' => [
                    'order' => ['id' => 'ORD-12345'],
                    'result' => ['payment' => ['status' => 'PAID']]
                ]
            ], 200)
        ]);

        $finpayPg = new FinpayPg($config);

        $response = $finpayPg->checkTransaction('ORD-12345');

        $this->assertEquals('00', $response->statusCode);
        $this->assertEquals('ORD-12345', $response->merchantOrderId);
    }

    public function test_callback_successfull()
    {
        // Set config value
        $key = 'test_merchant_key';
        Config::set('payment.gateways.finpay.merchant_key', $key);

        $config = [
            'merchant_code' => 'test_merchant_code',
            'merchant_key' => $key,
            'payment_url' => 'https://finpay.com/api',
        ];

        // Mock the request
        $request = Mockery::mock(Request::class);

        $data = [
            'result' => [
                'payment' => [
                    'status' => 'PAID',
                    'amount' => '100000',
                    'reference' => 'reference123'
                ]
            ],
            'meta' => [
                'data' => [
                    'invoice_id' => '12345'
                ]
            ],
            'sourceOfFunds' => [
                'type' => 'credit_card',
                'paymentCode' => 'publisher_order_id'
            ],
            'merchant' => [
                'id' => 'test_merchant_code'
            ],
            'order' => [
                'id' => 'ORD-12345'
            ],
            'signature' => hash_hmac('sha512', json_encode([
                'result' => [
                    'payment' => [
                        'status' => 'PAID',
                        'amount' => '100000',
                        'reference' => 'reference123'
                    ]
                ],
                'meta' => [
                    'data' => [
                        'invoice_id' => '12345'
                    ]
                ],
                'sourceOfFunds' => [
                    'type' => 'credit_card',
                    'paymentCode' => 'publisher_order_id'
                ],
                'merchant' => [
                    'id' => 'test_merchant_code'
                ],
                'order' => [
                    'id' => 'ORD-12345'
                ]
            ]), $key)
        ];

        

        $request->shouldReceive('getContent')
            ->andReturn(json_encode($data));

        // Set expectations for input() method
        $request->shouldReceive('input')
            ->with('signature')
            ->andReturn($data['signature']);

        $request->shouldReceive('input')
            ->with('meta.data.invoice_id')
            ->andReturn($data['meta']['data']['invoice_id']);

        $request->shouldReceive('input')
            ->with('result.payment.amount')
            ->andReturn($data['result']['payment']['amount']);

        $request->shouldReceive('input')
            ->with('sourceOfFunds.type')
            ->andReturn($data['sourceOfFunds']['type']);

        $request->shouldReceive('input')
            ->with('result.payment.reference')
            ->andReturn($data['result']['payment']['reference']);

        $request->shouldReceive('input')
            ->with('merchant.id')
            ->andReturn($data['merchant']['id']);

        $request->shouldReceive('input')
            ->with('order.id')
            ->andReturn($data['order']['id']);

        $request->shouldReceive('input')
            ->with('sourceOfFunds.paymentCode')
            ->andReturn($data['sourceOfFunds']['paymentCode']);

        // Create an instance of FinpayPg with the config
        $finpayPg = new FinpayPg($config);

        // Call the callback method
        $response = $finpayPg->callback($request);

        // Assertions
        $this->assertEquals('00', $response['resultCode']);
        $this->assertEquals('12345', $response['payment']['invoice_id']);
        $this->assertEquals('100000', $response['payment']['amount']);
        $this->assertEquals('credit_card', $response['payment']['payment_method']);
        $this->assertEquals('reference123', $response['payment']['reference']);
        $this->assertEquals('test_merchant_code', $response['payment']['merchant_code']);
        $this->assertEquals('ORD-12345', $response['payment']['merchant_order_id']);
        $this->assertEquals('publisher_order_id', $response['payment']['publisher_order_id']);
        $this->assertEquals('paid', $response['payment']['status']);
    }
}
