<?php
/**
 * dashboard.php - The main dashboard page for the FinLab ERP application.
 *
 * This page acts as a central hub for all application modules.
 * It checks for user authentication and dynamically displays a navigation bar
 * and application cards based on the user's role and permissions.
 */

// Start the session to access user data and check for authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Set variables for the navbar.php include
$isDashboard = true;
$pageTitle = 'Dashboard';

// Include the universal navigation bar
include 'navbar.php';

// Retrieve user name and role from the session for display and logic
$userName = $_SESSION['user_name'] ?? 'User';
$userRole = $_SESSION['user_role'] ?? 'user';
// Retrieve user permissions from the session. This is needed for front-end logic.
$userPermissions = json_encode($_SESSION['user_permissions'] ?? []);

// ADDED: Generate a CSRF token if one doesn't exist
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
    <!-- ADDED: CSRF Token for security -->
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken); ?>">
    <title>FinLab ERP - Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-color: #87CEEB; /* Sky Blue */
            --primary-dark: #60bdee; /* Slightly Darker Sky Blue */
            --primary-light: #aee5f4; /* Lighter Sky Blue */
            --primary-bg: rgba(135, 206, 235, 0.1); /* Sky Blue with 10% alpha */
            --text-color: #1e293b; /* Slate 800 */
            --text-light: #64748b; /* Slate 500 */
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --highlight-bg: #87CEEB;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --text-color: #f1f5f9;
                --text-light: #94a3b8;
                --card-bg: #1e293b;
                --border-color: #334155;
                --highlight-bg: #0c4a6e;
            }
        }

        /* Re-adding original styles */
        body {
            background-color: var(--bg-color); /* Assuming this is defined in style.css */
            font-family: 'Inter', sans-serif;
            color: var(--text-color);
            min-height: 100vh;
        }

        .dashboard-content {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .dashboard-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .dashboard-header h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }

        .dashboard-header p {
            font-size: 1.2rem;
            color: var(--text-light);
        }

        .category-filter {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.75rem;
            margin-bottom: 2rem;
        }

        .category-filter button {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            color: var(--text-color);
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
        }

        .category-filter button:hover {
            background-color: var(--primary-bg);
            border-color: var(--primary-color);
        }

        .category-filter button.active {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
        }

        .search-bar {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        
        #app-search {
            width: 100%;
            max-width: 80vw; /* Increased width to 80% of viewport width */
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            font-size: 1rem;
            background-color: var(--card-bg);
            color: var(--text-color);
            transition: all 0.2s ease-in-out;
        }

        #app-search:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px var(--primary-bg);
        }
        
        /* Adjusted margin for app-boxes */
        .app-boxes {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin: 0 5%;
        }

        .app-box {
            display: none; /* Hide all app boxes by default */
            flex-direction: column;
            background-color: var(--card-bg);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .app-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .app-box i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        /* --- Colorful Icons --- */
        .app-box .employees { color: #3b82f6; /* Blue */ }
        .app-box .payroll { color: #22c55e; /* Green */ }
        .app-box .reimbursement { color: #f97316; /* Orange */ }
        .app-box .departments { color: #8b5cf6; /* Violet */ }
        .app-box .tax { color: #ef4444; /* Red */ }
        .app-box .invoicing { color: #06b6d4; /* Cyan */ }
        .app-box .accounting { color: #eab308; /* Yellow */ }
        .app-box .timesheet { color: #a855f7; /* Purple */ }
        .app-box .project { color: #10b981; /* Emerald */ }
        .app-box .inventory { color: #f43f5e; /* Rose */ }
        .app-box .purchase { color: #f59e0b; /* Amber */ }
        .app-box .documents { color: #60a5fa; /* Blue-400 */ }
        .app-box .attendance { color: #14b8a6; /* Teal */ }
        .app-box .expenses { color: #fb923c; /* Amber-500 */ }
        .app-box .approval { color: #84cc16; /* Lime */ }
        .app-box .recruitment { color: #ec4899; /* Pink */ }
        .app-box .final-settlement { color: #78350f; /* Cocoa */ }
        .app-box .audit { color: #4b5563; /* Slate-600 */ }
        .app-box .tax-filing { color: #6d28d9; /* Indigo */ }
        .app-box .knowledge { color: #9333ea; /* Purple-600 */ }
        .app-box .bookkeeping { color: #f59e0b; /* Amber-500 */ }
        .app-box .vendors { color: #334155; /* Slate-700 */ }
        .app-box .calendar { color: #f472b6; /* Pink-400 */ }
        .app-box .contacts { color: #1d4ed8; /* Blue-700 */ }
        .app-box .user-management { color: var(--primary-color); }

        .app-box h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .app-box p {
            font-size: 0.9rem;
            color: var(--text-light);
        }

        .highlight {
            background-color: var(--highlight-bg);
            padding: 0 2px;
            border-radius: 3px;
            font-weight: bold;
            color: #fff;
        }
        
        #no-results-message {
            text-align: center;
            color: var(--text-light);
            grid-column: 1 / -1;
            padding: 20px;
            font-style: italic;
        }

    </style>
</head>
<body class="min-h-screen">
    <!-- The navbar.php include has already rendered the header. -->
    <main class="dashboard-container">
        <header class="dashboard-header">
            <h2>FinLab Dashboard</h2>
            <p>Greetings, <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'User'; ?>!</p>
        </header>

        <div class="category-filter" id="category-filter">
            <button data-category="all" class="active">All</button>
            <button data-category="finance">Finance</button>
            <button data-category="hr">HR</button>
            <button data-category="sales">Sales</button>
            <button data-category="productivity">Productivity</button>
        </div>

        <div class="search-bar">
            <input type="text" id="dashboard-app-search" placeholder="Search applications..." aria-label="Search applications">
        </div>
        
        <section class="app-boxes" id="app-boxes-container">
           

            <div class="app-box" data-app-id="employees" data-page="employees.php" data-keywords="employees hr personnel recruitment onboarding" data-category="hr"> <i class="fas fa-users employees"></i> <h3>Employees</h3> <p>Manage employee information.</p> </div>
            <div class="app-box" data-app-id="payroll" data-page="payroll.php" data-keywords="payroll salaries wages compensation payment" data-category="hr"> <i class="fas fa-money-bill-wave payroll"></i> <h3>Payroll</h3> <p>Manage employee payroll.</p> </div>
            <div class="app-box" data-app-id="reimbursements" data-page="reimbursements.php" data-keywords="reimbursement expenses claims finance payment" data-category="finance"> <i class="fas fa-money-check-alt reimbursement"></i> <h3>Reimbursement</h3> <p>Manage reimbursements.</p> </div>
            <div class="app-box" data-app-id="departments" data-page="departments.php" data-keywords="departments teams administration" data-category="hr"> <i class="fas fa-building departments"></i> <h3>Departments</h3> <p>Administer company departments and teams.</p> </div>
            <div class="app-box" data-app-id="tax_slabs" data-page="tax_slabs.php" data-keywords="tax filing taxes returns finance" data-category="finance"> <i class="fas fa-percent tax"></i> <h3>Tax Slabs</h3> <p>Configure and manage income tax slabs.</p> </div>
            <div class="app-box" data-app-id="invoicing" data-page="invoicing.php" data-keywords="invoices billing payments" data-category="finance"> <i class="fas fa-file-invoice invoicing"></i> <h3>Invoicing</h3> <p>Create and manage invoices.</p> </div>
            <div class="app-box" data-app-id="accounting" data-page="accounting.php" data-keywords="accounting finance ledger" data-category="finance"> <i class="fas fa-calculator accounting"></i> <h3>Accounting</h3> <p>Manage your financial records.</p> </div>
            <div class="app-box" data-app-id="timesheet" data-page="timesheet.php" data-keywords="timesheet time tracking hours" data-category="productivity"> <i class="fas fa-clock timesheet"></i> <h3>Timesheet</h3> <p>Track employee work hours.</p> </div>
            <div class="app-box" data-app-id="project" data-page="project.php" data-keywords="project management tasks planning" data-category="productivity"> <i class="fas fa-tasks project"></i> <h3>Project</h3> <p>Organize and track projects.</p> </div>
            <div class="app-box" data-app-id="inventory" data-page="inventory.php" data-keywords="inventory stock supplies warehouse" data-category="finance"> <i class="fas fa-boxes inventory"></i> <h3>Inventory</h3> <p>Manage your stock and supplies.</p> </div>
            <div class="app-box" data-app-id="purchase" data-page="purchase.php" data-keywords="purchase orders vendors procurement" data-category="finance"> <i class="fas fa-shopping-cart purchase"></i> <h3>Purchase</h3> <p>Manage purchase orders.</p> </div>
            <div class="app-box" data-app-id="documents" data-page="documents.php" data-keywords="documents files management storage" data-category="productivity"> <i class="fas fa-folder documents"></i> <h3>Documents</h3> <p>Manage your documents.</p> </div>
            <div class="app-box" data-app-id="attendance" data-page="attendance.php" data-keywords="attendance time tracking check-in check-out clock" data-category="hr"> <i class="fas fa-calendar-check attendance"></i> <h3>Attendance</h3> <p>Track employee attendance.</p> </div>
            <div class="app-box" data-app-id="expenses" data-page="expenses.php" data-keywords="expenses reports costs spending reimbursement" data-category="finance"> <i class="fas fa-file-invoice-dollar expenses"></i> <h3>Expenses</h3> <p>Manage expense reports.</p> </div>
            <div class="app-box" data-app-id="approval" data-page="approval.php" data-keywords="approval requests authorize validation workflow" data-category="productivity"> <i class="fas fa-check-circle approval"></i> <h3>Approval</h3> <p>Manage approvals.</p> </div>
            <div class="app-box" data-app-id="recruitment" data-page="recruitment.php" data-keywords="recruitment hiring jobs candidates hr" data-category="hr"> <i class="fas fa-user-plus recruitment"></i> <h3>Recruitment</h3> <p>Manage recruitment process.</p> </div>
            <div class="app-box" data-app-id="fnf" data-page="fnf.php" data-keywords="final settlement termination resignation hr exit" data-category="hr"> <i class="fas fa-hand-holding-usd final-settlement"></i> <h3>Final Settlement</h3> <p>Manage final settlements.</p> </div>
            <div class="app-box" data-app-id="audit" data-page="audit.php" data-keywords="audit compliance inspection finance" data-category="finance"> <i class="fas fa-search audit"></i> <h3>Audit</h3> <p>Manage audits.</p> </div>
            <div class="app-box" data-app-id="tax_filing" data-page="taxes.php" data-keywords="tax filing taxes returns finance" data-category="finance"> <i class="fas fa-file-alt tax-filing"></i> <h3>Tax Filing</h3> <p>Manage tax filings.</p> </div>
            <div class="app-box" data-app-id="knowledge" data-page="knowledge.php" data-keywords="knowledge base wiki information help docs" data-category="productivity"> <i class="fas fa-book knowledge"></i> <h3>Knowledge</h3> <p>Manage knowledge.</p> </div>
            <div class="app-box" data-app-id="bookkeeping" data-page="bookkeeping.php" data-keywords="bookkeeping accounting records finance journal ledger" data-category="finance"> <i class="fas fa-calculator bookkeeping"></i> <h3>Bookkeeping</h3> <p>Manage financial records.</p> </div>
            <div class="app-box" data-app-id="vendors" data-page="vendors.php" data-keywords="vendors suppliers purchase finance contacts" data-category="finance"> <i class="fas fa-truck vendors"></i> <h3>Vendors</h3> <p>Manage vendors.</p> </div>
            <div class="app-box" data-app-id="calendar" data-page="calendar.php" data-keywords="calendar events schedule planning meetings tasks" data-category="productivity"> <i class="fas fa-calendar-alt calendar"></i> <h3>Calendar</h3> <p>Manage your schedule.</p> </div>
            <div class="app-box" data-app-id="contacts" data-page="contacts.php" data-keywords="contacts customers clients vendors address book sales crm" data-category="sales"> <i class="fas fa-address-book contacts"></i> <h3>Contacts</h3> <p>Manage your contacts.</p> </div>
        </section>
        <div id="no-results-container"></div>
    </main>
    <script>
        $(document).ready(function() {
            const appBoxesContainer = $('#app-boxes-container');
            const searchInput = $('#dashboard-app-search');
            const categoryButtons = $('#category-filter button');
            const noResultsContainer = $('#no-results-container');
            const userRole = '<?php echo $userRole; ?>';
            const allApps = appBoxesContainer.find('.app-box');
            let userPermissions = <?php echo $userPermissions; ?>;

            // Map app pages to module names for permission checking
            const appPageToModuleMap = {
                'employees.php': 'employees',
                'payroll.php': 'payroll',
                'reimbursements.php': 'reimbursements',
                'departments.php': 'departments',
                'tax_slabs.php': 'tax_slabs',
                'invoicing.php': 'invoicing',
                'accounting.php': 'accounting',
                'timesheet.php': 'timesheet',
                'project.php': 'project',
                'inventory.php': 'inventory',
                'purchase.php': 'purchase',
                'documents.php': 'documents',
                'attendance.php': 'attendance',
                'expenses.php': 'expenses',
                'approval.php': 'approval',
                'recruitment.php': 'recruitment',
                'fnf.php': 'final-settlement',
                'audit.php': 'audit',
                'taxes.php': 'tax-filing',
                'knowledge.php': 'knowledge',
                'bookkeeping.php': 'bookkeeping',
                'vendors.php': 'vendors',
                'calendar.php': 'calendar',
                'contacts.php': 'contacts',
                'user_management.php': 'users'
            };

            // Function to check if a user has permission for a specific app module
            function hasPermission(appId) {
                // Super admins and admins have full access
                if (userRole === 'super-admin' || userRole === 'admin') {
                    return true;
                }
                
                // Get the module name from the data attribute
                const module = allApps.filter(`[data-app-id="${appId}"]`).data('app-id');

                // Check for role-based permissions first
                // If a user has a specific role (e.g., 'finance'), give them access to all apps in that category
                const roleMapping = {
                    'finance': ['reimbursements', 'tax_slabs', 'invoicing', 'accounting', 'inventory', 'purchase', 'expenses', 'audit', 'tax-filing', 'bookkeeping', 'vendors'],
                    'hr': ['employees', 'payroll', 'departments', 'attendance', 'recruitment', 'final-settlement'],
                    'productivity': ['timesheet', 'project', 'documents', 'approval', 'knowledge', 'calendar'],
                    'sales': ['contacts']
                };

                if (roleMapping[userRole] && roleMapping[userRole].includes(module)) {
                    return true;
                }

                // Finally, check for custom permissions stored in the session
                return userPermissions && userPermissions[module] && userPermissions[module].includes('view');
            }
            
            // Function to filter and highlight app boxes based on search and category
            function filterAndHighlightApps() {
                let searchTerm = searchInput.val().toLowerCase().trim();
                let searchRegex = searchTerm ? new RegExp(searchTerm.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&'), 'gi') : null;
                let activeCategory = categoryButtons.filter('.active').data('category');
                let resultsFound = false;

                allApps.each(function() {
                    const $box = $(this);
                    
                    const appId = $box.data('app-id');
                    if (!hasPermission(appId)) {
                        $box.hide();
                        return; // Skip apps the user doesn't have permission for
                    }

                    // Store original text if not already stored, then reset
                    let originalAppName = $box.data('original-name');
                    let originalAppDescription = $box.data('original-desc');
                    if (typeof originalAppName === 'undefined') { originalAppName = $box.find('h3').text(); $box.data('original-name', originalAppName); }
                    else { $box.find('h3').text(originalAppName); } // Reset before highlighting
                    if (typeof originalAppDescription === 'undefined') { originalAppDescription = $box.find('p').text(); $box.data('original-desc', originalAppDescription); }
                    else { $box.find('p').text(originalAppDescription); } // Reset before highlighting

                    const keywords = ($box.data('keywords') || '').toLowerCase();
                    const category = $box.data('category');
                    let isSearchMatch = true;
                    let isCategoryMatch = (activeCategory === 'all' || category === activeCategory);

                    // Apply search term filter
                    if (searchTerm) {
                        isSearchMatch = originalAppName.toLowerCase().includes(searchTerm) ||
                                         originalAppDescription.toLowerCase().includes(searchTerm) ||
                                         keywords.includes(searchTerm);

                        // Apply highlighting if match found
                        if (isSearchMatch && searchRegex) {
                            $box.find('h3').html(originalAppName.replace(searchRegex, match => `<span class="highlight">${match}</span>`));
                            $box.find('p').html(originalAppDescription.replace(searchRegex, match => `<span class="highlight">${match}</span>`));
                        }
                    }

                    // Show/hide based on combined filters
                    if (isSearchMatch && isCategoryMatch) {
                        $box.css('display', 'flex');
                        resultsFound = true;
                    } else {
                        $box.hide();
                    }
                });

                // Display 'no results' message if applicable
                if (!resultsFound) {
                    if (noResultsContainer.find('#no-results-message').length === 0) {
                        noResultsContainer.html('<p id="no-results-message">No applications found matching your criteria.</p>');
                    }
                } else {
                    noResultsContainer.empty();
                }
            }

            // Debounce function to limit search execution rate
            function debounce(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }

            // Fetch user permissions and then initialize the dashboard
            async function initializeDashboard() {
                try {
                    const response = await $.get('api.php', { action: 'get_user_permissions', user_id: '<?php echo $_SESSION['user_id']; ?>' });
                    if (response.success) {
                        userPermissions = response.permissions || {};
                    }
                } catch (error) {
                    console.error("Failed to fetch user permissions:", error);
                    userPermissions = {};
                }
                filterAndHighlightApps();
            }
            
            // Event Listeners
            const debouncedSearch = debounce(filterAndHighlightApps, 250);
            searchInput.on('input', debouncedSearch);
            // Prevent form submission on enter key in search
            searchInput.on('keydown', function(event) { if (event.key === 'Enter' || event.keyCode === 13) { event.preventDefault(); } });
            // Category filter buttons
            categoryButtons.on('click', function(event) {
                event.preventDefault();
                const $button = $(this);
                if ($button.hasClass('active')) return; // Do nothing if already active
                categoryButtons.removeClass('active');
                $button.addClass('active');
                filterAndHighlightApps(); // Re-filter when category changes
            });
            
            // App box click navigation
            appBoxesContainer.on('click', '.app-box', function(e) {
                const $box = $(this);
                const appId = $box.data('app-id');
                const page = $box.data('page');
                
                if (hasPermission(appId)) {
                     // Check if a link exists
                    if (page && page !== '#') {
                        window.location.href = page;
                    } else {
                        // Show "Coming Soon" for pages with no link
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'info',
                                title: 'Coming Soon',
                                text: 'This module is not yet available.',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }
                    }
                } else {
                    // Show access denied pop-up for non-permitted apps
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Access Denied',
                            text: 'You do not have permission to access this application. Please contact an administrator.',
                            showConfirmButton: true,
                            confirmButtonText: 'OK',
                            customClass: { popup: 'swal2-popup-themed' }
                        });
                    }
                }
            });

            // Initial setup on page load
            initializeDashboard();
        }); // End document ready
    </script>
</body>
</html>
