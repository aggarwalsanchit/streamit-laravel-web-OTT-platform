<div class="col">
    <a href="{{ route('livetv-details', ['id' => $value['slug']]) }}"
        class="livetv-card d-block position-relative">
        <div class="image-box w-100 position-relative">
            <img src="{{ $value['poster_image'] }}" alt="{{ $value['name'] }}"
                class="livetv-img object-cover img-fluid w-100 rounded">
            @if (!empty($value['show_premium_badge']))
                <button type="button" class="product-premium border-0" data-bs-toggle="tooltip"
                    data-bs-placement="top" data-bs-title="{{ __('messages.lbl_premium') }}">
                    <i class="ph ph-crown-simple"></i>
                </button>
            @endif

            <span class="live-card-badge">
                <span
                    class="live-badge fw-semibold text-uppercase">{{ __('frontend.live') }}</span>
            </span>
        </div>
    </a>
</div>
