import videojs from 'video.js'
import 'videojs-contrib-ads'
import 'video.js/dist/video-js.css'
import 'videojs-youtube'

const Button = videojs.getComponent('Button');
let currentEpisodeId = null;
let currentEntertainmentId = null;
let currentEntertainmentType = null;
let currentEpisodeName = null; // Variable to store current episode name
// Global flag to track if episode video successfully started playing
let episodeVideoPlaybackStarted = false;


try {
  if (!videojs.getComponent('SkipIntroButton')) {
    // console.log('[SkipIntro][global] registering component');
    const ButtonComp = videojs.getComponent('Button');
    class SkipIntroButton extends ButtonComp {
      constructor(player, options) {
        super(player, options);
        this.controlText(typeof skipIntroText !== 'undefined' ? skipIntroText : 'Skip Intro');
        this.addClass('vjs-skip-intro-button');
        this.player_ = player;
        const parse = (hms) => {
          if (!hms) return NaN;
          const p = String(hms).split(':').map(Number);
          if (p.length === 3) return p[0] * 3600 + p[1] * 60 + p[2];
          if (p.length === 2) return p[0] * 60 + p[1];
          return Number(hms);
        };
        this.start = parse(options?.start);
        this.end = parse(options?.end);
        this.on('click', () => {
          if (isFinite(this.end)) {
            this.player_.currentTime(this.end);
            this.hide();
          }
        });
        this._onTime = () => {
          const t = this.player_.currentTime();
          if (isFinite(this.start) && isFinite(this.end) && t >= this.start && t < this.end) {
            this.show();
          } else {
            this.hide();
          }
        };
        this.player_.on('timeupdate', this._onTime);
        this.hide();
      }
      createEl() {
        const el = super.createEl('button', {
          innerHTML: '<span style="display:inline-flex;align-items:center;gap:8px;"><span style="display:inline-block;width:0;height:0;border-left:8px solid #fff;border-top:6px solid transparent;border-bottom:6px solid transparent"></span><span>' + (typeof skipIntroText !== 'undefined' ? skipIntroText : 'Skip Intro') + '</span></span>',
          className: 'vjs-skip-intro-button vjs-control vjs-button'
        });
        return el;
      }
      dispose() {
        if (this.player_ && this._onTime) {
          this.player_.off('timeupdate', this._onTime);
        }
        super.dispose();
      }
    }
    videojs.registerComponent('SkipIntroButton', SkipIntroButton);
    // console.log('[SkipIntro][global] registered');
  } else {
    // console.log('[SkipIntro][global] already registered');
  }
} catch (e) { console.error('[SkipIntro][global] registration error:', e); }

// Create a custom button class

