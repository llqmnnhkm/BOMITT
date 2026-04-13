<?php
// guest/conference_includes/conference_form_sections/conference_room_info.php
// Multi-select room sizes with quantity input per room
// Meeting Type and Installation Type removed
?>

<!-- ── Info Banner ───────────────────────────────────────────────────── -->
<div style="background:linear-gradient(135deg,#e3f2fd 0%,#f3e5f5 100%); padding:15px 20px; border-radius:12px; margin-bottom:1.5rem; border-left:4px solid #80c7a0; display:flex; align-items:center; gap:1rem;">
    <div style="font-size:1.5rem;">ℹ️</div>
    <div style="flex:1;">
        <div style="font-weight:600; color:#2e7d32; margin-bottom:4px;">Project Information</div>
        <div style="font-size:0.875rem; color:#666;">
            These details are imported from your Project Details.
            <a href="project_details.php" style="color:#80c7a0; font-weight:600; text-decoration:none;">Click here to edit →</a>
        </div>
    </div>
</div>

<!-- ── Read-only project fields ─────────────────────────────────────── -->
<div style="display:flex; flex-wrap:wrap; gap:1.5rem; margin-bottom:2rem;">
    <?php
    $fields = [
        'Project Name'              => ['name'=>'project_name',      'type'=>'text',   'value'=>$project_name       ?? ''],
        'Requesting Manager'        => ['name'=>'requesting_manager','type'=>'text',   'value'=>$requesting_manager ?? ''],
        'Project Duration (Months)' => ['name'=>'project_duration',  'type'=>'number', 'value'=>$project_duration   ?? ''],
        'Deployment Date'           => ['name'=>'deployment_date',   'type'=>'date',   'value'=>$deployment_date    ?? ''],
    ];
    foreach ($fields as $label => $field):
    ?>
    <div style="flex:1; min-width:220px;">
        <label style="font-weight:600; display:block; margin-bottom:0.5rem; color:#666;"><?php echo $label; ?></label>
        <input type="<?php echo $field['type']; ?>"
               name="<?php echo $field['name']; ?>"
               value="<?php echo htmlspecialchars($field['value']); ?>"
               disabled
               style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; font-family:Montserrat; font-size:1rem; background:#f5f5f5; color:#666; cursor:not-allowed;">
    </div>
    <?php endforeach; ?>
</div>

