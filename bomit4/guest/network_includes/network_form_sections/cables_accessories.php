<?php
// includes/network_form_sections/cables_accessories.php
// Cable and Accessories Section - Database-Driven

// Fetch accessories from database
$accessories_query = "SELECT * FROM network_cables_accessories WHERE category = 'accessory' AND is_active = 1 ORDER BY display_order";
$accessories_result = $conn->query($accessories_query);

// Fetch cables from database
$cables_query = "SELECT * FROM network_cables_accessories WHERE category = 'cable' AND is_active = 1 ORDER BY display_order";
$cables_result = $conn->query($cables_query);
?>

<div class="question-group" style="margin-top: 2rem; margin-bottom: 2rem;">
    <label style="font-size: 1.125rem; font-weight: 600; display:block; margin-bottom: 0.25rem;">
        Cable and Accessories
    </label>
    <p style="font-size: 0.875rem; color: #666; margin-top: 0; margin-bottom: 1rem;">
        General accessories needed regardless of site type (e.g., patch panels) and comprehensive cable inventory.
    </p>

    <h4 style="font-size: 1rem; font-weight: 600; margin-bottom: 1rem; padding: 10px; background-color: #e3f2fd; border-left: 4px solid #2196F3; border-radius: 4px;">General Accessories</h4>
    
    <table style="width: 100%; border-collapse: collapse; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-bottom: 1rem;">
        <thead style="background: linear-gradient(90deg, #4A90E2 0%, #357ABD 100%); color: white;">
            <tr>
                <th style="padding: 12px; text-align: left;">Description/Part Number</th>
                <th style="padding: 12px; text-align: center; width: 100px;">Quantity</th>
                <th style="padding: 12px; text-align: right; width: 120px;">Unit Price</th>
                <th style="padding: 12px; text-align: right; width: 120px;">Total</th>
                <th style="padding: 12px; text-align: center; width: 80px;">Action</th>
            </tr>
        </thead>
        <tbody id="general-accessories-list" style="background-color: white;">
            <?php while ($accessory = $accessories_result->fetch_assoc()): ?>
            <tr style="border-bottom: 1px solid #e0e0e0;">
                <td style="padding: 12px;">
                    <input type="text" name="generalAccessoryItem[]" 
                        value="<?php echo htmlspecialchars($accessory['item_name']); ?><?php echo $accessory['item_description'] ? ' (' . htmlspecialchars($accessory['item_description']) . ')' : ''; ?>" 
                        data-price="<?php echo $accessory['unit_price']; ?>"
                        style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-family: Montserrat;">
                </td>
                <td style="padding: 12px; text-align: center;">
                    <input type="number" name="generalAccessoryQty[]" min="0" 
                        value="<?php echo $accessory['default_quantity']; ?>" 
                        onchange="updateCablesAccessoriesPricing()"
                        style="width: 80px; text-align: center; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                </td>
                <td style="padding: 12px; text-align: right; font-weight: bold; color: #0070ef;">
                    $<?php echo number_format($accessory['unit_price'], 2); ?>
                </td>
                <td style="padding: 12px; text-align: right; font-weight: bold; color: #0070ef;" class="accessory-row-total">
                    $<?php echo number_format($accessory['unit_price'] * $accessory['default_quantity'], 2); ?>
                </td>
                <td style="padding: 12px; text-align: center;">
                    <button type="button" onclick="removeCableRow(this)" 
                        style="background-color: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">✖</button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot style="background: linear-gradient(90deg, rgba(74, 144, 226, 0.1) 0%, rgba(53, 122, 189, 0.1) 100%); border-top: 2px solid #4A90E2;">
            <tr>
                <td colspan="3" style="padding: 12px; text-align: right; font-weight: bold; color: #4A90E2;">Accessories Subtotal:</td>
                <td id="accessories-subtotal" style="padding: 12px; text-align: right; font-weight: bold; font-size: 1.1rem; color: #4A90E2;">$0.00</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    <button type="button" onclick="addAccessoryRow()" 
        style="background-color: #28a745; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; margin-bottom: 2rem;">
        ➕ Add Accessory
    </button>

    <h4 style="font-size: 1rem; font-weight: 600; margin-bottom: 1rem; padding: 10px; background-color: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
        Detailed Cable Inventory
    </h4>
    <p style="font-size: 0.875rem; color: #856404; background-color: #fff3cd; padding: 10px; border-radius: 4px; margin-bottom: 1rem;">
        Adjust the default quantities for your specific project needs. You can also add or remove items.
    </p>

    <table style="width: 100%; border-collapse: collapse; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-bottom: 1rem;">
        <thead style="background: linear-gradient(90deg, #4A90E2 0%, #357ABD 100%); color: white;">
            <tr>
                <th style="padding: 12px; text-align: left;">Cable Type/Length/Connectors</th>
                <th style="padding: 12px; text-align: center; width: 100px;">Quantity</th>
                <th style="padding: 12px; text-align: right; width: 120px;">Unit Price</th>
                <th style="padding: 12px; text-align: right; width: 120px;">Total</th>
                <th style="padding: 12px; text-align: center; width: 80px;">Action</th>
            </tr>
        </thead>
        <tbody id="detailed-cable-list" style="background-color: white;">
            <?php while ($cable = $cables_result->fetch_assoc()): ?>
            <tr style="border-bottom: 1px solid #e0e0e0;">
                <td style="padding: 12px;">
                    <input type="text" name="cableItem[]" 
                        value="<?php echo htmlspecialchars($cable['item_description']); ?> (<?php echo htmlspecialchars($cable['item_name']); ?>)" 
                        data-price="<?php echo $cable['unit_price']; ?>"
                        style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-family: Montserrat;">
                </td>
                <td style="padding: 12px; text-align: center;">
                    <input type="number" name="cableQty[]" min="0" 
                        value="<?php echo $cable['default_quantity']; ?>" 
                        onchange="updateCablesAccessoriesPricing()"
                        style="width: 80px; text-align: center; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                </td>
                <td style="padding: 12px; text-align: right; font-weight: bold; color: #0070ef;">
                    $<?php echo number_format($cable['unit_price'], 2); ?>
                </td>
                <td style="padding: 12px; text-align: right; font-weight: bold; color: #0070ef;" class="cable-row-total">
                    $<?php echo number_format($cable['unit_price'] * $cable['default_quantity'], 2); ?>
                </td>
                <td style="padding: 12px; text-align: center;">
                    <button type="button" onclick="removeCableRow(this)" 
                        style="background-color: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">✖</button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot style="background: linear-gradient(90deg, rgba(74, 144, 226, 0.1) 0%, rgba(53, 122, 189, 0.1) 100%); border-top: 2px solid #4A90E2;">
            <tr>
                <td colspan="3" style="padding: 12px; text-align: right; font-weight: bold; color: #4A90E2;">Cables Subtotal:</td>
                <td id="cables-subtotal" style="padding: 12px; text-align: right; font-weight: bold; font-size: 1.1rem; color: #4A90E2;">$0.00</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    <button type="button" onclick="addCableRow()" 
        style="background-color: #28a745; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; margin-bottom: 1.5rem;">
        ➕ Add Custom Cable
    </button>

    <!-- Grand Total Section -->
    <div style="background: linear-gradient(135deg, #0070ef 0%, #4A90E2 100%); padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); margin-top: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div style="color: white; font-size: 1.3rem; font-weight: bold;">
                Total Cable & Accessories:
            </div>
            <div id="cables-accessories-grand-total" style="color: white; font-size: 1.5rem; font-weight: bold;">
                $0.00
            </div>
        </div>
    </div>
