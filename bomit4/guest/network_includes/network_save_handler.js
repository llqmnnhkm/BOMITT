// network_includes/network_save_handler.js
// UPDATED VERSION - Forces price recalculation after load

let hasUnsavedChanges = false;

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Network Save Handler Initialized');
    
    // Auto-load saved configuration (this will handle site type detection)
    autoLoadConfiguration();
    
    // Track changes
    const networkContainer = document.querySelector('#container-network');
    if (networkContainer) {
        networkContainer.addEventListener('change', function(e) {
            if (e.target.matches('input, select, textarea')) {
                hasUnsavedChanges = true;
                showUnsavedIndicator();
            }
        });
        
        networkContainer.addEventListener('input', function(e) {
            if (e.target.matches('input[type="number"]')) {
                hasUnsavedChanges = true;
                showUnsavedIndicator();
            }
        });
    }
    
    // Watch for user quantity changes
    const userQuantitySelect = document.querySelector('select[name="user_quantity"]');
    if (userQuantitySelect) {
        userQuantitySelect.addEventListener('change', function() {
            console.log('👥 User quantity changed');
            // Force recalculation of site type
            if (typeof detectUserRange === 'function') {
                detectUserRange();
            }
            if (typeof updateSiteTypeConfiguration === 'function') {
                updateSiteTypeConfiguration();
            }
        });
    }
    
    // IMPORTANT: Wrap hideAllContainers AFTER page loads (so the function exists)
    if (typeof window.hideAllContainers === 'function') {
        const originalHideAllContainers = window.hideAllContainers;
        window.hideAllContainers = function() {
            if (hasUnsavedChanges) {
                if (!confirm('⚠️ You have unsaved changes in Network Configuration.\n\nDo you want to leave without saving?')) {
                    return; // Don't hide if user cancels
                }
                hasUnsavedChanges = false;
            }
            
            // Call original function
            originalHideAllContainers();
        };
        console.log('✅ hideAllContainers wrapped with unsaved changes check');
    } else {
        console.warn('⚠️ hideAllContainers function not found');
    }
});

// Auto-load configuration
async function autoLoadConfiguration() {
    const projectName = document.querySelector('input[name="project_name"]')?.value;
    
    if (!projectName) {
        console.log('ℹ️ No project name - skipping auto-load');
        return;
    }
    
    console.log('🔄 Auto-loading for:', projectName);
    
    try {
        const response = await fetch(`network_includes/load_network_config.php?project_name=${encodeURIComponent(projectName)}`);
        const responseText = await response.text();
        
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            console.error('❌ JSON Parse Error:', e);
            return;
        }
        
        if (result.success) {
            console.log('✅ Configuration loaded');
            
            // Check if user quantity changed
            const currentUserQty = document.querySelector('select[name="user_quantity"]')?.value;
            const savedUserQty = result.configuration?.project_info?.user_quantity;
            
            if (currentUserQty && savedUserQty && currentUserQty !== savedUserQty) {
                // User quantity changed — load silently with new quantity, no popup
                populateForm(result.configuration, true);
                showInfoMessage(`✅ Configuration loaded. Equipment updated for "${currentUserQty}".`);
                hasUnsavedChanges = false;
            } else {
                // No change - load normally
                populateForm(result.configuration, false);
                showInfoMessage(`Last saved: ${result.last_saved}`);
                hasUnsavedChanges = false;
            }
        } else {
            console.log('ℹ️ No saved configuration');
            
            // No saved config - but still detect site type from current user quantity
            setTimeout(() => {
                console.log('🔄 Detecting site type from current project details...');
                if (typeof detectUserRange === 'function') {
                    detectUserRange();
                }
                if (typeof updateSiteTypeConfiguration === 'function') {
                    updateSiteTypeConfiguration();
                }
            }, 100);
        }
        
    } catch (error) {
        console.log('ℹ️ Auto-load skipped:', error.message);
    }
}

