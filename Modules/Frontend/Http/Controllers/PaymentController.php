<?php

namespace Modules\Frontend\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Modules\Subscriptions\Models\Plan;
use Modules\Subscriptions\Models\SubscriptionTransactions;
use Modules\Subscriptions\Models\Subscription;
use Modules\Subscriptions\Trait\SubscriptionTrait;
use Modules\Tax\Models\Tax;
use Modules\Coupon\Models\Coupon;
use GuzzleHttp\Client;
use Stripe\StripeClient;
use Midtrans\Snap;
use Midtrans\Config;
use  Modules\Subscriptions\Transformers\SubscriptionResource;
use Modules\Subscriptions\Transformers\PlanResource;
use Modules\Subscriptions\Transformers\PlanlimitationMappingResource;
use Illuminate\Support\Facades\Log;
use Currency;
use Carbon\Carbon;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use Illuminate\Support\Facades\Cache;

class PaymentController extends Controller
{
    use SubscriptionTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('frontend::index');
    }

    public function selectPlan(Request $request)
    {
        $planId = $request->input('plan_id');
        $promotionId = $request->input('promotionId');
        $planName = $request->input('plan_name');
        $plan= Plan::where('status',1)->with('planLimitation')->get();

        $plans = PlanResource::collection($plan);

        $activeSubscriptions = Subscription::where('user_id', auth()->id())->where('status', 'active')->where('end_date', '>', now())->orderBy('id','desc')->first();
        $currentPlanId = $activeSubscriptions ? $activeSubscriptions->plan_id : null;

        $userProfiles = \App\Models\UserMultiProfile::where('user_id', auth()->id())->get();
        $userProfileCount = $userProfiles->count();


        $planId = $planId ?? $currentPlanId ?? Plan::first()->id ?? null;

        $promotions = Coupon::where('status', 1)
        ->where('start_date', '<=', now())
        ->where('expire_date', '>=', now())
        ->get();

        $clientKey = GetpaymentMethod('midtrans_client_id');
        $view = view('frontend::subscriptionPayment', compact('plans', 'planId', 'currentPlanId', 'promotions', 'clientKey', 'userProfileCount','userProfiles'))->render();
        return response()->json(['success' => true, 'view' => $view]);
    }

    public function processPayment(Request $request)
    {
        \Log::info('processPayment called', $request->all());
        
        $paymentMethod = $request->input('payment_method');
        $plan_id = $request->input('plan_id');
        $promotionId = $request->input('promotion_id');

        $plan = Plan::where('id', $plan_id)->where('status', 1)->first();
        if (!$plan) {
            return response()->json(['error' => 'Invalid or inactive plan.'], 400);
        }

        $basePrice = $plan->price;
        if ($plan->discount_percentage > 0) {
            $basePrice = $plan->price - ($plan->price * $plan->discount_percentage / 100);
        }

        $finalPrice = $basePrice;
        if ($promotionId) {
            $coupon = Coupon::where('id', $promotionId)
                ->where('status', 1)
                ->where('start_date', '<=', now())
                ->where('expire_date', '>=', now())
                ->whereHas('CouponPlanMappings', function ($query) use ($plan_id) {
                    $query->where('subscription_plan_id', $plan_id);
                })
                ->first();
            if ($coupon) {
                if ($coupon->discount_type == 'fixed') {
                    $finalPrice = max(0, $basePrice - $coupon->discount);
                } else {
                    $finalPrice = $basePrice - ($basePrice * $coupon->discount / 100);
                }
            }
        }

        $taxes = Tax::active()->get();
        $totalTax = 0;
        foreach ($taxes as $tax) {
            if (strtolower($tax->type) == 'fixed') {
                $totalTax += $tax->value;
            } else {
                $totalTax += ($finalPrice * $tax->value) / 100;
            }
        }
        
        $price = round($finalPrice + $totalTax, 2);

        $request->merge(['price' => $price]);

        $paymentHandlers = [
            'stripe' => 'StripePayment',
            'razorpay' => 'RazorpayPayment',
            'paystack' => 'PaystackPayment',
            'paypal' => 'PayPalPayment',
            'flutterwave' => 'FlutterwavePayment',
            'cinet' => 'CinetPayment',
            'sadad' => 'SadadPayment',
            'airtel' => 'AirtelPayment',
            'phonepe' => 'PhonePePayment',
            'midtrans' => 'MidtransPayment',
        ];

        if (array_key_exists($paymentMethod, $paymentHandlers)) {
            return $this->{$paymentHandlers[$paymentMethod]}($request, $price, $promotionId);
        }

        \Log::warning('Invalid payment method', ['payment_method' => $paymentMethod]);
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['error' => 'Invalid payment method.'], 400);
        }
        return redirect()->back()->withErrors('Invalid payment method.');
    }


    protected function StripePayment(Request $request)
    {
        $baseURL = url('/');
        $stripe_secret_key = GetpaymentMethod('stripe_secretkey');
        $currency = GetcurrentCurrency();

        $stripe = new \Stripe\StripeClient($stripe_secret_key);
        $price = $request->input('price');
        $plan_id = $request->input('plan_id');

        $currenciesWithoutCents = ['XAF', 'XOF', 'JPY', 'KRW'];
        $priceInCents = in_array(strtoupper($currency), $currenciesWithoutCents) ? $price : $price * 100;

        $promotion_id = $request->input('promotion_id');
        // Validate and sanitize the pricedd
        if (!is_numeric($price) || $price <= 0) {
            return response()->json(['error' => 'Invalid price value.'], 400);
        }

        // Convert price to the smallest currency unit (e.g., cents for USD)
        $currenciesWithoutCents = ['XAF', 'XOF', 'JPY', 'KRW'];
        $priceInCents = in_array(strtoupper($currency), $currenciesWithoutCents)
            ? intval($price) // No conversion for currencies without cents
            : intval($price * 100); // Convert to cents for other currencies

        try {
            $checkout_session = $stripe->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => 'Subscription Plan',
                        ],
                        'unit_amount' => $priceInCents, // Ensure this is an integer
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'metadata' => [
                    'plan_id' => $plan_id,
                    'promotion_id' => $promotion_id,
                ],
                'success_url' => $baseURL . '/payment/success?gateway=stripe&session_id={CHECKOUT_SESSION_ID}'
            ]);

            return response()->json(['redirect' => $checkout_session->url]);

        } catch (\Stripe\Exception\InvalidRequestException $e) {

            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, "must convert to at least") !== false) {
                $errorMessage = "The amount entered is too low to process a payment. Please increase the amount and try again.";
            }
            return response()->json(['error' => $errorMessage], 400);

        } catch (\Exception $e) {

            return response()->json(['error' => 'Something went wrong. Please try again later.'], 500);
        }
    }

    protected function RazorpayPayment(Request $request, float $price)
    {
        $baseURL = env('APP_URL');
        $razorpayKey = GetpaymentMethod('razorpay_publickey');
        $razorpaySecret = GetpaymentMethod('razorpay_secretkey');
        $plan_id = $request->input('plan_id');
        $promotion_id = $request->input('promotion_id');
        $priceInPaise = $price * 100;
        $currency=GetcurrentCurrency();
        $formattedCurrency = strtoupper(strtolower($currency));
        $api = new \Razorpay\Api\Api($razorpayKey, $razorpaySecret);

        try {

            $amount = $price * 100; // Convert to paisa
            $razorpayKey = GetpaymentMethod('razorpay_publickey');

            $order = $api->order->create([
                'receipt' => 'order_' . time(),
                'amount' => $priceInPaise,
                'currency' => $formattedCurrency,
                'payment_capture' => 1
            ]);

            session(['razorpay_order_id' => $order['id']]);

            return response()->json([
                'key' => $razorpayKey,
                'amount' => $amount,
                'currency' => $formattedCurrency,
                'name' => config('app.name'),
                'description' => 'Subscription Payment',
                'plan_id' => $plan_id,
                'order_id' => $order['id'],
                'success_url' => route('payment.success'),
                'prefill' => [
                    'name' => auth()->user()->name ?? '',
                    'email' => auth()->user()->email ?? '',
                    'contact' => auth()->user()->phone ?? ''
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
          }
    }


    protected function PaystackPayment(Request $request, float $price)
    {
        $baseURL = env('APP_URL');


        $paystackSecretKey = GetpaymentMethod('paystack_secretkey');
        $price = $request->input('price');
        $plan_id = $request->input('plan_id');
        $priceInKobo = $price * 100; // Paystack uses kobo

        $currency=GetcurrentCurrency();
        $formattedCurrency = strtoupper(strtolower($currency));


        // Create a new Paystack payment
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $paystackSecretKey,
        ])->post('https://api.paystack.co/transaction/initialize', [
            'email' => auth()->user()->email, // Get user email from authenticated user
            'amount' => $priceInKobo,
            'currency' => $formattedCurrency,
            'callback_url' => $baseURL . '/payment/success?gateway=paystack',
            'metadata' => [
                'plan_id' => $plan_id,
            ],
        ]);

        $responseBody = $response->json();

        if ($responseBody['status']) {
            return response()->json([
                'success' => true,
                'redirect' => $responseBody['data']['authorization_url'],
            ]);
        } else {

            $message = isset($responseBody['message']) ? $responseBody['message'] : 'Something went wrong, choose a different method';

            return response()->json(['error' =>$message], 400);
        }
    }
    /**
     * Check if Paystack allows USD for this account
     */
    private function isUSDAllowedByPaystack()
    {
        $paystackSecretKey = GetpaymentMethod('paystack_secretkey');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $paystackSecretKey,
        ])->get('https://api.paystack.co/transaction/currencies');

        $responseBody = $response->json();

        if (isset($responseBody['status']) && $responseBody['status']) {
            $currencies = array_column($responseBody['data'], 'currency');
            Log::info("Supported currencies from Paystack: " . implode(', ', $currencies));
            return in_array('USD', $currencies);
        }

        return false; // Default to false if API fails
    }

    protected function PayPalPayment(Request $request)
    {
        $baseURL = env('APP_URL');
        $price = $request->input('price');
        $plan_id = $request->input('plan_id');

        // Validate price
        if (!is_numeric($price) || $price <= 0) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Invalid price value.'], 400);
            }
            return redirect()->back()->withErrors('Invalid price value.');
        }

        try {
            // Get Access Token
            $accessToken = $this->getAccessToken();

            // Create Payment
            $payment = $this->createPayment($accessToken, $price, $plan_id);

            \Log::info('PayPal createPayment response', ['payment' => $payment]);

            if (isset($payment['links'])) {
                foreach ($payment['links'] as $link) {
                    if ($link['rel'] === 'approval_url') {
                        return response()->json(['success' => true, 'redirect' => $link['href']]);
                    }
                }
            }

            \Log::warning('PayPal payment creation failed', ['payment' => $payment]);
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Payment creation failed.'], 400);
            }
            return redirect()->back()->withErrors('Payment creation failed.');
        } catch (\Exception $ex) {
            \Log::error('PayPal payment processing exception', ['error' => $ex->getMessage()]);
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Payment processing failed: ' . $ex->getMessage()], 400);
            }
            return redirect()->back()->withErrors('Payment processing failed: ' . $ex->getMessage());
        }
    }

    protected function FlutterwavePayment(Request $request)
    {
        try {
            $baseURL = env('APP_URL');
            $flutterwaveKey = GetpaymentMethod('flutterwave_publickey');
            $price = $request->input('price');
            $plan_id = $request->input('plan_id');
            $currency = GetcurrentCurrency();
            $formattedCurrency = strtoupper(strtolower($currency));

             $logo=GetSettingValue('mini_logo') ? setBaseUrlWithFileName(GetSettingValue('mini_logo'),'image','logos') :  asset(setting('mini_logo'));

            // Generate unique transaction reference
            $tx_ref = 'FLW-' . uniqid() . '-' . time();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'public_key' => $flutterwaveKey,
                    'tx_ref' => $tx_ref,
                    'amount' => $price,
                    'currency' => $formattedCurrency,
                    'country' => 'NG',
                    'payment_options' => 'card',
                    'customer' => [
                        'email' => auth()->user()->email,
                        'name' => auth()->user()->name ?? 'Customer',
                        'phonenumber' => auth()->user()->phone ?? ''
                    ],
                    'meta' => [
                        'plan_id' => $plan_id
                    ],
                    'customizations' => [
                        'title' => config('app.name', 'Subscription Payment'),
                        'description' => 'Payment for Plan #' . $plan_id,
                        'logo' => $logo
                    ],
                    'redirect_url' => $baseURL . '/payment/success?gateway=flutterwave'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Flutterwave Payment Error:', [
                'error' => $e->getMessage(),
                'user' => auth()->user()->email
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Payment initialization failed: ' . $e->getMessage()
            ], 400);
        }
    }
    protected function CinetPayment(Request $request)
    {
        $siteId = GetpaymentMethod('cinet_siteid');
        $apiKey = GetpaymentMethod('cinet_api_key');
        $secretKey = GetpaymentMethod('cinet_Secret_key');

        $price = $request->input('price');
        $plan_id = $request->input('plan_id');

        $priceFormatted = number_format((float)$price, 2, '.', '');

        $currency = GetcurrentCurrency();
        $formattedCurrency = strtoupper($currency);

        $transactionId = uniqid(); // Unique per payment

        $data = [
            'apikey' => $apiKey,
            'site_id' => $siteId,
            'transaction_id' => $transactionId,
            'amount' => $priceFormatted,
            'currency' => $formattedCurrency,
            'description' => 'Plan purchase #' . $plan_id,
            'return_url' => url('/payment/success?gateway=cinet'),
            'notify_url' => url('/payment/webhook/cinet'),
            'channels' => 'ALL',
            'lang' => 'en',
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post('https://api-checkout.cinetpay.com/v2/payment', $data);

            $responseBody = $response->json();

            if ($response->successful() && isset($responseBody['data']['payment_url'])) {
                if ($request->ajax()) {
                    return response()->json([
                        'redirect' => $responseBody['data']['payment_url']
                    ]);
                }
                return redirect()->away($responseBody['data']['payment_url']);
            } else {
                return redirect()->back()->withErrors('Payment failed: ' . json_encode($responseBody));
            }
        } catch (\Exception $e) {
            return redirect()->back()->withErrors('Connection error: ' . $e->getMessage());
        }
    }




    protected function SadadPayment(Request $request)
    {
        $baseURL = env('APP_URL');
        $price = $request->input('price');
        $plan_id = $request->input('plan_id');
        $response = $this->makeSadadPaymentRequest($price, $plan_id);
        if ($response->isSuccessful()) {
            return redirect($response->redirect_url);
        } else {
            return redirect()->back()->withErrors('Payment initiation failed: ' . $response->message);
        }
    }

    protected function AirtelPayment(Request $request)
    {
        $baseURL = env('APP_URL');
        $price = $request->input('price');
        $plan_id = $request->input('plan_id');

        $response = $this->makeAirtelPaymentRequest($price, $plan_id);

        if ($response->isSuccessful()) {
            return redirect($response->redirect_url);
        } else {
            return redirect()->back()->withErrors('Payment initiation failed: ' . $response->message);
        }
    }

    protected function PhonePePayment(Request $request)
    {
        $baseURL = env('APP_URL');
        $price = $request->input('price');
        $plan_id = $request->input('plan_id');

        $response = $this->makePhonePePaymentRequest($price, $plan_id);

        if ($response->isSuccessful()) {
            return redirect($response->payment_url);
        } else {
            return redirect()->back()->withErrors('Payment initiation failed: ' . $response->message);
        }
    }

    protected function MidtransPayment(Request $request)
    {
        $serverKey = GetpaymentMethod('midtrans_server_key');
        Config::$serverKey = $serverKey;
        Config::$isProduction = false;
        try {
            $price = $request->input('price');
            $plan_id = $request->input('plan_id');
            $currency = GetcurrentCurrency();

            $grossAmount = (int) round($price);
            $transactionDetails = [
                'order_id' => uniqid(),
                'gross_amount' => $grossAmount,
            ];

            $customerDetails = [
                'first_name' => auth()->user()->name,
                'email' => auth()->user()->email,
            ];

            $transaction = [
                'transaction_details' => $transactionDetails,
                'customer_details' => $customerDetails,
            ];

            $snapToken = Snap::getSnapToken($transaction);
            $clientKey = GetpaymentMethod('midtrans_client_id');
            return response()->json(['snapToken' => $snapToken, 'clientKey' => $clientKey]);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors('Payment initiation failed: ' . $e->getMessage());
        }
    }

    private function getAccessToken()
    {
        $clientId =  GetpaymentMethod('paypal_clientid');
        $clientSecret =GetpaymentMethod('paypal_secretkey');

        $client = new Client();
        $response = $client->post('https://api.sandbox.paypal.com/v1/oauth2/token', [
            'auth' => [$clientId, $clientSecret],
            'form_params' => [
                'grant_type' => 'client_credentials',
            ],
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['access_token'];
    }

    private function createPayment(string $accessToken, float $price, int $planId)
    {
        $baseURL = env('APP_URL');
        $currency = GetcurrentCurrency();
        $formattedCurrency = strtoupper(strtolower($currency));

        $client = new Client();
        $response = $client->post('https://api.sandbox.paypal.com/v1/payments/payment', [
            'headers' => [
                'Authorization' => "Bearer $accessToken",
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'intent' => 'sale',
                'payer' => [
                    'payment_method' => 'paypal',
                ],
                'transactions' => [[
                    'amount' => [
                        'total' => $price,
                        'currency' =>  $formattedCurrency,
                    ],
                    'description' => 'Payment for plan ID: ' . $planId,
                    'item_list' => [
                        'items' => [[
                            'name' => 'Subscription Plan',
                            'sku' => $planId,
                            'price' => $price,
                            'currency' => $formattedCurrency,
                            'quantity' => 1
                        ]]
                    ]
                ]],
                'redirect_urls' => [
                    'return_url' => $baseURL . '/payment/success?gateway=paypal',
                    'cancel_url' => $baseURL . '/payment/cancel',
                ],
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    public function paymentSuccess(Request $request)
    {
        $gateway = $request->input('gateway');

        switch ($gateway) {
            case 'stripe':
                return $this->handleStripeSuccess($request);
            case 'razorpay':
                return $this->handleRazorpaySuccess($request);
            case 'paystack':
                return $this->handlePaystackSuccess($request);
            case 'paypal':
                return $this->handlePayPalSuccess($request);
            case 'flutterwave':
                return $this->handleFlutterwaveSuccess($request);
            case 'cinet':
                return $this->handleCinetSuccess($request);
            case 'sadad':
                return $this->handleSadadSuccess($request);
            case 'airtel':
                return $this->handleAirtelSuccess($request);
            case 'phonepe':
                return $this->handlePhonePeSuccess($request);
            case 'midtrans':
                return $this->MidtransPayment($request);
            default:
                return redirect('/')->with('error', 'Invalid payment gateway.');
        }
    }

    protected function handlePaymentSuccess(int $plan_id, float $amount, string $payment_type, string $transaction_id, ?int $promotion_id = null)
    {
        $user = Auth::user();

        $plan = Plan::where('id', $plan_id)->where('status', 1)->first();
        if (! $plan) {
            Log::warning('Payment rejected: invalid or inactive plan', ['plan_id' => $plan_id, 'user_id' => $user->id ?? null, 'payment_type' => $payment_type]);
            return redirect('/')->with('error', 'Invalid or inactive plan.');
        }
        if ($amount <= 0 || ! is_numeric($amount)) {
            Log::warning('Payment rejected: invalid amount', ['plan_id' => $plan_id, 'user_id' => $user->id ?? null, 'payment_type' => $payment_type]);
            return redirect('/')->with('error', 'Invalid payment amount.');
        }

        // Idempotency: prevent duplicate subscription for same transaction (A04)
        if (SubscriptionTransactions::where('transaction_id', $transaction_id)->where('user_id', $user->id)->exists()) {
            Log::info('Payment idempotent: transaction already processed', ['transaction_id' => $transaction_id, 'user_id' => $user->id]);
            return redirect('payment-history')->with('success', [
                'title' => 'Payment Successful!',
                'message' => 'Your subscription was already activated for this payment.',
            ]);
        }

        // Deactivate existing active subscriptions
        Subscription::where('user_id', $user->id)
        ->where('status', 'active')
        ->update(['status' => 'inactive']);
        $limitation_data = PlanlimitationMappingResource::collection($plan->planLimitation);
        $user = Auth::user();

        $start_date = now();
        $end_date = $this->get_plan_expiration_date($start_date, $plan->duration, $plan->duration_value);

        // Calculate the base price after plan discount percentage
        $basePrice = $plan->price;
        if (isset($plan->discount_percentage) && $plan->discount_percentage > 0) {
            $basePrice = $plan->price - ($plan->price * $plan->discount_percentage / 100);
        }

        // Calculate promotion discount if exists
        $discountedPrice = $basePrice;
        $couponDiscount = 0;

        if ($promotion_id) {
            $promotion = Coupon::find($promotion_id); // Fetch the promotion details
            if ($promotion) {
                if ($promotion->discount_type == 'fixed') {
                    $couponDiscount = $promotion->discount;
                } elseif ($promotion->discount_type == 'percentage') {
                    $couponDiscount = ($basePrice * $promotion->discount) / 100;
                }

                // Ensure the discount does not exceed the base price
                $couponDiscount = min($couponDiscount, $basePrice);

                // Apply the promotion discount
                $discountedPrice = $basePrice - $couponDiscount;
            }
        }
        // Calculate taxes based on the discounted price
        $taxes = Tax::active()->get();
        $totalTax = 0;
        foreach ($taxes as $tax) {
            if (strtolower($tax->type) == 'fixed') {
                $totalTax += $tax->value;
            } elseif (strtolower($tax->type) == 'percentage') {
                $totalTax += ($discountedPrice * $tax->value) / 100;
            }
        }

        // Calculate the total amount (discounted price + taxes)
        $totalAmount = $discountedPrice + $totalTax;
        // Create the subscription
        $subscription = Subscription::create([
            'plan_id' => $plan_id,
            'user_id' => auth()->id(),
            'device_id' => auth()->user()->devices->pluck('device_id')->first(),
            'start_date' => now(),
            'end_date' => $end_date,
            'status' => 'active',
            'amount' => $plan->price,
            'discount_percentage' => $plan->discount_percentage,
            'coupon_discount' => $couponDiscount,
            'promotion_id' => $promotion_id,
            'tax_amount' => $totalTax,
            'total_amount' => $totalAmount,
            'name' => $plan->name,
            'identifier' => $plan->identifier,
            'type' => $plan->duration,
            'duration' => $plan->duration_value,
            'level' => $plan->level,
            'plan_type' => $limitation_data ? json_encode($limitation_data) : null,
            'payment_id' => null,
        ]);

        Subscription::where('user_id', auth()->id())
    ->where('id', '!=', $subscription->id)
    ->update(['status' => 'inactive']);


        // Create a subscription transaction
        SubscriptionTransactions::create([
            'user_id' => auth()->id(),
            'amount' => $amount, // Use the final total amount
            'payment_type' => $payment_type,
            'payment_status' => 'paid',
            'tax_data' => $taxes->isEmpty() ? null : json_encode($taxes),
            'transaction_id' => $transaction_id,
            'subscriptions_id' => $subscription->id,
        ]);


       $response = new SubscriptionResource($subscription);

       $this->sendNotificationOnsubscription('new_subscription', $response);

        auth()->user()->update(['is_subscribe' => 1]);

        Log::info('Subscription payment success', [
            'user_id' => auth()->id(),
            'plan_id' => $plan_id,
            'amount' => $amount,
            'payment_type' => $payment_type,
            'transaction_id' => $transaction_id,
        ]);

        Cache::flush();


        // message with SweetAlert
        return redirect('payment-history')->with('success', [
            'title' => 'Payment Successful!',
            'message' => 'Your subscription has been activated successfully.',
            'plan_name' => $plan->name,
            'amount' => Currency::format($amount),
            'valid_until' => Carbon::parse($end_date)->format('Y-m-d'),

        ]);



        return redirect('payment-history')->with('success', 'Payment completed successfully!');
    }

    protected function handleStripeSuccess(Request $request)
    {
        $sessionId = $request->input('session_id');
        $stripe_secret_key=GetpaymentMethod('stripe_secretkey');
        $stripe = new StripeClient($stripe_secret_key);

        try {
            $session = $stripe->checkout->sessions->retrieve($sessionId);
            return $this->handlePaymentSuccess($session->metadata->plan_id, $session->amount_total / 100, 'stripe', $session->payment_intent, !empty($session->metadata->promotion_id) ? (int)$session->metadata->promotion_id : null);
        } catch (\Exception $e) {
            return redirect('/')->with('error', 'Payment failed: ' . $e->getMessage());
        }
    }

    protected function handleRazorpaySuccess(Request $request)
{
    $paymentId = $request->input('razorpay_payment_id');
    $razorpayOrderId = session('razorpay_order_id');
    $plan_id = $request->input('plan_id');

    $razorpayKey = GetpaymentMethod('razorpay_publickey');
    $razorpaySecret = GetpaymentMethod('razorpay_secretkey');

    $api = new \Razorpay\Api\Api($razorpayKey, $razorpaySecret);
    $payment = $api->payment->fetch($paymentId);

    if ($payment['status'] == 'captured') {
        return $this->handlePaymentSuccess($plan_id, $payment['amount'] / 100, 'razorpay', $paymentId);
    } else {
        return redirect('/')->with('error', 'Payment failed: ' . $payment['error_description']);
    }
}

   protected function handlePaystackSuccess(Request $request)
    {
        $reference = $request->input('reference');

        $paystackSecretKey = GetpaymentMethod('paystack_secretkey');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $paystackSecretKey,
        ])->get("https://api.paystack.co/transaction/verify/{$reference}");

        $responseBody = $response->json();

        if ($responseBody['status']) {
            return $this->handlePaymentSuccess($responseBody['data']['metadata']['plan_id'], $responseBody['data']['amount'] / 100, 'paystack', $responseBody['data']['id']);
        } else {
            return redirect('/')->with('error', 'Payment verification failed: ' . $responseBody['message']);
        }
    }

   protected function handlePayPalSuccess(Request $request)
    {
        $paymentId = $request->input('paymentId');
        $payerId = $request->input('PayerID');

        $paypal_secretkey = GetpaymentMethod('paypal_secretkey');
        $paypal_clientid = GetpaymentMethod('paypal_clientid');

        $apiContext = new ApiContext(
            new OAuthTokenCredential(
                $paypal_secretkey,
                $paypal_clientid
            )
        );

        try {
            $payment = Payment::get($paymentId, $apiContext);
            $execution = new PaymentExecution();
            $execution->setPayerId($payerId);
            $result = $payment->execute($execution, $apiContext);

            if ($result->getState() == 'approved') {
                $plan_id = $result->getTransactions()[0]->getItemList()->getItems()[0]->getSku();
                return $this->handlePaymentSuccess($plan_id, $result->getTransactions()[0]->getAmount()->getTotal(), 'paypal', $paymentId);
            } else {
                \Log::error('PayPal payment not approved', ['paymentId' => $paymentId, 'result' => $result]);
                return redirect('/')->with('error', 'Payment not approved.');
            }
        } catch (\Exception $e) {
            \Log::error('PayPal payment verification failed', ['paymentId' => $paymentId, 'error' => $e->getMessage()]);
            return redirect('/')->with('error', 'Payment verification failed: ' . $e->getMessage());
        }
    }

    protected function handleFlutterwaveSuccess(Request $request)
    {
        try {
            $transactionId = $request->input('transaction_id');
            $tx_ref = $request->input('tx_ref');
            $plan_id = $request->input('plan_id');

            $flutterwaveKey = GetpaymentMethod('flutterwave_secretkey');

            // Verify the transaction
            $response = Http::withToken($flutterwaveKey)
                ->get("https://api.flutterwave.com/v3/transactions/{$transactionId}/verify");

            $responseData = $response->json();

            if ($response->successful() &&
                isset($responseData['status']) &&
                $responseData['status'] === 'success' &&
                $responseData['data']['tx_ref'] === $tx_ref) {

                return $this->handlePaymentSuccess(
                    $plan_id,
                    $responseData['data']['amount'],
                    'flutterwave',
                    $transactionId
                );
            }

            throw new \Exception('Payment verification failed');

        } catch (\Exception $e) {
            Log::error('Flutterwave Payment Error', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId ?? null,
                'tx_ref' => $tx_ref ?? null
            ]);

            return redirect('/')->with('error', 'Payment verification failed: ' . $e->getMessage());
        }
    }
    protected function handleCinetSuccess(Request $request)
    {
        $transactionId = $request->input('transaction_id');
        $paymentStatus = $request->input('status');
        $planId = $request->input('plan_id');

        if ($paymentStatus !== 'success') {
            return redirect('/')->with('error', 'Payment failed: Invalid payment status.');
        }

        return $this->handlePaymentSuccess($planId, $request->input('amount'), 'cinet', $transactionId);
    }

    protected function handleSadadSuccess(Request $request)
    {
        $transactionId = $request->input('transaction_id');
        $paymentStatus = $request->input('status');
        $plan_id = $request->input('plan_id');

        if ($paymentStatus !== 'success') {
            return redirect('/')->with('error', 'Payment failed: Invalid payment status.');
        }

        return $this->handlePaymentSuccess($plan_id, $request->input('amount'), 'sadad', $transactionId);
    }

   public function midtransNotification(Request $request)
    {
        $payload = json_decode($request->getContent(), true);

        if ($payload['transaction_status'] === 'settlement') {
            $transactionId = $payload['order_id'];
            $plan_id = $payload['item_details'][0]['id'];
            $amount = $payload['gross_amount'];

            return $this->handlePaymentSuccess($plan_id, $amount, 'midtrans', $transactionId);
        }

        return response()->json(['status' => 'success']);
    }

    protected function makeSadadPaymentRequest(float $price, int $plan_id)    
    {
        $sadad_Sadadkey=GetpaymentMethod('sadad_Sadadkey');

        $url = 'https://api.sadad.com/payment';
        $data = [
            'amount' => $price,
            'plan_id' => $plan_id,
            'callback_url' => env('APP_URL') . '/payment/success?gateway=sadad',
        ];

        $client = new \GuzzleHttp\Client();
        $response = $client->post($url, [
            'json' => $data,
            'headers' => [
                'Authorization' => 'Bearer ' . $sadad_Sadadkey,
            ]
        ]);

        return json_decode($response->getBody());
    }

    protected function handleAirtelSuccess(Request $request)
    {
        $transactionId = $request->input('transaction_id');
        $paymentStatus = $request->input('status');
        $planId = $request->input('plan_id');

        if ($paymentStatus !== 'success') {
            return redirect('/')->with('error', 'Payment failed: Invalid payment status.');
        }

        return $this->handlePaymentSuccess($planId, $request->input('amount'), 'airtel', $transactionId);
    }

     protected function handlePhonePeSuccess(Request $request)
    {
        $transactionId = $request->input('transaction_id');
        $paymentStatus = $request->input('status');
        $planId = $request->input('plan_id');

        if ($paymentStatus !== 'success') {
            return redirect('/')->with('error', 'Payment failed: Invalid payment status.');
        }

        return $this->handlePaymentSuccess($planId, $request->input('amount'), 'phonepe', $transactionId);
    }

    protected function makePhonePePaymentRequest(float $price, int $plan_id)
    {
        $currency=GetcurrentCurrency();

        $formattedCurrency = strtoupper(strtolower($currency));


        $url = 'https://api.phonepe.com/apis/hermes/pg/v1/pay';
        $data = [
            'amount' => $price,
            'plan_id' => $plan_id,
            'callbackUrl' => env('APP_URL') . '/payment/success?gateway=phonepe',
            'currency' =>   $formattedCurrency,
        ];
        $client = new Client();
        $response = $client->post($url, [
            'json' => $data,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-VERIFY-TOKEN' => env('PHONEPE_VERIFY_TOKEN'),
            ]
        ]);

        return json_decode($response->getBody());
    }
    protected function makeAirtelPaymentRequest(float $price, int $plan_id)
    {

        $airtel_money_secretkey=GetpaymentMethod('airtel_money_secretkey');


        $url = 'https://api.airtel.com/payment';
        $data = [
            'amount' => $price,
            'plan_id' => $plan_id,
            'callback_url' => env('APP_URL') . '/payment/success?gateway=airtel',
        ];

        $client = new Client();
        $response = $client->post($url, [
            'json' => $data,
            'headers' => [
                'Authorization' => 'Bearer ' .  $airtel_money_secretkey,
            ]
        ]);

        return json_decode($response->getBody());
    }

    public function getAvailablePromotions(Request $request)
    {
        $planId = $request->input('plan_id');
        $today = now()->toDateString();

        $promotions = Coupon::where('status', 1)
        ->whereDate('start_date', '<=', $today)
        ->whereDate('expire_date', '>=', $today)
            ->where(function($query) use ($planId) {
                $query->whereDoesntHave('subscriptionPlans')
                      ->orWhereHas('subscriptionPlans', function($q) use ($planId) {
                          $q->where('subscription_plan_id', $planId);
                      });
            })
            ->get();
        return response()->json([
            'promotions' => $promotions
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('frontend::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id)
    {
        return view('frontend::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id)
    {
        return view('frontend::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        //
    }
}
