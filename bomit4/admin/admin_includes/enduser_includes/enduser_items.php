<?php
// admin/admin_includes/enduser_items.php
// End User Equipment Items Management
// Mirrors network_equipment_items.php exactly — same pattern, same utilities

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($conn)) include dirname(dirname(__DIR__)) . '/db_connect.php';
require_once dirname(__DIR__) . '/admin_utilities.php';
requireAdminAuth($conn);

// ── Lookup maps (mirrors getAllSiteTypes() in utilities) ──────────────────
$eu_user_types = [
    'general'   => 'General User',
    'technical' => 'Technical User',
    'design'    => 'Design / CAD',
    'field'     => 'Field / Mobile',
    'executive' => 'Executive / VIP',
];

$eu_item_categories = [
    'workstation' => 'Workstation Equipment',
    'peripherals' => 'Peripherals & Accessories',
    'mobile'      => 'Mobile & Communications',
    'software'    => 'Software & Licenses',
];

// ── Fetch all records (safe direct query with error handling) ─────────────
$records = [];
$eu_fetch_error = '';

// Check if table exists
$table_check = $conn->query("SHOW TABLES LIKE 'enduser_equipment'");
if ($table_check && $table_check->num_rows > 0) {
    $res = $conn->query("SELECT * FROM enduser_equipment ORDER BY user_type, item_category, display_order, id");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $records[] = $row;
        }
    } else {
        $eu_fetch_error = 'Query error: ' . $conn->error;
    }
} else {
    $eu_fetch_error = 'Table <strong>enduser_equipment</strong> does not exist yet. Please <a href="#" style="color:#721c24;font-weight:700;">import enduser_equipment.sql</a> into your database first.';
}
?>

<?php if ($eu_fetch_error): ?>
<div style="padding:1.5rem; background:#f8d7da; border:1px solid #f5c6cb; border-radius:8px; color:#721c24; margin-bottom:1rem;">
    ⚠️ <?php echo $eu_fetch_error; ?>
</div>
<?php endif; ?>

<!-- ── Filter Bar ─────────────────────────────────────────────────────── -->
<div class="filter-section">
    <label for="eu-user-type-filter">Filter by User Type:</label>
    <select id="eu-user-type-filter" onchange="euFilterItems()">
        <option value="">All User Types</option>
        <?php foreach ($eu_user_types as $key => $label): ?>
            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
        <?php endforeach; ?>
    </select>

    <label for="eu-category-filter" style="margin-left:2rem;">Category:</label>
    <select id="eu-category-filter" onchange="euFilterItems()">
        <option value="">All Categories</option>
        <?php foreach ($eu_item_categories as $key => $label): ?>
            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
        <?php endforeach; ?>
    </select>

    <label for="eu-status-filter" style="margin-left:2rem;">Status:</label>
    <select id="eu-status-filter" onchange="euFilterItems()">
        <option value="">All</option>
        <option value="1">Active</option>
        <option value="0">Inactive</option>
    </select>
</div>

<!-- ── Action Bar ─────────────────────────────────────────────────────── -->
<div class="action-bar">
    <h4>End User Equipment Items
        <span id="eu-row-count" style="font-size:0.85rem; font-weight:400; color:#888; margin-left:0.5rem;">
            (<?php echo count($records); ?> items)
        </span>
    </h4>
    <button class="add-btn" onclick="euOpenItemModal('add')">➕ Add Item</button>
</div>

