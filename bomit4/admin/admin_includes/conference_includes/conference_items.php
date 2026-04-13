<?php
// admin/admin_includes/conference_items.php
// Conference Room Equipment CRUD table
// Mirrors network_equipment_items.php exactly
// Uses CORRECT DB column names: room_size, equipment_category (live DB)

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($conn)) include dirname(dirname(__DIR__)) . '/db_connect.php';
require_once dirname(__DIR__) . '/admin_utilities.php';
requireAdminAuth($conn);

// ── Lookup maps (match ACTUAL live DB values) ──────────────────────────────
$conf_room_types = [
    'small'  => 'Small Room (4–6 people)',
    'medium' => 'Medium Room (8–12 people)',
    'large'  => 'Large Room (15+ people)',
];

$conf_categories = [
    'av'           => '🎥 AV Equipment',
    'connectivity' => '🔌 Connectivity',
    'furniture'    => '🪑 Furniture',
    'other'        => '📦 Other',
];

// ── Fetch all records (safe direct query with error handling) ─────────────
$records = [];
$conf_fetch_error = '';

// Verify table exists first
$table_check = $conn->query("SHOW TABLES LIKE 'conference_equipment'");
if ($table_check && $table_check->num_rows > 0) {
    $res = $conn->query("SELECT * FROM conference_equipment ORDER BY room_size, equipment_category, display_order");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $records[] = $row;
        }
    } else {
        $conf_fetch_error = 'Query error: ' . $conn->error;
    }
} else {
    $conf_fetch_error = 'Table <strong>conference_equipment</strong> does not exist. Please import the database SQL file.';
}
?>

<?php if ($conf_fetch_error): ?>
<div style="padding:1.5rem; background:#f8d7da; border:1px solid #f5c6cb; border-radius:8px; color:#721c24; margin-bottom:1rem;">
    ⚠️ <?php echo $conf_fetch_error; ?>
</div>
<?php endif; ?>

<!-- ── Filter Bar ─────────────────────────────────────────────────────── -->
<div class="filter-section">
    <label for="conf-room-filter">Filter by Room Type:</label>
    <select id="conf-room-filter" onchange="confFilterItems()">
        <option value="">All Room Types</option>
        <?php foreach ($conf_room_types as $key => $label): ?>
            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
        <?php endforeach; ?>
    </select>

    <label for="conf-cat-filter" style="margin-left:2rem;">Category:</label>
    <select id="conf-cat-filter" onchange="confFilterItems()">
        <option value="">All Categories</option>
        <?php foreach ($conf_categories as $key => $label): ?>
            <option value="<?php echo $key; ?>"><?php echo strip_tags($label); ?></option>
        <?php endforeach; ?>
    </select>
</div>

<!-- ── Action Bar ─────────────────────────────────────────────────────── -->
<div class="action-bar">
    <h4>Conference Room Equipment Items
        <span id="conf-row-count" style="font-size:.85rem; font-weight:400; color:#888; margin-left:.5rem;">
            (<?php echo count($records); ?> items)
        </span>
    </h4>
    <button class="add-btn" onclick="confOpenModal('add')">➕ Add Equipment</button>
</div>

