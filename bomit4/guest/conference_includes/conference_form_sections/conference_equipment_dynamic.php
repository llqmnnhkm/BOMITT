<?php
// guest/conference_includes/conference_form_sections/conference_equipment_dynamic.php
// DB-driven equipment table per room size — mirrors equipment_dynamic.php pattern

$conf_room_sizes = [
    'small'  => 'Small (4–6 people)',
    'medium' => 'Medium (8–12 people)',
    'large'  => 'Large (15+ people)',
];

foreach ($conf_room_sizes as $room_size_code => $room_size_label):

    // Fetch AV + other items for this room size
    $equip_query = "SELECT * FROM conference_equipment 
                    WHERE room_size = ? AND equipment_category IN ('av','furniture','other') AND is_active = 1 
                    ORDER BY display_order";
    $equip_stmt = $conn->prepare($equip_query);
    $equip_stmt->bind_param("s", $room_size_code);
    $equip_stmt->execute();
    $equip_result = $equip_stmt->get_result();

    // Fetch connectivity items
    $conn_query = "SELECT * FROM conference_equipment 
                   WHERE room_size = ? AND equipment_category = 'connectivity' AND is_active = 1 
                   ORDER BY display_order";
    $conn_stmt = $conn->prepare($conn_query);
    $conn_stmt->bind_param("s", $room_size_code);
    $conn_stmt->execute();
    $conn_result = $conn_stmt->get_result();

    $section_id     = 'conf_equip_' . $room_size_code;
    $equip_tbody_id = 'conf-equip-tbody-' . $room_size_code;
    $conn_tbody_id  = 'conf-conn-tbody-' . $room_size_code;
    $total_id       = 'conf-total-' . $room_size_code;
?>

<div id="<?php echo $section_id; ?>" class="conf-size-section hidden">
    <div class="question-group" style="margin-bottom: 2rem;">
        <label style="font-size: 1.125rem; font-weight: 600; display:block; margin-bottom: 0.25rem;">
            Equipment: <?php echo htmlspecialchars($room_size_label); ?>
        </label>
        <p style="font-size: 0.875rem; color: #666; margin: 0 0 1rem 0;">
            Recommended equipment for a <?php echo strtolower($room_size_label); ?> conference room. Adjust quantities as needed.
        </p>

        <?php if ($equip_result->num_rows > 0): ?>
        <!-- AV / General Equipment Table -->
        <h4 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem; padding: 8px 12px;
                   background: #e8f5e9; border-left: 4px solid #80c7a0; border-radius: 4px;">
            🎬 AV & Room Equipment
        </h4>
        <table style="width:100%; border-collapse:collapse; border-radius:12px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.1); margin-bottom:1.5rem;">
            <thead style="background: linear-gradient(90deg, #80c7a0 0%, #4caf50 100%); color:white;">
                <tr>
                    <th style="padding:12px; text-align:left;">Equipment Description</th>
                    <th style="padding:12px; text-align:center; width:100px;">Quantity</th>
                    <th style="padding:12px; text-align:right; width:120px;">Unit Price</th>
                    <th style="padding:12px; text-align:right; width:120px;">Total</th>
                </tr>
            </thead>
            <tbody id="<?php echo $equip_tbody_id; ?>" style="background:white;">
                <?php while ($item = $equip_result->fetch_assoc()):
                    $row_total = $item['unit_price'] * $item['default_quantity'];
                ?>
                <tr style="border-bottom:1px solid #e0e0e0;">
                    <td style="padding:12px;">
                        <?php echo htmlspecialchars($item['item_name']); ?>
                        <?php if ($item['item_description']): ?>
                            <br><small style="color:#666;">(<?php echo htmlspecialchars($item['item_description']); ?>)</small>
                        <?php endif; ?>
                    </td>
                    <td style="padding:12px; text-align:center;">
                        <input type="number"
                            name="<?php echo $room_size_code; ?>_equip_<?php echo $item['id']; ?>"
                            min="0"
                            value="<?php echo $item['default_quantity']; ?>"
                            data-price="<?php echo $item['unit_price']; ?>"
                            onchange="calculateConferenceTotals('<?php echo $room_size_code; ?>')"
                            style="width:80px; text-align:center; padding:6px; border-radius:6px; border:1px solid #ccc;">
                    </td>
                    <td style="padding:12px; text-align:right; color:#666;">
                        $<?php echo number_format($item['unit_price'], 2); ?>
                    </td>
                    <td class="conf-row-total" style="padding:12px; text-align:right; font-weight:bold; color:#80c7a0;">
                        $<?php echo number_format($row_total, 2); ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <?php if ($conn_result->num_rows > 0): ?>
        <!-- Connectivity Table -->
        <h4 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem; padding: 8px 12px;
                   background: #e3f2fd; border-left: 4px solid #2196F3; border-radius: 4px;">
            🔌 Connectivity Equipment
        </h4>
        <table style="width:100%; border-collapse:collapse; border-radius:12px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.1); margin-bottom:1.5rem;">
            <thead style="background: linear-gradient(90deg, #2196F3 0%, #0070ef 100%); color:white;">
                <tr>
                    <th style="padding:12px; text-align:left;">Connectivity Item</th>
                    <th style="padding:12px; text-align:center; width:100px;">Quantity</th>
                    <th style="padding:12px; text-align:right; width:120px;">Unit Price</th>
                    <th style="padding:12px; text-align:right; width:120px;">Total</th>
                </tr>
            </thead>
            <tbody id="<?php echo $conn_tbody_id; ?>" style="background:white;">
                <?php while ($item = $conn_result->fetch_assoc()):
                    $row_total = $item['unit_price'] * $item['default_quantity'];
                ?>
                <tr style="border-bottom:1px solid #e0e0e0;">
                    <td style="padding:12px;">
                        <?php echo htmlspecialchars($item['item_name']); ?>
                        <?php if ($item['item_description']): ?>
                            <br><small style="color:#666;">(<?php echo htmlspecialchars($item['item_description']); ?>)</small>
                        <?php endif; ?>
                    </td>
                    <td style="padding:12px; text-align:center;">
                        <input type="number"
                            name="<?php echo $room_size_code; ?>_conn_<?php echo $item['id']; ?>"
                            min="0"
                            value="<?php echo $item['default_quantity']; ?>"
                            data-price="<?php echo $item['unit_price']; ?>"
                            onchange="calculateConferenceTotals('<?php echo $room_size_code; ?>')"
                            style="width:80px; text-align:center; padding:6px; border-radius:6px; border:1px solid #ccc;">
                    </td>
                    <td style="padding:12px; text-align:right; color:#666;">
                        $<?php echo number_format($item['unit_price'], 2); ?>
                    </td>
                    <td class="conf-row-total" style="padding:12px; text-align:right; font-weight:bold; color:#0070ef;">
                        $<?php echo number_format($row_total, 2); ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <!-- Grand Total -->
        <div style="background: linear-gradient(135deg, #80c7a0 0%, #0070ef 100%); padding:20px; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.2);">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div style="color:white; font-size:1.2rem; font-weight:bold;">Total Equipment & Connectivity:</div>
                <div id="<?php echo $total_id; ?>" style="color:white; font-size:1.5rem; font-weight:bold;">$0.00</div>
            </div>
        </div>
    </div>