// Populate form - FIXED VERSION with proper checkbox restoration timing
function populateForm(config, userQtyChanged = false) {
    console.log('📥 Populating form', userQtyChanged ? '(User quantity changed - equipment will reset)' : '');
    
    // 1. Load Project Info (session values)
    if (config.project_info) {
        const pi = config.project_info;
        
        // Only load user_quantity if it hasn't changed
        if (!userQtyChanged && pi.user_quantity) {
            const userQtySelect = document.querySelector('select[name="user_quantity"]');
            if (userQtySelect) {
                userQtySelect.disabled = false;
                userQtySelect.value = pi.user_quantity;
                userQtySelect.disabled = true;
            }
        }
    }
    
    // 2. Load Server Preference
    if (config.site_config && config.site_config.server_required) {
        const radio = document.querySelector(`input[name="server_required"][value="${config.site_config.server_required}"]`);
        if (radio) radio.checked = true;
    }
    
    // 3. Load Network Infrastructure (WITH TOGGLES!)
    if (config.network_infrastructure) {
        const ni = config.network_infrastructure;
        console.log('🌐 Loading network infrastructure:', ni);
        
        // Internet Access
        if (ni.internet_access) {
            document.querySelector('select[name="internet_access"]').value = ni.internet_access;
            if (typeof toggleInternetAccess === 'function') toggleInternetAccess();
        }
        
        // Small delay to let toggle complete
        setTimeout(() => {
            // DIA
            if (ni.dia) {
                document.querySelector('select[name="dia"]').value = ni.dia;
            }
            
            // Business Broadband
            if (ni.business_broadband) {
                document.querySelector('select[name="business_broadband"]').value = ni.business_broadband;
                if (typeof toggleStarlink === 'function') toggleStarlink();
            }
            
            // Another small delay for Starlink toggle
            setTimeout(() => {
                // Starlink Type
                if (ni.starlink_type) {
                    document.querySelector('select[name="starlink_type"]').value = ni.starlink_type;
                }
            }, 100);
            
            // WAN
            if (ni.wan_connectivity) {
                document.querySelector('select[name="wan_connectivity"]').value = ni.wan_connectivity;
            }
            
            // VSAT
            if (ni.vsat) {
                document.querySelector('select[name="vsat"]').value = ni.vsat;
                if (typeof toggleVSATHA === 'function') toggleVSATHA();
            }
            
            // Another delay for VSAT HA toggle
            setTimeout(() => {
                // VSAT HA
                if (ni.vsat_ha) {
                    document.querySelector('select[name="vsat_ha"]').value = ni.vsat_ha;
                    if (typeof toggleVSATService === 'function') toggleVSATService();
                }
                
                // Final delay for VSAT Service
                setTimeout(() => {
                    // VSAT Service
                    if (ni.vsat_service) {
                        document.querySelector('select[name="vsat_service"]').value = ni.vsat_service;
                    }
                    
                    // ✅ NOW trigger pricing update which creates installation checkboxes
                    console.log('💰 Triggering pricing update to create checkboxes...');
                    if (typeof updateNetworkConfigPricing === 'function') {
                        updateNetworkConfigPricing();
                    }
                    
                    // ✅ THEN restore installation checkboxes AFTER they're created
                    restoreInstallationCheckboxes(config.installation_services);
                    
                }, 100);
            }, 100);
        }, 100);
    }
    
    // 4. Load Cables & Accessories
    if (config.cables && Array.isArray(config.cables)) {
        console.log('📦 Loading saved cables:', config.cables.length, 'items');
        
        let loadedCount = 0;
        
        config.cables.forEach(savedCable => {
            const itemName = savedCable.item;
            let found = false;
            
            // Check in accessories
            const accessoryRows = document.querySelectorAll('#general-accessories-list tr');
            accessoryRows.forEach(row => {
                const itemInput = row.querySelector('input[name="generalAccessoryItem[]"]');
                if (itemInput && itemInput.value === itemName) {
                    const qtyInput = row.querySelector('input[name="generalAccessoryQty[]"]');
                    if (qtyInput) {
                        qtyInput.value = savedCable.quantity;
                        console.log('✅ Set accessory:', itemName, '=', savedCable.quantity);
                        found = true;
                        loadedCount++;
                    }
                }
            });
            
            // Check in cables
            const cableRows = document.querySelectorAll('#detailed-cable-list tr');
            cableRows.forEach(row => {
                const itemInput = row.querySelector('input[name="cableItem[]"]');
                if (itemInput && itemInput.value === itemName) {
                    const qtyInput = row.querySelector('input[name="cableQty[]"]');
                    if (qtyInput) {
                        qtyInput.value = savedCable.quantity;
                        console.log('✅ Set cable:', itemName, '=', savedCable.quantity);
                        found = true;
                        loadedCount++;
                    }
                }
            });
            
            if (!found) {
                console.warn('⚠️ Not found:', itemName);
            }
        });
        
        console.log('📊 Total loaded:', loadedCount, 'out of', config.cables.length);
    }
    
    // 5. Load Notes
    if (config.notes) {
        document.querySelector('textarea[name="notes"]').value = config.notes;
    }
    
    // 6. Calculate Site Type
    setTimeout(() => {
        console.log('🔢 Calculating site type from current selections...');
        
        if (typeof detectUserRange === 'function') {
            detectUserRange();
        }
        
        if (typeof updateSiteTypeConfiguration === 'function') {
            updateSiteTypeConfiguration();
        }
        
        if (typeof updateCablesAccessoriesPricing === 'function') {
            console.log('📄 Updating cable pricing after load...');
            updateCablesAccessoriesPricing();
        }
        
        console.log('✅ Site type calculated and prices updated');
    }, 600);
    
    hasUnsavedChanges = false;
}

