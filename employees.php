<?php
/**
 * employees.php - Redesigned Employee Management Dashboard.
 *
 * This file has been redesigned for improved UI/UX, featuring a modern,
 * cleaner layout and multi-step, tabbed modals for better navigation.
 *
 * It still relies on 'api.php' for all backend data operations.
 */

// Enable error reporting for debugging. REMOVE OR DISABLE IN PRODUCTION!
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Authentication Check ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?error=login_required');
    exit;
}
// --- END Authentication Check ---

// Include configuration - defines $pdo connection if used globally, or constants
require_once 'config.php';

// Set Navbar variables for this page
$pageTitle = "Employee Management";
$pageIconClass = "users"; // Lucide icon for users/employees
// --- END OF PHP HEADER SECTION ---
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
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.1/dist/sweetalert2.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #6366f1;
            --secondary: #6b7280;
            --secondary-light: #9ca3af;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --card-bg: #ffffff;
            --bg-page: #f9fafb;
            --text-color: #1f2937;
            --text-light: #6b7280;
            --border-color: #e5e7eb;
            --border-radius: 0.5rem;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-page); color: var(--text-color); }
        .page-content { padding: 2rem; max-width: 1200px; margin: 0 auto; }
        .card { background: var(--card-bg); border-radius: var(--border-radius); box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); }
        .card-header { padding: 1.5rem; border-bottom: 1px solid var(--border-color); }
        .page-header h1 { font-size: 1.875rem; font-weight: 700; color: #1f2937; display: flex; align-items: center; gap: 0.75rem; }
        .page-header p { color: #6b7280; margin-top: 0.25rem; }
        
        .employee-dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .employee-dashboard-cards .card {
            text-align: center;
            padding: 1.5rem;
            transition: all 0.2s ease-in-out;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }
        .employee-dashboard-cards .card:hover { transform: translateY(-5px); box-shadow: var(--shadow-md); }
        .employee-dashboard-cards .card::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 5px;
            height: 100%;
            background-color: var(--primary);
        }
        .employee-dashboard-cards .card h4 { font-size: 1rem; color: var(--text-light); margin-top: 0; }
        .employee-dashboard-cards .card .count { font-size: 2.25rem; font-weight: 700; line-height: 1; color: var(--text-color); margin-bottom: 0.5rem; }
        .total-employees-card::before { background-color: var(--primary); }
        .active-employees-card::before { background-color: var(--success); }
        .on-leave-employees-card::before { background-color: var(--warning); }
        .resigned-employees-card::before { background-color: var(--secondary); }
        .terminated-employees-card::before { background-color: var(--danger); }
        .card.active-filter { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary); }

        .employee-filters { display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end; margin-bottom: 1.5rem; }
        .employee-filters .top-search-bar { position: relative; flex: 1 1 300px; }
        .employee-filters .top-search-bar input { width: 100%; padding-left: 2.5rem; }
        .employee-filters .top-search-bar .search-icon { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-light); }
        
        .filter-multiselect-container { width: 100%; }
        .filter-multiselect-button {
            width: 100%; display: flex; justify-content: space-between; align-items: center;
            padding: 0.625rem 1rem; border: 1px solid var(--border-color); border-radius: 0.375rem;
            background-color: var(--card-bg); cursor: pointer;
            box-shadow: var(--shadow-sm);
        }
        .filter-multiselect-dropdown {
            position: absolute; width: 100%; max-height: 200px; overflow-y: auto;
            background: var(--card-bg); border: 1.5px solid var(--border-color); border-radius: var(--border-radius);
            box-shadow: var(--shadow-md); z-index: 10; margin-top: 0.5rem; display: none;
            padding: 0.5rem;
        }
        .filter-multiselect-dropdown label { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; cursor: pointer; }
        .filter-multiselect-dropdown label:hover { background-color: var(--bg-page); }
        
        .table-container { overflow-x: auto; }
        #employees-table_wrapper { padding: 1rem; }
        #employees-table { width: 100% !important; border-collapse: collapse; }
        #employees-table th, #employees-table td { padding: 1rem; text-align: left; vertical-align: middle; }
        #employees-table thead th { font-weight: 600; color: var(--text-light); }
        #employees-table tbody tr:nth-child(even) { background-color: #f9fafb; }
        #employees-table .employee-list-photo { width: 30px; height: 30px; border-radius: 50%; object-fit: cover; }
        .dataTables_wrapper .btn-small { padding: 0.3rem; border-radius: 9999px; width: 2rem; height: 2rem; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s ease; }
        .dataTables_wrapper .btn-small:hover { opacity: 0.8; transform: translateY(-1px); }
        .dataTables_wrapper .btn-primary { background-color: var(--primary); color: white; }
        .dataTables_wrapper .btn-danger { background-color: var(--danger); color: white; }
        .dataTables_wrapper .btn-warning { background-color: var(--warning); color: white; }
        .dataTables_wrapper .btn-success { background-color: var(--success); color: white; }
        .dataTables_wrapper .btn-info { background-color: var(--info); color: white; }
        
        /* Updated styles for modal tabs and header */
        .swal-tab-button.completed {
            color: var(--primary);
        }
        .swal-tab-button .swal-tab-icon {
            color: var(--secondary-light);
        }
        .swal-tab-button.completed .swal-tab-icon {
            color: var(--success);
        }

        .swal2-popup-themed {
            font-family: 'Inter', sans-serif;
            background-color: #f9fafb;
        }
        .swal2-title { font-size: 1.5rem; font-weight: 700; color: var(--text-color); }
        .swal2-content { padding: 0 !important; }
        
        .swal-tabs-container {
            display: flex;
            justify-content: center;
            border-bottom: 1px solid var(--border-color);
            padding: 0 1rem;
            overflow-x: auto;
            white-space: nowrap;
        }
        .swal-tab-button {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 2rem;
            font-size: 1.1rem;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            font-weight: 500;
            color: var(--text-light);
            transition: all 0.2s ease;
        }

        .swal-tab-button.active {
            color: var(--primary);
            border-color: var(--primary);
        }
        .swal-tab-button:hover {
            color: var(--primary);
        }
        .swal-tab-icon {
            width: 24px;
            height: 24px;
            margin-right: 0.75rem;
            color: #d1d5db;
            transition: color 0.2s ease;
        }
        .swal-tab-icon.completed {
            color: var(--success);
        }
        
        .modal-form-header {
            padding: 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            background-color: #f0f8ff; /* Light Sky Blue */
            border-bottom: 1px solid var(--border-color);
        }
        .modal-form-header .profile-photo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 2px solid #87ceeb;
            object-fit: cover;
        }
        .modal-form-header h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        .modal-form-header p {
            font-size: 0.875rem;
            color: var(--text-light);
        }
        .modal-content-area {
            padding: 2rem;
            overflow-y: auto;
            max-height: 60vh;
        }

        .swal2-popup.swal2-modal {
            width: 95% !important;
            max-width: 1200px !important; /* Adjusted for better visibility */
            /* New rule to fix the height */
            height: auto !important;
            max-height: 90vh !important;
        }
        
        .quick-note-form {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            padding: 0 1rem;
        }
        .quick-note-form .form-label {
            display: block;
            margin-top: 1rem;
            margin-bottom: 0.25rem;
            font-weight: 500;
            color: var(--text-color);
        }
        .quick-note-form .form-control {
            width: 100%;
            box-sizing: border-box;
            margin-bottom: 0.5rem;
        }
        .quick-note-form .swal2-textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        /* Centered modal actions */
        .form-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            padding: 1rem;
            border-top: 1px solid var(--border-color);
        }
        
        /* View-only modal-specific styles */
        .read-only-field {
            padding: 0.625rem 1rem;
            background-color: #e5e7eb;
            border-radius: 0.375rem;
            color: #4b5563;
            font-weight: 500;
        }
        .view-field-label {
            font-weight: 500;
            color: #4b5563;
            margin-bottom: 0.25rem;
        }

        /* Document Upload section */
        .document-dropzone {
            border: 2px dashed #6ca4bc;
            border-radius: 0.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        .document-dropzone.dragover {
            background-color: #e0f2fe;
            border-color: var(--primary);
        }
        .document-folder-icon {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100px;
            height: 100px;
            background-color: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            position: relative;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .document-folder-icon:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        .document-folder-icon i[data-lucide] {
            width: 48px;
            height: 48px;
            color: var(--primary);
        }
        .document-folder-icon span {
            font-size: 0.75rem;
            text-align: center;
            margin-top: 0.5rem;
            word-break: break-all;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .document-folder-icon .remove-doc-btn {
            position: absolute;
            top: -10px;
            right: -10px;
            padding: 0;
            background-color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e2e8f0;
            box-shadow: var(--shadow-sm);
        }
        .btn-modal-primary {
            background-color: #0ea5e9; /* sky-500 */
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
        }
        .btn-modal-primary:hover {
            background-color: #0284c7; /* sky-600 */
        }
        .btn-modal-secondary {
            background-color: #6b7280; /* gray-500 */
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
        }
        .btn-modal-secondary:hover {
            background-color: #4b5563; /* gray-600 */
        }
        /* Two-column form layout */
        .form-row-2-col {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        /* Style for profile photo upload */
        .profile-photo-upload-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 2px dashed var(--border-color);
            cursor: pointer;
            overflow: hidden;
            position: relative;
            margin: 0 auto 1rem;
        }
        .profile-photo-upload-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .profile-photo-upload-wrapper:hover img {
            transform: scale(1.05);
        }
        .profile-photo-upload-wrapper input[type="file"] {
            opacity: 0;
            position: absolute;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        @media (max-width: 1024px) {
            .swal-tabs-container {
                overflow-x: auto;
                white-space: nowrap;
            }
            .swal-tab-button {
                padding: 1rem;
            }
            .modal-form-header {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
            .form-row-2-col {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="page-content">
    <div class="page-header flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
        <h1><i data-lucide="<?= htmlspecialchars($pageIconClass); ?>"></i> <?= htmlspecialchars($pageTitle); ?></h1>
        <p class="page-subtitle mt-2 sm:mt-0">Manage and maintain all employee records and employment statuses.</p>
    </div>

    <div class="employee-dashboard-cards">
        <div class="card total-employees-card" data-filter-status="">
            <div class="count" id="total-employees-count">0</div>
            <h4>Total Employees</h4>
        </div>
        <div class="card active-employees-card" data-filter-status="active">
            <div class="count" id="active-employees-count">0</div>
            <h4>Active Employees</h4>
        </div>
        <div class="card on-leave-employees-card" data-filter-status="on_leave">
            <div class="count" id="on-leave-employees-count">0</div>
            <h4>On Leave</h4>
        </div>
        <div class="card resigned-employees-card" data-filter-status="resigned">
            <div class="count" id="resigned-employees-count">0</div>
            <h4>Resigned</h4>
        </div>
        <div class="card terminated-employees-card" data-filter-status="terminated">
            <div class="count" id="terminated-employees-count">0</div>
            <h4>Terminated</h4>
        </div>
    </div>

    <div class="employee-filters">
        <div class="top-search-bar">
            <input type="text" id="search-employee-name" class="form-control" placeholder="Search employee (name, ID, designation)..." aria-label="Search employees">
            <i data-lucide="search" class="search-icon"></i>
        </div>

        <div class="form-group flex-1">
            <label class="form-label">Filter by Department</label>
            <div class="filter-multiselect-container">
                <button type="button" class="filter-multiselect-button" id="department-filter-toggle">
                    <span id="selected-departments-text">All Departments</span>
                    <i data-lucide="chevron-down"></i>
                </button>
                <div class="filter-multiselect-dropdown" id="department-filter-dropdown">
                    <label><input type="checkbox" value="all" class="filter-department-checkbox" checked> All Departments</label>
                </div>
            </div>
        </div>

        <div class="form-group flex-1">
            <label class="form-label">Filter by Status</label>
            <div class="filter-multiselect-container">
                <button type="button" class="filter-multiselect-button" id="status-filter-toggle">
                    <span id="selected-statuses-text">All Statuses</span>
                    <i data-lucide="chevron-down"></i>
                </button>
                <div class="filter-multiselect-dropdown" id="status-filter-dropdown">
                    <label><input type="checkbox" value="all" class="filter-status-checkbox" checked> All Statuses</label>
                    <label><input type="checkbox" value="active" class="filter-status-checkbox"> Active</label>
                    <label><input type="checkbox" value="on_leave" class="filter-status-checkbox"> On Leave</label>
                    <label><input type="checkbox" value="resigned" class="filter-status-checkbox"> Resigned</label>
                    <label><input type="checkbox" value="terminated" class="filter-status-checkbox"> Terminated</label>
                    <label><input type="checkbox" value="probation" class="filter-status-checkbox"> On Probation</label>
                </div>
            </div>
        </div>

        <div class="flex gap-2">
            <button class="btn btn-success" id="add-employee-btn">
                <i data-lucide="user-plus"></i> Add New
            </button>
            <button class="btn btn-info" id="employee-reports-btn">
                <i data-lucide="bar-chart-2"></i> Reports
            </button>
        </div>
    </div>

    <div class="card employee-list-card">
        <div class="card-header flex justify-between items-center">
            <h3 class="section-title text-xl font-semibold flex items-center gap-2"><i data-lucide="clipboard-list"></i> Employee List</h3>
        </div>

        <div class="p-4 bg-gray-100 rounded-b-lg flex flex-wrap items-center gap-3 transition-all duration-300 ease-in-out" id="employee-bulk-actions" style="display: none;">
            <span class="text-sm text-gray-600 font-semibold flex-shrink-0">
                <span id="bulk-selected-count">0</span> employee(s) selected
            </span>
            <button class="btn btn-primary bg-indigo-600 text-white rounded-md px-4 py-2 text-sm flex items-center gap-2 hover:bg-indigo-700" id="quick-edit-selected-btn-action">
                <i data-lucide="edit"></i> Quick Edit
            </button>
            <button class="btn btn-primary bg-sky-500 text-white rounded-md px-4 py-2 text-sm flex items-center gap-2 hover:bg-sky-600" id="bulk-change-status-btn-action">
                <i data-lucide="shuffle"></i> Change Status
            </button>
            <button class="btn btn-warning text-white rounded-md px-4 py-2 text-sm flex items-center gap-2 hover:bg-yellow-600" id="bulk-add-quick-note">
                <i data-lucide="plus"></i> Quick Note
            </button>
            <button class="btn btn-danger bg-red-600 text-white rounded-md px-4 py-2 text-sm flex items-center gap-2 hover:bg-red-700" id="bulk-delete-employees-btn-action">
                <i data-lucide="trash-2"></i> Delete
            </button>
            <button class="btn btn-secondary bg-gray-500 text-white rounded-md px-4 py-2 text-sm flex items-center gap-2 hover:bg-gray-600" id="bulk-add-payment-btn">
                <i data-lucide="wallet"></i> Add Payment
            </button>
            <button class="btn btn-secondary bg-gray-500 text-white rounded-md px-4 py-2 text-sm flex items-center gap-2 hover:bg-gray-600" id="bulk-add-deduction-btn">
                <i data-lucide="minus-circle"></i> Add Deduction
            </button>
            <button class="btn btn-outline-primary border border-indigo-600 text-indigo-600 rounded-md px-4 py-2 text-sm flex items-center gap-2 hover:bg-indigo-50" id="export-csv-btn">
                <i data-lucide="download"></i> Export CSV
            </button>
            <button class="btn btn-outline-success border border-green-600 text-green-600 rounded-md px-4 py-2 text-sm flex items-center gap-2 hover:bg-green-50" id="export-excel-btn">
                <i data-lucide="file-spreadsheet"></i> Export Excel
            </button>
        </div>

        <div class="table-container p-4">
            <table id="employees-table" class="display responsive nowrap w-full text-sm">
                <thead>
                    <tr>
                        <th class="p-2"><input type="checkbox" id="select-all-employees" class="employee-select-checkbox"></th>
                        <th class="p-2">Photo</th>
                        <th class="p-2">Emp. No.</th>
                        <th class="p-2">Name</th>
                        <th class="p-2">Designation</th>
                        <th class="p-2">Department</th>
                        <th class="p-2">Status</th>
                        <th class="p-2">Date of Joining</th>
                        <th class="p-2">Basic Salary</th>
                        <th class="p-2">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            <div id="no-employees-message" class="no-results text-center py-8 text-lg text-gray-500" style="display: none;">No employees found matching your criteria.</div>
        </div>
        <div id="filtered-salary-total-container" class="p-4 bg-gray-100 rounded-b-lg text-right text-sm font-semibold text-gray-700" style="display: none;">
            <span id="filtered-salary-total-label">Total Salary (Filtered):</span>
            <span id="filtered-salary-total-value" class="text-indigo-600 ml-2">0.00</span>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.1/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>

<script>
$(document).ready(function() {
    // console.log("DOM ready. Initializing employees.php JS.");

    // Initial call to render icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
        // console.log("Initial Lucide icons rendered.");
    } else {
        console.error("Lucide icons library not loaded.");
    }

    // --- Caching UI elements and defining global variables FIRST ---
    const $searchNameInput = $('#search-employee-name');
    const $addEmployeeBtn = $('#add-employee-btn');
    const $employeeReportsBtn = $('#employee-reports-btn');

    const $employeesTableBody = $('#employees-table tbody');
    const $noEmployeesMessage = $('#no-employees-message');

    const $dashboardCards = $('.employee-dashboard-cards .card');
    const $totalEmployeesCount = $('#total-employees-count');
    const $activeEmployeesCount = $('#active-employees-count');
    const $onLeaveEmployeesCount = $('#on-leave-employees-count');
    const $resignedEmployeesCount = $('#resigned-employees-count');
    const $terminatedEmployeesCount = $('#terminated-employees-count');

    const $filteredSalaryTotalContainer = $('#filtered-salary-total-container');
    const $filteredSalaryTotalValue = $('#filtered-salary-total-value');

    const $employeeBulkActionsContainer = $('#employee-bulk-actions');
    const $bulkSelectedCount = $('#bulk-selected-count');
    const $selectAllCheckbox = $('#select-all-employees');

    let employeesDataTable;
    let currentSelectedDepartments = ['all'];
    let currentSelectedStatuses = ['all'];
    let currentSearchQuery = '';

    let selectedReportType = '';
    let reportChartInstance = null;
    
    // Global list of countries for dropdowns
    const countries = [
        { code: "US", name: "United States" }, { code: "UK", name: "United Kingdom" },
        { code: "CA", name: "Canada" }, { code: "AU", name: "Australia" },
        { code: "PK", name: "Pakistan" }, { code: "IN", name: "India" },
        { code: "DE", name: "Germany" }, { code: "FR", name: "France" },
        { code: "JP", name: "Japan" }, { code: "BR", name: "Brazil" },
        { code: "AE", name: "United Arab Emirates" },
    ];
    countries.sort(function(a, b) { return a.name.localeCompare(b.name); });
    
    // --- All helper function declarations ---
    function showSpinner(button, spinnerElement) {
        const $button = $(button);
        const $spinner = $button.find(spinnerElement);
        $spinner.css('display', 'inline-block');
        $button.prop('disabled', true);
    }

    function hideSpinner(button, spinnerElement) {
        const $button = $(button);
        const $spinner = $button.find(spinnerElement);
        $spinner.hide();
        $button.prop('disabled', false);
    }

    function escapeHtml(unsafe) {
        if (typeof unsafe !== 'string' && typeof unsafe !== 'number') return unsafe;
        return String(unsafe).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    function escapeJsString(unsafe) {
        if (typeof unsafe !== 'string') return unsafe;
        return unsafe.replace(/\\/g, "\\\\").replace(/'/g, "\\'").replace(/"/g, "\\\"").replace(/\n/g, "\\n").replace(/\r/g, "\\r").replace(/`/g, "\\`");
    }

    function handleAjaxError(jqXHR, textStatus, errorThrown, context = 'Operation') {
        let message = `${context} failed.`;
        let details = '';
        try {
            const response = jqXHR.responseJSON || JSON.parse(jqXHR.responseText);
            details = response.message || JSON.stringify(response.errors || errorThrown);
        } catch (e) {
            details = jqXHR.responseText?.substring(0, 500) || errorThrown;
            if (details.startsWith('<br /><b>')) {
                message = 'Server Error: Unexpected HTML response. Check PHP error logs for full details.';
            }
        }
        console.error(`AJAX Error (${jqXHR.status}):`, { context, details, request: jqXHR.config?.url, payload: jqXHR.config?.data });
        Swal.fire({ title: 'Error', html: `<p>${message}</p><small class="text-muted">${details}</small>`, icon: 'error' });
    }

    function populateCountryDropdown($selectElement, selectedCountryCode = '') {
        $selectElement.empty();
        $selectElement.append($('<option>', { value: "", text: "Select country", disabled: true, selected: true }));
        countries.forEach(function(country) {
            $selectElement.append($('<option>', {
                value: country.code,
                text: country.name,
                selected: country.code === selectedCountryCode
            }));
        });
    }

    function getFileIcon(filePath) {
        const ext = filePath.split('.').pop().toLowerCase();
        switch (ext) { case 'pdf': return 'file-text'; case 'doc': case 'docx': return 'file-text'; case 'xls': case 'xlsx': return 'file-spreadsheet'; case 'jpg': case 'jpeg': case 'png': case 'gif': case 'webp': return 'image'; default: return 'file'; }
    }

    function updateMultiSelectText(filterType) {
        const $toggleButton = $(`#${filterType}-filter-toggle`);
        const $dropdown = $(`#${filterType}-filter-dropdown`);
        const $checkboxes = $dropdown.find(`.filter-${filterType}-checkbox:checked`);
        const $allCheckbox = $dropdown.find(`.filter-${filterType}-checkbox[value="all"]`);
        const $textSpan = $(`#selected-${filterType}s-text`);

        let selectedValues = [];
        $checkboxes.each(function() { selectedValues.push($(this).val()); });

        if (selectedValues.includes('all') || selectedValues.length === 0) {
            $textSpan.text(`All ${filterType.charAt(0).toUpperCase() + filterType.slice(1)}s`);
        } else if (selectedValues.length === 1) {
            $textSpan.text($checkboxes.parent().text().trim());
        } else {
            $textSpan.text(`${selectedValues.length} ${filterType.charAt(0).toUpperCase() + filterType.slice(1)}s selected`);
        }

        if (filterType === 'department') { currentSelectedDepartments = selectedValues; }
        else if (filterType === 'status') { currentSelectedStatuses = selectedValues; }
    }

    function syncFilterCheckboxes(filterType, selectedArray) {
        const $dropdown = $(`#${filterType}-filter-dropdown`);
        const $allCheckbox = $dropdown.find(`.filter-${filterType}-checkbox[value="all"]`);
        const $individualCheckboxes = $dropdown.find(`.filter-${filterType}-checkbox:not([value="all"])`);

        $individualCheckboxes.prop('checked', false).parent().removeClass('selected');
        $allCheckbox.prop('checked', false).parent().removeClass('selected');

        if (selectedArray.includes('all') || selectedArray.length === 0) {
            $allCheckbox.prop('checked', true).parent().addClass('selected');
        } else {
            selectedArray.forEach(function(val) {
                $dropdown.find(`.filter-${filterType}-checkbox[value="${val}"]`).prop('checked', true).parent().addClass('selected');
            });
        }
        updateMultiSelectText(filterType);
    }

    function getSelectedEmployeeIds() {
        if (!employeesDataTable) { return []; }
        const selectedIds = [];
        employeesDataTable.rows({ search: 'applied' }).nodes().to$().find('.employee-select-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });
        return selectedIds;
    }

    function updateBulkActionsVisibility() {
        const selectedCount = getSelectedEmployeeIds().length;
        if (selectedCount > 0) {
            $bulkSelectedCount.text(selectedCount);
            $employeeBulkActionsContainer.show();
        } else {
            $employeeBulkActionsContainer.hide();
        }
    }

    function updateSelectAllCheckbox() {
        if (!employeesDataTable) {
            $selectAllCheckbox.prop('checked', false).prop('indeterminate', false);
        } else {
            const visibleCheckboxes = employeesDataTable.rows({ search: 'applied' }).nodes().to$().find('.employee-select-checkbox:not(:disabled)');
            const checkedVisibleCheckboxes = visibleCheckboxes.filter(':checked');
            if (visibleCheckboxes.length === 0) {
                $selectAllCheckbox.prop('checked', false).prop('indeterminate', false);
            } else if (checkedVisibleCheckboxes.length === visibleCheckboxes.length) {
                $selectAllCheckbox.prop('checked', true).prop('indeterminate', false);
            } else if (checkedVisibleCheckboxes.length > 0) {
                $selectAllCheckbox.prop('checked', false).prop('indeterminate', true);
            } else {
                $selectAllCheckbox.prop('checked', false).prop('indeterminate', false);
            }
        }
    }

    function updateEmployeeCounts(counts) {
        $totalEmployeesCount.text(counts.total || 0);
        $activeEmployeesCount.text(counts.active || 0);
        $onLeaveEmployeesCount.text(counts.on_leave || 0);
        $resignedEmployeesCount.text(counts.resigned || 0);
        $terminatedEmployeesCount.text(counts.terminated || 0);

        // Highlight the active card based on the current filter
        $dashboardCards.removeClass('ring-2 ring-indigo-500 ring-offset-2');
        if (currentSelectedStatuses.length === 1 && currentSelectedStatuses[0] !== 'all') {
            const activeFilter = currentSelectedStatuses[0];
            $(`.employee-dashboard-cards .card[data-filter-status="${activeFilter}"]`).addClass('ring-2 ring-indigo-500 ring-offset-2');
        } else if (currentSelectedStatuses.length > 1) {
            $(`.employee-dashboard-cards .card[data-filter-status=""]`).addClass('ring-2 ring-indigo-500 ring-offset-2');
        }
    }

    function updateTotalSalary() {
        // console.log("Calculating total salary...");
        if (employeesDataTable && employeesDataTable.rows().count() > 0) {
            let totalSalary = 0;
            let firstCurrencySymbol = '';
            employeesDataTable.rows({ search: 'applied' }).data().each(function(rowData) {
                const basicSalaryString = rowData[8];
                const parts = basicSalaryString.split(' ');
                const basicSalary = parseFloat(parts[0].replace(/,/g, ''));
                const currentCurrencySymbol = parts[1] || 'PKR';
                if (!isNaN(basicSalary)) {
                    totalSalary += basicSalary;
                    if (!firstCurrencySymbol && currentCurrencySymbol) {
                        firstCurrencySymbol = currentCurrencySymbol;
                    }
                }
            });
            $filteredSalaryTotalValue.text(`${totalSalary.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${firstCurrencySymbol || 'PKR'}`);
            $filteredSalaryTotalContainer.show();
            // console.log("Total salary updated:", totalSalary, firstCurrencySymbol);
        } else {
            $filteredSalaryTotalContainer.hide();
            // console.log("No employees in table or DataTable not initialized, hiding total salary.");
        }
    }
    
    // --- Primary functions that handle user interactions ---

    function populateDepartmentsFilterAndDropdowns(selectedDepartmentId = null) {
        // console.log("Populating departments...");
        $.ajax({
            url: 'api.php',
            method: 'GET',
            data: { action: 'get_departments' },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.departments) {
                    const $deptFilterDropdown = $('#department-filter-dropdown');
                    $deptFilterDropdown.find('label:not(:first-child)').remove();
                    response.departments.forEach(function(dept) {
                        $deptFilterDropdown.append(`
                            <label><input type="checkbox" value="${dept.id}" class="filter-department-checkbox"> ${escapeHtml(dept.department_name)}</label>
                        `);
                    });
                    updateMultiSelectText('department');
                    syncFilterCheckboxes('department', currentSelectedDepartments);
                    // Also update the select dropdown in the modal
                    const $modalDeptSelects = $('.employee-form-main-content #department_id');
                    $modalDeptSelects.empty();
                    $modalDeptSelects.append($('<option>', { value: "", text: "Select Department", disabled: true, selected: true }));
                    response.departments.forEach(function(dept) {
                        $modalDeptSelects.append($('<option>', { value: dept.id, text: dept.department_name, selected: dept.id == selectedDepartmentId }));
                    });
                    // console.log("Departments populated successfully.");
                } else {
                    console.error('Failed to load departments:', response.message);
                    $('#department-filter-dropdown').empty().html('<label class="text-red-500">Error loading departments</label>');
                    Swal.fire('Error', response.message || 'Failed to load department list.', 'error');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error loading departments:', textStatus, errorThrown, jqXHR.responseText);
                handleAjaxError(jqXHR, textStatus, errorThrown, 'Failed to load departments');
            }
        });
    }

    function loadEmployees(filters = {}) {
        // console.log("Loading employees with filters:", filters);
        $noEmployeesMessage.hide();

        const isInitialLoad = (filters.name === undefined && filters.department_id === undefined && filters.status === undefined);
        if (isInitialLoad) {
            Swal.fire({
                title: 'Loading Employees...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: function() { Swal.showLoading(); }
            });
        }

        let department_ids_to_send = (filters.department_id !== undefined) ? filters.department_id : currentSelectedDepartments;
        let status_to_send = (filters.status !== undefined) ? filters.status : currentSelectedStatuses;

        if (Array.isArray(department_ids_to_send) && department_ids_to_send.includes('all') && department_ids_to_send.length === 1) { department_ids_to_send = 'all'; }
        if (Array.isArray(status_to_send) && status_to_send.includes('all') && status_to_send.length === 1) { status_to_send = 'all'; }
        if (Array.isArray(department_ids_to_send) && department_ids_to_send.length === 0) { department_ids_to_send = 'all'; }
        if (Array.isArray(status_to_send) && status_to_send.length === 0) { status_to_send = 'all'; }

        let dataToSend = {
            action: 'get_employees',
            name: filters.name || '',
            department_id: JSON.stringify(department_ids_to_send),
            status: JSON.stringify(status_to_send)
        };

        $.ajax({
            url: 'api.php',
            method: 'GET',
            data: dataToSend,
            dataType: 'json',
            success: function(response) {
                if (isInitialLoad) { Swal.close(); }
                if ($.fn.DataTable.isDataTable('#employees-table')) { employeesDataTable.destroy(); // console.log("Destroyed existing DataTable instance."); 
                }
                $employeesTableBody.empty();

                if (response.success && response.employees && response.employees.length > 0) {
                    const dataForDataTable = response.employees.map(function(emp) {
                         // Corrected the string concatenation for the action buttons
                         const actionsHtml = `
                             <div class="flex gap-1 items-center">
                                 <button class="btn btn-small btn-info view-employee-profile-btn" data-id="${emp.id}" title="View"><i data-lucide="eye"></i></button>
                                 <button class="btn btn-small btn-success manage-finances-btn" data-id="${emp.id}" title="Manage Finances"><i data-lucide="wallet"></i></button>
                                 <button class="btn btn-small btn-primary edit-employee-btn" data-id="${emp.id}" title="Edit"><i data-lucide="edit"></i></button>
                                 <button class="btn btn-small btn-danger delete-employee-btn" data-id="${emp.id}" data-name="${escapeJsString(emp.full_name)}" title="Delete"><i data-lucide="trash-2"></i></button>
                             </div>
                         `;
                         return [
                             `<input type="checkbox" class="employee-select-checkbox" value="${emp.id}">`,
                             `<img src="${escapeHtml(emp.avatar_url || 'https://placehold.co/30x30/87CEEB/ffffff?text=EMP')}" alt="Photo" class="employee-list-photo" onerror="this.src='https://placehold.co/30x30/87CEEB/ffffff?text=EMP';">`,
                             escapeHtml(emp.employee_number || 'N/A'),
                             escapeHtml(emp.full_name),
                             escapeHtml(emp.designation || 'N/A'),
                             escapeHtml(emp.department_name || 'N/A'),
                             `<span class="status-badge status-${emp.employee_status.toLowerCase().replace('_', '-')}">${escapeHtml(emp.employee_status)}</span>`,
                             escapeHtml(emp.date_of_joining || 'N/A'),
                             `${parseFloat(emp.basic_salary).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${escapeHtml(emp.currency || 'PKR')}`,
                             actionsHtml
                         ]; 
                     });
                     employeesDataTable = $('#employees-table').DataTable({
                         "data": dataForDataTable,
                         "paging": true, "lengthMenu": [10, 25, 50, 100], "pageLength": 25, "ordering": true, "info": true, "searching": true, "autoWidth": false, "responsive": true, "dom": 'rt<"bottom flex justify-between items-center mt-4"lip><"clear">',
                         "columnDefs": [ { "orderable": false, "targets": [0, 1, 9] } ],
                         "order": [[3, 'asc']],
                         "language": { "emptyTable": "No employees found matching your criteria." },
                         "createdRow": function (row) { if (typeof lucide !== 'undefined') { lucide.createIcons({nodes: row.getElementsByTagName('i')}); } const $rowCheckbox = $(row).find('.employee-select-checkbox'); if ($selectAllCheckbox.prop('checked')) { $rowCheckbox.prop('checked', true); } },
                         "initComplete": function() { updateBulkActionsVisibility(); updateSelectAllCheckbox(); lucide.createIcons(); },
                         "drawCallback": function() { lucide.createIcons(); }
                     });
                     // console.log("DataTable initialized with data.");
                     employeesDataTable.off('draw.dt.salaryTotal').on('draw.dt.salaryTotal', function() { updateTotalSalary(); updateBulkActionsVisibility(); updateSelectAllCheckbox(); // console.log("DataTable drawn, updating total salary and bulk actions."); 
                    });
                     updateTotalSalary();
                 } else {
                     $noEmployeesMessage.text(response.message || 'No employees found.').show();
                     // console.log("No employees found or response not successful.");
                     if ($.fn.DataTable.isDataTable('#employees-table')) { employeesDataTable.destroy(); }
                     $employeesTableBody.empty();
                     $filteredSalaryTotalContainer.hide();
                     updateBulkActionsVisibility();
                 }
                 updateEmployeeCounts(response.counts || {});
                 updateBulkActionsVisibility();
             },
             error: function(jqXHR, textStatus, errorThrown) {
                 if (isInitialLoad) { Swal.close(); }
                 console.error("AJAX Error in loadEmployees:", { status: jqXHR.status, textStatus: textStatus, errorThrown: errorThrown, responseText: jqXHR.responseText });
                 handleAjaxError(jqXHR, textStatus, errorThrown, 'Failed to load employee data.');
                 $noEmployeesMessage.text('Error loading employees.').show();
                 if ($.fn.DataTable.isDataTable('#employees-table')) { employeesDataTable.destroy(); }
                 $employeesTableBody.empty();
                 $filteredSalaryTotalContainer.hide();
                 updateBulkActionsVisibility();
             }
         });
     }

     // --- Start of Re-added Bulk Action and other missing functions ---

     function bulkDeleteEmployees(selectedIds) {
         Swal.fire({
             title: 'Are you sure?',
             html: `You are about to permanently delete <strong>${selectedIds.length}</strong> employee(s). This action cannot be undone!`,
             icon: 'warning',
             showCancelButton: true,
             confirmButtonColor: '#d33',
             cancelButtonColor: '#aaa',
             confirmButtonText: 'Yes, delete them!'
         }).then(function(result) {
             if (result.isConfirmed) {
                 Swal.fire({ title: 'Deleting...', allowOutsideClick: false, didOpen: function() { Swal.showLoading(); } });
                 $.ajax({
                     url: 'api.php',
                     method: 'POST',
                     data: { action: 'bulk_delete_employees', employee_ids: JSON.stringify(selectedIds), csrf_token: $('meta[name="csrf-token"]').attr('content') },
                     dataType: 'json',
                     success: function(response) {
                         if (response.success) {
                             Swal.fire('Deleted!', response.message, 'success');
                             loadEmployees();
                         } else {
                             Swal.fire('Error', response.message || 'Failed to delete employees.', 'error');
                         }
                     },
                     error: function(jqXHR, textStatus, errorThrown) {
                         handleAjaxError(jqXHR, textStatus, errorThrown, 'Bulk delete employees');
                     }
                 });
             }
         });
     }

     function showBulkAddPaymentModal(selectedIds) {
         $.ajax({
             url: 'api.php?action=get_financial_transaction_types',
             method: 'GET',
             dataType: 'json',
             success: function(response) {
                 if (!response.success) {
                     Swal.fire('Error', response.message || 'Failed to load payment types.', 'error');
                     return;
                 }
                 const paymentTypes = response.types.earning.map(function(type) { return `<option value="${escapeHtml(type)}">${escapeHtml(type)}</option>`; }).join('');
                 Swal.fire({
                     title: `Add Payment for ${selectedIds.length} Employee(s)`,
                     html: `
                         <form id="bulk-payment-form" class="swal-form">
                             <input type="hidden" name="employee_ids" value="${escapeHtml(JSON.stringify(selectedIds))}">
                             <div class="form-group">
                                 <label for="payment-type">Payment Type</label>
                                 <select id="payment-type" name="payment_type" class="form-control">${paymentTypes}</select>
                             </div>
                             <div class="form-group">
                                 <input type="number" id="payment-amount" name="payment_amount" placeholder="Amount" class="form-control" step="0.01" required min="0">
                             </div>
                             <div class="form-group">
                                 <textarea id="remarks" name="remarks" class="form-control" placeholder="Remarks" rows="3" required></textarea>
                             </div>
                             <div class="form-group">
                                 <input type="date" id="payment-date" name="payment_date" placeholder="Payment Date" class="form-control datepicker" required>
                             </div>
                         </form>
                     `,
                     showCancelButton: true,
                     confirmButtonText: 'Submit Payment',
                     didOpen: function() {
                         $('#payment-date').flatpickr({ dateFormat: "Y-m-d", allowInput: true });
                     },
                     preConfirm: function() {
                         const form = $('#bulk-payment-form')[0];
                         if (!form.checkValidity()) {
                             form.reportValidity();
                             return false;
                         }
                         const formData = new FormData(form);
                         formData.append('action', 'bulk_add_payment');
                         formData.append('csrf_token', $('meta[name="csrf-token"]').attr('content'));

                         Swal.showLoading();
                         return $.ajax({
                             url: 'api.php',
                             type: 'POST',
                             data: formData,
                             processData: false,
                             contentType: false
                         }).fail(function(jqXHR) { return Swal.showValidationMessage(jqXHR.responseJSON?.message || 'Request failed.'); });
                     }
                 }).then(function(result) {
                     if (result.isConfirmed && result.value.success) {
                         Swal.fire('Success!', result.value.message, 'success');
                         loadEmployees();
                     } else if (result.isConfirmed) {
                         Swal.fire('Error', result.value.message || 'Failed to add payment.', 'error');
                     }
                 });
             },
             error: function(jqXHR, textStatus, errorThrown) {
                 handleAjaxError(jqXHR, textStatus, errorThrown, 'Loading payment types');
             }
         });
     }

     function showBulkAddDeductionModal(selectedIds) {
         $.ajax({
             url: 'api.php?action=get_financial_transaction_types',
             method: 'GET',
             dataType: 'json',
             success: function(response) {
                 if (!response.success) {
                     Swal.fire('Error', response.message || 'Failed to load deduction types.', 'error');
                     return;
                 }
                 const deductionTypes = response.types.deduction.map(function(type) { return `<option value="${escapeHtml(type)}">${escapeHtml(type)}</option>`; }).join('');
                 Swal.fire({
                     title: `Add Deduction for ${selectedIds.length} Employee(s)`,
                     html: `
                         <form id="bulk-deduction-form" class="swal-form">
                             <input type="hidden" name="employee_ids" value="${escapeHtml(JSON.stringify(selectedIds))}">
                             <div class="form-group">
                                 <label for="deduction-type">Deduction Type</label>
                                 <select id="deduction-type" name="deduction_type" class="form-control">${deductionTypes}</select>
                             </div>
                             <div class="form-group">
                                 <input type="number" id="deduction-amount" name="deduction_amount" Placeholder="Deduction Amount" class="form-control" step="0.01" required min="0">
                             </div>
                             <div class="form-group">
                                 <textarea id="remarks" name="remarks" class="form-control" Placeholder="Remarks" rows="3" required></textarea>
                             </div>
                             <div class="form-group">
                                 <input type="date" id="deduction-date" name="deduction_date" placeholder="Deduction Date" class="form-control datepicker" required>
                             </div>
                         </form>
                     `,
                     showCancelButton: true,
                     confirmButtonText: 'Submit Deduction',
                     didOpen: function() {
                         $('#deduction-date').flatpickr({ dateFormat: "Y-m-d", allowInput: true });
                     },
                     preConfirm: function() {
                         const form = $('#bulk-deduction-form')[0];
                         if (!form.checkValidity()) {
                             form.reportValidity();
                             return false;
                         }
                         const formData = new FormData(form);
                         formData.append('action', 'bulk_add_deduction');
                         formData.append('csrf_token', $('meta[name="csrf-token"]').attr('content'));

                         Swal.showLoading();
                         return $.ajax({
                             url: 'api.php',
                             type: 'POST',
                             data: formData,
                             processData: false,
                             contentType: false
                         }).fail(function(jqXHR) { return Swal.showValidationMessage(jqXHR.responseJSON?.message || 'Request failed.'); });
                     }
                 }).then(function(result) {
                     if (result.isConfirmed && result.value.success) {
                         Swal.fire('Success!', result.value.message, 'success');
                         loadEmployees();
                     } else if (result.isConfirmed) {
                         Swal.fire('Error', result.value.message || 'Failed to add deduction.', 'error');
                     }
                 });
             },
             error: function(jqXHR, textStatus, errorThrown) {
                 handleAjaxError(jqXHR, textStatus, errorThrown, 'Loading deduction types');
             }
         });
     }
     
     function showBulkChangeStatusModal(selectedIds) {
         Swal.fire({
             title: `Change Status for ${selectedIds.length} Employee(s)`,
             html: `
                 <form id="bulk-status-form" class="swal-form text-left">
                     <div class="form-group">
                         <label for="new-status" class="form-label">New Status</label>
                         <select id="new-status" name="new_status" class="form-control" required>
                             <option value="">Select a status</option>
                             <option value="active">Active</option>
                             <option value="on_leave">On Leave</option>
                             <option value="resigned">Resigned</option>
                             <option value="terminated">Terminated</option>
                             <option value="probation">On Probation</option>
                         </select>
                     </div>
                     <div class="form-group">
                         <label for="status-change-reason" class="form-label">Reason (Optional)</label>
                         <textarea id="status-change-reason" name="status_change_reason" class="form-control" rows="3"></textarea>
                     </div>
                 </form>
             `,
             showCancelButton: true,
             confirmButtonText: 'Confirm Change',
             preConfirm: function() {
                 const newStatus = $('#new-status').val();
                 if (!newStatus) {
                     Swal.showValidationMessage('Please select a new status.');
                     return false;
                 }

                 const reason = $('#status-change-reason').val();
                 
                 const formData = new FormData();
                 formData.append('action', 'bulk_update_status');
                 formData.append('employee_ids', JSON.stringify(selectedIds));
                 formData.append('new_status', newStatus);
                 formData.append('status_change_reason', reason);
                 formData.append('csrf_token', $('meta[name="csrf-token"]').attr('content'));

                 Swal.showLoading();
                 return $.ajax({
                     url: 'api.php',
                     type: 'POST',
                     data: formData,
                     processData: false,
                     contentType: false
                 }).fail(function(jqXHR) { return Swal.showValidationMessage(jqXHR.responseJSON?.message || 'Request failed.'); });
             }
         }).then(function(result) {
             if (result.isConfirmed && result.value?.success) {
                 Swal.fire('Success!', result.value.message, 'success');
                 loadEmployees();
             } else if (result.isConfirmed) {
                 Swal.fire('Error', result.value?.message || 'Failed to change status.', 'error');
             }
         });
     }

     function showQuickEditSelectedEmployeesModal(selectedIds) {
         Swal.fire({ title: 'Loading...', allowOutsideClick: false, didOpen: function() { Swal.showLoading(); } });

         $.ajax({
             url: 'api.php',
             method: 'GET',
             data: { action: 'get_employees_by_ids', ids: JSON.stringify(selectedIds) },
             dataType: 'json',
             success: function(response) {
                 Swal.close();
                 if (!response.success) {
                     Swal.fire('Error', response.message || 'Failed to fetch employee data.', 'error');
                     return;
                 }

                 const employees = response.employees;
                 let employeesHtml = employees.map(function(emp) { return `
                     <div class="p-4 border rounded-md mb-2 bg-gray-50">
                         <h4 class="text-md font-semibold">${escapeHtml(emp.full_name)} <span class="text-sm text-gray-500">(ID: ${escapeHtml(emp.employee_number)})</span></h4>
                         <input type="hidden" name="employees[${emp.id}][id]" value="${emp.id}">
                         <div class="form-row mt-2">
                             <div class="form-group">
                                 <input type="number" name="employees[${emp.id}][basic_salary]" class="form-control" placeholder="Basic Salary*" value="${parseFloat(emp.basic_salary).toFixed(2)}" step="0.01" min="0">
                             </div>
                             <div class="form-group">
                                 <input type="number" name="employees[${emp.id}][increment_percentage]" class="form-control" placeholder="Increment %" value="${parseFloat(emp.increment_percentage).toFixed(2)}" step="0.01" min="0">
                             </div>
                             <div class="form-group">
                                 <label>Status</label>
                                 <select name="employees[${emp.id}][employee_status]" class="form-control">
                                     <option value="active" ${emp.employee_status === 'active' ? 'selected' : ''}>Active</option>
                                     <option value="on_leave" ${emp.employee_status === 'on_leave' ? 'selected' : ''}>On Leave</option>
                                     <option value="resigned" ${emp.employee_status === 'resigned' ? 'selected' : ''}>Resigned</option>
                                     <option value="terminated" ${emp.employee_status === 'terminated' ? 'selected' : ''}>Terminated</option>
                                     <option value="probation" ${emp.employee_status === 'probation' ? 'selected' : ''}>On Probation</option>
                                 </select>
                             </div>
                         </div>
                     </div>
                 `; }).join('');

                 Swal.fire({
                     title: `Quick Edit ${employees.length} Employee(s)`,
                     html: `<form id="quick-edit-form">${employeesHtml}</form>`,
                     showCancelButton: true,
                     confirmButtonText: 'Save Changes',
                     width: '700px',
                     preConfirm: function() {
                         const form = $('#quick-edit-form')[0];
                         if (!form.checkValidity()) {
                             form.reportValidity();
                             return false;
                         }
                         const formData = new FormData(form);
                         formData.append('action', 'bulk_quick_edit_employees');
                         formData.append('csrf_token', $('meta[name="csrf-token"]').attr('content'));

                         Swal.showLoading();
                         return $.ajax({
                             url: 'api.php',
                             type: 'POST',
                             data: formData,
                             processData: false,
                             contentType: false
                         }).fail(function(jqXHR) { return Swal.showValidationMessage(jqXHR.responseJSON?.message || 'Request failed.'); });
                     }
                 }).then(function(result) {
                     if (result.isConfirmed && result.value.success) {
                         Swal.fire('Success!', result.value.message, 'success');
                         loadEmployees();
                     } else if (result.isConfirmed) {
                         Swal.fire('Error', result.value.message || 'Failed to save changes.', 'error');
                     }
                 });
             },
             error: function(jqXHR, textStatus, errorThrown) {
                 handleAjaxError(jqXHR, textStatus, errorThrown, 'Loading quick edit data');
             }
         });
     }
     
     // --- New: Load notes functionality ---
     function loadEmployeeNotes(employeeId) {
         const $notesContainer = $('#employee-notes-list');
         $notesContainer.html('<p class="text-center text-gray-500">Loading notes...</p>');

         $.ajax({
             url: 'api.php',
             method: 'GET',
             data: { action: 'get_employee_notes', employee_id: employeeId },
             dataType: 'json',
             success: function(response) {
                 if (response.success) {
                     $notesContainer.empty();
                     const notes = response.notes;
                     if (notes && notes.length > 0) {
                         notes.sort(function(a, b) { return b.is_pinned - a.is_pinned || new Date(b.created_at) - new Date(a.created_at); });
                         notes.forEach(function(note) {
                             const noteHtml = `
                                 <div class="note-item card p-4 shadow-sm mb-3 ${note.is_pinned ? 'border-yellow-500 bg-yellow-50' : ''}" data-note-id="${note.id}">
                                     <div class="flex justify-between items-center mb-2">
                                         <h5 class="font-semibold">${escapeHtml(note.note_title || 'Untitled Note')}</h5>
                                         <div class="flex gap-2">
                                             <button type="button" class="pin-note-btn text-yellow-500 hover:text-yellow-700">
                                                 <i data-lucide="${note.is_pinned ? 'pin' : 'pin-off'}" class="w-4 h-4"></i>
                                             </button>
                                             <button type="button" class="edit-note-btn text-blue-500 hover:text-blue-700">
                                                 <i data-lucide="edit" class="w-4 h-4"></i>
                                             </button>
                                             <button type="button" class="delete-note-btn text-red-500 hover:text-red-700">
                                                 <i data-lucide="trash-2" class="w-4 h-4"></i>
                                             </button>
                                         </div>
                                     </div>
                                     <p class="text-sm text-gray-700 whitespace-pre-wrap">${escapeHtml(note.note_text)}</p>
                                     <small class="text-xs text-gray-500 mt-2 block">Created: ${moment(note.created_at).format('YYYY-MM-DD HH:mm')} by ${escapeHtml(note.author_name)}</small>
                                 </div>
                             `;
                             $notesContainer.append(noteHtml);
                         });
                         lucide.createIcons({nodes: $notesContainer.find('i[data-lucide]')});
                     } else {
                         $notesContainer.html('<p class="text-center text-gray-500">No notes found for this employee.</p>');
                     }
                 } else {
                     $notesContainer.html(`<p class="text-center text-red-500">Error loading notes: ${escapeHtml(response.message)}</p>`);
                 }
             },
             error: function(jqXHR, textStatus, errorThrown) {
                 $notesContainer.html(`<p class="text-center text-red-500">Error loading notes. Please try again.</p>`);
                 handleAjaxError(jqXHR, textStatus, errorThrown, 'Loading notes');
             }
         });
     }

     function addEmployeeNote(employeeId) {
         // The quick note modal now handles this.
         // This function is no longer called directly but is kept for context if needed.
     }
     
     // New function for quick notes
     function showQuickNoteModal(employeeId, employeeName) {
         Swal.fire({
             title: `Add a Quick Note for ${escapeHtml(employeeName)}`,
             html: `
                 <div class="quick-note-form">
                     <input id="swal-input-title" class="form-control" placeholder="Note Title*">
                     <textarea id="swal-input-note" class="form-control" placeholder="Type your note here...*" required></textarea>
                 </div>
             `,
             showCancelButton: true,
             confirmButtonText: 'Save Note',
             preConfirm: function() {
                 const noteTitle = $('#swal-input-title').val().trim();
                 const noteText = $('#swal-input-note').val().trim();
                 
                 if (!noteTitle) {
                     Swal.showValidationMessage('Note title is mandatory.');
                     return false;
                 }
                 
                 if (!noteText) {
                     Swal.showValidationMessage('Note content cannot be empty.');
                     return false;
                 }
                 
                 return $.ajax({
                     url: 'api.php',
                     method: 'POST',
                     data: {
                         action: 'add_employee_note',
                         employee_id: employeeId,
                         note_title: noteTitle,
                         note_text: noteText,
                         csrf_token: $('meta[name="csrf-token"]').attr('content')
                     },
                     dataType: 'json'
                 }).fail(function(jqXHR) { return Swal.showValidationMessage(jqXHR.responseJSON?.message || 'Request failed.'); });
             }
         }).then(function(result) {
             if (result.isConfirmed && result.value?.success) {
                 Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, icon: 'success', title: 'Quick note added successfully!' });
                 if ($('.employee-modal-container').is(':visible')) {
                     loadEmployeeNotes(employeeId);
                 }
             } else if (result.isConfirmed) {
                 Swal.fire('Error', result.value?.message || 'Failed to add quick note.', 'error');
             }
         });
     }


     function toggleNotePin(noteId, employeeId) {
         // Find the note item to get the current pin status
         const $noteItem = $(`[data-note-id="${noteId}"]`);
         const isPinned = $noteItem.hasClass('pinned');

         Swal.fire({
             title: isPinned ? 'Unpinning Note...' : 'Pinning Note...',
             allowOutsideClick: false,
             didOpen: function() { Swal.showLoading(); }
         });

         $.ajax({
             url: 'api.php',
             method: 'POST',
             data: {
                 action: 'toggle_employee_note_pin',
                 note_id: noteId,
                 is_pinned: isPinned ? 0 : 1,
                 csrf_token: $('meta[name="csrf-token"]').attr('content')
             },
             dataType: 'json',
             success: function(response) {
                 if (response.success) {
                     loadEmployeeNotes(employeeId);
                     Swal.close();
                     Swal.fire({toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, icon: 'success', title: response.message});
                 } else {
                     Swal.fire('Error', response.message || 'Failed to toggle pin status.', 'error');
                 }
             },
             error: function(jqXHR, textStatus, errorThrown) {
                 handleAjaxError(jqXHR, textStatus, errorThrown, 'Toggle note pin');
             }
         });
     }

     function deleteNote(noteId, employeeId) {
         Swal.fire({
             title: 'Delete this note?',
             text: "This action cannot be undone!",
             icon: 'warning',
             showCancelButton: true,
             confirmButtonColor: '#d33',
             cancelButtonColor: '#aaa',
             confirmButtonText: 'Yes, delete it!'
         }).then(function(result) {
             if (result.isConfirmed) {
                 Swal.fire({
                     title: 'Deleting note...',
                     allowOutsideClick: false,
                     didOpen: function() { Swal.showLoading(); }
                 });
                 $.ajax({
                     url: 'api.php',
                     method: 'POST',
                     data: {
                         action: 'delete_employee_note',
                         note_id: noteId,
                         csrf_token: $('meta[name="csrf-token"]').attr('content')
                     },
                     dataType: 'json',
                     success: function(response) {
                         if (response.success) {
                             loadEmployeeNotes(employeeId);
                             Swal.close();
                             Swal.fire({toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, icon: 'success', title: 'Note deleted!'});
                         } else {
                             Swal.fire('Error', response.message || 'Failed to delete note.', 'error');
                         }
                     },
                     error: function(jqXHR, textStatus, errorThrown) {
                         handleAjaxError(jqXHR, textStatus, errorThrown, 'Delete note');
                     }
                 });
             }
         });
     }

     function editNote(noteId, employeeId) {
         const $noteItem = $(`[data-note-id="${noteId}"]`);
         const currentTitle = $noteItem.find('.note-title').text();
         const currentText = $noteItem.find('.note-content').text().trim();
         Swal.fire({
             title: 'Edit Note',
             html: `
                 <input id="swal-input-title" class="swal2-input" placeholder="Note Title" value="${escapeHtml(currentTitle)}">
                 <textarea id="swal-input-note" class="swal2-textarea" placeholder="Note content...">${escapeHtml(currentText)}</textarea>
             `,
             showCancelButton: true,
             confirmButtonText: 'Save Changes',
             preConfirm: function() {
                 const newTitle = $('#swal-input-title').val().trim();
                 const newText = $('#swal-input-note').val().trim();
                 if (!newText) {
                     Swal.showValidationMessage('Note content cannot be empty.');
                     return false;
                 }
                 if (newTitle === currentTitle && newText === currentText) {
                     Swal.showValidationMessage('No changes were made.');
                     return false;
                 }
                 return { note_title: newTitle, note_text: newText };
             }
         }).then(function(result) {
             if (result.isConfirmed) {
                 Swal.fire({ title: 'Saving...', allowOutsideClick: false, didOpen: function() { Swal.showLoading(); } });
                 $.ajax({
                     url: 'api.php',
                     method: 'POST',
                     data: {
                         action: 'update_employee_note',
                         note_id: noteId,
                         note_title: result.value.note_title,
                         note_text: result.value.note_text,
                         csrf_token: $('meta[name="csrf-token"]').attr('content')
                     },
                     dataType: 'json',
                     success: function(response) {
                         if (response.success) {
                             loadEmployeeNotes(employeeId);
                             Swal.close();
                             Swal.fire({toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, icon: 'success', title: 'Note updated!'});
                         } else {
                             Swal.fire('Error', response.message || 'Failed to update note.', 'error');
                         }
                     },
                     error: function(jqXHR, textStatus, errorThrown) {
                         handleAjaxError(jqXHR, textStatus, errorThrown, 'Update note');
                     }
                 });
             }
         });
     }


     function exportTableData(format) {
         if (!employeesDataTable) {
             Swal.fire('Error', 'No table data to export.', 'error');
         }

         const dataToExport = employeesDataTable.rows({ search: 'applied' }).data().toArray();
         if (dataToExport.length === 0) {
             Swal.fire('Info', 'No data to export based on current filters.', 'info');
             return;
         }

         let headers = ['Employee Number', 'Name', 'Designation', 'Department', 'Status', 'Date of Joining', 'Basic Salary'];
         const csvContent = "data:text/csv;charset=utf-8," + headers.join(',') + "\n" +
             dataToExport.map(function(row) {
                 // Remove the checkbox and photo columns, and the actions column
                 return [
                     `"${row[2]}"`, // Employee Number
                     `"${row[3]}"`, // Name
                     `"${row[4]}"`, // Designation
                     `"${row[5]}"`, // Department
                     `"${$(row[6]).text()}"`, // Status (from HTML)
                     `"${row[7]}"`, // Date of Joining
                     `"${row[8]}"` // Basic Salary
                 ].join(',');
             }).join('\n');

         const encodedUri = encodeURI(csvContent);
         const link = document.createElement("a");
         link.setAttribute("href", encodedUri);
         link.setAttribute("download", `employees_data_${moment().format('YYYYMMDD_HHmmss')}.${format}`);
         document.body.appendChild(link);
         link.click();
         link.remove();

         Swal.fire('Success', `Data exported to ${format.toUpperCase()} successfully!`, 'success');
     }

     // --- End of Re-added Bulk Action and other missing functions ---
     
     /**
      * Finds the first invalid form control and returns the name of its containing tab.
      * @param {HTMLElement} form The form element to check.
      * @returns {string|null} The data-tab value of the first invalid tab, or null if all is valid.
      */
     function findInvalidTab(form) {
         const invalidFields = form.querySelectorAll(':invalid');
         if (invalidFields.length > 0) {
             const firstInvalidField = invalidFields[0];
             const tabContent = $(firstInvalidField).closest('.swal-tab-content');
             if (tabContent.length > 0) {
                 return tabContent.data('tab-content');
             }
         }
         return null;
     }

     /**
      * Activates a specific tab in the modal.
      * @param {jQuery} $modal The modal jQuery object.
      * @param {string} tabName The data-tab value of the tab to activate.
      * @param {boolean} isViewMode Whether the modal is in view-only mode.
      */
     function activateModalTab($modal, tabName, isViewMode = false) {
         $modal.find('.swal-tab-button').removeClass('active');
         $modal.find(`.swal-tab-button[data-tab="${tabName}"]`).addClass('active');
         $modal.find('.swal-tab-content').removeClass('active').hide();
         $modal.find(`.swal-tab-content[data-tab-content="${tabName}"]`).addClass('active').show();
         lucide.createIcons({nodes: $modal.find('.swal-tab-content.active i[data-lucide]')});
         
         if (isViewMode) {
             $modal.find('input, select, textarea, button').prop('disabled', true);
             $modal.find('.profile-photo-upload-wrapper, .document-dropzone').off('click dragover dragleavel drop').css('pointer-events', 'none');
             // Re-enable the close button
             $modal.find('button[onclick="Swal.close();"]').prop('disabled', false).css('pointer-events', 'auto');
         } else {
             $modal.find('input, select, textarea, button').prop('disabled', false);
         }

         // Handle notes tab load specifically
         if (tabName === 'notes') {
             const employeeId = $modal.find('#employee-form input[name="id"]').val();
             if (employeeId) {
                 loadEmployeeNotes(employeeId);
             }
         }
     }

     let deductionOptions = '';
     let paymentOptions = '';
     let fetchedFinancialTypes = false;
     
     function fetchFinancialTypes() {
         if (fetchedFinancialTypes) {
             return $.Deferred().resolve({ success: true }).promise();
         }
         return $.ajax({
             url: 'api.php?action=get_financial_transaction_types',
             method: 'GET',
             dataType: 'json',
             success: function(response) {
                 if (response.success) {
                     deductionOptions = response.types.deduction.map(t => `<option value="${t}">${escapeHtml(t)}</option>`).join('');
                     paymentOptions = response.types.earning.map(t => `<option value="${t}">${escapeHtml(t)}</option>`).join('');
                     fetchedFinancialTypes = true;
                 }
             }
         });
     }

     // --- MODAL FUNCTIONS ---
     function showEmployeeProfileModal(employeeId = null, isViewMode = false) {
         const isEdit = employeeId !== null;
         if (isEdit && !isViewMode) { // Only show loading spinner for edit mode
             Swal.fire({ title: 'Loading Profile...', allowOutsideClick: false, didOpen: function() { Swal.showLoading(); } });
         }
         
         const fetchDataPromise = isEdit ? $.ajax({
             url: 'api.php?action=get_employee_full_profile', 
             method: 'GET', 
             data: { id: employeeId },
             dataType: 'json'
         }) : $.Deferred().resolve([{employee: {}}]).promise();
         
         const fetchDepartmentsPromise = $.ajax({ url: 'api.php?action=get_departments', dataType: 'json' });
         const fetchTaxSlabsPromise = $.ajax({ url: 'api.php?action=get_tax_slabs', dataType: 'json' });
         const fetchActiveEmployeesPromise = $.ajax({ url: 'api.php?action=get_active_employees', dataType: 'json' });
         
         $.when(
             fetchDepartmentsPromise,
             fetchTaxSlabsPromise,
             fetchDataPromise,
             fetchActiveEmployeesPromise
         ).done(function(departmentsResponse, taxSlabsResponse, employeeResponse, activeEmployeesResponse) {
             Swal.close();
             const employeeData = employeeResponse[0]?.employee || null;
             const departments = departmentsResponse[0].departments || [];
             const taxSlabs = taxSlabsResponse[0].tax_slabs || [];
             const activeEmployees = activeEmployeesResponse[0].employees || [];
             const countryCode = employeeData?.country || 'PK';
             
             if (isEdit && !employeeData) {
                 Swal.fire('Error', 'Employee not found.', 'error');
                 return;
             }
             if (!taxSlabsResponse[0].success) {
                 Swal.fire('Error', taxSlabsResponse[0].message, 'error');
                 return;
             }
             
             const renderValue = (value) => value ? escapeHtml(value) : '<span class="text-gray-400">Not provided</span>';

             let departmentOptions = departments.map(function(d) { return `<option value="${d.id}" ${employeeData?.department_id == d.id ? 'selected' : ''}>${escapeHtml(d.department_name)}</option>`; }).join('');
             
             let managerOptions = '<option value="">Select Manager</option>';
             let approverOptions = '<option value="">Select Approver</option>';
             activeEmployees.forEach(function(emp) {
                 const managerSelected = employeeData?.reporting_manager == emp.id ? 'selected' : '';
                 const approverSelected = employeeData?.expense_approver == emp.id ? 'selected' : '';
                 managerOptions += `<option value="${emp.id}" ${managerSelected}>${escapeHtml(emp.full_name)}</option>`;
                 approverOptions += `<option value="${emp.id}" ${approverSelected}>${escapeHtml(emp.full_name)}</option>`;
             });

             const basicSalaryValue = parseFloat(employeeData?.basic_salary || 0);
             const yearlySalary = basicSalaryValue * 12;
             let selectedTaxSlabId = employeeData?.tax_slab_id || '';
             let taxSlabOptions = '<option value="">-- No Tax --</option>';

             if (yearlySalary > 0) {
                 taxSlabs.sort(function(a, b) { return parseFloat(a.min_annual_income) - parseFloat(b.min_annual_income); });
                 for (const slab of taxSlabs) {
                     const minIncome = parseFloat(slab.min_annual_income);
                     const maxIncome = parseFloat(slab.max_annual_income === null ? Number.MAX_VALUE : slab.max_annual_income);
                     if (yearlySalary >= minIncome && yearlySalary <= maxIncome) {
                         selectedTaxSlabId = slab.id;
                         break;
                     }
                 }
             }
             taxSlabs.forEach(function(s) {
                 const fixedTaxDisplay = parseFloat(s.base_tax_amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                 const rateDisplay = parseFloat(s.excess_tax_percentage).toFixed(2);
                 taxSlabOptions += `<option value="${s.id}" ${selectedTaxSlabId == s.id ? 'selected' : ''}>${escapeHtml(s.slab_name)} (Fixed: PKR ${fixedTaxDisplay} + Rate: ${rateDisplay}%)</option>`;
             });

             const documentSectionsConfig = {
                 'Personal Documents': { categories: ['ID Card', 'Passport', 'Visa', 'Driving License'], mandatory: true, icon: 'user' },
                 'Educational Documents': { categories: ['Degree', 'Transcript', 'Certificate'], mandatory: false, icon: 'book-open' },
                 'Work History Documents': { categories: ['Resume', 'Experience Letter', 'Reference Letter'], mandatory: false, icon: 'briefcase' },
                 'Previous Payslips': { categories: ['Previous Payslip'], mandatory: false, icon: 'wallet' },
                 'Other Documents': { categories: ['Other'], mandatory: false, icon: 'file' }
             };

             const getCategoryOptionsHtml = function(selectedCategory = null) {
                 let options = `<option value="">Select Category</option>`;
                 Object.values(documentSectionsConfig).forEach(function(section) {
                     options += section.categories.map(function(cat) { return `<option value="${cat}" ${selectedCategory === cat ? 'selected' : ''}>${escapeHtml(cat)}</option>`; }).join('');
                 });
                 options += `<option value="Other" ${selectedCategory && !options.includes(`value="${selectedCategory}"`) ? 'selected' : ''}>Other (Custom)</option>`;
                 return options;
             };

             const existingDocuments = employeeData?.documents || [];
             const dependents = JSON.parse(employeeData?.dependents || '[]');
             
             // NEW HTML STRUCTURE
             const formHtml = `
                 <div class="swal-tabs-container">
                     <button type="button" class="swal-tab-button active" data-tab="personal">
                         <i data-lucide="user-round" class="swal-tab-icon"></i><span>Personal</span>
                     </button>
                     <button type="button" class="swal-tab-button" data-tab="company-info">
                         <i data-lucide="briefcase" class="swal-tab-icon"></i><span>Company Info</span>
                     </button>
                     <button type="button" class="swal-tab-button" data-tab="payroll">
                         <i data-lucide="wallet" class="swal-tab-icon"></i><span>Payroll</span>
                     </button>
                     <button type="button" class="swal-tab-button" data-tab="bank-info">
                         <i data-lucide="banknote" class="swal-tab-icon"></i><span>Bank Info</span>
                     </button>
                     <button type="button" class="swal-tab-button" data-tab="dependents">
                         <i data-lucide="users-round" class="swal-tab-icon"></i><span>Dependents</span>
                     </button>
                     <button type="button" class="swal-tab-button" data-tab="documents">
                         <i data-lucide="file-text" class="swal-tab-icon"></i><span>Documents</span></button>
                     ${isEdit ? `<button type="button" class="swal-tab-button" data-tab="notes"><i data-lucide="sticky-note" class="swal-tab-icon"></i><span>Notes</span></button>` : ''}
                 </div>
                 <div class="modal-content-area">
                     <form id="employee-form" onsubmit="return false;">
                         <input type="hidden" name="id" value="${employeeData?.id || ''}">
                         
                         <div class="swal-tab-content active" data-tab-content="personal">
                             <div class="profile-photo-upload-wrapper">
                                 <img id="profile-photo-preview" src="${escapeHtml(employeeData?.profile_photo_url || 'https://placehold.co/150x150/f1f5f9/a0aec0?text=Upload')}" alt="Profile Photo">
                                 <div class="upload-text">
                                     <i data-lucide="image" class="w-8 h-8"></i>
                                     <span>Upload Photo</span>
                                 </div>
                                 <input type="file" id="profile-photo-input" name="profile_photo">
                             </div>
                             <div class="form-row-2-col">
                                 <div class="form-group"><input type="text" name="first_name" class="form-control" placeholder="First Name*" value="${escapeHtml(employeeData?.first_name || '')}" required></div>
                                 <div class="form-group"><input type="text" name="last_name" class="form-control" placeholder="Last Name*" value="${escapeHtml(employeeData?.last_name || '')}" required></div>
                                 <div class="form-group"><input type="email" name="contact_email" class="form-control" placeholder="Email Address*" value="${escapeHtml(employeeData?.contact_email || '')}" required></div>
                                 <div class="form-group"><input type="tel" name="contact_mobile" class="form-control" placeholder="Phone Number*" value="${escapeHtml(employeeData?.contact_mobile || '')}" required></div>
                                 <div class="form-group"><input type="date" name="date_of_birth" class="form-control" placeholder="Date of Birth*" value="${employeeData?.date_of_birth || ''}" required></div>
                                 <div class="form-group"><select name="country" id="country-select" class="form-control" placeholder="Country*" required></select></div>
                                 <div class="form-group"><input type="text" name="address" class="form-control" placeholder="Address" value="${escapeHtml(employeeData?.address || '')}"></div>
                                 <div class="form-group"><input type="text" name="citizenship" class="form-control" placeholder="Citizenship" value="${escapeHtml(employeeData?.citizenship || '')}"></div>
                             </div>
                         </div>

                         <div class="swal-tab-content" data-tab-content="company-info">
                             <div class="form-row-2-col">
                                 <div class="form-group"><input type="text" name="employee_number" class="form-control" placeholder="Employee ID" value="${escapeHtml(employeeData?.employee_number || '')}" readonly></div>
                                 <div class="form-group"><input type="text" name="designation" class="form-control" placeholder="Designation*" value="${escapeHtml(employeeData?.designation || '')}" required></div>
                                 <div class="form-group"><select name="department_id" id="department_id" class="form-control" placeholder="Department*" required>${departmentOptions}</select></div>
                                 <div class="form-group"><input type="date" name="date_of_joining" class="form-control" placeholder="Date of Joining*" value="${employeeData?.date_of_joining || ''}" required></div>
                                 <div class="form-group"><select name="reporting_manager" id="reporting_manager" class="form-control" placeholder="Reporting Manager">${managerOptions}</select></div>
                                 <div class="form-group"><select name="expense_approver" id="expense_approver" class="form-control" placeholder="Expense Approver">${approverOptions}</select></div>
                             </div>
                         </div>

                         <div class="swal-tab-content" data-tab-content="payroll">
                             <h5 class="font-semibold mb-2">Basic Salary & Allowances</h5>
                             <div class="form-row-2-col">
                                 <div class="form-group"><input type="number" name="basic_salary" id="basic_salary" class="form-control" placeholder="Basic Salary*" value="${employeeData?.basic_salary || ''}" required></div>
                                 <div class="form-group"><select name="currency" id="currency-select" class="form-control" placeholder="Currency*" required>
                                     <option value="">Select Currency</option>
                                     <option value="PKR" ${employeeData?.currency === 'PKR' ? 'selected' : ''}>PKR</option>
                                     <option value="USD" ${employeeData?.currency === 'USD' ? 'selected' : ''}>USD</option>
                                     <option value="EUR" ${employeeData?.currency === 'EUR' ? 'selected' : ''}>EUR</option>
                                     <option value="GBP" ${employeeData?.currency === 'GBP' ? 'selected' : ''}>GBP</option>
                                     <option value="AUD" ${employeeData?.currency === 'AUD' ? 'selected' : ''}>AUD</option>
                                 </select></div>
                                 <div class="form-group"><input type="number" name="housing_allowance" class="form-control" placeholder="Housing Allowance" value="${employeeData?.housing_allowance || ''}"></div>
                                 <div class="form-group"><input type="number" name="transportation_allowance" class="form-control" placeholder="Transportation Allowance" value="${employeeData?.transportation_allowance || ''}"></div>
                                 <div class="form-group"><input type="number" name="cost_of_living" class="form-control" placeholder="Cost of Living" value="${employeeData?.cost_of_living || ''}"></div>
                                 <div class="form-group"><input type="number" name="fuel_allowance" class="form-control" placeholder="Fuel Allowance" value="${employeeData?.fuel_allowance || ''}"></div>
                                 <div class="form-group"><input type="number" name="telephone_allowance" class="form-control" placeholder="Telephone Allowance" value="${employeeData?.telephone_allowance || ''}"></div>
                                 <div class="form-group"><input type="number" name="food_allowance" class="form-control" placeholder="Food Allowance" value="${employeeData?.food_allowance || ''}"></div>
                                 <div class="form-group"><input type="number" name="conveyance_allowance" class="form-control" placeholder="Conveyance Allowance" value="${employeeData?.conveyance_allowance || ''}"></div>
                             </div>
                             <h5 class="font-semibold mt-4 mb-2">Tax & Other Info</h5>
                             <div class="form-row-2-col">
                                 <div class="form-group"><input type="text" name="increment_month" id="increment_month" class="form-control datepicker" placeholder="Increment Month" value="${employeeData?.increment_month || ''}"></div>
                                 <div class="form-group"><input type="number" step="0.01" name="increment_percentage" class="form-control" placeholder="Increment Percentage" value="${employeeData?.increment_percentage || ''}" min="0"></div>
                                 <div class="form-group"><input type="text" name="gross_salary" id="gross_salary" class="form-control bg-gray-100" placeholder="Gross Salary" readonly></div>
                                 <div class="form-group"><input type="text" id="taxable_income_display" class="form-control bg-gray-100" placeholder="Taxable Income" readonly></div>
                                 <div class="form-group"><select name="tax_slab_id" id="tax_slab_id" class="form-control" placeholder="Tax Slab" disabled>${taxSlabOptions}</select></div>
                                 <div class="form-group"><input type="text" id="monthly_tax_display" class="form-control bg-gray-100" placeholder="Monthly Tax" readonly></div>
                                 <div class="form-group"><select name="overtime_rate_multiplier" id="overtime_rate_multiplier" class="form-control" placeholder="Overtime Rate">
                                     <option value="1" ${employeeData?.overtime_rate_multiplier == 1 ? 'selected' : ''}>100% of Salary</option>
                                     <option value="1.5" ${employeeData?.overtime_rate_multiplier == 1.5 ? 'selected' : ''}>150% of Salary</option>
                                     <option value="2" ${employeeData?.overtime_rate_multiplier == 2 ? 'selected' : ''}>200% of Salary</option>
                                 </select></div>
                             </div>
                         </div>

                         <div class="swal-tab-content" data-tab-content="bank-info">
                             <div class="form-row-2-col">
                                 <div class="form-group"><input type="text" name="bank_name" class="form-control" placeholder="Bank Name*" value="${escapeHtml(employeeData?.bank_name || '')}" required></div>
                                 <div class="form-group"><input type="text" name="bank_iban" class="form-control" placeholder="IBAN / Account Number*" value="${escapeHtml(employeeData?.bank_iban || '')}" required></div>
                                 <div class="form-group"><input type="text" name="account_title" class="form-control" placeholder="Account Title*" value="${escapeHtml(employeeData?.account_title || '')}" required></div>
                                 <div class="form-group"><input type="text" name="branch_code" class="form-control" placeholder="Branch Code*" value="${escapeHtml(employeeData?.branch_code || '')}" required></div>
                             </div>
                         </div>

                         <div class="swal-tab-content" data-tab-content="dependents">
                             <div class="form-row">
                                 <div class="form-group"><input type="number" id="num-dependents" class="form-control" placeholder="Number of Dependents" min="0" value="${(employeeData?.dependents && JSON.parse(employeeData.dependents).length) || 0}"></div>
                             </div>
                             <div id="dependents-container" class="mt-4 space-y-4"></div>
                             <h4 class="employee-form-section-title mt-6">Emergency Contact</h4>
                             <div class="form-row">
                                 <div class="form-group"><input type="text" name="emergency_contact_name" class="form-control" placeholder="Name*" value="${escapeHtml(employeeData?.emergency_contact_name || '')}" required></div>
                                 <div class="form-group"><input type="tel" name="emergency_contact_phone" class="form-control" placeholder="Phone Number*" value="${escapeHtml(employeeData?.emergency_contact_phone || '')}" required></div>
                                 <div class="form-group"><input type="text" name="emergency_contact_relationship" class="form-control" placeholder="Relation*" value="${escapeHtml(employeeData?.emergency_contact_relationship || '')}" required></div>
                                 <div class="form-group"><input type="text" name="emergency_contact_address" class="form-control" placeholder="Address" value="${escapeHtml(employeeData?.emergency_contact_address || '')}"></div>
                             </div>
                         </div>
                         
                         <div class="swal-tab-content" data-tab-content="documents">
                             <h4 class="employee-form-section-title">Employee Documents</h4>
                             <div class="document-dropzone p-8" id="document-dropzone">
                                 <i data-lucide="upload-cloud" class="w-12 h-12 text-[#6ca4bc] mx-auto"></i>
                                 <p class="mt-2 text-[#6ca4bc] font-semibold">Drag & Drop files here or click to upload</p>
                                 <input type="file" id="document-file-input" class="hidden" multiple>
                                 <div id="document-upload-errors" class="text-red-500 mt-2 text-sm"></div>
                             </div>

                             <div id="employee-documents-container" class="mt-4 flex flex-wrap gap-4">
                             </div>
                         </div>
                         
                         ${isEdit ? `
                         <div class="swal-tab-content" data-tab-content="notes">
                             <h4 class="employee-form-section-title">Employee Notes
                             <button type="button" class="btn btn-sm btn-primary ml-2" id="add-note-btn">
                                 <i data-lucide="plus"></i> Add Note
                             </button>
                             </h4>
                             <div id="employee-notes-list" class="note-container space-y-4 h-96 overflow-y-auto p-2">
                                 </div>
                         </div>` : ''}
                     </form>
                 </div>
                 <div class="form-actions mt-auto p-4 border-t border-gray-200">
                     <button type="button" class="btn btn-modal-secondary" id="modal-cancel-btn" onclick="Swal.close();">Cancel</button>
                     <button type="button" class="btn btn-modal-primary" id="modal-submit-btn">${isEdit ? 'Update Profile' : 'Add Employee'}</button>
                 </div>
             `;

             Swal.fire({
                 title: '',
                 html: formHtml,
                 showConfirmButton: false,
                 showCancelButton: false,
                 width: '1500px', // This is being overriden by the CSS below
                 heightAuto: false,
                 customClass: {
                     popup: 'swal2-popup-themed',
                     container: 'swal2-container-full-height',
                     htmlContainer: 'swal2-html-container-scrollable'
                 },
                 didOpen: function(modalElement) {
                     const $modal = $(modalElement);
                     const $form = $modal.find('#employee-form');
                     const $tabs = $modal.find('.swal-tab-button');
                     const $tabContents = $modal.find('.swal-tab-content');
                     const $submitBtn = $('#modal-submit-btn');
                     
                     // Set up the form title and buttons
                     if (isEdit) {
                        $modal.find('.swal2-title').text('Edit Employee Profile');
                     } else {
                        $modal.find('.swal2-title').text('Add New Employee');
                     }
                     if (isViewMode) {
                         $modal.find('input, select, textarea').prop('disabled', true);
                         $modal.find('.profile-photo-upload-wrapper, .document-dropzone, #add-note-btn').remove(); // Remove interactive elements
                         $modal.find('.form-actions').html('<button type="button" class="btn btn-modal-secondary" onclick="Swal.close();">Close</button>');
                         $modal.find('.swal2-title').text('View Employee Profile');
                     }
                     
                     // NEW: Logic for dynamic icon colors and button text
                     const requiredFieldsByTab = {
                         'personal': ['first_name', 'last_name', 'contact_email', 'contact_mobile', 'date_of_birth', 'country'],
                         'company-info': ['designation', 'department_id', 'date_of_joining'],
                         'payroll': ['basic_salary', 'currency'],
                         'bank-info': ['bank_name', 'bank_iban', 'account_title', 'branch_code'],
                         'dependents': ['emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship']
                     };

                     function checkTabValidity(tabName) {
                         const $tabContent = $modal.find(`.swal-tab-content[data-tab-content="${tabName}"]`);
                         let isTabValid = true;
                         
                         if (requiredFieldsByTab[tabName]) {
                             requiredFieldsByTab[tabName].forEach(function(field) {
                                 const $field = $tabContent.find(`[name="${field}"]`);
                                 if ($field.length > 0) {
                                     if (!$field.val()?.trim()) {
                                         isTabValid = false;
                                     }
                                 }
                             });
                         }
                         return isTabValid;
                     }

                     function checkAllTabsValidity() {
                         return Object.keys(requiredFieldsByTab).every(tabName => checkTabValidity(tabName));
                     }

                    function updateTabIconsAndButton() {
                         const allTabs = Object.keys(requiredFieldsByTab);
                         const completedTabs = [];

                         // Loop through all defined tabs and check their validity
                         for (const tabName of allTabs) {
                             const $tabButton = $(`.swal-tab-button[data-tab="${tabName}"]`);
                             const $tabContent = $(`.swal-tab-content[data-tab-content="${tabName}"]`);
                             
                             let isTabValid = true;

                             if (requiredFieldsByTab[tabName]) {
                                 // Check each required field for the current tab
                                 for (const fieldName of requiredFieldsByTab[tabName]) {
                                     const $field = $tabContent.find(`[name="${fieldName}"]`);
                                     if ($field.length && !$field.val()?.trim()) {
                                         isTabValid = false;
                                         break;
                                     }
                                 }
                             }
                             
                             // Add or remove the 'completed' class based on validity
                             if (isTabValid) {
                                 $tabButton.addClass('completed');
                                 completedTabs.push(tabName);
                             } else {
                                 $tabButton.removeClass('completed');
                             }
                         }
                         
                         // Update the button text based on whether all tabs are complete
                         if (!isViewMode) {
                            const isEdit = $('#employee-form input[name="id"]').val() !== '';
                            const allTabsValid = allTabs.length === completedTabs.length;
                            
                            if (allTabsValid) {
                                $('#modal-submit-btn').text(isEdit ? 'Update Profile' : 'Add Employee').prop('disabled', false);
                            } else {
                                $('#modal-submit-btn').text('Next Step').prop('disabled', false);
                            }
                        }
                     }

                     // Event listener for tab transitions via the button
                     $('#modal-submit-btn').on('click', function(e) {
                         e.preventDefault();
                         const isEdit = $('#employee-form input[name="id"]').val() !== '';
                         const allTabsValid = checkAllTabsValidity();
                         
                         if (allTabsValid) {
                             // Correctly trigger the form submission when all tabs are valid
                             $form.trigger('submit');
                         } else {
                             const currentTab = $modal.find('.swal-tab-button.active').data('tab');
                             const allTabs = Object.keys(requiredFieldsByTab);
                             const currentIndex = allTabs.indexOf(currentTab);
                             const nextIndex = (currentIndex + 1) % allTabs.length;
                             
                             activateModalTab($modal, allTabs[nextIndex]);
                             updateTabIconsAndButton();
                         }
                     });

                     // Event listener for tab clicks to enable free switching
                     $modal.find('.swal-tab-button').on('click', function(e) {
                         e.preventDefault();
                         const tabName = $(this).data('tab');
                         activateModalTab($modal, tabName);
                         updateTabIconsAndButton();
                     });
                     
                     // Attach change event listener to form inputs to update tab icons
                     $form.on('input change', 'input, select, textarea', function() {
                        updateTabIconsAndButton();
                    });
                     
                     // Initial setup
                     lucide.createIcons({nodes: $modal.find('i[data-lucide]')});
                     $modal.find('input[type="date"]').flatpickr({ dateFormat: "Y-m-d", allowInput: true });
                     $modal.find('input.month-picker').flatpickr({ plugins: [new monthSelectPlugin({ shorthand: true, dateFormat: "Y-m" })], allowInput: true });
                     populateCountryDropdown($modal.find('#country-select'), employeeData?.country || '');
                     
                     if (employeeData?.dependents) {
                         try {
                             const dependentsData = JSON.parse(employeeData.dependents);
                             $modal.find('#num-dependents').val(dependentsData.length).trigger('input');
                             dependentsData.forEach(function(dep, index) {
                                 $modal.find(`input[name="dependent[${index}][name]"]`).val(dep.name);
                                 $modal.find(`input[name="dependent[${index}][relationship]"]`).val(dep.relationship);
                                 $modal.find(`input[name="dependent[${index}][occupation]"]`).val(dep.occupation);
                                 $modal.find(`input[name="dependent[${index}][dob]"]`).val(dep.dob);
                             });
                         } catch (e) {
                             console.error("Failed to parse dependents JSON:", e);
                         }
                     }

                     $modal.find('#num-dependents').on('input', function() {
                         const num = parseInt($(this).val()) || 0;
                         const $container = $modal.find('#dependents-container');
                         const currentCount = $container.children().length;
                         if (num > currentCount) {
                             for (let i = currentCount; i < num; i++) {
                                 const dependentHtml = `
                                     <div class="dependent-item">
                                         <h5 class="font-semibold text-sm mb-2">Dependent #${i + 1}</h5>
                                         <div class="form-row-2-col">
                                             <div class="form-group"><input type="text" name="dependent[${i}][name]" class="form-control" placeholder="Name"></div>
                                             <div class="form-group"><input type="text" name="dependent[${i}][relationship]" class="form-control" placeholder="Relationship"></div>
                                             <div class="form-group"><input type="text" name="dependent[${i}][occupation]" class="form-control" placeholder="Occupation"></div>
                                             <div class="form-group"><input type="date" name="dependent[${i}][dob]" class="form-control" placeholder="Date of Birth"></div>
                                         </div>
                                     </div>
                                 `;
                                 $container.append(dependentHtml);
                                 $container.find('input[type="date"]').flatpickr({ dateFormat: "Y-m-d", allowInput: true });
                             }
                         } else if (num < currentCount) {
                             $container.children().slice(num).remove();
                         }
                     });

                     
                     // Handle profile picture upload
                     $('#profile-photo-input').on('change', function() {
                         const file = this.files[0];
                         if (file) {
                             const reader = new FileReader();
                             reader.onload = function(e) {
                                 $('#profile-photo-preview').attr('src', e.target.result);
                                 fileMap.set('profile_photo', file);
                             }
                             reader.readAsDataURL(file);
                         }
                     });
                     
                     // Handle payroll inputs
                     const payrollInputs = $modal.find('input[name="basic_salary"], input[name="housing_allowance"], input[name="transportation_allowance"], input[name="cost_of_living"], input[name="fuel_allowance"], input[name="telephone_allowance"], input[name="food_allowance"], input[name="conveyance_allowance"]');
                     payrollInputs.on('input', function() {
                         const basicSalary = parseFloat($modal.find('#basic_salary').val()) || 0;
                         const allowances = parseFloat($modal.find('input[name="housing_allowance"]').val() || 0) +
                                                                   parseFloat($modal.find('input[name="transportation_allowance"]').val() || 0) +
                                                                   parseFloat($modal.find('input[name="cost_of_living"]').val() || 0) +
                                                                   parseFloat($modal.find('input[name="fuel_allowance"]').val() || 0) +
                                                                   parseFloat($modal.find('input[name="telephone_allowance"]').val() || 0) +
                                                                   parseFloat($modal.find('input[name="food_allowance"]').val() || 0) +
                                                                   parseFloat($modal.find('input[name="conveyance_allowance"]').val() || 0);

                         const grossSalary = basicSalary + allowances;
                         $modal.find('#gross_salary').val(grossSalary.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                         const taxFreeAmount = 600000; // Example tax-free amount for PKR
                         const taxableIncome = Math.max(0, grossSalary * 12 - taxFreeAmount); // Tax is calculated on annual income
                         $modal.find('#taxable_income_display').val(taxableIncome.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                         let estimatedMonthlyTax = 0;
                         let autoSelectedTaxSlabId = '';
                         taxSlabs.sort((a, b) => parseFloat(a.min_annual_income) - parseFloat(b.min_annual_income));
                         for (const slab of taxSlabs) {
                             const minIncome = parseFloat(slab.min_annual_income);
                             const maxIncome = parseFloat(slab.max_annual_income === null ? Number.MAX_VALUE : slab.max_annual_income);
                             if (taxableIncome >= minIncome && taxableIncome <= maxIncome) {
                                 autoSelectedTaxSlabId = slab.id;
                                 const taxableAmountInSlab = Math.max(0, taxableIncome - minIncome);
                                 estimatedMonthlyTax = (parseFloat(slab.base_tax_amount) + (taxableAmountInSlab * (parseFloat(slab.excess_tax_percentage) / 100))) / 12;
                                 break;
                             }
                         }
                         $modal.find('#tax_slab_id').val(autoSelectedTaxSlabId);
                         $modal.find('#monthly_tax_display').val(estimatedMonthlyTax.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                     }).trigger('input');


                     const $documentDropzone = $modal.find('#document-dropzone');
                     const $documentFileInput = $modal.find('#document-file-input');
                     const $documentsContainer = $modal.find('#employee-documents-container');
                     const $docsCountBadge = $modal.find('#docs-count-badge');
                     
                     let fileMap = new Map();
                     let docCounter = 0;

                     function promptForDocumentDetails(file) {
                         Swal.fire({
                             title: `Document: ${escapeHtml(file.name)}`,
                             html: `
                                 <div class="quick-note-form text-left">
                                     <input id="doc-title" class="form-control" placeholder="Document Title*" required>
                                     <label for="doc-category" class="form-label mt-4">Document Category</label>
                                     <select id="doc-category" class="form-control">${getCategoryOptionsHtml()}</select>
                                 </div>
                             `,
                             showCancelButton: true,
                             confirmButtonText: 'Add Document',
                             preConfirm: () => {
                                 const title = $('#doc-title').val().trim();
                                 const category = $('#doc-category').val();
                                 if (!title) {
                                     Swal.showValidationMessage('Document title is required.');
                                     return false;
                                 }
                                 return { file, title, category };
                             }
                         }).then(result => {
                             if (result.isConfirmed) {
                                 addDocumentItem(result.value.file, null, result.value.title, result.value.category);
                             }
                         });
                     }

                     function updateDocumentCount() {
                         const count = $documentsContainer.find('.document-folder-icon:not([data-deleted="1"])').length;
                         $docsCountBadge.text(count);
                     }

                     function addDocumentItem(file = null, doc = null, title = null, category = null) {
                         const currentDocIndex = doc ? doc.id : `new_${docCounter++}_${Date.now()}`;
                         const isExisting = !!doc;
                         const fileName = file ? file.name : doc.file_name;
                         const fileIcon = getFileIcon(fileName);

                         const docItemHtml = `
                             <div class="document-folder-icon" data-doc-id="${currentDocIndex}" data-existing="${isExisting}" data-deleted="0">
                                 <i data-lucide="${fileIcon}"></i>
                                 <span>${escapeHtml(title || doc?.document_title || fileName)}</span>
                                 <div class="absolute top-0 right-0 p-1 bg-white rounded-bl-lg shadow-md">
                                     <button type="button" class="remove-doc-btn text-red-500 hover:text-red-700"><i data-lucide="x" class="w-4 h-4"></i></button>
                                 </div>
                                 <input type="hidden" name="documents[${currentDocIndex}][id]" value="${isExisting ? currentDocIndex : ''}">
                                 <input type="hidden" name="documents[${currentDocIndex}][file_key]" value="${isExisting ? '' : currentDocIndex}">
                                 <input type="hidden" name="documents[${currentDocIndex}][is_deleted]" value="0">
                                 <input type="hidden" name="documents[${currentDocIndex}][title]" value="${escapeHtml(title || doc?.document_title || '')}">
                                 <input type="hidden" name="documents[${currentDocIndex}][category]" value="${escapeHtml(category || doc?.document_category || '')}">
                             </div>
                         `;
                         const $item = $(docItemHtml);
                         $documentsContainer.append($item);
                         lucide.createIcons({nodes: $item.find('i[data-lucide]')});
                         
                         if (file) {
                             fileMap.set(currentDocIndex, file);
                         }
                         updateDocumentCount();
                     }

                     if (employeeData && employeeData.documents) {
                         employeeData.documents.sort(function(a, b) { return new Date(b.uploaded_at) - new Date(a.uploaded_at); });
                         employeeData.documents.forEach(function(doc) { addDocumentItem(null, doc, doc.document_title, doc.document_category); });
                     }
                     updateDocumentCount();

                     $documentDropzone.on('dragover', function(e) {
                         e.preventDefault(); e.stopPropagation();
                         $(this).addClass('dragover');
                     }).on('dragleave', function(e) {
                         e.preventDefault(); e.stopPropagation();
                         $(this).removeClass('dragover');
                     }).on('drop', function(e) {
                         e.preventDefault(); e.stopPropagation();
                         $(this).removeClass('dragover');
                         const files = e.originalEvent.dataTransfer.files;
                         Array.from(files).forEach(function(file) {
                             if (file.size > 10 * 1024 * 1024) {
                                 $('#document-upload-errors').text(`File '${file.name}' exceeds the 10MB limit.`);
                                 return;
                             }
                             promptForDocumentDetails(file);
                         });
                     }).on('click', function() {
                         $documentFileInput.click();
                     });

                     $documentFileInput.on('change', function() {
                         const files = this.files;
                         Array.from(files).forEach(function(file) {
                             if (file.size > 10 * 1024 * 1024) {
                                 $('#document-upload-errors').text(`File '${file.name}' exceeds the 10MB limit.`);
                                 return;
                             }
                             promptForDocumentDetails(file);
                         });
                         $(this).val('');
                     });
                     
                     if (isEdit) {
                         $modal.on('click', '#add-note-btn', function() { showQuickNoteModal(employeeData.id, employeeData.full_name); });
                         $modal.on('click', '.note-actions .delete-note-btn', function() {
                             const noteId = $(this).closest('.note-item').data('note-id');
                             deleteNote(noteId, employeeData.id);
                         });
                         $modal.on('click', '.note-actions .edit-note-btn', function() {
                             const noteId = $(this).closest('.note-item').data('note-id');
                             editNote(noteId, employeeData.id);
                         });
                         $modal.on('click', '.note-actions .pin-note-btn', function() {
                             const noteId = $(this).closest('.note-item').data('note-id');
                             toggleNotePin(noteId, employeeData.id);
                         });
                     }
                     
                     $modal.on('click', '.remove-doc-btn', function() {
                         const $docItem = $(this).closest('.document-folder-icon');
                         const docId = $docItem.data('doc-id');
                         const isExisting = $docItem.data('existing') === true;
                         
                         Swal.fire({
                             title: 'Confirm Delete Document?',
                             text: "This will permanently remove this document. This action cannot be undone!",
                             icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#aaa', confirmButtonText: 'Yes, delete it!'
                         }).then(function(result) {
                             if (result.isConfirmed) {
                                 if (isExisting) {
                                     $docItem.attr('data-deleted', '1').hide();
                                 } else {
                                     fileMap.delete(docId);
                                     $docItem.remove();
                                 }
                                 updateDocumentCount();
                                 Swal.fire({toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, icon: 'success', title: 'Document marked for deletion.'});
                             }
                         });
                     });
                     updateTabIconsAndButton();
                 },
                 preConfirm: function() {
                     const $modal = $(Swal.getPopup());
                     const form = $modal.find('#employee-form')[0];
                     
                     if (!checkAllTabsValidity()) {
                         const invalidTabName = findInvalidTab(form);
                         if (invalidTabName) {
                             activateModalTab($modal, invalidTabName);
                         }
                         return false;
                     }

                     const formData = new FormData(form);
                     formData.append('action', employeeId ? 'update_employee' : 'add_employee');
                     formData.append('csrf_token', $('meta[name="csrf-token"]').attr('content'));

                     // Append files from the map
                     fileMap.forEach(function(file, key) {
                         formData.append(`documents_files[${key}]`, file);
                     });
                     // Append profile picture if it exists
                     if (fileMap.has('profile_photo')) {
                         formData.append('profile_photo_file', fileMap.get('profile_photo'));
                     }

                     // Collect and append documents metadata
                     const documentsData = [];
                     $('#employee-documents-container').find('.document-folder-icon').each(function() {
                         const $docItem = $(this);
                         const docId = $docItem.data('doc-id');
                         const isExisting = $docItem.data('existing') === true;
                         const isDeleted = $docItem.data('deleted') === '1';

                         documentsData.push({
                             id: isExisting ? docId : null,
                             file_key: isExisting ? null : docId,
                             is_deleted: isDeleted,
                             category: $docItem.find('input[name*="[category]"]').val(),
                             title: $docItem.find('input[name*="[title]"]').val(),
                         });
                     });
                     formData.append('documents_data', JSON.stringify(documentsData));

                     Swal.showLoading();
                     return $.ajax({
                         url: 'api.php',
                         type: 'POST',
                         data: formData,
                         processData: false,
                         contentType: false
                     })
                     .then(function(response) {
                         return response;
                     })
                     .fail(function(jqXHR) {
                         const errorMsg = jqXHR.responseJSON?.message || 'Request failed.';
                         Swal.showValidationMessage(errorMsg);
                         return null; // Return null on failure to prevent the outer then() from running
                     });
                 }
             })
             .then(function(result) {
                 if (result) { // Check if the AJAX call was successful and returned a non-null value
                     if (result.isConfirmed && result.value && result.value.success) {
                         Swal.fire('Success!', result.value.message, 'success');
                         loadEmployees();
                     } else if (result.isConfirmed) {
                         Swal.fire('Error', result.value?.message || 'Could not save data.', 'error');
                     }
                 }
             });
         }).fail(function(jqXHR, textStatus, errorThrown) {
             Swal.close();
             handleAjaxError(jqXHR, textStatus, errorThrown, 'Could not load form data.');
         });
     }

     function showManageFinancesModal(employeeId) {
         if (!employeeId) {
             Swal.fire('Error', 'Employee ID is missing for the financial view.', 'error');
             return;
         }

         Swal.fire({
             title: 'Loading Financial Data...',
             allowOutsideClick: false,
             didOpen: function() { Swal.showLoading(); }
         });

         const employeeDataPromise = $.ajax({
             url: 'api.php',
             method: 'GET',
             data: { action: 'get_employee_full_profile', id: employeeId },
             dataType: 'json'
         });

         const financeDataPromise = $.ajax({
             url: 'api.php',
             method: 'GET',
             data: { action: 'get_employee_financial_summary', id: employeeId },
             dataType: 'json'
         });
         
         $.when(employeeDataPromise, financeDataPromise, fetchFinancialTypes()).done(function(employeeResponse, financeSummaryResponse) {
             Swal.close();
             const employee = employeeResponse[0].employee;
             const financeSummary = financeSummaryResponse[0].summary || {};

             if (!employeeResponse[0].success) {
                 Swal.fire('Error', employeeResponse[0].message || 'Failed to fetch employee details.', 'error');
                 return;
             }

             // --- BEGIN FIX: Define and populate the financial history HTML ---
             const formatCurrency = (amount) => {
                 if (typeof amount !== 'number') { amount = parseFloat(amount) || 0; }
                 return amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
             };
             const formatDate = (date) => date ? moment(date).format('YYYY-MM-DD') : 'N/A';

             let advanceDetailsHtml = '';
             if (financeSummary.advances && financeSummary.advances.length > 0) {
                 const advanceRows = financeSummary.advances.map(adv => `
                     <tr>
                         <td>${formatDate(adv.date)}</td>
                         <td>${formatCurrency(adv.amount)}</td>
                         <td>${formatCurrency(adv.deducted_amount)}</td>
                         <td>${formatCurrency(adv.remaining_balance)}</td>
                         <td>${escapeHtml(adv.remarks)}</td>
                     </tr>
                 `).join('');
                 advanceDetailsHtml = `
                     <table class="detail-table w-full">
                         <thead><tr><th>Date</th><th>Amount</th><th>Deducted</th><th>Remaining</th><th>Remarks</th></tr></thead>
                         <tbody>${advanceRows}</tbody>
                     </table>
                 `;
             } else {
                 advanceDetailsHtml = `<p class="text-muted text-center">No advances recorded.</p>`;
             }

             let loanDetailsHtml = '';
             if (financeSummary.loans && financeSummary.loans.length > 0) {
                 const loanRows = financeSummary.loans.map(loan => `
                     <tr>
                         <td>${formatDate(loan.start_date)}</td>
                         <td>${formatCurrency(loan.loan_amount)}</td>
                         <td>${formatCurrency(loan.monthly_deduction_amount)}</td>
                         <td>${formatCurrency(loan.remaining_balance)}</td>
                         <td>${escapeHtml(loan.notes)}</td>
                     </tr>
                 `).join('');
                 loanDetailsHtml = `
                     <table class="detail-table w-full">
                         <thead><tr><th>Start Date</th><th>Total Amount</th><th>Monthly Deduction</th><th>Remaining</th><th>Notes</th></tr></thead>
                         <tbody>${loanRows}</tbody>
                     </table>
                 `;
             } else {
                 loanDetailsHtml = `<p class="text-muted text-center">No loans recorded.</p>`;
             }
             
             let additionalPaymentsHtml = '';
             if (financeSummary.additional_payments && financeSummary.additional_payments.length > 0) {
                 const paymentRows = financeSummary.additional_payments.map(payment => `
                     <tr>
                         <td>${formatDate(payment.date)}</td>
                         <td>${escapeHtml(payment.type)}</td>
                         <td>${formatCurrency(payment.amount)}</td>
                         <td>${escapeHtml(payment.remarks)}</td>
                     </tr>
                 `).join('');
                 additionalPaymentsHtml = `
                     <table class="detail-table w-full">
                         <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Remarks</th></tr></thead>
                         <tbody>${paymentRows}</tbody>
                     </table>
                 `;
             } else {
                 additionalPaymentsHtml = `<p class="text-muted text-center">No additional payments recorded.</p>`;
             }

             let additionalDeductionsHtml = '';
             if (financeSummary.additional_deductions && financeSummary.additional_deductions.length > 0) {
                 const deductionRows = financeSummary.additional_deductions.map(deduction => `
                     <tr>
                         <td>${formatDate(deduction.date)}</td>
                         <td>${escapeHtml(deduction.type)}</td>
                         <td>${formatCurrency(deduction.amount)}</td>
                         <td>${escapeHtml(deduction.remarks)}</td>
                     </tr>
                 `).join('');
                 additionalDeductionsHtml = `
                     <table class="detail-table w-full">
                         <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Remarks</th></tr></thead>
                         <tbody>${deductionRows}</tbody>
                     </table>
                 `;
             } else {
                 additionalDeductionsHtml = `<p class="text-muted text-center">No additional deductions recorded.</p>`;
             }
             // --- END FIX: Define and populate the financial history HTML ---

             // FIX START: Corrected the taxDetailsHtml assignment to be a single template string.
             let taxDetailsHtml = '';
             if (financeSummaryResponse[0].success && financeSummary.tax_info) {
                 const taxInfo = financeSummary.tax_info;
                 taxDetailsHtml = `
                     <div class="employee-profile-financial-summary">
                         <div class="financial-summary-card bg-blue-100 border-blue-400">
                             <h4>Estimated Annual Tax Liability</h4>
                             <div class="amount">${parseFloat(taxInfo.estimated_yearly_tax || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${escapeHtml(taxInfo.currency || 'PKR')}</div>
                             <small>Based on ${escapeHtml(taxInfo.tax_slab_name || 'N/A')} tax slab.</small>
                         </div>
                         <div class="financial-summary-card">
                             <h4>Total Tax Deducted YTD</h4>
                             <div class="amount">${parseFloat(taxInfo.total_deducted_ytd || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${escapeHtml(taxInfo.currency || 'PKR')}</div>
                             <small>As of ${moment().format('MMMM YYYY')}</small>
                         </div>
                         <div class="financial-summary-card">
                             <h4>Remaining Tax to Deduct</h4>
                             <div class="amount">${parseFloat(taxInfo.remaining_tax_to_deduct || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${escapeHtml(taxInfo.currency || 'PKR')}</div>
                             <small>Remaining for current fiscal year.</small>
                         </div>
                     </div>
                     <h5 class="employee-profile-section-title mt-4"><i data-lucide="calendar"></i> Monthly Tax Deductions</h5>
                     <div class="table-container">
                         <table class="detail-table">
                             <thead>
                                 <tr>
                                     <th>Month</th>
                                     <th>Year</th>
                                     <th>Deducted Amount</th>
                                     <th>Remaining Annual Tax</th>
                                 </tr>
                             </thead>
                             <tbody>
                                 ${(taxInfo.monthly_deductions && taxInfo.monthly_deductions.length > 0) ?
                                     taxInfo.monthly_deductions.map(function(mth) { return `
                                         <tr>
                                             <td>${moment().month(mth.month - 1).format('MMMM')}</td>
                                             <td>${mth.year}</td>
                                             <td>${parseFloat(mth.deducted_amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                             <td>${parseFloat(mth.remaining_annual_tax).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                         </tr>
                                     `; }).join('')
                                     : '<tr><td colspan="4" class="text-center text-muted">No monthly tax deductions recorded.</td></tr>'
                                 }
                             </tbody>
                         </table>
                     </div>
                 `;
             } else {
                 taxDetailsHtml = '<p class="text-muted text-center">No tax details available. Please ensure a valid country is selected for this employee.</p>';
             }
             // FIX END

             const financeHtml = `
                 <div class="swal-tabs">
                     <button type="button" class="swal-tab-button active" data-tab="deduction">Add Deduction / Loan</button>
                     <button type="button" class="swal-tab-button" data-tab="payment">Add Payment</button>
                     <button type="button" class="swal-tab-button" data-tab="history">Financial History</button>
                     <button type="button" class="swal-tab-button" data-tab="tax">Tax Details</button>
                 </div>
                 <div class="modal-content-area">
                     <div class="swal-tab-content active" data-tab-content="deduction">
                         <form id="add-deduction-form" class="swal-form">
                             <input type="hidden" name="employee_id" value="${employeeId}">
                             <div class="form-group"><label>Deduction Type</label><div class="custom-select"><select name="type" id="deduction-type-select" class="form-control">${deductionOptions}</select></div></div>
                             <div class="form-group"><label id="amount-label">Amount</label><input type="number" name="amount" class="form-control" required></div>
                             <div class="form-group" id="monthly-deduction-group" style="display: none;"><label>Monthly Deduction Amount</label><input type="number" name="monthly_deduction_amount" class="form-control"></div>
                             <div class="form-group"><label>Remarks</label><textarea name="remarks" class="form-control" rows="2"></textarea></div>
                             <div class="form-group"><label id="date-label">Date</label><input type="text" name="date" class="form-control datepicker" required></div>
                         </form>
                     </div>
                     <div class="swal-tab-content" data-tab-content="payment">
                         <form id="add-payment-form" class="swal-form">
                             <input type="hidden" name="employee_id" value="${employeeId}">
                             <div class="form-group"><label>Payment Type</label><div class="custom-select"><select name="type" class="form-control">${paymentOptions}</select></div></div>
                             <div class="form-group"><label>Payment Amount</label><input type="number" name="payment_amount" class="form-control" required></div>
                             <div class="form-group"><label>Remarks</label><textarea name="remarks" class="form-control" rows="2"></textarea></div>
                             <div class="form-group"><label>Payment Date</label><input type="text" name="payment_date" class="form-control datepicker" required></div>
                         </form>
                     </div>
                     <div class="swal-tab-content" data-tab-content="history">
                         <h4 class="form-section-title"><i data-lucide="wallet"></i> Advances</h4>
                         <div class="employee-transactions-list-view">
                             ${advanceDetailsHtml}
                         </div>
                         <h4 class="form-section-title"><i data-lucide="banknote"></i> Loans</h4>
                         <div class="employee-transactions-list-view">
                             ${loanDetailsHtml}
                         </div>
                         <h4 class="form-section-title"><i data-lucide="plus-square"></i> Additional Payments</h4>
                         <div class="employee-transactions-list-view">
                             ${additionalPaymentsHtml}
                         </div>
                         <h4 class="form-section-title"><i data-lucide="minus-square"></i> Additional Deductions</h4>
                         <div class="employee-transactions-list-view">
                             ${additionalDeductionsHtml}
                         </div>
                     </div>
                     <div class="swal-tab-content" data-tab-content="tax">
                         <h4 class="form-section-title"><i data-lucide="file-bar-chart"></i> Tax Details</h4>
                         <div class="employee-tax-details-view">
                             ${taxDetailsHtml}
                         </div>
                     </div>
                 </div>
             `;
             Swal.fire({
                 title: `Manage Finances for ${escapeHtml(employee.full_name)}`,
                 html: financeHtml,
                 showCancelButton: true,
                 confirmButtonText: 'Save',
                 width: '900px',
                 customClass: { popup: 'swal2-popup-themed', htmlContainer: 'swal2-html-container-scrollable' },
                 didOpen: function(modalElement) {
                     const $modalContent = $(modalElement);
                     lucide.createIcons({nodes: $(modalElement).find('i[data-lucide]')});
                     const initializeDatepickerForTab = function(tabContent) { $(tabContent).find('.datepicker:not(.flatpickr-input)').flatpickr({ dateFormat: "Y-m-d", allowInput: true }); };
                     initializeDatepickerForTab($modalContent.find('.swal-tab-content.active'));
                     $modalContent.find('.swal-tabs').on('click', '.swal-tab-button', function(e) {
                         e.preventDefault(); e.stopPropagation();
                         const tabId = $(this).data('tab');
                         $modalContent.find('.swal-tab-button').removeClass('active'); $(this).addClass('active');
                         $modalContent.find('.swal-tab-content').removeClass('active'); $(this).blur();
                         $modalContent.find(`[data-tab-content="${tabId}"]`).addClass('active');
                         initializeDatepickerForTab($modalContent.find(`[data-tab-content="${tabId}"]`));
                         lucide.createIcons({nodes: $(modalElement).find('.swal-tab-content.active i[data-lucide]')});
                     });

                     const $deductionTypeSelect = $modalContent.find('#deduction-type-select');
                     const $monthlyDeductionGroup = $modalContent.find('#monthly-deduction-group');
                     const $amountLabel = $modalContent.find('#amount-label');
                     const $dateLabel = $modalContent.find('#date-label');
                     $deductionTypeSelect.on('change', function() {
                         if ($(this).val() === 'Loan' || $(this).val() === 'Advance') {
                             $amountLabel.text('Total Loan/Advance Amount');
                             $dateLabel.text('Loan/Advance Date');
                             $monthlyDeductionGroup.slideDown();
                         } else {
                             $amountLabel.text('Deduction Amount');
                             $dateLabel.text('Deduction Date');
                             $monthlyDeductionGroup.slideUp();
                         }
                     }).trigger('change');
                 },
                 preConfirm: function() {
                     const activeTab = $('.swal-tab-button.active').data('tab');
                     let formId, action;
                     let isLoanOrAdvance = false;

                     if (activeTab === 'deduction') {
                         formId = '#add-deduction-form';
                         const deductionType = $('#deduction-type-select').val();
                         if (deductionType === 'Loan' || deductionType === 'Advance') {
                             action = 'add_loan_advance';
                             isLoanOrAdvance = true;
                         } else {
                             action = 'add_deduction';
                         }
                     } else if (activeTab === 'payment') {
                         formId = '#add-payment-form';
                         action = 'add_payment';
                     } else {
                         Swal.showValidationMessage('Please switch to "Add Deduction / Loan" or "Add Payment" tab to save changes.');
                         return false;
                     }

                     const form = $(formId)[0];
                     if (!form.checkValidity()) {
                         form.reportValidity();
                         return false;
                     }

                     const formData = new FormData(form);
                     formData.append('action', action);
                     formData.append('csrf_token', $('meta[name="csrf-token"]').attr('content'));

                     if (isLoanOrAdvance) {
                         formData.set('loan_advance_type', formData.get('type'));
                         formData.set('amount', formData.get('amount'));
                         formData.set('monthly_deduction_amount', formData.get('monthly_deduction_amount') || '0');
                         formData.set('start_date', formData.get('date'));
                         formData.set('notes', formData.get('remarks'));
                         formData.delete('type'); formData.delete('amount'); formData.delete('date');
                     } else if (action === 'add_deduction') {
                         formData.set('deduction_type', formData.get('type'));
                         formData.set('deduction_amount', formData.get('amount'));
                         formData.set('deduction_date', formData.get('date'));
                         formData.delete('type'); formData.delete('amount'); formData.delete('date');
                     } else if (action === 'add_payment') {
                         formData.set('payment_type', formData.get('type'));
                         formData.set('payment_amount', formData.get('amount'));
                         formData.set('payment_date', formData.get('date'));
                         formData.delete('type'); formData.delete('amount'); formData.delete('date');
                     }

                     Swal.showLoading();
                     return $.ajax({
                         url: 'api.php',
                         type: 'POST',
                         data: formData,
                         processData: false,
                         contentType: false
                     }).fail(function(jqXHR) {
                         const errorMsg = jqXHR.responseJSON?.message || 'Request failed.';
                         Swal.showValidationMessage(errorMsg);
                     });
                 }
             }).then(function(result) {
                 if (result.isConfirmed && result.value.success) {
                     Swal.fire('Success!', result.value.message, 'success');
                     showManageFinancesModal(employeeId);
                 } else if (result.isConfirmed) {
                     Swal.fire('Error', result.value?.message || 'Could not save data.', 'error');
                 }
             });
         });
     }

     function deleteEmployee(employeeId, employeeName) {
         Swal.fire({
             title: 'Are you sure?',
             html: `You are about to permanently delete <strong>${escapeHtml(employeeName)}</strong>. This action cannot be undone!`, // FIX: Escaped the employeeName here
             icon: 'warning',
             showCancelButton: true,
             confirmButtonColor: '#d33',
             cancelButtonColor: '#aaa',
             confirmButtonText: 'Yes, delete it!'
         }).then(function(result) {
             if (result.isConfirmed) {
                 Swal.fire({ title: 'Deleting...', allowOutsideClick: false, didOpen: function() { Swal.showLoading(); } });
                 $.ajax({
                     url: 'api.php',
                     method: 'POST',
                     data: {
                         action: 'delete_employee',
                         id: employeeId,
                         csrf_token: $('meta[name="csrf-token"]').attr('content')
                     },
                     dataType: 'json',
                     success: function(response) {
                         if (response.success) {
                             Swal.fire('Deleted!', response.message, 'success');
                             loadEmployees();
                         } else {
                             Swal.fire('Error', response.message || 'Failed to delete the employee.', 'error');
                         }
                     },
                     error: function(jqXHR, textStatus, errorThrown) {
                         handleAjaxError(jqXHR, textStatus, errorThrown, 'Delete employee');
                     }
                 });
             }
         });
     }
     
     function renderTaxDetails(taxInfo) {
        let taxDetailsHtml = '';
        if (taxInfo) {
            taxDetailsHtml = `
                <div class="employee-profile-financial-summary">
                    <div class="financial-summary-card bg-blue-100 border-blue-400">
                        <h4>Estimated Annual Tax Liability</h4>
                        <div class="amount">${parseFloat(taxInfo.estimated_yearly_tax || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${escapeHtml(taxInfo.currency || 'PKR')}</div>
                        <small>Based on ${escapeHtml(taxInfo.tax_slab_name || 'N/A')} tax slab.</small>
                    </div>
                    <div class="financial-summary-card">
                        <h4>Total Tax Deducted YTD</h4>
                        <div class="amount">${parseFloat(taxInfo.total_deducted_ytd || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${escapeHtml(taxInfo.currency || 'PKR')}</div>
                        <small>As of ${moment().format('MMMM YYYY')}</small>
                    </div>
                    <div class="financial-summary-card">
                        <h4>Remaining Tax to Deduct</h4>
                        <div class="amount">${parseFloat(taxInfo.remaining_tax_to_deduct || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${escapeHtml(taxInfo.currency || 'PKR')}</div>
                        <small>Remaining for current fiscal year.</small>
                    </div>
                </div>
                <h5 class="employee-profile-section-title mt-4"><i data-lucide="calendar"></i> Monthly Tax Deductions</h5>
                <div class="table-container">
                    <table class="detail-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Year</th>
                                <th>Deducted Amount</th>
                                <th>Remaining Annual Tax</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${(taxInfo.monthly_deductions && taxInfo.monthly_deductions.length > 0) ?
                                taxInfo.monthly_deductions.map(function(mth) { return `
                                    <tr>
                                        <td>${moment().month(mth.month - 1).format('MMMM')}</td>
                                        <td>${mth.year}</td>
                                        <td>${parseFloat(mth.deducted_amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                        <td>${parseFloat(mth.remaining_annual_tax).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                    </tr>
                                `; }).join('')
                                : '<tr><td colspan="4" class="text-center text-muted">No monthly tax deductions recorded.</td></tr>'
                            }
                        </tbody>
                    </table>
                </div>
            `;
        } else {
            taxDetailsHtml = '<p class="text-muted text-center">No tax details available. Please ensure a valid country is selected for this employee.</p>';
        }
        return taxDetailsHtml;
    }
     
     function showReportSelectionModal() {
         Swal.fire({
             title: 'Generate Employee Report',
             html: `
                 <div class="form-group text-left">
                     <label for="report_type_select" class="form-label">Choose a report:</label>
                     <select id="report_type_select" class="form-control">
                         <option value="">-- Select Report --</option>
                         <option value="by_department">Employees by Department</option>
                         <option value="by_hiring_date">Employees by Hiring Date Range</option>
                         <option value="by_salary_range">Employees by Salary Range</option>
                         <option value="by_service_year">Employees by Service Year</option>
                         <option value="by_status">Employees by Status</option>
                         <option value="full_employee_data">Full Employee Data (Export)</option>
                         <option value="increment_due_report">Increment Due Report</option>
                         <option value="birthday_notification">Birthday Notification Report</option>
                         <option value="employee_advances_loans_payments_deductions">Advances/Loans/Payments/Deductions Report</option>
                     </select>
                 </div>
                 <div id="report-params-container" class="form-row mt-3" style="display: none;"></div>
                 <div id="report-output-container" class="mt-4 p-4 border border-gray-200 rounded-md bg-gray-50" style="display:none;">
                     <h5 class="section-title text-lg font-semibold mb-4 flex items-center gap-2"><i data-lucide="list"></i> Report Results Table</h5>
                     <div class="table-container">
                         <table id="report-output-table" class="w-full">
                             <thead><tr></tr></thead>
                             <tbody></tbody>
                         </table>
                     </div>
                     <div id="report-no-results" class="text-center py-4 text-gray-500" style="display: none;"></div>
                     <div id="report-chart-container" class="mt-4 w-full h-80"></div>
                 </div>
             `,
             showCancelButton: true,
             confirmButtonText: 'Generate Report',
             width: '900px',
             customClass: {
                 popup: 'swal2-popup-themed',
                 htmlContainer: 'swal2-html-container-scrollable'
             },
             didOpen: function(modalElement) {
                 const $modalContent = $(modalElement);
                 lucide.createIcons({ nodes: $modalContent.find('i[data-lucide]') });
                 const $reportTypeSelect = $modalContent.find('#report_type_select');

                 $reportTypeSelect.on('change', function() {
                     selectedReportType = $(this).val();
                     if (selectedReportType) {
                         renderReportParametersForm(selectedReportType, $modalContent);
                     } else {
                         $modalContent.find('#report-params-container').slideUp();
                         $modalContent.find('#report-output-container').hide();
                         $modalContent.find('#report-chart-container').empty().hide();
                         if (reportChartInstance) { reportChartInstance.destroy(); reportChartInstance = null; }
                     }
                 });
             },
             preConfirm: function() {
                 if (!selectedReportType) {
                     Swal.showValidationMessage('Please select a report type.');
                     return false;
                 }
                 return generateReport();
             }
         });
     }

     function renderReportParametersForm(reportType, $modal) {
         const $paramsContainer = $modal.find('#report-params-container');
         const $form = $('<form id="dynamic-report-params-form" class="form-row"></form>');
         const $reportOutputContainer = $modal.find('#report-output-container');
         const $reportChartContainer = $modal.find('#report-chart-container');

         $paramsContainer.empty().append($form);
         $reportOutputContainer.hide();
         $reportChartContainer.empty().hide();
         if (reportChartInstance) { reportChartInstance.destroy(); reportChartInstance = null; }

         let paramsHtml = '';
         switch(reportType) {
             case 'by_department':
                 $.ajax({
                     url: 'api.php?action=get_departments', method: 'GET', dataType: 'json',
                     success: function(response) {
                         let deptOptions = response.success ? response.departments.map(function(d) { return `<option value="${d.id}">${escapeHtml(d.department_name)}</option>`; }).join('') : '';
                         $form.append(`
                             <div class="form-group col-md-6">
                                 <label for="report_dept_id" class="form-label">Department</label>
                                 <select id="report_dept_id" name="department_id" class="form-control">
                                     <option value="">All Departments</option>${deptOptions}
                                 </select>
                             </div>
                         `);
                         $paramsContainer.slideDown();
                     },
                     error: function(jqXHR, textStatus, errorThrown) {
                         console.error("AJAX Error loading departments for report:", { status: jqXHR.status, textStatus: textStatus, errorThrown: errorThrown, responseText: jqXHR.responseText });
                         handleAjaxError(jqXHR, textStatus, errorThrown, 'Error loading departments for report.');
                     }
                 });
                 break;
             case 'by_hiring_date':
                 paramsHtml = `
                     <div class="form-group col-md-6"><label for="start_date" class="form-label">From Date</label><input type="date" id="start_date" name="start_date" class="form-control datepicker"></div>
                     <div class="form-group col-md-6"><label for="end_date" class="form-label">To Date</label><input type="date" id="end_date" name="end_date" class="form-control datepicker"></div>
                 `;
                 break;
             case 'by_salary_range':
                 paramsHtml = `
                     <div class="form-group col-md-6"><label for="min_salary" class="form-label">Min Salary</label><input type="number" id="min_salary" name="min_salary" class="form-control" step="0.01" min="0"></div>
                     <div class="form-group col-md-6"><label for="max_salary" class="form-label">Max Salary</label><input type="number" id="max_salary" name="max_salary" class="form-control" step="0.01" min="0"></div>
                 `;
                 break;
             case 'by_service_year':
                 paramsHtml = `
                     <div class="form-group col-md-6"><label for="min_years" class="form-label">Min Service Years</label><input type="number" id="min_years" name="min_years" class="form-control" min="0"></div>
                     <div class="form-group col-md-6"><label for="max_years" class="form-label">Max Service Years</label><input type="number" id="max_years" name="max_years" class="form-control" min="0"></div>
                 `;
                 break;
             case 'by_status':
                 paramsHtml = `
                     <div class="form-group col-md-6"><label for="employee_status_report" class="form-label">Status</label><select id="employee_status_report" name="status" class="form-control">
                         <option value="">All Statuses</option>
                         <option value="active">Active</option>
                         <option value="on_leave">On Leave</option>
                         <option value="resigned">Resigned</option>
                         <option value="terminated">Terminated</option>
                         <option value="probation">On Probation</option>
                     </select></div>
                 `;
                 break;
             case 'increment_due_report':
                 paramsHtml = `
                     <div class="form-group col-md-6"><label for="due_date" class="form-label">Due By Date</label><input type="date" id="due_date" name="due_date" class="form-control datepicker"></div>
                 `;
                 break;
             case 'birthday_notification':
                 paramsHtml = `
                     <div class="form-group col-md-6"><label for="month" class="form-label">Month</label><input type="number" id="month" name="month" class="form-control" min="1" max="12" placeholder="e.g., 1 (Jan)" value="${moment().month() + 1}"></div>
                 `;
                 break;
             case 'employee_advances_loans_payments_deductions':
                 paramsHtml = `
                     <div class="form-group col-md-6"><label for="transaction_type" class="form-label">Transaction Type</label><select id="transaction_type" name="transaction_type" class="form-control">
                         <option value="">All Types</option>
                         <option value="advance">Advances</option>
                         <option value="loan">Loans</option>
                         <option value="payment">Additional Payments</option>
                         <option value="deduction">Additional Deductions</option>
                     </select></div>
                     <div class="form-group col-md-6"><label for="from_date_transactions" class="form-label">From Date</label><input type="date" id="from_date_transactions" name="from_date" class="form-control datepicker"></div>
                     <div class="form-group col-md-6"><label for="to_date_transactions" class="form-label">To Date</label><input type="date" id="to_date_transactions" name="to_date" class="form-control datepicker"></div>
                 `;
                 break;
             case 'full_employee_data':
             default:
                 paramsHtml = '<p class="text-gray-500 col-span-full">No specific parameters for this report.</p>';
                 break;
         }
         $form.append(paramsHtml);
         $paramsContainer.slideDown();
         $form.find('.datepicker').flatpickr({ dateFormat: "Y-m-d", allowInput: true });
         lucide.createIcons({nodes: $form.find('i[data-lucide]')});
     }

     function getReportParameters() {
         const params = {};
         const $paramsForm = $('#dynamic-report-params-form');
         $paramsForm.find('input, select, textarea').each(function() {
             const name = $(this).attr('name');
             const value = $(this).val();
             if (name) { params[name] = value; }
         });
         return params;
     }

     function generateReport() {
         const params = getReportParameters();
         if (!selectedReportType) { Swal.fire('Error', 'Please select a report type first.', 'error'); return; }

         Swal.fire({ title: 'Generating report...', allowOutsideClick: false, didOpen: function() { Swal.showLoading(); } });

         $.ajax({
             url: 'api.php',
             method: 'POST',
             data: { action: 'generate_report', report_type: selectedReportType, params: params, csrf_token: $('meta[name="csrf-token"]').attr('content') },
             dataType: 'json',
             success: function(response) {
                 Swal.close();
                 if (response.success && response.report_data) {
                     displayReportOutput(selectedReportType, response.report_data);
                     $('#report-output-container').slideDown();
                 } else {
                     Swal.fire('Error', response.message || 'Failed to generate report or no data found.', 'error');
                 }
             },
             error: function(jqXHR, textStatus, errorThrown) {
                 Swal.close();
                 console.error("AJAX Error generating report:", { status: jqXHR.status, textStatus: textStatus, errorThrown: errorThrown, responseText: jqXHR.responseText });
                 handleAjaxError(jqXHR, textStatus, errorThrown, 'Error generating report.');
             }
         });
     }

     function displayReportOutput(reportType, data) {
         const $outputTableHead = $('#report-output-table thead tr');
         const $outputTableBody = $('#report-output-table tbody');
         const $noResultsDiv = $('#report-no-results');
         const $reportChartContainer = $('#report-chart-container');
         const $chartCanvas = $('<canvas id="report-chart"></canvas>');

         $outputTableHead.empty();
         $outputTableBody.empty();
         $noResultsDiv.hide();
         $reportChartContainer.empty().hide();

         if (reportChartInstance) {
             reportChartInstance.destroy();
             reportChartInstance = null;
         }

         if (!data || data.length === 0) {
             $noResultsDiv.text('No data found for this report criteria.').show();
             return;
         }

         const headers = Object.keys(data[0]);
         headers.forEach(function(header) { $outputTableHead.append(`<th>${escapeHtml(header.replace(/_/g, ' ')).toUpperCase()}</th>`); });
         data.forEach(function(row) {
             let rowHtml = '<tr>';
             headers.forEach(function(header) {
                 let cellData = row[header];
                 if (typeof cellData === 'number' && !['id', 'employee_id'].includes(header.toLowerCase())) {
                     cellData = cellData.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                 } else if (typeof cellData === 'string' && (header.includes('date') || header.includes('at') || header.includes('dob') || header.includes('joining'))) {
                     const dateObj = moment(cellData);
                     if (dateObj.isValid()) {
                         cellData = dateObj.format('YYYY-MM-DD');
                     }
                 }
                 rowHtml += `<td>${escapeHtml(cellData || 'N/A')}</td>`;
             });
             rowHtml += '</tr>';
             $outputTableBody.append(rowHtml);
         });

         if (window.Chart) {
             let chartData = { labels: [], datasets: [] };
             let chartType = 'bar';
             let chartOptions = {};

             switch (reportType) {
                 case 'by_department':
                     const departmentCounts = {};
                     data.forEach(function(emp) { departmentCounts[emp.department_name] = (departmentCounts[emp.department_name] || 0) + 1; });
                     chartData.labels = Object.keys(departmentCounts);
                     chartData.datasets.push({
                         label: 'Employees per Department',
                         data: Object.values(departmentCounts),
                         backgroundColor: 'rgba(74, 144, 226, 0.7)',
                         borderColor: 'rgba(74, 144, 226, 1)',
                         borderWidth: 1
                     });
                     break;
                 case 'by_status':
                     const statusCounts = {};
                     data.forEach(function(emp) { statusCounts[emp.employee_status] = (statusCounts[emp.employee_status] || 0) + 1; });
                     chartData.labels = Object.keys(statusCounts);
                     chartData.datasets.push({
                         label: 'Employees by Status',
                         data: Object.values(statusCounts),
                         backgroundColor: ['#4CAF50', '#FF9800', '#F44336', '#9E9E9E', '#2196F3'],
                         borderColor: ['#4CAF50', '#FF9800', '#F44336', '#9E9E9E', '#2196F3'],
                         borderWidth: 1
                     });
                     chartType = 'pie';
                     chartOptions = {
                         responsive: true,
                         maintainAspectRatio: false,
                         plugins: {
                             legend: {
                                 position: 'top',
                             },
                             title: {
                                 display: true,
                                 text: `Report: Employees by Status`,
                                 font: { size: 16 }
                             }
                         }
                     };
                     break;
                 case 'by_salary_range':
                     const bins = {
                         'Up to 20k': 0, '20k-40k': 0, '40k-60k': 0, '60k-80k': 0, '80k+': 0
                     };
                     data.forEach(function(emp) {
                         const salary = parseFloat(emp.basic_salary);
                         if (salary >= 0 && salary < 20000) bins['Up to 20k']++;
                         else if (salary >= 20000 && salary < 40000) bins['20k-40k']++;
                         else if (salary >= 40000 && salary < 60000) bins['40k-60k']++;
                         else if (salary >= 60000 && salary < 80000) bins['60k-80k']++;
                         else if (salary >= 80000) bins['80k+']++;
                     });
                     chartData.labels = Object.keys(bins);
                     chartData.datasets.push({
                         label: 'Employees by Salary Range',
                         data: Object.values(bins),
                         backgroundColor: 'rgba(74, 144, 226, 0.7)',
                         borderColor: 'rgba(74, 144, 226, 1)',
                         borderWidth: 1
                     });
                     break;
                 case 'full_employee_data':
                     const avgSalary = data.reduce(function(sum, emp) { return sum + parseFloat(emp.basic_salary); }, 0) / data.length;
                     $reportChartContainer.append(`<p class="text-center font-bold">Average Basic Salary: ${avgSalary.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} PKR</p>`);
                     break;
             }

             if (chartData.labels.length > 0 || reportType === 'full_employee_data') {
                 $reportChartContainer.append($chartCanvas);
                 const ctx = document.getElementById('report-chart')?.getContext('2d');
                 if (ctx) {
                     reportChartInstance = new Chart(ctx, {
                         type: chartType,
                         data: chartData,
                         options: {
                             responsive: true,
                             maintainAspectRatio: false,
                             plugins: {
                                 legend: {
                                     display: chartType === 'pie' || chartType === 'doughnut',
                                     position: 'top',
                                 },
                                 title: {
                                     display: true,
                                     text: `Report: Employees by Status`,
                                     font: { size: 16 }
                                 }
                             },
                             ...chartOptions
                         }
                     });
                     $reportChartContainer.slideDown();
                 }
             }
         }
         lucide.createIcons();
     }
     
     // --- Event listeners and initial calls LAST ---
     $employeesTableBody.on('change', '.employee-select-checkbox', function() {
         updateBulkActionsVisibility();
         updateSelectAllCheckbox();
     });

     $selectAllCheckbox.on('change', function() {
         const isChecked = $(this).prop('checked');
         if (employeesDataTable) {
             employeesDataTable.rows({ search: 'applied' }).nodes().to$().find('.employee-select-checkbox:not(:disabled)').prop('checked', isChecked);
         }
         updateBulkActionsVisibility();
         updateSelectAllCheckbox();
     });
     
     $dashboardCards.on('click', function() {
         const filterStatus = $(this).data('filter-status');
         const newStatuses = filterStatus ? [filterStatus] : ['all'];
         const newDepartments = ['all'];
         
         currentSelectedStatuses = newStatuses;
         currentSelectedDepartments = newDepartments;
         currentSearchQuery = '';
         
         syncFilterCheckboxes('status', newStatuses);
         syncFilterCheckboxes('department', newDepartments);
         $searchNameInput.val('');
         
         loadEmployees({ status: newStatuses, department_id: newDepartments });
     });

     $('#add-employee-btn').on('click', function() {
         showEmployeeProfileModal(null, false);
     });

     $employeesTableBody.on('click', '.view-employee-profile-btn', function() {
         const employeeId = $(this).data('id');
         showEmployeeProfileModal(employeeId, true);
     });
     
     $employeesTableBody.on('click', '.edit-employee-btn', function() {
         const employeeId = $(this).data('id');
         showEmployeeProfileModal(employeeId, false);
     });
     
     $employeesTableBody.on('click', '.manage-finances-btn', function() {
         const employeeId = $(this).data('id');
         showManageFinancesModal(employeeId);
     });

     $employeesTableBody.on('click', '.delete-employee-btn', function() {
         const employeeId = $(this).data('id');
         const employeeName = $(this).data('name');
         deleteEmployee(employeeId, employeeName);
     });
     
     $('#bulk-add-quick-note').on('click', function(e) {
         e.preventDefault();
         const selectedIds = getSelectedEmployeeIds();
         if (selectedIds.length !== 1) { 
             Swal.fire('Select One Employee', 'Please select exactly one employee to add a quick note.', 'warning');
             return;
         }
         const employeeId = selectedIds[0];
         const employeeName = employeesDataTable.row($(`input[value="${employeeId}"]`).closest('tr')).data()[3];
         showQuickNoteModal(employeeId, employeeName);
     });

     
     $searchNameInput.on('keyup', function() {
         currentSearchQuery = $(this).val();
         loadEmployees({ name: currentSearchQuery });
     });

     $('#employee-reports-btn').on('click', function() {
         // console.log("Employee Reports button clicked.");
         showReportSelectionModal();
     });

     $('#bulk-add-payment-btn').on('click', function(e) {
         e.preventDefault();
         const selectedIds = getSelectedEmployeeIds();
         if (selectedIds.length === 0) { Swal.fire('No Employees Selected', 'Please select at least one employee for this action.', 'warning'); return; }
         showBulkAddPaymentModal(selectedIds);
     });

     $('#bulk-add-deduction-btn').on('click', function(e) {
         e.preventDefault();
         const selectedIds = getSelectedEmployeeIds();
         if (selectedIds.length === 0) { Swal.fire('No Employees Selected', 'Please select at least one employee for this action.', 'warning'); return; }
         showBulkAddDeductionModal(selectedIds);
     });
     
     $('#bulk-change-status-btn-action').on('click', function(e) {
         e.preventDefault();
         const selectedIds = getSelectedEmployeeIds();
         if (selectedIds.length === 0) { Swal.fire('No Employees Selected', 'Please select at least one employee for this action.', 'warning'); return; }
         showBulkChangeStatusModal(selectedIds);
     });

     $('#bulk-delete-employees-btn-action').on('click', function(e) {
         e.preventDefault();
         const selectedIds = getSelectedEmployeeIds();
         if (selectedIds.length === 0) { Swal.fire('No Employees Selected', 'Please select at least one employee for deletion.', 'warning'); return; }
         bulkDeleteEmployees(selectedIds);
     });

     $('#quick-edit-selected-btn-action').on('click', function(e) {
         e.preventDefault();
         const selectedIds = getSelectedEmployeeIds();
         if (selectedIds.length === 0) { Swal.fire('No Employees Selected', 'Please select at least one employee for quick editing.', 'warning'); return; }
         showQuickEditSelectedEmployeesModal(selectedIds);
     });

     $('#export-csv-btn').on('click', function() { exportTableData('csv'); });
     $('#export-excel-btn').on('click', function() { exportTableData('excel'); });

     $('.filter-multiselect-button').on('click', function(e) {
         e.stopPropagation();
         const filterType = $(this).attr('id').replace('-filter-toggle', '');
         $(`#${filterType}-filter-dropdown`).slideToggle(100);
         if (typeof lucide !== 'undefined') { lucide.createIcons(); }
     });

     $(document).on('click', function(e) {
         if (!$(e.target).closest('.filter-multiselect-container').length) {
             $('.filter-multiselect-dropdown').slideUp(100);
         }
     });

     $('.filter-multiselect-dropdown').on('change', 'input[type="checkbox"]', function() {
         const filterType = $(this).closest('.filter-multiselect-dropdown').attr('id').replace('-filter-dropdown', '');
         const $this = $(this);
         const value = $this.val();
         const $allCheckbox = $(`#${filterType}-filter-dropdown .filter-${filterType}-checkbox[value="all"]`);
         const $individualCheckboxes = $(`#${filterType}-filter-dropdown .filter-${filterType}-checkbox:not([value="all"])`);

         if (value === 'all') {
             if ($this.is(':checked')) {
                 $individualCheckboxes.prop('checked', false).parent().removeClass('selected');
                 $allCheckbox.prop('checked', true).parent().addClass('selected');
                 if (filterType === 'department') currentSelectedDepartments = ['all'];
                 else if (filterType === 'status') currentSelectedStatuses = ['all'];
             } else {
                 if ($individualCheckboxes.filter(':checked').length === 0) {
                     $allCheckbox.prop('checked', true).parent().addClass('selected');
                     Swal.fire({toast: true, position: 'top-end', showConfirmButton: false, timer: 2000, icon: 'info', title: 'Cannot unselect "All" without selecting others.'});
                 }
             }
         } else {
             $allCheckbox.prop('checked', false).parent().removeClass('selected');
             if ($this.is(':checked')) {
                 $this.parent().addClass('selected');
             } else {
                 $this.parent().removeClass('selected');
             }

             if ($individualCheckboxes.filter(':checked').length === 0) {
                 $allCheckbox.prop('checked', true).parent().addClass('selected');
             }

             let selected = [];
             $individualCheckboxes.filter(':checked').each(function() {
                 selected.push($(this).val());
             });
             if (selected.length === 0 && $allCheckbox.is(':checked')) {
                 selected = ['all'];
             }

             if (filterType === 'department') currentSelectedDepartments = selected;
             else if (filterType === 'status') currentSelectedStatuses = selected;
         }

         updateMultiSelectText(filterType);
         loadEmployees({
             department_id: currentSelectedDepartments,
             status: currentSelectedStatuses,
             name: currentSearchQuery
         });
     });

     // Initial call to load employees and financial types
     loadEmployees();
     fetchFinancialTypes();

     const requiredFieldsByTab = {
         'personal': ['first_name', 'last_name', 'contact_email', 'contact_mobile', 'date_of_birth', 'country'],
         'company-info': ['designation', 'department_id', 'date_of_joining'],
         'payroll': ['basic_salary', 'currency'],
         'bank-info': ['bank_name', 'bank_iban', 'account_title', 'branch_code'],
         'dependents': ['emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship']
     };
     function checkAllTabsValidity() {
         const allTabs = Object.keys(requiredFieldsByTab);
         for (const tabName of allTabs) {
             const $tabContent = $(`.swal-tab-content[data-tab-content="${tabName}"]`);
             
             if (requiredFieldsByTab[tabName]) {
                 for (const fieldName of requiredFieldsByTab[tabName]) {
                     const $field = $tabContent.find(`[name="${fieldName}"]`);
                     if ($field.length && !$field.val()?.trim()) {
                         return false; // Found an invalid field
                     }
                 }
             }
         }
         return true; // All required fields are valid
     }
});
</script>
</body>
</html>