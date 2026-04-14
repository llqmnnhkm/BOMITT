<?php
// admin/admin_includes/account_list.php
// Lists all user accounts with actions: Edit, Toggle Active, Delete

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($conn)) include dirname(__DIR__) . '/db_connect.php';
require_once __DIR__ . '/admin_utilities.php';
requireAdminAuth($conn);

// Fetch all accounts
$accounts = [];
$res = $conn->query(
    "SELECT id, user_id, full_name, email, department, phone, role,
            is_active, must_change_password, last_login, created_by, created_at
     FROM users ORDER BY role DESC, created_at DESC"
);
if ($res) {
    while ($r = $res->fetch_assoc()) $accounts[] = $r;
}

$total   = count($accounts);
$active  = count(array_filter($accounts, fn($a) => $a['is_active'] == 1));
$guests  = count(array_filter($accounts, fn($a) => $a['role'] === 'guest'));
$admins  = count(array_filter($accounts, fn($a) => $a['role'] === 'admin'));
?>

<!-- Stat cards -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem;">
    <?php foreach ([
        ['','Total Accounts', $total,   '#1565c0','#e3f2fd'],
        ['','Active',         $active,  '#2e7d32','#e8f5e9'],
        ['','Guests',         $guests,  '#e65100','#fff3e0'],
        ['','Admins',         $admins,  '#6a1b9a','#f3e5f5'],
    ] as [$icon,$label,$val,$color,$bg]): ?>
    <div style="background:<?php echo $bg; ?>;border-left:4px solid <?php echo $color; ?>;padding:1rem;border-radius:10px;">
        <div style="font-size:1.4rem;"><?php echo $icon; ?></div>
        <div style="font-weight:700;color:<?php echo $color; ?>;margin:4px 0;"><?php echo $label; ?></div>
        <div style="font-size:1.8rem;font-weight:800;color:<?php echo $color; ?>"><?php echo $val; ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Search + filter bar -->
<div class="filter-section" style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;margin-bottom:1rem;">
    <input type="text" id="acc-search" placeholder="Search by name, ID, email, department…"
           oninput="accFilterList()"
           style="flex:1;min-width:220px;padding:9px 12px;border:1px solid #ccc;border-radius:8px;font-family:Montserrat;font-size:.9rem;">
    <select id="acc-role-filter" onchange="accFilterList()"
            style="padding:9px 12px;border:1px solid #ccc;border-radius:8px;font-family:Montserrat;font-size:.9rem;">
        <option value="">All Roles</option>
        <option value="guest">Guest</option>
        <option value="admin">Admin</option>
    </select>
    <select id="acc-status-filter" onchange="accFilterList()"
            style="padding:9px 12px;border:1px solid #ccc;border-radius:8px;font-family:Montserrat;font-size:.9rem;">
        <option value="">All Status</option>
        <option value="1">Active</option>
        <option value="0">Inactive</option>
    </select>
    <span id="acc-list-count" style="font-size:.85rem;color:#888;white-space:nowrap;">
        <?php echo $total; ?> accounts
    </span>
</div>

