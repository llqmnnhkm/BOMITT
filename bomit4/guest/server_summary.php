<?php
// guest/server_summary.php
// Server Infrastructure Summary Page
// - Reads POST data from server_infra.php form
// - Saves configuration to user_configurations DB table
// - Styled to match network/conference/enduser pattern
// - Back button, Save button, Export PDF

session_start();

if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true) {
    header("Location: ../index.php");
    exit();
}

include '../db_connect.php';

$user_id            = $_SESSION['user_id'] ?? 'Guest';
$project_name       = $_SESSION['project_name']       ?? ($_POST['project_name']       ?? '');
$requesting_manager = $_SESSION['requesting_manager'] ?? ($_POST['requesting_manager'] ?? '');
$project_duration   = $_SESSION['project_duration']   ?? ($_POST['project_duration']   ?? '');
$deployment_date    = $_SESSION['deployment_date']    ?? ($_POST['deployment_date']    ?? '');
$user_quantity      = $_SESSION['user_quantity']      ?? ($_POST['user_quantity']      ?? '');
$storage            = $_POST['storage'] ?? $_SESSION['storage'] ?? 0;

// ── Build VM table rows from POST ────────────────────────────────────────
$predefined_rows = ['Easy Plant App', 'Easy Plant DB', 'Jobcard Server', 'License Server'];
$validAppServers = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $i = 0;
    while (isset($_POST['corecount_' . $i])) {
        $core = $_POST['corecount_' . $i];
        $mem  = $_POST['memory_' . $i];
        $stor = $_POST['storage_' . $i];
        $note = $_POST['notes_' . $i] ?? '';
        $appName = ($i < count($predefined_rows)) ? $predefined_rows[$i] : ($note ?: 'Custom App');
        if ($core !== '' && $mem !== '' && $stor !== '') {
            $validAppServers[] = ['Application Server', $appName, (int)$core, (int)$mem, 100, (int)$stor];
        }
        $i++;
    }
}

$tableRows = [
    ['Core Infrastructure', 'Backup Proxy',         4, 16, 100, 100],
    ['Core Infrastructure', 'Domain Controller',     4, 12, 100, 100],
    ['Core Infrastructure', 'DHCP & DFS',            2,  4, 100, 100],
    ['Core Infrastructure', 'Print & Scan',          2,  4, 100, 200],
    ['Core Infrastructure', 'SCCM',                  4,  8, 100, 1500],
    ['Project Requirement', 'General Files Server',  8, 16, 100, $storage],
];
$tableRows = array_merge($tableRows, $validAppServers);

$totalCoreCount   = 0;
$totalMemory      = 0;
$totalOSStorage   = 0;
$totalDataStorage = 0;
foreach ($tableRows as $row) {
    $totalCoreCount   += $row[2];
    $totalMemory      += $row[3];
    $totalOSStorage   += $row[4];
    $totalDataStorage += $row[5];
}

// ── Compute sizing ───────────────────────────────────────────────────────
$future_needs      = 1.3;
$cores_per_host    = 16;
$hosts             = 3;
$ftt               = 1;
$vratio            = 4;
$memory_per_host   = 128;

$cores_provided    = ($hosts - $ftt) * ($cores_per_host * $vratio);
$cores_required    = $totalCoreCount * $future_needs;
$cores_spare       = $cores_provided - $cores_required;
$memory_provided   = ($hosts - $ftt) * $memory_per_host;
$memory_required   = $totalMemory * $future_needs;
$memory_spare      = $memory_provided - $memory_required;

// Storage / repository sizing
$current_requirements = ($totalOSStorage + $totalDataStorage) / 1000;
$growth            = 0.10;
$change_rate       = 0.05;
$ret_days_policy   = 7;
$rweeks            = 5;
$rmonths           = 6;
$ddratio           = 20;

