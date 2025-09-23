<?php
/**
 * department.php - Department Management Page.
 * This page allows managing company departments (view, add, edit, delete).
 * It relies on 'api.php' for all backend data operations, which in turn
 * uses 'classes/DepartmentManager.php'.
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

// Include configuration - defines $pdo connection if used globally, and other constants
require_once 'config.php';
// For this page, we don't directly instantiate DepartmentManager here,
// as all data operations go through api.php.
// However, ensure DepartmentManager.php is accessible to api.php.

// Set Navbar variables for this page
$pageTitle = "Department Management";
$pageIconClass = "building-2"; // Lucide icon for building/departments
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

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

    <style>
        /* General styling for cards, tables, forms consistent with employees.php */
        /* Assuming style.css handles most of these. Only add specific overrides here. */

        /* Styles for Add/Edit Department Modal forms */
        .department-form-modal-content {
            text-align: left;
            padding: 10px;
        }
        .department-form-modal-content .form-group {
            margin-bottom: 15px;
        }
        .department-form-modal-content label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--text-color);
        }
        .department-form-modal-content input[type="text"],
        .department-form-modal-content textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-sm);
            background-color: var(--background-color-light);
            color: var(--text-color);
        }
        .department-form-modal-content .invalid-feedback {
            color: var(--danger-color);
            font-size: 0.8em;
            margin-top: 5px;
            display: none; /* Hidden by default */
        }
        .department-form-modal-content input.is-invalid,
        .department-form-modal-content textarea.is-invalid {
            border-color: var(--danger-color);
        }
        .required-asterisk {
            color: var(--danger-color);
            margin-left: 3px;
        }

        /* DataTables specific adjustments if needed */
        #departments-table_filter {
            text-align: right; /* Align search box to the right */
        }
        #departments-table_filter input {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-sm);
            background-color: var(--background-color-light);
            color: var(--text-color);
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 6px 10px;
            margin: 0 2px;
            border: 1px solid var(--border-color);
            background-color: var(--background-color-light);
            color: var(--text-color);
            border-radius: var(--border-radius-sm);
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background-color: var(--primary-bg-light);
            color: var(--primary-dark);
            border-color: var(--primary-light);
        }

    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="page-content">
    <div class="page-header">
        <h1><i data-lucide="<?= htmlspecialchars($pageIconClass); ?>"></i> <?= htmlspecialchars($pageTitle); ?></h1>
        <p class="page-subtitle">Manage all company departments.</p>
    </div>

    <div class="filter-actions-row">
        <div class="top-search-bar" style="max-width: 300px;">
            <input type="text" id="search-department-name" placeholder="Search department..." aria-label="Search departments">
            <i data-lucide="search" class="search-icon"></i>
        </div>
        <button class="btn btn-success" id="add-department-btn">
            <i data-lucide="plus"></i> Add New Department
        </button>
    </div>

    <div class="card department-list-card">
        <h3 class="section-title"><i data-lucide="building-2"></i> Department List</h3>

        <div class="table-container">
            <table id="departments-table" class="display responsive nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Department Name</th>
                        <th>Description</th>
                        <th>Created At</th>
                        <th>Updated At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    </tbody>
            </table>
            <div id="no-departments-message" class="no-results" style="display: none;">No departments found.</div>
        </div>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.1/dist/sweetalert2.all.min.js"></script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>


