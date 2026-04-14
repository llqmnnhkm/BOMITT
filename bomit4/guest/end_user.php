<?php
// ── Fetch catalog from DB ─────────────────────────────────────────────────
$eu_user_types = [
    'general'   => ['label'=>'General User',   'emoji'=>'🖥️', 'bg'=>'#e8f4fd', 'accent'=>'#0277bd', 'border'=>'#81c4f0', 'header'=>'linear-gradient(90deg,#0288d1,#0277bd)', 'sub_text'=>'#01579b'],
    'technical' => ['label'=>'Technical User',  'emoji'=>'⚙️', 'bg'=>'#e8f5e9', 'accent'=>'#2e7d32', 'border'=>'#a5d6a7', 'header'=>'linear-gradient(90deg,#43a047,#2e7d32)', 'sub_text'=>'#1b5e20'],
    'design'    => ['label'=>'Design / CAD',    'emoji'=>'🎨', 'bg'=>'#fce4ec', 'accent'=>'#ad1457', 'border'=>'#f48fb1', 'header'=>'linear-gradient(90deg,#c2185b,#ad1457)', 'sub_text'=>'#880e4f'],
    'field'     => ['label'=>'Field / Mobile',  'emoji'=>'🏗️', 'bg'=>'#fff3e0', 'accent'=>'#e65100', 'border'=>'#ffcc80', 'header'=>'linear-gradient(90deg,#f57c00,#e65100)', 'sub_text'=>'#bf360c'],
    'executive' => ['label'=>'Executive / VIP', 'emoji'=>'💼', 'bg'=>'#ede7f6', 'accent'=>'#4527a0', 'border'=>'#b39ddb', 'header'=>'linear-gradient(90deg,#512da8,#4527a0)', 'sub_text'=>'#311b92'],
];
$eu_category_labels = [
    'workstation' => ['label' => 'Workstation Equipment',     'emoji' => ''],
    'peripherals' => ['label' => 'Peripherals & Accessories', 'emoji' => ''],
    'mobile'      => ['label' => 'Mobile & Communications',   'emoji' => ''],
    'software'    => ['label' => 'Software & Licenses',       'emoji' => ''],
];
$eu_catalog = [];
$eu_stmt = $conn->prepare(
    "SELECT * FROM enduser_equipment WHERE is_active=1 ORDER BY user_type,item_category,display_order,id"
);
if ($eu_stmt) {
    $eu_stmt->execute();
    $eu_result = $eu_stmt->get_result();
    while ($row = $eu_result->fetch_assoc()) {
        $eu_catalog[$row['user_type']][$row['item_category']][] = $row;
    }
    $eu_stmt->close();
}

// Parse project user_quantity → numeric ceiling for validation
function euParseMaxUsers($qty_str) {
    $s = strtolower($qty_str ?? '');
    if (strpos($s,'less than 50') !== false) return 49;
    if (strpos($s,'51')  !== false)          return 150;
    if (strpos($s,'151') !== false)          return 300;
    if (strpos($s,'301') !== false)          return 400;
    if (strpos($s,'more than 400') !== false)return 9999;
    return 9999;
}
$eu_max_users = euParseMaxUsers($user_quantity ?? '');
?>

