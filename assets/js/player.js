document.addEventListener('DOMContentLoaded', function() {
    const videoContainer = document.querySelector('.video-container');
    if (!videoContainer) return;
    
    const episodeId = videoContainer.dataset.episodeId;
    
    // Variables to track watch time
    let lastUpdateTime = 0;
    let watchedTime = 0;
    let isCompleted = false;
    
    // Check for saved time in localStorage
    const savedTime = localStorage.getItem(`episode_${episodeId}_time`);
    if (savedTime) {
        watchedTime = parseInt(savedTime);
    }
    
    // Listen for messages from the iframe
    window.addEventListener('message', function(event) {
        // Make sure the message is from Bunny.net player
        if (event.origin.includes('mediadelivery.net')) {
            try {
                const data = typeof event.data === 'string' ? JSON.parse(event.data) : event.data;
                
                // Handle player events
                if (data.event) {
                    switch (data.event) {
                        case 'ready':
                            // Player is ready, if we have saved time, seek to it
                            if (watchedTime > 0) {
                                sendMessageToPlayer('seek', { time: watchedTime });
                            }
                            break;
                            
                        case 'play':
                            // Video started playing
                            startTrackingTime();
                            break;
                            
                        case 'pause':
                            // Video paused
                            stopTrackingTime();
                            updateHistory();
                            break;
                            
                        case 'timeupdate':
                            // Current playback time updated
                            if (data.currentTime) {
                                watchedTime = Math.floor(data.currentTime);
                                
                                // Save current time to localStorage every 5 seconds
                                if (watchedTime - lastUpdateTime >= 5) {
                                    lastUpdateTime = watchedTime;
                                    localStorage.setItem(`episode_${episodeId}_time`, watchedTime);
                                }
                            }
                            break;
                            
                        case 'ended':
                            // Video ended
                            isCompleted = true;
                            stopTrackingTime();
                            updateHistory();
                            // Mark as completed in localStorage
                            localStorage.setItem(`episode_${episodeId}_completed`, 'true');
                            break;
                    }
                }
            } catch (error) {
                console.error('Error processing player message:', error);
            }
        }
    });
    
    // Interval for tracking time while playing
    let trackingInterval;
    
    function startTrackingTime() {
        // Clear existing interval if any
        stopTrackingTime();
        
        // Update history every 10 seconds while playing
        trackingInterval = setInterval(() => {
            updateHistory();
        }, 10000);
    }
    
    function stopTrackingTime() {
        if (trackingInterval) {
            clearInterval(trackingInterval);
            trackingInterval = null;
        }
    }
    
    function updateHistory() {
        // Don't update if we don't have a valid watched time
        if (watchedTime <= 0) return;
        
        // Use the updateWatchHistory function from main.js
        if (typeof updateWatchHistory === 'function') {
            updateWatchHistory(episodeId, watchedTime, isCompleted);
        }
    }
    
    // Send message to Bunny player iframe
    function sendMessageToPlayer(action, data = {}) {
        const iframe = videoContainer.querySelector('iframe');
        if (!iframe) return;
        
        const message = {
            action: action,
            ...data
        };
        
        iframe.contentWindow.postMessage(JSON.stringify(message), '*');
    }
    
    // Save watch history when user leaves the page
    window.addEventListener('beforeunload', function() {
        updateHistory();
    });
});
