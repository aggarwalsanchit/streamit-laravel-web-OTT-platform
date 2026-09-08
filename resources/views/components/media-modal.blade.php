<!-- Modal -->
<div class="modal fade modal-xl" style="max-width: 100%;" id="exampleModal" tabindex="-1"
    aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">{{ __('placeholder.lbl_image') }}</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                @include('components.filemanager-section', compact('page_type'))
            </div>
        </div>
    </div>
</div>



<style>
    .swal2-modal-custom {
        z-index: 9999 !important;
        /* Make sure it's higher than Bootstrap's modal (1050) */
    }
</style>
