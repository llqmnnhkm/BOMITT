<?php
// admin/admin_includes/enduser_catalog_overview.php
// Read-only catalog summary grouped by user type → category
// Helps admin see at a glance what items exist per user type

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($conn)) include dirname(dirname(__DIR__)) . '/db_connect.php';
require_once dirname(__DIR__) . '/admin_utilities.php';
requireAdminAuth($conn);

$eu_user_types = [
    'general'   => ['label'=>'General User',    'emoji'=>'🖥️', 'color'=>'#1565c0', 'bg'=>'#e3f2fd'],
    'technical' => ['label'=>'Technical User',   'emoji'=>'⚙️', 'color'=>'#2e7d32', 'bg'=>'#e8f5e9'],
    'design'    => ['label'=>'Design / CAD',     'emoji'=>'🎨', 'color'=>'#880e4f', 'bg'=>'#fce4ec'],
    'field'     => ['label'=>'Field / Mobile',   'emoji'=>'🏗️', 'color'=>'#e65100', 'bg'=>'#fff3e0'],
    'executive' => ['label'=>'Executive / VIP',  'emoji'=>'💼', 'color'=>'#4527a0', 'bg'=>'#ede7f6'],
];
$eu_categories = [
    'workstation' => '🖥️ Workstation Equipment',
    'peripherals' => '🖱️ Peripherals & Accessories',
    'mobile'      => '📱 Mobile & Communications',
    'software'    => '💿 Software & Licenses',
];

// Fetch all active items grouped (safe — table may not exist yet)
$catalog = [];
$eu_overview_error = '';
$table_check = $conn->query("SHOW TABLES LIKE 'enduser_equipment'");
if ($table_check && $table_check->num_rows > 0) {
    $result = $conn->query("
        SELECT user_type, item_category, item_name, item_description,
               default_quantity, unit_price, is_active
        FROM enduser_equipment
        ORDER BY user_type, item_category, display_order, id
    ");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $catalog[$row['user_type']][$row['item_category']][] = $row;
        }
    }
} else {
    $eu_overview_error = 'enduser_equipment table not found. Import enduser_equipment.sql first.';
}

// Stats per user type
$stats = [];
foreach ($catalog as $ut => $cats) {
    $total_items  = 0;
    $total_value  = 0;
    $active_items = 0;
    foreach ($cats as $cat => $items) {
        foreach ($items as $item) {
            $total_items++;
            $total_value += $item['unit_price'] * $item['default_quantity'];
            if ($item['is_active']) $active_items++;
        }
    }
    $stats[$ut] = compact('total_items','total_value','active_items');
}
?>

<?php if (!empty($eu_overview_error)): ?>
<div style="padding:1.5rem; background:#f8d7da; border:1px solid #f5c6cb; border-radius:8px; color:#721c24; margin-bottom:1rem;">
    ⚠️ <?php echo $eu_overview_error; ?>
</div>
<?php endif; ?>

<div style="margin-bottom:1.5rem;">
    <h4 style="color:#333; margin-bottom:0.5rem;">📋 Catalog Overview</h4>
    <p style="color:#666; font-size:0.875rem;">
        Read-only summary of all equipment items grouped by user type.
        Use the <strong>Equipment Items</strong> tab to add or edit items.
    </p>
</div>

<!-- ── Stats Row ────────────────────────────────────────────────────────── -->
<div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:1rem; margin-bottom:2rem;">
    <?php foreach ($eu_user_types as $key => $info):
        $s = $stats[$key] ?? ['total_items'=>0,'total_value'=>0,'active_items'=>0];
    ?>
    <div style="background:<?php echo $info['bg']; ?>; border-left:4px solid <?php echo $info['color']; ?>; padding:1rem; border-radius:10px;">
        <div style="font-size:1.5rem;"><?php echo $info['emoji']; ?></div>
        <div style="font-weight:700; color:<?php echo $info['color']; ?>; margin:4px 0;"><?php echo $info['label']; ?></div>
        <div style="font-size:0.875rem; color:#555;">
            <?php echo $s['total_items']; ?> items &bull;
            <?php echo $s['active_items']; ?> active
        </div>
        <div style="font-size:1rem; font-weight:700; color:<?php echo $info['color']; ?>; margin-top:4px;">
            Est. $<?php echo number_format($s['total_value'], 2); ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ── Catalog Cards ─────────────────────────────────────────────────────── -->
