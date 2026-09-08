@extends('frontend::layouts.master')

@section('title')
    {{ __('frontend.payment_history') }}
@endsection

@section('content')
    <div class="section-spacing">

        <div class="container-fluid">
            <div class="row gy-4">
                <div class="col-lg-3 col-md-4">
                    @include('frontend::components.account-settings-sidebar')
                </div>
                <div class="col-lg-9 col-md-8">
                    <div class="d-flex justify-content-start mb-4">
                        <h4 class="m-0 text-center">{{ __('frontend.payment_history') }}</h4>
                    </div>

                    <div class="section-spacing-bottom px-0">

                        <div class="table-responsive">
                            <table class="table payment-history table-borderless">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="text-white">{{ __('frontend.date') }}</th>
                                        <th class="text-white">{{ __('frontend.plan') }}</th>
                                        <th class="text-white">{{ __('dashboard.duration') }}</th>
                                        <th class="text-white">{{ __('frontend.expiry_date') }}</th>
                                        <th class="text-white">{{ __('frontend.amount') }}</th>
                                        <th class="text-white">{{ __('frontend.discount') }}</th>
                                        <th class="text-white">{{ __('frontend.coupon_discount') }}</th>
                                        <th class="text-white">{{ __('frontend.tax') }}</th>
                                        <th class="text-white">{{ __('messages.total_amount') }}</th>
                                        <th class="text-white">{{ __('frontend.payment_method') }}</th>
                                        <th class="text-white">{{ __('frontend.status') }}</th>
                                        <th class="text-white">{{ __('frontend.invoice') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="payment-info">
                                    @if ($subscriptions->isEmpty())
                                        <tr>
                                            <td colspan="12" class="text-center text-white fw-bold">
                                                {{ __('frontend.subscription_history_not_found') }}
                                            </td>
                                        </tr>
                                    @else
                                    @foreach ($subscriptions as $subscription)
                                        @php
                                            $amount = $subscription->amount ?? 0;
                                            $discount = $subscription->discount_percentage ?? 0;
                                            $total = $amount - ($amount * $discount) / 100;
                                            $discountAmount = ($amount * $discount) / 100;
                                            $value =  $subscription->duration ?? $subscription->plan->duration_value ;
                                            $unit =   $subscription->type ?? $subscription->plan->duration;
                                            $unit = \Illuminate\Support\Str::plural($unit, $value);
                                        
                                        @endphp
                                        <tr>
                                            <td>{{ $subscription->is_manual == 1 ? (optional($subscription->start_date) ? formatDate($subscription->start_date) : '--') : (optional($subscription->subscription_transaction?->created_at) ? formatDate($subscription->subscription_transaction?->created_at) : '--') }}
                                            </td>
                                            <td class="">{{ $subscription->name }}</td>
                                            <td class="">{{ $value }} {{ $unit }}</td>
                                            <td class="">{{ formatDate($subscription->end_date) }}</td>
                                            <td class="">{{ Currency::format($amount) }}</td>
                                            <td class=""> <span class="text-success fw-500"
                                                >{{ Currency::format($discountAmount) }}</span></td>
                                            <td class=""><span
                                                    class="text-success fw-500">{{ Currency::format($subscription->coupon_discount) }}</span>
                                            </td>
                                            <td class="">{{ Currency::format($subscription->tax_amount) }}</td>
                                            <td class=""><span
                                                    class="text-primary fw-500">{{ Currency::format($subscription->total_amount) }}<span>
                                            </td>
                                            <td class=""><span
                                                    class="badge bg-info-subtle rounded">{{ ucfirst($subscription->subscription_transaction->payment_type ?? '-') }}</span>
                                            </td>
                                            @php
                                                $status = $subscription->status ?? '-';
                                                $class = $status == 'active' ? 'bg-success-subtle' : 'bg-danger-subtle';
                                                $statusText = $status == 'cancel' ?  __('messages.lbl_canceled') : ucfirst($status);
                                                $statusText = $status == 'active' ? __('messages.active') : $statusText;
                                                $statusText = $status == 'inactive' ? __('messages.inactive') : $statusText;
                                            @endphp
                                            <td class=""><span
                                                    class="badge {{ $class }} rounded">{{ ucfirst($statusText) }}</span>
                                            </td>
                                            <td class="fw-bold"><a
                                                    href="{{ route('downloadinvoice', ['id' => $subscription->id]) }}"
                                                    class="btn btn-info-subtle btn-sm ms-2"><i class="ph ph-cloud-arrow-down align-middle"></i></a>
                                            </td>
                                        </tr>
                                    @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                        @if($subscriptions instanceof \Illuminate\Pagination\LengthAwarePaginator && $subscriptions->total() > 0)
                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <div class="text-muted">
                                    {{ __('messages.showing') }} {{ $subscriptions->firstItem() ?? 0 }} {{ __('messages.to') }}
                                    {{ $subscriptions->lastItem() ?? 0 }} {{ __('messages.of') }} {{ $subscriptions->total() }}
                                    {{ __('messages.records') }}
                                </div>
                                <div>
                                    {{ $subscriptions->appends(request()->query())->links() }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
