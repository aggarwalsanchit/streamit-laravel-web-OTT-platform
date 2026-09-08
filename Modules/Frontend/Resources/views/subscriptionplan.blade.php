@extends('frontend::layouts.master')

@section('title')
    {{ __('frontend.subscription_plan') }}
@endsection
@section('content')
    <div class="section-spacing-bottom">
        <div class="container" id="payment-container">
            <div class="page-title">
                <h4 class="m-0 text-center">{{ __('frontend.subscription_plan') }}</h4>
            </div>
            <div class="">
                <div
                    class="upgrade-plan d-flex flex-wrap gap-3 align-items-center justify-content-between rounded p-4 bg-warning-subtle border border-warning">
                    <div class="d-flex justify-content-center align-items-center gap-4">
                        <i class="ph ph-crown text-warning fs-2"></i>
                        <div>
                            @if (!empty($activeSubscriptions))
                                <h6 class="super-plan">{{ $activeSubscriptions->name }}</h6>
                                <p class="mb-0 text-body">{{ __('frontend.expiring_on') }}
                                    {{ formatDate($activeSubscriptions->end_date) }}</p>
                            @else
                                <h6 class="super-plan">{{ __('frontend.no_active_plan') }}</h6>
                                <p class="mb-0 text-body">{{ __('frontend.not_active_subscription') }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="d-flex gap-3">
                        @if (!empty($activeSubscriptions))
                            <button class="btn btn-warning subscription-btn">{{ __('frontend.upgrade') }}</button>
                        @else
                            <button class="btn btn-warning subscription-btn">{{ __('frontend.subscribe') }}</button>
                        @endif
                    </div>
                </div>

                <div class="subscription-plan-tabs">
                    <div class="text-center">
                        <ul class="nav nav-pills" id="pills-tab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="pills-monthly-tab" data-bs-toggle="pill"
                                    data-bs-target="#pills-monthly" type="button" role="tab"
                                    aria-controls="pills-monthly" aria-selected="true">{{ __('messages.monthly') }}</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="pills-yearly-tab" data-bs-toggle="pill"
                                    data-bs-target="#pills-yearly" type="button" role="tab"
                                    aria-controls="pills-yearly" aria-selected="false">{{ __('messages.yearly') }}</button>
                            </li>
                        </ul>
                    </div>
                    <div class="tab-content" id="pills-tabContent">
                        <div class="tab-pane fade show active" id="pills-monthly" role="tabpanel"
                            aria-labelledby="pills-monthly-tab" tabindex="0">
                            <div class="row gy-4 row-cols-1 row-cols-sm-1 row-cols-md-2 row-cols-lg-2 row-cols-xl-3">
                                <!-- Subscription Plan Basic -->
                                @foreach ($plans as $plan)
                                    @continue(strtolower($plan->duration ?? '') !== 'month')
                                    <div class="col">
                                        @include('frontend::components.subscription_plan_card', [
                                            'plan' => $plan,
                                            'currentPlanId' => $currentPlanId,
                                        ])
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="tab-pane fade" id="pills-yearly" role="tabpanel" aria-labelledby="pills-yearly-tab"
                            tabindex="0">
                            <div class="row gy-4 row-cols-1 row-cols-sm-1 row-cols-md-2 row-cols-lg-2 row-cols-xl-3">
                                @foreach ($plans as $plan)
                                    @continue(strtolower($plan->duration ?? '') !== 'year')
                                    <div class="col">
                                        @include('frontend::components.subscription_plan_card', [
                                            'plan' => $plan,
                                            'currentPlanId' => $currentPlanId,
                                        ])
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>


            </div>
        </div>
    </div>

<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script>
        $(document).ready(function() {

            const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
            $('.subscription-btn').on('click', function() {
                var planId = $(this).data('plan-id');
                var planName = $(this).data('plan-name');

                $.ajax({
                    url: `${baseUrl}/select-plan`, // Your route to handle plan selection
                    method: 'POST',
                    data: {
                        plan_id: planId,
                        plan_name: planName,
                        _token: '{{ csrf_token() }}' // CSRF token for security
                    },
                    success: function(response) {
                        $('#payment-container').empty();
                        $('#payment-container').html(response
                            .view); // Inject the view into a container
                    },
                    error: function(xhr) {
                        if (xhr.status === 419) {
                            // Token mismatch error, redirect to login page
                            window.location.href = `${baseUrl}/login`;
                        } else {
                            // Handle other errors
                            alert('An error occurred while selecting the plan.');
                        }
                    }
                });
            });
        });
    </script>
@endsection
