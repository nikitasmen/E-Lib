<?php
/**
 * User Profile Component
 * This component now uses client-side API calls for data fetching
 * instead of relying on server-side variables passed from the controller
 */
?>

<div class="container my-4">
    <!-- Profile Header (compact) -->
    <div class="profile-header shadow-sm p-3 mb-3 bg-light rounded">
        <div class="row align-items-center g-3">
            <div class="col-auto">
                <div class="profile-avatar profile-avatar-sm" id="profile-avatar"></div>
            </div>
            <div class="col">
                <div id="username-display" class="d-flex align-items-center flex-wrap gap-2">
                    <h1 id="current-username" class="h4 mb-0">Loading...</h1>
                    <div class="profile-menu-wrap position-relative" id="profile-menu-wrap">
                        <button type="button" class="btn btn-sm btn-light border profile-menu-trigger" id="profile-menu-trigger" aria-label="Account menu" aria-expanded="false" aria-haspopup="true">
                            <i class="fas fa-bars" aria-hidden="true"></i>
                        </button>
                        <div class="profile-menu-panel border rounded-2 bg-white shadow-sm py-1" role="menu" aria-hidden="true">
                            <button type="button" class="profile-menu-item" id="menu-edit-username" role="menuitem">Edit username</button>
                            <button type="button" class="profile-menu-item" id="menu-change-password" role="menuitem">Change password</button>
                        </div>
                    </div>
                </div>
                <p class="text-muted small mb-1 mt-2 mb-0">
                    <i class="fas fa-envelope me-1"></i><span id="user-email">Loading...</span>
                </p>
                <p class="text-muted small mb-0">
                    <i class="fas fa-clock me-1"></i>Member since <span id="member-since">Loading...</span>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Books Tabs -->
    <ul class="nav nav-pills mb-4" id="booksTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="saved-tab" data-bs-toggle="pill" data-bs-target="#saved" type="button">
                <i class="fas fa-bookmark me-2"></i>Saved Books
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="downloaded-tab" data-bs-toggle="pill" data-bs-target="#downloaded" type="button">
                <i class="fas fa-download me-2"></i>Downloaded
            </button>
        </li>
    </ul>
    
    <div class="tab-content" id="booksTabContent">
        <!-- Saved Books Tab -->
        <div class="tab-pane fade show active" id="saved" role="tabpanel" aria-labelledby="saved-tab">
            <!-- Content will be loaded via API -->
            <div class="text-center py-5" id="saved-books-loading">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3">Loading your saved books...</p>
            </div>
        </div>
        <div class="tab-pane fade" id="downloaded" role="tabpanel" aria-labelledby="downloaded-tab">
            <div class="text-center py-4 text-muted" id="downloaded-books-placeholder">
                <p class="mb-0 small">Open this tab to see books you have downloaded.</p>
            </div>
        </div>
    </div>
</div>

