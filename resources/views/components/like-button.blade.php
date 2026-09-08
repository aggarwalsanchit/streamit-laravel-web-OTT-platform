<button class="like-btn action-btn btn {{ $isLiked == true ? 'btn-primary' : 'btn-dark' }}"
    data-entertainment-id="{{ $entertainmentId }}" data-type="{{ $type }}" data-bs-toggle="tooltip"
    title="{{ $isLiked == true ? __('messages.lbl_unlike') : __('messages.lbl_like') }}"
    data-is-liked="{{ $isLiked ? 'true' : 'false' }}">
    <i class="{{ $isLiked == true ? 'ph-fill ph-heart' : 'ph ph-heart' }}"></i>
</button>
{{-- <script src="{{ mix('js/backend-custom.js') }}"></script>
<script>
    $(document).ready(function() {

        var $likeButton = $('#like-btn-{{ $entertainmentId }}');
        var isLiked = $likeButton.data('is-liked') == true;
        const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');

        $likeButton.click(function() {
            var url = `${baseUrl}/api/save-likes`;
            var newIsLiked = isLiked ? 0 : 1; // Toggle like status
            var type = $likeButton.data('type');

            $.ajax({
                url: url,
                method: 'POST',
                data: {
                    entertainment_id: $likeButton.data('entertainment-id'),
                    is_like: newIsLiked,
                    type: type,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.status) {
                        window.successSnackbar(response.message)
                        var $icon = $likeButton.find('i');
                        var newTitle = '';
                        if (newIsLiked === 1) {
                            $icon.removeClass('ph ph-heart').addClass('ph-fill ph-heart');
                            $likeButton.removeClass('btn-dark').addClass('btn-primary');
                            newTitle = '{{ __('messages.lbl_unlike') }}';
                        } else {
                            $icon.removeClass('ph-fill ph-heart').addClass('ph ph-heart');
                            $likeButton.removeClass('btn-primary').addClass('btn-dark');
                            newTitle = '{{ __('messages.lbl_like') }}';
                        }

                        // Update tooltip text robustly across BS versions
                        $likeButton.attr('title', newTitle);
                        $likeButton.attr('data-bs-original-title', newTitle);
                        try {
                            var tt = bootstrap.Tooltip.getInstance($likeButton[0]);
                            if (tt) { tt.dispose(); }
                            new bootstrap.Tooltip($likeButton[0]);
                        } catch (e) {}

                        $likeButton.data('is-liked', newIsLiked === 1);
                        isLiked = newIsLiked === 1;
                    }

                },
                error: function(xhr) {
                    if (xhr.status === 401) {

                        window.location.href = `${baseUrl}/login`;

                    } else {
                        console.error(xhr);
                    }
                }
            });
        });
    });

</script> --}}
