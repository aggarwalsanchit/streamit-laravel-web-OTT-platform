document.addEventListener('DOMContentLoaded', function() {
    // Safe Env URL Retrieval
    const metaBaseUrl = document.querySelector('meta[name="baseUrl"]');
    const envURL = metaBaseUrl ? metaBaseUrl.getAttribute('content') : '';

    // DOM Elements
    const wrapper = document.querySelector('.search-input-wrapper');
    const searchInput = document.getElementById('search-query');
    const iconBtn = document.getElementById('search-icon-btn');
    const closeBtn = document.getElementById('search-close-btn');
    const suggestionsBox = document.getElementById('search-suggestions');

    // Critical Element Check - Exit if search bar doesn't exist on this page
    if (!wrapper || !searchInput || !iconBtn) {
        return;
    }

    let debounceTimer;

    // Translations and Config
    const config = window.searchConfig || {};
    const translations = config.translations || {};

    // Toggle Expand
    const expandSearch = () => {
        wrapper.classList.add('active');
        searchInput.focus();
        // Show mic button only while search is open
        const mic = document.getElementById('search-mic-btn');
        if (mic && mic.dataset.micSupported === 'true') mic.style.display = 'flex';
    };

    const collapseSearch = () => {
             // Only collapse if empty
             if(searchInput.value.trim() === '') {
                 wrapper.classList.remove('active');
                 if(suggestionsBox) suggestionsBox.classList.add('d-none');
                 // Hide mic button when search bar closes
                 const mic = document.getElementById('search-mic-btn');
                 if (mic) mic.style.display = 'none';
             }
    };

    // Icon Click Handler
    iconBtn.addEventListener('click', (e) => {
        e.preventDefault();
        if(searchInput.value.trim() !== '') {
            performSearch();
        } else {
            if(wrapper.classList.contains('active')) collapseSearch();
            else expandSearch();
        }
    });

    // Click Text Input
    searchInput.addEventListener('click', (e) => {
         e.stopPropagation();
         if(!wrapper.classList.contains('active')) expandSearch();
    });

    // Close/Clear Button
    if (closeBtn) {
        closeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            searchInput.value = '';
            closeBtn.classList.add('d-none');
            if(suggestionsBox) suggestionsBox.classList.add('d-none');
            searchInput.focus();
        });
    }

    // Click Outside to Collapse
    document.addEventListener('click', (e) => {
        if (!wrapper.contains(e.target) && (!suggestionsBox || !suggestionsBox.contains(e.target))) {
            if (wrapper.classList.contains('active')) {
                 collapseSearch();
                 if(suggestionsBox) suggestionsBox.classList.add('d-none');
            }
        }
    });

    // Input Handler
    searchInput.addEventListener('input', function() {
        const term = this.value.trim();
        
        // Show/Hide Close Button
        if (closeBtn) {
            if(term.length > 0) closeBtn.classList.remove('d-none');
            else closeBtn.classList.add('d-none');
        }

        clearTimeout(debounceTimer);

        if (term.length < 2) {
            if(suggestionsBox) suggestionsBox.classList.add('d-none');
            return;
        }

        debounceTimer = setTimeout(() => {
            const searchUrl = `${envURL}/search/live?q=${encodeURIComponent(term)}`;
            fetch(searchUrl)
                .then(response => response.json())
                .then(data => {
                    renderSuggestions(data);
                })
                .catch(console.error);
        }, 300);
    });

    // --- Voice Search Implementation ---
    const micBtn = document.getElementById('search-mic-btn');
    
    // Detect Speech Recognition API (standard or webkit prefixed)
    const SpeechRecognitionAPI = window.SpeechRecognition || window.webkitSpeechRecognition;
    
    // Check if browser supports Speech Recognition AND is in secure context
    const isSecureContext = window.isSecureContext || location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1';
    
    if (micBtn && SpeechRecognitionAPI && isSecureContext) {
        // Mark as supported but DO NOT show yet — it will show only when search expands
        micBtn.dataset.micSupported = 'true';
        micBtn.style.display = 'none'; // Hidden by default; expandSearch() will reveal it

        let recognition = null;
        let isListening = false; // Flag to prevent multiple start() calls
        let permissionGranted = false; // Track if permission was granted

        // Function to request microphone permission explicitly
        const requestMicrophonePermission = async () => {
            try {
                // Request microphone access
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                // Stop the stream immediately - we just needed permission
                stream.getTracks().forEach(track => track.stop());
                permissionGranted = true;
                return true;
            } catch (err) {
                console.error('Microphone permission denied:', err);
                searchInput.placeholder = 'Mic permission denied';
                setTimeout(() => {
                    searchInput.placeholder = translations.placeholder || 'Search...';
                    micBtn.style.display = 'none'; // Hide mic if permission denied
                }, 2000);
                return false;
            }
        };

        // Initialize recognition only after permission is granted
        const initializeRecognition = () => {
            if (!recognition) {
                recognition = new SpeechRecognitionAPI();
                recognition.continuous = false;
                recognition.interimResults = false;
                recognition.maxAlternatives = 1;
                recognition.lang = 'en-US'; // Default to English, could be dynamic

                recognition.onstart = () => {
                    isListening = true;
                    micBtn.classList.add('listening');
                    searchInput.placeholder = 'Listening...';
                };

                recognition.onend = () => {
                    isListening = false;
                    micBtn.classList.remove('listening');
                    searchInput.placeholder = translations.placeholder || 'Search...';
                };

                recognition.onresult = (event) => {
                    try {
                        const transcript = event.results[0][0].transcript;
                        if(transcript && transcript.trim()) {
                            searchInput.value = transcript;
                            // Trigger input event to run live search
                            searchInput.dispatchEvent(new Event('input'));
                            searchInput.focus();
                        }
                    } catch (err) {
                        console.error('Error processing speech result:', err);
                    }
                };
                
                recognition.onerror = (event) => {
                    console.error('Voice Recognition Error:', event.error);
                    isListening = false;
                    micBtn.classList.remove('listening');
                    
                    // Handle specific error types
                    let errorMessage = translations.placeholder || 'Search...';
                    
                    switch(event.error) {
                        case 'not-allowed':
                        case 'service-not-allowed':
                            errorMessage = 'Mic permission denied';
                            permissionGranted = false;
                            // Hide mic button if permission is permanently denied
                            setTimeout(() => {
                                micBtn.style.display = 'none';
                            }, 2000);
                            break;
                        case 'no-speech':
                            errorMessage = 'No speech detected';
                            break;
                        case 'network':
                            errorMessage = 'Network error';
                            break;
                        case 'aborted':
                            // User cancelled, just reset
                            errorMessage = translations.placeholder || 'Search...';
                            break;
                        default:
                            errorMessage = 'Try again';
                    }
                    
                    searchInput.placeholder = errorMessage;
                    
                    // Reset placeholder after 2 seconds
                    setTimeout(() => {
                        if (!isListening) {
                            searchInput.placeholder = translations.placeholder || 'Search...';
                        }
                    }, 2000);
                };
            }
        };

        // Mic button click handler
        micBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            // If search is not active, expand it first
            if(!wrapper.classList.contains('active')) {
                 expandSearch();
            }

            // If permission not granted yet, request it first
            if (!permissionGranted) {
                searchInput.placeholder = 'Requesting permission...';
                const granted = await requestMicrophonePermission();
                if (!granted) {
                    return; // Exit if permission denied
                }
                // Initialize recognition after permission granted
                initializeRecognition();
            }

            // Toggle listening state
            if (isListening) {
                try {
                    recognition.stop();
                } catch (err) {
                    console.error('Error stopping recognition:', err);
                    isListening = false;
                    micBtn.classList.remove('listening');
                }
            } else {
                try {
                    recognition.start();
                } catch (err) {
                    console.error('Error starting recognition:', err);
                    // Reset state if start fails
                    isListening = false;
                    micBtn.classList.remove('listening');
                    searchInput.placeholder = translations.placeholder || 'Search...';
                }
            }
        });
    } else {
        // Browser doesn't support speech recognition or not in secure context
        // Hide mic button
        if (micBtn) {
            micBtn.style.display = 'none';
        }
        
        // Log reason for debugging
        if (!SpeechRecognitionAPI) {
            console.info('Speech Recognition API not supported in this browser');
        } else if (!isSecureContext) {
            console.warn('Speech Recognition requires HTTPS or localhost');
        }
    }
    // ----------------------------------
    
    // Render Logic - ICONS ONLY, NO IMAGES
    const renderSuggestions = (data) => {
         if(!suggestionsBox) return;

         suggestionsBox.innerHTML = '';
         let hasResults = false;

         // Helper to generate specific section HTML
         const generateSection = (items, title, isGenre = false) => {
             if(!items || items.length === 0) return '';
             hasResults = true;
             let html = `<div class="premium-search-header">${title}</div>`;
             items.forEach(item => {
                 // Determine Icon based on Type
                 let iconClass = 'ph-film-strip'; // default movie
                 if (isGenre) {
                     iconClass = 'ph-hash';
                 } else if (item.type === 'tvshow') {
                     iconClass = 'ph-television';
                 } else if (item.type === 'video') {
                     iconClass = 'ph-play-circle';
                 } else if (item.type === 'person') {
                     iconClass = 'ph-user';
                 }

                 // PURE ICON RENDERING - NO <IMG> TAGS
                 html += `
                    <a href="${item.url}" class="premium-search-item">
                        <div class="search-poster" style="display:flex;align-items:center;justify-content:center;background:#222;color:#e50914;font-size:20px;">
                            <i class="ph ${iconClass}"></i>
                        </div>
                        <div class="search-info">
                            <h6>${item.label}</h6>
                            <span>${item.meta || item.type || ''}</span>
                        </div>
                    </a>
                 `;
             });
             return html;
         };

         let innerHtml = '';
         innerHtml += generateSection(data.titles, translations.movies_tv || 'Movies & TV');
         innerHtml += generateSection(data.people, translations.cast_crew || 'Cast & Crew');
         innerHtml += generateSection(data.genres, translations.genres || 'Genres', true);

         if(hasResults) {
             const currentQuery = searchInput.value.trim();
             const viewAllUrl = `${envURL}/search?search=${encodeURIComponent(currentQuery)}`;
             // Wrap results in a scrollable area + add sticky "View All" footer
             suggestionsBox.innerHTML = `
                 <div class="search-scroll-area">${innerHtml}</div>
                 <a href="${viewAllUrl}" class="search-view-all">
                     View all results &nbsp;<i class="ph ph-arrow-right"></i>
                 </a>
             `;
             suggestionsBox.classList.remove('d-none');
         } else {
             suggestionsBox.innerHTML = `<div class="search-empty">${translations.no_record || 'No record found'}</div>`;
             suggestionsBox.classList.remove('d-none');
         }
    };

    const performSearch = () => {
        const query = searchInput.value.trim();
        if (query) {
            window.location.href = `${envURL}/search?search=${encodeURIComponent(query)}`;
        }
    };

    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') performSearch();
    });
    
    // Init: Check URL for query
    const urlParams = new URLSearchParams(window.location.search);
    const query = urlParams.get('search') || urlParams.get('query');
    if (query) {
        searchInput.value = query;
        expandSearch();
        if(closeBtn) closeBtn.classList.remove('d-none');
    }
    
     window.SelectProfile11 = function(id) {
        if (!envURL) return;
        const apiUrl = `${envURL}/api/select-userprofile/${id}`;
        fetch(apiUrl, { 
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
            .then(response => response.json())
            .then(response => { window.location.href = envURL; })
            .catch(error => { console.error('Error:', error); });
    };
});
