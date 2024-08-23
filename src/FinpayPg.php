<?php

namespace yogigr\PaymentGateway;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FinpayPg implements PaymentGatewayInterface
{
    protected $config;
    protected $http;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->http = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($config['merchant_code'] . ':' . $config['merchant_key'])
        ]);
    }

    public function createInvoice(array $data, $paymentMethod = null)
    {
        [$fist, $last] = splitFullName($data['customer_name'] ?? 'Noname');
        $payload = [
            'customer' => [
                'email' => $data['email'] ?? '',
                "firstName" => $fist,
                "lastName" => $last,
                "mobilePhone" => '+' . $data['phone'] ?? '62'
            ],
            "order" => [
                "id" => $data['merchant_order_id'],
                "amount" => $data['amount'],
                "description" => $data['product_details'] ?? '',
            ],
            "url" => [
                "backUrl" => config('payment.return_domain') . ($data['return_path'] ?? '/app/invoices/' . $data['invoice_id']),
                "callbackUrl" => config('payment.callback_domain') . ($data['callback_path'] ?? '/payment/callback'),
                "successUrl" => config('payment.return_domain') . ($data['success_path'] ?? '/app/invoices/' . $data['invoice_id'] . "?invoice=" . $data['merchant_order_id'] . '&status=success'),
            ],
            "meta" => [
                "data" => [
                    "invoice_id" => $data['invoice_id']
                ]
            ]
        ];
        
        try {
            $response = $this->http->post($this->config['payment_url'] . '/pg/payment/card/initiate', $payload);
            $body = json_decode($response->body());
            if ($body->responseCode == '2000000' || $body->responseCode == '4041014') {
                $result = new \stdClass();
                $result->statusCode = '00';
                $result->paymentUrl = $body->redirecturl;
                return $result;
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 1);
        }
    }

    public function checkTransaction($merchantOrderId)
    {
        try {
            $result = new \stdClass();
            $result->statusCode = '01';

            $response = $this->http->get($this->config['payment_url'] . '/pg/payment/card/check/' . $merchantOrderId);
            $body = json_decode($response->body());
            $data = $body->data;
            $payment = $data->result->payment ?? null;
            if ($body->responseCode === '2000000' && $payment && $payment->status === 'PAID') {
                $result->statusCode = '00';
                $result->merchantOrderId = $data->order->id ?? '';
                $result->reference = $payment->reference ?? '';
                $result->amount = $payment->amount ?? '';
                $result->fee = '';
                $result->statusMessage = $payment->status;
            }
            return $result;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 1);
        }
    }

    public function callback(Request $request)
    {
        try {
            $result = ['resultCode' => '01'];

            // Mendapatkan body JSON mentah
            $rawJson = $request->getContent();

            // Mengonversi JSON mentah menjadi array
            $data = json_decode($rawJson, true);

            // Menyimpan nilai signature yang dikirim oleh Finpay
            $receivedSignature = $data['signature'];

            // Menghapus key 'signature' dari data untuk hashing
            unset($data['signature']);

            // Menggunakan merchant key untuk hashing
            $merchantKey = config('payment.gateways.finpay.merchant_key'); // Ganti dengan key asli Anda

            // Melakukan hashing ulang payload
            $generatedSignature = hash_hmac('sha512', json_encode($data), $merchantKey);

            if ($receivedSignature === $generatedSignature && $data['result']['payment']['status'] === 'PAID') {
                $result = [
                    'resultCode' => '00',
                    'resultData' => $data,
                    'payment' => [
                        'pg' => 'finpay',
                        'invoice_id' => $request->input('meta.data.invoice_id'),
                        'amount' => $request->input('result.payment.amount'),
                        'payment_date' => today()->toDateString(),
                        'payment_method' => $request->input('sourceOfFunds.type'),
                        'reference' => $request->input('result.payment.reference'),
                        'merchant_code' => $request->input('merchant.id'),
                        'merchant_order_id' => $request->input('order.id'),
                        'publisher_order_id' => $request->input('sourceOfFunds.paymentCode'),
                        'status' => 'paid'
                    ]
                ];
            }

            return $result;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 1);
        }
    }
}
