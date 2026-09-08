    @php
        $storagePath = storage_path('app/public');
        $folders = [];
        $selectedFolder = request('folder', '');
        $folderContents = [];

        if (function_exists('media_library_root_folders')) {
            foreach (media_library_root_folders() as $folder) {
                $folders[$folder['name']] = $folder['display_name'];
            }
        } elseif (is_dir($storagePath)) {
            foreach (scandir($storagePath) ?: [] as $item) {
                if ($item === '.' || $item === '..' || ! is_dir($storagePath . '/' . $item)) {
                    continue;
                }
                $name = function_exists('normalize_media_library_folder')
                    ? normalize_media_library_folder($item)
                    : $item;
                if (isset($folders[$name])) {
                    continue;
                }
                $labelKey = 'folder_' . strtolower($name);
                $folders[$name] = \Lang::has('messages.' . $labelKey)
                    ? __('messages.' . $labelKey)
                    : ucfirst(str_replace(['-', '_'], ' ', $name));
            }
        }

        // Get contents of selected folder
        if ($selectedFolder && is_dir($storagePath . '/' . $selectedFolder)) {
            $contents = scandir($storagePath . '/' . $selectedFolder);
            foreach ($contents as $item) {
                if ($item !== '.' && $item !== '..') {
                    $itemPath = $storagePath . '/' . $selectedFolder . '/' . $item;
                    $folderContents[] = [
                        'name' => $item,
                        'path' => $itemPath,
                        'is_dir' => is_dir($itemPath),
                        'size' => is_file($itemPath) ? filesize($itemPath) : 0,
                        'modified' => filemtime($itemPath),
                    ];
                }
            }
        }
    @endphp

    @if (count($folders) > 0)
        @foreach ($folders as $folder => $folderName)
            <div id="folder-section" class="mb-3">
                <div class="iq-folder-item position-relative border rounded p-3">
                    <div class="d-flex align-items-center">
                        <div class="folder-icon me-3">
                            <i class="ph ph-folder text-primary" style="font-size: 2rem;"></i>
                        </div>
                        <div class="folder-info flex-grow-1">
                            <h6 class="mb-1 folder-name">{{ $folderName }}</h6>
                            <small class="text-muted">storage/app/{{ $folder }}/</small>
                        </div>
                        <div class="folder-actions">
                            <button class="btn btn-sm btn-outline-primary me-2"
                                onclick="openFolder('{{ $folder }}')">
                                <i class="ph ph-eye"></i> {{ __('frontend.view') }}
                            </button>
                            <button class="btn btn-sm btn-outline-info" onclick="browseFolder('{{ $folder }}')">
                                <i class="ph ph-folder-open"></i> {{ __('frontend.browse') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        <script>
            var baseUrl = document.querySelector('meta[name="base-url"]').getAttribute('content');

            function openFolder(folderName) {
                // Open folder in new tab
                window.open(`${baseUrl}/storage/app/${folderName}`, '_blank');
            }

            function browseFolder(folderName) {
                // Navigate to folder contents
                window.location.href = `${baseUrl}/app/filemanager/browse?folder=storage/app/public/${folderName}`;
            }

            function deleteImage(url) {
                Swal.fire({
                        title: "{{ __('frontend.delete_confirm_title', ['type' => __('frontend.media')]) }}",
                        icon: "warning",
                        showCancelButton: true,
                        showCloseButton: true,
                        closeButtonAriaLabel: "{{ __('frontend.close') }}",
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "{{ __('frontend.delete_confirm_ok') }}",
                        showCloseButton: true,
                        closeButtonAriaLabel: "{{ __('frontend.close') }}",
                        reverseButtons: true,
                    })
                    .then((result) => {
                        if (result.isConfirmed) {
                            fetch(`${baseUrl}/app/media-library/destroy`, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify({ url: url })
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        const mediaContainer = document.querySelector(
                                            `img[src="${url}"], video source[src="${url}"]`);
                                        if (mediaContainer) {
                                            mediaContainer.closest('#media-images').remove();
                                        }
                                        Swal.fire({
                                            title: "{{ __('frontend.deleted_title') }}",
                                            text: "{{ __('frontend.your_media_deleted') }}",
                                            icon: 'success',
                                            showConfirmButton: false,
                                            timer: 3000,
                                            timerProgressBar: true
                                        });
                                    } else {
                                        Swal.fire(
                                            "{{ __('frontend.delete_error_title') }}",
                                            "{{ __('frontend.there_was_problem_deleting_media') }}",
                                            'error'
                                        );
                                    }
                                });
                        }
                    });
            }
        </script>
    @endif
