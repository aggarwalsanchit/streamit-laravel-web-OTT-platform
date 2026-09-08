@php
    if (! function_exists('short_drama_show_mobile_hub') || ! short_drama_show_mobile_hub()) {
        return;
    }

    $shortsLabel = function_exists('short_drama_feature_name')
        ? short_drama_feature_name()
        : (\Modules\Setting\Models\Setting::get('short_drama_feature_name', 'Shorts') ?: 'Shorts');
    $shortsIconUrl = function_exists('short_drama_feature_icon_url')
        ? short_drama_feature_icon_url()
        : null;

    $homeActive = request()->routeIs('user.login');
    $shortsActive = request()->routeIs('short-drama.index', 'short-drama.all');

    $placement = $placement ?? 'stack';
    $placementClass = $placement === 'header' ? 'sd-mobile-hub-tabs--in-header' : 'sd-mobile-hub-tabs--in-stack';
    $aboveBannerClass = (! empty($aboveBanner) && $placement === 'stack') ? ' sd-mobile-hub-tabs--above-banner' : '';
@endphp
<nav class="sd-mobile-hub-tabs {{ $placementClass }}{{ $aboveBannerClass }}"
     role="tablist"
     aria-label="{{ __('frontend.home') }} / {{ $shortsLabel }}">
    <a href="{{ route('user.login') }}"
       role="tab"
       aria-selected="{{ $homeActive ? 'true' : 'false' }}"
       class="sd-mobile-hub-tabs__btn sd-mobile-hub-tabs__btn--home {{ $homeActive ? 'is-active' : '' }}">
        <span>{{ __('frontend.home') }}</span>
    </a>
    <a href="{{ route('short-drama.index') }}"
       role="tab"
       aria-selected="{{ $shortsActive ? 'true' : 'false' }}"
       class="sd-mobile-hub-tabs__btn sd-mobile-hub-tabs__btn--shorts {{ $shortsActive ? 'is-active' : '' }}">
        @if($shortsIconUrl)
            <img src="{{ $shortsIconUrl }}" alt="" class="sd-mobile-hub-tabs__icon-img" aria-hidden="true">
        @else
            <i class="ph-fill ph-lightning" aria-hidden="true"></i>
        @endif
        <span>{{ $shortsLabel }}</span>
    </a>
</nav>
