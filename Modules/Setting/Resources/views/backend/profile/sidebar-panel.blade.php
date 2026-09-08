<div class="col-md-4 col-lg-3">
    <div id="setting-sidebar" class="setting-sidebar-inner">
        <div class="card">
            <div class="card-body">
                <div class="list-group list-group-flush" id="setting-list">
                    <div class="mb-3 active-menu" id="Settings.module">
                        <a href="{{ route('backend.profile.information') }}"
                            class="btn btn-border w-100 {{ request()->routeIs('backend.profile.information') || request()->routeIs('backend.my-profile') || request()->is('app/profile/information') || request()->is('app/my-profile*') ? 'active' : '' }}"><i
                                class="fa-solid fa-user"></i>{{ __('profile.info') }}</a>
                    </div>
                        <div class="mb-3 active-menu" id="Settings.home">
                            <a href="{{ route('backend.profile.change-password') }}"
                                class="btn btn-border w-100 {{ request()->routeIs('backend.profile.change-password') || request()->is('app/profile/change-password') ? 'active' : '' }}"><i
                                    class="fa-solid fa-key"></i>{{ __('profile.change_password') }}</a>
                        </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function toggle() {
        const formOffcanvas = document.getElementById('offcanvas');
        formOffcanvas.classList.add('show');
    }

    function hasPermission(permission) {
        return window.auth_permissions.includes(permission);
    }
</script>
@endpush

<style scoped>
    .btn-border {
        text-align: left;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
</style>