<!-- End User Container -->
<div id="container-enduser" class="question-container">
    <div class="container-header">
        <div class="container-icon enduser"></div>
        <div class="container-title">
            <h3>End User Equipment</h3>
            <p>Configure workstation and peripheral requirements</p>
        </div>
    </div>

    <div id="eu-save-status" style="padding:12px;margin-bottom:20px;border-radius:8px;display:none;font-family:Montserrat;font-size:.95rem;font-weight:500;">
        <span id="eu-save-status-text"></span>
    </div>

    <form id="enduser-form">
        <input type="hidden" name="category" value="enduser">

        <!-- ── 1. PROJECT INFO ───────────────────────────────────────── -->
        <div style="background:linear-gradient(135deg,#e3f2fd,#f3e5f5);padding:15px 20px;border-radius:12px;margin-bottom:1.5rem;border-left:4px solid #2196F3;display:flex;align-items:center;gap:1rem;">
            <div style="font-size:1.5rem;">ℹ️</div>
            <div style="flex:1;">
                <div style="font-weight:600;color:#1565c0;margin-bottom:4px;">Project Information</div>
                <div style="font-size:.875rem;color:#666;">
                    Imported from your Project Details.
                    <a href="project_details.php" style="color:#2196F3;font-weight:600;text-decoration:none;">Click here to edit →</a>
                </div>
            </div>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:1.5rem;margin-bottom:2rem;">
            <?php
            $eu_proj_fields = [
                'project_name'       => ['label'=>'Project Name',       'type'=>'text'],
                'requesting_manager' => ['label'=>'Requesting Manager',  'type'=>'text'],
                'project_duration'   => ['label'=>'Duration (Months)',   'type'=>'number'],
                'deployment_date'    => ['label'=>'Deployment Date',     'type'=>'date'],
                'user_quantity'      => ['label'=>'Number of Users',     'type'=>'text'],
            ];
            foreach ($eu_proj_fields as $key => $meta):
                $val = $$key ?? '';
            ?>
            <div style="flex:1;min-width:180px;">
                <label style="font-weight:600;display:block;margin-bottom:.5rem;color:#666;"><?php echo $meta['label']; ?></label>
                <input type="<?php echo $meta['type']; ?>" value="<?php echo htmlspecialchars($val); ?>" disabled
                       style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;font-family:Montserrat;font-size:1rem;background:#f5f5f5;color:#666;cursor:not-allowed;box-sizing:border-box;">
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ── 2. USER TYPE MULTI-SELECTION ─────────────────────────── -->
        <div class="eu-section">
            <div class="eu-section-header"><span></span> User Types & Headcount</div>

            <!-- User range banner -->
            <div style="padding:14px 18px;background:linear-gradient(90deg,rgba(0,112,239,.07),rgba(128,199,160,.07));border-radius:8px;margin-bottom:1.25rem;border-left:4px solid #0070ef;display:flex;align-items:center;gap:12px;">
                <span style="font-size:1.8rem;"></span>
                <div>
                    <div style="font-size:.8rem;color:#666;margin-bottom:2px;">Project user range:</div>
                    <div style="font-size:1.1rem;font-weight:700;color:#0070ef;" id="eu-range-label">
                        <?php echo htmlspecialchars($user_quantity ?: 'Not set'); ?>
                    </div>
                </div>
                <div style="margin-left:auto;text-align:right;">
                    <div style="font-size:.8rem;color:#666;margin-bottom:2px;">Users assigned:</div>
                    <div style="font-size:1.1rem;font-weight:700;" id="eu-total-assigned-display">0 / <?php echo $eu_max_users === 9999 ? '400+' : $eu_max_users; ?></div>
                </div>
            </div>

            <!-- User count warning -->
            <div id="eu-user-count-warn" style="display:none;padding:10px 16px;background:#fff3cd;border-left:4px solid #ffc107;border-radius:8px;font-size:.875rem;color:#856404;margin-bottom:1rem;">
                ⚠️ <span id="eu-warn-text"></span>
            </div>

            <label style="font-weight:600;display:block;margin-bottom:.75rem;">
                Select user types and enter exact number of users per type <span class="required">*</span>
            </label>
            <p style="font-size:.875rem;color:#666;margin:0 0 1rem;">
                Click a card to select it, then enter how many users of that type.
                Total must match your project headcount.
            </p>

            <!-- User type cards — multi-select with user count input -->
            <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
                <?php foreach ($eu_user_types as $ut_key => $ut_info):
                    $item_count = 0;
                    if (!empty($eu_catalog[$ut_key])) {
                        foreach ($eu_catalog[$ut_key] as $items) $item_count += count($items);
                    }
                ?>
                <div class="eu-type-card" id="eu-card-<?php echo $ut_key; ?>"
                     data-type="<?php echo $ut_key; ?>"
                     onclick="euToggleType('<?php echo $ut_key; ?>')"
                     style="flex:1;min-width:150px;padding:18px;border:2px solid #e0e0e0;
                            border-radius:12px;text-align:center;cursor:pointer;
                            transition:all .3s ease;background:white;user-select:none;">
                    <div style="font-size:2rem;margin-bottom:6px;pointer-events:none;"><?php echo $ut_info['emoji']; ?></div>
                    <div style="font-weight:600;font-size:.95rem;color:#2d3748;margin-bottom:3px;pointer-events:none;"><?php echo $ut_info['label']; ?></div>
                    <div style="font-size:.75rem;color:#888;pointer-events:none;"><?php echo $item_count; ?> items</div>

                    <!-- User count input — shown when selected -->
                    <div id="eu-count-wrap-<?php echo $ut_key; ?>"
                         style="display:none;margin-top:12px;padding-top:12px;border-top:1px solid #e0e0e0;"
                         onclick="event.stopPropagation();">
                        <label style="font-size:.75rem;font-weight:600;color:#0070ef;display:block;margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px;">
                            No. of users
                        </label>
                        <input type="number"
                               id="eu-count-<?php echo $ut_key; ?>"
                               name="eu_count_<?php echo $ut_key; ?>"
                               value="1" min="1" max="<?php echo $eu_max_users === 9999 ? 9999 : $eu_max_users; ?>"
                               oninput="euOnCountChange('<?php echo $ut_key; ?>')"
                               onchange="euOnCountChange('<?php echo $ut_key; ?>')"
                               style="width:70px;text-align:center;padding:7px;border:2px solid #0070ef;
                                      border-radius:8px;font-family:Montserrat;font-size:1.1rem;
                                      font-weight:700;color:#0070ef;background:white;">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Selection summary badge -->
            <div id="eu-selection-summary" style="display:none;padding:12px 16px;background:#e8f5e9;border-radius:8px;border-left:4px solid #000000;margin-top:.5rem;">
                <span style="font-size:1rem;">✅</span>
                <span id="eu-summary-text" style="font-weight:600;color:#1b5e20;margin-left:8px;"></span>
            </div>
        </div>

        <!-- ── 3. EQUIPMENT SECTIONS — one per selected user type ────── -->
        <?php foreach ($eu_user_types as $ut_key => $ut_info):
            if (empty($eu_catalog[$ut_key])) continue;
        ?>
        <div id="eu-section-<?php echo $ut_key; ?>" class="eu-usertype-section" style="display:none; background:<?php echo $ut_info['bg']; ?>; border-radius:16px; padding:20px; margin-bottom:1.5rem; border:2px solid <?php echo $ut_info['border']; ?>;">

            <!-- Section label with user count badge -->
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:1rem;padding:14px 18px;
                        background:<?php echo $ut_info['bg']; ?>;
                        border-radius:10px;border-left:5px solid <?php echo $ut_info['accent']; ?>;">
                <span style="font-size:1.5rem;"><?php echo $ut_info['emoji']; ?></span>
                <div>
                    <div style="font-weight:700;font-size:1.05rem;color:<?php echo $ut_info['accent']; ?>;"><?php echo $ut_info['label']; ?></div>
                    <div style="font-size:.82rem;color:#666;margin-top:2px;">
                        Quantities = default per user × <strong id="eu-count-label-<?php echo $ut_key; ?>">1</strong> user(s)
                    </div>
                </div>
            </div>

            <?php foreach ($eu_category_labels as $cat_key => $cat_info):
                if (empty($eu_catalog[$ut_key][$cat_key])) continue;
                $items    = $eu_catalog[$ut_key][$cat_key];
                $tbody_id = 'eu-tbody-' . $ut_key . '-' . $cat_key;
                $total_id = 'eu-total-' . $ut_key . '-' . $cat_key;
            ?>
            <div class="eu-section">
                <div class="eu-section-header">
                    <span><?php echo $cat_info['emoji']; ?></span>
                    <?php echo $cat_info['label']; ?>
                    <span style="font-size:.8rem;font-weight:400;color:#888;margin-left:auto;">Adjust as needed</span>
                </div>
                <table style="width:100%;border-collapse:collapse;border-radius:12px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,.1);margin-bottom:1rem;">
                    <thead style="background:<?php echo $ut_info['header']; ?>;color:white;">
                        <tr>
                            <th style="padding:12px;text-align:left;">Item / Specification</th>
                            <th style="padding:12px;text-align:center;width:110px;">Quantity</th>
                            <th style="padding:12px;text-align:right;width:140px;">Unit Price</th>
                            <th style="padding:12px;text-align:right;width:130px;">Total</th>
                        </tr>
                    </thead>
                    <tbody id="<?php echo $tbody_id; ?>" style="background:white;">
                        <?php foreach ($items as $item):
                            $row_total = $item['unit_price'] * $item['default_quantity'];
                        ?>
                        <tr style="border-bottom:1px solid #e0e0e0;">
                            <td style="padding:12px;">
                                <div style="font-weight:500;color:#2d3748;"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                <?php if (!empty($item['item_description'])): ?>
                                <div style="font-size:.8rem;color:#888;margin-top:2px;">(<?php echo htmlspecialchars($item['item_description']); ?>)</div>
                                <?php endif; ?>
                            </td>
                            <td style="padding:12px;text-align:center;">
                                <input type="number"
                                    name="eu_qty_<?php echo $ut_key; ?>_<?php echo $item['id']; ?>"
                                    min="0"
                                    value="<?php echo (int)$item['default_quantity']; ?>"
                                    data-price="<?php echo $item['unit_price']; ?>"
                                    data-default="<?php echo (int)$item['default_quantity']; ?>"
                                    data-item-id="<?php echo $item['id']; ?>"
                                    data-ut="<?php echo $ut_key; ?>"
                                    data-cat="<?php echo $cat_key; ?>"
                                    onchange="euCalcSectionTotal('<?php echo $ut_key; ?>','<?php echo $cat_key; ?>')"
                                    oninput="euCalcSectionTotal('<?php echo $ut_key; ?>','<?php echo $cat_key; ?>')"
                                    style="width:80px;text-align:center;padding:8px;border:1px solid #ccc;border-radius:6px;font-family:Montserrat;">
                            </td>
                            <td style="padding:12px;text-align:right;color:#666;" data-myr-price="<?php echo $item['unit_price']; ?>">
                                <?php echo function_exists('formatCurrency') ? formatCurrency($item['unit_price']) : 'RM ' . number_format($item['unit_price'],2); ?>
                            </td>
                            <td class="eu-row-total" style="padding:12px;text-align:right;font-weight:700;color:<?php echo $ut_info['accent']; ?>;">
                                <?php echo function_exists('formatCurrency') ? formatCurrency($row_total) : 'RM ' . number_format($row_total,2); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot style="background:rgba(0,0,0,.03);border-top:2px solid <?php echo $ut_info['border']; ?>;">
                        <tr>
                            <td colspan="3" style="padding:12px;text-align:right;font-weight:700;color:<?php echo $ut_info['accent']; ?>;">Subtotal:</td>
                            <td id="<?php echo $total_id; ?>" style="padding:12px;text-align:right;font-weight:700;font-size:1.1rem;color:<?php echo $ut_info['accent']; ?>;">
                                <?php echo function_exists('formatCurrency') ? formatCurrency(array_sum(array_map(fn($i)=>$i['unit_price']*$i['default_quantity'],$items))) : 'RM 0.00'; ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php endforeach; // categories ?>

            <!-- Per user-type subtotal -->
            <div style="background:<?php echo $ut_info['bg']; ?>;padding:16px 20px;border-radius:10px;display:flex;justify-content:space-between;align-items:center;border:1px solid <?php echo $ut_info['border']; ?>;margin-bottom:0.5rem;">
                <div style="font-weight:600;font-size:1rem;color:<?php echo $ut_info['sub_text']; ?>;">
                    <?php echo $ut_info['label']; ?> Subtotal
                    <span id="eu-count-sub-label-<?php echo $ut_key; ?>" style="font-size:.8rem;color:#888;font-weight:400;margin-left:4px;"></span>
                </div>
                <div id="eu-grand-<?php echo $ut_key; ?>" style="font-size:1.3rem;font-weight:800;color:<?php echo $ut_info['sub_text']; ?>;">RM 0.00</div>
            </div>
        </div><!-- /eu-section -->
        <?php endforeach; // user types ?>

        <!-- ── Combined grand total ───────────────────────────────────── -->
        <div id="eu-combined-grand-wrap" style="display:none;background:linear-gradient(135deg,#0070ef,#4A90E2);padding:22px 28px;border-radius:14px;box-shadow:0 6px 20px rgba(0,0,0,.15);margin-bottom:1.5rem;">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
                <div>
                    <div style="color:white;font-size:.8rem;opacity:.85;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px;">Combined Total — All User Types</div>
                    <div id="eu-combined-breakdown" style="color:rgba(255,255,255,.85);font-size:.875rem;"></div>
                </div>
                <div style="color:white;font-size:1.8rem;font-weight:800;" id="eu-combined-grand">RM 0.00</div>
            </div>
        </div>

        <!-- ── Notes ─────────────────────────────────────────────────── -->
        <div class="eu-section">
            <div class="eu-section-header"><span></span> Additional Notes</div>
            <textarea name="eu_notes" rows="4" placeholder="Vendor preferences, special requirements…"
                style="width:100%;padding:12px;font-family:Montserrat;font-size:1rem;border:1px solid #ccc;border-radius:8px;box-sizing:border-box;resize:vertical;"></textarea>
        </div>

        <!-- ── Form Actions ───────────────────────────────────────────── -->
        <div class="form-actions" style="display:flex;gap:1rem;margin-top:2rem;align-items:center;flex-wrap:wrap;">
            <button type="button" onclick="euResetForm()" class="btn btn-warning" style="flex:.8;background:#f59e0b;color:white;"> Reset</button>
            <button type="button" onclick="euSaveConfiguration()" class="btn btn-success" style="flex:1;background:#10b981;color:white;"> Save Configuration</button>
            <button type="button" onclick="euShowReport()" class="btn btn-primary" style="flex:1;background:linear-gradient(90deg,#0070ef,#4A90E2);color:white;"> View Report</button>
            <button type="button" class="btn btn-secondary" onclick="hideAllContainers()" style="flex:.6;background:#e2e8f0;color:#4a5568;"> Cancel</button>
        </div>
    </form>
