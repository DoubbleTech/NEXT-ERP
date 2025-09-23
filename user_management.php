<?php
// Start session and check for authentication
session_start();
require_once 'config.php';
require_once 'functions.php';
require_once 'classes/UserManager.php';

// Check for permission to access this page
$currentUserRole = $_SESSION['user_role'] ?? 'user';
if ($currentUserRole !== 'super-admin' && $currentUserRole !== 'admin') {
    // Redirect to the dashboard or an access denied page
    header('Location: dashboard.php');
    exit;
}

// Page variables for the navbar
$isDashboard = false;
$pageTitle = 'User Management';
include 'navbar.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - FinLab ERP</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Add some basic styles for the form */
        body { padding-top: 5rem; }
        .container { max-width: 800px; margin: 2rem auto; padding: 2rem; background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { text-align: center; margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.5rem; }
        label { font-weight: 600; display: block; margin-bottom: 0.5rem; }
        input, select { width: 100%; padding: 0.75rem; border: 1px solid #ccc; border-radius: 4px; }
        .btn-primary { background-color: #87CEEB; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; }
        .btn-primary:hover { background-color: #60bdee; }
        .error-message { color: #ef4444; font-size: 0.8em; margin-top: 0.25rem; display: none; }
        .spinner { display: inline-block; width: 1.2em; height: 1.2em; border: 3px solid rgba(255,255,255,0.3); border-radius: 50%; border-top-color: white; animation: spin 1s ease-in-out infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="container">
        <h1>Create New User</h1>
        <div id="general-message"></div>
        <form id="create-user-form">
            <div class="form-group">
                <label for="new_user_email">Email Address</label>
                <input type="email" id="new_user_email" name="new_user_email" required>
                <div class="error-message" id="new_user_email-error"></div>
            </div>
            <div class="form-group">
                <label for="new_user_password">Password</label>
                <input type="password" id="new_user_password" name="new_user_password" required>
                <div class="error-message" id="new_user_password-error"></div>
            </div>
            <div class="form-group">
                <label for="new_user_role">Role</label>
                <select id="new_user_role" name="new_user_role" required>
                    <option value="user">User</option>
                    <?php if ($currentUserRole === 'super-admin'): ?>
                        <option value="admin">Admin</option>
                    <?php endif; ?>
                </select>
                <div class="error-message" id="new_user_role-error"></div>
            </div>
            <button type="submit" class="btn-primary" id="create-user-btn">
                <span id="spinner" style="display: none;"><i class="fas fa-spinner fa-spin"></i></span>
                Create User
            </button>
        </form>
    </div>

    <script>
        $(document).ready(function() {
            const $form = $('#create-user-form');
            const $btn = $('#create-user-btn');
            const $spinner = $('#spinner');
            const $generalMessage = $('#general-message');

            function showMessage(message, type) {
                $generalMessage.text(message).css('background-color', type === 'success' ? '#10b981' : '#ef4444').css('color', 'white').css('padding', '1rem').show();
            }

            function clearErrors() {
                $('.error-message').text('').hide();
                $('input, select').removeClass('is-invalid');
                $generalMessage.hide();
            }

            $form.on('submit', function(e) {
                e.preventDefault();
                clearErrors();
                $btn.prop('disabled', true);
                $spinner.show();

                $.ajax({
                    url: 'create_user.php',
                    method: 'POST',
                    data: $form.serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({ icon: 'success', title: 'Success', text: response.message });
                            $form[0].reset();
                        } else {
                            if (response.errors) {
                                $.each(response.errors, function(key, message) {
                                    $(`#${key}-error`).text(message).show();
                                });
                            }
                            if (response.message) {
                                showMessage(response.message, 'error');
                            }
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred. Please try again.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        showMessage(errorMessage, 'error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                        $spinner.hide();
                    }
                });
            });
        });
    </script>
</body>
</html>