<?php
// includes/network_form_sections/project_info.php
// Project Information Section - Read-Only Display
?>

<!-- Info Banner -->
<div style="background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%); padding: 15px 20px; border-radius: 12px; margin-bottom: 1.5rem; border-left: 4px solid #2196F3; display: flex; align-items: center; gap: 1rem;">
    <div style="font-size: 1.5rem;">ℹ️</div>
    <div style="flex: 1;">
        <div style="font-weight: 600; color: #1565c0; margin-bottom: 4px;">Project Information</div>
        <div style="font-size: 0.875rem; color: #666;">
            These details are imported from your Project Details. 
            <a href="project_details.php" style="color: #2196F3; font-weight: 600; text-decoration: none;">
                Click here to edit →
            </a>
        </div>
    </div>
</div>

<form id="serverForm" method="POST" action="server_summary.php">
    
    <div class="top-textboxes" style="display: flex; flex-wrap: wrap; gap: 1.5rem; margin-bottom: 2rem;">
        <div class="textbox-item" style="flex: 1; min-width: 250px;">
            <label style="font-weight: 600; display: block; margin-bottom: 0.5rem; color: #666;">
                Project Name:
            </label>
            <input type="text" name="project_name" 
                value="<?php echo htmlspecialchars($project_name); ?>" 
                disabled
                style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-family: Montserrat; font-size: 1rem; background-color: #f5f5f5; color: #666; cursor: not-allowed;">
        </div>

        <div class="textbox-item" style="flex: 1; min-width: 250px;">
            <label style="font-weight: 600; display: block; margin-bottom: 0.5rem; color: #666;">
                Requesting Manager:
            </label>
            <input type="text" name="requesting_manager" 
                value="<?php echo htmlspecialchars($requesting_manager); ?>" 
                disabled
                style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-family: Montserrat; font-size: 1rem; background-color: #f5f5f5; color: #666; cursor: not-allowed;">
        </div>

        <div class="textbox-item" style="flex: 1; min-width: 250px;">
            <label style="font-weight: 600; display: block; margin-bottom: 0.5rem; color: #666;">
                Project Duration (In Months):
            </label>
            <input type="number" name="project_duration" 
                min="1" 
                value="<?php echo htmlspecialchars($project_duration); ?>"
                disabled
                style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-family: Montserrat; font-size: 1rem; background-color: #f5f5f5; color: #666; cursor: not-allowed;">
        </div>

        <div class="textbox-item" style="flex: 1; min-width: 250px;">
            <label style="font-weight: 600; display: block; margin-bottom: 0.5rem; color: #666;">
                Required Deployment Date:
            </label>
            <input type="date" name="deployment_date" 
                min="<?php echo date('Y-m-d'); ?>" 
                value="<?php echo htmlspecialchars($deployment_date); ?>"
                disabled
                style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-family: Montserrat; font-size: 1rem; background-color: #f5f5f5; color: #666; cursor: not-allowed;">
        </div>

        <div class="textbox-item" style="flex: 1; min-width: 250px;">
            <label style="font-weight: 600; display: block; margin-bottom: 0.5rem; color: #666;">
                Number of Users:
            </label>
            <select name="user_quantity"
                disabled
                style="width: 100%; text-align: center; font-family: Montserrat; padding: 10px; font-size: 1rem; border-radius: 6px; border: 1px solid #ddd; background-color: #f5f5f5; color: #666; cursor: not-allowed;">
                <?php
                    $options = [
                        "Less than 50 users",
                        "51-150 users",
                        "151-300 users",
                        "301-400 users",
                        "More than 400 users"
                    ];

                    foreach ($options as $option) {
                        $selected = ($option == $user_quantity) ? "selected" : "";
                        echo "<option value=\"$option\" $selected>$option</option>";
                    }
                ?>
            </select>
        </div>
    </div>
</form>

<style>
/* Ensure disabled inputs have proper styling */
input[disabled], select[disabled] {
    background-color: #f5f5f5 !important;
    color: #666 !important;
    border-color: #ddd !important;
    cursor: not-allowed !important;
    opacity: 1 !important; /* Prevent browser from making it too faded */
}

/* Hover effect to emphasize read-only */
input[disabled]:hover, select[disabled]:hover {
    background-color: #eeeeee !important;
}
</style>