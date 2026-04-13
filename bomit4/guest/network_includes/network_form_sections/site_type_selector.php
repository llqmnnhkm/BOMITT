<?php
// includes/network_form_sections/site_type_selector.php
// Automatic Site Type Configuration Based on Number of Users
?>

<div class="question-group" style="margin-bottom: 2rem;">
    <label style="font-size: 1.125rem; font-weight: 600; display:block; margin-bottom: 0.25rem;">
        Site Type Configuration <span class="required">*</span>
    </label>
    <p style="font-size: 0.875rem; color: #666; margin: 0 0 1rem 0;">
        Configuration is auto-detected from Project Details. Choose server requirement below.
    </p>

    <!-- Hidden field to store the determined site type -->
    <input type="hidden" name="site_type" id="site-type-value">
    
    <!-- Auto-detected User Range Display -->
    <div style="padding: 15px; background: linear-gradient(90deg, rgba(0, 112, 239, 0.1) 0%, rgba(128, 199, 160, 0.1) 100%); border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid #0070ef;">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <span style="font-size: 2rem;">👥</span>
            <div>
                <div style="font-size: 0.875rem; color: #666; margin-bottom: 4px;">Detected User Range:</div>
                <div style="font-size: 1.25rem; font-weight: 700; color: #0070ef;" id="detected-user-range">
                    Please select number of users in Project Details
                </div>
            </div>
        </div>
    </div>

    <!-- Server Requirement Selection -->
    <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        <label style="font-size: 1rem; font-weight: 600; display:block; margin-bottom: 1rem; color: #2d3748;">
            Does this site require a local server? <span class="required">*</span>
        </label>
        
        <div style="display: flex; gap: 1rem;">
            <label style="flex: 1; cursor: pointer;">
                <input type="radio" name="server_required" value="with_server" required 
                    onchange="updateSiteTypeConfiguration()" 
                    style="display: none;">
                <div class="server-option-card" data-option="with_server" 
                    style="padding: 20px; border: 2px solid #e0e0e0; border-radius: 8px; text-align: center; transition: all 0.3s ease;">
                    <div style="font-size: 2.5rem; margin-bottom: 10px;">🖥️</div>
                    <div style="font-weight: 600; font-size: 1.1rem; color: #2d3748;">With Server</div>
                    <div style="font-size: 0.875rem; color: #666; margin-top: 5px;">Includes server infrastructure</div>
                </div>
            </label>
            
            <label style="flex: 1; cursor: pointer;">
                <input type="radio" name="server_required" value="no_server" 
                    onchange="updateSiteTypeConfiguration()" 
                    style="display: none;">
                <div class="server-option-card" data-option="no_server" 
                    style="padding: 20px; border: 2px solid #e0e0e0; border-radius: 8px; text-align: center; transition: all 0.3s ease;">
                    <div style="font-size: 2.5rem; margin-bottom: 10px;">☁️</div>
                    <div style="font-weight: 600; font-size: 1.1rem; color: #2d3748;">No Server</div>
                    <div style="font-size: 0.875rem; color: #666; margin-top: 5px;">Cloud-based or remote</div>
                </div>
            </label>
        </div>
    </div>

    <!-- Final Configuration Display -->
    <div id="final-config-display" style="display: none; margin-top: 1.5rem; padding: 15px; background: #e8f5e9; border-radius: 8px; border-left: 4px solid #4caf50;">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <span style="font-size: 2rem;">✅</span>
            <div>
                <div style="font-size: 0.875rem; color: #2e7d32; margin-bottom: 4px;">Selected Configuration:</div>
                <div style="font-size: 1.1rem; font-weight: 700; color: #1b5e20;" id="final-config-text">-</div>
            </div>
        </div>
    </div>
</div>

<style>
.server-option-card {
    transition: all 0.3s ease;
}

.server-option-card:hover {
    border-color: #0070ef !important;
    box-shadow: 0 4px 12px rgba(0, 112, 239, 0.2);
    transform: translateY(-2px);
}

input[type="radio"]:checked + .server-option-card {
    border-color: #0070ef !important;
    background: linear-gradient(135deg, rgba(0, 112, 239, 0.1) 0%, rgba(128, 199, 160, 0.1) 100%);
    box-shadow: 0 4px 12px rgba(0, 112, 239, 0.3);
}
</style>

<script>
// Global variable to store detected user range
let detectedUserRange = '';

// Map user quantity text to file naming convention
function mapUserQuantityToRange(userQuantityText) {
    if (!userQuantityText) return '';
    
    const text = userQuantityText.toLowerCase();
    
    if (text.includes('less than 50') || text.includes('< 50')) {
        return 'less_50';
    } else if (text.includes('51-150') || text.includes('51 to 150')) {
        return '51_150';
    } else if (text.includes('151-300') || text.includes('151 to 300')) {
        return '151_300';
    } else if (text.includes('301-400') || text.includes('301 to 400')) {
        return '301_400';
    } else if (text.includes('more than 400') || text.includes('> 400') || text.includes('400+')) {
        return 'more_400';
    }
    
    return '';
}

