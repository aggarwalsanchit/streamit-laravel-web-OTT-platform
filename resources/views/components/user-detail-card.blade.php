
@php
    $wrapperClasses = 'd-flex gap-3 align-items-center';
@endphp

@if (!empty($url))
    <a href="{{ $url }}" class="{{ $wrapperClasses }} text-decoration-none text-reset">
        <img src="{{ $image }}" alt="avatar" class="avatar avatar-40 rounded-pill">
        <div class="text-start">
            <h6 class="m-0">{{ $name }}</h6>
            <span>{{ $email }}</span>
        </div>
    </a>
@else
    <div class="{{ $wrapperClasses }}">
        <img src="{{ $image }}" alt="avatar" class="avatar avatar-40 rounded-pill">
        <div class="text-start">
            <h6 class="m-0">{{ $name }}</h6>
            <span>{{ $email }}</span>
        </div>
    </div>
@endif
