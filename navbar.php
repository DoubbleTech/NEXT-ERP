<?php
/**
 * navbar.php - Reusable Navigation Bar (Redesigned with Original Sky Blue Theme)
 * Includes Profile Dropdown, Help Modal, and a unified Settings Modal.
 * Now includes a User Management link for super-admins and admins.
 */

// Ensure session is started before accessing session variables
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Navbar variables (provide defaults if not set by including page)
$isDashboard = isset($isDashboard) ? $isDashboard : true;
$pageTitle = isset($pageTitle) ? $pageTitle : 'Dashboard';
// Default icon for page titles, you might want to adjust these per page
$pageIconClass = isset($pageIconClass) ? $pageIconClass : 'layout-dashboard';
$pageIconColorClass = isset($pageIconColorClass) ? $pageIconColorClass : '';

// User session data
$userName = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Guest';
$userEmail = isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : '';
$userRole = isset($_SESSION['user_role']) ? htmlspecialchars($_SESSION['user_role']) : 'user';

// Generate a CSRF token if one doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinLab - <?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Home'; ?></title>
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken); ?>">

    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        /* IMPORTANT: These variables and custom CSS rules MUST be integrated into your style.css file. */
        /* They are here for demonstration purposes to show the new design and theme. */
        :root {
            /* Original Sky Blue Theme Colors */
            --primary-color: #87CEEB; /* Sky Blue */
            --primary-dark: #60bdee;  /* Slightly Darker Sky Blue */
            --primary-light: #aee5f4; /* Lighter Sky Blue */
            --primary-bg: rgba(135, 206, 235, 0.1); /* Sky Blue with 10% alpha */
            /* Other variables from your style.css remain the same */
            --highlight-bg: #e0f2fe; /* Sky 100 for highlight (already defined in your CSS) */
            --text-color: #1e293b; /* Slate 800 */
            --text-light: #64748b; /* Slate 500 */
            --card-bg: #ffffff;
            --bg-color: #f8fafc;
            --border-color: #e2e8f0;
            --border-radius: 12px;
            --border-radius-sm: 8px;
            --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            --box-shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.4);
            --transition: all 0.2s ease-in-out;
            --danger-color: #ef4444;
            --success-color: #10b981;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --highlight-bg: #0c4a6e;
                --text-color: #f1f5f9;
                --text-light: #94a3b8;
                --card-bg: #1e293b;
                --bg-color: #0f172a;
                --border-color: #334155;
            }
            .custom-header { background-color: var(--primary-dark); }
            /* Other dark mode overrides from your style.css apply */
            .swal2-popup-themed .profile-modal-content .profile-field strong { color: var(--primary-light); }
            .swal2-popup-themed .profile-modal-content .profile-field span { color: var(--text-color); }
        }

        .swal2-popup-over-modal {
            z-index: 1300 !important;
        }

        /* Navbar Layout - Modern & Functional */
        .custom-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 24px;
            background-color: var(--primary-color); /* Uses original primary color */
            box-shadow: var(--box-shadow-md); /* Slightly more prominent shadow */
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
            height: 64px;
            color: white; /* Default text color for the header */
        }
        .custom-header .logo-or-title-container {
            display: flex;
            align-items: center;
            height: 100%;
        }
        .custom-header .navbar-logo {
            height: 36px; /* Slightly larger logo */
            width: auto;
            display: block;
            filter: drop-shadow(0 2px 2px rgba(0, 0, 0, 0.3)); /* More subtle shadow for the logo */
            transition: filter 0.3s ease;
        }
        .navbar-page-title {
            display: flex;
            align-items: center;
            gap: 10px; /* Increased gap */
            font-size: 1.25rem; /* Larger font size */
            font-weight: 700; /* Bolder */
            color: white;
            letter-spacing: -0.02em; /* Tighter letter spacing for readability */
        }
        .navbar-page-title .page-title-icon {
            color: white;
            stroke: white;
            width: 24px; /* Larger icon */
            height: 24px;
            stroke-width: 2;
        }
        .custom-header .icon-container {
            display: flex;
            gap: 12px; /* Increased gap between icons */
            align-items: center;
        }
        .navbar-icon-btn {
            background: none;
            border: none;
            color: white; /* Icons are white on primary background */
            padding: 10px; /* Larger hit area */
            border-radius: 50%;
            cursor: pointer;
            transition: background-color 0.2s ease, color 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            outline: none; /* Remove default outline */
        }
        .navbar-icon-btn:hover,
        .navbar-icon-btn:focus {
            background-color: rgba(255, 255, 255, 0.2); /* Stronger hover effect */
            color: var(--primary-light); /* Slight color change on hover */
        }
        .navbar-icon-btn i {
            width: 22px; /* Larger icons */
            height: 22px;
            stroke-width: 2;
        }
        .custom-header .icon-container .notifications-badge {
            position: absolute;
            top: 0px; /* Adjust vertical position */
            right: 0px; /* Adjust horizontal position */
            background-color: var(--danger-color);
            color: white;
            font-size: 10px;
            font-weight: 600;
            padding: 1px 5px;
            border-radius: 50%;
            border: 1px solid var(--primary-color); /* Border color matches new theme */
            line-height: 1;
            pointer-events: none;
            z-index: 10; /* Ensure it's above the icon */
        }

        /* Profile Dropdown Styles - adjusted for better UX */
        .profile-menu-container {
            position: relative; /* Keep this for positioning dropdown */
        }
        .dropdown-menu {
            position: absolute;
            top: calc(100% + 15px); /* More space from navbar */
            right: 0;
            background-color: var(--card-bg); /* Set to white */
            border-radius: var(--border-radius); /* Use global border radius */
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2); /* Stronger shadow */
            width: 250px; /* Slightly wider */
            z-index: 1100;
            border: 1px solid var(--border-color);
            overflow: hidden;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: opacity 0.3s ease, transform 0.3s ease, visibility 0s linear 0.3s; /* Smoother transition */
            padding: 0;
        }
        .dropdown-menu.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            transition-delay: 0s;
        }
        .dropdown-header .username {
            display: block;
            font-size: 1rem; /* Slightly larger name */
            font-weight: 700;
            color: var(--text-color); /* Set to dark color */
            line-height: 1.3;
        }
        .dropdown-header .useremail {
            display: block;
            font-size: 0.85rem;
            color: var(--text-light); /* Set to dark color */
            line-height: 1.3;
        }
        .dropdown-item {
            padding: 10px 16px;
            border-bottom: none; /* No border between items */
            transition: var(--transition);
            cursor: pointer;
            font-size: 0.95rem; /* Slightly larger text */
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-color); /* Set to dark color */
            text-decoration: none;
        }
        .dropdown-item:last-child {
            border-bottom: none;
        }
        .dropdown-item:hover {
            background-color: var(--primary-bg);
            color: var(--primary-dark); /* Darker text on hover */
        }
        .dropdown-item i {
            width: 20px; /* Slightly larger icons */
            height: 20px;
            stroke-width: 2;
            color: var(--text-color); /* Set to dark color */
            stroke: var(--text-color);
        }
        .dropdown-item-text {
            flex-grow: 1;
        }

        /* Settings Modal Styling */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1200;
            padding: 20px;
            overflow-y: auto;
        }

        .modal-overlay.visible {
            display: flex;
        }

        .settings-modal {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow-md);
            width: 100%;
            max-width: 800px;
            padding: 30px;
            position: relative;
            animation: fadeIn 0.3s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .settings-modal .close-modal-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-light);
            cursor: pointer;
            transition: color 0.2s ease;
        }
        .settings-modal .close-modal-btn:hover { color: var(--primary-color); }
        .settings-modal h2 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 1.8rem;
            font-weight: 700;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 10px;
        }

        .settings-tabs {
            display: flex;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 20px;
        }
        .settings-tab {
            padding: 10px 20px;
            cursor: pointer;
            font-weight: 600;
            color: var(--text-light);
            border-bottom: 3px solid transparent;
            transition: var(--transition);
            margin-bottom: -1px;
            text-align: center;
            user-select: none;
        }
        .settings-tab:hover { color: var(--text-color); }
        .settings-tab.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }

        .tab-content {
            display: none;
            padding-top: 10px;
        }
        .tab-content.active { display: block; }
        .tab-content h3 {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 15px;
        }

        .form-group { margin-bottom: 15px; }
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--text-color);
        }
        .form-control, .custom-select select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-sm);
            font-size: 1rem;
            background-color: var(--bg-color);
            color: var(--text-color);
            transition: var(--transition);
        }
        .form-control:focus, .custom-select select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px var(--primary-bg);
            background-color: var(--card-bg);
        }
        .form-control.is-invalid { border-color: var(--danger-color); }
        .invalid-feedback {
            color: var(--danger-color);
            font-size: 0.85rem;
            margin-top: 5px;
            display: none;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: var(--border-radius-sm);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            text-align: center;
            user-select: none;
            text-decoration: none;
            font-size: 1rem;
        }
        .btn-primary:hover:not(:disabled) { background-color: var(--primary-dark); transform: translateY(-1px); }
        .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
        .btn-primary { background-color: var(--primary-color); color: white; }


        .form-fields-container {
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-top: 20px;
            display: none; /* Hidden by default */
        }
        .form-fields-container.visible { display: block; }
        .form-fields-container > h4 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px dashed var(--border-color);
        }

        /* Grid layout for user info fields */
        .user-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        /* Delegation fields layout */
        .delegation-fields-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }

        /* User Management Layout */
        .user-management-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .permissions-container {
            background-color: var(--bg-color);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-sm);
            padding: 15px;
            margin-top: 15px;
            display: none; /* Hidden by default */
        }
        .permissions-header {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 10px;
        }
        .permissions-search-container {
            position: relative;
            margin-bottom: 15px;
        }
        .permissions-search-results {
            position: absolute;
            top: calc(100% + 5px); /* Position it just below the input field */
            left: 0;
            right: 0;
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-sm);
            box-shadow: var(--box-shadow);
            z-index: 10;
            max-height: 200px;
            overflow-y: auto;
            display: none;
        }
        .permissions-search-results .result-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 15px;
            cursor: pointer;
            transition: var(--transition);
        }
        .permissions-search-results .result-item:hover {
            background-color: var(--primary-bg);
            color: var(--primary-dark);
        }
        .permissions-search-results .result-item i {
            color: var(--primary-color);
        }
        .selected-permissions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .permission-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-sm);
            padding: 10px 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .permission-row .app-name {
            font-weight: 500;
            color: var(--text-color);
            flex-grow: 1;
        }
        .permission-row .access-select {
            width: 150px;
            margin-left: 20px;
        }
        .permission-row .remove-btn {
            background: none;
            border: none;
            color: var(--danger-color);
            cursor: pointer;
            margin-left: 10px;
            font-size: 1.2em;
        }
        .message-container {
            margin-top: 20px;
            padding: 10px;
            border-radius: var(--border-radius-sm);
            text-align: center;
            font-weight: 600;
            transition: all 0.3s ease-in-out;
            opacity: 0;
        }
        .message-container.visible {
            opacity: 1;
            transform: translateY(0);
        }
        .message-success {
            background-color: var(--success-color);
            color: white;
        }
        .message-error {
            background-color: var(--danger-color);
            color: white;
        }
        .button-group-centered {
            display: flex;
            justify-content: center;
            gap: 10px;
        }
    </style>
