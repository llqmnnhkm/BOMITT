<?php
// admin/admin_includes/server_overview.php
// Read-only overview of server VM items

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($conn)) include dirname(dirname(__DIR__)) . '/db_connect.php';
require_once dirname(__DIR__) . '/admin_utilities.php';
requireAdminAuth($conn);

$srv_types = [
    'core_infra'  => ['label'=>'Core Infrastructure', 'emoji'=>'','color'=>'#1565c0','bg'=>'#e3f2fd'],
    'project_req' => ['label'=>'Project Requirement',  'emoji'=>'','color'=>'#e65100','bg'=>'#fff3e0'],
    'application' => ['label'=>'Application Servers',  'emoji'=>'','color'=>'#2e7d32','bg'=>'#e8f5e9'],
];

$catalog = [];
$res = $conn->query("SELECT * FROM server_equipment WHERE is_active=1 ORDER BY display_order,id");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $catalog[$r['item_type']][] = $r;
    }
}

// Totals
$totals = ['cores'=>0,'memory'=>0,'os'=>0,'data'=>0,'items'=>0];
foreach ($catalog as $items) {
    foreach ($items as $i) {
        $totals['cores']  += $i['default_cores'];
        $totals['memory'] += $i['default_memory'];
        $totals['os']     += $i['default_os_storage'];
        $totals['data']   += $i['default_data_storage'];
        $totals['items']++;
    }
}
?>

<div style="margin-bottom:1.5rem;">
    <h4 style="color:#333;margin-bottom:.5rem;">Server Equipment Overview</h4>
    <p style="color:#666;font-size:.875rem;">Read-only. Use <strong>VM Items</strong> tab to add or edit.</p>
</div>

<!-- Summary stat cards -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:2rem;">
    <div style="background:#e3f2fd;border-left:4px solid #1565c0;padding:1rem;border-radius:10px;">
        <div style="font-size:1.5rem;"></div>
        <div style="font-weight:700;color:#1565c0;margin:4px 0;">Total VM Items</div>
        <div style="font-size:1.8rem;font-weight:800;color:#1565c0;"><?php echo $totals['items']; ?></div>
    </div>
    <div style="background:#e8f5e9;border-left:4px solid #2e7d32;padding:1rem;border-radius:10px;">
        <div style="font-size:1.5rem;"></div>
        <div style="font-weight:700;color:#2e7d32;margin:4px 0;">Total Cores</div>
        <div style="font-size:1.8rem;font-weight:800;color:#2e7d32;"><?php echo $totals['cores']; ?></div>
    </div>
    <div style="background:#fff3e0;border-left:4px solid #e65100;padding:1rem;border-radius:10px;">
        <div style="font-size:1.5rem;"></div>
        <div style="font-weight:700;color:#e65100;margin:4px 0;">Total Memory (GB)</div>
        <div style="font-size:1.8rem;font-weight:800;color:#e65100;"><?php echo $totals['memory']; ?></div>
    </div>
    <div style="background:#ede7f6;border-left:4px solid #4527a0;padding:1rem;border-radius:10px;">
        <div style="font-size:1.5rem;"></div>
        <div style="font-weight:700;color:#4527a0;margin:4px 0;">Total Data Storage (GB)</div>
        <div style="font-size:1.8rem;font-weight:800;color:#4527a0;"><?php echo number_format($totals['data']); ?></div>
    </div>
</div>

<!-- Per-type breakdown -->
<?php foreach ($srv_types as $type_key => $type_info):
    if (empty($catalog[$type_key])) continue;
    $type_items = $catalog[$type_key];
    $type_cores = array_sum(array_column($type_items, 'default_cores'));
    $type_mem   = array_sum(array_column($type_items, 'default_memory'));
?>
<div style="margin-bottom:2rem;border:1px solid #e0e0e0;border-radius:14px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06);">
    <div style="background:<?php echo $type_info['bg']; ?>;padding:14px 20px;display:flex;align-items:center;gap:10px;border-bottom:2px solid <?php echo $type_info['color']; ?>20;">
        <span style="font-size:1.6rem;"><?php echo $type_info['emoji']; ?></span>
        <div>
            <div style="font-weight:700;font-size:1.1rem;color:<?php echo $type_info['color']; ?>"><?php echo $type_info['label']; ?></div>
            <div style="font-size:.8rem;color:#666;"><?php echo count($type_items); ?> items · <?php echo $type_cores; ?> cores · <?php echo $type_mem; ?> GB RAM</div>
        </div>
    </div>
    <div style="padding:16px 20px;">
        <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
            <thead style="background:#f8f9fa;">
                <tr>
                    <th style="padding:8px;text-align:left;color:#555;font-weight:600;">VM / Application</th>
                    <th style="padding:8px;text-align:center;color:#555;font-weight:600;">Cores</th>
                    <th style="padding:8px;text-align:center;color:#555;font-weight:600;">Memory (GB)</th>
                    <th style="padding:8px;text-align:center;color:#555;font-weight:600;">OS Storage (GB)</th>
                    <th style="padding:8px;text-align:center;color:#555;font-weight:600;">Data Storage (GB)</th>
                    <th style="padding:8px;text-align:center;color:#555;font-weight:600;">User Editable</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($type_items as $item): ?>
                <tr style="border-bottom:1px solid #f5f5f5;">
                    <td style="padding:8px;font-weight:500;color:#2d3748;"><?php echo htmlspecialchars($item['item_name']); ?><br><small style="color:#888;font-weight:400;"><?php echo htmlspecialchars($item['item_description'] ?? ''); ?></small></td>
                    <td style="padding:8px;text-align:center;"><?php echo $item['default_cores']; ?></td>
                    <td style="padding:8px;text-align:center;"><?php echo $item['default_memory']; ?></td>
                    <td style="padding:8px;text-align:center;"><?php echo $item['default_os_storage']; ?></td>
                    <td style="padding:8px;text-align:center;"><?php echo number_format($item['default_data_storage']); ?></td>
                    <td style="padding:8px;text-align:center;">
                        <?php echo $item['is_editable']
                            ? '<span style="color:#10b981;font-weight:600;">✔ Yes</span>'
                            : '<span style="color:#9ca3af;">Fixed</span>'; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; ?>
