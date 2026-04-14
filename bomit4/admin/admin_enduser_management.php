<?php
// admin/admin_enduser_management.php
// Main End User Equipment Management Container
// Mirrors admin_network_management.php structure exactly

// DB table used: enduser_equipment
// Required columns:
//   id INT AUTO_INCREMENT PRIMARY KEY,
//   user_type      VARCHAR(50)   -- 'general' | 'technical' | 'design' | 'field' | 'executive'
//   item_category  VARCHAR(50)   -- 'workstation' | 'peripherals' | 'mobile' | 'software'
//   item_name      VARCHAR(255)
//   item_description TEXT
//   default_quantity INT DEFAULT 1
//   unit_price     DECIMAL(10,2) DEFAULT 0.00
//   is_active      TINYINT(1)    DEFAULT 1
//   display_order  INT           DEFAULT 0
//   created_at     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
//   updated_at     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
?>

<!-- Alert Messages -->
<div id="eu-alert-message" class="alert"></div>

<!-- Management Tabs -->
<div class="management-tabs">
    <button class="tab-btn active" onclick="euSwitchTab('items', event)">Equipment Items</button>
    <button class="tab-btn"        onclick="euSwitchTab('catalog', event)">Catalog Overview</button>
</div>

<!-- Tab 1: Equipment Items (main CRUD table) -->
<div id="eu-tab-items" class="tab-content active">
    <?php include __DIR__ . '/admin_includes/enduser_includes/enduser_items.php'; ?>
</div>

<!-- Tab 2: Catalog Overview (read-only summary by user type) -->
<div id="eu-tab-catalog" class="tab-content">
    <?php include __DIR__ . '/admin_includes/enduser_includes/enduser_catalog_overview.php'; ?>
</div>

<script>
// ── Tab switching (namespaced to avoid clash with network tabs) ───────────
function euSwitchTab(tab, event) {
    // Deactivate all EU tabs
    document.querySelectorAll('#container-enduser .tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('#container-enduser .tab-content').forEach(c => c.classList.remove('active'));

    // Activate selected
    if (event && event.target) event.target.classList.add('active');
    const el = document.getElementById('eu-tab-' + tab);
    if (el) el.classList.add('active');
}

// ── Alert function (namespaced) ───────────────────────────────────────────
function euShowAlert(message, type) {
    const alert = document.getElementById('eu-alert-message');
    alert.textContent = message;
    alert.className   = 'alert alert-' + type + ' show';
    setTimeout(() => alert.classList.remove('show'), 5000);
}

// Close modal on outside click
document.querySelectorAll('#container-enduser .modal').forEach(modal => {
    modal.addEventListener('click', e => {
        if (e.target === modal) modal.classList.remove('active');
    });
});

// Shared escape helper (same as network management)
function euEscapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
