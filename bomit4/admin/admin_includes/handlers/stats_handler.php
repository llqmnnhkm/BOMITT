<?php
// admin/admin_includes/handlers/stats_handler.php
// Returns live item counts from all equipment tables as JSON

session_start();
require_once dirname(__DIR__) . '/admin_utilities.php';
include dirname(dirname(__DIR__)) . '/db_connect.php';

setupErrorHandling();
header('Content-Type: application/json');

requireAdminAuth($conn);

try {
    $counts = [];

    // Network equipment
    $r = $conn->query("SELECT COUNT(*) as c FROM network_equipment WHERE is_active = 1");
    $counts['network'] = $r ? (int)$r->fetch_assoc()['c'] : 0;

    // Conference equipment
    $r = $conn->query("SELECT COUNT(*) as c FROM conference_equipment WHERE is_active = 1");
    $counts['conference'] = $r ? (int)$r->fetch_assoc()['c'] : 0;

    // End user equipment
    $r = $conn->query("SELECT COUNT(*) as c FROM enduser_equipment WHERE is_active = 1");
    $counts['enduser'] = $r ? (int)$r->fetch_assoc()['c'] : 0;

    // Server (table may not exist yet — safe fallback)
    $r = $conn->query("SELECT COUNT(*) as c FROM server_equipment WHERE is_active = 1");
    $counts['server'] = $r ? (int)$r->fetch_assoc()['c'] : 0;

    echo json_encode($counts);

} catch (Exception $e) {
    echo json_encode(['network'=>0,'conference'=>0,'enduser'=>0,'server'=>0]);
}

$conn->close();
?>
