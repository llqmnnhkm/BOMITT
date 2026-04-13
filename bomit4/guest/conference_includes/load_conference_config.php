<?php
// guest/conference_includes/load_conference_config.php

error_reporting(0);
ini_set('display_errors', 0);

session_start();

$db_path = dirname(__FILE__) . '/../db_connect.php';
if (!file_exists($db_path)) {
    $db_path = dirname(__FILE__) . '/../../db_connect.php';
}
include $db_path;

header('Content-Type: application/json');
if (ob_get_length()) ob_clean();

if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id      = $_SESSION['user_id'];
$project_name = $_GET['project_name'] ?? '';

if (empty($project_name)) {
    echo json_encode(['success' => false, 'message' => 'Project name is required']);
    exit();
}

try {
    $stmt = $conn->prepare(
        "SELECT configuration_data, updated_at 
         FROM user_configurations 
         WHERE user_id=? AND project_name=? AND configuration_type='conference' 
         ORDER BY updated_at DESC LIMIT 1"
    );
    $stmt->bind_param("ss", $user_id, $project_name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row   = $result->fetch_assoc();
        $config = json_decode($row['configuration_data'], true);
        echo json_encode([
            'success'       => true,
            'configuration' => $config,
            'last_saved'    => date('M d, Y h:i A', strtotime($row['updated_at'])),
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No saved configuration found']);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
