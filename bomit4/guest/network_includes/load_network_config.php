<?php
// network_includes/load_network_config.php
// Load Network Infrastructure Configuration from Database

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

$user_id = $_SESSION['user_id'];
$project_name = $_GET['project_name'] ?? '';

if (empty($project_name)) {
    echo json_encode(['success' => false, 'message' => 'Project name is required']);
    exit();
}

try {
    // Fetch configuration
    $stmt = $conn->prepare("SELECT configuration_data, updated_at FROM user_configurations WHERE user_id=? AND project_name=? AND configuration_type='network' ORDER BY updated_at DESC LIMIT 1");
    $stmt->bind_param("ss", $user_id, $project_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $configuration = json_decode($row['configuration_data'], true);
        
        echo json_encode([
            'success' => true,
            'configuration' => $configuration,
            'last_saved' => date('M d, Y h:i A', strtotime($row['updated_at']))
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No saved configuration found for this project'
        ]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>