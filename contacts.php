<?php
/**
 * contact.php - Redesigned Contacts Dashboard.
 * - Includes shared navbar.php (which uses SweetAlert for profile).
 * - Links main style.css.
 * - Uses Lucide icons.
 * - Shows summary graph (placeholder), icon boxes, unified contact list.
 * - Includes Add Contact and Import Modals (initially hidden).
 * - Implements page-wide drag & drop for Excel import simulation.
 * - Requires api.php actions for fetching/adding/updating details via AJAX.
 * - Requires database schema updates for new fields (balances, etc.).
 */

// --- Authentication Check ---
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) { header('Location: index.php?error=login_required'); exit; }
// --- END Authentication Check ---

require_once 'config.php';
require_once 'functions.php';

// --- Set Navbar Variables ---
$isDashboard = false;
$pageTitle = "Contacts";
$pageIconClass = "contact"; // Lucide icon name
$pageIconColorClass = "contacts"; // CSS class for potential specific color

// --- Database Connection & Data Fetching ---
$pdo = connect_db();
$customers = [];
$vendors = [];
$fetch_error = null;
$customer_balance_total = 0; // Placeholder
$vendor_balance_total = 0;   // Placeholder
$active_customers = 0;
$active_vendors = 0;

if (!$pdo) {
    $fetch_error = "Database connection failed.";
} else {
    try {
        // Fetch basic data for list (adjust columns as needed)
        $customerStmt = $pdo->query("SELECT id, name, email, phone, status, avatar FROM customers ORDER BY name"); // Added avatar
        $customers = $customerStmt->fetchAll(PDO::FETCH_ASSOC);
        $active_customers = count(array_filter($customers, fn($c) => isset($c['status']) && $c['status'] == 'active'));

        $vendorStmt = $pdo->query("SELECT id, company_name, email, phone, status, avatar FROM suppliers ORDER BY company_name"); // Added avatar
        $vendors = $vendorStmt->fetchAll(PDO::FETCH_ASSOC);
        $active_vendors = count(array_filter($vendors, fn($v) => isset($v['status']) && $v['status'] == 'active'));


        // TODO: Fetch aggregated balance data for the graph (Replace placeholders)
        // Example placeholder calculation (replace with actual DB query)
        // $customer_balance_total = $pdo->query("SELECT SUM(balance) FROM customers WHERE status='active'")->fetchColumn() ?: 0;
        // $vendor_balance_total = $pdo->query("SELECT SUM(balance) FROM suppliers WHERE status='active'")->fetchColumn() ?: 0;
        $customer_balance_total = 12345.67; // Dummy data
        $vendor_balance_total = -7890.12;   // Dummy data (negative for payables)


    } catch (PDOException $e) {
        error_log("Database Error fetching contacts: " . $e->getMessage());
        $fetch_error = "Error fetching contact data.";
    }
    $pdo = null; // Close connection
}

// Combine for initial "All" view, adding a type indicator
$all_contacts = [];
foreach ($customers as $c) { $c['contact_type'] = 'customer'; $all_contacts[] = $c; }
foreach ($vendors as $v) { $v['contact_type'] = 'vendor'; $all_contacts[] = $v; }
// Sort the combined list by name/company name
usort($all_contacts, function($a, $b) {
    $nameA = $a['contact_type'] === 'customer' ? ($a['name'] ?? '') : ($a['company_name'] ?? '');
    $nameB = $b['contact_type'] === 'customer' ? ($b['name'] ?? '') : ($b['company_name'] ?? '');
    return strcasecmp((string)$nameA, (string)$nameB);
});


// Get flash message from session
$flash_message = $_SESSION['flash_message'] ?? null;
$flash_status = $_SESSION['flash_status'] ?? null;
unset($_SESSION['flash_message'], $_SESSION['flash_status']);

