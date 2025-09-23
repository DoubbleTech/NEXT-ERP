<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) { header('Location: index.php?error=login_required'); exit; }
require_once 'config.php';
require_once 'functions.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pageTitle = "Reimbursements";
$pageIconClass = "receipt";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinLab - <?= htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #6366f1;
            --secondary: #6b7280;
            --secondary-light: #9ca3af;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
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

        /* Status Badges - Using Tailwind classes for colors */
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }
        .status-needs-correction { background-color: #cfe2ff; color: #052c65; }

        /* Table custom styling for DataTables */
        .dataTables_wrapper .dataTables_paginate, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter { display: none !important; }
        .dataTables_wrapper .table-container { overflow-x: auto; }
        #claims-table { width: 100% !important; border-collapse: collapse; }
        #claims-table th, #claims-table td { padding: 1rem; text-align: left; }
        #claims-table th { font-weight: 600; color: var(--text-light); }
        #claims-table tbody tr:nth-child(even) { background-color: #f9fafb; }

        /* SweetAlert2 Modal Enhancements */
        .swal2-title { font-size: 1.5rem; font-weight: 600; color: var(--text-color); }
        .swal2-html-container { text-align: left; }
        .claim-detail-section { margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px dashed var(--border-color); }
        .claim-detail-section:last-child { border-bottom: none; padding-bottom: 0; margin-bottom: 0; }
        .claim-detail-section h4 { font-size: 1.125rem; font-weight: 600; color: var(--text-color); margin: 0 0 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); }
        .claim-detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
        .claim-detail-item { padding: 0.5rem 0; }
        .claim-detail-item strong { display: block; font-size: 0.85rem; color: var(--text-light); margin-bottom: 0.2rem; }
        .claim-detail-item span { font-size: 1rem; color: var(--text-color); }
        .line-items-list { list-style-type: none; padding: 0; margin: 0; }
        .line-items-list li { background: var(--bg-page); border: 1px solid var(--border-color); border-radius: var(--border-radius); padding: 1.5rem; margin-bottom: 1rem; box-shadow: var(--shadow-sm); }
        .line-item-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; }
        .line-item-grid .item-field strong { font-size: 0.8rem; color: var(--text-light); display: block; margin-bottom: 0.2rem; }
        .line-item-grid .item-field span { font-size: 0.9rem; color: var(--text-color); }
        .line-item-grid .item-field.full-width { grid-column: 1 / -1; }
        .attachment-link { display: inline-flex; align-items: center; gap: 5px; text-decoration: none; }
        .attachment-link i { width: 1rem; height: 1rem; }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div id="file-drop-zone" class="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-gray-900 bg-opacity-70 text-white text-2xl text-center opacity-0 invisible transition-all duration-300 pointer-events-none">
    <i data-lucide="file-plus" class="w-20 h-20 text-white mb-4 animate-bounce"></i>
    <span id="drop-zone-text">Drop your attachment here!</span>
    <span class="text-base text-gray-300 mt-2">(JPG, PNG, PDF, GIF, WEBP up to 5MB)</span>
</div>

