@extends('backend.layouts.app')

@section('title', __('messages.short_drama_installer_page_title'))

@push('after-styles')
<style>
    .sd-installer-icon { font-size: 3rem; }
    .sd-zone { border: 2px dashed var(--bs-border-color); border-radius: .5rem;
               padding: 2rem 1rem; text-align: center; cursor: pointer; transition: border-color .2s; }
    .sd-zone:hover, .sd-zone.dragover { border-color: var(--bs-primary); }
    .sd-zone .sd-zone-icon { font-size: 2.5rem; color: var(--bs-secondary); }
    #selected-file-name { font-size: .85rem; margin-top: .5rem; }
    .progress { height: 6px; }
</style>
@endpush

@section('content')

<div class="d-flex align-items-center gap-3 mb-4">
    <div class="sd-installer-icon text-primary">
        <i class="fa-solid fa-film"></i>
    </div>
    <div>
        <h4 class="mb-0">{{ __('messages.short_drama_installer_heading') }}</h4>
        <small class="text-muted">{{ __('messages.short_drama_installer_subtitle') }}</small>
    </div>
    <div class="ms-auto" id="sd-install-badge-wrap">
        @if ($installed)
            <span class="badge bg-success fs-6 px-3 py-2" id="sd-install-badge">
                <i class="fa-solid fa-circle-check me-1"></i> {{ __('messages.short_drama_installer_badge_installed') }}
            </span>
        @else
            <span class="badge bg-secondary fs-6 px-3 py-2" id="sd-install-badge">
                <i class="fa-solid fa-circle-xmark me-1"></i> {{ __('messages.short_drama_installer_badge_not_installed') }}
            </span>
        @endif
    </div>
</div>

<div class="row g-4">

    <div class="col-xl-7 order-2 order-xl-1">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fa-solid fa-upload me-2 text-primary"></i>
                    @if ($installed)
                        {{ __('messages.short_drama_installer_upload_title_reinstall') }}
                    @else
                        {{ __('messages.short_drama_installer_upload_title') }}
                    @endif
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">
                    {!! __('messages.short_drama_installer_instructions') !!}
                </p>

                <form method="POST"
                      action="{{ route('backend.short-drama.installer.install') }}"
                      enctype="multipart/form-data"
                      id="install-form"
                      novalidate>
                    @csrf

                    <div class="sd-zone mb-3"
                         id="drop-zone"
                         onclick="document.getElementById('addon_zip').click()">
                        <div class="sd-zone-icon mb-2">
                            <i class="fa-solid fa-file-zipper"></i>
                        </div>
                        <div class="fw-semibold">{{ __('messages.short_drama_installer_dropzone_title') }}</div>
                        <div class="text-muted" style="font-size:.8rem">
                            {{ __('messages.short_drama_installer_dropzone_hint') }}
                        </div>
                        <div id="selected-file-name" class="text-success fw-semibold"></div>
                    </div>

                    <input type="file"
                           id="addon_zip"
                           name="addon_zip"
                           accept=".zip,application/zip"
                           class="d-none">

                    <div class="progress mb-3 d-none" id="upload-progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                             role="progressbar" style="width: 0%"></div>
                    </div>

                    <button type="submit"
                            class="btn btn-primary w-100"
                            id="install-btn">
                        <i class="fa-solid fa-bolt me-2"></i>
                        @if ($installed)
                            {{ __('messages.short_drama_installer_btn_reinstall') }}
                        @else
                            {{ __('messages.short_drama_installer_btn_install') }}
                        @endif
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-5 order-1 order-xl-2">

        <div class="card mb-4" id="sd-installer-status">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fa-solid fa-circle-info me-2 text-info"></i>
                    {{ __('messages.short_drama_installer_current_status') }}
                </h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush" id="sd-status-list">
                    @foreach ($statusChecks as $label => $ok)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="text-muted" style="font-size:.85rem">{{ $label }}</span>
                            @if ($ok)
                                <span class="badge bg-success rounded-pill">
                                    <i class="fa-solid fa-check"></i> {{ __('messages.short_drama_installer_status_ok') }}
                                </span>
                            @else
                                <span class="badge bg-danger rounded-pill">
                                    <i class="fa-solid fa-xmark"></i> {{ __('messages.short_drama_installer_status_missing') }}
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

    </div>