<!-- Edit username -->
<div class="modal fade" id="editUsernameModal" tabindex="-1" aria-labelledby="editUsernameModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="editUsernameModalLabel">Edit username</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <form id="edit-username-form" novalidate>
                    <div class="mb-3">
                        <label for="new-username" class="form-label small">Username</label>
                        <input type="text" id="new-username" class="form-control form-control-sm" value="" autocomplete="username" minlength="3" required>
                        <div class="form-text small">At least 3 characters.</div>
                    </div>
                    <small id="username-error" class="text-danger d-none d-block mb-2" role="alert"></small>
                    <div class="d-flex gap-2 justify-content-end">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal" id="cancel-edit-btn">Cancel</button>
                        <button type="button" class="btn btn-primary btn-sm" id="save-username-btn">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Change password (modal keeps the page compact) -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="changePasswordModalLabel">Change password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <form id="change-password-form" novalidate>
                    <div class="mb-3">
                        <label for="current-password" class="form-label small">Current password</label>
                        <input type="password" class="form-control form-control-sm" id="current-password" name="current_password" autocomplete="current-password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new-password" class="form-label small">New password</label>
                        <input type="password" class="form-control form-control-sm" id="new-password" name="new_password" autocomplete="new-password" minlength="8" required>
                        <div class="form-text small">At least 8 characters.</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm-new-password" class="form-label small">Confirm new password</label>
                        <input type="password" class="form-control form-control-sm" id="confirm-new-password" name="confirm_new_password" autocomplete="new-password" minlength="8" required>
                    </div>
                    <p id="change-password-feedback" class="small mb-2 d-none" role="alert"></p>
                    <button type="submit" class="btn btn-primary btn-sm" id="change-password-submit">Update password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Axios if not already included -->
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const currentUsername = document.getElementById('current-username');
    const profileMenuWrap = document.getElementById('profile-menu-wrap');
    const menuEditUsername = document.getElementById('menu-edit-username');
    const menuChangePassword = document.getElementById('menu-change-password');
    const editUsernameModalEl = document.getElementById('editUsernameModal');
    const changePasswordModalEl = document.getElementById('changePasswordModal');
    const newUsernameInput = document.getElementById('new-username');
    const saveUsernameBtn = document.getElementById('save-username-btn');
    const usernameError = document.getElementById('username-error');
    const profileAvatar = document.querySelector('.profile-avatar');
    const userEmail = document.getElementById('user-email');
    const memberSince = document.getElementById('member-since');
    
    // Load user profile data
    loadUserProfile();
    
    function loadUserProfile() {
        const token = localStorage.getItem('authToken') || sessionStorage.getItem('authToken') || '';
        
        axios.get('/api/v1/user/profile', {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        })
        .then(function(response) {
            if (response.data && (response.data.success || response.data.status === 'success')) {
                const user = response.data.data;
                
                // Update profile information
                currentUsername.textContent = user.username || 'User';
                userEmail.textContent = user.email || '';
                
                // Format the date
                if (user.createdAt) {
                    const date = new Date(user.createdAt);
                    memberSince.textContent = date.toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric' 
                    });
                } else {
                    memberSince.textContent = 'N/A';
                }
                
                // Update avatar
                profileAvatar.textContent = (user.username ? user.username.charAt(0).toUpperCase() : 'U');
                
                // Update form field
                newUsernameInput.value = user.username || '';
                
                // Also update any session value if needed
                if (typeof updateSessionValue === 'function') {
                    updateSessionValue('username', user.username);
                    updateSessionValue('email', user.email);
                }
            } else {
                console.error('Failed to load profile:', response.data);
                showError('Failed to load profile information. Please try refreshing the page.');
            }
        })
        .catch(function(error) {
            console.error('Error loading profile:', error);
            showError('An error occurred while loading your profile information.');
            
            // Redirect if unauthorized
            if (error.response && (error.response.status === 401 || error.response.status === 403)) {
                window.location.href = '/?showLogin=1&redirect=' + encodeURIComponent(window.location.pathname);
            }
        });
    }
    
    function showError(message) {
        // You could add a more sophisticated error display here
        alert(message);
    }
    
    function closeProfileMenu() {
        if (profileMenuWrap) {
            profileMenuWrap.classList.remove('show-menu');
        }
    }

    if (profileMenuWrap && document.getElementById('profile-menu-trigger')) {
        document.getElementById('profile-menu-trigger').addEventListener('click', function (e) {
            e.stopPropagation();
            profileMenuWrap.classList.toggle('show-menu');
        });
        document.addEventListener('click', function () {
            closeProfileMenu();
        });
        profileMenuWrap.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    }

    if (editUsernameModalEl && typeof bootstrap !== 'undefined') {
        editUsernameModalEl.addEventListener('hidden.bs.modal', function () {
            if (usernameError) {
                usernameError.classList.add('d-none');
                usernameError.textContent = '';
            }
            if (newUsernameInput && currentUsername) {
                newUsernameInput.value = currentUsername.textContent.trim();
            }
        });
    }

    if (menuEditUsername && editUsernameModalEl && typeof bootstrap !== 'undefined') {
        menuEditUsername.addEventListener('click', function () {
            closeProfileMenu();
            if (usernameError) {
                usernameError.classList.add('d-none');
                usernameError.textContent = '';
            }
            newUsernameInput.value = currentUsername.textContent.trim();
            bootstrap.Modal.getOrCreateInstance(editUsernameModalEl).show();
            setTimeout(function () {
                newUsernameInput.focus();
                newUsernameInput.select();
            }, 300);
        });
    }

    if (menuChangePassword && changePasswordModalEl && typeof bootstrap !== 'undefined') {
        menuChangePassword.addEventListener('click', function () {
            closeProfileMenu();
            const feedback = document.getElementById('change-password-feedback');
            if (feedback) {
                feedback.classList.add('d-none');
                feedback.textContent = '';
            }
            const form = document.getElementById('change-password-form');
            if (form) {
                form.reset();
            }
            bootstrap.Modal.getOrCreateInstance(changePasswordModalEl).show();
        });
    }
    
    // Save button (modal)
    if (saveUsernameBtn) {
        saveUsernameBtn.addEventListener('click', updateUsername);
    }
    const editUsernameForm = document.getElementById('edit-username-form');
    if (editUsernameForm) {
        editUsernameForm.addEventListener('submit', function (e) {
            e.preventDefault();
            updateUsername();
        });
    }

    if (newUsernameInput) {
        newUsernameInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                updateUsername();
            }
        });
    }
    
    function updateUsername() {
        const newUsername = newUsernameInput.value.trim();
        
        // Basic validation
        if (!newUsername) {
            showUsernameError('Username cannot be empty');
            return;
        }
        
        if (newUsername.length < 3) {
            showUsernameError('Username must be at least 3 characters');
            return;
        }
        
        if (newUsername === currentUsername.textContent.trim()) {
            if (editUsernameModalEl && typeof bootstrap !== 'undefined') {
                var um = bootstrap.Modal.getInstance(editUsernameModalEl);
                if (um) {
                    um.hide();
                }
            }
            return;
        }
        
        // Show loading state
        saveUsernameBtn.disabled = true;
        saveUsernameBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
        
        // Send request to update username
        axios.post('/api/v1/update-profile', {
            username: newUsername
        }, {
            headers: {
                'Authorization': 'Bearer ' + (localStorage.getItem('authToken') || sessionStorage.getItem('authToken') || '')
            }
        })
        .then(function(response) {
            if (response.data.success || response.data.status === 'success') {
                currentUsername.textContent = newUsername;
                profileAvatar.textContent = newUsername.charAt(0);
                if (editUsernameModalEl && typeof bootstrap !== 'undefined') {
                    var um = bootstrap.Modal.getInstance(editUsernameModalEl);
                    if (um) {
                        um.hide();
                    }
                }
                showNotification('Username updated successfully', 'success');
                if (typeof updateSessionValue === 'function') {
                    updateSessionValue('username', newUsername);
                }
            } else {
                showUsernameError(response.data.message || 'Failed to update username');
            }
        })
        .catch(function(error) {
            console.error('Error updating username:', error);
            showUsernameError(error.response?.data?.message || 'An error occurred. Please try again.');
        })
        .finally(function() {
            saveUsernameBtn.disabled = false;
            saveUsernameBtn.innerHTML = 'Save';
        });
    }
    
    function showUsernameError(message) {
        usernameError.textContent = message;
        usernameError.classList.remove('d-none');
    }
    
    function showNotification(message, type = 'info') {
        // Create toast notification container if it doesn't exist
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(toastContainer);
        }
        
        // Create toast element
        const toastId = 'toast-' + Date.now();
        const bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center ${bgClass} text-white border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        
        // Initialize and show the toast
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
        toast.show();
        
        // Remove the toast element after it's hidden
        toastElement.addEventListener('hidden.bs.toast', function () {
            toastElement.remove();
        });
    }

    const changePasswordForm = document.getElementById('change-password-form');
    const changePasswordFeedback = document.getElementById('change-password-feedback');
    const changePasswordSubmit = document.getElementById('change-password-submit');

    function setPasswordFeedback(message, isError) {
        if (!changePasswordFeedback) return;
        changePasswordFeedback.textContent = message;
        changePasswordFeedback.classList.remove('d-none', 'text-danger', 'text-success');
        changePasswordFeedback.classList.add(isError ? 'text-danger' : 'text-success');
    }

    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const current = document.getElementById('current-password').value;
            const newPass = document.getElementById('new-password').value;
            const confirm = document.getElementById('confirm-new-password').value;
            const token = localStorage.getItem('authToken') || sessionStorage.getItem('authToken') || '';

            if (!token) {
                alert('Please log in again.');
                window.location.href = '/login?redirect=' + encodeURIComponent(window.location.pathname);
                return;
            }
            if (newPass !== confirm) {
                setPasswordFeedback('New password and confirmation do not match.', true);
                return;
            }
            if (newPass.length < 8) {
                setPasswordFeedback('New password must be at least 8 characters.', true);
                return;
            }

            setPasswordFeedback('', false);
            changePasswordFeedback.classList.add('d-none');
            changePasswordSubmit.disabled = true;
            changePasswordSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Updating…';

            axios.post('/api/v1/change-password', {
                current_password: current,
                new_password: newPass
            }, {
                headers: { 'Authorization': 'Bearer ' + token }
            })
            .then(function (response) {
                if (response.data && response.data.status === 'success') {
                    changePasswordForm.reset();
                    setPasswordFeedback('Password updated successfully.', false);
                    showNotification('Password updated successfully', 'success');
                    if (changePasswordModalEl && typeof bootstrap !== 'undefined') {
                        var pwdModal = bootstrap.Modal.getInstance(changePasswordModalEl);
                        if (pwdModal) {
                            pwdModal.hide();
                        }
                    }
                } else {
                    setPasswordFeedback(response.data.message || 'Could not update password.', true);
                }
            })
            .catch(function (error) {
                const msg = error.response?.data?.message || 'Could not update password. Please try again.';
                setPasswordFeedback(msg, true);
            })
            .finally(function () {
                changePasswordSubmit.disabled = false;
                changePasswordSubmit.innerHTML = '<i class="fas fa-save me-1"></i>Update password';
            });
        });
    }
    
    // Existing saved books functionality
    const savedTab = document.getElementById('saved-tab');
    const savedBooksContainer = document.getElementById('saved');
    const downloadedTab = document.getElementById('downloaded-tab');
    const downloadedBooksContainer = document.getElementById('downloaded');
    loadSavedBooks();

    savedTab.addEventListener('click', function () {
        loadSavedBooks();
    });

    if (downloadedTab && downloadedBooksContainer) {
        downloadedTab.addEventListener('shown.bs.tab', function () {
            loadDownloadedBooks();
        });
    }

    function loadSavedBooks() {
        savedBooksContainer.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3">Loading your saved books...</p>
            </div>
        `;
        
        // Get saved books using Axios
        axios.get('/api/v1/saved-books', {
            headers: {
                'Authorization': 'Bearer ' + (localStorage.getItem('authToken') || sessionStorage.getItem('authToken') || '')
            }
        })
        .then(function(response) {
            books = response.data.data;
            if (books) {
                
                if (books.length > 0) {
                    try {
                        // Render the books using BookCard structure
                        let booksHTML = '<div class="row">';
                        
                        books.forEach(function(book, index) {
                            console.log(`Processing book ${index}:`, book);
                            const cardHTML = generateBookCardHTML(book);
                            console.log(`Card HTML for book ${index}:`, cardHTML.substring(0, 100) + '...');
                            booksHTML += cardHTML;
                        });
                        
                        booksHTML += '</div>';
                        console.log('Final HTML length:', booksHTML.length);
                        
                        // Set innerHTML and verify it worked
                        savedBooksContainer.innerHTML = booksHTML;
                        console.log('DOM updated with new content');
                        
                        // Attach event listeners to the new buttons
                        attachRemoveButtonListeners();
                    } catch (err) {
                        console.error('Error rendering books:', err);
                        showNoSavedBooksMessage();
                    }
                } else {
                    console.log('No saved books found');
                    showNoSavedBooksMessage();
                }
            } else {
                console.log('No saved books found in response');
                showNoSavedBooksMessage();
            }
        })
        .catch(function(error) {
           
            console.error('Error loading saved books:', error);
            if (error.response?.data?.status === "success") {
                showNoSavedBooksMessage();
            } else {
                console.error('Error loading saved books:', error);  
                savedBooksContainer.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                        <h4>Error loading books</h4>
                        <p class="text-muted">${error.response?.data?.message || 'There was a problem loading your saved books.'}</p>
                        <button class="btn btn-primary" onclick="loadSavedBooks()">Retry</button>
                    </div>
                `;
            }
        });
    }

    function loadDownloadedBooks() {
        if (!downloadedBooksContainer) {
            return;
        }
        downloadedBooksContainer.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3">Loading your downloaded books...</p>
            </div>
        `;

        axios.get('/api/v1/downloaded-books', {
            headers: {
                'Authorization': 'Bearer ' + (localStorage.getItem('authToken') || sessionStorage.getItem('authToken') || '')
            }
        })
        .then(function (response) {
            const books = response.data.data;
            if (books && books.length > 0) {
                try {
                    let booksHTML = '<div class="row">';
                    books.forEach(function (book) {
                        booksHTML += generateBookCardHTML(book, false);
                    });
                    booksHTML += '</div>';
                    downloadedBooksContainer.innerHTML = booksHTML;
                } catch (err) {
                    console.error('Error rendering downloaded books:', err);
                    showNoDownloadedBooksMessage();
                }
            } else {
                showNoDownloadedBooksMessage();
            }
        })
        .catch(function (error) {
            console.error('Error loading downloaded books:', error);
            downloadedBooksContainer.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                    <h4>Error loading books</h4>
                    <p class="text-muted">${error.response?.data?.message || 'There was a problem loading your downloaded books.'}</p>
                    <button type="button" class="btn btn-primary" id="downloaded-books-retry">Retry</button>
                </div>
            `;
            const retryBtn = document.getElementById('downloaded-books-retry');
            if (retryBtn) {
                retryBtn.addEventListener('click', function () {
                    loadDownloadedBooks();
                });
            }
        });
    }

    window.loadDownloadedBooks = loadDownloadedBooks;
    
    // Function to generate HTML for a book card based on BookCard.php structure
    function generateBookCardHTML(book, includeRemove) {
        if (includeRemove === undefined) {
            includeRemove = true;
        }
        const title = book.title || 'Unknown Title';
        const author = book.author || 'Unknown Author';
        const bookId = book._id.$oid || book._id || '';
        const thumbnailPath = book.thumbnail || '/assets/uploads/thumbnails/placeholder-book.jpg';
        const year = book.year || '';
        const categories = book.categories || [];
        const averageRating = book.average_rating || 0;
        
        // Build categories HTML
        let categoriesHTML = '';
        if (categories.length > 0) {
            categoriesHTML = '<div class="mb-2">';
            const maxCategoriesToShow = 2;
            const categoriesToShow = categories.slice(0, maxCategoriesToShow);
            
            categoriesToShow.forEach(function(category) {
                categoriesHTML += `<span class="badge bg-secondary me-1">${category}</span>`;
            });
            
            if (categories.length > maxCategoriesToShow) {
                categoriesHTML += `<span class="badge bg-secondary">+${categories.length - maxCategoriesToShow} more</span>`;
            }
            
            categoriesHTML += '</div>';
        }
        
        // Build rating HTML
        let ratingHTML = '';
        if (averageRating > 0) {
            ratingHTML = '<div class="mb-2">';
            const roundedRating = Math.round(averageRating);
            
            for (let i = 1; i <= 5; i++) {
                const starClass = i <= roundedRating ? 'text-warning' : 'text-muted';
                ratingHTML += `<i class="fas fa-star ${starClass}"></i>`;
            }
            
            ratingHTML += `<span class="small text-muted">(${averageRating})</span></div>`;
        }
        
        // Return the complete book card HTML
        return `
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm position-relative">
                    <!-- Book thumbnail -->
                    <img src="${thumbnailPath}" 
                         class="card-img-top" style="height: 200px; object-fit: cover;"
                         alt="${title}"
                         onerror="this.src='/assets/uploads/thumbnails/placeholder-book.jpg'">
                    <div class="card-body d-flex flex-column">
                        <!-- Title -->
                        <h5 class="card-title text-truncate" title="${title}">
                            ${title}
                        </h5>
                        <!-- Author -->
                        <p class="card-text text-muted small text-truncate">
                            By ${author}
                        </p>
                        <!-- Year if available -->
                        ${year ? `<p class="card-text small mb-2">${year}</p>` : ''}
                        <!-- Categories -->
                        ${categoriesHTML}
                        <!-- Average Rating -->
                        ${ratingHTML}
                        <!-- Action Buttons -->
                        <div class="mt-auto d-flex ${includeRemove ? 'justify-content-between' : 'justify-content-center'}">
                            <a href="/book/${bookId}" class="btn btn-sm btn-primary">
                                View Details
                            </a>
                            ${includeRemove ? `
                            <button class="btn btn-sm btn-outline-danger remove-saved-book" data-book-id="${bookId}">
                                <i class="fas fa-times"></i> Remove
                            </button>` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function showNoSavedBooksMessage() {
        savedBooksContainer.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-bookmark fa-3x text-muted mb-3"></i>
                <h4>No saved books</h4>
                <p class="text-muted">You haven't saved any books to your list yet.</p>
                <a href="/search" class="btn btn-primary">Browse Books</a>
            </div>
        `;
    }

    function showNoDownloadedBooksMessage() {
        if (!downloadedBooksContainer) {
            return;
        }
        downloadedBooksContainer.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-download fa-3x text-muted mb-3"></i>
                <h4>No downloads yet</h4>
                <p class="text-muted">Books you download will appear here.</p>
                <a href="/search" class="btn btn-primary">Browse Books</a>
            </div>
        `;
    }

    function attachRemoveButtonListeners() {
        const removeButtons = document.querySelectorAll('.remove-saved-book');

        removeButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const bookId = this.getAttribute('data-book-id');
                if (confirm('Remove this book from your saved list?')) {
                    removeBook(bookId, this);
                }
            });
        });
    }

    function removeBook(bookId, buttonElement) {
        const originalButtonText = buttonElement.innerHTML;
        buttonElement.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Removing...';
        buttonElement.disabled = true;

        axios.post('/api/v1/remove-book', { book_id: bookId }, {
            headers: {
                'Authorization': 'Bearer ' + (localStorage.getItem('authToken') || sessionStorage.getItem('authToken') || '')
            }
        })
        .then(function (response) {
            console.log('Remove book response:', response.data);
            
            // Fix the success check to match the actual API response format
            if (response.data.success || response.data.status === 'success') { 
                const bookCard = buttonElement.closest('.col-md-4');
                bookCard.style.transition = 'all 0.3s ease';
                bookCard.style.opacity = '0';
                
                setTimeout(() => {
                    bookCard.remove();
                    
                    // Check for any remaining book cards using the correct selector
                    const remainingBooks = document.querySelectorAll('#saved .card');
                    console.log(`${remainingBooks.length} books remaining after removal`);
                    
                    if (remainingBooks.length === 0) {
                        showNoSavedBooksMessage();
                    }
                }, 300);
            } else {
                buttonElement.innerHTML = originalButtonText;
                buttonElement.disabled = false;
                alert('Failed to remove book: ' + (response.data.message || 'Unknown error'));
            }
        })
        .catch(function (error) {
            console.error('Error removing book:', error);
            buttonElement.innerHTML = originalButtonText;
            buttonElement.disabled = false;
            alert('An error occurred while removing the book. Please try again.');
        });
    }

    // Initial event listener attachment (for pre-loaded books)
    attachRemoveButtonListeners();
});
</script>

<style>
    .profile-menu-wrap .profile-menu-panel {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        min-width: 12rem;
        z-index: 1080;
        margin-top: 2px;
    }
    .profile-menu-wrap:hover .profile-menu-panel,
    .profile-menu-wrap:focus-within .profile-menu-panel,
    .profile-menu-wrap.show-menu .profile-menu-panel {
        display: block;
    }
    .profile-menu-item {
        display: block;
        width: 100%;
        padding: 0.45rem 0.9rem;
        border: 0;
        background: transparent;
        text-align: left;
        font-size: 0.9rem;
        cursor: pointer;
        color: #212529;
    }
    .profile-menu-item:hover {
        background: #f8f9fa;
    }
    .profile-menu-item + .profile-menu-item {
        border-top: 1px solid #e9ecef;
    }
</style>
