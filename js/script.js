/**
 * AITools - AI Tools Directory
 * Main JavaScript File
 */

document.addEventListener('DOMContentLoaded', function() {
    // Mobile Menu Toggle
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mobileMenu = document.querySelector('.mobile-menu');

    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            mobileMenu.classList.toggle('active');
            document.body.classList.toggle('menu-open');

            // Toggle the hamburger icon to X
            const spans = mobileMenuToggle.querySelectorAll('span');
            spans.forEach(span => span.classList.toggle('active'));
        });
    }

    // Close mobile menu when clicking outside
    document.addEventListener('click', function(event) {
        if (mobileMenu && mobileMenu.classList.contains('active') &&
            !mobileMenu.contains(event.target) &&
            !mobileMenuToggle.contains(event.target)) {
            mobileMenu.classList.remove('active');
            document.body.classList.remove('menu-open');

            const spans = mobileMenuToggle.querySelectorAll('span');
            spans.forEach(span => span.classList.remove('active'));
        }
    });

    // Category Tags Active State
    const categoryTags = document.querySelectorAll('.category-tag');

    categoryTags.forEach(tag => {
        tag.addEventListener('click', function(e) {
            // If it's a filter that should allow multiple selections, skip toggling active state
            if (!this.classList.contains('multi-select')) {
                categoryTags.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            } else {
                this.classList.toggle('active');
            }

            // If it has data-filter attribute, we need to filter the tools
            if (this.hasAttribute('data-filter')) {
                const filter = this.getAttribute('data-filter');
                filterTools(filter);
            }
        });
    });

    // Get current language from HTML tag
    const currentLang = document.documentElement.lang;
    const isDefaultLang = currentLang === 'en';

    // Initialize upvote buttons
    const upvoteButtons = document.querySelectorAll('.upvote-btn');
    upvoteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const toolId = this.getAttribute('data-id');
            const countElement = this.querySelector('.count');

            // Send upvote request with language parameter
            fetch('/includes/upvote.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `tool_id=${toolId}&lang=${currentLang}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the UI
                    if (countElement) {
                        countElement.textContent = data.upvotes;
                    }

                    // Disable button to prevent multiple votes
                    this.classList.add('voted');
                    this.disabled = true;

                    if (this.querySelector('.label')) {
                        const labelElement = this.querySelector('.label');
                        labelElement.textContent = document.documentElement.lang === 'pl' ? 'Zagłosowano' : 'Upvoted';
                    }

                    // Add to localStorage to persist the vote
                    let votedTools = JSON.parse(localStorage.getItem('votedTools') || '[]');
                    votedTools.push(toolId);
                    localStorage.setItem('votedTools', JSON.stringify(votedTools));
                } else {
                    // Don't show error if user already voted - just update UI
                    if (data.message.includes('already_upvoted') || data.message.includes('Już zagłosowałeś')) {
                        this.classList.add('voted');
                        this.disabled = true;
                        if (this.querySelector('.label')) {
                            const labelElement = this.querySelector('.label');
                            labelElement.textContent = document.documentElement.lang === 'pl' ? 'Zagłosowano' : 'Upvoted';
                        }

                        // Add to localStorage to prevent future attempts
                        let votedTools = JSON.parse(localStorage.getItem('votedTools') || '[]');
                        if (!votedTools.includes(toolId)) {
                            votedTools.push(toolId);
                            localStorage.setItem('votedTools', JSON.stringify(votedTools));
                        }
                    } else if (data.message.includes('login') || data.message.includes('zalogowany')) {
                        // Redirect to login page with appropriate language path
                        window.location.href = isDefaultLang ? '/login' : `/${currentLang}/login`;
                    } else {
                        showMessage(data.message, 'error');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Get translated error message from PHP
                fetch(`/includes/language.php?get_translation=vote_process_error&lang=${currentLang}`, {
                    method: 'GET'
                })
                .then(response => response.text())
                .then(errorMsg => {
                    showMessage(errorMsg || 'Failed to process your vote. Please try again.', 'error');
                })
                .catch(() => {
                    // Fallback if translation fetch fails
                    showMessage('Failed to process your vote. Please try again.', 'error');
                });
            });
        });
    });

    // Check if user already voted for tools
    function checkVotedTools() {
        const votedTools = JSON.parse(localStorage.getItem('votedTools') || '[]');

        upvoteButtons.forEach(button => {
            const toolId = button.getAttribute('data-id');

            if (votedTools.includes(toolId)) {
                button.classList.add('voted');
                button.disabled = true;
            }
        });
    }

    // Call the function to check voted tools
    checkVotedTools();

    // Filter tools based on category or tag
    function filterTools(filter) {
        const toolCards = document.querySelectorAll('.tool-card');

        if (filter === 'all') {
            // Show all tools
            toolCards.forEach(card => {
                card.style.display = 'block';
            });
        } else {
            // Filter by category or tag
            toolCards.forEach(card => {
                const category = card.getAttribute('data-category');
                const tags = card.getAttribute('data-tags');

                if (category === filter || (tags && tags.includes(filter))) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    }

    // Search Functionality
    const searchForm = document.querySelector('.hero-search form');
    const searchInput = document.querySelector('.hero-search input');

    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            if (searchInput.value.trim() === '') {
                e.preventDefault();
                searchInput.focus();
            }
        });
    }

    // Tool Card Hover Animation
    const toolCards = document.querySelectorAll('.tool-card');

    toolCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 10px 20px rgba(0, 0, 0, 0.1)';
        });

        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
        });
    });

    // Rating stars generation
    const ratingElements = document.querySelectorAll('.stars');

    ratingElements.forEach(element => {
        const rating = parseFloat(element.getAttribute('data-rating'));
        let starsHTML = '';

        // Generate full stars
        for (let i = 1; i <= Math.floor(rating); i++) {
            starsHTML += '<i class="fas fa-star"></i>';
        }

        // Generate half star if needed
        if (rating % 1 >= 0.5) {
            starsHTML += '<i class="fas fa-star-half-alt"></i>';
        }

        // Generate empty stars
        const emptyStars = 5 - Math.ceil(rating);
        for (let i = 1; i <= emptyStars; i++) {
            starsHTML += '<i class="far fa-star"></i>';
        }

        element.innerHTML = starsHTML;
    });

    // Newsletter Form Submission
    const newsletterForm = document.querySelector('.newsletter form');

    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const emailInput = this.querySelector('input[type="email"]');
            const email = emailInput.value.trim();

            // Basic validation
            if (email === '') {
                // Get translated error message
                fetch('/includes/language.php?get_translation=email_required_newsletter', {
                    method: 'GET'
                })
                .then(response => response.text())
                .then(errorMsg => {
                    showMessage(errorMsg || 'Please enter your email address.', 'error');
                })
                .catch(() => {
                    showMessage('Please enter your email address.', 'error');
                });
                return;
            }

            // Send subscription request
            fetch('/includes/subscribe.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `email=${encodeURIComponent(email)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Get translated success message
                    fetch('/includes/language.php?get_translation=subscription_success', {
                        method: 'GET'
                    })
                    .then(response => response.text())
                    .then(successMsg => {
                        showMessage(successMsg || 'Thank you for subscribing!', 'success');
                    })
                    .catch(() => {
                        showMessage('Thank you for subscribing!', 'success');
                    });
                    emailInput.value = '';
                } else {
                    // If server returned a message, use it, otherwise get generic error message
                    if (data.message) {
                        showMessage(data.message, 'error');
                    } else {
                        fetch('/includes/language.php?get_translation=subscription_failed_generic', {
                            method: 'GET'
                        })
                        .then(response => response.text())
                        .then(errorMsg => {
                            showMessage(errorMsg || 'Subscription failed. Please try again.', 'error');
                        })
                        .catch(() => {
                            showMessage('Subscription failed. Please try again.', 'error');
                        });
                    }
                }
            })
            .catch(error => {
                fetch('/includes/language.php?get_translation=general_error', {
                    method: 'GET'
                })
                .then(response => response.text())
                .then(errorMsg => {
                    showMessage(errorMsg || 'An error occurred. Please try again later.', 'error');
                })
                .catch(() => {
                    showMessage('An error occurred. Please try again later.', 'error');
                });
                console.error('Error:', error);
            });
        });
    }

    // Function to show messages
    function showMessage(message, type = 'info') {
        // Check if message container exists, if not create it
        let messageContainer = document.querySelector('.message-container');

        if (!messageContainer) {
            messageContainer = document.createElement('div');
            messageContainer.className = 'message-container';
            document.body.appendChild(messageContainer);
        }

        // Create message element
        const messageElement = document.createElement('div');
        messageElement.className = `message message-${type}`;
        messageElement.textContent = message;

        // Add close button
        const closeButton = document.createElement('button');
        closeButton.innerHTML = '&times;';
        closeButton.className = 'message-close';
        closeButton.addEventListener('click', function() {
            messageElement.remove();
        });

        messageElement.appendChild(closeButton);
        messageContainer.appendChild(messageElement);

        // Auto remove after 5 seconds
        setTimeout(() => {
            messageElement.remove();
        }, 5000);
    }
});

// Add CSS for the message system
document.addEventListener('DOMContentLoaded', function() {
    const style = document.createElement('style');
    style.textContent = `
        .message-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .message {
            padding: 15px 40px 15px 15px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
            animation: slideIn 0.3s ease;
            max-width: 300px;
        }

        .message-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .message-error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .message-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }

        .message-close {
            position: absolute;
            top: 5px;
            right: 5px;
            background: transparent;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: inherit;
            opacity: 0.7;
        }

        .message-close:hover {
            opacity: 1;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    `;

    document.head.appendChild(style);
});