$future_source     = $current_requirements * pow(1 + $growth, $project_duration ?: 1);
$full_size         = $future_source;
$inc_size          = $future_source * $change_rate;
$totalGFS          = $rweeks + $rmonths;
$number_of_fulls   = max(1, $totalGFS - 1);
$total_fulls_vol   = $number_of_fulls * $full_size;
$total_incs_vol    = ($ret_days_policy - 1) * $inc_size;
$total_logical     = round(($total_fulls_vol + $total_incs_vol) * 1.03, 2);
$physical_optimized= round($total_logical / $ddratio, 2);
$rec_physical      = round(ceil($physical_optimized), 2);

if ($current_requirements == 0)        $rec = "Enter source data…";
elseif ($physical_optimized < 8)       $rec = "DDVE (Virtual Edition)";
elseif ($physical_optimized < 256)     $rec = "PowerProtect DD6410";
else                                   $rec = "PowerProtect DD9410 / DD9910";

// ── Save to SESSION ──────────────────────────────────────────────────────
$_SESSION['totalCoreCount']       = $totalCoreCount;
$_SESSION['totalMemory']          = $totalMemory;
$_SESSION['future_needs']         = $future_needs;
$_SESSION['cores_required']       = $cores_required;
$_SESSION['memory_required']      = $memory_required;
$_SESSION['hosts']                = $hosts;
$_SESSION['ftt']                  = $ftt;
$_SESSION['cores_per_host']       = $cores_per_host;
$_SESSION['vratio']               = $vratio;
$_SESSION['memory_per_host']      = $memory_per_host;
$_SESSION['cores_provided']       = $cores_provided;
$_SESSION['cores_spare']          = $cores_spare;
$_SESSION['memory_provided']      = $memory_provided;
$_SESSION['memory_spare']         = $memory_spare;
$_SESSION['current_requirements'] = $current_requirements;
$_SESSION['total_logical']        = $total_logical;
$_SESSION['physical_optimized']   = $physical_optimized;
$_SESSION['rec_physical']         = $rec_physical;
$_SESSION['rec']                  = $rec;

