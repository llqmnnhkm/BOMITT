<!-- Network Container -->
<div id="container-network" class="question-container">
    <div class="container-header">
        <div class="container-icon network"></div>
        <div class="container-title">
            <h3>Network Infrastructure</h3>
            <p>Configure networking requirements</p>
        </div>
    </div>

    <!-- Save Status Indicator -->
    <div id="save-status" style="padding: 12px; margin-bottom: 20px; border-radius: 8px; display: none; font-family: Montserrat; font-size: 0.95rem; font-weight: 500;">
        <span id="save-status-text"></span>
    </div>

    <form method="POST" action="">
        <input type="hidden" name="request_id" value="<?php echo $request_id ?? ''; ?>">
        <input type="hidden" name="category" value="network">

        <!-- Project Information Section -->
        <?php include 'network_includes/network_form_sections/project_info.php'; ?>
        
        <!-- Network Infrastructure Selection -->
        <?php include 'network_includes/network_form_sections/infrastructure_config.php'; ?>
        
        <!-- Site Type Configuration -->
        <?php include 'network_includes/network_form_sections/site_type_selector.php'; ?>
        
        <!-- Site Equipment -->
        <?php include 'network_includes/network_form_sections/equipment_dynamic.php'; ?>
        
        <!-- Cable and Accessories Section -->
        <?php include 'network_includes/network_form_sections/cables_accessories.php'; ?>
        
        <!-- Additional Notes -->
        <?php include 'network_includes/network_form_sections/additional_notes.php'; ?> 
        
        <!-- Network Report Summary -->
        <?php include 'network_includes/network_report.php'; ?> 

        <!-- Form Actions with Save and Reset Buttons -->
        <div class="form-actions" style="display: flex; gap: 1rem; margin-top: 2rem; align-items: center;">
            <!-- Reset Button (clears form, doesn't delete database) -->
            <button type="button" onclick="resetNetworkConfiguration()" class="btn btn-warning" 
                style="flex: 0.8; background: #f59e0b; color: white;">
                 Reset Form
            </button>
            
            <!-- Save Configuration Button -->
            <button type="button" onclick="saveNetworkConfiguration()" class="btn btn-success" 
                style="flex: 1; background: #10b981; color: white;">
                 Save Configuration
            </button>
            
            <!-- View Report Button -->
            <button type="button" onclick="showNetworkReport()" class="btn btn-primary" 
                style="flex: 1; background: linear-gradient(90deg, #0070ef 0%, #4A90E2 100%); color: white;">
                 View Report Summary
            </button>
            
            <!-- Cancel Button -->
            <button type="button" class="btn btn-secondary" onclick="hideAllContainers()" 
                style="flex: 0.6; background: #e2e8f0; color: #4a5568;">
                 Cancel
            </button>
        </div>
    </form>
</div>

<!-- Include Save Handler JavaScript -->
<script src="network_includes/network_save_handler.js"></script>

<style>
.hidden {
    display: none !important;
}

/* Ensure site-specific-fields are visible when not hidden */
.site-specific-fields {
    display: block;
}

.site-specific-fields.hidden {
    display: none !important;
}

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
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.btn-primary {
    background: linear-gradient(90deg, #0070ef 0%, #4A90E2 100%);
    color: white;
}

.btn-primary:hover:not(:disabled) {
    box-shadow: 0 4px 12px rgba(0, 112, 239, 0.3);
}

.btn-success {
    background: #10b981;
    color: white;
}

.btn-success:hover:not(:disabled) {
    background: #059669;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-warning {
    background: #f59e0b;
    color: white;
}

.btn-warning:hover:not(:disabled) {
    background: #d97706;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.btn-secondary {
    background: #e2e8f0;
    color: #4a5568;
}

.btn-secondary:hover:not(:disabled) {
    background: #cbd5e0;
}

.form-actions {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 2px solid #e0e0e0;
}

/* Save Status Styles */
#save-status {
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>