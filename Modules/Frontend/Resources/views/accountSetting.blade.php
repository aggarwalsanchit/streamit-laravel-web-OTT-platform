@extends('frontend::layouts.master')

@section('title')
    {{ __('frontend.account_setting') }}
@endsection

@section('content')
    <div>

        <div class="section-spacing">
            <div class="container-fluid">
                <div class="row gy-4">
                    <div class="col-lg-3 col-md-4">
                        @include('frontend::components.account-settings-sidebar')
                    </div>
                    <div class="col-lg-9 col-md-8">
                        <div class="d-flex justify-content-start mb-4">
                            <h4 class="m-0">{{ __('frontend.account_setting') }}</h4>
                        </div>
                        <div class="row gy-4">
                            <div class="col-lg-7 col-xl-8">
                                <div class="card h-100 account-card">
                                    <div class="card-body">
                                        @if (!empty($subscriptions))
                                            <div class="upgrade-plan mb-0">
                                                <div class="d-flex align-items-center gap-4">
                                                    <div class="acc-icon bg-warning-subtle">
                                                        <i class="ph ph-crown text-warning"></i>
                                                    </div>
                                                    <div>
                                                        <h4 class="super-plan">{{ $subscriptions['name'] }}</h4>
                                                        <p>{{ __('messages.premium_streaming_experience') }}</p>
                                                    </div>
                                                </div>
                                                <div class="bg-primary-subtle rounded p-3 mt-4">
                                                    <p class="mb-0 text-body"><i class="ph ph-watch px-3"></i>
                                                        {{ __('frontend.expiring_on') }}
                                                        {{ formatDate($subscriptions['end_date']) ?? '-' }}</p>
                                                </div>
                                                <div class="d-flex align-items-center justify-content-center gap-4 mt-5">
                                                    <a href="{{ route('subscriptionPlan') }}"
                                                        class="btn btn-warning">{{ __('frontend.upgrade') }}</a>
                                                    <button type="button" class="btn btn-primary" id="cancelSubscriptionBtn"
                                                        onclick="confirmCancelSubscription({{ $subscriptions->id }})">
                                                        <span class="btn-text">{{ __('frontend.cancel') }}</span>
                                                        <span class="btn-loader d-none">
                                                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                                            {{ __('messages.loading') }}
                                                        </span>
                                                    </button>
                                                </div>
                                            </div>
                                        @else
                                            <div class="upgrade-plan">
                                                <div
                                                    class="d-flex justify-content-between align-items-center gap-4 flex-wrap">
                                                    <div class="">
                                                        <h6 class="super-plan m-0">
                                                            {{ __('frontend.not_active_subscription') }}</h6>
                                                        <p class="mb-0 text-body">{{ __('frontend.no_subscription') }}</p>
                                                    </div>
                                                    <a href="{{ route('subscriptionPlan') }}" class="btn btn-warning">
                                                        <span class="d-flex align-items-center gap-2">
                                                            <i class="ph-fill ph-crown-simple fs-6"></i>
                                                            {{ __('messages.subscribe_now') }}
                                                        </span>
                                                    </a>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5 col-xl-4">
                                <div class="card h-100 account-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center gap-4 mb-4">
                                            <div class="acc-icon bg-primary-subtle">
                                                <i class="ph ph-device-mobile"></i>
                                            </div>
                                            <div>
                                                <h4 class="super-plan">{{ __('messages.contact_info') }}</h4>
                                                <p class="mb-0">{{ __('messages.your_registered_details') }}</p>
                                            </div>
                                        </div>
                                        <div class="card bg-body border mb-5">
                                            @if($user->mobile)
                                                <div class="card-body">
                                                    <h6 class="main-title text-capitalize mb-2">
                                                        {{ __('frontend.register_mobile_number') }}:</h6>
                                                    <div class="d-flex flex-column">
                                                        <p class="m-0">
                                                            <strong>{{ __('frontend.mobile') }}:</strong>
                                                            @if ($user->mobile)
                                                                @php
                                                                    $mobile = $user->mobile;
                                                                    $length = strlen($mobile);
                                                                    if ($length > 3) {
                                                                        $masked =
                                                                            str_repeat('*', $length - 3) .
                                                                            substr($mobile, -3);
                                                                    } else {
                                                                        $masked = $mobile;
                                                                    }
                                                                @endphp
                                                                {{ $masked }}
                                                            @endif
                                                        </p>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                        <a class="btn btn-primary d-flex align-items-center justify-content-center gap-2"
                                            href="{{ route('update-profile') }}" >
                                            <i class="ph ph-pencil-simple-line align-middle"></i>{{ __('messages.update_number') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="card h-100 account-card">
                                    <div class="card-body">
                                        <!-- Your Device -->
                                        <div class="d-flex align-items-center gap-4 justify-content-between mb-4 flex-wrap">
                                            <div class="d-flex align-items-center gap-4">
                                                <div class="acc-icon bg-primary-subtle">
                                                    <i class="ph ph-devices"></i>
                                                </div>
                                                <div>
                                                    <h4 class="super-plan">{{ __('messages.active_devices') }}</h4>
                                                    <p class="mb-0">{{ __('messages.manage_logged_in_devices') }}</p>
                                                </div>
                                            </div>
                                            @if ($other_devices && count($other_devices) > 0)
                                                <button type="button" class="btn btn-primary"
                                                    onclick="confirmLogoutAll()">{{ __('messages.logout_all') }}</button>
                                            @endif
                                        </div>

                                        <div>
                                            <div class="mb-5">
                                                @if ($your_device)
                                                    <!-- <h5 class="mb-3">{{ __('frontend.your_devices') }}</h5> -->
                                                    <div class="card bg-body rounded divise-card mb-4">
                                                        <div class="card-body">
                                                            <div
                                                                class="d-flex align-items-md-center flex-md-row flex-column gap-3">
                                                                <div class="">
                                                                    <i class="ph ph-devices fs-3"></i>
                                                                </div>
                                                                <div
                                                                    class="d-flex justify-content-between align-items-center gap-4 account-setting-content flex-grow-1">
                                                                    <div>
                                                                        <span
                                                                            class="badge bg-primary p-1 rounded-pill font-size-10">{{ __('messages.current_device') }}</span>
                                                                        <h5 class="my-1">
                                                                            {{ $your_device->device_name ?? 'Unknown Device' }}
                                                                        </h5>
                                                                        <div class="d-flex align-items-center gap-2">
                                                                            <i class="ph ph-clock-clockwise text-white"></i>
                                                                            <small>{{ formatDateTimeWithTimezone($your_device->updated_at) }}</small>
                                                                        </div>
                                                                    </div>
                                                                    <button type="button" class="btn btn-primary-subtle"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#LogoutModal">{{ __('frontend.logout') }}</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @else
                                                    <p class="text-muted">{{ __('frontend.no_current_device') }}</p>
                                                @endif
                                            </div>

                                            <!-- Other Devices -->
                                            <div>
                                                <h5 class="mb-3">{{ __('frontend.other_devices') }}</h5>
                                                <div class="row gy-2">
                                                    @forelse($other_devices as $device)
                                                        <div class="col-lg-6">
                                                            <div class="card">
                                                                <div class="card-body bg-body rounded">
                                                                    <div
                                                                        class="d-flex justify-content-between align-items-center gap-4 account-setting-content">
                                                                        <div>
                                                                            <h6>{{ $device->device_name ?? 'No Device' }}
                                                                            </h6>
                                                                            <div class="d-flex align-items-center gap-2">
                                                                                <i
                                                                                    class="ph ph-clock-clockwise text-white"></i>
                                                                                <small>{{ formatDateTimeWithTimezone($device->updated_at) }}</small>
                                                                            </div>
                                                                        </div>
                                                                        <button type="button" class="btn btn-link p-0"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#DeviceLogoutModal"
                                                                            data-device-id="{{ $device->device_id }}"
                                                                            data-device-name="{{ $device->device_name ?? 'Unknown Device' }}">{{ __('frontend.logout') }}</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @empty
                                                        <p class="text-muted">{{ __('frontend.no_other_devices') }}</p>
                                                    @endforelse
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="card h-100 account-card">
                                    <div class="card-body">
                                        <div class="bg-primary-subtle text-body p-3 rounded">
                                            <div class="d-flex align-items-center gap-4 mb-4">
                                                <div class="acc-icon bg-warning text-white">
                                                    <i class="ph ph-warning"></i>
                                                </div>
                                                <div>
                                                    <h4 class="super-plan">{{ __('messages.danger_zone') }}</h4>
                                                    <p class="mb-0">{{ __('messages.permanent_delete_warning') }}</p>
                                                </div>
                                            </div>
                                            <button class="btn btn-primary" data-bs-toggle="modal"
                                                data-bs-target="#deleteAccountModal">{{ __('frontend.delete_account') }}</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- old design -->
                        <!-- @if (!empty($subscriptions))
    <div class="upgrade-plan bg-warning-subtle d-flex align-items-center justify-content-between flex-wrap gap-3 p-4 rounded border border-warning">
                                        <div class="d-flex justify-content-center align-items-center gap-4 flex-wrap">
                                            <i class="ph ph-crown text-warning"></i>
                                            <div>
                                                <h6 class="super-plan">{{ $subscriptions['name'] }}</h6>
                                                <p class="mb-0 text-body">{{ __('frontend.expiring_on') }}  {{ formatDate($subscriptions['end_date']) ?? '-' }}</p>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-3">
                                            <a href="{{ route('subscriptionPlan') }}" class="btn btn-warning">{{ __('frontend.upgrade') }}</a>
                                            <button type="button" class="btn btn-primary" onclick="confirmCancelSubscription({{ $subscriptions->id }})">{{ __('frontend.cancel') }}</button>
                                        </div>
                                    </div>
@else
    <div class="upgrade-plan bg-warning-subtle  p-4 rounded border border-warning">
                                        <div class="d-flex justify-content-between align-items-center gap-4 flex-wrap">
                                            <div class="">
                                                    <h6 class="super-plan m-0">{{ __('frontend.not_active_subscription') }}</h6>
                                                    <p class="mb-0 text-body">{{ __('frontend.no_subscription') }}</p>
                                            </div>
                                            <button class="btn btn-warning">
                                                <span class="d-flex align-items-center gap-2">
                                                    <i class="ph-fill ph-crown-simple fs-6"></i>
                                                    Subscribe Now
                                                </span>
                                            </button>
                                        </div>
                                    </div>
    @endif -->


                        <!-- Register Mobile Number -->
                        <!-- <div class="mb-5">
                                    <h5 class="main-title text-capitalize mb-2">{{ __('frontend.register_mobile_number') }}:</h5>
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="d-flex flex-column">
                                                    <p class="m-0">
                                                        <strong>{{ __('frontend.mobile') }}:</strong>
                                                        @if ($user->mobile)
    @php
        $mobile = $user->mobile;
        $length = strlen($mobile);
        if ($length > 3) {
            $masked = str_repeat('*', $length - 3) . substr($mobile, -3);
        } else {
            $masked = $mobile;
        }
    @endphp
                                                            {{ $masked }}
    @endif
                                                    </p>
                                                </div>
                                                <a class="text-warning" href="{{ route('update-profile') }}" data-bs-toggle="tooltip" title="{{ __('frontend.edit') }}">
                                                    <i class="ph ph-pencil-simple-line align-middle"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div> -->

                        <!-- Your Device -->
                        <!-- <div>
                                    <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
                                        <h5 class="main-title text-capitalize mb-0">All Devices</h5>
                                        <button type="button" class="btn btn-dark" onclick="confirmLogoutAll()">Logout All</button>
                                    </div>

                                    <div class="card">
                                        <div class="card-body">
                                                <div class="mb-5">
                                                @if ($your_device)
    <h5 class="mb-3">{{ __('frontend.your_devices') }}</h5>
                                                <div class="card divise-card mb-4">
                                                    <div class="card-body bg-body rounded">
                                                        <div class="d-flex align-items-md-center flex-md-row flex-column gap-3">
                                                            <div class="">
                                                                <i class="ph ph-devices fs-3"></i>
                                                            </div>
                                                            <div class="d-flex justify-content-between align-items-center gap-4 account-setting-content flex-grow-1">
                                                                <div>
                                                                    <span class="badge bg-primary p-1 rounded-pill font-size-10">Current Device</span>
                                                                    <h5 class="my-1">{{ $your_device->device_name ?? 'Unknown Device' }}</h5>
                                                                        <div class="d-flex align-items-center gap-2">
                                                                        <i class="ph ph-clock-clockwise text-white"></i>
                                                                        <small>{{ formatDate($your_device->updated_at) }}</small>
                                                                    </div>
                                                                </div>
                                                                <button type="button" class="btn btn-link" data-bs-toggle="modal" data-bs-target="#LogoutModal">{{ __('frontend.logout') }}</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
@else
    <p class="text-muted">{{ __('frontend.no_current_device') }}</p>
    @endif
                                            </div>


                                            <div>
                                                <h5 class="mb-3">{{ __('frontend.other_devices') }}</h5>
                                                <div class="row gy-2">
                                                    @forelse($other_devices as $device)
    <div class="col-lg-6">
                                                        <div class="card">
                                                            <div class="card-body bg-body rounded">
                                                                <div class="d-flex justify-content-between align-items-center gap-4 account-setting-content">
                                                                    <div>
                                                                        <h6>{{ $device->device_name ?? 'No Device' }}</h6>
                                                                        <div class="d-flex align-items-center gap-2">
                                                                            <i class="ph ph-clock-clockwise text-white"></i>
                                                                            <small>{{ formatDate($device->updated_at) }}</small>
                                                                        </div>
                                                                    </div>
                                                                    <button type="button" class="btn btn-link p-0" data-bs-toggle="modal" data-bs-target="#DeviceLogoutModal" data-device-id="{{ $device->device_id }}" data-device-name="{{ $device->device_name ?? 'Unknown Device' }}">{{ __('frontend.logout') }}</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                        @empty
                                                    <p class="text-muted">{{ __('frontend.no_other_devices') }}</p>
    @endforelse
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                                <div class="text-end mt-4">
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">{{ __('frontend.delete_account') }}</button>
                                </div> -->

                        <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-acoount-card">
                                <div class="modal-content position-relative">
                                    <button type="button" class="btn btn-primary custom-close-btn rounded-2"
                                        data-bs-dismiss="modal">
                                        <i class="ph ph-x text-white fw-bold align-middle"></i>
                                    </button>
                                    <div class="modal-body modal-acoount-info text-center">
                                        <img src="{{ asset('img/web-img/remove_icon.png') }}" alt="delete image">
                                        <h4 class="mt-5 pt-4">{{ __('frontend.permanent_delete') }}</h4>
                                        <div class="d-flex justify-content-center gap-3 mt-4 pt-3">
                                            <button type="button" class="btn btn-dark"
                                                data-bs-dismiss="modal">{{ __('frontend.cancel') }}</button>
                                            <button type="button" class="btn btn-primary"
                                                onclick="proceedToDeleteAccount()">{{ __('frontend.proceed') }}</button>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="proceedAccountModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered successfully-acoount-card">
                                <div class="modal-content position-relative"
                                    style="background-image: url('../img/web-img/successfully_deleted.png');  background-repeat: no-repeat; background-size: cover;">
                                    <button type="button" class="btn btn-primary custom-close-btn rounded-2"
                                        data-bs-dismiss="modal">
                                        <i class="ph ph-x text-white fw-bold align-middle"></i>
                                    </button>
                                    <div class="modal-body successfully-info text-center">
                                        <div class="modal-icon-check m-auto fw-bold text-center">
                                            <i class="ph ph-check text-white"></i>
                                        </div>
                                        <h5 class="mt-5 pt-3">{{ __('frontend.success') }}</h5>
                                        <p class="pb-4 mb-0">{{ __('frontend.success_content') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="CancleSubscriptionModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-acoount-card">
                                <div class="modal-content position-relative">
                                    <button type="button" class="btn btn-primary custom-close-btn rounded-2"
                                        data-bs-dismiss="modal">
                                        <i class="ph ph-x text-white fw-bold align-middle"></i>
                                    </button>
                                    <div class="modal-body modal-acoount-info text-center">
                                        <h6 class="mt-3 pt-2">{{ __('frontend.cancle_subscription') }}</h6>
                                        <div class="d-flex justify-content-center gap-3 mt-4 pt-3">
                                            <button type="button" class=" btn btn-dark"
                                                data-bs-dismiss="modal">{{ __('frontend.cancel') }}</button>
                                            <button type="button" class="btn btn-primary"
                                                onclick="cancelSubscription()">{{ __('frontend.proceed') }}</button>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Logout Confirmation Modal -->
                        <div class="modal fade" id="LogoutModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-acoount-card">
                                <div class="modal-content position-relative">
                                    <button type="button" class="btn btn-primary custom-close-btn rounded-2"
                                        data-bs-dismiss="modal">
                                        <i class="ph ph-x text-white fw-bold align-middle"></i>
                                    </button>
                                    <div class="modal-body modal-acoount-info text-center">
                                        <p class="text-muted mt-3">{{ __('messages.logout_confirmation_message') }}</p>
                                        <div class="d-flex justify-content-center gap-3 mt-4 pt-3">
                                            <button type="button" class="btn btn-dark"
                                                data-bs-dismiss="modal">{{ __('frontend.cancel') }}</button>
                                            <button type="button" class="btn btn-primary"
                                                onclick="confirmLogoutAccount()">{{ __('frontend.logout') }}</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Device Logout Confirmation Modal -->
                        <div class="modal fade" id="DeviceLogoutModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-acoount-card">
                                <div class="modal-content position-relative">
                                    <button type="button" class="btn btn-primary custom-close-btn rounded-2"
                                        data-bs-dismiss="modal">
                                        <i class="ph ph-x text-white fw-bold align-middle"></i>
                                    </button>
                                    <div class="modal-body modal-acoount-info text-center">
                                        <h6 class="mt-3 pt-2">{{ __('messages.logout_confirmation') }}</h6>
                                        <p class="text-muted mt-3">{{ __('messages.device_logout_confirmation_message') }}
                                            <strong id="deviceName"></strong> {{ __('messages.lbl_device') }}
                                        </p>
                                        <div class="d-flex justify-content-center gap-3 mt-4 pt-3">
                                            <button type="button" class="btn btn-dark"
                                                data-bs-dismiss="modal">{{ __('frontend.cancel') }}</button>
                                            <button type="button" class="btn btn-primary"
                                                id="deviceLogoutConfirmBtn"
                                                onclick="confirmDeviceLogout()">{{ __('frontend.logout') }}</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/form/index.js') }}" defer></script>
    <script>
        let baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
        document.body.setAttribute('data-swal2-theme', 'dark');


        function confirmLogoutAll() {
            if (!window.Swal) {
                if (!confirm('{{ __('messages.logout_from_all_devices') }}')) return;
                return fetch(baseUrl + '/api/logout-all-data', {
                        method: 'GET',
                        credentials: 'same-origin'
                    })
                    .finally(() => window.location.reload());
            }
            Swal.fire({
                title: '{{ __('messages.logout_all_title') }}',
                text: '{{ __('messages.logout_all_text') }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '{{ __('frontend.logout') }}',
                cancelButtonText: '{{ __('frontend.cancel') }}',
                confirmButtonColor: 'var(--bs-primary)',
                reverseButtons: true
            }).then(function(res) {
                if (!res.isConfirmed) return;
                fetch(baseUrl + '/api/logout-all-data', {
                        method: 'GET',
                        credentials: 'same-origin'
                    })
                    .finally(() => window.location.reload());
            });
        }

        function confirmCancelSubscription(subscriptionId) {
            const proceedCancel = () => cancelAndLogoutAll(subscriptionId);

            if (window.Swal) {
                Swal.fire({
                    title: '{{ __('frontend.cancle_subscription') }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '{{ __('frontend.proceed') }}',
                    cancelButtonText: '{{ __('frontend.cancel') }}',
                    confirmButtonColor: 'var(--bs-primary)',
                    reverseButtons: true
                }).then(result => {
                    if (result.isConfirmed) proceedCancel();
                });
            } else {
                if (confirm('{{ __('messages.confirm_cancel_subscription_message') }}')) {
                    proceedCancel();
                }
            }
        }

        const logoutMessage = '{{ __('messages.after_cancel_plan_logout_message') }}';


        function cancelAndLogoutAll(subscriptionId) {
            const cancelBtn = document.getElementById('cancelSubscriptionBtn');
            if (cancelBtn) {
                cancelBtn.disabled = true;
                cancelBtn.querySelector('.btn-text').classList.add('d-none');
                cancelBtn.querySelector('.btn-loader').classList.remove('d-none');
            }

            fetch(`${baseUrl}/cancel-subscription`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        id: subscriptionId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (cancelBtn) {
                        cancelBtn.disabled = false;
                        cancelBtn.querySelector('.btn-text').classList.remove('d-none');
                        cancelBtn.querySelector('.btn-loader').classList.add('d-none');
                    }

                    if (!data.success) {
                        return window.errorSnackbar?.('{{ __('messages.could_not_cancel_subscription') }}');
                    }
                    const logoutAll = () => fetch(baseUrl + '/api/logout-all-data', {
                        method: 'GET',
                        credentials: 'same-origin'
                    }).then(function() {
                        const loginUrl = baseUrl + '/login';
                        localStorage.setItem('logout_message', logoutMessage);
                        window.location.href = loginUrl;
                    })
                    .catch(function(error) {
                        console.error('Error:', error);
                        const loginUrl = baseUrl + '/login';
                        localStorage.setItem('logout_message', logoutMessage);
                        window.location.href = loginUrl;
                    });

                    
                    logoutAll();
                    
                })
                .catch(err => {
                    console.error(err);
                    if (cancelBtn) {
                        cancelBtn.disabled = false;
                        cancelBtn.querySelector('.btn-text').classList.remove('d-none');
                        cancelBtn.querySelector('.btn-loader').classList.add('d-none');
                    }
                });
        }

        function cancelSubscription() {

            const subscriptionId = document.querySelector('[data-bs-target="#CancleSubscriptionModal"]').getAttribute(
                'data-subscription-id');

            fetch(`${baseUrl}/cancel-subscription`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        id: subscriptionId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.successSnackbar('{{ __('messages.your_subscription_has_been_canceled') }}');
                        location.reload();
                    } else {

                    }
                })
                .catch(error => {
                    console.error('Error:', error);

                });

        }

        function proceedToDeleteAccount() {
            fetch(`${baseUrl}/api/delete-account`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status) {
                        $('#deleteAccountModal').modal('hide');

                        $('#proceedAccountModal').modal('show');

                        // Redirect after 3 seconds
                        setTimeout(function() {
                            window.location.href = '{{ route('user.login') }}';
                        }, 3000);
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An unexpected error occurred.');
                });
        }

        function confirmLogoutAccount() {
            const logoutButton = document.querySelector('#LogoutModal button[onclick="confirmLogoutAccount()"]');
            if (logoutButton) {
                const originalText = logoutButton.textContent;
                logoutButton.disabled = true;
                logoutButton.textContent = '{{ __('messages.logging_out') }}';
            }

            if (window.successSnackbar) {
                window.successSnackbar('{{ __('messages.user_logout') }}');
                setTimeout(function(){
                    window.location.href = "{{ route('user-logout') }}";
                }, 1000);
            } else {
                window.location.href = "{{ route('user-logout') }}";
            }
        }

        function confirmDeviceLogout() {
            const modal = document.getElementById('DeviceLogoutModal');
            if (!modal) {
                console.warn('Device logout modal not found.');
                return;
            }

            const deviceId = modal.getAttribute('data-device-id');
            if (!deviceId) {
                window.errorSnackbar?.('{{ __('users.device_not_found') }}');
                return;
            }

            const logoutButton = document.getElementById('deviceLogoutConfirmBtn');
            if (logoutButton) {
                logoutButton.disabled = true;
                logoutButton.dataset.originalText = logoutButton.dataset.originalText || logoutButton.innerHTML;
                logoutButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>{{ __('messages.logging_out') }}';
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('device-logout') }}';

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);

            const deviceIdInput = document.createElement('input');
            deviceIdInput.type = 'hidden';
            deviceIdInput.name = 'device_id';
            deviceIdInput.value = deviceId;
            form.appendChild(deviceIdInput);

            document.body.appendChild(form);
            form.submit();
        }

        const deviceLogoutModal = document.getElementById('DeviceLogoutModal');
        if (deviceLogoutModal) {
            deviceLogoutModal.addEventListener('show.bs.modal', function(event) {
                // const logoutButton = document.getElementById('deviceLogoutConfirmBtn');
                // if (logoutButton && logoutButton.dataset.originalText) {
                //     logoutButton.disabled = false;
                //     logoutButton.innerHTML = logoutButton.dataset.originalText;
                // }
                const button = event.relatedTarget;
                const deviceId = button?.getAttribute('data-device-id');
                const deviceName = button?.getAttribute('data-device-name') || '{{ __('messages.no_devices_found') }}';
                this.setAttribute('data-device-id', deviceId || '');
                const deviceNameLabel = document.getElementById('deviceName');
                if (deviceNameLabel) {
                    deviceNameLabel.textContent = deviceName;
                }
                const logoutButton = document.getElementById('deviceLogoutConfirmBtn');
                if (logoutButton) {
                    logoutButton.disabled = false;
                    logoutButton.innerHTML = logoutButton.dataset.originalText || '{{ __('frontend.logout') }}';
                }
            });
        }
    </script>
@endsection