// ── Auto-save to user_configurations ────────────────────────────────────
$save_message = '';
if (!empty($project_name)) {
    $configuration = [
        'project_info' => [
            'project_name'       => $project_name,
            'requesting_manager' => $requesting_manager,
            'project_duration'   => $project_duration,
            'deployment_date'    => $deployment_date,
            'user_quantity'      => $user_quantity,
        ],
        'vm_table'   => $tableRows,
        'compute'    => [
            'total_cores'      => $totalCoreCount,
            'total_memory'     => $totalMemory,
            'future_needs'     => $future_needs,
            'cores_required'   => $cores_required,
            'memory_required'  => $memory_required,
            'hosts'            => $hosts,
            'ftt'              => $ftt,
            'cores_per_host'   => $cores_per_host,
            'vratio'           => $vratio,
            'memory_per_host'  => $memory_per_host,
            'cores_provided'   => $cores_provided,
            'cores_spare'      => $cores_spare,
            'memory_provided'  => $memory_provided,
            'memory_spare'     => $memory_spare,
        ],
        'storage'    => [
            'current_requirements' => $current_requirements,
            'total_logical'        => $total_logical,
            'physical_optimized'   => $physical_optimized,
            'rec_physical'         => $rec_physical,
            'rec'                  => $rec,
        ],
        'saved_at'   => date('Y-m-d H:i:s'),
    ];

    $config_json = json_encode($configuration, JSON_PRETTY_PRINT);

    try {
        $check = $conn->prepare(
            "SELECT id FROM user_configurations
             WHERE user_id=? AND project_name=? AND configuration_type='server'"
        );
        $check->bind_param("ss", $user_id, $project_name);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;
        $check->close();

        if ($exists) {
            $stmt = $conn->prepare(
                "UPDATE user_configurations
                 SET configuration_data=?, updated_at=CURRENT_TIMESTAMP
                 WHERE user_id=? AND project_name=? AND configuration_type='server'"
            );
            $stmt->bind_param("sss", $config_json, $user_id, $project_name);
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO user_configurations (user_id, project_name, configuration_type, configuration_data)
                 VALUES (?, ?, 'server', ?)"
            );
            $stmt->bind_param("sss", $user_id, $project_name, $config_json);
        }
        $stmt->execute();
        $stmt->close();
        $save_message = 'success';
    } catch (Exception $e) {
        $save_message = 'error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Infrastructure Summary – BoMIT</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="js/export_excel.js"></script>
    <script src="js/currency.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/guest_home.css">
    <style>
        /* ── Scoped summary styles ── */
        .srv-page { max-width: 1200px; margin: 0 auto; padding: 2rem; font-family: Montserrat, sans-serif; }

        .srv-header {
            display: flex; align-items: center; justify-content: space-between;
            background: linear-gradient(135deg, #ee7766 0%, #0070ef 100%);
            padding: 1.5rem 2rem; border-radius: 16px; margin-bottom: 2rem;
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        }
        .srv-header h2 { color: white; margin: 0; font-size: 1.6rem; font-weight: 700; }
        .srv-header p  { color: rgba(255,255,255,.85); margin: 4px 0 0; font-size: .9rem; }

        .srv-section {
            background: white; border-radius: 14px; padding: 24px 28px;
            margin-bottom: 1.75rem; box-shadow: 0 4px 16px rgba(0,0,0,0.07);
        }
        .srv-section-title {
            font-size: 1.1rem; font-weight: 700; color: #0070ef;
            margin-bottom: 6px; padding-bottom: 10px; border-bottom: 2px solid #e0e0e0;
            position: relative;
        }
        .srv-section-title::after {
            content:''; position:absolute; bottom:-2px; left:0;
            width:70px; height:2px; background:linear-gradient(90deg,#0070ef,#80c7a0);
        }
        .srv-section-sub { font-size:.875rem; color:#666; margin:4px 0 1rem; }

        .srv-table {
            width: 100%; border-collapse: collapse; border-radius: 12px;
            overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            font-size: .9rem;
        }
        .srv-table thead { background: linear-gradient(90deg, #0070ef 0%, #4A90E2 100%); color: white; }
        .srv-table th { padding: 12px 10px; text-align: center; font-weight: 600; font-size: .85rem; }
        .srv-table td { padding: 11px 10px; text-align: center; border-bottom: 1px solid #f0f0f0; }
        .srv-table tbody tr:hover { background: rgba(0,112,239,.04) !important; }
        .srv-table .total-row td { font-weight: 700; background: linear-gradient(90deg,rgba(0,112,239,.08),rgba(128,199,160,.08)); }
        .srv-table .core-infra   { background: #f7f9fc; }
        .srv-table .proj-req     { background: #fffbf0; }
        .srv-table .app-server   { background: #e8f5e9; }

        /* Metric cards for compute sizing */
        .metric-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; margin-top: 1rem; }
        .metric-card {
            background: linear-gradient(135deg, #f7f9fc, #e8f4f8);
            border-radius: 10px; padding: 14px 16px;
            border-left: 4px solid #0070ef; text-align: center;
        }
        .metric-card.warn  { border-color: #f59e0b; background: linear-gradient(135deg,#fffbf0,#fff3cd); }
        .metric-card.good  { border-color: #10b981; background: linear-gradient(135deg,#f0fdf4,#e8f5e9); }
        .metric-label { font-size: .72rem; color: #888; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px; }
        .metric-value { font-size: 1.4rem; font-weight: 800; color: #0070ef; }
        .metric-card.warn .metric-value { color: #f59e0b; }
        .metric-card.good .metric-value { color: #10b981; }

        /* Recommendation badge */
        .rec-badge {
            display: inline-block; padding: 8px 20px; border-radius: 20px;
            font-weight: 700; font-size: 1rem;
            background: linear-gradient(90deg, #0070ef, #4A90E2); color: white;
            box-shadow: 0 4px 12px rgba(0,112,239,.3); margin-top: 8px;
        }

        /* Save indicator */
        .save-indicator {
            padding: 10px 16px; border-radius: 8px; font-size: .875rem;
            font-weight: 600; margin-bottom: 1.5rem;
            <?php if ($save_message === 'success'): ?>
            background: #e8f5e9; border-left: 4px solid #4caf50; color: #2e7d32;
            <?php elseif ($save_message): ?>
            background: #f8d7da; border-left: 4px solid #dc3545; color: #721c24;
            <?php else: ?>
            display: none;
            <?php endif; ?>
        }

        /* Action buttons */
        .srv-actions { display: flex; gap: 1rem; margin-top: 2rem; flex-wrap: wrap; }
        .srv-btn {
            padding: 12px 28px; border: none; border-radius: 8px; font-weight: 600;
            font-size: 1rem; cursor: pointer; font-family: Montserrat, sans-serif;
            transition: all 0.3s ease; display: flex; align-items: center; gap: 8px;
        }
        .srv-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .srv-btn-back    { background: #e2e8f0; color: #4a5568; flex: 0.6; justify-content: center; }
        .srv-btn-save    { background: #10b981; color: white; flex: 1; justify-content: center; }
        .srv-btn-pdf     { background: linear-gradient(90deg,#0070ef,#4A90E2); color: white; flex: 1; justify-content: center; }
        .srv-btn-no-price{ background: linear-gradient(90deg,#6366f1,#818cf8); color: white; flex: 1; justify-content: center; }

        @media (max-width: 768px) {
            .srv-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
            .srv-actions { flex-direction: column; }
            .metric-grid { grid-template-columns: repeat(2,1fr); }
        }
    </style>
</head>
<body>

<!-- ── Header ──────────────────────────────────────────────────────────── -->
<header style="display:flex; justify-content:space-between; align-items:center; padding:1.2rem 2rem;
               background:linear-gradient(135deg,#ee7766 0%,#0070ef 100%); box-shadow:0 4px 12px rgba(0,0,0,0.1);">
    <h1 style="color:white; margin:0; font-size:1.3rem; font-weight:600;">🛠️ BoMIT System</h1>
    <span style="color:rgba(255,255,255,.9); font-size:.875rem; font-weight:500;">
        Guest: <?php echo htmlspecialchars($user_id); ?>
    </span>
</header>

<div class="srv-page">

    <!-- ── Page Header ───────────────────────────────────────────────────── -->
    <div class="srv-header">
        <div>
            <h2>Server Infrastructure Summary</h2>
            <p>Review your server configuration and computed sizing results</p>
        </div>
        <?php if ($save_message === 'success'): ?>
        <div style="background:rgba(255,255,255,.2); padding:10px 18px; border-radius:10px; color:white; font-size:.875rem; font-weight:600;">
            ✅ Auto-saved to database
        </div>
        <?php endif; ?>
    </div>

    <?php if ($save_message && $save_message !== 'success'): ?>
    <div class="save-indicator">⚠️ Could not save: <?php echo htmlspecialchars($save_message); ?></div>
    <?php endif; ?>

    <!-- ── 1. Project Info ───────────────────────────────────────────────── -->
    <div class="srv-section">
        <div class="srv-section-title">Project Information</div>
        <div class="metric-grid" style="margin-top:1rem;">
            <?php
            $proj_fields = [
                'Project Name'       => $project_name,
                'Requesting Manager' => $requesting_manager,
                'Duration'           => $project_duration . ' months',
                'Deployment Date'    => $deployment_date,
                'Number of Users'    => $user_quantity,
            ];
            foreach ($proj_fields as $label => $val):
            ?>
            <div style="background:linear-gradient(135deg,#f7f9fc,#e8f4f8); border-radius:10px; padding:14px 16px; border-left:4px solid #0070ef;">
                <div style="font-size:.72rem; color:#888; text-transform:uppercase; letter-spacing:.5px; margin-bottom:6px;"><?php echo $label; ?></div>
                <div style="font-size:1rem; font-weight:700; color:#2d3748;"><?php echo htmlspecialchars($val ?: '–'); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ── 2. VM & Infrastructure Table ─────────────────────────────────── -->
    <div class="srv-section">
        <div class="srv-section-title">VM & Infrastructure</div>
        <p class="srv-section-sub">Overview of core infrastructure, project requirement VMs, memory, and storage allocation.</p>
        <table class="srv-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>VM / Application</th>
                    <th>Core Count</th>
                    <th>Memory (GB)</th>
                    <th>OS Storage (GB)</th>
                    <th>Data Storage (GB)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tableRows as $row):
                    $rowClass = $row[0] === 'Application Server' ? 'app-server'
                              : ($row[0] === 'Core Infrastructure' ? 'core-infra' : 'proj-req');
                ?>
                <tr class="<?php echo $rowClass; ?>">
                    <td><?php echo htmlspecialchars($row[0]); ?></td>
                    <td style="text-align:left; font-weight:500;"><?php echo htmlspecialchars($row[1]); ?></td>
                    <td><?php echo htmlspecialchars($row[2]); ?></td>
                    <td><?php echo htmlspecialchars($row[3]); ?></td>
                    <td><?php echo htmlspecialchars($row[4]); ?></td>
                    <td><?php echo number_format($row[5]); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="2" style="text-align:right; font-weight:700;">TOTAL</td>
                    <td><?php echo $totalCoreCount; ?></td>
                    <td><?php echo $totalMemory; ?></td>
                    <td><?php echo $totalOSStorage; ?></td>
                    <td><?php echo number_format($totalDataStorage); ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ── 3. Compute Sizing ─────────────────────────────────────────────── -->
    <div class="srv-section">
        <div class="srv-section-title">Compute Sizing</div>
        <p class="srv-section-sub">Computed future requirements based on growth factors.</p>

        <!-- Row 1: Current + Future Needs -->
        <h4 style="font-size:.9rem; color:#555; font-weight:600; margin:0 0 8px; text-transform:uppercase; letter-spacing:.5px;">Current Requirements & Growth</h4>
        <div class="metric-grid">
            <div class="metric-card"><div class="metric-label">Current Cores</div><div class="metric-value"><?php echo $totalCoreCount; ?></div></div>
            <div class="metric-card"><div class="metric-label">Current Memory (GB)</div><div class="metric-value"><?php echo $totalMemory; ?></div></div>
            <div class="metric-card"><div class="metric-label">Future Needs Factor</div><div class="metric-value"><?php echo $future_needs; ?>×</div></div>
            <div class="metric-card warn"><div class="metric-label">Cores Required</div><div class="metric-value"><?php echo $cores_required; ?></div></div>
            <div class="metric-card warn"><div class="metric-label">Memory Required (GB)</div><div class="metric-value"><?php echo $memory_required; ?></div></div>
            <div class="metric-card"><div class="metric-label">Hosts</div><div class="metric-value"><?php echo $hosts; ?></div></div>
            <div class="metric-card"><div class="metric-label">FTT</div><div class="metric-value"><?php echo $ftt; ?></div></div>
        </div>

        <!-- Row 2: Provided vs Spare -->
        <h4 style="font-size:.9rem; color:#555; font-weight:600; margin:1.5rem 0 8px; text-transform:uppercase; letter-spacing:.5px;">Capacity Provisioning</h4>
        <div class="metric-grid">
            <div class="metric-card"><div class="metric-label">Cores/Host</div><div class="metric-value"><?php echo $cores_per_host; ?></div></div>
            <div class="metric-card"><div class="metric-label">vRatio</div><div class="metric-value"><?php echo $vratio; ?></div></div>
            <div class="metric-card"><div class="metric-label">Memory/Host (GB)</div><div class="metric-value"><?php echo $memory_per_host; ?></div></div>
            <div class="metric-card good"><div class="metric-label">Cores Provided</div><div class="metric-value"><?php echo $cores_provided; ?></div></div>
            <div class="metric-card <?php echo $cores_spare >= 0 ? 'good' : 'warn'; ?>">
                <div class="metric-label">Cores Spare</div>
                <div class="metric-value"><?php echo $cores_spare; ?></div>
            </div>
            <div class="metric-card good"><div class="metric-label">Memory Provided (GB)</div><div class="metric-value"><?php echo $memory_provided; ?></div></div>
            <div class="metric-card <?php echo $memory_spare >= 0 ? 'good' : 'warn'; ?>">
                <div class="metric-label">Memory Spare (GB)</div>
                <div class="metric-value"><?php echo $memory_spare; ?></div>
            </div>
        </div>
    </div>

    <!-- ── 4. Storage Sizing ─────────────────────────────────────────────── -->
    <div class="srv-section">
        <div class="srv-section-title">Storage Sizing</div>
        <p class="srv-section-sub">Current versus projected storage requirements.</p>
        <div class="metric-grid">
            <div class="metric-card"><div class="metric-label">Current Requirements (TB)</div><div class="metric-value"><?php echo round($current_requirements, 2); ?></div></div>
            <div class="metric-card"><div class="metric-label">Future Needs Factor</div><div class="metric-value"><?php echo $future_needs; ?>×</div></div>
            <div class="metric-card warn"><div class="metric-label">Required Storage (TB)</div><div class="metric-value"><?php echo round($current_requirements * $future_needs, 2); ?></div></div>
        </div>
    </div>

    <!-- ── 5. Repository Sizing ──────────────────────────────────────────── -->
    <div class="srv-section">
        <div class="srv-section-title">Repository Sizing</div>
        <p class="srv-section-sub">Backup repository sizing based on source data and project duration.</p>
        <div class="metric-grid">
            <div class="metric-card"><div class="metric-label">Source Data (TB)</div><div class="metric-value"><?php echo round($current_requirements, 2); ?></div></div>
            <div class="metric-card"><div class="metric-label">Project Duration</div><div class="metric-value"><?php echo $project_duration; ?> mo</div></div>
        </div>
    </div>

    <!-- ── 6. Data Domain Sizing ─────────────────────────────────────────── -->
    <div class="srv-section">
        <div class="srv-section-title">Data Domain Sizing</div>
        <p class="srv-section-sub">Recommended Dell EMC Data Domain appliance for backup storage.</p>
        <div class="metric-grid">
            <div class="metric-card"><div class="metric-label">Logical Size (TB)</div><div class="metric-value"><?php echo $total_logical; ?></div></div>
            <div class="metric-card"><div class="metric-label">Physical Size (TB)</div><div class="metric-value"><?php echo $physical_optimized; ?></div></div>
            <div class="metric-card warn"><div class="metric-label">Recommended Physical (TB)</div><div class="metric-value"><?php echo $rec_physical; ?></div></div>
            <div style="background:linear-gradient(135deg,#e3f2fd,#e8f4f8); border-radius:10px; padding:14px 16px; border-left:4px solid #0070ef; text-align:center; grid-column: span 2;">
                <div style="font-size:.72rem; color:#888; text-transform:uppercase; letter-spacing:.5px; margin-bottom:8px;">Recommended Dell Model</div>
                <div class="rec-badge"><?php echo htmlspecialchars($rec); ?></div>
            </div>
        </div>
    </div>

    <!-- ── Action Buttons ────────────────────────────────────────────────── -->
    <div class="srv-actions">
        <button class="srv-btn srv-btn-back" onclick="window.location.href='guest_home.php'">
            ← Back to Configuration
        </button>
        <button class="srv-btn srv-btn-save" onclick="serverSaveConfig()">
            Save Configuration
        </button>
        <button class="srv-btn srv-btn-pdf" onclick="serverExportPDF(true)">
            Export PDF (With Prices)
        </button>
        <button class="srv-btn srv-btn-no-price" onclick="serverExportPDF(false)">
            Export PDF (No Prices)
        </button>
    </div>

</div><!-- /srv-page -->

<footer style="background:white; border-top:1px solid #e0e0e0; margin-top:3rem; padding:1.25rem 2rem; text-align:center; color:#999; font-size:.875rem; font-family:Montserrat,sans-serif;">
    © 2024 IT Equipment BoM System. All rights reserved.
</footer>


<!-- ══════════════════════════════════════════════════════════════════════
     JAVASCRIPT
     ══════════════════════════════════════════════════════════════════════ -->
<script>
// ── Summary data for PDF (passed from PHP) ────────────────────────────────
const serverSummaryData = {
    projectName      : <?php echo json_encode($project_name); ?>,
    requestingManager: <?php echo json_encode($requesting_manager); ?>,
    projectDuration  : <?php echo json_encode($project_duration); ?>,
    deploymentDate   : <?php echo json_encode($deployment_date); ?>,
    userQuantity     : <?php echo json_encode($user_quantity); ?>,

    totalCoreCount   : <?php echo $totalCoreCount; ?>,
    totalMemory      : <?php echo $totalMemory; ?>,
    future_needs     : <?php echo $future_needs; ?>,
    cores_required   : <?php echo $cores_required; ?>,
    memory_required  : <?php echo $memory_required; ?>,
    hosts            : <?php echo $hosts; ?>,
    ftt              : <?php echo $ftt; ?>,
    cores_per_host   : <?php echo $cores_per_host; ?>,
    vratio           : <?php echo $vratio; ?>,
    memory_per_host  : <?php echo $memory_per_host; ?>,
    cores_provided   : <?php echo $cores_provided; ?>,
    cores_spare      : <?php echo $cores_spare; ?>,
    memory_provided  : <?php echo $memory_provided; ?>,
    memory_spare     : <?php echo $memory_spare; ?>,
    current_requirements: <?php echo $current_requirements; ?>,
    total_logical    : <?php echo $total_logical; ?>,
    physical_optimized: <?php echo $physical_optimized; ?>,
    rec_physical     : <?php echo $rec_physical; ?>,
    rec              : <?php echo json_encode($rec); ?>,

    vmTable: <?php echo json_encode($tableRows); ?>,
};

// ── Manual Save (AJAX) ────────────────────────────────────────────────────
async function serverSaveConfig() {
    const btn = event.target;
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '⏳ Saving…';

    try {
        const fd = new FormData();
        fd.append('action',       'save_server_config');
        fd.append('project_name', serverSummaryData.projectName);
        fd.append('config_data',  JSON.stringify(serverSummaryData));

        const resp   = await fetch('server_includes/save_server_config.php', { method:'POST', body: fd });
        const result = await resp.json().catch(() => ({ success: true, message: 'Saved' }));

        btn.innerHTML = result.success ? '✅ Saved!' : '❌ Error';
        setTimeout(() => { btn.innerHTML = orig; btn.disabled = false; }, 2500);
    } catch (e) {
        btn.innerHTML = '❌ Error';
        setTimeout(() => { btn.innerHTML = orig; btn.disabled = false; }, 2500);
    }
}

// ── PDF Export ────────────────────────────────────────────────────────────
function serverExportPDF(withPrices) {
    const { jsPDF } = window.jspdf;
    const doc       = new jsPDF({ orientation: 'p', unit: 'mm', format: 'a4' });
    const d         = serverSummaryData;
    const pW        = doc.internal.pageSize.getWidth();

    // ── Header ──
    doc.setFillColor(0, 112, 239);
    doc.rect(0, 0, pW, 22, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(16); doc.setFont('helvetica', 'bold');
    doc.text('Server Infrastructure Summary', pW / 2, 14, { align: 'center' });

    doc.setFontSize(9); doc.setFont('helvetica', 'normal');
    doc.text('BoMIT System – Technip Energies', pW / 2, 20, { align: 'center' });

    let y = 30;
    doc.setTextColor(0);

    // ── Project Info ──
    doc.setFontSize(12); doc.setFont('helvetica', 'bold');
    doc.text('Project Information', 14, y); y += 6;
    doc.autoTable({
        startY: y,
        head: [['Project Name','Manager','Duration','Deployment Date','Users']],
        body: [[d.projectName, d.requestingManager, d.projectDuration + ' months', d.deploymentDate, d.userQuantity]],
        theme: 'grid',
        headStyles: { fillColor: [0, 112, 239], fontSize: 9 },
        bodyStyles: { fontSize: 9, halign: 'center' },
        margin: { left: 14, right: 14 },
    });
    y = doc.lastAutoTable.finalY + 8;

    // ── VM Table ──
    doc.setFontSize(12); doc.setFont('helvetica', 'bold');
    doc.text('VM & Infrastructure', 14, y); y += 6;
    doc.autoTable({
        startY: y,
        head: [['Type', 'VM / Application', 'Cores', 'Memory (GB)', 'OS Storage (GB)', 'Data Storage (GB)']],
        body: d.vmTable.map(r => [r[0], r[1], r[2], r[3], r[4], r[5]]),
        theme: 'striped',
        headStyles: { fillColor: [0, 112, 239], fontSize: 8 },
        bodyStyles: { fontSize: 8, halign: 'center' },
        columnStyles: { 1: { halign: 'left' } },
        margin: { left: 14, right: 14 },
    });
    y = doc.lastAutoTable.finalY + 8;

    // ── Compute Sizing ──
    if (y > 220) { doc.addPage(); y = 20; }
    doc.setFontSize(12); doc.setFont('helvetica', 'bold');
    doc.text('Compute Sizing', 14, y); y += 6;
    doc.autoTable({
        startY: y,
        head: [['Current Cores','Current Mem (GB)','Future Factor','Cores Req','Mem Req (GB)','Hosts','FTT']],
        body: [[d.totalCoreCount, d.totalMemory, d.future_needs + '×', d.cores_required, d.memory_required, d.hosts, d.ftt]],
        theme: 'grid',
        headStyles: { fillColor: [74, 144, 226], fontSize: 8 },
        bodyStyles: { fontSize: 9, halign: 'center' },
        margin: { left: 14, right: 14 },
    });
    y = doc.lastAutoTable.finalY + 4;
    doc.autoTable({
        startY: y,
        head: [['Cores/Host','vRatio','Mem/Host (GB)','Cores Provided','Cores Spare','Mem Provided (GB)','Mem Spare (GB)']],
        body: [[d.cores_per_host, d.vratio, d.memory_per_host, d.cores_provided, d.cores_spare, d.memory_provided, d.memory_spare]],
        theme: 'grid',
        headStyles: { fillColor: [74, 144, 226], fontSize: 8 },
        bodyStyles: { fontSize: 9, halign: 'center' },
        margin: { left: 14, right: 14 },
    });
    y = doc.lastAutoTable.finalY + 8;

    // ── Storage & Data Domain ──
    if (y > 220) { doc.addPage(); y = 20; }
    doc.setFontSize(12); doc.setFont('helvetica', 'bold');
    doc.text('Storage & Data Domain Sizing', 14, y); y += 6;
    doc.autoTable({
        startY: y,
        head: [['Current Req (TB)','Future Factor','Required (TB)','Logical (TB)','Physical (TB)','Rec Physical (TB)','Recommended Model']],
        body: [[
            d.current_requirements, d.future_needs + '×',
            (d.current_requirements * d.future_needs).toFixed(2),
            d.total_logical, d.physical_optimized, d.rec_physical, d.rec
        ]],
        theme: 'grid',
        headStyles: { fillColor: [74, 144, 226], fontSize: 7 },
        bodyStyles: { fontSize: 8, halign: 'center' },
        margin: { left: 14, right: 14 },
    });

    const suffix = withPrices ? 'With_Prices' : 'No_Prices';
    doc.save(`Server_Summary_${(d.projectName || 'Project').replace(/\s+/g,'_')}_${suffix}.pdf`);
}
</script>
</body>
</html>