<!-- Accounts table -->
<table class="equipment-table" id="acc-table">
    <thead>
        <tr>
            <th>User</th>
            <th>Login ID / Email</th>
            <th>Department</th>
            <th>Phone</th>
            <th>Role</th>
            <th>Status</th>
            <th>Last Login</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody id="acc-tbody">
        <?php if (empty($accounts)): ?>
        <tr>
            <td colspan="8" style="text-align:center;padding:2rem;color:#999;font-style:italic;">
                No accounts found.
            </td>
        </tr>
        <?php else: ?>
        <?php foreach ($accounts as $acc):
            $initials = strtoupper(substr($acc['full_name'] ?: $acc['user_id'], 0, 1)
                      . (strpos($acc['full_name'] ?? '', ' ') !== false
                         ? substr(strrchr($acc['full_name'], ' '), 1, 1) : ''));
            $isAdmin   = $acc['role'] === 'admin';
            $isActive  = $acc['is_active'] == 1;
            $avatarBg  = $isAdmin ? '#4527a0' : '#1565c0';
        ?>
        <tr data-role="<?php echo $acc['role']; ?>"
            data-status="<?php echo $acc['is_active']; ?>"
            data-search="<?php echo htmlspecialchars(strtolower(
                ($acc['full_name']  ?? '') . ' ' .
                ($acc['user_id']   ?? '') . ' ' .
                ($acc['email']     ?? '') . ' ' .
                ($acc['department'] ?? '')
            )); ?>"
            style="opacity:<?php echo $isActive ? '1' : '0.55'; ?>;">

            <!-- User avatar + name -->
            <td style="display:flex;align-items:center;gap:10px;padding:12px;">
                <div style="width:38px;height:38px;border-radius:50%;background:<?php echo $avatarBg; ?>;
                            color:white;font-weight:700;font-size:.9rem;display:flex;align-items:center;
                            justify-content:center;flex-shrink:0;">
                    <?php echo htmlspecialchars($initials); ?>
                </div>
                <div>
                    <div style="font-weight:600;color:#2d3748;">
                        <?php echo htmlspecialchars($acc['full_name'] ?: '—'); ?>
                    </div>
                    <?php if ($acc['must_change_password']): ?>
                    <div style="font-size:.72rem;color:#e65100;font-weight:600;">⚠ Must change password</div>
                    <?php endif; ?>
                </div>
            </td>

            <!-- Login ID / Email -->
            <td>
                <div style="font-size:.85rem;font-weight:500;color:#0070ef;"><?php echo htmlspecialchars($acc['user_id']); ?></div>
                <?php if ($acc['email'] && $acc['email'] !== $acc['user_id']): ?>
                <div style="font-size:.78rem;color:#888;"><?php echo htmlspecialchars($acc['email']); ?></div>
                <?php endif; ?>
            </td>

            <td style="font-size:.875rem;color:#555;"><?php echo htmlspecialchars($acc['department'] ?: '—'); ?></td>
            <td style="font-size:.875rem;color:#555;"><?php echo htmlspecialchars($acc['phone'] ?: '—'); ?></td>

            <!-- Role badge -->
            <td>
                <span style="padding:3px 10px;border-radius:20px;font-size:.78rem;font-weight:600;
                             background:<?php echo $isAdmin ? '#ede7f6' : '#e3f2fd'; ?>;
                             color:<?php echo $isAdmin ? '#4527a0' : '#1565c0'; ?>;">
                    <?php echo $isAdmin ? 'Admin' : 'Guest'; ?>
                </span>
            </td>

            <!-- Active status -->
            <td>
                <span style="padding:3px 10px;border-radius:20px;font-size:.78rem;font-weight:600;
                             background:<?php echo $isActive ? '#e8f5e9' : '#ffebee'; ?>;
                             color:<?php echo $isActive ? '#2e7d32' : '#c62828'; ?>;">
                    <?php echo $isActive ? '✔ Active' : '✘ Inactive'; ?>
                </span>
            </td>

            <td style="font-size:.8rem;color:#888;">
                <?php echo $acc['last_login'] ? date('d M Y H:i', strtotime($acc['last_login'])) : 'Never'; ?>
            </td>

            <!-- Actions -->
            <td>
                <button class="btn-edit" style="margin-bottom:4px;"
                    onclick='accOpenEdit(this)'
                    data-row='<?php echo htmlspecialchars(json_encode([
                        'id'         => $acc['id'],
                        'user_id'    => $acc['user_id'],
                        'full_name'  => $acc['full_name'],
                        'email'      => $acc['email'],
                        'department' => $acc['department'],
                        'phone'      => $acc['phone'],
                        'role'       => $acc['role'],
                        'is_active'  => $acc['is_active'],
                    ]), ENT_QUOTES); ?>'>
                    Edit
                </button>
                <button onclick="accToggleActive(<?php echo $acc['id']; ?>, <?php echo $isActive ? 0 : 1; ?>, this)"
                    style="display:block;width:100%;margin-bottom:4px;padding:5px 10px;border-radius:6px;
                           border:none;cursor:pointer;font-size:.78rem;font-weight:600;font-family:Montserrat;
                           background:<?php echo $isActive ? '#fff3e0' : '#e8f5e9'; ?>;
                           color:<?php echo $isActive ? '#e65100' : '#2e7d32'; ?>;">
                    <?php echo $isActive ? '🔴 Deactivate' : '🟢 Activate'; ?>
                </button>
                <?php if (!$isAdmin): // Protect admin accounts from deletion ?>
                <button class="btn-delete"
                    onclick="accConfirmDelete(<?php echo $acc['id']; ?>, '<?php echo htmlspecialchars($acc['full_name'] ?: $acc['user_id'], ENT_QUOTES); ?>')">
                    Delete
                </button>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<!-- Edit Modal -->
