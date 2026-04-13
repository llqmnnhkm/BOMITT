<?php
// includes/network_form_sections/equipment_dynamic.php
// Universal Equipment Handler - Replaces all 10 individual equipment files
// Dynamically generates equipment sections for ALL site types from database

// Define all possible site types
$all_site_types = [
    'less_50_no_server' => 'Less than 50 Users - No Server',
    'less_50_with_server' => 'Less than 50 Users - With Server',
    '51_150_no_server' => '51-150 Users - No Server',
    '51_150_with_server' => '51-150 Users - With Server',
    '151_300_no_server' => '151-300 Users - No Server',
    '151_300_with_server' => '151-300 Users - With Server',
    '301_400_no_server' => '301-400 Users - No Server',
    '301_400_with_server' => '301-400 Users - With Server',
    'more_400_no_server' => 'More than 400 Users - No Server',
    'more_400_with_server' => 'More than 400 Users - With Server'
];

// Loop through each site type and generate its section
foreach ($all_site_types as $site_type_code => $site_type_label):
    
    // Fetch equipment items for this site type
    $equipment_query = "SELECT * FROM network_equipment WHERE site_type = ? AND equipment_category = 'equipment' AND is_active = 1 ORDER BY display_order";
    $equipment_stmt = $conn->prepare($equipment_query);
    $equipment_stmt->bind_param("s", $site_type_code);
    $equipment_stmt->execute();
    $equipment_result = $equipment_stmt->get_result();
    
    // Fetch module items for this site type
    $modules_query = "SELECT * FROM network_equipment WHERE site_type = ? AND equipment_category = 'modules' AND is_active = 1 ORDER BY display_order";
    $modules_stmt = $conn->prepare($modules_query);
    $modules_stmt->bind_param("s", $site_type_code);
    $modules_stmt->execute();
    $modules_result = $modules_stmt->get_result();
    
    // Generate unique IDs for this site type
    $section_id = 'equipment_' . $site_type_code;
    $equipment_tbody_id = 'equipment-tbody-' . str_replace('_', '-', $site_type_code);
    $modules_tbody_id = 'modules-tbody-' . str_replace('_', '-', $site_type_code);
    $grand_total_id = 'grand-total-' . str_replace('_', '-', $site_type_code);
?>

<div id="<?php echo $section_id; ?>" class="site-specific-fields hidden">
    <div class="question-group" style="margin-bottom: 2rem;">
        <label style="font-size: 1.125rem; font-weight: 600; display:block; margin-bottom: 0.25rem;">
            Equipment: <?php echo htmlspecialchars($site_type_label); ?>
        </label>
        <p style="font-size: 0.875rem; color: #666; margin-top: 0; margin-bottom: 1rem;">
            Configuration for <?php echo strtolower($site_type_label); ?>.
        </p>

        <?php if ($equipment_result->num_rows > 0): ?>
        <!-- Equipment Table -->
        <table style="width: 100%; border-collapse: collapse; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
            <thead style="background: linear-gradient(90deg, #4A90E2 0%, #357ABD 100%); color: white;">
                <tr>
                    <th style="padding: 12px; text-align: left;">Equipment Description</th>
                    <th style="padding: 12px; text-align: center; width: 100px;">Quantity</th>
                    <th style="padding: 12px; text-align: right; width: 120px;">Unit Price</th>
                    <th style="padding: 12px; text-align: right; width: 120px;">Total</th>
                </tr>
            </thead>
            <tbody id="<?php echo $equipment_tbody_id; ?>" style="background-color: white;">
                <?php while ($equipment = $equipment_result->fetch_assoc()): 
                    $row_total = $equipment['unit_price'] * $equipment['default_quantity'];
                ?>
                <tr style="border-bottom: 1px solid #e0e0e0;">
                    <td style="padding: 12px;">
                        <?php echo htmlspecialchars($equipment['item_name']); ?>
                        <?php if ($equipment['item_description']): ?>
                            <br><small style="color: #666;">(<?php echo htmlspecialchars($equipment['item_description']); ?>)</small>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 12px; text-align: center;">
                        <input type="number" 
                            name="<?php echo $site_type_code; ?>_equipment_<?php echo $equipment['id']; ?>" 
                            min="0" 
                            value="<?php echo $equipment['default_quantity']; ?>" 
                            data-price="<?php echo $equipment['unit_price']; ?>" 
                            onchange="calculateEquipmentTotals('<?php echo $site_type_code; ?>')"
                            style="width:80px; text-align:center; padding: 6px; border-radius: 6px; border: 1px solid #ccc;">
                    </td>
                    <td style="padding: 12px; text-align: right; color: #666;">
                        $<?php echo number_format($equipment['unit_price'], 2); ?>
                    </td>
                    <td class="row-total" style="padding: 12px; text-align: right; font-weight: bold; color: #0070ef;">
                        $<?php echo number_format($row_total, 2); ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div style="padding: 20px; background: #f9f9f9; border-radius: 8px; text-align: center; color: #666;">
            No equipment configured for this site type yet.
        </div>
        <?php endif; ?>

        <?php if ($modules_result->num_rows > 0): ?>
        <!-- Transceiver Modules Table -->
        <label style="font-size: 1.125rem; font-weight: 600; display:block; margin-top: 2rem; margin-bottom: 1rem;">
            Transceiver Modules
        </label>
        
        <table style="width: 100%; border-collapse: collapse; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
            <thead style="background: linear-gradient(90deg, #4A90E2 0%, #357ABD 100%); color: white;">
                <tr>
                    <th style="padding: 12px; text-align: left;">Module Description</th>
                    <th style="padding: 12px; text-align: center; width: 100px;">Quantity</th>
                    <th style="padding: 12px; text-align: right; width: 120px;">Unit Price</th>
                    <th style="padding: 12px; text-align: right; width: 120px;">Total</th>
                </tr>
            </thead>
            <tbody id="<?php echo $modules_tbody_id; ?>" style="background-color: white;">
                <?php while ($module = $modules_result->fetch_assoc()): 
                    $row_total = $module['unit_price'] * $module['default_quantity'];
                ?>
                <tr style="border-bottom: 1px solid #e0e0e0;">
                    <td style="padding: 12px;">
                        <?php echo htmlspecialchars($module['item_name']); ?>
                        <?php if ($module['item_description']): ?>
                            <br><small style="color: #666;">(<?php echo htmlspecialchars($module['item_description']); ?>)</small>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 12px; text-align: center;">
                        <input type="number" 
                            name="<?php echo $site_type_code; ?>_module_<?php echo $module['id']; ?>" 
                            min="0" 
                            value="<?php echo $module['default_quantity']; ?>" 
                            data-price="<?php echo $module['unit_price']; ?>" 
                            onchange="calculateEquipmentTotals('<?php echo $site_type_code; ?>')"
                            style="width:80px; text-align:center; padding: 6px; border-radius: 6px; border: 1px solid #ccc;">
                    </td>
                    <td style="padding: 12px; text-align: right; color: #666;">
                        $<?php echo number_format($module['unit_price'], 2); ?>
                    </td>
                    <td class="row-total" style="padding: 12px; text-align: right; font-weight: bold; color: #0070ef;">
                        $<?php echo number_format($row_total, 2); ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <!-- Grand Total Section -->
        <div style="background: linear-gradient(135deg, #0070ef 0%, #4A90E2 100%); padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); margin-top: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="color: white; font-size: 1.3rem; font-weight: bold;">
                    Total Equipment & Modules:
                </div>
                <div id="<?php echo $grand_total_id; ?>" style="color: white; font-size: 1.5rem; font-weight: bold;">
                    $0.00
                </div>
            </div>
        </div>
    </div>
