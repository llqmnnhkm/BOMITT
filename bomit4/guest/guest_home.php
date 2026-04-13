<?php
session_start();
include '../db_connect.php';
// Check if user is logged in
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$project_name = $_SESSION['project_name'] ?? '';
$requesting_manager = $_SESSION['requesting_manager'] ?? '';
$project_duration = $_SESSION['project_duration'] ?? '';
$deployment_date = $_SESSION['deployment_date'] ?? '';
$user_quantity = $_SESSION['user_quantity'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Equipment BoM System - Guest</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- External CSS Files -->
    <link rel="stylesheet" href="css/guest_home.css">
</head>

<body>
    <header>
        <div class="user-section">
            <a href="project_details.php"
               style="display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; background: linear-gradient(145deg, #ffffff, #f1f5f9); border-radius: 50%; border: 1px solid #e2e8f0; box-shadow: 2px 2px 6px rgba(0,0,0,0.12), -2px -2px 6px rgba(255,255,255,0.8); text-decoration: none; font-size: 20px; line-height: 0; color: #0070ef; transition: all 0.25s ease; cursor: pointer;"
               title="Edit Project Details"
               onmouseover="this.style.background='linear-gradient(145deg, #0070ef, #80c7a0)'; this.style.color='#ffffff'; this.style.boxShadow='0 4px 12px rgba(59,130,246,0.5)'; this.style.transform='scale(1.12)';"
               onmouseout="this.style.background='linear-gradient(145deg, #ffffff, #f1f5f9)'; this.style.color='#3b82f6'; this.style.boxShadow='2px 2px 6px rgba(0,0,0,0.12), -2px -2px 6px rgba(255,255,255,0.8)'; this.style.transform='scale(1)';">
                📝
            </a>

            <a href="view_cart.php"
               style="display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; background: linear-gradient(145deg, #ffffff, #f1f5f9); border-radius: 50%; border: 1px solid #e2e8f0; box-shadow: 2px 2px 6px rgba(0,0,0,0.12), -2px -2px 6px rgba(255,255,255,0.8); text-decoration: none; font-size: 20px; line-height: 0; color: #80c7a0; transition: all 0.25s ease; cursor: pointer;"
               title="View Cart"
               onmouseover="this.style.background='linear-gradient(145deg, #0070ef, #80c7a0)'; this.style.color='#ffffff'; this.style.boxShadow='0 4px 12px rgba(16,185,129,0.45)'; this.style.transform='scale(1.12)';"
               onmouseout="this.style.background='linear-gradient(145deg, #ffffff, #f1f5f9)'; this.style.color='#10b981'; this.style.boxShadow='2px 2px 6px rgba(0,0,0,0.12), -2px -2px 6px rgba(255,255,255,0.8)'; this.style.transform='scale(1)';">
                🛒
            </a>

            <!-- Currency toggle: MYR | USD | EUR -->
            <div id="bomit-currency-toggle"
                 style="display:flex; align-items:center; gap:6px; margin-right:4px;">
            </div>

            <div class="dropdown">
                <button class="guest-badge" id="dropdownBtn">
                    Guest: <?php echo htmlspecialchars($user_id); ?> ▼
                </button>
                <div class="dropdown-content" id="dropdownMenu">
                    <a href="../index.php" onclick="return confirmLogout()">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main>
        <div class="welcome-section">
            <h2>BoMIT System</h2>
            <p>Select a category below to configure your IT equipment requirements</p>
        </div>

        <!-- Category Cards -->
        <div class="categories-grid">
            <div class="category-card" onclick="showCategory('server')" id="card-server">
                <div class="category-header server">🖥️</div>
                <div class="category-body">
                    <h3>Server Infrastructure</h3>
                    <p>Configure server storage and applications</p>
                </div>
            </div>

            <div class="category-card" onclick="showCategory('network')" id="card-network">
                <div class="category-header network">🌐</div>
                <div class="category-body">
                    <h3>Network Infrastructure</h3>
                    <p>Configure networking equipment</p>
                </div>
            </div>

            <div class="category-card" onclick="showCategory('conference')" id="card-conference">
                <div class="category-header conference">👥</div>
                <div class="category-body">
                    <h3>Conference Room</h3>
                    <p>Configure meeting room technology</p>
                </div>
            </div>

            <div class="category-card" onclick="showCategory('enduser')" id="card-enduser">
                <div class="category-header enduser">💻</div>
                <div class="category-body">
                    <h3>End User Equipment</h3>
                    <p>Configure workstations and peripherals</p>
                </div>
            </div>
        </div>

        <!-- Question Containers-->
        <?php include 'server_infra.php'; ?>
        <?php include 'network_infrastructure.php'; ?>
        <?php include 'conference_room.php'; ?>
        <?php include 'end_user.php'; ?>
    </main>

        <?php
        // NOW close the connection after everything is done
        $conn->close();
        ?>

        <footer>
            <div class="footer-content">
                © 2024 IT Equipment BoM System. All rights reserved.
            </div>
        </footer>

        <!-- SheetJS for Excel export -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
        <!-- Shared Excel export functions -->
        <script src="js/export_excel.js"></script>
        <!-- Currency switcher — loaded here so DOM is ready -->
        <script src="js/currency.js"></script>

        <script>
            function showCategory(category) {
                // Hide all containers and deactivate all cards
                document.querySelectorAll('.question-container').forEach(c => c.classList.remove('active'));
                document.querySelectorAll('.category-card').forEach(c => c.classList.remove('active'));

                // Show the selected container
                const container = document.getElementById('container-' + category);
                const card      = document.getElementById('card-' + category);
                if (container) container.classList.add('active');
                if (card)      card.classList.add('active');

                // Retag currency price cells after section opens
                setTimeout(function() {
                    if (typeof bomitTagUnitPriceCells === 'function') bomitTagUnitPriceCells();
                    if (typeof bomitRefreshAll === 'function') bomitRefreshAll();
                }, 400);
            }

            function hideAllContainers() {
                document.querySelectorAll('.question-container').forEach(c => c.classList.remove('active'));
                document.querySelectorAll('.category-card').forEach(c => c.classList.remove('active'));
            }

            function confirmLogout() {
                return confirm('Are you sure you want to logout?');
            }

            // Dropdown toggle
            document.getElementById('dropdownBtn').addEventListener('click', function(e) {
                e.stopPropagation();
                this.closest('.dropdown').classList.toggle('active');
            });
            document.addEventListener('click', function() {
                const dd = document.querySelector('.dropdown');
                if (dd) dd.classList.remove('active');
            });
        </script>
    </body>
</html>