</div>

<!-- Report Modal -->
<div id="eu-report-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.7);backdrop-filter:blur(5px);z-index:9999;overflow-y:auto;">
    <div style="max-width:1100px;margin:40px auto;background:white;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.3);overflow:hidden;">
        <div style="background:linear-gradient(135deg,#0070ef,#4A90E2);padding:36px 40px;color:white;display:flex;justify-content:space-between;align-items:center;">
            <div><h2 style="margin:0;font-size:1.8rem;font-weight:700;">End User Equipment Report</h2></div>
            <button onclick="euCloseReport()" style="background:rgba(255,255,255,.2);border:2px solid white;color:white;width:44px;height:44px;border-radius:50%;font-size:1.6rem;cursor:pointer;">×</button>
        </div>
        <div id="eu-report-body" style="padding:36px 40px;"></div>
        <div style="padding:20px 40px;border-top:1px solid #e0e0e0;display:flex;gap:12px;justify-content:flex-end;flex-wrap:wrap;">
            <button onclick="euExportPDFWithPrices()" class="btn btn-primary" style="background:linear-gradient(135deg,#0070ef,#4A90E2);color:white;">💰 Export PDF (With Prices)</button>
            <button onclick="euExportPDFNoPrices()" class="btn" style="background:linear-gradient(135deg,#6366f1,#818cf8);color:white;border:none;padding:10px 20px;border-radius:8px;cursor:pointer;font-family:Montserrat,sans-serif;font-weight:600;font-size:1rem;">📄 Export PDF (Without Prices)</button>
            <button onclick="euCloseReport()" class="btn btn-secondary" style="background:#6b7280;color:white;">✕ Close</button>
        </div>
    </div>
