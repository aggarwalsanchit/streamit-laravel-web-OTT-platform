<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">{{ __('movie.import') }} <span id="importTypeDisplay">{{ __('messages.loading') }}...</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="importForm" method="POST" enctype="multipart/form-data"
                action="{{ route('backend.import.import') }}">
                @csrf
                <input type="hidden" name="type" id="importType" value="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="importFile" class="form-label">{{ __('movie.choose_file') }}</label>
                        <input class="form-control" type="file" id="importFile" name="import_file" 
                            accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
                            required>
                        <div class="form-text">{{ __('movie.file_format_requirements') }}</div>
                    </div>
                    <div class="mb-3">
                        <a href="#" class="btn btn-outline-primary btn-sm" id="downloadSampleBtn">
                            <i class="ph ph-download align-middle me-1"></i>
                            {{ __('movie.download_sample_file') }}
                        </a>
                    </div>
                    <div class="mb-3">
                        <h6>{{ __('movie.required_columns') }}:</h6>
                        <div id="requiredColumns" class="text-muted small">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            {{ __('messages.loading') }}...
                        </div>
                    </div>
                    <div id="importMessages"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelBtn"
                        data-bs-dismiss="modal">{{ __('movie.cancel') }}</button>
                    <button type="submit" class="btn btn-primary" id="importBtn">
                        <span class="btn-text">{{ __('movie.import') }}</span>
                        <span class="btn-loading d-none">
                            <span class="spinner-border spinner-border-sm me-2" role="status"
                                aria-hidden="true"></span>
                            {{ __('movie.uploading') }}
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const importModal = new bootstrap.Modal(document.getElementById('importModal'));
    const importType = document.getElementById('importType');
    const importTypeDisplay = document.getElementById('importTypeDisplay');
    const downloadSampleBtn = document.getElementById('downloadSampleBtn');
    const requiredColumnsDiv = document.getElementById('requiredColumns');
    
    const typeDisplayNames = {
        'movie': '{{ __("movie.import_movies") }}',
        'tvshow': '{{ __("movie.import_tvshows") }}', 
        'season': '{{ __("movie.import_seasons") }}',
        'episode': '{{ __("movie.import_episodes") }}',
        'video': '{{ __("movie.import_videos") }}',
        'genre': '{{ __("movie.import_genres") }}',
        'castcrew': '{{ __("movie.import_castcrew") }}',
        'user': '{{ __("movie.import_users") }}',
        'tv_category': '{{ __("movie.import_tv_category") }}',
        'tv_channel': '{{ __("movie.import_tv_channel") }}',
        'director': '{{ __("movie.import_director") }}',    
        'actor': '{{ __("movie.import_actors") }}',     
       
    };
    
    // Handle import button clicks from any page
    document.addEventListener('click', function(e) {
        if (e.target.matches('[data-bs-target="#importModal"]') || e.target.closest('[data-bs-target="#importModal"]')) {
            const button = e.target.matches('[data-bs-target="#importModal"]') ? e.target : e.target.closest('[data-bs-target="#importModal"]');
            const type = button.getAttribute('data-type') || 'movie';
            const castcrewType = button.getAttribute('data-castcrew-type') || 'actor';
            
            console.log('Import button clicked, setting type:', type); // Debug log
            
            importType.value = type;
            if (type === 'castcrew') {
                importTypeDisplay.textContent = typeDisplayNames[castcrewType] || 'Cast & Crew';
            } else {
                importTypeDisplay.textContent = typeDisplayNames[type] || type.charAt(0).toUpperCase() + type.slice(1);
            }
            
            importType.setAttribute('data-castcrew-type', castcrewType);
            
            loadRequiredColumns(type);
        }
    });
    $('#importModal').on('show.bs.modal', function() {
        console.log('Import modal is being shown');
        console.log('Current type:', importType.value);
        console.log('Current display:', importTypeDisplay.textContent);
        
        $('#importMessages').empty();
        
        $('#importForm')[0].reset();
        const importBtn = $('#importBtn');
        const btnText = importBtn.find('.btn-text');
        const btnLoading = importBtn.find('.btn-loading');
        
        importBtn.prop('disabled', false);
        btnText.removeClass('d-none');
        btnLoading.addClass('d-none');
    });
    
    // Handle download sample button
    downloadSampleBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const type = importType.value || 'movie';
        const castcrewType = importType.getAttribute('data-castcrew-type') || 'actor';
        
        let url = `{{ route('backend.import.download_sample') }}?type=${type}`;
        
        // Add castcrew_type parameter for castcrew imports
        if (type === 'castcrew') {
            url += `&castcrew_type=${castcrewType}`;
        }
        
        window.open(url, '_blank');
    });
    
    // Handle cancel button click - clear messages and reset form
    document.getElementById('cancelBtn').addEventListener('click', function() {
        // Clear all import messages
        $('#importMessages').empty();
        
        // Reset form
        $('#importForm')[0].reset();
        
        // Reset any loading states
        const importBtn = $('#importBtn');
        const btnText = importBtn.find('.btn-text');
        const btnLoading = importBtn.find('.btn-loading');
        
        importBtn.prop('disabled', false);
        btnText.removeClass('d-none');
        btnLoading.addClass('d-none');
    });
    
    // Load required columns
    let isLoadingColumns = false;
    function loadRequiredColumns(type) {
        // Prevent duplicate calls
        if (isLoadingColumns) {
            return;
        }
        
        isLoadingColumns = true;
        requiredColumnsDiv.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>{{ __("messages.loading") }}...';
        
        fetch(`{{ route('backend.import.required_columns') }}?type=${type}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    requiredColumnsDiv.innerHTML = data.data.columns.join(', ');
                } else {
                    requiredColumnsDiv.innerHTML = '<span class="text-danger">Error loading columns</span>';
                }
            })
            .catch(error => {
                requiredColumnsDiv.innerHTML = '<span class="text-danger">Error loading columns</span>';
            })
            .finally(() => {
                isLoadingColumns = false;
            });
    }
    
    // Handle import form submission
    $('#importForm').on('submit', function(e) {
        e.preventDefault();
        
        const fileInput = $('#importFile')[0];
        const file = fileInput.files[0];
        const importType = $('#importType').val();
        
        console.log('Form submission - import type:', importType); // Debug log
        
        // Ensure type is set - fallback to 'user' if empty
        if (!importType || importType === '') {
            console.log('Type is empty, setting fallback to user');
            $('#importType').val('user');
        }
        
        // Basic client-side validation
        if (!file) {
            $('#importMessages').html(`
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="ph ph-warning-circle me-2"></i>
                    Please select a file to import.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `);
            return;
        }
        
        // Check file type
        const allowedTypes = ['text/csv', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'];
        const allowedExtensions = ['.csv', '.xlsx', '.xls'];
        const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
        
        if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(fileExtension)) {
            $('#importMessages').html(`
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="ph ph-warning-circle me-2"></i>
                    Please select a valid CSV or Excel file (.csv, .xlsx, .xls).
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `);
            return;
        }
        
        // Check file size (10MB limit)
        if (file.size > 10 * 1024 * 1024) {
            $('#importMessages').html(`
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="ph ph-warning-circle me-2"></i>
                    File size must not exceed 10MB.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `);
            return;
        }
        
        const formData = new FormData(this);
        const importBtn = $('#importBtn');
        const btnText = $('.btn-text');
        const btnLoading = $('.btn-loading');
        
        // Show loading state
        importBtn.prop('disabled', true);
        btnText.addClass('d-none');
        btnLoading.removeClass('d-none');
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                // Hide loading state
                importBtn.prop('disabled', false);
                btnText.removeClass('d-none');
                btnLoading.addClass('d-none');
                
                if (response.success) {
                    console.log("message=======>",response.data.message);
                    // Show success message
                    $('#importMessages').html(`
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="ph ph-check-circle me-2"></i>
                            ${response.data.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `);
                    
                    // Close modal after 2 seconds
                    setTimeout(function() {
                        importModal.hide();
                        // Reset form
                        $('#importForm')[0].reset();
                        $('#importMessages').empty();
                    }, 2000);
                    
                    // Reload datatable if exists
                    if (window.renderedDataTable) {
                        window.renderedDataTable.ajax.reload();
                    }
                } else {
                    console.log("message=======>",response.data.message);
                    // Show error message
                    $('#importMessages').html(`
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="ph ph-warning-circle me-2"></i>
                            ${response.data.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `);
                }
            },
            error: function(xhr) {
                // Hide loading state
                importBtn.prop('disabled', false);
                btnText.removeClass('d-none');
                btnLoading.addClass('d-none');
                
                let errorMessage = 'An error occurred while importing the file.';
                let errorDetails = '';
                
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMessage = xhr.responseJSON.data.message;
                    
                    // Show detailed errors if available
                    if (xhr.responseJSON.data.errors && xhr.responseJSON.data.errors.length > 0) {
                        errorDetails = '<div class="mt-3"><strong>Validation Errors:</strong><ul class="mb-0 mt-2">';
                        xhr.responseJSON.data.errors.forEach(function(error) {
                            errorDetails += '<li>' + error + '</li>';
                        });
                        errorDetails += '</ul></div>';
                    }
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                // Show error message with details
                $('#importMessages').html(`
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="ph ph-warning-circle me-2"></i>
                        ${errorMessage}
                        ${errorDetails}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `);
            }
        });
    });
});
</script>