</div>

<?php
endforeach; // End loop through all site types
?>

<script>
// Universal calculation function for all site types
function calculateEquipmentTotals(siteType) {
    console.log('📊 Calculating totals for:', siteType);
    
    let grandTotal = 0;
    
    // Convert site_type code to CSS ID format (underscores to hyphens)
    const siteTypeId = siteType.replace(/_/g, '-');
    
    // Calculate equipment totals
    const equipmentTbodyId = 'equipment-tbody-' + siteTypeId;
    const equipmentRows = document.querySelectorAll('#' + equipmentTbodyId + ' tr');
    
    equipmentRows.forEach(row => {
        const qtyInput = row.querySelector('input[type="number"]');
        const totalCell = row.querySelector('.row-total');
        
        if (qtyInput && totalCell) {
            const qty = parseFloat(qtyInput.value) || 0;
            const price = parseFloat(qtyInput.getAttribute('data-price')) || 0;
            const rowTotal = qty * price;
            
            totalCell.textContent = formatCurrency(rowTotal);
            grandTotal += rowTotal;
        }
    });
    
    // Calculate module totals
    const modulesTbodyId = 'modules-tbody-' + siteTypeId;
    const moduleRows = document.querySelectorAll('#' + modulesTbodyId + ' tr');
    
    moduleRows.forEach(row => {
        const qtyInput = row.querySelector('input[type="number"]');
        const totalCell = row.querySelector('.row-total');
        
        if (qtyInput && totalCell) {
            const qty = parseFloat(qtyInput.value) || 0;
            const price = parseFloat(qtyInput.getAttribute('data-price')) || 0;
            const rowTotal = qty * price;
            
            totalCell.textContent = formatCurrency(rowTotal);
            grandTotal += rowTotal;
        }
    });
    
    // Update grand total
    const grandTotalId = 'grand-total-' + siteTypeId;
    const grandTotalElement = document.getElementById(grandTotalId);
    if (grandTotalElement) {
        grandTotalElement.textContent = formatCurrency(grandTotal);
    }
    
    console.log('✅ Grand Total:', formatCurrency(grandTotal));
}

// Currency formatter helper
function formatCurrency(amount) {
    return '$' + amount.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

// Initialize totals when a section becomes visible
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Equipment Dynamic Script Loaded');
    
    // Create a MutationObserver to watch for sections becoming visible
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                const target = mutation.target;
                if (target.classList.contains('site-specific-fields') && !target.classList.contains('hidden')) {
                    // Section just became visible, calculate its totals
                    const sectionId = target.id;
                    if (sectionId && sectionId.startsWith('equipment_')) {
                        const siteType = sectionId.replace('equipment_', '');
                        console.log('👁️ Section visible:', siteType);
                        calculateEquipmentTotals(siteType);
                    }
                }
            }
        });
    });
    
    // Observe all equipment sections
    const allSections = document.querySelectorAll('.site-specific-fields[id^="equipment_"]');
    allSections.forEach(section => {
        observer.observe(section, { attributes: true });
        
        // If already visible, calculate now
        if (!section.classList.contains('hidden')) {
            const sectionId = section.id;
            const siteType = sectionId.replace('equipment_', '');
            calculateEquipmentTotals(siteType);
        }
    });
});
</script>