<div id="acc-edit-modal" class="modal">
    <div class="modal-content" style="max-width:600px;">
        <div class="modal-header">
            <h3>Edit Account</h3>
            <button class="modal-close" onclick="document.getElementById('acc-edit-modal').classList.remove('active')">&times;</button>
        </div>
        <form id="acc-edit-form" onsubmit="accSaveEdit(event)">
            <input type="hidden" id="acc-edit-id" name="id">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" id="acc-edit-name" name="full_name" placeholder="Full Name">
                </div>
                <div class="form-group">
                    <label>Login ID (username) <span style="color:red;">*</span></label>
                    <input type="text" id="acc-edit-userid" name="user_id" required placeholder="user@ten.com">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" id="acc-edit-email" name="email" placeholder="email@technip.com">
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <input type="text" id="acc-edit-dept" name="department" placeholder="e.g. IT, Engineering">
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" id="acc-edit-phone" name="phone" placeholder="+60 12-345 6789">
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select id="acc-edit-role" name="role">
                        <option value="guest">Guest (IT Project Manager)</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>

            <!-- Password reset section -->
            <div style="background:#fff3e0;border-radius:8px;padding:1rem;margin-top:.5rem;border-left:4px solid #e65100;">
                <label style="font-weight:600;color:#e65100;display:block;margin-bottom:.5rem;">
                    Reset Password (leave blank to keep current)
                </label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div class="form-group" style="margin:0;">
                        <label>New Password</label>
                        <input type="password" id="acc-edit-pw" name="new_password" placeholder="New password"
                               autocomplete="new-password" minlength="6">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label>Confirm Password</label>
                        <input type="password" id="acc-edit-pw2" name="confirm_password" placeholder="Confirm password"
                               autocomplete="new-password">
                    </div>
                </div>
                <label style="display:flex;align-items:center;gap:8px;margin-top:.75rem;font-size:.875rem;cursor:pointer;">
                    <input type="checkbox" id="acc-edit-must-change" name="must_change_password" value="1">
                    Force user to change password on next login
                </label>
            </div>

            <div class="form-actions" style="margin-top:1rem;">
                <button type="button" class="btn-cancel"
                    onclick="document.getElementById('acc-edit-modal').classList.remove('active')">Cancel</button>
                <button type="submit" class="btn-save" id="acc-edit-save-btn">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirm Modal -->
<div id="acc-delete-modal" class="modal">
    <div class="modal-content" style="max-width:420px;text-align:center;">
        <div style="font-size:3rem;margin-bottom:1rem;"></div>
        <h3 style="color:#dc3545;margin-bottom:.75rem;">Delete Account?</h3>
        <p style="color:#666;margin-bottom:.5rem;" id="acc-delete-text">This will permanently remove the account.</p>
        <p style="font-size:.82rem;color:#999;margin-bottom:1.5rem;">
            All saved configurations for this user will also be deleted.
        </p>
        <div style="display:flex;gap:1rem;justify-content:center;">
            <button class="btn-cancel" onclick="document.getElementById('acc-delete-modal').classList.remove('active')"
                style="padding:.75rem 2rem;">Cancel</button>
            <button class="btn-delete" id="acc-delete-confirm-btn"
                style="padding:.75rem 2rem;border-radius:8px;">Yes, Delete</button>
        </div>
    </div>
</div>

<script>
// ── Filter ────────────────────────────────────────────────────────────────
function accFilterList() {
    const q      = document.getElementById('acc-search').value.toLowerCase();
    const role   = document.getElementById('acc-role-filter').value;
    const status = document.getElementById('acc-status-filter').value;
    let vis = 0;
    document.querySelectorAll('#acc-tbody tr[data-role]').forEach(tr => {
        const matchQ  = !q      || tr.dataset.search.includes(q);
        const matchR  = !role   || tr.dataset.role   === role;
        const matchS  = !status || tr.dataset.status === status;
        const show    = matchQ && matchR && matchS;
        tr.style.display = show ? '' : 'none';
        if (show) vis++;
    });
    const cnt = document.getElementById('acc-list-count');
    if (cnt) cnt.textContent = vis + ' account' + (vis !== 1 ? 's' : '');
}

// ── Open Edit Modal ───────────────────────────────────────────────────────
function accOpenEdit(btn) {
    const data = JSON.parse(btn.getAttribute('data-row'));
    document.getElementById('acc-edit-id').value       = data.id;
    document.getElementById('acc-edit-userid').value   = data.user_id    || '';
    document.getElementById('acc-edit-name').value     = data.full_name  || '';
    document.getElementById('acc-edit-email').value    = data.email      || '';
    document.getElementById('acc-edit-dept').value     = data.department || '';
    document.getElementById('acc-edit-phone').value    = data.phone      || '';
    document.getElementById('acc-edit-role').value     = data.role       || 'guest';
    document.getElementById('acc-edit-pw').value       = '';
    document.getElementById('acc-edit-pw2').value      = '';
    document.getElementById('acc-edit-must-change').checked = false;
    document.getElementById('acc-edit-modal').classList.add('active');
}

