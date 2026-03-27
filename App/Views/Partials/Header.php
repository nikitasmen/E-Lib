<?php
/**
 * Navbar Component
 * 
 * @param string $activePage Optional - current active page for nav highlight
 * @param string $searchUrl Optional - search form submission URL (default: '/search_results')
 */

// Default values
$activePage = $activePage ?? '';
$searchUrl = $searchUrl ?? '/search_results';

// Initialize username from session if available
$username = '';
if (!empty($_SESSION['user_id']) && !empty($_SESSION['username'])) {
    $username = $_SESSION['username'];
} else if (!empty($_SESSION['user_id']) && !empty($_SESSION['email'])) {
    // Fallback to email if name isn't available
    $username = $_SESSION['email'];
}
?>
<link rel="stylesheet" href="/styles/userForm.css">
<nav class="navbar site-navbar navbar-expand-lg navbar-dark shadow-sm sticky-top border-bottom border-light border-opacity-10">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="/">
            <span class="site-navbar-brand-icon rounded-2 d-inline-flex align-items-center justify-content-center">
                <i class="fas fa-book-open"></i>
            </span>
            <span>Epictetus Library</span>
        </a>
        <?php if (empty($_SESSION['user_id'])): ?>
            <!-- Always visible on small screens (auth was only inside collapse, so it was easy to miss) -->
            <div class="d-flex d-lg-none align-items-center gap-2 ms-auto me-2 navbar-guest-toolbar">
                <button type="button" class="btn btn-sm btn-light fw-semibold js-navbar-auth-btn" onclick="openPopup('loginPopup')" id="navbarBtnLoginMobile">Log in</button>
                <button type="button" class="btn btn-sm btn-primary fw-semibold js-navbar-auth-btn" onclick="openPopup('signupPopup')" id="navbarBtnSignupMobile">Sign up</button>
            </div>
        <?php endif; ?>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link px-lg-3 <?= $activePage === 'home' ? 'active' : '' ?>" href="/">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-lg-3 <?= $activePage === 'books' ? 'active' : '' ?>" href="/view-books">Books</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-lg-3 <?= $activePage === 'add' ? 'active' : '' ?>" href="/add-book">Add Book</a>
                </li>
            </ul>

            <form class="d-flex me-lg-3 mb-2 mb-lg-0" id="searchForm" action="<?= htmlspecialchars($searchUrl) ?>" method="GET">
                <div class="input-group site-navbar-search-group">
                    <input type="search" name="title" id="bookToSearch" class="form-control border-0 shadow-none site-navbar-search"
                           placeholder="Search titles…" aria-label="Search">
                    <button type="submit" class="btn btn-primary px-3" aria-label="Search">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>

            <?php if (!empty($_SESSION['user_id'])): ?>
                <!-- User is logged in -->
                <div id="profileDropdown" class="dropdown" style="display: none;">
                    <button class="btn btn-outline-light dropdown-toggle d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar me-2"><?= !empty($username) ? htmlspecialchars(substr($username, 0, 1)) : '?' ?></div>
                        <span class="d-none d-md-inline"><?= htmlspecialchars($username ?: 'User') ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-lg-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="/profile"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="#" onclick="handleLogout(event)">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            <?php else: ?>
                <!-- User is not logged in (desktop: inside collapse; lg+ navbar is expanded) -->
                <div class="d-none d-lg-flex align-items-center gap-2 ms-lg-2 navbar-guest-desktop">
                    <button type="button" id="navbarBtnLogin" class="btn btn-outline-light js-navbar-auth-btn" onclick="openPopup('loginPopup')">Log in</button>
                    <button type="button" id="navbarBtnSignup" class="btn btn-primary js-navbar-auth-btn" onclick="openPopup('signupPopup')">Sign up</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Login Popup -->
<div id="loginPopup" class="popup-overlay" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
                background-color: rgba(0,0,0,0.5); z-index: 1050;">
        <?php include __DIR__ . '/../Components/LoginForm.php'; ?>
</div>

<!-- Signup Popup -->
<div id="signupPopup" class="popup-overlay" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
                background-color: rgba(0,0,0,0.5); z-index: 1050;">
        <?php include __DIR__ . '/../Components/SignUpForm.php'; ?>
</div>


