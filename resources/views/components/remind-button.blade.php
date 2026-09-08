
<button id="remind-btn-{{ $entertainmentId }}" class="remind-btn btn {{ $inremindlist ? 'btn-primary' : 'btn-dark' }} p-2"
            data-entertainment-id="{{ $entertainmentId }}"
            data-in-remindlist="{{ $inremindlist ? true : false }}"
            data-bs-toggle="tooltip" data-bs-title="{{ $inremindlist ? __('messages.remove_reminder') : __('messages.add_reminder') }}" data-bs-placement="top">
            <span class="d-flex align-items-center justify-content-center gap-2">
                <i class="ph {{ $inremindlist ? 'ph-fill ph-bell-simple-ringing' : 'ph ph-bell-simple-ringing' }}">
                    </i>
        </span>
</button>



<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>


<script>
    $(document).ready(function() {
        $('#remind-btn-{{ $entertainmentId }}').click(function() {
            var $this = $(this);
            var isInremindlist = $this.data('in-remindlist');
            var entertainmentId = $this.data('entertainment-id');
            let action = isInremindlist == '1' ? 'delete' : 'save';
            const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
            var data = isInremindlist
                ? { is_remind:0,id: [entertainmentId], _token: '{{ csrf_token() }}' }  // Send an array for delete
                : { is_remind:1,entertainment_id: entertainmentId, _token: '{{ csrf_token() }}' };

            // Perform the AJAX request
            $.ajax({
                url: action === 'save' ? `${baseUrl}/api/save-reminder` : `${baseUrl}/api/delete-reminder?is_ajax=1`,
                method: 'POST',
                data: data,
                success: function(response) {
                    if (response.status && response.message) {
                        window.successSnackbar(response.message);
                    }

                    $this.find('i').toggleClass('ph-fill');
                    $this.toggleClass('btn-primary btn-dark');
                    $this.data('in-remindlist', !isInremindlist);

                    var newInRemind = !isInremindlist ? 'true' : 'false';
                    var newTooltip = newInRemind === 'true' ? '{{ __('messages.remove_reminder') }}' : '{{ __('messages.add_reminder') }}';

                    if ($this.tooltip) {
                        $this.tooltip('dispose');
                        $this.attr('data-bs-title', newTooltip);
                        $this.tooltip();
                    }


                },
                error: function(xhr) {
                    if (xhr.status === 401) {

                        window.location.href =  `${baseUrl}/login`;

                    } else {
                        console.error(xhr);
                    }
                }
            });
        });
    });
</script>
