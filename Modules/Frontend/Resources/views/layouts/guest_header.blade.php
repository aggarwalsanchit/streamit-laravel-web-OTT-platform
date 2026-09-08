<header class="header-search header-sticky">
    <div class="container-fluid">
        <div class="row align-items-center gx-0">
            <div class="col-md-3 col-sm-3 col-3">
                @include('frontend::components.partials.logo')
            </div>
            <div class="col-md-5 col-sm-4 col-3 px-0 px-2">
                <div class="d-flex justify-content-end">
                    <div class="dropdown dropdown-search-wrapper text-end">
                        <a href="javascript:void(0);" class="dropdown-toggle-item d-sm-none" id="searchDropdown"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="ph ph-magnifying-glass align-middle"></i>
                        </a>
                        <div class="search-box dropdown-menu dropdown-menu-md-end" aria-labelledby="searchDropdown"
                            data-bs-popper="static">
                            <div class="form-group input-group mb-0">
                                {{-- <button type="submit" class="search-submit input-group-text">
                                    <i class="ph ph-magnifying-glass"></i>
                                </button> --}}
                                <input type="text" id="search-query" class="form-control border-0"
                                    placeholder="{{ __('placeholder.lbl_search') }}">
                                <button type="submit" class="search-submit input-group-text remove-search d-none">
                                    <i class="ph ph-x"></i>
                                </button>
                            </div>
                        </div>


                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-5 col-6">
                <div class="d-flex justify-content-end">
                    <div class="dropdown dropdown-user-wrapper">
                        @if (auth()->check())

                            @if (auth()->user()->user_type == 'user' && auth()->user()->is_subscribe == 0)
                                <button class="btn btn-warning-subtle font-size-14 text-uppercase subscribe-btn"
                                    onclick="window.location.href='{{ route('subscriptionPlan') }}'">
                                    {{ __('frontend.subscribe') }}
                                </button>
                            @else
                                <button class="btn btn-warning-subtle font-size-14 text-uppercase subscribe-btn"
                                    onclick="window.location.href='{{ route('subscriptionPlan') }}'">
                                    {{ __('frontend.upgrade') }}
                                </button>
                            @endif

                            <a class="dropdown-toggle-item dropdown-user ms-3" href="#" id="navbarDropdown"
                                role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="{{ setBaseUrlWithFileName(auth()->user()->file_url, 'image', 'users') }}"
                                    class="img-fluid user-image rounded-circle" alt="user image">
                            </a>
                            <div class="dropdown-menu dropdown-menu-end dropdown-user-menu border border-gray-900"
                                aria-labelledby="navbarDropdown">
                                <div
                                    class="bg-body p-3 d-flex justify-content-between align-items-center gap-3 rounded mb-4">
                                    <a href="{{ route('update-profile') }}">
                                        <div class="d-inline-flex align-items-center gap-3">
                                            <div class="image flex-shrink-0">
                                                <img src="{{ setBaseUrlWithFileName(auth()->user()->file_url, 'image', 'users') }}"
                                                    class="img-fluid dropdown-user-menu-image" alt="">
                                            </div>
                                            <div class="content">
                                                <h6 class="mb-1">{{ auth()->user()->full_name ?? default_user_name() }}
                                                </h6>
                                                <span class="font-size-14 dropdown-user-menu-contnet text-white">
                                                    {{ auth()->user()->email }}</span>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <ul class="d-flex flex-column gap-3 list-inline m-0 p-0">
                                    <li>
                                        <a href="{{ route('watchList') }}" class="link-body-emphasis font-size-14">
                                            <span class="d-flex align-items-center justify-content-between gap-3">
                                                <span class="fw-medium">{{ __('frontend.my_watchlist') }}</span>
                                                <i class="ph ph-caret-right"></i>
                                            </span>
                                        </a>
                                    </li>

                                    <li>
                                        <a href="{{ route('update-profile') }}" class="link-body-emphasis font-size-14">
                                            <span class="d-flex align-items-center justify-content-between gap-3">
                                                <span class="fw-medium">{{ __('frontend.profile') }}</span>
                                                <i class="ph ph-caret-right"></i>
                                            </span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('subscriptionPlan') }}"
                                            class="link-body-emphasis font-size-14">
                                            <span class="d-flex align-items-center justify-content-between gap-3">
                                                <span class="fw-medium">{{ __('frontend.subscription_plan') }}</span>
                                                <i class="ph ph-caret-right"></i>
                                            </span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('accountSetting') }}"
                                            class="link-body-emphasis font-size-14">
                                            <span class="d-flex align-items-center justify-content-between gap-3">
                                                <span class="fw-medium">{{ __('frontend.account_setting') }}</span>
                                                <i class="ph ph-caret-right"></i>
                                            </span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('payment-history') }}"
                                            class="link-body-emphasis font-size-14">
                                            <span class="d-flex align-items-center justify-content-between gap-3">
                                                <span
                                                    class="fw-medium">{{ __('frontend.subscription_history') }}</span>
                                                <i class="ph ph-caret-right"></i>
                                            </span>
                                        </a>
                                    </li>

                                    <li>
                                        <a href="#" class="link-primary font-size-14" data-bs-toggle="modal"
                                            data-bs-target="#LogoutModal">
                                            <span class="d-flex align-items-center justify-content-between gap-3">
                                                <span class="fw-medium">{{ __('frontend.logout') }}</span>
                                            </span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        @else
                            <a class="dropdown-toggle-item " href="{{ route('login') }}">
                                <button class="btn btn-primary font-size-14 login-btn"> {{ __('frontend.login') }}
                                </button>
                            </a>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="LogoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-acoount-card">
        <div class="modal-content position-relative">
            <button type="button" class="btn btn-primary custom-close-btn rounded-2" data-bs-dismiss="modal">
                <i class="ph ph-x text-white fw-bold align-middle"></i>
            </button>
            <div class="modal-body modal-acoount-info text-center">
                <h6 class="mt-3 pt-2">{{ __('messages.logout_confirmation') }}</h6>
                <p class="text-muted mt-3">{{ __('messages.logout_confirmation_message') }}</p>
                <div class="d-flex justify-content-center gap-3 mt-4 pt-3">
                    <button type="button" class="btn btn-dark"
                        data-bs-dismiss="modal">{{ __('frontend.cancel') }}</button>
                    <button type="button" class="btn btn-primary"
                        onclick="confirmLogout()">{{ __('frontend.logout') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Logout confirmation function
    function confirmLogout() {
        const logoutButton = document.querySelector('#LogoutModal button[onclick="confirmLogout()"]');
        const originalText = logoutButton.textContent;

        logoutButton.disabled = true;
        logoutButton.textContent = '{{ __('messages.logging_out') }}';

        // Redirect to logout (show snackbar if available)
        if (window.successSnackbar) {
            window.successSnackbar('{{ __('messages.user_logout') }}');
            setTimeout(function(){
                window.location.href = "{{ route('user-logout') }}";
            }, 1000);
        } else {
            window.location.href = "{{ route('user-logout') }}";
        }
    }
</script>