</div>

<script>
// Format currency - same as infrastructure_config.php
function formatCurrency(value) {
    return '$' + parseFloat(value).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// Main pricing calculation function - similar to updateNetworkConfigPricing()
function updateCablesAccessoriesPricing() {
    let accessoriesSubtotal = 0;
    let cablesSubtotal = 0;
    
    // Calculate Accessories Subtotal
    const accessoryRows = document.querySelectorAll('#general-accessories-list tr');
    accessoryRows.forEach(row => {
        const qtyInput = row.querySelector('input[name="generalAccessoryQty[]"]');
        const itemInput = row.querySelector('input[name="generalAccessoryItem[]"]');
        const totalCell = row.querySelector('.accessory-row-total');
        
        if (qtyInput && itemInput && totalCell) {
            const qty = parseFloat(qtyInput.value) || 0;
            const price = parseFloat(itemInput.getAttribute('data-price')) || 0;
            const rowTotal = qty * price;
            
            totalCell.textContent = formatCurrency(rowTotal);
            accessoriesSubtotal += rowTotal;
        }
    });
    
    // Calculate Cables Subtotal
    const cableRows = document.querySelectorAll('#detailed-cable-list tr');
    cableRows.forEach(row => {
        const qtyInput = row.querySelector('input[name="cableQty[]"]');
        const itemInput = row.querySelector('input[name="cableItem[]"]');
        const totalCell = row.querySelector('.cable-row-total');
        
        if (qtyInput && itemInput && totalCell) {
            const qty = parseFloat(qtyInput.value) || 0;
            const price = parseFloat(itemInput.getAttribute('data-price')) || 0;
            const rowTotal = qty * price;
            
            totalCell.textContent = formatCurrency(rowTotal);
            cablesSubtotal += rowTotal;
        }
    });
    
    // Update subtotals
    document.getElementById('accessories-subtotal').textContent = formatCurrency(accessoriesSubtotal);
    document.getElementById('cables-subtotal').textContent = formatCurrency(cablesSubtotal);
    
    // Update grand total
    const grandTotal = accessoriesSubtotal + cablesSubtotal;
    document.getElementById('cables-accessories-grand-total').textContent = formatCurrency(grandTotal);
}

// Add new accessory row
function addAccessoryRow() {
    const tbody = document.getElementById('general-accessories-list');
    const newRow = document.createElement('tr');
    newRow.style.borderBottom = '1px solid #e0e0e0';
    newRow.innerHTML = `
        <td style="padding: 12px;">
            <input type="text" name="generalAccessoryItem[]" value="New Accessory" 
                data-price="0.00"
                style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-family: Montserrat;">
        </td>
        <td style="padding: 12px; text-align: center;">
            <input type="number" name="generalAccessoryQty[]" min="0" value="1" onchange="updateCablesAccessoriesPricing()"
                style="width: 80px; text-align: center; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </td>
        <td style="padding: 12px; text-align: right; font-weight: bold; color: #0070ef;">$0.00</td>
        <td style="padding: 12px; text-align: right; font-weight: bold; color: #0070ef;" class="accessory-row-total">$0.00</td>
        <td style="padding: 12px; text-align: center;">
            <button type="button" onclick="removeCableRow(this)" 
                style="background-color: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">✖</button>
        </td>
    `;
    tbody.appendChild(newRow);
    updateCablesAccessoriesPricing();
}

// Add new cable row
function addCableRow() {
    const tbody = document.getElementById('detailed-cable-list');
    const newRow = document.createElement('tr');
    newRow.style.borderBottom = '1px solid #e0e0e0';
    newRow.innerHTML = `
        <td style="padding: 12px;">
            <input type="text" name="cableItem[]" value="Custom Cable" 
                data-price="0.00"
                style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-family: Montserrat;">
        </td>
        <td style="padding: 12px; text-align: center;">
            <input type="number" name="cableQty[]" min="0" value="1" onchange="updateCablesAccessoriesPricing()"
                style="width: 80px; text-align: center; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </td>
        <td style="padding: 12px; text-align: right; font-weight: bold; color: #0070ef;">$0.00</td>
        <td style="padding: 12px; text-align: right; font-weight: bold; color: #0070ef;" class="cable-row-total">$0.00</td>
        <td style="padding: 12px; text-align: center;">
            <button type="button" onclick="removeCableRow(this)" 
                style="background-color: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">✖</button>
        </td>
    `;
    tbody.appendChild(newRow);
    updateCablesAccessoriesPricing();
}

// Remove row
function removeCableRow(button) {
    const row = button.closest('tr');
    row.remove();
    updateCablesAccessoriesPricing();
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCablesAccessoriesPricing();
});
</script>

<style>
.question-group table tbody tr:hover {
    background: linear-gradient(90deg, rgba(74, 144, 226, 0.05) 0%, rgba(53, 122, 189, 0.05) 100%) !important;
}
</style>