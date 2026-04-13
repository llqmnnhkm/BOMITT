<?php
// admin/admin_includes/handlers/account_handler.php
// AJAX handler for Account Manager — create, update, toggle, delete, check

ob_start();
session_start();
require_once '../admin_utilities.php';
include '../../../db_connect.php';

setupErrorHandling();
header('Content-Type: application/json');
if (ob_get_length()) ob_clean();

requireAdminAuth($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    jsonError('Invalid request');
}

// ── Detect which extra columns exist (migration may not have run yet) ──────
function columnExists($conn, $table, $column) {
    $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $res && $res->num_rows > 0;
}

// Auto-add missing columns so the handler works even before migration
$missing_cols = [
    'full_name'            => "ALTER TABLE users ADD COLUMN full_name VARCHAR(255) DEFAULT NULL AFTER role",
    'email'                => "ALTER TABLE users ADD COLUMN email VARCHAR(255) DEFAULT NULL",
    'department'           => "ALTER TABLE users ADD COLUMN department VARCHAR(100) DEFAULT NULL",
    'phone'                => "ALTER TABLE users ADD COLUMN phone VARCHAR(30) DEFAULT NULL",
    'is_active'            => "ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1",
    'must_change_password' => "ALTER TABLE users ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0",
    'last_login'           => "ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL DEFAULT NULL",
    'created_by'           => "ALTER TABLE users ADD COLUMN created_by VARCHAR(255) DEFAULT NULL",
];
foreach ($missing_cols as $col => $sql) {
    if (!columnExists($conn, 'users', $col)) {
        $conn->query($sql);
    }
}

$admin_id = $_SESSION['user_id'];
$action   = $_POST['action'];

