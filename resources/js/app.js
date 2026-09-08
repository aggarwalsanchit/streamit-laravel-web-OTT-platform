(function () {
  "use strict";
  $(document).on('change', '.datatable-filter [data-filter="select"]', function () {
    window.renderedDataTable.ajax.reload(null, false)
  })

  $(document).on('input', '.dt-search', function () {
    window.renderedDataTable.ajax.reload(null, false)
  })

  const confirmSwal = async (message, actionType = null) => {
    // Determine button text based on action type
    let buttonText;
    if (actionType) {
      // Use action type to determine button text
      if (actionType === 'restore') {
        buttonText = window.localMessagesUpdate?.messages?.yes_restore_it || 'Yes, restore it!';
      } else if (actionType === 'change-status' || actionType === 'change-featured') {
        buttonText = window.localMessagesUpdate?.messages?.yes_do_it || 'Yes, do it!';
      } else {
        buttonText = window.localMessagesUpdate?.messages?.yes_delete_it || 'Yes, delete it!';
      }
    } else if (message && typeof message === 'string') {
      // Fallback: determine from message content
      const lowerMessage = message.toLowerCase();
      if (lowerMessage.includes('restore')) {
        buttonText = window.localMessagesUpdate?.messages?.yes_restore_it || 'Yes, restore it!';
      } else if (lowerMessage.includes('change') && lowerMessage.includes('status')) {
        buttonText = window.localMessagesUpdate?.messages?.yes_do_it || 'Yes, do it!';
      } else {
        buttonText = window.localMessagesUpdate?.messages?.yes_delete_it || 'Yes, delete it!';
      }
    } else {
      buttonText = window.localMessagesUpdate?.messages?.yes_delete_it || 'Yes, delete it!';
    }

    // Same icon for change-status in both cases (active/inactive) – plain exclamation, no circle
    const iconHtml = (actionType === 'change-status' || actionType === 'change-featured')
      ? '<i class="fa fa-exclamation fa-1x" style="color:#f0ad4e;"></i>'
      : '<i class="fa fa-trash" style="color:#A52A2A;"></i>';

    return await Swal.fire({
      title: message,
      text: '',
      icon: 'warning',
      iconHtml: iconHtml,
      showCancelButton: true,
      confirmButtonColor: '#A52A2A',
      cancelButtonColor: '#6c757d',
      confirmButtonText: buttonText,
      cancelButtonText: window.localMessagesUpdate?.messages?.cancel || 'Cancel',
      reverseButtons: true,
      showClass: {
        popup: 'animate__animated animate__zoomIn'
      },
      hideClass: {
        popup: 'animate__animated animate__zoomOut'
      }
    }).then((result) => {
      return result
    })
  }

  window.confirmSwal = confirmSwal

  $('#quick-action-form').on('submit', function (e) {
    e.preventDefault()
    const form = $(this)
    const url = form.attr('action')
    const actionType = $('[name="action_type"]').val()
    const rowdIds = $("#datatable_wrapper .select-table-row:checked").map(function () {
      return $(this).val();
    }).get();

    let message = $('[name="message_' + actionType + '"]').val()

    const entityName = $('[name="entity_name"]').val()
    const entityNamePlural = $('[name="entity_name_plural"]').val()

    if (entityName && entityNamePlural && message) {
      const isPlural = rowdIds.length > 1
      const entityToUse = isPlural ? entityNamePlural : entityName

      const capitalizedEntity = entityToUse.charAt(0).toUpperCase() + entityToUse.slice(1)

      message = message.replace(/:entity/g, capitalizedEntity)
    }

    // For change-status, replace "selected" with Active/Inactive from the status dropdown
    if (actionType === 'change-status' && message) {
      const statusLabel = $('#status option:selected').text() || ''
      const firstOpt = $('#status').find('option:first').text()
      if (statusLabel && statusLabel !== firstOpt) {
        message = message.replace(/\bthe selected\b/gi, statusLabel)
      }
    }

    confirmSwal(message, actionType).then((result) => {
      if (!result.isConfirmed) return
      callActionAjax({ url: `${url}?rowIds=${rowdIds}`, body: form.serialize() })
    })
  })

  // Update status on switch
  $(document).on('change', '#datatable_wrapper .switch-status-featured', function () {
    let url = $(this).attr('data-url')
    let body = {
      featured: $(this).prop('checked') ? 1 : 0,
      _token: $(this).attr('data-token')
    }
    callActionAjax({ url: url, body: body })
  })

  // Update status on switch
  $(document).on('change', '#datatable_wrapper .switch-status-change', function () {
    let url = $(this).attr('data-url')
    let body = {
      status: $(this).prop('checked') ? 1 : 0,
      _token: $(this).attr('data-token')
    }
    callActionAjax({ url: url, body: body })
  })


  $(document).on('change', '#datatable_wrapper .switch-restricted-change', function () {
    let url = $(this).attr('data-url')
    let body = {
      status: $(this).prop('checked') ? 1 : 0,
      _token: $(this).attr('data-token')
    }
    callActionAjax({ url: url, body: body })
  })

  $(document).on('change', '#datatable_wrapper .change-select', function () {
    let url = $(this).attr('data-url')
    let body = {
      value: $(this).val(),
      _token: $(this).attr('data-token')
    }
    callActionAjax({ url: url, body: body })
  })

  function callActionAjax({ url, body }) {
    $.ajax({
      type: 'POST',
      url: url,
      data: body,
      success: function (res) {
        if (res.status) {
          window.successSnackbar(res.message)
          window.renderedDataTable.ajax.reload(resetActionButtons, false)
          const event = new CustomEvent('update_quick_action', { detail: { value: true } })
          document.dispatchEvent(event)
        } else {
          Swal.fire({
            title: 'Error',
            text: res.message,
            icon: "error",
            showClass: {
              popup: 'animate__animated animate__zoomIn'
            },
            hideClass: {
              popup: 'animate__animated animate__zoomOut'
            }
          })
          // window.errorSnackbar(res.message)
        }
      }
    })
  }

  // Update status on button click
  $(document).on('click', '#datatable_wrapper .button-status-change', function () {

    let url = $(this).attr('data-url')
    let body = {
      status: 1,
      _token: $(this).attr('data-token')
    }
    callActionAjax({ url: url, body: body })
  })

  function callActionAjax({ url, body }) {
    $.ajax({
      type: 'POST',
      url: url,
      data: body,
      success: function (res) {
        if (res.status) {
          window.successSnackbar(res.message)
          window.renderedDataTable.ajax.reload(resetActionButtons, false)
          const event = new CustomEvent('update_quick_action', { detail: { value: true } })
          document.dispatchEvent(event)
        } else {
          window.errorSnackbar(res.message)
        }
      }
    })
  }

  //select row in datatable
  const dataTableRowCheck = (id, source = null) => {
    var dataType = source ? source.getAttribute('data-type') : null;

    checkRow();
    const actionDropdown = document.getElementById('quick-action-type');
    const selectAllEl = document.getElementById('select-all-table');
    if ($(".select-table-row:checked").length > 0) {
      $("#quick-action-form").removeClass('form-disabled');
      //if at-least one row is selected
      if (selectAllEl) {
        selectAllEl.indeterminate = true;
      }
      $("#quick-actions").find("input, textarea, button, select").removeAttr("disabled");
    } else {
      //if no row is selected
      if (selectAllEl) {
        selectAllEl.indeterminate = false;
      }
      $("#select-all-table").attr("checked", false);
      resetActionButtons();
    }

    if ($("#datatable-row-" + id).is(":checked")) {
      $("#row-" + id).addClass("table-active");
    } else {
      $("#row-" + id).removeClass("table-active");
    }

    const rowdIds = $("#datatable_wrapper .select-table-row:checked").map(function () {
      return $(this).val();
    }).get();

    if (actionDropdown && dataType !== null) {

      if (dataType === 'cast-crew' || dataType === 'review' || dataType === 'notifications') {
        if (actionDropdown.options[2] !== undefined) {
          actionDropdown.options[2].disabled = true;  // Restore option
        }
        if (actionDropdown.options[3] !== undefined) {
          actionDropdown.options[3].disabled = true;  // Permanently Delete option
        }
        if (actionDropdown.options[1] !== undefined) {
          actionDropdown.options[1].disabled = false;
        }
      } else {
        if (actionDropdown.options[3] !== undefined) {
          actionDropdown.options[3].disabled = true;  // Restore option
        }
        if (actionDropdown.options[4] !== undefined) {
          actionDropdown.options[4].disabled = true;  // Permanently Delete option
        }
        if (actionDropdown.options[2] !== undefined) {
          actionDropdown.options[2].disabled = false;
        }
      }

    }


    const baseUrl = document.querySelector('meta[name="base-url"]').getAttribute('content').replace(/\/$/, '');
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    $.ajax({
      type: 'POST',
      headers: {
        'X-CSRF-Token': csrfToken,
      },
      url: baseUrl + "/app/check-in-trash",
      data: { ids: rowdIds, datatype: dataType },
      success: function (response) {
        // Cache the result
        trashStatusCache = {
          ids: rowdIds,
          dataType: dataType,
          allInTrash: response.all_in_trash == true,
          noneInTrash: response.none_in_trash == true,
          hasMixed: response.has_mixed == true,
          timestamp: Date.now()
        };
        
        // Apply dropdown state based on the three cases
        applyDropdownState(actionDropdown, dataType, response);
      }
    });
    checkRow();
  };
  window.dataTableRowCheck = dataTableRowCheck

  const selectAllTable = (source) => {
    var dataType = source.getAttribute('data-type');
    const checkboxes = document.getElementsByName("datatable_ids[]");
    const actionDropdown = document.getElementById('quick-action-type');
    const selectedIds = [];
    for (var i = 0, n = checkboxes.length; i < n; i++) {
      // if disabled property is given to checkbox, it won't select particular checkbox.
      if (!$("#" + checkboxes[i].id).prop('disabled')) {
        checkboxes[i].checked = source.checked;
        if (checkboxes[i].checked) {
          selectedIds.push(checkboxes[i].value);
        } else {
          const selectAllHeader = document.getElementById("select-all-table");
          if (selectAllHeader) {
            selectAllHeader.indeterminate = false;
          }
          $("#select-all-table").attr("checked", false);
          resetActionButtons();
        }
      }
      // if ($("#" + checkboxes[i].id).is(":checked")) {
      //     $("#" + checkboxes[i].id)
      //         .closest("tr")
      //         .addClass("table-active");
      //     $("#quick-actions")
      //         .find("input, textarea, button, select")
      //         .removeAttr("disabled");
      //     if ($("#quick-action-type").val() == "") {
      //         $("#quick-action-apply").attr("disabled", true);
      //       }
      // } else {
      //     $("#" + checkboxes[i].id)
      //         .closest("tr")
      //         .removeClass("table-active");
      //     resetActionButtons();
      // }
    }
    if (actionDropdown && dataType !== null) {
      if ( dataType === 'review' || dataType === 'notifications') { //dataType === 'cast-crew' ||

        if (actionDropdown.options[2] !== undefined) {
          actionDropdown.options[2].disabled = true;  // Restore option
        }
        if (actionDropdown.options[3] !== undefined) {
          actionDropdown.options[3].disabled = true;  // Permanently Delete option
        }
        if (actionDropdown.options[1] !== undefined) {
          actionDropdown.options[1].disabled = false;
        }
      } else {
        if (actionDropdown.options[3] !== undefined) {
          actionDropdown.options[3].disabled = true;  // Restore option
        }
        if (actionDropdown.options[4] !== undefined) {
          actionDropdown.options[4].disabled = true;  // Permanently Delete option
        }
        if (actionDropdown.options[2] !== undefined) {
          actionDropdown.options[2].disabled = false;
        }
      }
    }

    const baseUrl = document.querySelector('meta[name="base-url"]').getAttribute('content').replace(/\/$/, '');
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    $.ajax({
      type: 'POST',
      headers: {
        'X-CSRF-Token': csrfToken,
      },
      url: baseUrl + "/app/check-in-trash",
      data: { ids: selectedIds, datatype: dataType },
      success: function (response) {
        // Cache the result
        trashStatusCache = {
          ids: selectedIds,
          dataType: dataType,
          allInTrash: response.all_in_trash == true,
          noneInTrash: response.none_in_trash == true,
          hasMixed: response.has_mixed == true,
          timestamp: Date.now()
        };
        
        // Apply dropdown state based on the three cases
        applyDropdownState(actionDropdown, dataType, response);
      }
    });


    checkRow();
  };


  window.selectAllTable = selectAllTable

  const checkRow = () => {
    const selectAllHeader = document.getElementById("select-all-table");
    if ($(".select-table-row:checked").length > 0) {
      $("#quick-action-type").prop('disabled', false);
      $("#quick-action-form").removeClass('form-disabled');
      // $("#quick-action-apply").removeClass("btn-primary").addClass("btn-primary");
    } else {
      $("#quick-action-type").prop('disabled', true);
      $("#quick-action-form").addClass('form-disabled');
      if (selectAllHeader) {
        selectAllHeader.indeterminate = false;
      }
      // $("#quick-action-apply").removeClass("btn-primary").addClass("btn-primary");
    }
  }

  window.checkRow = checkRow

  //reset table action form elements
  const resetActionButtons = () => {
    checkRow()
    const quickActionForm = $("#quick-action-form")[0];
    if (document.getElementById("select-all-table") !== undefined && document.getElementById("select-all-table") !== null) {
      document.getElementById("select-all-table").checked = false;
      if (quickActionForm !== undefined && quickActionForm !== null) {
        quickActionForm.reset();  // Only reset if the form exists
      }
      $("#quick-actions")
        .find("input, textarea, button, select")
        .attr("disabled", "disabled");
      $("#quick-action-form").find("select").select2("destroy").select2().val(null).trigger("change")
    }
  };

  window.resetActionButtons = resetActionButtons

  // Cache for trash status to avoid duplicate AJAX calls
  let trashStatusCache = {
    ids: [],
    dataType: null,
    allInTrash: null,
    noneInTrash: null,
    hasMixed: null,
    timestamp: 0
  };

  // Function to check trash status and update dropdown options
  const updateBulkActionDropdownState = function(sync = false) {
    const actionDropdown = document.getElementById('quick-action-type');
    if (!actionDropdown) return;

    const selectedIds = $(".select-table-row:checked").map(function () {
      return $(this).val();
    }).get();

    if (selectedIds.length === 0) return;

    // Get data type from first checked checkbox
    const firstChecked = $(".select-table-row:checked").first();
    const dataType = firstChecked.data('type') || firstChecked.attr('data-type');

    if (!dataType) return;

    // Check cache first (if cache is recent and matches current selection)
    const idsMatch = selectedIds.length === trashStatusCache.ids.length && 
                     selectedIds.every(id => trashStatusCache.ids.includes(id));
    const typeMatch = dataType === trashStatusCache.dataType;
    const cacheValid = (Date.now() - trashStatusCache.timestamp) < 5000; // 5 second cache

    if (idsMatch && typeMatch && cacheValid && trashStatusCache.allInTrash !== null) {
      // Use cached result immediately - reconstruct response object from cache
      const cachedResponse = {
        all_in_trash: trashStatusCache.allInTrash,
        none_in_trash: trashStatusCache.noneInTrash,
        has_mixed: trashStatusCache.hasMixed
      };
      applyDropdownState(actionDropdown, dataType, cachedResponse);
      return;
    }

    // Set initial state (disable restore/permanently delete)
    if ( dataType === 'review' || dataType === 'notifications') { //dataType === 'cast-crew' ||
      if (actionDropdown.options[2] !== undefined) {
        actionDropdown.options[2].disabled = true;  // Restore option
      }
      if (actionDropdown.options[3] !== undefined) {
        actionDropdown.options[3].disabled = true;  // Permanently Delete option
      }
      if (actionDropdown.options[1] !== undefined) {
        actionDropdown.options[1].disabled = false;
      }
    } else {
      if (actionDropdown.options[3] !== undefined) {
        actionDropdown.options[3].disabled = true;  // Restore option
      }
      if (actionDropdown.options[4] !== undefined) {
        actionDropdown.options[4].disabled = true;  // Permanently Delete option
      }
      if (actionDropdown.options[2] !== undefined) {
        actionDropdown.options[2].disabled = false;
      }
    }

    // Check trash status via AJAX
    const baseUrl = document.querySelector('meta[name="base-url"]').getAttribute('content').replace(/\/$/, '');
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    $.ajax({
      type: 'POST',
      async: !sync, // Make synchronous when dropdown opens
      headers: {
        'X-CSRF-Token': csrfToken,
      },
      url: baseUrl + "/app/check-in-trash",
      data: { ids: selectedIds, datatype: dataType },
      success: function (response) {
        // Cache the result
        trashStatusCache = {
          ids: selectedIds,
          dataType: dataType,
          allInTrash: response.all_in_trash == true,
          noneInTrash: response.none_in_trash == true,
          hasMixed: response.has_mixed == true,
          timestamp: Date.now()
        };
        
        // Apply the state
        applyDropdownState(actionDropdown, dataType, response);
      }
    });
  };

  // Function to apply dropdown state based on three cases
  const applyDropdownState = function(actionDropdown, dataType, response) {
    if (!actionDropdown) {
      return;
    }
    const allInTrash = response.all_in_trash == true;
    const noneInTrash = response.none_in_trash == true;
    const hasMixed = response.has_mixed == true;
    
    // Case 3: Only soft-deleted records selected
    if (allInTrash) {
      if ( dataType === 'review' || dataType === 'notifications') { //dataType === 'cast-crew' ||
        if (actionDropdown.options[2] !== undefined) {
          actionDropdown.options[2].disabled = false;  // Restore option - ENABLED
        }
        if (actionDropdown.options[3] !== undefined) {
          actionDropdown.options[3].disabled = false;  // Permanently Delete option - ENABLED
        }
        if (actionDropdown.options[1] !== undefined) {
          actionDropdown.options[1].disabled = true;  // Status option - DISABLED
        }
      } else {
        if (actionDropdown.options[3] !== undefined) {
          actionDropdown.options[3].disabled = false; // Restore option - ENABLED
        }
        if (actionDropdown.options[4] !== undefined) {
          actionDropdown.options[4].disabled = false; // Permanently Delete option - ENABLED
        }
        if (actionDropdown.options[2] !== undefined) {
          actionDropdown.options[2].disabled = true;  // Delete option - DISABLED
        }
        if (actionDropdown.options[1] !== undefined) {
          actionDropdown.options[1].disabled = true;  // Status option - DISABLED
        }
      }
    }
    // Case 1: Mixed selection (both soft-deleted and not soft-deleted)
    else if (hasMixed) {
      if ( dataType === 'review' || dataType === 'notifications') { //dataType === 'cast-crew' ||
        if (actionDropdown.options[1] !== undefined) {
          actionDropdown.options[1].disabled = true;  // Status option - DISABLED
        }
        if (actionDropdown.options[2] !== undefined) {
          actionDropdown.options[2].disabled = true;  // Restore option - DISABLED
        }
        if (actionDropdown.options[3] !== undefined) {
          actionDropdown.options[3].disabled = true;  // Permanently Delete option - DISABLED
        }
        // Note: Review/notifications don't have a separate Delete option, so nothing to enable
      } else {
        if (actionDropdown.options[1] !== undefined) {
          actionDropdown.options[1].disabled = false; // Status option - ENABLED
        }
        if (actionDropdown.options[2] !== undefined) {
          actionDropdown.options[2].disabled = false; // Delete option - ENABLED
        }
        if (actionDropdown.options[3] !== undefined) {
          actionDropdown.options[3].disabled = true;  // Restore option - DISABLED
        }
        if (actionDropdown.options[4] !== undefined) {
          actionDropdown.options[4].disabled = true;  // Permanently Delete option - DISABLED
        }
      }
    }
    // Case 2: Only not soft-deleted records selected
    else if (noneInTrash) {
      if ( dataType === 'review' || dataType === 'notifications') { //dataType === 'cast-crew' ||
        if (actionDropdown.options[1] !== undefined) {
          actionDropdown.options[1].disabled = false; // Status option - ENABLED
        }
        if (actionDropdown.options[2] !== undefined) {
          actionDropdown.options[2].disabled = true;  // Restore option - DISABLED
        }
        if (actionDropdown.options[3] !== undefined) {
          actionDropdown.options[3].disabled = true;  // Permanently Delete option - DISABLED
        }
        // Note: Review/notifications don't have a separate Delete option
      } else {
        if (actionDropdown.options[1] !== undefined) {
          actionDropdown.options[1].disabled = false; // Status option - ENABLED
        }
        if (actionDropdown.options[2] !== undefined) {
          actionDropdown.options[2].disabled = false; // Delete option - ENABLED
        }
        if (actionDropdown.options[3] !== undefined) {
          actionDropdown.options[3].disabled = true;  // Restore option - DISABLED
        }
        if (actionDropdown.options[4] !== undefined) {
          actionDropdown.options[4].disabled = true;  // Permanently Delete option - DISABLED
        }
      }
    }
  };

  window.updateBulkActionDropdownState = updateBulkActionDropdownState;

  // Update dropdown state when Select2 dropdown is about to open
  $(document).on('select2:opening', '#quick-action-type', function(e) {
    // Check trash status synchronously before dropdown opens to ensure correct state
    updateBulkActionDropdownState(true);
  });

  //  const initDatatable = ({url, finalColumns, advanceFilter, drawCallback = undefined, orderColumn}) => {

  //     const data_table_limit = $('meta[name="data_table_limit"]').attr('content');

  //     window.renderedDataTable = $('#datatable').DataTable({
  //         processing: true,
  //         serverSide: true,
  //         autoWidth: false,
  //         responsive: true,
  //         fixedHeader: true,
  //         order: orderColumn,
  //         pageLength: data_table_limit,
  //         dom: '<"table-responsive my-3 mt-3 mb-5 pb-1" rt>' +  // Table without search
  //              '<"row"<"col-md-6" info><"col-md-6" p>>' +  // Removed length menu (l) and search (f)
  //              '<"clear">',
  //         ajax: {
  //             "type": "GET",
  //             "url": url,
  //             "data": function(d) {
  //                 d.search = {
  //                     value: $('.dt-search').val()
  //                 };
  //                 d.filter = {
  //                     column_status: $('#column_status').val()
  //                 }
  //                 if (typeof advanceFilter == 'function' && advanceFilter() !== undefined) {
  //                     d.filter = { ...d.filter, ...advanceFilter() }
  //                 }
  //             },
  //         },
  //         drawCallback: function() {
  //             if (laravel !== undefined) {
  //                 window.laravel.initialize();
  //             }
  //             $('.select2').select2();
  //             if (drawCallback !== undefined && typeof drawCallback == 'function') {
  //                 drawCallback();
  //             }
  //         },
  //         columns: finalColumns,
  //         infoCallback: function(settings, start, end, max, total, pre) {
  //             const info = `Showing ${start} to ${end} of ${total} entries`;

  //             // Update the info display at the bottom
  //             $('.data_table_widgets .info').html(info);

  //             return info;
  //         },
  //     });

  //     // Hide the search box by adding d-none class
  //     $('.dataTables_filter').addClass('d-none');
  // }

  const initDatatable = ({
    url,
    finalColumns,
    advanceFilter,
    drawCallback = undefined,
    orderColumn,
    responsive = true,
    fixedHeader = true,
    autoWidth = false,
    scrollX = false,
  }) => {

    const data_table_limit = parseInt($('meta[name="data_table_limit"]').attr('content'), 10);
    const default_date_format = $('meta[name="default_date_format"]').attr('content') || 'jS F Y';

    // Global date formatting function for tables
    window.formatDate = function (dateString) {
      if (!dateString || dateString === '-') return '-';

      const date = new Date(dateString);
      if (isNaN(date.getTime())) return '-';

      // Convert PHP date format to JavaScript format
      const formatMap = {
        'Y-m-d': 'yyyy-MM-dd',
        'm-d-Y': 'MM-dd-yyyy',
        'd-m-Y': 'dd-MM-yyyy',
        'd/m/Y': 'dd/MM/yyyy',
        'm/d/Y': 'MM/dd/yyyy',
        'Y/m/d': 'yyyy/MM/dd',
        'Y.m.d': 'yyyy.MM.dd',
        'd.m.Y': 'dd.MM.yyyy',
        'm.d.Y': 'MM.dd.yyyy',
        'jS F Y': 'd\'S\' MMMM yyyy',
        'M jS Y': 'MMM d\'S\' yyyy',
        'D, M d, Y': 'EEE, MMM d, yyyy',
        'D, d M, Y': 'EEE, d MMM, yyyy',
        'D, M jS Y': 'EEE, MMM d\'S\' yyyy',
        'D, jS M Y': 'EEE, d\'S\' MMM yyyy',
        'F j, Y': 'MMMM d, yyyy',
        'd F, Y': 'd MMMM, yyyy',
        'jS F, Y': 'd\'S\' MMMM, yyyy',
        'l jS F Y': 'EEEE d\'S\' MMMM yyyy',
        'l, F j, Y': 'EEEE, MMMM d, yyyy'
      };

      const jsFormat = formatMap[default_date_format] || 'd\'S\' MMMM yyyy';

      try {
        return new Intl.DateTimeFormat('en-US', {
          year: 'numeric',
          month: jsFormat.includes('MMMM') ? 'long' : jsFormat.includes('MMM') ? 'short' : '2-digit',
          day: jsFormat.includes('d\'S\'') ? 'numeric' : '2-digit',
          weekday: jsFormat.includes('EEE') ? 'short' : jsFormat.includes('EEEE') ? 'long' : undefined
        }).format(date);
      } catch (e) {
        // Fallback to simple format
        return date.toLocaleDateString();
      }
    };


    window.renderedDataTable = $('#datatable').DataTable({
      processing: true,
      serverSide: true,
      autoWidth,
      scrollX,
      responsive,
      fixedHeader,
      order: orderColumn,
      pageLength: data_table_limit,
      lengthMenu: [[10, 20, 25, 50, 100], [10, 20, 25, 50, 100]],
      language: {
        processing: window.localMessagesUpdate?.messages?.processing || "Processing...",
        emptyTable: window.localMessagesUpdate?.messages?.emptyTable || "No data available in table",
        zeroRecords: window.localMessagesUpdate?.messages?.zeroRecords || "No matching records found",
        paginate: {
          previous: window.localMessagesUpdate?.messages?.previous || "Previous",
          next: window.localMessagesUpdate?.messages?.next || "Next",
        },
        lengthMenu: window.localMessagesUpdate?.messages?.show + " _MENU_ " + window.localMessagesUpdate?.messages?.entries || "Show _MENU_ entries",
      },
      dom: '<"row align-items-center"><"table-responsive my-3 mt-3 mb-2 pb-1" rt><"row align-items-center data_table_widgets" <"col-md-6" <"d-flex align-items-center flex-wrap gap-3" l i>><"col-md-6" p>><"clear">',
      ajax: {
        type: "GET",
        url: url,
        data: function (d) {
          // Prefer the listing toolbar search inside main content (avoids wrong field if multiple .dt-search exist).
          const $scoped = $('#page_layout .dt-search')
          const searchVal =
            $scoped.length > 0 ? String($scoped.first().val() ?? '') : String($('.dt-search').first().val() ?? '')
          d.search = Object.assign({}, typeof d.search === 'object' && d.search !== null ? d.search : {}, {
            value: searchVal,
            regex: false,
          })
          d.filter = {
            column_status: $('#column_status').val()
          };
          if (typeof advanceFilter === 'function' && advanceFilter() !== undefined) {
            d.filter = { ...d.filter, ...advanceFilter() };
          }
        },
      },
      drawCallback: function () {
        if (typeof window.laravel !== 'undefined') {
          window.laravel.initialize();
        }
        $('#datatable_wrapper .select2').each(function () {
          const $select = $(this);
          if (!$select.hasClass('select2-hidden-accessible')) {
            $select.select2();
          }
        });

        $('#datatable_wrapper').find('.dataTables_info').addClass('p-0');
        if (typeof drawCallback === 'function') {
          drawCallback();
        }
        try {
          $(this).DataTable().columns.adjust();
        } catch (e) {}
      },
      columns: finalColumns,
      infoCallback: function (settings, start, end, max, total, pre) {
        const showing = window.localMessagesUpdate?.messages?.showing || "Showing";
        const of = window.localMessagesUpdate?.messages?.of || "of";
        const entries = window.localMessagesUpdate?.messages?.entries || "entries";
        const info = `${showing} ${start} to ${end} ${of} ${total} ${entries}`;

        // Update the info display at the bottom
        $('.data_table_widgets .info').html(info);

        return info;
      },
    });

    // Hide the search box by adding d-none class
    $('.dataTables_filter').addClass('d-none');
  }

  window.initDatatable = initDatatable;


  window.initDatatable = initDatatable

  function formatCurrency(number, noOfDecimal, decimalSeparator, thousandSeparator, currencyPosition, currencySymbol) {
    // Convert the number to a string with the desired decimal places
    let formattedNumber = number.toFixed(noOfDecimal)

    // Split the number into integer and decimal parts
    let [integerPart, decimalPart] = formattedNumber.split('.')

    // Add thousand separators to the integer part
    integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, thousandSeparator)

    // Set decimalPart to an empty string if it is undefined
    decimalPart = decimalPart || ''

    // Construct the final formatted currency string
    let currencyString = ''

    if (currencyPosition === 'left' || currencyPosition === 'left_with_space') {
      currencyString += currencySymbol
      if (currencyPosition === 'left_with_space') {
        currencyString += ' '
      }
      currencyString += integerPart
      // Add decimal part and decimal separator if applicable
      if (noOfDecimal > 0) {
        currencyString += decimalSeparator + decimalPart
      }
    }

    if (currencyPosition === 'right' || currencyPosition === 'right_with_space') {
      // Add decimal part and decimal separator if applicable
      if (noOfDecimal > 0) {
        currencyString += integerPart + decimalSeparator + decimalPart
      }
      if (currencyPosition === 'right_with_space') {
        currencyString += ' '
      }
      currencyString += currencySymbol
    }

    return currencyString
  }

  window.formatCurrency = formatCurrency

})()
