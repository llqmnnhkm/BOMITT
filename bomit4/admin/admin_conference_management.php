<?php
// admin/admin_conference_management.php
// Conference Room Equipment Management Container
// Mirrors admin_network_management.php structure exactly
// DB table: conference_equipment
//   room_type: small_meeting | medium_conference | large_boardroom | training_room
//   equipment_category: video | audio | display | control | furniture | other
?>

<!-- Alert Messages -->
<div id="conf-alert-message" class="alert"></div>

<!-- Management Tabs -->
<div class="management-tabs">
    <button class="tab-btn active" onclick="confSwitchTab('items', event)">Equipment Items</button>
    <button class="tab-btn"        onclick="confSwitchTab('overview', event)">Room Overview</button>
</div>

<!-- Tab 1: Equipment CRUD table -->
<div id="conf-tab-items" class="tab-content active">
    <?php include __DIR__ . '/admin_includes/conference_includes/conference_items.php'; ?>
</div>

<!-- Tab 2: Overview grouped by room type -->
<div id="conf-tab-overview" class="tab-content">
    <?php include __DIR__ . '/admin_includes/conference_includes/conference_overview.php'; ?>
</div>

<script>
// ── Tab switching (namespaced to avoid clash with network/eu tabs) ─────────
function confSwitchTab(tab, event) {
    document.querySelectorAll('#container-conference .tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('#container-conference .tab-content').forEach(c => c.classList.remove('active'));
    if (event && event.target) event.target.classList.add('active');
    const el = document.getElementById('conf-tab-' + tab);
    if (el) el.classList.add('active');
}

// ── Alert (namespaced) ─────────────────────────────────────────────────────
function confShowAlert(message, type) {
    const el = document.getElementById('conf-alert-message');
    el.textContent = message;
    el.className   = 'alert alert-' + type + ' show';
    setTimeout(() => el.classList.remove('show'), 5000);
}

// Close modal on backdrop click
document.querySelectorAll('#container-conference .modal').forEach(modal => {
    modal.addEventListener('click', e => {
        if (e.target === modal) modal.classList.remove('active');
    });
});

function confEscapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
}
</script>
