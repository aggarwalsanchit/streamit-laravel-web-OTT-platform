<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('messages.invoice') }}</title>
</head>
<style>
    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
        margin: 0;
        color: #101828;
        font-weight: 600;
    }

    p {
        margin: 0;
    }

    body {
        color: #6a7282;
        font-size: 14px;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .badge-box svg {
        height: 16px;
        width: 16px;
        vertical-align: top;
        margin-right: 4px;
        stroke: #00a63e;
    }

    .badge-box .bill-badge {
        color: #008236;
        background-color: #f0fdf4;
        border: 1px solid #b9f8cf;
        padding: 1.5px 7px;
        border-radius: 4px;
        font-size: 10px;
        text-transform: uppercase;
    }

    .text-right {
        text-align: right;
    }

    .text-title {
        color: #4a5565;
    }

    .text-sm {
        font-size: 12px;
    }

    .pb-14 {
        padding-bottom: 14px;
    }

    .pb-10 {
        padding-bottom: 10px;
    }

    .mb-14 {
        margin-bottom: 14px;
    }

    .mb-10 {
        margin-bottom: 10px;
    }

    .mb-8 {
        margin-bottom: 8px;
    }

    .mb-4 {
        margin-bottom: 4px;
    }

    .info svg {
        height: 16px;
        width: 16px;
        vertical-align: top;
        margin-right: 7px;
        stroke: #6a7282;
    }

    .img-fluid {
        max-width: 100%;
        height: auto;
        object-fit: cover;
    }

    .img-cart {
        height: 70px;
        width: 56px;
    }

    .cart-info {
        display: flex;
        gap: 16px;
    }

    .rounded {
        border-radius: 8px;
    }

    .truncate {
        text-overflow: ellipsis;
        white-space: nowrap;
        overflow: hidden;
    }

    .flex-1 {
        flex: 1;
    }

    .fw-bold {
        font-weight: 700;
    }

    .text-danger {
        color: #e7000b;
    }

    .danger-badge {
        color: #9f2d00;
        background-color: #ffedd4;
        border: 1px solid #ffd7a8;
        padding: 1.5px 7px;
        border-radius: 4px;
        font-size: 10px;
    }

    .primary-badge {
        color: #193cb8;
        background-color: #dbeafe;
        border: 1px solid #bedbff;
        padding: 1.5px 7px;
        border-radius: 4px;
        font-size: 10px;
    }

    .bg-card {
        background-color: #f8f9fa80;
    }

    .col {
        flex: 1;
        flex-basis: 0;
        flex-grow: 1;
        max-width: 50%;
        min-width: 0;
    }

    .row {
        display: flex;
        flex-wrap: nowrap;
        margin: 0;
        position: relative;
        width: 100%;
        min-height: 1px;
        padding: 0;
        gap: 16px;
    }

    .row:after {
        content: "";
        display: table;
        clear: both;
    }

    .payment-svg svg {
        height: 14px;
        width: 14px;
        vertical-align: top;
        margin-right: 7px;
        stroke: #6a7282;
    }

    .additional-info {}

    .additional-info .description {
        display: flex;
        gap: 8px;
    }

    .rent-svg {
        svg {
            height: 14px;
            width: 14px;
            color: #ff6900;
        }

    }

    .subscription-svg {
        svg {
            height: 14px;
            width: 14px;
            color: #2b7fff;
        }
    }
</style>

<body>
    <table class="table">
        <tbody>
            <tr>
                <td>
                    @if (!empty($logoSrc))
                        <img src="{{ $logoSrc }}" alt="Logo" height="45" style="max-width: 200px;">
                    @endif
                </td>
                <td class="text-right">
                    <div>
                        <div class="mb-8 badge-box">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" aria-hidden="true">
                                <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                                <path d="m9 11 3 3L22 4"></path>
                            </svg>
                            <span
                                class="bill-badge">{{ optional($data->subscription_transaction)->payment_status ?? 'Paid' }}</span>
                        </div>
                        <h4 class="mb-8">{{ __('messages.invoice') }}</h4>
                        <p class="text-title mb-4">{{ __('messages.invoice_id') }}: #INV-{{ $data->id }}</p>
                        <p class="text-title mb-4">{{ __('messages.invoice_date') }}:
                            {{ formatDate(now()->format('F d, Y')) }}</p>
                        <p class="text-sm">{{ __('messages.customer_id') }}: {{ $data->user?->id }}</p>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
    <div style="padding-top: 24px; margin-top: 24px; border-top: 1px solid #0000001a;"></div>
    <h4 style="margin-bottom: 14px;">{{ __('messages.bill_to') }}</h4>
    <table style="width: 100%; margin-bottom: 28px; border: 1px solid #0000001a; border-radius: 12px;">
        <tbody>
            <tr>
                <td style="padding: 21px;">
                    <h4 class="mb-10">{{ $data->user->full_name ?? default_user_name() }}</h4>
                    <p class="info mb-10">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-mail w-4 h-4" aria-hidden="true">
                            <path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7"></path>
                            <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                        </svg>
                        <span class="mb-4">{{ $data->user->email ?? '-' }}</span>
                    </p>
                    <p class="info mb-10">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-phone w-4 h-4" aria-hidden="true">
                            <path
                                d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384">
                            </path>
                        </svg>
                        <span class="mb-4">{{ $data->user->mobile ?? '-' }}</span>
                    </p>
                    <p class="info">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="lucide lucide-map-pin w-4 h-4 mt-1" aria-hidden="true">
                            <path
                                d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0">
                            </path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <span>{{ $data->user->address ?? '-' }}</span>
                    </p>
                </td>
            </tr>
        </tbody>
    </table>
    <h4 style="margin-bottom: 21px;">{{ __('messages.order_summary') }}</h4>
    <!-- Subscription table -->
    <table style="padding: 21px; border: 1px solid #0000001a; border-radius: 12px; width: 100%; margin-bottom: 14px;">
        <tbody>
            <tr>
                <td>
                    <div class="cart-info">
                        <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODgiIGhlaWdodD0iODgiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgc3Ryb2tlPSIjMDAwIiBzdHJva2UtbGluZWpvaW49InJvdW5kIiBvcGFjaXR5PSIuMyIgZmlsbD0ibm9uZSIgc3Ryb2tlLXdpZHRoPSIzLjciPjxyZWN0IHg9IjE2IiB5PSIxNiIgd2lkdGg9IjU2IiBoZWlnaHQ9IjU2IiByeD0iNiIvPjxwYXRoIGQ9Im0xNiA1OCAxNi0xOCAzMiAzMiIvPjxjaXJjbGUgY3g9IjUzIiBjeT0iMzUiIHI9IjciLz48L3N2Zz4KCg=="
                            alt="Product" class="img-fluid img-cart rounded">
                        <div class="flex-1">
                            <h2 class="mb-4 truncate">{{ $data->name }}</h2>
                            <p class="text-sm mb-4">{{ $data->duration }} {{ $data->type }}</p>
                            <span class="primary-badge">Subscription</span>
                        </div>
                    </div>
                </td>
                <td class="text-right">
                    <h3 class="fw-bold">{{ \Currency::format(floatval($data->amount ?? 0)) }}</h3>
                </td>
            </tr>
        </tbody>

    </table>
    @php
        $baseAmount = floatval($data->amount ?? 0);
        $discountPercent = floatval($data->discount_percentage ?? 0);
        $couponDiscountAmount = floatval($data->coupon_discount ?? 0);
        $planDiscountAmount = $discountPercent > 0 ? ($baseAmount * $discountPercent) / 100 : 0;
        $amountAfterDiscount = max(0, $baseAmount - $planDiscountAmount - $couponDiscountAmount);
    @endphp
    <!-- total table -->
    <table class="bg-card"
        style="padding: 21px; border: 1px solid #0000001a; border-radius: 12px; width: 100%; margin-bottom: 14px;">
        <tbody>
            <tr>
                <td class="pb-14">
                    <span>{{ __('messages.subtotal') }}</span>
                </td>
                <td class="text-right pb-14">
                    <h3>{{ \Currency::format($baseAmount) }}</h3>
                </td>
            </tr>
            @if ($planDiscountAmount > 0)
                <tr>
                    <td class="pb-14">
                        <span>{{ __('messages.discount') }} ({{ $discountPercent }}%)</span>
                    </td>
                    <td class="text-right pb-14">
                        <h3 class="text-danger">-{{ \Currency::format($planDiscountAmount) }}</h3>
                    </td>
                </tr>
            @endif
            @if ($couponDiscountAmount > 0)
                <tr>
                    <td class="pb-14">
                        <span>{{ __('messages.coupon_discount') }}</span>
                    </td>
                    <td class="text-right pb-14">
                        <h3 class="text-danger">-{{ \Currency::format($couponDiscountAmount) }}</h3>
                    </td>
                </tr>
            @endif
            @php
                $taxArrayRaw = optional($data->subscription_transaction)->tax_data ?? '[]';
                if (is_string($taxArrayRaw)) {
                    $taxArray = json_decode($taxArrayRaw, true) ?: [];
                } elseif (is_array($taxArrayRaw)) {
                    $taxArray = $taxArrayRaw;
                } else {
                    $taxArray = [];
                }
                $taxTotal = 0;
            @endphp
            @foreach ($taxArray as $tax)
                @php
                    $tType = strtolower($tax['type'] ?? ($tax['Type'] ?? ''));
                    $tTitle = $tax['title'] ?? ($tax['Title'] ?? 'Tax');
                    $tValue = floatval($tax['value'] ?? ($tax['Value'] ?? 0));
                    $tAmount = 0;
                    if ($tType === 'percentage') {
                        $tAmount = ($amountAfterDiscount * $tValue) / 100;
                    } else {
                        $tAmount = $tValue;
                    }
                    $taxTotal += $tAmount;
                @endphp
                <tr>
                    <td class="pb-14">
                        <span>{{ $tTitle }}
                            ({{ $tType === 'percentage' ? $tValue . '%' : \Currency::format($tValue) }})
                        </span>
                    </td>
                    <td class="text-right pb-14">
                        <h3>{{ \Currency::format($tAmount) }}</h3>
                    </td>
                </tr>
            @endforeach
            <tr>
                <td style="padding-top: 21px; border-top: 1px solid #0000001a;">
                    <h3 class="fw-bold">{{ __('messages.total_amount') }}</h3>
                </td>
                <td class="text-right" style="padding-top: 21px; border-top: 1px solid #0000001a;">
                    <h2 class="fw-bold">{{ \Currency::format($amountAfterDiscount + ($taxTotal ?? 0)) }}</h2>
                </td>
            </tr>
        </tbody>
    </table>
    <!-- details -->
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 50%; padding-right: 8px; vertical-align: top;">
                <div style="padding: 21px; border: 1px solid #0000001a; border-radius: 12px;">
                    <h3 class="mb-14">{{ __('messages.payment_details') }}</h3>
                    <table class="table">
                        <tr>
                            <td class="pb-10">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="lucide lucide-credit-card w-4 h-4 text-gray-500" aria-hidden="true">
                                    <rect width="20" height="14" x="2" y="5" rx="2"></rect>
                                    <line x1="2" x2="22" y1="10" y2="10"></line>
                                </svg>
                                <span>{{ __('messages.payment_method') }}:</span>
                            </td>
                            <td class="pb-10">
                                <h4 class="font-medium">
                                    {{ optional($data->subscription_transaction)->payment_method ?? ' ' }}</h4>
                            </td>
                        </tr>
                        <tr>
                            <td class="pb-10">{{ __('messages.transaction_id') }}:</td>
                            <td class="text-sm text-right pb-10">
                                {{ optional($data->subscription_transaction)->transaction_id ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="pb-10">{{ __('messages.payment_date') }}:</td>
                            <td class="text-sm text-right pb-10">
                                {{ optional($data->subscription_transaction)->created_at ? formatDate(optional($data->subscription_transaction)->created_at) : '-' }}
                            </td>
                        </tr>
                        <tr>
                            <td>{{ __('messages.amount_paid') }}:</td>
                            <td class="text-right">
                                <span class="fw-bold"
                                    style="color: #00a63e;">{{ \Currency::format($amountAfterDiscount + ($taxTotal ?? 0)) }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
            <td style="width: 50%; padding-left: 8px; vertical-align: top;">
                <div style="padding: 21px; border: 1px solid #0000001a; border-radius: 12px;">
                    <h3 class="mb-14">{{ __('messages.additional_information') }}</h3>
                    <table class="table">
                        <tr>
                            <td class="pb-10">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round"
                                        class="lucide lucide-credit-card w-4 h-4 text-gray-500" aria-hidden="true">
                                        <rect width="20" height="14" x="2" y="5" rx="2"></rect>
                                        <line x1="2" x2="22" y1="10" y2="10"></line>
                                    </svg>
                                    <span>{{ __('messages.subscription_plan') }}:</span>
                                </div>
                            </td>
                            <td class="pb-10 text-right">
                                <span>{{ $data->plan->name ?? '' }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="pb-10">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" style="color: #ff6900;"
                                        aria-hidden="true">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12,6 12,12 16,14"></polyline>
                                    </svg>
                                    <span>{{ __('messages.subscription_expiry') }}:</span>
                                </div>
                            </td>
                            <td class="pb-10 text-right">
                                <span>{{ $data->end_date ? formatDate($data->end_date) : '-' }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" style="color: #00a63e;"
                                        aria-hidden="true">
                                        <path d="M9 12l2 2 4-4"></path>
                                        <path d="M21 12c-1 0-3-1-3-3s2-3 3-3 3 1 3 3-2 3-3 3"></path>
                                        <path d="M3 12c1 0 3-1 3-3s-2-3-3-3-3 1-3 3 2 3 3 3"></path>
                                        <path d="M12 3c0 1-1 3-3 3s-3-2-3-3 1-3 3-3 3 2 3 3"></path>
                                        <path d="M12 21c0-1 1-3 3-3s3 2 3 3-1 3-3 3-3-2-3-3"></path>
                                    </svg>
                                    <span>{{ __('messages.status') }}:</span>
                                </div>
                            </td>
                            <td class="text-right">
                                <span style="color: #00a63e; font-weight: 500;">{{ $data->status ?? 'Active' }}</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

</body>

</html>
