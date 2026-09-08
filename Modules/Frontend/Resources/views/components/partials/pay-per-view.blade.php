<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
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
        font-size: 12px;
        font-family: 'DejaVu Sans', Arial, sans-serif;
        direction: ltr;
    }

    /* Arabic text support */
    .arabic-text {
        direction: rtl;
        text-align: right;
        font-family: 'DejaVu Sans', Arial, sans-serif;
    }

    /* Ensure proper Unicode support */
    * {
        font-family: 'DejaVu Sans', Arial, sans-serif;
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
        flex-basis: 0;
        flex-grow: 1;
        max-width: 50%;
    }

    .row {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -5px;
        position: relative;
        width: 100%;
        min-height: 1px;
        padding-right: 15px;
        padding-left: 15px;
        gap: 16px;
    }

    .row:after {
        content: "";
        display: table;
        clear: both;
    }
    .payment-svg  svg{
        height: 14px;
        width: 14px;
        vertical-align: top;
        margin-right: 7px;
        stroke: #6a7282;
    }
    .additional-info {

    }
    .additional-info .description {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .additional-info .description h3 {
        display: inline;
        margin: 0 6px 0 0;
    }
    .additional-info .description span {
        display: inline;
        white-space: nowrap;
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
                        @php
                            $logo = setBaseUrlWithFileName(GetSettingValue('dark_logo'),'image','logos') ?? 'img/logo/dark_logo.png';
                            $logoSrc = '';
                            try {
                                $logoPath = '';
                                if (filter_var($logo, FILTER_VALIDATE_URL)) {
                                    $parsedUrl = parse_url($logo);
                                    if (isset($parsedUrl['path'])) {
                                        $path = ltrim($parsedUrl['path'], '/');
                                        if (strpos($path, 'storage/') === 0) {
                                            $logoPath = storage_path('app/public/' . substr($path, 8));
                                        } else {
                                            $logoPath = public_path($path);
                                        }
                                    }
                                } elseif (Str::startsWith($logo, 'storage/')) {
                                    $logoPath = storage_path('app/public/' . substr($logo, 8));
                                } else {
                                    $logoPath = public_path($logo);
                                }

                                if (!empty($logoPath) && file_exists($logoPath)) {
                                    $imageData = base64_encode(file_get_contents($logoPath));
                                    $mimeType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $logoPath);
                                    $logoSrc = "data:{$mimeType};base64,{$imageData}";
                                } elseif (filter_var($logo, FILTER_VALIDATE_URL)) {
                                    // Fetch remote URL and convert to base64 only if not local
                                    $appUrl = config('app.url');
                                    if (strpos($logo, $appUrl) === false) {
                                        $imageData = @file_get_contents($logo);
                                        if ($imageData !== false) {
                                            $mimeType = finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $imageData);
                                            $logoSrc = "data:{$mimeType};base64," . base64_encode($imageData);
                                        }
                                    }
                                }
                            } catch (\Exception $e) {
                                \Log::error('Error loading logo: ' . $e->getMessage());
                            }
                            if (empty($logoSrc)) {
                                $defaultLogoPath = public_path('img/logo/dark_logo.png');
                                if (file_exists($defaultLogoPath)) {
                                    $imageData = base64_encode(file_get_contents($defaultLogoPath));
                                    $mimeType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $defaultLogoPath);
                                    $logoSrc = "data:{$mimeType};base64,{$imageData}";
                                }
                            }
                        @endphp
                        @if($logoSrc)
                        <img src="{{ $logoSrc }}" alt="Logo"
                            height="45">
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
                                <span class="bill-badge">
                                    @php
                                        $status = trim(strtolower($ppv->PayperviewTransaction->payment_status ?? ''));
                                    @endphp
                                    {{ $status === 'paid' ? __('messages.paid') : ($status !== '' ? ucfirst($status) : __('messages.paid')) }}
                                </span>
                            </div>
                            <h4 class="text-title fw-bold mb-8">{{ __('messages.invoice') }}</h4>
                            <p class="text-title mb-4">{{ __('messages.invoice_id') }}: #INV-{{ $ppv->id }}</p>
                            <p class="text-title mb-4">{{ __('messages.invoice_date') }}: {{ formatDateTimeWithTimezone($ppv->created_at) }}</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        <div style="padding-top: 24px; margin-top: 24px; border-top: 1px solid #0000001a;"></div>
        <h4 class="text-title fw-bold mb-8">{{ __('messages.bill_to') }}</h4>
        <table style="width: 100%; margin-bottom: 28px; border: 1px solid #0000001a; border-radius: 12px;">
            <tbody>
                <tr>
                    <td style="padding: 21px;">
                        <h4 class="mb-10">{{ $ppv->user?->full_name ?? '-' }}</h4>
                        <p class="info mb-10">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-mail w-4 h-4" aria-hidden="true">
                                <path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7"></path>
                                <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                            </svg>
                            <span class="mb-4">{{ $ppv->user?->email ?? '-' }}</span>
                        </p>
                        <p class="info mb-10">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-phone w-4 h-4" aria-hidden="true">
                                <path
                                    d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384">
                                </path>
                            </svg>
                            <span class="mb-4">{{ $ppv->user?->mobile ?? '-' }}</span>
                        </p>
                        <p class="info">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-map-pin w-4 h-4 mt-1" aria-hidden="true">
                                <path
                                    d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0">
                                </path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <span>{{ $ppv->user?->address ?? '-' }}</span>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        <h4 class="text-title fw-bold mb-8">{{ __('messages.order_summary') }}</h4>
        <!-- order table -->
        <table style="padding: 21px; border: 1px solid #0000001a; border-radius: 12px; width: 100%; margin-bottom: 14px;">
            <tbody>
                <tr>
                    <td>
                        <div class="cart-info">
                            @php
                                $posterSrc = '';

                                try {
                                    $poster = null;

                                    if (isset($ppv->movie) && !empty($ppv->movie->poster_url)) {
                                        $poster = $ppv->movie->poster_url;
                                    } elseif (isset($ppv->episode) && !empty($ppv->episode->poster_url)) {
                                        $poster = $ppv->episode->poster_url;
                                    } elseif (isset($ppv->video) && !empty($ppv->video->poster_url)) {
                                        $poster = $ppv->video->poster_url;
                                    }

                                    if ($poster) {
                                        if (filter_var($poster, FILTER_VALIDATE_URL)) {
                                            $parsedUrl = parse_url($poster);
                                            $path = preg_replace('/^\/storage/', '', $parsedUrl['path']);
                                            $posterPath = storage_path('app/public' . $path);
                                        } elseif (Str::startsWith($poster, 'storage/')) {
                                            $posterPath = storage_path('app/public/' . str_replace('storage/', '', $poster));
                                        } else {
                                            $posterPath = public_path($poster);
                                        }

                                        if (file_exists($posterPath)) {
                                            $imageData = base64_encode(file_get_contents($posterPath));
                                            $mimeType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $posterPath);
                                            $posterSrc = "data:{$mimeType};base64,{$imageData}";
                                        } else {
                                            $posterSrc = setBaseUrlWithFileName($poster,'image',$ppv->type);
                                        }
                                    }
                                } catch (\Exception $e) {
                                    \Log::error('Error loading poster: ' . $e->getMessage());
                                }

                                if (empty($posterSrc)) {
                                    $posterSrc = asset('frontend/images/placeholder-vertical.png');
                                }
                            @endphp
                                        @php
                                            $typeLabel = $ppv->type ? __('messages.' . $ppv->type) : '-';
                                        @endphp
                                        <h5 class="fw-bold">{{ $typeLabel }}</h5>
                            {{-- <img src="{{ $posterSrc }}"
                                alt="{{ $ppv->movie->name ?? $ppv->episode->name ?? $ppv->video->name ?? '-' }}" class="img-fluid img-cart rounded"> --}}
                                <h2 class="mb-4 truncate">
                                    @if($ppv->type === 'movie')
                                        {{ $ppv->movie->name ?? '-' }}
                                    @elseif($ppv->type === 'episode')
                                        {{ $ppv->episode->name ?? '-' }}
                                    @elseif($ppv->type === 'video')
                                        {{ $ppv->video->name ?? '-' }}
                                    @endif
                                </h2>
                                <p class="text-sm mb-4">
                                    @if($ppv->available_for)
                                        {{ $ppv->available_for }} {{ __('messages.days') }}
                                    @endif
                                </p>
                                @php
                                    $purchaseType = null;
                                    if($ppv->type === 'movie') {
                                        $purchaseType = $ppv->movie->purchase_type ?? null;
                                    } elseif($ppv->type === 'episode') {
                                        $purchaseType = $ppv->episode->purchase_type ?? null;
                                    } elseif($ppv->type === 'video') {
                                        $purchaseType = $ppv->video->purchase_type ?? null;
                                    }
                                    $purchaseLabel = match ($purchaseType) {
                                        'rental' => __('messages.lbl_rental'),
                                        'onetime' => __('messages.onetime'),
                                        default => ucfirst($purchaseType ?? '-'),
                                    };
                                @endphp
                                <span class="danger-badge">{{ $purchaseLabel }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="text-right">
                        <h3 class="fw-bold">{{ Currency::format($ppv->content_price) }}</h3>
                    </td>
                </tr>
            </tbody>

        </table>
        <!-- total table -->
        <table class="bg-card" style="padding: 21px; border: 1px solid #0000001a; border-radius: 12px; width: 100%; margin-bottom: 14px; box-sizing: border-box;">
            <tbody>
                <tr>
                    <td class="pb-14">
                        <span>{{ __('messages.subtotal') }}</span>
                    </td>
                    <td class="text-right pb-14">
                        <h3 class="fw-bold">{{ Currency::format($ppv->content_price) }}</h3>
                    </td>
                </tr>
                <tr>
                    <td class="pb-14">
                        <span>{{ __('messages.discount') }}</span>
                    </td>
                    <td class="text-right pb-14">
                        <h3 class="text-danger">-{{ $ppv->discount_percentage }}%</h3>
                    </td>
                </tr>
                <tr>
                    <td style="padding-top: 21px; border-top: 1px solid #0000001a;">
                        <h3 class="fw-bold">{{ __('messages.total_amount') }}</h3>
                    </td>
                    <td class="text-right" style="padding-top: 21px; border-top: 1px solid #0000001a;">
                        <h2 class="fw-bold">{{ Currency::format($ppv->total_amount ?? $ppv->price) }}</h2>
                    </td>
                </tr>
            </tbody>
        </table>
        <!-- details -->
        <table style="width: 100%; border-spacing: 0; border-collapse: collapse;">
            <tbody>
                <tr>
                    <td style="width: calc(50% - 8px); vertical-align: top; padding-right: 8px; box-sizing: border-box;">
                        <div style="padding: 21px; border: 1px solid #0000001a; border-radius: 12px; box-sizing: border-box;">
                            <h3 class="text-title fw-bold mb-14">{{ __('messages.payment_details') }}</h3>
                            <table class="table">
                                <tr>
                                    <td class="pb-10" style="color: #6a7282;">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="14" x="2" y="5" rx="2"></rect><line x1="2" x2="22" y1="10" y2="10"></line></svg>
                                            <span>{{ __('frontend.payment_method') }}:</span>
                                        </div>
                                    </td>
                                    <td class="pb-10 text-right">
                                        <h4 class="fw-bold m-0" style="color: #101828;">{{ ucfirst($ppv->PayperviewTransaction->payment_type ?? '-') }}</h4>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="pb-10" style="color: #6a7282;">{{ __('messages.transaction_id') }}:</td>
                                    <td class="text-sm text-right pb-10" style="color: #101828;">{{ $ppv->PayperviewTransaction->transaction_id ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="pb-10" style="color: #6a7282;">{{ __('messages.payment_date') }}:</td>
                                    <td class="text-sm text-right pb-10" style="color: #101828;">{{ formatDateTimeWithTimezone($ppv->created_at) }}</td>
                                </tr>
                                <tr>
                                    <td style="color: #6a7282;">{{ __('messages.amount_paid') }}:</td>
                                    <td class="text-right">
                                        <span class="fw-bold" style="color: #00a63e;">{{ Currency::format($ppv->total_amount ?? $ppv->price) }}</span>
                                    </td>
                                </tr>
                            </table>

                        </div>
                    </td>
                    <td style="width: calc(50% - 8px); vertical-align: top; padding-left: 8px; box-sizing: border-box;">
                        <div style="padding: 21px; border: 1px solid #0000001a; border-radius: 12px; box-sizing: border-box;">
                            <h3 class="text-title fw-bold mb-14">{{ __('messages.additional_information') }}</h3>
                            <div class="additional-info">
                                <div class="description" style="margin-bottom: 16px;">
                                    <div>
                                        <h3 class="text-title fw-bold">{{ __('messages.rental_expiry') }}:</h3>
                                        @if($ppv->view_expiry_date)
                                            <span class="text-sm" style="color: #101828;">{{ formatDateTimeWithTimezone($ppv->view_expiry_date) }}</span>
                                        @endif
                                    </div>
                                </div>
                                @php
                                    $contactNumber = GetSettingValue('helpline_number');
                                    $invoiceEmail = GetSettingValue('inquriy_email');
                                @endphp
                                @if($contactNumber)
                                <div class="description" style="margin-bottom: 16px;">
                                    <div>
                                        <h3 class="text-title fw-bold mb-8">{{ __('users.lbl_contact_number') }}:</h3>
                                        <span class="text-sm">{{ $contactNumber }}</span>
                                    </div>
                                </div>
                                @endif
                                @if($invoiceEmail)
                                <div class="description">
                                    <div>
                                        <h3 class="text-title fw-bold mb-8">{{ __('messages.invoice') }} Email:</h3>
                                        <span class="text-sm">{{ $invoiceEmail }}</span>
                                    </div>
                                </div>
                                @endif
                            </div>

                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </body>

</html>
