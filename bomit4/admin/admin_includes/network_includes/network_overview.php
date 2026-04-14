<?php
// admin/admin_includes/network_overview.php
// Read-only overview of all network equipment grouped by site type
// Mirrors conference_overview.php and enduser_catalog_overview.php

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($conn)) include dirname(dirname(__DIR__)) . '/db_connect.php';
require_once dirname(__DIR__) . '/admin_utilities.php';
requireAdminAuth($conn);

$site_types = getAllSiteTypes(); // from admin_utilities.php

$net_categories = [
    'equipment' => 'Equipment',
    'modules'   => 'Modules',
];

// Fetch all active equipment
$catalog = [];
$config_total = 0;
$cables_total = 0;

$res = $conn->query("SELECT * FROM network_equipment WHERE is_active = 1 ORDER BY site_type, equipment_category, display_order");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $catalog[$row['site_type']][$row['equipment_category']][] = $row;
    }
}

// Stats per site type
$stats = [];
foreach ($catalog as $st => $cats) {
    $total_items = $total_value = $active = 0;
    foreach ($cats as $items) {
        foreach ($items as $i) {
            $total_items++;
            $total_value += $i['unit_price'] * $i['default_quantity'];
            $active++;
        }
    }
    $stats[$st] = compact('total_items', 'total_value', 'active');
}

// Infrastructure config count
$r = $conn->query("SELECT COUNT(*) as c FROM network_infrastructure_config WHERE is_active = 1");
if ($r) $config_total = (int)$r->fetch_assoc()['c'];

// Cables count
$r = $conn->query("SELECT COUNT(*) as c FROM network_cables_accessories WHERE is_active = 1");
if ($r) $cables_total = (int)$r->fetch_assoc()['c'];

// Grand totals
$total_equipment_items = array_sum(array_column($stats, 'total_items'));
$total_equipment_value = array_sum(array_column($stats, 'total_value'));
?>

<div style="margin-bottom:1.5rem;">
    <h4 style="color:#333; margin-bottom:.5rem;">Network Infrastructure Overview</h4>
    <p style="color:#666; font-size:.875rem;">Read-only summary of all network items. Use the other tabs to add or edit.</p>
</div>

<!-- ── Top Summary Cards ────────────────────────────────────────────────── -->
<div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:1rem; margin-bottom:2rem;">
    <div style="background:#e3f2fd; border-left:4px solid #1565c0; padding:1rem; border-radius:10px;">
        <div style="font-size:1.5rem;"></div>
        <div style="font-weight:700; color:#1565c0; margin:4px 0;">Equipment Items</div>
        <div style="font-size:1.8rem; font-weight:800; color:#1565c0;"><?php echo $total_equipment_items; ?></div>
        <div style="font-size:.8rem; color:#555;">across <?php echo count($site_types); ?> site types</div>
    </div>
    <div style="background:#e8f5e9; border-left:4px solid #2e7d32; padding:1rem; border-radius:10px;">
        <div style="font-size:1.5rem;"></div>
        <div style="font-weight:700; color:#2e7d32; margin:4px 0;">Infrastructure Configs</div>
        <div style="font-size:1.8rem; font-weight:800; color:#2e7d32;"><?php echo $config_total; ?></div>
        <div style="font-size:.8rem; color:#555;">internet, WAN, VSAT options</div>
    </div>
    <div style="background:#fff3e0; border-left:4px solid #e65100; padding:1rem; border-radius:10px;">
        <div style="font-size:1.5rem;"></div>
        <div style="font-weight:700; color:#e65100; margin:4px 0;">Cables & Accessories</div>
        <div style="font-size:1.8rem; font-weight:800; color:#e65100;"><?php echo $cables_total; ?></div>
        <div style="font-size:.8rem; color:#555;">cables, patch panels, accessories</div>
    </div>
    <div style="background:linear-gradient(135deg,#e3f2fd,#e8f5e9); border-left:4px solid #0070ef; padding:1rem; border-radius:10px;">
        <div style="font-size:1.5rem;"></div>
        <div style="font-weight:700; color:#0070ef; margin:4px 0;">Est. Equipment Value</div>
        <div style="font-size:1.4rem; font-weight:800; color:#0070ef;">$<?php echo number_format($total_equipment_value, 2); ?></div>
        <div style="font-size:.8rem; color:#555;">default qty × unit price</div>
    </div>
</div>