<!-- ── Equipment Table ────────────────────────────────────────────────── -->
<table class="equipment-table">
    <thead>
        <tr>
            <th>User Type</th>
            <th>Category</th>
            <th>Item Name</th>
            <th>Description</th>
            <th>Default Qty</th>
            <th>Unit Price</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody id="eu-items-tbody">
        <?php if (empty($records)): ?>
        <tr id="eu-empty-row">
            <td colspan="8" style="text-align:center; padding:2rem; color:#999; font-style:italic;">
                No items yet. Click <strong>➕ Add Item</strong> to get started.
            </td>
        </tr>
        <?php else: ?>
        <?php foreach ($records as $row): ?>
        <tr data-user-type="<?php echo htmlspecialchars($row['user_type']); ?>"
            data-category="<?php echo htmlspecialchars($row['item_category']); ?>"
            data-status="<?php echo $row['is_active']; ?>">
            <!-- User Type -->
            <td>
                <span class="eu-type-badge eu-type-<?php echo $row['user_type']; ?>">
                    <?php echo htmlspecialchars($eu_user_types[$row['user_type']] ?? $row['user_type']); ?>
                </span>
            </td>
            <!-- Category -->
            <td>
                <span class="eu-cat-badge eu-cat-<?php echo $row['item_category']; ?>">
                    <?php echo htmlspecialchars($eu_item_categories[$row['item_category']] ?? $row['item_category']); ?>
                </span>
            </td>
            <!-- Item Name -->
            <td><strong><?php echo htmlspecialchars($row['item_name']); ?></strong></td>
            <!-- Description -->
            <td style="max-width:220px; font-size:0.875rem; color:#666;">
                <?php echo htmlspecialchars($row['item_description'] ?? ''); ?>
            </td>
            <!-- Default Qty -->
            <td style="text-align:center;"><?php echo (int)$row['default_quantity']; ?></td>
            <!-- Unit Price -->
            <td><span class="price-badge"><?php echo formatPrice($row['unit_price']); ?></span></td>
            <!-- Status -->
            <td>
                <?php if ($row['is_active']): ?>
                    <span style="color:#28a745; font-weight:600;">● Active</span>
                <?php else: ?>
                    <span style="color:#dc3545; font-weight:600;">● Inactive</span>
                <?php endif; ?>
            </td>
            <!-- Actions -->
            <td>
                <button class="btn-edit"
                    onclick='euOpenItemModal("edit", <?php echo json_encode($row); ?>)'>
                    ✏️ Edit
                </button>
                <button class="btn-delete"
                    onclick="euDeleteItem(<?php echo (int)$row['id']; ?>)">
                    🗑️ Delete
                </button>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<!-- ── Add / Edit Modal ───────────────────────────────────────────────── -->
<div id="eu-item-modal" class="modal">
    <div class="modal-content" style="max-width:640px;">
        <div class="modal-header">
            <h3 id="eu-item-modal-title">Add End User Item</h3>
            <button class="modal-close" onclick="euCloseModal('eu-item-modal')">&times;</button>
        </div>

        <form id="eu-item-form" onsubmit="euSaveItem(event)">
            <input type="hidden" id="eu-item-id" name="id">

            <!-- Row 1: User Type + Category -->
            <div style="display:flex; gap:1rem;">
                <div class="form-group" style="flex:1;">
                    <label>User Type <span style="color:#dc3545;">*</span></label>
                    <select id="eu-item-user-type" name="user_type" required>
                        <?php foreach ($eu_user_types as $key => $label): ?>
                            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex:1;">
                    <label>Category <span style="color:#dc3545;">*</span></label>
                    <select id="eu-item-category" name="item_category" required>
                        <?php foreach ($eu_item_categories as $key => $label): ?>
                            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Item Name -->
            <div class="form-group">
                <label>Item Name <span style="color:#dc3545;">*</span></label>
                <input type="text" id="eu-item-name" name="name"
                       placeholder="e.g. Business Laptop, Microsoft 365 License" required>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label>Description / Spec</label>
                <textarea id="eu-item-description" name="description" rows="2"
                    placeholder="e.g. Intel Core i5, 16GB RAM, 512GB SSD — brief spec or notes"></textarea>
            </div>

            <!-- Row 2: Qty + Price + Status -->
            <div style="display:flex; gap:1rem;">
                <div class="form-group" style="flex:1;">
                    <label>Default Quantity <span style="color:#dc3545;">*</span></label>
                    <input type="number" id="eu-item-quantity" name="quantity"
                           min="0" value="1" required>
                    <small style="color:#888;">Pre-filled qty shown to users</small>
                </div>
                <div class="form-group" style="flex:1;">
                    <label>Unit Price (USD) <span style="color:#dc3545;">*</span></label>
                    <input type="number" id="eu-item-price" name="price"
                           step="0.01" min="0" value="0.00" required>
                </div>
                <div class="form-group" style="flex:1;">
                    <label>Status</label>
                    <select id="eu-item-status" name="is_active">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                    <small style="color:#888;">Inactive = hidden from users</small>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-cancel"
                        onclick="euCloseModal('eu-item-modal')">Cancel</button>
                <button type="submit" class="btn-save" id="eu-item-save-btn">Save Item</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Delete Confirm Modal ───────────────────────────────────────────── -->