<div class="page-content">
    <div class="page-header flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-4">
            <i data-lucide="<?= htmlspecialchars($pageIconClass); ?>" class="w-8 h-8 text-indigo-500"></i>
            <?= htmlspecialchars($pageTitle); ?>
        </h1>
        <p class="text-gray-500 text-lg hidden md:block">Track and manage all employee expense claims.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="summary-card bg-gradient-to-br from-indigo-500 to-blue-500 text-white p-6 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 cursor-pointer flex flex-col items-start gap-4" id="summary-total-card" data-filter-status="">
            <i data-lucide="clipboard-list" class="card-icon w-10 h-10"></i>
            <div class="flex flex-col">
                <p class="text-4xl font-extrabold" id="summary-total-amount">0.00</p>
                <h3 class="text-sm uppercase font-semibold text-indigo-100">Total Claims</h3>
            </div>
            <small class="text-indigo-200"><span id="summary-total-count">0</span> Claims</small>
        </div>
        <div class="summary-card bg-gradient-to-br from-green-500 to-emerald-500 text-white p-6 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 cursor-pointer flex flex-col items-start gap-4" id="summary-approved-card" data-filter-status="Approved">
            <i data-lucide="check-circle" class="card-icon w-10 h-10"></i>
            <div class="flex flex-col">
                <p class="text-4xl font-extrabold" id="summary-approved-amount">0.00</p>
                <h3 class="text-sm uppercase font-semibold text-green-100">Approved</h3>
            </div>
            <small class="text-green-200"><span id="summary-approved-count">0</span> Claims</small>
        </div>
        <div class="summary-card bg-gradient-to-br from-yellow-500 to-amber-500 text-white p-6 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 cursor-pointer flex flex-col items-start gap-4" id="summary-pending-card" data-filter-status="Pending">
            <i data-lucide="clock" class="card-icon w-10 h-10"></i>
            <div class="flex flex-col">
                <p class="text-4xl font-extrabold" id="summary-pending-amount">0.00</p>
                <h3 class="text-sm uppercase font-semibold text-yellow-100">Pending</h3>
            </div>
            <small class="text-yellow-200"><span id="summary-pending-count">0</span> Claims</small>
        </div>
        <div class="summary-card bg-gradient-to-br from-red-500 to-rose-500 text-white p-6 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 cursor-pointer flex flex-col items-start gap-4" id="summary-rejected-card" data-filter-status="Rejected">
            <i data-lucide="x-circle" class="card-icon w-10 h-10"></i>
            <div class="flex flex-col">
                <p class="text-4xl font-extrabold" id="summary-rejected-amount">0.00</p>
                <h3 class="text-sm uppercase font-semibold text-red-100">Rejected</h3>
            </div>
            <small class="text-red-200"><span id="summary-rejected-count">0</span> Claims</small>
        </div>
    </div>

    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
        <div class="relative w-full md:w-1/2">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5"></i>
            <input type="text" id="claims-search-input" class="w-full pl-10 pr-4 py-2 border rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200" placeholder="Search claims by ID, title or employee...">
        </div>
        <button class="btn btn-primary bg-indigo-600 text-white hover:bg-indigo-700 transition-colors duration-200 font-semibold py-2 px-6 rounded-full shadow-lg whitespace-nowrap" id="add-claim-btn">
            <i data-lucide="plus" class="inline-block w-4 h-4 mr-2"></i>
            Add Claim or Drop File
        </button>
    </div>

    <div class="card p-6">
        <div class="card-header border-b-0 pb-0">
            <h3 class="text-xl font-semibold text-gray-800">All Reimbursement Claims</h3>
        </div>
        <div class="table-container pt-4">
            <table id="claims-table" class="display responsive nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>Claim ID</th>
                        <th>Employee</th>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div id="view-more-claims-btn-container" class="mt-6 text-center" style="display: none;">
            <button class="btn btn-primary bg-blue-600 text-white hover:bg-blue-700 transition-colors duration-200 py-2 px-8 rounded-full font-semibold" id="view-more-claims-btn">View More</button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
