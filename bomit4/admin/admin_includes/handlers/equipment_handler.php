<?php
// admin/admin_includes/handlers/equipment_handler.php
// AJAX-only handler for equipment CRUD operations

session_start();
require_once '../admin_utilities.php';
include '../../../db_connect.php';

// Setup clean error handling
setupErrorHandling();

// Set JSON header FIRST
header('Content-Type: application/json');

// Check admin authentication
requireAdminAuth($conn);

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    jsonError('Invalid request');
}

$action = $_POST['action'];

try {
    switch ($action) {
        case 'add_equipment':
            // Validate required fields
            $validation = validateRequiredFields(['site_type', 'category', 'name', 'quantity', 'price']);
            if ($validation !== true) {
                jsonError('Missing required fields: ' . implode(', ', $validation));
            }
            
            // Validate numeric fields
            if (!isValidNumber($_POST['quantity'], 0)) {
                jsonError('Quantity must be a positive number');
            }
            if (!isValidNumber($_POST['price'], 0)) {
                jsonError('Price must be a positive number');
            }
            
            // Insert record using utility
            $result = insertRecord($conn, 'network_equipment', [
                'site_type' => sanitizeInput($_POST['site_type']),
                'equipment_category' => sanitizeInput($_POST['category']),
                'item_name' => sanitizeInput($_POST['name']),
                'item_description' => sanitizeInput($_POST['description'] ?? ''),
                'default_quantity' => (int)$_POST['quantity'],
                'unit_price' => (float)$_POST['price']
            ]);
            
            if ($result['success']) {
                logAdminAction('ADD_EQUIPMENT', "Added: {$_POST['name']}");
                jsonSuccess($result['message'], ['id' => $result['id']]);
            } else {
                jsonError($result['message']);
            }
            break;
            
        case 'update_equipment':
            // Validate required fields
            $validation = validateRequiredFields(['id', 'name', 'quantity', 'price']);
            if ($validation !== true) {
                jsonError('Missing required fields: ' . implode(', ', $validation));
            }
            
            // Validate numeric fields
            if (!isValidNumber($_POST['quantity'], 0)) {
                jsonError('Quantity must be a positive number');
            }
            if (!isValidNumber($_POST['price'], 0)) {
                jsonError('Price must be a positive number');
            }
            
            // Update record using utility
            $result = updateRecord($conn, 'network_equipment', [
                'item_name' => sanitizeInput($_POST['name']),
                'item_description' => sanitizeInput($_POST['description'] ?? ''),
                'default_quantity' => (int)$_POST['quantity'],
                'unit_price' => (float)$_POST['price']
            ], (int)$_POST['id']);
            
            if ($result['success']) {
                logAdminAction('UPDATE_EQUIPMENT', "Updated ID: {$_POST['id']} - {$_POST['name']}");
                jsonSuccess($result['message']);
            } else {
                jsonError($result['message']);
            }
            break;

        case 'delete_equipment':
            // Validate ID
            if (!isset($_POST['id']) || !isValidNumber($_POST['id'], 1)) {
                jsonError('Invalid equipment ID');
            }
            
            // Delete record using utility
            $result = deleteRecord($conn, 'network_equipment', (int)$_POST['id']);
            
            if ($result['success']) {
                logAdminAction('DELETE_EQUIPMENT', "Deleted ID: {$_POST['id']}");
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