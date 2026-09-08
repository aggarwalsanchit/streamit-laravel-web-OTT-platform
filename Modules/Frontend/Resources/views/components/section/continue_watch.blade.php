<div class="continue-watching-block">
    <div class="d-flex align-items-center justify-content-between my-3">

        @php

          $profile_id=getCurrentProfile(auth()->user()->id, request());

          $name = optional(App\Models\UserMultiProfile::where('id', $profile_id)->first())->name ?? null;

        @endphp

            @if($name == null)
            <h5 class="main-title text-capitalize mb-0">{{__('frontend.continue_watching')}} </h5>
            @else

            <h5 class="main-title text-capitalize mb-0">{{__('frontend.continue_watching_for')}}  {{ $name }}</h5>
            @endif


        @if(count($continuewatchData)>6)
        <a href="{{ route('continueWatchList')}}" class="view-all-button text-decoration-none flex-none"><span>{{__('frontend.view_all')}}</span> <i class="ph ph-caret-right"></i></a>
        @endif
    </div>
    <div class="card-style-slider {{ count($continuewatchData) < 7 ? 'slide-data-less' : '' }}">
        <div class="card-style-slider continue-watch-delete {{ count($continuewatchData) < 7 ? 'slide-data-less' : '' }}">
            <div class="slick-general slick-general-continue-watch " data-items="5.5" data-items-laptop="4.5" data-items-tab="3.5" data-items-mobile-sm="2.5"
                data-items-mobile="1.2" data-speed="1000" data-autoplay="false" data-center="false" data-infinite="false"
                data-navigation="true" data-pagination="false" data-spacing="12">
                    @foreach (array_values($continuewatchData) as $data)
                        <div class="slick-item remove-continuewatch-card">
                             @include('frontend::components.card.card_continue_watch' ,['value' =>$data ])
                        </div>
                    @endforeach
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Use event delegation to handle dynamically loaded buttons
        $(document).off('click.continueRemoveBtn').on('click.continueRemoveBtn', '.continue_remove_btn', function(e) {
            e.preventDefault();
            const button = this;
            const itemId = button.getAttribute('data-id');
            const itemName = button.getAttribute('data-name');
            const baseUrl = document.querySelector('meta[name="baseUrl"]')?.getAttribute('content') || '';

            if (!itemId) {
                console.error('Continue watch delete button missing data-id attribute');
                return;
            }

            document.body?.setAttribute('data-swal2-theme', 'dark');

                Swal.fire({
                    title: @json(__('messages.are_you_sure')),
                    text: window.localMessagesUpdate?.frontend?.continue_watch_remove_with_name?.replace(':name', itemName) || `Do you want to remove "${itemName}" from Continue Watching?`,
                    showCancelButton: true,
                    confirmButtonColor: 'var(--bs-primary)',
                    cancelButtonColor: '#22292E',
                    confirmButtonText: @json(__('messages.yes_delete_it')),
                    cancelButtonText: @json(__('messages.cancel')),
                    background: '#1e1e1e',
                    reverseButtons: true,
                    color: '#ffffff',
                    customClass: {
                        popup: 'swal2-dark',
                        title: 'swal2-title-dark',
                        confirmButton: 'swal2-confirm-dark',
                        cancelButton: 'swal2-cancel-dark'
                    }
                }).then(async (result) => {
                    if (!result.isConfirmed) return;

                    const data = {
                        id: itemId,
                        _token: '{{ csrf_token() }}'
                    };

                    try {
                        const response = await fetch(`${baseUrl}/api/delete-continuewatch`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(data),
                        });

                        if (!response.ok) {
                            let msg = 'Failed to delete item';
                            try {
                                const json = await response.json();
                                msg = json?.message || msg;
                            } catch (e) {}
                            window.errorSnackbar ? window.errorSnackbar(msg) : alert(msg);
                            return;
                        }

                        let successMsg = @json(__('movie.continuewatch_delete'));
                        try {
                            const json = await response.json();
                            successMsg = json?.message || successMsg;
                        } catch (e) {}

                        window.successSnackbar && window.successSnackbar(successMsg);

                        $(button).closest('.slick-item').remove();
                        const totalSlickItems = $('.continue-watch-delete .slick-item').length;
                        if (totalSlickItems === 0) {
                            $('.continue-watching-block').addClass('d-none');
                        }
                        
                        // Re-initialize slick position if needed
                        if ($('.slick-general-continue-watch').hasClass('slick-initialized')) {
                            $('.slick-general-continue-watch').slick('setPosition');
                        }

                    } catch (error) {
                        console.error('Error deleting continue watch item:', error);
                        window.errorSnackbar ? window.errorSnackbar('An error occurred while trying to delete the item.') : alert('An error occurred while trying to delete the item.');
                    }
                });
            });
        });
</script>
