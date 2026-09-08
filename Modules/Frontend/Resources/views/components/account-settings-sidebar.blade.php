<div id="account-settings-sidebar" class="account-settings-sidebar-inner">
    <ul class="nav nav-tabs flex-md-column gap-3 gap-lg-4 account-settings-tab" id="account-settings-tabs">
        @if(getCurrentProfileSession('is_child_profile') == 0)
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('accountSetting') ? 'active' : '' }} p-3 text-center" href="{{ route('accountSetting') }}">
                    <i class="ph ph-user-circle"></i>
                    <h6 class="m-0">{{ __('frontend.account_settings') }}</h6>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('security-control') ? 'active' : '' }} p-3 text-center" href="{{ route('security-control') }}">
                    <i class="ph ph-shield-check"></i>
                    <h6 class="m-0">{{ __('frontend.parental_controls') }}</h6>
                </a>
            </li>
        @endif
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('watchList') ? 'active' : '' }} p-3 text-center" href="{{ route('watchList') }}">
                <i class="ph ph-bookmark"></i>
                <h6 class="m-0">{{ __('frontend.my_watchlist') }}</h6>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('unlock.videos') ? 'active' : '' }} p-3 text-center" href="{{ route('unlock.videos') }}">
                <i class="ph ph-video"></i>
                <h6 class="m-0">{{ __('frontend.rent_videos') }}</h6>
            </a>
        </li>
        @if(getCurrentProfileSession('is_child_profile') == 0)
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('payment-history') ? 'active' : '' }} p-3 text-center" href="{{ route('payment-history') }}">
                    <i class="ph ph-credit-card"></i>
                    <h6 class="m-0">{{ __('frontend.payment_history') }}</h6>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('transaction-history') ? 'active' : '' }} p-3 text-center" href="{{ route('transaction-history') }}">
                    <i class="ph ph-receipt"></i>
                    <h6 class="m-0">{{ __('frontend.pay_per_view_transactions') }}</h6>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('update-profile') ? 'active' : '' }} p-3 text-center" href="{{ route('update-profile') }}">
                    <i class="ph ph-user-circle"></i>
                    <h6 class="m-0">{{ __('frontend.profiles_details') }}</h6>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('change-password') ? 'active' : '' }} p-3 text-center" href="{{ route('change-password') }}">
                    <i class="ph ph-key"></i>
                    <h6 class="m-0">{{ __('frontend.change_password') }}</h6>
                </a>
            </li>
        @endif
    </ul>
</div>

@push('scripts')
    <script>
        function toggle() {
            const formOffcanvas = document.getElementById('offcanvas');
            formOffcanvas.classList.add('show');
        }
    </script>
@endpush
