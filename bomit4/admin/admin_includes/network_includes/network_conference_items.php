<?php
// admin/admin_includes/network_conference_items.php
// Conference Room Equipment Management - VIEW ONLY (AJAX handlers in conference_handler.php)

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($conn)) include dirname(dirname(__DIR__)) . '/db_connect.php';
require_once dirname(__DIR__) . '/admin_utilities.php';

require_once 'admin_utilities.php';
requireAdminAuth($conn);

$room_sizes = [
    'small'  => 'Small (4-6 people)',
    'medium' => 'Medium (8-12 people)',
    'large'  => 'Large (15+ people)',
];

$categories = [
    'av'           => 'AV Equipment',
    'connectivity' => 'Connectivity',
    'furniture'    => 'Furniture',
    'other'        => 'Other',
];
?>

<!-- Filter Section -->
<div class="filter-section">
    <label for="room-size-filter">Filter by Room Size:</label>
    <select id="room-size-filter" onchange="filterConferenceEquipment()">
        <option value="">All Room Sizes</option>
        <?php foreach ($room_sizes as $key => $label): ?>
            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
        <?php endforeach; ?>
    </select>

    <label for="conf-category-filter" style="margin-left: 2rem;">Category:</label>
    <select id="conf-category-filter" onchange="filterConferenceEquipment()">
        <option value="">All Categories</option>
        <?php foreach ($categories as $key => $label): ?>
            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
        <?php endforeach; ?>
    </select>
</div>

<!-- Action Bar -->
<div class="action-bar">
    <h4>Conference Room Equipment</h4>
    <button class="add-btn" onclick="openConferenceModal('add')">Add Equipment</button>
</div>

<!-- Equipment Table -->
<table class="equipment-table">
    <thead>
        <tr>
            <th>Room Size</th>
            <th>Category</th>
            <th>Item Name</th>
            <th>Description</th>
            <th>Default Qty</th>
            <th>Unit Price</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody id="conference-equipment-tbody">
        <?php
        $records = getAllRecords($conn, 'conference_equipment', 'room_size, equipment_category, display_order');
        foreach ($records as $row):
        ?>
        <tr data-room-size="<?php echo $row['room_size']; ?>" data-category="<?php echo $row['equipment_category']; ?>">
            <td><small><?php echo $room_sizes[$row['room_size']] ?? ucfirst($row['room_size']); ?></small></td>
            <td><span style="text-transform: capitalize;"><?php echo $categories[$row['equipment_category']] ?? $row['equipment_category']; ?></span></td>
            <td><strong><?php echo htmlspecialchars($row['item_name']); ?></strong></td>
            <td><?php echo htmlspecialchars($row['item_description']); ?></td>
            <td><?php echo $row['default_quantity']; ?></td>
            <td><span class="price-badge"><?php echo formatPrice($row['unit_price']); ?></span></td>
            <td>
                <button class="btn-edit" onclick='editConferenceItem(<?php echo json_encode($row); ?>)'>Edit</button>
                <button class="btn-delete" onclick="deleteConferenceItem(<?php echo $row['id']; ?>)">Delete</button>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Equipment Modal -->
<div id="conference-equipment-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="conference-modal-title">Add Conference Equipment</h3>
            <button class="modal-close" onclick="closeModal('conference-equipment-modal')">&times;</button>
        </div>
        <form id="conference-equipment-form" onsubmit="saveConferenceItem(event)">
            <input type="hidden" id="conf-item-id" name="id">

            <div class="form-group">
                <label>Room Size *</label>
                <select id="conf-room-size" name="room_size" required>
                    <?php foreach ($room_sizes as $key => $label): ?>
                        <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Category *</label>
                <select id="conf-category" name="category" required>
                    <?php foreach ($categories as $key => $label): ?>
                        <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Item Name *</label>
                <input type="text" id="conf-item-name" name="name" required>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea id="conf-item-description" name="description"></textarea>
            </div>

            <div class="form-group">
                <label>Default Quantity *</label>
                <input type="number" id="conf-item-quantity" name="quantity" min="0" required>
            </div>

            <div class="form-group">
                <label>Unit Price ($) *</label>
                <input type="number" id="conf-item-price" name="price" step="0.01" min="0" required>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('conference-equipment-modal')">Cancel</button>
                <button type="submit" class="btn-save">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
// Filter
function filterConferenceEquipment() {
    const roomSize = document.getElementById('room-size-filter').value;
    const category = document.getElementById('conf-category-filter').value;
    const rows = document.querySelectorAll('#conference-equipment-tbody tr');

    rows.forEach(row => {
        const rowSize = row.getAttribute('data-room-size');
        const rowCat  = row.getAttribute('data-category');
        const sizeMatch = !roomSize || rowSize === roomSize;
        const catMatch  = !category || rowCat === category;
        row.style.display = (sizeMatch && catMatch) ? '' : 'none';
    });
}

