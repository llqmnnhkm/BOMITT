<?php
// includes/network_form_sections/infrastructure_config.php
// Network Infrastructure Selection Section with Installation Service Support

// Fetch all network infrastructure config from database (including installation fields)
$config_query = "SELECT * FROM network_infrastructure_config WHERE is_active = 1 ORDER BY display_order";
$config_result = $conn->query($config_query);

// Organize data by type
$internet_dia = [];
$internet_broadband = [];
$wan_options = [];
$vsat_options = [];
$config_prices = [];
$installation_config = []; // Store installation settings

while ($row = $config_result->fetch_assoc()) {
    $config_prices[$row['item_value']] = $row['price'];
    
    // Store installation config
    $installation_config[$row['item_value']] = [
        'type' => $row['installation_type'],
        'value' => $row['installation_value']
    ];
    
    if ($row['item_type'] === 'Internet Access') {
        if ($row['parent_item'] === 'DIA') {
            $internet_dia[] = $row;
        } elseif ($row['parent_item'] === 'Business Broadband') {
            $internet_broadband[] = $row;
        }
    } elseif ($row['item_type'] === 'WAN Connectivity') {
        $wan_options[] = $row;
    } elseif ($row['item_type'] === 'VSAT') {
        $vsat_options[] = $row;
    }
}
?>