// ✅ NEW: Separate function to restore installation checkboxes
// This is called AFTER updateNetworkConfigPricing() creates the checkboxes
function restoreInstallationCheckboxes(installationServices) {
    if (!installationServices || !Array.isArray(installationServices)) {
        console.log('ℹ️ No installation services to restore');
        return;
    }
    
    console.log('📦 Attempting to restore installation selections:', installationServices.length, 'items');
    
    // Wait a bit more to ensure checkboxes are fully created
    setTimeout(() => {
        let restoredCount = 0;
        
        installationServices.forEach(installation => {
            const checkboxName = `install_${installation.item_key}`;
            const checkbox = document.querySelector(`input[name="${checkboxName}"]`);
            
            if (checkbox) {
                checkbox.checked = true;
                restoredCount++;
                console.log('✅ Restored installation checkbox for:', installation.item_key, '($' + installation.install_cost + ')');
            } else {
                console.warn('⚠️ Installation checkbox not found:', checkboxName);
                // Debug: show all installation checkboxes that exist
                const allInstallCheckboxes = document.querySelectorAll('input[type="checkbox"][name^="install_"]');
                console.log('Available installation checkboxes:', Array.from(allInstallCheckboxes).map(cb => cb.name));
            }
        });
        
        console.log(`📊 Restored ${restoredCount} out of ${installationServices.length} installation checkboxes`);
        
        // Recalculate pricing to show updated totals with installation costs
        if (typeof updateNetworkConfigPricing === 'function') {
            console.log('💰 Recalculating pricing with restored installation selections...');
            updateNetworkConfigPricing();
        }
    }, 300); // Wait 300ms after pricing update to ensure checkboxes exist
}

// Status indicators
function showInfoMessage(message) {
    const status = document.getElementById('save-status');
    const statusText = document.getElementById('save-status-text');
    
    if (status && statusText) {
        status.style.display = 'block';
        status.style.background = '#e3f2fd';
        status.style.borderLeft = '4px solid #2196F3';
        status.style.color = '#1565c0';
        statusText.textContent = 'ℹ️ ' + message;
        
        setTimeout(() => {
            status.style.display = 'none';
        }, 5000);
    }
}

function showUnsavedIndicator() {
    const status = document.getElementById('save-status');
    const statusText = document.getElementById('save-status-text');
    
    if (status && statusText) {
        status.style.display = 'block';
        status.style.background = '#fff3cd';
        status.style.borderLeft = '4px solid #ffc107';
        status.style.color = '#856404';
        statusText.textContent = '⚠️ You have unsaved changes';
    }
}

