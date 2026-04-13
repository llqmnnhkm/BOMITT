<?php
// admin/admin_includes/handlers/conference_handler.php
// AJAX handler for conference equipment CRUD operations

session_start();
require_once '../admin_utilities.php';
include '../../../db_connect.php';

setupErrorHandling();
header('Content-Type: application/json');

requireAdminAuth($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    jsonError('Invalid request');
}

$action = $_POST['action'];

$valid_room_sizes  = ['small', 'medium', 'large'];
$valid_categories  = ['av', 'connectivity', 'furniture', 'other'];

try {
    switch ($action) {

        case 'add_conference_item':
            $validation = validateRequiredFields(['room_size', 'category', 'name', 'quantity', 'price']);
            if ($validation !== true) {
                jsonError('Missing required fields: ' . implode(', ', $validation));
            }
            if (!in_array($_POST['room_size'], $valid_room_sizes)) {
                jsonError('Invalid room size');
            }
            if (!in_array($_POST['category'], $valid_categories)) {
                jsonError('Invalid category');
            }
            if (!isValidNumber($_POST['quantity'], 0)) {
                jsonError('Quantity must be a positive number');
            }
            if (!isValidNumber($_POST['price'], 0)) {
                jsonError('Price must be a positive number');
            }

            $result = insertRecord($conn, 'conference_equipment', [
                'room_size'          => sanitizeInput($_POST['room_size']),
                'equipment_category' => sanitizeInput($_POST['category']),
                'item_name'          => sanitizeInput($_POST['name']),
                'item_description'   => sanitizeInput($_POST['description'] ?? ''),
                'default_quantity'   => (int)$_POST['quantity'],
                'unit_price'         => (float)$_POST['price'],
            ]);

            if ($result['success']) {
                logAdminAction('ADD_CONF_ITEM', "Added: {$_POST['name']}");
                jsonSuccess($result['message'], ['id' => $result['id']]);
            } else {
                jsonError($result['message']);
            }
            break;

        case 'update_conference_item':
            $validation = validateRequiredFields(['id', 'name', 'quantity', 'price']);
            if ($validation !== true) {
                jsonError('Missing required fields: ' . implode(', ', $validation));
            }
            if (!isValidNumber($_POST['quantity'], 0)) {
                jsonError('Quantity must be a positive number');
            }
            if (!isValidNumber($_POST['price'], 0)) {
                jsonError('Price must be a positive number');
            }

            $result = updateRecord($conn, 'conference_equipment', [
                'room_size'          => sanitizeInput($_POST['room_size'] ?? 'small'),
                'equipment_category' => sanitizeInput($_POST['category'] ?? 'av'),
                'item_name'          => sanitizeInput($_POST['name']),
                'item_description'   => sanitizeInput($_POST['description'] ?? ''),
                'default_quantity'   => (int)$_POST['quantity'],
                'unit_price'         => (float)$_POST['price'],
            ], (int)$_POST['id']);

            if ($result['success']) {
                logAdminAction('UPDATE_CONF_ITEM', "Updated ID: {$_POST['id']} - {$_POST['name']}");
                jsonSuccess($result['message']);
            } else {
                jsonError($result['message']);
            }
            break;

        case 'delete_conference_item':
            if (!isset($_POST['id']) || !isValidNumber($_POST['id'], 1)) {
                jsonError('Invalid item ID');
            }
            $result = deleteRecord($conn, 'conference_equipment', (int)$_POST['id']);
            if ($result['success']) {
                logAdminAction('DELETE_CONF_ITEM', "Deleted ID: {$_POST['id']}");
                jsonSuccess($result['message']);
            } else {
                jsonError($result['message']);
            }
            break;

        default:
            jsonError('Invalid action');
    }
} catch (Exception $e) {
    jsonError('Error: ' . $e->getMessage());
}

$conn->close();
?>