</div>

<script>
// ── State ─────────────────────────────────────────────────────────────────
window.euTypeState = {}; // { general: 20, technical: 10, ... } — 0 = deselected
const EU_MAX_USERS = <?php echo $eu_max_users; ?>;
const EU_TYPE_LABELS = <?php echo json_encode(array_map(fn($v)=>$v['label'], $eu_user_types)); ?>;
const EU_CAT_LABELS  = <?php echo json_encode(array_map(fn($v)=>$v['label'], $eu_category_labels)); ?>;

// ── Toggle a user type card ───────────────────────────────────────────────
function euToggleType(utKey) {
    const isSelected = (window.euTypeState[utKey] || 0) > 0;
    const wrap = document.getElementById('eu-count-wrap-' + utKey);
    const inp  = document.getElementById('eu-count-' + utKey);
    const card = document.getElementById('eu-card-' + utKey);

    if (isSelected) {
        // Deselect
        window.euTypeState[utKey] = 0;
        wrap.style.display = 'none';
        card.classList.remove('eu-card-selected');
        card.style.borderColor = '#e0e0e0';
        card.style.background  = 'white';
        document.getElementById('eu-section-' + utKey).style.display = 'none';
    } else {
        // Select with count = 1
        window.euTypeState[utKey] = 1;
        inp.value = 1;
        wrap.style.display = 'block';
        card.classList.add('eu-card-selected');
        card.style.borderColor = '#0070ef';
        card.style.background  = 'linear-gradient(135deg,rgba(0,112,239,.07),rgba(128,199,160,.05))';
        card.style.boxShadow   = '0 4px 14px rgba(0,112,239,.2)';
        document.getElementById('eu-section-' + utKey).style.display = 'block';
        euApplyUserCount(utKey, 1);
        euCalcAllTotals(utKey);
        // No auto-scroll — user stays at card area to enter counts
    }
    euUpdateSummary();
}

