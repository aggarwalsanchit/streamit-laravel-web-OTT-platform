import {mergeWith} from 'lodash'
import * as moment from 'moment'

export const XSRF_REQUEST_HEADER = () => {
    const csrfToken = document.head.querySelector("[name~=csrf-token][content]").content;
    return {
        "X-CSRF-Token": csrfToken
    }
}
export const JSON_REQUEST_HEADER = () =>{
    return {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...XSRF_REQUEST_HEADER()
    }
}

export const createRequest = async (URL,header, bodyData = {}, options = {}) => {
    let headerMerged = mergeWith(JSON_REQUEST_HEADER(), header)
    let response
    switch (URL.method) {
        case 'GET':
            response = await fetch(URL.path, {headers: headerMerged});
            return response.json()

        case 'POST':
        case 'PUT':
        case "PATCH":
            response = await fetch(URL.path, {method: URL.method, body: JSON.stringify(bodyData), headers: headerMerged, ...options});
            return response.json()

        case 'DELETE':
          response = await fetch(URL.path, { method: 'DELETE', headers: headerMerged });
          return response.json();

        default:
            break;
    }
    return false
}

export const createRequestWithFormData = async (URL, header, bodyData, options = {}) => {
    let headerMerged = mergeWith(XSRF_REQUEST_HEADER(), header)
    let response

    switch (URL.method) {
        case "POST":
        case "PUT":
        case "PATCH":
            response = await fetch(URL.path, {
                method: URL.method,
                headers: headerMerged,
                body: bodyData,
                ...options
            });
            return response.json()
            break;
    }
}

export const readFile = (file, callback) => {
    let reader = new FileReader();
    reader.addEventListener('load', () => {
        callback(reader.result)
    })
    reader.readAsDataURL(file);
}

export const buildMultiSelectObject = (arr, {value, label}) => {
    return arr.map((item) => { return {value: item[value], label:item[label]}})
}

export const startTime = (value) => {
  return moment(value).format('YYYY-MM-DDTHH:mm')
}

export const endTime = (value, addTime) => {
  return moment(value).add(addTime, 'minutes').format('YYYY-MM-DDTHH:mm')
}

export const confirmSwal = async ({title, confirmButtonText = null}) => {
    // Determine button text based on action type if not provided
    let buttonText = confirmButtonText;
    if (!buttonText) {
      // Check if title contains "restore" to use restore button text
      if (title && typeof title === 'string' && title.toLowerCase().includes('restore')) {
        buttonText = window.localMessagesUpdate?.messages?.yes_restore_it || 'Yes, restore it!';
      } else {
        buttonText = window.localMessagesUpdate?.messages?.yes_delete_it || 'Yes, delete it!';
      }
    }
    
    return await Swal.fire({
        title: title,
        text: '',
        icon: 'warning',
        iconHtml: '<i class="fa fa-trash" style="color:#A52A2A;"></i>',
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

export const confirmcancleSwal = async ({title}) => {
    return await Swal.fire({
        title: title,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#858482',
        confirmButtonText: window.localMessagesUpdate?.messages?.yes_do_it || 'Yes, do it!',
        cancelButtonText: window.localMessagesUpdate?.messages?.cancel || 'Cancel',
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
