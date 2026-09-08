@section('title', __('messages.access_control'))

<x-guest-layout>
    <x-auth-card>
        <x-slot name="logo">
            <a href="{{ url('/') }}">
                <x-application-logo class="w-20 h-20" />
            </a>
        </x-slot>

        <div class="mb-4">
            {{ __('messages.password_msg') }}
        </div>

        <!-- Validation Errors -->
        {{-- <x-auth-validation-errors class="mb-4" :errors="$errors" /> --}}

        <form method="POST" id="confirm-password-form" action="{{ route('password.confirm') }}" novalidate>
            @csrf

            <!-- Password -->
            <div>
                <x-label for="password" :value="__('messages.password')" />

                <x-input id="password" class="block mt-1 @error('password') is-invalid @enderror" type="password"
                    name="password" placeholder="{{ __('messages.enter_password') }}" required
                    autocomplete="current-password" />
                @error('password')
                    <div class="invalid-feedback d-block" id="password-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex justify-content-end align-items-center mt-4">
                <x-button id="confirm-submit" type="submit">
                    {{ __('messages.confirm') }}
                </x-button>
            </div>
        </form>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var form = document.getElementById('confirm-password-form');
                var btn = document.getElementById('confirm-submit');
                if (!form || !btn) return;
                form.addEventListener('submit', function() {
                    if (btn.dataset.loading === '1') return;
                    btn.dataset.loading = '1';
                    btn.setAttribute('disabled', 'disabled');
                    btn.dataset.originalHtml = btn.innerHTML;
                    btn.innerHTML =
                        '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>{{ __('messages.loading') }}';
                }, {
                    once: true
                });
            });
        </script>
    </x-auth-card>
</x-guest-layout>