// ── Save Edit ─────────────────────────────────────────────────────────────
async function accSaveEdit(event) {
    event.preventDefault();
    const pw  = document.getElementById('acc-edit-pw').value;
    const pw2 = document.getElementById('acc-edit-pw2').value;
    if (pw && pw !== pw2) { alert('Passwords do not match.'); return; }
    if (pw && pw.length < 6) { alert('Password must be at least 6 characters.'); return; }

    const btn = document.getElementById('acc-edit-save-btn');
    btn.disabled = true; btn.textContent = 'Saving…';

    const fd = new FormData(document.getElementById('acc-edit-form'));
    fd.append('action', 'update_account');

    try {
        const resp = await fetch('admin_includes/handlers/account_handler.php', { method:'POST', body:fd });
        const text = await resp.text();
        let result;
        try { result = JSON.parse(text); }
        catch(e) {
            // Server returned non-JSON — show raw output so we can debug
            srvShowAlert('Server error: ' + text.substring(0, 200), 'error');
            return;
        }
        if (result.success) {
            srvShowAlert(result.message, 'success');
            document.getElementById('acc-edit-modal').classList.remove('active');
            setTimeout(() => location.reload(), 1500);
        } else {
            srvShowAlert(result.message || 'Unknown error', 'error');
        }
    } catch(e) { srvShowAlert('Network error: ' + e.message, 'error'); }
    finally { btn.disabled = false; btn.textContent = 'Save Changes'; }
}

// ── Toggle Active/Inactive ────────────────────────────────────────────────
async function accToggleActive(id, newStatus, btn) {
    btn.disabled = true;
    const fd = new FormData();
    fd.append('action',    'toggle_active');
    fd.append('id',        id);
    fd.append('is_active', newStatus);
    try {
        const resp   = await fetch('admin_includes/handlers/account_handler.php', { method:'POST', body:fd });
        const result = await resp.json();
        if (result.success) {
            srvShowAlert(result.message, 'success');
            setTimeout(() => location.reload(), 900);
        } else {
            srvShowAlert(result.message, 'error');
            btn.disabled = false;
        }
    } catch(e) { srvShowAlert('Error: ' + e.message, 'error'); btn.disabled = false; }
}

// ── Delete ────────────────────────────────────────────────────────────────
function accConfirmDelete(id, name) {
    document.getElementById('acc-delete-text').innerHTML =
        'Permanently remove account <strong>' + accEscape(name) + '</strong> and all their saved configurations?';
    const btn = document.getElementById('acc-delete-confirm-btn');
    btn.onclick = async () => {
        btn.disabled = true; btn.textContent = 'Deleting…';
        const fd = new FormData();
        fd.append('action', 'delete_account');
        fd.append('id', id);
        try {
            const resp   = await fetch('admin_includes/handlers/account_handler.php', { method:'POST', body:fd });
            const result = await resp.json();
            if (result.success) {
                srvShowAlert(result.message, 'success');
                document.getElementById('acc-delete-modal').classList.remove('active');
                setTimeout(() => location.reload(), 900);
            } else { srvShowAlert(result.message, 'error'); }
        } catch(e) { srvShowAlert('Error: ' + e.message, 'error'); }
        finally { btn.disabled = false; btn.textContent = 'Yes, Delete'; }
    };
    document.getElementById('acc-delete-modal').classList.add('active');
}

function srvShowAlert(msg, type) {
    // Try the dedicated alert element first
    const el = document.getElementById('srv-alert-message');
    if (el) {
        el.style.display  = 'block';
        el.style.padding  = '12px 16px';
        el.style.borderRadius = '8px';
        el.style.fontWeight = '600';
        el.style.fontSize = '.9rem';
        el.style.marginBottom = '1rem';
        el.style.background  = type === 'success' ? '#e8f5e9' : '#ffebee';
        el.style.borderLeft  = type === 'success' ? '4px solid #4caf50' : '4px solid #f44336';
        el.style.color       = type === 'success' ? '#1b5e20'  : '#c62828';
        el.textContent = (type === 'success' ? '✅ ' : '❌ ') + msg;
        setTimeout(() => { el.style.display = 'none'; }, 5000);
        return;
    }
    // Fallback: browser alert so it's never invisible
    alert((type === 'success' ? '✅ ' : '❌ ') + msg);
}

function accEscape(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}
</script>