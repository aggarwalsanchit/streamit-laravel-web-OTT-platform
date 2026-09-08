@once

@endonce

<div>
    <!-- Folder Navigation -->
    <div class="mb-3" id="folder-navigation" style="display: none;">
        <div class="d-flex align-items-center justify-content-between">
            <h3 class="mb-0" id="current-folder-name"></h3>
            <div class="d-flex align-items-center gap-3">
                <div class="mb-0" id="search-bar-container" style="display: none;">
                    <div class="input-group">
                        <span class="input-group-text pe-1">
                            <i class="ph ph-magnifying-glass"></i>
                        </span>
                        <input type="text" class="form-control" id="mediaSearchInput"
                            placeholder="{{ __('frontend.search_placeholder') }}" onkeyup="filterMediaContent()">
                        <button class="btn btn-link clear-search" type="button" onclick="clearSearch()">
                            <i class="ph ph-x"></i>
                        </button>
                    </div>
                </div>
                <button class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1" type="button"
                    onclick="goBackToFolders()">
                    <i class="ph ph-caret-double-left"></i> {{ __('frontend.back') }}
                </button>
            </div>

        </div>
    </div>

    <!-- Folders Grid -->
    <div class="row row-cols-lg-5 row-cols-md-4 row-cols-sm-3 row-cols-1 gy-3" id="folders-grid">
        @php
            $folders = media_library_root_folders(['avatars', 'subtitles']);
        @endphp

        @foreach ($folders as $folder)
            @if ($folder['name'] != 'avatars' && $folder['name'] != 'subtitles')
                <div class="col">
                    <div class="card h-100 folder-card text-center bg-body rounded"
                        onclick="openFolder('{{ $folder['name'] }}')" style="cursor: pointer;">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center">
                            <i class="ph ph-folder text-primary" style="font-size: 3rem;"></i>
                            <h6 class="mt-2 mb-1">{{ $folder['display_name'] }}</h6>
                            {{-- <small class="text-muted">{{ $folder['name'] }}</small> --}}
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    <!-- Folder Contents -->
    <div class="row" id="folder-contents" style="display: none;">
        <div class="col-12">


            <div class="media-scroll-container mb-3" style="max-height: 540px; overflow-y: auto; overflow-x: hidden;">
                <div class="row gy-3" id="mediaLibraryContent_folder_browser">
                    <!-- Contents will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    if (typeof baseUrl === 'undefined') {
        var baseUrl = document.querySelector('meta[name="base-url"]').getAttribute('content');
    }

    // pagination state for smooth incremental loading
    let fbCurrentFolder = '';
    let fbNextOffset = 0;
    const fbPageLimit = 60;
    let fbIsLoading = false;
    let fbAbortController;
    let fbInfiniteInitDone = false;
    let fbIoObserver = null;

    function openFolder(folderName) {
        cleanupFbInfiniteScroll();
        document.getElementById('folder-navigation').style.display = 'block';
        (function () {
            var base = folderName;
            var transKey = 'folder_' + base.toLowerCase();
            var display = (window.localMessagesUpdate?.messages?.[transKey] && window.localMessagesUpdate.messages[transKey] !== 'messages.' + transKey)
                ? window.localMessagesUpdate.messages[transKey]
                : ((typeof base === 'string' && base.length > 0) ? (base.charAt(0).toUpperCase() + base.slice(1)) : base);
            document.getElementById('current-folder-name').textContent = display;
        })();
        document.getElementById('folders-grid').style.display = 'none';
        document.getElementById('folder-contents').style.display = 'block';
        var saveBtn = document.getElementById('mediaSubmitButton');
        if (saveBtn) {
            saveBtn.classList.add('d-none');
        }
        fbCurrentFolder = folderName;
        fbNextOffset = 0;
        fbInfiniteInitDone = false;
        loadFolderContents(folderName);
    }

    function goBackToFolders() {
        cleanupFbInfiniteScroll();
        const current = fbCurrentFolder || '';
        const hasParent = current && current.includes('/');

        if (hasParent) {
            // navigate up one level
            const parent = current.substring(0, current.lastIndexOf('/'));
            fbCurrentFolder = parent;
            fbNextOffset = 0;
            fbInfiniteInitDone = false;

            document.getElementById('folder-navigation').style.display = 'block';
            (function () {
                var base = parent.split('/').pop();
                var transKey = 'folder_' + base.toLowerCase();
                var display = (window.localMessagesUpdate?.messages?.[transKey] && window.localMessagesUpdate.messages[transKey] !== 'messages.' + transKey)
                    ? window.localMessagesUpdate.messages[transKey]
                    : ((typeof base === 'string' && base.length > 0) ? (base.charAt(0).toUpperCase() + base.slice(1)) : base);
                document.getElementById('current-folder-name').textContent = display || '';
            })();
            document.getElementById('folders-grid').style.display = 'none';
            document.getElementById('folder-contents').style.display = 'block';
            document.getElementById('mediaLibraryContent_folder_browser').innerHTML = '';
            var saveBtn = document.getElementById('mediaSubmitButton');
            if (saveBtn) {
                saveBtn.classList.add('d-none');
            }
            loadFolderContents(parent);
        } else {
            // root
            document.getElementById('folder-navigation').style.display = 'none';
            document.getElementById('folder-contents').style.display = 'none';
            document.getElementById('folders-grid').style.display = 'flex';
            document.getElementById('mediaLibraryContent_folder_browser').innerHTML = '';
            var saveBtn = document.getElementById('mediaSubmitButton');
            if (saveBtn) {
                saveBtn.classList.add('d-none');
            }
            fbCurrentFolder = '';
            fbNextOffset = 0;
            fbInfiniteInitDone = false;
        }
    }

    function loadFolderContents(folderName) {
        const container = document.getElementById('mediaLibraryContent_folder_browser');
        container.style.transition = 'opacity 0.3s ease';
        container.style.opacity = '0.7';
        container.innerHTML =
            '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="sr-only">{{ __('frontend.loading') }}</span></div><div class="mt-2">{{ __('frontend.loading_folder_contents') }}</div></div>';

        if (fbAbortController) {
            try {
                fbAbortController.abort();
            } catch (e) { }
        }
        fbAbortController = new AbortController();
        fbIsLoading = true;

        fetch(`${baseUrl}/app/media-library/get-folder-contents?folder=${encodeURIComponent(folderName)}&limit=${fbPageLimit}&offset=0`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            signal: fbAbortController.signal
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    fbNextOffset = (data.pagination && data.pagination.next_offset) ? data.pagination.next_offset :
                        null;
                    displayFolderContents(data.contents, false);
                } else {
                    container.innerHTML =
                        '<div class="text-center text-danger">{{ __('frontend.error_loading_folder_contents') }}</div>';
                }
                container.style.opacity = '1';
                fbIsLoading = false;
            })
            .catch(error => {
                if (error && error.name === 'AbortError') {
                    return;
                }
                container.innerHTML =
                    '<div class="text-center text-danger">{{ __('frontend.error_loading_folder_contents') }}</div>';
                container.style.opacity = '1';
                fbIsLoading = false;
            });
    }

    function loadMoreFolderContents() {
        if (fbIsLoading) return;
        if (fbNextOffset === null || fbNextOffset === undefined) return;
        if (!fbCurrentFolder) return;

        // Show smooth infinite scroll loader
        showFbInfiniteScrollLoader(true);
        fbIsLoading = true;

        fetch(`${baseUrl}/app/media-library/get-folder-contents?folder=${encodeURIComponent(fbCurrentFolder)}&limit=${fbPageLimit}&offset=${fbNextOffset}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
            .then(r => r.json())
            .then(data => {
                if (data && data.success) {
                    fbNextOffset = (data.pagination && data.pagination.next_offset) ? data.pagination.next_offset :
                        null;
                    displayFolderContents((data.contents || []), true);
                }
                showFbInfiniteScrollLoader(false);
                fbIsLoading = false;
            })
            .catch(e => {
                showFbInfiniteScrollLoader(false);
                fbIsLoading = false;
            });
    }

    // Store original contents for search filtering
    let originalContents = [];

    // Smooth infinite scroll loader
    function showFbInfiniteScrollLoader(show) {
        let loader = document.getElementById('fbInfiniteScrollLoader');
        if (!loader) {
            loader = document.createElement('div');
            loader.id = 'fbInfiniteScrollLoader';
            loader.className = 'text-center py-3';
            loader.innerHTML =
                '<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="sr-only">{{ __('frontend.loading_more') }}</span></div><div class="mt-1 small text-muted">{{ __('frontend.loading_more_content') }}</div>';
            loader.style.display = 'none';
            loader.style.transition = 'opacity 0.3s ease';
            document.getElementById('mediaLibraryContent_folder_browser').appendChild(loader);
        }

        if (show) {
            loader.style.display = 'block';
            loader.style.opacity = '0';
            setTimeout(() => {
                loader.style.opacity = '1';
            }, 10);
        } else {
            loader.style.opacity = '0';
            setTimeout(() => {
                loader.style.display = 'none';
            }, 300);
        }
    }

    function displayFolderContents(contents, append) {
        // Store original contents for search filtering
        if (append) {
            originalContents = originalContents.concat(contents);
        } else {
            originalContents = contents;
        }

        let html = '';
        var hasMediaFiles = false; // Track if folder has images or videos

        if (!append && contents.length === 0) {
            html = '<div class="text-center text-muted">{{ __('frontend.no_files_found_in_folder') }}</div>';
        } else {
            var hasSelectable = false;
            contents.forEach(item => {
                const isDir = item.is_dir;
                const isVideo = item.is_video;
                const isImage = item.is_image;

                if (isDir) {
                    const folderName = item.name || '';
                    const transKey = 'folder_' + folderName.toLowerCase();
                    const displayName = (window.localMessagesUpdate?.messages?.[transKey] && window.localMessagesUpdate.messages[transKey] !== 'messages.' + transKey)
                        ? window.localMessagesUpdate.messages[transKey]
                        : (folderName.charAt(0).toUpperCase() + folderName.slice(1));
                    html += `
                        <div class="col-md-2 col-sm-1">
                            <div class="card h-100 text-center bg-body rounded" onclick="openSubFolder('${item.path}')" style="cursor: pointer;">
                                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                                    <i class="ph ph-folder text-warning" style="font-size: 2rem;"></i>
                                    <h6 class="mt-2 mb-1 text-truncate" title="${displayName}">${displayName}</h6>

                                    <div class="mt-2">
                                        <small class="text-muted">${new Date(item.modified * 1000).toLocaleDateString()}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                } else if (isVideo) {
                    hasSelectable = true;
                    hasMediaFiles = true; // Mark that folder has media files
                    const videoUrl = item.media_url;
                    html += `
                        <div class="col-md-2 col-sm-1">
                            <div class="iq-media-images position-relative">
                                <video class="img-fluid object-fit-cover media-thumb-10" preload="metadata" controlsList="nodownload" controls>
                                    <source src="${videoUrl}" type="video/mp4">
                                </video>
                                <button type="button" class="btn btn-danger position-absolute top-0 end-0 m-2 p-0 iq-button-delete avatar-30" onclick="deleteImage('${videoUrl}', 'video', '${item.name}', getFolderFromUrl('${videoUrl}'))">
                                    <i class="ph ph-trash"></i>
                                </button>
                                <p class="media-title pt-2 mb-0" data-bs-toggle="tooltip" data-bs-title="${item.name}">${item.name}</p>
                            </div>
                        </div>
                    `;
                } else if (isImage) {
                    hasSelectable = true;
                    hasMediaFiles = true; // Mark that folder has media files
                    const imageUrl = item.media_url;
                    html += `
                        <div class="col-md-2 col-sm-1">
                            <div class="iq-media-images position-relative">
                                <img class="img-fluid object-fit-cover media-thumb-10" src="${imageUrl}"  loading="lazy" decoding="async" style="opacity:0;transition:opacity .2s" onload="this.style.opacity=1">
                                <button type="button" class="btn btn-danger position-absolute top-0 end-0 m-2 p-0 iq-button-delete avatar-30" onclick="deleteImage('${imageUrl}', 'image', '${item.name}', getFolderFromUrl('${imageUrl}'))">
                                    <i class="ph ph-trash"></i>
                                </button>
                                <p class="media-title pt-2 mb-0" data-bs-toggle="tooltip" data-bs-title="${item.name}">${item.name}</p>
                            </div>
                        </div>
                    `;
                } else {
                    const iconClass = getFileIcon(item.name);
                    const iconColor = getFileColor(item.name);
                    const size = formatFileSize(item.size);
                    const fileUrl = `${baseUrl}/storage/app/public/${item.path}`;

                    html += `
                        <div class="col-md-2 col-sm-1">
                            <div class="card h-100 position-relative">
                                <div class="card-body text-center bg-body">
                                    <i class="ph ${iconClass} ${iconColor}" style="font-size: 2rem;"></i>
                                    <h6 class="mt-2 mb-1 text-truncate" title="${item.name ? (item.name.charAt(0).toUpperCase() + item.name.slice(1)) : item.name}">${item.name ? (item.name.charAt(0).toUpperCase() + item.name.slice(1)) : item.name}</h6>

                                    <div class="mt-2">
                                        <small class="text-muted">${new Date(item.modified * 1000).toLocaleDateString()}</small>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-danger position-absolute top-0 end-0 m-2 p-0 iq-button-delete avatar-30" onclick="deleteImage('${fileUrl}', 'file', '${item.name}', getFolderFromUrl('${fileUrl}'))">
                                    <i class="ph ph-trash"></i>
                                </button>
                            </div>
                        </div>
                    `;
                }
            });
            var saveBtnToggle = document.getElementById('mediaSubmitButton');
            if (saveBtnToggle) {
                if (hasSelectable) {
                    saveBtnToggle.classList.remove('d-none');
                } else {
                    saveBtnToggle.classList.add('d-none');
                }
            }
        }

        // Show or hide search bar based on whether folder has media files
        const searchBarContainer = document.getElementById('search-bar-container');
        if (hasMediaFiles) {
            searchBarContainer.style.display = 'block';
        } else {
            searchBarContainer.style.display = 'none';
        }

        const container = document.getElementById('mediaLibraryContent_folder_browser');
        if (append) {
            // For infinite scroll, add content smoothly
            container.insertAdjacentHTML('beforeend', html);
            // Add fade-in effect to new content
            const newItems = container.querySelectorAll('.col-md-2:not(.fade-in)');
            newItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transition = 'opacity 0.3s ease';
                setTimeout(() => {
                    item.style.opacity = '1';
                    item.classList.add('fade-in');
                }, index * 50); // Stagger the animation
            });
        } else {
            // For initial load, use smooth transition
            container.style.opacity = '0';
            container.innerHTML = html;
            setTimeout(() => {
                container.style.opacity = '1';
            }, 100);
        }

        // Initialize infinite scroll only after first render
        if (!fbInfiniteInitDone && fbNextOffset !== null && contents.length > 0) {
            initFbInfiniteScroll();
        }

        if (fbNextOffset === null && fbIoObserver) {
            fbIoObserver.disconnect();
            fbIoObserver = null;
        }
    }

    // Cached file type mappings for better performance
    const fbFileIconMap = {
        'jpg': 'ph-image',
        'jpeg': 'ph-image',
        'png': 'ph-image',
        'gif': 'ph-image',
        'webp': 'ph-image',
        'svg': 'ph-image',
        'mp4': 'ph-video',
        'avi': 'ph-video',
        'mov': 'ph-video',
        'webm': 'ph-video',
        'mkv': 'ph-video',
        'pdf': 'ph-file-pdf',
        'doc': 'ph-file-doc',
        'docx': 'ph-file-doc',
        'zip': 'ph-archive',
        'rar': 'ph-archive',
        '7z': 'ph-archive'
    };

    const fbFileColorMap = {
        'jpg': 'text-success',
        'jpeg': 'text-success',
        'png': 'text-success',
        'gif': 'text-success',
        'webp': 'text-success',
        'svg': 'text-success',
        'mp4': 'text-primary',
        'avi': 'text-primary',
        'mov': 'text-primary',
        'webm': 'text-primary',
        'mkv': 'text-primary',
        'pdf': 'text-danger',
        'doc': 'text-info',
        'docx': 'text-info',
        'zip': 'text-warning',
        'rar': 'text-warning',
        '7z': 'text-warning'
    };

    function getFileIcon(filename) {
        const extension = filename.split('.').pop().toLowerCase();
        return fbFileIconMap[extension] || 'ph-file';
    }

    function getFileColor(filename) {
        const extension = filename.split('.').pop().toLowerCase();
        return fbFileColorMap[extension] || 'text-secondary';
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
    }

    function openSubFolder(folderPath) {
        cleanupFbInfiniteScroll();
        fbCurrentFolder = folderPath;
        fbNextOffset = 0;
        fbInfiniteInitDone = false;
        loadFolderContents(folderPath);
        (function () {
            var base = folderPath.split('/').pop();
            var transKey = 'folder_' + base.toLowerCase();
            var display = (window.localMessagesUpdate?.messages?.[transKey] && window.localMessagesUpdate.messages[transKey] !== 'messages.' + transKey)
                ? window.localMessagesUpdate.messages[transKey]
                : ((typeof base === 'string' && base.length > 0) ? (base.charAt(0).toUpperCase() + base.slice(1)) : base);
            document.getElementById('current-folder-name').textContent = display;
        })();
    }

    // Hide save button on initial load; defer infinite scroll init until contents render
    document.addEventListener('DOMContentLoaded', function () {
        var saveBtn = document.getElementById('mediaSubmitButton');
        if (saveBtn) {
            saveBtn.classList.add('d-none');
        }
    });

    function initFbInfiniteScroll() {
        const scroller = document.querySelector('.media-scroll-container');
        if (!scroller || fbInfiniteInitDone) return;
        if (fbNextOffset === null) return; // nothing to load

        let sentinel = scroller.querySelector('.fb-bottom-sentinel');
        if (!sentinel) {
            sentinel = document.createElement('div');
            sentinel.className = 'fb-bottom-sentinel';
            sentinel.style.height = '1px';
            scroller.appendChild(sentinel);
        }

        fbIoObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    loadMoreFolderContents();
                }
            });
        }, {
            root: scroller,
            rootMargin: '120px',
            threshold: 0
        });
        fbIoObserver.observe(sentinel);
        fbInfiniteInitDone = true;
    }

    function cleanupFbInfiniteScroll() {
        if (fbIoObserver) {
            try {
                fbIoObserver.disconnect();
            } catch (e) { }
            fbIoObserver = null;
        }
        fbInfiniteInitDone = false;
    }

    function getFolderFromUrl(url) {
        try {
            const urlParts = url.split('/storage/');
            if (urlParts.length > 1) {
                const pathAfterStorage = urlParts[1];
                const pathSegments = pathAfterStorage.split('/');
                return pathSegments[0];
            }
            return 'default';
        } catch (error) {
            return 'default';
        }
    }

    // Debounced search functionality for better performance
    let fbSearchTimeout;
    // Track last applied search to avoid redundant renders
    let fbLastSearchTerm = '';

    function filterMediaContent() {
        clearTimeout(fbSearchTimeout);
        fbSearchTimeout = setTimeout(() => {
            const searchTerm = document.getElementById('mediaSearchInput').value.toLowerCase().trim();

            if (searchTerm === '') {
                // Show all content if search is empty; but avoid duplicate render
                if (fbLastSearchTerm !== '') {
                    displayFolderContents(originalContents);
                    fbLastSearchTerm = '';
                }
                return;
            }

            // Filter contents to only show images and videos that match search term within current folder
            const filteredContents = originalContents.filter(item => {
                const isImage = item.is_image;
                const isVideo = item.is_video;
                const fileName = item.name.toLowerCase();

                // Only filter images and videos, exclude folders
                if (isImage || isVideo) {
                    return fileName.includes(searchTerm);
                }
                return false; // Hide folders from search results
            });

            // Display filtered results
            fbLastSearchTerm = searchTerm;
            displayFilteredContents(filteredContents);
        }, 300); // 300ms debounce
    }


    function displayFilteredContents(contents) {
        let html = '';

        if (contents.length === 0) {
            html = '<div class="text-center text-muted">{{ __('frontend.no_images_videos_found') }}</div>';
        } else {
            var hasSelectable = false;
            contents.forEach(item => {
                const isVideo = item.is_video;
                const isImage = item.is_image;

                if (isVideo) {
                    hasSelectable = true;
                    const videoUrl = item.media_url;
                    html += `
                        <div class="col-md-2 col-sm-1">
                            <div class=" position-relative">
                                <video class="img-fluid object-fit-cover media-thumb-10" preload="metadata" controlsList="nodownload" controls>
                                    <source src="${videoUrl}" type="video/mp4">
                                </video>
                                <button type="button" class="btn btn-danger position-absolute top-0 end-0 m-2 p-0 iq-button-delete avatar-30" onclick="deleteImage('${videoUrl}', 'video', '${item.name}', getFolderFromUrl('${videoUrl}'))">
                                    <i class="ph ph-trash"></i>
                                </button>
                                <p class="media-title pt-2 mb-0" data-bs-toggle="tooltip" data-bs-title="${item.name}">${item.name}</p>
                            </div>
                        </div>
                    `;
                } else if (isImage) {
                    hasSelectable = true;
                    const imageUrl = item.media_url;
                    html += `
                        <div class="col-md-2 col-sm-1">
                            <div class=" position-relative">
                                <img class="img-fluid object-fit-cover media-thumb-10" src="${imageUrl}" loading="lazy" decoding="async" style="opacity:0;transition:opacity .2s" onload="this.style.opacity=1">
                                <button type="button" class="btn btn-danger position-absolute top-0 end-0 m-2 p-0 iq-button-delete avatar-30" onclick="deleteImage('${imageUrl}', 'image', '${item.name}', getFolderFromUrl('${imageUrl}'))">
                                    <i class="ph ph-trash"></i>
                                </button>
                                <p class="media-title pt-2 mb-0" data-bs-toggle="tooltip" data-bs-title="${item.name}">${item.name}</p>
                            </div>
                        </div>
                    `;
                }
            });

            var saveBtnToggle = document.getElementById('mediaSubmitButton');
            if (saveBtnToggle) {
                if (hasSelectable) {
                    saveBtnToggle.classList.remove('d-none');
                } else {
                    saveBtnToggle.classList.add('d-none');
                }
            }
        }

        document.getElementById('mediaLibraryContent_folder_browser').innerHTML = html;
    }

    function clearSearch() {
        document.getElementById('mediaSearchInput').value = '';
        // Show all contents of the current folder (including folders and all files)
        displayFolderContents(originalContents);
        fbLastSearchTerm = '';
        // Hide clear icon when input is empty after clearing
        var clearBtn = document.querySelector('#search-bar-container .clear-search');
        if (clearBtn) {
            clearBtn.classList.add('d-none');
        }
    }
</script>

<script>
    // Toggle clear search icon visibility when user types
    (function () {
        var searchInput = document.getElementById('mediaSearchInput');
        var clearBtn = document.querySelector('#search-bar-container .clear-search');
        if (!searchInput || !clearBtn) return;

        // Set initial visibility
        clearBtn.classList.toggle('d-none', !(searchInput.value || '').trim());

        searchInput.addEventListener('input', function () {
            clearBtn.classList.toggle('d-none', !(this.value || '').trim());
        });
    })();
</script>