<?php foreach ($eu_user_types as $ut_key => $ut_info): ?>
<div style="margin-bottom:2rem; border:1px solid #e0e0e0; border-radius:14px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <!-- User type header -->
    <div style="background:<?php echo $ut_info['bg']; ?>; padding:14px 20px; display:flex; align-items:center; gap:10px; border-bottom:2px solid <?php echo $ut_info['color']; ?>20;">
        <span style="font-size:1.6rem;"><?php echo $ut_info['emoji']; ?></span>
        <div>
            <div style="font-weight:700; font-size:1.1rem; color:<?php echo $ut_info['color']; ?>"><?php echo $ut_info['label']; ?></div>
            <?php $s = $stats[$ut_key] ?? ['total_items'=>0,'total_value'=>0,'active_items'=>0]; ?>
            <div style="font-size:0.8rem; color:#666;">
                <?php echo $s['total_items']; ?> total items &bull;
                <?php echo $s['active_items']; ?> active &bull;
                Est. $<?php echo number_format($s['total_value'],2); ?>
            </div>
        </div>
        <div style="margin-left:auto;">
            <button class="add-btn" style="font-size:0.8rem; padding:6px 12px;"
                onclick='euOpenItemModal("add"); document.getElementById("eu-item-user-type").value="<?php echo $ut_key; ?>"'>
                ➕ Add Item
            </button>
        </div>
    </div>

    <?php if (empty($catalog[$ut_key])): ?>
    <div style="padding:1.5rem; text-align:center; color:#999; font-style:italic;">
        No items configured for this user type yet.
    </div>
    <?php else: ?>
    <!-- Category sections -->
    <?php foreach ($eu_categories as $cat_key => $cat_label): ?>
        <?php if (empty($catalog[$ut_key][$cat_key])) continue; ?>
        <div style="padding:0 20px 16px;">
            <div style="font-weight:600; color:#555; font-size:0.9rem; padding:12px 0 8px; border-bottom:1px solid #f0f0f0; margin-bottom:8px;">
                <?php echo $cat_label; ?>
                <span style="font-weight:400; color:#999; margin-left:0.5rem;">
                    (<?php echo count($catalog[$ut_key][$cat_key]); ?> items)
                </span>
            </div>
            <table style="width:100%; border-collapse:collapse; font-size:0.875rem;">
                <thead style="background:#f8f9fa;">
                    <tr>
                        <th style="padding:8px; text-align:left; color:#555; font-weight:600;">Item</th>
                        <th style="padding:8px; text-align:left; color:#555; font-weight:600;">Description</th>
                        <th style="padding:8px; text-align:center; color:#555; font-weight:600;">Default Qty</th>
                        <th style="padding:8px; text-align:right; color:#555; font-weight:600;">Unit Price</th>
                        <th style="padding:8px; text-align:center; color:#555; font-weight:600;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($catalog[$ut_key][$cat_key] as $item): ?>
                    <tr style="border-bottom:1px solid #f5f5f5;">
                        <td style="padding:8px; font-weight:500; color:#2d3748;">
                            <?php echo htmlspecialchars($item['item_name']); ?>
                        </td>
                        <td style="padding:8px; color:#666; max-width:200px;">
                            <?php echo htmlspecialchars($item['item_description'] ?? ''); ?>
                        </td>
                        <td style="padding:8px; text-align:center; color:#555;">
                            <?php echo (int)$item['default_quantity']; ?>
                        </td>
                        <td style="padding:8px; text-align:right; font-weight:600; color:#0070ef;">
                            $<?php echo number_format($item['unit_price'],2); ?>
                        </td>
                        <td style="padding:8px; text-align:center;">
                            <?php if ($item['is_active']): ?>
                                <span style="color:#28a745; font-size:0.78rem; font-weight:600;">● Active</span>
                            <?php else: ?>
                                <span style="color:#dc3545; font-size:0.78rem; font-weight:600;">● Inactive</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php endforeach; ?>