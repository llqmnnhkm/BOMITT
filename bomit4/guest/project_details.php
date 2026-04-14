<?php
session_start();

if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true) {
    header("Location: index.php");
    exit();
}

// Fetch user ID from session
$user_id = $_SESSION['user_id'] ?? 'Guest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['project_name'] = $_POST['project_name'];
    $_SESSION['requesting_manager'] = $_POST['requesting_manager'];
    $_SESSION['project_duration'] = $_POST['project_duration'];
    $_SESSION['deployment_date'] = $_POST['deployment_date'];
    $_SESSION['user_quantity'] = $_POST['user_quantity'];

    // Redirect to guest_home.php
    header('Location: guest_home.php');
    exit();
}

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
    <title>BoMIT System - Project Details</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/project_details.css">
</head>
<body>
    <header>
        <div class="user-section">
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
        </div>

        <div id="container-server" class="project-container active" style="max-width: 900px;">
            <div class="container-header" style="justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div class="container-icon server"></div>
                    <div class="container-title">
                        <h3>Project Details</h3>
                        <p>Specify your project details for easier configuration</p>
                    </div>
                </div>

                <button type="button" id="clear"
                    style="
                        background: #ee7766;
                        color: white;
                        border: none;
                        border-radius: 6px;
                        padding: 8px 16px;
                        cursor: pointer;
                        font-family: Montserrat, sans-serif;
                        font-weight: 600;
                        height: fit-content;
                        align-self: flex-start;
                    ">
                    Clear
                </button>
            </div>

            <!-- Success Message -->
            <div id="successMessage" class="success-message">
                ✅ Project details saved successfully! Redirecting...
            </div>

            <form id="projectForm" method="POST" action="">
    
                <div class="top-textboxes" style="display: flex; flex-wrap: wrap; gap: 1.5rem; margin-bottom: 2rem;">
                    <div class="textbox-item" style="flex: 1; min-width: 250px;">
                        <label style="font-weight: 600; display: block; margin-bottom: 0.5rem;">
                            Project Name: <span class="required">*</span>
                        </label>
                        <input type="text" name="project_name" 
                            value="<?php echo htmlspecialchars($project_name); ?>" 
                            required
                            style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-family: Montserrat; font-size: 1rem;">
                    </div>

                    <div class="textbox-item" style="flex: 1; min-width: 250px;">
                        <label style="font-weight: 600; display: block; margin-bottom: 0.5rem;">
                            Requesting IT Project Manager: <span class="required">*</span>
                        </label>
                        <input type="text" name="requesting_manager" 
                            value="<?php echo htmlspecialchars($requesting_manager); ?>" 
                            required
                            style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-family: Montserrat; font-size: 1rem;">
                    </div>

                    <div class="textbox-item" style="flex: 1; min-width: 250px;">
                        <label style="font-weight: 600; display: block; margin-bottom: 0.5rem;">
                            Project Duration (In Months): <span class="required">*</span>
                        </label>
                        <input type="number" name="project_duration" 
                            min="1" 
                            value="<?php echo htmlspecialchars($project_duration); ?>"
                            required
                            style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-family: Montserrat; font-size: 1rem;">
                    </div>

                    <div class="textbox-item" style="flex: 1; min-width: 250px;">
                        <label style="font-weight: 600; display: block; margin-bottom: 0.5rem;">
                            Required Deployment Date: <span class="required">*</span>
                        </label>
                        <input type="date" name="deployment_date" 
                            min="<?php echo date('Y-m-d'); ?>" 
                            value="<?php echo htmlspecialchars($deployment_date); ?>"
                            required
                            style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-family: Montserrat; font-size: 1rem;">
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 1rem; margin-top: 1rem; flex-wrap: wrap;">
                    <div class="textbox-item">
                        <label style="font-weight: 600; display:block; margin-bottom: 0.5rem;">
                            Number of Users: <span class="required">*</span>
                        </label>
                        <select name="user_quantity"
                            required
                            style="width: 220px; text-align:center; font-family: Montserrat; padding: 10px; font-size: 1rem; border-radius: 6px; border: 1px solid #ccc; background-color: #fff;">
                            
                            <?php
                            $options = [
                                "Less than 50 users",
                                "51-150 users",
                                "151-300 users",
                                "301-400 users",
                                "More than 400 users"
                            ];

                            foreach ($options as $option) {
                                $selected = ($option == $user_quantity) ? "selected" : "";
                                echo "<option value=\"$option\" $selected>$option</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <button type="submit" id="confirm"
                        style="
                            font-family: Montserrat, sans-serif;
                            padding: 10px 20px;
                            background: linear-gradient(90deg, #80c7a0, #0070ef);
                            color: white;
                            border: none;
                            border-radius: 6px;
                            cursor: pointer;
                            margin-top: 1.5rem;
                            font-weight: 600;
                            font-size: 1rem;
                            width: 50%;
                            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
                            transition: transform 0.2s ease, box-shadow 0.2s ease;
                        "
                        onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 14px rgba(0,0,0,0.2)';"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 10px rgba(0,0,0,0.15)';">
                        Confirm & Save
                    </button>
                </div>

            </form>
        </div>
    </main>

    <footer>
        <div class="footer-content">
            © 2024 IT Equipment BoM System. All rights reserved.
        </div>
    </footer>

    <script>
    // Dropdown functionality
    const dropdownBtn = document.getElementById('dropdownBtn');
    const dropdownMenu = document.getElementById('dropdownMenu');
    const dropdown = dropdownBtn.parentElement;

    dropdownBtn.addEventListener('click', () => {
        dropdown.classList.toggle('active');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!dropdown.contains(e.target)) {
            dropdown.classList.remove('active');
        }
    });

    // Clear button functionality
    document.getElementById('clear').addEventListener('click', () => {
        if (!confirm('Are you sure you want to clear all fields?')) return;

        // Clear all input fields
        document.querySelectorAll('#projectForm input').forEach(input => {
            input.value = '';
        });

        // Reset select to first option
        document.querySelector('select[name="user_quantity"]').selectedIndex = 0;

        // Send request to PHP to clear session
        fetch('clear_session.php')
            .then(res => res.text())
            .then(() => {
                console.log('✅ Session cleared');
            })
            .catch(err => {
                console.error('❌ Error clearing session:', err);
            });
    });

    // Form submission with validation
    document.getElementById('projectForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default form submission
        
        // Get form data
        const formData = new FormData(this);
        
        // Log for debugging
        console.log('Submitting Project Details:');
        for (let [key, value] of formData.entries()) {
            console.log(`  ${key}: ${value}`);
        }
        
        // Show success message
        const successMsg = document.getElementById('successMessage');
        successMsg.style.display = 'block';
        
        // Submit form after a short delay to show success message
        setTimeout(() => {
            this.submit();
        }, 800);
    });

    // Logout confirmation
    function confirmLogout() {
        return confirm('Are you sure you want to logout?');
    }

    // Log current values on page load
    console.log('Current Project Details:');
    console.log('  Project Name:', document.querySelector('input[name="project_name"]').value);
    console.log('  Manager:', document.querySelector('input[name="requesting_manager"]').value);
    console.log('  Duration:', document.querySelector('input[name="project_duration"]').value, 'months');
    console.log('  Deployment Date:', document.querySelector('input[name="deployment_date"]').value);
    console.log('  User Quantity:', document.querySelector('select[name="user_quantity"]').value);
    </script>
</body>
</html>