<?php

namespace yogigr\PaymentGateway;

use Illuminate\Http\Request;

class DuitkuPg implements PaymentGatewayInterface
{
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function config()
    {
        $duitkuConfig = new \Duitku\Config($this->config['merchant_key'], $this->config['merchant_code']);
        // false for production mode
        // true for sandbox mode
        $duitkuConfig->setSandboxMode($this->config['sandbox_mode']);
        // set sanitizer (default : true)
        $duitkuConfig->setSanitizedMode($this->config['sanitized_mode']);
        // set log parameter (default : true)
        $duitkuConfig->setDuitkuLogs($this->config['log']);

        return $duitkuConfig;
    }

    public function createInvoice(array $data, $paymentMethod)
    {
        $paymentAmount      = $data['amount'];
        $paymentMethod      = $paymentMethod;
        $email              = $data['email'] ?? '';
        $phoneNumber        = $data['phone'] ?? '';
        $productDetails     = $data['product_details'] ?? '';
        $merchantOrderId    = $data['merchant_order_id'] ?? time();
        $additionalParam    = $data['invoice_id'];
        $merchantUserInfo   = $data['merchant_user_info'] ?? ''; // optional
        $customerVaName     = $data['customer_name'] ?? '';
        $callbackUrl        = config('payment.callback_domain') . ($data['callback_path'] ?? '/payment/callback');
        $returnUrl          = config('payment.return_domain') . ($data['return_path'] ?? ('/app/invoices/' . $data['invoice_id']));
        $expiryPeriod       = $data['expiry_period'] ?? 60;

        // Customer Detail
        [$first, $last] = splitFullName($customerVaName ?? 'Noname');
        $firstName          = $first;
        $lastName           = $last;

        // Address
        $alamat             = $data['address'] ?? '';
        $city               = $data['city'] ?? '';
        $postalCode         = $data['postal_code'] ?? '';
        $countryCode        = $data['country_code'] ?? 'ID';

        $address = array(
            'firstName'     => $firstName,
            'lastName'      => $lastName,
            'address'       => $alamat,
            'city'          => $city,
            'postalCode'    => $postalCode,
            'phone'         => $phoneNumber,
            'countryCode'   => $countryCode
        );

        $customerDetail = array(
            'firstName'         => $firstName,
            'lastName'          => $lastName,
            'email'             => $email,
            'phoneNumber'       => $phoneNumber,
            'billingAddress'    => $address,
            'shippingAddress'   => $address
        );

        // Item Details
        $item1 = array(
            'name'      => $productDetails,
            'price'     => $paymentAmount,
            'quantity'  => 1
        );

        $itemDetails = array(
            $item1
        );

        $params = array(
            'paymentAmount'     => $paymentAmount,
            'paymentMethod'     => $paymentMethod,
            'merchantOrderId'   => $merchantOrderId,
            'productDetails'    => $productDetails,
            'additionalParam'   => $additionalParam,
            'merchantUserInfo'  => $merchantUserInfo,
            'customerVaName'    => $customerVaName,
            'email'             => $email,
            'phoneNumber'       => $phoneNumber,
            'itemDetails'       => $itemDetails,
            'customerDetail'    => $customerDetail,
            'callbackUrl'       => $callbackUrl,
            'returnUrl'         => $returnUrl,
            'expiryPeriod'      => $expiryPeriod
        );

        try {
            // createInvoice Request
            $responseDuitkuApi = \Duitku\Api::createInvoice($params, $this->config());
            return json_decode($responseDuitkuApi);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 1);
        }
    }

    public function checkTransaction($merchantOrderId)
    {
        try {
            $transactionList = \Duitku\Api::transactionStatus($merchantOrderId, $this->config());
            $transaction = json_decode($transactionList);
            return $transaction;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 1);
        }
    }

    public function callback(Request $request)
    {
        try {
            $result = ['resultCode' => '01'];

            $callback = \Duitku\Pop::callback($this->config());

            $callback = json_decode($callback);

            if ($callback->resultCode == '00' && $callback->signature == $request->input('signature')) {
                $result = [
                    'resultCode' => '00',
                    'resultData' => $callback,
                    'payment' => [
                        'pg' => 'duitku',
                        'invoice_id' => $request->input('additionalParam'),
                        'amount' => $request->input('amount'),
                        'payment_date' => today()->toDateString(),
                        'payment_method' => $request->input('paymentCode') . ' / ' . $this->getPaymentMethodOptions()[$request->input('paymentCode')],
                        'reference' => $request->input('reference'),
                        'merchant_code' => $request->input('merchantCode'),
                        'merchant_order_id' => $request->input('merchantOrderId'),
                        'publisher_order_id' => $request->input('publisherOrderId'),
                        'status' => 'paid'
                    ]
                ];
            }

            return $result;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 1);
        }
    }

    public function getPaymentMethods()
    {
        try {
            $paymentAmount = "10000"; //"YOUR_AMOUNT";
            $paymentMethodList = \Duitku\Api::getPaymentMethod($paymentAmount, $this->config());
            return json_decode($paymentMethodList);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 1);
        }
    }

    public function getPaymentMethodOptions()
    {
        if ($this->getPaymentMethods()->responseCode == '00') {
            foreach ($this->getPaymentMethods()->paymentFee as $key => $value) {
                $data[$value->paymentMethod] = $value->paymentName;
            }
            return $data;
        }
        return [];
    }
}