// ── User count input changed ──────────────────────────────────────────────
function euOnCountChange(utKey) {
    const inp = document.getElementById('eu-count-' + utKey);
    const count = Math.max(1, parseInt(inp.value) || 1);
    inp.value = count;
    window.euTypeState[utKey] = count;
    euUpdateSummary();
    euApplyUserCount(utKey, count);
    euCalcAllTotals(utKey);
}

// ── Apply user count multiplier to qty inputs ─────────────────────────────
function euApplyUserCount(utKey, count) {
    const section = document.getElementById('eu-section-' + utKey);
    if (!section) return;
    section.querySelectorAll('input[type="number"]').forEach(inp => {
        const def = parseInt(inp.getAttribute('data-default')) || 1;
        inp.value = def * count;
    });
    // Update count label in section header
    const lbl = document.getElementById('eu-count-label-' + utKey);
    if (lbl) lbl.textContent = count;
    const subLbl = document.getElementById('eu-count-sub-label-' + utKey);
    if (subLbl) subLbl.textContent = '(' + count + ' user' + (count > 1 ? 's' : '') + ')';
}

// ── Calc section subtotal ─────────────────────────────────────────────────
function euCalcSectionTotal(utKey, catKey) {
    const tbody  = document.getElementById('eu-tbody-' + utKey + '-' + catKey);
    const totEl  = document.getElementById('eu-total-' + utKey + '-' + catKey);
    if (!tbody) return 0;
    let sub = 0;
    tbody.querySelectorAll('tr').forEach(row => {
        const inp  = row.querySelector('input[type="number"]');
        const cell = row.querySelector('.eu-row-total');
        if (!inp || !cell) return;
        const qty   = parseFloat(inp.value) || 0;
        const price = parseFloat(inp.getAttribute('data-price')) || 0;
        const total = qty * price;
        cell.textContent = window.formatCurrency ? formatCurrency(total) : 'RM ' + total.toFixed(2);
        sub += total;
    });
    if (totEl) totEl.textContent = window.formatCurrency ? formatCurrency(sub) : 'RM ' + sub.toFixed(2);
    euCalcTypeGrand(utKey);
    return sub;
}

// ── Calc all category totals for one user type ────────────────────────────
function euCalcAllTotals(utKey) {
    Object.keys(EU_CAT_LABELS).forEach(cat => euCalcSectionTotal(utKey, cat));
}

// ── Calc per-type grand ───────────────────────────────────────────────────
function euCalcTypeGrand(utKey) {
    let grand = 0;
    Object.keys(EU_CAT_LABELS).forEach(cat => {
        const el = document.getElementById('eu-total-' + utKey + '-' + cat);
        if (el) grand += parseFloat(el.textContent.replace(/[^0-9.]/g,'')) || 0;
    });
    const el = document.getElementById('eu-grand-' + utKey);
    if (el) el.textContent = window.formatCurrency ? formatCurrency(grand) : 'RM ' + grand.toFixed(2);
    euUpdateCombinedGrand();
}

// ── Combined grand total across all selected types ────────────────────────
function euUpdateCombinedGrand() {
    const activeTypes = Object.keys(window.euTypeState).filter(k => window.euTypeState[k] > 0);
    const wrap = document.getElementById('eu-combined-grand-wrap');
    const grandEl = document.getElementById('eu-combined-grand');
    const breakEl = document.getElementById('eu-combined-breakdown');

    if (activeTypes.length < 2) { if(wrap) wrap.style.display = 'none'; return; }

    let total = 0;
    const parts = [];
    activeTypes.forEach(utKey => {
        const el = document.getElementById('eu-grand-' + utKey);
        const val = el ? (parseFloat(el.textContent.replace(/[^0-9.]/g,'')) || 0) : 0;
        total += val;
        const cnt = window.euTypeState[utKey] || 1;
        parts.push((EU_TYPE_LABELS[utKey] || utKey) + ' ×' + cnt);
    });

    if (wrap)    wrap.style.display = 'block';
    if (grandEl) grandEl.textContent = window.formatCurrency ? formatCurrency(total) : 'RM ' + total.toFixed(2);
    if (breakEl) breakEl.textContent = parts.join('  ·  ');
}

// ── Validate user count + update summary ─────────────────────────────────
function euUpdateSummary() {
    const active = Object.keys(window.euTypeState).filter(k => window.euTypeState[k] > 0);
    const total  = active.reduce((s, k) => s + (window.euTypeState[k] || 0), 0);

    // Update assigned display
    const dispEl  = document.getElementById('eu-total-assigned-display');
    const maxLabel = EU_MAX_USERS === 9999 ? '400+' : EU_MAX_USERS;
    if (dispEl) {
        dispEl.textContent = total + ' / ' + maxLabel;
        dispEl.style.color = total > EU_MAX_USERS ? '#dc3545' : (total === 0 ? '#666' : '#2e7d32');
    }

    // Warning logic
    const warnEl = document.getElementById('eu-user-count-warn');
    const warnTx = document.getElementById('eu-warn-text');
    if (total > EU_MAX_USERS) {
        warnEl.style.display = 'block';
        warnTx.textContent = `You have assigned ${total} users, which exceeds the project range (${maxLabel}). You can proceed, but please verify your headcount.`;
    } else {
        warnEl.style.display = 'none';
    }

    // Summary badge
    const summEl = document.getElementById('eu-selection-summary');
    const summTx = document.getElementById('eu-summary-text');
    if (active.length > 0 && summEl) {
        summEl.style.display = 'block';
        const parts = active.map(k => (EU_TYPE_LABELS[k] || k) + ': ' + window.euTypeState[k] + ' users');
        summTx.textContent = parts.join(' · ') + '  —  ' + total + ' total';
    } else if (summEl) {
        summEl.style.display = 'none';
    }
}

