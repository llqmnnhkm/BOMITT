<?php
// admin/admin_includes/conference_overview.php
// Read-only summary grouped by room_type → equipment_category

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($conn)) include dirname(dirname(__DIR__)) . '/db_connect.php';
require_once dirname(__DIR__) . '/admin_utilities.php';
requireAdminAuth($conn);

$conf_room_types = [
    'small'  => ['label'=>'Small Room (4–6 people)',    'emoji'=>'', 'color'=>'#2e7d32', 'bg'=>'#e8f5e9'],
    'medium' => ['label'=>'Medium Room (8–12 people)', 'emoji'=>'', 'color'=>'#1565c0', 'bg'=>'#e3f2fd'],
    'large'  => ['label'=>'Large Room (15+ people)',   'emoji'=>'', 'color'=>'#4527a0', 'bg'=>'#ede7f6'],
];
$conf_categories = [
    'av'           => 'AV Equipment',
    'connectivity' => 'Connectivity',
    'furniture'    => 'Furniture',
    'other'        => 'Other',
];

$catalog = [];
$res = $conn->query("SELECT * FROM conference_equipment ORDER BY room_size, equipment_category, display_order");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $catalog[$row['room_size']][$row['equipment_category']][] = $row;
    }
}

// Stats
$stats = [];
foreach ($catalog as $rt => $cats) {
    $total_items = $total_value = $active = 0;
    foreach ($cats as $items) {
        foreach ($items as $i) {
            $total_items++;
            $total_value += $i['unit_price'] * $i['default_quantity'];
            if ($i['is_active']) $active++;
        }
    }
    $stats[$rt] = compact('total_items','total_value','active');
}
?>

<div style="margin-bottom:1.5rem;">
    <h4 style="color:#333; margin-bottom:.5rem;">
        Room Equipment Overview
        <span id="conf-overview-total-badge" style="font-size:.85rem; font-weight:400; color:#888; margin-left:.5rem;">
            (<?php echo array_sum(array_map(function($c){ return $c['total_items']; }, $stats)); ?> total items)
        </span>
    </h4>
    <p style="color:#666; font-size:.875rem;">Read-only summary. Use <strong>Equipment Items</strong> tab to add or edit.</p>
</div>

<!-- Stats row -->
<div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:1rem; margin-bottom:2rem;">
    <?php foreach ($conf_room_types as $key => $info):
        $s = $stats[$key] ?? ['total_items'=>0,'total_value'=>0,'active'=>0];
    ?>
    <div style="background:<?php echo $info['bg']; ?>; border-left:4px solid <?php echo $info['color']; ?>; padding:1rem; border-radius:10px;">
        <div style="font-size:1.5rem;"><?php echo $info['emoji']; ?></div>
        <div style="font-weight:700; color:<?php echo $info['color']; ?>; margin:4px 0; font-size:.95rem;"><?php echo $info['label']; ?></div>
        <div style="font-size:.875rem; color:#555;" id="conf-overview-count-<?php echo $key; ?>"><?php echo $s['total_items']; ?> items &bull; <?php echo $s['active']; ?> active</div>
        <div style="font-size:1rem; font-weight:700; color:<?php echo $info['color']; ?>; margin-top:4px;">
            Est. $<?php echo number_format($s['total_value'], 2); ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Per-room cards -->
<?php foreach ($conf_room_types as $rt_key => $rt_info): ?>
<div style="margin-bottom:2rem; border:1px solid #e0e0e0; border-radius:14px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.06);">
    <div style="background:<?php echo $rt_info['bg']; ?>; padding:14px 20px; display:flex; align-items:center; gap:10px; border-bottom:2px solid <?php echo $rt_info['color']; ?>20;">
        <span style="font-size:1.6rem;"><?php echo $rt_info['emoji']; ?></span>
        <div>
            <div style="font-weight:700; font-size:1.1rem; color:<?php echo $rt_info['color']; ?>"><?php echo $rt_info['label']; ?></div>
            <?php $s = $stats[$rt_key] ?? ['total_items'=>0,'total_value'=>0,'active'=>0]; ?>
            <div style="font-size:.8rem; color:#666;">
                <?php echo $s['total_items']; ?> items &bull; <?php echo $s['active']; ?> active &bull;
                Est. $<?php echo number_format($s['total_value'],2); ?>
            </div>
        </div>
        <div style="margin-left:auto;">
            <button class="add-btn" style="font-size:.8rem; padding:6px 12px;"
                onclick='confSwitchToItemsAndAdd("<?php echo $rt_key; ?>")'>
                Add Item
            </button>
        </div>
    </div>

    <?php if (empty($catalog[$rt_key])): ?>
    <div style="padding:1.5rem; text-align:center; color:#999; font-style:italic;">No items configured for this room type yet.</div>
    <?php else: ?>
    <?php foreach ($conf_categories as $cat_key => $cat_label):
        if (empty($catalog[$rt_key][$cat_key])) continue; ?>
    <div style="padding:0 20px 16px;">
        <div style="font-weight:600; color:#555; font-size:.9rem; padding:12px 0 8px; border-bottom:1px solid #f0f0f0; margin-bottom:8px;">
            <?php echo $cat_label; ?>
            <span style="font-weight:400; color:#999; margin-left:.5rem;">(<?php echo count($catalog[$rt_key][$cat_key]); ?> items)</span>
        </div>
        <table style="width:100%; border-collapse:collapse; font-size:.875rem;">
            <thead style="background:#f8f9fa;">
                <tr>
                    <th style="padding:8px; text-align:left; color:#555; font-weight:600;">Item</th>
                    <th style="padding:8px; text-align:left; color:#555; font-weight:600;">Description</th>
                    <th style="padding:8px; text-align:center; color:#555; font-weight:600;">Qty</th>
                    <th style="padding:8px; text-align:right; color:#555; font-weight:600;">Unit Price</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($catalog[$rt_key][$cat_key] as $item): ?>
                <tr style="border-bottom:1px solid #f5f5f5;">
                    <td style="padding:8px; font-weight:500; color:#2d3748;"><?php echo htmlspecialchars($item['item_name']); ?></td>
                    <td style="padding:8px; color:#666; max-width:200px;"><?php echo htmlspecialchars($item['item_description'] ?? ''); ?></td>
                    <td style="padding:8px; text-align:center; color:#555;"><?php echo (int)$item['default_quantity']; ?></td>
                    <td style="padding:8px; text-align:right; font-weight:600; color:#0070ef;">$<?php echo number_format($item['unit_price'],2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<script>
// Switch to Equipment Items tab and open Add modal pre-selecting a room
function confSwitchToItemsAndAdd(roomSize) {
    // Switch tab
    if (typeof confSwitchTab === 'function') {
        // Find the Equipment Items tab button
        const tabBtns = document.querySelectorAll('#container-conference .tab-btn');
        if (tabBtns.length > 0) {
            confSwitchTab('items', { target: tabBtns[0] });
        }
    }
    // Small delay to let tab switch render
    setTimeout(function() {
        if (typeof confOpenModal === 'function') {
            confOpenModal('add');
            const sel = document.getElementById('conf-room-type');
            if (sel && roomSize) sel.value = roomSize;
        }
    }, 150);
}
</script>