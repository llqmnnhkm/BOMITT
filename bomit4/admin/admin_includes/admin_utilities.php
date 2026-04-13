<?php
// admin/admin_includes/admin_utilities.php
// Shared utilities and common functions for admin management

/**
 * =============================================================================
 * AUTHENTICATION & SESSION UTILITIES
 * =============================================================================
 */

/**
 * Check if user is authenticated as admin
 * Redirects to index if not authorized
 */
function requireAdminAuth($conn = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['isLoggedIn']) || 
        $_SESSION['isLoggedIn'] !== true || 
        $_SESSION['role'] !== 'admin') {
        
        // If AJAX request, return JSON error
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }
        
        // Otherwise redirect
        header("Location: ../index.php");
        exit();
    }
}

/**
 * =============================================================================
 * DATABASE UTILITIES
 * =============================================================================
 */

/**
 * Execute a prepared statement with error handling
 * 
 * @param mysqli $conn Database connection
 * @param string $sql SQL query with placeholders
 * @param string $types Parameter types (e.g., "ssi" for string, string, int)
 * @param array $params Parameters to bind
 * @return mysqli_stmt|false Statement object or false on error
 */
function executeQuery($conn, $sql, $types = "", $params = []) {
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        return false;
    }
    
    if (!empty($types) && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    return $stmt;
}

/**
 * Get all records from a table with optional ordering
 * 
 * @param mysqli $conn Database connection
 * @param string $table Table name
 * @param string $orderBy ORDER BY clause (e.g., "display_order, id")
 * @return array Array of records
 */
function getAllRecords($conn, $table, $orderBy = "display_order") {
    $sql = "SELECT * FROM " . $conn->real_escape_string($table) . " ORDER BY " . $orderBy;
    $result = $conn->query($sql);
    
    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    
    return $records;
}

/**
 * =============================================================================
 * JSON RESPONSE UTILITIES
 * =============================================================================
 */

/**
 * Send JSON success response
 * 
 * @param string $message Success message
 * @param mixed $data Optional additional data
 */
function jsonSuccess($message, $data = null) {
    header('Content-Type: application/json');
    $response = ['success' => true, 'message' => $message];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit();
}

/**
 * Send JSON error response
 * 
 * @param string $message Error message
 * @param int $httpCode HTTP status code (default 400)
 */
function jsonError($message, $httpCode = 400) {
    header('Content-Type: application/json');
    http_response_code($httpCode);
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

/**
 * =============================================================================
 * VALIDATION UTILITIES
 * =============================================================================
 */

/**
 * Validate required POST fields
 * 
 * @param array $requiredFields Array of field names
 * @return array|true Returns array of missing fields or true if all present
 */
function validateRequiredFields($requiredFields) {
    $missing = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            $missing[] = $field;
        }
    }
    
    return empty($missing) ? true : $missing;
}

/**
 * Sanitize string input
 * 
 * @param string $input Input string
 * @return string Sanitized string
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate numeric input
 * 
 * @param mixed $value Value to check
 * @param float $min Minimum value (optional)
 * @param float $max Maximum value (optional)
 * @return bool
 */
function isValidNumber($value, $min = null, $max = null) {
    if (!is_numeric($value)) {
        return false;
    }
    
    $num = floatval($value);
    
    if ($min !== null && $num < $min) {
        return false;
    }
    
    if ($max !== null && $num > $max) {
        return false;
    }
    
    return true;
}

/**
 * =============================================================================
 * CRUD OPERATION UTILITIES
 * =============================================================================
 */

/**
 * Generic INSERT operation
 * 
 * @param mysqli $conn Database connection
 * @param string $table Table name
 * @param array $data Associative array of column => value
 * @return array ['success' => bool, 'id' => insert_id, 'message' => string]
 */
function insertRecord($conn, $table, $data) {
    $columns = array_keys($data);
    $values = array_values($data);
    
    $columnsList = implode(', ', $columns);
    $placeholders = str_repeat('?,', count($values) - 1) . '?';
    
    $sql = "INSERT INTO $table ($columnsList) VALUES ($placeholders)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ['success' => false, 'message' => 'Database error: ' . $conn->error];
    }
    
    // Build type string (s for string, i for int, d for double)
    $types = '';
    foreach ($values as $value) {
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }
    
    $stmt->bind_param($types, ...$values);
    
    if ($stmt->execute()) {
        return [
            'success' => true, 
            'id' => $conn->insert_id,
            'message' => 'Record added successfully'
        ];
    } else {
        return ['success' => false, 'message' => 'Insert failed: ' . $stmt->error];
    }
}

/**
 * Generic UPDATE operation
 * 
 * @param mysqli $conn Database connection
 * @param string $table Table name
 * @param array $data Associative array of column => value to update
 * @param int $id Record ID
 * @return array ['success' => bool, 'message' => string]
 */
function updateRecord($conn, $table, $data, $id) {
    $setParts = [];
    $values = [];
    
    foreach ($data as $column => $value) {
        $setParts[] = "$column = ?";
        $values[] = $value;
    }
    
    $values[] = $id; // Add ID for WHERE clause
    
    $setClause = implode(', ', $setParts);
    $sql = "UPDATE $table SET $setClause WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ['success' => false, 'message' => 'Database error: ' . $conn->error];
    }
    
    // Build type string
    $types = '';
    foreach ($values as $value) {
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }
    
    $stmt->bind_param($types, ...$values);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Record updated successfully'];
    } else {
        return ['success' => false, 'message' => 'Update failed: ' . $stmt->error];
    }
}