<script>
$(document).ready(function() {
    console.log("DOM ready. Initializing department.php JS.");

    // Initialize Lucide icons on page load
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
        console.log("Initial Lucide icons rendered.");
    } else {
        console.error("Lucide icons library not loaded.");
    }

    // --- UI Elements ---
    const $addDepartmentBtn = $('#add-department-btn');
    const $departmentsTableBody = $('#departments-table tbody');
    const $noDepartmentsMessage = $('#no-departments-message');
    const $searchDepartmentNameInput = $('#search-department-name');

    let departmentsDataTable; // DataTable instance

    // --- Helper Functions ---
    /**
     * Escapes HTML entities in a string to prevent XSS.
     * @param {string|number} unsafe The string or number to escape.
     * @returns {string|number} The escaped string or original number.
     */
    function escapeHtml(unsafe) {
        if (typeof unsafe !== 'string' && typeof unsafe !== 'number') return unsafe;
        return String(unsafe).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    /**
     * Displays an error message below a form field in a modal.
     * @param {HTMLElement} fieldElement The input/textarea HTML element.
     * @param {string} message The error message to display.
     */
    function showModalError(fieldElement, message) {
        const $field = $(fieldElement);
        let $errorDiv = $field.next('.invalid-feedback');
        if ($errorDiv.length === 0) {
            $errorDiv = $('<div class="invalid-feedback"></div>').insertAfter($field);
        }
        $errorDiv.text(message).show();
        $field.addClass('is-invalid');
    }

    /**
     * Clears an error message from below a form field in a modal.
     * @param {HTMLElement} fieldElement The input/textarea HTML element.
     */
    function clearModalError(fieldElement) {
        const $field = $(fieldElement);
        const $errorDiv = $field.next('.invalid-feedback');
        if ($errorDiv.length) {
            $errorDiv.hide().text('');
        }
        $field.removeClass('is-invalid');
    }

    /**
     * Clears all validation error messages and styles from a given modal context.
     * @param {HTMLElement} modalContext The DOM element (e.g., SweetAlert popup) to clear errors from.
     */
    function clearAllModalErrors(modalContext) {
        const $modalContext = $(modalContext);
        $modalContext.find('.invalid-feedback').hide().text('');
        $modalContext.find('.form-control').removeClass('is-invalid');
    }

    /**
     * Handles AJAX errors, logging them to console and displaying a SweetAlert.
     * @param {jqXHR} jqXHR The jQuery XMLHttpRequest object.
     * @param {string} textStatus The status of the request (e.g., "error", "timeout").
     * @param {string} errorThrown The HTTP status text (e.g., "Not Found", "Internal Server Error").
     * @param {string} context A descriptive string for the operation that failed.
     */
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

        Swal.fire({
            title: 'Error',
            html: `<p>${message}</p><small class="text-muted">${details}</small>`,
            icon: 'error'
        });
    }

    // --- Load Departments Function ---
    /**
     * Fetches and displays department data in the DataTable.
     */
    function loadDepartments() {
        console.log("Loading departments...");
        $noDepartmentsMessage.hide();

        Swal.fire({
            title: 'Loading Departments...',
            text: 'Please wait',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: 'api.php',
            method: 'GET',
            data: { action: 'get_departments' }, // Call the API to get department data
            dataType: 'json',
            success: function(response) {
                Swal.close(); // Close loading spinner

                // Destroy existing DataTable instance before re-initializing
                if ($.fn.DataTable.isDataTable('#departments-table')) {
                    departmentsDataTable.destroy();
                    console.log("Destroyed existing DataTable instance.");
                }
                $departmentsTableBody.empty(); // Clear table body HTML

                if (response.success && response.departments && response.departments.length > 0) {
                    const dataForDataTable = response.departments.map(dept => [
                        escapeHtml(dept.id),
                        escapeHtml(dept.department_name || 'N/A'), // Use department_name from alias
                        escapeHtml(dept.description || 'N/A'),
                        escapeHtml(dept.created_at || 'N/A'),
                        escapeHtml(dept.updated_at || 'N/A'),
                        `
                        <div style="display:flex; gap:5px;">
                            <button class="btn btn-primary btn-small edit-department-btn" data-id="${dept.id}" data-name="${escapeHtml(dept.department_name)}" data-description="${escapeHtml(dept.description || '')}" title="Edit Department"><i data-lucide="edit"></i></button>
                            <button class="btn btn-danger btn-small delete-department-btn" data-id="${dept.id}" data-name="${escapeHtml(dept.department_name)}" title="Delete Department"><i data-lucide="trash-2"></i></button>
                        </div>
                        `
                    ]);

                    // Initialize DataTable with new data
                    departmentsDataTable = $('#departments-table').DataTable({
                        "data": dataForDataTable,
                        "paging": true,
                        "lengthMenu": [10, 25, 50],
                        "pageLength": 10,
                        "ordering": true,
                        "info": true,
                        "searching": true, // Enable default DataTables search
                        "autoWidth": false,
                        "responsive": true,
                        "dom": '<"top"fl>rt<"bottom"ip><"clear">', // Layout control
                        "columnDefs": [
                            { "orderable": false, "targets": [5] } // Actions column not sortable
                        ],
                        "order": [[1, 'asc']], // Default sort by Department Name
                        "language": {
                            "emptyTable": "No departments found."
                        },
                        "initComplete": function(settings, json) {
                            // Re-render Lucide icons after DataTable is drawn
                            if (typeof lucide !== 'undefined') {
                                lucide.createIcons({nodes: $('#departments-table i[data-lucide]')});
                            }
                        },
                        "drawCallback": function(settings) {
                             // Re-render icons on each draw (pagination, search, sort)
                            if (typeof lucide !== 'undefined') {
                                lucide.createIcons({nodes: $('#departments-table i[data-lucide]')});
                            }
                        }
                    });
                    console.log("Departments DataTable initialized with data.");

                    // Apply custom search input to DataTable
                    $searchDepartmentNameInput.off('keyup.customSearch').on('keyup.customSearch', function() {
                        departmentsDataTable.search(this.value).draw();
                    });

                } else {
                    $noDepartmentsMessage.text(response.message || 'No departments found.').show();
                    console.log("No departments found or response not successful.");
                    if ($.fn.DataTable.isDataTable('#departments-table')) {
                        departmentsDataTable.destroy();
                    }
                    $departmentsTableBody.empty();
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                Swal.close();
                handleAjaxError(jqXHR, textStatus, errorThrown, 'Failed to load department data');
                $noDepartmentsMessage.text('Error loading departments.').show();
                if ($.fn.DataTable.isDataTable('#departments-table')) {
                    departmentsDataTable.destroy();
                }
                $departmentsTableBody.empty();
            }
        });
    }

    // --- Add/Edit Department Modal ---
    /**
     * Displays the Add/Edit Department modal.
     * @param {Object|null} departmentData Pre-fills the form for editing an existing department.
     */
    function showDepartmentFormModal(departmentData = null) {
        const isEdit = departmentData !== null;

        Swal.fire({
            title: isEdit ? 'Edit Department' : 'Add New Department',
            html: `
                <form id="department-form" class="department-form-modal-content">
                    <input type="hidden" name="id" value="${departmentData?.id || ''}">
                    <div class="form-group">
                        <label for="department_name">Department Name<span class="required-asterisk">*</span></label>
                        <input type="text" id="department_name" name="department_name" class="form-control" value="${escapeHtml(departmentData?.department_name || '')}" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="form-group">
                        <label for="department_description">Description</label>
                        <textarea id="department_description" name="description" class="form-control" rows="3">${escapeHtml(departmentData?.description || '')}</textarea>
                        <div class="invalid-feedback"></div>
                    </div>
                </form>
            `,
            showCancelButton: true,
            confirmButtonText: 'Save',
            didOpen: (modalElement) => {
                const $modalContent = $(modalElement);
                // Attach focus/blur to clear errors
                $modalContent.find('#department_name').on('input', function() { clearModalError(this); });
                $modalContent.find('#department_description').on('input', function() { clearModalError(this); });
            },
            preConfirm: () => {
                const $modalContent = $(Swal.getPopup());
                clearAllModalErrors($modalContent); // Clear all errors on preConfirm

                const departmentId = $modalContent.find('#department-form [name="id"]').val();
                const departmentName = $modalContent.find('#department_name').val().trim();
                const description = $modalContent.find('#department_description').val().trim();
                let isValid = true;

                if (!departmentName) {
                    showModalError($modalContent.find('#department_name'), 'Department name is required.');
                    isValid = false;
                }

                if (!isValid) {
                    Swal.showValidationMessage('Please correct the highlighted fields.');
                    return false;
                }

                Swal.showLoading();
                return $.ajax({
                    url: 'api.php',
                    method: 'POST',
                    data: {
                        action: isEdit ? 'update_department' : 'add_department',
                        id: departmentId,
                        department_name: departmentName,
                        description: description,
                        csrf_token: $('meta[name="csrf-token"]').attr('content')
                    },
                    dataType: 'json'
                }).fail((jqXHR) => {
                    Swal.showValidationMessage(jqXHR.responseJSON?.message || 'Request failed.');
                });
            }
        }).then((result) => {
            if (result.isConfirmed && result.value.success) {
                Swal.fire('Success!', result.value.message, 'success');
                loadDepartments(); // Reload departments to reflect changes
            } else if (result.isConfirmed) {
                Swal.fire('Error', result.value.message || 'Could not save department.', 'error');
            }
        });
    }

    // --- Delete Department Function ---
    /**
     * Handles deletion of a single department record after confirmation.
     * @param {number} departmentId
     * @param {string} departmentName
     */
    function deleteDepartmentRecord(departmentId, departmentName) {
        Swal.fire({
            title: 'Are you sure?',
            text: `You are about to delete department "${escapeHtml(departmentName)}". This action cannot be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: 'var(--danger-color)',
            cancelButtonColor: 'var(--text-light)',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.showLoading();
                $.ajax({
                    url: 'api.php',
                    method: 'POST',
                    data: { action: 'delete_department', id: departmentId, csrf_token: $('meta[name="csrf-token"]').attr('content') },
                    dataType: 'json',
                    success: function(response) {
                        Swal.close();
                        if (response.success) {
                            Swal.fire('Deleted!', response.message || 'Department has been deleted.', 'success');
                            loadDepartments(); // Reload departments table
                        } else {
                            Swal.fire('Error', response.message || 'Failed to delete department.', 'error');
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) { Swal.close(); handleAjaxError(jqXHR, textStatus, errorThrown, 'Failed to delete department.'); }
                });
            }
        });
    }

    // --- Event Listeners for Department Page ---
    $addDepartmentBtn.on('click', function() {
        console.log("Add New Department button clicked.");
        showDepartmentFormModal(); // Open modal in add mode
    });

    // Delegate event listeners for Edit and Delete buttons in the DataTable
    $departmentsTableBody.on('click', '.edit-department-btn', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const description = $(this).data('description');
        showDepartmentFormModal({ id: id, department_name: name, description: description });
    });

    $departmentsTableBody.on('click', '.delete-department-btn', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        deleteDepartmentRecord(id, name);
    });

    // Initial load of departments when the page is ready
    loadDepartments();

    console.log("department.php JS initialization complete.");
}); // End document ready
</script>
</body>

</html>