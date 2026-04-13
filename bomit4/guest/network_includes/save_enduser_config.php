<?php
// guest/network_includes/save_enduser_config.php
// Saves End User Equipment configuration to user_configurations table
// Called by end_user.php via fetch POST

error_reporting(0);
ini_set('display_errors', 0);

session_start();

// Find db_connect from this file's location (guest/network_includes/)
$db_path = dirname(__FILE__) . '/../../db_connect.php';
if (!file_exists($db_path)) {
    $db_path = dirname(__FILE__) . '/../db_connect.php';
}
include $db_path;

header('Content-Type: application/json');
if (ob_get_length()) ob_clean();

// Auth check
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_POST['action']) || $_POST['action'] !== 'save_enduser_config') {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

$user_id      = $_SESSION['user_id'];
$project_name = $_POST['project_name'] ?? '';
$user_type    = $_POST['user_type']    ?? '';

if (empty($project_name)) {
    echo json_encode(['success' => false, 'message' => 'Project name is required']);
    exit();
}

// Parse config data
$config_data_raw = $_POST['config_data'] ?? '{}';
$config_data     = json_decode($config_data_raw, true);

if (!$config_data) {
    echo json_encode(['success' => false, 'message' => 'Invalid configuration data']);
    exit();
}

// Build full configuration with project info from session
$configuration = [
    'project_info' => [
        'project_name'       => $_SESSION['project_name']       ?? $project_name,
        'requesting_manager' => $_SESSION['requesting_manager'] ?? '',
        'project_duration'   => $_SESSION['project_duration']   ?? '',
        'deployment_date'    => $_SESSION['deployment_date']    ?? '',
        'user_quantity'      => $_SESSION['user_quantity']      ?? '',
    ],
    'user_type'   => $user_type,
    'categories'  => $config_data['categories'] ?? [],
    'grand_total' => $config_data['grand_total'] ?? 0,
    'notes'       => $_POST['notes'] ?? $config_data['notes'] ?? '',
    'saved_at'    => date('Y-m-d H:i:s'),
];

$config_json = json_encode($configuration, JSON_PRETTY_PRINT);

try {
    // Check if config already exists for this user + project
    $check = $conn->prepare(
        "SELECT id FROM user_configurations
         WHERE user_id = ? AND project_name = ? AND configuration_type = 'enduser'"
    );
    $check->bind_param("ss", $user_id, $project_name);
    $check->execute();
    $exists = $check->get_result()->num_rows > 0;
    $check->close();

    if ($exists) {
        $stmt = $conn->prepare(
            "UPDATE user_configurations
             SET configuration_data = ?, updated_at = CURRENT_TIMESTAMP
             WHERE user_id = ? AND project_name = ? AND configuration_type = 'enduser'"
        );
        $stmt->bind_param("sss", $config_json, $user_id, $project_name);
        $message = 'End User configuration updated successfully';
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO user_configurations (user_id, project_name, configuration_type, configuration_data)
             VALUES (?, ?, 'enduser', ?)"
        );
        $stmt->bind_param("sss", $user_id, $project_name, $config_json);
        $message = 'End User configuration saved successfully';
    }

    if ($stmt->execute()) {
        echo json_encode([
            'success'  => true,
            'message'  => $message,
            'saved_at' => date('M d, Y h:i A'),
        ]);
    } else {
        throw new Exception('Database error: ' . $stmt->error);
    }

    $stmt->close();

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
    ]);
}

$conn->close();
?>