<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
function handleLogout(event) {
    event.preventDefault();
    axios.get('/api/v1/logout')
        .then(response => {
            if (response.data.status === 'success') {
                sessionStorage.clear();
                localStorage.clear();   
                window.location.href = '/';
            }
        })
        .catch(error => {
            console.error('Logout error:', error);
        });
}
function closePopup(popupId) {
    const popup = document.getElementById(popupId);
    if (popup) {
        popup.style.display = 'none';
    } else {
        console.error(`Popup with id "${popupId}" not found.`);
    }
}

function openPopup(popupId) {
    const popup = document.getElementById(popupId);
    if (!popup) {
        console.error(`Popup with id "${popupId}" not found.`);
        return;
    }
    popup.style.display = 'flex';
}
document.addEventListener('DOMContentLoaded', function () {
    const authToken = localStorage.getItem('authToken') || sessionStorage.getItem('authToken');
    const isAdmin = localStorage.getItem('isAdmin') || sessionStorage.getItem('isAdmin');
    const username = localStorage.getItem('username') || sessionStorage.getItem('username');

    const guestAuthButtons = document.querySelectorAll('.js-navbar-auth-btn');
    const guestToolbar = document.querySelector('.navbar-guest-toolbar');
    const guestDesktop = document.querySelector('.navbar-guest-desktop');
    const userDropdown = document.querySelector('#profileDropdown');

    function setGuestAuthVisible(visible) {
        guestAuthButtons.forEach(function (btn) {
            btn.style.display = visible ? '' : 'none';
        });
        if (guestToolbar) {
            guestToolbar.style.display = visible ? '' : 'none';
        }
        if (guestDesktop) {
            guestDesktop.style.display = visible ? '' : 'none';
        }
    }

    if (authToken && userDropdown) {
        // Logged in: server rendered profile menu + client token
        setGuestAuthVisible(false);
        userDropdown.style.display = 'block';

        if (username) {
            const avatarDiv = userDropdown.querySelector('.user-avatar');
            const nameSpan = userDropdown.querySelector('.d-none.d-md-inline');
            if (avatarDiv) {
                avatarDiv.textContent = username.substring(0, 1).toUpperCase();
            }
            if (nameSpan) {
                nameSpan.textContent = username;
            }
        }

        if (isAdmin === 'true') {
            const dropdownMenu = userDropdown.querySelector('.dropdown-menu');
            if (dropdownMenu && !dropdownMenu.querySelector('a[href="/dashboard"]')) {
                const dashboardLink = document.createElement('li');
                dashboardLink.innerHTML = '<a class="dropdown-item" href="/dashboard"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>';
                dropdownMenu.insertBefore(dashboardLink, dropdownMenu.firstChild);
            }
        }
    } else if (authToken && !userDropdown) {
        // Stale client token without PHP session: keep Log in / Sign up visible
        setGuestAuthVisible(true);
    } else {
        // Guest
        setGuestAuthVisible(true);
        if (userDropdown) {
            userDropdown.style.display = 'none';
        }
    }
});

// Check for URL parameters to show login popup
document.addEventListener('DOMContentLoaded', function() {
    // Parse URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    
    // Check if showLogin parameter exists
    if (urlParams.has('showLogin')) {
        openPopup('loginPopup');
        const loginPopup = document.getElementById('loginPopup');
        if (loginPopup) {
            loginPopup.style.display = 'flex';
            
            // Store redirect URL if provided
            const redirectUrl = urlParams.get('redirect');
            if (redirectUrl) {
                sessionStorage.setItem('redirectAfterLogin', redirectUrl);
            }
        }
    }
    
    // Check if we have error messages to display
    if (urlParams.has('error')) {
        const errorMessage = urlParams.get('error');
        // You can use a toast or alert library here, for simplicity we'll use alert
        alert(decodeURIComponent(errorMessage));
    }
    
    // Check for login success/failure messages
    if (urlParams.has('login')) {
        const loginStatus = urlParams.get('login');
        if (loginStatus === 'success') {
            alert('Login successful!');
        } else if (loginStatus === 'failed') {
            alert('Login failed. Please try again.');
            // Show login popup again
            const loginPopup = document.getElementById('loginPopup');
            if (loginPopup) {
                loginPopup.style.display = 'flex';
            }
        }
    }
});

</script>