<!-- ── Room Size Multi-Selection ────────────────────────────────────── -->
<div class="question-group" style="margin-bottom:2rem;">
    <label style="font-size:1.125rem; font-weight:600; display:block; margin-bottom:0.25rem;">
        Conference Room Configuration <span style="color:#ef4444;">*</span>
    </label>
    <p style="font-size:0.875rem; color:#666; margin:0 0 1.25rem;">
        Select one or more room sizes and enter how many rooms of each type are needed.
    </p>

    <!-- Room size cards — click to select, qty input appears inside -->
    <div style="display:flex; gap:1rem; flex-wrap:wrap; margin-bottom:1.25rem;">
        <?php
        $sizes = [
            'small'  => ['label'=>'Small',  'sub'=>'4–6 people',  'icon'=>'🪑'],
            'medium' => ['label'=>'Medium', 'sub'=>'8–12 people', 'icon'=>'👥'],
            'large'  => ['label'=>'Large',  'sub'=>'15+ people',  'icon'=>'🏛️'],
        ];
        foreach ($sizes as $size_key => $info):
        ?>
        <div class="conf-room-card" id="conf-card-<?php echo $size_key; ?>"
             data-size="<?php echo $size_key; ?>"
             onclick="confToggleRoom('<?php echo $size_key; ?>')"
             style="flex:1; min-width:150px; padding:20px; border:2px solid #e0e0e0;
                    border-radius:12px; text-align:center; cursor:pointer;
                    transition:all 0.3s ease; background:white; user-select:none;">

            <div style="font-size:2.5rem; margin-bottom:8px; pointer-events:none;">
                <?php echo $info['icon']; ?>
            </div>
            <div style="font-weight:700; font-size:1.1rem; color:#2d3748; pointer-events:none;">
                <?php echo $info['label']; ?>
            </div>
            <div style="font-size:0.875rem; color:#666; margin-top:4px; pointer-events:none;">
                <?php echo $info['sub']; ?>
            </div>

            <!-- Qty input — hidden until card is selected -->
            <div id="conf-qty-wrap-<?php echo $size_key; ?>"
                 style="display:none; margin-top:14px; padding-top:14px; border-top:1px solid #e0e0e0;"
                 onclick="event.stopPropagation();">
                <label style="font-size:0.78rem; font-weight:600; color:#2e7d32; display:block; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px;">
                    How many rooms?
                </label>
                <div style="display:flex; align-items:center; justify-content:center; gap:8px;">
                    <button type="button"
                        onclick="confChangeQty('<?php echo $size_key; ?>', -1)"
                        style="width:30px; height:30px; border-radius:50%; border:2px solid #80c7a0;
                               background:white; color:#2e7d32; font-size:1.1rem; font-weight:700;
                               cursor:pointer; display:flex; align-items:center; justify-content:center;
                               line-height:1;">−</button>
                    <input type="number"
                           id="conf-qty-<?php echo $size_key; ?>"
                           name="conf_qty_<?php echo $size_key; ?>"
                           value="1" min="0" max="20"
                           onchange="confOnQtyChange('<?php echo $size_key; ?>')"
                           oninput="confOnQtyChange('<?php echo $size_key; ?>')"
                           style="width:50px; text-align:center; padding:6px; border:2px solid #80c7a0;
                                  border-radius:8px; font-family:Montserrat; font-size:1.1rem;
                                  font-weight:700; color:#2e7d32; background:white;">
                    <button type="button"
                        onclick="confChangeQty('<?php echo $size_key; ?>', 1)"
                        style="width:30px; height:30px; border-radius:50%; border:2px solid #80c7a0;
                               background:white; color:#2e7d32; font-size:1.1rem; font-weight:700;
                               cursor:pointer; display:flex; align-items:center; justify-content:center;
                               line-height:1;">+</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Summary badge — shows selected rooms + total rooms count -->
    <div id="conf-selection-summary" style="display:none; padding:12px 16px; background:#e8f5e9;
         border-radius:8px; border-left:4px solid #4caf50; margin-bottom:0.5rem;">
        <span style="font-size:1rem;">✅</span>
        <span id="conf-summary-text" style="font-weight:600; color:#1b5e20; margin-left:8px;"></span>
    </div>

    <!-- Validation warning if nothing selected -->
    <div id="conf-no-selection-warn" style="display:none; padding:10px 16px; background:#fff3cd;
         border-radius:8px; border-left:4px solid #ffc107; font-size:0.875rem; color:#856404;">
        ⚠️ Please select at least one room size to continue.
    </div>
</div>

<style>
.conf-room-card:hover {
    border-color: #80c7a0 !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(128,199,160,0.25);
}
.conf-room-card.selected {
    border-color: #4caf50 !important;
    background: linear-gradient(135deg, rgba(128,199,160,0.12), rgba(76,175,80,0.06)) !important;
    box-shadow: 0 4px 16px rgba(76,175,80,0.25) !important;
}
.conf-room-card.selected:hover { transform: translateY(-2px); }
</style>

<script>
// ── State ─────────────────────────────────────────────────────────────────
// confRoomState[size] = qty (0 = deselected)
window.confRoomState = { small: 0, medium: 0, large: 0 };