// Get display text for user range
function getUserRangeDisplayText(rangeCode) {
    const displayMap = {
        'less_50': 'Less than 50 Users (Small Site)',
        '51_150': '51-150 Users (Medium Site)',
        '151_300': '151-300 Users (Large Site)',
        '301_400': '301-400 Users (Large Enterprise)',
        'more_400': 'More than 400 Users (Enterprise)'
    };
    return displayMap[rangeCode] || '';
}

// Auto-detect user range from project details
function detectUserRange() {
    const userQuantitySelect = document.querySelector('select[name="user_quantity"]');
    const displayElement = document.getElementById('detected-user-range');
    
    if (!userQuantitySelect) {
        console.error('❌ user_quantity select not found!');
        return;
    }
    
    const userQuantityText = userQuantitySelect.value;
    console.log('📊 User Quantity Selected:', userQuantityText);
    
    if (!userQuantityText) {
        detectedUserRange = '';
        displayElement.textContent = 'Please select number of users in Project Details';
        displayElement.style.color = '#666';
        return;
    }
    
    // Map to range code
    detectedUserRange = mapUserQuantityToRange(userQuantityText);
    console.log('✅ Mapped to Range Code:', detectedUserRange);
    
    // Update display
    const displayText = getUserRangeDisplayText(detectedUserRange);
    displayElement.textContent = displayText;
    displayElement.style.color = '#0070ef';
    
    // Re-trigger configuration if server already selected
    updateSiteTypeConfiguration();
}

// Update site type configuration
function updateSiteTypeConfiguration() {
    const serverRequired = document.querySelector('input[name="server_required"]:checked');
    const serverValue = serverRequired ? serverRequired.value : '';
    
    console.log('=== updateSiteTypeConfiguration ===');
    console.log('Server Required:', serverValue);
    console.log('Detected User Range:', detectedUserRange);
    
    if (!serverValue || !detectedUserRange) {
        console.log('⚠️ Missing selection');
        document.getElementById('final-config-display').style.display = 'none';
        document.getElementById('site-type-value').value = '';
        return;
    }
    
    // Build site type value
    const serverSuffix = serverValue === 'with_server' ? '_with_server' : '_no_server';
    const siteTypeValue = detectedUserRange + serverSuffix;
    
    console.log('✅ Built Site Type Value:', siteTypeValue);
    
    // Store in hidden field
    document.getElementById('site-type-value').value = siteTypeValue;
    
    // Update display
    const userRangeText = getUserRangeDisplayText(detectedUserRange);
    const serverText = serverValue === 'with_server' ? 'With Server' : 'No Server';
    const configText = userRangeText + ' - ' + serverText;
    
    document.getElementById('final-config-text').textContent = configText;
    document.getElementById('final-config-display').style.display = 'block';
    
    // Show equipment section
    showEquipmentSection(siteTypeValue);
}

// Show the correct equipment section
function showEquipmentSection(siteTypeValue) {
    console.log('=== Showing Equipment Section ===');
    console.log('Site Type:', siteTypeValue);
    
    // Hide all sections
    const allSections = document.querySelectorAll('.site-specific-fields');
    console.log('Found', allSections.length, 'equipment sections');
    
    allSections.forEach(section => {
        section.classList.add('hidden');
        section.style.display = 'none';
    });
    
    // Build section ID
    const sectionId = 'equipment_' + siteTypeValue;
    console.log('Looking for:', sectionId);
    
    // Find and show
    const targetSection = document.getElementById(sectionId);
    
    if (targetSection) {
        console.log('✅ FOUND! Showing section...');
        targetSection.classList.remove('hidden');
        targetSection.style.display = 'block';
        
        // Call pricing function
        const functionName = 'updateEquipment' + siteTypeValue.replace(/_/g, '') + 'Pricing';
        console.log('Looking for function:', functionName);
        
        if (typeof window[functionName] === 'function') {
            console.log('✅ Calling pricing function');
            window[functionName]();
        }
        
        // Smooth scroll
        setTimeout(() => {
            targetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 200);
    } else {
        console.error('❌ Section NOT found:', sectionId);
        console.log('Available sections:');
        allSections.forEach(s => {
            console.log('  -', s.id || 'NO ID');
        });
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== Site Type Selector Initialized ===');
    
    // Find user quantity select
    const userQuantitySelect = document.querySelector('select[name="user_quantity"]');
    
    if (userQuantitySelect) {
        console.log('✅ Found user_quantity select');
        
        // Listen for changes
        userQuantitySelect.addEventListener('change', function() {
            console.log('🔄 User quantity changed');
            detectUserRange();
        });
        
        // Detect immediately if already selected
        if (userQuantitySelect.value) {
            console.log('🔍 Auto-detecting from existing value');
            detectUserRange();
        }
    } else {
        console.error('❌ user_quantity select not found!');
    }
});
</script>