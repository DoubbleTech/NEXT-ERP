<?php
/**
 * [Original page description, e.g., contacts.php]
 * - Added Authentication Check: Redirects to index.php if user not logged in.
 * ... other comments ...
 */

// --- Authentication Check ---
// Ensure session is started BEFORE checking session variables
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user ID session variable is set (which logout.php destroys).
// Redirect to login if not set.
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?error=login_required');
    exit; // Stop script execution immediately
}
// --- If we reach here, the user is logged in ---
// --- END Authentication Check ---


// --- Original PHP code for the page starts below ---
require_once 'config.php';
// ... rest of the PHP code for that specific page ...
?>

// accounting.php - Accounting Application Page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounting - Accounting ERP</title> <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />

</head>
<body>

    <?php include 'navbar.php'; // Common Navbar ?>

    <div class="page-content">

        <h1>Sales Application</h1>

        <div class="search-bar"> ... </div>

        <p>This is where the sales content ...</p>
        </div>

        
        <div class="search-bar">
            <input type="text" id="accounting-search" placeholder="Search accounts, journal entries..." aria-label="Search Accounting Content">
        </div>

        <p>This is where the accounting content (chart of accounts, ledgers, etc.) that will be searched goes.</p>
        <ul id="chart-of-accounts"> <li>1001 - Cash</li>
            <li>1101 - Accounts Receivable</li>
            <li>2001 - Accounts Payable</li>
            </ul>


    </div> <script>
        $(document).ready(function() {
            console.log("Accounting page JavaScript loaded.");

            // --- Accounting Page Search Functionality ---
            $('#accounting-search').on('input', function() {
                let searchTerm = $(this).val().toLowerCase().trim();

                // Example: Filtering list items in a ul with id="chart-of-accounts"
                $('#chart-of-accounts li').each(function() {
                    let itemText = $(this).text().toLowerCase();
                    if (itemText.includes(searchTerm)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });

                // Add logic here for other searchable elements if needed
            });

            // Add other Accounting page specific JS interactions here

        });
    </script>

</body>
</html>
<?php
// accounting.php - Accounting Application Page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounting - Accounting ERP</title> <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />

</head>
<body>

    <?php include 'navbar.php'; // Common Navbar ?>

    <div class="page-content">

        <h1>Sales Application</h1>

        <div class="search-bar"> ... </div>

        <p>This is where the sales content ...</p>
        </div>

        
        <div class="search-bar">
            <input type="text" id="accounting-search" placeholder="Search accounts, journal entries..." aria-label="Search Accounting Content">
        </div>

        <p>This is where the accounting content (chart of accounts, ledgers, etc.) that will be searched goes.</p>
        <ul id="chart-of-accounts"> <li>1001 - Cash</li>
            <li>1101 - Accounts Receivable</li>
            <li>2001 - Accounts Payable</li>
            </ul>


    </div> <script>
        $(document).ready(function() {
            console.log("Accounting page JavaScript loaded.");

            // --- Accounting Page Search Functionality ---
            $('#accounting-search').on('input', function() {
                let searchTerm = $(this).val().toLowerCase().trim();

                // Example: Filtering list items in a ul with id="chart-of-accounts"
                $('#chart-of-accounts li').each(function() {
                    let itemText = $(this).text().toLowerCase();
                    if (itemText.includes(searchTerm)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });

                // Add logic here for other searchable elements if needed
            });

            // Add other Accounting page specific JS interactions here

        });
    </script>

</body>
</html>
