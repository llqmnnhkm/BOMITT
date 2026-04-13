<?php
// guest/conference_room.php
// Conference Room Configuration — mirrors network_infrastructure.php structure
// Sections: Room Info → Equipment (DB-driven) → AV & Connectivity → Notes → Report

// Session vars from guest_home.php scope
$project_name       = $project_name       ?? ($_SESSION['project_name']       ?? '');
$requesting_manager = $requesting_manager ?? ($_SESSION['requesting_manager'] ?? '');
$project_duration   = $project_duration   ?? ($_SESSION['project_duration']   ?? '');
$deployment_date    = $deployment_date    ?? ($_SESSION['deployment_date']    ?? '');
$user_quantity      = $user_quantity      ?? ($_SESSION['user_quantity']      ?? '');
?>

<!-- ── Conference Container ───────────────────────────────────── -->
<div id="container-conference" class="question-container">

    <!-- Container Header -->
    <div class="container-header">
        <div class="container-icon conference" style="background: linear-gradient(135deg, #80c7a0 0%, #4caf50 100%);">
            👥
        </div>
        <div class="container-title">
            <h3>Conference Room</h3>
            <p>Configure meeting room technology</p>
        </div>
    </div>

    <!-- Save Status Indicator -->
    <div id="conference-save-status"
         style="padding:12px; margin-bottom:20px; border-radius:8px; display:none;
                font-family:Montserrat; font-size:0.95rem; font-weight:500; border-left:4px solid transparent;">
        <span id="conference-save-status-text"></span>
    </div>

    <form id="conference-form" method="POST" action="">
        <input type="hidden" name="project_name" value="<?php echo htmlspecialchars($project_name); ?>">
        <input type="hidden" name="category" value="conference">

        <!-- ── Section 1: Room & Meeting Info ───────────────────── -->
        <?php include 'conference_includes/conference_form_sections/conference_room_info.php'; ?>

        <!-- ── Section 2: Equipment (DB-driven by room size) ─────── -->
        <div class="question-group" style="margin-bottom: 2rem;">
            <label style="font-size:1.125rem; font-weight:600; display:block; margin-bottom:0.25rem;">
                Equipment Selection
            </label>
            <p style="font-size:0.875rem; color:#666; margin:0 0 1rem 0;">
                Select a room size above to load the recommended equipment list. Adjust quantities as needed.
            </p>

            <!-- No-selection placeholder -->
            <div id="conf-equip-placeholder"
                 style="padding:24px; background:#f9f9f9; border-radius:8px; text-align:center; color:#999; border:2px dashed #ddd;">
                👆 Please select a room size above to load equipment
            </div>

            <?php include 'conference_includes/conference_form_sections/conference_equipment_dynamic.php'; ?>
        </div>

        <!-- ── Section 3: AV & Connectivity ─────────────────────── -->
        <?php include 'conference_includes/conference_form_sections/conference_av_connectivity.php'; ?>

        <!-- ── Section 4: Additional Notes ──────────────────────── -->
        <?php include 'conference_includes/conference_form_sections/conference_additional_notes.php'; ?>

        <!-- ── Report (modal) ────────────────────────────────────── -->
        <?php include 'conference_includes/conference_report.php'; ?>

        <!-- ── Form Actions ──────────────────────────────────────── -->
        <div class="form-actions" style="display:flex; gap:1rem; margin-top:2rem; align-items:center;">

            <button type="button" onclick="resetConferenceConfiguration()" class="btn btn-warning"
                style="flex:0.8; background:#f59e0b; color:white;">
                🔄 Reset Form
            </button>

            <button type="button" onclick="saveConferenceConfiguration()" class="btn btn-success"
                style="flex:1; background:#10b981; color:white;">
                💾 Save Configuration
            </button>

            <button type="button" onclick="showConferenceReport()" class="btn btn-primary"
                style="flex:1; background:linear-gradient(90deg,#80c7a0 0%,#0070ef 100%); color:white;">
                📊 View Report Summary
            </button>

            <button type="button" class="btn btn-secondary" onclick="hideAllContainers()"
                style="flex:0.6; background:#e2e8f0; color:#4a5568;">
                ❌ Cancel
            </button>
        </div>
    </form>
</div>

<!-- ── Scripts ─────────────────────────────────────────────────── -->
<script src="conference_includes/js/conference_report_core.js"></script>
<script src="conference_includes/conference_save_handler.js"></script>

<script>
// Show/hide equipment placeholder based on room size selection
function showConferenceEquipmentSection(roomSize) {
    // Hide placeholder
    const placeholder = document.getElementById('conf-equip-placeholder');
    if (placeholder) placeholder.style.display = 'none';

    // Hide all size sections
    document.querySelectorAll('.conf-size-section').forEach(s => {
        s.classList.add('hidden');
        s.style.display = 'none';
    });

    // Show target
    const target = document.getElementById('conf_equip_' + roomSize);
    if (target) {
        target.classList.remove('hidden');
        target.style.display = 'block';
        if (typeof calculateConferenceTotals === 'function') {
            calculateConferenceTotals(roomSize);
        }
        setTimeout(() => target.scrollIntoView({ behavior: 'smooth', block: 'start' }), 200);
    }
}
</script>

<style>
/* Shared btn styles (mirrors network_infrastructure.php) */
.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    font-size: 1rem;
    transition: all 0.3s ease;
    font-family: 'Montserrat', sans-serif;
}
.btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}
.btn-success  { background: #10b981; color: white; }
.btn-success:hover:not(:disabled)  { background: #059669; }
.btn-warning  { background: #f59e0b; color: white; }
.btn-warning:hover:not(:disabled)  { background: #d97706; }
.btn-secondary { background: #e2e8f0; color: #4a5568; }
.btn-secondary:hover:not(:disabled) { background: #cbd5e0; }
.form-actions {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 2px solid #e0e0e0;
}
#conference-save-status {
    animation: slideDown 0.3s ease;
}
@keyframes slideDown {
    from { opacity:0; transform:translateY(-10px); }
    to   { opacity:1; transform:translateY(0); }
}
@media (max-width: 768px) {
    .form-actions { flex-direction: column; }
    .form-actions button { width: 100%; }
}
</style>
