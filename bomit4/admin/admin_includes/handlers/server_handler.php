<?php
// admin/admin_includes/handlers/server_handler.php
// AJAX CRUD handler for server_equipment table

session_start();
require_once dirname(__DIR__) . '/admin_utilities.php';
include dirname(dirname(__DIR__)) . '/db_connect.php';

setupErrorHandling();
header('Content-Type: application/json');
requireAdminAuth($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    jsonError('Invalid request');
}

$valid_types = ['core_infra', 'project_req', 'application'];

try {
    switch ($_POST['action']) {

        case 'add_server_item':
            $v = validateRequiredFields(['item_type','name','cores','memory']);
            if ($v !== true) jsonError('Missing fields: ' . implode(', ', $v));
            if (!in_array($_POST['item_type'], $valid_types)) jsonError('Invalid item type');
            if (!isValidNumber($_POST['cores'],  1)) jsonError('Cores must be ≥ 1');
            if (!isValidNumber($_POST['memory'], 1)) jsonError('Memory must be ≥ 1');

            $result = insertRecord($conn, 'server_equipment', [
                'item_type'            => sanitizeInput($_POST['item_type']),
                'item_name'            => sanitizeInput($_POST['name']),
                'item_description'     => sanitizeInput($_POST['description'] ?? ''),
                'default_cores'        => (int)$_POST['cores'],
                'default_memory'       => (int)$_POST['memory'],
                'default_os_storage'   => (int)($_POST['os_storage']   ?? 100),
                'default_data_storage' => (int)($_POST['data_storage'] ?? 100),
                'is_editable'          => (int)($_POST['is_editable']  ?? 1),
            ]);
            if ($result['success']) {
                logAdminAction('SRV_ADD', "Added: {$_POST['name']}");
                jsonSuccess($result['message'], ['id' => $result['id']]);
            } else { jsonError($result['message']); }
            break;

        case 'update_server_item':
            $v = validateRequiredFields(['id','name','cores','memory']);
            if ($v !== true) jsonError('Missing fields: ' . implode(', ', $v));
            if (!isValidNumber($_POST['id'],     1)) jsonError('Invalid ID');
            if (!isValidNumber($_POST['cores'],  1)) jsonError('Cores must be ≥ 1');
            if (!isValidNumber($_POST['memory'], 1)) jsonError('Memory must be ≥ 1');

            $result = updateRecord($conn, 'server_equipment', [
                'item_type'            => sanitizeInput($_POST['item_type'] ?? 'application'),
                'item_name'            => sanitizeInput($_POST['name']),
                'item_description'     => sanitizeInput($_POST['description'] ?? ''),
                'default_cores'        => (int)$_POST['cores'],
                'default_memory'       => (int)$_POST['memory'],
                'default_os_storage'   => (int)($_POST['os_storage']   ?? 100),
                'default_data_storage' => (int)($_POST['data_storage'] ?? 100),
                'is_editable'          => (int)($_POST['is_editable']  ?? 1),
            ], (int)$_POST['id']);
            if ($result['success']) {
                logAdminAction('SRV_UPDATE', "Updated ID:{$_POST['id']} — {$_POST['name']}");
                jsonSuccess($result['message']);
            } else { jsonError($result['message']); }
            break;

        case 'delete_server_item':
            if (!isset($_POST['id']) || !isValidNumber($_POST['id'], 1)) jsonError('Invalid ID');
            $result = deleteRecord($conn, 'server_equipment', (int)$_POST['id']);
            if ($result['success']) {
                logAdminAction('SRV_DELETE', "Deleted ID:{$_POST['id']}");
                jsonSuccess($result['message']);
            } else { jsonError($result['message']); }
            break;

        default: jsonError('Invalid action');
    }
} catch (Exception $e) {
    jsonError('Server error: ' . $e->getMessage());
}
$conn->close();
?>