function showSavedIndicator(message) {
    const status = document.getElementById('save-status');
    const statusText = document.getElementById('save-status-text');
    
    if (status && statusText) {
        status.style.display = 'block';
        status.style.background = '#d4edda';
        status.style.borderLeft = '4px solid #28a745';
        status.style.color = '#155724';
        statusText.textContent = '✅ ' + (message || 'Configuration saved');
        
        hasUnsavedChanges = false;
        
        setTimeout(() => {
            status.style.display = 'none';
        }, 5000);
    }
}

function showErrorIndicator(message) {
    const status = document.getElementById('save-status');
    const statusText = document.getElementById('save-status-text');
    
    if (status && statusText) {
        status.style.display = 'block';
        status.style.background = '#f8d7da';
        status.style.borderLeft = '4px solid #dc3545';
        status.style.color = '#721c24';
        statusText.textContent = '❌ ' + message;
        
        setTimeout(() => {
            status.style.display = 'none';
        }, 7000);
    }
}

// Reset form
function resetNetworkConfiguration() {
    if (!confirm('🔄 Reset form to defaults?\n\nThis will not delete your saved configuration.')) {
        return;
    }
    
    console.log('🔄 Resetting...');
    
    // Reset fields
    document.querySelector('input[name="requesting_manager"]').value = '';
    document.querySelector('input[name="project_duration"]').value = '';
    document.querySelector('input[name="deployment_date"]').value = '';
    document.querySelector('select[name="user_quantity"]').selectedIndex = 0;
    document.querySelector('select[name="internet_access"]').selectedIndex = 0;
    document.querySelector('select[name="wan_connectivity"]').selectedIndex = 0;
    document.querySelector('select[name="vsat"]').selectedIndex = 0;
    document.querySelector('textarea[name="notes"]').value = '';
    
    // Uncheck server radios
    const serverRadios = document.querySelectorAll('input[name="server_required"]');
    serverRadios.forEach(radio => radio.checked = false);
    
    // Hide sections
    document.getElementById('dia-row').style.display = 'none';
    document.getElementById('business-broadband-row').style.display = 'none';
    document.getElementById('starlink-row').style.display = 'none';
    document.getElementById('vsat-ha-row').style.display = 'none';
    document.getElementById('vsat-service-row').style.display = 'none';
    document.getElementById('final-config-display').style.display = 'none';
    
    // Hide equipment sections
    document.querySelectorAll('.site-specific-fields').forEach(section => {
        section.classList.add('hidden');
        section.style.display = 'none';
    });
    
    // Update pricing
    if (typeof updateNetworkConfigPricing === 'function') updateNetworkConfigPricing();
    if (typeof updateCablesAccessoriesPricing === 'function') updateCablesAccessoriesPricing();
    
    hasUnsavedChanges = false;
    showInfoMessage('Form reset. Save to update database.');
    console.log('✅ Reset complete');
}

