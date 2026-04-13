<?php
// admin/admin_includes/network_infrastructure_config.php
// Network Infrastructure Configuration Management - VIEW ONLY

// Initialize session and database
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($conn)) include dirname(dirname(__DIR__)) . '/db_connect.php';
require_once dirname(__DIR__) . '/admin_utilities.php';

// Check admin authentication
requireAdminAuth($conn);
?>

<!-- Filter Section -->
<div class="filter-section">
    <label for="infra-type-filter">Filter by Type:</label>
    <select id="infra-type-filter" onchange="filterInfrastructure()">
        <option value="">All Types</option>
        <option value="site_type">Site Type</option>
        <option value="bandwidth">Bandwidth</option>
        <option value="redundancy">Redundancy</option>
        <option value="installation">Installation</option>
    </select>
</div>

<!-- Action Bar -->
<div class="action-bar">
    <h4>Infrastructure Configuration Items</h4>
    <button class="add-btn" onclick="openConfigModal('add')">➕ Add Config Item</button>
</div>

<!-- Config Table -->
<table class="equipment-table">
    <thead>
        <tr>
            <th>Type</th>
            <th>Item Name</th>
            <th>Value</th>
            <th>Price</th>
            <th>Installation</th>
            <th>Parent Item</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody id="config-tbody">
        <?php
        // Get all config records
        $records = getAllRecords($conn, 'network_infrastructure_config', 'item_type, display_order');
        foreach ($records as $row):
        ?>
        <tr data-type="<?php echo $row['item_type']; ?>">
            <td><span style="text-transform: capitalize;"><?php echo htmlspecialchars($row['item_type']); ?></span></td>
            <td><strong><?php echo htmlspecialchars($row['item_name']); ?></strong></td>
            <td><?php echo htmlspecialchars($row['item_value']); ?></td>
            <td><span class="price-badge"><?php echo formatPrice($row['price']); ?></span></td>
            <td>
                <?php 
                if ($row['installation_type'] === 'none') {
                    echo '<span style="color: #999;">None</span>';
                } else {
                    echo '<span style="text-transform: capitalize;">' . $row['installation_type'] . '</span>: ' . formatPrice($row['installation_value']);
                }
                ?>
            </td>
            <td><?php echo $row['parent_item'] ? htmlspecialchars($row['parent_item']) : '<span style="color: #999;">-</span>'; ?></td>
            <td>
                <button class="btn-edit" onclick='editConfig(<?php echo json_encode($row); ?>)'>✏️ Edit</button>
                <button class="btn-delete" onclick="deleteConfig(<?php echo $row['id']; ?>)">🗑️ Delete</button>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Config Modal -->
<div id="config-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="config-modal-title">Add Infrastructure Config</h3>
            <button class="modal-close" onclick="closeModal('config-modal')">&times;</button>
        </div>
        <form id="config-form" onsubmit="saveConfig(event)">
            <input type="hidden" id="config-id" name="id">
            
            <div class="form-group">
                <label>Item Type *</label>
                <select id="config-type" name="type" required>
                    <option value="site_type">Site Type</option>
                    <option value="bandwidth">Bandwidth</option>
                    <option value="redundancy">Redundancy</option>
                    <option value="installation">Installation</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Item Name *</label>
                <input type="text" id="config-name" name="name" required>
            </div>
            
            <div class="form-group">
                <label>Item Value *</label>
                <input type="text" id="config-value" name="value" required>
            </div>
            
            <div class="form-group">
                <label>Price ($) *</label>
                <input type="number" id="config-price" name="price" step="0.01" min="0" required>
            </div>
            
            <div class="form-group">
                <label>Parent Item (Optional)</label>
                <input type="text" id="config-parent" name="parent" placeholder="Leave blank if none">
            </div>
            
            <div class="form-group">
                <label>Installation Type *</label>
                <select id="config-installation-type" name="installation_type" onchange="toggleInstallationValue()" required>
                    <option value="none">None</option>
                    <option value="fixed">Fixed Price</option>
                    <option value="per_point">Per Point</option>
                </select>
            </div>
            
            <div class="form-group" id="installation-value-group" style="display: none;">
                <label>Installation Value ($)</label>
                <input type="number" id="config-installation-value" name="installation_value" step="0.01" min="0">
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('config-modal')">Cancel</button>
                <button type="submit" class="btn-save">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
// Filter infrastructure
function filterInfrastructure() {
    const type = document.getElementById('infra-type-filter').value;
    const rows = document.querySelectorAll('#config-tbody tr');
    
    rows.forEach(row => {
        const rowType = row.getAttribute('data-type');
        row.style.display = (!type || rowType === type) ? '' : 'none';
    });
}

// Toggle installation value field
function toggleInstallationValue() {
    const type = document.getElementById('config-installation-type').value;
    const valueGroup = document.getElementById('installation-value-group');
    const valueInput = document.getElementById('config-installation-value');
    
    if (type === 'none') {
        valueGroup.style.display = 'none';
        valueInput.required = false;
        valueInput.value = '0';
    } else {
        valueGroup.style.display = 'block';
        valueInput.required = true;
    }
}

// Modal functions
function openConfigModal(mode, data = null) {
    const modal = document.getElementById('config-modal');
    const title = document.getElementById('config-modal-title');
    const form = document.getElementById('config-form');
    
    form.reset();
    title.textContent = mode === 'add' ? 'Add Infrastructure Config' : 'Edit Infrastructure Config';
    
    if (data) {
        document.getElementById('config-id').value = data.id;
        document.getElementById('config-type').value = data.item_type;
        document.getElementById('config-name').value = data.item_name;
        document.getElementById('config-value').value = data.item_value;
        document.getElementById('config-price').value = data.price;
        document.getElementById('config-parent').value = data.parent_item || '';
        document.getElementById('config-installation-type').value = data.installation_type;
        document.getElementById('config-installation-value').value = data.installation_value || '0';
        toggleInstallationValue();
    }
    
    modal.classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

function editConfig(data) {
    openConfigModal('edit', data);
}

// Save Config - FIXED: Point to handler
async function saveConfig(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const id = formData.get('id');
    
    formData.append('action', id ? 'update_config' : 'add_config');
    
    try {
        const response = await fetch('admin_includes/handlers/config_handler.php', {
            method: 'POST',
            body: formData
        });
        
        const text = await response.text();
        let result;
        
        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error('Response was not JSON:', text);
            showAlert('Server error: Invalid response format', 'error');
            return;
        }
        
        if (result.success) {
            showAlert(result.message, 'success');
            closeModal('config-modal');
            // Reload page to refresh table
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(result.message, 'error');
        }
    } catch (error) {
        console.error('Fetch error:', error);
        showAlert('Error: ' + error.message, 'error');
    }
}

// Delete Config - FIXED: Point to handler
async function deleteConfig(id) {
    if (!confirm('Are you sure you want to delete this configuration item?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_config');
    formData.append('id', id);
    
    try {
        const response = await fetch('admin_includes/handlers/config_handler.php', {
            method: 'POST',
            body: formData
        });
        
        const text = await response.text();
        let result;
        
        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error('Response was not JSON:', text);
            showAlert('Server error: Invalid response format', 'error');
            return;
        }
        
        if (result.success) {
            showAlert(result.message, 'success');
            // Reload page to refresh table
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(result.message, 'error');
        }
    } catch (error) {
        console.error('Fetch error:', error);
        showAlert('Error: ' + error.message, 'error');
    }
}
</script>