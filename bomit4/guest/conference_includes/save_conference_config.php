<?php
// guest/conference_includes/save_conference_config.php
// Save Conference Room Configuration to user_configurations table

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

if ($_POST['action'] !== 'save_conference_config') {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

$user_id      = $_SESSION['user_id'];
$project_name = $_POST['project_name'] ?? '';

if (empty($project_name)) {
    echo json_encode(['success' => false, 'message' => 'Project name is required']);
    exit();
}

// Collect equipment data
$equipment_data     = json_decode($_POST['equipment_data']     ?? '[]', true);
$av_selections      = json_decode($_POST['av_selections']      ?? '{}', true);

$configuration = [
    'project_info' => [
        'project_name'      => $_SESSION['project_name']       ?? $project_name,
        'requesting_manager'=> $_SESSION['requesting_manager'] ?? '',
        'project_duration'  => $_SESSION['project_duration']   ?? '',
        'deployment_date'   => $_SESSION['deployment_date']    ?? '',
        'user_quantity'     => $_SESSION['user_quantity']       ?? '',
    ],
    'room_info' => [
        'conference_size'        => $_POST['conference_size']        ?? '',
        'conference_meeting_type'=> $_POST['conference_meeting_type'] ?? '',
        'conference_setup_type'  => $_POST['conference_setup_type']  ?? '',
    ],
    'av_connectivity' => [
        'display_type'     => $_POST['av_display_type']      ?? '',
        'display_required' => isset($_POST['av_display_required']),
        'vc_platform'      => $_POST['av_vc_platform']       ?? '',
        'vc_required'      => isset($_POST['av_vc_required']),
        'wireless_type'    => $_POST['av_wireless_type']     ?? '',
        'wireless_required'=> isset($_POST['av_wireless_required']),
        'wired_drops'      => $_POST['av_wired_drops']       ?? '',
        'wired_required'   => isset($_POST['av_wired_required']),
        'control_system'   => $_POST['av_control_system']    ?? '',
        'control_required' => isset($_POST['av_control_required']),
        'network_type'     => $_POST['av_network_type']      ?? '',
        'network_required' => isset($_POST['av_network_required']),
    ],
    'equipment'   => $equipment_data,
    'av_selections'=> $av_selections,
    'notes'       => $_POST['conference_notes'] ?? '',
    'saved_at'    => date('Y-m-d H:i:s'),
];

$config_json = json_encode($configuration, JSON_PRETTY_PRINT);

try {
    $check = $conn->prepare(
        "SELECT id FROM user_configurations 
         WHERE user_id=? AND project_name=? AND configuration_type='conference'"
    );
    $check->bind_param("ss", $user_id, $project_name);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $stmt = $conn->prepare(
            "UPDATE user_configurations 
             SET configuration_data=?, updated_at=CURRENT_TIMESTAMP 
             WHERE user_id=? AND project_name=? AND configuration_type='conference'"
        );
        $stmt->bind_param("sss", $config_json, $user_id, $project_name);
        $message = 'Conference configuration updated successfully';
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO user_configurations (user_id, project_name, configuration_type, configuration_data) 
             VALUES (?, ?, 'conference', ?)"
        );
        $stmt->bind_param("sss", $user_id, $project_name, $config_json);
        $message = 'Conference configuration saved successfully';
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
    $check->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