<!-- ── Equipment Table ────────────────────────────────────────────────── -->
<table class="equipment-table">
    <thead>
        <tr>
            <th>Room Type</th>
            <th>Category</th>
            <th>Item Name</th>
            <th>Description</th>
            <th>Default Qty</th>
            <th>Unit Price</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody id="conf-items-tbody">
        <?php if (empty($records)): ?>
        <tr id="conf-empty-row">
            <td colspan="7" style="text-align:center; padding:2rem; color:#999; font-style:italic;">
                No items yet. Click <strong>➕ Add Equipment</strong> to get started.
            </td>
        </tr>
        <?php else: ?>
        <?php foreach ($records as $row): ?>
        <tr data-room-size="<?php echo htmlspecialchars($row['room_size']); ?>"
            data-category="<?php echo htmlspecialchars($row['equipment_category']); ?>">
            <td>
                <span class="conf-room-badge conf-room-<?php echo $row['room_size']; ?>">
                    <?php echo htmlspecialchars($conf_room_types[$row['room_size']] ?? ucfirst(str_replace('_',' ',$row['room_size']))); ?>
                </span>
            </td>
            <td>
                <span class="conf-cat-badge conf-cat-<?php echo $row['equipment_category']; ?>">
                    <?php echo $conf_categories[$row['equipment_category']] ?? ucfirst($row['equipment_category']); ?>
                </span>
            </td>
            <td><strong><?php echo htmlspecialchars($row['item_name']); ?></strong></td>
            <td style="max-width:200px; font-size:.875rem; color:#666;">
                <?php echo htmlspecialchars($row['item_description'] ?? ''); ?>
            </td>
            <td style="text-align:center;"><?php echo (int)$row['default_quantity']; ?></td>
            <td><span class="price-badge"><?php echo formatPrice($row['unit_price']); ?></span></td>
            <td>
                <button class="btn-edit"
                    onclick='confOpenModal("edit", this)' data-row='<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES); ?>'>
                    ✏️ Edit
                </button>
                <button class="btn-delete"
                    onclick="confDeleteItem(<?php echo (int)$row['id']; ?>)">
                    🗑️ Delete
                </button>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<!-- ── Add / Edit Modal ───────────────────────────────────────────────── -->
