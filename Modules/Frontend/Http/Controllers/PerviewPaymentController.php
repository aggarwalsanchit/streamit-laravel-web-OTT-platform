<?php

namespace Modules\Frontend\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Entertainment\Models\Entertainment;
use Modules\Episode\Models\Episode;
use Modules\Frontend\Models\PayPerView;
use Modules\Season\Models\Season;
use Illuminate\Support\Facades\Http;
use Stripe\StripeClient;
use Illuminate\Support\Facades\Auth;
use Modules\Frontend\Models\PayperviewTransaction;
use Modules\Video\Models\Video;
use Modules\Entertainment\Transformers\MoviesResource;
use Modules\Entertainment\Transformers\TvshowResource;
use Modules\Video\Transformers\VideoResource;
use Modules\Entertainment\Transformers\SeasonResource;
use Modules\Entertainment\Transformers\EpisodeResource;
use Modules\Entertainment\Transformers\MoviesResourceV3;
use Modules\Entertainment\Transformers\TvshowResourceV3;
use Modules\Video\Transformers\VideoResourceV3;
use Modules\Entertainment\Transformers\SeasonResourceV3;
use Modules\Entertainment\Transformers\EpisodeResourceV3;
use Modules\Video\Transformers\Backend\VideoResourceV3 as BackendVideoResourceV3;
use Modules\Entertainment\Transformers\Backend\EpisodeResourceV3 as BackendEpisodeResourceV3;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Entertainment\Models\Watchlist;
use App\Models\User;
use Modules\Subscriptions\Models\Subscription;
use Modules\Entertainment\Transformers\Backend\CommonContentResourceV3;

class PerviewPaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('frontend::index');
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
    public function update(Request $request, int $id): RedirectResponse
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

    public function PayPerViewForm(Request $request)
    {

        if (!Auth::check()) {
            return redirect()->route('login');
        }
        if ($request->type == 'video') {
            $data = Video::findOrFail($request->id);
            $data->type = 'video';
        } else if ($request->type == 'episode') {
            $data = Episode::findOrFail($request->id);
            $data->type = 'episode';
        } else if ($request->type == 'season') {
            $data = Season::findOrFail($request->id);
            $data->type = 'season';
        } else {
            $data = Entertainment::findOrFail($request->id);
        }

        return view('frontend::perviewpayment', compact('data'));
    }

    public function processPayment(Request $request)
    {
        $paymentMethod = $request->input('payment_method');
        $price = $request->input('price');
        // dd($price);
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
            return $this->{$paymentHandlers[$paymentMethod]}($request, $price);
        }

        return redirect()->back()->withErrors('Invalid payment method.');
    }

    protected function StripePayment(Request $request)
    {
        $baseURL = env('APP_URL');
        $stripe_secret_key = GetpaymentMethod('stripe_secretkey');
        $currency = GetcurrentCurrency();

        $stripe = new \Stripe\StripeClient($stripe_secret_key);
        $price = $request->input('price');

        $currenciesWithoutCents = ['XAF', 'XOF', 'JPY', 'KRW'];
        $priceInCents = in_array(strtoupper($currency), $currenciesWithoutCents) ? $price : (int)round($price * 100);

        try {
            $checkout_session = $stripe->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => 'Pay Per View',
                        ],
                        'unit_amount' => $priceInCents,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'metadata' => [
                    'movie_id' => $request->input('movie_id'),
                    'type' => $request->input('type'),
                    'access_duration' => $request->input('access_duration'),
                    'available_for' => $request->input('available_for'),
                    'discount' => $request->input('discount'),
                ],
                'success_url' => $baseURL . '/payment/success/pay-per-view?gateway=stripe&session_id={CHECKOUT_SESSION_ID}'
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

    protected function PaystackPayment(Request $request)
    {
        try {
            $baseURL = env('APP_URL');
            $paystackSecretKey = GetpaymentMethod('paystack_secretkey');
            $price = $request->input('price');
            $priceInKobo = $price * 100;

            $callbackUrl = $baseURL . '/payment/success/pay-per-view?' . http_build_query([
                'gateway' => 'paystack',
                'movie_id' => $request->input('movie_id'),
                'type' => $request->input('type'),
                'access_duration' => $request->input('access_duration'),
                'available_for' => $request->input('available_for'),
                'discount' => $request->input('discount'),
            ]);

            $currency=GetcurrentCurrency();
            $formattedCurrency = strtoupper($currency);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $paystackSecretKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.paystack.co/transaction/initialize', [
                'email' => auth()->user()->email,
                'amount' => $priceInKobo,
                'currency' => $formattedCurrency,
                'callback_url' => $callbackUrl,
                'metadata' => [
                    'movie_id' => $request->input('movie_id'),
                    'type' => $request->input('type'),
                    'access_duration' => $request->input('access_duration'),
                    'available_for' => $request->input('available_for'),
                    'discount' => $request->input('discount'),
                ],
            ]);

            $responseBody = $response->json();

            if ($responseBody['status']) {
                return response()->json([
                    'success' => true,
                    'authorization_url' => $responseBody['data']['authorization_url']
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => __('messages.something_wrong_choose_another')
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    protected function RazorpayPayment(Request $request, float $price)
    {
        $baseURL = env('APP_URL');
        $razorpayKey = GetpaymentMethod('razorpay_publickey');
        $razorpaySecret = GetpaymentMethod('razorpay_secretkey');
        $plan_id = $request->input('plan_id');
        $currency = GetcurrentCurrency();
        $supportedCurrencies = ['INR', 'USD', 'EUR', 'GBP', 'SGD', 'AED'];
        $formattedCurrency = strtoupper($currency);

        $api = new \Razorpay\Api\Api($razorpayKey, $razorpaySecret);

        try {
            if (!in_array($formattedCurrency, $supportedCurrencies)) {
                $formattedCurrency = 'INR';
                $price = $price;
            }
            $amount = $price * 100;

            $order = $api->order->create([
                'receipt' => 'order_' . time(),
                'amount' => $amount,
                'currency' => $formattedCurrency,
                'payment_capture' => 1
            ]);

            session(['razorpay_order_id' => $order['id']]);

            return response()->json([
                'key' => $razorpayKey,
                'amount' => $amount,
                'currency' => $formattedCurrency,
                'name' => config('app.name'),
                'description' => 'Pay Per View Payment',
                'plan_id' => $plan_id,
                'order_id' => $order['id'],
                'success_url' => route('payperview.payment.success', [
                    'movie_id' => $request->movie_id,
                    'type' => $request->type,
                    'access_duration' => $request->access_duration,
                    'available_for' => $request->available_for,
                    'discount' => $request->discount,
                    'plan_id' => $request->plan_id,
                ]),
                'prefill' => [
                    'name' => auth()->user()->name ?? '',
                    'email' => auth()->user()->email ?? '',
                    'contact' => auth()->user()->phone ?? ''
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Currency not supported. Please try with INR or contact support.',
                'details' => $e->getMessage()
            ], 400);
        }
    }

    protected function FlutterwavePayment(Request $request)
    {
        try {
            $baseURL = env('APP_URL');
            $flutterwavePublicKey = GetpaymentMethod('flutterwave_publickey');
            $price = $request->input('price');

            $txRef = 'PPV_' . uniqid() . '_' . time();

            $callbackUrl = $baseURL . '/payment/success/pay-per-view?' . http_build_query([
                'gateway' => 'flutterwave',
                'movie_id' => $request->input('movie_id'),
                'type' => $request->input('type'),
                'access_duration' => $request->input('access_duration'),
                'available_for' => $request->input('available_for'),
                'discount' => $request->input('discount'),
            ]);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'public_key' => $flutterwavePublicKey,
                    'tx_ref' => $txRef,
                    'amount' => $price,
                    'currency' => 'NGN',
                    'payment_options' => 'card,banktransfer',
                    'customer' => [
                        'email' => auth()->user()->email,
                        'name' => auth()->user()->name,
                        'phonenumber' => auth()->user()->phone ?? ''
                    ],
                    'customizations' => [
                        'title' => config('app.name') . ' - Pay Per View',
                        'description' => 'Payment for Pay Per View content',
                        'logo' => asset('images/logo.png')
                    ],
                    'redirect_url' => $callbackUrl
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    protected function PayPalPayment(Request $request)
    {
        try {
            $baseURL = env('APP_URL');
            $paypalClientId = GetpaymentMethod('paypal_clientid');

            if (!$paypalClientId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'PayPal client ID is not configured'
                ], 400);
            }

            $price = $request->input('price');

            return response()->json([
                'status' => 'success',
                'data' => [
                    'client_id' => $paypalClientId,
                    'currency' => 'USD',
                    'amount' => $price,
                    'return_url' => $baseURL . '/payment/success/pay-per-view?' . http_build_query([
                        'gateway' => 'paypal',
                        'movie_id' => $request->input('movie_id'),
                        'type' => $request->input('type'),
                        'access_duration' => $request->input('access_duration'),
                        'discount' => $request->input('discount'),
                    ])
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
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

    protected function handlePaymentSuccess(float $amount, string $payment_type, string $transaction_id, int $movie_id = null, string $type = null, int $access_duration = null, int $available_for = null, int $discount = null)
    {
        $user = Auth::user();

        if ($type == 'movie') {
            $movie = Entertainment::find($movie_id);
        } else if ($type == 'tvshow') {
            $movie = Entertainment::find($movie_id);
        } else if ($type == 'video') {
            $movie = Video::find($movie_id);
        } else if ($type == 'episode') {
            $movie = Episode::find($movie_id);
        } else if ($type == 'season') {
            $movie = season::find($movie_id);
        }


        $viewExpiry = now()->addDays((int)$available_for ?? 48); // default to 48 hours if not provide
        $payperview = PayPerView::create([
            'user_id' => $user->id,
            'movie_id' => $movie_id,
            'type' => $type,
            'content_price' => $movie->price,
            'price' => $amount,
            'discount_percentage' => $discount,
            'view_expiry_date' => $viewExpiry,
            'access_duration' => $access_duration,
            'available_for' => $available_for,
        ]);


        PayperviewTransaction::create([
            'user_id' => auth()->id(),
            'amount' => $amount,
            'payment_type' => $payment_type,
            'payment_status' => 'paid',
            'transaction_id' => $transaction_id,
            'pay_per_view_id' => $payperview->id,
        ]);


        // sendNotification([
        //     'notification_type' => $movie->purchase_type == 'rental' ? 'rent_video' : 'purchase_video',
        //     'user_id' => $user->id,
        //     'movie_id' => $movie_id,
        //     'id' =>$payperview->id,
        //     'user_name' => $user->full_name,
        //     'name' => $movie->name ?? 'Video',
        //     'content_type' => $type,
        //     'status' => 'success',
        //     'amount' => $amount,
        //     'notification_group' => 'pay_per_view',
        //     'start_date' => now()->toDateString(),
        //     'end_date' => $viewExpiry->toDateString(),
        // ]);

        sendNotification([
            'notification_type' => $movie->purchase_type == 'rental' ? 'rent_video' : 'purchase_video',
            'id' =>$payperview->id,
            'content_id' => $movie_id,
            'content_type' => $type,
            'name' => $movie->name ?? 'Video',
            'notification_group' => 'pay_per_view',
            'content_type' => $type,
            'user_id' => $user->id,
            'user_name' => $user->full_name,
            'start_date' => now()->toDateString(),
            'end_date' => $viewExpiry->toDateString(),
            'amount' => $amount,
            'transaction_id' => $transaction_id,
            'payment_type' => $payment_type,
            'payment_status' => 'paid',
        ]);

        return redirect('/')->with([
            'purchase_success' => 'Payment completed successfully!',
            'movie_name' => $movie->name ?? 'Video',
            'view_expiry' => $viewExpiry->format('j F, Y') // e.g., "3 March, 2024"
        ]);
    }


    protected function handleStripeSuccess(Request $request)
    {
        $sessionId = $request->input('session_id');
        $stripe_secret_key = GetpaymentMethod('stripe_secretkey');
        $stripe = new StripeClient($stripe_secret_key);

        try {
            $session = $stripe->checkout->sessions->retrieve($sessionId);
            return $this->handlePaymentSuccess(
                $session->amount_total / 100, 
                'stripe', 
                $session->payment_intent, 
                $session->metadata->movie_id ? (int)$session->metadata->movie_id : null, 
                $session->metadata->type, 
                $session->metadata->access_duration ? (int)$session->metadata->access_duration : null, 
                $session->metadata->available_for ? (int)$session->metadata->available_for : null, 
                $session->metadata->discount ? (int)$session->metadata->discount : null
            );
        } catch (\Exception $e) {
            return redirect('/')->with('error', 'Payment failed: ' . $e->getMessage());
        }
    }

    protected function handleRazorpaySuccess(Request $request)
    {
        $paymentId = $request->input('razorpay_payment_id');
        $razorpayKey = GetpaymentMethod('razorpay_publickey');
        $razorpaySecret = GetpaymentMethod('razorpay_secretkey');

        if (empty($razorpayKey) || empty($razorpaySecret) || empty($paymentId)) {
            return redirect('/')->with('error', 'Missing required payment information.');
        }

        try {
            $api = new \Razorpay\Api\Api($razorpayKey, $razorpaySecret);
            $payment = $api->payment->fetch($paymentId);

            // Capture payment if authorized
            if ($payment['status'] === 'authorized') {
                $payment = $payment->capture([
                    'amount' => $payment['amount'],
                    'currency' => $payment['currency'],
                ]);
            }

            if ($payment['status'] === 'captured') {
                return $this->handlePaymentSuccess(
                    $payment['amount'] / 100,
                    'razorpay',
                    $paymentId,
                    $request->input('movie_id') ? (int)$request->input('movie_id') : null,
                    $request->input('type'),
                    $request->input('access_duration') ? (int)$request->input('access_duration') : null,
                    $request->input('available_for') ? (int)$request->input('available_for') : null,
                    $request->input('discount') ? (int)$request->input('discount') : null
                );
            }

            return redirect('/')->with('error', 'Payment verification failed. Status: ' . $payment['status']);
        } catch (\Exception $e) {
            return redirect('/')->with('error', 'Payment processing error: ' . $e->getMessage());
        }
    }


    protected function handlePaystackSuccess(Request $request)
    {
        $reference = $request->input('reference');
        $paystackSecretKey = GetpaymentMethod('paystack_secretkey');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $paystackSecretKey,
            ])->get("https://api.paystack.co/transaction/verify/{$reference}");

            $responseBody = $response->json();

            if ($responseBody['status'] && isset($responseBody['data']['amount'])) {
                $movie_id = $request->query('movie_id');
                $type = $request->query('type');
                $access_duration = $request->query('access_duration');
                $available_for = $request->query('available_for');
                $discount = $request->query('discount');

                if (!$movie_id) {
                    throw new \Exception('Movie ID is required');
                }

                return $this->handlePaymentSuccess(
                    $responseBody['data']['amount'] / 100,
                    'paystack',
                    $responseBody['data']['reference'],
                    $movie_id ? (int)$movie_id : null,
                    $type,
                    $access_duration ? (int)$access_duration : null,
                    $available_for ? (int)$available_for : null,
                    $discount ? (int)$discount : null
                );
            }

            return redirect('/')->with('error', __('messages.payment_verification_failed'));
        } catch (\Exception $e) {
            return redirect('/')->with('error', 'Payment verification failed: ' . $e->getMessage());
        }
    }

    protected function handleFlutterwaveSuccess(Request $request)
    {
        try {
            $flutterwaveSecretKey = GetpaymentMethod('flutterwave_secretkey');
            $transactionId = $request->input('transaction_id');

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $flutterwaveSecretKey,
            ])->get("https://api.flutterwave.com/v3/transactions/{$transactionId}/verify");

            $responseData = $response->json();

            if ($responseData['status'] === 'success' && $responseData['data']['status'] === 'successful') {
                return $this->handlePaymentSuccess(
                    $responseData['data']['amount'],
                    'flutterwave',
                    $responseData['data']['id'],
                    $request->query('movie_id') ? (int)$request->query('movie_id') : null,
                    $request->query('type'),
                    $request->query('access_duration') ? (int)$request->query('access_duration') : null,
                    $request->query('available_for') ? (int)$request->query('available_for') : null,
                    $request->query('discount') ? (int)$request->query('discount') : null
                );
            }

            throw new \Exception('Payment verification failed');
        } catch (\Exception $e) {
            return redirect('/')->with('error', 'Payment verification failed: ' . $e->getMessage());
        }
    }

    protected function handlePayPalSuccess(Request $request)
    {
        try {
            $paypalClientId = GetpaymentMethod('paypal_clientid');
            $paypalSecret = GetpaymentMethod('paypal_secretkey');
            $paypalOrderId = $request->input('orderID');

            if (!$paypalClientId || !$paypalSecret || !$paypalOrderId) {
                throw new \Exception('Missing PayPal credentials or order ID.');
            }

            $apiBase = 'https://api-m.sandbox.paypal.com';

            $accessToken = Http::withBasicAuth($paypalClientId, $paypalSecret)
                ->asForm()
                ->post("$apiBase/v1/oauth2/token", ['grant_type' => 'client_credentials'])
                ->json()['access_token'] ?? null;

            if (!$accessToken) {
                throw new \Exception('Could not retrieve PayPal access token.');
            }

            $orderData = Http::withToken($accessToken)
                ->get("$apiBase/v2/checkout/orders/$paypalOrderId")
                ->json();

            if ($orderData['status'] !== 'COMPLETED') {
                throw new \Exception("Payment status is {$orderData['status']}.");
            }

            $amount = $orderData['purchase_units'][0]['amount']['value'];
            $availableFor = $request->query('available_for') ? (int)$request->query('available_for') : 48;

            return $this->handlePaymentSuccess(
                $amount,
                'paypal',
                $paypalOrderId,
                $request->query('movie_id') ? (int)$request->query('movie_id') : null,
                $request->query('type'),
                $request->query('access_duration') ? (int)$request->query('access_duration') : null,
                $availableFor,
                $request->query('discount') ? (int)$request->query('discount') : null
            );
        } catch (\Exception $e) {
            $message = app()->environment('production')
                ? 'Payment verification failed. Please contact support.'
                : 'Payment verification failed: ' . $e->getMessage();

            return redirect('/')->with('error', $message);
        }
    }


    public function savePaymentPayperview(Request $request)
    {
        $userId = $request->user_id ?? auth()->user()->id;
        $user = User::find($userId);
        $accessDuration = $request->input('available_for', 48); // default to 48 hours
        $viewExpiry = now()->addDays((int) $accessDuration);

        if ($request->type == 'movie') {
            $movie = Entertainment::find($request->movie_id);
        } else if ($request->type == 'tvshow') {
            $movie = Entertainment::find($request->movie_id);
        } else if ($request->type == 'video') {
            $movie = Video::find($request->movie_id);
        } else if ($request->type == 'episode') {
            $movie = Episode::find($request->movie_id);
        } else if ($request->type == 'season') {
            $movie = season::find($request->movie_id);
        }

        // Update or create the PayPerView record
        $payperview = PayPerView::create(
            [
                'user_id' => $userId,
                'movie_id' => $request->movie_id,
                'type' => $request->type,
                'content_price' => $movie->price,
                'price' => $request->price,
                'discount_percentage' => $request->discount,
                'view_expiry_date' => $viewExpiry,
                'access_duration' => $accessDuration,
                'available_for' => $request->available_for,
            ]
        );

        // Always create a new transaction
        PayperviewTransaction::create([
            'user_id' => $userId,
            'amount' => $request->price,
            'payment_type' => $request->payment_type,
            'payment_status' => $request->payment_status,
            'transaction_id' => $request->transaction_id,
            'pay_per_view_id' => $payperview->id,
        ]);

        sendNotification([
            'notification_type' => $movie->purchase_type == 'rental' ? 'rent_video' : 'purchase_video',
            'content_id' => $request->movie_id,
            'user_id' => $userId,
            'user_name' => $user->full_name,
            'name' => $movie->name ?? 'Video',
            'content_type' => $request->type,
            'status' => 'success',
            'amount' => $request->price,
            'notification_group' => 'pay_per_view',
            'start_date' => now()->toDateString(),
            'end_date' => $viewExpiry->toDateString(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Payment successful and content rented successfully.'], 200);
    }

    public function unlockVideos()
    {
        $user =  auth()->user();
        if (!$user) {
            return redirect()->route('login');
        }
        return view('frontend::unlockvideo');
    }

    public function allUnlockVideos(Request $request)
    {
        try {
            $user = Auth::user();

            // Get all purchased content
            $purchasedContent = [
                'movies' => MoviesResource::collection(
                    Entertainment::where('movie_access', 'pay-per-view')
                        ->where('type', 'movie')
                        ->where('status', 1)
                        ->when(request()->has('is_restricted'), function ($query) {
                            $query->where('is_restricted', request()->is_restricted);
                        })
                        ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                            $query->where('is_restricted', 0);
                        })
                        ->whereExists(function ($query) use ($user) {
                            $query->select('id')
                                ->from('pay_per_views')
                                ->whereColumn('movie_id', 'entertainments.id')
                                ->where('user_id', $user->id)
                                ->where('type', 'movie')
                                ->where(function ($q) {
                                    $q->whereNull('view_expiry_date')
                                        ->orWhere('view_expiry_date', '>', now());
                                })
                                ->where(function ($q) {
                                    $q->whereNull('first_play_date')
                                        ->orWhereRaw('DATE_ADD(first_play_date, INTERVAL access_duration DAY) > ?', [now()]);
                                });
                        })
                        ->get()
                )->map(function ($item) use ($user) {
                    $item->user_id = $user->id;
                    return $item;
                }),
                'tvshows' => TvshowResource::collection(
                    Entertainment::where('movie_access', 'pay-per-view')
                        ->where('type', 'tvshow')
                        ->where('status', 1)
                        ->whereExists(function ($query) use ($user) {
                            $query->select('id')
                                ->from('pay_per_views')
                                ->whereColumn('movie_id', 'entertainments.id')
                                ->where('user_id', $user->id)
                                ->where('type', 'tvshow')
                                ->where(function ($q) {
                                    $q->whereNull('view_expiry_date')
                                        ->orWhere('view_expiry_date', '>', now());
                                })
                                ->where(function ($q) {
                                    $q->whereNull('first_play_date')
                                        ->orWhereRaw('DATE_ADD(first_play_date, INTERVAL access_duration DAY) > ?', [now()]);
                                });
                        })
                        ->get()
                )->map(function ($item) use ($user) {
                    $item->user_id = $user->id;
                    return $item;
                }),
                'videos' => VideoResource::collection(
                    Video::where('access', 'pay-per-view')
                        ->where('status', 1)
                        ->when(request()->has('is_restricted'), function ($query) {
                            $query->where('is_restricted', request()->is_restricted);
                        })
                        ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                            $query->where('is_restricted', 0);
                        })
                        ->whereExists(function ($query) use ($user) {
                            $query->select('id')
                                ->from('pay_per_views')
                                ->whereColumn('movie_id', 'videos.id')
                                ->where('user_id', $user->id)
                                ->where('type', 'video')
                                ->where(function ($q) {
                                    $q->whereNull('view_expiry_date')
                                        ->orWhere('view_expiry_date', '>', now());
                                })
                                ->where(function ($q) {
                                    $q->whereNull('first_play_date')
                                        ->orWhereRaw('DATE_ADD(first_play_date, INTERVAL access_duration DAY) > ?', [now()]);
                                });
                        })
                        ->get()
                )->map(function ($item) use ($user) {
                    $item->user_id = $user->id;
                    return $item;
                }),
                'seasons' => SeasonResource::collection(
                    Season::where('access', 'pay-per-view')
                        ->where('status', 1)
                        ->whereExists(function ($query) use ($user) {
                            $query->select('id')
                                ->from('pay_per_views')
                                ->whereColumn('movie_id', 'seasons.id')
                                ->where('user_id', $user->id)
                                ->where('type', 'season')
                                ->where(function ($q) {
                                    $q->whereNull('view_expiry_date')
                                        ->orWhere('view_expiry_date', '>', now());
                                })
                                ->where(function ($q) {
                                    $q->whereNull('first_play_date')
                                        ->orWhereRaw('DATE_ADD(first_play_date, INTERVAL access_duration DAY) > ?', [now()]);
                                });
                        })
                        ->get()
                )->map(function ($item) use ($user) {
                    $item->user_id = $user->id;
                    return $item;
                }),
                'episodes' => EpisodeResource::collection(
                    Episode::where('access', 'pay-per-view')
                        ->where('status', 1)
                        ->when(request()->has('is_restricted'), function ($query) {
                            $query->where('is_restricted', request()->is_restricted);
                        })
                        ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                            $query->where('is_restricted', 0);
                        })
                        ->whereExists(function ($query) use ($user) {
                            $query->select('id')
                                ->from('pay_per_views')
                                ->whereColumn('movie_id', 'episodes.id')
                                ->where('user_id', $user->id)
                                ->where('type', 'episode')
                                ->where(function ($q) {
                                    $q->whereNull('view_expiry_date')
                                        ->orWhere('view_expiry_date', '>', now());
                                })
                                ->where(function ($q) {
                                    $q->whereNull('first_play_date')
                                        ->orWhereRaw('DATE_ADD(first_play_date, INTERVAL access_duration DAY) > ?', [now()]);
                                });
                        })
                        ->get()
                )->map(function ($item) use ($user) {
                    $item->user_id = $user->id;
                    return $item;
                })
            ];

            return response()->json([
                'status' => true,
                'data' => $purchasedContent
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

     public function allUnlockVideosV3(Request $request)
    {
        try {
            $device_type = getDeviceType($request);
            $user = Auth::user();

            // Create cache key based on user and request parameters
            $cacheKey = 'all_unlock_videos_v3_' . md5(json_encode([
                'user_id' => $user->id,
                'device_type' => $device_type,
                'is_restricted' => $request->is_restricted ?? null,
                'is_child_profile' => getCurrentProfileSession('is_child_profile')
            ]));

            // Use Redis caching with 5 minutes TTL
            $cachedResult = cacheApiResponse($cacheKey, 300, function () use ($request, $user, $device_type) {
                $userPlanId = Subscription::select('plan_id')
                ->where(['user_id' => $user->id, 'status' => 'active'])
                ->latest()
                ->first();
                $userPlanId = optional($userPlanId)->plan_id ?? 0;
                $deviceTypeResponse = Subscription::checkPlanSupportDevice($user->id, $device_type);
                $deviceTypeResponse = json_decode($deviceTypeResponse->getContent(), true);

                // Get all purchased content
                // Optimized: Fetch all purchased IDs grouped by type for setContentAccess
                $purchasedIds = PayPerView::where('user_id', $user->id)
                    ->where(function($q) {
                        $q->whereNull('view_expiry_date')
                          ->orWhere('view_expiry_date', '>', now());
                    })
                     ->where(function($q) {
                        $q->whereNull('first_play_date')
                          ->orWhereRaw('DATE_ADD(first_play_date, INTERVAL available_for DAY) > ?', [now()]);
                    })
                    ->get()
                    ->groupBy('type')
                    ->map(function($items) {
                        return $items->pluck('movie_id')->toArray();
                    })
                    ->toArray();

                $purchasedContent = [
                'movies' => MoviesResourceV3::collection(
                    Entertainment::where('movie_access', 'pay-per-view')
                        ->where('type', 'movie')
                        ->where('status', 1)
                        ->where('deleted_at',null)
                        ->when(request()->has('is_restricted'), function ($query) {
                            $query->where('is_restricted', request()->is_restricted);
                        })
                        ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                            $query->where('is_restricted', 0);
                        })
                        ->whereExists(function ($query) use ($user) {
                            $query->select('id')
                                ->from('pay_per_views')
                                ->whereColumn('movie_id', 'entertainments.id')
                                ->where('user_id', $user->id)
                                ->where('type', 'movie')
                                ->where(function ($q) {
                                    $q->whereNull('view_expiry_date')
                                        ->orWhere('view_expiry_date', '>', now());
                                })
                                ->where(function ($q) {
                                    $q->whereNull('first_play_date')
                                        ->orWhereRaw('DATE_ADD(first_play_date, INTERVAL available_for DAY) > ?', [now()]);
                                });
                        })
                        ->get()
                )->map(function ($item) use ($user, $device_type,$deviceTypeResponse,$userPlanId, $purchasedIds) {
                    $item->user_id = $user->id;
                    $item->poster_image = $device_type == 'tv' ? setBaseUrlWithFileName($item->poster_tv_url, 'image', $item->type) : setBaseUrlWithFileName($item->poster_url, 'image', $item->type);
                    $item->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                    $item->trailer_url =  $item->trailer_url_type == 'Local' ? setBaseUrlWithFileName($item->trailer_url, 'video', $item->type) : $item->trailer_url;
                    $item['access'] = 'pay-per-view';
                    $item = setContentAccess($item, $user->id, $userPlanId, $purchasedIds);
                    return $item;
                }),
                'tvshows' => TvshowResourceV3::collection(
                    Entertainment::where('movie_access', 'pay-per-view')
                        ->where('type', 'tvshow')
                        ->where('status', 1)
                        ->where('deleted_at',null)
                        ->when(request()->has('is_restricted'), function ($query) {
                            $query->where('is_restricted', request()->is_restricted);
                        })
                        ->whereExists(function ($query) use ($user) {
                            $query->select('id')
                                ->from('pay_per_views')
                                ->whereColumn('movie_id', 'entertainments.id')
                                ->where('user_id', $user->id)
                                ->where('type', 'tvshow')
                                ->where(function ($q) {
                                    $q->whereNull('view_expiry_date')
                                        ->orWhere('view_expiry_date', '>', now());
                                })
                                ->where(function ($q) {
                                    $q->whereNull('first_play_date')
                                        ->orWhereRaw('DATE_ADD(first_play_date, INTERVAL available_for DAY) > ?', [now()]);
                                });
                        })
                        ->get()
                )->map(function ($item) use ($user, $device_type,$deviceTypeResponse,$userPlanId, $purchasedIds) {
                    $item->user_id = $user->id;
                    $item->poster_image = $device_type == 'tv' ? setBaseUrlWithFileName($item->poster_tv_url, 'image', $item->type) : setBaseUrlWithFileName($item->poster_url, 'image', $item->type);
                    $item->trailer_url =  $item->trailer_url_type == 'Local' ? setBaseUrlWithFileName($item->trailer_url, 'video', $item->type) : $item->trailer_url;
                    $item['access'] = 'pay-per-view';
                    $item = setContentAccess($item, $user->id, $userPlanId, $purchasedIds);
                    $item->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                    return $item;
                }),
                'videos' => VideoResourceV3::collection(
                    Video::where('access', 'pay-per-view')
                        ->where('status', 1)
                        ->where('deleted_at',null)
                        ->when(request()->has('is_restricted'), function ($query) {
                            $query->where('is_restricted', request()->is_restricted);
                        })
                        ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                            $query->where('is_restricted', 0);
                        })
                        ->whereExists(function ($query) use ($user) {
                            $query->select('id')
                                ->from('pay_per_views')
                                ->whereColumn('movie_id', 'videos.id')
                                ->where('user_id', $user->id)
                                ->where('type', 'video')
                                ->where(function ($q) {
                                    $q->whereNull('view_expiry_date')
                                        ->orWhere('view_expiry_date', '>', now());
                                })
                                ->where(function ($q) {
                                    $q->whereNull('first_play_date')
                                        ->orWhereRaw('DATE_ADD(first_play_date, INTERVAL available_for DAY) > ?', [now()]);
                                });
                        })
                        ->get()
                )->map(function ($item) use ($user, $device_type,$deviceTypeResponse,$userPlanId, $purchasedIds) {
                    $item->user_id = $user->id;
                    $item->poster_image = $device_type == 'tv' ? setBaseUrlWithFileName($item->poster_tv_url, 'image', 'video') : setBaseUrlWithFileName($item->poster_url, 'image', 'video');
                    $item->trailer_url =  $item->trailer_url_type == 'Local' ? setBaseUrlWithFileName($item->trailer_url, 'video', $item->type) : $item->trailer_url;
                    $item['type'] = 'video';
                    $item = setContentAccess($item, $user->id, $userPlanId, $purchasedIds);
                    $item->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                    return $item;
                }),
                'seasons' => SeasonResourceV3::collection(
                    Season::where('access', 'pay-per-view')
                        ->where('status', 1)
                        ->where('deleted_at',null)
                        ->whereExists(function ($query) use ($user) {
                            $query->select('id')
                                ->from('pay_per_views')
                                ->whereColumn('movie_id', 'seasons.id')
                                ->where('user_id', $user->id)
                                ->where('type', 'season')
                                ->where(function ($q) {
                                    $q->whereNull('view_expiry_date')
                                        ->orWhere('view_expiry_date', '>', now());
                                })
                                ->where(function ($q) {
                                    $q->whereNull('first_play_date')
                                        ->orWhereRaw('DATE_ADD(first_play_date, INTERVAL available_for DAY) > ?', [now()]);
                                });
                        })
                        ->get()
                )->map(function ($item) use ($user, $device_type,$deviceTypeResponse,$userPlanId, $purchasedIds) {
                    $item->user_id = $user->id;
                    $item->poster_image = $device_type == 'tv' ? setBaseUrlWithFileName($item->poster_tv_url, 'image', 'season') : setBaseUrlWithFileName($item->poster_url, 'image', 'season');
                    $item->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                    $item->trailer_url =  $item->trailer_url_type == 'Local' ? setBaseUrlWithFileName($item->trailer_url, 'video', $item->type) : $item->trailer_url;
                    $item->plan_id =$item->plan_id ?? 0;
                    $item['type'] = 'season';
                    $item = setContentAccess($item, $user->id, $userPlanId, $purchasedIds);
                    $item->seasonData = $item->seasons->map(function ($season) {
                        return [
                            'id'            => $season->id,
                            'name'          => $season->name,
                            'season_id'     => $season->id,
                            'total_episode' => $season->episodes()->count(),
                        ];
                    });
                    return $item;
                }),
                'episodes' => EpisodeResourceV3::collection(
                    Episode::where('access', 'pay-per-view')
                        ->where('status', 1)
                        ->where('deleted_at',null)
                        ->when(request()->has('is_restricted'), function ($query) {
                            $query->where('is_restricted', request()->is_restricted);
                        })
                        ->whereExists(function ($query) use ($user) {
                            $query->select('id')
                                ->from('pay_per_views')
                                ->whereColumn('movie_id', 'episodes.id')
                                ->where('user_id', $user->id)
                                ->where('type', 'episode')
                                ->where(function ($q) {
                                    $q->whereNull('view_expiry_date')
                                        ->orWhere('view_expiry_date', '>', now());
                                })
                                ->where(function ($q) {
                                    $q->whereNull('first_play_date')
                                        ->orWhereRaw('DATE_ADD(first_play_date, INTERVAL available_for DAY) > ?', [now()]);
                                });
                        })
                        ->get()
                )->map(function ($item) use ($user, $device_type,$deviceTypeResponse,$userPlanId, $purchasedIds) {
                    $item->user_id = $user->id;
                    $item['type'] = 'episode';
                    $item->poster_image = $device_type == 'tv' ? setBaseUrlWithFileName($item->poster_tv_url, 'image', 'episode') : setBaseUrlWithFileName($item->poster_url, 'image', 'episode');
                    $item->trailer_url =  $item->trailer_url_type == 'Local' ? setBaseUrlWithFileName($item->trailer_url, 'video', $item->type) : $item->trailer_url;
                    $item = setContentAccess($item, $user->id, $userPlanId, $purchasedIds);
                    $item->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                    $item->plan_id =$item->plan_id ?? 0;
                    $item->tv_show_data = [
                        'id' => $item->season_id,
                        'season_id' => $item->season_id,
                    ];
                    return $item;
                })
                ];

                return $purchasedContent;
            });

            return response()->json([
                'status' => true,
                'data' => $cachedResult['data']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function setStartDate(Request $request)
    {
        // dd($request->all());
        $payPerView = PayPerView::where('user_id', $request->user_id)
            ->where('movie_id', $request->entertainment_id)
            ->where('type', $request->entertainment_type)
            ->where(function ($query) {
                $query->whereNull('view_expiry_date')
                    ->orWhere('view_expiry_date', '>', now());
            })
            ->whereNull('first_play_date')
            ->first();

        if ($payPerView && is_null($payPerView->first_play_date)) {
            $payPerView->first_play_date = now();
            $payPerView->save();
        }

        return response()->json(['success' => true]);
    }

    public function peyPerView()
    {
        return view('frontend::payperview');
    }

    public function PayPerViewList(Request $request)
    {
        // Validate pagination parameters individually
        if ($request->has('page') && (!is_numeric($request->page) || $request->page < 1)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid page parameter'
            ], 422);
        }

        if ($request->has('per_page') && (!is_numeric($request->per_page) || $request->per_page < 1)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid per_page parameter'
            ], 422);
        }

        $perPage = $request->input('per_page', 10);



        // === Get Pay-Per-View Videos ===
        $videoList = Video::with('VideoStreamContentMappings', 'plan')
            ->where('status', 1)
            ->where('access', 'pay-per-view')
            ->when(request()->has('is_restricted'), function ($query) {
                $query->where('is_restricted', request()->is_restricted);
            })
            ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                $query->where('is_restricted', 0);
            });

        $videoData = $videoList->orderBy('updated_at', 'desc')->get();
        $videoResponse = \Modules\Video\Transformers\Backend\VideoResourceV3::collection($videoData)->toArray($request);

        $episodeList = Episode::where('access', 'pay-per-view')
            ->where('status', 1)
            ->when(request()->has('is_restricted'), function ($query) {
                $query->where('is_restricted', request()->is_restricted);
            })
            ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                $query->where('is_restricted', 0);
            });

        $episodeData = $episodeList->orderBy('updated_at', 'desc')->get();
        $episodeResponse = \Modules\Entertainment\Transformers\Backend\EpisodeResourceV3::collection($episodeData)->toArray($request);

        // === Get Pay-Per-View Movies ===
        $movieList = Entertainment::select([
            'id','name','type','price','slug','purchase_type','type','access_duration','discount','available_for',
            'plan_id','description','trailer_url_type','is_restricted','language','imdb_rating',
            'content_rating','duration','video_upload_type','release_date','trailer_url','video_url_input',
            'poster_url','thumbnail_url','movie_access'
        ])
            ->with(['plan:id,level','genresdata:id,name'])
            ->where('movie_access', 'pay-per-view')
            ->where('status', 1)
            ->orderBy('id', 'desc');

        $movieData = $movieList->when(request()->has('is_restricted'), function ($query) {
                $query->where('is_restricted', request()->is_restricted);
            })
            ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                $query->where('is_restricted', 0);
            })
            ->get()
            ->map(function ($item) {
                // Preserve aliases expected by MoviesResourceV2/templates
                $item->e_id = $item->id;
                $item->thumbnail_image = $item->thumbnail_url;
                $item->base_url = $item->trailer_url;
                $item->plan_level = optional($item->plan)->level;
                return $item;
            });
        $movieResponse = CommonContentResourceV3::collection($movieData)->toArray($request);



        // === Merge Video + Movie Data ===
        $combinedData = array_merge($videoResponse, $movieResponse, $episodeResponse);

        // Optional: sort combined data by release date (descending)
        usort($combinedData, function ($a, $b) {
            $aDate = isset($a['release_date']) && !empty($a['release_date']) ? strtotime($a['release_date']) : 0;
            $bDate = isset($b['release_date']) && !empty($b['release_date']) ? strtotime($b['release_date']) : 0;
            return $bDate <=> $aDate;
        });

        // === Pagination manually since we're merging two lists ===
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $items = collect($combinedData);
        $paginated = new LengthAwarePaginator(
            $items->forPage($currentPage, $perPage),
            $items->count(),
            $perPage,
            $currentPage,
            ['path' => url()->current()]
        );

        // === Handle AJAX response ===
        if ($request->has('is_ajax') && $request->is_ajax == 1) {
            $html = $this->renderCardComponents($paginated->items());

            return response()->json([
                'status' => true,
                'html' => $html,
                'message' => 'Pay-per-view list loaded',
                'hasMore' => $paginated->hasMorePages(),
            ]);
        }

        // === JSON response for API (non-AJAX) ===
        return response()->json([
            'status' => true,
            'data' => $paginated,
            'message' => 'Pay-per-view list loaded',
        ]);
    }

    public function videoPayPerViewList(Request $request)
    {
        $perPage = (int) $request->input('per_page', 12);
        $page = (int) $request->input('page', 1);

        $videoList = Video::with('VideoStreamContentMappings', 'plan')
            ->where('status', 1)
            ->where('access', 'pay-per-view')
            ->when(request()->has('is_restricted'), function ($query) {
                $query->where('is_restricted', request()->is_restricted);
            })
            ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                $query->where('is_restricted', 0);
            })
            ->orderBy('updated_at', 'desc')
            ->get();

        $collection = collect(BackendVideoResourceV3::collection($videoList)->toArray($request));

        $paginator = new LengthAwarePaginator(
            $collection->forPage($page, $perPage)->values(),
            $collection->count(),
            $perPage,
            $page,
            ['path' => url()->current()]
        );

        $html = '';

        if ($paginator && $paginator->items())
        {
            $html .= view('frontend::components.card.card_video', ['values' => $paginator->items()])->render();
        }


        return response()->json([
            'status' => true,
            'html' => $html,
            'hasMore' => $paginator->hasMorePages(),
            'message' => 'Pay-per-view video list loaded',
        ]);
    }

    /**
     * Render card components efficiently
     */
    private function renderCardComponents(array $items)
    {
        $html = '';
        $userId = auth()->id();


        if (!is_array($items) && !is_object($items)) {
            return $html;
        }

    if (is_object($items) && method_exists($items, 'toArray')) {
            $items = $items->toArray();
        }



        // Pre-fetch watch list items for better performance
        $watchListItems = [];
        if ($userId) {
            $watchListItems = WatchList::where('user_id', $userId)
                ->pluck('entertainment_id', 'entertainment_id')
                ->toArray();
        }

        foreach ($items as $item) {

            if (!is_array($item)) {
                continue;
            }

            if (!isset($item['id']) && !isset($item['name'])) {
                continue;
            }

            $type = $item['type'] ?? (isset($item['season_id']) ? 'episode' : (isset($item['access']) ? 'video' : 'movie'));

            // Add watch list status efficiently
            if ($userId && in_array($type, ['movie', 'tvshow', 'video']) && isset($item['id'])) {
                $item['is_watch_list'] = isset($watchListItems[$item['id']]);
            }

            // Render appropriate card component
            $html .= $this->getCardComponent($type, $item);
        }

        return $html;
    }

    /**
     * Get the appropriate card component based on type
     */
    private function getCardComponent(string $type, array $item)
    {
        // Wrap single item in array for card templates that expect multiple items
        $values = [$item];

        switch ($type) {
            case 'movie':
                return view('frontend::components.card.card_movie', ['values' => $values])->render();

            case 'tvshow':
                return view('frontend::components.card.card_tvshow', ['values' => $values])->render();

            case 'video':
                return view('frontend::components.card.card_video', ['values' => $values])->render();

            case 'episode':
                // Debug: Log the item structure for episodes
                if (!isset($item['slug'])) {
                    $item['slug'] = 'unknown';
                }
                if (!isset($item['poster_image'])) {
                    $item['poster_image'] = asset('default-image/default-movie.jpg');
                }

                return view('frontend::components.card.card_pay_per_view', ['value' => $item])->render();

            default:
                return '';
        }
    }

    public function moviePayPerViewList(Request $request)
    {
        $perPage = (int) $request->input('per_page', 12);
        $page = (int) $request->input('page', 1);

        $movieList = Entertainment::select([
            'id','name','slug','type','release_date','trailer_url','plan_id','description',
            'trailer_url_type','is_restricted','language','imdb_rating','content_rating',
            'duration','video_upload_type','poster_url','thumbnail_url','poster_tv_url',
            'video_url_input','movie_access','price','purchase_type','access_duration',
            'discount','available_for'
        ])
        ->with([
            'plan:id,level',
            'genresdata:id,name'
        ])
        ->where('movie_access', 'pay-per-view')
        ->where('status', 1)
        ->when(request()->has('is_restricted'), function ($query) {
            $query->where('is_restricted', request()->is_restricted);
        })
        ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
            $query->where('is_restricted', 0);
        })
        ->orderBy('updated_at', 'desc')
        ->get();

        $collection = collect(CommonContentResourceV3::collection($movieList)->toArray($request));

        $paginator = new LengthAwarePaginator(
            $collection->forPage($page, $perPage)->values(),
            $collection->count(),
            $perPage,
            $page,
            ['path' => url()->current()]
        );

        $html = '';

        if ($paginator)
        {
            $html .= view('frontend::components.card.card_movie', ['values' => $paginator->items()])->render();
        }


        return response()->json([
            'status' => true,
            'html' => $html,
            'hasMore' => $paginator->hasMorePages(),
            'message' => 'Pay-per-view movie list loaded',
        ]);
    }

    public function episodePayPerViewList(Request $request)
    {
        $perPage = (int) $request->input('per_page', 12);
        $page = (int) $request->input('page', 1);

        $episodeList = Episode::where('access', 'pay-per-view')
            ->where('status', 1)
            ->when(request()->has('is_restricted'), function ($query) {
                $query->where('is_restricted', request()->is_restricted);
            })
            ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                $query->where('is_restricted', 0);
            })
            ->orderBy('updated_at', 'desc')
            ->get();

        $collection = collect(BackendEpisodeResourceV3::collection($episodeList)->toArray($request));

        $paginator = new LengthAwarePaginator(
            $collection->forPage($page, $perPage)->values(),
            $collection->count(),
            $perPage,
            $page,
            ['path' => url()->current()]
        );

        $html = '';
        foreach ($paginator->items() as $item) {
            $html .= view('frontend::components.card.card_pay_per_view', ['value' => $item])->render();
        }

        return response()->json([
            'status' => true,
            'html' => $html,
            'hasMore' => $paginator->hasMorePages(),
            'message' => 'Pay-per-view episode list loaded',
        ]);
    }
}
