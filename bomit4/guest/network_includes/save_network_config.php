<?php
// network_includes/save_network_config.php
// Save Network Infrastructure Configuration to Database

// Turn off error display to prevent HTML in JSON response
error_reporting(0);
ini_set('display_errors', 0);

session_start();

// Use absolute path for db_connect
$db_path = dirname(__FILE__) . '/../db_connect.php';
if (!file_exists($db_path)) {
    // Try alternative path
    $db_path = dirname(__FILE__) . '/../../db_connect.php';
}

include $db_path;

// Set JSON header AFTER all includes
header('Content-Type: application/json');

// Clear any output buffer
if (ob_get_length()) ob_clean();

// Check authentication
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_POST['action'] === 'save_network_config') {
    $user_id = $_SESSION['user_id'];
    $project_name = $_POST['project_name'] ?? '';
    
    // Validate required fields
    if (empty($project_name)) {
        echo json_encode(['success' => false, 'message' => 'Project name is required']);
        exit();
    }
    
     // ✅ Decode installation selections
    $installation_selections = json_decode($_POST['installation_selections'] ?? '[]', true);

    // ⭐ IMPORTANT: Always use SESSION values for project info (not form POST data)
    // This ensures we always save the latest values from Project Details page
    // ✅ Build configuration with installation data
    $configuration = [
        'project_info' => [
            'project_name' => $_SESSION['project_name'] ?? $_POST['project_name'],
            'requesting_manager' => $_SESSION['requesting_manager'] ?? $_POST['requesting_manager'],
            'project_duration' => $_SESSION['project_duration'] ?? $_POST['project_duration'],
            'deployment_date' => $_SESSION['deployment_date'] ?? $_POST['deployment_date'],
            'user_quantity' => $_SESSION['user_quantity'] ?? $_POST['user_quantity']
        ],
        'site_config' => [
            'site_type' => $_POST['site_type'] ?? '',
            'server_required' => $_POST['server_required'] ?? ''
        ],
        'network_infrastructure' => [
            'internet_access' => $_POST['internet_access'] ?? '',
            'dia' => $_POST['dia'] ?? '',
            'business_broadband' => $_POST['business_broadband'] ?? '',
            'starlink_type' => $_POST['starlink_type'] ?? '',
            'wan_connectivity' => $_POST['wan_connectivity'] ?? '',
            'vsat' => $_POST['vsat'] ?? '',
            'vsat_ha' => $_POST['vsat_ha'] ?? '',
            'vsat_service' => $_POST['vsat_service'] ?? ''
        ],
        'installation_services' => $installation_selections, // ✅ NEW: Save installation selections
        'equipment' => json_decode($_POST['equipment_data'] ?? '[]', true),
        'cables' => json_decode($_POST['cables_data'] ?? '[]', true),
        'notes' => $_POST['notes'] ?? '',
        'saved_at' => date('Y-m-d H:i:s')
    ];
    
    $config_json = json_encode($configuration, JSON_PRETTY_PRINT);
    
    try {
        // Check if configuration already exists for this user and project
        $check = $conn->prepare("SELECT id FROM user_configurations WHERE user_id=? AND project_name=? AND configuration_type='network'");
        $check->bind_param("ss", $user_id, $project_name);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing configuration
            $stmt = $conn->prepare("UPDATE user_configurations SET configuration_data=?, updated_at=CURRENT_TIMESTAMP WHERE user_id=? AND project_name=? AND configuration_type='network'");
            $stmt->bind_param("sss", $config_json, $user_id, $project_name);
            $message = 'Configuration updated successfully';
        } else {
            // Insert new configuration
            $stmt = $conn->prepare("INSERT INTO user_configurations (user_id, project_name, configuration_type, configuration_data) VALUES (?, ?, 'network', ?)");
            $stmt->bind_param("sss", $user_id, $project_name, $config_json);
            $message = 'Configuration saved successfully';
        }
        
        if ($stmt->execute()) {
            // Log what was saved for debugging
            error_log("Network Config Saved - Installations: " . count($installation_selections));
            
            echo json_encode([
                'success' => true, 
                'message' => $message,
                'saved_at' => date('M d, Y h:i A'),
                'installation_count' => count($installation_selections)
            ]);
        } else {
            throw new Exception('Database error: ' . $stmt->error);
        }
        
        $stmt->close();
        $check->close();
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

$conn->close();
?>