<script>
    // Note: The JavaScript below is largely the same as your original,
    // with minor adjustments to match the new HTML/class names.
    // The core logic and API calls remain identical.

    function htmlspecialchars(str) {
        if (typeof str != 'string') return str;
        var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return str.replace(/[&<>"']/g, m => map[m]);
    }

    function formatAmount(amount) {
        return parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function cleanAmountForSubmission(amountString) {
        if (typeof amountString !== 'string') return amountString;
        return amountString.replace(/,/g, '');
    }

    function validateAttachment(file) {
        const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf', 'image/gif', 'image/webp'];
        const maxSize = 5 * 1024 * 1024;
        if (!file) return { success: true };
        if (!allowedTypes.includes(file.type)) {
            return { success: false, message: `Invalid file type: ${file.type}.` };
        }
        if (file.size > maxSize) {
            return { success: false, message: `File size exceeds limit: ${formatBytes(file.size)}. Max ${formatBytes(maxSize)}.` };
        }
        return { success: true };
    }

    function formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    let offset = 0;
    const limit = 30;
    let claimsTable;
    let currentStatusFilter = '';

    const updateSummaryCards = () => {
        const csrf = $('meta[name="csrf-token"]').attr('content');
        const searchQuery = $('#claims-search-input').val();
        $.ajax({
            url: 'api.php?action=get_reimbursements_paginated',
            type: 'GET',
            data: { offset: 0, limit: 1, search_query: searchQuery, status_filter: '', csrf_token: csrf },
            success: function(response) {
                if (response.success && response.summary) {
                    $('#summary-total-amount').text(formatAmount(response.summary.total_amount));
                    $('#summary-total-count').text(response.summary.total_claims);
                    $('#summary-approved-amount').text(formatAmount(response.summary.approved_amount));
                    $('#summary-approved-count').text(response.summary.approved);
                    $('#summary-pending-amount').text(formatAmount(response.summary.pending_amount));
                    $('#summary-pending-count').text(response.summary.pending);
                    $('#summary-rejected-amount').text(formatAmount(response.summary.rejected_amount));
                    $('#summary-rejected-count').text(response.summary.rejected);
                } else {
                    console.error('Failed to retrieve summary data:', response.message);
                }
            },
            error: function(xhr) {
                console.error("AJAX Error in updateSummaryCards:", xhr.responseText);
            }
        });
    };

    const loadClaims = () => {
        const csrf = $('meta[name="csrf-token"]').attr('content');
        const searchQuery = $('#claims-search-input').val();
        if (offset === 0) {
            $('#view-more-claims-btn').text('Loading...').prop('disabled', true);
            if (claimsTable) claimsTable.clear().draw();
        }
        $.ajax({
            url: 'api.php?action=get_reimbursements_paginated',
            type: 'GET',
            data: { offset: offset, limit: limit, search_query: searchQuery, status_filter: currentStatusFilter, csrf_token: csrf },
            success: function(response) {
                if (response.success) {
                    if (claimsTable) {
                        claimsTable.rows.add(response.data).draw(false);
                    } else {
                        claimsTable = $('#claims-table').DataTable({
                            data: response.data,
                            columns: [
                                { data: 'id', render: data => `CLAIM-${String(data).padStart(4,'0')}` },
                                { data: 'employee_name' },
                                { data: 'claim_title' },
                                { data: 'claim_date' },
                                { data: 'total_amount', render: data => formatAmount(data) },
                                { data: 'status', render: data => `<span class="status-badge status-${data.toLowerCase().replace(' ', '-')}">${data}</span>` },
                                { 
                                    data: null,
                                    render: (data, type, row) => {
                                        let actions = `<button class="btn btn-small btn-primary view-claim-btn" data-id="${row.id}" title="View"><i data-lucide="eye"></i></button>`;
                                        if (row.status === 'Pending' || row.status === 'Needs Correction') {
                                            actions += `<button class="btn btn-small btn-warning edit-claim-btn" data-id="${row.id}" title="Edit"><i data-lucide="edit-2"></i></button>
                                                <button class="btn btn-small btn-success approve-claim-btn" data-id="${row.id}" title="Approve"><i data-lucide="check"></i></button>
                                                <button class="btn btn-small btn-danger reject-claim-btn" data-id="${row.id}" title="Reject"><i data-lucide="x"></i></button>`;
                                        }
                                        return actions;
                                    },
                                    orderable: false
                                }
                            ],
                            paging: false, info: false, filter: false, responsive: true, order: [[3, 'desc']],
                            drawCallback: () => lucide.createIcons()
                        });
                        window.claimsTable = claimsTable;
                    }
                    offset += response.data.length;
                    if (response.data.length < limit || (offset >= response.total_filtered_claims)) {
                        $('#view-more-claims-btn-container').hide();
                    } else {
                        $('#view-more-claims-btn-container').show();
                    }
                } else {
                    Swal.fire('Error', response.message || 'Failed to load claims.', 'error');
                }
            },
            error: function(xhr) {
                console.error("AJAX Error in loadClaims:", xhr.responseText);
                Swal.fire('Error', 'An error occurred while loading claims.', 'error');
            },
            complete: function() {
                $('#view-more-claims-btn').text('View More').prop('disabled', false);
            }
        });
    };

    function openClaimForm(claimId = null, preSelectedFile = null) {
        const isEdit = claimId !== null;
        const csrf = $('meta[name="csrf-token"]').attr('content');
        Swal.fire({ title: isEdit ? 'Loading Claim...' : 'Loading Form Data...', didOpen: () => Swal.showLoading(), allowOutsideClick: false });

        Promise.all([
            fetch('api.php?action=get_employees', { headers: { 'X-CSRF-Token': csrf } }).then(res => res.json()),
            fetch('api.php?action=get_reimbursement_categories', { headers: { 'X-CSRF-Token': csrf } }).then(res => res.json()),
            isEdit ? fetch(`api.php?action=get_reimbursement_claim_details&id=${claimId}`, { headers: { 'X-CSRF-Token': csrf } }).then(res => res.json()) : Promise.resolve(null)
        ]).then(([employeesRes, categoriesRes, claimRes]) => {
            Swal.close();
            if (!employeesRes.success || !categoriesRes.success) {
                Swal.fire('Error', 'Failed to load essential data (employees or categories).', 'error');
                return;
            }
            if (isEdit && (!claimRes || !claimRes.success || !claimRes.details)) {
                Swal.fire('Error', 'Failed to load claim details for editing.', 'error');
                return;
            }

            const employees = employeesRes.employees || [];
            const uniqueCategoriesMap = new Map();
            (categoriesRes.categories || []).forEach(c => {
                if (!uniqueCategoriesMap.has(c.name.toLowerCase())) uniqueCategoriesMap.set(c.name.toLowerCase(), c);
            });
            const uniqueCategories = Array.from(uniqueCategoriesMap.values());
            const claim = claimRes ? claimRes.details : null;
            const employeeOptions = employees.map(e => `<option value="${e.id}" ${claim && claim.employee_id==e.id?'selected':''}>${htmlspecialchars(e.full_name)}</option>`).join('');
            const generateCategoryOptions = (selectedCategoryId = null) => {
                return uniqueCategories.map(c => `<option value="${c.id}" ${selectedCategoryId !== null && c.id == selectedCategoryId ? 'selected' : ''}>${htmlspecialchars(c.name)}</option>`).join('');
            };
            const currencyOptions = ['MYR','USD','GBP','AED','PKR'].map(curr => `<option value="${curr}" ${claim && claim.currency===curr?'selected':''}>${curr}</option>`).join('');
            const today = new Date().toISOString().slice(0, 10);
            const titleVal = claim ? htmlspecialchars(claim.claim_title) : '';
            const totalVal = claim ? formatAmount(claim.total_amount) : '0.00';

            const formHtml = `
                <form id="claim-form" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Employee</label>
                            <select name="claim[employee_id]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>${employeeOptions}</select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Claim Title</label>
                            <input type="text" name="claim[claim_title]" value="${titleVal}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Currency</label>
                            <select name="claim[currency]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">${currencyOptions}</select>
                        </div>
                    </div>
                    <input type="hidden" name="claim[submission_date]" value="${today}">

                    <h4 class="text-lg font-semibold text-gray-800">Line Items</h4>
                    <div id="line-items-container" class="space-y-4"></div>
                    <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                        <button type="button" id="add-line-item-btn" class="btn btn-info flex items-center gap-2 text-indigo-600 hover:text-indigo-800">
                            <i data-lucide="plus" class="w-5 h-5"></i> Add Line
                        </button>
                        <div class="text-lg font-bold">Total: <span id="claim-total">${totalVal}</span></div>
                        <input type="hidden" name="claim[total_amount]" id="claim-total-input" value="${totalVal}">
                    </div>
                </form>`;

            Swal.fire({
                title: isEdit ? 'Edit Reimbursement Claim' : 'New Reimbursement Claim',
                html: formHtml,
                width: '90%',
                showCancelButton: true,
                confirmButtonText: isEdit ? 'Update Claim' : 'Submit Claim',
                customClass: { container: 'swal2-container' },
                didOpen: () => {
                    initializeClaimForm(generateCategoryOptions, claim, preSelectedFile);
                },
                preConfirm: () => submitClaim(isEdit, claimId)
            }).then(res => {
                if (res.isConfirmed && res.value && res.value.success) {
                    Swal.fire('Success', res.value.message || (isEdit ? 'Claim updated!' : 'Claim submitted!'), 'success');
                    offset = 0;
                    loadClaims();
                    updateSummaryCards();
                } else if (res.isConfirmed && res.value && !res.value.success) {
                    Swal.fire('Error', res.value.message || (isEdit ? 'Failed to update claim.' : 'Failed to submit claim.'), 'error');
                } else if (res.isConfirmed) {
                    Swal.fire('Error', 'An unexpected error occurred during claim submission.', 'error');
                }
            });
        }).catch(error => {
            Swal.fire('Error', 'An error occurred while preparing the form: ' + error.message, 'error');
            console.error('Error in openClaimForm:', error);
        });
    }

    function initializeClaimForm(generateCategoryOptionsFn, claim, preSelectedFile = null) {
        lucide.createIcons();
        const container = $('#line-items-container');
        const totalSpan = $('#claim-total');
        const totalInput = $('#claim-total-input');
        
        const updateTotal = () => {
            let total = 0;
            container.find('.line-item-amount').each(function() {
                total += parseFloat(cleanAmountForSubmission($(this).val())) || 0;
            });
            totalSpan.text(formatAmount(total));
            totalInput.val(formatAmount(total));
        };

        const addLineItem = (item=null) => {
            const idx = item && item.id ? item.id : Date.now() + Math.floor(Math.random() * 1000);
            const dateVal = item ? item.expense_date : '';
            const descVal = item ? htmlspecialchars(item.description) : '';
            const amtVal = item ? formatAmount(item.amount) : '';
            const categoryOptionsHtml = generateCategoryOptionsFn(item ? item.category_id : null);
            const existingAttachmentHtml = item && item.attachment_path ? `
                <div class="mt-2 text-sm text-gray-600 flex items-center gap-4">
                    Current Attachment: <a href="${item.attachment_path}" target="_blank" class="text-blue-500 hover:underline">View File</a>
                    <input type="hidden" name="line_items[${idx}][attachment_path_existing]" value="${item.attachment_path}">
                    <label class="flex items-center gap-1"><input type="checkbox" name="line_items[${idx}][delete_attachment]" value="1"> Delete Current</label>
                </div>` : '<div class="mt-2 text-sm text-gray-600 flex items-center">No attachment</div>';
            
            const html = `
                <div class="line-item p-4 border rounded-lg shadow-sm bg-gray-50">
                    <input type="hidden" name="line_items[${idx}][id]" value="${item ? item.id : ''}">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date</label>
                            <input type="date" name="line_items[${idx}][expense_date]" value="${dateVal}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Category</label>
                            <select name="line_items[${idx}][category_id]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>${categoryOptionsHtml}</select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Amount</label>
                            <input type="number" step="0.01" name="line_items[${idx}][amount]" value="${amtVal}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm line-item-amount" required>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <input type="text" name="line_items[${idx}][description]" value="${descVal}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div class="mt-4 flex items-end gap-4">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700">Attachment</label>
                            <input type="file" name="attachments[${idx}]" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 line-item-attachment-input">
                        </div>
                        <button type="button" class="btn btn-danger py-2 px-4 rounded-full text-white bg-red-500 hover:bg-red-600 transition-colors duration-200 remove-line-item-btn"><i data-lucide="trash-2"></i></button>
                    </div>
                    ${existingAttachmentHtml}
                </div>`;
            container.append(html);
            lucide.createIcons();
            if (preSelectedFile && container.find('.line-item').length === 1) {
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(preSelectedFile);
                container.find('.line-item:first .line-item-attachment-input')[0].files = dataTransfer.files;
                const fileName = preSelectedFile.name;
                container.find('.line-item:first input[name*="[description]"]').val(`Expense for ${fileName}`);
                if (!$('#claim-form input[name*="[claim_title]"]').val()) {
                    $('#claim-form input[name*="[claim_title]"]').val(`Claim for ${fileName.replace(/\.[^/.]+$/, "")}`);
                }
            }
        };

        container.off('click', '.remove-line-item-btn').on('click', '.remove-line-item-btn', function() { $(this).closest('.line-item').remove(); updateTotal(); });
        container.off('input change', '.line-item-amount').on('input change', '.line-item-amount', updateTotal);
        container.off('change', '.line-item-attachment-input').on('change', '.line-item-attachment-input', function() {
            const file = this.files[0];
            const validationResult = validateAttachment(file);
            if (!validationResult.success) { Swal.fire('Invalid File', validationResult.message, 'error'); $(this).val(''); }
        });
        $('#add-line-item-btn').off('click').on('click', () => addLineItem());

        container.empty();
        if (claim && claim.line_items && claim.line_items.length > 0) {
            claim.line_items.forEach(item => addLineItem(item));
        } else {
            addLineItem();
        }
        updateTotal();
    }

    function submitClaim(isEdit=false, claimId=null) {
        const form = document.querySelector('#claim-form');
        const formData = new FormData();
        $(form).find('input, select, textarea').each(function() {
            const inputName = $(this).attr('name');
            let inputValue = $(this).val();
            if (inputName) {
                if (inputName === 'claim[total_amount]' || (inputName.startsWith('line_items[') && inputName.endsWith('[amount]'))) {
                    inputValue = cleanAmountForSubmission(inputValue);
                }
                if ($(this).is(':checkbox') && !$(this).is(':checked')) return;
                formData.append(inputName, inputValue);
            }
        });
        $(form).find('input[type="file"][name^="attachments["]').each(function() {
            const inputName = $(this).attr('name');
            if (this.files.length > 0) {
                const validationResult = validateAttachment(this.files[0]);
                if (!validationResult.success) { throw new Error(validationResult.message); }
                formData.append(inputName, this.files[0]);
            }
        });
        formData.append('action', isEdit ? 'update_reimbursement_claim' : 'submit_reimbursement_claim');
        if (isEdit) formData.append('claim_id', claimId);
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        formData.append('csrf_token', csrfToken);
        return fetch('api.php', { method: 'POST', body: formData })
            .then(r => {
                if (!r.ok) return r.text().then(text => { throw new Error('HTTP error: ' + r.status + ' - ' + text); });
                return r.json();
            })
            .then(data => {
                if (!data.success) { Swal.showValidationMessage(data.message || 'Failed to save claim.'); return false; }
                return data;
            })
            .catch(err => {
                console.error('Fetch error:', err);
                Swal.showValidationMessage('Error saving claim: ' + err.message);
                return false;
            });
    }

    function viewClaimDetails(id) {
        Swal.fire({ title: 'Loading Claim Details...', didOpen: () => Swal.showLoading() });
        const csrf = $('meta[name="csrf-token"]').attr('content');
        fetch(`api.php?action=get_reimbursement_claim_details&id=${id}`, { headers: { 'X-CSRF-Token': csrf } })
            .then(r => r.json())
            .then(data => {
                Swal.close();
                if (!data.success || !data.details) {
                    Swal.fire('Error', data.message || 'Failed to load claim details.', 'error');
                    return;
                }
                const claim = data.details;
                let lineItemsHtml = '';
                if (claim.line_items && claim.line_items.length > 0) {
                    lineItemsHtml = '<ul class="line-items-list">';
                    claim.line_items.forEach(item => {
                        const attachmentUrl = item.attachment_path ? item.attachment_path : '';
                        const attachmentLink = attachmentUrl ?
                            `<a href="${attachmentUrl}" target="_blank" class="attachment-link btn btn-sm bg-blue-500 text-white rounded-md px-3 py-1 text-sm hover:bg-blue-600"><i data-lucide="paperclip"></i> View Attachment</a>` :
                            `<span class="text-gray-500 text-sm">No Attachment</span>`;
                        lineItemsHtml += `
                            <li>
                                <div class="line-item-grid">
                                    <div class="item-field"><strong>Date:</strong> <span>${item.expense_date}</span></div>
                                    <div class="item-field"><strong>Category:</strong> <span>${htmlspecialchars(item.category_name)}</span></div>
                                    <div class="item-field"><strong>Amount:</strong> <span>${claim.currency} ${formatAmount(item.amount)}</span></div>
                                    <div class="item-field full-width"><strong>Description:</strong> <span>${htmlspecialchars(item.description)}</span></div>
                                    <div class="item-field full-width"><strong>Attachment:</strong> ${attachmentLink}</div>
                                </div>
                            </li>`;
                    });
                    lineItemsHtml += '</ul>';
                } else {
                    lineItemsHtml = '<p class="text-gray-500">No line items found for this claim.</p>';
                }

                const modalHtml = `
                    <div class="claim-detail-section">
                        <h4>Claim Overview</h4>
                        <div class="claim-detail-grid">
                            <div class="claim-detail-item"><strong>Claim ID:</strong> <span>CLAIM-${String(claim.id).padStart(4, '0')}</span></div>
                            <div class="claim-detail-item"><strong>Employee:</strong> <span>${htmlspecialchars(claim.employee_name)}</span></div>
                            <div class="claim-detail-item"><strong>Claim Title:</strong> <span>${htmlspecialchars(claim.claim_title)}</span></div>
                            <div class="claim-detail-item"><strong>Claim Date:</strong> <span>${claim.claim_date}</span></div>
                            <div class="claim-detail-item"><strong>Total Claim Amount:</strong> <span>${claim.currency} ${formatAmount(claim.total_amount)}</span></div>
                            <div class="claim-detail-item"><strong>Status:</strong> <span class="status-badge status-${claim.status.toLowerCase().replace(' ', '-')}">${claim.status}</span></div>
                            <div class="claim-detail-item"><strong>Submitted On:</strong> <span>${claim.submission_date}</span></div>
                            ${claim.supervisor_notes ? `<div class="claim-detail-item full-width"><strong>Supervisor Notes:</strong> <span>${htmlspecialchars(claim.supervisor_notes)}</span></div>` : ''}
                            ${claim.processed_by_name && claim.processed_date ? `<div class="claim-detail-item full-width"><strong>Processed By:</strong> <span>${htmlspecialchars(claim.processed_by_name)} on ${claim.processed_date}</span></div>` : ''}
                        </div>
                    </div>
                    <div class="claim-detail-section">
                        <h4>Line Items</h4>
                        ${lineItemsHtml}
                    </div>
                    <div class="claim-actions-footer flex justify-end gap-4 mt-6">
                        ${(claim.status === 'Pending' || claim.status === 'Needs Correction') ? `
                            <button class="btn bg-green-500 text-white hover:bg-green-600 rounded-full py-2 px-4 font-semibold" onclick="updateClaimStatus(${claim.id}, 'Approved', true, this)">Approve</button>
                            <button class="btn bg-red-500 text-white hover:bg-red-600 rounded-full py-2 px-4 font-semibold" onclick="updateClaimStatus(${claim.id}, 'Rejected', true, this)">Reject</button>
                            <button class="btn bg-yellow-500 text-white hover:bg-yellow-600 rounded-full py-2 px-4 font-semibold" onclick="updateClaimStatus(${claim.id}, 'Needs Correction', true, this)">Needs Correction</button>
                        ` : ''}
                    </div>
                `;
                Swal.fire({
                    title: `Reimbursement Claim: ${htmlspecialchars(claim.claim_title)}`,
                    html: modalHtml,
                    width: '80%',
                    showConfirmButton: true,
                    confirmButtonText: 'Close',
                    didOpen: () => { lucide.createIcons(); }
                });
            })
            .catch(error => { Swal.close(); Swal.fire('Error', 'An error occurred while fetching claim details: ' + error.message, 'error'); console.error('Error in viewClaimDetails:', error); });
    }

    function updateClaimStatus(claimId, status, fromViewModal = false, clickedElement = null) {
        const csrf = $('meta[name="csrf-token"]').attr('content');
        Swal.fire({
            title: `Confirm ${status} Claim?`,
            input: 'textarea',
            inputPlaceholder: 'Add notes (optional)',
            showCancelButton: true,
            confirmButtonText: `Yes, ${status}`,
            showLoaderOnConfirm: true,
            preConfirm: (notes) => {
                const formData = new FormData();
                formData.append('action', 'update_reimbursement_claim_status');
                formData.append('claim_id', claimId);
                formData.append('status', status);
                formData.append('notes', notes);
                formData.append('csrf_token', csrf);
                return fetch('api.php', { method: 'POST', body: formData })
                    .then(response => { if (!response.ok) return response.text().then(text => { throw new Error('HTTP error: ' + response.status + ' - ' + text); }); return response.json(); })
                    .then(data => { if (!data.success) throw new Error(data.message || 'Failed to update status.'); return data; })
                    .catch(error => { Swal.showValidationMessage(`Request failed: ${error}`); });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire('Success', result.value.message || 'Claim status updated.', 'success');
                if (window.claimsTable) {
                    let rowToUpdate = null;
                    claimsTable.data().each(function(rowData, index) { if (rowData.id === claimId) { rowToUpdate = claimsTable.row(index); return false; } });
                    if (!rowToUpdate && clickedElement) rowToUpdate = claimsTable.row($(clickedElement).closest('tr'));
                    if (rowToUpdate && rowToUpdate.length) {
                        const rowData = rowToUpdate.data();
                        rowData.status = status;
                        rowToUpdate.data(rowData).draw(false);
                        const statusCell = $(rowToUpdate.node()).find('td:eq(5)');
                        statusCell.html(`<span class="status-badge status-${status.toLowerCase().replace(' ', '-')}">${status}</span>`);
                        lucide.createIcons();
                        if (currentStatusFilter && currentStatusFilter !== status && currentStatusFilter !== '') {
                            rowToUpdate.remove().draw(false);
                        }
                    } else {
                        console.warn("Could not find DataTables row. Performing full reload.");
                        offset = 0;
                        loadClaims();
                    }
                } else {
                    console.warn("claimsTable not initialized, performing full reload.");
                    offset = 0;
                    loadClaims();
                }
                updateSummaryCards();
                if (fromViewModal) viewClaimDetails(claimId);
            }
        });
    }

    $(document).ready(function() {
        lucide.createIcons();
        loadClaims();
        updateSummaryCards();

        $('#view-more-claims-btn').on('click', loadClaims);
        $('#claims-search-input').on('keyup', function() {
            offset = 0;
            currentStatusFilter = '';
            loadClaims();
            updateSummaryCards();
        });
        $('#add-claim-btn').on('click', () => openClaimForm());
        $('#claims-table').on('click', '.edit-claim-btn', function() { openClaimForm($(this).data('id')); });
        $('#claims-table').on('click', '.view-claim-btn', function() { viewClaimDetails($(this).data('id')); });
        $('#claims-table').on('click', '.approve-claim-btn, .reject-claim-btn', function() {
            const claimId = $(this).data('id');
            const actionStatus = $(this).hasClass('approve-claim-btn') ? 'Approved' : 'Rejected';
            updateClaimStatus(claimId, actionStatus, true, $(this).closest('tr'));
        });
        $('.summary-card').on('click', function() {
            const clickedCard = $(this);
            const newStatusFilter = clickedCard.data('filter-status');
            $('.summary-card').removeClass('ring-2 ring-indigo-500');
            if (currentStatusFilter !== newStatusFilter) {
                clickedCard.addClass('ring-2 ring-indigo-500');
            }
            currentStatusFilter = (currentStatusFilter === newStatusFilter) ? '' : newStatusFilter;
            offset = 0;
            loadClaims();
            updateSummaryCards();
        });
        
        // Drag and Drop functionality
        const dropZone = $('#file-drop-zone');
        let dragCounter = 0;
        $(document).on('dragenter', function(e) {
            if (e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.types.indexOf('Files') >= 0) {
                dragCounter++;
                if (dragCounter === 1) dropZone.removeClass('invisible opacity-0 pointer-events-none').addClass('visible opacity-100 pointer-events-auto');
            }
        });
        $(document).on('dragleave', function(e) {
            if (e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.types.indexOf('Files') >= 0) {
                // Ensure we only decrease if leaving a valid drag target
                const rect = e.target.getBoundingClientRect();
                if (e.clientX <= rect.left || e.clientX >= rect.right || e.clientY <= rect.top || e.clientY >= rect.bottom) {
                    dragCounter--;
                }
                if (dragCounter === 0) dropZone.addClass('invisible opacity-0 pointer-events-none');
            }
        });
        dropZone.on('dragover', e => { e.preventDefault(); e.stopPropagation(); });
        dropZone.on('drop', function(e) {
            e.preventDefault(); e.stopPropagation();
            dropZone.addClass('invisible opacity-0 pointer-events-none');
            dragCounter = 0;
            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                const file = files[0];
                const validationResult = validateAttachment(file);
                if (!validationResult.success) {
                    Swal.fire('Invalid File', validationResult.message, 'error');
                    return;
                }
                openClaimForm(null, file);
            } else {
                Swal.fire('No File', 'No file was dropped. Please try again.', 'info');
            }
        });
    });
</script>
</body>
</html>