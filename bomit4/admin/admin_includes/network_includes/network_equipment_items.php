<?php
// admin/admin_includes/network_equipment_items.php
// Network Equipment Items Management - VIEW ONLY (AJAX handlers moved to separate file)

// Initialize session and database
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($conn)) include dirname(dirname(__DIR__)) . '/db_connect.php';
require_once dirname(__DIR__) . '/admin_utilities.php';

// Check admin authentication
requireAdminAuth($conn);

// Get site types using utility
$site_types = getAllSiteTypes();
?>

<!-- Filter Section -->
<div class="filter-section">
    <label for="site-type-filter">Filter by Site Type:</label>
    <select id="site-type-filter" onchange="filterEquipment()">
        <option value="">All Site Types</option>
        <?php foreach ($site_types as $key => $label): ?>
            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
        <?php endforeach; ?>
    </select>
    
    <label for="category-filter" style="margin-left: 2rem;">Category:</label>
    <select id="category-filter" onchange="filterEquipment()">
        <option value="">All Categories</option>
        <option value="equipment">Equipment</option>
        <option value="modules">Modules</option>
    </select>
</div>

<!-- Action Bar -->
<div class="action-bar">
    <h4>Network Equipment Items
        <span id="net-eq-row-count" style="font-size:.85rem; font-weight:400; color:#888; margin-left:.5rem;"></span>
    </h4>
    <button class="add-btn" onclick="openEquipmentModal('add')">➕ Add Equipment</button>
</div>

<!-- Equipment Table -->
<table class="equipment-table">
    <thead>
        <tr>
            <th>Site Type</th>
            <th>Category</th>
            <th>Item Name</th>
            <th>Description</th>
            <th>Default Qty</th>
            <th>Unit Price</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody id="equipment-tbody">
        <?php
        // Get all equipment records using utility
        $records = getAllRecords($conn, 'network_equipment', 'site_type, equipment_category, display_order');
        foreach ($records as $row):
        ?>
        <tr data-site-type="<?php echo $row['site_type']; ?>" data-category="<?php echo $row['equipment_category']; ?>">
            <td>
                <span class="net-site-badge net-site-<?php echo preg_replace('/[^a-z0-9]/', '_', $row['site_type']); ?>">
                    <?php echo getSiteTypeLabel($row['site_type']); ?>
                </span>
            </td>
            <td>
                <span class="net-cat-badge net-cat-<?php echo $row['equipment_category']; ?>">
                    <?php echo $row['equipment_category'] === 'equipment' ? '🖥️ Equipment' : '🔌 Modules'; ?>
                </span>
            </td>
            <td><strong><?php echo htmlspecialchars($row['item_name']); ?></strong></td>
            <td><?php echo htmlspecialchars($row['item_description']); ?></td>
            <td><?php echo $row['default_quantity']; ?></td>
            <td><span class="price-badge"><?php echo formatPrice($row['unit_price']); ?></span></td>
            <td>
                <button class="btn-edit" onclick='editEquipment(<?php echo json_encode($row); ?>)'>✏️ Edit</button>
                <button class="btn-delete" onclick="deleteEquipment(<?php echo $row['id']; ?>)">🗑️ Delete</button>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Equipment Modal -->
<div id="equipment-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="equipment-modal-title">Add Equipment</h3>
            <button class="modal-close" onclick="closeModal('equipment-modal')">&times;</button>
        </div>
        <form id="equipment-form" onsubmit="saveEquipment(event)">
            <input type="hidden" id="equipment-id" name="id">
            
            <div class="form-group">
                <label>Site Type *</label>
                <select id="equipment-site-type" name="site_type" required>
                    <?php foreach ($site_types as $key => $label): ?>
                        <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Category *</label>
                <select id="equipment-category" name="category" required>
                    <option value="equipment">Equipment</option>
                    <option value="modules">Modules</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Item Name *</label>
                <input type="text" id="equipment-name" name="name" required>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea id="equipment-description" name="description"></textarea>
            </div>
            
            <div class="form-group">
                <label>Default Quantity *</label>
                <input type="number" id="equipment-quantity" name="quantity" min="0" required>
            </div>
            
            <div class="form-group">
                <label>Unit Price ($) *</label>
                <input type="number" id="equipment-price" name="price" step="0.01" min="0" required>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('equipment-modal')">Cancel</button>
                <button type="submit" class="btn-save">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
