@extends('setting::backend.setting.index')
@section('title')
    {{ __('setting_sidebar.lbl_database_reset') }}
@endsection

@section('settings-content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"> <i class="fa-solid fa-database"></i> {{ __('setting_sidebar.lbl_database_reset') }}</h3>

    </div>




    <div class="row">
        <div class="col-lg-6">
            <div class="form-group">
                <div class="col-md-offset-3 col-sm-12 ">
                    <a href="{{ route('backend.dataload') }}"
                        class= "btn btn-md btn-primary float-md-end">{{ __('setting_sidebar.load_simple_data') }}</a>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="form-group">
                <div class="col-md-offset-3 col-sm-12 ">
                    <a href="{{ route('backend.datareset') }}"
                        class= "btn btn-md btn-primary float-md-end">{{ __('setting_sidebar.reset_database') }}</a>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('after-scripts')
    <script>
        function clearCache() {
            Swal.fire({
                title: '{{ __('messages.are_you_sure') }}',
                text: "{{ __('messages.are_you_sure_you_want_to_clear_the_cache') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: '{{ __('messages.yes_clear_it') }}',
                cancelButtonText: '{{ __('messages.cancel') }}',
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('{{ route('backend.settings.clear-cache') }}', {
                            method: 'GET',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status) {
                                Swal.fire({
                                    title: '{{ __('messages.success') }}',
                                    text: '{{ __('messages.cache_clear_successfully') }}', // Use the dynamic message from the server
                                    icon: 'success',
                                    showConfirmButton: false,
                                    timer: 3000,
                                    timerProgressBar: true
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'An unexpected error occurred.',
                                    icon: 'error',
                                    showConfirmButton: true
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error clearing cache:', error);
                            Swal.fire({
                                title: 'Error!',
                                text: 'An error occurred while clearing the cache.',
                                icon: 'error',
                                showConfirmButton: true
                            });
                        });
                }
            });
        }
    </script>
@endpush