<!-- ── Per-site-type cards ───────────────────────────────────────────────── -->
<?php
// Group site types into server/no-server pairs for cleaner display
$site_groups = [
    'Less than 50 Users' => ['less_50_no_server', 'less_50_with_server'],
    '51–150 Users'       => ['51_150_no_server',  '51_150_with_server'],
    '151–300 Users'      => ['151_300_no_server', '151_300_with_server'],
    '301–400 Users'      => ['301_400_no_server', '301_400_with_server'],
    'More than 400 Users'=> ['more_400_no_server','more_400_with_server'],
];

$group_colors = [
    'Less than 50 Users'  => ['color'=>'#2e7d32', 'bg'=>'#e8f5e9', 'emoji'=>''],
    '51–150 Users'        => ['color'=>'#1565c0', 'bg'=>'#e3f2fd', 'emoji'=>''],
    '151–300 Users'       => ['color'=>'#6a1b9a', 'bg'=>'#f3e5f5', 'emoji'=>''],
    '301–400 Users'       => ['color'=>'#e65100', 'bg'=>'#fff3e0', 'emoji'=>''],
    'More than 400 Users' => ['color'=>'#880e4f', 'bg'=>'#fce4ec', 'emoji'=>''],
];

foreach ($site_groups as $group_label => $group_keys):
    $gc = $group_colors[$group_label];
    // Total items across both variants in this group
    $group_items = 0;
    foreach ($group_keys as $key) {
        $group_items += $stats[$key]['total_items'] ?? 0;
    }
?>
<div style="margin-bottom:2rem; border:1px solid #e0e0e0; border-radius:14px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.06);">
    <!-- Group header -->
    <div style="background:<?php echo $gc['bg']; ?>; padding:14px 20px; display:flex; align-items:center; gap:10px; border-bottom:2px solid <?php echo $gc['color']; ?>20;">
        <span style="font-size:1.6rem;"><?php echo $gc['emoji']; ?></span>
        <div>
            <div style="font-weight:700; font-size:1.1rem; color:<?php echo $gc['color']; ?>"><?php echo $group_label; ?></div>
            <div style="font-size:.8rem; color:#666;"><?php echo $group_items; ?> equipment items across both variants</div>
        </div>
    </div>

    <!-- Two variants side by side -->
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:0;">
    <?php foreach ($group_keys as $st_key):
        $st_label = $site_types[$st_key] ?? $st_key;
        $s = $stats[$st_key] ?? ['total_items'=>0,'total_value'=>0];
        $is_with_server = str_contains($st_key, 'with_server');
    ?>
    <div style="padding:16px 20px; <?php echo $is_with_server ? 'border-left:1px solid #e0e0e0;' : ''; ?>">
        <div style="font-weight:600; font-size:.9rem; color:#2d3748; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
            <?php echo $is_with_server ? '🖥️ With Server' : '☁️ No Server'; ?>
            <span style="font-size:.75rem; color:#888; font-weight:400; margin-left:auto;">
                <?php echo $s['total_items']; ?> items · Est. $<?php echo number_format($s['total_value'], 0); ?>
            </span>
        </div>

        <?php if (empty($catalog[$st_key])): ?>
        <div style="color:#ccc; font-size:.85rem; font-style:italic; padding:8px 0;">No items configured</div>
        <?php else: ?>
        <?php foreach ($net_categories as $cat_key => $cat_label):
            if (empty($catalog[$st_key][$cat_key])) continue; ?>
        <div style="margin-bottom:10px;">
            <div style="font-size:.78rem; font-weight:600; color:#666; text-transform:uppercase; letter-spacing:.5px; margin-bottom:4px;">
                <?php echo $cat_label; ?> (<?php echo count($catalog[$st_key][$cat_key]); ?>)
            </div>
            <table style="width:100%; border-collapse:collapse; font-size:.82rem;">
                <?php foreach ($catalog[$st_key][$cat_key] as $item): ?>
                <tr style="border-bottom:1px solid #f5f5f5;">
                    <td style="padding:4px 6px; font-weight:500; color:#2d3748;">
                        <?php echo htmlspecialchars($item['item_name']); ?>
                    </td>
                    <td style="padding:4px 6px; text-align:center; color:#666; width:40px;">
                        ×<?php echo (int)$item['default_quantity']; ?>
                    </td>
                    <td style="padding:4px 6px; text-align:right; color:#0070ef; font-weight:600; width:80px;">
                        $<?php echo number_format($item['unit_price'], 2); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>