</div>

@endsection

@push('after-scripts')
<script>
    const MAX_ZIP_BYTES = {{ \App\Http\Requests\ShortDramaPluginInstallerRequest::MAX_ZIP_BYTES }};
    const MSG_ZIP_REQUIRED = @json(__('messages.short_drama_plugin_zip_required'));
    const MSG_ZIP_MIMES = @json(__('messages.short_drama_plugin_zip_mimes'));
    const MSG_ZIP_MAX = @json(__('messages.short_drama_plugin_zip_max', ['max' => '50 MB']));
    const MSG_UPLOAD_FAILED = @json(__('messages.short_drama_plugin_upload_failed'));
    const INSTALLER_URL = @json(route('backend.short-drama.installer'));
    const L10N = {
        statusOk: @json(__('messages.short_drama_installer_status_ok')),
        statusMissing: @json(__('messages.short_drama_installer_status_missing')),
        badgeInstalled: @json(__('messages.short_drama_installer_badge_installed')),
        badgeNotInstalled: @json(__('messages.short_drama_installer_badge_not_installed')),
        installing: @json(__('messages.short_drama_installer_btn_installing')),
        noFileSelected: @json(__('messages.short_drama_installer_no_file_selected')),
    };

    function statusCheckRowHtml(label, ok) {
        const badge = ok
            ? '<span class="badge bg-success rounded-pill"><i class="fa-solid fa-check"></i> ' + L10N.statusOk + '</span>'
            : '<span class="badge bg-danger rounded-pill"><i class="fa-solid fa-xmark"></i> ' + L10N.statusMissing + '</span>';
        return '<li class="list-group-item d-flex justify-content-between align-items-center">'
            + '<span class="text-muted" style="font-size:.85rem">' + label + '</span>'
            + badge
            + '</li>';
    }

    function renderStatusChecks(checks) {
        const list = document.getElementById('sd-status-list');
        if (!list || !Array.isArray(checks)) {
            return;
        }
        list.innerHTML = checks.map((row) => statusCheckRowHtml(row.label, !!row.ok)).join('');
    }

    function updateInstallBadge(installed) {
        const wrap = document.getElementById('sd-install-badge-wrap');
        if (!wrap) {
            return;
        }
        if (installed) {
            wrap.innerHTML = '<span class="badge bg-success fs-6 px-3 py-2" id="sd-install-badge">'
                + '<i class="fa-solid fa-circle-check me-1"></i> ' + L10N.badgeInstalled + '</span>';
        } else {
            wrap.innerHTML = '<span class="badge bg-secondary fs-6 px-3 py-2" id="sd-install-badge">'
                + '<i class="fa-solid fa-circle-xmark me-1"></i> ' + L10N.badgeNotInstalled + '</span>';
        }
    }

    function finishInstallSuccess(payload) {
        if (payload && Array.isArray(payload.statusChecks)) {
            renderStatusChecks(payload.statusChecks);
            updateInstallBadge(!!payload.installed);
            document.getElementById('sd-installer-status')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        const message = payload && payload.message;
        if (message && typeof window.successSnackbar === 'function') {
            window.successSnackbar(message);
        }

        const target = (payload && payload.redirect) ? payload.redirect : (INSTALLER_URL + '?installed=1');
        window.location.assign(target);
    }

    const input     = document.getElementById('addon_zip');
    const zone      = document.getElementById('drop-zone');
    const label     = document.getElementById('selected-file-name');
    const form      = document.getElementById('install-form');
    const btn       = document.getElementById('install-btn');
    const progress  = document.getElementById('upload-progress');
    const bar       = progress?.querySelector('.progress-bar');
    const installBtnDefaultHtml = btn ? btn.innerHTML : '';

    function showInstallerToastError(message) {
        const text = String(message || '').trim();
        if (!text) {
            return;
        }
        if (typeof window.errorSnackbar === 'function') {
            window.errorSnackbar(text);
            return;
        }
        alert(text);
    }

    function validateZipFile(file) {
        if (!file) {
            return MSG_ZIP_REQUIRED;
        }
        const name = (file.name || '').toLowerCase();
        if (!name.endsWith('.zip')) {
            return MSG_ZIP_MIMES;
        }
        if (file.size > MAX_ZIP_BYTES) {
            return MSG_ZIP_MAX;
        }
        return null;
    }

    function resetInstallButton() {
        if (!btn) return;
        btn.disabled = false;
        btn.innerHTML = installBtnDefaultHtml;
        progress?.classList.add('d-none');
        if (bar) bar.style.width = '0%';
    }

    function applySelectedFile(file) {
        const err = validateZipFile(file);
        if (err) {
            showInstallZipError(err);
            if (input) input.value = '';
            if (label) {
                label.textContent = file ? file.name : L10N.noFileSelected;
            }
            return false;
        }
        if (label) {
            label.classList.remove('text-danger');
            label.classList.add('text-success');
            label.textContent = '✓ ' + file.name;
        }
        return true;
    }

    function showInstallZipError(msg) {
        showInstallerToastError(msg);
        label?.classList.remove('text-success');
        label?.classList.add('text-danger');
        zone?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    input?.addEventListener('change', () => {
        if (input.files[0]) {
            applySelectedFile(input.files[0]);
        }
    });

    zone?.addEventListener('dragover', (e) => { e.preventDefault(); zone.classList.add('dragover'); });
    zone?.addEventListener('dragleave', ()  => zone.classList.remove('dragover'));
    zone?.addEventListener('drop', (e) => {
        e.preventDefault();
        zone.classList.remove('dragover');
        const dropped = e.dataTransfer.files[0];
        if (!dropped) return;
        const dt = new DataTransfer();
        dt.items.add(dropped);
        input.files = dt.files;
        applySelectedFile(dropped);
    });

    form?.addEventListener('submit', (e) => {
        e.preventDefault();

        const file = input.files[0];
        const clientValidation = validateZipFile(file);
        if (clientValidation) {
            showInstallZipError(clientValidation);
            if (label) label.textContent = file ? file.name : L10N.noFileSelected;
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>' + L10N.installing;
        progress.classList.remove('d-none');

        const data = new FormData(form);
        const xhr  = new XMLHttpRequest();

        xhr.upload.addEventListener('progress', (ev) => {
            if (ev.lengthComputable) {
                const pct = Math.round((ev.loaded / ev.total) * 100);
                bar.style.width = pct + '%';
            }
        });

        xhr.addEventListener('load', () => {
            bar.style.width = '100%';

            let payload = null;
            try {
                payload = JSON.parse(xhr.responseText || '');
            } catch (e) {
                payload = null;
            }

            if (xhr.status >= 200 && xhr.status < 300) {
                finishInstallSuccess(payload);
                return;
            }

            const message = (payload && (payload.message || (payload.errors && payload.errors.addon_zip && payload.errors.addon_zip[0])))
                || MSG_UPLOAD_FAILED;
            showInstallZipError(message);
            resetInstallButton();
        });

        xhr.addEventListener('error', () => {
            showInstallZipError(MSG_UPLOAD_FAILED);
            resetInstallButton();
        });

        xhr.open('POST', form.action);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.send(data);
    });

    @if ($errors->has('addon_zip'))
    document.addEventListener('DOMContentLoaded', function () {
        showInstallerToastError(@json($errors->first('addon_zip')));
    });
    @endif

    document.addEventListener('DOMContentLoaded', function () {
        const params = new URLSearchParams(window.location.search);
        if (params.get('installed') === '1') {
            document.getElementById('sd-installer-status')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
</script>
@endpush
