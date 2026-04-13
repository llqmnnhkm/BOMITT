<?php
// admin/admin_server_management.php
// Server Infrastructure Management Container
// No pricing columns yet — manages VM types and core infrastructure items only
// DB table: server_equipment (created on first load if missing)
?>

<!-- Alert Messages -->
<div id="srv-alert-message" class="alert"></div>

<!-- Management Tabs -->
<div class="management-tabs">
    <button class="tab-btn active" onclick="srvSwitchTab('items', event)">🖥️ VM Items</button>
    <button class="tab-btn"        onclick="srvSwitchTab('infra',  event)">⚙️ Core Infrastructure</button>
    <button class="tab-btn"        onclick="srvSwitchTab('overview', event)">📋 Overview</button>
</div>

<!-- Tab 1: VM Items -->
<div id="srv-tab-items" class="tab-content active">
    <?php include __DIR__ . '/admin_includes/server_includes/server_items.php'; ?>
</div>

<!-- Tab 2: Core Infrastructure defaults -->
<div id="srv-tab-infra" class="tab-content">
    <?php include __DIR__ . '/admin_includes/server_includes/server_infra_defaults.php'; ?>
</div>

<!-- Tab 3: Overview -->
<div id="srv-tab-overview" class="tab-content">
    <?php include __DIR__ . '/admin_includes/server_includes/server_overview.php'; ?>
</div>

<script>
function srvSwitchTab(tab, event) {
    document.querySelectorAll('#container-server .tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('#container-server .tab-content').forEach(c => c.classList.remove('active'));
    if (event && event.target) event.target.classList.add('active');
    const el = document.getElementById('srv-tab-' + tab);
    if (el) el.classList.add('active');
}

function srvShowAlert(message, type) {
    const el = document.getElementById('srv-alert-message');
    el.textContent = message;
    el.className   = 'alert alert-' + type + ' show';
    setTimeout(() => el.classList.remove('show'), 5000);
}

document.querySelectorAll('#container-server .modal').forEach(modal => {
    modal.addEventListener('click', e => {
        if (e.target === modal) modal.classList.remove('active');
    });
});

function srvEscapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
}
</script>
