<!-- Review Modal -->
<div class="modal fade rating-modal" id="rattingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center">
                <button class="custom-close-btn btn btn-primary" data-bs-dismiss="modal">
                    <i class="ph ph-x"></i>
                </button>
                <h5 class="mb-2">{{ __('frontend.share_movie_experience') }}</h5>
                <p class="m-0">{{ __('frontend.share_your_thoughts') }}</p>

                <div class="mt-5 pt-3">
                    <form class="m-0" id="reviewForm" action="javascript:void(0);" onsubmit="return false;">
                        <ul class="list-inline m-0 p-0 d-flex align-items-center justify-content-center gap-3 rating-list" id="ratingStarsList">
                            @for ($i = 1; $i <= 5; $i++)
                                <li data-value="{{ $i }}" class="star list-inline-item" role="button" tabindex="0">
                                    <span class="text-warning fs-4 icon">
                                        <i class="ph-fill ph-star icon-fill"></i>
                                        <i class="ph ph-star icon-normal"></i>
                                    </span>
                                </li>
                            @endfor
                        </ul>
                        <div id="ratingError" class="text-danger mt-2" style="display: none;"></div>

                        <div class="mt-5">
                            <textarea class="form-control" placeholder="{{ __('messages.share_thoughts_placeholder') }}" rows="4" id="reviewTextarea"></textarea>
                            <div id="reviewError" class="text-danger mt-2" style="display: none;"></div>
                        </div>

                        <div class="mt-5 pt-2">
                            <button type="button" class="btn btn-primary" id="submitBtn">{{ __('frontend.submit') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('after-scripts')
<script>
(function() {
    var selectedRating = 0;
    var entertainmentId = null;
    var reviewId = null;
    var firstEdit = true;
    var ratingStar = 0;
    var ratingText = '';
    var translations = {
        my_review: "{{ __('messages.my_review') }}",
        edit: "{{ __('messages.edit') }}",
        delete: "{{ __('messages.delete') }}"
    };

    function highlightStars(rating) {
        jQuery('#rattingModal .star').each(function () {
            var starValue = parseInt(jQuery(this).attr('data-value') || 0, 10);
            jQuery(this).toggleClass('selected', starValue <= rating);
        });
    }
    function resetRating() {
        selectedRating = 0;
        jQuery('#rattingModal .star').removeClass('selected');
    }

    jQuery(document).ready(function () {
        var ratingModal = jQuery('#rattingModal');
        if (!ratingModal.length) return;

        jQuery('#rattingModal').on('hidden.bs.modal', function () {
            jQuery('.modal-backdrop').remove();
            jQuery('body').css({ 'overflow': '', 'padding-right': '' });
            firstEdit = true;
        });

        ratingModal.on('show.bs.modal', function (event) {
            var button = event.relatedTarget ? jQuery(event.relatedTarget) : jQuery(this).find('[data-entertainment-id]').first();
            entertainmentId = button.attr('data-entertainment-id') || button.data('entertainment-id') || null;
            reviewId = button.attr('data-review-id') || button.data('review-id') || null;

            jQuery('#ratingError').hide().text('');
            jQuery('#reviewError').hide().text('');

            if (reviewId) {
                var rawReview = button.attr('data-review') || button.data('review') || '';
                if (rawReview === null || rawReview === 'null') rawReview = '';
                var rating = parseInt(button.attr('data-rating') || button.data('rating') || 0, 10);
                selectedRating = rating;
                jQuery('#reviewTextarea').val(rawReview);
                highlightStars(selectedRating);
                ratingStar = selectedRating;
                ratingText = rawReview;
            } else {
                resetRating();
                jQuery('#reviewTextarea').val('');
                ratingStar = 0;
                ratingText = '';
            }
        });

        jQuery(document).on('click', '#rattingModal .star', function (e) {
            e.preventDefault();
            var star = jQuery(this).closest('.star');
            if (star.length) {
                selectedRating = parseInt(star.attr('data-value') || 0, 10);
                highlightStars(selectedRating);
                jQuery('#ratingError').hide().text('');
            }
        });

        jQuery('#reviewTextarea').on('input', function () {
            if (jQuery(this).val().trim() !== '') {
                jQuery('#reviewError').hide().text('');
            }
        });

        var isSubmitting = false;

        function submitReview() {
            jQuery('#ratingError').hide().text('');
            jQuery('#reviewError').hide().text('');

            var textarea = jQuery('#reviewTextarea').val().trim();
            var submitBtn = jQuery('#submitBtn');
        let hasError = false;

        if (!entertainmentId) {
            jQuery('#ratingError').text("{{ __('messages.failed_to_submit_review') }}").show();
            hasError = true;
        }
        if (selectedRating === 0) {
            jQuery('#ratingError').text("{{ __('messages.please_select_rating') }}").show();
            hasError = true;
        }

        if (hasError || isSubmitting) return;

        isSubmitting = true;
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> {{ __('messages.submitting') }}');

        jQuery.ajax({
            url: '{{ route('save-rating') }}?is_ajax=1',
            method: 'POST',
            contentType: 'application/json',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            xhrFields: { withCredentials: true },
            data: JSON.stringify({
                entertainment_id: entertainmentId,
                rating: selectedRating,
                review: textarea,
                id: reviewId,
            }),
            success: function (data) {
                window.successSnackbar(data.message);
                const reviewdata = data.data;

                jQuery('#reviweList').removeClass('d-none');
                jQuery('#review-list').removeClass('d-none');
                jQuery('#addratingbtn1').addClass('d-none');
                jQuery('#addratingbtn').addClass('d-none');

                jQuery('#rattingModal').modal('hide');
                jQuery('.modal-backdrop').remove();

                if (reviewId) {
                    firstEdit = false;
                    ratingStar = selectedRating;
                    ratingText = textarea;

                    var reviewCard = jQuery('#your_review');
                    if (reviewCard.length) {
                        const reviewText = reviewCard.find('.review-detail p.mb-0.mt-3');
                        if (reviewText.length) {
                            reviewText.text(textarea);
                        } else {
                            const reviewDetail = reviewCard.find('.review-detail');
                            if (reviewDetail.length) {
                                reviewDetail.append(`<p class="mb-0 mt-3 fw-medium">${textarea}</p>`);
                            }
                        }

                        const starList = reviewCard.find('.review-detail ul.list-inline');
                        if (starList.length) {
                            starList.empty();
                            for (let i = 0; i < selectedRating; i++) {
                                starList.append('<li class="text-warning"><i class="ph-fill ph-star"></i></li>');
                            }
                        } else {
                            const starContainer = reviewCard.find('.review-detail .d-flex.align-items-center.gap-1').last();
                            if (starContainer.length) {
                                starContainer.empty();
                                for (let i = 0; i < selectedRating; i++) {
                                    starContainer.append('<i class="ph-fill ph-star text-warning"></i>');
                                }
                            }
                        }


                        const editButton = reviewCard.find('button[data-bs-toggle="modal"][data-bs-target="#rattingModal"]');
                        if (editButton.length) {
                            editButton.removeData('review');
                            editButton.removeData('rating');

                            editButton.attr('data-review', textarea);
                            editButton.attr('data-rating', selectedRating);

                            editButton.data('review', textarea);
                            editButton.data('rating', selectedRating);
                        }
                    }

                    jQuery('#reviewTextarea').val(textarea);
                    highlightStars(selectedRating);
                } else {
                    ratingStar = reviewdata.rating;
                    ratingText = reviewdata.review || '';
                    var newReview = jQuery('#reviewlist');

                    const reviewCard = `
                        <div id="your_review">
                            <div class="review-card">
                                <div class="mb-3 d-flex align-items-center justify-content-between">
                                    <h5 class="m-0">${translations.my_review}</h5>
                                    <div class="d-flex align-items-center gap-3">
                                        <button class="btn btn-link p-0 fw-semibold d-flex align-items-center gap-1"
                                                data-bs-toggle="modal"
                                                data-bs-target="#rattingModal"
                                                data-review-id="${reviewdata.id}"
                                                data-entertainment-id="${reviewdata.entertainment_id}"
                                                data-review="${reviewdata.review ? reviewdata.review : ''}"
                                                data-rating="${reviewdata.rating}">
                                            <i class="ph ph-pencil-line"></i>
                                        </button>
                                        <button type="button" class="btn btn-link p-0 fw-semibold d-flex align-items-center gap-1"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteratingModal"
                                                data-id="${reviewdata.id}"
                                                onclick="setDeleteId(${reviewdata.id})">
                                            <i class="ph ph-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="review-detail rounded">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                        <div class="d-flex align-items-center justify-content-center gap-3">
                                            <img src="${reviewdata.profile_image}" alt="user" class="img-fluid user-img rounded-circle">
                                            <div>
                                                <h6 class="line-count-1 font-size-18">${reviewdata.username}</h6>
                                                <p class="mb-0 font-size-14-0">${(reviewdata.created_at)}</p>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-1">
                                            ${Array.from({ length: reviewdata.rating }, (_, i) => `<i class="ph-fill ph-star text-warning"></i>`).join('')}
                                        </div>
                                    </div>
                                    <p class="mb-0 mt-3 fw-medium">${reviewdata.review ? reviewdata.review : ''}</p>
                                </div>
                            </div>
                        </div>`;
                    newReview.append(reviewCard);
                }
            },
            error: function (xhr) {
                if (typeof window.errorSnackbar === 'function') {
                    const msg = (xhr.status === 401)
                        ? (xhr.responseJSON && xhr.responseJSON.message) || "{{ __('messages.failed_to_submit_review') }}"
                        : "{{ __('messages.failed_to_submit_review') }}";
                    window.errorSnackbar(msg);
                }
            },
            complete: function () {
                isSubmitting = false;
                submitBtn.prop('disabled', false).html('{{ __('frontend.submit') }}');
            }
        });
    }

        jQuery(document).on('click', '#rattingModal #submitBtn', function (e) {
            e.preventDefault();
            e.stopPropagation();
            submitReview();
            return false;
        });

        jQuery(document).on('submit', '#rattingModal #reviewForm', function (e) {
            e.preventDefault();
            e.stopPropagation();
            submitReview();
            return false;
        });
    });
})();
</script>
@endpush

<style>
#rattingModal .star {
    cursor: pointer;
}
#rattingModal .star .icon-fill {
    display: none;
}
#rattingModal .star .icon-normal {
    display: inline;
}
#rattingModal .star.selected .icon-fill {
    display: inline;
}
#rattingModal .star.selected .icon-normal {
    display: none;
}
#rattingModal .text-danger {
    font-size: 0.9rem;
}
</style>