// Equipment filtering
function filterEquipment() {
    const siteType = document.getElementById('site-type-filter').value;
    const category = document.getElementById('category-filter').value;
    const rows = document.querySelectorAll('#equipment-tbody tr');
    
    rows.forEach(row => {
        const rowSiteType = row.getAttribute('data-site-type');
        const rowCategory = row.getAttribute('data-category');
        
        const siteMatch = !siteType || rowSiteType === siteType;
        const categoryMatch = !category || rowCategory === category;
        
        row.style.display = (siteMatch && categoryMatch) ? '' : 'none';
    });
}

// Modal functions
function openEquipmentModal(mode, data = null) {
    const modal = document.getElementById('equipment-modal');
    const title = document.getElementById('equipment-modal-title');
    const form = document.getElementById('equipment-form');
    
    form.reset();
    title.textContent = mode === 'add' ? 'Add Equipment' : 'Edit Equipment';
    
    if (data) {
        document.getElementById('equipment-id').value = data.id;
        document.getElementById('equipment-site-type').value = data.site_type;
        document.getElementById('equipment-category').value = data.equipment_category;
        document.getElementById('equipment-name').value = data.item_name;
        document.getElementById('equipment-description').value = data.item_description || '';
        document.getElementById('equipment-quantity').value = data.default_quantity;
        document.getElementById('equipment-price').value = data.unit_price;
    }
    
    modal.classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

function editEquipment(data) {
    openEquipmentModal('edit', data);
}

// Save Equipment - FIXED: Point to the separate handler file
async function saveEquipment(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const id = formData.get('id');
    
    formData.append('action', id ? 'update_equipment' : 'add_equipment');
    
    try {
        // CHANGED: Point to the dedicated AJAX handler
        const response = await fetch('admin_includes/handlers/equipment_handler.php', {
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
            closeModal('equipment-modal');
            
            if (id) {
                updateEquipmentRow(id, formData);
            } else {
                addEquipmentRow(result.data.id, formData);
            }
        } else {
            showAlert(result.message, 'error');
        }
    } catch (error) {
        showAlert('Error: ' + error.message, 'error');
    }
}

// Delete Equipment - FIXED: Point to the separate handler file
async function deleteEquipment(id) {
    if (!confirm('Are you sure you want to delete this equipment item?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_equipment');
    formData.append('id', id);
    
    try {
        // CHANGED: Point to the dedicated AJAX handler
        const response = await fetch('admin_includes/handlers/equipment_handler.php', {
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
            removeEquipmentRow(id);
        } else {
            showAlert(result.message, 'error');
        }
    } catch (error) {
        showAlert('Error: ' + error.message, 'error');
    }
}

// DOM Update Helpers
function updateEquipmentRow(id, formData) {
    const tbody = document.getElementById('equipment-tbody');
    const row = Array.from(tbody.querySelectorAll('tr')).find(tr => {
        const deleteBtn = tr.querySelector('.btn-delete');
        return deleteBtn && deleteBtn.getAttribute('onclick').includes(`(${id})`);
    });
    
    if (row) {
        const cells = row.querySelectorAll('td');
        cells[2].innerHTML = `<strong>${escapeHtml(formData.get('name'))}</strong>`;
        cells[3].textContent = escapeHtml(formData.get('description'));
        cells[4].textContent = formData.get('quantity');
        cells[5].innerHTML = `<span class="price-badge">$${parseFloat(formData.get('price')).toFixed(2)}</span>`;
        
        row.style.backgroundColor = '#d4edda';
        setTimeout(() => row.style.backgroundColor = '', 2000);
    }
}

function addEquipmentRow(id, formData) {
    const tbody = document.getElementById('equipment-tbody');
    const siteTypes = {
        'less_50_no_server': 'Less than 50 Users - No Server',
        'less_50_with_server': 'Less than 50 Users - With Server',
        '51_150_no_server': '51-150 Users - No Server',
        '51_150_with_server': '51-150 Users - With Server',
        '151_300_no_server': '151-300 Users - No Server',
        '151_300_with_server': '151-300 Users - With Server',
        '301_400_no_server': '301-400 Users - No Server',
        '301_400_with_server': '301-400 Users - With Server',
        'more_400_no_server': 'More than 400 Users - No Server',
        'more_400_with_server': 'More than 400 Users - With Server'
    };
    
    const row = document.createElement('tr');
    row.setAttribute('data-site-type', formData.get('site_type'));
    row.setAttribute('data-category', formData.get('category'));
    row.innerHTML = `
        <td><small>${siteTypes[formData.get('site_type')] || formData.get('site_type')}</small></td>
        <td><span style="text-transform: capitalize;">${formData.get('category')}</span></td>
        <td><strong>${escapeHtml(formData.get('name'))}</strong></td>
        <td>${escapeHtml(formData.get('description'))}</td>
        <td>${formData.get('quantity')}</td>
        <td><span class="price-badge">$${parseFloat(formData.get('price')).toFixed(2)}</span></td>
        <td>
            <button class="btn-edit" onclick='editEquipment(${JSON.stringify({
                id, 
                site_type: formData.get('site_type'),
                equipment_category: formData.get('category'),
                item_name: formData.get('name'),
                item_description: formData.get('description'),
                default_quantity: formData.get('quantity'),
                unit_price: formData.get('price')
            })})'>✏️ Edit</button>
            <button class="btn-delete" onclick="deleteEquipment(${id})">🗑️ Delete</button>
        </td>
    `;
    
    tbody.appendChild(row);
    row.style.backgroundColor = '#d4edda';
    setTimeout(() => row.style.backgroundColor = '', 2000);
    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function removeEquipmentRow(id) {
    const tbody = document.getElementById('equipment-tbody');
    const row = Array.from(tbody.querySelectorAll('tr')).find(tr => {
        const deleteBtn = tr.querySelector('.btn-delete');
        return deleteBtn && deleteBtn.getAttribute('onclick').includes(`(${id})`);
    });
    
    if (row) {
        row.style.opacity = '0';
        row.style.transition = 'opacity 0.3s';
        setTimeout(() => row.remove(), 300);
    }
}
</script>

<!-- Delete Confirm Modal -->
<div id="net-eq-delete-modal" class="modal">
    <div class="modal-content" style="max-width:420px; text-align:center;">
        <div style="font-size:3rem; margin-bottom:1rem;">🗑️</div>
        <h3 style="color:#dc3545; margin-bottom:.75rem;">Delete Item?</h3>
        <p style="color:#666; margin-bottom:1.5rem;" id="net-eq-delete-text">This will permanently remove the item.</p>
        <div style="display:flex; gap:1rem; justify-content:center;">
            <button class="btn-cancel" onclick="document.getElementById('net-eq-delete-modal').classList.remove('active')"
                style="padding:.75rem 2rem;">Cancel</button>
            <button class="btn-delete" id="net-eq-delete-confirm-btn"
                style="padding:.75rem 2rem; border-radius:8px;">Yes, Delete</button>
        </div>
    </div>
</div>

<style>
/* ── Site type badges ─────────────────────────────────────────────────── */
.net-site-badge {
    display:inline-block; padding:3px 8px; border-radius:12px;
    font-size:.72rem; font-weight:600; white-space:nowrap; line-height:1.4;
}
.net-site-badge[class*="no_server"]   { background:#e3f2fd; color:#1565c0; }
.net-site-badge[class*="with_server"] { background:#e8f5e9; color:#2e7d32; }

/* ── Category badges ──────────────────────────────────────────────────── */
.net-cat-badge {
    display:inline-block; padding:3px 10px; border-radius:20px;
    font-size:.78rem; font-weight:600; white-space:nowrap;
}
.net-cat-equipment { background:#e8eaf6; color:#283593; }
.net-cat-modules   { background:#fff3e0; color:#e65100; }

/* ── Table row hover ──────────────────────────────────────────────────── */
#equipment-tbody tr:hover {
    background:linear-gradient(90deg,rgba(0,112,239,.04),rgba(128,199,160,.04)) !important;
}
</style>

<script>
// Init row count on load
document.addEventListener('DOMContentLoaded', function() {
    var rows = document.querySelectorAll('#equipment-tbody tr[data-site-type]');
    var el   = document.getElementById('net-eq-row-count');
    if (el) el.textContent = '(' + rows.length + ' items)';
});

// Override deleteEquipment to use confirm modal
var _origDeleteEquipment = typeof deleteEquipment === 'function' ? deleteEquipment : null;
function deleteEquipment(id) {
    var tbody = document.getElementById('equipment-tbody');
    var row   = Array.from(tbody.querySelectorAll('tr')).find(tr => {
        var btn = tr.querySelector('.btn-delete');
        return btn && btn.getAttribute('onclick').includes('(' + id + ')');
    });
    var name = row ? (row.querySelector('strong') ? row.querySelector('strong').textContent : 'Item #' + id) : 'Item #' + id;

    document.getElementById('net-eq-delete-text').innerHTML =
        'Permanently remove <strong>' + (name.replace(/</g,'&lt;')) + '</strong> from the database.';

    var btn = document.getElementById('net-eq-delete-confirm-btn');
    btn.onclick = async function() {
        btn.disabled = true; btn.textContent = 'Deleting…';
        var fd = new FormData();
        fd.append('action', 'delete_equipment');
        fd.append('id', id);
        try {
            var resp = await fetch('admin_includes/handlers/equipment_handler.php', { method:'POST', body:fd });
            var result = await resp.json();
            if (result.success) {
                showAlert(result.message, 'success');
                document.getElementById('net-eq-delete-modal').classList.remove('active');
                removeEquipmentRow(id);
                // Update count
                var cnt = document.getElementById('net-eq-row-count');
                if (cnt) {
                    var vis = Array.from(document.querySelectorAll('#equipment-tbody tr[data-site-type]'))
                        .filter(r => r.style.display !== 'none').length;
                    cnt.textContent = '(' + vis + ' items)';
                }
            } else { showAlert(result.message, 'error'); }
        } catch(e) { showAlert('Network error: ' + e.message, 'error'); }
        finally { btn.disabled = false; btn.textContent = 'Yes, Delete'; }
    };

    document.getElementById('net-eq-delete-modal').classList.add('active');
}

// Also update filterEquipment to refresh count
var _origFilter = typeof filterEquipment === 'function' ? filterEquipment : null;
function filterEquipment() {
    var siteType = document.getElementById('site-type-filter').value;
    var category = document.getElementById('category-filter').value;
    var rows = document.querySelectorAll('#equipment-tbody tr');
    var visible = 0;
    rows.forEach(function(row) {
        var rowSiteType = row.getAttribute('data-site-type');
        var rowCategory = row.getAttribute('data-category');
        var siteMatch = !siteType || rowSiteType === siteType;
        var categoryMatch = !category || rowCategory === category;
        var show = siteMatch && categoryMatch;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    var el = document.getElementById('net-eq-row-count');
    if (el) el.textContent = '(' + visible + ' items)';
}
</script>