<div id="conf-item-modal" class="modal">
    <div class="modal-content" style="max-width:620px;">
        <div class="modal-header">
            <h3 id="conf-modal-title">Add Conference Equipment</h3>
            <button class="modal-close" onclick="confCloseModal()">&times;</button>
        </div>

        <form id="conf-item-form" onsubmit="confSaveItem(event)">
            <input type="hidden" id="conf-item-id" name="id">

            <!-- Row 1: Room Type + Category -->
            <div style="display:flex; gap:1rem;">
                <div class="form-group" style="flex:1;">
                    <label>Room Type <span style="color:#dc3545;">*</span></label>
                    <select id="conf-room-type" name="room_size" required>
                        <?php foreach ($conf_room_types as $key => $label): ?>
                            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex:1;">
                    <label>Category <span style="color:#dc3545;">*</span></label>
                    <select id="conf-category" name="equipment_category" required>
                        <?php foreach ($conf_categories as $key => $label): ?>
                            <option value="<?php echo $key; ?>"><?php echo strip_tags($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Item Name -->
            <div class="form-group">
                <label>Item Name <span style="color:#dc3545;">*</span></label>
                <input type="text" id="conf-item-name" name="name"
                       placeholder="e.g. PTZ Camera, Ceiling Microphone Array" required>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label>Description / Model</label>
                <textarea id="conf-item-desc" name="description" rows="2"
                    placeholder="e.g. Logitech Rally Plus with pan-tilt-zoom"></textarea>
            </div>

            <!-- Row 2: Qty + Price -->
            <div style="display:flex; gap:1rem;">
                <div class="form-group" style="flex:1;">
                    <label>Default Quantity <span style="color:#dc3545;">*</span></label>
                    <input type="number" id="conf-item-qty" name="quantity" min="0" value="1" required>
                </div>
                <div class="form-group" style="flex:1;">
                    <label>Unit Price (USD) <span style="color:#dc3545;">*</span></label>
                    <input type="number" id="conf-item-price" name="price"
                           step="0.01" min="0" value="0.00" required>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="confCloseModal()">Cancel</button>
                <button type="submit" class="btn-save" id="conf-save-btn">Save Equipment</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Delete Confirm Modal ───────────────────────────────────────────── -->
<div id="conf-delete-modal" class="modal">
    <div class="modal-content" style="max-width:420px; text-align:center;">
        <div style="font-size:3rem; margin-bottom:1rem;">🗑️</div>
        <h3 style="color:#dc3545; margin-bottom:.75rem;">Delete Item?</h3>
        <p style="color:#666; margin-bottom:1.5rem;" id="conf-delete-text">
            This will permanently remove the item from the database.
        </p>
        <div style="display:flex; gap:1rem; justify-content:center;">
            <button class="btn-cancel" onclick="document.getElementById('conf-delete-modal').classList.remove('active')"
                style="padding:.75rem 2rem;">Cancel</button>
            <button class="btn-delete" id="conf-delete-confirm-btn"
                style="padding:.75rem 2rem; border-radius:8px;">Yes, Delete</button>
        </div>
    </div>
</div>


<!-- ══════════════════════════════════════════════════════════════════════
     JAVASCRIPT
     ══════════════════════════════════════════════════════════════════════ -->
<script>
const CONF_ROOM_TYPE_LABELS = <?php echo json_encode($conf_room_types); ?>;

// Safe JSON for data-row attributes — replaces single quotes to avoid onclick breakage
function confSafeJson(obj) {
    return JSON.stringify(obj).replace(/'/g, '&#39;');
}

// Refresh overview tab counts after add/edit/delete without page reload
function confRefreshOverviewCounts() {
    const tbody = document.getElementById('conf-items-tbody');
    if (!tbody) return;

    // Count visible items per room size (all, not filtered)
    const roomCounts = {};
    let total = 0;
    tbody.querySelectorAll('tr[data-room-size]').forEach(tr => {
        const rs = tr.dataset.roomSize;
        roomCounts[rs] = (roomCounts[rs] || 0) + 1;
        total++;
    });

    // Update per-room stat cards in overview tab
    Object.entries(roomCounts).forEach(([rs, cnt]) => {
        const el = document.getElementById('conf-overview-count-' + rs);
        if (el) {
            const active = cnt; // all active since we only store active
            el.textContent = cnt + ' items • ' + active + ' active';
        }
    });

    // Update total badge in overview header
    const totalBadge = document.getElementById('conf-overview-total-badge');
    if (totalBadge) totalBadge.textContent = '(' + total + ' total items)';

    // Also update row count in items tab
    const rowCount = document.getElementById('conf-row-count');
    if (rowCount) rowCount.textContent = '(' + total + ' items)';
}
const CONF_CATEGORY_LABELS  = <?php echo json_encode(array_map('strip_tags', $conf_categories)); ?>;

// ── Filter ────────────────────────────────────────────────────────────────
function confFilterItems() {
    const room = document.getElementById('conf-room-filter').value;
    const cat  = document.getElementById('conf-cat-filter').value;
    let visible = 0;

    document.querySelectorAll('#conf-items-tbody tr[data-room-size]').forEach(row => {
        const rMatch = !room || row.dataset.roomSize === room;
        const cMatch = !cat  || row.dataset.category  === cat;
        row.style.display = (rMatch && cMatch) ? '' : 'none';
        if (rMatch && cMatch) visible++;
    });

    const cnt = document.getElementById('conf-row-count');
    if (cnt) cnt.textContent = `(${visible} items)`;
}

// ── Modal helpers ─────────────────────────────────────────────────────────
function confCloseModal() {
    document.getElementById('conf-item-modal').classList.remove('active');
}

function confOpenModal(mode, btnOrData = null) {
    document.getElementById('conf-item-form').reset();
    document.getElementById('conf-modal-title').textContent =
        mode === 'add' ? '➕ Add Conference Equipment' : '✏️ Edit Conference Equipment';

    if (mode === 'add') {
        document.getElementById('conf-item-id').value    = '';
        document.getElementById('conf-item-qty').value   = '1';
        document.getElementById('conf-item-price').value = '0.00';
    } else {
        // Read data from data-row attribute (safe — no inline JSON quoting issues)
        const raw  = btnOrData instanceof Element
            ? btnOrData.getAttribute('data-row')
            : JSON.stringify(btnOrData);
        const data = JSON.parse(raw);

        document.getElementById('conf-item-id').value    = data.id;
        // Use room_size (actual DB column) — pre-selects current room type
        document.getElementById('conf-room-type').value  = data.room_size || data.room_type || '';
        document.getElementById('conf-category').value   = data.equipment_category || '';
        // item_name from JSON is already decoded (no &quot;) since JSON.parse handles it
        document.getElementById('conf-item-name').value  = data.item_name || '';
        document.getElementById('conf-item-desc').value  = data.item_description || '';
        document.getElementById('conf-item-qty').value   = data.default_quantity || 0;
        document.getElementById('conf-item-price').value = data.unit_price || 0;
    }

    document.getElementById('conf-item-modal').classList.add('active');
}

// ── Save (Add / Update) ───────────────────────────────────────────────────
async function confSaveItem(event) {
    event.preventDefault();
    const form     = event.target;
    const formData = new FormData(form);
    const id       = formData.get('id');
    formData.append('action', id ? 'update_conference_item' : 'add_conference_item');

    const btn = document.getElementById('conf-save-btn');
    btn.disabled    = true;
    btn.textContent = 'Saving…';

    try {
        const response = await fetch('admin_includes/handlers/conference_handler.php', {
            method: 'POST', body: formData
        });
        const text = await response.text();
        let result;
        try { result = JSON.parse(text); }
        catch (e) { confShowAlert('Server error: Invalid response format', 'error'); return; }

        if (result.success) {
            confShowAlert(result.message, 'success');
            confCloseModal();
            if (id) {
                confUpdateRow(id, formData);
            } else {
                confAddRow(result.data.id, formData);
                const empty = document.getElementById('conf-empty-row');
                if (empty) empty.remove();
            }
        } else {
            confShowAlert(result.message, 'error');
        }
    } catch (err) {
        confShowAlert('Network error: ' + err.message, 'error');
    } finally {
        btn.disabled    = false;
        btn.textContent = 'Save Equipment';
    }
}

// ── Delete ────────────────────────────────────────────────────────────────
function confDeleteItem(id) {
    const tbody  = document.getElementById('conf-items-tbody');
    const row    = confFindRow(tbody, id);
    const name   = row ? row.querySelector('strong')?.textContent : `Item #${id}`;

    document.getElementById('conf-delete-text').innerHTML =
        `Permanently remove <strong>${confEscapeHtml(name)}</strong> from the database.`;

    const btn = document.getElementById('conf-delete-confirm-btn');
    btn.onclick = async () => {
        btn.disabled    = true;
        btn.textContent = 'Deleting…';

        const fd = new FormData();
        fd.append('action', 'delete_conference_item');
        fd.append('id', id);

        try {
            const response = await fetch('admin_includes/handlers/conference_handler.php', {
                method: 'POST', body: fd
            });
            const text = await response.text();
            let result;
            try { result = JSON.parse(text); }
            catch (e) { confShowAlert('Server error', 'error'); return; }

            if (result.success) {
                confShowAlert(result.message, 'success');
                document.getElementById('conf-delete-modal').classList.remove('active');
                confRemoveRow(id);
            } else {
                confShowAlert(result.message, 'error');
            }
        } catch (err) {
            confShowAlert('Network error: ' + err.message, 'error');
        } finally {
            btn.disabled    = false;
            btn.textContent = 'Yes, Delete';
        }
    };

    document.getElementById('conf-delete-modal').classList.add('active');
}

// ── DOM Row Helpers ───────────────────────────────────────────────────────
function confFindRow(tbody, id) {
    return Array.from(tbody.querySelectorAll('tr')).find(tr => {
        const del = tr.querySelector('.btn-delete');
        return del && del.getAttribute('onclick').includes(`(${id})`);
    });
}

function confUpdateRow(id, fd) {
    const tbody   = document.getElementById('conf-items-tbody');
    const row     = confFindRow(tbody, id);
    if (!row) return;

    const roomType = fd.get('room_size');
    const cat      = fd.get('equipment_category');
    const cells    = row.querySelectorAll('td');

    cells[0].innerHTML = `<span class="conf-room-badge conf-room-${roomType}">${confEscapeHtml(CONF_ROOM_TYPE_LABELS[roomType] || roomType)}</span>`;
    cells[1].innerHTML = `<span class="conf-cat-badge conf-cat-${cat}">${confEscapeHtml(CONF_CATEGORY_LABELS[cat] || cat)}</span>`;
    cells[2].innerHTML = `<strong>${confEscapeHtml(fd.get('name'))}</strong>`;
    cells[3].textContent = fd.get('description') || '';
    cells[4].textContent = fd.get('quantity');
    cells[5].innerHTML   = `<span class="price-badge">$${parseFloat(fd.get('price')).toFixed(2)}</span>`;

    row.dataset.roomSize = roomType;
    row.dataset.category = cat;

    // Rebuild rowData for the edit button so future edits have fresh data
    const updatedRowData = {
        id: id,
        room_size: roomType,
        equipment_category: cat,
        item_name: fd.get('name'),
        item_description: fd.get('description') || '',
        default_quantity: fd.get('quantity'),
        unit_price: fd.get('price'),
    };
    const editBtn = row.querySelector('.btn-edit');
    if (editBtn) editBtn.setAttribute('data-row', confSafeJson(updatedRowData));

    row.style.backgroundColor = '#d4edda';
    setTimeout(() => row.style.backgroundColor = '', 2000);

    // Update overview tab counts without page reload
    confRefreshOverviewCounts();
}

function confAddRow(id, fd) {
    const tbody    = document.getElementById('conf-items-tbody');
    const roomType = fd.get('room_size');
    const cat      = fd.get('equipment_category');

    const rowData = {
        id, room_size: roomType, equipment_category: cat,
        item_name: fd.get('name'), item_description: fd.get('description'),
        default_quantity: fd.get('quantity'), unit_price: fd.get('price'),
    };

    const row = document.createElement('tr');
    row.setAttribute('data-room-size', roomType);
    row.setAttribute('data-category',  cat);
    row.innerHTML = `
        <td><span class="conf-room-badge conf-room-${roomType}">${confEscapeHtml(CONF_ROOM_TYPE_LABELS[roomType] || roomType)}</span></td>
        <td><span class="conf-cat-badge conf-cat-${cat}">${confEscapeHtml(CONF_CATEGORY_LABELS[cat] || cat)}</span></td>
        <td><strong>${confEscapeHtml(fd.get('name'))}</strong></td>
        <td style="max-width:200px;font-size:.875rem;color:#666;">${confEscapeHtml(fd.get('description') || '')}</td>
        <td style="text-align:center;">${fd.get('quantity')}</td>
        <td><span class="price-badge">$${parseFloat(fd.get('price')).toFixed(2)}</span></td>
        <td>
            <button class="btn-edit" onclick='confOpenModal("edit", this)' data-row='${confSafeJson(rowData)}'>✏️ Edit</button>
            <button class="btn-delete" onclick="confDeleteItem(${id})">🗑️ Delete</button>
        </td>`;

    tbody.appendChild(row);
    row.style.backgroundColor = '#d4edda';
    setTimeout(() => row.style.backgroundColor = '', 2000);
    row.scrollIntoView({ behavior: 'smooth', block: 'center' });

    // Refresh all overview counts
    confRefreshOverviewCounts();
}

function confRemoveRow(id) {
    const tbody = document.getElementById('conf-items-tbody');
    const row   = confFindRow(tbody, id);
    if (!row) return;
    row.style.transition = 'opacity 0.3s';
    row.style.opacity    = '0';
    setTimeout(() => {
        row.remove();
        // Refresh all overview counts after removal
        confRefreshOverviewCounts();
    }, 300);
}
</script>

<!-- ── Scoped badge styles ────────────────────────────────────────────── -->
<style>
/* Room type badges */
.conf-room-badge {
    display: inline-block; padding: 3px 10px; border-radius: 20px;
    font-size: .78rem; font-weight: 600; white-space: nowrap;
}
.conf-room-small     { background: #e8f5e9; color: #2e7d32; }
.conf-room-medium { background: #e3f2fd; color: #1565c0; }
.conf-room-large   { background: #ede7f6; color: #4527a0; }
.conf-room-training     { background: #fff3e0; color: #e65100; }

/* Category badges */
.conf-cat-badge {
    display: inline-block; padding: 3px 10px; border-radius: 20px;
    font-size: .78rem; font-weight: 600; white-space: nowrap;
}
.conf-cat-av    { background: #fce4ec; color: #880e4f; }
.conf-cat-audio    { background: #e8eaf6; color: #283593; }
.conf-cat-display  { background: #e0f2f1; color: #00695c; }
.conf-cat-control  { background: #fff8e1; color: #f57f17; }
.conf-cat-furniture{ background: #f3e5f5; color: #6a1b9a; }
.conf-cat-other    { background: #eceff1; color: #455a64; }

/* Table row hover */
#conf-items-tbody tr:hover {
    background: linear-gradient(90deg, rgba(128,199,160,.05), rgba(0,112,239,.05)) !important;
}
</style>