try {
    switch ($action) {

        // ── Check if user_id is available ────────────────────────────────
        case 'check_userid': {
            $uid = trim($_POST['user_id'] ?? '');
            if (empty($uid)) { echo json_encode(['available' => false]); exit; }
            $stmt = $conn->prepare("SELECT id FROM users WHERE user_id = ?");
            $stmt->bind_param("s", $uid);
            $stmt->execute();
            $taken = $stmt->get_result()->num_rows > 0;
            $stmt->close();
            echo json_encode(['available' => !$taken]);
            exit;
        }

        // ── Create new account ────────────────────────────────────────────
        case 'create_account': {
            $v = validateRequiredFields(['user_id', 'password', 'full_name']);
            if ($v !== true) jsonError('Missing: ' . implode(', ', $v));

            $user_id    = trim(sanitizeInput($_POST['user_id']));
            $password   = $_POST['password'];
            $full_name  = sanitizeInput($_POST['full_name']  ?? '');
            $email      = sanitizeInput($_POST['email']      ?? '') ?: null;
            $department = sanitizeInput($_POST['department'] ?? '') ?: null;
            $phone      = sanitizeInput($_POST['phone']      ?? '') ?: null;
            $role       = in_array($_POST['role'] ?? '', ['admin','guest']) ? $_POST['role'] : 'guest';
            $must_change= isset($_POST['must_change_password']) ? 1 : 0;

            if (strlen($user_id) < 2) jsonError('Login ID must be at least 2 characters');
            if (strlen($password) < 6) jsonError('Password must be at least 6 characters');

            // Check user_id not already taken
            $chk = $conn->prepare("SELECT id FROM users WHERE user_id = ?");
            $chk->bind_param("s", $user_id);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                jsonError("Login ID '$user_id' is already taken");
            }
            $chk->close();

            // Check email uniqueness if provided
            if ($email) {
                $chk2 = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $chk2->bind_param("s", $email);
                $chk2->execute();
                if ($chk2->get_result()->num_rows > 0) {
                    jsonError("Email '$email' is already registered");
                }
                $chk2->close();
            }

            $hashed = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $conn->prepare(
                "INSERT INTO users
                 (user_id, user_pw, role, full_name, email, department, phone,
                  is_active, must_change_password, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?)"
            );
            $stmt->bind_param("sssssssss", $user_id, $hashed, $role, $full_name,
                              $email, $department, $phone, $must_change, $admin_id);

            if ($stmt->execute()) {
                logAdminAction('ACC_CREATE', "Created account: $user_id ($full_name)");
                jsonSuccess("Account '$user_id' created successfully");
            } else {
                jsonError('Database error: ' . $stmt->error);
            }
            $stmt->close();
            break;
        }

        // ── Update account (edit) ─────────────────────────────────────────
        case 'update_account': {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) jsonError('Invalid account ID');

            $user_id    = trim(sanitizeInput($_POST['user_id']    ?? ''));
            $full_name  = sanitizeInput($_POST['full_name']  ?? '');
            $email      = sanitizeInput($_POST['email']      ?? '') ?: null;
            $department = sanitizeInput($_POST['department'] ?? '') ?: null;
            $phone      = sanitizeInput($_POST['phone']      ?? '') ?: null;
            $role       = in_array($_POST['role'] ?? '', ['admin','guest']) ? $_POST['role'] : 'guest';
            $must_change= isset($_POST['must_change_password']) ? 1 : 0;
            $new_pw     = $_POST['new_password'] ?? '';

            if (empty($user_id)) jsonError('Login ID cannot be empty');

            // Check user_id not taken by someone else
            $chk = $conn->prepare("SELECT id FROM users WHERE user_id = ? AND id != ?");
            $chk->bind_param("si", $user_id, $id);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                jsonError("Login ID '$user_id' is already taken by another account");
            }
            $chk->close();

            // Check email uniqueness
            if ($email) {
                $chk2 = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $chk2->bind_param("si", $email, $id);
                $chk2->execute();
                if ($chk2->get_result()->num_rows > 0) {
                    jsonError("Email '$email' is already registered to another account");
                }
                $chk2->close();
            }

            // Build update SQL
            if (!empty($new_pw)) {
                if (strlen($new_pw) < 6) jsonError('New password must be at least 6 characters');
                $confirm = $_POST['confirm_password'] ?? '';
                if ($new_pw !== $confirm) jsonError('Passwords do not match');
                $hashed = password_hash($new_pw, PASSWORD_BCRYPT);
                $stmt = $conn->prepare(
                    "UPDATE users SET user_id=?, user_pw=?, full_name=?, email=?,
                     department=?, phone=?, role=?, must_change_password=?
                     WHERE id=?"
                );
                $stmt->bind_param("ssssssssi", $user_id, $hashed, $full_name,
                                  $email, $department, $phone, $role, $must_change, $id);
            } else {
                $stmt = $conn->prepare(
                    "UPDATE users SET user_id=?, full_name=?, email=?,
                     department=?, phone=?, role=?, must_change_password=?
                     WHERE id=?"
                );
                $stmt->bind_param("sssssssi", $user_id, $full_name, $email,
                                  $department, $phone, $role, $must_change, $id);
            }

            if ($stmt->execute()) {
                logAdminAction('ACC_UPDATE', "Updated account ID:$id ($user_id)");
                jsonSuccess("Account updated successfully");
            } else {
                jsonError('Database error: ' . $stmt->error);
            }
            $stmt->close();
            break;
        }

        // ── Toggle active/inactive ────────────────────────────────────────
        case 'toggle_active': {
            $id        = (int)($_POST['id']        ?? 0);
            $is_active = (int)($_POST['is_active'] ?? 0);
            if ($id <= 0) jsonError('Invalid ID');

            // Prevent deactivating own account
            $self = $conn->prepare("SELECT user_id FROM users WHERE id=?");
            $self->bind_param("i", $id);
            $self->execute();
            $row = $self->get_result()->fetch_assoc();
            $self->close();
            if ($row && $row['user_id'] === $admin_id && $is_active == 0) {
                jsonError('You cannot deactivate your own account');
            }

            $stmt = $conn->prepare("UPDATE users SET is_active=? WHERE id=?");
            $stmt->bind_param("ii", $is_active, $id);
            if ($stmt->execute()) {
                $label = $is_active ? 'activated' : 'deactivated';
                logAdminAction('ACC_TOGGLE', "Account ID:$id $label");
                jsonSuccess("Account $label successfully");
            } else { jsonError('Database error'); }
            $stmt->close();
            break;
        }

        // ── Delete account (and configurations) ───────────────────────────
        case 'delete_account': {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) jsonError('Invalid ID');

            // Prevent deleting own account
            $self = $conn->prepare("SELECT user_id, role FROM users WHERE id=?");
            $self->bind_param("i", $id);
            $self->execute();
            $row = $self->get_result()->fetch_assoc();
            $self->close();
            if (!$row) jsonError('Account not found');
            if ($row['user_id'] === $admin_id) jsonError('You cannot delete your own account');
            if ($row['role'] === 'admin') jsonError('Admin accounts cannot be deleted from here');

            $uid = $row['user_id'];

            // Delete user configurations first
            $del_cfg = $conn->prepare("DELETE FROM user_configurations WHERE user_id=?");
            $del_cfg->bind_param("s", $uid);
            $del_cfg->execute();
            $del_cfg->close();

            // Delete user
            $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                logAdminAction('ACC_DELETE', "Deleted account ID:$id ($uid)");
                jsonSuccess("Account '$uid' and all configurations deleted");
            } else { jsonError('Database error: ' . $stmt->error); }
            $stmt->close();
            break;
        }

        default: jsonError('Unknown action');
    }

} catch (Exception $e) {
    jsonError('Server error: ' . $e->getMessage());
}

$conn->close();
?>