</div>

<?php endforeach; ?>

<script>
function calculateConferenceTotals(roomSize) {
    let grandTotal = 0;
    const sectionId = 'conf_equip_' + roomSize;
    const section = document.getElementById(sectionId);
    if (!section) return;

    section.querySelectorAll('input[type="number"]').forEach(input => {
        const qty   = parseFloat(input.value) || 0;
        const price = parseFloat(input.getAttribute('data-price')) || 0;
        const rowTotal = qty * price;
        const totalCell = input.closest('tr')?.querySelector('.conf-row-total');
        if (totalCell) totalCell.textContent = '$' + rowTotal.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        grandTotal += rowTotal;
    });

    const totalEl = document.getElementById('conf-total-' + roomSize);
    if (totalEl) totalEl.textContent = '$' + grandTotal.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

// Show the section for the selected room size, hide others
function showConferenceEquipmentSection(roomSize) {
    document.querySelectorAll('.conf-size-section').forEach(s => {
        s.classList.add('hidden');
        s.style.display = 'none';
    });
    const target = document.getElementById('conf_equip_' + roomSize);
    if (target) {
        target.classList.remove('hidden');
        target.style.display = 'block';
        calculateConferenceTotals(roomSize);
        setTimeout(() => target.scrollIntoView({ behavior: 'smooth', block: 'start' }), 200);
    }
}

// Watch for section becoming visible via MutationObserver
document.addEventListener('DOMContentLoaded', function() {
    const observer = new MutationObserver(mutations => {
        mutations.forEach(m => {
            if (m.type === 'attributes' && m.attributeName === 'class') {
                const el = m.target;
                if (el.classList.contains('conf-size-section') && !el.classList.contains('hidden')) {
                    const roomSize = el.id.replace('conf_equip_', '');
                    calculateConferenceTotals(roomSize);
                }
            }
        });
    });
    document.querySelectorAll('.conf-size-section').forEach(s => {
        observer.observe(s, { attributes: true });
        if (!s.classList.contains('hidden')) {
            calculateConferenceTotals(s.id.replace('conf_equip_', ''));
        }
    });
});
</script>

<style>
.conf-size-section { display: block; }
.conf-size-section.hidden { display: none !important; }
</style>