// ── Reset ─────────────────────────────────────────────────────────────────
function euResetForm() {
    if (!confirm('Reset all End User selections?')) return;
    window.euTypeState = {};
    Object.keys(EU_TYPE_LABELS).forEach(utKey => {
        const card = document.getElementById('eu-card-' + utKey);
        if (card) { card.style.borderColor='#e0e0e0'; card.style.background='white'; card.style.boxShadow='none'; }
        const wrap = document.getElementById('eu-count-wrap-' + utKey);
        if (wrap) wrap.style.display = 'none';
        const section = document.getElementById('eu-section-' + utKey);
        if (section) section.style.display = 'none';
        const inp = document.getElementById('eu-count-' + utKey);
        if (inp) inp.value = 1;
    });
    const notesTa = document.querySelector('textarea[name="eu_notes"]');
    if (notesTa) notesTa.value = '';
    document.getElementById('eu-selection-summary').style.display = 'none';
    document.getElementById('eu-user-count-warn').style.display   = 'none';
    document.getElementById('eu-combined-grand-wrap').style.display= 'none';
    euUpdateSummary();
}

// ── Collect data ──────────────────────────────────────────────────────────
function euCollectData() {
    const active = Object.keys(window.euTypeState).filter(k => window.euTypeState[k] > 0);
    const data = { types: {}, notes: '', combined_total: 0 };

    active.forEach(utKey => {
        data.types[utKey] = { user_count: window.euTypeState[utKey], categories: {} };
        Object.keys(EU_CAT_LABELS).forEach(cat => {
            const tbody = document.getElementById('eu-tbody-' + utKey + '-' + cat);
            if (!tbody) return;
            const items = [];
            tbody.querySelectorAll('tr').forEach(row => {
                const inp  = row.querySelector('input[type="number"]');
                const name = row.querySelector('td:first-child div:first-child')?.textContent.trim() || '';
                if (!inp) return;
                const qty   = parseFloat(inp.value) || 0;
                const price = parseFloat(inp.getAttribute('data-price')) || 0;
                items.push({ name, qty, price, total: qty * price });
                data.combined_total += qty * price;
            });
            data.types[utKey].categories[cat] = items;
        });
    });

    data.notes = document.querySelector('textarea[name="eu_notes"]')?.value || '';
    return data;
}

// ── Save ──────────────────────────────────────────────────────────────────
async function euSaveConfiguration() {
    const active = Object.keys(window.euTypeState).filter(k => window.euTypeState[k] > 0);
    if (active.length === 0) { alert('Please select at least one user type.'); return; }

    const projName = document.querySelector('input[name="project_name"]')?.value || '';
    const statusEl = document.getElementById('eu-save-status');
    const textEl   = document.getElementById('eu-save-status-text');
    if (!projName) {
        statusEl.style.cssText='display:block;padding:12px;border-radius:8px;background:#fff3cd;border:1px solid #ffc107;font-family:Montserrat;font-size:.95rem;font-weight:500;';
        textEl.textContent='⚠️ Please set a Project Name in Project Details first.';
        return;
    }
    statusEl.style.cssText='display:block;padding:12px;border-radius:8px;background:#e3f2fd;border:1px solid #2196F3;font-family:Montserrat;font-size:.95rem;font-weight:500;';
    textEl.textContent='💾 Saving…';

    const data = euCollectData();
    const fd   = new FormData();
    fd.append('action','save_enduser_config');
    fd.append('project_name', projName);
    fd.append('config_data', JSON.stringify(data));
    fd.append('notes', data.notes);

    try {
        const resp   = await fetch('network_includes/save_enduser_config.php', { method:'POST', body:fd });
        const result = await resp.json().catch(() => ({ success:true, message:'Saved' }));
        statusEl.style.cssText = result.success
            ? 'display:block;padding:12px;border-radius:8px;background:#e8f5e9;border:1px solid #4caf50;font-family:Montserrat;font-size:.95rem;font-weight:500;'
            : 'display:block;padding:12px;border-radius:8px;background:#ffebee;border:1px solid #f44336;font-family:Montserrat;font-size:.95rem;font-weight:500;';
        textEl.textContent = result.success ? '✅ ' + result.message : '❌ ' + result.message;
    } catch(e) {
        statusEl.style.cssText='display:block;padding:12px;border-radius:8px;background:#e8f5e9;border:1px solid #4caf50;font-family:Montserrat;font-size:.95rem;font-weight:500;';
        textEl.textContent='✅ Saved locally.';
    }
    setTimeout(() => { statusEl.style.display='none'; }, 5000);
}

