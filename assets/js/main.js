document.addEventListener('DOMContentLoaded', function() {
    // Initialize all feather icons
    feather.replace();
    
    // Initialize featured slider
    initFeaturedSlider();
    
    // Initialize social share buttons
    initSocialShare();
    
    // Initialize favorite buttons
    initFavoriteButtons();
    
    // Initialize rating system if exists
    initRatingSystem();
    
    // Initialize comment form if exists
    initCommentForm();
});

// Handle favorite buttons
function initFavoriteButtons() {
    document.querySelectorAll('.favorite-toggle').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const animeId = this.dataset.animeId;
            const isFavorite = this.classList.contains('active');
            
            // Send AJAX request to toggle favorite status
            fetch('/api/favorites.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    anime_id: animeId,
                    action: isFavorite ? 'remove' : 'add'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Toggle active class
                    this.classList.toggle('active');
                    
                    // Update icon
                    const icon = this.querySelector('svg');
                    if (isFavorite) {
                        icon.innerHTML = feather.icons.heart.toSvg();
                    } else {
                        icon.innerHTML = feather.icons['heart-fill'].toSvg();
                    }
                    
                    // Re-initialize feather
                    feather.replace();
                    
                    // Show message
                    showAlert(data.message, 'success');
                } else {
                    showAlert(data.message || 'An error occurred', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred. Please try again.', 'danger');
            });
        });
    });
}

// Handle rating system
function initRatingSystem() {
    const ratingContainer = document.querySelector('.rating-container');
    if (!ratingContainer) return;
    
    const stars = ratingContainer.querySelectorAll('.star');
    const ratingValue = ratingContainer.querySelector('input[name="rating"]');
    const animeId = ratingContainer.dataset.animeId;
    
    // Set initial rating
    const currentRating = parseInt(ratingValue.value) || 0;
    highlightStars(stars, currentRating);
    
    // Handle hover events
    stars.forEach((star, index) => {
        // Mouseover
        star.addEventListener('mouseover', () => {
            highlightStars(stars, index + 1);
        });
        
        // Mouseout
        star.addEventListener('mouseout', () => {
            highlightStars(stars, parseInt(ratingValue.value) || 0);
        });
        
        // Click
        star.addEventListener('click', () => {
            const rating = index + 1;
            ratingValue.value = rating;
            highlightStars(stars, rating);
            
            // Send rating to server
            fetch('/api/ratings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    anime_id: animeId,
                    rating: rating
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Rating saved successfully!', 'success');
                } else {
                    showAlert(data.message || 'Failed to save rating', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred. Please try again.', 'danger');
            });
        });
    });
}

// Highlight stars for rating
function highlightStars(stars, count) {
    stars.forEach((star, index) => {
        if (index < count) {
            star.classList.add('filled');
            star.innerHTML = feather.icons['star-fill'].toSvg();
        } else {
            star.classList.remove('filled');
            star.innerHTML = feather.icons.star.toSvg();
        }
    });
}

// Handle comment form submission
function initCommentForm() {
    const commentForm = document.getElementById('comment-form');
    if (!commentForm) return;
    
    commentForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const animeId = this.dataset.animeId;
        const episodeId = this.dataset.episodeId || null;
        const commentText = this.querySelector('textarea[name="comment"]').value;
        
        if (!commentText.trim()) {
            showAlert('Please enter a comment', 'warning');
            return;
        }
        
        // Send comment to server
        fetch('/api/comments.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                anime_id: animeId,
                episode_id: episodeId,
                comment: commentText
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Clear the form
                commentForm.reset();
                
                // Add new comment to the list
                addCommentToList(data.comment);
                
                showAlert('Comment added successfully!', 'success');
            } else {
                showAlert(data.message || 'Failed to add comment', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An error occurred. Please try again.', 'danger');
        });
    });
}

// Add a new comment to the comments list
function addCommentToList(comment) {
    const commentsList = document.querySelector('.comments-list');
    if (!commentsList) return;
    
    const commentHTML = `
        <div class="comment">
            <div class="comment-header">
                <span class="comment-user">${comment.username || comment.first_name}</span>
                <span class="comment-date">Just now</span>
            </div>
            <div class="comment-text">${comment.comment}</div>
        </div>
    `;
    
    commentsList.insertAdjacentHTML('afterbegin', commentHTML);
}

