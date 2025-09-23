<?php
require_once 'config.php';

// TEMPORARY DEBUG CODE - Remove after fixing
echo "<!-- Debug: Session Status: " . session_status() . " -->";
echo "<!-- Debug: CSRF Token: " . ($_SESSION['csrf_token'] ?? 'NOT SET') . " -->";
echo "<!-- Debug: Session ID: " . session_id() . " -->";


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    <title>Sign In / Sign Up - FinLab ERP</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        /* --- Base Styles (Variables, Dark Mode, Reset, Body) --- */
        /* Using standard blue primary color */
        :root {
            --primary-color: #87CEEB; /* Sky Blue */
            --primary-dark: #60bdee; /* Slightly Darker Sky Blue */
            --primary-light: #aee5f4; /* Lighter Sky Blue */
            --primary-bg: rgba(135, 206, 235, 0.1); /* Sky Blue with 10% alpha */
            --success-color: #10b981; /* Emerald 500 */
            --warning-color: #f59e0b; /* Amber 500 */
            --danger-color: #ef4444; /* Red 500 */
            --info-color: #0ea5e9; /* Sky 500 */
            --text-color: #1e293b; /* Slate 800 */
            --text-light: #64748b; /* Slate 500 */
            --text-lighter: #94a3b8;/* Slate 400 */
            --bg-color: #f8fafc; /* Slate 50 */
            --card-bg: #ffffff;
            --border-color: #e2e8f0; /* Slate 200 */
            --modal-bg: rgba(0, 0, 0, 0.4);
            --border-radius: 12px;
            --border-radius-sm: 8px;
            --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            --box-shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.4);
            --transition: all 0.2s ease-in-out;
        }

        /* Dark Mode Styles */
        @media (prefers-color-scheme: dark) {
            :root {
                --text-color: #f1f5f9; --text-light: #94a3b8; --text-lighter: #64748b;
                --bg-color: #0f172a; --card-bg: #1e293b; --border-color: #334155;
                --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -2px rgba(0, 0, 0, 0.3);
                --box-shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.4), 0 4px 6px -4px rgba(0, 0, 0, 0.4);
            }
            .form-control, .custom-select select { background-color: #334155; border-color: #475569; color: var(--text-color); }
            .form-control:focus, .custom-select select:focus { background-color: #334155; border-color: var(--primary-color); box-shadow: 0 0 0 3px var(--primary-bg); }
            .form-control::placeholder { color: var(--text-lighter); }
            .btn-outline { color: var(--text-color); border-color: var(--border-color); }
            .btn-outline:hover:not(:disabled) { background-color: var(--primary-bg); border-color: var(--primary-color); color: var(--primary-light); }
            .auth-tab { color: var(--text-light); } .auth-tab:hover { color: var(--text-color); } .auth-tab.active { color: var(--primary-color); border-bottom-color: var(--primary-color); }
            .password-toggle { color: var(--text-light); } .password-toggle:hover { color: var(--text-color); }
            .step { background-color: var(--border-color); color: var(--text-light); } .step.active { background-color: var(--primary-color); color: var(--card-bg); } .step.completed { background-color: var(--success-color); color: var(--card-bg); } .step.completed::after { color: var(--card-bg); } .step::before { background-color: var(--border-color); } .step.active::before, .step.completed::before { background-color: inherit; } .step[data-step="2"].active::before { background-color: var(--success-color); } .step[data-step="2"].active.completed::before { background-color: var(--success-color); }
            .btn-outline .spinner { border-color: rgba(148, 163, 184, 0.3); border-top-color: var(--primary-color); }
            .auth-container { background-color: var(--card-bg); }
            .invalid-feedback { color: var(--danger-color); } .valid-feedback { color: var(--success-color); }
            #login-error, #signup-error { background-color: rgba(239, 68, 68, 0.15); border-color: rgba(239, 68, 68, 0.4); color: #fca5a5; }
            #general-message.success { background-color: rgba(16, 185, 129, 0.15); border-color: rgba(16, 185, 129, 0.4); color: #6ee7b7; } #general-message.error { background-color: rgba(239, 68, 68, 0.15); border-color: rgba(239, 68, 68, 0.4); color: #fca5a5; } #general-message.info { background-color: rgba(59, 130, 246, 0.15); border-color: rgba(59, 130, 246, 0.4); color: #93c5fd; }
            body.swal2-shown.swal2-height-auto { background-color: var(--bg-color) !important; } .swal2-popup { background: var(--card-bg) !important; color: var(--text-color) !important; } .swal2-title { color: var(--text-color) !important; } .swal2-html-container { color: var(--text-light) !important; } .swal2-success-circular-line-left, .swal2-success-circular-line-right, .swal2-success-fix { background-color: var(--card-bg) !important; } .swal2-success [class^=swal2-success-line] { background-color: var(--success-color) !important; } .swal2-close:hover { color: var(--primary-color) !important; }
        }

        /* Basic Reset */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-color); color: var(--text-color); line-height: 1.5; min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
        .auth-container { display: flex; width: 100%; max-width: 1200px; background-color: var(--card-bg); border-radius: var(--border-radius); box-shadow: var(--box-shadow-md); overflow: hidden; min-height: 700px; }
        .auth-illustration { flex: 1; background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 40px; color: white; position: relative; overflow: hidden; text-align: center; }
        .auth-illustration::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, rgba(255,255,255,0) 60%); transform: rotate(30deg); animation: subtleRotate 60s linear infinite; z-index: 0; }
        @keyframes subtleRotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .auth-illustration h2 { font-size: 2rem; margin-bottom: 1rem; position: relative; z-index: 1; font-weight: 700; }
        .auth-illustration p { font-size: 1.1rem; margin-bottom: 2rem; position: relative; z-index: 1; max-width: 400px; opacity: 0.9; }
        .auth-illustration img { max-width: 80%; height: auto; margin-bottom: 2rem; position: relative; z-index: 1; filter: drop-shadow(0 10px 10px rgba(0,0,0,0.2)); }
        .auth-form-container { flex: 1; padding: 50px 40px; display: flex; flex-direction: column; justify-content: center; }
        .auth-header { margin-bottom: 30px; text-align: center; }
        .auth-header h1 { font-size: 2.2rem; margin-bottom: 0.5rem; color: var(--primary-color); font-weight: 700; }
        .auth-header p { color: var(--text-light); font-size: 1rem; }
        .auth-tabs { display: flex; margin-bottom: 30px; border-bottom: 1px solid var(--border-color); }
        .auth-tab { padding: 12px 20px; cursor: pointer; font-weight: 600; color: var(--text-light); border-bottom: 3px solid transparent; transition: var(--transition); margin-bottom: -1px; text-align: center; flex-grow: 1; user-select: none; }
        .auth-tab:hover { color: var(--text-color); }
        .auth-tab.active { color: var(--primary-color); border-bottom-color: var(--primary-color); }
        .auth-form, .form-section { display: none; }
        .auth-form.active, .form-section.active { display: block; animation: fadeIn 0.4s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
        .form-group { margin-bottom: 20px; position: relative; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-color); font-size: 0.9rem; }
        .form-control { width: 100%; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: var(--border-radius-sm); font-size: 1rem; background-color: var(--bg-color); color: var(--text-color); transition: var(--transition); }
        .form-control[type="password"], .form-control[type="text"][id*="password"] { padding-right: 45px; }
        .form-control:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px var(--primary-bg); background-color: var(--card-bg); }
        .form-control::placeholder { color: var(--text-lighter); opacity: 0.8; }
        .form-control.is-invalid, .custom-select select.is-invalid { border-color: var(--danger-color); background-color: rgba(239, 68, 68, 0.05); }
        .form-control.is-invalid:focus, .custom-select select.is-invalid:focus { box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2); border-color: var(--danger-color); }
        .checkbox-group input[type="checkbox"].is-invalid + label { color: var(--danger-color); }
        .invalid-feedback { color: var(--danger-color); font-size: 0.85rem; margin-top: 6px; display: block; min-height: 1.2em; font-weight: 500; display: none; }
        .valid-feedback { color: var(--success-color); font-size: 0.85rem; margin-top: 6px; display: block; min-height: 1.2em; font-weight: 500; display: none; }
        #login-error, #signup-error { background-color: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: var(--danger-color); padding: 10px 15px; border-radius: var(--border-radius-sm); text-align: center; font-weight: 500; display: none; margin-bottom: 20px; }
        #general-message { padding: 10px 15px; border-radius: var(--border-radius-sm); text-align: center; font-weight: 500; display: none; margin-bottom: 20px; }
        #general-message.success { background-color: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); color: var(--success-color); }
        #general-message.error { background-color: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: var(--danger-color); }
        #general-message.info { background-color: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); color: var(--info-color); }
        .password-toggle { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-light); transition: color 0.2s ease; padding: 5px; z-index: 2; }
        .password-toggle:hover { color: var(--text-color); }
        .form-group > div[style*="position: relative;"] { position: relative; }
        .password-strength { height: 5px; background-color: var(--border-color); border-radius: 3px; margin-top: 8px; overflow: hidden; }
        .password-strength-bar { height: 100%; width: 0; background-color: transparent; transition: width 0.3s ease, background-color 0.3s ease; border-radius: 3px; }
        .password-strength-text { font-size: 0.8rem; color: var(--text-light); margin-top: 5px; height: 1.2em; font-weight: 500; text-align: right; }
        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 12px 24px; border-radius: var(--border-radius-sm); font-weight: 600; cursor: pointer; transition: var(--transition); border: none; width: 100%; font-size: 1rem; text-align: center; white-space: nowrap; user-select: none; }
        .btn i { margin-left: 8px; margin-right: -4px; font-size: 0.9em; }
        .btn i.fa-arrow-left { margin-right: 8px; margin-left: -4px; }
        .btn .spinner { margin-left: -4px; margin-right: 8px; }
        .btn-primary { background-color: var(--primary-color); color: white; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05); }
        .btn-primary:hover:not(:disabled) { background-color: var(--primary-dark); transform: translateY(-1px); box-shadow: var(--box-shadow); }
        .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; box-shadow: none; }
        .btn-outline { background-color: transparent; border: 1px solid var(--border-color); color: var(--text-color); }
        .btn-outline:hover:not(:disabled) { background-color: var(--primary-bg); border-color: var(--primary-color); color: var(--primary-dark); }
        .btn-outline:disabled { opacity: 0.6; cursor: not-allowed; background-color: transparent !important; border-color: var(--border-color); color: var(--text-light); }
        .form-footer { margin-top: 25px; text-align: center; font-size: 0.9rem; color: var(--text-light); }
        .form-footer a { color: var(--primary-color); text-decoration: none; font-weight: 600; }
        .form-footer a:hover { text-decoration: underline; color: var(--primary-dark); }
        .checkbox-group { display: flex; align-items: flex-start; margin-bottom: 20px; position: relative; }
        .checkbox-group input[type="checkbox"] { margin-right: 10px; height: 16px; width: 16px; accent-color: var(--primary-color); cursor: pointer; flex-shrink: 0; margin-top: 3px; }
        .checkbox-group label { font-size: 0.9rem; color: var(--text-color); cursor: pointer; user-select: none; margin-bottom: 0; line-height: 1.3; }
        .checkbox-group label a { color: var(--primary-color); text-decoration: none; font-weight: 500; }
        .checkbox-group label a:hover { text-decoration: underline; }
        .checkbox-group .invalid-feedback { position: absolute; bottom: -18px; left: 0; width: 100%; margin-top: 0; }
        .custom-select { position: relative; }
        .custom-select select { appearance: none; -webkit-appearance: none; -moz-appearance: none; width: 100%; padding: 12px 40px 12px 16px; border: 1px solid var(--border-color); border-radius: var(--border-radius-sm); font-size: 1rem; background-color: var(--bg-color); color: var(--text-color); cursor: pointer; transition: var(--transition); }
        .custom-select select:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px var(--primary-bg); background-color: var(--card-bg); }
        .custom-select::after { content: "\f078"; font-family: 'Font Awesome 6 Free'; font-weight: 900; font-size: 0.8rem; color: var(--text-light); position: absolute; right: 16px; top: 50%; transform: translateY(-50%); pointer-events: none; transition: transform 0.2s ease; }
        .form-navigation { display: flex; justify-content: space-between; margin-top: 30px; gap: 15px; }
        .form-navigation .btn { width: auto; padding: 10px 20px; flex-grow: 1; }
        .form-navigation .btn:first-child { flex-grow: 0; min-width: 100px; }
        .progress-steps { display: flex; justify-content: center; align-items: center; margin-bottom: 30px; padding: 0; list-style: none; position: relative; }
        .step { width: 35px; height: 35px; border-radius: 50%; background-color: var(--border-color); color: var(--text-light); display: flex; align-items: center; justify-content: center; margin: 0 25px; position: relative; font-weight: 600; transition: var(--transition); z-index: 1; flex-shrink: 0; }
        .step.active { background-color: var(--primary-color); color: white; box-shadow: 0 0 0 4px var(--primary-bg); }
        .step.completed span { display: none; }
        .step.completed::after { content: '\f00c'; font-family: 'Font Awesome 6 Free'; font-weight: 900; font-size: 1rem; color: white; position: absolute; line-height: 1; }
        .step::before { content: ""; position: absolute; height: 3px; background-color: var(--border-color); width: calc(50px + 35px); right: 100%; margin-right: -17.5px; top: 50%; transform: translateY(-50%); z-index: 0; transition: background-color 0.3s ease; }
        .step:first-child::before { display: none; }
        .step.active::before { background-color: var(--primary-color); }
        .step.completed::before { background-color: var(--success-color); }
        .step[data-step="2"].active::before { background-color: var(--success-color); }
        @media (max-width: 992px) { /* ... Tablet Styles ... */ }
        @media (max-width: 576px) { /* ... Mobile Styles ... */ }
        .spinner { display: inline-block; width: 1em; height: 1em; border: 3px solid rgba(255,255,255,0.3); border-radius: 50%; border-top-color: white; animation: spin 1s ease-in-out infinite; vertical-align: -0.15em; }
        .btn-outline .spinner { border-color: rgba(100, 116, 139, 0.3); border-top-color: var(--primary-color); }
        @keyframes spin { to { transform: rotate(360deg); } }
        .swal2-popup.swal2-toast { /* ... SweetAlert Toast Styles ... */ }

        /* Styles from the animated logo */
        .auth-illustration-logo {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            width: 100%;
            overflow: hidden;
            position: relative;
        }

        .auth-illustration-logo::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
            background-size: 20px 20px;
            animation: texturePulse 15s infinite alternate;
        }

        @keyframes texturePulse {
            from { opacity: 0.5; }
            to { opacity: 0.7; }
        }

        /* Logo Container */
        .logo-container {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 10;
        }

        .logo-svg {
            width: 150px;
            height: 150px;
            fill: transparent;
            opacity: 0;
            animation: fadeInLogo 0.5s forwards 5.5s;
        }

        /* The falling lines */
        .kinetic-line {
            position: absolute;
            width: 3px; /* Changed to be thicker */
            height: 100%;
            background-color: #ffffff; /* Changed to white */
            opacity: 0;
            transform: translateY(-100vh);
            animation: fallAndForm 4s ease-in-out forwards;
        }

        /* Create multiple lines with different delays */
        .line-1 { left: 40%; animation-delay: 0.2s; }
        .line-2 { left: 42%; animation-delay: 0.5s; }
        .line-3 { left: 45%; animation-delay: 0s; }
        .line-4 { left: 48%; animation-delay: 0.3s; }
        .line-5 { left: 50%; animation-delay: 0.7s; }
        .line-6 { left: 53%; animation-delay: 0.1s; }
        .line-7 { left: 55%; animation-delay: 0.4s; }
        .line-8 { left: 58%; animation-delay: 0.6s; }
        .line-9 { left: 60%; animation-delay: 0.9s; }

        /* The 'N' and box paths */
        .logo-path {
            stroke: #ffffff;
            stroke-width: 2px;
            stroke-dasharray: 200;
            stroke-dashoffset: 200;
            opacity: 0;
            animation: drawPath 1.5s forwards 5s, pulseGlow 2s infinite alternate 6s;
        }

        /* Text containers */
        .logo-text-container {
            opacity: 0;
            animation: textFadeIn 1s forwards 6s;
            text-align: center;
            margin-top: 0.5rem;
        }

        .logo-text {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: 0.5rem;
            color: #ffffff;
            text-shadow: 0 0 5px #ffffff;
        }

        .logo-subtext {
            font-size: 1rem;
            letter-spacing: 0.2rem;
            color: #e0e0e0;
            text-shadow: 0 0 2px #ffffff;
        }

        /* Media queries for responsiveness */
        @media (max-width: 768px) {
            .logo-svg {
                width: 100px;
                height: 100px;
            }
            .logo-text {
                font-size: 1.5rem;
                letter-spacing: 0.3rem;
            }
            .logo-subtext {
                font-size: 0.8rem;
                letter-spacing: 0.1rem;
            }
        }

        /* Keyframes for the Kinetic Reveal */
        @keyframes fallAndForm {
            0% { transform: translateY(-100vh); opacity: 0; }
            50% { transform: translateY(0); opacity: 0.5; }
            70% { opacity: 0.8; }
            100% { opacity: 0; transform: translateY(0); }
        }

        @keyframes fadeInLogo {
            to { opacity: 1; }
        }

        @keyframes drawPath {
            to { stroke-dashoffset: 0; opacity: 1; }
        }

        @keyframes pulseGlow {
            from { filter: drop-shadow(0 0 5px #ffffff); }
            to { filter: drop-shadow(0 0 15px #ffffff); }
        }

        @keyframes textFadeIn {
            to { opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-illustration">
            <div class="auth-illustration-logo">
                <!-- The falling lines that create the kinetic effect -->
                <div class="kinetic-line line-1"></div>
                <div class="kinetic-line line-2"></div>
                <div class="kinetic-line line-3"></div>
                <div class="kinetic-line line-4"></div>
                <div class="kinetic-line line-5"></div>
                <div class="kinetic-line line-6"></div>
                <div class="kinetic-line line-7"></div>
                <div class="kinetic-line line-8"></div>
                <div class="kinetic-line line-9"></div>
                <div class="logo-container">
                    <svg class="logo-svg" viewBox="0 0 100 100">
                        <!-- Box Path -->
                        <path class="logo-path" d="M 10 10 L 90 10 L 90 90 L 10 90 Z" />
                        <!-- Letter N Path -->
                        <path class="logo-path" d="M 35 75 L 35 25 L 65 75 L 65 25" />
                    </svg>
                    <div class="logo-text-container">
                        <div class="logo-text">NEXT</div>
                        <div class="logo-subtext">YOUR BUSINESS PARTNER</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="auth-form-container">
            <div class="auth-header">
                <h1>NEXT ERP</h1>
                <p id="auth-header-subtitle">Sign in to access your dashboard</p>
            </div>

            <div class="auth-tabs">
                <div class="auth-tab active" data-tab="login">Sign In</div>
                <div class="auth-tab" data-tab="signup">Sign Up</div>
            </div>

            <div id="general-message" style="display: none;"></div>

            <form id="login-form" class="auth-form active" novalidate>
                <div id="login-error" class="invalid-feedback" style="display: none; margin-bottom: 20px;"></div>
                <div class="form-group">
                    <label for="login-email" class="form-label">Email Address</label>
                    <input type="email" id="login-email" name="login_email" class="form-control" placeholder="Enter your email" required autocomplete="email">
                    <div id="login-email-error" class="invalid-feedback"></div>
                </div>
                <div class="form-group">
                    <label for="login-password" class="form-label">Password</label>
                    <div style="position: relative;">
                        <input type="password" id="login-password" name="login_password" class="form-control" placeholder="Enter your password" required autocomplete="current-password">
                        <span class="password-toggle" id="login-password-toggle" title="Toggle password visibility"><i class="fas fa-eye"></i></span>
                    </div>
                    <div id="login-password-error" class="invalid-feedback"></div>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" id="remember-me" name="remember_me">
                    <label for="remember-me">Remember me</label>
                </div>
                <button type="submit" class="btn btn-primary" id="login-btn">
                    <span class="spinner" id="login-spinner" style="display: none;"></span>
                    Sign In
                </button>
                <div class="form-footer">
                    <a href="#" id="forgot-password">Forgot password?</a>
                </div>
            </form>

            <form id="signup-form" class="auth-form" novalidate>
                <div class="progress-steps">
                    <div class="step active" data-step="1"><span>1</span></div>
                    <div class="step" data-step="2"><span>2</span></div>
                </div>
                <div id="signup-error" class="invalid-feedback" style="display: none; margin-bottom: 20px;"></div>

                <div id="signup-step-1" class="form-section active" data-step="1">
                    <div class="form-group">
                        <label for="signup-first-name" class="form-label">First Name</label>
                        <input type="text" id="signup-first-name" name="signup-first-name" class="form-control" placeholder="Enter your first name" required autocomplete="given-name">
                        <div id="signup-first-name-error" class="invalid-feedback"></div>
                    </div>
                    <div class="form-group">
                        <label for="signup-last-name" class="form-label">Last Name</label>
                        <input type="text" id="signup-last-name" name="signup-last-name" class="form-control" placeholder="Enter your last name" required autocomplete="family-name">
                        <div id="signup-last-name-error" class="invalid-feedback"></div>
                    </div>
                    <div class="form-group">
                        <label for="signup-email" class="form-label">Email Address</label>
                        <input type="email" id="signup-email" name="signup_email" class="form-control" placeholder="Enter your email" required autocomplete="email">
                        <div id="signup-email-error" class="invalid-feedback"></div>
                    </div>
                    <div class="form-group">
                        <label for="signup-country" class="form-label">Country</label>
                        <div class="custom-select">
                            <select id="signup-country" name="signup_country" required autocomplete="country-name">
                                <option value="" disabled selected>Select your country</option>
                            </select>
                        </div>
                        <div id="signup-country-error" class="invalid-feedback"></div>
                    </div>
                    <div class="form-group">
                        <label for="signup-password" class="form-label">Password</label>
                        <div style="position: relative;">
                            <input type="password" id="signup-password" name="signup_password" class="form-control" placeholder="Create a password" required autocomplete="new-password" aria-describedby="password-strength-text signup-password-error">
                            <span class="password-toggle" id="signup-password-toggle" title="Toggle password visibility"><i class="fas fa-eye"></i></span>
                        </div>
                        <div class="password-strength"><div class="password-strength-bar" id="password-strength-bar"></div></div>
                        <div class="password-strength-text" id="password-strength-text"></div>
                        <div id="signup-password-error" class="invalid-feedback">Must be 8+ chars, with uppercase, lowercase, number, and special character.</div>
                    </div>
                    <div class="form-group">
                        <label for="signup-confirm-password" class="form-label">Confirm Password</label>
                        <div style="position: relative;">
                            <input type="password" id="signup-confirm-password" name="signup_confirm_password" class="form-control" placeholder="Confirm your password" required autocomplete="new-password">
                            <span class="password-toggle" id="confirm-password-toggle" title="Toggle password visibility"><i class="fas fa-eye"></i></span>
                        </div>
                        <div id="signup-confirm-password-feedback" class="invalid-feedback"></div>
                    </div>
                    <div class="form-navigation">
                        <button type="button" class="btn btn-outline" id="cancel-signup">Cancel</button>
                        <button type="button" class="btn btn-primary" id="next-step-1"> Continue <i class="fas fa-arrow-right"></i> </button>
                    </div>
                </div>

                <div id="signup-step-2" class="form-section" data-step="2">
                    <div class="form-group">
                        <label for="signup-business-name" class="form-label">Business Name</label>
                        <input type="text" id="signup-business-name" name="signup_business_name" class="form-control" placeholder="Enter your business name" required autocomplete="organization">
                        <div id="signup-business-name-error" class="invalid-feedback"></div>
                    </div>
                    <div class="form-group">
                        <label for="signup-business-type" class="form-label">Business Type</label>
                        <div class="custom-select">
                            <select id="signup-business-type" name="signup_business_type" required>
                                <option value="" disabled selected>Select business type</option>
                                <option value="sole">Sole Proprietorship</option>
                                <option value="partnership">Partnership</option>
                                <option value="llc">LLC</option>
                                <option value="corporation">Corporation</option>
                                <option value="nonprofit">Nonprofit</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div id="signup-business-type-error" class="invalid-feedback"></div>
                    </div>
                    <div class="form-group">
                        <label for="signup-business-reg" class="form-label">Business Registration Number <span id="reg-num-optional-text">(Optional)</span></label>
                        <input type="text" id="signup-business-reg" name="signup_business_reg" class="form-control" placeholder="Enter registration number">
                        <div id="signup-business-reg-error" class="invalid-feedback"></div>
                    </div>
                    <div class="form-group">
                        <label for="signup-business-country" class="form-label">Business Country</label>
                        <div class="custom-select">
                            <select id="signup-business-country" name="signup_business_country" required autocomplete="country-name">
                                <option value="" disabled selected>Select business country</option>
                            </select>
                        </div>
                        <div id="signup-business-country-error" class="invalid-feedback"></div>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" id="signup-terms" name="signup_terms" value="agreed" required>
                        <label for="signup-terms">I agree to the <a href="#" target="_blank" title="Opens Terms of Service in new tab">Terms of Service</a> and <a href="#" target="_blank" title="Opens Privacy Policy in new tab">Privacy Policy</a></label>
                        <div id="signup-terms-error" class="invalid-feedback"></div>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" id="signup-newsletter" name="signup_newsletter" value="subscribed">
                        <label for="signup-newsletter">Subscribe to our newsletter</label>
                    </div>
                    <div class="form-navigation">
                        <button type="button" class="btn btn-outline" id="prev-step-2"> <i class="fas fa-arrow-left"></i> Back </button>
                        <button type="submit" class="btn btn-primary" id="signup-btn"> <span class="spinner" id="signup-spinner" style="display: none;"></span> Complete Registration </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

<script>
$(document).ready(function() {
    
    // CSRF Token Generation (NEW)
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    if (!csrfToken) {
        console.warn("CSRF token not found. Security features may not work as expected.");
    }

    // --- Configuration ---
    const MIN_PASSWORD_LENGTH = 8;
    const DASHBOARD_URL = 'dashboard.php';

    // --- UI Elements ---
    const $loginForm = $('#login-form');
    const $signupForm = $('#signup-form');
    const $signupSteps = $signupForm.find('.form-section');
    const $stepIndicators = $signupForm.find('.step');
    const $nextBtnStep1 = $signupForm.find('#next-step-1');
    const $prevBtnStep2 = $signupForm.find('#prev-step-2');
    const $signupSubmitBtn = $signupForm.find('#signup-btn');
    const $cancelSignupBtn = $signupForm.find('#cancel-signup');
    const $passwordInput = $('#signup-password');
    const $confirmPasswordInput = $('#signup-confirm-password');
    const $strengthBar = $('#password-strength-bar');
    const $strengthText = $('#password-strength-text');
    const $confirmPasswordFeedback = $('#signup-confirm-password-feedback');
    const $businessRegInput = $('#signup-business-reg');
    const $businessCountrySelect = $('#signup-business-country');
    const $regNumOptionalText = $('#reg-num-optional-text');

    // --- State ---
    let currentSignupStep = 1;
    const totalSignupSteps = $signupSteps.length;

    // --- Helper Functions ---
    function showSpinner(buttonId, spinnerId) { $(`#${spinnerId}`).css('display', 'inline-block'); $(`#${buttonId}`).prop('disabled', true); }
    function hideSpinner(buttonId, spinnerId) { $(`#${spinnerId}`).hide(); $(`#${buttonId}`).prop('disabled', false); }
    function displayFormError(formType, message) { const errorDiv = $(`#${formType}-error`); errorDiv.text(message).show(); $('#general-message').hide(); }
    function displayGeneralMessage(message, type = 'success') { /* ... */ }
    function validateEmail(email) { const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/; return re.test(String(email).toLowerCase()); }
    function showError(fieldId, message) { const field = $(`#${fieldId}`); const errorDiv = $(`#${fieldId}-error, #${fieldId}-feedback`); if(errorDiv.length){ errorDiv.text(message).removeClass('valid-feedback').addClass('invalid-feedback').show(); } field.addClass('is-invalid'); if (field.is(':checkbox')) { field.addClass('is-invalid'); } }
    function clearError(fieldId) { const field = $(`#${fieldId}`); const errorDiv = $(`#${fieldId}-error, #${fieldId}-feedback`); if(errorDiv.length){ errorDiv.hide().text(''); } field.removeClass('is-invalid'); if (field.is(':checkbox')) { field.removeClass('is-invalid'); } }
    function clearAllErrors(formId) { const $form = $(`#${formId}`); $form.find('.invalid-feedback, .valid-feedback').hide().text(''); $form.find('.form-control, .custom-select select, .checkbox-group input[type="checkbox"]').removeClass('is-invalid'); const formType = formId === 'login-form' ? 'login' : 'signup'; $(`#${formType}-error`).hide(); }
    function escapeHtml(unsafe) { if (typeof unsafe !== 'string') return ''; return unsafe.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;"); }
    function validatePasswordComplexity(password) { if (!password) return { valid: false, missing: ['8+ chars', 'lowercase', 'uppercase', 'number', 'special char'] }; let missing = []; const hasMinLength = password.length >= MIN_PASSWORD_LENGTH; const hasLowercase = /[a-z]/.test(password); const hasUppercase = /[A-Z]/.test(password); const hasNumber = /\d/.test(password); const hasSpecial = /[ `!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~]/.test(password); if (!hasMinLength) missing.push('8+ chars'); if (!hasLowercase) missing.push('lowercase'); if (!hasUppercase) missing.push('uppercase'); if (!hasNumber) missing.push('number'); if (!hasSpecial) missing.push('special char'); const isValid = hasMinLength && hasLowercase && hasUppercase && hasNumber && hasSpecial; return { valid: isValid, missing: missing }; }

    // Populate Country Dropdowns
    const countries = [ { code: "US", name: "United States" }, { code: "UK", name: "United Kingdom" }, { code: "CA", name: "Canada" }, { code: "AU", name: "Australia" }, { code: "PK", name: "Pakistan" }, { code: "IN", name: "India" }, { code: "DE", name: "Germany" }, { code: "FR", name: "France" }, { code: "JP", name: "Japan" }, { code: "BR", name: "Brazil" }, ];
    const countrySelects = $('#signup-country, #signup-business-country');
    countries.sort((a, b) => a.name.localeCompare(b.name));
    countries.forEach(country => { countrySelects.append($('<option>', { value: country.code, text: country.name })); });

    // --- Event Handlers ---

    // Tab switching
    $('.auth-tab').on('click', function() {
        const $this = $(this); if ($this.hasClass('active')) return;
        const tab = $this.data('tab');
        $('.auth-tab').removeClass('active'); $this.addClass('active');
        $('.auth-form').removeClass('active'); // Remove from all
        $(`#${tab}-form`).addClass('active'); // Add to target
        $('#auth-header-subtitle').text(tab === 'login' ? 'Sign in to access your dashboard' : 'Create a new account');
        clearAllErrors('login-form'); clearAllErrors('signup-form');
        $('#general-message').hide();
        if (tab === 'signup') { currentSignupStep = 1; showSignupStep(1); }
    });

    // Password visibility toggle
    $('.auth-form-container').on('click', '.password-toggle', function() {
        const $input = $(this).closest('div').find('input');
        const $icon = $(this).find('i');
        if ($input.length) {
            const isPassword = $input.attr('type') === 'password';
            $input.attr('type', isPassword ? 'text' : 'password');
            $icon.toggleClass('fa-eye fa-eye-slash');
            $(this).attr('title', isPassword ? 'Hide password' : 'Show password');
        }
    });

    // Password strength meter
    $passwordInput.on('input', function() {
        const password = $(this).val();
        clearError('signup-password');
        $confirmPasswordFeedback.text('').hide();
        $confirmPasswordInput.removeClass('is-invalid');
        if (password.length === 0) { $strengthBar.css('width', '0%').css('background-color', 'transparent'); $strengthText.text(''); return; }
        const complexity = validatePasswordComplexity(password);
        let strengthScore = 0;
        if (password.length >= MIN_PASSWORD_LENGTH) strengthScore++; if (/[a-z]/.test(password)) strengthScore++; if (/[A-Z]/.test(password)) strengthScore++; if (/\d/.test(password)) strengthScore++; if (/[ `!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~]/.test(password)) strengthScore++; if (password.length > 11) strengthScore++;
        let calculatedStrengthLevel = 0;
        if (!complexity.valid) { if (strengthScore <= 1) calculatedStrengthLevel = 1; else if (strengthScore <= 3) calculatedStrengthLevel = 2; else calculatedStrengthLevel = 3; }
        else { calculatedStrengthLevel = (strengthScore > 5) ? 5 : 4; }
        let widthPercent = 0; let barColor = 'transparent'; let strengthLabel = '';
        switch(calculatedStrengthLevel) {
            case 1: widthPercent = 20; barColor = 'var(--danger-color)'; strengthLabel = 'Very Weak'; break;
            case 2: widthPercent = 40; barColor = 'var(--warning-color)'; strengthLabel = 'Weak'; break;
            case 3: widthPercent = 60; barColor = 'var(--info-color)'; strengthLabel = 'Moderate'; break;
            case 4: widthPercent = 80; barColor = 'var(--primary-color)'; strengthLabel = 'Strong'; break;
            case 5: default: widthPercent = 100; barColor = 'var(--success-color)'; strengthLabel = 'Very Strong'; break;
        }
        $strengthBar.css('width', `${widthPercent}%`).css('background-color', barColor);
        let hintText = !complexity.valid ? ` (Needs: ${complexity.missing.slice(0, 2).join(', ')}${complexity.missing.length > 2 ? '...' : ''})` : '';
        $strengthText.text(strengthLabel + hintText).css('color', barColor);
    });

    // Password Match Check
    $confirmPasswordInput.on('input', function() {
        const password = $passwordInput.val();
        const confirmPassword = $(this).val();
        clearError('signup-confirm-password');
        $confirmPasswordFeedback.text('').hide();
        if (confirmPassword.length > 0 && password.length > 0) {
            if (password === confirmPassword) {
                if (validatePasswordComplexity(password).valid) { $confirmPasswordFeedback.text('Passwords match!').removeClass('invalid-feedback').addClass('valid-feedback').show(); $(this).removeClass('is-invalid'); }
                else { $confirmPasswordFeedback.text('').hide(); }
            } else { $confirmPasswordFeedback.text('Passwords do not match').removeClass('valid-feedback').addClass('invalid-feedback').show(); }
        }
    });
    $passwordInput.on('input', function() {
        const confirmPassword = $confirmPasswordInput.val();
        if (confirmPassword.length > 0) {
            if ($(this).val() === confirmPassword && validatePasswordComplexity($(this).val()).valid) { $confirmPasswordFeedback.text('Passwords match!').removeClass('invalid-feedback').addClass('valid-feedback').show(); $confirmPasswordInput.removeClass('is-invalid'); }
            else { if ($confirmPasswordFeedback.hasClass('invalid-feedback')) { $confirmPasswordFeedback.text('Passwords do not match'); } else { $confirmPasswordFeedback.text('').hide(); } }
        } else { $confirmPasswordFeedback.text('').hide(); $confirmPasswordInput.removeClass('is-invalid'); }
        clearError('signup-confirm-password');
    });


    // --- Login Form Submission (AJAX) ---
    $loginForm.on('submit', function(e) {
        e.preventDefault();
        clearAllErrors('login-form'); $('#general-message').hide();
        let isValid = true;
        const email = $('#login-email').val().trim(); const password = $('#login-password').val();
        if (!email) { showError('login-email', 'Email is required'); isValid = false; }
        else if (!validateEmail(email)) { showError('login-email', 'Please enter a valid email address'); isValid = false; }
        if (!password) { showError('login-password', 'Password is required'); isValid = false; }

        if (isValid) {
            showSpinner('login-btn', 'login-spinner');
            $.ajax({
                 url: 'login.php', method: 'POST', data: $(this).serialize(), dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Signed in successfully!', showConfirmButton: false, timer: 2000, timerProgressBar: true, customClass: { popup: 'swal2-toast' }
                        }).then(() => { setTimeout(() => { window.location.href = DASHBOARD_URL; }, 50); });
                        if ($('#remember-me').is(':checked')) { localStorage.setItem('erp_remember_email', email); } else { localStorage.removeItem('erp_remember_email'); }
                    } else { displayFormError('login', response.message || 'No account found with that email, or incorrect password.'); }
                },
                error: function() { displayFormError('login', 'An error occurred connecting to the server.'); },
                complete: function() { hideSpinner('login-btn', 'login-spinner'); }
            });
        } else { hideSpinner('login-btn', 'login-spinner'); }
    }); // End login form submit

    // --- Signup Form Step Navigation ---
    function showSignupStep(step) {
        currentSignupStep = step;
        $signupSteps.removeClass('active').hide();
        $(`#signup-step-${step}`).addClass('active').show();
        $stepIndicators.removeClass('active completed');
        $stepIndicators.each(function() { const stepNum = parseInt($(this).data('step')); if (stepNum < currentSignupStep) { $(this).addClass('completed'); } else if (stepNum === currentSignupStep) { $(this).addClass('active'); } });
        $prevBtnStep2.prop('disabled', step === 1);
        $nextBtnStep1.toggle(step === 1);
        $signupSubmitBtn.toggle(step === totalSignupSteps);
    }
    function validateSignupStep1() {
        let isValid = true; let firstErrorField = null;
        clearAllErrors('signup-form'); $('#general-message').hide(); // Clear all errors for step 1 validation start
        const fields = ['signup-first-name', 'signup-last-name', 'signup-email', 'signup-country', 'signup-password', 'signup-confirm-password'];
        fields.forEach(id => {
            const input = $(`#${id}`); const value = input.val() ? (input.is('select') ? input.val() : input.val().trim()) : ''; let fieldError = false; let errMsg = '';
            if (!value && input.prop('required')) { // Check required attribute too
                 errMsg = 'This field is required'; fieldError = true;
            } else if (value) { // Only run specific validations if there's a value
                if (id === 'signup-email' && !validateEmail(value)) { errMsg = 'Invalid email format'; fieldError = true; }
                if (id === 'signup-password') { const pw = input.val(); const complexityResult = validatePasswordComplexity(pw); if (!complexityResult.valid) { errMsg = 'Requires: ' + complexityResult.missing.join(', '); fieldError = true; } }
                if (id === 'signup-confirm-password') { const cpw = input.val(); if (!cpw) { errMsg = 'Please confirm password'; fieldError = true; } else if (cpw !== $('#signup-password').val()) { errMsg = 'Passwords do not match'; fieldError = true; } }
            }
            if(fieldError) { showError(id, errMsg); isValid = false; if (!firstErrorField) firstErrorField = input; }
        });
        // Explicitly check password match again if both fields have values and passed individual checks
        if (isValid && $passwordInput.val() && $confirmPasswordInput.val() && $passwordInput.val() !== $confirmPasswordInput.val()) {
             showError('signup-confirm-password', 'Passwords do not match'); isValid = false; if (!firstErrorField) firstErrorField = $confirmPasswordInput;
        }

        if (!isValid && firstErrorField) { $('.auth-form-container').animate({ scrollTop: firstErrorField.offset().top - $('.auth-form-container').offset().top + $('.auth-form-container').scrollTop() - 20 }, 300); }
        return isValid;
    }
    function validateSignupStep2() {
        let isValid = true; let firstErrorField = null;
        // Don't clear errors from step 1 here, only step 2 errors
        $('#signup-step-2 .invalid-feedback').hide().text('');
        $('#signup-step-2 .form-control, #signup-step-2 .custom-select select, #signup-step-2 .checkbox-group input[type="checkbox"]').removeClass('is-invalid');
        $('#general-message').hide();

        const fields = ['signup-business-name', 'signup-business-type', 'signup-business-country', 'signup-terms'];
        const businessCountry = $businessCountrySelect.val();
        const isRegRequired = (businessCountry === 'PK');

        if (isRegRequired) { fields.push('signup-business-reg'); $businessRegInput.prop('required', true); $regNumOptionalText.hide(); }
        else { $businessRegInput.prop('required', false); $regNumOptionalText.show(); clearError('signup-business-reg'); } // Clear error if no longer required

        fields.forEach(id => {
            const input = $(`#${id}`); let fieldError = false; let errMsg = '';
            if (input.is(':checkbox')) { if (!input.is(':checked') && input.prop('required')) { errMsg = 'You must accept the terms'; fieldError = true; } }
            else {
                const value = input.val() ? input.val().trim() : '';
                const required = input.prop('required'); // Check the required attribute directly
                if (!value && required) { errMsg = 'This field is required'; fieldError = true; }
            }
            if(fieldError) { showError(id, errMsg); isValid = false; if (!firstErrorField) firstErrorField = input; }
        });

        if (!isValid && firstErrorField) { $('.auth-form-container').animate({ scrollTop: firstErrorField.offset().top - $('.auth-form-container').offset().top + $('.auth-form-container').scrollTop() - 20 }, 300); }
        return isValid;
    }

    $nextBtnStep1.on('click', function() { if (validateSignupStep1()) { showSignupStep(2); } });
    $prevBtnStep2.on('click', function() { showSignupStep(1); });
    if ($cancelSignupBtn.length) { $cancelSignupBtn.on('click', function() { $('.auth-tab[data-tab="login"]').click(); }); } else { console.error("Cancel signup button (#cancel-signup) not found!"); }
    $businessCountrySelect.on('change', function() { const isRegRequired = ($(this).val() === 'PK'); $regNumOptionalText.toggle(!isRegRequired); if (!isRegRequired) { clearError('signup-business-reg'); } });


    // --- Signup Form Final Submission (AJAX) --- UPDATED ---
    $signupForm.on('submit', function(e) {
        e.preventDefault();
        console.log("Signup form submit event triggered.");

        // Re-validate both steps on final submit
        const step1Valid = validateSignupStep1();
        const step2Valid = validateSignupStep2();
        console.log(`Final Validation Results - Step 1: ${step1Valid}, Step 2: ${step2Valid}`);

        if (step1Valid && step2Valid) {
            console.log("Signup validation passed, sending AJAX...");
            showSpinner('signup-btn', 'signup-spinner');

            // Get first/last name (needed for the submitData object)
            const firstName = $('#signup-first-name').val().trim();
            const lastName = $('#signup-last-name').val().trim();
            // const fullName = `${firstName} ${lastName}`.trim(); // Optional: if PHP uses a combined name field

            // *** THE FIX IS HERE ***
            // Create the data object to send
            const submitData = {
                // ADDED the keys expected by signup.php for first and last name
                'signup-first-name': firstName,
                'signup-last-name': lastName,

                // Keep the rest of the fields
                // signup_name: fullName, // Optional: Keep if your PHP uses this for the 'name' column
                signup_email: $('#signup-email').val().trim(),
                signup_password: $('#signup-password').val(),
                signup_confirm_password: $('#signup-confirm-password').val(), // Make sure PHP checks this key
                signup_terms: $('#signup-terms').is(':checked') ? 'agreed' : '',
                signup_country: $('#signup-country').val(), // Make sure PHP checks this key
                signup_business_name: $('#signup-business-name').val().trim(), // Make sure PHP checks this key
                signup_business_type: $('#signup-business-type').val(), // Make sure PHP checks this key
                signup_business_reg: $('#signup-business-reg').val().trim(), // Make sure PHP checks this key
                signup_business_country: $('#signup-business-country').val(), // Make sure PHP checks this key
                signup_newsletter: $('#signup-newsletter').is(':checked') ? 1 : 0 // Send 1 or 0 as PHP expects
            };
            // *** END OF FIX ***

            console.log("Data being sent:", submitData); // Verify in browser console

            $.ajax({
                url: 'signup.php',
                method: 'POST',
                data: submitData, // Sending the corrected data object
                dataType: 'json', // Expecting JSON response from PHP
                success: function(response) {
                    console.log("Signup Response:", response);
                    if (response.success) {
                        Swal.fire({
                            toast: true, position: 'top-end', icon: 'success', title: 'Registered successfully!', showConfirmButton: false, timer: 2000, timerProgressBar: true, customClass: { popup: 'swal2-toast' }
                        }).then(() => {
                            console.log("Signup successful, redirecting...");
                            setTimeout(() => { window.location.href = DASHBOARD_URL; }, 50);
                        });
                    } else {
                        console.log("Signup failed (server response):", response.message || response.errors);
                        // Clear previous general errors
                        $('#signup-error').empty().hide();
                        let firstErrorFieldElement = null;

                        if (response.errors && typeof response.errors === 'object') {
                             $.each(response.errors, function(key, message) {
                                 // Try to map PHP key (e.g., signup-first-name) to HTML field ID
                                 let fieldId = key; // Assume key directly maps for now
                                 // Special mapping if needed (e.g., if PHP returned 'email' but ID is 'signup-email')
                                 // if (key === 'some_php_key') fieldId = 'corresponding-html-id';

                                 const $errorField = $('#' + fieldId);
                                 showError(fieldId, message); // Use existing showError function

                                 if($errorField.length && !firstErrorFieldElement) {
                                     firstErrorFieldElement = $errorField; // Keep track of the first field with an error
                                 } else if (!$errorField.length) {
                                     // If field ID not found, show error in general area
                                     $('#signup-error').append(`<div>${escapeHtml(key)}: ${escapeHtml(message)}</div>`).show();
                                 }
                             });

                             // Scroll to the first error and switch step if necessary
                             if (firstErrorFieldElement) {
                                 const errorStep = firstErrorFieldElement.closest('.form-section').data('step');
                                 if (errorStep && errorStep < currentSignupStep) {
                                     showSignupStep(errorStep); // Go back to the step with the error
                                 }
                                 // Scroll the form container to the error field
                                 $('.auth-form-container').animate({
                                      scrollTop: firstErrorFieldElement.offset().top - $('.auth-form-container').offset().top + $('.auth-form-container').scrollTop() - 20
                                 }, 300);
                             }

                        } else {
                            // General error message if response.errors is not a detailed object
                            displayFormError('signup', response.message || 'An unknown error occurred during registration.');
                        }
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("Signup AJAX Error:", textStatus, errorThrown, jqXHR.responseText);
                    displayFormError('signup', 'An error occurred connecting to the server. Please check the console and server logs.');
                },
                complete: function() {
                    hideSpinner('signup-btn', 'signup-spinner');
                }
            }); // End AJAX call

        } else {
            hideSpinner('signup-btn', 'signup-spinner'); // Ensure spinner hidden if validation fails
            // If validation failed, switch to the first step that has errors
            if (!step1Valid) {
                 if (currentSignupStep !== 1) showSignupStep(1);
                 // Find first error in step 1 and scroll to it
                 const firstStep1Error = $('#signup-step-1 .is-invalid').first();
                 if (firstStep1Error.length) {
                     $('.auth-form-container').animate({ scrollTop: firstStep1Error.offset().top - $('.auth-form-container').offset().top + $('.auth-form-container').scrollTop() - 20 }, 300);
                 }
            } else if (!step2Valid) {
                if (currentSignupStep !== 2) showSignupStep(2);
                 // Find first error in step 2 and scroll to it
                 const firstStep2Error = $('#signup-step-2 .is-invalid').first();
                 if (firstStep2Error.length) {
                      $('.auth-form-container').animate({ scrollTop: firstStep2Error.offset().top - $('.auth-form-container').offset().top + $('.auth-form-container').scrollTop() - 20 }, 300);
                 }
            }
            console.log("Final signup validation failed. Check form fields for errors.");
        }
    }); // End signup form submit


    // --- Forgot Password ---
    $('#forgot-password').on('click', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Forgot Password',
            input: 'email',
            inputPlaceholder: 'Enter your email address',
            inputAttributes: { autocapitalize: 'off', autocorrect: 'off' },
            showCancelButton: true,
            confirmButtonText: 'Reset Password',
            showLoaderOnConfirm: true,
            preConfirm: (email) => {
                if (!validateEmail(email)) {
                     Swal.showValidationMessage('Please enter a valid email address');
                     return false;
                }
                // Replace with your actual password reset AJAX call
                return new Promise((resolve) => {
                    setTimeout(() => {
                         // Simulate AJAX call
                         console.log("Simulating password reset for:", email);
                         // Assuming success for now
                         resolve({ success: true, message: 'Password reset link sent (simulation).' });
                         // On actual error: resolve({ success: false, message: 'Error sending reset link.' });
                         // On user not found: resolve({ success: false, message: 'Email not found.' });
                    }, 1500);
                });
            },
             allowOutsideClick: () => !Swal.isLoading()
          }).then((result) => {
              if (result.isConfirmed && result.value) {
                  if (result.value.success) {
                      Swal.fire({ icon: 'success', title: 'Check your email!', text: result.value.message });
                  } else {
                      Swal.fire({ icon: 'error', title: 'Oops...', text: result.value.message || 'Could not send reset link.' });
                  }
              }
          });
    });

    // --- Initial Setup ---
    // Check for remembered email
    const rememberedEmail = localStorage.getItem('erp_remember_email');
    if (rememberedEmail) {
         $('#login-email').val(rememberedEmail);
         $('#remember-me').prop('checked', true);
    }
    // Start on the login tab
    $('.auth-tab[data-tab="login"]').trigger('click');

});
</script>

</body>
</html>
