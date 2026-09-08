@component('mail::layout')
{{-- Header --}}
@slot('header')
@php
    $mailAppName = app_name();
    $mailLogo = GetSettingValue('dark_logo') ?? asset('img/logo/dark_logo.png');
    $mailLogoUrl = $mailLogo ? setBaseUrlWithFileName($mailLogo, 'image', 'logos') : null;
@endphp

@component('mail::header', ['url' => config('app.url')])
<span style="display: inline-flex; align-items: center;">
    @if ($mailLogoUrl)
        <img 
            src="{{ $mailLogoUrl }}" 
            alt="{{ $mailAppName }} Logo" 
            style="height: 32px; margin-right: 10px;"
        >
    @endif
    <strong style="font-size: 18px; color: #111827;">
        {{ $mailAppName }}
    </strong>
</span>
@endcomponent
@endslot

{{-- Body --}}
{{ $slot }}

{{-- Subcopy --}}
@isset($subcopy)
@slot('subcopy')
@component('mail::subcopy')
{{ $subcopy }}
@endcomponent
@endslot
@endisset

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
© {{ date('Y') }} {{ app_name() }}. @lang('All rights reserved.')
@endcomponent
@endslot
@endcomponent
