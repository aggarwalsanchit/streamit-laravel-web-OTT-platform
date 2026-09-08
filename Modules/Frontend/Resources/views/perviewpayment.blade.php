@extends('frontend::layouts.master')
@section('title')
    {{ __('messages.pay_per_view') }}
@endsection
@section('content')
    <div class="section-spacing-bottom">
        <div class="container">
            <div class="mt-5">
                <div class="row">
                    <div class="col-lg-3">
                        <form id="plan-form">
                            <div class="col-12 mb-4">
                                <label class="form-check stripe-payment-form p-4 position-relative rounded">
                                    <span class="form-check-label">
                                        <span class="fw-medium d-block mb-2">{{ $data->name }}</span>
                                        <span class="h4">{{ Currency::format($data->price) }}<span
                                                class="font-size-14 text-body"></span></span>
                                    </span>
                                </label>
                            </div>
                        </form>
                    </div>
                    <div class="col-lg-9 mt-lg-0 mt-5">
                        <form action="{{ route('process-payment.payperview') }}" method="POST" id="payment-form">
                            @csrf
                            <div class="form-group">
                                <div class="card">
                                    <div class="card-header pb-0">
                                        <input type="hidden" id="selected-price" name="price"
                                            value="{{ $data->price - ($data->price * $data->discount) / 100 }}">
                                        <input type="hidden" name="type" value="{{ $data->type }}">
                                        <input type="hidden" name="movie_id" value="{{ $data->id }}">
                                        <input type="hidden" name="discount" value="{{ $data->discount }}">
                                        <input type="hidden" name="access_duration" value="{{ $data->access_duration }}">
                                        <input type="hidden" name="available_for" value="{{ $data->available_for }}">
                                        <label class="form-label">{{ __('frontend.choose_payment_method') }}:</label>
                                    </div>
                                    <div class="card-body">
                                        <input type="hidden" id="payment-method" name="payment_method">
                                        <div class="list-unstyled mb-0 payment-method-list d-flex flex-wrap gap-3">
                                            @php
                                                $payment_methods = [
                                                    'str_payment_method' => 'stripe',
                                                    'razor_payment_method' => 'razorpay',
                                                    'paystack_payment_method' => 'paystack',
                                                    'paypal_payment_method' => 'paypal',
                                                    'flutterwave_payment_method' => 'flutterwave',
                                                    'cinet_payment_method' => 'cinet',
                                                    'sadad_payment_method' => 'sadad',
                                                    'airtel_payment_method' => 'airtel',
                                                    'phonepe_payment_method' => 'phonepe',
                                                    'midtrans_payment_method' => 'midtrans',
                                                ];
                                            @endphp
                                            @foreach ($payment_methods as $setting => $method)
                                                @if (setting($setting) == 1)
                                                    <div data-setting="radio" class="payment-method-box">
                                                        <input type="radio" value="{{ strtolower($method) }}"
                                                            class="btn-check payment-method-radio"
                                                            name="payment_method_radio" id="pm-{{ $method }}">
                                                        <label
                                                            class="btn-box d-flex align-items-center justify-content-center gap-1"
                                                            for="pm-{{ $method }}" title="{{ $method }}">
                                                            @if ($method == 'stripe')
                                                                <img src="{{ asset('images/stripe.svg') }}" alt="Stripe"
                                                                    class="payment-icon">
                                                            @elseif($method == 'razorpay')
                                                                <img src="{{ asset('images/razorpay.svg') }}"
                                                                    alt="Razorpay" class="payment-icon">
                                                            @elseif($method == 'paystack')
                                                                <img src="{{ asset('images/paystack.svg') }}"
                                                                    alt="Paystack" class="payment-icon">
                                                            @elseif($method == 'paypal')
                                                                <img src="{{ asset('images/paypal.svg') }}" alt="PayPal"
                                                                    class="payment-icon">
                                                            @elseif($method == 'flutterwave')
                                                                <img src="{{ asset('images/flutterwave.svg') }}"
                                                                    alt="Flutterwave" class="payment-icon">
                                                            @else
                                                                <i class="ph-bold ph-credit-card text-primary"></i>
                                                            @endif
                                                            <span class="ms-2">{{ __('frontend.' . $method) }}</span>
                                                        </label>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <div class="payment-detail rounded">
                                    <h6 class="font-size-18">{{ __('frontend.payment_details') }}</h6>
                                    <div class="table-responsive">
                                        <table class="table table-borderless">
                                            <tbody>
                                                <tr>
                                                    <td>{{ __('frontend.price') }}</td>
                                                    <td>
                                                        <h6 class="font-size-18 text-end mb-0" id="price">
                                                            {{ Currency::format($data->price) }}</h6>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>{{ __('messages.lbl_discount') }}</td>
                                                    <td>
                                                        <h6 class="font-size-18 text-end mb-0 text-success" id="discount">
                                                            {{ Currency::format(($data->price * $data->discount) / 100) }}
                                                        </h6>
                                                    </td>
                                                </tr>
                                                <tr class="border-bottom">
                                                    <td>{{ __('frontend.total') }}</td>
                                                    <td>
                                                        <h6 class="font-size-18 text-end mb-0" id="total">
                                                            {{ Currency::format($data->price - ($data->price * $data->discount) / 100) }}
                                                        </h6>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <div class="mt-4">
                                            <div class="d-flex justify-content-between gap-3">
                                                <h6>{{ __('frontend.total_payment') }}</h6>
                                                <div class="d-flex justify-content-center align-items-center gap-3">
                                                    <h5 class="mb-0" id="total-payment">
                                                        {{ Currency::format($data->price - ($data->price * $data->discount) / 100) }}
                                                    </h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <div class="d-flex justify-content-end">
                                        <div class="d-flex justify-content-center align-items-center gap-4 flex-wrap">
                                            <div class="d-flex justify-content-center align-items-center gap-2">
                                                <i class="ph ph-lock-key text-primary"></i>
                                                <p class="mb-0">{{ __('frontend.payment_secure') }}</p>
                                            </div>
                                            <button type="submit"
                                                class="btn btn-primary">{{ __('frontend.proceed_payment') }}</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="paypal-button-container" style="display: none;"></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center">
                <div class="modal-header justify-content-center">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="errorModalMessage"></p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="paypalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">PayPal Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="paypal-button-container-modal"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script src="https://checkout.flutterwave.com/v3.js"></script>
    <script>
        function formatCurrencyvalue(value) {
            if (window.currencyFormat !== undefined) {
                return window.currencyFormat(value);
            }
            return value;
        }

        function loadPayPalScript(clientId) {
            return new Promise((resolve, reject) => {
                // Remove existing PayPal script if any
                const existingScript = document.getElementById('paypal-script');
                if (existingScript) {
                    existingScript.remove();
                }

                const script = document.createElement('script');
                script.id = 'paypal-script';
                script.src = `https://www.paypal.com/sdk/js?client-id=${clientId}&currency=USD`;
                script.async = true;

                script.onload = () => resolve();
                script.onerror = () => reject(new Error('PayPal SDK failed to load'));

                document.body.appendChild(script);
            });
        }

        $(document).ready(function() {
            // Initialize payment method selection
            const $pmRadios = $('.payment-method-radio');
            const $pmHidden = $('#payment-method');

            // Auto-select first available payment method
            if ($pmRadios.length > 0) {
                $pmRadios.first().prop('checked', true);
                $pmHidden.val($pmRadios.first().val());
            }

            // Handle radio button changes
            $pmRadios.on('change', function() {
                $pmHidden.val($(this).val());
            });

            @if (session('error'))
                $('#errorModalMessage').text('{{ session('error') }}');
                if (!$('#errorModal').hasClass('show')) {
                    $('#errorModal').modal('show');
                }
            @endif



            $('#payment-form').on('submit', function(e) {
                e.preventDefault(); // Prevent default form submission
                const paymentMethod = $('#payment-method').val();
                if (!paymentMethod) {
                    $('#errorModalMessage').text('Please select a payment method before proceeding.');
                    if (!$('#errorModal').hasClass('show')) {
                        $('#errorModal').modal('show');
                    }
                    return; // Exit the function
                }
                const formData = $(this).serialize();
                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        }
                    },
                    error: function(xhr) {
                        const errorResponse = xhr.responseJSON || {};
                        // Fix: Use response.message instead of response.error
                        const errorMessage = errorResponse.message || errorResponse.error ||
                            'An error occurred. Please try another payment method.';
                        // Display an error modal using Bootstrap
                        $('#errorModalMessage').text(errorMessage);
                        if (!$('#errorModal').hasClass('show')) {
                            $('#errorModal').modal('show');
                        }
                    }
                });
            });

            // Razor Pay Integration
            $('#payment-form').on('submit', function(e) {
                if (document.getElementById('payment-method').value !== 'razorpay') {
                    return true;
                }

                e.preventDefault();

                $.ajax({
                    url: $(this).attr('action'),
                    type: "POST",
                    data: $(this).serialize(),
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        var options = {
                            "key": response.key,
                            "amount": response.amount,
                            "currency": "INR",
                            "name": response.name,
                            "description": response.description,
                            "order_id": response.order_id,
                            "handler": function(paymentResponse) {
                                if (paymentResponse.razorpay_payment_id) {
                                    const successUrl = new URL(response.success_url);
                                    successUrl.searchParams.append('gateway',
                                        'razorpay');
                                    successUrl.searchParams.append(
                                        'razorpay_payment_id', paymentResponse
                                        .razorpay_payment_id);
                                    $('#payment-processing').show();
                                    window.location.href = successUrl.toString();
                                } else {
                                    $('#errorModalMessage').text(
                                        'Payment was not completed. Please try again.'
                                    );
                                    if (!$('#errorModal').hasClass('show')) {
                                        $('#errorModal').modal('show');
                                    }
                                }
                            },
                            "modal": {
                                "ondismiss": function() {
                                    $('#errorModalMessage').text(
                                        'Payment was cancelled. Please try again.');
                                    if (!$('#errorModal').hasClass('show')) {
                                        $('#errorModal').modal('show');
                                    }
                                }
                            },
                            "prefill": {
                                "name": response.prefill.name ?? '-',
                                "email": response.prefill.email,
                                "contact": response.prefill.contact ?? '-'
                            },
                            "theme": {
                                "color": "#F37254"
                            }
                        };
                        options.modal.ondismiss = function() {
                            $('#errorModalMessage').text('Payment cancelled by user');
                            if (!$('#errorModal').hasClass('show')) {
                                $('#errorModal').modal('show');
                            }
                        };

                        var rzp1 = new Razorpay(options);
                        rzp1.on('payment.failed', function(response) {
                            $('#errorModalMessage').text('Payment failed: ' + response
                                .error.description);
                            if (!$('#errorModal').hasClass('show')) {
                                $('#errorModal').modal('show');
                            }
                        });
                        rzp1.open();
                    },
                    error: function(xhr) {
                        if (xhr.status === 401) {
                            window.location.href = xhr.responseJSON.redirect_url;
                        } else {
                            console.log('Something went wrong. Please try again.');
                        }
                    }
                });
            });

            // Flutterwave Integration
            $('#payment-form').on('submit', function(e) {
                if (document.getElementById('payment-method').value !== 'flutterwave') {
                    return true;
                }

                e.preventDefault();

                $.ajax({
                    url: $(this).attr('action'),
                    type: "POST",
                    data: $(this).serialize(),
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            console.log(response);
                            const config = response.data;
                            FlutterwaveCheckout({
                                public_key: config.public_key,
                                tx_ref: config.tx_ref,
                                amount: config.amount,
                                currency: config.currency,
                                payment_options: config.payment_options,
                                customer: {
                                    email: config.customer.email,
                                    name: config.customer.name,
                                    phone_number: config.customer.phonenumber
                                },
                                customizations: config.customizations,
                                callback: function(response) {
                                    if (response.status === "successful") {
                                        window.location.href = config.redirect_url +
                                            '&transaction_id=' + response
                                            .transaction_id;
                                    } else {
                                        $('#errorModalMessage').text(
                                            'Payment failed or was cancelled');
                                        if (!$('#errorModal').hasClass('show')) {
                                            $('#errorModal').modal('show');
                                        }
                                    }
                                },
                                onclose: function() {

                                }
                            });
                        } else {
                            $('#errorModalMessage').text(response.message ||
                                'Payment initialization failed');
                            if (!$('#errorModal').hasClass('show')) {
                                $('#errorModal').modal('show');
                            }
                        }
                    },
                    error: function(xhr) {
                        const errorMsg = xhr.responseJSON?.message ||
                            'Something went wrong. Please try again.';
                        $('#errorModalMessage').text(errorMsg);
                        if (!$('#errorModal').hasClass('show')) {
                            $('#errorModal').modal('show');
                        }
                    }
                });
            });
            $('#payment-form').on('submit', function(e) {
                if (document.getElementById('payment-method').value !== 'paystack') {
                    return true;
                }

                e.preventDefault();

                $.ajax({
                    url: $(this).attr('action'),
                    type: "POST",
                    data: $(this).serialize(),
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success && response.authorization_url) {
                            window.location.href = response.authorization_url;
                        } else {
                            $('#errorModalMessage').text('Payment initialization failed');
                            if (!$('#errorModal').hasClass('show')) {
                                $('#errorModal').modal('show');
                            }
                        }
                    },
                    error: function(xhr) {
                        const errorMsg = xhr.responseJSON?.error ||
                            'Something went wrong. Please try again.';
                        $('#errorModalMessage').text(errorMsg);
                        if (!$('#errorModal').hasClass('show')) {
                            $('#errorModal').modal('show');
                        }
                    }
                });
            });
            $('#payment-form').on('submit', async function(e) {
                if ($('#payment-method').val() !== 'paypal') {
                    return true;
                }

                e.preventDefault();

                try {
                    const response = await $.ajax({
                        url: $(this).attr('action'),
                        type: 'POST',
                        data: $(this).serialize(),
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });

                    if (response.status === 'success' && response.data?.client_id) {
                        const config = response.data;

                        await loadPayPalScript(config.client_id);

                        paypal.Buttons({
                            createOrder(data, actions) {
                                return actions.order.create({
                                    purchase_units: [{
                                        amount: {
                                            value: config.amount,
                                            currency_code: config.currency
                                        }
                                    }]
                                });
                            },
                            onApprove(data, actions) {
                                return actions.order.capture().then(orderData => {
                                    window.location.href =
                                        `${config.return_url}&orderID=${orderData.id}`;
                                });
                            },
                            onError() {
                                showError('PayPal payment failed. Please try again.');
                                $('#paypalModal').modal('hide');
                            }
                        }).render('#paypal-button-container-modal');

                        $('#paypalModal').modal('show');
                    } else {
                        showError(response.message || 'PayPal is not properly configured.');
                    }
                } catch (xhr) {
                    const errorMsg = xhr.responseJSON?.message ||
                        'Something went wrong. Please try again.';
                    showError(errorMsg);
                }

                function showError(message) {
                    $('#errorModalMessage').text(message);
                    if (!$('#errorModal').hasClass('show')) {
                        $('#errorModal').modal('show');
                    }
                }
            });

        });
    </script>
@endsection
