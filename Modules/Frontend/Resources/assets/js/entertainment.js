
import Snackbar from 'node-snackbar';
import 'node-snackbar/dist/snackbar.css';
const PRIMARY_COLOR = window.getComputedStyle(document.querySelector('html')).getPropertyValue('--bs-success').trim()

function loadData() {
  if(!hasMore){
    shimmerContainer.style.display = 'none';
  }
  if (isLoading || !hasMore) return;
  isLoading = true;

  const watchListPresent = typeof emptyWatchList !== 'undefined' ? !!emptyWatchList : null;
  let currentSort = typeof currentSortType !== 'undefined' ? currentSortType : '';
  const sortParam = currentSort === 'top_star' ? '&sort=top_star' : '';



  const actorIdParam = actor_id ? `&actor_id=${actor_id}` : '';
  const typeParam = type ? `&type=${type}` : '';
  const movieIDParam = movie_id ? `&movie_id=${movie_id}` : '';

  fetch(`${apiUrl}?page=${currentPage}&is_ajax=1&per_page=${per_page}${actorIdParam}${typeParam}${movieIDParam}${sortParam}`)
      .then(response => response.json())
      .then(data => {
          if (data?.html) {
              EntertainmentList.insertAdjacentHTML(currentPage === 1 ? 'afterbegin' : 'beforeend', data.html);
              if (window.initTrailerHover) window.initTrailerHover();
              hasMore = !!data.hasMore;

              if (hasMore) currentPage++;
              if (watchListPresent) {
                emptyWatchList.style.display = 'none';
            }
            shimmerContainer.style.display = 'none';
              initializeWatchlistButtons();
              initializeRemaindButtons();
              intializeremoveButton()
          } else {
            shimmerContainer.innerHTML = '';
            hasMore = !!data.hasMore;
            const noDataImage = document.createElement('img');
            noDataImage.src = noDataImageSrc;
            noDataImage.alt = 'No Data Found';
            pageTitle.classList.add('d-none');
            noDataImage.style.display = 'block';
            noDataImage.style.margin = '0 auto';

            shimmerContainer.appendChild(noDataImage);
              console.error('Invalid data from the API');
          }
      })
      .catch(error => console.error('Fetch error:', error))
      .finally(() => {

        // Hide the shimmer effect after loading completes
        isLoading = false;
    });
}

function handleScroll() {
  if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 500) {
    if(hasMore){
      shimmerContainer.style.display = '';
      loadData();
    }

  }
}

// Watchlist clicks are handled by a single delegated handler in master layout (no duplicate API calls).
// Only (re-)initialize tooltips for watchlist buttons after new content is loaded.
function initializeWatchlistButtons() {
  $('[data-bs-toggle="tooltip"]').tooltip();
}


function initializeRemaindButtons() {
      $('.remind-btn').off('click').on('click',function() {

          var $this = $(this);
          var isInremindlist = $this.data('in-remindlist');
          var entertainmentId = $this.data('entertainment-id');
          let action = isInremindlist == '1' ? 'delete' : 'save';
          var data = isInremindlist
              ? { is_remind:0,id: [entertainmentId], _token: csrf_token }
              : { is_remind:1,entertainment_id: entertainmentId, _token: csrf_token };
          $.ajax({
              url: action === 'save' ? `${baseUrl}/api/save-reminder` : `${baseUrl}/api/delete-reminder?is_ajax=1`,
              method: 'POST',
              data: data,
              success: function(response) {
                Snackbar.show({
                  text:  response.message || 'Default message',
                  pos: 'bottom-left',
                  actionTextColor: PRIMARY_COLOR,
                  actionText: window.localMessagesUpdate?.messages?.dismiss || 'Dismiss',
                  duration: 2500
              })
                  $this.find('i').toggleClass('ph-fill');
                  $this.toggleClass('btn-primary btn-dark');
                  $this.data('in-remindlist', !isInremindlist);

                  var newInRemind = !isInremindlist ? 'true' : 'false';
                  var newTooltip = newInRemind === 'true' ? 'Remove Reminder' : 'Add Reminder';

                  // Destroy the current tooltip
                  $this.tooltip('dispose');

                  // Update the tooltip attribute
                  $this.attr('data-bs-title', newTooltip);
              },
              error: function(xhr) {
                  if (xhr.status === 401) {

                    window.location.href = `${baseUrl}/login`;

                  } else {
                      console.error(xhr);
                  }
              }
          });
      });
}

function intializeremoveButton() {
  $('.continue_remove_btn')
    .off('click')
    .on('click', function () {
      const button = this
      const itemId = button.getAttribute('data-id')
      const itemName = button.getAttribute('data-name')

      Swal.fire({
        title: window.localMessagesUpdate?.messages?.are_you_sure || 'Are you sure?',
        text: window.localMessagesUpdate?.frontend?.continue_watch_remove_with_name?.replace(':name', itemName) || `Do you want to remove "${itemName}" from Continue Watching?`,
        showCancelButton: true,
        confirmButtonColor: 'var(--bs-primary)',
        cancelButtonColor: '#22292E',
        confirmButtonText: window.localMessagesUpdate?.messages?.yes_delete_it || 'Yes, delete it!',
        cancelButtonText: window.localMessagesUpdate?.messages?.cancel || 'Cancel',
        background: '#1e1e1e',
        reverseButtons: true,
        color: '#ffffff',
        customClass: {
          popup: 'swal2-dark',
          title: 'swal2-title-dark',
          confirmButton: 'swal2-confirm-dark',
          cancelButton: 'swal2-cancel-dark'
        }
      }).then((result) => {
        if (!result.isConfirmed) return

        const data = {
          id: itemId,
          _token: csrf_token
        }

        fetch(`${baseUrl}/api/delete-continuewatch`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(data)
        })
          .then(async (response) => {
            if (!response.ok) return

            const json = await response.json()

            Snackbar.show({
              text: json.message || 'Default message',
              pos: 'bottom-left',
              actionTextColor: PRIMARY_COLOR,
              actionText: window.localMessagesUpdate?.messages?.dismiss || 'Dismiss',
              duration: 2500
            })

            button.closest('.continue-watch-card').remove()
          })
          .catch((error) => console.error('Error:', error))
      })
    })
}

  const sortDropdown = document.getElementById('sort-reviews');
  if (sortDropdown) {
      sortDropdown.addEventListener('change', (event) => {
          currentSortType = event.target.value;
          currentPage = 1; // Reset to the first page
          hasMore = true; // Reset the hasMore flag
          EntertainmentList.innerHTML = ''; // Clear current reviews
          loadData(); // Load data with the updated sorting state
      });
  }


document.addEventListener('DOMContentLoaded', () => {

  loadData();  // Load the first page of movies
  window.addEventListener('scroll', handleScroll);  // Attach scroll listener
  initializeWatchlistButtons();
  initializeRemaindButtons();
  intializeremoveButton()
});