// ── Report ────────────────────────────────────────────────────────────────
function euShowReport() {
    const active = Object.keys(window.euTypeState).filter(k => window.euTypeState[k] > 0);
    if (active.length === 0) { alert('Please select at least one user type.'); return; }

    const d = euCollectData();
    const projName = document.querySelector('input[name="project_name"]')?.value || '–';
    const manager  = document.querySelector('input[name="requesting_manager"]')?.value || '–';
    const depDate  = document.querySelector('input[name="deployment_date"]')?.value || '–';
    const userQty  = document.querySelector('select[name="user_quantity"]')?.value || '–';
    const fmt = v => window.formatCurrency ? formatCurrency(v) : 'RM ' + parseFloat(v).toFixed(2);

    let html = `<div style="background:linear-gradient(135deg,#f7f9fc,#e8f4f8);padding:24px;border-radius:12px;margin-bottom:28px;">
        <h3 style="margin:0 0 16px;color:#0070ef;font-size:1.3rem;border-bottom:2px solid #e0e0e0;padding-bottom:10px;">Project Information</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;">
            ${[['Project',projName],['Manager',manager],['Deployment',depDate],['Users Range',userQty]].map(([l,v])=>
            `<div><div style="font-size:.75rem;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">${l}</div><div style="font-weight:600;color:#2d3748;">${v}</div></div>`).join('')}
        </div></div>`;

    let combinedTotal = 0;
    Object.entries(d.types).forEach(([utKey, typeData]) => {
        const typeLabel = EU_TYPE_LABELS[utKey] || utKey;
        const count = typeData.user_count;
        let typeTotal = 0;
        let typeHtml  = '';

        Object.entries(typeData.categories).forEach(([cat, items]) => {
            const filled = items.filter(i => i.qty > 0);
            if (!filled.length) return;
            const sub = filled.reduce((s,i) => s+i.total, 0);
            typeTotal += sub;
            typeHtml += `<h4 style="color:#555;font-size:.9rem;margin:14px 0 8px;text-transform:uppercase;letter-spacing:.5px;">${EU_CAT_LABELS[cat]||cat}</h4>
            <table style="width:100%;border-collapse:collapse;font-size:.875rem;margin-bottom:8px;">
                <thead style="background:#f0f0f0;"><tr>
                    <th style="padding:8px;text-align:left;font-weight:600;">Item</th>
                    <th style="padding:8px;text-align:center;width:60px;font-weight:600;">Qty</th>
                    <th style="padding:8px;text-align:right;width:110px;font-weight:600;">Unit</th>
                    <th style="padding:8px;text-align:right;width:120px;font-weight:600;">Total</th>
                </tr></thead><tbody>
                ${filled.map(i=>`<tr style="border-bottom:1px solid #f0f0f0;">
                    <td style="padding:8px;color:#2d3748;">${i.name}</td>
                    <td style="padding:8px;text-align:center;">${i.qty}</td>
                    <td style="padding:8px;text-align:right;color:#666;">${fmt(i.price)}</td>
                    <td style="padding:8px;text-align:right;font-weight:600;color:#0070ef;">${fmt(i.total)}</td>
                </tr>`).join('')}
                </tbody>
            </table>`;
        });

        combinedTotal += typeTotal;
        html += `<div style="margin-bottom:24px;border:1px solid #e0e0e0;border-radius:12px;overflow:hidden;">
            <div style="background:linear-gradient(90deg,rgba(0,112,239,.08),rgba(74,144,226,.05));padding:14px 18px;border-bottom:1px solid #e0e0e0;display:flex;justify-content:space-between;align-items:center;">
                <div style="font-weight:700;font-size:1.05rem;color:#0070ef;">${typeLabel}</div>
                <div style="font-size:.82rem;color:#666;">${count} user${count>1?'s':''} · Subtotal: <strong>${fmt(typeTotal)}</strong></div>
            </div>
            <div style="padding:16px 18px;">${typeHtml}</div>
        </div>`;
    });

    const pm = combinedTotal * 0.10;
    const ct = combinedTotal * 0.15;
    html += `<div style="margin-bottom:24px;">
        <h3 style="color:#0070ef;font-size:1.15rem;margin-bottom:12px;padding-bottom:8px;border-bottom:2px solid #e0e0e0;">💰 Cost Summary</h3>
        <table style="width:100%;border-collapse:collapse;border-radius:10px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,.08);">
            <tbody>
                <tr style="background:white;border-bottom:1px solid #e0e0e0;"><td style="padding:13px;">Hardware & Software Subtotal</td><td style="padding:13px;text-align:right;color:#0070ef;font-weight:600;">${fmt(combinedTotal)}</td></tr>
                <tr style="background:#fffbf0;border-bottom:1px solid #e0e0e0;"><td style="padding:13px;color:#666;">Project Management (10%)</td><td style="padding:13px;text-align:right;color:#f59e0b;font-weight:600;">${fmt(pm)}</td></tr>
                <tr style="background:#fffbf0;border-bottom:2px solid #f59e0b;"><td style="padding:13px;color:#666;">Contingency (15%)</td><td style="padding:13px;text-align:right;color:#f59e0b;font-weight:600;">${fmt(ct)}</td></tr>
                <tr style="background:linear-gradient(135deg,#0070ef,#4A90E2);">
                    <td style="padding:18px;color:white;font-weight:700;font-size:1.1rem;">GRAND TOTAL</td>
                    <td style="padding:18px;text-align:right;color:white;font-weight:800;font-size:1.35rem;">${fmt(combinedTotal+pm+ct)}</td>
                </tr>
            </tbody>
        </table></div>`;

    if (d.notes) html += `<div><h3 style="color:#0070ef;font-size:1.1rem;margin-bottom:8px;"> Notes</h3><div style="background:#f7f9fc;padding:16px;border-radius:10px;line-height:1.8;color:#2d3748;">${d.notes.replace(/\n/g,'<br>')}</div></div>`;

    document.getElementById('eu-report-body').innerHTML = html;
    document.getElementById('eu-report-modal').style.display = 'block';
}

