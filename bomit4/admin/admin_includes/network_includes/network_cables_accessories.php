<?php
// admin/admin_includes/network_cables_accessories.php
// Network Cables & Accessories Management - VIEW ONLY

// Initialize session and database
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($conn)) include dirname(dirname(__DIR__)) . '/db_connect.php';
require_once dirname(__DIR__) . '/admin_utilities.php';

// Check admin authentication
requireAdminAuth($conn);
?>

<!-- Action Bar -->
<div class="action-bar">
    <h4>Cables & Accessories</h4>
    <button class="add-btn" onclick="openCableModal('add')">➕ Add Item</button>
</div>

<!-- Cable Table -->
<table class="equipment-table">
    <thead>
        <tr>
            <th>Category</th>
            <th>Item Name</th>
            <th>Description</th>
            <th>Default Qty</th>
            <th>Unit Price</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Get all cable records
        $records = getAllRecords($conn, 'network_cables_accessories', 'category, display_order');
        foreach ($records as $row):
        ?>
        <tr>
            <td><span style="text-transform: capitalize;"><?php echo htmlspecialchars($row['category']); ?></span></td>
            <td><strong><?php echo htmlspecialchars($row['item_name']); ?></strong></td>
            <td><?php echo htmlspecialchars($row['item_description']); ?></td>
            <td><?php echo $row['default_quantity']; ?></td>
            <td><span class="price-badge"><?php echo formatPrice($row['unit_price']); ?></span></td>
            <td>
                <button class="btn-edit" onclick='editCable(<?php echo json_encode($row); ?>)'>✏️ Edit</button>
                <button class="btn-delete" onclick="deleteCable(<?php echo $row['id']; ?>)">🗑️ Delete</button>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Cable Modal -->
<div id="cable-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="cable-modal-title">Add Cable/Accessory</h3>
            <button class="modal-close" onclick="closeModal('cable-modal')">&times;</button>
        </div>
        <form id="cable-form" onsubmit="saveCable(event)">
            <input type="hidden" id="cable-id" name="id">
            
            <div class="form-group">
                <label>Category *</label>
                <select id="cable-category" name="category" required>
                    <option value="cables">Cables</option>
                    <option value="accessories">Accessories</option>
                    <option value="connectors">Connectors</option>
                    <option value="power">Power & UPS</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Item Name *</label>
                <input type="text" id="cable-name" name="name" required>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea id="cable-description" name="description"></textarea>
            </div>
            
            <div class="form-group">
                <label>Default Quantity *</label>
                <input type="number" id="cable-quantity" name="quantity" min="0" required>
            </div>
            
            <div class="form-group">
                <label>Unit Price ($) *</label>
                <input type="number" id="cable-price" name="price" step="0.01" min="0" required>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('cable-modal')">Cancel</button>
                <button type="submit" class="btn-save">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal functions
function openCableModal(mode, data = null) {
    const modal = document.getElementById('cable-modal');
    const title = document.getElementById('cable-modal-title');
    const form = document.getElementById('cable-form');
    
    form.reset();
    title.textContent = mode === 'add' ? 'Add Cable/Accessory' : 'Edit Cable/Accessory';
    
    if (data) {
        document.getElementById('cable-id').value = data.id;
        document.getElementById('cable-category').value = data.category;
        document.getElementById('cable-name').value = data.item_name;
        document.getElementById('cable-description').value = data.item_description || '';
        document.getElementById('cable-quantity').value = data.default_quantity;
        document.getElementById('cable-price').value = data.unit_price;
    }
    
    modal.classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

function editCable(data) {
    openCableModal('edit', data);
}

// Save Cable - FIXED: Point to handler
async function saveCable(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const id = formData.get('id');
    
    formData.append('action', id ? 'update_cable' : 'add_cable');
    
    try {
        const response = await fetch('admin_includes/handlers/cables_handler.php', {
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
            closeModal('cable-modal');
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

// Delete Cable - FIXED: Point to handler
async function deleteCable(id) {
    if (!confirm('Are you sure you want to delete this item?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_cable');
    formData.append('id', id);
    
    try {
        const response = await fetch('admin_includes/handlers/cables_handler.php', {
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