// Sample country data (move to config or DB later)
$countries = ['United States', 'United Kingdom', 'Canada', 'Australia', 'Germany', 'Pakistan', 'India'];
sort($countries);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - FinLab ERP</title>
    <link rel="stylesheet" href="style.css"> 
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="preload">

    <?php include 'navbar.php'; // Includes Navbar, Profile Modal (JS only), Help Modal ?>

    <div class="page-content container"> 

        <header style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
            <div class="header-text">
                <h1>Contacts Dashboard</h1>
                <p class="page-subtitle">Overview, management, and import options for customers & vendors.</p>
            </div>
        </header>

        <?php if ($flash_message): ?> <div class="update-message <?php echo $flash_status === 'success' ? 'success' : 'error'; ?>"> <span><?php echo htmlspecialchars($flash_message); ?></span> <button onclick="this.parentElement.style.display='none'">&times;</button> </div> <?php endif; ?>
        <?php if (isset($fetch_error)): ?> <div class="update-message error"> <span><?php echo htmlspecialchars($fetch_error); ?></span> </div> <?php endif; ?>

        <section class="contact-summary-grid">
            <div class="summary-card">
                <h3>Balance Overview</h3>
                <div class="summary-chart-container">
                    <canvas id="balanceChart"></canvas>
                </div>
            </div>
            <div class="summary-card">
                <h3>Quick Stats</h3>
                <p>Total Contacts: <span class="font-semibold"><?= count($all_contacts) ?></span></p>
                <p>Active Customers: <span class="font-semibold"><?= $active_customers ?></span></p>
                <p>Active Vendors: <span class="font-semibold"><?= $active_vendors ?></span></p>
               
            </div>
        </section>

        <section class="contact-action-boxes">
            <div class="action-box all-contacts active" onclick="filterContacts('all', this)"> <i data-lucide="contact"></i> <h3>All Contacts</h3> </div>
            <div class="action-box customers" onclick="filterContacts('customer', this)"> <i data-lucide="users"></i> <h3>Customers</h3> </div>
            <div class="action-box vendors" onclick="filterContacts('vendor', this)"> <i data-lucide="truck"></i> <h3>Vendors</h3> </div>
            <div class="action-box add-customer" onclick="openAddContactModal('customer')"> <i data-lucide="user-plus"></i> <h3>Add Customer</h3> </div>
            <div class="action-box add-vendor" onclick="openAddContactModal('vendor')"> <i data-lucide="building"></i> <h3>Add Vendor</h3> </div>
            <div class="action-box import-contacts" onclick="openImportModal()"> <i data-lucide="file-spreadsheet"></i> <h3>Import</h3> </div>
        </section>

        <div class="search-bar mb-6">
            <input type="text" id="contactSearchInput" placeholder="Search displayed contacts by name, company, email, or phone..." aria-label="Search contacts">
        </div>

        <section class="contact-table-container">
            <table class="contact-table w-full">
                <thead>
                    <tr>
                        <th>Name / Company</th>
                        <th>Type</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="contact-list-body">
                    <?php if (empty($all_contacts)): ?>
                        <tr><td colspan="6" class="no-results">No contacts found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($all_contacts as $contact):
                            $is_customer = ($contact['contact_type'] === 'customer');
                            $display_name = $is_customer ? ($contact['name'] ?? 'N/A') : ($contact['company_name'] ?? 'N/A');
                            $contact_id = $contact['id'];
                            $db_type = $is_customer ? 'customers' : 'suppliers';
                            $status = $contact['status'] ?? 'unknown';
                        ?>
                            <tr class="contact-row" data-type="<?= $contact['contact_type'] ?>" data-name="<?= htmlspecialchars(strtolower($display_name)) ?>" data-email="<?= htmlspecialchars(strtolower($contact['email'] ?? '')) ?>" data-phone="<?= htmlspecialchars(strtolower($contact['phone'] ?? '')) ?>">
                                <td class="font-medium"><?= htmlspecialchars($display_name) ?></td>
                                <td><span class="contact-type-badge <?= $contact['contact_type'] ?>"><?= ucfirst($contact['contact_type']) ?></span></td>
                                <td><?= htmlspecialchars($contact['email'] ?: 'N/A') ?></td>
                                <td><?= htmlspecialchars($contact['phone'] ?: 'N/A') ?></td>
                                <td><span class="status-<?= htmlspecialchars($status) ?>"><?= htmlspecialchars(ucfirst($status)) ?></span></td>
                                <td class="contact-table-actions">
                                    <button class="btn-icon see-details" title="View Details" onclick="openDetailsModal(<?= $contact_id ?>, '<?= $db_type ?>', 'view')"><i data-lucide="eye"></i></button>
                                    <button class="btn-icon edit-details" title="Edit Details" onclick="openDetailsModal(<?= $contact_id ?>, '<?= $db_type ?>', 'edit')"><i data-lucide="pencil"></i></button>
                                    
                                </td>
                            </tr>
                        <?php endforeach; ?>
                     <?php endif; ?>
                     <tr id="no-contacts-found" style="display: none;"><td colspan="6" class="no-results">No contacts match your search or filter.</td></tr>
                </tbody>
            </table>
        </section>

    </div><div id="addContactModal" class="modal hidden">
        <div class="modal-content">
             <div class="modal-header">
                <h3 class="modal-title">Add New Contact</h3>
                <button class="close-btn close-modal-btn" title="Close"><i data-lucide="x"></i></button>
            </div>
             <div class="modal-body-container">
                
                 <form id="addContactForm" method="POST" action="add_contact.php" enctype="multipart/form-data">
                     <input type="hidden" name="action" value="add">
                     <input type="hidden" name="contact_type" id="add_contact_type_hidden"> 

                     <div class="modal-row">
                         <div class="modal-col" style="flex: 0 0 200px; max-width: 200px;">
                             <div class="modal-avatar">
                                 <div class="avatar-placeholder" id="addAvatarPlaceholder"><i data-lucide="user-plus"></i></div>
                                 <input type="file" id="addAvatarUpload" name="avatar" accept="image/*" style="display: none;">
                                 <button type="button" class="btn btn-secondary btn-small" style="width: 100%; margin-top: 10px;" onclick="document.getElementById('addAvatarUpload').click()"><i data-lucide="upload"></i> Upload Avatar</button>
                                  <div id="add-avatar-feedback" class="invalid-feedback" style="text-align: center; margin-top: 5px;"></div>
                             </div>
                         </div>
                         <div class="modal-col" style="flex: 1;">
                            
                             <div id="addCustomerFields" style="display: none;">
                                 <div class="form-group"> <label for="add_customer_name">Customer Name *</label> <input type="text" class="form-control" id="add_customer_name" name="name"> <div id="add_customer_name-error" class="invalid-feedback"></div> </div>
                                 <div class="form-group"> <label for="add_customer_type">Customer Type</label> <select class="form-control" id="add_customer_type" name="customer_type"> <option value="individual">Individual</option> <option value="partnership">Partnership</option> <option value="company">Company</option> </select> </div>
                                 <div class="form-group"> <label for="add_customer_id">ID Number</label> <input type="text" class="form-control" id="add_customer_id" name="identification_number"> </div>
                             </div>
                             
                             <div id="addVendorFields" style="display: none;">
                                 <div class="form-group"> <label for="add_company_name">Company Name *</label> <input type="text" class="form-control" id="add_company_name" name="company_name"> <div id="add_company_name-error" class="invalid-feedback"></div> </div>
                                 <div class="form-group"> <label for="add_business_type">Business Type</label> <select class="form-control" id="add_business_type" name="business_type"> <option value="sole_proprietor">Sole Proprietor</option> <option value="partnership">Partnership</option> <option value="llc">LLC</option> <option value="corporation">Corporation</option> </select> </div>
                                 <div class="form-group"> <label for="add_registration_number">Registration #</label> <input type="text" class="form-control" id="add_registration_number" name="registration_number"> </div>
                             </div>
                             <div class="form-group"> <label for="add_email">Email</label> <input type="email" class="form-control" id="add_email" name="email"> </div>
                             <div class="form-group"> <label for="add_phone">Phone</label> <input type="tel" class="form-control" id="add_phone" name="phone"> </div>
                         </div>
                     </div>
                      <hr class="my-4">
                      <h4 class="text-md font-semibold mb-3 text-gray-700">Address & Other Details</h4>
                    
                     <div class="modal-row">
                         <div class="modal-col">
                             <div class="form-group"> <label for="add_contact_person">Contact Person</label> <input type="text" class="form-control" id="add_contact_person" name="contact_person"> </div>
                             <div class="form-group"> <label for="add_mobile_number">Mobile Number</label> <input type="tel" class="form-control" id="add_mobile_number" name="mobile_number"> </div>
                             <div class="form-group"> <label for="add_fax">Fax</label> <input type="text" class="form-control" id="add_fax" name="fax"> </div>
                             <div class="form-group"> <label for="add_website">Website</label> <input type="url" class="form-control" id="add_website" name="website" placeholder="https://example.com"> </div>
                             <div class="form-group"> <label for="add_address">Address</label> <textarea class="form-control" id="add_address" name="address" rows="3"></textarea> </div>
                             <div class="form-group">
                                 <label for="add_country">Country</label>
                                 <select class="form-control" id="add_country" name="country"> <option value="">Select Country</option> <?php foreach ($countries as $country): ?> <option value="<?= htmlspecialchars($country) ?>"><?= htmlspecialchars($country) ?></option> <?php endforeach; ?> </select>
                             </div>
                             <div class="form-group"> <label for="add_city">City</label> <input type="text" class="form-control" id="add_city" name="city"> </div>
                         </div>
                         <div class="modal-col">
                             <div class="form-group"> <label for="add_tax_type">Tax Type</label> <select class="form-control" id="add_tax_type" name="tax_type"> <option value="taxable" selected>Taxable</option> <option value="non_taxable">Non-Taxable</option> <option value="zero_rated">Zero-Rated</option> </select> </div>
                             <div id="addTaxPercentageGroup" class="form-group tax-percentage-group"> <label for="add_tax_percentage">Tax Percentage</label> <select class="form-control" id="add_tax_percentage" name="tax_percentage"> <option value="5">5%</option> <option value="10">10%</option> <option value="15">15%</option> <option value="17" selected>17%</option> <option value="20">20%</option> </select> </div>
                             <div class="form-group"> <label for="add_status">Status</label> <select class="form-control" id="add_status" name="status"> <option value="active" selected>Active</option> <option value="inactive">Inactive</option> <option value="pending">Pending</option> </select> </div>
                             <div class="form-group"> <label for="add_goods_services">Goods/Services</label> <select class="form-control" id="add_goods_services" name="goods_services"> <option value="goods">Goods</option> <option value="services">Services</option> </select> </div>
                             <div class="form-group"> <label for="add_product_category">Category/Nature</label> <input type="text" class="form-control" id="add_product_category" name="product_category"> </div>
                             <div class="form-group"> <label for="add_business_name">Business Name</label> <input type="text" class="form-control" id="add_business_name" name="business_name"> </div>
                             <div class="form-group"> <label for="add_business_reg_no">Business Reg No.</label> <input type="text" class="form-control" id="add_business_reg_no" name="business_reg_no"> </div>
                             <div class="form-group"> <label for="add_business_country">Business Country</label> <input type="text" class="form-control" id="add_business_country" name="business_country"> </div>
                         </div>
                     </div>
                      <hr class="my-4">
                      <h4 class="text-md font-semibold mb-3 text-gray-700">Banking Details</h4>
                     <div class="modal-row">
                         <div class="modal-col">
                             <div class="form-group"> <label for="add_bank_name">Bank Name</label> <input type="text" class="form-control" id="add_bank_name" name="bank_name"> </div>
                             <div class="form-group"> <label for="add_account_title">Account Title</label> <input type="text" class="form-control" id="add_account_title" name="account_title"> </div>
                         </div>
                         <div class="modal-col">
                             <div class="form-group"> <label for="add_account_number">Account Number/IBAN</label> <input type="text" class="form-control" id="add_account_number" name="account_number"> </div>
                             <div class="form-group"> <label for="add_swift_code">SWIFT Code</label> <input type="text" class="form-control" id="add_swift_code" name="swift_code"> </div>
                         </div>
                     </div>
                     <hr class="my-4">
                      <div class="form-group"> <label for="add_notes">Notes</label> <textarea class="form-control" id="add_notes" name="notes" rows="4"></textarea> </div>

                     <div class="form-actions">
                         <button type="button" class="btn btn-secondary close-modal-btn">Cancel</button>
                         <button type="submit" class="btn btn-primary"><i data-lucide="plus"></i> Add Contact</button>
                     </div>
                 </form>
             </div>
        </div>
    </div>

    <div id="importContactsModal" class="modal hidden">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Import Contacts from Excel</h3>
                <button class="close-btn close-modal-btn" title="Close"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body-container">
                <p class="mb-4 text-sm text-gray-600">Upload an Excel file (.xlsx, .xls). Ensure columns match: Name/Company, Type (customer/vendor), Email, Phone, [Other Optional Fields...].</p>
                <div class="form-group">
                    <label for="importFileUploadInput" class="file-upload-label">Select Excel File</label>
                    <div class="drag-drop-area" id="importDragDropArea">
                        <i data-lucide="file-spreadsheet"></i>
                        <p>Drag & drop Excel file here or click to browse</p>
                        <small>Supports: .xlsx, .xls</small>
                        <input type="file" id="importFileUploadInput" accept=".xlsx, .xls" style="display: none;">
                    </div>
                    <div id="importFileName" class="mt-2 text-sm font-medium text-gray-700"></div>
                </div>
                <div id="importPreviewTableContainer" class="hidden mt-4">
                    <h4 class="text-md font-semibold mb-2">Preview Data (First 5 Rows)</h4>
                    <div class="overflow-x-auto border border-gray-200 rounded-md">
                        <table id="importPreviewTable" class="w-full text-sm contact-table-container">
                            <thead></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                <div id="importErrorLog" class="mt-4 text-red-600 text-sm hidden"></div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary close-modal-btn">Cancel</button>
                <button type="button" id="confirmImportBtn" class="btn btn-primary" disabled><i data-lucide="upload-cloud"></i> Confirm Import</button>
            </div>
        </div>
    </div>

    <div id="detailsModal" class="modal hidden">
        <div class="modal-content">
             <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Contact Details</h3>
                 <div class="modal-header-actions">
                   
                    <button class="close-btn close-modal-btn" title="Close"><i data-lucide="x"></i></button>
                </div>
            </div>
             <div class="modal-body-container">
                 <div class="loading-placeholder">Loading details...</div>
              
             </div>
             
        </div>
    </div>


    <script>
        // Global scope helpers
        const countriesData = <?php echo json_encode($countries); ?>;
        let allContactsData = <?php echo json_encode($all_contacts); ?>; // Store initial data for client-side search

        // --- Helper Functions ---
        function escapeHtml(unsafe) { /* ... */ }
        function showSpinner(buttonId, spinnerId) { /* ... */ }
        function hideSpinner(buttonId, spinnerId) { /* ... */ }
        function showError(fieldId, message) { /* ... */ }
        function clearError(fieldId) { /* ... */ }
        function clearAllErrors(containerId) { /* ... */ }
        function validateEmail(email) { /* ... */ }
        function toggleTaxPercentage(taxType, percentageGroupId = 'taxPercentageGroup') { /* ... */ }
        function setFormReadOnly(formId, readOnly) { /* ... */ }
        function openModal(modalId) { const modal = document.getElementById(modalId); if(modal) { modal.classList.remove('hidden'); modal.style.display = 'flex'; if (typeof lucide !== 'undefined') { lucide.createIcons(); } } }
        function closeModal(modalId) { const modal = document.getElementById(modalId); if(modal) { modal.classList.add('hidden'); modal.style.display = 'none'; } }
        function switchTab(tabId, modalPrefix = '') { /* ... */ }

        // --- Specific Functions ---
        function filterContacts(type, clickedElement) {
             console.log("Filtering by:", type);
             const contactRows = document.querySelectorAll('#contact-list-body .contact-row');
             const noResultsRow = document.getElementById('no-contacts-found');
             const searchInput = document.getElementById('contactSearchInput');
             const searchTerm = searchInput.value.toLowerCase();
             let resultsFound = false;

             contactRows.forEach(row => {
                 const rowType = row.dataset.type; // 'customer' or 'vendor'
                 const rowText = row.textContent.toLowerCase();
                 const typeMatch = (type === 'all' || rowType === type);
                 const searchMatch = rowText.includes(searchTerm);
                 const shouldShow = typeMatch && searchMatch;
                 row.style.display = shouldShow ? '' : 'table-row'; // Use table-row for display
                 if (shouldShow) resultsFound = true;
             });

             document.querySelectorAll('.contact-action-boxes .action-box').forEach(box => box.classList.remove('active'));
             if(clickedElement) clickedElement.classList.add('active');
             if (noResultsRow) noResultsRow.style.display = resultsFound ? 'none' : 'table-row';
        }

        function openAddContactModal(type = 'customer') {
            console.log("JS: openAddContactModal called for type:", type);
            const modal = document.getElementById('addContactModal');
            const form = document.getElementById('addContactForm');
            if (!modal || !form) return;

            form.reset();
            clearAllErrors('addContactForm');
            document.getElementById('add_contact_type_hidden').value = type;

            const isCustomer = (type === 'customer');
            document.getElementById('addCustomerFields').style.display = isCustomer ? 'block' : 'none';
            document.getElementById('addVendorFields').style.display = isCustomer ? 'none' : 'block';
            document.getElementById('add_customer_name').required = isCustomer;
            document.getElementById('add_company_name').required = !isCustomer;

            modal.querySelector('.modal-title').textContent = `Add New ${isCustomer ? 'Customer' : 'Vendor'}`;
            document.getElementById('addAvatarPlaceholder').innerHTML = '<i data-lucide="user-plus"></i>';
            toggleTaxPercentage('taxable', 'addTaxPercentageGroup'); // Default tax type
            if (typeof lucide !== 'undefined') { lucide.createIcons(); }
            openModal('addContactModal');
        }
        function closeAddContactModal() { closeModal('addContactModal'); }
        function openImportModal() { /* ... Opens and resets Import modal ... */ }
        function closeImportModal() { closeModal('importContactsModal'); }
        function openDetailsModal(id, dbType, action = 'view') { /* ... Fetches data and opens Details modal ... */ }
        function generateContactFormHtml(contact, dbType) { /* ... Generates Details modal form HTML ... */ }
        function enableEdit() { /* ... Enables editing in Details modal ... */ }
        function handleImportFile(file) { /* ... Reads Excel and shows preview ... */ }
        function handleDrop(e) { /* ... Handles page-wide file drop ... */ }
        function attachModalEventListeners() { /* ... Attaches listeners to dynamic modal content ... */ }


        // --- Initialize Page ---
        document.addEventListener('DOMContentLoaded', function() {
            console.log("CONTACTS DEBUG: DOMContentLoaded event fired.");
            filterContacts('all', document.querySelector('.action-box.active')); // Show 'All' section initially

            // --- Event Listeners ---
            // Add Contact Type change (for Add Modal)
            document.getElementById('add_contact_type')?.addEventListener('change', function() { /* ... Show/hide fields ... */ });
            toggleTaxPercentage(document.getElementById('add_tax_type')?.value || 'taxable', 'addTaxPercentageGroup');
            document.getElementById('add_tax_type')?.addEventListener('change', (e) => toggleTaxPercentage(e.target.value, 'addTaxPercentageGroup'));

            // Global Search Filter (Applies to current filter)
            document.getElementById('contactSearchInput')?.addEventListener('input', function() {
                const activeFilterElement = document.querySelector('.contact-action-boxes .action-box.active');
                // Extract type from onclick attribute (more robust way needed if onclick changes)
                let activeFilter = 'all';
                if (activeFilterElement && activeFilterElement.getAttribute('onclick')) {
                    const match = activeFilterElement.getAttribute('onclick').match(/'([^']+)'/);
                    if (match && match[1]) {
                        activeFilter = match[1];
                    }
                }
                filterContacts(activeFilter, activeFilterElement);
            });

            // Modal close buttons (delegated)
            document.addEventListener('click', function(e) {
                const modalCloseBtn = e.target.closest('.close-modal-btn');
                if (modalCloseBtn) { const openModal = modalCloseBtn.closest('.modal'); if (openModal) { closeModal(openModal.id); } }
            });
             // Close modal on background click
            document.querySelectorAll('.modal').forEach(modal => {
                modal.addEventListener('click', function(event) { if (event.target === modal) { closeModal(modal.id); } });
            });
            // Tab clicks in Modals
            document.querySelectorAll('#detailsModal .modal-tab, #addContactModal .modal-tab').forEach(tab => { /* ... Tab switch logic ... */ });

            // --- Page-Wide Drag & Drop ---
            const body = document.body;
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => { body.addEventListener(eventName, preventDefaults, false); });
            function preventDefaults(e) { e.preventDefault(); e.stopPropagation(); }
            ['dragenter', 'dragover'].forEach(eventName => { body.addEventListener(eventName, (event) => { if (!event.target.closest('.modal')) body.classList.add('excel-drag-active'); }, false); });
            ['dragleave', 'drop'].forEach(eventName => { body.addEventListener(eventName, () => body.classList.remove('excel-drag-active'), false); });
            body.addEventListener('drop', handleDrop, false);
            function handleDrop(e) {
                 if (e.target.closest('.modal')) return; // Ignore drops inside modals
                 console.log("File dropped on page!"); const dt = e.dataTransfer; const files = dt.files;
                 if (files.length > 0) {
                     const file = files[0];
                     if (file.type === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' || file.type === 'application/vnd.ms-excel') { openImportModal(); handleImportFile(file); }
                     else { Swal.fire('Invalid File Type', 'Please drop an Excel file (.xlsx or .xls) for import.', 'warning'); }
                 }
            }
            // --- End Drag & Drop ---

            // --- Import Modal Logic ---
            const importModal = document.getElementById('importContactsModal');
            const importDropArea = document.getElementById('importDragDropArea');
            const importFileInput = document.getElementById('importFileUploadInput');
            const confirmImportBtn = document.getElementById('confirmImportBtn');
            importDropArea?.addEventListener('click', () => importFileInput.click());
            importDropArea?.addEventListener('dragover', (e) => { e.preventDefault(); importDropArea.classList.add('dragover'); });
            importDropArea?.addEventListener('dragleave', () => importDropArea.classList.remove('dragover'));
            importDropArea?.addEventListener('drop', (e) => { e.preventDefault(); importDropArea.classList.remove('dragover'); if (e.dataTransfer.files.length > 0) { handleImportFile(e.dataTransfer.files[0]); } });
            importFileInput?.addEventListener('change', function() { if (this.files.length > 0) { handleImportFile(this.files[0]); } });
            function handleImportFile(file) { /* ... Reads Excel and shows preview ... */ }
            confirmImportBtn?.addEventListener('click', function() { /* ... Sends parsedExcelData to backend via AJAX ... */ });
            // --- End Import Modal Logic ---

            // --- Initial Chart ---
            try {
                const ctx = document.getElementById('balanceChart')?.getContext('2d');
                if (ctx) {
                    new Chart(ctx, { /* ... Chart configuration ... */ });
                     console.log("CONTACTS DEBUG: Chart initialized.");
                } else { console.warn("CONTACTS DEBUG: Canvas element for chart not found."); }
            } catch(chartError) { console.error("CONTACTS DEBUG: Error initializing chart:", chartError); }

            // Initial Lucide render
            if (typeof lucide !== 'undefined') { lucide.createIcons(); }
            console.log("CONTACTS DEBUG: Page setup finished.");

             // Remove preload class after setup
             setTimeout(() => { document.body.classList.remove('preload'); }, 100);

        }); // End DOMContentLoaded

        // --- Function to re-attach listeners for dynamic modal content ---
        function attachModalEventListeners() {
            console.log("Attaching listeners inside details modal");
             // Re-attach listeners for elements inside the details modal form
             $('#detailsModal input, #detailsModal select, #detailsModal textarea').off('input change').on('input change', function() {
                 document.getElementById('saveBtn').disabled = false; // Enable save on any change
             });
             $('#avatarUpload').off('change').on('change', function(event) { if (this.files && this.files[0]) { handleFileSelection(this.files[0]); document.getElementById('saveBtn').disabled = false; } $(this).val(''); });
             $('#tax_type').off('change').on('change', (e) => toggleTaxPercentage(e.target.value));
             // Add other listeners needed within the details modal here
        }

    </script>

</body>
</html>
