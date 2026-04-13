<?php
session_start();
include '../db_connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Equipment BoM System - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="admin_includes/css/admin_style.css">
    <link rel="stylesheet" href="admin_includes/css/admin_network_management.css">
    <script src="admin_includes/js/admin_drag_drop_handler.js" defer></script>

    <!-- Core functions defined in <head> so they are always available,
         even if a PHP include below throws an error that truncates the page -->
    <script>
        function showManagement(category) {
            document.querySelectorAll('.management-container').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.category-card').forEach(c => c.classList.remove('active'));
            var container = document.getElementById('container-' + category);
            var card      = document.getElementById('card-' + category);
            if (container) { container.classList.add('active'); }
            if (card)      { card.classList.add('active'); }
            if (container) {
                setTimeout(function() {
                    container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }, 100);
            }
        }
        function hideAllContainers() {
            document.querySelectorAll('.management-container').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.category-card').forEach(c => c.classList.remove('active'));
        }
    </script>
</head>
<body>
    <!-- Admin Header -->
    <header class="admin-header">
        <h1>🛠️ BoMIT Admin Dashboard</h1>
        <div class="user-section">
            <span class="admin-badge">ADMIN</span>
            <div class="dropdown">
                <button class="dropdown-btn" id="dropdownBtn">
                    <?php echo htmlspecialchars($user_id); ?> ▼
                </button>
                <div class="dropdown-content" id="dropdownMenu">
                    <a href="../logout.php" onclick="return confirm('Are you sure you want to logout?')">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main>
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h2>Equipment Management Dashboard</h2>
            <p>Manage equipment items, pricing, and configurations for all categories</p>
        </div>

        <!-- Management Categories -->
        <div class="categories-grid" style="grid-template-columns: repeat(4, 1fr);">
            <div class="category-card" id="card-server">
                <div class="category-header server">🖥️</div>
                <div class="category-body">
                    <h3>Server Infrastructure</h3>
                    <p>Manage server equipment and configurations</p>
                    <button class="manage-btn" onclick="showManagement('server')">Manage Equipment</button>
                </div>
            </div>

            <div class="category-card" id="card-network">
                <div class="category-header network">🌐</div>
                <div class="category-body">
                    <h3>Network Infrastructure</h3>
                    <p>Manage network equipment and pricing</p>
                    <button class="manage-btn" onclick="showManagement('network')">Manage Equipment</button>
                </div>
            </div>

            <div class="category-card" id="card-conference">
                <div class="category-header conference">💥</div>
                <div class="category-body">
                    <h3>Conference Room</h3>
                    <p>Manage meeting room technology</p>
                    <button class="manage-btn" onclick="showManagement('conference')">Manage Equipment</button>
                </div>
            </div>

            <div class="category-card" id="card-enduser">
                <div class="category-header enduser">💻</div>
                <div class="category-body">
                    <h3>End User Equipment</h3>
                    <p>Manage workstations and peripherals</p>
                    <button class="manage-btn" onclick="showManagement('enduser')">Manage Equipment</button>
                </div>
            </div>

            <div class="category-card" id="card-accounts"
                 style="grid-column: 4;">
                <div class="category-header" style="background:linear-gradient(135deg,#0070ef,#4527a0);">👤</div>
                <div class="category-body">
                    <h3>Account Manager</h3>
                    <p>Create and manage user accounts</p>
                    <button class="manage-btn" onclick="showManagement('accounts')">Manage Accounts</button>
                </div>
            </div>
        </div>

        <!-- Management Containers -->
        <div id="container-server" class="management-container">
            <div class="container-header">
                <div class="header-left">
                    <div class="container-icon server">🖥️</div>
                    <div class="container-title">
                        <h3>Server Infrastructure Management</h3>
                        <p>Add, edit, or remove server equipment items</p>
                    </div>
                </div>
                <button class="close-btn" onclick="hideAllContainers()">✕ Close</button>
            </div>
            <?php if (file_exists(__DIR__ . '/admin_server_management.php')) { include 'admin_server_management.php'; } else { echo '<p style="color:red;padding:1rem;">⚠️ admin_server_management.php not found</p>'; } ?>
        </div>

        <div id="container-network" class="management-container">
            <div class="container-header">
                <div class="header-left">
                    <div class="container-icon network">🌐</div>
                    <div class="container-title">
                        <h3>Network Infrastructure Management</h3>
                        <p>Add, edit, or remove network equipment items</p>
                    </div>
                </div>
                <button class="close-btn" onclick="hideAllContainers()">✕ Close</button>
            </div>
            <?php if (file_exists(__DIR__ . '/admin_network_management.php')) { include 'admin_network_management.php'; } else { echo '<p style="color:red;padding:1rem;">⚠️ admin_network_management.php not found</p>'; } ?>
        </div>

        <div id="container-conference" class="management-container">
            <div class="container-header">
                <div class="header-left">
                    <div class="container-icon conference">💥</div>
                    <div class="container-title">
                        <h3>Conference Room Management</h3>
                        <p>Add, edit, or remove conference equipment items</p>
                    </div>
                </div>
                <button class="close-btn" onclick="hideAllContainers()">✕ Close</button>
            </div>
            <?php if (file_exists(__DIR__ . '/admin_conference_management.php')) { include 'admin_conference_management.php'; } else { echo '<p style="color:red;padding:1rem;">⚠️ admin_conference_management.php not found</p>'; } ?>
        </div>

        <div id="container-enduser" class="management-container">
            <div class="container-header">
                <div class="header-left">
                    <div class="container-icon enduser">💻</div>
                    <div class="container-title">
                        <h3>End User Equipment Management</h3>
                        <p>Add, edit, or remove end user equipment items</p>
                    </div>
                </div>
                <button class="close-btn" onclick="hideAllContainers()">✕ Close</button>
            </div>
            <?php if (file_exists(__DIR__ . '/admin_enduser_management.php')) { include 'admin_enduser_management.php'; } else { echo '<p style="color:red;padding:1rem;">⚠️ admin_enduser_management.php not found</p>'; } ?>
        </div>

        <!-- Account Manager Container -->
        <div id="container-accounts" class="management-container">
            <div class="container-header">
                <div class="header-left">
                    <div class="container-icon accounts">👤</div>
                    <div class="container-title">
                        <h3>Account Manager</h3>
                        <p>Create, edit and manage user accounts</p>
                    </div>
                </div>
                <button class="close-btn" onclick="hideAllContainers()">✕ Close</button>
            </div>
            <?php if (file_exists(__DIR__ . '/admin_account_management.php')) {
                include __DIR__ . '/admin_account_management.php';
            } ?>
        </div>


    </main>

        

    <!-- ── Exchange Rate Management ─────────────────────────────────────────── -->
    <div style="max-width:1400px; margin:0 auto; padding:0 2rem 2rem;">
        <?php
        if (!isset($conn)) include '../db_connect.php';
        if (file_exists(__DIR__ . '/admin_includes/exchange_rates.php')) {
            include __DIR__ . '/admin_includes/exchange_rates.php';
        }
        ?>
    </div>

    <footer>
        <div class="footer-content">
            © 2024 IT Equipment BoM System - Admin Dashboard. All rights reserved.
        </div>
    </footer>

    <script>
        // Dropdown
        document.addEventListener('DOMContentLoaded', function() {
            var dropdownBtn = document.getElementById('dropdownBtn');
            var dropdown    = dropdownBtn.parentElement;

            dropdownBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdown.classList.toggle('active');
            });
            document.addEventListener('click', function(e) {
                if (!dropdown.contains(e.target)) {
                    dropdown.classList.remove('active');
                }
            });

            // Live stats from DB via AJAX
            fetch('admin_includes/handlers/stats_handler.php')
                .then(r => r.json())
                .then(data => {
                    if (data.network    !== undefined) document.getElementById('network-count').textContent    = data.network;
                    if (data.conference !== undefined) document.getElementById('conference-count').textContent = data.conference;
                    if (data.enduser    !== undefined) document.getElementById('enduser-count').textContent    = data.enduser;
                    if (data.server     !== undefined) document.getElementById('server-count').textContent     = data.server;
                })
                .catch(() => {
                    // Fallback placeholders if handler not yet created
                    document.getElementById('server-count').textContent     = '–';
                    document.getElementById('network-count').textContent    = '–';
                    document.getElementById('conference-count').textContent = '–';
                    document.getElementById('enduser-count').textContent    = '–';
                });
        });
    </script>
</body>
</html>