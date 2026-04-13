<?php
// admin/admin_includes/server_infra_defaults.php
// Displays and allows editing of the fixed compute sizing defaults
// (future_needs, hosts, FTT, cores_per_host, vRatio, memory_per_host)
// Stored in a simple settings table or as a single JSON row

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($conn)) include dirname(dirname(__DIR__)) . '/db_connect.php';
require_once dirname(__DIR__) . '/admin_utilities.php';
requireAdminAuth($conn);

// Ensure settings table exists
$conn->query("
    CREATE TABLE IF NOT EXISTS server_sizing_defaults (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        setting_key  VARCHAR(100) NOT NULL UNIQUE,
        setting_value VARCHAR(255) NOT NULL,
        description  TEXT,
        updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Seed defaults if empty
$cnt = $conn->query("SELECT COUNT(*) as c FROM server_sizing_defaults")->fetch_assoc()['c'];
if ($cnt == 0) {
    $conn->query("INSERT INTO server_sizing_defaults (setting_key, setting_value, description) VALUES
        ('future_needs',    '1.3', 'Growth factor applied to current core & memory totals'),
        ('hosts',           '3',   'Number of ESXi hosts in the vSAN cluster'),
        ('ftt',             '1',   'Failures To Tolerate (FTT) — host count used for redundancy'),
        ('cores_per_host',  '16',  'Physical cores per ESXi host'),
        ('vratio',          '4',   'vCPU to pCPU overcommit ratio'),
        ('memory_per_host', '128', 'RAM (GB) per ESXi host'),
        ('os_storage_default', '100', 'Default OS storage (GB) per VM'),
        ('dd_ratio',        '20',  'Data Domain deduplication ratio'),
        ('growth_rate',     '0.10','Annual data growth rate (10% = 0.10)'),
        ('change_rate',     '0.05','Daily change rate for incremental backups'),
        ('ret_days',        '7',   'Retention days policy for incrementals'),
        ('rweeks',          '5',   'Number of weekly full backups retained'),
        ('rmonths',         '6',   'Number of monthly full backups retained')
    ");
}

// Fetch all settings
$settings = [];
$res = $conn->query("SELECT * FROM server_sizing_defaults ORDER BY id");
if ($res) { while ($r = $res->fetch_assoc()) $settings[$r['setting_key']] = $r; }

// Handle inline save
$save_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_defaults'])) {
    foreach ($_POST['defaults'] as $key => $value) {
        $key   = $conn->real_escape_string($key);
        $value = $conn->real_escape_string(trim($value));
        $conn->query("UPDATE server_sizing_defaults SET setting_value='$value' WHERE setting_key='$key'");
    }
    $save_msg = 'success';
    // Reload
    $settings = [];
    $res = $conn->query("SELECT * FROM server_sizing_defaults ORDER BY id");
    if ($res) { while ($r = $res->fetch_assoc()) $settings[$r['setting_key']] = $r; }
}
?>

<div class="action-bar">
    <h4>Compute & Sizing Defaults</h4>
    <span style="font-size:.875rem;color:#666;">These values drive the server summary calculations.</span>
</div>

<?php if ($save_msg === 'success'): ?>
<div style="padding:12px 16px;background:#e8f5e9;border-left:4px solid #4caf50;border-radius:8px;color:#2e7d32;font-weight:600;margin-bottom:1rem;">
    ✅ Defaults saved successfully
</div>
<?php endif; ?>

<form method="POST" action="">
    <input type="hidden" name="save_defaults" value="1">
    <table class="equipment-table">
        <thead>
            <tr>
                <th>Setting</th>
                <th>Value</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($settings as $key => $row): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars(str_replace('_',' ', ucfirst($key))); ?></strong></td>
                <td>
                    <input type="number" name="defaults[<?php echo htmlspecialchars($key); ?>]"
                           value="<?php echo htmlspecialchars($row['setting_value']); ?>"
                           step="any" min="0"
                           style="width:100px;padding:6px 10px;border:1px solid #ccc;border-radius:6px;font-family:Montserrat;text-align:center;">
                </td>
                <td style="color:#666;font-size:.875rem;"><?php echo htmlspecialchars($row['description'] ?? ''); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div style="margin-top:1.5rem;">
        <button type="submit" class="btn-save" style="padding:.75rem 2.5rem;">
            💾 Save Defaults
        </button>
    </div>
</form>