<div class="question-group" style="margin-bottom: 2rem;">
    <label style="font-size: 1.125rem; font-weight: 600; display:block; margin-bottom: 0.25rem;">
        Network Infrastructure Selection <span class="required">*</span>
    </label>
    <p style="font-size: 0.875rem; color: #666; margin: 0 0 1rem 0;">
        Configure your network infrastructure requirements. Prices will be calculated automatically and multiplied by project duration (<?php echo htmlspecialchars($project_duration); ?> months).
    </p>

    <table style="width: 100%; border-collapse: collapse; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-bottom: 1.5rem;">
        <thead style="background: linear-gradient(90deg, #0070ef 0%, #3d98b7 100%); color: white;">
            <tr>
                <th style="padding: 12px; text-align: left; font-size: 0.95rem; font-weight: 600;">Configuration Item</th>
                <th style="padding: 12px; text-align: center; width: 200px; font-size: 0.95rem; font-weight: 600;">Selection</th>
                <th style="padding: 12px; text-align: right; width: 130px; font-size: 0.95rem; font-weight: 600;">Monthly Price</th>
                <th style="padding: 12px; text-align: right; width: 150px; font-size: 0.95rem; font-weight: 600;">Total (<?php echo htmlspecialchars($project_duration); ?> months)</th>
                <th style="padding: 12px; text-align: center; width: 160px; font-size: 0.95rem; font-weight: 600;">Installation</th>
            </tr>
        </thead>
        <tbody style="background-color: white;">
            <!-- Internet Access (Parent) -->
            <tr style="border-bottom: 1px solid #e0e0e0; transition: background 0.2s;">
                <td style="padding: 12px; font-weight: 500; color: #2d3748;">
                    Internet Access
                </td>
                <td style="padding: 12px; text-align: center;">
                    <select name="internet_access" onchange="toggleInternetAccess(); updateNetworkConfigPricing()" 
                        style="width: 280px; padding: 8px 12px; border: 1px solid #ccc; border-radius: 6px; font-family: Montserrat; font-size: 0.9rem; cursor: pointer;">
                        <option value="">-- Select --</option>
                        <option value="DIA">Direct Internet Access (DIA)</option>
                        <option value="Business Broadband">Business Broadband</option>
                        <option value="None">None</option>
                    </select>
                </td>
                <td style="padding: 12px; text-align: right; font-weight: 600; color: #666;">-</td>
                <td style="padding: 12px; text-align: right; font-weight: 600; color: #0070ef;">-</td>
                <td style="padding: 12px; text-align: center;">-</td>
            </tr>

            <!-- DIA Sub-options -->
            <tr id="dia-row" style="display: none; border-bottom: 1px solid #e0e0e0; background-color: #f7f9fc; transition: background 0.2s;">
                <td style="padding: 12px; padding-left: 40px; font-weight: 500; color: #2d3748;">
                    → Direct Internet Access (DIA)
                </td>
                <td style="padding: 12px; text-align: center;">
                    <select name="dia" onchange="updateNetworkConfigPricing()" 
                        style="width: 280px; padding: 8px 12px; border: 1px solid #ccc; border-radius: 6px; font-family: Montserrat; font-size: 0.9rem; cursor: pointer;">
                        <option value="">-- Select --</option>
                        <?php foreach ($internet_dia as $item): ?>
                            <option value="<?php echo htmlspecialchars($item['item_value']); ?>">
                                <?php echo htmlspecialchars($item['item_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td style="padding: 12px; text-align: right; font-weight: 600; color: #80c7a0;" id="price-dia-monthly">-</td>
                <td style="padding: 12px; text-align: right; font-weight: 600; color: #0070ef;" id="price-dia">-</td>
                <td style="padding: 12px; text-align: center;" id="install-dia-cell">-</td>
            </tr>

            <!-- Business Broadband Sub-options -->
            <tr id="business-broadband-row" style="display: none; border-bottom: 1px solid #e0e0e0; background-color: #f7f9fc; transition: background 0.2s;">
                <td style="padding: 12px; padding-left: 40px; font-weight: 500; color: #2d3748;">
                    → Business Broadband
                </td>
                <td style="padding: 12px; text-align: center;">
                    <select name="business_broadband" onchange="toggleStarlink(); updateNetworkConfigPricing()" 
                        style="width: 280px; padding: 8px 12px; border: 1px solid #ccc; border-radius: 6px; font-family: Montserrat; font-size: 0.9rem; cursor: pointer;">
                        <option value="">-- Select --</option>
                        <?php 
                        $shown_starlink = false;
                        foreach ($internet_broadband as $item): 
                            if (strpos($item['item_value'], 'Starlink') !== false) {
                                if (!$shown_starlink) {
                                    echo '<option value="Starlink">Starlink</option>';
                                    $shown_starlink = true;
                                }
                            } else {
                        ?>
                            <option value="<?php echo htmlspecialchars($item['item_value']); ?>">
                                <?php echo htmlspecialchars($item['item_value']); ?>
                            </option>
                        <?php 
                            }
                        endforeach; 
                        ?>
                    </select>
                </td>
                <td style="padding: 12px; text-align: right; font-weight: 600; color: #80c7a0;" id="price-business-broadband-monthly">-</td>
                <td style="padding: 12px; text-align: right; font-weight: 600; color: #0070ef;" id="price-business-broadband">-</td>
                <td style="padding: 12px; text-align: center;" id="install-business-broadband-cell">-</td>
            </tr>

            <!-- Starlink Type -->
            <tr id="starlink-row" style="display: none; border-bottom: 1px solid #e0e0e0; background-color: #f0f4f8; transition: background 0.2s;">
                <td style="padding: 12px; padding-left: 60px; font-weight: 500; color: #2d3748; font-size: 0.9rem;">
                    ↳ Starlink Type
                </td>
                <td style="padding: 12px; text-align: center;">
                    <select name="starlink_type" onchange="updateNetworkConfigPricing()" 
                        style="width: 280px; padding: 8px 12px; border: 1px solid #ccc; border-radius: 6px; font-family: Montserrat; font-size: 0.9rem; cursor: pointer;">
                        <option value="">-- Select --</option>
                        <?php 
                        foreach ($internet_broadband as $item): 
                            if (strpos($item['item_value'], 'Starlink') !== false):
                                $display_name = str_replace('Starlink_', '', $item['item_value']);
                                $display_name = str_replace('_', ' ', $display_name);
                        ?>
                            <option value="<?php echo htmlspecialchars($item['item_value']); ?>">
                                <?php echo htmlspecialchars($display_name); ?>
                            </option>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </select>
                </td>
                <td style="padding: 12px; text-align: right; font-weight: 600; color: #80c7a0;" id="price-starlink-monthly">-</td>
                <td style="padding: 12px; text-align: right; font-weight: 600; color: #0070ef;" id="price-starlink">-</td>
                <td style="padding: 12px; text-align: center;" id="install-starlink-cell">-</td>
            </tr>
            
            <!-- WAN Connectivity -->
            <tr style="border-bottom: 1px solid #e0e0e0; transition: background 0.2s;">
                <td style="padding: 12px; font-weight: 500; color: #2d3748;">
                    Wide Area Network (WAN) Connectivity
                </td>
                <td style="padding: 12px; text-align: center;">
                    <select name="wan_connectivity" onchange="updateNetworkConfigPricing()"
                        style="width: 280px; padding: 8px 12px; border: 1px solid #ccc; border-radius: 6px; font-family: Montserrat; font-size: 0.9rem; cursor: pointer;">
                        <option value="">-- Select --</option>
                        <?php foreach ($wan_options as $item): ?>
                            <option value="<?php echo htmlspecialchars($item['item_value']); ?>">
                                <?php echo htmlspecialchars($item['item_name']); ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="None">None</option>
                    </select>
                </td>
                <td style="padding: 12px; text-align: right; font-weight: 600; color: #80c7a0;" id="price-wan-monthly">-</td>
                <td style="padding: 12px; text-align: right; font-weight: 600; color: #0070ef;" id="price-wan">-</td>
                <td style="padding: 12px; text-align: center;" id="install-wan-cell">-</td>
            </tr>
            
            <!-- VSAT -->
            <tr style="border-bottom: 1px solid #e0e0e0; transition: background 0.2s;">
                <td style="padding: 12px; font-weight: 500; color: #2d3748;">
                    VSAT (Satellite Communication)
                </td>
                <td style="padding: 12px; text-align: center;">
                    <select name="vsat" onchange="toggleVSATHA(); updateNetworkConfigPricing()" 
                        style="width: 280px; padding: 8px 12px; border: 1px solid #ccc; border-radius: 6px; font-family: Montserrat; font-size: 0.9rem; cursor: pointer;">
                        <option value="">-- Select --</option>
                        <?php foreach ($vsat_options as $item): ?>
                            <option value="<?php echo htmlspecialchars($item['item_value']); ?>">
                                <?php echo htmlspecialchars($item['item_name']); ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="None">None</option>
                    </select>
                </td>
                <td style="padding: 12px; text-align: right; font-weight: 600; color: #80c7a0;" id="price-vsat-monthly">-</td>
                <td style="padding: 12px; text-align: right; font-weight: 600; color: #0070ef;" id="price-vsat">-</td>
                <td style="padding: 12px; text-align: center;" id="install-vsat-cell">-</td>
            </tr>

            <!-- VSAT HA Sub-option -->
            <tr id="vsat-ha-row" style="display: none; border-bottom: 1px solid #e0e0e0; background-color: #f7f9fc; transition: background 0.2s;">
                <td style="padding: 12px; padding-left: 40px; font-weight: 500; color: #2d3748;">
                    → High Availability Required
                </td>
                <td style="padding: 12px; text-align: center;">
                    <select name="vsat_ha" onchange="toggleVSATService(); updateNetworkConfigPricing()" 
                        style="width: 280px; padding: 8px 12px; border: 1px solid #ccc; border-radius: 6px; font-family: Montserrat; font-size: 0.9rem; cursor: pointer;">
                        <option value="">-- Select --</option>
                        <option value="Yes">Yes</option>
                        <option value="No">No</option>
                    </select>
                </td>
                <td style="padding: 12px; text-align: right; font-weight: 600; color: #80c7a0;" id="price-vsat-ha-monthly">-</td>
                <td style="padding: 12px; text-align: right; font-weight: 600; color: #0070ef;" id="price-vsat-ha">-</td>
                <td style="padding: 12px; text-align: center;">-</td>
            </tr>

            <!-- VSAT Service Usage -->
            <tr id="vsat-service-row" style="display: none; border-bottom: 1px solid #e0e0e0; background-color: #f0f4f8; transition: background 0.2s;">
                <td style="padding: 12px; padding-left: 60px; font-weight: 500; color: #2d3748; font-size: 0.9rem;">
                    ↳ Intended Service Usage
                </td>
                <td style="padding: 12px; text-align: center;">
                    <select name="vsat_service" onchange="updateNetworkConfigPricing()" 
                        style="width: 280px; padding: 8px 12px; border: 1px solid #ccc; border-radius: 6px; font-family: Montserrat; font-size: 0.9rem; cursor: pointer;">
                        <option value="">-- Select --</option>
                        <option value="Internet Service">Internet Service</option>
                        <option value="Corporate Network Service">Corporate Network Service</option>
                    </select>
                </td>
                <td style="padding: 12px; text-align: right; font-weight: 600; color: #80c7a0;" id="price-vsat-service-monthly">-</td>
                <td style="padding: 12px; text-align: right; font-weight: 600; color: #0070ef;" id="price-vsat-service">-</td>
                <td style="padding: 12px; text-align: center;">-</td>
            </tr>

            <!-- Installation Subtotal Row -->
            <tr style="background: linear-gradient(90deg, rgba(76, 175, 80, 0.1) 0%, rgba(139, 195, 74, 0.1) 100%); border-top: 2px solid #4caf50;">
                <td colspan="4" style="padding: 12px; text-align: right; font-weight: 600; color: #388e3c;">
                    Installation Services Subtotal:
                </td>
                <td style="padding: 12px; text-align: center; font-weight: 700; font-size: 1.1rem; color: #2e7d32;" id="installation-subtotal">$0.00</td>
            </tr>

            <!-- Total Row -->
            <tr style="background: linear-gradient(90deg, rgba(0, 112, 239, 0.1) 0%, rgba(128, 199, 160, 0.1) 100%); border-top: 3px solid #0070ef;">
                <td colspan="4" style="padding: 15px; font-weight: 700; font-size: 1.1rem; color: #0070ef; text-align: right;">
                    Network Configuration Total (<?php echo htmlspecialchars($project_duration); ?> months):
                </td>
                <td style="padding: 15px; text-align: center; font-weight: 700; font-size: 1.2rem; color: #0070ef;" id="network-config-total">$0.00</td>
            </tr>
        </tbody>
    </table>
</div>

<script>
// Get project duration
function getProjectDuration() {
    const durationInput = document.querySelector('input[name="project_duration"]');
    const duration = durationInput ? parseFloat(durationInput.value) : 1;
    return duration > 0 ? duration : 1;
}

// Pricing database from PHP
const NETWORK_CONFIG_PRICES = <?php echo json_encode($config_prices); ?>;
const INSTALLATION_CONFIG = <?php echo json_encode($installation_config); ?>;

// Add hardcoded VSAT HA and Service prices
NETWORK_CONFIG_PRICES['vsat_ha_yes'] = 2000.00;
NETWORK_CONFIG_PRICES['vsat_ha_no'] = 0.00;
NETWORK_CONFIG_PRICES['Internet Service'] = 1000.00;
NETWORK_CONFIG_PRICES['Corporate Network Service'] = 2000.00;
NETWORK_CONFIG_PRICES['None'] = 0.00;

// Helper function to safely get price
function getPrice(key) {
    if (!key || key === '' || key === 'None') return 0;
    const price = NETWORK_CONFIG_PRICES[key];
    return (price !== undefined && price !== null && !isNaN(price)) ? parseFloat(price) : 0;
}

// Calculate installation cost
function calculateInstallation(itemKey, monthlyPrice) {
    const config = INSTALLATION_CONFIG[itemKey];
    if (!config || config.type === 'none') return 0;
    
    if (config.type === 'percentage') {
        return monthlyPrice * (parseFloat(config.value) / 100);
    } else if (config.type === 'fixed') {
        return parseFloat(config.value);
    }
    return 0;
}

// REPLACE the createInstallationCheckbox function in infrastructure_config.php
// This fixes the issue where installation percentages don't update when changing selections

function createInstallationCheckbox(itemKey, monthlyPrice, cellId) {
    const config = INSTALLATION_CONFIG[itemKey];
    const cell = document.getElementById(cellId);

    if (!config || config.type === 'none') {
        cell.innerHTML = '-';
        return;
    }

    // ✅ CHECK if checkbox exists AND if it's for the SAME item
    const existingCheckbox = cell.querySelector('input[type="checkbox"]');
    const existingItemKey = existingCheckbox?.getAttribute('data-item-key');
    
    // Only skip recreation if it's the EXACT SAME item
    if (existingCheckbox && existingItemKey === itemKey) {
        return; // Same item, no need to recreate
    }

    // Calculate installation cost
    const installCost = calculateInstallation(itemKey, monthlyPrice);
    const displayText = config.type === 'percentage'
        ? `${config.value}% ($${installCost.toFixed(2)})`
        : `$${installCost.toFixed(2)}`;

    // Save checkbox state if it exists
    const wasChecked = existingCheckbox ? existingCheckbox.checked : false;

    // Recreate with updated values
    cell.innerHTML = `
        <label style="cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px;">
            <input type="checkbox"
                name="install_${itemKey}"
                data-item-key="${itemKey}"
                data-install-cost="${installCost}"
                onchange="updateNetworkConfigPricing()"
                ${wasChecked ? 'checked' : ''}
                style="width:18px;height:18px;cursor:pointer;">
            <span style="font-size:0.9rem;font-weight:600;color:#2e7d32;">
                ${displayText}
            </span>
        </label>
    `;
}


// Toggle Internet Access sub-options
function toggleInternetAccess() {
    const internetAccess = document.querySelector('select[name="internet_access"]').value;
    const diaRow = document.getElementById('dia-row');
    const businessBroadbandRow = document.getElementById('business-broadband-row');
    const starlinkRow = document.getElementById('starlink-row');
    
    // Reset all
    diaRow.style.display = 'none';
    businessBroadbandRow.style.display = 'none';
    starlinkRow.style.display = 'none';
    
    document.querySelector('select[name="dia"]').value = '';
    document.querySelector('select[name="business_broadband"]').value = '';
    document.querySelector('select[name="starlink_type"]').value = '';
    
    // Reset prices
    document.getElementById('price-dia-monthly').textContent = '-';
    document.getElementById('price-dia').textContent = '-';
    document.getElementById('price-business-broadband-monthly').textContent = '-';
    document.getElementById('price-business-broadband').textContent = '-';
    document.getElementById('price-starlink-monthly').textContent = '-';
    document.getElementById('price-starlink').textContent = '-';
    
    // Reset installation cells
    document.getElementById('install-dia-cell').innerHTML = '-';
    document.getElementById('install-business-broadband-cell').innerHTML = '-';
    document.getElementById('install-starlink-cell').innerHTML = '-';
    
    if (internetAccess === 'DIA') {
        diaRow.style.display = 'table-row';
    } else if (internetAccess === 'Business Broadband') {
        businessBroadbandRow.style.display = 'table-row';
    }
}

// Toggle Starlink type
function toggleStarlink() {
    const businessBroadband = document.querySelector('select[name="business_broadband"]').value;
    const starlinkRow = document.getElementById('starlink-row');
    
    if (businessBroadband === 'Starlink') {
        starlinkRow.style.display = 'table-row';
        document.getElementById('price-business-broadband-monthly').textContent = 'See below ↓';
        document.getElementById('price-business-broadband').textContent = 'See below ↓';
        document.getElementById('install-business-broadband-cell').innerHTML = 'See below ↓';
    } else {
        starlinkRow.style.display = 'none';
        document.querySelector('select[name="starlink_type"]').value = '';
        document.getElementById('price-starlink-monthly').textContent = '-';
        document.getElementById('price-starlink').textContent = '-';
        document.getElementById('install-starlink-cell').innerHTML = '-';
    }
}

// Toggle VSAT HA
function toggleVSATHA() {
    const vsat = document.querySelector('select[name="vsat"]').value;
    const vsatHARow = document.getElementById('vsat-ha-row');
    const vsatServiceRow = document.getElementById('vsat-service-row');
    
    if (vsat && vsat !== 'None' && vsat !== '') {
        vsatHARow.style.display = 'table-row';
    } else {
        vsatHARow.style.display = 'none';
        vsatServiceRow.style.display = 'none';
        document.querySelector('select[name="vsat_ha"]').value = '';
        document.querySelector('select[name="vsat_service"]').value = '';
        document.getElementById('price-vsat-ha-monthly').textContent = '-';
        document.getElementById('price-vsat-ha').textContent = '-';
        document.getElementById('price-vsat-service-monthly').textContent = '-';
        document.getElementById('price-vsat-service').textContent = '-';
    }
}

// Toggle VSAT Service (after HA selection)
function toggleVSATService() {
    const vsatHA = document.querySelector('select[name="vsat_ha"]').value;
    const vsatServiceRow = document.getElementById('vsat-service-row');
    
    if (vsatHA) {
        vsatServiceRow.style.display = 'table-row';
    } else {
        vsatServiceRow.style.display = 'none';
        document.querySelector('select[name="vsat_service"]').value = '';
        document.getElementById('price-vsat-service-monthly').textContent = '-';
        document.getElementById('price-vsat-service').textContent = '-';
    }
}

// Format currency
function formatCurrency(value) {
    if (value === null || value === undefined || isNaN(value)) {
        return '$0.00';
    }
    return '$' + parseFloat(value).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// Update network configuration pricing display (WITH INSTALLATION SUPPORT)
function updateNetworkConfigPricing() {
    const projectDuration = getProjectDuration();
    let total = 0;
    let installationTotal = 0;
    
    console.log('🔢 Project Duration (months):', projectDuration);
    
    // Internet Access
    const internetAccess = document.querySelector('select[name="internet_access"]').value;
    
    // DIA
    const dia = document.querySelector('select[name="dia"]').value;
    const diaMonthlyPrice = getPrice(dia);
    const diaTotalPrice = diaMonthlyPrice * projectDuration;
    
    document.getElementById('price-dia-monthly').textContent = (dia && dia !== '') ? formatCurrency(diaMonthlyPrice) : '-';
    document.getElementById('price-dia').textContent = (dia && dia !== '') ? formatCurrency(diaTotalPrice) : '-';
    
    if (dia && dia !== '') {
        createInstallationCheckbox(dia, diaMonthlyPrice, 'install-dia-cell');
    }
    
    if (internetAccess === 'DIA' && dia && dia !== '') {
        total += diaTotalPrice;
        
        // Check if installation is selected
        const installCheckbox = document.querySelector(`input[name="install_${dia}"]`);
        if (installCheckbox && installCheckbox.checked) {
            installationTotal += parseFloat(installCheckbox.getAttribute('data-install-cost'));
        }
    }
    
    // Business Broadband
    const businessBroadband = document.querySelector('select[name="business_broadband"]').value;
    let businessBroadbandMonthlyPrice = 0;
    let businessBroadbandTotalPrice = 0;

    if (businessBroadband === 'Starlink') {
        const starlinkType = document.querySelector('select[name="starlink_type"]').value;
        businessBroadbandMonthlyPrice = getPrice(starlinkType);
        businessBroadbandTotalPrice = businessBroadbandMonthlyPrice * projectDuration;
        
        document.getElementById('price-starlink-monthly').textContent = (starlinkType && starlinkType !== '') ? formatCurrency(businessBroadbandMonthlyPrice) : '-';
        document.getElementById('price-starlink').textContent = (starlinkType && starlinkType !== '') ? formatCurrency(businessBroadbandTotalPrice) : '-';
        document.getElementById('price-business-broadband-monthly').textContent = 'See below ↓';
        document.getElementById('price-business-broadband').textContent = 'See below ↓';
        
        if (starlinkType && starlinkType !== '') {
            createInstallationCheckbox(starlinkType, businessBroadbandMonthlyPrice, 'install-starlink-cell');
        }
        
        if (internetAccess === 'Business Broadband' && starlinkType && starlinkType !== '') {
            total += businessBroadbandTotalPrice;
            
            const installCheckbox = document.querySelector(`input[name="install_${starlinkType}"]`);
            if (installCheckbox && installCheckbox.checked) {
                installationTotal += parseFloat(installCheckbox.getAttribute('data-install-cost'));
            }
        }
    } else if (businessBroadband && businessBroadband !== '') {
        businessBroadbandMonthlyPrice = getPrice(businessBroadband);
        businessBroadbandTotalPrice = businessBroadbandMonthlyPrice * projectDuration;
        
        document.getElementById('price-business-broadband-monthly').textContent = formatCurrency(businessBroadbandMonthlyPrice);
        document.getElementById('price-business-broadband').textContent = formatCurrency(businessBroadbandTotalPrice);
        
        createInstallationCheckbox(businessBroadband, businessBroadbandMonthlyPrice, 'install-business-broadband-cell');
        
        if (internetAccess === 'Business Broadband') {
            total += businessBroadbandTotalPrice;
            
            const installCheckbox = document.querySelector(`input[name="install_${businessBroadband}"]`);
            if (installCheckbox && installCheckbox.checked) {
                installationTotal += parseFloat(installCheckbox.getAttribute('data-install-cost'));
            }
        }
    } else {
        document.getElementById('price-business-broadband-monthly').textContent = '-';
        document.getElementById('price-business-broadband').textContent = '-';
    }
    
    // WAN Connectivity
    const wan = document.querySelector('select[name="wan_connectivity"]').value;
    const wanMonthlyPrice = getPrice(wan);
    const wanTotalPrice = wanMonthlyPrice * projectDuration;
    // WAN Connectivity (CONTINUED FROM PART 1)
    document.getElementById('price-wan-monthly').textContent = (wan && wan !== 'None' && wan !== '') ? formatCurrency(wanMonthlyPrice) : '-';
    document.getElementById('price-wan').textContent = (wan && wan !== 'None' && wan !== '') ? formatCurrency(wanTotalPrice) : '-';
    
    if (wan && wan !== '' && wan !== 'None') {
        createInstallationCheckbox(wan, wanMonthlyPrice, 'install-wan-cell');
    } else {
        document.getElementById('install-wan-cell').innerHTML = '-';
    }
    
    if (wan && wan !== 'None' && wan !== '') {
        total += wanTotalPrice;
        
        const installCheckbox = document.querySelector(`input[name="install_${wan}"]`);
        if (installCheckbox && installCheckbox.checked) {
            installationTotal += parseFloat(installCheckbox.getAttribute('data-install-cost'));
        }
    }
    
    // VSAT
    const vsat = document.querySelector('select[name="vsat"]').value;
    const vsatMonthlyPrice = getPrice(vsat);
    const vsatTotalPrice = vsatMonthlyPrice * projectDuration;
    
    document.getElementById('price-vsat-monthly').textContent = (vsat && vsat !== 'None' && vsat !== '') ? formatCurrency(vsatMonthlyPrice) : '-';
    document.getElementById('price-vsat').textContent = (vsat && vsat !== 'None' && vsat !== '') ? formatCurrency(vsatTotalPrice) : '-';
    
    if (vsat && vsat !== '' && vsat !== 'None') {
        createInstallationCheckbox(vsat, vsatMonthlyPrice, 'install-vsat-cell');
    } else {
        document.getElementById('install-vsat-cell').innerHTML = '-';
    }
    
    if (vsat && vsat !== 'None' && vsat !== '') {
        total += vsatTotalPrice;
        
        const installCheckbox = document.querySelector(`input[name="install_${vsat}"]`);
        if (installCheckbox && installCheckbox.checked) {
            installationTotal += parseFloat(installCheckbox.getAttribute('data-install-cost'));
        }
    }
    
    // VSAT HA
    const vsatHA = document.querySelector('select[name="vsat_ha"]').value;
    if (vsatHA && vsatHA !== '') {
        const vsatHAKey = 'vsat_ha_' + vsatHA.toLowerCase();
        const vsatHAMonthlyPrice = getPrice(vsatHAKey);
        const vsatHATotalPrice = vsatHAMonthlyPrice * projectDuration;
        
        document.getElementById('price-vsat-ha-monthly').textContent = formatCurrency(vsatHAMonthlyPrice);
        document.getElementById('price-vsat-ha').textContent = formatCurrency(vsatHATotalPrice);
        total += vsatHATotalPrice;
    } else {
        document.getElementById('price-vsat-ha-monthly').textContent = '-';
        document.getElementById('price-vsat-ha').textContent = '-';
    }
    
    // VSAT Service
    const vsatService = document.querySelector('select[name="vsat_service"]').value;
    const vsatServiceMonthlyPrice = getPrice(vsatService);
    const vsatServiceTotalPrice = vsatServiceMonthlyPrice * projectDuration;
    
    document.getElementById('price-vsat-service-monthly').textContent = (vsatService && vsatService !== '') ? formatCurrency(vsatServiceMonthlyPrice) : '-';
    document.getElementById('price-vsat-service').textContent = (vsatService && vsatService !== '') ? formatCurrency(vsatServiceTotalPrice) : '-';
    
    if (vsatService && vsatService !== '') {
        total += vsatServiceTotalPrice;
    }
    
    // Update installation subtotal display
    document.getElementById('installation-subtotal').textContent = formatCurrency(installationTotal);
    
    // Add installation to total
    total += installationTotal;
    
    // Update total - final safety check
    if (isNaN(total) || total === null || total === undefined) {
        console.error('Total calculation resulted in NaN. Resetting to 0.');
        total = 0;
    }
    
    document.getElementById('network-config-total').textContent = formatCurrency(total);
    
    // Debug log
    console.log('💰 Pricing Update:', {
        projectDuration,
        internetAccess,
        dia,
        diaMonthlyPrice,
        diaTotalPrice,
        businessBroadband,
        wan,
        wanMonthlyPrice,
        wanTotalPrice,
        vsat,
        vsatMonthlyPrice,
        vsatTotalPrice,
        installationTotal,
        total
    });
}

// Add hover effects
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('.question-group table tbody tr');
    rows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            if (this.style.background !== 'linear-gradient(90deg, rgba(0, 112, 239, 0.1) 0%, rgba(128, 199, 160, 0.1) 100%)') {
                this.style.background = 'linear-gradient(90deg, rgba(0, 112, 239, 0.05) 0%, rgba(128, 199, 160, 0.05) 100%)';
            }
        });
        row.addEventListener('mouseleave', function() {
            if (this.style.background !== 'linear-gradient(90deg, rgba(0, 112, 239, 0.1) 0%, rgba(128, 199, 160, 0.1) 100%)') {
                const rowId = this.id;
                if (rowId === 'dia-row' || rowId === 'business-broadband-row' || rowId === 'vsat-ha-row') {
                    this.style.background = '#f7f9fc';
                } else if (rowId === 'starlink-row' || rowId === 'vsat-service-row') {
                    this.style.background = '#f0f4f8';
                } else {
                    this.style.background = 'white';
                }
            }
        });
    });
    
    // === PROJECT DURATION CHANGE DETECTION ===
    // Listen for duration changes
    const durationInput = document.querySelector('input[name="project_duration"]');
    
    if (durationInput) {
        console.log('👂 Listening for project duration changes');
        
        // Update on change
        durationInput.addEventListener('change', function() {
            console.log('📅 Project duration changed to:', this.value, 'months');
            updateNetworkConfigPricing();
        });
        
        // Update on input (real-time as user types)
        durationInput.addEventListener('input', function() {
            updateNetworkConfigPricing();
        });
        
        // Initial calculation
        setTimeout(function() {
            updateNetworkConfigPricing();
            console.log('✅ Initial pricing calculated on page load');
        }, 300);
    } else {
        console.warn('⚠️ Project duration input not found!');
    }
});

// Force update when page fully loads
window.addEventListener('load', function() {
    console.log('🔄 Page fully loaded - forcing pricing update...');
    
    setTimeout(function() {
        if (typeof updateNetworkConfigPricing === 'function') {
            updateNetworkConfigPricing();
            console.log('✅ Network pricing updated on window load');
        }
    }, 500);
});
</script>

<style>
.question-group table tbody tr:hover {
    background: linear-gradient(90deg, rgba(0, 112, 239, 0.05) 0%, rgba(128, 199, 160, 0.05) 100%) !important;
}

.question-group table tbody tr:last-child:hover {
    background: linear-gradient(90deg, rgba(0, 112, 239, 0.1) 0%, rgba(128, 199, 160, 0.1) 100%) !important;
}

.question-group select:focus {
    outline: none;
    border-color: #0070ef;
    box-shadow: 0 0 0 3px rgba(0, 112, 239, 0.1);
}
</style>