// Show alert message
function showAlert(message, type = 'info') {
    // Create alert container if it doesn't exist
    let alertContainer = document.querySelector('.alert-container');
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.className = 'alert-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(alertContainer);
    }
    
    // Create alert element
    const alertElement = document.createElement('div');
    alertElement.className = `alert alert-${type} alert-dismissible fade show`;
    alertElement.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Add alert to container
    alertContainer.appendChild(alertElement);
    
    // Remove alert after 5 seconds
    setTimeout(() => {
        alertElement.classList.remove('show');
        setTimeout(() => {
            alertElement.remove();
        }, 150);
    }, 5000);
}

// Update watch history (called from player.js)
function updateWatchHistory(episodeId, watchedTime, completed = false) {
    fetch('/api/history.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            episode_id: episodeId,
            watched_time: watchedTime,
            completed: completed
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Failed to update watch history:', data.message);
        }
    })
    .catch(error => {
        console.error('Error updating watch history:', error);
    });
}

// Initialize featured slider
function initFeaturedSlider() {
    const slider = document.querySelector('.featured-slider');
    if (!slider) return;
    
    const slides = slider.querySelectorAll('.featured-slide');
    const dots = slider.querySelectorAll('.slider-dot');
    const leftArrow = slider.querySelector('.slider-arrow.left');
    const rightArrow = slider.querySelector('.slider-arrow.right');
    
    let currentSlide = 0;
    const slideCount = slides.length;
    
    // Function to show a specific slide
    function showSlide(index) {
        // Hide all slides
        slides.forEach(slide => {
            slide.style.display = 'none';
        });
        
        // Remove active class from all dots
        dots.forEach(dot => {
            dot.classList.remove('active');
        });
        
        // Show the selected slide and activate the corresponding dot
        if (slides[index]) {
            slides[index].style.display = 'block';
        }
        
        if (dots[index]) {
            dots[index].classList.add('active');
        }
        
        currentSlide = index;
    }
    
    // Next slide function
    function nextSlide() {
        let next = currentSlide + 1;
        if (next >= slideCount) {
            next = 0;
        }
        showSlide(next);
    }
    
    // Previous slide function
    function prevSlide() {
        let prev = currentSlide - 1;
        if (prev < 0) {
            prev = slideCount - 1;
        }
        showSlide(prev);
    }
    
    // Add click event listeners to arrows
    if (leftArrow) {
        leftArrow.addEventListener('click', prevSlide);
    }
    
    if (rightArrow) {
        rightArrow.addEventListener('click', nextSlide);
    }
    
    // Add click event listeners to dots
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            showSlide(index);
        });
    });
    
    // Auto-advance slides every 5 seconds
    let slideInterval = setInterval(nextSlide, 5000);
    
    // Pause auto-advance on hover
    slider.addEventListener('mouseenter', () => {
        clearInterval(slideInterval);
    });
    
    // Resume auto-advance when mouse leaves
    slider.addEventListener('mouseleave', () => {
        clearInterval(slideInterval);
        slideInterval = setInterval(nextSlide, 5000);
    });
    
    // Initialize with the first slide
    showSlide(0);
}

// Initialize social share buttons
function initSocialShare() {
    const shareButtons = document.querySelectorAll('.share-button');
    if (!shareButtons.length) return;
    
    // Get current page URL and title
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent(document.title);
    
    // Setup share URLs
    const shareUrls = {
        '.share-telegram': `https://t.me/share/url?url=${url}&text=${title}`,
        '.share-twitter': `https://twitter.com/intent/tweet?url=${url}&text=${title}`,
        '.share-facebook': `https://www.facebook.com/sharer/sharer.php?u=${url}`,
        '.share-reddit': `https://www.reddit.com/submit?url=${url}&title=${title}`,
        '.share-other': null // Handled differently
    };
    
    // Add click event listeners
    shareButtons.forEach(button => {
        button.addEventListener('click', function() {
            // For share-other, use the Web Share API if available
            if (this.classList.contains('share-other') && navigator.share) {
                navigator.share({
                    title: document.title,
                    url: window.location.href
                }).catch(err => {
                    console.error('Error sharing:', err);
                });
                return;
            }
            
            // For other platforms, open a popup window
            for (const [selector, shareUrl] of Object.entries(shareUrls)) {
                if (this.matches(selector) && shareUrl) {
                    window.open(shareUrl, '_blank', 'width=600,height=450');
                    
                    // Track share action for analytics - this would be wired to the real API
                    fetch('/api/share.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            url: window.location.href,
                            platform: selector.replace('.share-', '')
                        })
                    }).catch(error => {
                        console.error('Error tracking share:', error);
                    });
                    
                    break;
                }
            }
        });
    });
}
