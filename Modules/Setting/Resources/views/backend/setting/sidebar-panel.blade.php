
<div class="col-md-4 col-lg-3">
    <div id="setting-sidebar" class="setting-sidebar-inner">
        <div class="card">
            <div class="card-body">
                <div class="list-group list-group-flush" id="setting-list">
                    @hasPermission('setting_bussiness')
                        <div class="mb-3 active-menu">
                            <a id="link-general" href="{{ route('backend.settings.general') }}" class="btn btn-border {{ request()->routeIs('backend.settings.general') ? 'active' : '' }}">
                                <i class="fas fa-cube"></i>{{ __('setting_sidebar.lbl_General') }}
                            </a>
                        </div>
                    @endhasPermission
                    @hasPermission('setting_custom_code')
                        <div class="mb-3 active-menu">
                            <a id="link-custom-code" href="{{ route('backend.settings.custom-code') }}" class="btn btn-border {{ request()->routeIs('backend.settings.custom-code') ? 'active' : '' }}">
                                <i class="fa-solid fa-file-code"></i>{{ __('setting_sidebar.lbl_custom_code') }}
                            </a>
                        </div>
                    @endhasPermission
                    @hasPermission('setting_module')
                    <div class="mb-3 active-menu">
                        <a id="link-module-setting" href="{{ route('backend.settings.module') }}" class="btn btn-border {{ request()->routeIs('backend.settings.module') ? 'active' : '' }}">
                            <i class="icon ph ph-list-dashes"></i>{{ __('setting_sidebar.lbl_module-setting') }}
                        </a>
                    </div>
                    @endhasPermission
                    @hasPermission('setting_misc')
                        <div class="mb-3 active-menu">
                            <a id="link-misc" href="{{ route('backend.settings.misc') }}" class="btn btn-border {{ request()->routeIs('backend.settings.misc') ? 'active' : '' }}">
                                <i class="fa-solid fa-screwdriver-wrench"></i>{{ __('setting_sidebar.lbl_misc_setting') }}
                            </a>
                        </div>
                    @endhasPermission
                    @hasPermission('setting_customization')
                        <div class="mb-3 active-menu">
                            <a id="link-customization" href="{{ route('backend.settings.customization') }}" class="btn btn-border {{ request()->routeIs('backend.settings.customization') ? 'active' : '' }}">
                                <i class="fa-solid fa-swatchbook"></i>{{ __('setting_sidebar.lbl_customization') }}
                            </a>
                        </div>
                    @endhasPermission
                    @hasPermission('setting_mail')
                        <div class="mb-3 active-menu">
                            <a id="link-mail" href="{{ route('backend.settings.mail') }}" class="btn btn-border {{ request()->routeIs('backend.settings.mail') ? 'active' : '' }}">
                                <i class="fas fa-envelope"></i>{{ __('setting_sidebar.lbl_mail') }}
                            </a>
                        </div>
                    @endhasPermission
                    @hasPermission('setting_notification')
                        <div class="mb-3 active-menu">
                            <a id="link-notification" href="{{ route('backend.settings.notificationsetting') }}" class="btn btn-border {{ request()->routeIs('backend.settings.notificationsetting') ? 'active' : '' }}">
                                <i class="fa-solid fa-bullhorn"></i>{{ __('setting_sidebar.lbl_notification') }}
                            </a>
                        </div>
                    @endhasPermission
                    @if(auth()->user()->hasRole('admin'))
                    <div class="mb-3 active-menu">
                        <a id="link-payment-method" href="{{ route('backend.settings.payment-method') }}" class="btn btn-border {{ request()->routeIs('backend.settings.payment-method') ? 'active' : '' }}">
                            <i class="fa-solid fa-coins"></i>{{ __('setting_sidebar.lbl_payment') }}
                        </a>
                    </div>
                    @endif
                    @hasPermission('setting_language')
                        <div class="mb-3 active-menu">
                            <a id="link-language-settings" href="{{ route('backend.settings.language-settings') }}" class="btn btn-border {{ request()->routeIs('backend.settings.language-settings') ? 'active' : '' }}">
                                <i class="fa fa-language" aria-hidden="true"></i>{{ __('setting_sidebar.lbl_language') }}
                            </a>
                        </div>
                    @endhasPermission
                    <div class="mb-3 active-menu">
                        <a id="link-notification-configuration" href="{{ route('backend.settings.notification-configuration') }}" class="btn btn-border {{ request()->routeIs('backend.settings.notification-configuration') ? 'active' : '' }}">
                            <i class="fa-solid fa-bell"></i>{{ __('setting_sidebar.lbl_notification_configuration') }}
                        </a>
                    </div>
                    @hasPermission('view_currency')
                    <div class="mb-3 active-menu">
                        <a id="link-currency-settings" href="{{ route('backend.settings.currency-settings') }}" class="btn btn-border {{ request()->routeIs('backend.settings.currency-settings') ? 'active' : '' }}">
                            <i class="fa fa-dollar fa-lg mr-2"></i>{{ __('setting_sidebar.lbl_currency_setting') }}
                        </a>
                    </div>
                    @endhasPermission
                    <div class="mb-3 active-menu">
                        <a id="link-storage-settings" href="{{ route('backend.settings.storage-settings') }}" class="btn btn-border {{ request()->routeIs('backend.settings.storage-settings') ? 'active' : '' }}">
                            <i class="fa-solid fa-database"></i>{{ __('setting_sidebar.lbl_storage') }}
                        </a>
                    </div>


                    <div class="mb-3 active-menu">
                        <a id="link-seo-settings" href="{{ route('Seo.seo-settings') }}" class="btn btn-border {{ request()->routeIs('Seo.seo-settings') ? 'active' : '' }}">
                            <i class="fa-solid fa-search"></i>{{ __('setting_sidebar.lbl_seo') }}
                        </a>
                    </div>



                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        function toggle() {
            const formOffcanvas = document.getElementById('offcanvas');
            formOffcanvas.classList.add('show');
        }

        function hasPermission(permission) {
            return window.auth_permissions.includes(permission);
        }

        // AJAX navigation for settings sidebar links
        document.addEventListener('DOMContentLoaded', function() {
            const settingLinks = document.querySelectorAll('#setting-list a.btn-border');
            const mainContentArea = document.querySelector('.offcanvas-body .card-body');
            
            if (!mainContentArea) return;

            settingLinks.forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const url = this.getAttribute('href');
                    if (!url) return;
                    
                    // Close any open modals first
                    const openModals = document.querySelectorAll('.modal');
                    openModals.forEach(function(modal) {
                        if (modal.classList.contains('show') || modal.style.display !== 'none') {
                            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                                const modalInstance = bootstrap.Modal.getInstance(modal);
                                if (modalInstance) {
                                    modalInstance.hide();
                                } else {
                                    // Force hide if no instance exists
                                    modal.classList.remove('show');
                                    modal.style.display = 'none';
                                    document.body.classList.remove('modal-open');
                                    const backdrop = document.querySelector('.modal-backdrop');
                                    if (backdrop) {
                                        backdrop.remove();
                                    }
                                }
                            } else {
                                // Fallback if bootstrap is not available
                                modal.classList.remove('show');
                                modal.style.display = 'none';
                                document.body.classList.remove('modal-open');
                                const backdrop = document.querySelector('.modal-backdrop');
                                if (backdrop) {
                                    backdrop.remove();
                                }
                            }
                        }
                    });

                    // Add loading state
                    mainContentArea.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
                    
                    // Update active state
                    settingLinks.forEach(function(l) {
                        l.classList.remove('active');
                    });
                    this.classList.add('active');

                    // Load content via AJAX
                    fetch(url, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html'
                        }
                    })
                    .then(response => {
                        // Check if response was redirected
                        if (response.redirected) {
                            console.warn('Response was redirected to:', response.url);
                            // If redirected, try to fetch the final URL
                            return fetch(response.url, {
                                method: 'GET',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'text/html'
                                }
                            }).then(res => res.text());
                        }
                        
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.text();
                    })
                    .then(html => {
                        // Extract only the settings-content section
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        
                        // Try multiple selectors to find the content area
                        let settingsContent = null;
                        
                        // First, try to find the exact structure we need
                        const offcanvasBody = doc.querySelector('.offcanvas-body');
                        if (offcanvasBody) {
                            const cardBody = offcanvasBody.querySelector('.card-body');
                            if (cardBody) {
                                settingsContent = cardBody;
                            }
                        }
                        
                        // Fallback selectors
                        if (!settingsContent) {
                            settingsContent = doc.querySelector('.offcanvas-body .card-body') || 
                                            doc.querySelector('.card-body form') ||
                                            doc.querySelector('form#form-submit') ||
                                            doc.querySelector('form') ||
                                            doc.querySelector('.card-body');
                        }
                        
                        if (settingsContent) {
                            // Store scripts before replacing innerHTML
                            const scripts = Array.from(settingsContent.querySelectorAll('script'));
                            const scriptsData = scripts.map(script => ({
                                html: script.outerHTML,
                                src: script.src,
                                type: script.type,
                                content: script.innerHTML
                            }));
                            
                            // Update content
                            mainContentArea.innerHTML = settingsContent.innerHTML;
                            
                            // Reinitialize scripts after a short delay to ensure DOM is ready
                            setTimeout(function() {
                                scriptsData.forEach(function(scriptData) {
                                    if (scriptData.src) {
                                        // External script
                                        const newScript = document.createElement('script');
                                        newScript.src = scriptData.src;
                                        if (scriptData.type) newScript.type = scriptData.type;
                                        mainContentArea.appendChild(newScript);
                                    } else if (scriptData.content) {
                                        // Inline script
                                        const newScript = document.createElement('script');
                                        if (scriptData.type) newScript.type = scriptData.type;
                                        newScript.textContent = scriptData.content;
                                        mainContentArea.appendChild(newScript);
                                    }
                                });
                                
                                // Trigger DOMContentLoaded for new scripts
                                window.dispatchEvent(new Event('DOMContentLoaded'));
                                
                                // Ensure any modals are closed after content loads
                                const allModals = document.querySelectorAll('.modal');
                                allModals.forEach(function(modal) {
                                    if (modal.classList.contains('show') || modal.style.display !== 'none') {
                                        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                                            const modalInstance = bootstrap.Modal.getInstance(modal);
                                            if (modalInstance) {
                                                modalInstance.hide();
                                            }
                                        }
                                        modal.classList.remove('show');
                                        modal.style.display = 'none';
                                        document.body.classList.remove('modal-open');
                                        const backdrop = document.querySelector('.modal-backdrop');
                                        if (backdrop) {
                                            backdrop.remove();
                                        }
                                    }
                                });
                                
                                // Reinitialize form handlers
                                if (typeof window.handleFormSubmit === 'function') {
                                    const forms = mainContentArea.querySelectorAll('#form-submit');
                                    forms.forEach(form => {
                                        if (form.dataset.ajaxHandled !== 'true' && form.dataset.customHandler !== 'true') {
                                            window.handleFormSubmit(form);
                                        }
                                    });
                                }
                            }, 100);
                        } else {
                            console.error('Could not find settings content in response');
                            mainContentArea.innerHTML = '<div class="alert alert-danger">Error loading page. Please refresh the page.</div>';
                        }
                        
                        // Update URL without reload
                        if (window.history && window.history.pushState) {
                            window.history.pushState({path: url}, '', url);
                        }
                    })
                    .catch(error => {
                        console.error('Error loading settings page:', error);
                        mainContentArea.innerHTML = '<div class="alert alert-danger">Error loading page. Please refresh the page.</div>';
                    });
                });
            });
        });
    </script>
@endpush

<style scoped>
    .btn-border {
        text-align: left;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
</style>