<div id="eu-delete-modal" class="modal">
    <div class="modal-content" style="max-width:420px; text-align:center;">
        <div style="font-size:3rem; margin-bottom:1rem;">🗑️</div>
        <h3 style="color:#dc3545; margin-bottom:0.75rem;">Delete Item?</h3>
        <p style="color:#666; margin-bottom:1.5rem;" id="eu-delete-confirm-text">
            This will permanently remove the item from the database.
        </p>
        <div style="display:flex; gap:1rem; justify-content:center;">
            <button class="btn-cancel" onclick="euCloseModal('eu-delete-modal')"
                style="padding:0.75rem 2rem;">Cancel</button>
            <button class="btn-delete" id="eu-delete-confirm-btn"
                style="padding:0.75rem 2rem; border-radius:8px;">Yes, Delete</button>
        </div>
    </div>
</div>


<!-- ══════════════════════════════════════════════════════════════════════
     JAVASCRIPT
     ══════════════════════════════════════════════════════════════════════ -->
<script>
// ── Lookup maps (JS mirrors PHP above) ────────────────────────────────────
const EU_USER_TYPE_LABELS = {
    general:   'General User',
    technical: 'Technical User',
    design:    'Design / CAD',
    field:     'Field / Mobile',
    executive: 'Executive / VIP',
};
const EU_CATEGORY_LABELS = {
    workstation: 'Workstation Equipment',
    peripherals: 'Peripherals & Accessories',
    mobile:      'Mobile & Communications',
    software:    'Software & Licenses',
};

