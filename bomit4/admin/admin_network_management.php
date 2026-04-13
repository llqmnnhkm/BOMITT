<?php
// admin/admin_network_management.php
// Network Infrastructure Management — upgraded to match conference/enduser style
// Tabs: Equipment Items | Infrastructure Config | Cables & Accessories | Overview
?>

<!-- Alert Messages -->
<div id="alert-message" class="alert"></div>

<!-- Management Tabs -->
<div class="management-tabs">
    <button class="tab-btn active" onclick="netSwitchTab('equipment', event)">🌐 Equipment Items</button>
    <button class="tab-btn"        onclick="netSwitchTab('config',    event)">⚙️ Infrastructure Config</button>
    <button class="tab-btn"        onclick="netSwitchTab('cables',    event)">🔌 Cables & Accessories</button>
    <button class="tab-btn"        onclick="netSwitchTab('overview',  event)">📋 Overview</button>
</div>

<!-- Tab: Equipment -->
<div id="net-tab-equipment" class="tab-content active">
    <?php include __DIR__ . '/admin_includes/network_includes/network_equipment_items.php'; ?>
</div>

<!-- Tab: Infrastructure Config -->
<div id="net-tab-config" class="tab-content">
    <?php include __DIR__ . '/admin_includes/network_includes/network_infrastructure_config.php'; ?>
</div>

<!-- Tab: Cables & Accessories -->
<div id="net-tab-cables" class="tab-content">
    <?php include __DIR__ . '/admin_includes/network_includes/network_cables_accessories.php'; ?>
</div>

<!-- Tab: Overview -->
<div id="net-tab-overview" class="tab-content">
    <?php include __DIR__ . '/admin_includes/network_includes/network_overview.php'; ?>
</div>

<script>
// ── Tab switching (namespaced net to avoid clash) ──────────────────────────
function netSwitchTab(tab, event) {
    document.querySelectorAll('#container-network .tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('#container-network .tab-content').forEach(c => c.classList.remove('active'));
    if (event && event.target) event.target.classList.add('active');
    const el = document.getElementById('net-tab-' + tab);
    if (el) el.classList.add('active');
}

// ── Alert (keep original name for backward compat with network sub-files) ─
function showAlert(message, type) {
    const el = document.getElementById('alert-message');
    el.textContent = message;
    el.className   = 'alert alert-' + type + ' show';
    setTimeout(() => el.classList.remove('show'), 5000);
}

// Close modals on backdrop click
document.querySelectorAll('#container-network .modal').forEach(modal => {
    modal.addEventListener('click', e => {
        if (e.target === modal) modal.classList.remove('active');
    });
});

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>