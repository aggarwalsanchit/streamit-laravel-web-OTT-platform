@extends('frontend::layouts.master')

@section('title')
    {{ __('frontend.pay_per_view_transactions') }}
@endsection

@section('content')
    <!-- <div class="page-title">
                    <h4 class="m-0 text-center">{{ __('frontend.pay_per_view_transactions') }}</h4>
            </div> -->
    <div class="section-spacing">
        <div class="container-fluid">
            <div class="row gy-4">
                <div class="col-lg-3 col-md-4">
                    @include('frontend::components.account-settings-sidebar')
                </div>
                <div class="col-lg-9 col-md-8">
                    <div class="d-flex justify-content-start mb-4">
                        <h4 class="m-0 text-center">{{ __('frontend.pay_per_view_transactions') }}</h4>
                    </div>
                    <div class="table-responsive">
                        <table class="table payment-history table-borderless">
                            <thead class="table-dark">
                                <tr>
                                    <th class="heading-color">{{ __('frontend.date') }}</th>
                                    <th class="heading-color">{{ __('frontend.content') }}</th>
                                    <th class="heading-color">{{ __('frontend.type') }}</th>
                                    <th class="heading-color">{{ __('frontend.expiry_date') }}</th>
                                    <th class="heading-color">{{ __('frontend.price') }}</th>
                                    <th class="heading-color">{{ __('frontend.discount') }}</th>
                                    <th class="heading-color">{{ __('messages.total_amount') }}</th>
                                    <th class="heading-color">{{ __('frontend.payment_method') }}</th>
                                    <th class="heading-color">{{ __('frontend.invoice') }}</th>
                                </tr>
                            </thead>
                            <tbody class="payment-info">
                                @if ($payPerViews->isEmpty())
                                    <tr>
                                        <td colspan="9" class="text-center heading-color">
                                            {{ __('frontend.pay_per_view_history_not_found') }}
                                        </td>
                                    </tr>
                                @else
                                    @foreach ($payPerViews as $ppv)
                                        <tr>
                                            <td>{{ formatDate($ppv->created_at) }}</td>
                                            <td>
                                                @if ($ppv->type == 'movie')
                                                    {{ $ppv->movie->name ?? '-' }}
                                                @elseif($ppv->type == 'episode')
                                                    {{ $ppv->episode->name ?? '-' }}
                                                @elseif($ppv->type == 'video')
                                                    {{ $ppv->video->name ?? '-' }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>{{ __('messages.' . $ppv->type) ?? ucfirst($ppv->type) }}</td>
                                            <td>{{ formatDate($ppv->view_expiry_date) }}</td>
                                            <td>{{ Currency::format($ppv->content_price) }}</td>
                                            <td><span class="text-success">
                                                    {{ $ppv->discount_percentage }}% </span></td>
                                            <td
                                                class="d-flex align-items-center justify-content-between">
                                                <span>{{ Currency::format($ppv->price) }}</span>

                                            </td>
                                            <td class=""><span
                                                class="badge bg-info-subtle rounded">{{ ucfirst($ppv->PayperviewTransaction->payment_type ?? '-') }}</span>
                                            </td>
                                            <td>
                                                <a href="{{ route('payperview.invoice', ['id' => $ppv->id]) }}"
                                                    class="btn btn-info-subtle btn-sm ms-2"><i class="ph ph-cloud-arrow-down align-middle"></i></a>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                    @if($payPerViews instanceof \Illuminate\Pagination\LengthAwarePaginator && $payPerViews->total() > 0)
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="text-muted">
                            {{ __('messages.showing') }} {{ $payPerViews->firstItem() ?? 0 }} {{ __('messages.to') }}
                            {{ $payPerViews->lastItem() ?? 0 }} {{ __('messages.of') }} {{ $payPerViews->total() }}
                            {{ __('messages.records') }}
                        </div>
                        <div>
                            {{ $payPerViews->appends(request()->query())->links() }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