// ── Filter ────────────────────────────────────────────────────────────────
function euFilterItems() {
    const userType = document.getElementById('eu-user-type-filter').value;
    const category = document.getElementById('eu-category-filter').value;
    const status   = document.getElementById('eu-status-filter').value;
    const rows     = document.querySelectorAll('#eu-items-tbody tr[data-user-type]');

    let visible = 0;
    rows.forEach(row => {
        const utMatch  = !userType || row.dataset.userType  === userType;
        const catMatch = !category || row.dataset.category  === category;
        const stMatch  = status === '' || row.dataset.status === status;
        const show     = utMatch && catMatch && stMatch;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    // Update count badge
    const countEl = document.getElementById('eu-row-count');
    if (countEl) countEl.textContent = `(${visible} items)`;
}

// ── Modal helpers ─────────────────────────────────────────────────────────
function euCloseModal(id) {
    document.getElementById(id).classList.remove('active');
}

function euOpenItemModal(mode, data = null) {
    const modal = document.getElementById('eu-item-modal');
    const title = document.getElementById('eu-item-modal-title');
    document.getElementById('eu-item-form').reset();

    if (mode === 'add') {
        title.textContent = '➕ Add End User Item';
        document.getElementById('eu-item-id').value       = '';
        document.getElementById('eu-item-quantity').value  = '1';
        document.getElementById('eu-item-price').value     = '0.00';
        document.getElementById('eu-item-status').value    = '1';
    } else {
        title.textContent = '✏️ Edit End User Item';
        document.getElementById('eu-item-id').value          = data.id;
        document.getElementById('eu-item-user-type').value   = data.user_type;
        document.getElementById('eu-item-category').value    = data.item_category;
        document.getElementById('eu-item-name').value        = data.item_name;
        document.getElementById('eu-item-description').value = data.item_description || '';
        document.getElementById('eu-item-quantity').value    = data.default_quantity;
        document.getElementById('eu-item-price').value       = data.unit_price;
        document.getElementById('eu-item-status').value      = data.is_active;
    }

    modal.classList.add('active');
}

// ── Save (Add or Update) ──────────────────────────────────────────────────
async function euSaveItem(event) {
    event.preventDefault();
    const form     = event.target;
    const formData = new FormData(form);
    const id       = formData.get('id');
    formData.append('action', id ? 'update_item' : 'add_item');

    const saveBtn = document.getElementById('eu-item-save-btn');
    saveBtn.disabled    = true;
    saveBtn.textContent = 'Saving…';

    try {
        const response = await fetch('admin_includes/handlers/enduser_handler.php', {
            method: 'POST',
            body:   formData
        });

        const text = await response.text();
        let result;
        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error('Non-JSON response:', text);
            euShowAlert('Server error: Invalid response format', 'error');
            return;
        }

        if (result.success) {
            euShowAlert(result.message, 'success');
            euCloseModal('eu-item-modal');

            if (id) {
                euUpdateRow(id, formData);
            } else {
                euAddRow(result.data.id, formData);
                // Remove empty-state row if present
                const emptyRow = document.getElementById('eu-empty-row');
                if (emptyRow) emptyRow.remove();
            }
        } else {
            euShowAlert(result.message, 'error');
        }
    } catch (err) {
        euShowAlert('Network error: ' + err.message, 'error');
    } finally {
        saveBtn.disabled    = false;
        saveBtn.textContent = 'Save Item';
    }
}

// ── Delete ────────────────────────────────────────────────────────────────
function euDeleteItem(id) {
    // Use custom confirm modal
    const modal   = document.getElementById('eu-delete-modal');
    const textEl  = document.getElementById('eu-delete-confirm-text');
    const btn     = document.getElementById('eu-delete-confirm-btn');

    // Find row name for context
    const tbody = document.getElementById('eu-items-tbody');
    const row   = euFindRow(tbody, id);
    const name  = row ? row.querySelector('strong')?.textContent : `Item #${id}`;
    textEl.innerHTML = `This will permanently remove <strong>${euEscapeHtml(name)}</strong> from the database.`;

    // Wire confirm button
    btn.onclick = async () => {
        btn.disabled    = true;
        btn.textContent = 'Deleting…';

        const formData = new FormData();
        formData.append('action', 'delete_item');
        formData.append('id', id);

        try {
            const response = await fetch('admin_includes/handlers/enduser_handler.php', {
                method: 'POST', body: formData
            });
            const text = await response.text();
            let result;
            try { result = JSON.parse(text); }
            catch (e) { euShowAlert('Server error', 'error'); return; }

            if (result.success) {
                euShowAlert(result.message, 'success');
                euCloseModal('eu-delete-modal');
                euRemoveRow(id);
            } else {
                euShowAlert(result.message, 'error');
            }
        } catch (err) {
            euShowAlert('Network error: ' + err.message, 'error');
        } finally {
            btn.disabled    = false;
            btn.textContent = 'Yes, Delete';
        }
    };

    modal.classList.add('active');
}

// ── DOM Row Helpers ───────────────────────────────────────────────────────
function euFindRow(tbody, id) {
    return Array.from(tbody.querySelectorAll('tr')).find(tr => {
        const del = tr.querySelector('.btn-delete');
        return del && del.getAttribute('onclick').includes(`(${id})`);
    });
}

function euUpdateRow(id, fd) {
    const tbody = document.getElementById('eu-items-tbody');
    const row   = euFindRow(tbody, id);
    if (!row) return;

    const userType  = fd.get('user_type');
    const category  = fd.get('item_category');
    const isActive  = fd.get('is_active');

    const cells = row.querySelectorAll('td');
    cells[0].innerHTML = `<span class="eu-type-badge eu-type-${userType}">${euEscapeHtml(EU_USER_TYPE_LABELS[userType] || userType)}</span>`;
    cells[1].innerHTML = `<span class="eu-cat-badge eu-cat-${category}">${euEscapeHtml(EU_CATEGORY_LABELS[category] || category)}</span>`;
    cells[2].innerHTML = `<strong>${euEscapeHtml(fd.get('name'))}</strong>`;
    cells[3].textContent = fd.get('description') || '';
    cells[4].textContent = fd.get('quantity');
    cells[5].innerHTML   = `<span class="price-badge">$${parseFloat(fd.get('price')).toFixed(2)}</span>`;
    cells[6].innerHTML   = isActive === '1'
        ? '<span style="color:#28a745;font-weight:600;">● Active</span>'
        : '<span style="color:#dc3545;font-weight:600;">● Inactive</span>';

    // Update data attributes for filter
    row.dataset.userType = userType;
    row.dataset.category = category;
    row.dataset.status   = isActive;

    row.style.backgroundColor = '#d4edda';
    setTimeout(() => row.style.backgroundColor = '', 2000);
}

function euAddRow(id, fd) {
    const tbody    = document.getElementById('eu-items-tbody');
    const userType = fd.get('user_type');
    const category = fd.get('item_category');
    const isActive = fd.get('is_active');

    const rowData = {
        id,
        user_type:        userType,
        item_category:    category,
        item_name:        fd.get('name'),
        item_description: fd.get('description'),
        default_quantity: fd.get('quantity'),
        unit_price:       fd.get('price'),
        is_active:        isActive,
    };

    const row = document.createElement('tr');
    row.setAttribute('data-user-type', userType);
    row.setAttribute('data-category',  category);
    row.setAttribute('data-status',    isActive);

    row.innerHTML = `
        <td><span class="eu-type-badge eu-type-${userType}">${euEscapeHtml(EU_USER_TYPE_LABELS[userType] || userType)}</span></td>
        <td><span class="eu-cat-badge eu-cat-${category}">${euEscapeHtml(EU_CATEGORY_LABELS[category] || category)}</span></td>
        <td><strong>${euEscapeHtml(fd.get('name'))}</strong></td>
        <td style="max-width:220px;font-size:0.875rem;color:#666;">${euEscapeHtml(fd.get('description') || '')}</td>
        <td style="text-align:center;">${fd.get('quantity')}</td>
        <td><span class="price-badge">$${parseFloat(fd.get('price')).toFixed(2)}</span></td>
        <td>${isActive === '1'
            ? '<span style="color:#28a745;font-weight:600;">● Active</span>'
            : '<span style="color:#dc3545;font-weight:600;">● Inactive</span>'}</td>
        <td>
            <button class="btn-edit" onclick='euOpenItemModal("edit", ${JSON.stringify(rowData)})'>✏️ Edit</button>
            <button class="btn-delete" onclick="euDeleteItem(${id})">🗑️ Delete</button>
        </td>`;

    tbody.appendChild(row);
    row.style.backgroundColor = '#d4edda';
    setTimeout(() => row.style.backgroundColor = '', 2000);
    row.scrollIntoView({ behavior: 'smooth', block: 'center' });

    // Update count
    const countEl = document.getElementById('eu-row-count');
    if (countEl) {
        const visible = Array.from(tbody.querySelectorAll('tr[data-user-type]'))
            .filter(r => r.style.display !== 'none').length;
        countEl.textContent = `(${visible} items)`;
    }
}

function euRemoveRow(id) {
    const tbody = document.getElementById('eu-items-tbody');
    const row   = euFindRow(tbody, id);
    if (!row) return;

    row.style.transition = 'opacity 0.3s, background 0.3s';
    row.style.opacity    = '0';
    row.style.background = '#f8d7da';
    setTimeout(() => {
        row.remove();
        const countEl = document.getElementById('eu-row-count');
        if (countEl) {
            const visible = Array.from(tbody.querySelectorAll('tr[data-user-type]'))
                .filter(r => r.style.display !== 'none').length;
            countEl.textContent = `(${visible} items)`;
            if (visible === 0) {
                tbody.innerHTML = `<tr id="eu-empty-row"><td colspan="8" style="text-align:center;padding:2rem;color:#999;font-style:italic;">No items yet. Click <strong>➕ Add Item</strong> to get started.</td></tr>`;
            }
        }
    }, 300);
}
</script>

<!-- ── Scoped Styles (badges + type colours) ─────────────────────────── -->
<style>
/* ── User type badges ──────────────────────────────────────────────── */
.eu-type-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 600;
    white-space: nowrap;
}
.eu-type-general   { background:#e3f2fd; color:#1565c0; }
.eu-type-technical { background:#e8f5e9; color:#2e7d32; }
.eu-type-design    { background:#fce4ec; color:#880e4f; }
.eu-type-field     { background:#fff3e0; color:#e65100; }
.eu-type-executive { background:#ede7f6; color:#4527a0; }

/* ── Category badges ────────────────────────────────────────────────── */
.eu-cat-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 600;
    white-space: nowrap;
}
.eu-cat-workstation { background:#e0f2f1; color:#00695c; }
.eu-cat-peripherals { background:#f3e5f5; color:#6a1b9a; }
.eu-cat-mobile      { background:#fff8e1; color:#f57f17; }
.eu-cat-software    { background:#e8eaf6; color:#283593; }
</style>