<?php
// admin/admin_account_management.php
// Account Manager — Create, Edit, Deactivate, Delete guest accounts
?>

<style>
/* Self-contained alert styles for Account Manager */
#srv-alert-message {
    display: none;
    padding: 12px 16px;
    border-radius: 8px;
    font-weight: 600;
    font-size: .9rem;
    margin-bottom: 1rem;
    font-family: Montserrat, sans-serif;
}
/* Shared tab + form styles used by account sub-files */
#container-accounts .management-tabs { display:flex; gap:0; border-bottom:2px solid #e0e0e0; margin-bottom:1.5rem; }
#container-accounts .tab-btn { padding:.75rem 1.5rem; background:none; border:none; font-family:Montserrat; font-size:.9rem; font-weight:600; color:#888; cursor:pointer; border-bottom:3px solid transparent; margin-bottom:-2px; transition:all .2s; }
#container-accounts .tab-btn.active { color:#0070ef; border-bottom-color:#0070ef; }
#container-accounts .tab-content { display:none; }
#container-accounts .tab-content.active { display:block; }
#container-accounts .filter-section { display:flex; gap:1rem; align-items:center; flex-wrap:wrap; background:#f8f9fa; padding:1rem 1.25rem; border-radius:10px; margin-bottom:1rem; }
#container-accounts .form-group { margin-bottom:1rem; }
#container-accounts .form-group label { font-weight:600; display:block; margin-bottom:.4rem; font-size:.875rem; color:#4a5568; }
#container-accounts .form-group input,
#container-accounts .form-group select,
#container-accounts .form-group textarea { width:100%; padding:10px 12px; border:1.5px solid #ddd; border-radius:8px; font-family:Montserrat; font-size:.9rem; transition:border-color .2s; box-sizing:border-box; }
#container-accounts .form-group input:focus,
#container-accounts .form-group select:focus { outline:none; border-color:#0070ef; }
#container-accounts .btn-edit { background:#0070ef; color:white; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; font-size:.8rem; font-weight:600; font-family:Montserrat; transition:background .2s; }
#container-accounts .btn-edit:hover { background:#005cc5; }
#container-accounts .btn-delete { background:#dc3545; color:white; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; font-size:.8rem; font-weight:600; font-family:Montserrat; }
#container-accounts .btn-save { background:linear-gradient(90deg,#0070ef,#4A90E2); color:white; border:none; padding:10px 24px; border-radius:8px; cursor:pointer; font-size:.9rem; font-weight:700; font-family:Montserrat; }
#container-accounts .btn-cancel { background:#e2e8f0; color:#4a5568; border:none; padding:10px 20px; border-radius:8px; cursor:pointer; font-size:.9rem; font-weight:600; font-family:Montserrat; }
#container-accounts .form-actions { display:flex; gap:.75rem; justify-content:flex-end; margin-top:1.25rem; padding-top:1rem; border-top:1px solid #e0e0e0; }
/* Modal styles */
#container-accounts .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,.5); z-index:9999; align-items:center; justify-content:center; }
#container-accounts .modal.active { display:flex; }
#container-accounts .modal-content { background:white; border-radius:16px; padding:2rem; max-width:620px; width:90%; max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,.3); }
#container-accounts .modal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; padding-bottom:1rem; border-bottom:2px solid #f0f0f0; }
#container-accounts .modal-header h3 { font-size:1.2rem; color:#333; font-weight:700; }
#container-accounts .modal-close { background:none; border:none; font-size:1.5rem; cursor:pointer; color:#999; line-height:1; }
#container-accounts .modal-close:hover { color:#333; }
</style>
<div id="srv-alert-message" style="display:none; margin-bottom:1rem;"></div>

<div class="management-tabs">
    <button class="tab-btn active" onclick="accSwitchTab('accounts', event)">👤 All Accounts</button>
    <button class="tab-btn"        onclick="accSwitchTab('create',   event)">➕ Create Account</button>
</div>

<!-- Tab: All Accounts -->
<div id="acc-tab-accounts" class="tab-content active">
    <?php include __DIR__ . '/admin_includes/account_list.php'; ?>
</div>

<!-- Tab: Create Account -->
<div id="acc-tab-create" class="tab-content">
    <?php include __DIR__ . '/admin_includes/account_create.php'; ?>
</div>

<script>
function accSwitchTab(tab, event) {
    document.querySelectorAll('#container-accounts .tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('#container-accounts .tab-content').forEach(c => c.classList.remove('active'));
    if (event && event.target) event.target.classList.add('active');
    const el = document.getElementById('acc-tab-' + tab);
    if (el) el.classList.add('active');
}
</script>