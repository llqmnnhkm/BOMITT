<?php
// admin/admin_includes/handlers/enduser_handler.php
// AJAX-only CRUD handler for End User Equipment items
// Mirrors equipment_handler.php / cables_handler.php pattern exactly

session_start();
require_once '../admin_utilities.php';
include '../../../db_connect.php';

setupErrorHandling();
header('Content-Type: application/json');

requireAdminAuth($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    jsonError('Invalid request');
}

// ── Allowed enums (whitelist validation) ──────────────────────────────────
$allowed_user_types  = ['general','technical','design','field','executive'];
$allowed_categories  = ['workstation','peripherals','mobile','software'];

$action = $_POST['action'];

try {
    switch ($action) {

        // ── ADD ────────────────────────────────────────────────────────────
        case 'add_item':
            $validation = validateRequiredFields(['user_type','item_category','name','quantity','price']);
            if ($validation !== true) {
                jsonError('Missing required fields: ' . implode(', ', $validation));
            }

            // Enum validation
            if (!in_array($_POST['user_type'], $allowed_user_types, true)) {
                jsonError('Invalid user type');
            }
            if (!in_array($_POST['item_category'], $allowed_categories, true)) {
                jsonError('Invalid item category');
            }

            // Numeric validation
            if (!isValidNumber($_POST['quantity'], 0)) jsonError('Quantity must be 0 or more');
            if (!isValidNumber($_POST['price'],    0)) jsonError('Price must be 0 or more');

            $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
            $is_active = in_array($is_active, [0,1]) ? $is_active : 1;

            $result = insertRecord($conn, 'enduser_equipment', [
                'user_type'        => sanitizeInput($_POST['user_type']),
                'item_category'    => sanitizeInput($_POST['item_category']),
                'item_name'        => sanitizeInput($_POST['name']),
                'item_description' => sanitizeInput($_POST['description'] ?? ''),
                'default_quantity' => (int)$_POST['quantity'],
                'unit_price'       => (float)$_POST['price'],
                'is_active'        => $is_active,
            ]);

            if ($result['success']) {
                logAdminAction('EU_ADD_ITEM', "Added: {$_POST['name']} [{$_POST['user_type']}/{$_POST['item_category']}]");
                jsonSuccess($result['message'], ['id' => $result['id']]);
            } else {
                jsonError($result['message']);
            }
            break;

        // ── UPDATE ─────────────────────────────────────────────────────────
        case 'update_item':
            $validation = validateRequiredFields(['id','user_type','item_category','name','quantity','price']);
            if ($validation !== true) {
                jsonError('Missing required fields: ' . implode(', ', $validation));
            }

            if (!isValidNumber($_POST['id'], 1))       jsonError('Invalid item ID');
            if (!in_array($_POST['user_type'], $allowed_user_types, true)) jsonError('Invalid user type');
            if (!in_array($_POST['item_category'], $allowed_categories, true)) jsonError('Invalid item category');
            if (!isValidNumber($_POST['quantity'], 0)) jsonError('Quantity must be 0 or more');
            if (!isValidNumber($_POST['price'],    0)) jsonError('Price must be 0 or more');

            $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
            $is_active = in_array($is_active, [0,1]) ? $is_active : 1;

            $result = updateRecord($conn, 'enduser_equipment', [
                'user_type'        => sanitizeInput($_POST['user_type']),
                'item_category'    => sanitizeInput($_POST['item_category']),
                'item_name'        => sanitizeInput($_POST['name']),
                'item_description' => sanitizeInput($_POST['description'] ?? ''),
                'default_quantity' => (int)$_POST['quantity'],
                'unit_price'       => (float)$_POST['price'],
                'is_active'        => $is_active,
            ], (int)$_POST['id']);

            if ($result['success']) {
                logAdminAction('EU_UPDATE_ITEM', "Updated ID:{$_POST['id']} — {$_POST['name']}");
                jsonSuccess($result['message']);
            } else {
                jsonError($result['message']);
            }
            break;

        // ── DELETE ─────────────────────────────────────────────────────────
        case 'delete_item':
            if (!isset($_POST['id']) || !isValidNumber($_POST['id'], 1)) {
                jsonError('Invalid item ID');
            }

            $result = deleteRecord($conn, 'enduser_equipment', (int)$_POST['id']);

            if ($result['success']) {
                logAdminAction('EU_DELETE_ITEM', "Deleted ID:{$_POST['id']}");
                jsonSuccess($result['message']);
            } else {
                jsonError($result['message']);
            }
            break;

        // ── TOGGLE ACTIVE ──────────────────────────────────────────────────
        case 'toggle_active':
            if (!isset($_POST['id']) || !isValidNumber($_POST['id'], 1)) {
                jsonError('Invalid item ID');
            }

            // Flip is_active
            $stmt = $conn->prepare("UPDATE enduser_equipment SET is_active = 1 - is_active WHERE id = ?");
            if (!$stmt) jsonError('Database error: ' . $conn->error);
            $id = (int)$_POST['id'];
            $stmt->bind_param('i', $id);

            if ($stmt->execute()) {
                // Return new state
                $row = $conn->query("SELECT is_active FROM enduser_equipment WHERE id = $id")->fetch_assoc();
                logAdminAction('EU_TOGGLE_ITEM', "Toggled ID:$id to " . ($row['is_active'] ? 'active' : 'inactive'));
                jsonSuccess('Status updated', ['is_active' => (int)$row['is_active']]);
            } else {
                jsonError('Update failed: ' . $stmt->error);
            }
            break;

        // ── GET ALL (for dynamic reload without page refresh) ─────────────
        case 'get_all':
            $records = getAllRecords($conn, 'enduser_equipment', 'user_type, item_category, display_order');
            jsonSuccess('OK', $records);
            break;

        default:
            jsonError('Invalid action: ' . htmlspecialchars($action));
    }

} catch (Exception $e) {
    jsonError('Server error: ' . $e->getMessage());
}

$conn->close();
?>