document.addEventListener('DOMContentLoaded', function () {
  const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');

  const player = videojs('videoPlayer', {
    techOrder: ['vimeo', 'youtube', 'html5', 'hls', 'embed'],
    autoplay: false,
    controls: true,
    controlBar: {
      subsCapsButton: {
        textTrackSettings: false // Disable "captions settings"
      }
    }
  });

  function hideLoadingSpinner() {
    try {
      const root = player && player.el ? player.el() : null;
      if (!root) return;
      const spinner = root.querySelector('.vjs-loading-spinner');
      if (spinner) {
        spinner.style.display = 'none';
        spinner.classList.add('vjs-hidden');
      }
      try { player.removeClass('vjs-waiting'); } catch (error) { console.error('Error removing class:', error); }
    } catch (error) { console.error('Error hiding loading spinner:', error); }
  }

  ['ended'].forEach(evt => {
    try { player.on(evt, hideLoadingSpinner); } catch (error) { console.error('Error adding event listener:', error); }
  });

  // Fix progress bar when fast-forwarding trailer to end
  player.on('seeked', function() {
    try {
      const currentTime = player.currentTime();
      const duration = player.duration();
      
      // If we're at or very close to the end (within 0.5 seconds), update progress bar
      if (duration > 0 && currentTime >= duration - 0.5) {
        // Force update progress bar by triggering timeupdate
        player.trigger('timeupdate');
        
        // Also ensure progress bar is at 100% by directly setting it if needed
        const progressBar = player.el().querySelector('.vjs-play-progress');
        if (progressBar && duration > 0) {
          const percent = Math.min(100, (duration / duration) * 100);
          progressBar.style.width = percent + '%';
        }
      }
    } catch (error) {
      console.error('Error updating progress bar on seek:', error);
    }
  });

  // Also handle when video reaches the end
  player.on('ended', function() {
    try {
      // Force progress bar to 100% when video ends
      const progressBar = player.el().querySelector('.vjs-play-progress');
      if (progressBar) {
        progressBar.style.width = '100%';
      }
      
      // Trigger timeupdate to ensure UI is updated
      player.trigger('timeupdate');
    } catch (error) {
      console.error('Error updating progress bar on end:', error);
    }
  });

  // Handle timeupdate to ensure progress bar updates correctly near the end
  player.on('timeupdate', function() {
    try {
      const currentTime = player.currentTime();
      const duration = player.duration();
      
      // If we're at or very close to the end, ensure progress bar is at 100%
      if (duration > 0 && currentTime >= duration - 0.1) {
        const progressBar = player.el().querySelector('.vjs-play-progress');
        if (progressBar) {
          progressBar.style.width = '100%';
        }
      }
    } catch (error) {
      // Silently handle errors to avoid console spam
    }
  });

  function setStartDate(button) {
    if (!button) return;
    const movieAccess = button.getAttribute('data-movie-access');
    const purchaseType = button.getAttribute('data-purchase-type');
    if (movieAccess !== 'pay-per-view' || purchaseType !== 'onetime') return;

    const payload = {
      user_id: button.getAttribute('data-user-id'),
      entertainment_id: button.getAttribute('data-entertainment-id'),
      entertainment_type: button.getAttribute('data-entertainment-type'),
    };

    fetch(`${baseUrl}/pay-per-view/start-date`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      body: JSON.stringify(payload),
    }).catch((error) => console.error('Failed to set PPV start date:', error));
  }

  function mountSkipIntroFrom(button) {
    try {
      // console.log('==================================================', button);
      if (!button || !player) return;
      const s = button.getAttribute('data-start-time');
      const en = button.getAttribute('data-end-time');
      // console.log('==================================================', s, en);
      if (!s || !en) return;

      player.one('loadedmetadata', () => {
        if (!player.__skipIntroMounted && !player.el().querySelector('.vjs-skip-intro-button')) {
          try {
            const SkipIntroCtor = videojs.getComponent('SkipIntroButton');
            const skipIntroInstance = new SkipIntroCtor(player, { start: s, end: en });
            player.controlBar.addChild(skipIntroInstance, {}, 1);
            player.__skipIntroMounted = true;
            player.one('loadstart', () => {
              try { if (skipIntroInstance && skipIntroInstance.dispose) skipIntroInstance.dispose(); } catch (_) { }
              player.__skipIntroMounted = false;
            });
          } catch (err) {
            console.error('SkipIntro addChild error:', err);
          }
        }
      });
    } catch (_) { }
  }

  function stopAllVideoPlayers() {
    // Stop the main player
    if (player) {
      player.pause();
      player.muted(true);
      player.currentTime(0);
    }

    // Stop any video elements that might be playing
    const allVideos = document.querySelectorAll('video');
    allVideos.forEach(video => {
      video.pause();
      video.muted = true;
      video.currentTime = 0;
    });
  }
  const videoEl = document.getElementById('videoPlayer');
  // Detect iOS/iPadOS Safari (covers desktop UA spoofing with touch)
  const isIOS = () => {
    const ua = navigator.userAgent || '';
    const platform = navigator.platform || '';
    const vendor = navigator.vendor || '';
    const isAppleDevice = /iPad|iPhone|iPod/.test(ua);
    const isTouchMac = platform === 'MacIntel' && navigator.maxTouchPoints > 1;
    const isWebkit = /Apple/i.test(vendor) || /WebKit/i.test(ua);
    // Prefer userAgentData when available
    const uaBrandIsIOS = (() => {
      try {
        const uaData = navigator.userAgentData;
        if (!uaData || !uaData.platform) return false;
        const p = uaData.platform.toLowerCase();
        return p === 'ios' || p === 'ipad' || p === 'iphone' || p === 'ipados';
      } catch (_) {
        return false;
      }
    })();
    return uaBrandIsIOS || isAppleDevice || (isTouchMac && isWebkit);
  };

  // Ensure correct inline playback attributes for iOS Safari
  if (videoEl) {
    videoEl.setAttribute('playsinline', '');
    videoEl.setAttribute('webkit-playsinline', '');
    videoEl.setAttribute('preload', 'metadata');
  }
  // if (!videoEl) return;
  // Read variables from data-* attributes
  var contentId = videoEl.getAttribute('data-contentid') || '';
  var contentType = videoEl.getAttribute('data-contenttype') || '';
  var currentCategoryId = videoEl.getAttribute('data-category-id') || '';
  var contentVideoType = videoEl.getAttribute('content-video-type') || '';

  const access = document.querySelector('#videoPlayer').getAttribute('data-movie-access');
  const continueWatch = document.querySelector('#videoPlayer').getAttribute('data-continue-watch') === 'true';
  const videotype = document.querySelector('#videoPlayer').getAttribute('data-contentType');
  const content_type = document.querySelector('#videoPlayer').getAttribute('data-content_type');

  if (contentType != 'livetv') {
    player.ready(async function () {
      if (access === 'pay-per-view' && videotype === 'movie' && contentId) {
        // For pay-per-view movies: Only show skip button if purchased
        try {
          const response = await fetch(`${baseUrl}/api/check-movie-purchase?movie_id=${contentId}`);
          const data = await response.json();
          if (data.is_purchased) {
            const skipButton = new SkipTrainerButton(player, {
              baseUrl: baseUrl // Pass baseUrl to the button
            });
            player.controlBar.addChild(skipButton, {}, player.controlBar.children().length - 1);
          }
        } catch (error) {
          console.error('Error checking movie purchase:', error);
        }
      } else if (access != 'pay-per-view' && !continueWatch) {
        // For non pay-per-view content: Always show skip button (when not continuing watch)
        const skipButton = new SkipTrainerButton(player, {
          baseUrl: baseUrl // Pass baseUrl to the button
        });
        player.controlBar.addChild(skipButton, {}, player.controlBar.children().length - 1);
      }
      const nextButton = new NextEpisodeButton(player);
      player.controlBar.addChild(nextButton, {}, player.controlBar.children().length - 1);
    });
  }

  // iOS Safari fix: clear poster and force repaint as soon as playback starts
  const clearPosterForIOS = () => {
    if (!isIOS()) return;
    try {
      player.poster(''); // clear poster for video.js
      const posterEl = player?.el()?.querySelector('.vjs-poster');
      if (posterEl) posterEl.style.display = 'none';
      // Also clear the underlying tech video element poster
      const techVideo = player?.el()?.querySelector('video');
      if (techVideo) {
        techVideo.removeAttribute('poster');
        techVideo.setAttribute('playsinline', '');
        techVideo.setAttribute('webkit-playsinline', '');
        techVideo.setAttribute('preload', 'metadata');
      }
      if (videoEl) {
        videoEl.removeAttribute('poster');
        videoEl.setAttribute('playsinline', '');
        videoEl.setAttribute('webkit-playsinline', '');
        videoEl.setAttribute('preload', 'metadata');
        // Extra repaint: toggle display to force layer redraw on Safari
        const prevDisplay = videoEl.style.display;
        videoEl.style.display = 'none';
        void videoEl.offsetHeight;
        videoEl.style.display = prevDisplay || 'block';
        // Safari repaint workaround to redraw the video layer
        const prevTransform = videoEl.style.transform;
        videoEl.style.transform = 'translateZ(0)';
        void videoEl.offsetHeight; // force reflow
        videoEl.style.transform = prevTransform;
      }
      // Hide big play overlay if it lingers
      const bigPlay = player?.el()?.querySelector('.vjs-big-play-button');
      if (bigPlay) bigPlay.style.display = 'none';
    } catch (err) {
      console.error('iOS poster cleanup failed:', err);
    }
  };
  player.on('play', clearPosterForIOS);
  player.on('playing', clearPosterForIOS);
  player.on('loadeddata', clearPosterForIOS);
  player.on('loadedmetadata', clearPosterForIOS);
  // Some iOS builds need a short async reflow after play
  player.on('play', () => {
    if (!isIOS()) return;
    setTimeout(clearPosterForIOS, 150);
  });

  const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content')
  let isVideoLoaded = false
  let currentVideoUrl = ''
  let isWatchHistorySaved = false
  let isPopupShown = false // Flag to track if popup/modal was shown

  window.playClip = async function playClip({ url, type }) {
    if (!url) return;
    contentVideoType = 'clip';
    try {
      const stEl = document.querySelector('.vjs-skip-trainer-button.vjs-control');
      if (stEl) stEl.style.display = 'none';
    } catch (error) { console.error('Error hiding skip trainer button:', error); }
    try {
      const siEl = document.querySelector('.vjs-skip-intro-button');
      if (siEl) siEl.style.display = 'none';
    } catch (e) { console.error('Error hiding skip intro button:', error); }

    try { player.trigger('loadstart'); } catch (error) { console.error('Error triggering loadstart:', error); }

    try { if (typeof player.poster === 'function') player.poster(''); } catch (e) { console.error('Error setting poster:', error); }
    try {
      const posterEl = player && player.el ? player.el().querySelector('.vjs-poster') : null;
      if (posterEl) posterEl.style.display = 'none';
    } catch (e) { console.error('Error hiding poster:', error); }

    const lowercaseType = (type || '').toLowerCase();

    if (lowercaseType === 'local') {
      player.src({ type: 'video/mp4', src: url });
      player.load();
      player.muted(true);
      player.play();
      return;
    }
    try {
      const response = await fetch(`${baseUrl}/video/stream/${encodeURIComponent(url)}`);
      const data = await response.json();
      setVideoSource(player, data.platform, data.videoId, data.url || url, data.adTagUrl || '');
      player.load();
      player.muted(true);
      player.play();
    } catch (error) {
      console.error('Error resolving clip:', error);
    }
  };

  document.addEventListener('click', function (e) {
    const link = e.target.closest && e.target.closest('a.clip-link[data-url]');
    if (!link) return;
    e.preventDefault();

    const url = link.getAttribute('data-url') || '';
    const type = link.getAttribute('data-type') || '';

    window.playClip({ url, type });
  }, true);

  async function CheckDeviceType() {
    try {
      const response = await fetch(`${baseUrl}/check-device-type`)
      const data = await response.json()
      return data.isDeviceSupported
    } catch (error) {
      return false
    }
  }

  async function CheckSubscription(planId) {
    try {
      const response = await fetch(`${baseUrl}/check-subscription/${planId}`)
      const data = await response.json()
      return data.isActive
    } catch (error) {
      return false
    }
  }



  // Reusable function to save entertainment views
  // async function saveEntertainmentView(button) {
  //   try {
  //     const entertainmentId = button?.getAttribute('data-entertainment-id');
  //     const profileId = button?.getAttribute('data-profile-id');
  //     const typeOfContent = button?.getAttribute('data-entertainment-type');

  //     if (entertainmentId && ['movie', 'tvshow', 'video'].includes(typeOfContent)) {
  //       await fetch(`${baseUrl}/api/save-entertainment-views`, {
  //         method: 'POST',
  //         headers: {
  //           'Content-Type': 'application/json',
  //           'X-CSRF-TOKEN': csrfToken
  //         },
  //         body: JSON.stringify({
  //           entertainment_id: entertainmentId,
  //           profile_id: profileId,
  //         })
  //       });
  //     }
  //   } catch (error) {
  //     console.error('Error saving entertainment view:', error);
  //   }
  // }
  async function saveEntertainmentView(button) {
    try {
      const typeOfContent = button?.getAttribute('data-entertainment-type');
      const profileId = button?.getAttribute('data-profile-id');
      const episodeId = button?.getAttribute('data-episode-id');
      const contentId = button?.getAttribute('data-contentid');
      const entertainmentIdAttr = button?.getAttribute('data-entertainment-id');

      const isEpisode = typeOfContent === 'episode' || (episodeId && contentId && contentId !== entertainmentIdAttr);
      const entertainmentId = isEpisode ? contentId : entertainmentIdAttr;

      if (entertainmentId && ['movie', 'tvshow', 'video', 'episode'].includes(isEpisode ? 'episode' : typeOfContent)) {
        await fetch(`${baseUrl}/api/save-entertainment-views`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
          },
          body: JSON.stringify({
            entertainment_id: entertainmentId,
            profile_id: profileId,
          })
        });
      }
    } catch (error) {
      console.error('Error saving entertainment view:', error);
    }
  }

  async function checkAuthenticationAndDeviceSupport() {
    const isDeviceSupported = await CheckDeviceType()
    return isAuthenticated && isDeviceSupported
  }
  //global function to set subtitle
  function setSubtitle(videoPlayer, subtitleInfo) {
    if (subtitleInfo && subtitleInfo.length > 0) {
      // Remove any existing subtitle tracks
      const existingTracks = videoPlayer.textTracks();
      for (let i = existingTracks.length - 1; i >= 0; i--) {
        videoPlayer.removeRemoteTextTrack(existingTracks[i]);
      }

      // Add new subtitle tracks
      subtitleInfo.forEach(subtitle => {
        videoPlayer.addRemoteTextTrack({
          kind: 'subtitles',
          src: subtitle.subtitle_file,
          srclang: subtitle.language_code,
          label: subtitle.language,
          default: subtitle.is_default === 1
        }, false);
      });

      setTimeout(() => {
        const tracks = player.textTracks();
        for (let i = 0; i < tracks.length; i++) {
          if (tracks[i].kind === 'subtitles' && subtitleInfo[i] && subtitleInfo[i].is_default === 1) {
            tracks[i].mode = 'showing';
          } else {
            tracks[i].mode = 'disabled';
          }
        }
      }, 0);
    }
  }

  async function loadVideoIfAuthenticated() {


    const accessType = document.querySelector('#videoPlayer').getAttribute('data-movie-access')

    const plan_id = document.querySelector('#videoPlayer').getAttribute('data-plan-id')

    // if (!isAuthenticated) {
    //   return // Exit if not authenticated
    // }

    let canPlay = true
    if (accessType === 'paid') {
      canPlay = await checkAuthenticationAndDeviceSupport()

    }

    if (plan_id && accessType === 'paid') {
      const isSubscribed = await CheckSubscription(plan_id)
      if(isSubscribed) {
        canPlay = true
      }
    }

    //when content is paid but play the trailer
    if (contentVideoType == 'trailer') {
      canPlay = true;
    }

    if (canPlay && !isVideoLoaded) {
      const encryptedData = document.querySelector('#videoPlayer').getAttribute('data-encrypted')
      const watchTime = document.querySelector('#videoPlayer').getAttribute('data-watch-time')

      // Set global variables for watch history tracking
      // Try to get from watch buttons first, then from video element
      // this is global variable set for the watch history tracking
      const videoEl = document.getElementById('videoPlayer');
      const watchNowButton = document.getElementById('watchNowButton');
      const seasonWatchBtn = document.getElementById('seasonWatchBtn');

      const dataType = videoEl?.getAttribute('data-type');
      if (dataType === 'Local') {
        const bigPlayButton = player.el().querySelector('.vjs-big-play-button');
        if (bigPlayButton) bigPlayButton.style.display = 'none';
        player.muted(true);
        player.load();
        player.one('loadedmetadata', async () => {
          try {
            await player.play();
          } catch (err) {
            console.error('Autoplay error:', err);
          }
        });
        isVideoLoaded = true;
        return;
      }
      currentEntertainmentId = watchNowButton?.getAttribute('data-entertainment-id') ||
        seasonWatchBtn?.getAttribute('data-entertainment-id') ||
        videoEl.getAttribute('data-entertainment-id') ||
        document.querySelector('meta[name="entertainment-id"]')?.getAttribute('content');
      currentEntertainmentType = watchNowButton?.getAttribute('data-entertainment-type') ||
        seasonWatchBtn?.getAttribute('data-entertainment-type') ||
        videoEl.getAttribute('data-entertainment-type') ||
        document.querySelector('meta[name="entertainment-type"]')?.getAttribute('content');
      currentEpisodeId = watchNowButton?.getAttribute('data-episode-id') ||
        seasonWatchBtn?.getAttribute('data-episode-id') ||
        videoEl.getAttribute('data-episode-id') ||
        document.querySelector('meta[name="episode-id"]')?.getAttribute('content');
      currentVideoUrl = encryptedData; // Use encrypted data as video URL for tracking

      // Hide the big play button if encryptedData is empty
      const bigPlayButton = player.el().querySelector('.vjs-big-play-button');
      if (!encryptedData && bigPlayButton) {
        bigPlayButton.style.display = 'none';
        // Optionally, you could show a message to the user here
        // e.g., player.overlay({ content: 'Video not available.' });
        return; // Stop here if no video data
      }

      let watchTimeInSeconds = 0;
      if (watchTime) {
        const parts = watchTime.split(':').map(Number);
        if (parts.length === 3) {
          watchTimeInSeconds = (parts[0] * 3600) + (parts[1] * 60) + parts[2];
        } else if (parts.length === 2) {
          watchTimeInSeconds = (parts[0] * 60) + parts[1];
        } else if (parts.length === 1) {
          watchTimeInSeconds = parts[0];
        }
      }
      if (Number.isFinite(watchTimeInSeconds) && watchTimeInSeconds > 0) {
        player.currentTime(watchTimeInSeconds);
      }
      const subtitleInfoforwatchlist = JSON.parse(
        watchNowButton?.getAttribute('data-subtitle-info') ||
        seasonWatchBtn?.getAttribute('data-subtitle-info') ||
        '[]'
      );
      // console.log('-----------------------------------------------------------------',continueWatch)
      if (encryptedData) {
        fetch(`${baseUrl}/video/stream/${encodeURIComponent(encryptedData)}`)
          .then((response) => response.json())
          .then((data) => {
            const qualityOptions = data.qualityOptions
            setVideoSource(player, data.platform, data.videoId, data.url, data.mimeType, qualityOptions)
            player.load();
            if (continueWatch == true) {
              setSubtitle(player, subtitleInfoforwatchlist);
            }
            player.one('loadedmetadata', async function () {
              player.muted(true) // Mute the player for autoplay
              try {
                if (Number.isFinite(watchTimeInSeconds) && watchTimeInSeconds > 0) {
                  player.currentTime(watchTimeInSeconds);
                }
                await player.play()
                isPopupShown = false // Reset flag when video starts playing successfully
              } catch (error) {
                console.error('Error trying to autoplay:', error)
              }
            })
            isVideoLoaded = true
          })
          .catch((error) => console.error('Error fetching video:', error))
      }
    }
    else {
      isPopupShown = true // Mark that popup was shown
      $('#DeviceSupport').modal('show')
    }
  }

  loadVideoIfAuthenticated()
  const playButton = document.querySelector('.vjs-big-play-button')
  if (playButton) {
    playButton.addEventListener('click', async function (e) {
      if (!isAuthenticated) {
        e.preventDefault() // Prevent play
        window.location.href = loginUrl // Redirect to login
      } else {
        let canPlay = await checkAuthenticationAndDeviceSupport()
        
        // Check subscription overrides device check
        const vidPlayer = document.querySelector('#videoPlayer');
        const plan_id = vidPlayer?.getAttribute('data-plan-id');
        const accessType = vidPlayer?.getAttribute('data-movie-access');
        
        if (!canPlay && plan_id && accessType === 'paid') {
             const isSubscribed = await CheckSubscription(plan_id);
             if (isSubscribed) canPlay = true;
        }

        if (!canPlay) {
          e.preventDefault() // Prevent play if conditions are not met
          isPopupShown = true // Mark that popup was shown
          $('#DeviceSupport').modal('show');
          player.pause && player.pause();
        }
      }
    })
  }

  const handleWatchButtonClick = async (button, isSeasonWatch = false) => {

    const accessType = button.getAttribute('data-movie-access')
    const qualityOptionsData = button.getAttribute('data-quality-options')
    const qualityOptions = Object.entries(JSON.parse(qualityOptionsData)).map(([label, url]) => ({ label, url }))
    const videoUrl = button.getAttribute('data-video-url')
    currentVideoUrl = videoUrl
    contentVideoType = button.getAttribute('content-video-type');

    currentEpisodeId = button.getAttribute('data-episode-id');
    currentEntertainmentId = button.getAttribute('data-entertainment-id');
    currentEntertainmentType = button.getAttribute('data-entertainment-type');

    currentEpisodeName = button.getAttribute('data-episode-name') || '';
    // Update episode name display
    if (window.updateEpisodeNameDisplay) {
      window.updateEpisodeNameDisplay(currentEpisodeName, currentEpisodeId);
    }

    // Get subtitle data from the button and parse it
    const subtitleInfo = JSON.parse(button.getAttribute('data-subtitle-info') || '[]')

    // Hide subtitle button if no subtitles are available
    const subtitleButton = player.controlBar.subsCapsButton
    if (subtitleButton) {
      if (subtitleInfo && subtitleInfo.length > 0) {
        subtitleButton.show()
      } else {
        subtitleButton.hide()
      }
    }

    // showCustomAdThenPlayMain();

    window.scrollTo({ top: 0, behavior: 'smooth' })

    fetch(`${baseUrl}/api/continuewatch-list`)
      .then((response) => response.json())
      .then(async (data) => {

        const entertainmentId = button.getAttribute('data-entertainment-id')
        const entertainmentType = button.getAttribute('data-entertainment-type')
        const matchingVideo = data.data.find((item) => item.entertainment_id === parseInt(entertainmentId) && item.entertainment_type === entertainmentType)
        let lastWatchedTime = 0
        if (matchingVideo && matchingVideo.total_watched_time) {
          lastWatchedTime = timeStringToSeconds(matchingVideo.total_watched_time)
        }
        if (accessType === 'paid') {
          let canPlay = await checkAuthenticationAndDeviceSupport()
          
          // Check subscription overrides device check
          const plan_id = button.getAttribute('data-plan-id');
          if (!canPlay && plan_id) {
             const isSubscribed = await CheckSubscription(plan_id);
             if (isSubscribed) canPlay = true;
          }

          if (!canPlay) {
            player.pause()
            isPopupShown = true // Mark that popup was shown
            $('#DeviceSupport').modal('show') // Show device support modal if not supported
            return // Stop further execution
          }
        }
        if (accessType === 'free' || accessType === 'pay-per-view') {
          playVideo(player, videoUrl, qualityOptions, lastWatchedTime, subtitleInfo)
        } else {
          handleSubscription(button, videoUrl, qualityOptions, lastWatchedTime, subtitleInfo)
        }
      })
      .catch((error) => console.error('Error fetching continue watch:', error))

    isWatchHistorySaved = false // Reset flag
  }

  const watchNowButton = document.getElementById('watchNowButton')
  const seasonWatchBtn = document.getElementById('seasonWatchBtn')

  const subtitles = JSON.parse(watchNowButton.getAttribute('data-subtitle-info'));

  if (watchNowButton) {
    watchNowButton.addEventListener('click', async function (e) {
      e.preventDefault()
      if (!isAuthenticated) {
        window.location.href = loginUrl
        return
      }
      const accessTypeForWatchNow = watchNowButton.getAttribute('data-movie-access');
      if (accessTypeForWatchNow === 'paid') {
        let canPlay = await checkAuthenticationAndDeviceSupport()
        
        // Check subscription overrides device check
        const plan_id = watchNowButton.getAttribute('data-plan-id');
        if (!canPlay && plan_id) {
            const isSubscribed = await CheckSubscription(plan_id);
            if (isSubscribed) canPlay = true;
        }

        if (!canPlay) {
          player.pause()
          isPopupShown = true // Mark that popup was shown
          $('#DeviceSupport').modal('show') // Show device support modal if not supported
          return // Stop further execution
        }
      }
      // if (await CheckDeviceType() === false) {
      //   isPopupShown = true // Mark that popup was shown
      //   $('#DeviceSupport').modal('show')
      //   player.pause && player.pause();
      //   return
      // }
      // console.log('==================================================', watchNowButton);
      contentId = watchNowButton.getAttribute('data-contentid') || '';
      contentType = watchNowButton.getAttribute('data-contenttype') || '';
      contentVideoType = watchNowButton.getAttribute('content-video-type') || '';

      currentEpisodeName = watchNowButton.getAttribute('data-episode-name') || '';
      // Update episode name display
      if (window.updateEpisodeNameDisplay) {
        window.updateEpisodeNameDisplay(currentEpisodeName, currentEpisodeId);
      }

      // console.log('=================================================='.contentId, contentType, contentVideoType);

      await saveEntertainmentView(watchNowButton);
      // const entertainmentType = watchNowButton.getAttribute('data-entertainment-type');
      // if(entertainmentType == 'video'){
      const skipBtn = document.querySelector('.vjs-skip-trainer-button.vjs-control');
      if (skipBtn) {
        skipBtn.style.display = 'none';
      }
      // }

      // Reset custom ad flag for new content selection
      customAdPlayed = false;
      customAdAttempts = 0; // Reset attempts counter

      showCustomAdThenPlayMain(function () {
        loadAdsAndStartInterval();
        // 3. Only after the ad is done, play the main content
        handleWatchButtonClick(watchNowButton);
        // Mount Skip Intro for this watch
        mountSkipIntroFrom(watchNowButton);
      });
    })
  }

  document.addEventListener('click', async function (e) {
    const button = e.target.closest('.season-watch-btn');
    if (button) {
      contentId = button.getAttribute('data-contentid') || '';
      contentType = button.getAttribute('data-contenttype') || '';
      contentVideoType = button.getAttribute('content-video-type') || '';

      currentEpisodeName = button.getAttribute('data-episode-name') || '';
      // Update episode name display
      if (window.updateEpisodeNameDisplay) {
        window.updateEpisodeNameDisplay(currentEpisodeName, currentEpisodeId);
      }

    }

    if (button) {
      e.preventDefault();

      if (!isAuthenticated) {
        window.location.href = loginUrl;
        return;
      }

      const accessTypeForSeason = button.getAttribute('data-movie-access');
      if (accessTypeForSeason === 'paid') {
        let canPlay = await checkAuthenticationAndDeviceSupport()
        
        // Check subscription overrides device check
        const plan_id = button.getAttribute('data-plan-id');
        if (!canPlay && plan_id) {
             const isSubscribed = await CheckSubscription(plan_id);
             if (isSubscribed) canPlay = true;
        }

        if (!canPlay) {
          player.pause()
          isPopupShown = true // Mark that popup was shown
          $('#DeviceSupport').modal('show') // Show device support modal if not supported
          return // Stop further execution
        }
      }

      // if (await CheckDeviceType() === false) {
      //   isPopupShown = true // Mark that popup was shown
      //   $('#DeviceSupport').modal('show')
      //   player.pause && player.pause();
      //   return
      // }

      await saveEntertainmentView(button);

      // Hide skip button using CSS class selector
      const skipBtn = document.querySelector('.vjs-skip-trainer-button.vjs-control');
      if (skipBtn) {
        skipBtn.style.display = 'none';
      }

      // Reset custom ad flag for new episode selection
      customAdPlayed = false;
      customAdAttempts = 0; // Reset attempts counter

      showCustomAdThenPlayMain(function () {
        loadAdsAndStartInterval();
        handleWatchButtonClick(button);
        // Mount Skip Intro for this season watch
        mountSkipIntroFrom(button);
      });
    }
  });

  // Reusable function to create quality selector
  function createQualitySelector(player, qualityOptions, subtitleInfo = [], baseUrl) {
    const existingQualitySelector = document.querySelector('.vjs-quality-selector')
    if (!existingQualitySelector && qualityOptions.length > 0) {
      const qualitySelector = document.createElement('div')
      qualitySelector.classList.add('vjs-quality-selector')

      const qualityDropdown = document.createElement('select')

      // Add a default option representing the main playback content
      try {
        const defaultUrl = (watchNowButton?.getAttribute('data-video-url') || seasonWatchBtn?.getAttribute('data-video-url')) || ''
        const defaultType = (watchNowButton?.getAttribute('data-type') || seasonWatchBtn?.getAttribute('data-type')) || ''
        if (defaultUrl) {
          const defaultOption = document.createElement('option')
          defaultOption.value = defaultUrl
          defaultOption.innerText = typeof defaultText !== 'undefined' ? defaultText : 'Default'
          if (defaultType) defaultOption.setAttribute('data-type', defaultType)
          qualityDropdown.appendChild(defaultOption)
        }
      } catch (_) { }

      qualityOptions.forEach((option) => {
        const qualityOption = document.createElement('option')

        qualityOption.value = option.url.value // Use the URL for the quality option
        qualityOption.innerText = option.label // Display the label (e.g., "360p", "720p")
        qualityOption.setAttribute('data-type', option.url.type);
        qualityDropdown.appendChild(qualityOption)
      })

      // Toggle dropdown icon when select opens/closes
      qualityDropdown.addEventListener('mousedown', function (e) {
        const isOpen = this.classList.contains('dropdown-open');
        this.classList.toggle('dropdown-open', !isOpen);
      });
      
      qualityDropdown.addEventListener('focus', function () {
        this.classList.add('dropdown-open');
      });
      
      qualityDropdown.addEventListener('blur', function () {
       this.classList.remove('dropdown-open');
      });
      
      qualityDropdown.addEventListener('change', function () {
        this.classList.remove('dropdown-open');
      });
      
      document.addEventListener('click', function (e) {
        const qualitySelect = document.querySelector('.vjs-quality-selector select');
        if (qualitySelect && !qualitySelect.contains(e.target) && qualitySelect !== e.target) {
          qualitySelect.classList.remove('dropdown-open');
        }
      });

      qualityDropdown.addEventListener('change', function () {
        const selectedQuality = this.value;
        var videoId = null;
        var platform = null;
        var url = null;

        const dataType = document.querySelector('.vjs-quality-selector select')
          ?.selectedOptions[0]?.getAttribute('data-type');

        const currentTime = player.currentTime();
        const wasPlaying = !player.paused();
        const wasMuted = player.muted();

        try {
          const vjs = videojs('videoPlayer');
          const current = typeof vjs.currentSrc === 'function' ? vjs.currentSrc() : '';
          if (current && current === selectedQuality) {
            vjs.play().catch(() => { });
            return;
          }
        } catch (error) {
          console.error('Error checking current source:', error);
        }

        const filteredOptions = qualityOptions.filter(option =>
          option.url.type === 'Local' && dataType === option.url.type
        );

        // Handle Local video files
        if (filteredOptions.length > 0) {
          const option = filteredOptions[0];
          const videoSource = document.querySelectorAll('#videoSource');

          if (videoSource) {
            videoSource.src = option.url.value;
            const videoPlayer = videojs('videoPlayer');

            videoPlayer.src({ type: 'video/mp4', src: option.url.value });
            setSubtitle(videoPlayer, subtitleInfo);
            videoPlayer.load();

            videoPlayer.one('loadedmetadata', function () {
              videoPlayer.currentTime(currentTime);
              videoPlayer.muted(wasMuted); // Restore mute state
              if (wasPlaying) {
                videoPlayer.play().catch(err => {
                  console.error('Error resuming playback:', err);
                });
              }
            });

            try {
              mountSkipIntroFrom(watchNowButton || seasonWatchBtn);
            } catch (e) {
              console.error('Error mounting skip intro:', e);
            }
          }
        }

        else if (dataType && dataType.toLowerCase() === 'local') {
          const videoPlayer = videojs('videoPlayer');
          videoPlayer.src({ type: 'video/mp4', src: selectedQuality });
          setSubtitle(videoPlayer, subtitleInfo);
          videoPlayer.load();

          videoPlayer.one('loadedmetadata', function () {
            videoPlayer.currentTime(currentTime);
            if (wasPlaying) {
              videoPlayer.play().catch(err => {
                console.error('Error resuming playback:', err);
              });
            }
          });

          try {
            mountSkipIntroFrom(watchNowButton || seasonWatchBtn);
          } catch (e) {
            console.error('Error mounting skip intro:', e);
          }
        }
        else {
          fetch(`${baseUrl}/video/stream/${encodeURIComponent(selectedQuality)}`)
            .then(response => response.json())
            .then(data => {
              setVideoSource(player, data.platform, data.videoId, data.url || '', data.adTagUrl || '');
              setSubtitle(player, subtitleInfo);
              player.load();

              player.one('loadedmetadata', function () {
                player.currentTime(currentTime);
                player.muted(wasMuted); // Restore mute state
                if (wasPlaying) {
                  player.play().catch(err => {
                    console.error('Error resuming playback:', err);
                  });
                }
              });

              try {
                mountSkipIntroFrom(watchNowButton || seasonWatchBtn);
              } catch (e) {
                console.error('Error mounting skip intro:', e);
              }
            })
            .catch(error => console.error('Error playing video:', error));
        }
      });

      qualitySelector.appendChild(qualityDropdown)
      player.controlBar.el().appendChild(qualitySelector)
    }
  }

  function playVideo(player, videoUrl, qualityOptions, lastWatchedTime, subtitleInfo = []) {
    const datatype = watchNowButton?.getAttribute('data-type') || seasonWatchBtn?.getAttribute('data-type')

    if (datatype === 'Local') {
      const videoSource = document.querySelectorAll('#videoSource');

      videoSource.src = videoUrl;

      const videoPlayer = videojs('videoPlayer');
      videoPlayer.src({ type: 'video/mp4', src: videoUrl });
      setSubtitle(videoPlayer, subtitleInfo);
      videoPlayer.load();
      videoPlayer.play().then(() => {
        isPopupShown = false // Reset flag when video starts playing successfully
      }).catch(() => { });

      // Use the reusable function
      createQualitySelector(player, qualityOptions, subtitleInfo, baseUrl);
    } else {
      // // Check device support BEFORE fetching/setting video source
      // CheckDeviceType().then(isDeviceSupported => {
      //   if (!isDeviceSupported) {
      //     $('#DeviceSupport').modal('show');
      //     player.pause && player.pause();
      //     return;
      //   }
      // If supported, fetch and set video source
      fetch(`${baseUrl}/video/stream/${encodeURIComponent(videoUrl)}`)
        .then((response) => response.json())
        .then((data) => {
          setVideoSource(player, data.platform, data.videoId, data.url, data.mimeType, qualityOptions, subtitleInfo)
          player.load()
          setSubtitle(player, subtitleInfo);

          // Use the reusable function for quality selector
          createQualitySelector(player, qualityOptions, subtitleInfo, baseUrl);

          player.one('loadedmetadata', async function () {
            player.currentTime(lastWatchedTime)
            if (document.querySelector('#videoPlayer').getAttribute('data-movie-access') === 'free') {
              player.muted(true) // Mute the player for autoplay
              try {
                await player.play() // Attempt to autoplay
                isPopupShown = false // Reset flag when video starts playing successfully
              } catch (error) {
                console.error('Error trying to autoplay:', error)
              }
            }
          })
        })
        .catch((error) => console.error('Error playing video:', error))
      // });
    }
  }

  function handleSubscription(button, videoUrl, qualityOptions, lastWatchedTime, subtitleInfo = []) {
    const planId = button.getAttribute('data-plan-id')
    fetch(`${baseUrl}/check-subscription/${planId}`)
      .then((response) => response.json())
      .then((data) => {
        if (data.isActive) {
          playVideo(player, videoUrl, qualityOptions, lastWatchedTime, subtitleInfo)
        } else {
          // Open the modal to show the user options for selecting or confirming a plan
          isPopupShown = true // Mark that popup was shown
          $('#DeviceSupport').modal('show')

          // Assuming you have a button inside the modal to proceed with payment
          document.querySelector('#confirmSubscriptionButton').addEventListener('click', function () {
            // Redirect to subscription plan after modal confirmation
            window.location.href = `${baseUrl}/subscription-plan`
          })
        }
      })
      .catch((error) => console.error('Error checking subscription:', error))
  }

  // Helper function to format seconds to HH:MM:SS
  function formatTime(seconds) {
    return new Date(seconds * 1000).toISOString().substr(11, 8);
  }

  // Function to save watch time immediately for progress bar updates
  function saveWatchTimeImmediately(currentTime = null, duration = null) {
    if (contentVideoType == 'clip' || !isAuthenticated || !currentVideoUrl) {
      return;
    }

    const watchNowButton = document.getElementById('watchNowButton');
    const seasonWatchBtn = document.getElementById('seasonWatchBtn');

    const entertainmentId = currentEntertainmentId ||
      watchNowButton?.getAttribute('data-entertainment-id') ||
      seasonWatchBtn?.getAttribute('data-entertainment-id');

    const entertainmentType = currentEntertainmentType ||
      watchNowButton?.getAttribute('data-entertainment-type') ||
      seasonWatchBtn?.getAttribute('data-entertainment-type');

    if (!entertainmentId || !entertainmentType) {
      return;
    }

    const watchTime = currentTime ?? player.currentTime();
    const totalTime = duration ?? player.duration();

    if (!totalTime || watchTime < 0) {
      return;
    }

    fetch(`${baseUrl}/api/save-continuewatch`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify({
        entertainment_id: entertainmentId,
        entertainment_type: entertainmentType,
        total_watched_time: formatTime(totalTime),
        watched_time: formatTime(watchTime),
        episode_id: currentEpisodeId || watchNowButton?.getAttribute('data-episode-id') || seasonWatchBtn?.getAttribute('data-episode-id'),
        profile_id: watchNowButton?.getAttribute('data-profile-id') || seasonWatchBtn?.getAttribute('data-profile-id')
      })
    }).catch(error => console.error('Error saving continue watching:', error));
  }

  window.saveWatchTimeImmediately = saveWatchTimeImmediately;

  player.on('ended', async function () {
    if (contentVideoType == 'clip') {
      return;
    }
    if (isWatchHistorySaved) return

    const entertainmentId = currentEntertainmentId || watchNowButton?.getAttribute('data-entertainment-id') || seasonWatchBtn?.getAttribute('data-entertainment-id')
    const entertainmentType = currentEntertainmentType || watchNowButton?.getAttribute('data-entertainment-type') || seasonWatchBtn?.getAttribute('data-entertainment-type')
    const profileId = watchNowButton?.getAttribute('data-profile-id') || seasonWatchBtn?.getAttribute('data-profile-id')

    if (isAuthenticated && entertainmentId && entertainmentType && profileId) {
      // Save final watch time as duration to mark as completed
      const duration = player.duration();
      if (duration > 0) {
        window.saveWatchTimeImmediately?.(duration, duration);
      }

      const watchHistoryData = {
        entertainment_id: entertainmentId,
        entertainment_type: entertainmentType,
        profile_id: profileId
      }

      fetch(`${baseUrl}/api/save-watch-content`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify(watchHistoryData)
      })
        .then((response) => response.json())
        .then((data) => {
          isWatchHistorySaved = true
        })
        .catch((error) => console.error('Error saving watch history:', error))
    }
  })

  window.addEventListener('beforeunload', async function () {
    if (contentVideoType == 'clip' || contentVideoType == 'trailer') {
      return;
    }

    if (isPopupShown) {
      return;
    }
    const entertainmentId = currentEntertainmentId || watchNowButton?.getAttribute('data-entertainment-id') || seasonWatchBtn?.getAttribute('data-entertainment-id')
    const entertainmentType = currentEntertainmentType || watchNowButton?.getAttribute('data-entertainment-type') || seasonWatchBtn?.getAttribute('data-entertainment-type')
    const EpisodeId = currentEpisodeId || watchNowButton?.getAttribute('data-episode-id') || seasonWatchBtn?.getAttribute('data-episode-id')

    if (isAuthenticated && currentVideoUrl && entertainmentId && entertainmentType) {
      const currentTime = player.currentTime()
      const duration = player.duration()

      if (!duration || currentTime < 30) {
        return;
      }

      const formattedDuration = new Date(duration * 1000).toISOString().substr(11, 8)
      const formattedCurrentTime = new Date(currentTime * 1000).toISOString().substr(11, 8)


      fetch(`${baseUrl}/api/save-continuewatch`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
          entertainment_id: entertainmentId,
          entertainment_type: entertainmentType,
          total_watched_time: formattedDuration,
          watched_time: formattedCurrentTime,
          episode_id: EpisodeId,
          video_url: currentVideoUrl
        })
      })

        .then((response) => response.json())
        .then((data) => {

        })
        .catch((error) => console.error('Error saving continue watching:', error))
    }
  })

  // Function to initialize Kaltura Playkit player
  function initializeKalturaPlayer(playerId, partnerId, uiConfId, entryId) {
    try {
      // console.log('Initializing Kaltura player:', { playerId, partnerId, uiConfId, entryId });

      // Ensure target element exists before initializing
      const targetEl = document.getElementById(playerId);
      if (!targetEl) {
        console.warn('Kaltura target not yet in DOM, retrying...');
        setTimeout(() => initializeKalturaPlayer(playerId, partnerId, uiConfId, entryId), 50);
        return;
      }

      // Validate parameters
      if (!entryId || entryId.length < 3) {
        throw new Error('Invalid entry ID: ' + entryId);
      }

      if (!partnerId || !uiConfId) {
        throw new Error('Invalid partner ID or uiConf ID');
      }

      const kalturaPlayer = KalturaPlayer.setup({
        targetId: playerId,
        provider: {
          partnerId: parseInt(partnerId),
          uiConfId: parseInt(uiConfId)
        }
      });

      // console.log('Loading media with entry ID:', entryId);
      kalturaPlayer.loadMedia({ entryId: entryId });

      // Trigger loadedmetadata event after Kaltura player is ready
      kalturaPlayer.addEventListener('loadedmetadata', () => {
        // console.log('Kaltura player metadata loaded');
        // Find the video.js player and trigger the event
        const videoPlayer = document.getElementById('videoPlayer');
        if (videoPlayer && videoPlayer.player) {
          videoPlayer.player.trigger('loadedmetadata');
        }
      });

      // Also listen for play event
      kalturaPlayer.addEventListener('play', () => {
        // console.log('Kaltura player started playing');
      });

      // Listen for error events
      kalturaPlayer.addEventListener('error', (error) => {
        console.error('Kaltura player error:', error);
      });

      // Listen for media load events
      kalturaPlayer.addEventListener('mediaLoadError', (error) => {
        console.error('Kaltura media load error:', error);
      });

      // console.log('Kaltura player initialized successfully');
    } catch (e) {
      console.error('Error initializing Kaltura player:', e.message);
    }
  }

  function setVideoSource(player, platform, videoId, url = '', adTagUrl = '') {
    const videoEl = document.getElementById('videoPlayer');
    const vimeoContainer = document.getElementById('vimeoContainer');
    const vimeoIframe = document.getElementById('vimeoIframe');
    if (videoEl) videoEl.style.display = '';
    if (vimeoIframe) vimeoIframe.style.display = 'none';
    if (vimeoContainer) vimeoContainer.style.display = 'none';

    // Always hide both players first
    if (videoEl) videoEl.style.display = 'none';
    if (vimeoIframe) vimeoIframe.style.display = 'none';
    if (vimeoContainer) vimeoContainer.style.display = 'none';
    if (platform === 'youtube') {
      player.src({
        type: 'video/youtube',
        src: `https://www.youtube.com/watch?v=${videoId}&autoplay=1`
      });
    } else if (platform === 'hls') {
      player.src({ type: 'application/x-mpegURL', src: url });

      if (adTagUrl) {
        // ✅ Initialize IMA Ads only here (non-YouTube)
        player.ima({
          id: 'videoPlayer',
          adTagUrl: adTagUrl
        });
        player.ima.requestAds();
      }
    } else if (platform === 'local') {
      // player.src({ type: , src: url });
      player.src({ type: 'video/mp4', src: url });

      if (adTagUrl) {
        player.ima({
          id: 'videoPlayer',
          adTagUrl: adTagUrl
        });
        player.ima.requestAds();
      }
    } else if (platform === 'embed' || platform === 'embedded') {
      // Pause the player
      if (player && typeof player.pause === 'function') {
        player.pause();
      }
      // Hide native video element safely
      const nativeVideoEl = player.el().querySelector('video');
      if (nativeVideoEl) {
        nativeVideoEl.style.display = 'none';
      }
      // Remove existing iframe overlays if any
      const existingOverlay = player.el().querySelector('.vjs-iframe-overlay');
      if (existingOverlay) {
        existingOverlay.remove();
      }
      // Create a fresh overlay div with iframe
      const iframeOverlay = document.createElement('div');
      iframeOverlay.className = 'vjs-iframe-overlay';
      Object.assign(iframeOverlay.style, {
        position: 'absolute',
        top: '0',
        left: '0',
        width: '100%',
        height: '100%',
        zIndex: '10',
      });

      // Check if it's a Kaltura embed URL
      if (url.includes('kaltura.com')) {
        // console.log('Loading Kaltura Playkit embed:', url);

        // Extract entry ID from URL if it's a full URL
        let entryId = videoId;
        if (url.includes('entry_id=')) {
          const match = url.match(/entry_id=([^&\s]+)/);
          if (match) {
            entryId = match[1].trim();
          }
        } else if (url.includes('entryId=')) {
          const match = url.match(/entryId=([^&\s]+)/);
          if (match) {
            entryId = match[1].trim();
          }
        } else if (url.includes('entryId:')) {
          const match = url.match(/entryId:([^&\s]+)/);
          if (match) {
            entryId = match[1].trim();
          }
        }

        // Extract partner ID and uiConf ID from URL
        let partnerId = 650742; // default
        let uiConfId = 51878872; // default

        const partnerMatch = url.match(/partner_id=(\d+)/);
        if (partnerMatch) {
          partnerId = partnerMatch[1];
        } else {
          const partnerMatch2 = url.match(/p\/(\d+)/);
          if (partnerMatch2) {
            partnerId = partnerMatch2[1];
          }
        }

        const uiConfMatch = url.match(/uiconf_id=(\d+)/);
        if (uiConfMatch) {
          uiConfId = uiConfMatch[1];
        }

        // If we still don't have a valid entry ID, try to extract from videoId
        if (!entryId || entryId === 'undefined' || entryId === 'null' || entryId === '') {
          // Try to extract entry ID from videoId if it looks like a Kaltura entry ID
          if (videoId && videoId.includes('_')) {
            entryId = videoId;
          }
        }

        // Clean the entry ID - remove any HTML or extra characters
        if (entryId) {
          entryId = entryId.replace(/[^\w_-]/g, ''); // Only keep alphanumeric, underscore, and hyphen
        }

        // console.log('Kaltura parameters extracted:', { entryId, partnerId, uiConfId, url, videoId });
        // console.log('Original URL being processed:', url);

        // Validate entry ID
        if (!entryId || entryId.length < 3) {
          console.error('Invalid Kaltura entry ID:', entryId);
          return;
        }

        // Create unique player ID
        const playerId = 'kaltura_player_' + Date.now();

        iframeOverlay.innerHTML = `
          <div id="${playerId}" style="width: 100%; height: 100%;"></div>
        `;

        // Append the container BEFORE loading script to ensure it exists
        player.el().appendChild(iframeOverlay);

        // Load Kaltura Playkit script if not already loaded
        if (!window.KalturaPlayer) {
          const script = document.createElement('script');
          script.src = `https://cdnapisec.kaltura.com/p/${partnerId}/embedPlaykitJs/uiconf_id/${uiConfId}`;
          script.onload = () => {
            initializeKalturaPlayer(playerId, partnerId, uiConfId, entryId);
          };
          document.head.appendChild(script);
        } else {
          initializeKalturaPlayer(playerId, partnerId, uiConfId, entryId);
        }
      } else {
        // For other embed URLs
        // console.log('Loading generic embed:', url);
        iframeOverlay.innerHTML = `
          <iframe
            src="${url}"
            width="100%"
            height="100%"
            frameborder="0"
            allow="autoplay; fullscreen; picture-in-picture"
            style="border:0;"
            allowfullscreen>
          </iframe>
        `;

        // Append to the Video.js player container
        player.el().appendChild(iframeOverlay);
      }

      // Trigger loadedmetadata event for embed content (only for non-Kaltura embeds)
      if (!url.includes('kaltura.com')) {
        setTimeout(() => {
          player.trigger('loadedmetadata');
          // console.log('Embed iframe loaded');
        }, 100);
      }
    } else if (platform === 'vimeo') {
      playVimeoWithPreRoll(player, videoId, url, adTagUrl)
    } else if (platform === 'external') {
      player.src({ type: 'video/mp4', src: url });

      if (adTagUrl) {
        player.ima({
          id: 'videoPlayer',
          adTagUrl: adTagUrl
        });
        player.ima.requestAds();
      }
    }

    // Handle other platforms (YouTube, local, HLS)
    if (videoEl) videoEl.style.display = 'block';
    if (vimeoIframe) {
      vimeoIframe.src = ''; // cleanup
      vimeoIframe.style.display = 'none';
    }
    if (vimeoContainer) vimeoContainer.style.display = 'none';
  }

  // Dedicated function for Vimeo + pre-roll
  function playVimeoWithPreRoll(player, videoId, url = '', adTagUrl = '') {
    if (player) {
      try { player.pause && player.pause(); } catch (e) { console.error('Error pausing player:', e); }
      try { player.src && player.src({ src: '' }); } catch (e) { console.error('Error setting player source:', e); }
      try { player.load && player.load(); } catch (e) { console.error('Error loading player:', e); }
    }
    const vimeoContainer = document.getElementById('vimeoContainer');
    const vimeoIframe = document.getElementById('vimeoIframe');
    if (vimeoContainer) vimeoContainer.style.display = 'none';
    if (vimeoIframe) {
      vimeoIframe.style.display = 'none';
      vimeoIframe.src = '';
    }
    const videoEl = document.getElementById('videoPlayer');
    if (videoEl) videoEl.style.display = 'none';

    // Helper to show Vimeo after all ads
    function showVimeo() {
      stopAllVideoPlayers();
      if (vimeoIframe) {
        vimeoIframe.src = `https://player.vimeo.com/video/${videoId}?autoplay=1&muted=0`;
        vimeoIframe.style.display = 'block';
        videoEl.style.display = 'none';
      }
      if (vimeoContainer) vimeoContainer.style.display = 'block';
    }

    // 1. Check for custom ads first
    const paramsCustom = new URLSearchParams();
    if (typeof contentId !== 'undefined' && contentId) paramsCustom.append('content_id', contentId);
    if (typeof contentType !== 'undefined' && contentType) paramsCustom.append('type', contentType);
    if (typeof currentCategoryId !== 'undefined' && currentCategoryId) paramsCustom.append('category_id', currentCategoryId);
    const customApiUrl = `${baseUrl}/api/custom-ads/get-active?${paramsCustom.toString()}`;

    fetch(customApiUrl)
      .then(res => res.json())
      .then(customData => {
        let customAd = null;
        if (customData.success && Array.isArray(customData.data)) {
          customAd = customData.data.find(item => item.placement === 'player' && item.status == 1);
        }
        if (customAd) {
          // Play custom ad, then VAST, then Vimeo
          playCustomAd(customAd, () => {
            playVastAndVimeo();
          });
        } else {
          // No custom ad, just VAST then Vimeo
          playVastAndVimeo();
        }
      });

    // 2. Play VAST pre-rolls, then Vimeo
    function playVastAndVimeo() {
      //skip the vast ads for the vimeo content
      // const params = new URLSearchParams();
      // if (typeof contentId !== 'undefined' && contentId) params.append('content_id', contentId);
      // if (typeof contentType !== 'undefined' && contentType) params.append('type', contentType);
      // if (typeof contentVideoType !== 'undefined' && contentVideoType) params.append('video_type', contentVideoType);
      // if (typeof currentCategoryId !== 'undefined' && currentCategoryId) params.append('category_id', currentCategoryId);
      // if (typeof currentEpisodeId !== 'undefined' && currentEpisodeId) params.append('episode_id', currentEpisodeId);
      // fetch(`${baseUrl}/api/vast-ads/get-active?${params.toString()}`)
      //   .then(res => res.json())
      //   .then(response => {
      //     adQueue = response.data || [];
      //     const preRollAds = adQueue.filter(ad => ad.type === 'pre-roll');
      //     if (preRollAds.length > 0) {
      //       playAdsByType('pre-roll', showVimeo);
      //     } else {
      //       showVimeo();
      //     }
      //   });
      // console.log('skip the vast ads for the vimeo content');
      showVimeo();
    }

    // 3. Play custom ad (image/video/youtube)
    function playCustomAd(ad, onFinish) {
      // console.log('Playing custom ad:', ad.type);

      // Hide Vimeo container during custom ad
      if (vimeoContainer) vimeoContainer.style.display = 'none';
      const modal = document.getElementById('customAdModal');
      const content = document.getElementById('customAdContent');

      if (!modal || !content) {
        console.error('Custom ad modal elements not found');
        if (typeof onFinish === 'function') onFinish();
        return;
      }

      let adHtml = '';
      try {
        if (ad.type === 'image') {
          if (ad.redirect_url) {
            adHtml = `<a href="${ad.redirect_url}" target="_blank"><img src="${ad.media}" alt="Ad" style="max-width:100%; height:auto;" /></a>`;
          } else {
            adHtml = `<img src="${ad.media}" alt="Ad" style="max-width:100%; height:auto;" />`;
          }
        } else if (ad.type === 'video') {
          if (/youtu\.?be/.test(ad.media)) {
            let videoId = '';
            const ytMatch = ad.media.match(/(?:youtu\.be\/|youtube\.com.*(?:v=|\/embed\/|\/v\/|\/shorts\/))([a-zA-Z0-9_-]{11})/);
            if (ytMatch && ytMatch[1]) {
              videoId = ytMatch[1];
            }
            if (videoId) {
              adHtml = `<div style="position:relative;width:100%;height:100%;"><iframe id="customAdVideo" width="100%" height="100%" src="https://www.youtube.com/embed/${videoId}?autoplay=1&mute=1&rel=0" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen style="border-radius:12px; box-shadow:0 8px 32px rgba(0,0,0,0.7);"></iframe>`;
              if (ad.redirect_url) {
                adHtml += `<div id="adClickOverlay" style="position:absolute;top:0;left:0;width:100%;height:100%;z-index:10;cursor:pointer;"></div>`;
              }
              adHtml += `</div>`;
            } else {
              adHtml = `<div style="color:#fff;">Invalid YouTube URL</div>`;
            }
          } else if (ad.url_type == "url") {
            adHtml = `<div style="position:relative;width:100%;height:100%;"><video id="customAdVideo" autoplay style="background:#000;width:100%;height:100%;"><source src="${ad.media}" type="video/mp4"></video>`;
            if (ad.redirect_url) {
              adHtml += `<div id="adClickOverlay" style="position:absolute;top:0;left:0;width:100%;height:100%;z-index:10;cursor:pointer;"></div>`;
            }
            adHtml += `</div>`;
          } else {
            adHtml = `<div style="position:relative;width:100%;height:100%;"><video id="customAdVideo" autoplay style="background:#000;width:100%;height:100%;"><source src="${ad.media}" type="video/mp4"></video>`;
            if (ad.redirect_url) {
              adHtml += `<div id="adClickOverlay" style="position:absolute;top:0;left:0;width:100%;height:100%;z-index:10;cursor:pointer;"></div>`;
            }
            adHtml += `</div>`;
          }
        }
      } catch (error) {
        console.error('Error creating custom ad HTML:', error);
        const errorText = typeof errorLoadingAdText !== 'undefined' ? errorLoadingAdText : 'Error loading ad';
        adHtml = `<div style="color:#fff; padding:20px; text-align:center;">${errorText}: ${error.message}</div>`;
      }

      // Clear existing content
      content.querySelectorAll('img,video,iframe,a,div').forEach(el => el.remove());
      content.insertAdjacentHTML('afterbegin', adHtml);
      modal.style.display = 'flex';

      const closeBtn = document.getElementById('customAdCloseBtn');
      if (closeBtn) {
        closeBtn.onclick = () => closeCustomAdModal();
      }

      if (ad.type === 'image') {
        setTimeout(() => {
          if (modal.style.display === 'flex') {
            closeCustomAdModal();
          }
        }, 5000);
      }

      if (ad.type === 'video') {
        const adVideo = document.getElementById('customAdVideo');
        if (adVideo) {
          adVideo.onended = () => {
            // console.log('Custom video ad ended');
            closeCustomAdModal();
          };
          adVideo.onerror = (e) => {
            console.error('Custom video ad error:', e);
            closeCustomAdModal();
          };
        }
      }

      function closeCustomAdModal() {
        // console.log('Closing custom ad modal');
        modal.style.display = 'none';
        content.querySelectorAll('img,video,iframe,a,div').forEach(el => el.remove());
        if (typeof onFinish === 'function') onFinish();
      }

      // Add click handler for overlay if present
      if (ad.redirect_url) {
        setTimeout(() => {
          const overlay = document.getElementById('adClickOverlay');
          if (overlay) {
            overlay.onclick = () => {
              window.open(ad.redirect_url, '_blank');
            };
          }
        }, 0);
      }
    }
  }


  class SkipTrainerButton extends Button {
    constructor(player, options) {
      super(player, options);
      this.addClass('vjs-skip-trainer-button');
      // Get video element references
      const videoElement = document.querySelector('#videoPlayer');
      this.movieStartTime = parseFloat(videoElement?.getAttribute('data-movie-start')) || 300;
      this.watchNowButton = document.getElementById('watchNowButton');
      // Try to find seasonWatchBtn by ID first, then fallback to first .season-watch-btn
      // For TV show pages, find the first episode button (episode 1)
      this.seasonWatchBtn = document.getElementById('seasonWatchBtn');
      if (!this.seasonWatchBtn) {
        // Find all visible episode buttons
        const allEpisodeButtons = Array.from(document.querySelectorAll('.season-watch-btn:not(.d-none)'));
        if (allEpisodeButtons.length > 0) {
          // Try to find episode 1 by checking data-first-episode-id="1" first
          let firstEpisode = allEpisodeButtons.find(btn => 
            btn.getAttribute('data-first-episode-id') === '1'
          );
          
          // If not found, sort by first-episode-id attribute (episode number)
          if (!firstEpisode) {
            allEpisodeButtons.sort((a, b) => {
              const numA = parseInt(a.getAttribute('data-first-episode-id')) || 999999;
              const numB = parseInt(b.getAttribute('data-first-episode-id')) || 999999;
              return numA - numB;
            });
            firstEpisode = allEpisodeButtons[0];
          }
          
          // Fallback: if still no episode 1, use first button in DOM order
          this.seasonWatchBtn = firstEpisode || allEpisodeButtons[0];
        }
      }
      this.baseUrl = options.baseUrl;
      this.player_ = player;
      // Get entertainment data using data-entertainment-id instead of data-video-url
      this.entertainmentId = this.watchNowButton?.getAttribute('data-entertainment-id') ||
        this.seasonWatchBtn?.getAttribute('data-entertainment-id');
      this.entertainmentType = this.watchNowButton?.getAttribute('data-entertainment-type') ||
        this.seasonWatchBtn?.getAttribute('data-entertainment-type');
      this.episodeId = this.watchNowButton?.getAttribute('data-episode-id') ||
        this.seasonWatchBtn?.getAttribute('data-episode-id');
      // Get access type to check if it's pay-per-view
      this.accessType = this.watchNowButton?.getAttribute('data-movie-access') ||
        this.seasonWatchBtn?.getAttribute('data-movie-access') ||
        videoElement?.getAttribute('data-movie-access') || '';
      // Add timeupdate listener to control visibility
      this.timeUpdateHandler = this.handleTimeUpdate.bind(this);
      this.player_.on('timeupdate', this.timeUpdateHandler);
      this.autoAdvanceTriggered = false;
      // Initial visibility
      this.trailerSkipped = false; // Add flag to track if trailer was skipped
      this.handleTimeUpdate();
    }

    createEl() {
      return super.createEl('button', {
        innerHTML: typeof skipTrailerText !== 'undefined' ? skipTrailerText : 'Skip Trailer',
        className: 'vjs-skip-trainer-button vjs-control'
      });
    }

    handleTimeUpdate() {
      if (contentVideoType === 'clip' || contentType === 'comingsoon') {
        this.hide();
        return;
      }
      // Hide button if content type is not trailer (i.e., it's the main video)
      if (contentVideoType !== 'trailer') {
        this.hide();
        // Show NextEpisodeButton if it should be visible
        const nextBtn = this.player_.controlBar.getChild('NextEpisodeButton');
        if (nextBtn && typeof nextBtn.updateVisibility === 'function') nextBtn.updateVisibility();
        return;
      }
      // Show only if currentTime < movieStartTime and trailer not skipped
      if (this.player_.currentTime() < this.movieStartTime && !this.trailerSkipped) {
        this.show();
        // Hide NextEpisodeButton if present
        const nextBtn = this.player_.controlBar.getChild('NextEpisodeButton');
        if (nextBtn) nextBtn.hide();
      } else {
        this.hide();
        // Show NextEpisodeButton if it should be visible
        const nextBtn = this.player_.controlBar.getChild('NextEpisodeButton');
        if (nextBtn && typeof nextBtn.updateVisibility === 'function') nextBtn.updateVisibility();
      }
    }

    async handleClick() {
      // Get the video URL and quality options from watch now button
      // If neither exists, find the first episode button (episode 1) for TV show pages
      let button = this.watchNowButton || this.seasonWatchBtn;
      if (!button) {
        // Find all visible episode buttons
        const allEpisodeButtons = Array.from(document.querySelectorAll('.season-watch-btn:not(.d-none)'));
        if (allEpisodeButtons.length > 0) {
          // Try to find episode 1 by checking data-first-episode-id="1" first
          let firstEpisode = allEpisodeButtons.find(btn => 
            btn.getAttribute('data-first-episode-id') === '1'
          );
          
          // If not found, sort by first-episode-id attribute (episode number)
          if (!firstEpisode) {
            allEpisodeButtons.sort((a, b) => {
              const numA = parseInt(a.getAttribute('data-first-episode-id')) || 999999;
              const numB = parseInt(b.getAttribute('data-first-episode-id')) || 999999;
              return numA - numB;
            });
            firstEpisode = allEpisodeButtons[0];
          }
          
          // Fallback: if still no episode 1, use first button in DOM order
          button = firstEpisode || allEpisodeButtons[0];
          this.seasonWatchBtn = button; // Update reference for future use
        }
      }
      if (!button) {
        console.error('Watch button not found');
        return;
      }

      setStartDate(button);

      saveEntertainmentView(button);

      const videoUrl = button.getAttribute('data-video-url');
      const qualityOptionsData = button.getAttribute('data-quality-options');
      const qualityOptions = qualityOptionsData ?
        Object.entries(JSON.parse(qualityOptionsData)).map(([label, url]) => ({ label, url })) : [];
      const accessType = button.getAttribute('data-movie-access');
      const planId = button.getAttribute('data-plan-id');

      // Check episode purchase status if it's pay-per-view
      if (this.episodeId) {
        fetch(`${this.baseUrl}/api/check-episode-purchase?episode_id=${this.episodeId}`)
          .then(response => response.json())
          .then(data => {
            if (!data.is_purchased) {
              // Redirect to purchase page
              window.location.href = `${this.baseUrl}/payment-form/pay-per-view?type=episode&id=${this.episodeId}`;
              return;
            }
          })
          .catch(error => {
            console.error('Error checking episode purchase:', error);
          });
      }

      // Check authentication and device support for paid content
      if (accessType === 'paid') {
        let canPlay = await checkAuthenticationAndDeviceSupport()

        // Check subscription overrides device check
        if (!canPlay && planId) {
             const isSubscribed = await CheckSubscription(planId);
             if (isSubscribed) canPlay = true;
        }

        if (!canPlay) {
            isPopupShown = true // Mark that popup was shown
            $('#DeviceSupport').modal('show');
            return;
        }
        
        this._playMovie(videoUrl, qualityOptions);
      } else {
        // Free or no plan required, play movie
        this._playMovie(videoUrl, qualityOptions);
      }
    }

    _playMovie(videoUrl, qualityOptions) {
      // Validate video URL before proceeding
      if (!videoUrl || videoUrl.trim() === '') {
        console.error('Episode video URL is empty or invalid');
        // Don't change contentVideoType - keep it as 'trailer' so auto-advance is blocked
        // The error state will be shown by the player
        return;
      }

      // contentVideoType = contentType;
      const button = this.watchNowButton || this.seasonWatchBtn;
      contentId = button.getAttribute('data-contentid') || '';
      contentType = button.getAttribute('data-contenttype') || '';
      const newContentVideoType = button.getAttribute('content-video-type') || '';

      // Update currentEpisodeId when trailer is skipped to ensure correct episode tracking
      currentEpisodeId = button.getAttribute('data-episode-id') || null;
      currentEpisodeName = button.getAttribute('data-episode-name') || '';
      
      // Update NextEpisodeButton's currentEpisodeId to ensure it tracks the correct episode
      const nextBtn = this.player_.controlBar.getChild('NextEpisodeButton');
      if (nextBtn && currentEpisodeId) {
        nextBtn.currentEpisodeId = String(currentEpisodeId);
        nextBtn.updateVisibility();
      }
      
      // Update episode name display
      if (window.updateEpisodeNameDisplay) {
        window.updateEpisodeNameDisplay(currentEpisodeName, currentEpisodeId);
      }

      // Check if trailer URL and movie URL are the same
      // Compare the encrypted/original URLs (before transformation) to detect if they're identical
      const videoEl = document.getElementById('videoPlayer');
      const trailerUrl = videoEl ? videoEl.getAttribute('data-encrypted') : '';
      
      // Compare the original URLs (not the transformed ones)
      const isSameUrl = (trailerUrl === videoUrl);
      
      if (isSameUrl && contentVideoType === 'trailer') {
        // Same URL: just update contentVideoType and restart from beginning
        console.log('Trailer and movie URLs are the same - restarting video without reload');
        contentVideoType = newContentVideoType;
        
        // Hide the skip trailer button
        this.trailerSkipped = true;
        this.hide();
        
        // Mount Skip Intro based on the same button used to skip trailer
        mountSkipIntroFrom(button);
        
        // Restart video from beginning
        this.player_.currentTime(0);
        this.player_.play()
          .then(() => {
            episodeVideoPlaybackStarted = true;
          })
          .catch(error => {
            console.error('Error playing movie:', error);
          });
        
        return; // Exit early - no need to reload video or show ads again
      }

      // Different URLs: proceed with normal flow (load new video and show ads)
      contentVideoType = newContentVideoType;
      
      // console.log('==================================================', contentId, contentType, contentVideoType);
      showCustomAdThenPlayMain(function () {
        loadAdsAndStartInterval();
      });
      const subtitleInfo = JSON.parse(button.getAttribute('data-subtitle-info') || '[]');
      const dataType = button.getAttribute('data-type'); // e.g., "Local" or "Remote"



      if (dataType === 'Local') {
        // Set local video source directly
        this.player_.src({ type: 'video/mp4', src: videoUrl });
        // Add subtitle tracks if available
        if (subtitleInfo && subtitleInfo.length > 0) {
          // Remove any existing subtitle tracks
          const existingTracks = this.player_.textTracks();
          for (let i = existingTracks.length - 1; i >= 0; i--) {
            this.player_.removeRemoteTextTrack(existingTracks[i]);
          }
          // Add new subtitle tracks
          subtitleInfo.forEach(subtitle => {
            this.player_.addRemoteTextTrack({
              kind: 'subtitles',
              src: subtitle.subtitle_file,
              srclang: subtitle.language_code,
              label: subtitle.language,
              default: subtitle.is_default === 1
            }, false);
          });

          setTimeout(() => {
            const tracks = this.player_.textTracks();
            for (let i = 0; i < tracks.length; i++) {
              if (tracks[i].kind === 'subtitles' && subtitleInfo[i] && subtitleInfo[i].is_default === 1) {
                tracks[i].mode = 'showing';
              } else {
                tracks[i].mode = 'disabled';
              }
            }
          }, 0);

        }
        // Mount Skip Intro based on the same button used to skip trailer
        mountSkipIntroFrom(button);

        this.player_.load();

        // Use the reusable function for quality selector
        createQualitySelector(this.player_, qualityOptions, subtitleInfo, this.baseUrl);

            this.player_.one('loadedmetadata', () => {
              // Verify video actually loaded successfully
              const currentSrc = this.player_.src();
              if (!currentSrc || (typeof currentSrc === 'string' && currentSrc.trim() === '')) {
                console.error('Video failed to load - source is empty');
                // Reset contentVideoType to prevent auto-advance
                contentVideoType = 'trailer';
                episodeVideoPlaybackStarted = false;
                return;
              }
              // Reset auto-advance flag to prevent accidental episode skipping
              const nextBtn = this.player_.controlBar.getChild('NextEpisodeButton');
              if (nextBtn) {
                nextBtn.autoAdvanceTriggered = false;
              }
              this.player_.currentTime(0);
              this.player_.muted(true);
              this.player_.play()
                .then(() => {
                  this.trailerSkipped = true;
                  this.hide();
                  // Video started playing successfully - allow auto-advance
                  episodeVideoPlaybackStarted = true;
                  // Ensure NextEpisodeButton knows the correct current episode
                  if (nextBtn && currentEpisodeId) {
                    nextBtn.currentEpisodeId = String(currentEpisodeId);
                    nextBtn.updateVisibility();
                  }
                })
            .catch(error => {
              console.error('Error playing movie:', error);
              // Video failed to play - reset contentVideoType to prevent auto-advance
              contentVideoType = 'trailer';
              episodeVideoPlaybackStarted = false;
            });
        });
        // Add error handler to prevent auto-advance when video fails to load
        this.player_.one('error', () => {
          console.error('Video player error - episode video failed to load');
          // Reset contentVideoType to prevent auto-advance
          contentVideoType = 'trailer';
          episodeVideoPlaybackStarted = false;
        });
      } else {
        // Remote/encrypted video: use your existing logic
        fetch(`${this.baseUrl}/video/stream/${encodeURIComponent(videoUrl)}`)
          .then((response) => response.json())
          .then((data) => {
            setVideoSource(this.player_, data.platform, data.videoId, data.url, data.mimeType, qualityOptions, subtitleInfo);
            // Add subtitle tracks if available
            if (subtitleInfo && subtitleInfo.length > 0) {
              // Remove any existing subtitle tracks
              const existingTracks = this.player_.textTracks();
              for (let i = existingTracks.length - 1; i >= 0; i--) {
                this.player_.removeRemoteTextTrack(existingTracks[i]);
              }
              // Add new subtitle tracks
              subtitleInfo.forEach(subtitle => {
                this.player_.addRemoteTextTrack({
                  kind: 'subtitles',
                  src: subtitle.subtitle_file,
                  srclang: subtitle.language_code,
                  label: subtitle.language,
                  default: subtitle.is_default === 1
                }, false);
              });

              setTimeout(() => {
                const tracks = this.player_.textTracks();
                for (let i = 0; i < tracks.length; i++) {
                  if (tracks[i].kind === 'subtitles' && subtitleInfo[i] && subtitleInfo[i].is_default === 1) {
                    tracks[i].mode = 'showing';
                  } else {
                    tracks[i].mode = 'disabled';
                  }
                }
              }, 0);

            }
            // Mount Skip Intro based on the same button used to skip trailer
            mountSkipIntroFrom(button);

            this.player_.load();

            // Use the reusable function for quality selector
            createQualitySelector(this.player_, qualityOptions, subtitleInfo, this.baseUrl);

            this.player_.one('loadedmetadata', () => {
              // Verify video actually loaded successfully
              const currentSrc = this.player_.src();
              if (!currentSrc || (typeof currentSrc === 'string' && currentSrc.trim() === '')) {
                console.error('Video failed to load - source is empty');
                // Reset contentVideoType to prevent auto-advance
                contentVideoType = 'trailer';
                episodeVideoPlaybackStarted = false;
                return;
              }
              // Reset auto-advance flag to prevent accidental episode skipping
              const nextBtn = this.player_.controlBar.getChild('NextEpisodeButton');
              if (nextBtn) {
                nextBtn.autoAdvanceTriggered = false;
              }
              this.player_.currentTime(0);
              this.player_.muted(true);
              this.player_.play()
                .then(() => {
                  this.trailerSkipped = true;
                  this.hide();
                  // Video started playing successfully - allow auto-advance
                  episodeVideoPlaybackStarted = true;
                  // Ensure NextEpisodeButton knows the correct current episode
                  if (nextBtn && currentEpisodeId) {
                    nextBtn.currentEpisodeId = String(currentEpisodeId);
                    nextBtn.updateVisibility();
                  }
                })
                .catch(error => {
                  console.error('Error playing movie:', error);
                  // Video failed to play - reset contentVideoType to prevent auto-advance
                  contentVideoType = 'trailer';
                  episodeVideoPlaybackStarted = false;
                });
            });
            // Add error handler to prevent auto-advance when video fails to load
            this.player_.one('error', () => {
              console.error('Video player error - episode video failed to load');
              // Reset contentVideoType to prevent auto-advance
              contentVideoType = 'trailer';
              episodeVideoPlaybackStarted = false;
            });
          })
          .catch((error) => {
            console.error('Error loading video:', error);
            // Video URL fetch failed - reset contentVideoType to prevent auto-advance
            contentVideoType = 'trailer';
          });
      }
    }
  }

  // Register the new component
  videojs.registerComponent('SkipTrainerButton', SkipTrainerButton);


  class PreviousEpisodeButton extends Button {
    constructor(player, options) {
      super(player, options);
      this.controlText(typeof previousEpisodeText !== 'undefined' ? previousEpisodeText : 'Previous Episode');
      this.addClass('vjs-previous-episode-button');

      // Get button references
      this.watchNowButton = document.getElementById('watchNowButton');
      this.seasonWatchBtn = document.getElementById('seasonWatchBtn');

      // Get current episode info
      this.currentEpisodeId = this.watchNowButton?.getAttribute('data-episode-id') ||
        this.seasonWatchBtn?.getAttribute('data-episode-id');

      // Show button only if it's not the first episode
      this.updateVisibility();
    }

    createEl() {
      return super.createEl('button', {
        innerHTML: '<span class="vjs-icon-previous-item"></span>',
        className: 'vjs-previous-episode-button vjs-control vjs-button'
      });
    }

    updateVisibility() {
      const currentId = parseInt(this.currentEpisodeId);
      if (!this.currentEpisodeId || currentId <= 1) {
        this.hide();
      } else {
        this.show();
      }
    }

    handleClick() {
      // Get current episode button
      const button = this.watchNowButton || this.seasonWatchBtn;
      if (!button) return;

      // Get previous episode data
      const previousEpisodeId = parseInt(this.currentEpisodeId) - 1;
      const previousEpisodeButton = document.querySelector(`[data-episode-id="${previousEpisodeId}"]`);

      if (previousEpisodeButton) {
        // Store current time before switching
        const currentTime = this.player_.currentTime();

        // Update current episode ID before click
        this.currentEpisodeId = previousEpisodeId;

        // Trigger click on previous episode button
        previousEpisodeButton.click();

        currentEpisodeName = previousEpisodeButton.getAttribute('data-episode-name') || '';
        // Update episode name display
        if (window.updateEpisodeNameDisplay) {
          window.updateEpisodeNameDisplay(currentEpisodeName, currentEpisodeId);
        }

        // Update visibility after changing episode
        this.updateVisibility();

        // Handle video source change
        this.player_.one('loadedmetadata', () => {
          // Reset playback state
          this.player_.muted(true);
          this.player_.currentTime(0);
          this.player_.play().catch(error => {
            console.error('Error playing previous episode:', error);
          });
        });
      }
    }

    // Update episode ID when source changes
    updateEpisodeId(newId) {
      this.currentEpisodeId = newId;
      this.updateVisibility();
    }
  }

  // Register the component
  videojs.registerComponent('PreviousEpisodeButton', PreviousEpisodeButton);




  class NextEpisodeButton extends Button {
    constructor(player, options) {
      super(player, options);
      this.controlText(typeof nextEpisodeText !== 'undefined' ? nextEpisodeText : 'Next Episode');
      this.addClass('vjs-next-episode-button');
      this.watchNowButton = document.getElementById('watchNowButton');

      // Get initial episode ID from any season watch button
      const seasonWatchBtn = document.querySelector('.season-watch-btn');
      if (seasonWatchBtn) {
        this.currentEpisodeId = seasonWatchBtn.getAttribute('data-episode-id');
      } else {
        this.currentEpisodeId = this.watchNowButton?.getAttribute('data-episode-id');
      }

      this.totalEpisodes = this.getTotalEpisodes();
      this.player_ = player;
      // Get movieStartTime for trailer logic
      const videoElement = document.querySelector('#videoPlayer');
      this.movieStartTime = parseFloat(videoElement?.getAttribute('data-movie-start')) || 300;
      this.timeUpdateHandler = this.handleTimeUpdate.bind(this);
      this.player_.on('timeupdate', this.timeUpdateHandler);

      // Add click event listener to all season watch buttons
      document.querySelectorAll('.season-watch-btn').forEach(button => {
        button.addEventListener('click', (e) => {
          // Get episode ID from the clicked button
          const episodeId = e.currentTarget.getAttribute('data-episode-id');
          if (episodeId) {
            this.currentEpisodeId = String(episodeId);
            this.seasonWatchBtn = button; // Store reference
            this.updateVisibility();
            // Update poster image when episode changes
            if (this.el_ && this.el_.style.display !== 'none') {
              this.updatePosterImage();
            }
          }
        });
      });

      // Also listen for watchNowButton clicks (episode detail page)
      if (this.watchNowButton) {
        this.watchNowButton.addEventListener('click', (e) => {
          const episodeId = e.currentTarget.getAttribute('data-episode-id');
          if (episodeId) {
            this.currentEpisodeId = String(episodeId);
            this.seasonWatchBtn = null; // Clear seasonWatchBtn reference for episode detail page
            this.updateVisibility();
            // Update poster image when episode changes
            if (this.el_ && this.el_.style.display !== 'none') {
              this.updatePosterImage();
            }
          }
        });
      }

      this.removeExistingButtons();
      this.updateVisibility();
      this.removeExistingButtons();
      this.updateVisibility();

      // Update poster image if button is initially visible
      if (this.el_ && this.el_.style.display !== 'none') {
        this.updatePosterImage();
      }

      // Initialize auto-advance and bind ended fallback
      this.autoAdvanceTriggered = false;
      this.onEndedHandler = () => {
        if (contentVideoType === 'trailer' || contentVideoType === 'clip') return;
        // Verify current episode has valid video source before auto-advancing
        if (!this.hasValidCurrentEpisodeVideo()) {
          return; // Don't auto-advance if current episode video is unavailable
        }
        if (this.hasNextEpisode() && !this.isLastEpisode() && !this.autoAdvanceTriggered) {
          this.autoAdvanceTriggered = true;
          this.handleClick();
        }
      };
      this.player_.on('ended', this.onEndedHandler);
    }

    removeExistingButtons() {
      const existingButtons = document.querySelectorAll('.vjs-next-episode-button');
      existingButtons.forEach(button => {
        if (button !== this.el_) {
          button.remove();
        }
      });
    }

    createEl() {
      const button = super.createEl('button', {
        innerHTML: '<div class="next-episode-content"><div class="next-episode-poster"></div><div class="next-episode-text">' + (typeof nextEpisodeText !== 'undefined' ? nextEpisodeText : 'Next Episode') + '</div><div class="next-episode-timer" aria-hidden="true">30</div></div>',
        className: 'vjs-next-episode-button'
      });
      return button;
    }

    getTotalEpisodes() {
      // Use the same filtering logic as in handleClick
      const episodeButtons = Array.from(document.querySelectorAll('[data-episode-id]'));
      let episodeIds = episodeButtons.map(btn => btn.getAttribute('data-episode-id'));
      if (episodeIds.length > 1 && episodeIds[0] === episodeIds[1]) {
        episodeIds = episodeIds.slice(1);
      }
      return episodeIds.length;
    }

    handleTimeUpdate() {
      if (!this.player_) return;

      const currentTime = this.player_.currentTime();
      const duration = this.player_.duration();

      if (contentVideoType === 'trailer' || contentVideoType === 'clip') {
        this.hide();
        return;
      }

      // Show only in the last 30 seconds
      const showThresholdSeconds = 30;
      const trailerThreshold = Math.min(this.movieStartTime, duration * 0.3);

      // Hide if in trailer section
      if (currentTime < trailerThreshold) {
        this.hide();
        const skipBtn = this.player_.controlBar.getChild('SkipTrainerButton');
        if (skipBtn) skipBtn.show();
        // Reset auto-advance while in trailer
        this.autoAdvanceTriggered = false;
        return;
      }

      const timeLeft = duration - currentTime;

      // Show button when:
      // 1. Time left is less than or equal to 30 seconds
      // 2. There is a next episode
      // 3. It's not the last episode
      if (timeLeft <= showThresholdSeconds && this.hasNextEpisode() && !this.isLastEpisode()) {
        this.show();
        // Update countdown timer (30 -> 0 seconds); clamp final second to 0
        const secondsLeft = timeLeft <= 1 ? 0 : Math.max(0, Math.floor(timeLeft));
        const timerEl = this.el_ && this.el_.querySelector('.next-episode-timer');
        if (timerEl) timerEl.textContent = String(secondsLeft);
        this.updatePosterImage(); // Load the next episode's poster

        // Auto-advance when countdown hits 0 (only once) - but not for clips
        // Verify current episode has valid video source before auto-advancing
        if (secondsLeft === 0 && !this.autoAdvanceTriggered && contentVideoType !== 'clip' && this.hasValidCurrentEpisodeVideo()) {
          this.autoAdvanceTriggered = true;
          this.handleClick();
          return;
        }
      } else {
        this.hide();
        // Reset trigger when not in last-5s window
        this.autoAdvanceTriggered = false;
      }
    }

    isLastEpisode() {
      // Use the filtered episodeIds array
      const episodeButtons = Array.from(document.querySelectorAll('[data-episode-id]'));
      let episodeIds = episodeButtons.map(btn => btn.getAttribute('data-episode-id'));
      if (episodeIds.length > 1 && episodeIds[0] === episodeIds[1]) {
        episodeIds = episodeIds.slice(1);
      }
      const currentIndex = episodeIds.indexOf(this.currentEpisodeId);
      return currentIndex === episodeIds.length - 1;
    }

    hasValidCurrentEpisodeVideo() {
      // First check: contentVideoType must be 'video', not 'trailer' or 'clip'
      if (contentVideoType === 'trailer' || contentVideoType === 'clip') {
        return false;
      }

      // Critical check: Verify that episode video playback actually started successfully
      // This prevents auto-advance when video fails to load (e.g., YouTube CODE:1150)
      if (!episodeVideoPlaybackStarted) {
        return false; // Video hasn't started playing yet or failed to start
      }

      // Verify that the current episode has a valid, playable video source
      const watchNowButton = document.getElementById('watchNowButton');
      const currentId = currentEpisodeId || // Global variable (updated when button clicked)
        watchNowButton?.getAttribute('data-episode-id') ||
        this.seasonWatchBtn?.getAttribute('data-episode-id') ||
        this.currentEpisodeId;

      if (!currentId) return false;

      // Find the button for the current episode
      const currentEpisodeButton = document.querySelector(`[data-episode-id="${currentId}"]`);
      if (!currentEpisodeButton) return false;

      // Check if video URL exists and is not empty
      const videoUrl = currentEpisodeButton.getAttribute('data-video-url');
      if (!videoUrl || videoUrl.trim() === '') {
        return false; // No video URL available
      }

      // Verify player has a valid source loaded (not just trailer)
      if (this.player_ && this.player_.src) {
        const currentSrc = this.player_.src();
        // If player source is empty or invalid, episode video is not available
        if (!currentSrc || (typeof currentSrc === 'string' && currentSrc.trim() === '')) {
          return false;
        }
      }

      return true; // Current episode has valid video source
    }

    hasNextEpisode() {
      // First: Get current playing episode ID from global variable or active button
      const watchNowButton = document.getElementById('watchNowButton');
      const currentId = currentEpisodeId || // Global variable (updated when button clicked)
        watchNowButton?.getAttribute('data-episode-id') ||
        this.seasonWatchBtn?.getAttribute('data-episode-id') ||
        this.currentEpisodeId;

      if (!currentId) return false;

      // Second: Get all episode IDs from the container
      const findSeasonContainer = (el) => {
        let node = el;
        while (node && node !== document) {
          const items = node.querySelectorAll('[data-episode-id]');
          if (items && items.length > 1) return node;
          node = node.parentElement;
        }
        return document;
      };

      let seasonScope = null;
      if (this.seasonWatchBtn) {
        seasonScope = findSeasonContainer(this.seasonWatchBtn);
      } else if (watchNowButton) {
        const seasonsContainer = document.getElementById('seasons');
        seasonScope = seasonsContainer || findSeasonContainer(watchNowButton);
      }

      if (!seasonScope || seasonScope === document) {
        seasonScope = document;
      }

      const episodeButtons = Array.from(seasonScope.querySelectorAll('[data-episode-id]'));
      let episodeIds = episodeButtons.map(btn => btn.getAttribute('data-episode-id'));

      // Remove duplicates
      episodeIds = [...new Set(episodeIds)];
      if (episodeIds.length > 1 && episodeIds[0] === episodeIds[1]) {
        episodeIds = episodeIds.slice(1);
      }

      // Find current playing episode in the list
      const currentIndex = episodeIds.indexOf(String(currentId));
      const nextId = episodeIds[currentIndex + 1];

      return !!nextId;
    }

    updateVisibility() {
      if (!this.player_) return;

      if (contentVideoType === 'trailer' || contentVideoType === 'clip') {
        this.hide();
        return;
      }

      // Basic checks
      if (!this.currentEpisodeId || !this.hasNextEpisode() || this.isLastEpisode()) {
        this.hide();
        return;
      }

      const currentTime = this.player_.currentTime();
      const duration = this.player_.duration();

      // Show only in the last 30 seconds
      const showThresholdSeconds = 30;

      const timeLeft = duration - currentTime;

      // Show button if time left is less than or equal to 30 seconds
      if (timeLeft <= showThresholdSeconds) {
        this.show();
        // Update countdown timer (30 -> 0 seconds); clamp final second to 0
        const secondsLeft = timeLeft <= 1 ? 0 : Math.max(0, Math.floor(timeLeft));
        const timerEl = this.el_ && this.el_.querySelector('.next-episode-timer');
        if (timerEl) timerEl.textContent = String(secondsLeft);
        this.updatePosterImage(); // Load the next episode's poster
      } else {
        this.hide();
        this.autoAdvanceTriggered = false;
      }
    }

    updatePosterImage() {
      if (!this.el_) return;

      const watchNowButton = document.getElementById('watchNowButton');
      const currentId = currentEpisodeId || // Global variable (updated when button clicked)
        watchNowButton?.getAttribute('data-episode-id') ||
        this.seasonWatchBtn?.getAttribute('data-episode-id') ||
        this.currentEpisodeId;

      if (!currentId) return;

      const findSeasonContainer = (el) => {
        let node = el;
        while (node && node !== document) {
          const items = node.querySelectorAll('[data-episode-id]');
          if (items && items.length > 1) return node;
          node = node.parentElement;
        }
        return document;
      };

      let seasonScope = null;
      if (this.seasonWatchBtn) {
        seasonScope = findSeasonContainer(this.seasonWatchBtn);
      } else if (watchNowButton) {
        const seasonsContainer = document.getElementById('seasons');
        seasonScope = seasonsContainer || findSeasonContainer(watchNowButton);
      }

      if (!seasonScope || seasonScope === document) {
        seasonScope = document;
      }

      const episodeButtons = Array.from(seasonScope.querySelectorAll('[data-episode-id]'));
      let episodeIds = episodeButtons.map(btn => btn.getAttribute('data-episode-id'));

      episodeIds = [...new Set(episodeIds)];
      if (episodeIds.length > 1 && episodeIds[0] === episodeIds[1]) {
        episodeIds = episodeIds.slice(1);
      }

      const currentIndex = episodeIds.indexOf(String(currentId));
      const nextId = episodeIds[currentIndex + 1];

      if (nextId) {
        // Find the next episode button
        const filteredButtons = episodeButtons;
        if (episodeButtons.length > 1 && episodeButtons[0].getAttribute('data-episode-id') === episodeButtons[1].getAttribute('data-episode-id')) {
          filteredButtons.shift();
        }
        const nextEpisodeButton = filteredButtons[currentIndex + 1];

        if (nextEpisodeButton) {
          const posterUrl = nextEpisodeButton.getAttribute('data-poster-url');
          const episodeName = nextEpisodeButton.getAttribute('data-episode-name');

          if (posterUrl) {
            const posterElement = this.el_.querySelector('.next-episode-poster');
            if (posterElement) {
              posterElement.style.backgroundImage = `url('${posterUrl}')`;
            }
          }

          // Update episode name if available
          const textElement = this.el_.querySelector('.next-episode-text');
          if (textElement && episodeName) {
            const nextLabel = typeof nextText !== 'undefined' ? nextText : 'Next';
            textElement.textContent = `${nextLabel}: ${episodeName}`;
          }
        }
      }
    }

    async handleClick() {
      if (contentVideoType === 'trailer') return;
      const button = this.watchNowButton || this.seasonWatchBtn;
      if (!button) return;
      // Prevent duplicate auto-advances
      this.autoAdvanceTriggered = true;

      const watchNowButton = document.getElementById('watchNowButton');
      const currentId = currentEpisodeId || // Global variable (updated when button clicked)
        watchNowButton?.getAttribute('data-episode-id') ||
        this.seasonWatchBtn?.getAttribute('data-episode-id') ||
        this.currentEpisodeId;

      if (!currentId) return;

      const findSeasonContainer = (el) => {
        let node = el;
        while (node && node !== document) {
          const items = node.querySelectorAll('[data-episode-id]');
          if (items && items.length > 1) return node;
          node = node.parentElement;
        }
        return document;
      };

      let seasonScope = null;
      if (this.seasonWatchBtn) {
        seasonScope = findSeasonContainer(this.seasonWatchBtn);
      } else if (watchNowButton) {
        const seasonsContainer = document.getElementById('seasons');
        seasonScope = seasonsContainer || findSeasonContainer(watchNowButton);
      }

      if (!seasonScope || seasonScope === document) {
        seasonScope = document;
      }

      const episodeButtons = Array.from(seasonScope.querySelectorAll('[data-episode-id]'));
      let episodeIds = episodeButtons.map(btn => btn.getAttribute('data-episode-id'));

      episodeIds = [...new Set(episodeIds)];
      if (episodeIds.length > 1 && episodeIds[0] === episodeIds[1]) {
        episodeIds = episodeIds.slice(1);
      }

      const currentIndex = episodeIds.indexOf(String(currentId));
      const nextId = episodeIds[currentIndex + 1];


      // Find the corresponding button for nextId
      const filteredButtons = episodeButtons;
      if (episodeButtons.length > 1 && episodeButtons[0].getAttribute('data-episode-id') === episodeButtons[1].getAttribute('data-episode-id')) {
        filteredButtons.shift();
      }
      const nextEpisodeButton = filteredButtons[currentIndex + 1];

      if (nextEpisodeButton) {
        const wasPlaying = !this.player_.paused();
        // Immediately pause the current video to prevent replay
        this.player_.pause();
        // --- Pay-per-view logic start ---
        const accessType = nextEpisodeButton.getAttribute('data-movie-access');
        const episodeId = nextEpisodeButton.getAttribute('data-episode-id');
        const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
        if (accessType === 'pay-per-view') {
          setStartDate(nextEpisodeButton);
          try {
            const response = await fetch(`${baseUrl}/api/check-episode-purchase?episode_id=${episodeId}&t=${Date.now()}`);
            const data = await response.json();
            if (data.is_purchased == false) {
              // Not purchased: redirect to payment form
              window.location.href = `${baseUrl}/payment-form/pay-per-view?type=episode&id=${episodeId}`;
              return; // STOP here, do not continue!
            }
            // else: purchased, continue to play
          } catch (error) {
            console.error('[NextEpisodeButton] Error checking episode purchase:', error);
            return;
          }
        }

        // Update the current episode ID BEFORE clicking to prevent replay
        this.currentEpisodeId = nextId;
        nextEpisodeButton.click();

        currentEpisodeName = nextEpisodeButton.getAttribute('data-episode-name') || '';
        // Update episode name display
        if (window.updateEpisodeNameDisplay) {
          window.updateEpisodeNameDisplay(currentEpisodeName, currentEpisodeId);
        }

        this.player_.one('loadedmetadata', () => {
          this.player_.muted(true);
          this.player_.currentTime(0);
          // Reset for new episode
          this.autoAdvanceTriggered = false;
          const timerEl = this.el_ && this.el_.querySelector('.next-episode-timer');
          if (timerEl) timerEl.textContent = '30';
          this.updateVisibility();

          if (wasPlaying) {
            this.player_.play().catch(error => {
              console.error('Error playing next episode:', error);
            });
          }
        });
      } else {
        console.warn('No next episode available');
        this.hide();
      }
    }

    dispose() {
      if (this.player_) {
        this.player_.off('timeupdate', this.timeUpdateHandler);
        if (this.onEndedHandler) {
          this.player_.off('ended', this.onEndedHandler);
        }
      }
      super.dispose();
    }
  }
  videojs.registerComponent('NextEpisodeButton', NextEpisodeButton);


  class BackwordButton extends Button {
    constructor(player, options) {
      super(player, options);
      this.controlText(typeof backwardButtonText !== 'undefined' ? backwardButtonText : 'Backward Button');
      this.addClass('vjs-backword-button');
      this.player_ = player;
    }

    createEl() {
      const button = super.createEl('button', {
        className: 'vjs-backword-button vjs-control vjs-button'
      });

      const el = document.getElementById('videoPlayer');
      const n = el ? parseInt(el.getAttribute('data-backward-seconds') || '', 10) : NaN;
      const s = Number.isFinite(n) && n > 0 ? n : 30;
      button.title = `${s}s`;
      button.setAttribute('aria-label', `Backward ${s} seconds`);

      button.innerHTML = `
        <span class="vjs-icon-placeholder" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 24 24"
              width="24"
              height="24"
              fill="currentColor">
            <path d="M20 4v16l-8-8 8-8m-8 0v16l-8-8 8-8z"/>
          </svg>
        </span>
        <span id="backward-seconds-text">${s}s</span>
      `;
      return button;
    }

    handleClick() {
      const currentTime = this.player_.currentTime();
      const duration = this.player_.duration();
      const videoEl = document.getElementById('videoPlayer');
      const backwardAttr = videoEl ? parseInt(videoEl.getAttribute('data-backward-seconds') || '', 10) : NaN;
      const backwardSeconds = Number.isFinite(backwardAttr) && backwardAttr > 0 ? backwardAttr : 30;
      const newTime = Math.max(0, currentTime - backwardSeconds);
      this.player_.currentTime(newTime);

      // Save progress immediately when backward button is clicked
      window.saveWatchTimeImmediately?.(newTime, duration);
    }
  }

  class ForwordButton extends Button {
    constructor(player, options) {
      super(player, options);
      this.controlText(typeof forwardButtonText !== 'undefined' ? forwardButtonText : 'Forward Button');
      this.addClass('vjs-forword-button');
      this.player_ = player;
    }

    createEl() {
      const button = super.createEl('button', {
        className: 'vjs-forword-button vjs-control vjs-button'
      });

      const el = document.getElementById('videoPlayer');
      const n = el ? parseInt(el.getAttribute('data-forward-seconds') || '', 10) : NaN;
      const s = Number.isFinite(n) && n > 0 ? n : 30;
      button.title = `${s}s`;
      button.setAttribute('aria-label', `Forward ${s} seconds`);

      button.innerHTML = `
        <span class="vjs-icon-placeholder" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 24 24"
              width="24"
              height="24"
              fill="currentColor">
            <path d="M4 4l8 8-8 8V4m8 0l8 8-8 8V4z"/>
          </svg>
        </span>
        <span id="forward-seconds-text">${s}s</span>
      `;
      return button;
    }

    handleClick() {
      const currentTime = this.player_.currentTime();
      const duration = this.player_.duration();
      const videoEl = document.getElementById('videoPlayer');
      const forwardAttr = videoEl ? parseInt(videoEl.getAttribute('data-forward-seconds') || '', 10) : NaN;
      const forwardSeconds = Number.isFinite(forwardAttr) && forwardAttr > 0 ? forwardAttr : 30;
      const newTime = Math.min(duration, currentTime + forwardSeconds);
      this.player_.currentTime(newTime);

      // Save progress immediately when forward button is clicked
      window.saveWatchTimeImmediately?.(newTime, duration);
    }
  }

  videojs.registerComponent('BackwordButton', BackwordButton);
  videojs.registerComponent('ForwordButton', ForwordButton);

  function timeStringToSeconds(timeString) {
    const [hours, minutes, seconds] = timeString.split(':').map(Number)
    return hours * 3600 + minutes * 60 + seconds
  }

  // ADS CODE
  // console.log("contentId", contentId);
  // console.log("contentType", contentType);
  // console.log("currentCategoryId", currentCategoryId);



  var adQueue = [];
  var overlayAds = [];
  var playbackInterval = null;
  var midRollTime = 0;
  var midRollPlayed = false;
  var forcedMidRollPending = false;
  var postRollPlayed = false;
  let overlayAdsScheduled = false;
  var currentAd = null;
  var skipButton = null;
  var skipTimeout = null;
  let customAdPlayed = false;
  let blockPlay = false;
  let customAdShowing = false;
  let customAdAttempts = 0;
  const MAX_AD_ATTEMPTS = 3;
  let customAdsDisabled = false; // Nuclear option to disable custom ads

  function debugLog(message, data = null) {
    const timestamp = new Date().toISOString();
    // console.log(`[${timestamp}] ${message}`, data ?? '');
  }

  function showAdLoader() { }
  function hideAdLoader() { }

  function formatTime(seconds) {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = Math.floor(seconds % 60);
    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
  }

  function parseTimeToSeconds(timeString) {
    if (!timeString) return 0;
    const parts = timeString.split(':');
    if (parts.length === 2) {
      return parseInt(parts[0]) * 60 + parseInt(parts[1]);
    }
    return parseInt(timeString);
  }

  function createSkipButton() {
    if (skipButton) {
      skipButton.remove();
    }

    skipButton = document.createElement('div');
    skipButton.className = 'vjs-skip-ad-button';
    skipButton.innerHTML = 'Skip Ad';
    skipButton.style.cssText = `
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    z-index: 1001;
    font-size: 14px;
    font-weight: bold;
    transition: all 0.3s ease;
  `;

    skipButton.addEventListener('mouseenter', () => {
      skipButton.style.background = 'rgba(255, 0, 0, 0.9)';
    });

    skipButton.addEventListener('mouseleave', () => {
      skipButton.style.background = 'rgba(0, 0, 0, 0.8)';
    });

    skipButton.addEventListener('click', () => {
      debugLog('Skip button clicked');
      skipAd();
    });

    return skipButton;
  }

  function showSkipButton(afterSeconds) {
    if (!currentAd || !currentAd.enable_skip) return;

    debugLog('Setting up skip button', {
      skipAfter: currentAd.skip_after,
      skipAfterSeconds: afterSeconds
    });

    skipTimeout = setTimeout(() => {
      const button = createSkipButton();
      player.el().appendChild(button);
      debugLog('Skip button shown after', afterSeconds + ' seconds');
    }, afterSeconds * 1000);
  }

  function hideSkipButton() {
    if (skipButton) {
      skipButton.remove();
      skipButton = null;
    }
    if (skipTimeout) {
      clearTimeout(skipTimeout);
      skipTimeout = null;
    }
    currentAd = null;
  }

  function hideIMASkipButton() {
    const adContainers = document.querySelectorAll('.ima-ad-container, .vjs-ima-ad-container');
    adContainers.forEach(container => {
      container.querySelectorAll('button, div').forEach(el => {
        if (
          el.textContent && el.textContent.trim().toLowerCase().includes('skip ad') &&
          el.offsetParent !== null
        ) {
          el.style.display = 'none';
          el.style.visibility = 'hidden';
          el.style.opacity = '0';
          el.style.pointerEvents = 'none';
          el.style.width = '0';
          el.style.height = '0';
        }
      });
    });
  }

  function observeAndRemoveIMASkipButton() {
    const adContainer = document.querySelector('.ima-ad-container, .vjs-ima-ad-container');
    if (!adContainer) return;

    const observer = new MutationObserver(() => {
      hideIMASkipButton();
    });

    observer.observe(adContainer, { childList: true, subtree: true });
    player.one('adend', () => observer.disconnect());
  }

  function skipAd() {
    debugLog('Custom skip button clicked');
    hideSkipButton();

    try {
      // Pause ad playback
      player.pause();

      // Wait a tiny bit and force end the ad manually
      setTimeout(() => {
        debugLog('Force ending ad via adend trigger');
        player.trigger('adend');
      }, 100); // You can keep this short (100ms)
    } catch (e) {
      debugLog('Error during manual skip', e);
      player.trigger('adend');
    }
  }


  function markAdCue(seconds, label = 'Ad') {
    const duration = player.duration();
    if (!duration || isNaN(duration)) return;

    const percent = (seconds / duration) * 100;
    const cue = document.createElement('div');
    cue.className = 'vjs-ad-cue';
    cue.title = label;
    cue.style.left = `${percent}%`;

    const progressControl = player.el().querySelector('.vjs-progress-holder');
    if (progressControl) {
      progressControl.appendChild(cue);
    }
  }

  function playAdsByType(type, onComplete = () => { }) {
    const ads = adQueue.filter(a => a.type === type);
    if (!ads.length) return onComplete();

    debugLog(`Starting ${ads.length} ${type} ads`, ads);
    let index = 0;

    const playNextAd = () => {
      if (index >= ads.length) return onComplete();

      const ad = ads[index];
      currentAd = ad;
      debugLog(`Playing ${type} ad #${index + 1}`, ad);

      try {
        showAdLoader();

        const vastUrl = ad.url;
        debugLog('Loading VAST XML', { vastUrl });

        player.ima.initializeAdDisplayContainer(); // ✅ ensure fresh IMA container
        player.ima.changeAdTag(vastUrl);
        player.ima.requestAds();
        player.ima.playAdBreak();

        player.one('adstart', () => {
          debugLog(`${type} ad #${index + 1} started`);
          hideAdLoader();
          observeAndRemoveIMASkipButton();

          if (ad.enable_skip && ad.skip_after) {
            const skipAfterSeconds = parseTimeToSeconds(ad.skip_after);
            showSkipButton(skipAfterSeconds);
          }
        });

        player.one('adend', () => {
          debugLog(`${type} ad #${index + 1} ended`);
          hideSkipButton();
          index++;
          playNextAd();
        });

        player.one('adserror', (e) => {
          debugLog(`${type} ad #${index + 1} error`, e);
          hideSkipButton();
          index++;
          playNextAd();
        });

        player.one('adskip', () => {
          debugLog(`${type} ad #${index + 1} skipped`);
          hideSkipButton();
          index++;
          playNextAd();
        });

      } catch (e) {
        debugLog(`Error playing ${type} ad`, e);
        hideSkipButton();
        index++;
        playNextAd();
      }
    };

    playNextAd();
  }

  function scheduleOverlayAds() {
    if (!player || overlayAdsScheduled) return;
    const duration = player.duration();

    // Better duration validation
    if (!duration || isNaN(duration) || duration <= 0) {
      // console.log('Duration not available for overlay ads, retrying...');
      setTimeout(() => scheduleOverlayAds(), 1000);
      return;
    }

    const availableOverlayAds = adQueue.filter(a => a.type === 'overlay');
    if (availableOverlayAds.length === 0) return;

    overlayAdsScheduled = true;
    overlayAds = availableOverlayAds;

    const overlaySchedule = [
      { percent: 10, shown: false },
      { percent: 65, shown: false }
    ];

    overlayAds.forEach((ad, index) => {
      if (overlaySchedule[index]) {
        const time = Math.floor((overlaySchedule[index].percent / 100) * duration);
        overlaySchedule[index].time = time;
        markAdCue(time, `Overlay Ad #${index + 1}`);
      }
    });

    const overlayCheckInterval = setInterval(() => {
      const current = player.currentTime();

      overlaySchedule.forEach((schedule, index) => {
        if (!schedule.shown && current >= schedule.time && current < schedule.time + 2) {
          schedule.shown = true;
          const ad = overlayAds[index];
          if (ad) showOverlayAd(ad.url, index + 1);
        }
      });

      if (overlaySchedule.every((s, i) => i >= overlayAds.length || s.shown)) {
        clearInterval(overlayCheckInterval);
      }
    }, 1000);
  }

  function showOverlayAd(url, index = 1) {
    debugLog(`Showing overlay ad #${index}`, url);

    const overlay = document.createElement('div');
    overlay.className = 'custom-overlay-ad';
    overlay.style.position = 'absolute';
    overlay.style.bottom = '10%';
    overlay.style.left = '10%';
    overlay.style.width = '80%';
    overlay.style.height = '100px';
    overlay.style.backgroundColor = 'rgba(0,0,0,0.8)';
    overlay.style.color = '#fff';
    overlay.style.zIndex = 1000;
    // overlay.style.padding = '10px';
    // overlay.style.borderRadius = '8px';
    overlay.innerHTML = `
    <iframe src="${url}" style="width:100%; height:100%; border:none;"></iframe>
    <button style="position:absolute; top:5px; right:5px; background:#f00; color:#fff; border:none; padding:5px; cursor:pointer;">&times;</button>
  `;

    overlay.querySelector('button').onclick = () => overlay.remove();
    player.el().appendChild(overlay);
    setTimeout(() => overlay.remove(), 15000);
  }


  function showCustomAdThenPlayMain(afterAdCallback) {
    // Nuclear option - disable custom ads if they're causing problems
    if (customAdsDisabled) {
      console.warn('Custom ads disabled due to previous errors');
      if (typeof afterAdCallback === 'function') afterAdCallback();
      return;
    }

    // Prevent infinite loops
    if (customAdAttempts >= MAX_AD_ATTEMPTS) {
      console.warn('Max custom ad attempts reached, disabling custom ads...');
      customAdsDisabled = true;
      customAdPlayed = true;
      if (typeof afterAdCallback === 'function') afterAdCallback();
      return;
    }

    // Prevent multiple simultaneous ad attempts
    if (customAdShowing) {
      console.warn('Custom ad already showing, skipping...');
      if (typeof afterAdCallback === 'function') afterAdCallback();
      return;
    }

    if (customAdPlayed) {
      if (typeof afterAdCallback === 'function') afterAdCallback();
      return;
    }

    customAdAttempts++;

    const params = new URLSearchParams();
    if (contentId) params.append('content_id', contentId);
    if (contentType) params.append('type', contentType);
    if (currentCategoryId) params.append('category_id', currentCategoryId);
    if (contentVideoType) params.append('video_type', contentVideoType);
    const CustomapiUrl = `${baseUrl}/api/custom-ads/get-active?${params.toString()}`;
    // console.log(CustomapiUrl);
    fetch(CustomapiUrl)
      .then(res => res.json())
      .then(data => {
        if (data.success && Array.isArray(data.data)) {
          const ad = data.data.find(item => item.placement === 'player' && item.status == 1);
          if (ad) {
            try {
              customAdPlayed = false;
              blockPlay = true;
              customAdShowing = true;
              player.pause();
              player.currentTime(0);
              player.el().style.pointerEvents = 'none';
            } catch (error) {
              console.error('Error setting up custom ad:', error);
              customAdShowing = false;
              blockPlay = false;
              if (typeof afterAdCallback === 'function') afterAdCallback();
              return;
            }

            function preventPlayWhileAd(e) {
              if (blockPlay) {
                e.preventDefault();
                e.stopImmediatePropagation();
                e.stopPropagation();

                // Only pause if not already paused to prevent infinite loops
                if (!player.paused()) {
                  player.pause();
                }

                // Only set currentTime if it's not already 0
                if (player.currentTime() !== 0) {
                  player.currentTime(0);
                }

                return false;
              }
            }

            // Use a more targeted approach to prevent infinite loops
            let isHandlingEvent = false;
            let eventCount = 0;
            const MAX_EVENTS_PER_SECOND = 5;

            function safePreventPlayWhileAd(e) {
              if (isHandlingEvent || !blockPlay) return;

              eventCount++;
              if (eventCount > MAX_EVENTS_PER_SECOND) {
                console.warn('Too many events, blocking further processing');
                return;
              }

              isHandlingEvent = true;
              try {
                preventPlayWhileAd(e);
              } finally {
                // Reset flag after a short delay to prevent rapid firing
                setTimeout(() => {
                  isHandlingEvent = false;
                }, 200);
              }
            }

            // Reset event counter every second
            setInterval(() => {
              eventCount = 0;
            }, 1000);

            // Store event listeners for cleanup
            const eventListeners = [
              { element: player.el(), event: 'play', handler: safePreventPlayWhileAd, useCapture: true },
              { element: player.el(), event: 'playing', handler: safePreventPlayWhileAd, useCapture: true }
            ];

            // Add event listeners
            eventListeners.forEach(({ element, event, handler, useCapture }) => {
              element.addEventListener(event, handler, useCapture);
            });

            // Add player event listeners with throttling
            player.on('play', safePreventPlayWhileAd);
            player.on('playing', safePreventPlayWhileAd);

            const modal = document.getElementById('customAdModal');
            const content = document.getElementById('customAdContent');
            let adHtml = '';

            if (ad.type === 'image') {
              if (ad.redirect_url) {
                adHtml = `<a href="${ad.redirect_url}" target="_blank"><img src="${ad.media}" alt="Ad" style="max-width:100%; height:auto;" /></a>`;
              } else {
                adHtml = `<img src="${ad.media}" alt="Ad" style="max-width:100%; height:auto;" />`;
              }
            } else if (ad.type === 'video') {
              if (/youtu\.?be/.test(ad.media)) {
                let videoId = '';
                const ytMatch = ad.media.match(/(?:youtu\.be\/|youtube\.com.*(?:v=|\/embed\/|\/v\/|\/shorts\/))([a-zA-Z0-9_-]{11})/);
                if (ytMatch && ytMatch[1]) {
                  videoId = ytMatch[1];
                }
                if (videoId) {
                  adHtml = `<div style="position:relative;width:100%;height:100%;"><iframe id="customAdVideo" width="100%" height="100%" src="https://www.youtube.com/embed/${videoId}?autoplay=1&mute=1&rel=0" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen style="border-radius:12px; box-shadow:0 8px 32px rgba(0,0,0,0.7);"></iframe>`;
                  if (ad.redirect_url) {
                    adHtml += `<div id="adClickOverlay" style="position:absolute;top:0;left:0;width:100%;height:100%;z-index:10;cursor:pointer;"></div>`;
                  }
                  adHtml += `</div>`;
                } else {
                  adHtml = `<div style="color:#fff;">Invalid YouTube URL</div>`;
                }
              } else if (ad.url_type == "url") {
                adHtml = `<div style="position:relative;width:100%;height:100%;"><video id="customAdVideo" autoplay style="background:#000;width:100%;height:100%;"><source src="${ad.media}" type="video/mp4"></video>`;
                if (ad.redirect_url) {
                  adHtml += `<div id="adClickOverlay" style="position:absolute;top:0;left:0;width:100%;height:100%;z-index:10;cursor:pointer;"></div>`;
                }
                adHtml += `</div>`;
              } else {
                adHtml = `<div style="position:relative;width:100%;height:100%;"><video id="customAdVideo" autoplay style="background:#000;width:100%;height:100%;"><source src="${ad.media}" type="video/mp4"></video>`;
                if (ad.redirect_url) {
                  adHtml += `<div id="adClickOverlay" style="position:absolute;top:0;left:0;width:100%;height:100%;z-index:10;cursor:pointer;"></div>`;
                }
                adHtml += `</div>`;
              }
            }

            // Clear existing content
            content.querySelectorAll('img,video,iframe,a,div').forEach(el => el.remove());
            content.insertAdjacentHTML('afterbegin', adHtml);
            modal.style.display = 'flex';

            const closeBtn = document.getElementById('customAdCloseBtn');
            closeBtn.onclick = () => {
              closeCustomAdModal();
            };

            if (ad.type === 'image') {
              setTimeout(() => {
                if (modal.style.display === 'flex') {
                  closeCustomAdModal();
                }
              }, 5000000);
            }

            if (ad.type === 'video') {
              const adVideo = document.getElementById('customAdVideo');
              if (adVideo) {
                adVideo.onended = () => {
                  closeCustomAdModal();
                };
              }
            }

            function closeCustomAdModal() {
              modal.style.display = 'none';
              content.querySelectorAll('img,video,iframe,a,div').forEach(el => el.remove());

              // Clean up event listeners
              eventListeners.forEach(({ element, event, handler, useCapture }) => {
                element.removeEventListener(event, handler, useCapture);
              });

              player.off('play', safePreventPlayWhileAd);
              player.off('playing', safePreventPlayWhileAd);

              player.el().style.pointerEvents = '';
              blockPlay = false;
              customAdPlayed = true;
              customAdShowing = false;

              if (typeof afterAdCallback === 'function') afterAdCallback();
            }

            // Add click handler for overlay if present
            if (ad.redirect_url) {
              setTimeout(() => {
                const overlay = document.getElementById('adClickOverlay');
                if (overlay) {
                  overlay.onclick = () => {
                    window.open(ad.redirect_url, '_blank');
                  };
                }
              }, 0);
            }

            return;
          }
        }

        // ✅ No custom ad → proceed to VAST anyway
        if (typeof afterAdCallback === 'function') afterAdCallback();
      })
      .catch((error) => {
        console.error('Error fetching custom ads:', error);
        // ✅ Fetch failed → still proceed to VAST
        if (typeof afterAdCallback === 'function') afterAdCallback();
      });

    // // Add timeout to prevent hanging
    // setTimeout(() => {
    //   if (customAdShowing && !customAdPlayed) {
    //     console.warn('Custom ad timeout, forcing cleanup...');
    //     customAdShowing = false;
    //     blockPlay = false;
    //     customAdPlayed = true;
    //     if (typeof afterAdCallback === 'function') afterAdCallback();
    //   }
    // }, 30000); // 30 second timeout
  }


  function markAdCuesForAvailableAds() {
    const duration = player.duration();
    // console.log('Duration check:', duration, 'isNaN:', isNaN(duration));

    // Better duration validation with retry
    if (!duration || isNaN(duration) || duration <= 0) {
      // console.log('Invalid duration, retrying in 1 second...');
      setTimeout(() => markAdCuesForAvailableAds(), 1000);
      return;
    }

    // Clear any existing ad cues EXCEPT overlay cues
    const existingCues = player.el().querySelectorAll('.vjs-ad-cue');
    existingCues.forEach(cue => {
      if (!cue.title.includes('Overlay')) {
        cue.remove();
      }
    });

    // Reset mid-roll time
    midRollTime = 0;

    // Mark cues only for ads that are actually available
    adQueue.forEach((ad, index) => {
      switch (ad.type) {
        case 'mid-roll':
          midRollTime = Math.floor(duration / 2);
          markAdCue(midRollTime, 'Mid-roll Ad');
          debugLog('Set mid-roll time', { midRollTime, duration });
          break;
        case 'post-roll':
          markAdCue(Math.floor(duration * 0.9), 'Post-roll Ad');
          break;
        // Overlay cues are handled in scheduleOverlayAds and not removed here
        default:
          break;
      }
    });

    debugLog('Marked ad cues for available ads', {
      totalAds: adQueue.length,
      adTypes: adQueue.map(a => a.type),
      midRollTime: midRollTime,
      hasMidRollAds: adQueue.filter(a => a.type === 'mid-roll').length > 0
    });
  }


  function startPlaybackInterval() {
    if (playbackInterval) clearInterval(playbackInterval);

    playbackInterval = setInterval(() => {
      const current = player.currentTime();
      const duration = player.duration();

      if (!duration || isNaN(duration) || duration <= 0) {
        // console.log('Duration not available yet, skipping interval check');
        return;
      }

      if (!midRollPlayed && midRollTime > 0 && current >= midRollTime) {
        midRollPlayed = true;
        // console.log('Playing mid-roll ad at', current);
        player.pause();
        playAdsByType('mid-roll', () => player.play());
      }

      if (!midRollPlayed && midRollTime > 0 && current > midRollTime + 5 && !forcedMidRollPending) {
        forcedMidRollPending = true;
        // console.log('Setting forced mid-roll pending');
      }

      if (forcedMidRollPending && midRollTime > 0 && current > midRollTime + 5) {
        midRollPlayed = true;
        forcedMidRollPending = false;
        // console.log('Playing forced mid-roll ad');
        player.pause();
        playAdsByType('mid-roll', () => player.play());
      }

      if (!postRollPlayed && duration > 0 && current >= duration * 0.9) {
        postRollPlayed = true;
        // console.log('Playing post-roll ad at', current);
        playAdsByType('post-roll');
      }

      if (!midRollPlayed && adQueue.filter(a => a.type === 'mid-roll').length > 0) {
        // console.log('[AD DEBUG] Mid-roll ad expected but not played. Time:', current);
      }
      if (!postRollPlayed && adQueue.filter(a => a.type === 'post-roll').length > 0) {
        // console.log('[AD DEBUG] Post-roll ad expected but not played. Time:', current);
      }
    }, 1000);
  }

  player.ready(function () {
    debugLog('Player ready');
    // console.log('Video source:', player.src());
    // console.log('Video readyState:', player.readyState());

    // Global error handler to catch YouTube and other video errors
    player.on('error', function (error) {
      const errorCode = error?.code;
      const errorMessage = error?.message || '';
      
      // Check for YouTube embedding errors (CODE:1150) or other critical errors
      if (errorCode === 1150 || 
          errorMessage.includes('Playback on other Websites has been disabled') ||
          errorMessage.includes('Video unavailable') ||
          errorCode === 2 || // MEDIA_ERR_NETWORK
          errorCode === 3 || // MEDIA_ERR_DECODE
          errorCode === 4) { // MEDIA_ERR_SRC_NOT_SUPPORTED
        console.error('Video playback error detected:', errorCode, errorMessage);
        // Reset contentVideoType to prevent auto-advance to next episode
        if (contentVideoType === 'video') {
          contentVideoType = 'trailer';
          episodeVideoPlaybackStarted = false;
        }
      }
    });

    player.on('playing', function () {
      if (contentVideoType !== 'trailer' && contentVideoType !== 'clip') {
        if (!player.paused()) {
          isPopupShown = false;
          // Mark that episode video playback has started successfully
          episodeVideoPlaybackStarted = true;
        }
      }
    });

    // Reset flag when video source changes
    player.on('loadstart', function () {
      episodeVideoPlaybackStarted = false;
    });

    // Monitor for excessive errors and disable custom ads if needed
    let errorCount = 0;
    const MAX_ERRORS = 50;

    const originalConsoleError = console.error;
    console.error = function (...args) {
      errorCount++;
      if (errorCount > MAX_ERRORS && !customAdsDisabled) {
        console.warn('Too many errors detected, disabling custom ads');
        customAdsDisabled = true;
        customAdPlayed = true;
        blockPlay = false;
        customAdShowing = false;
      }
      originalConsoleError.apply(console, args);
    };

    // IMA setup
    const adsRenderingSettings = new google.ima.AdsRenderingSettings();
    adsRenderingSettings.enablePreloading = true;
    adsRenderingSettings.uiElements = [
      google.ima.UiElements.AD_ATTRIBUTION,
      google.ima.UiElements.COUNTDOWN,
    ];
    adsRenderingSettings.useStyledLinearAds = true;

    player.ima({
      id: 'videoPlayer',
      adTagUrl: '',
      debug: true,
      showControlsForJSAds: true,
      adsRenderingSettings: adsRenderingSettings,
      disableCustomPlaybackForIOS10Plus: true,
      contribAdsSettings: {
        prerollTimeout: 15000,  // 15s: VAST fetch + IMA load can take 5–10+ seconds
        postrollTimeout: 15000,
        disablePlayContentBehindAd: true
      }
    });

    let customAdChecked = false;
    player.one('play', function () {
      if (!customAdPlayed && !customAdChecked) {
        customAdChecked = true;
        player.pause();
        showCustomAdThenPlayMain(function () {
          player.ima.initializeAdDisplayContainer();
          loadAdsAndStartInterval(); // ✅ NEW central logic
        });
        return;
      }

      player.ima.initializeAdDisplayContainer();
      loadAdsAndStartInterval(); // ✅ in case no custom ad
    });

    player.on('ended', function () {
      debugLog('Video ended, checking for remaining ads');
      hideSkipButton();

      if (!postRollPlayed) {
        postRollPlayed = true;
        playAdsByType('post-roll');
      }

      if (!midRollPlayed && midRollTime > 0) {
        midRollPlayed = true;
        playAdsByType('mid-roll');
      }

      if (playbackInterval) clearInterval(playbackInterval);
    });

    function ensureSkipButtonsPosition() {
      try {
        const BackwordCtor = videojs.getComponent('BackwordButton');
        const ForwordCtor = videojs.getComponent('ForwordButton');
        if (!BackwordCtor || !ForwordCtor) return;

        const playerControlBar = player.controlBar;
        const cbBackword = playerControlBar && playerControlBar.getChild && playerControlBar.getChild('BackwordButton');
        const cbForword = playerControlBar && playerControlBar.getChild && playerControlBar.getChild('ForwordButton');
        if (cbBackword && cbBackword.dispose) cbBackword.dispose();
        if (cbForword && cbForword.dispose) cbForword.dispose();

        let backwordBtn = player.getChild && player.getChild('BackwordButton');
        let forwordBtn = player.getChild && player.getChild('ForwordButton');
        if (!backwordBtn) {
          backwordBtn = new BackwordCtor(player);
          backwordBtn.name_ = 'BackwordButton';
          player.addChild(backwordBtn);
        }
        if (!forwordBtn) {
          forwordBtn = new ForwordCtor(player);
          forwordBtn.name_ = 'ForwordButton';
          player.addChild(forwordBtn);
        }

        const root = player.el && player.el();
        const backwordEl = backwordBtn && backwordBtn.el && backwordBtn.el();
        const forwordEl = forwordBtn && forwordBtn.el && forwordBtn.el();
        if (!root || !backwordEl || !forwordEl) return;

        backwordEl.classList.add('vjs-overlay-skip', 'vjs-overlay-backword');
        forwordEl.classList.add('vjs-overlay-skip', 'vjs-overlay-forword');

        if (backwordEl.parentNode !== root) {
          backwordEl.parentNode && backwordEl.parentNode.removeChild(backwordEl);
          root.appendChild(backwordEl);
        }
        if (forwordEl.parentNode !== root) {
          forwordEl.parentNode && forwordEl.parentNode.removeChild(forwordEl);
          root.appendChild(forwordEl);
        }
      } catch (err) {
        console.warn('ensureSkipButtonsPosition error:', err);
      }
    }

    player.on('loadedmetadata', function () {
      // console.log('Metadata loaded, duration:', player.duration());
      scheduleOverlayAds();
      if (adQueue.length > 0) {
        markAdCuesForAvailableAds();
      }
      ensureSkipButtonsPosition();
    });


    player.on('loadstart', () => setTimeout(ensureSkipButtonsPosition, 100));
    player.one('play', ensureSkipButtonsPosition);

    player.on('durationchange', function () {
      // console.log('Duration changed:', player.duration());
      if (adQueue.length > 0) {
        markAdCuesForAvailableAds();
      }
    });

    // Debug interval for tracking playback
    setInterval(() => {
      if (player.duration() > 0) {
        // console.log('DEBUG - Current time:', player.currentTime(), 'Duration:', player.duration(), 'Mid-roll time:', midRollTime);
      }
    }, 5000);
  });

  function loadAdsAndStartInterval() {
    const params = new URLSearchParams();
    if (contentId) params.append('content_id', contentId);
    if (contentType) params.append('type', contentType);
    if (contentVideoType) params.append('video_type', contentVideoType);
    if (currentCategoryId) params.append('category_id', currentCategoryId);
    if (currentEpisodeId) params.append('episode_id', currentEpisodeId);
    const apiUrl = `${baseUrl}/api/vast-ads/get-active?${params.toString()}`;
    // console.log('Loading ads from API', apiUrl);

    fetch(apiUrl)
      .then(res => res.json())
      .then(response => {
        // console.log('Ads API response', response);
        // Backend responses use either `status` (ApiResponse) or `success` (some endpoints).
        // Accept both so VAST ads don't get skipped incorrectly.
        const ok = !!(response && (response.success === true || response.status === true));
        if (!ok || !response.data) throw new Error('No ads');
        adQueue = response.data;
        overlayAds = adQueue.filter(a => a.type === 'overlay');
        // console.log('Loaded ad types:', adQueue.map(a => a.type));

        const hasPreRoll = adQueue.some(ad => ad.type === 'pre-roll');

        // Wait for metadata to be loaded before scheduling ads
        if (player.readyState() >= 1) {
          markAdCuesForAvailableAds();
          scheduleOverlayAds();
        } else {
          player.one('loadedmetadata', () => {
            markAdCuesForAvailableAds();
            scheduleOverlayAds();
          });
        }

        // Ensure playback interval starts after ads setup
        const startVideo = () => {
          player.play();
          // console.log("[AD DEBUG] Starting playback interval");
          startPlaybackInterval();
        };

        // Pre-roll logic
        if (hasPreRoll) {
          playAdsByType('pre-roll', startVideo);
        } else {
          startVideo();
        }
      })
      .catch(err => {
        // console.log('[AD DEBUG] Ad fetch error', err);
        player.play();
        startPlaybackInterval();
      });
  }

  // if (isIOS()) {
  //   player.ready(function() {
  //     // Stop Video.js from auto-hiding on iOS
  //     player.userActive(true);
  //     player.on('userinactive', () => player.userActive(true));
  //   });
  // }
});