</head>
<body>
<header class="custom-header">
    <div class="logo-or-title-container">
        <a href="dashboard.php" title="Go to Dashboard" style="display: flex; align-items: center; height: 100%;">
            <svg class="navbar-logo-svg" viewBox="0 0 100 100" style="fill: currentColor; color: white; width: 36px; height: 36px; display: block;">
                <path d="M 10 10 L 90 10 L 90 90 L 10 90 Z" stroke="white" stroke-width="5" fill="none" />
                <path d="M 35 75 L 35 25 L 65 75 L 65 25" stroke="white" stroke-width="5" fill="none" />
            </svg>
        </a>
    </div>

    <div class="icon-container">
        <button class="navbar-icon-btn" id="navbar-notifications-icon" title="Notifications" aria-label="Notifications">
            <i data-lucide="bell"></i>
        </button>

        <button class="navbar-icon-btn" id="navbar-help-icon" title="Help / Keyboard Shortcuts" aria-label="Help">
            <i data-lucide="help-circle"></i>
        </button>
        
        <div class="profile-menu-container relative">
            <button class="navbar-icon-btn" id="navbar-profile-icon" title="Profile" aria-label="Profile" aria-haspopup="true" aria-expanded="false">
                <i data-lucide="user"></i>
            </button>
            <div class="dropdown-menu profile-dropdown" id="profileDropdown">
                <div class="dropdown-header">
                    <span class="username"><?php echo $userName; ?></span>
                    <span class="useremail"><?php echo $userEmail; ?></span>
                </div>
                <a href="#" class="dropdown-item" id="profileViewBtn"><i data-lucide="user-cog"></i> <span class="dropdown-item-text">View Profile</span></a>
                <a href="#" class="dropdown-item" id="settingsBtn"><i data-lucide="settings-2"></i> <span class="dropdown-item-text">Settings</span></a>
                <a href="logout.php" class="dropdown-item" id="signOutBtn"><i data-lucide="log-out"></i> <span class="dropdown-item-text">Sign Out</span></a>
            </div>
        </div>
    </div>
</header>