function euCloseReport() { document.getElementById('eu-report-modal').style.display = 'none'; }

function euExportPDFWithPrices() {
    const { jsPDF } = window.jspdf;
    if (!jsPDF) { alert('jsPDF not loaded'); return; }
    const d = euCollectData();
    const projName = document.querySelector('input[name="project_name"]')?.value || 'Project';
    const doc = new jsPDF({ orientation:'p', unit:'mm', format:'a4' });
    const fmt = v => window.formatCurrency ? formatCurrency(v) : 'RM ' + parseFloat(v).toFixed(2);

    doc.setFontSize(16); doc.setTextColor(0,112,239);
    doc.text('End User Equipment Report', 14, 18);
    doc.setFontSize(10); doc.setTextColor(100);
    doc.text('Project: ' + projName, 14, 26);
    doc.text('Generated: ' + new Date().toLocaleDateString(), 14, 31);

    let y = 40;
    Object.entries(d.types).forEach(([utKey, typeData]) => {
        if (y > 250) { doc.addPage(); y = 20; }
        doc.setFontSize(12); doc.setTextColor(0,112,239);
        doc.text((EU_TYPE_LABELS[utKey]||utKey) + ' (' + typeData.user_count + ' users)', 14, y); y += 6;
        Object.entries(typeData.categories).forEach(([cat, items]) => {
            const filled = items.filter(i => i.qty > 0);
            if (!filled.length) return;
            if (y > 250) { doc.addPage(); y = 20; }
            doc.autoTable({ startY:y, head:[[EU_CAT_LABELS[cat]||cat,'Qty','Unit Price','Total']],
                body: filled.map(i=>[i.name,i.qty,fmt(i.price),fmt(i.total)]),
                theme:'striped', headStyles:{fillColor:[74,144,226],fontSize:8}, bodyStyles:{fontSize:8}, margin:{left:14,right:14} });
            y = doc.lastAutoTable.finalY + 6;
        });
    });
    doc.save('End_User_Report_With_Prices_' + projName.replace(/\s+/g,'_') + '.pdf');
}

function euExportPDFNoPrices() {
    const { jsPDF } = window.jspdf;
    if (!jsPDF) { alert('jsPDF not loaded'); return; }
    const d = euCollectData();
    const projName = document.querySelector('input[name="project_name"]')?.value || 'Project';
    const doc = new jsPDF({ orientation:'p', unit:'mm', format:'a4' });

    doc.setFontSize(16); doc.setTextColor(0,112,239);
    doc.text('End User Equipment Report', 14, 18);
    doc.setFontSize(10); doc.setTextColor(100);
    doc.text('Project: ' + projName, 14, 26);
    doc.text('Generated: ' + new Date().toLocaleDateString(), 14, 31);

    let y = 40;
    Object.entries(d.types).forEach(([utKey, typeData]) => {
        if (y > 250) { doc.addPage(); y = 20; }
        doc.setFontSize(12); doc.setTextColor(0,112,239);
        doc.text((EU_TYPE_LABELS[utKey]||utKey) + ' (' + typeData.user_count + ' users)', 14, y); y += 6;
        Object.entries(typeData.categories).forEach(([cat, items]) => {
            const filled = items.filter(i => i.qty > 0);
            if (!filled.length) return;
            if (y > 250) { doc.addPage(); y = 20; }
            doc.autoTable({ startY:y, head:[[EU_CAT_LABELS[cat]||cat,'Qty']],
                body: filled.map(i=>[i.name,i.qty]),
                theme:'striped', headStyles:{fillColor:[74,144,226],fontSize:8}, bodyStyles:{fontSize:8}, margin:{left:14,right:14} });
            y = doc.lastAutoTable.finalY + 6;
        });
    });
    doc.save('End_User_Report_Without_Prices_' + projName.replace(/\s+/g,'_') + '.pdf');
}

function euFmt(v) { return window.formatCurrency ? formatCurrency(v) : 'RM ' + parseFloat(v||0).toFixed(2); }
</script>

<style>
.eu-section { background:white; border-radius:14px; padding:24px 28px; margin-bottom:1.75rem; box-shadow:0 4px 16px rgba(0,0,0,.07); transition:box-shadow .3s; }
.eu-section:hover { box-shadow:0 6px 24px rgba(0,112,239,.1); }
.eu-section-header { display:flex; align-items:center; gap:10px; font-size:1.1rem; font-weight:700; color:#0070ef; margin-bottom:1.25rem; padding-bottom:10px; border-bottom:2px solid #e0e0e0; position:relative; }
.eu-section-header::after { content:''; position:absolute; bottom:-2px; left:0; width:70px; height:2px; background:linear-gradient(90deg,#0070ef,#80c7a0); transition:width .3s; }
.eu-section:hover .eu-section-header::after { width:140px; }
.eu-type-card:hover { border-color:#0070ef !important; transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,112,239,.15) !important; }
.eu-usertype-section table tbody tr:hover { background:rgba(74,144,226,.04) !important; }
</style>