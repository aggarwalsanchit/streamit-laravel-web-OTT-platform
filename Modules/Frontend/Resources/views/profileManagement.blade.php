@extends('frontend::layouts.master')

@section('title')
    {{ __('frontend.profiles') }}
@endsection

@section('content')
    @php
        $parentCount = collect($userProfile->toArray(request()))
            ->where('is_child_profile', 0)
            ->count();
    @endphp
    <div class="page-title">
        <h4 class="m-0 text-center">{{ __('frontend.profiles') }}</h4>
    </div>
    <div class="section-spacing-bottom">
        <div class="container">
            <div class="edit-profile-content">
                <div class="row row-cols-xl-4 row-cols-md-3 row-cols-sm-2 row-cols-1 gy-5" id="profileList">
                    @foreach ($userProfile->toArray(request()) as $profile)
                        <?php
                        $class = $pinModel = '';
                        $profile['id'] == getCurrentProfileSession('id') && ($class = 'border border-primary');
                        ?>
                        @if ($profile['is_active'] == 1)
                            <div class="col">
                                <div class="card profil-card {{ $class }}">
                                    <div class="card-body rounded text-center position-relative">
                                        @if ($profile['is_child_profile'] == 1 || $parentCount > 1)
                                            <button type="button"
                                                class="btn btn-primary btn-sm removeProfileBtn position-absolute top-0 end-0 m-0"
                                                data-profile-id="{{ $profile['id'] }}"
                                                data-profile-name="{{ e($profile['name']) }}"
                                                onclick="event.stopPropagation();"
                                                aria-label="{{ __('messages.delete') }}">
                                                <i class="ph ph-trash"></i>
                                            </button>
                                        @endif
                                        <div class="profile-card-image">
                                            <div class="profile-kids-bagde">
                                                <img id="profile_image_{{ $profile['id'] }}"
                                                    src="{{ !empty($profile['avatar']) ? $profile['avatar'] : asset('path/to/default/image.png') }}"
                                                    alt="profile-image">
                                                 @if ($profile['is_child_profile'] == 1)
                                                       <span class="kids-badge">{{ __('messages.kids') }}</span>
                                                 @endif
                                            </div>
                                        </div>
                                        <h5 class="mt-3 mb-4 font-size-18" id="profile_name_{{ $profile['id'] }}"
                                            style="overflow: hidden; display: -webkit-box;-webkit-line-clamp: 2; line-clamp: 2;-webkit-box-orient: vertical">
                                            {{ $profile['name'] }}
                                        </h5>
                                        <button class="btn p-0 h6 mb-0" data-bs-toggle="modal"
                                            data-bs-target="#selectProfileModal" data-type="update">
                                            <span class="d-flex align-items-center gap-2"
                                                onclick="editProfile({{ $profile['id'] }})">
                                                <span><i class="ph ph-pencil-simple-line"></i></span>
                                                <span>{{ __('frontend.edit') }}</span>
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="col">
                                <div class="card profil-card {{ $class }}">
                                    <div class="card-body rounded text-center position-relative">
                                        @if ($profile['is_child_profile'] == 1 || $parentCount > 1)
                                            <button type="button"
                                                class="btn btn-primary btn-sm removeProfileBtn position-absolute top-0 end-0 m-0"
                                                data-profile-id="{{ $profile['id'] }}"
                                                data-profile-name="{{ e($profile['name']) }}"
                                                onclick="event.stopPropagation();"
                                                aria-label="{{ __('messages.delete') }}">
                                                <i class="ph ph-trash"></i>
                                            </button>
                                        @endif
                                        <div class="profile-card-image" {{-- onclick="SelectProfile11({{ $profile['id'] }})" --}}>
                                            <div class="profile-kids-bagde">
                                                <img id="profile_image_{{ $profile['id'] }}"
                                                    src="{{ !empty($profile['avatar']) ? $profile['avatar'] : asset('path/to/default/image.png') }}"
                                                    alt="profile-image">
                                                @if ($profile['is_child_profile'] == 1)
                                                    <span class="kids-badge">{{ $profile['is_child_profile'] == 1 ? __('messages.kids') : '' }}</span>
                                                @endif
                                                </div>

                                        </div>
                                        <h5 class="mt-3 mb-4 font-size-18" id="profile_name_{{ $profile['id'] }}"
                                            style="overflow: hidden; display: -webkit-box;-webkit-line-clamp: 2; line-clamp: 2;-webkit-box-orient: vertical">
                                            {{ $profile['name'] }}</h5>
                                        <button class="btn p-0 h6 mb-0" data-bs-toggle="modal" data-type="update"
                                            data-bs-target="#selectProfileModal">
                                            <span class="d-flex align-items-center gap-2"
                                                onclick=" event.stopPropagation(); editProfile({{ $profile['id'] }})">
                                                <span><i class="ph ph-pencil-simple-line"></i></span>
                                                <span>{{ __('frontend.edit') }}</span>
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach

                    <div class="col">
                        @if (!$isChildProfile)
                            <div class="card profil-card cursor-pointer" data-bs-toggle="modal" data-type="add"
                                data-bs-target="#selectProfileModal">
                                <div
                                    class="card-body rounded text-center d-flex flex-column align-items-center justify-content-center">
                                    <div class="profile-card-add-user bg-dark">
                                        <i class="ph ph-plus"></i>
                                    </div>
                                    <h5 class="mt-3 mb-0 font-size-18">{{ __('frontend.add_user') }}
                                    </h5>
                                </div>
                            </div>
                        @else
                            <div class="card profil-card cursor-pointer" id="addProfileBlocked">
                                <div
                                    class="card-body rounded text-center d-flex flex-column align-items-center justify-content-center">
                                    <div class="profile-card-add-user bg-dark">
                                        <i class="ph ph-plus"></i>
                                    </div>
                                    <h5 class="mt-3 mb-0 font-size-18">{{ __('frontend.add_user') }}
                                    </h5>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade add-profile-modal" id="selectProfileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content position-relative">
                <div class="">
                    <button type="button" class="btn custom-close-btn btn-primary" data-bs-dismiss="modal">
                        <i class="ph ph-x text-white fw-bold align-middle"> </i>
                    </button>
                </div>

                <form id="deleteProfileForm" method="POST" style="display:none;">
                    @csrf
                    @method('DELETE')
                </form>
                <form id="ProfileDetail" action="Post" class="requires-validation" data-toggle="validator" novalidate>
                    <div class="modal-body text-center">

                        <input type="hidden" name="profile_id" id="profile_id" value="" />
                        <div class="select-profile-slider d-flex align-items-center gap-3 ">
                            <!-- Example for the first image -->

                            <div class="slick-item">
                                <label class="select-profile-card">
                                    <input type="radio" name="profile_image"
                                        value="{{ asset('/img/web-img/icon2.png') }}" class="d-none" />
                                    <img src="{{ asset('/img/web-img/icon2.png') }}" class="select-profile-image"
                                        id="profile_image" alt="select-profile-image">
                                    <input type="file" id="profileFileImageInput" class="d-none"
                                        accept="image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp"
                                        onchange="previewProfileImage(event)">
                                    <i class="ph ph-pencil pencil-icon" onclick="triggerProfileFileInput()"></i>
                                </label>

                            </div>
                            <div class="slick-item">
                                <label class="select-profile-card">
                                    <input type="radio" name="profile_image"
                                        value="{{ asset('/img/web-img/icon5.png') }}" class="d-none" />
                                    <img src="{{ asset('/img/web-img/icon5.png') }}" class="select-profile-image"
                                        alt="select-profile-image">
                                </label>
                            </div>
                            <div class="slick-item">
                                <label class="select-profile-card">
                                    <input type="radio" name="profile_image"
                                        value="{{ asset('/img/web-img/icon6.png') }}" class="d-none"
                                        id="profile_image_value" checked />
                                    <img src="{{ asset('/img/web-img/icon6.png') }}" class="select-profile-image"
                                        alt="select-profile-image">
                                </label>
                            </div>
                            <div class="slick-item">
                                <label class="select-profile-card">
                                    <input type="radio" name="profile_image"
                                        value="{{ asset('/img/web-img/icon7.png') }}" class="d-none" />
                                    <img src="{{ asset('/img/web-img/icon7.png') }}" class="select-profile-image"
                                        alt="select-profile-image">
                                </label>
                            </div>
                            <div class="slick-item">
                                <label class="select-profile-card">
                                    <input type="radio" name="profile_image"
                                        value="{{ asset('/img/web-img/icon4.png') }}" class="d-none" />
                                    <img src="{{ asset('/img/web-img/icon4.png') }}" class="select-profile-image"
                                        alt="select-profile-image">
                                </label>
                            </div>
                        </div>
                        <div class="pt-4 mt-4 user-login-card">

                            <div class="mb-3">
                                <div class="input-group mb-0">
                                    <span class="input-style-text input-group-text"><i class="ph ph-user"></i></span>
                                    <input type="text" name="profile_first_name" class="form-control input-style-box"
                                        placeholder="{{ __('frontend.enter_name') }}" id="profile_first_name" maxlength="12" required>
                                </div>
                                <div class="invalid-feedback text-start" id="password-error">{{ __('messages.name_field_required') }}</div>
                            </div>

                            @if (getCurrentProfileSession('is_child_profile') == 0)
                                {{-- Show parent controller button --}}
                                <div class="input-group">
                                    <div class="d-flex justify-content-between align-items-center gap-2 w-100">
                                        <div
                                            class="d-flex align-items-center justify-content-between form-control bg-body">
                                            {{ html()->label(__('frontend.children_profile'), 'is_child_profile')->class('form-label mb-0 text-body') }}
                                            <label class="toggle-switch">
                                                <!-- This hidden input ensures "0" is sent when checkbox is unchecked -->
                                                <input type="hidden" name="is_child_profile" value="0">
                                                <input type="checkbox" name="is_child_profile" id="is_child_profile"
                                                    value="1">
                                                <span class="slider"></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            @endif



                            @csrf
                            @method('DELETE')
                </form>

                <div class="d-flex justify-content-center">
                    <button type="submit" id="update-profile" form="ProfileDetail" class="btn btn-primary">
                        {{ __('messages.add') }}
                    </button>
                </div>

            </div>
        </div>

    </div>
    </div>
    </form>
    </div>
    </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Helper: open hidden file input for profile image
        function triggerProfileFileInput() {
            var el = document.getElementById('profileFileImageInput');
            if (el) el.click();
        }

        // Helper: preview selected image in modal
        function previewProfileImage(event) {
            const fileInput = event.target;
            if (!fileInput || !fileInput.files || !fileInput.files[0]) return;

            const file = fileInput.files[0];
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            const extension = file.name.split('.').pop().toLowerCase();

            if (!allowedTypes.includes(file.type) || !allowedExtensions.includes(extension)) {
                fileInput.value = '';
                if (window.errorSnackbar) {
                    window.errorSnackbar('{{ __('messages.invalid_upload_file_type') }}');
                }
                return;
            }

            const reader = new FileReader();
            reader.onload = function() {
                var previewImage = document.getElementById('profile_image');
                if (previewImage) previewImage.src = reader.result;
            };
            reader.readAsDataURL(file);
        }

        // Populate edit modal with profile data
        function editProfile(id) {
            const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
            const apiUrl = `${baseUrl}/api/get-userprofile/${id}`;

            fetch(apiUrl, {
                    method: 'GET'
                })
                .then(r => {
                    if (!r.ok) throw new Error('Network response was not ok');
                    return r.json();
                })
                .then(res => {
                    var pid = document.getElementById('profile_id');
                    var pname = document.getElementById('profile_first_name');
                    var pchild = document.getElementById('is_child_profile');
                    var pimg = document.getElementById('profile_image');
                    var pimgVal = document.getElementById('profile_image_value');
                    if (pid) pid.value = res.data.id;
                    if (pname) pname.value = res.data.name;
                    if (pchild) pchild.checked = res.data.is_child_profile == 1;
                    if (pimg) pimg.setAttribute('src', res.data.avatar);
                    if (pimgVal) pimgVal.value = res.data.avatar;
                    // Ensure the radio matching the profile avatar is checked (preset or custom)
                    var matched = false;
                    document.querySelectorAll('input[name="profile_image"]').forEach(function(radio) {
                        if (radio.value === res.data.avatar) {
                            radio.checked = true;
                            matched = true;
                        } else {
                            radio.checked = false;
                        }
                    });
                    if (!matched && pimgVal) {
                        pimgVal.value = res.data.avatar;
                        pimgVal.checked = true;
                    }

                    // Optional child toggle visibility (if present)
                    var toggleDiv = document.getElementById('childProfileToggle');
                    if (toggleDiv) {
                        var profileCount = parseInt(`{{ $profileCount }}`);
                        toggleDiv.style.display = profileCount === 1 ? 'none' : 'block';
                    }

                    // Show modal
                    if (window.$) {
                        $('#selectProfileModal').modal('show');
                    }
                })
                .catch(err => console.error('Fetch error:', err));
        }

        // // Handle select profile with optional PIN flow
        // function SelectProfile11(id) {
        //     const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
        //     const pinModal = document.getElementById('verifyPinModal');
        //     const apiUrl = `${baseUrl}/api/get-pinpopup/${id}`;

        //     fetch(apiUrl, { method: 'GET' })
        //         .then(r => r.json())
        //         .then(res => {
        //             if (res.data == "yes" && pinModal && window.$) {
        //                 var sel = document.getElementById('select_profile_id');
        //                 if (sel) sel.value = id;
        //                 $('#verifyPinModal').modal('show');
        //             } else {
        //                 SelectProfile(id);
        //             }
        //         })
        //         .catch(err => console.error('Fetch error:', err));
        // }

        // Finalize profile selection
        function SelectProfile(id) {
            const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
            const apiUrl = `${baseUrl}/api/select-userprofile/${id}`;

            fetch(apiUrl, {
                    method: 'GET'
                })
                .then(r => {
                    if (!r.ok) throw new Error('Network response was not ok');
                    return r.json();
                })
                .then(response => {
                    var list = document.getElementById('profileList');
                    if (!list) return;
                    list.innerHTML = '';

                    response.data.forEach(function(profile) {
                        var timestamp = new Date().getTime();
                        var kidsBadge = profile.is_child_profile
                                        ? `<span class="kids-badge">{{ __('messages.kids') }}</span>`
                                        : ``;
                        var activeHtml = `
                        <div class="col">
                            <div class="card profil-card border border-primary">
                                <div class="card-body  rounded text-center">
                                    <div class="profile-card-image">
                                        <div class="profile-kids-bagde">
                                            <img id="profile_image_${profile.id}" src="${profile.avatar}?v=${timestamp}" alt="profile-image">
                                            ${kidsBadge}
                                        </div>
                                    </div>
                                    <h5 class="mt-3 mb-4 font-size-18 line-count-2" id="profile_name_${profile.id}">${profile.name}</h5>
                                    <button class="btn p-0 h6 mb-0" data-bs-toggle="modal" data-type="update" data-bs-target="#selectProfileModal">
                                        <span class="d-flex align-items-center gap-2" onclick="event.stopPropagation(); editProfile(${profile.id})">
                                            <span><i class="ph ph-pencil-simple-line"></i></span>
                                            <span>{{ __('messages.edit') }}</span>
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>`;

                        var inactiveHtml = `
                        <div class="col">
                            <div class="card profil-card">
                                <div class="card-body  rounded text-center">
                                    <div class="profile-card-image" >
                                        <div class="profile-kids-bagde">
                                            <img id="profile_image_${profile.id}" src="${profile.avatar}?v=${timestamp}" alt="profile-image">
                                            ${kidsBadge}
                                        </div>
                                    </div>
                                    <h5 class="mt-3 mb-4 font-size-18 line-count-2"  id="profile_name_${profile.id}">${profile.name}</h5>
                                    <button class="btn p-0 h6 mb-0" data-bs-toggle="modal" data-type="update" data-bs-target="#selectProfileModal">
                                        <span class="d-flex align-items-center gap-2" onclick="event.stopPropagation(); editProfile(${profile.id})">
                                            <span><i class="ph ph-pencil-simple-line"></i></span>
                                            <span>{{ __('messages.edit') }}</span>
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>`;

                        list.insertAdjacentHTML('beforeend', (profile.id == id) ? activeHtml : inactiveHtml);
                    });

                    const addUserHtml = `
                    <div class="col">
                        <div class="card profil-card cursor-pointer" data-bs-toggle="modal" data-type="add" data-bs-target="#selectProfileModal">
                            <div class="card-body bg-body rounded text-center d-flex flex-column align-items-center justify-content-center">
                                <div class="profile-card-add-user bg-dark">
                                    <i class="ph ph-plus"></i>
                                </div>
                                <h5 class="mt-3 mb-0 font-size-18">{{ __('messages.add_profile') }}</h5>
                            </div>
                        </div>
                    </div>`;
                    list.insertAdjacentHTML('beforeend', addUserHtml);

                    // Hide PIN modal if exists
                    if (window.$ && document.getElementById('verifyPinModal')) {
                        $('#verifyPinModal').modal('hide');
                    }

                })
                .catch(err => {
                    if (window.successSnackbar) window.successSnackbar(err);
                });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');

            // Remove profile handler (SweetAlert)
            document.querySelectorAll('.removeProfileBtn').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    if (window.$ && document.getElementById('selectProfileModal')) {
                        $('#selectProfileModal').modal('hide');
                    }

                    const profileId = this.getAttribute('data-profile-id');
                    const profileName = this.getAttribute('data-profile-name');
                    console.log(profileId, profileName);
                    
                    // Get the translated message template and replace the placeholder
                    const deleteMessageTemplate = '{{ __('messages.delete_profile_confirmation_new') }}';
                    const deleteMessage = deleteMessageTemplate.replace(':name', profileName);
                    
                    Swal.fire({
                        title: '{{ __('messages.are_you_sure') }}',
                        text: deleteMessage,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: 'var(--bs-primary)',
                        cancelButtonColor: '#22292E',
                        confirmButtonText: '{{ __('messages.yes_delete_it') }}',
                        cancelButtonText: '{{ __('messages.cancel') }}',
                        background: '#1e1e1e',
                        reverseButtons: true,
                        color: '#ffffff'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch(`${baseUrl}/profile/delete/${profileId}`, {
                                    method: 'DELETE',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector(
                                            'meta[name="csrf-token"]').getAttribute(
                                            'content'),
                                        'Accept': 'application/json'
                                    }
                                })
                                .then(r => r.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire({
                                            title: '{{ __('messages.deleted') }}',
                                            text: data.message,
                                            icon: 'success',
                                            background: '#1e1e1e',
                                            color: '#ffffff',
                                            confirmButtonColor: 'var(--bs-primary)',
                                            confirmButtonText: '{{ __('messages.ok') }}'
                                        }).then(() => window.location.reload());
                                    } else {
                                        Swal.fire({
                                            title: '{{ __('messages.error') }}',
                                            text: data.message ||
                                                '{{ __('messages.failed_to_delete_profile') }}',
                                            icon: 'error',
                                            background: '#1e1e1e',
                                            color: '#ffffff',
                                            confirmButtonColor: 'var(--bs-primary)',
                                            confirmButtonText: '{{ __('messages.ok') }}'
                                        });
                                    }
                                })
                                .catch(() => Swal.fire({
                                    title: '{{ __('messages.error') }}',
                                    text: '{{ __('messages.something_went_wrong') }}',
                                    icon: 'error',
                                    background: '#1e1e1e',
                                    color: '#ffffff',
                                    confirmButtonColor: 'var(--bs-primary)',
                                    confirmButtonText: '{{ __('messages.ok') }}'
                                }));
                        }
                    });
                });
            });

            // Create / update profile submit
            var form = document.getElementById('ProfileDetail');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const nameField = document.getElementById('profile_first_name');
                    const nameError = document.getElementById('password-error');
                    if (nameField && nameField.value.trim() === '') {
                        nameField.classList.add('is-invalid');
                        if (nameError) {
                            nameError.style.display = 'block';
                        }
                        return;
                    } else if (nameField && nameField.value.trim().length > 12) {
                        nameField.classList.add('is-invalid');
                        if (nameError) {
                            nameError.textContent = '{{ __('messages.name_max_12_characters') }}';
                            nameError.style.display = 'block';
                        }
                        return;
                    } else if (nameField) {
                        nameField.classList.remove('is-invalid');
                        if (nameError) {
                            nameError.style.display = 'none';
                        }
                    }

                    const apiUrl = `${baseUrl}/api/save-userprofile`;
                    const selectedImage = (document.querySelector('input[name="profile_image"]:checked') ||
                    {}).value;
                    const profileId = (document.getElementById('profile_id') || {}).value;
                    const submitButton = document.getElementById('update-profile');
                    const originalText = submitButton ? submitButton.textContent : '';
                    const isAddMode = !profileId;

                    var formData = new FormData();
                    formData.append('id', profileId || '');
                    if (selectedImage) formData.append('avatar', selectedImage);
                    if (nameField) formData.append('name', nameField.value);
                    var isChildEl = document.querySelector('input[name="is_child_profile"]:checked');
                    if (isChildEl) formData.append('is_child_profile', isChildEl.value);
                    var imageFileEl = document.getElementById('profileFileImageInput');
                    if (imageFileEl && imageFileEl.files && imageFileEl.files[0]) {
                        const uploadFile = imageFileEl.files[0];
                        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                        const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        const extension = uploadFile.name.split('.').pop().toLowerCase();

                        if (!allowedTypes.includes(uploadFile.type) || !allowedExtensions.includes(extension)) {
                            if (window.errorSnackbar) {
                                window.errorSnackbar('{{ __('messages.invalid_upload_file_type') }}');
                            }
                            if (submitButton) {
                                submitButton.disabled = false;
                                submitButton.textContent = originalText;
                            }
                            return;
                        }

                        formData.append('file_url', uploadFile);
                    }

                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.textContent = isAddMode ? '{{ __('messages.adding_profile') }}' :
                            '{{ __('messages.updating_profile') }}';
                    }

                    fetch(apiUrl, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content'),
                                'Accept': 'application/json'
                            }
                        })
                        .then(async (r) => {
                            let response = {};
                            try {
                                response = await r.json();
                            } catch (e) {
                                throw new Error('{{ __('messages.invalid_upload_file_type') }}');
                            }

                            if (!r.ok || response.status === false) {
                                const errorMessage = response.message || response.error ||
                                    '{{ __('messages.invalid_upload_file_type') }}';
                                if (window.errorSnackbar) window.errorSnackbar(errorMessage);
                                if (submitButton) {
                                    submitButton.disabled = false;
                                    submitButton.textContent = originalText;
                                }
                                return;
                            }


                            if (submitButton) {
                                submitButton.disabled = false;
                                submitButton.textContent = originalText;
                            }

                            if (window.successSnackbar) window.successSnackbar(response.message);
                            if (window.$) $('#selectProfileModal').modal('hide');
                            window.location.reload();


                        })
                        .catch(() => {
                            if (submitButton) {
                                submitButton.disabled = false;
                                submitButton.textContent = originalText;
                            }
                            if (window.errorSnackbar) {
                                window.errorSnackbar('{{ __('messages.invalid_upload_file_type') }}');
                            }
                        });
                });
            }

            // Clear error message when user starts typing
            const nameFieldInput = document.getElementById('profile_first_name');
            const nameErrorMsg = document.getElementById('password-error');
            if (nameFieldInput && nameErrorMsg) {
                nameFieldInput.addEventListener('input', function() {
                    if (this.value.trim() !== '' && this.value.trim().length <= 12) {
                        this.classList.remove('is-invalid');
                        nameErrorMsg.style.display = 'none';
                    } else if (this.value.trim().length > 12) {
                        this.classList.add('is-invalid');
                        nameErrorMsg.textContent = '{{ __('messages.name_max_12_characters') }}';
                        nameErrorMsg.style.display = 'block';
                    }
                });
            }

            // Modal mode switch: Add vs Update (if Bootstrap modal present)
            var selectProfileModal = document.getElementById('selectProfileModal');
            if (selectProfileModal) {
                selectProfileModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    if (!button) return;
                    const dataType = button.getAttribute('data-type');
                    const updateButton = document.getElementById('update-profile');
                    if (!updateButton) return;

                    if (dataType === 'add') {
                        var pd = document.getElementById('ProfileDetail');
                        if (pd) pd.reset();
                        var pid = document.getElementById('profile_id');
                        if (pid) pid.value = '';
                        var nameField = document.getElementById('profile_first_name');
                        var nameError = document.getElementById('password-error');
                        if (nameField) nameField.classList.remove('is-invalid');
                        if (nameError) nameError.style.display = 'none';
                        updateButton.textContent = '{{ __('messages.add') }}';
                    } else {
                        updateButton.textContent = '{{ __('frontend.update') }}';
                    }
                });
            }
        });
    </script>
@endsection