// Save configuration
async function saveNetworkConfiguration() {
    const btn = event.target;
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '⏳ Saving...';
    
    try {
        const formData = collectFormData();
        
        const response = await fetch('network_includes/save_network_config.php', {
            method: 'POST',
            body: formData
        });
        
        const responseText = await response.text();
        console.log('📡 Response:', responseText);
        
        if (!response.ok) {
            throw new Error('Network error');
        }
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            console.error('❌ JSON Error:', e);
            throw new Error('Invalid JSON response');
        }
        
        if (result.success) {
            showSavedIndicator(result.message);
            btn.innerHTML = '✅ Saved!';
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 2000);
        } else {
            throw new Error(result.message || 'Save failed');
        }
        
    } catch (error) {
        console.error('Save error:', error);
        showErrorIndicator('Error: ' + error.message);
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

// Collect form data - UPDATED VERSION with Installation Services
function collectFormData() {
    const formData = new FormData();
    formData.append('action', 'save_network_config');
    
    // Basic info
    formData.append('project_name', document.querySelector('input[name="project_name"]')?.value || '');
    formData.append('requesting_manager', document.querySelector('input[name="requesting_manager"]')?.value || '');
    formData.append('project_duration', document.querySelector('input[name="project_duration"]')?.value || '');
    formData.append('deployment_date', document.querySelector('input[name="deployment_date"]')?.value || '');
    formData.append('user_quantity', document.querySelector('select[name="user_quantity"]')?.value || '');
    
    // Server preference (NOT site_type - that's calculated dynamically)
    const serverRequired = document.querySelector('input[name="server_required"]:checked');
    formData.append('server_required', serverRequired?.value || '');
    
    // Network infrastructure
    formData.append('internet_access', document.querySelector('select[name="internet_access"]')?.value || '');
    formData.append('dia', document.querySelector('select[name="dia"]')?.value || '');
    formData.append('business_broadband', document.querySelector('select[name="business_broadband"]')?.value || '');
    formData.append('starlink_type', document.querySelector('select[name="starlink_type"]')?.value || '');
    formData.append('wan_connectivity', document.querySelector('select[name="wan_connectivity"]')?.value || '');
    formData.append('vsat', document.querySelector('select[name="vsat"]')?.value || '');
    formData.append('vsat_ha', document.querySelector('select[name="vsat_ha"]')?.value || '');
    formData.append('vsat_service', document.querySelector('select[name="vsat_service"]')?.value || '');
    
    // ✅ NEW: Collect Installation Service Selections
    const installationSelections = [];
    
    // Check all installation checkboxes
    const installCheckboxes = document.querySelectorAll('input[type="checkbox"][name^="install_"]');
    installCheckboxes.forEach(checkbox => {
        if (checkbox.checked) {
            const itemKey = checkbox.name.replace('install_', '');
            const installCost = parseFloat(checkbox.getAttribute('data-install-cost')) || 0;
            
            installationSelections.push({
                item_key: itemKey,
                install_cost: installCost,
                checked: true
            });
        }
    });
    
    formData.append('installation_selections', JSON.stringify(installationSelections));
    console.log('💾 Installation Selections:', installationSelections);
    
    // Equipment (from visible section)
    const equipmentData = [];
    const visibleSection = document.querySelector('.site-specific-fields:not(.hidden)');
    if (visibleSection) {
        visibleSection.querySelectorAll('input[type="number"]').forEach(input => {
            const qty = parseFloat(input.value) || 0;
            if (qty > 0) {
                const row = input.closest('tr');
                const itemName = row.querySelector('td:first-child')?.textContent.trim() || '';
                equipmentData.push({
                    name: input.name,
                    item_description: itemName,
                    quantity: qty,
                    price: input.getAttribute('data-price') || '0'
                });
            }
        });
    }
    formData.append('equipment_data', JSON.stringify(equipmentData));
    
    // Cables & Accessories
    const cablesData = [];
    document.querySelectorAll('#general-accessories-list tr').forEach(row => {
        const qtyInput = row.querySelector('input[name="generalAccessoryQty[]"]');
        const itemInput = row.querySelector('input[name="generalAccessoryItem[]"]');
        if (qtyInput && itemInput) {
            const qty = parseFloat(qtyInput.value) || 0;
            if (qty > 0) {
                cablesData.push({
                    type: 'accessory',
                    item: itemInput.value,
                    quantity: qty,
                    price: itemInput.getAttribute('data-price') || '0'
                });
            }
        }
    });
    
    document.querySelectorAll('#detailed-cable-list tr').forEach(row => {
        const qtyInput = row.querySelector('input[name="cableQty[]"]');
        const itemInput = row.querySelector('input[name="cableItem[]"]');
        if (qtyInput && itemInput) {
            const qty = parseFloat(qtyInput.value) || 0;
            if (qty > 0) {
                cablesData.push({
                    type: 'cable',
                    item: itemInput.value,
                    quantity: qty,
                    price: itemInput.getAttribute('data-price') || '0'
                });
            }
        }
    });
    formData.append('cables_data', JSON.stringify(cablesData));
    
    // Notes
    formData.append('notes', document.querySelector('textarea[name="notes"]')?.value || '');
    
    return formData;
}

// Warn before leaving
window.addEventListener('beforeunload', function(e) {
    if (hasUnsavedChanges) {
        e.preventDefault();
        e.returnValue = 'You have unsaved changes';
        return e.returnValue;
    }
});

console.log('✅ Network Save Handler Loaded (with forced price recalculation)');