// Modal open
function openConferenceModal(mode, data = null) {
    const modal = document.getElementById('conference-equipment-modal');
    const title = document.getElementById('conference-modal-title');
    document.getElementById('conference-equipment-form').reset();

    title.textContent = mode === 'add' ? 'Add Conference Equipment' : 'Edit Conference Equipment';

    if (data) {
        document.getElementById('conf-item-id').value          = data.id;
        document.getElementById('conf-room-size').value        = data.room_size;
        document.getElementById('conf-category').value         = data.equipment_category;
        document.getElementById('conf-item-name').value        = data.item_name;
        document.getElementById('conf-item-description').value = data.item_description || '';
        document.getElementById('conf-item-quantity').value    = data.default_quantity;
        document.getElementById('conf-item-price').value       = data.unit_price;
    }

    modal.classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

function editConferenceItem(data) {
    openConferenceModal('edit', data);
}

// Save
async function saveConferenceItem(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const id = formData.get('id');
    formData.append('action', id ? 'update_conference_item' : 'add_conference_item');

    try {
        const response = await fetch('admin_includes/handlers/conference_handler.php', {
            method: 'POST',
            body: formData
        });
        const text = await response.text();
        let result;
        try { result = JSON.parse(text); }
        catch (e) { showAlert('Server error: Invalid response', 'error'); return; }

        if (result.success) {
            showAlert(result.message, 'success');
            closeModal('conference-equipment-modal');
            if (id) {
                updateConferenceRow(id, formData);
            } else {
                addConferenceRow(result.data.id, formData);
            }
        } else {
            showAlert(result.message, 'error');
        }
    } catch (err) {
        showAlert('Error: ' + err.message, 'error');
    }
}

// Delete
async function deleteConferenceItem(id) {
    if (!confirm('Delete this conference equipment item?')) return;

    const formData = new FormData();
    formData.append('action', 'delete_conference_item');
    formData.append('id', id);

    try {
        const response = await fetch('admin_includes/handlers/conference_handler.php', {
            method: 'POST',
            body: formData
        });
        const text = await response.text();
        let result;
        try { result = JSON.parse(text); }
        catch (e) { showAlert('Server error: Invalid response', 'error'); return; }

        if (result.success) {
            showAlert(result.message, 'success');
            removeConferenceRow(id);
        } else {
            showAlert(result.message, 'error');
        }
    } catch (err) {
        showAlert('Error: ' + err.message, 'error');
    }
}

// DOM helpers
const ROOM_SIZE_LABELS = <?php echo json_encode($room_sizes); ?>;
const CATEGORY_LABELS  = <?php echo json_encode($categories); ?>;

function updateConferenceRow(id, formData) {
    const tbody = document.getElementById('conference-equipment-tbody');
    const row = Array.from(tbody.querySelectorAll('tr')).find(tr => {
        const btn = tr.querySelector('.btn-delete');
        return btn && btn.getAttribute('onclick').includes(`(${id})`);
    });
    if (row) {
        const cells = row.querySelectorAll('td');
        cells[0].innerHTML = `<small>${ROOM_SIZE_LABELS[formData.get('room_size')] || formData.get('room_size')}</small>`;
        cells[1].innerHTML = `<span style="text-transform:capitalize;">${CATEGORY_LABELS[formData.get('category')] || formData.get('category')}</span>`;
        cells[2].innerHTML = `<strong>${escapeHtml(formData.get('name'))}</strong>`;
        cells[3].textContent = escapeHtml(formData.get('description'));
        cells[4].textContent = formData.get('quantity');
        cells[5].innerHTML   = `<span class="price-badge">$${parseFloat(formData.get('price')).toFixed(2)}</span>`;
        row.style.backgroundColor = '#d4edda';
        setTimeout(() => row.style.backgroundColor = '', 2000);
    }
}

function addConferenceRow(id, formData) {
    const tbody = document.getElementById('conference-equipment-tbody');
    const itemData = {
        id, room_size: formData.get('room_size'),
        equipment_category: formData.get('category'),
        item_name: formData.get('name'),
        item_description: formData.get('description'),
        default_quantity: formData.get('quantity'),
        unit_price: formData.get('price')
    };
    const row = document.createElement('tr');
    row.setAttribute('data-room-size', formData.get('room_size'));
    row.setAttribute('data-category', formData.get('category'));
    row.innerHTML = `
        <td><small>${ROOM_SIZE_LABELS[formData.get('room_size')] || formData.get('room_size')}</small></td>
        <td><span style="text-transform:capitalize;">${CATEGORY_LABELS[formData.get('category')] || formData.get('category')}</span></td>
        <td><strong>${escapeHtml(formData.get('name'))}</strong></td>
        <td>${escapeHtml(formData.get('description'))}</td>
        <td>${formData.get('quantity')}</td>
        <td><span class="price-badge">$${parseFloat(formData.get('price')).toFixed(2)}</span></td>
        <td>
            <button class="btn-edit" onclick='editConferenceItem(${JSON.stringify(itemData)})'>Edit</button>
            <button class="btn-delete" onclick="deleteConferenceItem(${id})">Delete</button>
        </td>
    `;
    tbody.appendChild(row);
    row.style.backgroundColor = '#d4edda';
    setTimeout(() => row.style.backgroundColor = '', 2000);
    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function removeConferenceRow(id) {
    const tbody = document.getElementById('conference-equipment-tbody');
    const row = Array.from(tbody.querySelectorAll('tr')).find(tr => {
        const btn = tr.querySelector('.btn-delete');
        return btn && btn.getAttribute('onclick').includes(`(${id})`);
    });
    if (row) {
        row.style.opacity = '0';
        row.style.transition = 'opacity 0.3s';
        setTimeout(() => row.remove(), 300);
    }
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str || '';
    return div.innerHTML;
}
</script>
