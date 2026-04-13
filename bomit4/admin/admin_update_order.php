<?php
// admin/admin_update_order.php
// Simple version - Update display order

session_start();
include '../db_connect.php';

header('Content-Type: application/json');

// Check if admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

// Get data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data']);
    exit();
}

$type = $data['type'];
$order = $data['order'];

// Choose table
if ($type === 'equipment') {
    $table = 'network_equipment';
} elseif ($type === 'cable') {
    $table = 'network_cables_accessories';
} elseif ($type === 'config') {
    $table = 'network_infrastructure_config';
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid type']);
    exit();
}

// Update each item
$updated = 0;
foreach ($order as $item) {
    $id = (int)$item['id'];
    $display_order = (int)$item['display_order'];
    
    $sql = "UPDATE $table SET display_order = $display_order WHERE id = $id";
    if ($conn->query($sql)) {
        $updated++;
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Updated successfully',
    'updated_count' => $updated
]);

$conn->close();
?>