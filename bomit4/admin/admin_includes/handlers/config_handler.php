<?php
// admin/admin_includes/handlers/config_handler.php
// AJAX-only handler for infrastructure config CRUD operations

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
        case 'add_config':
            // Validate required fields
            $validation = validateRequiredFields(['type', 'name', 'value', 'price', 'installation_type']);
            if ($validation !== true) {
                jsonError('Missing required fields: ' . implode(', ', $validation));
            }
            
            // Validate price
            if (!isValidNumber($_POST['price'], 0)) {
                jsonError('Price must be a positive number');
            }
            
            // Validate installation value if not 'none'
            if ($_POST['installation_type'] !== 'none') {
                if (!isValidNumber($_POST['installation_value'], 0)) {
                    jsonError('Installation value must be a positive number');
                }
            }
            
            // Insert record
            $result = insertRecord($conn, 'network_infrastructure_config', [
                'item_type' => sanitizeInput($_POST['type']),
                'item_name' => sanitizeInput($_POST['name']),
                'item_value' => sanitizeInput($_POST['value']),
                'price' => (float)$_POST['price'],
                'parent_item' => !empty($_POST['parent']) ? sanitizeInput($_POST['parent']) : null,
                'installation_type' => sanitizeInput($_POST['installation_type']),
                'installation_value' => (float)($_POST['installation_value'] ?? 0)
            ]);
            
            if ($result['success']) {
                logAdminAction('ADD_INFRA_CONFIG', "Added: {$_POST['name']}");
                jsonSuccess($result['message'], ['id' => $result['id']]);
            } else {
                jsonError($result['message']);
            }
            break;
            
        case 'update_config':
            // Validate required fields
            $validation = validateRequiredFields(['id', 'name', 'value', 'price', 'installation_type']);
            if ($validation !== true) {
                jsonError('Missing required fields: ' . implode(', ', $validation));
            }
            
            // Validate price
            if (!isValidNumber($_POST['price'], 0)) {
                jsonError('Price must be a positive number');
            }
            
            // Validate installation value if not 'none'
            if ($_POST['installation_type'] !== 'none') {
                if (!isValidNumber($_POST['installation_value'], 0)) {
                    jsonError('Installation value must be a positive number');
                }
            }
            
            // Update record
            $result = updateRecord($conn, 'network_infrastructure_config', [
                'item_name' => sanitizeInput($_POST['name']),
                'item_value' => sanitizeInput($_POST['value']),
                'price' => (float)$_POST['price'],
                'installation_type' => sanitizeInput($_POST['installation_type']),
                'installation_value' => (float)($_POST['installation_value'] ?? 0)
            ], (int)$_POST['id']);
            
            if ($result['success']) {
                logAdminAction('UPDATE_INFRA_CONFIG', "Updated ID: {$_POST['id']} - {$_POST['name']}");
                jsonSuccess($result['message']);
            } else {
                jsonError($result['message']);
            }
            break;

        case 'delete_config':
            // Validate ID
            if (!isset($_POST['id']) || !isValidNumber($_POST['id'], 1)) {
                jsonError('Invalid config ID');
            }
            
            // Delete record using utility
            $result = deleteRecord($conn, 'network_infrastructure_config', (int)$_POST['id']);
            
            if ($result['success']) {
                logAdminAction('DELETE_INFRA_CONFIG', "Deleted ID: {$_POST['id']}");
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