<?php
// guest/get_exchange_rates.php
// Returns currency exchange rates as JSON for the guest-side currency switcher
// Called once on page load, cached in JS

error_reporting(0);
ini_set('display_errors', 0);
session_start();

include '../db_connect.php';
header('Content-Type: application/json');
if (ob_get_length()) ob_clean();

if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true) {
    // Return safe defaults so the page doesn't break if not logged in
    echo json_encode([
        'base'  => 'MYR',
        'rates' => ['USD' => 0.2128, 'EUR' => 0.1963],
        'symbols' => ['MYR' => 'RM', 'USD' => '$', 'EUR' => '€'],
        'labels'  => ['MYR' => 'Malaysian Ringgit', 'USD' => 'US Dollar', 'EUR' => 'Euro'],
    ]);
    exit();
}

try {
    $rates   = ['MYR' => 1.0];
    $symbols = ['MYR' => 'RM', 'USD' => '$', 'EUR' => '€'];
    $labels  = ['MYR' => 'Malaysian Ringgit', 'USD' => 'US Dollar', 'EUR' => 'Euro'];

    $res = $conn->query("SELECT currency_code, rate, symbol, label FROM currency_rates");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $rates[$row['currency_code']]   = (float)$row['rate'];
            $symbols[$row['currency_code']] = $row['symbol'];
            $labels[$row['currency_code']]  = $row['label'];
        }
    }

    echo json_encode([
        'base'    => 'MYR',
        'rates'   => $rates,
        'symbols' => $symbols,
        'labels'  => $labels,
    ]);
} catch (Exception $e) {
    echo json_encode([
        'base'    => 'MYR',
        'rates'   => ['MYR' => 1.0, 'USD' => 0.2128, 'EUR' => 0.1963],
        'symbols' => ['MYR' => 'RM', 'USD' => '$', 'EUR' => '€'],
        'labels'  => ['MYR' => 'Malaysian Ringgit', 'USD' => 'US Dollar', 'EUR' => 'Euro'],
    ]);
}
$conn->close();
?>