/**
 * Generic DELETE operation
 * 
 * @param mysqli $conn Database connection
 * @param string $table Table name
 * @param int $id Record ID
 * @return array ['success' => bool, 'message' => string]
 */
function deleteRecord($conn, $table, $id) {
    $sql = "DELETE FROM $table WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        return ['success' => false, 'message' => 'Database error: ' . $conn->error];
    }
    
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Record deleted successfully'];
    } else {
        return ['success' => false, 'message' => 'Delete failed: ' . $stmt->error];
    }
}

/**
 * =============================================================================
 * DISPLAY UTILITIES
 * =============================================================================
 */

/**
 * Format price for display
 * 
 * @param float $price Price value
 * @param string $currency Currency symbol
 * @return string Formatted price string
 */
function formatPrice($price, $currency = '$') {
    return $currency . number_format($price, 2);
}

/**
 * Get site type label from key
 * 
 * @param string $key Site type key
 * @return string Human-readable label
 */
function getSiteTypeLabel($key) {
    $siteTypes = [
        'less_50_no_server' => 'Less than 50 Users - No Server',
        'less_50_with_server' => 'Less than 50 Users - With Server',
        '51_150_no_server' => '51-150 Users - No Server',
        '51_150_with_server' => '51-150 Users - With Server',
        '151_300_no_server' => '151-300 Users - No Server',
        '151_300_with_server' => '151-300 Users - With Server',
        '301_400_no_server' => '301-400 Users - No Server',
        '301_400_with_server' => '301-400 Users - With Server',
        'more_400_no_server' => 'More than 400 Users - No Server',
        'more_400_with_server' => 'More than 400 Users - With Server'
    ];
    
    return $siteTypes[$key] ?? $key;
}

/**
 * Get all site types
 * 
 * @return array Associative array of key => label
 */
function getAllSiteTypes() {
    return [
        'less_50_no_server' => 'Less than 50 Users - No Server',
        'less_50_with_server' => 'Less than 50 Users - With Server',
        '51_150_no_server' => '51-150 Users - No Server',
        '51_150_with_server' => '51-150 Users - With Server',
        '151_300_no_server' => '151-300 Users - No Server',
        '151_300_with_server' => '151-300 Users - With Server',
        '301_400_no_server' => '301-400 Users - No Server',
        '301_400_with_server' => '301-400 Users - With Server',
        'more_400_no_server' => 'More than 400 Users - No Server',
        'more_400_with_server' => 'More than 400 Users - With Server'
    ];
}

/**
 * =============================================================================
 * LOGGING UTILITIES (Optional - for debugging)
 * =============================================================================
 */

/**
 * Log admin action to file (optional - for audit trail)
 * 
 * @param string $action Action performed
 * @param string $details Additional details
 */
function logAdminAction($action, $details = '') {
    if (!isset($_SESSION['user_id'])) {
        return;
    }
    
    $logFile = __DIR__ . '/../../logs/admin_actions.log';
    $logDir = dirname($logFile);
    
    // Create logs directory if it doesn't exist
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $userId = $_SESSION['user_id'];
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    $logEntry = "[$timestamp] User: $userId | IP: $ip | Action: $action | Details: $details\n";
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

/**
 * =============================================================================
 * ERROR HANDLING
 * =============================================================================
 */

/**
 * Setup error handling for clean JSON responses
 */
function setupErrorHandling() {
    error_reporting(E_ERROR | E_PARSE);
    ini_set('display_errors', 0);
}

/**
 * =============================================================================
 * USAGE EXAMPLES IN COMMENTS
 * =============================================================================
 * 
 * // In your management files, include this utilities file:
 * require_once 'admin_utilities.php';
 * 
 * // Example 1: Authentication
 * requireAdminAuth($conn);
 * 
 * // Example 2: Validate required fields
 * $validation = validateRequiredFields(['name', 'price', 'quantity']);
 * if ($validation !== true) {
 *     jsonError('Missing fields: ' . implode(', ', $validation));
 * }
 * 
 * // Example 3: Insert record
 * $result = insertRecord($conn, 'network_equipment', [
 *     'item_name' => $_POST['name'],
 *     'unit_price' => $_POST['price'],
 *     'default_quantity' => $_POST['quantity']
 * ]);
 * 
 * if ($result['success']) {
 *     jsonSuccess($result['message'], ['id' => $result['id']]);
 * } else {
 *     jsonError($result['message']);
 * }
 * 
 * // Example 4: Update record
 * $result = updateRecord($conn, 'network_equipment', [
 *     'item_name' => $_POST['name'],
 *     'unit_price' => $_POST['price']
 * ], $_POST['id']);
 * 
 * // Example 5: Delete record
 * $result = deleteRecord($conn, 'network_equipment', $_POST['id']);
 * 
 * // Example 6: Get site type label
 * $label = getSiteTypeLabel('less_50_no_server');
 * // Returns: "Less than 50 Users - No Server"
 * 
 * // Example 7: Format price
 * echo formatPrice(1234.56); // Outputs: $1,234.56
 * 
 */
?>