// ── Toggle a room card on/off ─────────────────────────────────────────────
function confToggleRoom(size) {
    const card    = document.getElementById('conf-card-' + size);
    const qtyWrap = document.getElementById('conf-qty-wrap-' + size);
    const qtyInp  = document.getElementById('conf-qty-' + size);
    const isSelected = window.confRoomState[size] > 0;

    if (isSelected) {
        // Deselect
        window.confRoomState[size] = 0;
        qtyInp.value = 1; // reset for next time
        qtyWrap.style.display = 'none';
        card.classList.remove('selected');
    } else {
        // Select with qty = 1
        window.confRoomState[size] = 1;
        qtyInp.value = 1;
        qtyWrap.style.display = 'block';
        card.classList.add('selected');
    }
    confUpdateAll();
}

// ── +/− stepper buttons ───────────────────────────────────────────────────
function confChangeQty(size, delta) {
    const inp = document.getElementById('conf-qty-' + size);
    const newVal = Math.max(0, Math.min(20, parseInt(inp.value || 1) + delta));
    inp.value = newVal;
    confOnQtyChange(size);
}

// ── Qty input changed ─────────────────────────────────────────────────────
function confOnQtyChange(size) {
    const inp = document.getElementById('conf-qty-' + size);
    const qty = parseInt(inp.value) || 0;
    inp.value = Math.max(0, qty);
    window.confRoomState[size] = parseInt(inp.value);

    // If user typed 0, deselect the card
    if (window.confRoomState[size] === 0) {
        document.getElementById('conf-card-' + size).classList.remove('selected');
        document.getElementById('conf-qty-wrap-' + size).style.display = 'none';
    }
    confUpdateAll();
}

// ── Update summary + equipment sections ──────────────────────────────────
function confUpdateAll() {
    const sizes   = ['small','medium','large'];
    const labels  = { small:'Small', medium:'Medium', large:'Large' };
    const active  = sizes.filter(s => window.confRoomState[s] > 0);

    // Summary badge
    const summaryEl = document.getElementById('conf-selection-summary');
    const textEl    = document.getElementById('conf-summary-text');
    const warnEl    = document.getElementById('conf-no-selection-warn');

    if (active.length > 0) {
        const parts = active.map(s => `${labels[s]} ×${window.confRoomState[s]}`).join(' · ');
        const totalRooms = active.reduce((sum, s) => sum + window.confRoomState[s], 0);
        textEl.textContent = parts + ` — ${totalRooms} room${totalRooms > 1 ? 's' : ''} total`;
        summaryEl.style.display = 'block';
        warnEl.style.display    = 'none';
    } else {
        summaryEl.style.display = 'none';
    }

    // Show / hide equipment sections and apply multiplier
    sizes.forEach(size => {
        const qty     = window.confRoomState[size];
        const section = document.getElementById('conf_equip_' + size);
        if (!section) return;

        if (qty > 0) {
            section.classList.remove('hidden');
            section.style.display = 'block';
            confApplyMultiplier(size, qty);
            if (typeof calculateConferenceTotals === 'function') {
                calculateConferenceTotals(size);
            }
        } else {
            section.classList.add('hidden');
            section.style.display = 'none';
        }
    });

    // No auto-scroll — user stays at cards to set quantities
}

// ── Apply multiplier to all qty inputs in a section ───────────────────────
// Sets each input to default_quantity × room_count
// Stores data-default-qty on the input so we always multiply from the original
function confApplyMultiplier(size, multiplier) {
    const section = document.getElementById('conf_equip_' + size);
    if (!section) return;

    section.querySelectorAll('input[type="number"]').forEach(inp => {
        // Store original DB default qty on first use
        if (!inp.hasAttribute('data-default-qty')) {
            inp.setAttribute('data-default-qty', inp.value);
        }
        const defaultQty = parseInt(inp.getAttribute('data-default-qty')) || 1;
        inp.value = defaultQty * multiplier;
    });

    // Update the multiplier badge label in the section header
    const badge = document.getElementById('conf-multiplier-badge-' + size);
    if (badge) {
        badge.textContent = '×' + multiplier + ' room' + (multiplier > 1 ? 's' : '');
        badge.style.display = multiplier > 1 ? 'inline-block' : 'none';
    }
}
</script>