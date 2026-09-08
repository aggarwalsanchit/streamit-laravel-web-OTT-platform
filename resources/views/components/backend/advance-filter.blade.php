<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasExample" aria-labelledby="offcanvasExampleLabel">
  <div class="offcanvas-header border-bottom">
    @if(isset($title))
      {{ $title }}
    @endif
    <button type="button" data-bs-dismiss="offcanvas" aria-label="Close" class="btn-close-offcanvas"><i class="ph ph-x-circle"></i></button>
  </div>
  <div class="offcanvas-body">
    {{ $slot }}
  </div>
  <div class="offcanvas-body">
    @if(isset($footer))
      {{$footer}}
    @endif
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var resetButton = document.getElementById('reset-filter');
    if (resetButton) {
        resetButton.addEventListener('click', function(event) {
            var offcanvas = bootstrap.Offcanvas.getInstance('#offcanvasExample');
            if (offcanvas) {
                offcanvas.hide();
            }
            // Reset filter count badge when reset is clicked
            updateFilterCountBadge();
        });
    }
    
    // Function to count active filters
    function countActiveFilters() {
        let count = 0;
        const allText = '{{ __('messages.all') }}';
        const processedSelects = new Set(); // Track processed selects to avoid double counting
        
        // Count filters in the offcanvas (Advanced Filter)
        // Use direct children approach to avoid counting nested datatable-filter divs multiple times
        $('#offcanvasExample select[data-filter="select"]').each(function() {
            const $select = $(this);
            
            // Skip if already processed
            if (processedSelects.has(this)) {
                return;
            }
            
            // Skip hidden fields (like type which is always set)
            if ($select.attr('type') === 'hidden' || this.type === 'hidden') {
                return;
            }
            
            // Skip selects inside hidden containers (like planSelection when hidden)
            if ($select.closest('.d-none').length > 0) {
                return;
            }
            
            let value;
            
            if ($select.hasClass('select2-hidden-accessible')) {
                // For select2, use jQuery val()
                value = $select.val();
                // Select2 might return null, empty string, or array
                if (Array.isArray(value)) {
                    // Filter out empty strings from array
                    value = value.filter(v => v !== '' && v !== null && v !== undefined);
                    if (value.length === 0) {
                        value = null; // Treat empty array as no selection
                    } else if (value.length === 1) {
                        value = value[0]; // Single value, extract it
                    }
                    // Multiple values remain as array
                }
            } else {
                value = this.value;
            }
            
            // Count if value is not empty and not "all" or empty string
            if (value !== null && value !== undefined && value !== '' && value !== 'all') {
                if (Array.isArray(value)) {
                    // For multiple select, count if any option is selected
                    if (value.length > 0 && !value.includes('') && !value.includes(null)) {
                        processedSelects.add(this);
                        count++;
                    }
                } else {
                    // For single select, check if it's not "all" text
                    const $option = $select.find('option:selected');
                    const optionText = $option.text().trim();
                    if (optionText !== '' && optionText !== allText && !optionText.includes(allText)) {
                        processedSelects.add(this);
                        count++;
                    }
                }
            }
        });
        
        // Check text and number inputs (only visible ones, avoid hidden containers)
        $('#offcanvasExample input[type="text"][data-filter="text"], #offcanvasExample input[type="number"][data-filter="text"]').each(function() {
            // Skip inputs inside hidden containers
            if ($(this).closest('.d-none').length > 0) {
                return;
            }
            if (this.value && this.value.trim() !== '') {
                count++;
            }
        });
        
        // Check radio button groups (count once per group if any is checked)
        // IMPORTANT: For movie_access radio, only count if plan_id is NOT set
        // This prevents double counting when plan filter is the only active filter
        const radioGroupsChecked = {};
        $('#offcanvasExample input[type="radio"][name]').each(function() {
            const name = $(this).attr('name');
            const $radio = $(this);
            
            // Skip radios inside hidden containers
            if ($radio.closest('.d-none').length > 0) {
                return;
            }
            
            if ($radio.is(':checked') && $radio.val() && $radio.val() !== '') {
                // Special handling for movie_access, access, and video_access radio buttons: only count if plan_id is empty
                // This ensures plan-only filtering doesn't count both plan and access
                if (name === 'movie_access' || name === 'access' || name === 'video_access') {
                    const $planSelect = $('#plan_id');
                    let planIdValue;
                    
                    // Get plan_id value correctly (handle select2)
                    if ($planSelect.hasClass('select2-hidden-accessible')) {
                        planIdValue = $planSelect.val();
                    } else {
                        planIdValue = $planSelect[0] ? $planSelect[0].value : null;
                    }
                    
                    // Handle array values from select2
                    if (Array.isArray(planIdValue)) {
                        planIdValue = planIdValue.length > 0 ? planIdValue[0] : null;
                    }
                    
                    // Only count access/movie_access if plan_id is empty/not set
                    // This means user is filtering by access type, not by plan
                    if (!planIdValue || planIdValue === '' || planIdValue === null || planIdValue === 'all') {
                        if (!radioGroupsChecked[name]) {
                            radioGroupsChecked[name] = true;
                            count++;
                        }
                    }
                    // If plan_id has a value, don't count access/movie_access separately
                    // because the plan filter already represents the filtering intent
                } else {
                    // For other radio groups, count normally
                    if (!radioGroupsChecked[name]) {
                        radioGroupsChecked[name] = true;
                        count++;
                    }
                }
            }
        });
        
        return count;
    }
    
    // Function to update filter count badge
    window.updateFilterCountBadge = function() {
        const count = countActiveFilters();
        const filterButton = document.querySelector('[data-bs-target="#offcanvasExample"]');
        
        if (filterButton) {
            // Remove existing badge if any
            let existingBadge = filterButton.querySelector('.filter-count-badge');
            if (existingBadge) {
                existingBadge.remove();
            }
            
            // Add badge if there are active filters - inline with button text
            if (count > 0) {
                const badge = document.createElement('span');
                badge.className = 'filter-count-badge ms-2';
                badge.setAttribute('title', count + ' {{ __('messages.filter_applied') }}');
                badge.textContent = count;
                // Append badge to button - it will align inline since button uses flexbox
                filterButton.appendChild(badge);
            }
        }
    };
    
    // Listen for filter changes - update count badge when filters change
    $(document).on('change select2:select select2:unselect select2:clear', '#offcanvasExample .datatable-filter select, #offcanvasExample .datatable-filter input[type="radio"]', function() {
        setTimeout(function() {
            if (typeof updateFilterCountBadge === 'function') {
                updateFilterCountBadge();
            }
        }, 150);
    });
    
    $(document).on('input change keyup', '.dt-search, #offcanvasExample .datatable-filter input[type="text"], #offcanvasExample .datatable-filter input[type="number"]', function() {
        setTimeout(function() {
            if (typeof updateFilterCountBadge === 'function') {
                updateFilterCountBadge();
            }
        }, 150);
    });
    
    // Also update when select2 values are cleared or changed programmatically
    $(document).on('select2:clearing', '#offcanvasExample .datatable-filter select', function() {
        setTimeout(function() {
            if (typeof updateFilterCountBadge === 'function') {
                updateFilterCountBadge();
            }
        }, 100);
    });
    
    // Initial count on page load
    setTimeout(function() {
        updateFilterCountBadge();
    }, 500);
    
    // Update count when offcanvas is shown (in case filters were changed programmatically)
    const offcanvasElem = document.querySelector('#offcanvasExample');
    if (offcanvasElem) {
        offcanvasElem.addEventListener('shown.bs.offcanvas', function() {
            // Initialize select2 for selects that don't already have it initialized
            // Skip elements that have custom initialization (marked with data-custom-select2)
            $('#offcanvasExample .datatable-filter select[data-filter="select"]').each(function() {
                const $select = $(this);
                // Check if it has custom initialization via data attribute
                const hasCustomInit = $select.attr('data-custom-select2') === 'true' 
                    || $select.data('custom-select2') === true 
                    || $select.data('custom-select2') === 'true';
                
                // Skip if already initialized or if it has custom initialization
                if (!$select.hasClass('select2-hidden-accessible') && !hasCustomInit) {
                    $select.select2({
                        dropdownParent: $('#offcanvasExample'),
                        minimumResultsForSearch: 0, // Always show search box (0 is the correct standard value)
                        allowClear: true,
                        language: {
                            noResults: function() {
                                return "{{ __('messages.no_results_found') }}";
                            }
                        }
                    });
                }
            });
            // Update count when offcanvas is shown
            setTimeout(function() {
                updateFilterCountBadge();
            }, 200);
        });
        
        // Update count when offcanvas is hidden (filters might have been applied)
        offcanvasElem.addEventListener('hidden.bs.offcanvas', function() {
            setTimeout(function() {
                updateFilterCountBadge();
            }, 100);
        });
    }
});

</script>
