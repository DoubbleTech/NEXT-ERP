<?php
/**
 * payroll.php - Web application page for managing payroll.
 * Collects employee data and attendance to calculate and generate payrolls.
 */

// Ensure session is started and user is authenticated
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?error=login_required');
    exit;
}

// Include shared config and functions
require_once 'config.php';
require_once 'functions.php'; // Assuming sanitize_input() is here

// Set Navbar variables for this page
$pageTitle = "Payroll Management";
$pageIconClass = "wallet"; // Lucide icon for finance/money

// Define company logo URL (You might get this from a config file or database)
// !!! IMPORTANT: CHANGE THIS TO YOUR ACTUAL LOGO URL !!!
$companyLogoUrl = 'path/to/your/company_logo.png';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinLab - <?php echo htmlspecialchars($pageTitle); ?></title>

    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.1/dist/sweetalert2.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.1/dist/sweetalert2.min.css">

    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css">
    <style>
        /* Base styles for the entire page */
        :root {
            --primary-color: #4A90E2; /* A vibrant blue */
            --primary-dark: #357ABD;
            --primary-light: #7DB8F5;
            --primary-bg: rgba(74, 144, 226, 0.1); /* Light transparent primary for backgrounds */
            --primary-bg-light: rgba(74, 144, 226, 0.05);

            --success-color: #5cb85c;
            --success-dark: #449d44;
            --info-color: #5bc0de;
            --info-dark: #31b0d5;
            --warning-color: #f0ad4e;
            --warning-dark: #ec971f;
            --danger-color: #d9534f;
            --danger-dark: #c9302c;

            --text-color: #333d47; /* Darker text for readability */
            --text-light: #6e7a87; /* Lighter text for secondary info */
            --text-lighter: #a0a8b1; /* Even lighter for muted text */

            --background-color: #f8f9fa; /* Light background for page */
            --background-color-light: #ffffff; /* Lighter background, e.g., for form fields in modals */
            --background-color-lighter: #f0f2f5; /* Even lighter, for hover effects */
            --background-color-light-alt: #e9ecef; /* Alt light background */

            --card-bg: #ffffff; /* Card background */
            --border-color: #e0e6ed; /* Subtle borders */
            --border-color-light: #f0f2f5;

            --border-radius: 8px;
            --border-radius-sm: 4px;

            --box-shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
            --box-shadow-md: 0 4px 8px rgba(0,0,0,0.08);
            --box-shadow-lg: 0 8px 16px rgba(0,0,0,0.1);
        }

        /* Dark mode adjustments */
        @media (prefers-color-scheme: dark) {
            :root {
                --primary-color: #6a9ee8; /* Lighter blue for dark mode */
                --primary-dark: #4a80d4;
                --primary-light: #8cbcfc;
                --primary-bg: rgba(106, 158, 232, 0.15);
                --primary-bg-light: rgba(106, 158, 232, 0.08);

                --text-color: #e0e6ed; /* Lighter text for dark background */
                --text-light: #a0a8b1;
                --text-lighter: #6e7a87;

                --background-color: #1a202c; /* Dark background for page */
                --background-color-light: #2d3748; /* Darker background, e.g., for form fields */
                --background-color-lighter: #1f2937; /* Even darker, for hover effects */
                --background-color-light-alt: #273449;

                --card-bg: #2d3748; /* Dark card background */
                --border-color: #4a5568; /* Darker subtle borders */
                --border-color-light: #2d3748; /* Consistent with card bg */

                --box-shadow-sm: 0 1px 3px rgba(0,0,0,0.2);
                --box-shadow-md: 0 4px 8px rgba(0,0,0,0.25);
                --box-shadow-lg: 0 8px 16px rgba(0,0,0,0.3);
            }
        }

        /* General page layout */
        .page-content {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: 2rem;
            text-align: center;
        }
        .page-header h1 {
            font-size: 2.5rem;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        .page-header h1 i {
            width: 40px;
            height: 40px;
            color: var(--primary-color);
        }
        .page-subtitle {
            font-size: 1.1rem;
            color: var(--text-light);
            max-width: 600px;
            margin: 0 auto;
        }

        /* New Layout for Payroll Page */
        .payroll-main-layout {
            display: flex;
            gap: 2rem; /* Increased space between sidebar and content */
            align-items: flex-start;
        }

        .payroll-sidebar {
            flex: 0 0 320px; /* Slightly wider sidebar */
            display: flex;
            flex-direction: column;
            gap: 2rem; /* Increased gap between cards in sidebar */
            position: sticky;
            top: 2rem; /* More distance from top */
            align-self: flex-start;
        }

        .payroll-content-area {
            flex: 1;
            min-width: 0;
            display: flex; /* Use flex to manage spacing between sections */
            flex-direction: column;
            gap: 2rem; /* Space between content cards/sections */
        }

        /* Redesigned Card styles */
        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 2rem; /* Increased padding */
            box-shadow: var(--box-shadow-md); /* More prominent shadow */
            transition: all 0.3s ease-in-out;
        }
        .card:hover {
            box-shadow: var(--box-shadow-lg); /* Deeper shadow on hover */
        }
        .card h3 {
            font-size: 1.5rem;
            color: var(--primary-dark);
            margin-top: 0;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card h3 i {
            color: var(--primary-color);
            width: 28px;
            height: 28px;
        }


        /* Action Card specific styling */
        #payroll-actions-card .form-group {
            margin-bottom: 1.5rem; /* More spacing */
        }
        #payroll-actions-card .payroll-period-selector {
            display: flex;
            gap: 15px;
        }
        #payroll-actions-card .payroll-period-selector select {
            flex: 1;
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-sm);
            background-color: var(--background-color-light);
            color: var(--text-color);
            font-size: 1rem;
        }

        #payroll-actions-card .payroll-action-items {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 15px; /* Increased gap */
            margin-top: 1.5rem;
        }
        #payroll-actions-card .payroll-action-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 15px; /* Increased padding */
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            background-color: var(--background-color-light);
            color: var(--text-color);
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: var(--box-shadow-sm);
        }
        #payroll-actions-card .payroll-action-item:hover:not(:disabled) {
            background-color: var(--primary-bg); /* Use primary light for hover */
            border-color: var(--primary-color);
            transform: translateY(-3px); /* More pronounced lift */
            box-shadow: var(--box-shadow-md);
        }
        #payroll-actions-card .payroll-action-item:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background-color: var(--background-color-light-alt);
        }
        #payroll-actions-card .payroll-action-item i {
            width: 36px; /* Larger icons */
            height: 36px;
            margin-bottom: 10px;
            color: var(--primary-color);
        }
        #payroll-actions-card .payroll-action-item h4 {
            font-size: 1rem;
            font-weight: 700; /* Bolder text */
            margin: 0;
            color: inherit;
        }

        /* Current Period Summary Card */
        #payroll-current-summary .summary-details p {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px; /* More spacing */
            font-size: 1rem;
            padding: 4px 0;
            border-bottom: 1px dashed var(--border-color-light); /* Subtle separator */
        }
        #payroll-current-summary .summary-details p:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        #payroll-current-summary .summary-details strong {
            color: var(--text-color);
            font-weight: 700;
        }
        #payroll-current-summary .summary-details span {
            color: var(--text-light);
            font-weight: 500;
        }
        #payroll-current-summary .summary-details #summary-net-pay {
            font-size: 1.2rem;
            color: var(--success-color);
            font-weight: 700;
        }

        #payroll-current-summary #finalize-payroll-btn {
            margin-top: 1.5rem; /* More spacing */
            width: 100%;
            padding: 12px; /* Larger button */
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: var(--border-radius);
        }
        .payroll-card-actions-group .btn {
            padding: 10px 15px;
            font-size: 0.95rem;
            border-radius: var(--border-radius-sm);
        }

        /* Payroll Details Table Section (main content area cards) */
        .payroll-content-area .card .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-light);
        }
        .payroll-content-area .card .section-title .title-text i {
            width: 32px;
            height: 32px;
            color: var(--primary-color);
        }
        #refresh-payroll-details-btn {
            padding: 10px 15px;
            border-radius: var(--border-radius-sm);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            background-color: var(--primary-color);
            color: white;
            box-shadow: var(--box-shadow-sm);
        }
        #refresh-payroll-details-btn:hover {
            background-color: var(--primary-dark);
            box-shadow: var(--box-shadow-md);
            transform: translateY(-1px);
        }
        #refresh-payroll-details-btn i {
            width: 18px;
            height: 18px;
        }

        /* DataTables Customizations */
        .dataTables_wrapper {
            background-color: var(--card-bg); /* Ensure wrapper has card background */
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow-sm);
            padding: 0; /* Remove padding here, handled by card */
        }
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_length {
            padding: 15px 20px; /* Add padding to top controls */
            background-color: var(--background-color-light);
            border-bottom: 1px solid var(--border-color);
        }
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            padding: 15px 20px; /* Add padding to bottom controls */
            background-color: var(--background-color-light);
            border-top: 1px solid var(--border-color);
        }

        .payroll-table-section table.dataTable,
        .past-payrolls-history table.dataTable {
            margin-top: 0 !important; /* Remove default DataTables top margin */
            margin-bottom: 0 !important; /* Remove default DataTables bottom margin */
        }

        /* Styling for icon-only buttons in table cells */
        .payroll-table-section td .btn-small,
        .past-payrolls-history td .btn-small {
            padding: 8px; /* Square padding */
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: var(--info-color); /* Default blue for view/info */
            color: white;
            border: none;
            box-shadow: var(--box-shadow-sm);
            transition: all 0.2s ease;
        }
        .payroll-table-section td .btn-small:hover,
        .past-payrolls-history td .btn-small:hover {
            transform: translateY(-2px);
            box-shadow: var(--box-shadow-md);
        }
        .payroll-table-section td .btn-small i,
        .past-payrolls-history td .btn-small i {
            width: 18px;
            height: 18px;
            margin: 0; /* No margin on icons inside small buttons */
        }

        /* Specific action button colors in table cells */
        .payroll-table-section td .approve-payslip-btn,
        .past-payrolls-history td .approve-payslip-btn { background-color: var(--success-color); }
        .payroll-table-section td .approve-payslip-btn:hover,
        .past-payrolls-history td .approve-payslip-btn:hover { background-color: var(--success-dark); }

        .payroll-table-section td .mark-payslip-review-btn,
        .past-payrolls-history td .mark-payslip-review-btn { background-color: var(--warning-color); }
        .payroll-table-section td .mark-payslip-review-btn:hover,
        .past-payrolls-history td .mark-payslip-review-btn:hover { background-color: var(--warning-dark); }

        .payroll-table-section td .mark-payslip-pending-btn,
        .past-payrolls-history td .mark-payslip-pending-btn { background-color: var(--info-color); }
        .payroll-table-section td .mark-payslip-pending-btn:hover,
        .past-payrolls-history td .mark-payslip-pending-btn:hover { background-color: var(--info-dark); }

        .past-payrolls-history td .regenerate-payroll-btn { background-color: var(--warning-color); }
        .past-payrolls-history td .regenerate-payroll-btn:hover { background-color: var(--warning-dark); }

        .past-payrolls-history td .delete-payroll-btn { background-color: var(--danger-color); }
        .past-payrolls-history td .delete-payroll-btn:hover { background-color: var(--danger-dark); }


        /* Styles for Department Selection Modal (Refined UI) */
        .department-selection-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); /* Auto-fit columns */
            gap: 15px; /* Increased gap */
            margin-top: 1.5rem; /* Increased margin */
            max-height: 300px; /* Limit height for scroll */
            overflow-y: auto;
            padding: 10px; /* Padding for scrollable area */
            border: 1px solid var(--border-color); /* Add a border for the scrollable area */
            border-radius: var(--border-radius); /* Larger border radius */
            background-color: var(--background-color-light); /* Light background for the grid area */
        }
        .department-selection-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px 15px; /* Increased padding */
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius); /* Consistent border radius */
            background-color: var(--card-bg); /* Use card background for items */
            color: var(--text-color);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease; /* Smoother transitions */
            box-shadow: var(--box-shadow-sm);
            font-weight: 600;
            user-select: none; /* Prevent text selection on click */
            min-height: 120px; /* Ensure a decent size for the cards */
        }
        .department-selection-item:hover {
            background-color: var(--primary-bg-light); /* Lighter primary background on hover */
            border-color: var(--primary-color); /* Primary border on hover */
            color: var(--primary-dark);
            transform: translateY(-4px); /* More pronounced lift */
            box-shadow: var(--box-shadow-md);
        }
        .department-selection-item.active {
            background-color: var(--primary-color);
            border-color: var(--primary-dark);
            color: white;
            box-shadow: var(--box-shadow-md);
            transform: translateY(-2px); /* Slightly lifted when active */
        }
        .department-selection-item.active i {
            color: white; /* Active icon color */
        }
        .department-selection-item i {
            width: 40px; /* Larger icons */
            height: 40px;
            margin-bottom: 12px;
            color: var(--primary-color); /* Default icon color */
        }
        .department-selection-item.disabled {
            opacity: 0.5; /* More faded for disabled */
            cursor: not-allowed;
            background-color: var(--background-color-light-alt);
            border-color: var(--border-color-light);
            box-shadow: none;
            transform: none;
        }
        .department-selection-item.disabled i {
            color: var(--text-light);
        }

        .template-list-section {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px dashed var(--border-color);
        }
        .template-list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.25rem; /* Larger padding */
            background-color: var(--background-color-light);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-sm);
            margin-bottom: 0.75rem; /* More space */
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: var(--box-shadow-sm); /* Subtle shadow */
        }
        .template-list-item:hover {
            background-color: var(--primary-bg); /* Lighter primary for hover */
            transform: translateX(3px); /* More noticeable shift */
            box-shadow: var(--box-shadow-md);
        }
        .template-list-item .template-name {
            flex-grow: 1; /* Allow name to take available space */
            padding-right: 10px; /* Space before action icons */
            color: var(--text-color);
            font-size: 1rem;
            font-weight: 600;
        }
        .template-list-item .actions {
            display: flex;
            gap: 8px; /* More space between icons */
        }
        .template-list-item .btn-icon {
            background: none;
            border: none;
            padding: 8px; /* Larger hit area */
            cursor: pointer;
            color: var(--primary-color);
            transition: all 0.2s ease;
            border-radius: 50%; /* Make them round */
        }
        .template-list-item .btn-icon:hover {
            color: var(--primary-dark);
            background-color: rgba(0,0,0,0.05); /* Subtle background on hover */
            transform: scale(1.1);
        }
        .template-list-item .btn-icon.delete-template-btn {
            color: var(--danger-color);
        }
        .template-list-item .btn-icon.delete-template-btn:hover {
            background-color: rgba(var(--danger-color), 0.1);
            color: var(--danger-dark);
        }
        .template-list-item .btn-icon i {
            width: 20px; /* Larger icons */
            height: 20px;
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .payroll-main-layout {
                flex-direction: column; /* Stack sidebar and content vertically */
                gap: 1rem; /* Adjust gap for smaller screens */
                align-items: stretch; /* Stretch items to full width */
            }

            .payroll-sidebar {
                flex: none; /* Remove fixed width */
                width: 100%; /* Take full width */
                position: static; /* Remove sticky on smaller screens */
                top: auto; /* Remove top offset */
                align-self: auto; /* Remove align-self */
                padding: 1rem; /* Adjust padding */
            }

            .payroll-content-area {
                flex: 1; /* Allow to grow */
                min-width: 0; /* Important for flex items to shrink */
                width: 100%; /* Take full width */
                padding: 1rem; /* Adjust padding */
            }

            /* Adjust cards within sidebar/content for smaller screens */
            .payroll-sidebar .card,
            .payroll-content-area .card {
                padding: 1rem; /* Smaller padding on smaller screens */
            }

            /* Payroll Actions Grid for smaller screens */
            #payroll-actions-card .payroll-action-items {
                grid-template-columns: repeat(auto-fit, minmax(80px, 1fr)); /* Smaller grid items */
                gap: 5px; /* Reduce gap */
            }
            #payroll-actions-card .payroll-action-item h4 {
                font-size: 0.8rem; /* Smaller text */
            }
            #payroll-actions-card .payroll-action-item i {
                width: 24px; /* Smaller icons */
                height: 24px;
                margin-bottom: 5px;
            }

            /* Summary details font size adjustment */
            #payroll-current-summary .summary-details p {
                font-size: 0.9rem;
            }

            /* DataTables text size adjustments */
            .payroll-table-section table.dataTable,
            .past-payrolls-history table.dataTable {
                font-size: 0.85rem; /* Slightly smaller font for table text */
            }
            .payroll-table-section table.dataTable th,
            .past-payrolls-history table.dataTable th,
            .payroll-table-section table.dataTable td,
            .past-payrolls-history table.dataTable td {
                padding: 8px 10px; /* Reduce cell padding */
            }

            /* Buttons in table cells */
            .payroll-table-section td .btn-small,
            .past-payrolls-history td .btn-small {
                padding: 4px 6px; /* Even smaller padding for icon buttons */
                width: 28px; /* Make them smaller squares/circles */
                height: 28px;
            }

            /* Page header adjustments */
            .page-header h1 {
                font-size: 1.8rem; /* Smaller heading */
            }
            .page-subtitle {
                font-size: 0.9rem;
            }

            /* Department selection items responsive adjustment */
            .department-selection-item {
                 padding: 10px 5px; /* Smaller padding */
                 margin-bottom: 5px;
                 min-width: 100px;
            }
            .department-selection-item h4 {
                 font-size: 0.85rem;
                 margin-bottom: 0;
            }
            .department-selection-item i {
                 width: 24px;
                 height: 24px;
                 margin-bottom: 5px;
            }
            /* Ensure grid wraps nicely */
            .department-selection-grid {
                grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            }
            .department-selection-grid-individual {
                grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; // Includes the redesigned navbar ?>

    <div class="page-content">
        <div class="page-header">
            <h1><i data-lucide="<?php echo htmlspecialchars($pageIconClass); ?>"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
            <p class="page-subtitle">Manage and generate employee payrolls for each period.</p>
        </div>

        <div class="payroll-main-layout">
            <div class="payroll-sidebar">
                <div class="card" id="payroll-actions-card">
                    <h3>Payroll Actions</h3>
                    <div class="form-group">
                        <label for="payroll-period-month" class="form-label">Select Payroll Period:</label>
                        <div class="payroll-period-selector">
                            <select id="payroll-period-month" class="form-control">
                                <option value="">Select Month</option>
                                <?php
                                    for ($m=1; $m<=12; $m++) {
                                        $monthName = date('F', mktime(0,0,0,$m, 1));
                                        echo '<option value="'.sprintf('%02d', $m).'">'.$monthName.'</option>';
                                    }
                                ?>
                            </select>
                            <select id="payroll-period-year" class="form-control">
                                <option value="">Select Year</option>
                                <?php
                                    $currentYear = date('Y');
                                    for ($y=$currentYear + 1; $y>=($currentYear-5); $y--) {
                                        echo '<option value="'.$y.'">'.$y.'</option>';
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="payroll-action-items">
                        <button class="payroll-action-item" id="test-run-payroll-btn" disabled> <span class="spinner"></span> <i data-lucide="test-tube-2"></i>
                            <h4>Test Run</h4>
                        </button>
                        <button class="payroll-action-item" id="generate-payroll-btn" disabled> <span class="spinner"></span> <i data-lucide="calculator"></i>
                            <h4>Generate Payroll</h4>
                        </button>
                        <button class="payroll-action-item" id="view-past-payrolls-btn"> <span class="spinner"></span> <i data-lucide="history"></i>
                            <h4>View Past Payrolls</h4>
                        </button>
                    </div>
                </div>

                <div class="card" id="payroll-current-summary" style="display: none;">
                    <h3>Payroll Summary for <span id="summary-period"></span></h3>
                    <div class="summary-details">
                        <p><strong>Total Employees:</strong> <span id="summary-total-employees">0</span></p>
                        <p><strong>Total Gross Pay:</strong> <span id="summary-gross-pay">0.00</span></p>
                        <p><strong>Total Reimbursements:</strong> <span id="summary-total-reimbursements">0.00</span></p>
                        <p><strong>Total Deductions:</strong> <span id="summary-deductions">0.00</span></p>
                        <p><strong>Total Net Pay:</strong> <span id="summary-net-pay">0.00</span></p>
                    </div>
                    <div class="payroll-card-actions-group">
                        <button class="btn btn-success" id="finalize-payroll-btn" disabled> <span class="spinner"></span> <i data-lucide="check-circle" class="mr-1"></i> Finalize Payroll</button>
                    </div>
                </div>

                <div class="card" id="reports-card">
                    <h3>Payroll Reports</h3>
                    <div class="payroll-action-items">
                        <button class="payroll-action-item" id="view-forecast-report-btn">
                            <i data-lucide="trending-up"></i>
                            <h4>Forecasted YTD</h4>
                        </button>
                        <button class="payroll-action-item" id="view-tax-report-btn">
                            <i data-lucide="file-bar-chart"></i>
                            <h4>Tax Report</h4>
                        </button>
                        <button class="payroll-action-item" id="view-deductions-report-btn">
                            <i data-lucide="minus-circle"></i>
                            <h4>Deductions Report</h4>
                        </button>
                    </div>
                </div>
            </div>

            <div class="payroll-content-area">
                <div id="payroll-main-display">
                    <div class="card" id="welcome-payroll-card">
                        <h3>Welcome to Payroll Management!</h3>
                        <p>Select a payroll period and click "Generate Payroll" to begin, or "View Past Payrolls" to review history.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    <script>
    // Helper function to escape HTML for safe display
    function escapeHtml(str) {
        if (typeof str !== 'string' && typeof str !== 'number') return str; // Handle numbers too
        str = String(str); // Ensure it's a string
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return str.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Helper function to format amount with thousands separators and 2 decimal places
    function formatAmount(amount) {
        // Ensure amount is a number before formatting
        const num = parseFloat(amount);
        if (isNaN(num)) return '0.00'; // Handle non-numeric input gracefully
        return num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    $(document).ready(function() {
        // Add a console log to check Swal object immediately on document ready
        console.log("Swal object on document ready:", typeof Swal, Swal); // DEBUGGING Swal
        console.log("Swal.showToast function:", typeof Swal.showToast, Swal.showToast); // DEBUGGING Swal.showToast

        // Initialize Lucide icons on page load
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        // --- DOM Elements ---
        const $testRunPayrollBtn = $('#test-run-payroll-btn');
        const $generatePayrollBtn = $('#generate-payroll-btn');
        const $viewPastPayrollsBtn = $('#view-past-payrolls-btn');
        const $finalizePayrollBtn = $('#finalize-payroll-btn');

        const $payrollCurrentSummaryCard = $('#payroll-current-summary');
        const $monthSelect = $('#payroll-period-month');
        const $yearSelect = $('#payroll-period-year');
        const $payrollMainDisplay = $('#payroll-main-display'); // The dynamic content area

        // --- State Variables ---
        let lastFinalizedMonth = null;
        let lastFinalizedYear = null;
        let currentLoadedPayroll = null; // Stores data for the currently displayed payroll (summary & details)
        let payrollDetailsDataTable = null; // Declare DataTable instance


        // --- UI Helper Functions ---
        function showSpinner(button, spinnerElement) {
            $(spinnerElement).css('display', 'inline-block');
            button.prop('disabled', true);
        }

        function hideSpinner(button, spinnerElement) {
            $(spinnerElement).hide();
            button.prop('disabled', false);
        }

        // Helper for displaying SweetAlert errors from AJAX (cleaned up debug logs for production)
        function handleAjaxError(jqXHR, textStatus, errorThrown, contextMessage = 'An error occurred.') {
            let errorMessage = contextMessage;
            // Check if jqXHR.responseText is defined before trying to parse
            if (jqXHR.responseText && typeof jqXHR.responseText === 'string' && jqXHR.responseText.startsWith('<')) {
                // If it starts with HTML, it's a PHP error output or host interference.
                // Extract useful parts if possible, otherwise use a generic message.
                const warningMatch = jqXHR.responseText.match(/<br \/><b>Warning<\/b>: (.+?) in <b>(.+?)<\/b> on line <b>(\d+)<\/b><br \/>/);
                if (warningMatch && warningMatch[1]) {
                    errorMessage = `Server Warning: ${warningMatch[1]} (in ${warningMatch[2]} on line ${warningMatch[3]})`;
                } else {
                    errorMessage = 'Received unexpected HTML response from server. Check server logs for details.';
                }
                console.error("AJAX Invalid JSON Response (HTML detected):", jqXHR.responseText); // Log the full HTML response
            } else if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                errorMessage = jqXHR.responseJSON.message;
            } else if (textStatus === 'parsererror') {
                errorMessage = 'Received an unexpected response from the server (not valid JSON). This could be a server error.';
                console.error("AJAX Invalid JSON Response (Parser Error):", jqXHR.responseText); // Log raw response
            } else if (errorThrown) {
                errorMessage = errorThrown;
            }
            Swal.fire('Error', errorMessage, 'error');
            console.error("AJAX Error (Full):", textStatus, errorThrown, jqXHR.responseText); // Final error log
        }


        // --- Payroll Period Dropdown Logic ---
        function updatePayrollPeriodSelectors() {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: 'api.php',
                    method: 'GET',
                    data: { action: 'get_last_finalized_payroll_period' },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.last_finalized_payroll_period) {
                            lastFinalizedMonth = parseInt(response.last_finalized_payroll_period.pay_period_month);
                            lastFinalizedYear = parseInt(response.last_finalized_payroll_period.pay_period_year);
                        } else {
                            lastFinalizedMonth = null;
                            lastFinalizedYear = null;
                        }
                        populateAndLockPeriodDropdowns();
                        resolve();
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("Failed to fetch last finalized payroll period:", textStatus, errorThrown);
                        lastFinalizedMonth = null; // Assume no finalized period on error
                        lastFinalizedYear = null;
                        populateAndLockPeriodDropdowns();
                        handleAjaxError(jqXHR, textStatus, errorThrown, 'Failed to fetch last finalized payroll period.'); // Use generic handler here too
                        reject(new Error("Failed to fetch last finalized payroll period"));
                    }
                });
            });
        }

        function populateAndLockPeriodDropdowns() {
            const currentMonth = new Date().getMonth() + 1; // 1-12
            const currentYear = new Date().getFullYear();

            $monthSelect.empty();
            $yearSelect.empty();

            let defaultMonthToSelect = currentMonth;
            let defaultYearToSelect = currentYear;

            // Determine the next available month/year for generation
            if (lastFinalizedMonth !== null && lastFinalizedYear !== null) {
                let nextMonth = lastFinalizedMonth + 1;
                let nextYear = lastFinalizedYear;
                if (nextMonth > 12) {
                    nextMonth = 1;
                    nextYear++;
                }
                defaultMonthToSelect = nextMonth;
                defaultYearToSelect = nextYear;
            }

            // Populate Years (Current year +/- 5 years, plus next year)
            const startYear = currentYear - 5;
            const endYear = currentYear + 1; // Allow selecting next year

            for (let y = endYear; y >= startYear; y--) {
                $yearSelect.append($('<option>', {
                    value: y,
                    text: y,
                    selected: (y === defaultYearToSelect)
                }));
            }

            // Populate Months
            for (let m = 1; m <= 12; m++) {
                const monthName = moment().month(m - 1).format('MMMM');
                const $option = $('<option>', {
                    value: String(m).padStart(2, '0'),
                    text: monthName
                });

                // Disable past/finalized months
                if (lastFinalizedMonth !== null && lastFinalizedYear !== null) {
                    const currentSelectedYear = parseInt($yearSelect.val());
                    if (currentSelectedYear < lastFinalizedYear || (currentSelectedYear === lastFinalizedYear && m <= lastFinalizedMonth)) {
                        $option.prop('disabled', true);
                    }
                }
                $monthSelect.append($option);
            }

            // Set selected values *after* populating, then re-evaluate button states
            setTimeout(() => { // Small delay to ensure DOM is ready for .val()
                $monthSelect.val(String(defaultMonthToSelect).padStart(2, '0'));
                $yearSelect.val(defaultYearToSelect);

                $monthSelect.off('change').on('change', checkGenerateButtonStatus);
                $yearSelect.off('change').on('change', checkGenerateButtonStatus);

                checkGenerateButtonStatus(); // Initial check
            }, 50);
        }

        // Checks whether generate/test run/finalize buttons should be enabled
        function checkGenerateButtonStatus() {
            const month = $monthSelect.val();
            const year = $yearSelect.val();
            const selectedMonthOption = $monthSelect.find('option:selected');

            const isPeriodLocked = selectedMonthOption.is(':disabled');
            const isValidPeriodSelected = month && year && !isPeriodLocked;

            $testRunPayrollBtn.prop('disabled', !isValidPeriodSelected);
            $generatePayrollBtn.prop('disabled', !isValidPeriodSelected);

            // Finalize button is only enabled if a payroll is *loaded* and is *pending*
            // This will be handled by displayPayrollDetails, not here directly for all periods
        }

        // --- NEW: Department and Template Selection Modal Function ---
        async function showDepartmentSelectionModal(type) { // 'type' can be 'test_run' or 'generate'
            const csrf = $('meta[name="csrf-token"]').attr('content');
            let departments = [];
            let templates = [];

            Swal.fire({
                title: 'Loading Payroll Areas...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                // Fetch departments
                const departmentsResponse = await $.ajax({
                    url: 'api.php',
                    method: 'GET',
                    data: { action: 'get_departments' },
                    dataType: 'json'
                });
                if (departmentsResponse.success) {
                    departments = departmentsResponse.departments;
                } else {
                    throw new Error(departmentsResponse.message || 'Failed to load departments.');
                }

                // Fetch templates
                const templatesResponse = await $.ajax({
                    url: 'api.php',
                    method: 'GET',
                    data: { action: 'get_payroll_templates' },
                    dataType: 'json'
                });
                if (templatesResponse.success) {
                    templates = templatesResponse.templates;
                } else {
                    throw new Error(templatesResponse.message || 'Failed to load templates.');
                }

                Swal.close(); // Close loading spinner

                // Defensive check: Ensure 'departments' is an array before attempting to map
                if (!Array.isArray(departments)) {
                    console.error("Departments data is not an array:", departments);
                    throw new Error("Invalid departments data received from server. Please check API response for 'get_departments'.");
                }
                // Defensive check: Ensure 'templates' is an array before attempting to map/iterate
                if (!Array.isArray(templates)) {
                    console.error("Templates data is not an array:", templates);
                    throw new Error("Invalid templates data received from server. Please check API response for 'get_payroll_templates'.");
                }


                // HTML for department selection items (using div for custom styling)
                let departmentSelectionItemsHtml = departments
                    .filter(dept => dept && typeof dept === 'object' && dept.id !== undefined && dept.department_name !== undefined) // Robust filter to ensure valid objects
                    .map(dept => `
                        <div class="department-selection-item" data-department-id="${escapeHtml(dept.id)}">
                            <i data-lucide="users-round"></i>
                            <h4>${escapeHtml(dept.department_name)}</h4>
                            <input type="checkbox" name="selected_departments_checkboxes[]" value="${escapeHtml(dept.id)}" class="hidden-department-checkbox" style="display:none;">
                        </div>
                    `).join('');

                let templateListHtml = '';
                if (templates.length > 0) {
                    templates.forEach(tpl => {
                        templateListHtml += `
                            <div class="template-list-item" data-template-id="${tpl.id}" data-department-ids='${JSON.stringify(tpl.department_ids)}'>
                                <span class="template-name">${escapeHtml(tpl.template_name)}</span>
                                <div class="actions">
                                    <button type="button" class="btn-icon delete-template-btn" data-template-id="${tpl.id}" title="Delete Payroll Area">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    templateListHtml = '<div class="no-results" style="text-align: center; font-style: italic;">No saved payroll areas.</div>';
                }

                const modalHtml = `
                    <div style="text-align: left;">
                        <h4 style="margin-bottom: 10px; color: var(--text-color);">Select Departments:</h4>
                        <div class="department-selection-grid">
                            <div class="department-selection-item all-departments-toggle active" data-department-id="all">
                                <i data-lucide="grid"></i>
                                <h4>All Departments</h4>
                                <input type="checkbox" name="selected_departments_checkboxes[]" value="all" class="hidden-department-checkbox" style="display:none;" checked>
                            </div>
                            <div id="individual-departments-container" class="department-selection-grid-individual" style="display: grid; grid-template-columns: subgrid; grid-column: span 1 / -1;">
                                ${departmentSelectionItemsHtml}
                            </div>
                        </div>
                        <hr style="margin: 1.5rem 0; border-color: var(--border-color);">
                        <div class="form-group">
                            <label>Saved Payroll Areas:</label>
                            <div id="template-list-container">
                                ${templateListHtml}
                            </div>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="save-template-checkbox"> Save Current Payroll Area
                            </label>
                        </div>
                        <div class="form-group" id="template-name-group" style="display: none;">
                            <label for="template-name-input">Payroll Area Name:</label>
                            <input type="text" id="template-name-input" class="form-control" placeholder="e.g., Monthly Admin Payroll" required>
                            <div class="invalid-feedback" id="template-name-error"></div>
                        </div>
                        <div id="selected-departments-hidden-inputs" style="display:none;"></div>
                    </div>
                `;

                const { value: formValues } = await Swal.fire({
                    title: 'Configure Payroll Run',
                    html: modalHtml,
                    width: '80%', // Increased modal width
                    showCancelButton: true,
                    confirmButtonText: 'Proceed',
                    focusConfirm: false,
                    customClass: {
                        popup: 'swal2-popup-themed',
                        htmlContainer: 'swal2-html-container-scrollable'
                    },
                    preConfirm: () => {
                        const $modalContent = $(Swal.getPopup()); // Ensure $modalContent is a jQuery object here
                        // Get selected department IDs from hidden checkboxes
                        const selectedDepartmentIds = [];
                        // Check for 'all' first
                        if ($modalContent.find('#selected-departments-hidden-inputs input[value="all"]').is(':checked')) {
                            selectedDepartmentIds.push('all');
                        } else {
                            $modalContent.find('#selected-departments-hidden-inputs .hidden-department-checkbox:checked').each(function() {
                                selectedDepartmentIds.push($(this).val());
                            });
                        }

                        // Validation
                        if (selectedDepartmentIds.length === 0) {
                            Swal.showValidationMessage('Please select at least one department or "All Departments".');
                            return false;
                        }

                        const saveTemplate = $modalContent.find('#save-template-checkbox').is(':checked');
                        let templateName = '';

                        if (saveTemplate) {
                            templateName = $modalContent.find('#template-name-input').val().trim();
                            if (!templateName) {
                                $modalContent.find('#template-name-input').addClass('is-invalid');
                                $('#template-name-error').text('Payroll Area Name is required.').show();
                                Swal.showValidationMessage('Payroll Area Name is required.');
                                return false;
                            }
                            // Clear any previous error on the template name field
                            $modalContent.find('#template-name-input').removeClass('is-invalid');
                            $('#template-name-error').hide().text('');
                        }

                        return { selectedDepartmentIds, saveTemplate, templateName };
                    },
                    didOpen: (modalElement) => {
                        const $modalContent = $(modalElement);
                        lucide.createIcons({nodes: $modalContent.find('i[data-lucide]')}); // Render Lucide icons in the modal

                        const $allDepartmentsToggle = $modalContent.find('.all-departments-toggle');
                        const $departmentItems = $modalContent.find('.department-selection-item:not(.all-departments-toggle)');
                        const $saveTemplateCheckbox = $modalContent.find('#save-template-checkbox');
                        const $templateNameGroup = $modalContent.find('#template-name-group');
                        const $templateNameInput = $modalContent.find('#template-name-input');
                        const $templateListContainer = $modalContent.find('#template-list-container');
                        const $hiddenInputsContainer = $modalContent.find('#selected-departments-hidden-inputs');

                        // Function to update hidden checkboxes based on active selection items
                        function updateHiddenCheckboxes() {
                            $hiddenInputsContainer.empty(); // Clear existing hidden checkboxes
                            if ($allDepartmentsToggle.hasClass('active')) {
                                $hiddenInputsContainer.append(`<input type="checkbox" name="selected_departments[]" value="all" checked class="hidden-department-checkbox">`);
                            } else {
                                $modalContent.find('.department-selection-item.active').each(function() {
                                    const deptId = $(this).data('department-id');
                                    $hiddenInputsContainer.append(`<input type="checkbox" name="selected_departments[]" value="${deptId}" checked class="hidden-department-checkbox">`);
                                });
                            }
                        }

                        // Initial state: "All Departments" active by default
                        $departmentItems.removeClass('active').addClass('disabled'); // Individual items are disabled/inactive by default
                        $allDepartmentsToggle.addClass('active'); // Explicitly mark 'All' as active
                        updateHiddenCheckboxes(); // Set initial hidden input state

                        $allDepartmentsToggle.on('click', function() {
                            $(this).toggleClass('active');
                            if ($(this).hasClass('active')) {
                                $departmentItems.removeClass('active').addClass('disabled'); // Deactivate & disable individuals
                            } else {
                                $departmentItems.removeClass('disabled'); // Enable individuals
                            }
                            updateHiddenCheckboxes();
                        });

                        $departmentItems.on('click', function() {
                            $allDepartmentsToggle.removeClass('active'); // Deactivate 'All Departments'
                            $(this).toggleClass('active');
                            $departmentItems.removeClass('disabled'); // Ensure all are enabled if 'All' is unchecked
                            updateHiddenCheckboxes();
                        });

                        $saveTemplateCheckbox.on('change', function() {
                            if ($(this).is(':checked')) {
                                $templateNameGroup.slideDown();
                                $templateNameInput.prop('required', true);
                            } else {
                                $templateNameGroup.slideUp();
                                $templateNameInput.prop('required', false).val('');
                                $('#template-name-input').removeClass('is-invalid');
                                $('#template-name-error').hide().text('');
                            }
                        });

                        // Event delegation for Load and Delete Template buttons
                        $templateListContainer.on('click', '.template-list-item', function(e) {
                            // Prevent event from bubbling if the delete button was clicked
                            if ($(e.target).closest('.btn-icon').length) {
                                return; // Let specific delete button handler take over
                            }

                            const selectedTemplateDeptIds = $(this).data('department-ids'); // This will be array or string 'all'

                            $departmentItems.removeClass('active').removeClass('disabled'); // Clear current selection and enable all
                            $allDepartmentsToggle.removeClass('active'); // Uncheck "All"

                            if (selectedTemplateDeptIds === 'all') {
                                $allDepartmentsToggle.addClass('active');
                                $departmentItems.removeClass('active').addClass('disabled'); // Disable individuals
                            } else if (Array.isArray(selectedTemplateDeptIds)) {
                                selectedTemplateDeptIds.forEach(id => {
                                    $modalContent.find(`.department-selection-item[data-department-id="${id}"]`).addClass('active');
                                });
                            }
                            updateHiddenCheckboxes(); // Update hidden inputs based on loaded template
                            // Use native browser console.log as fallback if Swal.showToast is not available
                            if (typeof Swal.showToast === 'function') { // Check if function exists
                                Swal.showToast({
                                    icon: 'info',
                                    title: 'Payroll Area loaded!',
                                    position: 'top-end',
                                    timer: 2000
                                });
                            } else {
                                console.log('Payroll Area loaded: ' + $(this).find('.template-name').text());
                            }
                        });

                        $templateListContainer.on('click', '.delete-template-btn', async function() {
                            const templateIdToDelete = $(this).data('template-id');
                            const templateNameToDelete = $(this).closest('.template-list-item').find('.template-name').text();

                            const confirmDelete = await Swal.fire({
                                title: 'Are you sure?',
                                text: `You are about to delete payroll area "${escapeHtml(templateNameToDelete)}". This cannot be undone!`,
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: 'var(--danger-color)',
                                cancelButtonColor: 'var(--text-light)',
                                confirmButtonText: 'Yes, delete it!'
                            });

                            if (confirmDelete.isConfirmed) {
                                Swal.showLoading();
                                try {
                                    const deleteResponse = await $.ajax({
                                        url: 'api.php',
                                        method: 'POST',
                                        data: { action: 'delete_payroll_template', template_id: templateIdToDelete, csrf_token: csrf },
                                        dataType: 'json'
                                    });

                                    if (deleteResponse.success) {
                                        Swal.fire('Deleted!', deleteResponse.message || 'Payroll area deleted successfully.', 'success');
                                        // Re-open the department selection modal to show updated template list
                                        showDepartmentSelectionModal(type);
                                    } else {
                                        throw new Error(deleteResponse.message || 'Failed to delete payroll area.');
                                    }
                                } catch (error) {
                                    Swal.fire('Error', error.message || 'An error occurred during deletion.', 'error');
                                }
                            }
                        });
                    }
                });

                if (formValues) {
                    const { selectedDepartmentIds, saveTemplate, templateName } = formValues;

                    // If user chose to save as template, call API first
                    if (saveTemplate) {
                        const saveResponse = await $.ajax({
                            url: 'api.php',
                            method: 'POST',
                            data: {
                                action: 'save_payroll_template',
                                template_name: templateName,
                                department_ids: selectedDepartmentIds, // Send 'all' string or array
                                csrf_token: csrf
                            },
                            dataType: 'json'
                        });

                        if (saveResponse.success) {
                            if (typeof Swal.showToast === 'function') { // Check if function exists
                                Swal.showToast({
                                    icon: 'success',
                                    title: saveResponse.message || 'Payroll Area saved!',
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 3000
                                });
                            } else {
                                console.log('Payroll Area saved: ' + (saveResponse.message || 'Success'));
                            }
                        } else {
                            Swal.fire('Error', saveResponse.message || 'Failed to save payroll area.', 'error');
                            return; // Stop if template save fails
                        }
                    }

                    // Now proceed with payroll generation using the selected departments
                    if (type === 'test_run') {
                        triggerTestRunPayroll(selectedDepartmentIds);
                    } else if (type === 'generate') {
                        triggerGeneratePayroll(selectedDepartmentIds);
                    }
                }
            } catch (error) {
                // This catch handles errors from AJAX calls (get_departments, get_payroll_templates)
                // or if formValues is undefined (user clicked cancel)
                Swal.fire('Error', error.message || 'An unexpected error occurred while preparing department selection.', 'error');
                console.error("Department selection modal error:", error);
            }
        }

        // --- Updated Test Run Payroll Button Click Handler ---
        $testRunPayrollBtn.on('click', function() {
            const month = $monthSelect.val();
            const year = $yearSelect.val();
            if (!month || !year) { Swal.fire('Error', 'Please select a valid payroll month and year.', 'error'); return; }

            showDepartmentSelectionModal('test_run'); // Open the department selection modal
        });

        // --- Updated Generate Payroll Button Click Handler ---
        $generatePayrollBtn.on('click', function() {
            const month = $monthSelect.val();
            const year = $yearSelect.val();
            if (!month || !year) { Swal.fire('Error', 'Please select a valid payroll month and year.', 'error'); return; }
            if ($monthSelect.find('option:selected').is(':disabled')) { Swal.fire('Error', 'Payroll for the selected period is already finalized.', 'error'); return; }

            showDepartmentSelectionModal('generate'); // Open the department selection modal
        });

        // --- Refactored Payroll Trigger Functions (called by showDepartmentSelectionModal) ---
        function triggerTestRunPayroll(departmentIds) {
            const month = $monthSelect.val();
            const year = $yearSelect.val();
            const csrf = $('meta[name="csrf-token"]').attr('content');

            showSpinner($testRunPayrollBtn, '#test-run-payroll-btn .spinner');
            $payrollCurrentSummaryCard.hide();
            $payrollMainDisplay.empty();

            $.ajax({
                url: 'api.php',
                method: 'POST',
                data: { action: 'test_run_payroll', month: month, year: year, department_ids: departmentIds, csrf_token: csrf },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Test Run Complete', response.message || 'This is a preview only. No data has been saved.', 'info');
                        displayPayrollDetails(response.payroll_summary, response.payroll_details, month, year, true, departmentIds);
                    } else {
                        Swal.fire('Error', response.message || 'Failed to run test payroll.', 'error');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    handleAjaxError(jqXHR, textStatus, errorThrown, 'An error occurred during the test run.');
                },
                complete: function() {
                    hideSpinner($testRunPayrollBtn, '#test-run-payroll-btn .spinner');
                }
            });
        }

        function triggerGeneratePayroll(departmentIds) {
            const month = $monthSelect.val();
            const year = $yearSelect.val();
            const csrf = $('meta[name="csrf-token"]').attr('content');

            Swal.fire({
                title: 'Confirm Payroll Generation?',
                text: "Generating payroll will include all approved reimbursements for this period that haven't been processed yet. Are you sure?",
                icon: 'info',
                showCancelButton: true, confirmButtonColor: 'var(--primary-color)', cancelButtonColor: 'var(--danger-color)', confirmButtonText: 'Yes, Generate!'
            }).then((result) => {
                if (result.isConfirmed) {
                    showSpinner($generatePayrollBtn, '#generate-payroll-btn .spinner');
                    $payrollCurrentSummaryCard.hide();
                    $payrollMainDisplay.empty();

                    $.ajax({
                        url: 'api.php',
                        method: 'POST',
                        data: { action: 'generate_payroll', month: month, year: year, department_ids: departmentIds, csrf_token: csrf },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire('Success', response.message || 'Payroll generated successfully for review.', 'success');
                                displayPayrollDetails(response.payroll_summary, response.payroll_details, month, year, false, departmentIds);
                            } else {
                                Swal.fire('Error', response.message || 'Failed to generate payroll.', 'error');
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            handleAjaxError(jqXHR, textStatus, errorThrown, 'An error occurred during payroll generation.');
                        },
                        complete: function() {
                            hideSpinner($generatePayrollBtn, '#generate-payroll-btn .spinner');
                        }
                    });
                }
            });
        }
        // --- END Refactored Payroll Trigger Functions ---


        // --- Display Payroll Details (after Generate/Test Run/View Past) ---
        // This function will render the payroll details table
        function displayPayrollDetails(summary, details, month, year, isTestRun = false, departmentIds = null) { // departmentIds parameter is now an array or 'all'
            const monthName = moment().month(month - 1).format('MMMM');
            const periodString = `${monthName} ${year}`;

            // Render the payroll details section HTML if it doesn't exist
            let $payrollDetailsSection = $('#payroll-details-section');
            if ($payrollDetailsSection.length === 0) {
                $payrollMainDisplay.append(`
                    <div class="card" id="payroll-details-section">
                        <h3 class="section-title"><span class="title-text"><i data-lucide="list-checks"></i> Payroll Details: <span id="details-period"></span></span>
                            <button class="btn btn-primary btn-small" id="refresh-payroll-details-btn" title="Refresh Payroll Data">
                                <i data-lucide="refresh-cw"></i>
                            </button>
                        </h3>
                        <div class="table-container">
                            <table id="payroll-details-table">
                                <thead>
                                    <tr>
                                        <th>Employee Name</th>
                                        <th>Designation</th>
                                        <th>Department</th>
                                        <th>Gross Pay</th>
                                        <th>Additional Payments</th>
                                        <th>Tax Deduction</th>
                                        <th>Reimbursements</th>
                                        <th>Deductions</th>
                                        <th>Net Pay</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                            <div id="no-payroll-data" class="no-results" style="display: none;"></div>
                        </div>
                    </div>
                `);
                $payrollDetailsSection = $('#payroll-details-section'); // Re-select the newly added element
                if (typeof lucide !== 'undefined') { lucide.createIcons(); } // Create icons for new HTML
            }

            const $payrollDetailsTableBody = $payrollDetailsSection.find('tbody');
            const $noPayrollDataMessage = $payrollDetailsSection.find('#no-payroll-data');

            // --- Attach Refresh Button Handler using event delegation ---
            // This listener will now be attached only once on page load to a static parent
            // and filter clicks by '#refresh-payroll-details-btn'.


            // Update summary card in sidebar
            $('#summary-period').html(periodString + (isTestRun ? " (Test Run)" : ""));
            $('#summary-total-employees').text(summary.total_employees || 0);
            $('#summary-gross-pay').text(formatAmount(summary.total_gross_pay || 0)); // Formatted
            $('#summary-total-reimbursements').text(formatAmount(summary.total_reimbursements || 0)); // Formatted
            $('#summary-deductions').text(formatAmount(summary.total_deductions || 0)); // Formatted
            $('#summary-net-pay').text(formatAmount(summary.total_net_pay || 0)); // Formatted
            $payrollCurrentSummaryCard.show(); // Ensure summary card is visible

            $payrollDetailsTableBody.empty();
            $noPayrollDataMessage.hide();
            $payrollMainDisplay.empty().append($payrollDetailsSection); // Show this section, hide others

            // Set current loaded payroll state
            currentLoadedPayroll = { summary, details, month, year, isTestRun, status: summary.status, department_ids: departmentIds }; // Store department_ids
            // Note: department_ids now an array or 'all' string, not a single ID

            // Initialize/Destroy DataTables
            if (payrollDetailsDataTable) {
                payrollDetailsDataTable.destroy(); // Destroy existing instance
                $payrollDetailsTableBody.empty(); // Clear tbody to prevent duplicate rows after destroy
            }

            if (details && details.length > 0) {
                payrollDetailsDataTable = $('#payroll-details-table').DataTable({
                    data: details,
                    columns: [
                        { data: 'employee_name', title: 'Employee Name' },
                        { data: 'designation', title: 'Designation' },
                        { data: 'department', title: 'Department' },
                        { data: 'gross_pay', title: 'Gross Pay', render: data => formatAmount(data) }, // Formatted
                        { data: 'additional_payments', title: 'Additional Payments', render: data => formatAmount(data) }, // Additional Payments Column
                        { data: 'tax_deduction', title: 'Tax Deduction', render: data => formatAmount(data) }, // Formatted
                        { data: 'reimbursement_amount', title: 'Reimbursements', render: data => formatAmount(data) }, // Formatted
                        { data: 'total_deductions', title: 'Deductions', render: data => formatAmount(data) }, // Formatted
                        { data: 'net_pay', title: 'Net Pay', render: data => formatAmount(data) }, // Formatted
                        { data: 'status', title: 'Status', render: data => `<span class="status-badge status-${data.toLowerCase().replace(' ', '-')}">${escapeHtml(data)}</span>` },
                        {   // Actions Column
                            data: null,
                            title: 'Actions',
                            orderable: false,
                            searchable: false, // Actions column typically not searchable
                            render: (data, type, row) => {
                                const isPending = row.status === 'Pending';
                                const isNeedsReview = row.status === 'Needs Review'; // New check for Needs Review status
                                const isFinalizedOverall = summary.status === 'Finalized'; // Check overall payroll status

                                return `
                                    <div style="display:flex; gap: 5px; align-items: center;">
                                        ${!isTestRun ? `
                                        <button class="btn btn-small btn-primary view-payslip-btn"
                                                     data-employee-id="${row.employee_id}"
                                                     data-payroll-detail-id="${row.id}"
                                                     data-month="${month}"
                                                     data-year="${year}"
                                                     title="View Payslip">
                                                     <i data-lucide="file-text"></i>
                                                 </button>
                                                 ` : `
                                                 <button class="btn btn-small btn-outline" disabled title="Payslip not available for Test Run">
                                                     <i data-lucide="file-text"></i>
                                                 </button>
                                                 `}
                                                 ${isPending && !isTestRun && !isFinalizedOverall ? `
                                                 <button class="btn btn-small btn-success approve-payslip-btn"
                                                     data-employee-id="${row.employee_id}"
                                                     data-payroll-detail-id="${row.id}"
                                                     title="Approve Payslip">
                                                     <i data-lucide="check"></i>
                                                 </button>
                                                 <button class="btn btn-small btn-warning mark-payslip-review-btn" data-employee-id="${row.employee_id}"
                                                     data-payroll-detail-id="${row.id}"
                                                     title="Mark for Review">
                                                     <i data-lucide="alert-circle"></i>
                                                 </button>
                                                 ` : (isNeedsReview && !isTestRun && !isFinalizedOverall ? `
                                                 <button class="btn btn-small btn-info approve-payslip-btn"
                                                     data-employee-id="${row.employee_id}"
                                                     data-payroll-detail-id="${row.id}"
                                                     title="Approve Payslip (currently under review)">
                                                     <i data-lucide="check"></i>
                                                 </button>
                                                 <button class="btn btn-small btn-secondary mark-payslip-pending-btn" data-employee-id="${row.employee_id}"
                                                     data-payroll-detail-id="${row.id}"
                                                     title="Revert to Pending">
                                                     <i data-lucide="rotate-ccw"></i>
                                                 </button>
                                                 ` : '')}
                                             </div>
                                             `;
                                         }
                                     }
                                 ],
                                 paging: true, // Enable DataTables pagination for this table
                                 lengthMenu: [10, 25, 50, 100], // Options for number of entries per page
                                 pageLength: 25, // Default number of entries per page
                                 info: true,    // Show "Showing X of Y entries"
                                 filter: true, // Show built-in search box (for client-side table filtering)
                                 responsive: true, // IMPORTANT: Enable DataTables Responsive for column hiding/expansion
                                 order: [[0, 'asc']], // Default sort by Employee Name
                                 drawCallback: function() {
                                     if (typeof lucide !== 'undefined') { lucide.createIcons(); } // Re-render icons after each draw
                                 }
                             });
                         } else {
                             $noPayrollDataMessage.text(`No payroll details available for ${periodString}.`).show();
                         }

                         // Manage Finalize Button visibility/state (simplified logic)
                         // Finalize only if not test run, overall payroll is pending, all payslips are approved, and there's data
                         const allPayslipsApproved = details.every(detail => detail.status === 'Approved');
                         const payrollIsPending = summary.status === 'Pending';

                         if (!isTestRun && payrollIsPending && allPayslipsApproved && details.length > 0) {
                             $finalizePayrollBtn.prop('disabled', false).show();
                         } else if (!isTestRun && summary.status === 'Finalized') {
                             $finalizePayrollBtn.hide(); // Hide if already finalized
                         } else {
                             $finalizePayrollBtn.prop('disabled', true).show();
                         }
                     }

                     // --- View Past Payrolls Logic ---
                     $viewPastPayrollsBtn.on('click', function() {
                         showSpinner($(this), '#view-past-payrolls-btn .spinner');
                         $payrollCurrentSummaryCard.hide();
                         $payrollMainDisplay.empty(); // Clear dynamic content area

                         // Render the past payrolls history section HTML if it doesn't exist
                         let $pastPayrollsHistorySection = $('#past-payrolls-history-section');
                         if ($pastPayrollsHistorySection.length === 0) {
                             $payrollMainDisplay.append(`
                                 <div class="card" id="past-payrolls-history-section">
                                     <h3 class="section-title"><i data-lucide="archive"></i> Past Payroll History</h3>
                                     <div class="table-container">
                                         <table id="past-payrolls-table">
                                             <thead>
                                                 <tr>
                                                     <th>Period</th>
                                                     <th>Total Net Pay</th>
                                                     <th>Status</th>
                                                     <th>Finalized On</th>
                                                     <th>Actions</th>
                                                 </tr>
                                             </thead>
                                             <tbody></tbody>
                                         </table>
                                         <div id="no-past-payrolls" class="no-results" style="display: none;"></div>
                                     </div>
                                 </div>
                             `);
                             $pastPayrollsHistorySection = $('#past-payrolls-history-section'); // Re-select
                             if (typeof lucide !== 'undefined') { lucide.createIcons(); } // Create icons
                         }

                         const $pastPayrollsTableBody = $pastPayrollsHistorySection.find('tbody');
                         const $noPastPayrollsMessage = $pastPayrollsHistorySection.find('#no-past-payrolls');


                         $pastPayrollsTableBody.empty();
                         $noPastPayrollsMessage.hide();
                         $payrollMainDisplay.empty().append($pastPayrollsHistorySection); // Show this section

                         $.ajax({
                             url: 'api.php',
                             method: 'GET',
                             data: { action: 'get_payroll_history' },
                             dataType: 'json',
                             success: function(response) {
                                 if (response.success && response.history && response.history.length > 0) {
                                     // Destroy existing DataTables instance if any
                                     if ($.fn.DataTable.isDataTable('#past-payrolls-table')) {
                                         $('#past-payrolls-table').DataTable().destroy();
                                     }

                                     // Initialize DataTables for past payrolls table
                                     $('#past-payrolls-table').DataTable({
                                         data: response.history,
                                         columns: [
                                             { data: null, title: 'Period', render: (data) => `${moment().month(data.pay_period_month - 1).format('MMMM')} ${data.pay_period_year}` },
                                             { data: 'total_net_pay', title: 'Total Net Pay', render: data => formatAmount(data) },
                                             { data: 'status', title: 'Status', render: data => `<span class="status-badge status-${data.toLowerCase().replace(' ', '-')}">${escapeHtml(data)}</span>` },
                                             { data: 'finalized_at', title: 'Finalized On', render: data => data ? new Date(data).toLocaleDateString() : 'N/A' },
                                             { // Actions Column
                                                 data: null,
                                                 title: 'Actions',
                                                 orderable: false,
                                                 searchable: false,
                                                 render: (data, type, row) => {
                                                     const isPending = row.status.toLowerCase() === 'pending';
                                                     const isFinalized = row.status.toLowerCase() === 'finalized';
                                                     return `
                                                         <div style="display:flex; gap: 5px; align-items: center;">
                                                             <button class="btn btn-small btn-primary view-past-details-btn"
                                                                      data-month="${row.pay_period_month}"
                                                                      data-year="${row.pay_period_year}"
                                                                      title="View Details">
                                                                     <i data-lucide="eye"></i>
                                                                 </button>
                                                                 ${isPending ? `
                                                                 <button class="btn btn-small btn-warning regenerate-payroll-btn"
                                                                      data-month="${row.pay_period_month}"
                                                                      data-year="${row.pay_period_year}"
                                                                      title="Regenerate Payroll">
                                                                      <i data-lucide="refresh-cw"></i>
                                                                  </button>
                                                                  <button class="btn btn-small btn-danger delete-payroll-btn"
                                                                      data-id="${row.id}"
                                                                      title="Delete Payroll Draft">
                                                                      <i data-lucide="trash-2"></i>
                                                                  </button>
                                                                  ` : ''}
                                                                  ${isFinalized ? `
                                                                  ` : ''}
                                                             </div>
                                                             `;
                                                         }
                                                     }
                                                 ],
                                                 paging: true, // Enable pagination
                                                 lengthMenu: [10, 25, 50],
                                                 pageLength: 10,
                                                 info: true,
                                                 filter: true, // Enable search for history
                                                 responsive: true, // IMPORTANT: Enable DataTables Responsive
                                                 order: [[0, 'desc']], // Sort by period descending
                                                 drawCallback: function() {
                                                     if (typeof lucide !== 'undefined') { lucide.createIcons(); } // Re-render icons after each draw
                                                 }
                                             });

                                         } else {
                                             $noPastPayrollsMessage.text('No past payroll records found.').show();
                                         }
                                     },
                                     error: function(jqXHR, textStatus, errorThrown) {
                                         handleAjaxError(jqXHR, textStatus, errorThrown, 'An error occurred retrieving payroll history.');
                                     },
                                     complete: function() {
                                         hideSpinner($viewPastPayrollsBtn, '#view-past-payrolls-btn .spinner');
                                     }
                                 });
                             });


                             // --- Finalize Payroll Logic ---
                             $finalizePayrollBtn.on('click', function() {
                                 if (!currentLoadedPayroll || currentLoadedPayroll.isTestRun) {
                                     Swal.fire('Error', 'No payroll selected or it is a test run. Generate a real payroll first.', 'error');
                                     return;
                                 }
                                 if (currentLoadedPayroll.status === 'Finalized') {
                                     Swal.fire('Info', 'Payroll is already finalized for this period.', 'info');
                                     return;
                                 }
                                 // Check if all individual payslips are approved before allowing finalization
                                 const allPayslipsApproved = currentLoadedPayroll.details.every(detail => detail.status === 'Approved');
                                 if (!allPayslipsApproved) {
                                     Swal.fire('Warning', 'All individual payslips must be \'Approved\' before finalizing the payroll.', 'warning');
                                     return;
                                 }

                                 const month = currentLoadedPayroll.month;
                                 const year = currentLoadedPayroll.year;

                                 Swal.fire({
                                     title: 'Are you sure?',
                                     text: `You are about to finalize the payroll for ${moment().month(month - 1).format('MMMM')} ${year}. This action cannot be undone!`,
                                     icon: 'warning',
                                     showCancelButton: true,
                                     confirmButtonColor: 'var(--success-color)',
                                     cancelButtonColor: 'var(--danger-color)',
                                     confirmButtonText: 'Yes, finalize it!'
                                 }).then((result) => {
                                     if (result.isConfirmed) {
                                         showSpinner($(this), '.spinner'); // Use class for spinner
                                         $.ajax({
                                             url: 'api.php',
                                             method: 'POST',
                                             data: {
                                                 action: 'finalize_payroll',
                                                 month: month,
                                                 year: year,
                                                 csrf_token: $('meta[name="csrf-token"]').attr('content')
                                             },
                                             dataType: 'json',
                                             success: function(response) {
                                                 if (response.success) {
                                                     Swal.fire('Finalized!', response.message || 'Payroll has been finalized.', 'success');
                                                     updatePayrollPeriodSelectors(); // Re-evaluate locking status for dropdowns
                                                     // Reload the finalized payroll details to show updated status and disabled actions
                                                     displayPayrollDetails(
                                                         { ...currentLoadedPayroll.summary, status: 'Finalized' }, // Update status locally for immediate display
                                                         currentLoadedPayroll.details.map(d => ({ ...d, status: 'Approved' })), // Mark all payslips approved
                                                         month, year, false, currentLoadedPayroll.department_ids // Pass department IDs (array or 'all')
                                                     );
                                                     $viewPastPayrollsBtn.trigger('click'); // Refresh history to reflect finalized status
                                                 } else {
                                                     Swal.fire('Error', response.message || 'Failed to finalize payroll.', 'error');
                                                 }
                                             },
                                             error: function(jqXHR, textStatus, errorThrown) {
                                                 handleAjaxError(jqXHR, textStatus, errorThrown, 'An error occurred during finalization.');
                                             },
                                             complete: function() {
                                                 hideSpinner($(this), '.spinner'); // Hide spinner on the clicked button
                                             }
                                         });
                                     }
                                 });
                             });

                             // --- Individual Payslip Status Update (NEW: Mark for Review, Revert to Pending) ---
                             $payrollMainDisplay.on('click', '.mark-payslip-review-btn, .mark-payslip-pending-btn', function() {
                                 const payrollDetailId = $(this).data('payroll-detail-id');
                                 const currentStatus = $(this).closest('tr').find('.status-badge').text().trim();
                                 let newStatus;
                                 let confirmText;
                                 let successMessage;
                                 let icon;
                                 let confirmButtonColor;

                                 if ($(this).hasClass('mark-payslip-review-btn')) {
                                     newStatus = 'Needs Review';
                                     confirmText = 'Mark for Review?';
                                     successMessage = 'Payslip marked for review.';
                                     icon = 'question';
                                     confirmButtonColor = 'var(--warning-color)';
                                 } else { // mark-payslip-pending-btn (revert from Needs Review)
                                     newStatus = 'Pending';
                                     confirmText = 'Revert to Pending?';
                                     successMessage = 'Payslip reverted to pending.';
                                     icon = 'info';
                                     confirmButtonColor = 'var(--info-color)';
                                 }

                                 Swal.fire({
                                     title: confirmText,
                                     text: `Are you sure you want to change this payslip's status to "${newStatus}"?`,
                                     icon: icon,
                                     showCancelButton: true,
                                     confirmButtonColor: confirmButtonColor,
                                     cancelButtonColor: 'var(--text-light)',
                                     confirmButtonText: `Yes, ${newStatus} it!`
                                 }).then((result) => {
                                     if (result.isConfirmed) {
                                         showSpinner($(this), '.spinner'); // Use class for spinner
                                         $.ajax({
                                             url: 'api.php',
                                             method: 'POST',
                                             data: {
                                                 action: 'update_payslip_status', // Use the new action
                                                 payslip_detail_id: payrollDetailId,
                                                 new_status: newStatus,
                                                 csrf_token: $('meta[name="csrf-token"]').attr('content')
                                             },
                                             dataType: 'json',
                                             success: function(response) {
                                                 if (response.success) {
                                                     Swal.fire('Updated!', successMessage, 'success');
                                                     // Optimistic update for the current view
                                                     if (currentLoadedPayroll) {
                                                         const updatedDetails = currentLoadedPayroll.details.map(detail =>
                                                             detail.id === payrollDetailId ? { ...detail, status: newStatus } : detail
                                                         );
                                                         // Re-render the details with the updated status
                                                         displayPayrollDetails(currentLoadedPayroll.summary, updatedDetails, currentLoadedPayroll.month, currentLoadedPayroll.year, currentLoadedPayroll.isTestRun, currentLoadedPayroll.department_ids); // Pass department IDs
                                                     }
                                                     updatePayrollPeriodSelectors(); // Re-evaluate locking status for period dropdowns
                                                 } else {
                                                     Swal.fire('Error', response.message, 'error');
                                                 }
                                             },
                                             error: function(jqXHR, textStatus, errorThrown) {
                                                 handleAjaxError(jqXHR, textStatus, errorThrown);
                                             },
                                             complete: function() {
                                                 hideSpinner($(this), '.spinner'); // Hide spinner on the clicked button
                                             }
                                         });
                                     }
                                 });
                             });


                             // --- View Payslip Logic ---
                             // Delegated event listener for dynamically added buttons
                             $payrollMainDisplay.on('click', '.view-payslip-btn', function() {
                                 const payrollDetailId = $(this).data('payroll-detail-id');
                                 // Check if payrollDetailId is valid (i.e., not from a test run)
                                 if (!payrollDetailId) {
                                     Swal.fire('Error', 'Invalid payslip ID. Payslips are only available for generated (non-test run) payrolls.', 'error');
                                     return; // Stop execution
                                 }

                                 Swal.fire({ title: 'Loading Payslip...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                                 $.ajax({
                                     url: 'api.php',
                                     method: 'GET',
                                     data: { action: 'get_payslip_details', payroll_detail_id: payrollDetailId },
                                     dataType: 'json',
                                     success: function(response) {
                                         Swal.close(); // Close loading spinner
                                         if (response.success && response.payslip_data) {
                                             const payslip = response.payslip_data;
                                             // Use PHP variable for logo URL
                                             const companyLogoUrl = '<?= $companyLogoUrl ?>'; // PHP variable here

                                             const payslipHtml = `
                                                 <div class="payslip-modal-content">
                                                     <div style="text-align: center; margin-bottom: 20px;">
                                                         ${companyLogoUrl && companyLogoUrl !== 'path/to/your/company_logo.png' ? `<img src="${companyLogoUrl}" alt="Company Logo" class="payslip-logo">` : ''}
                                                         <h4 style="color: var(--primary-dark); margin-bottom: 15px;">Payslip for ${escapeHtml(payslip.employee_name)} - ${moment().month(payslip.pay_period_month - 1).format('MMMM')} ${payslip.pay_period_year}</h4>
                                                     </div>
                                                     <div class="payslip-section-title">Employee Details</div>
                                                     <div class="payslip-row">
                                                         <span><strong>Employee ID:</strong> ${escapeHtml(payslip.employee_number)}</span>
                                                         <span><strong>Designation:</strong> ${escapeHtml(payslip.designation)}</span>
                                                         <span><strong>Department:</strong> ${escapeHtml(payslip.department)}</span>
                                                     </div>
                                                     <div class="payslip-row">
                                                         <span><strong>Date of Joining:</strong> ${escapeHtml(payslip.date_of_joining || 'N/A')}</span>
                                                         <span><strong>Bank IBAN:</strong> ${escapeHtml(payslip.bank_iban || 'N/A')}</span>
                                                     </div>

                                                     <div class="payslip-section-title">Earnings</div>
                                                     <table class="payslip-table">
                                                         <thead>
                                                             <tr>
                                                                 <th>Type</th>
                                                                 <th>Remarks</th> <th class="text-right">Amount</th>
                                                             </tr>
                                                         </thead>
                                                         <tbody>
                                                             ${payslip.earning_details.map(e => `
                                                                 <tr>
                                                                     <td>${escapeHtml(e.type)}</td>
                                                                     <td>${escapeHtml(e.remarks || 'N/A')}</td> <td class="text-right">${formatAmount(e.amount)} ${escapeHtml(payslip.currency || payslip.currency)}</td>
                                                                 </tr>
                                                             `).join('')}
                                                         </tbody>
                                                     </table>

                                                     ${payslip.reimbursement_claims && payslip.reimbursement_claims.length > 0 ? `
                                                     <div class="payslip-section-title">Reimbursement Details</div>
                                                     <table class="payslip-table">
                                                         <thead>
                                                             <tr>
                                                                 <th>Reimbursement</th>
                                                                 <th class="text-right">Amount</th>
                                                             </tr>
                                                         </thead>
                                                         <tbody>
                                                             ${payslip.reimbursement_claims.map(claim => `
                                                                 <tr>
                                                                     <td>${escapeHtml(claim.claim_title)}</td> <td class="text-right">${formatAmount(claim.total_amount)} ${escapeHtml(claim.currency || payslip.currency)}</td>
                                                                 </tr>
                                                             `).join('')}
                                                         </tbody>
                                                     </table>
                                                     ` : ''}
                                                     <div class="payslip-section-title">Deductions</div>
                                                     <table class="payslip-table">
                                                         <thead>
                                                             <tr>
                                                                 <th>Type</th>
                                                                 <th>Remarks</th> <th class="text-right">Amount</th>
                                                             </tr>
                                                         </thead>
                                                         <tbody>
                                                             ${payslip.deduction_details.map(d => `
                                                                 <tr>
                                                                     <td>${escapeHtml(d.type)}</td>
                                                                     <td>${escapeHtml(d.remarks || 'N/A')}</td> <td class="text-right">${formatAmount(d.amount)} ${escapeHtml(payslip.currency || payslip.currency)}</td>
                                                                 </tr>
                                                             `).join('')}
                                                         </tbody>
                                                     </table>

                                                     <div class="payslip-total"><strong>Total Deductions:</strong> <span>${formatAmount(payslip.total_deductions)} ${escapeHtml(payslip.currency)}</span></div>

                                                     <div class="payslip-final-net">
                                                         <strong>Net Pay:</strong> <span style="color: var(--success-color); font-size: 1.5em;">${formatAmount(payslip.net_pay)} ${escapeHtml(payslip.currency)}</span>
                                                     </div>
                                                 </div>
                                                 `;

                                             Swal.fire({
                                                 title: 'Payslip',
                                                 html: payslipHtml,
                                                 width: '800px',
                                                 showCloseButton: true,
                                                 showConfirmButton: true,
                                                 confirmButtonText: '<i data-lucide="download"></i> Download',
                                                 confirmButtonColor: 'var(--primary-color)',
                                                 customClass: { popup: 'swal2-popup-themed' },
                                                 didOpen: () => { if (typeof lucide !== 'undefined') { lucide.createIcons(); } }
                                             }).then((result) => {
                                                 if (result.isConfirmed) {
                                                     Swal.fire('Info', 'Payslip download feature coming soon!', 'info');
                                                 }
                                             });
                                         } else {
                                             Swal.fire('Error', response.message || 'Failed to retrieve payslip details.', 'error');
                                         }
                                     },
                                     error: function(jqXHR, textStatus, errorThrown) {
                                         handleAjaxError(jqXHR, textStatus, errorThrown, 'An error occurred loading payslip.');
                                     }
                                 });
                             });

                             // --- Approve Payslip Logic ---
                             // Delegated event listener for dynamically added buttons
                             $payrollMainDisplay.on('click', '.approve-payslip-btn', function() {
                                 const payrollDetailId = $(this).data('payroll-detail-id');
                                 Swal.fire({
                                     title: 'Approve Payslip?',
                                     text: "Are you sure you want to approve this payslip?",
                                     icon: 'question',
                                     showCancelButton: true,
                                     confirmButtonText: 'Yes, Approve!'
                                 }).then((result) => {
                                     if (result.isConfirmed) {
                                         showSpinner($(this), '.spinner'); // Use class for spinner
                                         $.ajax({
                                             url: 'api.php',
                                             method: 'POST',
                                             data: {
                                                 action: 'approve_payslip',
                                                 payslip_detail_id: payrollDetailId,
                                                 csrf_token: $('meta[name="csrf-token"]').attr('content')
                                             },
                                             dataType: 'json',
                                             success: function(response) {
                                                 if (response.success) {
                                                     Swal.fire('Approved!', response.message, 'success');
                                                     // Optimistic update for the current view
                                                     if (currentLoadedPayroll) {
                                                         const updatedDetails = currentLoadedPayroll.details.map(detail =>
                                                             detail.id === payrollDetailId ? { ...detail, status: 'Approved' } : detail
                                                         );
                                                         // Re-render the details with the updated status
                                                         displayPayrollDetails(currentLoadedPayroll.summary, updatedDetails, currentLoadedPayroll.month, currentLoadedPayroll.year, currentLoadedPayroll.isTestRun, currentLoadedPayroll.department_ids); // Pass department IDs
                                                     }
                                                     updatePayrollPeriodSelectors(); // Re-evaluate locking status for period dropdowns
                                                 } else {
                                                     Swal.fire('Error', response.message, 'error');
                                                 }
                                             },
                                             error: function(jqXHR, textStatus, errorThrown) {
                                                 handleAjaxError(jqXHR, textStatus, errorThrown);
                                             },
                                             complete: function() {
                                                 hideSpinner($(this), '.spinner'); // Hide spinner on the clicked button
                                             }
                                         });
                                     }
                                 });
                             });

                             // --- Delete Payroll Logic ---
                             // Delegated event listener for dynamically added buttons
                             $payrollMainDisplay.on('click', '.delete-payroll-btn', function() {
                                 const payrollId = $(this).data('id');
                                 Swal.fire({
                                     title: 'Are you sure?',
                                     text: "You are about to delete this entire pending payroll. This cannot be undone! This will also make any linked reimbursements available for processing again.",
                                     icon: 'warning',
                                     showCancelButton: true,
                                     confirmButtonColor: 'var(--danger-color)',
                                     cancelButtonColor: 'var(--text-light)',
                                     confirmButtonText: 'Yes, delete it!'
                                 }).then((result) => {
                                     if (result.isConfirmed) {
                                         showSpinner($(this), '.delete-payroll-btn .spinner');
                                         $.ajax({
                                             url: 'api.php',
                                             method: 'POST',
                                             data: {
                                                 action: 'delete_payroll',
                                                 payroll_id: payrollId,
                                                 csrf_token: $('meta[name="csrf-token"]').attr('content')
                                             },
                                             dataType: 'json',
                                             success: function(response) {
                                                 if (response.success) {
                                                     Swal.fire('Deleted!', response.message, 'success');
                                                     updatePayrollPeriodSelectors(); // Re-evaluate locking status
                                                     $viewPastPayrollsBtn.trigger('click'); // Refresh history
                                                 } else {
                                                     Swal.fire('Error', response.message, 'error');
                                                 }
                                             },
                                             error: function(jqXHR, textStatus, errorThrown) {
                                                 handleAjaxError(jqXHR, textStatus, errorThrown, 'An error occurred while deleting the payroll.');
                                             },
                                             complete: function() {
                                                 hideSpinner($(`.delete-payroll-btn[data-id="${payrollId}"]`), '.delete-payroll-btn .spinner');
                                             }
                                         });
                                     }
                                 });
                             });

                             // --- Regenerate Payroll Logic ---
                             // Delegated event listener for dynamically added buttons
                             $payrollMainDisplay.on('click', '.regenerate-payroll-btn', function() {
                                 const month = $(this).data('month');
                                 const year = $(this).data('year');
                                 Swal.fire({
                                     title: 'Regenerate Payroll?',
                                     text: "This will delete the current draft and create a new one with the latest employee data. Any linked reimbursements will also be unlinked and available for the new draft. Are you sure?",
                                     icon: 'question',
                                     showCancelButton: true,
                                     confirmButtonColor: 'var(--warning-color)',
                                     cancelButtonColor: 'var(--text-light)',
                                     confirmButtonText: 'Yes, regenerate!'
                                 }).then((result) => {
                                     if (result.isConfirmed) {
                                         showSpinner($(this), '.spinner'); // Use class for spinner
                                         $.ajax({
                                             url: 'api.php',
                                             method: 'POST',
                                             data: {
                                                 action: 'regenerate_payroll',
                                                 month: month,
                                                 year: year,
                                                 csrf_token: $('meta[name="csrf-token"]').attr('content')
                                             },
                                             dataType: 'json',
                                             success: function(response) {
                                                 if (response.success) {
                                                     Swal.fire('Regenerated!', response.message, 'success');
                                                     displayPayrollDetails(response.payroll_summary, response.payroll_details, month, year, false, currentLoadedPayroll ? currentLoadedPayroll.department_ids : null); // Pass department IDs
                                                     updatePayrollPeriodSelectors(); // Re-evaluate locking status
                                                 } else {
                                                     Swal.fire('Error', response.message || 'Failed to regenerate payroll.', 'error');
                                                 }
                                             },
                                             error: function(jqXHR, textStatus, errorThrown) {
                                                 handleAjaxError(jqXHR, textStatus, errorThrown, 'An error occurred while regenerating the payroll.');
                                             },
                                             complete: function() {
                                                 hideSpinner($(this), '.spinner'); // Hide spinner on the clicked button
                                             }
                                         });
                                     }
                                 });
                             });

                             // --- Placeholder Buttons for Reports ---
                             $('#reports-card').on('click', '#view-forecast-report-btn', () => Swal.fire('Info', 'Forecast report feature coming soon!', 'info'));
                             $('#reports-card').on('click', '#view-tax-report-btn', () => Swal.fire('Info', 'Tax report feature coming soon!', 'info'));
                             $('#reports-card').on('click', '#view-deductions-report-btn', () => Swal.fire('Info', 'Deductions report feature coming soon!', 'info'));

                             // --- View Details of Past Payroll ---
                             // Delegated event listener for dynamically added buttons
                             $payrollMainDisplay.on('click', '.view-past-details-btn', function() {
                                 const month = $(this).data('month');
                                 const year = $(this).data('year');
                                 Swal.fire({ title: 'Loading Payroll Details...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                                 $.ajax({
                                     url: 'api.php',
                                     method: 'GET',
                                     data: { action: 'get_payroll_details', month: month, year: year },
                                     dataType: 'json',
                                     success: function(response) {
                                         Swal.close();
                                         if (response.success && response.payroll_details) {
                                             displayPayrollDetails(response.payroll_summary, response.payroll_details, month, year, false, currentLoadedPayroll ? currentLoadedPayroll.department_ids : null); // Pass department IDs
                                             // Optional: Scroll to the details section if coming from history
                                             $('html, body').animate({ scrollTop: $('#payroll-details-section').offset().top - 80 }, 500);
                                         } else {
                                             Swal.fire('Info', response.message || 'No detailed payroll data found for this period.', 'info');
                                         }
                                     },
                                     error: function(jqXHR, textStatus, errorThrown) {
                                         handleAjaxError(jqXHR, textStatus, errorThrown);
                                     }
                                 });
                             });

                             // --- GLOBAL Refresh Button Event Listener (Delegated) ---
                             // This listener is attached once to a static parent and listens for clicks on the dynamic refresh button.
                             $payrollMainDisplay.on('click', '#refresh-payroll-details-btn', function() {
                                 if (currentLoadedPayroll) {
                                     showSpinner($(this), $(this).find('.spinner'));
                                     $.ajax({
                                         url: 'api.php',
                                         method: 'GET', // Use GET for re-fetching details
                                         data: {
                                             action: 'get_payroll_details',
                                             month: currentLoadedPayroll.month,
                                             year: currentLoadedPayroll.year,
                                             department_ids: currentLoadedPayroll.department_ids // Ensure department IDs are passed
                                         },
                                         dataType: 'json',
                                         success: function(response) {
                                             if (response.success && response.payroll_details) {
                                                 Swal.fire({
                                                     toast: true,
                                                     position: 'top-end',
                                                     showConfirmButton: false,
                                                     timer: 3000,
                                                     timerProgressBar: true,
                                                     icon: 'success',
                                                     title: 'Payroll data refreshed!'
                                                 });
                                                 // Re-render the details with the updated data
                                                 displayPayrollDetails(response.payroll_summary, response.payroll_details, currentLoadedPayroll.month, currentLoadedPayroll.year, currentLoadedPayroll.isTestRun, currentLoadedPayroll.department_ids);
                                             } else {
                                                 Swal.fire('Error', response.message || 'Failed to refresh payroll data.', 'error');
                                             }
                                         },
                                         error: function(jqXHR, textStatus, errorThrown) {
                                             handleAjaxError(jqXHR, textStatus, errorThrown, 'An error occurred during refresh.');
                                         },
                                         complete: function() {
                                             hideSpinner($('#refresh-payroll-details-btn'), $('#refresh-payroll-details-btn .spinner'));
                                         }
                                     });
                                 } else {
                                     Swal.fire('Info', 'No payroll is currently displayed to refresh.', 'info');
                                 }
                             });


                             // --- Initial Load ---
                             // Disable buttons initially until selectors are populated and validated
                             $testRunPayrollBtn.prop('disabled', true);
                             $generatePayrollBtn.prop('disabled', true);
                             $finalizePayrollBtn.prop('disabled', true);

                             updatePayrollPeriodSelectors().then(() => {
                                 // After selectors are populated, default to showing past payrolls, or welcome message
                                 $viewPastPayrollsBtn.trigger('click'); // Automatically load history
                             }).catch(error => {
                                 console.error("Initialization error:", error);
                                 // The handleAjaxError already displays a more detailed error, so this one might be redundant or could be simplified.
                                 // Swal.fire('Error', 'Failed to initialize payroll period selectors.', 'error'); // REMOVED redundant error display here
                             });

                         }); // End document ready
    </script>
</body>
</html>