<div id="settingsModal" class="modal-overlay" aria-hidden="true">
    <div class="settings-modal">
        <button class="close-modal-btn" aria-label="Close settings modal">&times;</button>
        <h2>Settings</h2>

        <div class="settings-tabs">
            <div class="settings-tab active" data-tab="security">Security</div>
            <div class="settings-tab" data-tab="delegation">Delegation</div>
            <?php if ($userRole === 'super-admin' || $userRole === 'admin'): ?>
                <div class="settings-tab" data-tab="users">Users</div>
                <div class="settings-tab" data-tab="access">Access</div>
            <?php endif; ?>
        </div>

        <div id="settings-content">
            <div id="security-tab" class="tab-content active">
                <h3>Account Security</h3>
                <div class="user-info-grid">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="security-fullname" value="<?php echo $userName; ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="security-email" value="<?php echo $userEmail; ?>" readonly>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <button class="btn btn-primary btn-sm" id="change-password-btn">Change Password</button>
                </div>
                
                <form id="password-change-form" class="form-fields-container">
                    <h4>Change Your Password</h4>
                    <div class="form-group">
                        <label for="current-password" class="form-label">Current Password</label>
                        <input type="password" id="current-password" name="current_password" class="form-control" autocomplete="off" required>
                        <div class="invalid-feedback" id="current-password-error"></div>
                    </div>
                    <div class="form-group">
                        <label for="new-password" class="form-label">New Password</label>
                        <input type="password" id="new-password" name="new_password" class="form-control" autocomplete="new-password" required>
                        <div id="new-password-error" class="invalid-feedback"></div>
                    </div>
                    <div class="form-group">
                        <label for="confirm-new-password" class="form-label">Confirm New Password</label>
                        <input type="password" id="confirm-new-password" name="confirm_new_password" class="form-control" autocomplete="new-password" required>
                        <div id="confirm-new-password-error" class="invalid-feedback"></div>
                    </div>
                    <button type="submit" class="btn btn-primary" id="save-new-password-btn">
                        <span class="spinner" id="password-spinner" style="display: none;"></span>
                        Save New Password
                    </button>
                </form>
            </div>

            <div id="delegation-tab" class="tab-content">
                <h3>Delegate Your Responsibilities</h3>
                <form id="delegation-form">
                    <div class="delegation-fields-grid">
                        <div class="form-group">
                            <label for="delegate-user" class="form-label">Delegate to User</label>
                            <div class="custom-select">
                                <select id="delegate-user" name="delegate_user" class="form-control" required>
                                    <option value="" disabled selected>Select a user</option>
                                    </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="delegation-reason" class="form-label">Reason for Delegation</label>
                            <textarea id="delegation-reason" name="delegation_reason" class="form-control" rows="3" placeholder="Explain why you are delegating..." required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="delegation-start-date" class="form-label">Start Date</label>
                            <input type="date" id="delegation-start-date" name="start_date" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="delegation-end-date" class="form-label">End Date</label>
                            <input type="date" id="delegation-end-date" name="end_date" class="form-control">
                        </div>
                        <div class="form-group">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="unlimited-delegation">
                                <label class="form-check-label" for="unlimited-delegation">
                                    Permanent Delegation
                                </label>
                            </div>
                        </div>
                    </div>
                    <div style="margin-top: 20px;">
                        <button type="submit" class="btn btn-primary">Save Delegation</button>
                    </div>
                </form>
            </div>
            
            <?php if ($userRole === 'super-admin' || $userRole === 'admin'): ?>
            <div id="users-tab" class="tab-content">
                <h3>User Management</h3>
                <form id="create-user-form">
                    <div class="user-management-grid">
                        <div class="form-group">
                            <label for="new-user-employee" class="form-label">Select Employee</label>
                            <div class="custom-select">
                                <select id="new-user-employee" name="employee_id" class="form-control" required>
                                    <option value="" disabled selected>Select an existing employee</option>
                                    </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="new-user-email" class="form-label">Email Address</label>
                            <input type="email" id="new-user-email" name="email" class="form-control" placeholder="Enter user's email" required>
                        </div>
                        <div class="form-group">
                            <label for="new-user-password" class="form-label">Password</label>
                            <input type="password" id="new-user-password" name="password" class="form-control" placeholder="Create a password" required>
                        </div>
                        <div class="form-group">
                            <label for="new-user-role" class="form-label">User Role</label>
                            <div class="custom-select">
                                <select id="new-user-role" name="role" class="form-control" required>
                                    <option value="" disabled selected>Select a role</option>
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                    <option value="super-admin">Super Admin</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div style="margin-top: 20px;">
                        <button type="submit" class="btn btn-primary" id="create-user-btn">
                            <span class="spinner" id="create-user-spinner" style="display: none;"></span>
                            Create User
                        </button>
                    </div>
                </form>
            </div>

            <div id="access-tab" class="tab-content">
                <h3>Access Control</h3>
                <p>Manage user roles and access permissions.</p>
                <form id="access-control-form">
                    <div class="user-management-grid">
                        <div class="form-group">
                            <label for="access-user-select" class="form-label">Select User</label>
                            <div class="custom-select">
                                <select id="access-user-select" name="user_id" class="form-control" required>
                                    <option value="" disabled selected>Select a user to modify</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="access-role-select" class="form-label">User Role</label>
                            <div class="custom-select">
                                <select id="access-role-select" name="role" class="form-control" required>
                                    <option value="" disabled selected>Select a role</option>
                                    <option value="super-admin">Super Admin</option>
                                    <option value="admin">Admin</option>
                                    <option value="finance">Finance User</option>
                                    <option value="hr">HR User</option>
                                    <option value="productivity">Productivity User</option>
                                    <option value="sales">Sales User</option>
                                    <option value="user">General User</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div id="access-permissions-container" class="permissions-container" style="display: none;">
                        <div class="permissions-header">Grant Module Permissions:</div>
                        
                        <div id="custom-permissions-controls" style="display: none;">
                            <div class="form-group">
                                <label for="app-search-input" class="form-label">Search and Add Applications</label>
                                <div class="permissions-search-container">
                                    <input type="text" id="app-search-input" class="form-control" placeholder="Search for an application...">
                                    <div id="app-search-results" class="permissions-search-results"></div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="access-type-select" class="form-label">Access Type</label>
                                <select id="access-type-select" class="form-control access-select">
                                    <option value="view">View Only</option>
                                    <option value="edit">View and Edit</option>
                                </select>
                            </div>
                            <div class="button-group-centered" style="margin-bottom: 15px;">
                            <button type="button" class="btn btn-primary btn-sm" id="add-permission-btn" >Add Application</button>
                            </div>
                        </div>

                        <div id="access-permissions-list">
                            </div>
                    </div>
                    <div class="button-group-centered" style="margin-top: 20px;">
                        <button type="submit" class="btn btn-primary" id="update-access-btn">
                            <span class="spinner" id="update-access-spinner" style="display: none;"></span>
                            Update Access
                        </button>
                    </div>
                    <div id="access-message-container" class="message-container" style="display: none;"></div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="helpModal" class="modal-overlay" aria-hidden="true">
    <div class="help-modal">
        <button class="close-modal-btn" aria-label="Close help modal">&times;</button>
        <h2>Help & Shortcuts</h2>
        <p>Quick access to common actions and keyboard shortcuts:</p>
        <ul>
            <li>
                <span>Dashboard</span>
                <kbd>Alt</kbd> + <kbd>D</kbd>
            </li>
            <li>
                <span>New Invoice</span>
                <kbd>Alt</kbd> + <kbd>I</kbd>
            </li>
            <li>
                <span>Search (Focus)</span>
                <kbd>Ctrl</kbd> + <kbd>K</kbd>
            </li>
            <li>
                <span>Toggle Dark Mode</span>
                <kbd>Ctrl</kbd> + <kbd>M</kbd>
            </li>
            <li>
                <span>Logout</span>
                <kbd>Alt</kbd> + <kbd>L</kbd>
            </li>
            <li>
                <span>Open Quick Add</span>
                <kbd>Ctrl</kbd> + <kbd>Q</kbd>
            </li>
        </ul>
        <p>Need more help? Visit our <a href="#" target="_blank">Knowledge Base</a>.</p>
    </div>
</div>

