<?php
// sales.php - Comprehensive Sales Management Hub

// --- Authentication & Authorization ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?error=login_required');
    exit;
}

// Load configuration and functions
require_once 'config.php';
require_once 'functions.php';

// Check user role
if (!user_has_role('sales') && !user_has_role('admin')) {
    header('Location: unauthorized.php');
    exit;
}

// --- Database Connection ---
try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection error. Please try again later.");
}

// --- Fetch Sales Data ---
try {
    // Get today's sales
    $todayStmt = $pdo->prepare("SELECT SUM(total_amount) as total FROM sales_orders WHERE DATE(order_date) = CURDATE()");
    $todayStmt->execute();
    $todaySales = $todayStmt->fetchColumn() ?? 0;

    // Get monthly target (from settings table)
    $targetStmt = $pdo->prepare("SELECT sales_target FROM monthly_targets WHERE month = MONTH(CURDATE()) AND year = YEAR(CURDATE())");
    $targetStmt->execute();
    $monthlyTarget = $targetStmt->fetchColumn() ?? 0;

    // Get pending orders count
    $pendingStmt = $pdo->prepare("SELECT COUNT(*) FROM sales_orders WHERE status = 'pending'");
    $pendingStmt->execute();
    $pendingCount = $pendingStmt->fetchColumn();

    // Get recent orders
    $ordersStmt = $pdo->prepare("
        SELECT so.*, c.name as customer_name 
        FROM sales_orders so
        LEFT JOIN customers c ON so.customer_id = c.id
        ORDER BY so.order_date DESC 
        LIMIT 50
    ");
    $ordersStmt->execute();
    $salesOrders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Sales data fetch error: " . $e->getMessage());
    $todaySales = 0;
    $monthlyTarget = 0;
    $pendingCount = 0;
    $salesOrders = [];
}

// Generate CSRF token
$csrfToken = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrfToken;

// --- Page Configuration ---
$isDashboard = false;
$pageTitle = "Sales Management";
$pageIconClass = "fas fa-shopping-bag";
$pageIconColorClass = "sales";

// Include navbar after setting variables
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Accounting ERP</title>

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="sales.css"> <!-- Additional sales-specific styles -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- For export functionality -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="page-content">
        <div class="sales-container">
            <!-- Sales Header with Actions -->
            <div class="sales-header">
                <h2><i class="fas fa-shopping-bag"></i> Sales Dashboard</h2>
                <div class="sales-actions">
                    <button id="new-order-btn" class="btn-primary">
                        <i class="fas fa-plus"></i> New Order
                    </button>
                    <button id="refresh-data" class="btn-secondary">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                    <div class="export-options">
                        <button id="export-csv" class="btn-export" title="Export to CSV">
                            <i class="fas fa-file-csv"></i>
                        </button>
                        <button id="export-pdf" class="btn-export" title="Export to PDF">
                            <i class="fas fa-file-pdf"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="sales-stats">
                <div class="stat-card">
                    <h3>Today's Sales</h3>
                    <p>$<?= number_format($todaySales, 2) ?></p>
                    <div class="stat-trend <?= $todaySales > 0 ? 'up' : 'neutral' ?>">
                        <i class="fas fa-arrow-<?= $todaySales > 0 ? 'up' : 'right' ?>"></i>
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Monthly Target</h3>
                    <p>$<?= number_format($monthlyTarget, 2) ?></p>
                    <div class="progress-bar">
                        <div class="progress" style="width: <?= min(100, ($todaySales / max($monthlyTarget, 1)) * 100) ?>%"></div>
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Pending Orders</h3>
                    <p><?= $pendingCount ?></p>
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="sales-filters">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="sales-search" placeholder="Search orders, customers...">
                </div>
                <div class="filter-group">
                    <select id="status-filter" class="filter-select">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <input type="date" id="date-filter" class="filter-select">
                    <select id="customer-filter" class="filter-select">
                        <option value="">All Customers</option>
                        <?php
                        $customers = $pdo->query("SELECT id, name FROM customers ORDER BY name")->fetchAll();
                        foreach ($customers as $customer) {
                            echo '<option value="' . $customer['id'] . '">' . htmlspecialchars($customer['name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>

            <!-- Main Sales Table -->
            <div class="sales-table-container">
                <table id="sales-table" class="responsive-table">
                    <thead>
                        <tr>
                            <th data-sort="order_number">Order ID <i class="fas fa-sort"></i></th>
                            <th data-sort="customer_name">Customer <i class="fas fa-sort"></i></th>
                            <th data-sort="order_date">Date <i class="fas fa-sort"></i></th>
                            <th data-sort="total_amount">Amount <i class="fas fa-sort"></i></th>
                            <th data-sort="status">Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salesOrders as $order): ?>
                        <tr data-order-id="<?= htmlspecialchars($order['id']) ?>">
                            <td><?= htmlspecialchars($order['order_number']) ?></td>
                            <td><?= htmlspecialchars($order['customer_name']) ?></td>
                            <td><?= date('M d, Y', strtotime($order['order_date'])) ?></td>
                            <td>$<?= number_format($order['total_amount'], 2) ?></td>
                            <td>
                                <span class="status-badge status-<?= htmlspecialchars($order['status']) ?>">
                                    <?= ucfirst(htmlspecialchars($order['status'])) ?>
                                </span>
                            </td>
                            <td class="actions-cell">
                                <button class="action-btn view-order" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="action-btn edit-order" title="Edit Order">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="action-btn print-order" title="Print Invoice">
                                    <i class="fas fa-print"></i>
                                </button>
                                <button class="action-btn chat-order" title="Customer Chat">
                                    <i class="fas fa-comment-dots"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="pagination-controls">
                    <button id="prev-page" class="pagination-btn" disabled>
                        <i class="fas fa-chevron-left"></i> Previous
                    </button>
                    <span id="page-info" class="page-info">Page 1 of 5</span>
                    <button id="next-page" class="pagination-btn">
                        Next <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- Quick Actions Sidebar -->
            <div class="quick-actions">
                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                <ul>
                    <li>
                        <a href="#" id="quick-new-customer">
                            <i class="fas fa-user-plus"></i> New Customer
                        </a>
                    </li>
                    <li>
                        <a href="#" id="quick-new-quote">
                            <i class="fas fa-file-invoice"></i> Create Quote
                        </a>
                    </li>
                    <li>
                        <a href="#" id="quick-reports">
                            <i class="fas fa-chart-bar"></i> Sales Reports
                        </a>
                    </li>
                    <li>
                        <a href="#" id="quick-targets">
                            <i class="fas fa-bullseye"></i> Sales Targets
                        </a>
                    </li>
                    <li>
                        <a href="#" id="quick-returns">
                            <i class="fas fa-undo"></i> Process Return
                        </a>
                    </li>
                    <li>
                        <a href="#" id="quick-discounts">
                            <i class="fas fa-tag"></i> Discount Manager
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Chat Integration (initially hidden) -->
    <div class="chat-container">
        <div class="chat-header">
            <h3>Sales Support Chat</h3>
            <button id="close-chat" class="chat-close-btn">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="chat-messages" id="chat-messages">
            <!-- Messages will be loaded here -->
        </div>
        <div class="chat-input">
            <input type="text" id="chat-message" placeholder="Type your message...">
            <button id="send-message" class="chat-send-btn">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
    <button id="chat-toggle" class="chat-toggle-btn">
        <i class="fas fa-comments"></i>
    </button>

    <!-- Hidden form for CSRF protection -->
    <input type="hidden" id="csrf_token" value="<?= $csrfToken ?>">

    <script>
    // Main Sales Page JavaScript
    $(document).ready(function() {
        // Initialize variables
        let currentPage = 1;
        const rowsPerPage = 10;
        let sortColumn = 'order_date';
        let sortDirection = 'desc';
        let allOrders = <?= json_encode($salesOrders) ?>;
        
        // Initialize page
        initSalesPage();
        
        function initSalesPage() {
            // Set up event listeners
            setupEventListeners();
            
            // Apply initial filters
            filterOrders();
            
            // Initialize pagination
            updatePagination();
            
            // Initialize tooltips
            $('[title]').tooltip({
                position: {
                    my: "center bottom-20",
                    at: "center top",
                    using: function(position, feedback) {
                        $(this).css(position);
                        $("<div>")
                            .addClass("tooltip-arrow")
                            .addClass(feedback.vertical)
                            .appendTo(this);
                    }
                }
            });
        }
        
        function setupEventListeners() {
            // Search with debounce
            let searchTimeout;
            $('#sales-search').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(filterOrders, 300);
            });
            
            // Filter changes
            $('#status-filter, #date-filter, #customer-filter').change(filterOrders);
            
            // New order button
            $('#new-order-btn').click(function() {
                window.location.href = 'new_order.php?csrf=<?= $csrfToken ?>';
            });
            
            // Refresh data
            $('#refresh-data').click(refreshSalesData);
            
            // Export buttons
            $('#export-csv').click(exportToCSV);
            $('#export-pdf').click(exportToPDF);
            
            // Quick actions
            $('#quick-new-customer').click(function(e) {
                e.preventDefault();
                openModal('new_customer.php');
            });
            
            // Pagination
            $('#prev-page').click(goToPrevPage);
            $('#next-page').click(goToNextPage);
            
            // Table sorting
            $('[data-sort]').click(function() {
                const column = $(this).data('sort');
                if (sortColumn === column) {
                    sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    sortColumn = column;
                    sortDirection = 'asc';
                }
                sortOrders();
                updateSortIcons();
            });
            
            // Order actions
            $(document).on('click', '.view-order', function() {
                const orderId = $(this).closest('tr').data('order-id');
                viewOrderDetails(orderId);
            });
            
            // Chat functionality
            $('#chat-toggle').click(toggleChat);
            $('#close-chat').click(toggleChat);
            $('#send-message').click(sendChatMessage);
            $('#chat-message').keypress(function(e) {
                if (e.which === 13) sendChatMessage();
            });
        }
        
        function filterOrders() {
            const searchTerm = $('#sales-search').val().toLowerCase();
            const statusFilter = $('#status-filter').val();
            const dateFilter = $('#date-filter').val();
            const customerFilter = $('#customer-filter').val();
            
            let filteredOrders = allOrders;
            
            // Apply filters
            if (searchTerm) {
                filteredOrders = filteredOrders.filter(order => 
                    order.order_number.toLowerCase().includes(searchTerm) || 
                    order.customer_name.toLowerCase().includes(searchTerm) ||
                    order.total_amount.toString().includes(searchTerm)
                );
            }
            
            if (statusFilter) {
                filteredOrders = filteredOrders.filter(order => 
                    order.status === statusFilter
                );
            }
            
            if (dateFilter) {
                filteredOrders = filteredOrders.filter(order => 
                    order.order_date.startsWith(dateFilter)
                );
            }
            
            if (customerFilter) {
                filteredOrders = filteredOrders.filter(order => 
                    order.customer_id == customerFilter
                );
            }
            
            // Update table
            renderOrders(filteredOrders);
            updatePagination();
        }
        
        function renderOrders(orders) {
            const startIdx = (currentPage - 1) * rowsPerPage;
            const paginatedOrders = orders.slice(startIdx, startIdx + rowsPerPage);
            
            const $tbody = $('#sales-table tbody');
            $tbody.empty();
            
            if (paginatedOrders.length === 0) {
                $tbody.append(
                    '<tr><td colspan="6" class="no-results">No orders match your criteria</td></tr>'
                );
                return;
            }
            
            paginatedOrders.forEach(order => {
                $tbody.append(`
                    <tr data-order-id="${order.id}">
                        <td>${order.order_number}</td>
                        <td>${order.customer_name}</td>
                        <td>${new Date(order.order_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
                        <td>$${parseFloat(order.total_amount).toFixed(2)}</td>
                        <td>
                            <span class="status-badge status-${order.status}">
                                ${order.status.charAt(0).toUpperCase() + order.status.slice(1)}
                            </span>
                        </td>
                        <td class="actions-cell">
                            <button class="action-btn view-order" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="action-btn edit-order" title="Edit Order">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn print-order" title="Print Invoice">
                                <i class="fas fa-print"></i>
                            </button>
                            <button class="action-btn chat-order" title="Customer Chat">
                                <i class="fas fa-comment-dots"></i>
                            </button>
                        </td>
                    </tr>
                `);
            });
        }
        
        function refreshSalesData() {
            $.ajax({
                url: 'api/get_sales_data.php',
                method: 'GET',
                data: { csrf_token: $('#csrf_token').val() },
                dataType: 'json',
                beforeSend: function() {
                    $('#refresh-data').prop('disabled', true)
                        .html('<i class="fas fa-spinner fa-spin"></i> Refreshing');
                },
                success: function(data) {
                    if (data.success) {
                        allOrders = data.orders;
                        filterOrders();
                        showNotification('Data refreshed successfully', 'success');
                    } else {
                        showNotification(data.error || 'Error refreshing data', 'error');
                    }
                },
                error: function(xhr) {
                    showNotification('Error connecting to server', 'error');
                    console.error(xhr.responseText);
                },
                complete: function() {
                    $('#refresh-data').prop('disabled', false)
                        .html('<i class="fas fa-sync-alt"></i> Refresh');
                }
            });
        }
        
        function exportToCSV() {
            const headers = ['Order ID', 'Customer', 'Date', 'Amount', 'Status'];
            const data = allOrders.map(order => [
                order.order_number,
                order.customer_name,
                new Date(order.order_date).toLocaleDateString(),
                order.total_amount,
                order.status
            ]);
            
            const csvContent = [
                headers.join(','),
                ...data.map(row => row.join(','))
            ].join('\n');
            
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            link.setAttribute('href', url);
            link.setAttribute('download', `sales_export_${new Date().toISOString().slice(0,10)}.csv`);
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            doc.autoTable({
                head: [['Order ID', 'Customer', 'Date', 'Amount', 'Status']],
                body: allOrders.map(order => [
                    order.order_number,
                    order.customer_name,
                    new Date(order.order_date).toLocaleDateString(),
                    '$' + parseFloat(order.total_amount).toFixed(2),
                    order.status.charAt(0).toUpperCase() + order.status.slice(1)
                ]),
                margin: { top: 20 },
                styles: {
                    fontSize: 8,
                    cellPadding: 2,
                    valign: 'middle'
                },
                headStyles: {
                    fillColor: [41, 128, 185],
                    textColor: 255,
                    fontStyle: 'bold'
                },
                alternateRowStyles: {
                    fillColor: [245, 245, 245]
                }
            });
            
            doc.save(`sales_report_${new Date().toISOString().slice(0,10)}.pdf`);
        }
        
        function viewOrderDetails(orderId) {
            $.ajax({
                url: 'api/get_order_details.php',
                method: 'GET',
                data: { 
                    order_id: orderId,
                    csrf_token: $('#csrf_token').val()
                },
                dataType: 'html',
                success: function(html) {
                    openModal('', html);
                },
                error: function(xhr) {
                    showNotification('Error loading order details', 'error');
                    console.error(xhr.responseText);
                }
            });
        }
        
        function toggleChat() {
            $('.chat-container').toggleClass('active');
            if ($('.chat-container').hasClass('active')) {
                loadChatHistory('sales');
            }
        }
        
        // Other helper functions...
        function updatePagination() {
            const totalRows = allOrders.length;
            const totalPages = Math.ceil(totalRows / rowsPerPage);
            
            $('#page-info').text(`Page ${currentPage} of ${totalPages}`);
            $('#prev-page').prop('disabled', currentPage === 1);
            $('#next-page').prop('disabled', currentPage === totalPages || totalPages === 0);
        }
        
        function goToPrevPage() {
            if (currentPage > 1) {
                currentPage--;
                filterOrders();
            }
        }
        
        function goToNextPage() {
            const totalPages = Math.ceil(allOrders.length / rowsPerPage);
            if (currentPage < totalPages) {
                currentPage++;
                filterOrders();
            }
        }
        
        function sortOrders() {
            allOrders.sort((a, b) => {
                let valA = a[sortColumn];
                let valB = b[sortColumn];
                
                // Handle special cases
                if (sortColumn === 'order_date') {
                    valA = new Date(valA);
                    valB = new Date(valB);
                } else if (sortColumn === 'total_amount') {
                    valA = parseFloat(valA);
                    valB = parseFloat(valB);
                }
                
                if (valA < valB) return sortDirection === 'asc' ? -1 : 1;
                if (valA > valB) return sortDirection === 'asc' ? 1 : -1;
                return 0;
            });
            
            filterOrders();
        }
        
        function updateSortIcons() {
            $('[data-sort] i').removeClass('fa-sort-up fa-sort-down').addClass('fa-sort');
            const $currentSortIcon = $(`[data-sort="${sortColumn}"] i`);
            $currentSortIcon.removeClass('fa-sort').addClass(sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down');
        }
        
        function showNotification(message, type) {
            const $notification = $(`
                <div class="notification ${type}">
                    ${message}
                    <span class="close-notification">&times;</span>
                </div>
            `);
            
            $('.page-content').prepend($notification);
            
            setTimeout(() => {
                $notification.fadeOut(500, () => $notification.remove());
            }, 5000);
            
            $notification.find('.close-notification').click(function() {
                $notification.remove();
            });
        }
        
        function openModal(title, content) {
            // Implement modal opening logic
            console.log(`Opening modal: ${title}`);
        }
        
        function loadChatHistory(context) {
            // Implement chat history loading
            console.log(`Loading chat for ${context}`);
        }
        
        function sendChatMessage() {
            const message = $('#chat-message').val().trim();
            if (message) {
                // In a real app, you would send this to the server
                $('#chat-messages').append(`
                    <div class="message sent">
                        <div class="message-content">${message}</div>
                        <div class="message-time">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                    </div>
                `);
                $('#chat-message').val('');
                $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
            }
        }
    });
    </script>
</body>
</html>