<?php
// admin/admin_includes/handlers/save_exchange_rates.php
// AJAX handler — saves updated exchange rates to currency_rates table
// Also used by the exchange_rates.php form (POST action)

session_start();
require_once dirname(__DIR__) . '/admin_utilities.php';
include dirname(dirname(__DIR__)) . '/db_connect.php';

setupErrorHandling();
header('Content-Type: application/json');
requireAdminAuth($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    jsonError('Invalid request');
}

if ($_POST['action'] !== 'save_rates') jsonError('Invalid action');

$user_id = $_SESSION['user_id'];
$errors  = [];
$updated = 0;

foreach (['USD', 'EUR'] as $code) {
    $key  = 'rate_' . $code;
    if (!isset($_POST[$key])) { $errors[] = "Missing rate for $code"; continue; }

    $rate = floatval($_POST[$key]);
    if ($rate <= 0 || $rate >= 1) {
        $errors[] = "$code rate must be > 0 and < 1 (e.g. 0.2128)";
        continue;
    }

    $stmt = $conn->prepare(
        "UPDATE currency_rates SET rate=?, updated_by=? WHERE currency_code=?"
    );
    $stmt->bind_param("dss", $rate, $user_id, $code);
    if ($stmt->execute()) $updated++;
    $stmt->close();
}

if (!empty($errors)) {
    jsonError(implode(' | ', $errors));
}

logAdminAction('UPDATE_EXCHANGE_RATES', "Updated $updated rates");
jsonSuccess("$updated exchange rate(s) saved successfully");

$conn->close();
?>