<script>
    // IMPORTANT: These scripts should ideally be in a separate .js file (e.g., assets/js/navbar.js)
    // and included after the body content. This inline script is for demonstration.

    // Ensure jQuery is available
    if (typeof jQuery === 'undefined') {
        console.error("jQuery is not loaded! Navbar JavaScript requires jQuery.");
    } else {

        // --- Define ALL Helper Functions First ---
        function escapeHtml(unsafe) {
            if (typeof unsafe !== 'string') return '';
            return unsafe.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }
        function showModalError(fieldId, message, modalContext) {
            const field = modalContext.find(`#${fieldId}`);
            const errorDiv = modalContext.find(`#${fieldId}-error, #${fieldId}-feedback`);
            if (errorDiv.length) { errorDiv.text(message).show(); }
            field.addClass('is-invalid');
        }
        function clearModalError(fieldId, modalContext) {
            const field = modalContext.find(`#${fieldId}`);
            const errorDiv = modalContext.find(`#${fieldId}-error, #${fieldId}-feedback`);
            if (errorDiv.length) { errorDiv.hide().text(''); }
            field.removeClass('is-invalid');
        }
        function clearAllModalErrors(modalContext) {
            modalContext.find('.invalid-feedback, .valid-feedback').hide().text('');
            modalContext.find('.form-control, .custom-select select, .form-check-input').removeClass('is-invalid');
        }
        function populateCountryDropdown($selectElement, selectedCountryCode = '') {
            $selectElement.empty();
            $selectElement.append($('<option>', { value: "", text: "Select country", disabled: true, selected: true }));
            const countries = [
                { code: "US", name: "United States" }, { code: "UK", name: "United Kingdom" },
                { code: "CA", name: "Canada" }, { code: "AU", name: "Australia" },
                { code: "PK", name: "Pakistan" }, { code: "IN", name: "India" },
                { code: "DE", name: "Germany" }, { code: "FR", name: "France" },
                { code: "JP", name: "Japan" }, { code: "BR", name: "Brazil" },
            ];
            countries.sort((a, b) => a.name.localeCompare(b.name));
            countries.forEach(country => {
                $selectElement.append($('<option>', {
                    value: country.code,
                    text: country.name,
                    selected: country.code === selectedCountryCode
                }));
            });
        }
        function showSpinner(buttonId, spinnerId) { $(`#${spinnerId}`).css('display', 'inline-block'); $(`#${buttonId}`).prop('disabled', true).addClass('loading'); }
        function hideSpinner(buttonId, spinnerId) { $(`#${spinnerId}`).hide(); $(`#${buttonId}`).prop('disabled', false).removeClass('loading'); }
        function showLoader(element) { element.html('<div class="loading-spinner"></div>'); }
        function hideLoader(element, originalContent) { element.html(originalContent); }

        function showMessage(containerId, message, isSuccess) {
            const $container = $(`#${containerId}`);
            $container.text(message);
            $container.removeClass('message-success message-error').addClass(isSuccess ? 'message-success' : 'message-error');
            $container.css('opacity', 0); // Reset opacity before showing
            $container.show().animate({ opacity: 1 }, 300).delay(2000).animate({ opacity: 0 }, 300, function() {
                $(this).hide();
            });
        }

        // --- Application data by category for Access tab ---
        const allAppsByCategory = {
            finance: [
                { id: 'reimbursement', name: 'Reimbursement', icon: 'fas fa-money-check-alt' },
                { id: 'tax-slabs', name: 'Tax Slabs', icon: 'fas fa-percent' },
                { id: 'invoicing', name: 'Invoicing', icon: 'fas fa-file-invoice' },
                { id: 'accounting', name: 'Accounting', icon: 'fas fa-calculator' },
                { id: 'inventory', name: 'Inventory', icon: 'fas fa-boxes' },
                { id: 'purchase', name: 'Purchase', icon: 'fas fa-shopping-cart' },
                { id: 'expenses', name: 'Expenses', icon: 'fas fa-file-invoice-dollar' },
                { id: 'audit', name: 'Audit', icon: 'fas fa-search' },
                { id: 'tax-filing', name: 'Tax Filing', icon: 'fas fa-file-alt' },
                { id: 'bookkeeping', name: 'Bookkeeping', icon: 'fas fa-calculator' },
                { id: 'vendors', name: 'Vendors', icon: 'fas fa-truck' }
            ],
            hr: [
                { id: 'employees', name: 'Employees', icon: 'fas fa-users' },
                { id: 'payroll', name: 'Payroll', icon: 'fas fa-money-bill-wave' },
                { id: 'departments', name: 'Departments', icon: 'fas fa-building' },
                { id: 'attendance', name: 'Attendance', icon: 'fas fa-calendar-check' },
                { id: 'recruitment', name: 'Recruitment', icon: 'fas fa-user-plus' },
                { id: 'final-settlement', name: 'Final Settlement', icon: 'fas fa-hand-holding-usd' }
            ],
            productivity: [
                { id: 'timesheet', name: 'Timesheet', icon: 'fas fa-clock' },
                { id: 'project', name: 'Project', icon: 'fas fa-tasks' },
                { id: 'documents', name: 'Documents', icon: 'fas fa-folder' },
                { id: 'approval', name: 'Approval', icon: 'fas fa-check-circle' },
                { id: 'knowledge', name: 'Knowledge', icon: 'fas fa-book' },
                { id: 'calendar', name: 'Calendar', icon: 'fas fa-calendar-alt' }
            ],
            sales: [
                { id: 'contacts', name: 'Contacts', icon: 'fas fa-address-book' }
            ]
        };

        // Consolidate all applications into a single, sorted array
        const allApplications = [
            ...allAppsByCategory.finance.map(app => ({...app, category: 'Finance'})),
            ...allAppsByCategory.hr.map(app => ({...app, category: 'HR'})),
            ...allAppsByCategory.productivity.map(app => ({...app, category: 'Productivity'})),
            ...allAppsByCategory.sales.map(app => ({...app, category: 'Sales'})),
        ].sort((a, b) => a.name.localeCompare(b.name));

        function populateAccessPermissions(role, permissions = {}) {
            const $permissionsContainer = $('#access-permissions-container');
            const $permissionsList = $('#access-permissions-list');
            const $customControls = $('#custom-permissions-controls');
            
            $permissionsList.empty(); // Clear previous permissions

            let appsToDisplay = [];
            
            const granularRoles = ['finance', 'hr', 'productivity', 'sales'];

            if (granularRoles.includes(role)) {
                $customControls.hide();
                appsToDisplay = allAppsByCategory[role];

                if (appsToDisplay && appsToDisplay.length > 0) {
                    appsToDisplay.forEach(app => {
                        const hasEdit = (permissions[app.id] && permissions[app.id].includes('edit'));
                        const rowHtml = `
                            <div class="permission-row" data-app-id="${app.id}">
                                <span class="app-name">${app.name} (${role.charAt(0).toUpperCase() + role.slice(1)})</span>
                                <select class="form-control access-select">
                                    <option value="view">View Only</option>
                                    <option value="edit" ${hasEdit ? 'selected' : ''}>View and Edit</option>
                                </select>
                                <button type="button" class="remove-btn" title="Remove permission">&times;</button>
                            </div>
                        `;
                        $permissionsList.append(rowHtml);
                    });
                } else {
                    $permissionsList.html('<p class="text-light">No specific application permissions for this role.</p>');
                }
            } else if (role === 'user') {
                $customControls.show();
                // For a custom user, we populate the list with their existing permissions
                if (permissions && Object.keys(permissions).length > 0) {
                    Object.entries(permissions).forEach(([appId, accessTypes]) => {
                        const app = allApplications.find(a => a.id === appId);
                        if (app) {
                        const hasEdit = accessTypes.includes('edit');
                        const rowHtml = `
                               <div class="permission-row" data-app-id="${app.id}">
                                    <span class="app-name">${app.name} (${app.category})</span>
                                    <select class="form-control access-select">
                                        <option value="view" ${!hasEdit ? 'selected' : ''}>View Only</option>
                                        <option value="edit" ${hasEdit ? 'selected' : ''}>View and Edit</option>
                                    </select>
                                    <button type="button" class="remove-btn" title="Remove permission">&times;</button>
                                </div>
                        `;
                        $permissionsList.append(rowHtml);
                        }
                    });
                }
            } else {
                // Hide the permissions section for Super Admin and Admin
                $permissionsContainer.hide();
                $customControls.hide();
            }
        }
        
        $(document).ready(function() {
            console.log("NAVBAR DEBUG: Document ready. Initializing navbar JS...");

            // --- Navbar Icons & Dropdown Logic ---
            const $profileBtn = $('#navbar-profile-icon');
            const $profileDropdown = $('#profileDropdown');
            const $settingsModal = $('#settingsModal');
            const $helpModal = $('#helpModal');

            if ($profileBtn.length && $profileDropdown.length) {
                $profileBtn.on('click', function(event) {
                    event.preventDefault();
                    event.stopPropagation();
                    $profileDropdown.toggleClass('show');
                    $(this).attr('aria-expanded', $profileDropdown.hasClass('show'));
                });

                $(document).on('click', function(event) {
                    if ($profileDropdown.hasClass('show') && !$profileBtn.is(event.target) && !$profileBtn.has(event.target).length && !$profileDropdown.is(event.target) && !$profileDropdown.has(event.target).length) {
                        $profileDropdown.removeClass('show');
                        $profileBtn.attr('aria-expanded', 'false');
                    }
                });
            } else {
                console.error("NAVBAR DEBUG: Profile button or dropdown element missing.");
            }

            // Notifications Icon Click
            $('#navbar-notifications-icon').on('click', function(event) {
                event.preventDefault();
                Swal.fire({
                    icon: 'info',
                    title: 'Notifications',
                    text: 'Notifications feature is coming soon!',
                    timer: 2000,
                    showConfirmButton: false,
                    customClass: { popup: 'swal2-popup-over-modal' }
                });
            });
            // Help Modal Logic
            const helpButton = document.getElementById('navbar-help-icon');
            if (helpButton) {
                helpButton.addEventListener('click', function(event) {
                    event.preventDefault();
                    $helpModal.addClass('visible').attr('aria-hidden', 'false');
                });
            } else {
                console.warn("NAVBAR DEBUG: Help button (#navbar-help-icon) not found.");
            }

            $helpModal.on('click', '.close-modal-btn', function() {
                $helpModal.removeClass('visible').attr('aria-hidden', 'true');
            });
            $('#settingsModal').on('click', '.close-modal-btn', function() {
                $('#settingsModal').removeClass('visible').attr('aria-hidden', 'true');
            });
            $('#settingsModal').on('click', function(event) {
                if ($(event.target).is('#settingsModal')) {
                    $('#settingsModal').removeClass('visible').attr('aria-hidden', 'true');
                }
            });

            // --- Profile Modal Logic (Using SweetAlert2) ---
            $('#profileViewBtn').on('click', async function(e) {
                e.preventDefault();
                $profileDropdown.removeClass('show');
                Swal.fire({
                    title: 'Loading Profile...',
                    html: '<div class="spinner-border text-primary" role="status"></div>', // A simple loading spinner
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    customClass: { popup: 'swal2-popup-over-modal' }, // FIX: Added to show on top
                    willOpen: () => Swal.showLoading()
                });
                try {
                    const response = await $.ajax({
                        url: 'api.php',
                        method: 'GET',
                        data: { action: 'get_user_profile' },
                        dataType: 'json',
                        cache: false,
                    });
                    if (response.success && response.user) {
                        const user = response.user;
                        const joinedDate = user.created_at ? new Date(user.created_at).toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A';
                        const mobileNumber = user.mobile_number || 'N/A';
                        const businessName = user.business_name || 'N/A';
                        const businessRegNo = user.business_reg || 'N/A';
                        const businessType = user.business_type || 'N/A';
                        const role = user.role || 'User';
                        const avatarUrl = user.avatar_url || null;
                        let avatarSrc = `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name || 'U')}&background=87CEEB&color=ffffff&size=90&rounded=true`;
                        if (avatarUrl) {
                            let avatarPath = avatarUrl.startsWith('uploads/') ? './' + avatarUrl : avatarUrl;
                            avatarSrc = escapeHtml(avatarPath) + '?t=' + new Date().getTime();
                        }
                        const profileHtml = `
                            <div class="profile-modal-avatar-container">
                                <img src="${avatarSrc}" alt="Profile" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(user.name || 'U')}&background=87CEEB&color=ffffff&size=90&rounded=true';">
                            </div>
                            <div class="profile-modal-content">
                                <div class="profile-modal-section-title">Personal Information</div>
                                <div class="profile-field-item"><strong>Name</strong> <span>${escapeHtml(user.name || 'N/A')}</span></div>
                                <div class="profile-field-item"><strong>Email</strong> <span>${escapeHtml(user.email || 'N/A')}</span></div>
                                <div class="profile-field-item"><strong>Mobile</strong> <span>${escapeHtml(mobileNumber)}</span></div>
                                <div class="profile-modal-section-title">Business Information</div>
                                <div class="profile-field-item"><strong>Business</strong> <span>${escapeHtml(businessName)}</span></div>
                                <div class="profile-field-item"><strong>Type</strong> <span>${escapeHtml(businessType)}</span></div>
                                <div class="profile-field-item"><strong>Reg No</strong> <span>${escapeHtml(businessRegNo)}</span></div>
                                <div class="profile-field-item"><strong>Country</strong> <span>${escapeHtml(user.business_country || 'N/A')}</span></div>
                                <div class="profile-modal-section-title">Account Details</div>
                                <div class="profile-field-item"><strong>Role</strong> <span>${escapeHtml(role)}</span></div>
                                <div class="profile-field-item"><strong>Member Since</strong> <span>${joinedDate}</span></div>
                            </div>
                        `;
                        Swal.fire({
                            title: 'User Profile',
                            html: profileHtml,
                            showCloseButton: true,
                            showCancelButton: false,
                            confirmButtonText: '<i data-lucide="pencil" class="inline-block mr-1"></i> Edit Profile',
                            confirmButtonColor: 'var(--primary-color)',
                            customClass: { popup: 'swal2-popup-themed swal2-popup-over-modal' },
                            didOpen: () => {
                                if (typeof lucide !== 'undefined') { lucide.createIcons(); }
                            }
                        }).then((result) => {
                            if (result.isConfirmed) { showEditProfileModal(user); }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Could not load profile data.',
                            customClass: { popup: 'swal2-popup-over-modal' }
                        });
                    }
                } catch (error) {
                    console.error("NAVBAR DEBUG: AJAX error fetching profile:", error);
                    // ADDED: Log more detail about the error to the console.
                    console.error("NAVBAR DEBUG: Full error object for profile fetch:", error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while fetching profile data. Check the browser console for details.',
                        customClass: { popup: 'swal2-popup-over-modal' }
                    });
                }
            });

            function showEditProfileModal(user) {
                const editFormHtml = `
                    <div class="edit-profile-modal-content">
                        <div class="profile-header">
                            <div class="profile-avatar-container">
                                <img class="profile-avatar" id="profile-avatar-preview" src="${user.avatar_url ? escapeHtml(user.avatar_url) : `https://ui-avatars.com/api/?name=${encodeURIComponent(user.first_name + ' ' + user.last_name || 'U')}&background=87CEEB&color=ffffff&size=120&rounded=true`}" alt="User Profile">
                                <label for="avatar-upload" class="avatar-upload-btn" title="Change profile picture">
                                    <i class="fas fa-camera"></i>
                                </label>
                                <input type="file" id="avatar-upload" name="avatar_upload" accept="image/*">
                            </div>
                            <div class="user-details">
                                <h3 class="user-name">${escapeHtml(user.first_name || '') + ' ' + escapeHtml(user.last_name || '')}</h3>
                                <span class="user-email">${escapeHtml(user.email || '')}</span>
                            </div>
                        </div>

                        <form id="edit-profile-form">
                            <div class="form-section">
                                <h4 class="form-section-title">Personal Information</h4>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="edit-first-name" class="form-label">First Name</label>
                                        <input type="text" id="edit-first-name" name="first_name" class="form-control" value="${escapeHtml(user.first_name || '')}" required>
                                        <div id="edit-first-name-error" class="invalid-feedback"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit-last-name" class="form-label">Last Name</label>
                                        <input type="text" id="edit-last-name" name="last_name" class="form-control" value="${escapeHtml(user.last_name || '')}" required>
                                        <div id="edit-last-name-error" class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="edit-email" class="form-label">Email Address</label>
                                        <input type="email" id="edit-email" name="email" class="form-control" value="${escapeHtml(user.email || '')}" disabled>
                                        <div id="edit-email-error" class="invalid-feedback"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit-mobile" class="form-label">Mobile Number</label>
                                        <input type="text" id="edit-mobile" name="mobile_number" class="form-control" value="${escapeHtml(user.mobile_number || '')}">
                                        <div id="edit-mobile-error" class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-section">
                                <h4 class="form-section-title">Business Information</h4>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="edit-business-name" class="form-label">Business Name</label>
                                        <input type="text" id="edit-business-name" name="business_name" class="form-control" value="${escapeHtml(user.business_name || '')}" required>
                                        <div id="edit-business-name-error" class="invalid-feedback"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit-business-type" class="form-label">Business Type</label>
                                        <select id="edit-business-type" name="business_type" class="form-control" required>
                                            <option value="">Select type</option>
                                            <option value="sole">Sole Proprietorship</option>
                                            <option value="partnership">Partnership</option>
                                            <option value="llc">LLC</option>
                                            <option value="corporation">Corporation</option>
                                            <option value="nonprofit">Nonprofit</option>
                                            <option value="other">Other</option>
                                        </select>
                                        <div id="edit-business-type-error" class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="edit-business-reg" class="form-label">Business Registration Number</label>
                                        <input type="text" id="edit-business-reg" name="business_reg" class="form-control" value="${escapeHtml(user.business_reg || '')}">
                                        <div id="edit-business-reg-error" class="invalid-feedback"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit-business-country" class="form-label">Business Country</label>
                                        <select id="edit-business-country" name="business_country" class="form-control" required></select>
                                        <div id="edit-business-country-error" class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-section">
                                <h4 class="form-section-title">Account Details</h4>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="edit-role" class="form-label">Role</label>
                                        <input type="text" id="edit-role" name="role" class="form-control" value="${escapeHtml(user.role || 'User')}" disabled>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit-user-id" class="form-label">User ID</label>
                                        <input type="text" id="edit-user-id" name="user_id" class="form-control" value="${escapeHtml(user.id || 'N/A')}" disabled>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                `;

                Swal.fire({
                    title: 'Edit Profile',
                    html: editFormHtml,
                    showCloseButton: true,
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-save"></i> Update Profile',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: 'var(--primary-color)',
                    cancelButtonColor: 'var(--text-light)',
                    customClass: { popup: 'swal2-popup-themed swal2-popup-over-modal', actions: 'swal2-actions-spaced' },
                    didOpen: (modalElement) => {
                        const $modalContent = $(modalElement);
                        if (typeof lucide !== 'undefined') { lucide.createIcons(); }
                        populateCountryDropdown($modalContent.find('#edit-business-country'), user.business_country);
                        $modalContent.find('#edit-business-type').val(user.business_type || '');

                        $modalContent.find('input, select').on('input change', function() {
                            clearModalError($(this).attr('id'), $modalContent);
                        });

                        // Handle the new avatar upload logic
                        $modalContent.find('#avatar-upload').on('change', function() {
                            const file = this.files[0];
                            if (file) {
                                const reader = new FileReader();
                                reader.onload = function(e) {
                                    $modalContent.find('#profile-avatar-preview').attr('src', e.target.result);
                                };
                                reader.readAsDataURL(file);
                            }
                        });
                    },
                    preConfirm: async () => {
                        const $modalContent = $('.swal2-popup');
                        clearAllModalErrors($modalContent);
                        
                        const formData = new FormData();
                        formData.append('action', 'update_user_profile');
                        formData.append('csrf_token', $('meta[name="csrf-token"]').attr('content'));
                        formData.append('first_name', $modalContent.find('#edit-first-name').val().trim());
                        formData.append('last_name', $modalContent.find('#edit-last-name').val().trim());
                        formData.append('mobile_number', $modalContent.find('#edit-mobile').val().trim());
                        formData.append('business_name', $modalContent.find('#edit-business-name').val().trim());
                        formData.append('business_type', $modalContent.find('#edit-business-type').val());
                        formData.append('business_reg', $modalContent.find('#edit-business-reg').val().trim()); // Corrected field name
                        formData.append('business_country', $modalContent.find('#edit-business-country').val());
                        
                        const avatarFile = $modalContent.find('#avatar-upload')[0].files[0];
                        if (avatarFile) {
                            formData.append('avatar_upload', avatarFile);
                        }
                        
                        // Client-side validation
                        let isValid = true;
                        if (!formData.get('first_name')) { showModalError('edit-first-name', 'First Name is required.', $modalContent); isValid = false; }
                        if (!formData.get('last_name')) { showModalError('edit-last-name', 'Last Name is required.', $modalContent); isValid = false; }
                        if (!formData.get('business_name')) { showModalError('edit-business-name', 'Business Name is required.', $modalContent); isValid = false; }
                        if (!formData.get('business_type')) { showModalError('edit-business-type', 'Business Type is required.', $modalContext); isValid = false; }
                        if (!formData.get('business_country')) { showModalError('edit-business-country', 'Business Country is required.', $modalContext); isValid = false; }
                        if (formData.get('business_country') === 'PK' && !formData.get('business_reg')) {
                            showModalError('edit-business-reg', 'Registration number is required for Pakistan.', $modalContext); isValid = false;
                        }
                        if (!isValid) { return false; }

                        Swal.showLoading();
                        
                        try {
                            const response = await $.ajax({
                                url: 'api.php',
                                method: 'POST',
                                data: formData,
                                dataType: 'json',
                                processData: false,
                                contentType: false,
                            });
                            
                            if (response.success) {
                                Swal.hideLoading();
                                await Swal.fire({
                                    icon: 'success',
                                    title: 'Profile Updated!',
                                    text: response.message || 'Your profile has been successfully updated.',
                                    timer: 2000,
                                    showConfirmButton: false,
                                    customClass: { popup: 'swal2-popup-themed swal2-popup-over-modal' }
                                });
                                // Refresh the page to show the updated profile info in the navbar
                                window.location.reload();  
                            } else {
                                Swal.hideLoading();
                                if (response.errors) {
                                    $.each(response.errors, function(key, message) {
                                        const fieldId = `edit-${key.replace(/_/g, '-')}`;
                                        showModalError(fieldId, message, $modalContent);
                                    });
                                    Swal.showValidationMessage('Please correct the errors.');
                                } else {
                                    Swal.showValidationMessage(response.message || 'Failed to update profile. Please try again.');
                                }
                                return false;
                            }
                        } catch (jqXHR) {
                            Swal.hideLoading();
                            let errorMessage = 'An error occurred while communicating with the server.';
                            if (jqXHR.responseText) {
                                // ADDED: Try to parse the response text if it's not a valid JSON.
                                try {
                                    const errorResponse = JSON.parse(jqXHR.responseText);
                                    errorMessage = errorResponse.message || errorMessage;
                                } catch (e) {
                                    console.warn("NAVBAR DEBUG: Could not parse non-JSON response from server.", jqXHR.responseText);
                                    errorMessage = 'Received an invalid response from the server.';
                                }
                            }
                            Swal.showValidationMessage(errorMessage);
                            return false;
                        }
                    }
                });
            }
            
            // --- Settings Modal Logic ---
            $('#settingsBtn').on('click', async function(e) {
                e.preventDefault();
                $profileDropdown.removeClass('show');
                
                const $settingsModal = $('#settingsModal');
                $settingsModal.addClass('visible').attr('aria-hidden', 'false');
                
                // FIX: Check if the element exists before trying to access it
                const createUserForm = $('#create-user-form')[0];
                if (createUserForm) {
                    createUserForm.reset();
                    $('#new-user-employee').prop('selectedIndex', 0);
                    $('#new-user-email').val('');
                    $('#new-user-password').val('');
                }
                
                // Load initial data for the User Management tab and Access tab
                if ($('.settings-tab[data-tab="users"]').length) {
                    try {
                        const employeesResponse = await $.get('api.php', { action: 'get_employees' });
                        const $employeeSelect = $('#new-user-employee');
                        const $accessUserSelect = $('#access-user-select');
                        
                        $employeeSelect.empty().append('<option value="" disabled selected>Select an existing employee</option>');
                        $accessUserSelect.empty().append('<option value="" disabled selected>Select a user to modify</option>');
                        
                        if (employeesResponse.success && employeesResponse.employees) {
                            employeesResponse.employees.forEach(emp => {
                                const option = `<option value="${emp.id}" data-email="${escapeHtml(emp.contact_email)}">${escapeHtml(emp.full_name)}</option>`;
                                $employeeSelect.append(option);
                            });
                        }
                        
                        // Use the new API endpoint to get only registered users
                        const usersResponse = await $.get('api.php', { action: 'get_registered_users' });
                        if (usersResponse.success && usersResponse.users) {
                            usersResponse.users.forEach(user => {
                                const option = `<option value="${user.id}" data-email="${escapeHtml(user.email)}">${escapeHtml(user.name)} (${escapeHtml(user.email)})</option>`;
                                $accessUserSelect.append(option);
                            });
                        }
                    } catch (error) {
                        console.error("Error fetching employees or users:", error);
                    }
                }
            });

            $('#settingsModal').on('click', '.close-modal-btn', function() {
                $('#settingsModal').removeClass('visible').attr('aria-hidden', 'true');
            });
            $('#settingsModal').on('click', function(event) {
                if ($(event.target).is('#settingsModal')) {
                    $('#settingsModal').removeClass('visible').attr('aria-hidden', 'true');
                }
            });

            // Tab switching
            $('#settingsModal').on('click', '.settings-tab', function() {
                const tab = $(this).data('tab');
                $('.settings-tab').removeClass('active');
                $(this).addClass('active');
                $('#settings-content .tab-content').removeClass('active');
                $(`#${tab}-tab`).addClass('active');
            });

            // Password change form toggle
            $('#change-password-btn').on('click', function() {
                $('#password-change-form').toggleClass('visible');
            });

            // Delegation checkbox toggle
            $('#unlimited-delegation').on('change', function() {
                const isChecked = $(this).is(':checked');
                $('#delegation-end-date').prop('disabled', isChecked).val('');
            });

            // User Management - Employee selection
            $('#new-user-employee').on('change', function() {
                const employeeId = $(this).val();
                if (employeeId) {
                    const selectedEmail = $(this).find('option:selected').data('email');
                    $('#new-user-email').val(selectedEmail);
                } else {
                    $('#new-user-email').val('');
                }
            });

            // New logic for the Access tab
            $('#access-user-select').on('change', async function() {
                const userId = $(this).val();
                if (!userId) {
                    $('#access-permissions-container').hide();
                    return;
                }
                
                try {
                    const response = await $.get('api.php', { action: 'get_user_permissions', user_id: userId });
                    if (response.success) {
                        $('#access-permissions-container').show();
                        $('#access-role-select').val(response.role);
                        // Populate permissions based on role
                        populateAccessPermissions(response.role, response.permissions);
                    } else {
                        showMessage('access-message-container', response.message || 'Failed to fetch user permissions.', false);
                    }
                } catch (error) {
                    console.error("Error fetching user permissions:", error);
                    showMessage('access-message-container', 'An error occurred while fetching user permissions.', false);
                }
            });
            
            $('#access-role-select').on('change', function() {
                const selectedRole = $(this).val();
                const $permissionsContainer = $('#access-permissions-container');
                
                $permissionsContainer.show();
                
                // Clear and re-populate permissions based on the new role
                populateAccessPermissions(selectedRole);
            });
            
            // --- General User Permissions Logic ---
            const $appSearchInput = $('#app-search-input');
            const $appSearchResults = $('#app-search-results');
            const $addPermissionBtn = $('#add-permission-btn');
            const $accessPermissionsList = $('#access-permissions-list');
            
            // Filter applications as the user types
            $appSearchInput.on('input', function() {
                const searchTerm = $(this).val().toLowerCase().trim();
                $appSearchResults.empty();

                if (searchTerm.length > 0) {
                    const filteredApps = allApplications.filter(app => 
                        app.name.toLowerCase().includes(searchTerm) || app.category.toLowerCase().includes(searchTerm)
                    );
                    
                    if (filteredApps.length > 0) {
                        filteredApps.forEach(app => {
                            const resultItem = `<div class="result-item" data-app-id="${app.id}" data-app-name="${app.name}" data-app-category="${app.category}"><span>${app.name} (${app.category})</span></div>`;
                            $appSearchResults.append(resultItem);
                        });
                        $appSearchResults.show();
                    } else {
                        $appSearchResults.html('<div class="result-item" style="cursor: default;">No applications found.</div>').show();
                    }
                } else {
                    $appSearchResults.hide();
                }
            });

            // Handle selection from search results
            $appSearchResults.on('click', '.result-item', function() {
                const appId = $(this).data('app-id');
                const appName = $(this).data('app-name');
                const appCategory = $(this).data('app-category');
                $appSearchInput.val(`${appName} (${appCategory})`).data('selected-app-id', appId);
                $appSearchResults.hide();
            });

            // Add selected app to the permissions list
            $addPermissionBtn.on('click', function() {
                const appId = $appSearchInput.data('selected-app-id');
                const appNameWithCategory = $appSearchInput.val();
                const accessType = $('#access-type-select').val();

                if (appId) {
                    // Check if the permission already exists
                    if ($accessPermissionsList.find(`.permission-row[data-app-id='${appId}']`).length === 0) {
                            const newRow = `
                            <div class="permission-row" data-app-id="${appId}">
                                <span class="app-name">${appNameWithCategory}</span>
                                <select class="form-control access-select">
                                    <option value="view" ${accessType === 'view' ? 'selected' : ''}>View Only</option>
                                    <option value="edit" ${accessType === 'edit' ? 'selected' : ''}>View and Edit</option>
                                </select>
                                <button type="button" class="remove-btn" title="Remove permission">&times;</button>
                            </div>
                        `;
                        $accessPermissionsList.append(newRow);
                        $appSearchInput.val('').data('selected-app-id', null);
                    } else {
                        showMessage('access-message-container', 'This application is already in the list.', false);
                    }
                }
            });

            // Remove a permission row
            $('#access-permissions-list').on('click', '.remove-btn', function() {
                $(this).closest('.permission-row').remove();
            });
            
            // --- User Creation Form Submission (NEW LOGIC) ---
            $('#create-user-form').on('submit', async function(e) {
                e.preventDefault();
                const $form = $(this);
                const $modalContext = $form.closest('.settings-modal');
                clearAllModalErrors($modalContext);

                // Collect form data
                const employeeId = $('#new-user-employee').val();
                const email = $('#new-user-email').val().trim();
                const password = $('#new-user-password').val();
                const role = $('#new-user-role').val();
                const csrfToken = $('meta[name="csrf-token"]').attr('content');

                // Basic client-side validation
                let isValid = true;
                if (!employeeId) { showModalError('new-user-employee', 'Please select an employee.', $modalContext); isValid = false; }
                if (!email) { showModalError('new-user-email', 'Email is required.', $modalContext); isValid = false; }
                if (!password) { showModalError('new-user-password', 'Password is required.', $modalContext); isValid = false; }
                if (!role) { showModalError('new-user-role', 'Role is required.', $modalContext); isValid = false; }
                if (!isValid) { return; }

                showSpinner('create-user-btn', 'create-user-spinner');

                try {
                    const response = await $.ajax({
                        url: 'api.php',
                        method: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'create_user',
                            csrf_token: csrfToken,
                            employee_id: employeeId,
                            email: email,
                            password: password,
                            role: role,
                        }
                    });

                    if (response.success) {
                        showMessage('access-message-container', response.message, true);
                        $form[0].reset();
                        clearAllModalErrors($modalContext);
                        
                        // Reload employee list to reflect the new user
                        await $.get('api.php', { action: 'get_employees' }).then(employeesResponse => {
                               const $employeeSelect = $('#new-user-employee');
                               const $accessUserSelect = $('#access-user-select');
                               $employeeSelect.empty().append('<option value="" disabled selected>Select an existing employee</option>');
                               $accessUserSelect.empty().append('<option value="" disabled selected>Select a user to modify</option>');
                               if (employeesResponse.success && employeesResponse.employees) {
                                   employeesResponse.employees.forEach(emp => {
                                       const option = `<option value="${emp.id}" data-email="${escapeHtml(emp.contact_email)}">${escapeHtml(emp.full_name)}</option>`;
                                       $employeeSelect.append(option);
                                   });
                               }
                        });
                    } else {
                        showMessage('access-message-container', response.message || 'Failed to create user. Please try again.', false);
                    }
                } catch (jqXHR) {
                    console.error("User creation AJAX error:", jqXHR);
                    showMessage('access-message-container', 'An error occurred. Please check the console.', false);
                } finally {
                    hideSpinner('create-user-btn', 'create-user-spinner');
                }
            });
            // End of NEW LOGIC

            // --- User Access Form Submission (NEW LOGIC) ---
            $('#access-control-form').on('submit', async function(e) {
                e.preventDefault();
                const $form = $(this);
                const userId = $('#access-user-select').val();
                const newRole = $('#access-role-select').val();
                const csrfToken = $('meta[name="csrf-token"]').attr('content');
                
                if (!userId || !newRole) {
                    showMessage('access-message-container', 'Please select a user and a role.', false);
                    return;
                }
                
                const permissions = [];
                $('#access-permissions-list .permission-row').each(function() {
                    const appId = $(this).data('app-id');
                    const accessType = $(this).find('.access-select').val();
                    permissions.push({ app_id: appId, access_type: accessType });
                });
                
                showSpinner('update-access-btn', 'update-access-spinner');
                
                try {
                    const response = await $.ajax({
                        url: 'api.php',
                        method: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'update_user_access',
                            csrf_token: csrfToken,
                            user_id: userId,
                            new_role: newRole,
                            permissions: JSON.stringify(permissions)
                        }
                    });
                    
                    if (response.success) {
                        showMessage('access-message-container', response.message, true);
                        if (response.refresh) {
                            window.location.reload();
                        }
                    } else {
                        showMessage('access-message-container', response.message || 'Failed to update user access.', false);
                    }
                } catch (jqXHR) {
                    console.error("User access update AJAX error:", jqXHR);
                    showMessage('access-message-container', 'An error occurred. Please check the console.', false);
                } finally {
                    hideSpinner('update-access-btn', 'update-access-spinner');
                }
            });
            // End of NEW LOGIC

            // Handle password change form submission
            $('#password-change-form').on('submit', async function(e) {
                e.preventDefault();
                const $form = $(this);
                const $modalContext = $form.closest('.settings-modal');
                clearAllModalErrors($modalContext);

                const currentPassword = $('#current-password').val();
                const newPassword = $('#new-password').val();
                const confirmNewPassword = $('#confirm-new-password').val();

                let isValid = true;
                if (!currentPassword) { showModalError('current-password', 'Current password is required.', $modalContext); isValid = false; }
                if (!newPassword) { showModalError('new-password', 'New password is required.', $modalContext); isValid = false; }
                if (newPassword !== confirmNewPassword) { showModalError('confirm-new-password', 'Passwords do not match.', $modalContext); isValid = false; }
                // Add your password complexity validation here...

                if (!isValid) { return; }

                showSpinner('save-new-password-btn', 'password-spinner');

                try {
                    const response = await $.ajax({
                        url: 'api.php',
                        method: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'change_password',
                            csrf_token: $('meta[name="csrf-token"]').attr('content'),
                            current_password: currentPassword,
                            new_password: newPassword,
                        }
                    });

                    if (response.success) {
                        showMessage('access-message-container', response.message, true);
                        $form[0].reset();
                        $('#password-change-form').removeClass('visible');
                    } else {
                        let errorMessage = response.message || 'Failed to change password.';
                        showModalError('current-password', errorMessage, $modalContext);
                    }
                } catch (jqXHR) {
                    console.error("Password change AJAX error:", jqXHR);
                    let errorMessage = 'An error occurred while communicating with the server.';
                    if (jqXHR.responseText) {
                        // ADDED: Try to parse the response text if it's not a valid JSON.
                        try {
                            const errorResponse = JSON.parse(jqXHR.responseText);
                            errorMessage = errorResponse.message || errorMessage;
                        } catch (e) {
                            console.warn("NAVBAR DEBUG: Could not parse non-JSON response from server.", jqXHR.responseText);
                            errorMessage = 'Received an invalid response from the server.';
                        }
                    }
                    showModalError('current-password', errorMessage, $modalContext);
                } finally {
                    hideSpinner('save-new-password-btn', 'password-spinner');
                }
            });


            // --- Initialize Lucide Icons and Font Awesome ---
            try {
                if (typeof lucide !== 'undefined' && typeof lucide.createIcons === 'function') {
                    console.log("NAVBAR DEBUG: Initial Lucide icon rendering...");
                    lucide.createIcons();
                    console.log("NAVBAR DEBUG: Lucide icons rendered.");
                } else {
                    console.warn("NAVBAR DEBUG: Lucide library not available or createIcons function missing.");
                }
            } catch (lucideError) {
                console.error("NAVBAR DEBUG: Error calling lucide.createIcons():", lucideError);
            }

            console.log("NAVBAR DEBUG: Navbar JS setup finished.");
        }); // End document ready
    } // End jQuery check
</script>