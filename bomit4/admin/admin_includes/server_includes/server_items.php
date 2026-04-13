<?php
// admin/admin_includes/server_items.php
// Server VM Items Management — no pricing columns
// Table: server_equipment

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($conn)) include dirname(dirname(__DIR__)) . '/db_connect.php';
require_once dirname(__DIR__) . '/admin_utilities.php';
requireAdminAuth($conn);

// Auto-create table if it doesn't exist
$conn->query("
    CREATE TABLE IF NOT EXISTS server_equipment (
        id               INT AUTO_INCREMENT PRIMARY KEY,
        item_type        VARCHAR(50)   NOT NULL DEFAULT 'application'
            COMMENT 'core_infra | project_req | application',
        item_name        VARCHAR(255)  NOT NULL,
        item_description TEXT,
        default_cores    INT           NOT NULL DEFAULT 2,
        default_memory   INT           NOT NULL DEFAULT 4,
        default_os_storage  INT        NOT NULL DEFAULT 100,
        default_data_storage INT       NOT NULL DEFAULT 100,
        is_editable      TINYINT(1)    NOT NULL DEFAULT 1
            COMMENT '0=fixed default, 1=user can change qty',
        is_active        TINYINT(1)    NOT NULL DEFAULT 1,
        display_order    INT           NOT NULL DEFAULT 0,
        created_at       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
        updated_at       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    COMMENT='Server VM and infrastructure items'
");

// Seed core items if empty
$count = $conn->query("SELECT COUNT(*) as c FROM server_equipment")->fetch_assoc()['c'];
if ($count == 0) {
    $conn->query("INSERT INTO server_equipment
        (item_type, item_name, item_description, default_cores, default_memory, default_os_storage, default_data_storage, is_editable, display_order) VALUES
        ('core_infra', 'Backup Proxy',        'Veeam backup proxy',               4,  16, 100, 100,  0, 1),
        ('core_infra', 'Domain Controller',   'Active Directory domain controller', 4, 12, 100, 100,  0, 2),
        ('core_infra', 'DHCP & DFS',          'DHCP and distributed file service', 2,  4, 100, 100,  0, 3),
        ('core_infra', 'Print & Scan',        'Print and scan server',             2,  4, 100, 200,  0, 4),
        ('core_infra', 'SCCM',               'System Center Configuration Manager',4, 8, 100,1500,  0, 5),
        ('project_req','General Files Server','General shared file storage',        8, 16, 100,   0,  1, 6),
        ('application','Easy Plant App',      'Easy Plant application server',      2,  8, 100, 100,  1, 7),
        ('application','Easy Plant DB',       'Easy Plant database server',         2,  8, 100, 500,  1, 8),
        ('application','Jobcard Server',      'Jobcard management server',          2,  4, 100, 200,  1, 9),
        ('application','License Server',      'Software license server',            2,  4, 100, 100,  1,10)
    ");
}

// Lookup
$srv_types = [
    'core_infra'  => '⚙️ Core Infrastructure',
    'project_req' => '📁 Project Requirement',
    'application' => '💻 Application Server',
];

// Fetch
$records = [];
$res = $conn->query("SELECT * FROM server_equipment ORDER BY display_order, id");
if ($res) { while ($r = $res->fetch_assoc()) $records[] = $r; }
?>

<!-- Filter Bar -->
<div class="filter-section">
    <label for="srv-type-filter">Filter by Type:</label>
    <select id="srv-type-filter" onchange="srvFilterItems()">
        <option value="">All Types</option>
        <?php foreach ($srv_types as $k => $l): ?>
            <option value="<?php echo $k; ?>"><?php echo strip_tags($l); ?></option>
        <?php endforeach; ?>
    </select>
</div>

<!-- Action Bar -->
<div class="action-bar">
    <h4>Server VM Items
        <span id="srv-row-count" style="font-size:.85rem;font-weight:400;color:#888;margin-left:.5rem;">
            (<?php echo count($records); ?> items)
        </span>
    </h4>
    <button class="add-btn" onclick="srvOpenModal('add')">➕ Add VM Item</button>
</div>

<!-- Table -->
<table class="equipment-table">
    <thead>
        <tr>
            <th>Type</th>
            <th>Item Name</th>
            <th>Description</th>
            <th style="text-align:center;">Cores</th>
            <th style="text-align:center;">Memory (GB)</th>
            <th style="text-align:center;">OS Storage (GB)</th>
            <th style="text-align:center;">Data Storage (GB)</th>
            <th style="text-align:center;">User Editable</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody id="srv-items-tbody">
        <?php if (empty($records)): ?>
        <tr id="srv-empty-row">
            <td colspan="9" style="text-align:center;padding:2rem;color:#999;font-style:italic;">
                No items yet. Click <strong>➕ Add VM Item</strong>.
            </td>
        </tr>
        <?php else: ?>
        <?php foreach ($records as $row): ?>
        <tr data-type="<?php echo htmlspecialchars($row['item_type']); ?>">
            <td>
                <span class="srv-type-badge srv-type-<?php echo $row['item_type']; ?>">
                    <?php echo $srv_types[$row['item_type']] ?? ucfirst($row['item_type']); ?>
                </span>
            </td>
            <td><strong><?php echo htmlspecialchars($row['item_name']); ?></strong></td>
            <td style="font-size:.875rem;color:#666;max-width:200px;">
                <?php echo htmlspecialchars($row['item_description'] ?? ''); ?>
            </td>
            <td style="text-align:center;"><?php echo $row['default_cores']; ?></td>
            <td style="text-align:center;"><?php echo $row['default_memory']; ?></td>
            <td style="text-align:center;"><?php echo $row['default_os_storage']; ?></td>
            <td style="text-align:center;"><?php echo number_format($row['default_data_storage']); ?></td>
            <td style="text-align:center;">
                <?php if ($row['is_editable']): ?>
                    <span style="color:#10b981;font-weight:600;">✔ Yes</span>
                <?php else: ?>
                    <span style="color:#9ca3af;">Fixed</span>
                <?php endif; ?>
            </td>
            <td>
                <button class="btn-edit"
                    onclick='srvOpenModal("edit", this)'
                    data-row='<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES); ?>'>
                    ✏️ Edit
                </button>
                <button class="btn-delete"
                    onclick="srvDeleteItem(<?php echo (int)$row['id']; ?>)">
                    🗑️ Delete
                </button>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<!-- Add/Edit Modal -->
<div id="srv-item-modal" class="modal">
    <div class="modal-content" style="max-width:640px;">
        <div class="modal-header">
            <h3 id="srv-modal-title">Add VM Item</h3>
            <button class="modal-close" onclick="document.getElementById('srv-item-modal').classList.remove('active')">&times;</button>
        </div>
        <form id="srv-item-form" onsubmit="srvSaveItem(event)">
            <input type="hidden" id="srv-item-id" name="id">

            <div style="display:flex;gap:1rem;">
                <div class="form-group" style="flex:1;">
                    <label>Item Type <span style="color:#dc3545;">*</span></label>
                    <select id="srv-item-type" name="item_type" required>
                        <?php foreach ($srv_types as $k => $l): ?>
                            <option value="<?php echo $k; ?>"><?php echo strip_tags($l); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex:1;">
                    <label>User Editable</label>
                    <select id="srv-item-editable" name="is_editable">
                        <option value="1">Yes — user can adjust</option>
                        <option value="0">No — fixed default</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Item Name <span style="color:#dc3545;">*</span></label>
                <input type="text" id="srv-item-name" name="name" placeholder="e.g. Easy Plant App" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="srv-item-desc" name="description" rows="2"
                    placeholder="Brief description of this VM's purpose"></textarea>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:1rem;">
                <div class="form-group">
                    <label>Default Cores <span style="color:#dc3545;">*</span></label>
                    <input type="number" id="srv-item-cores"   name="cores"       min="1" value="2" required>
                </div>
                <div class="form-group">
                    <label>Memory (GB) <span style="color:#dc3545;">*</span></label>
                    <input type="number" id="srv-item-memory"  name="memory"      min="1" value="4" required>
                </div>
                <div class="form-group">
                    <label>OS Storage (GB)</label>
                    <input type="number" id="srv-item-os"      name="os_storage"  min="0" value="100">
                </div>
                <div class="form-group">
                    <label>Data Storage (GB)</label>
                    <input type="number" id="srv-item-data"    name="data_storage" min="0" value="100">
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-cancel"
                    onclick="document.getElementById('srv-item-modal').classList.remove('active')">Cancel</button>
                <button type="submit" class="btn-save" id="srv-save-btn">Save Item</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirm Modal -->
<div id="srv-delete-modal" class="modal">
    <div class="modal-content" style="max-width:420px;text-align:center;">
        <div style="font-size:3rem;margin-bottom:1rem;">🗑️</div>
        <h3 style="color:#dc3545;margin-bottom:.75rem;">Delete VM Item?</h3>
        <p style="color:#666;margin-bottom:1.5rem;" id="srv-delete-text">This will permanently remove the item.</p>
        <div style="display:flex;gap:1rem;justify-content:center;">
            <button class="btn-cancel" onclick="document.getElementById('srv-delete-modal').classList.remove('active')"
                style="padding:.75rem 2rem;">Cancel</button>
            <button class="btn-delete" id="srv-delete-confirm-btn"
                style="padding:.75rem 2rem;border-radius:8px;">Yes, Delete</button>
        </div>
    </div>
</div>

<style>
.srv-type-badge { display:inline-block;padding:3px 10px;border-radius:20px;font-size:.78rem;font-weight:600;white-space:nowrap; }
.srv-type-core_infra  { background:#e3f2fd;color:#1565c0; }
.srv-type-project_req { background:#fff3e0;color:#e65100; }
.srv-type-application { background:#e8f5e9;color:#2e7d32; }
#srv-items-tbody tr:hover { background:linear-gradient(90deg,rgba(0,112,239,.04),rgba(128,199,160,.04)) !important; }
</style>

<script>
const SRV_TYPE_LABELS = <?php echo json_encode(array_map('strip_tags', $srv_types)); ?>;

function srvFilterItems() {
    const type = document.getElementById('srv-type-filter').value;
    let vis = 0;
    document.querySelectorAll('#srv-items-tbody tr[data-type]').forEach(tr => {
        const show = !type || tr.dataset.type === type;
        tr.style.display = show ? '' : 'none';
        if (show) vis++;
    });
    const el = document.getElementById('srv-row-count');
    if (el) el.textContent = '(' + vis + ' items)';
}

function srvOpenModal(mode, btnOrData = null) {
    document.getElementById('srv-item-form').reset();
    document.getElementById('srv-modal-title').textContent =
        mode === 'add' ? '➕ Add VM Item' : '✏️ Edit VM Item';

    if (mode === 'add') {
        document.getElementById('srv-item-id').value = '';
    } else {
        const raw  = btnOrData instanceof Element ? btnOrData.getAttribute('data-row') : JSON.stringify(btnOrData);
        const data = JSON.parse(raw);
        document.getElementById('srv-item-id').value         = data.id;
        document.getElementById('srv-item-type').value       = data.item_type;
        document.getElementById('srv-item-editable').value   = data.is_editable;
        document.getElementById('srv-item-name').value       = data.item_name;
        document.getElementById('srv-item-desc').value       = data.item_description || '';
        document.getElementById('srv-item-cores').value      = data.default_cores;
        document.getElementById('srv-item-memory').value     = data.default_memory;
        document.getElementById('srv-item-os').value         = data.default_os_storage;
        document.getElementById('srv-item-data').value       = data.default_data_storage;
    }
    document.getElementById('srv-item-modal').classList.add('active');
}

async function srvSaveItem(event) {
    event.preventDefault();
    const fd  = new FormData(event.target);
    const id  = fd.get('id');
    fd.append('action', id ? 'update_server_item' : 'add_server_item');

    const btn = document.getElementById('srv-save-btn');
    btn.disabled = true; btn.textContent = 'Saving…';

    try {
        const resp = await fetch('admin_includes/handlers/server_handler.php', { method:'POST', body:fd });
        const text = await resp.text();
        let result;
        try { result = JSON.parse(text); } catch(e) { srvShowAlert('Server error','error'); return; }

        if (result.success) {
            srvShowAlert(result.message, 'success');
            document.getElementById('srv-item-modal').classList.remove('active');
            id ? srvUpdateRow(id, fd) : srvAddRow(result.data.id, fd);
            const emptyRow = document.getElementById('srv-empty-row');
            if (emptyRow) emptyRow.remove();
        } else {
            srvShowAlert(result.message, 'error');
        }
    } catch(e) { srvShowAlert('Network error: ' + e.message, 'error'); }
    finally { btn.disabled = false; btn.textContent = 'Save Item'; }
}

function srvDeleteItem(id) {
    const tbody = document.getElementById('srv-items-tbody');
    const row   = Array.from(tbody.querySelectorAll('tr')).find(tr => {
        const b = tr.querySelector('.btn-delete');
        return b && b.getAttribute('onclick').includes('(' + id + ')');
    });
    const name = row ? row.querySelector('strong')?.textContent : 'Item #' + id;
    document.getElementById('srv-delete-text').innerHTML =
        'Permanently remove <strong>' + srvEscapeHtml(name) + '</strong>?';

    const btn = document.getElementById('srv-delete-confirm-btn');
    btn.onclick = async () => {
        btn.disabled = true; btn.textContent = 'Deleting…';
        const fd = new FormData();
        fd.append('action', 'delete_server_item');
        fd.append('id', id);
        try {
            const resp = await fetch('admin_includes/handlers/server_handler.php', { method:'POST', body:fd });
            const result = await resp.json();
            if (result.success) {
                srvShowAlert(result.message, 'success');
                document.getElementById('srv-delete-modal').classList.remove('active');
                const row2 = Array.from(document.querySelectorAll('#srv-items-tbody tr')).find(tr => {
                    const b = tr.querySelector('.btn-delete');
                    return b && b.getAttribute('onclick').includes('(' + id + ')');
                });
                if (row2) { row2.style.opacity = '0'; row2.style.transition = 'opacity .3s'; setTimeout(() => row2.remove(), 300); }
                const el = document.getElementById('srv-row-count');
                if (el) {
                    const vis = document.querySelectorAll('#srv-items-tbody tr[data-type]').length;
                    el.textContent = '(' + (vis - 1) + ' items)';
                }
            } else { srvShowAlert(result.message, 'error'); }
        } catch(e) { srvShowAlert('Error: ' + e.message, 'error'); }
        finally { btn.disabled = false; btn.textContent = 'Yes, Delete'; }
    };
    document.getElementById('srv-delete-modal').classList.add('active');
}

function srvUpdateRow(id, fd) {
    const tbody = document.getElementById('srv-items-tbody');
    const row = Array.from(tbody.querySelectorAll('tr')).find(tr => {
        const b = tr.querySelector('.btn-delete');
        return b && b.getAttribute('onclick').includes('(' + id + ')');
    });
    if (!row) return;
    const type = fd.get('item_type');
    const editable = fd.get('is_editable') === '1';
    const cells = row.querySelectorAll('td');
    cells[0].innerHTML = `<span class="srv-type-badge srv-type-${type}">${srvEscapeHtml(SRV_TYPE_LABELS[type] || type)}</span>`;
    cells[1].innerHTML = `<strong>${srvEscapeHtml(fd.get('name'))}</strong>`;
    cells[2].textContent = fd.get('description') || '';
    cells[3].textContent = fd.get('cores');
    cells[4].textContent = fd.get('memory');
    cells[5].textContent = fd.get('os_storage');
    cells[6].textContent = parseInt(fd.get('data_storage')).toLocaleString();
    cells[7].innerHTML = editable
        ? '<span style="color:#10b981;font-weight:600;">✔ Yes</span>'
        : '<span style="color:#9ca3af;">Fixed</span>';
    row.dataset.type = type;

    // Update data-row on edit button
    const updatedData = {
        id, item_type: type, item_name: fd.get('name'),
        item_description: fd.get('description'), is_editable: fd.get('is_editable'),
        default_cores: fd.get('cores'), default_memory: fd.get('memory'),
        default_os_storage: fd.get('os_storage'), default_data_storage: fd.get('data_storage'),
    };
    const editBtn = row.querySelector('.btn-edit');
    if (editBtn) editBtn.setAttribute('data-row', JSON.stringify(updatedData).replace(/'/g,"&#39;"));

    row.style.backgroundColor = '#d4edda';
    setTimeout(() => row.style.backgroundColor = '', 2000);
}

function srvAddRow(id, fd) {
    const tbody = document.getElementById('srv-items-tbody');
    const type  = fd.get('item_type');
    const rowData = {
        id, item_type: type, item_name: fd.get('name'),
        item_description: fd.get('description'), is_editable: fd.get('is_editable'),
        default_cores: fd.get('cores'), default_memory: fd.get('memory'),
        default_os_storage: fd.get('os_storage'), default_data_storage: fd.get('data_storage'),
    };
    const tr = document.createElement('tr');
    tr.setAttribute('data-type', type);
    tr.innerHTML = `
        <td><span class="srv-type-badge srv-type-${type}">${srvEscapeHtml(SRV_TYPE_LABELS[type] || type)}</span></td>
        <td><strong>${srvEscapeHtml(fd.get('name'))}</strong></td>
        <td style="font-size:.875rem;color:#666;max-width:200px;">${srvEscapeHtml(fd.get('description') || '')}</td>
        <td style="text-align:center;">${fd.get('cores')}</td>
        <td style="text-align:center;">${fd.get('memory')}</td>
        <td style="text-align:center;">${fd.get('os_storage')}</td>
        <td style="text-align:center;">${parseInt(fd.get('data_storage')).toLocaleString()}</td>
        <td style="text-align:center;">${fd.get('is_editable') === '1'
            ? '<span style="color:#10b981;font-weight:600;">✔ Yes</span>'
            : '<span style="color:#9ca3af;">Fixed</span>'}</td>
        <td>
            <button class="btn-edit" onclick='srvOpenModal("edit",this)'
                data-row='${JSON.stringify(rowData).replace(/'/g,"&#39;")}'>✏️ Edit</button>
            <button class="btn-delete" onclick="srvDeleteItem(${id})">🗑️ Delete</button>
        </td>`;
    tbody.appendChild(tr);
    tr.style.backgroundColor = '#d4edda';
    setTimeout(() => tr.style.backgroundColor = '', 2000);
    tr.scrollIntoView({ behavior:'smooth', block:'center' });
    const el = document.getElementById('srv-row-count');
    if (el) el.textContent = '(' + tbody.querySelectorAll('tr[data-type]').length + ' items)';
}
</script>
