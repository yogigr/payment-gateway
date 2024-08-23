# payment-gateway
A simple and versatile Laravel package for integrating multiple payment providers. Easily manage and process payments from various gateways with a unified API.

## Installation

To install the `PaymentGateway` package, follow these steps:

1. **Require the Package via Composer**

   Run the following command in your Laravel project directory:

   ```bash
   composer require yogigr/payment-gateway
   ```

2. **Publish the Configuration File**
   
   Publish the configuration file using the following command:

   ```bash
   php artisan vendor:publish --provider="yogigr\PaymentGateway\PaymentGatewayServiceProvider" --tag="config"
   ```

3. **Set Up Environment Variables**

   your .env file:

   ```env
    PAYMENT_GATEWAY=duitku
    PAYMENT_CALLBACK_DOMAIN=https://example.app
    PAYMENT_RETURN_DOMAIN=https://example.app

    DUITKU_MERCHANT_CODE=duitku-merchant-code
    DUITKU_MERCHANT_KEY=duitku-merchant-key
    DUITKU_SANDBOX_MODE=true
    DUITKU_SANITIZED_MODE=false
    DUITKU_LOGS=true
    DUITKU_PAYMENT_URL=https://sandbox.duitku.com/TopUp/v2/DuitkuNotification.aspx

    FINPAY_MERCHANT_CODE=finpay-merchant-id
    FINPAY_MERCHANT_KEY=finpay-merchant-key
    FINPAY_PAYMENT_URL=https://devo.finnet.co.id
    ```

## Usage

After installation, you can use the package via the provided Facade. Below are examples of how to fetch categories and items.

### Show Payment Methods
```php
use yogigr\PaymentGateway\Facades\PaymentGateway;

$methods = PaymentGateway::getPaymentMethodOptions();
```

### Create Invoice
```php
use yogigr\PaymentGateway\Facades\PaymentGateway;

$data = [
    'amount' => 100000,
    'email' => 'customer@email.com',
    'phone' => '628100000000',
    'product_details' => 'Product details',
    'merchant_order_id' => 'order id / kode',
    'invoice_id' => 'Invoice id',
    'merchant_user_info' => 'Customer info / user id', // (opsional)
    'customer_name' => 'Customer name',
    'callback_path' => '/payment/callback',
    'return_path' => '/invoices',
    'success_path' => '/invoices?status=success',
    'expiry_period' => 60, 
    'address' => 'Customer address',
    'city' => 'Customer city',
    'postal_code' => 'Postal code',
    'country_code' => 'ID'
];

$paymentMethod = 'credit_card';

$response = PaymentGateway::createInvoice($data, $paymentMethod);

if ($response->statusCode == '00') {
    redirect($response->paymentUrl);
}
```

### Callback
```php
use yogigr\PaymentGateway\Facades\PaymentGateway;
use Illuminate\Http\Request;

class CallbackController extends Controller
{
    public function callback(Request $request)
    {
        $result = PaymentGateway::callback($request);

        if ($result['resultCode'] === '00') { // success
            // actions if success
        }
    }
}
```

### Check transaction
```php
use yogigr\PaymentGateway\Facades\PaymentGateway;

$transaction = PaymentGateway::checkTransaction($merchantOrderId);

dd($transaction);
```