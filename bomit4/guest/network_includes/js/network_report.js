// network_includes/js/network_report.js
// Network Report Modal Functions with PDF Export

// Currency formatter
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount);
}

// Show Network Report
function showNetworkReport() {
    console.log('📊 Generating Network Report...');
    
    // Gather all data
    gatherProjectInfo();
    gatherNetworkConfig();
    gatherEquipment();
    gatherCables();
    gatherNotes();
    calculateTotals();
    
    // Show modal with animation
    const modal = document.getElementById('network-report-modal');
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    // Trigger reflow for animation
    modal.offsetHeight;
}

// Close Network Report
function closeNetworkReport() {
    const modal = document.getElementById('network-report-modal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close on outside click
document.addEventListener('click', function(e) {
    const modal = document.getElementById('network-report-modal');
    if (e.target === modal) {
        closeNetworkReport();
    }
});

// Close on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('network-report-modal');
        if (modal && modal.style.display === 'block') {
            closeNetworkReport();
        }
    }
});

// Gather Project Information
function gatherProjectInfo() {
    const projectName = document.querySelector('input[name="project_name"]')?.value || 'Not specified';
    const location = document.querySelector('input[name="location"]')?.value || 'Not specified';
    const users = document.querySelector('select[name="user_quantity"]')?.value || 'Not specified';
    const siteType = document.getElementById('final-config-text')?.textContent || 'Not configured';
    
    document.getElementById('report-project-name').textContent = projectName;
    document.getElementById('report-location').textContent = location;
    document.getElementById('report-users').textContent = users;
    document.getElementById('report-site-type').textContent = siteType;
}

// Gather Network Configuration - COMPLETE VERSION with Installation Display
function gatherNetworkConfig() {
    const configContainer = document.getElementById('report-network-config');
    const installationServicesContainer = document.getElementById('report-installation-services');
    const installationListContainer = document.getElementById('installation-services-list');
    
    let configHtml = '<table class="report-table">';
    let installHtml = '<table class="report-table">';
    let total = 0;
    let installationTotal = 0;
    let hasInstallations = false;
    
    // Internet Access
    const internetAccess = document.querySelector('select[name="internet_access"]')?.value;
    if (internetAccess && internetAccess !== 'None') {
        const dia = document.querySelector('select[name="dia"]')?.value;
        const businessBroadband = document.querySelector('select[name="business_broadband"]')?.value;
        const starlinkType = document.querySelector('select[name="starlink_type"]')?.value;
        
        if (internetAccess === 'DIA' && dia) {
            const price = parseFloat(document.getElementById('price-dia')?.textContent.replace(/[$,]/g, '')) || 0;
            configHtml += `<tr><td>Direct Internet Access - ${dia}</td><td>${formatCurrency(price)}</td></tr>`;
            total += price;
            
            // Check installation
            const installCheckbox = document.querySelector(`input[name="install_${dia}"]`);
            if (installCheckbox && installCheckbox.checked) {
                hasInstallations = true;
                const installCost = parseFloat(installCheckbox.getAttribute('data-install-cost')) || 0;
                const label = installCheckbox.parentElement.querySelector('span')?.textContent || '';
                installHtml += `<tr>
                    <td><span style="color: #666;">✓</span> DIA - ${dia}</td>
                    <td style="color: #2e7d32;">${label}</td>
                    <td>${formatCurrency(installCost)}</td>
                </tr>`;
                installationTotal += installCost;
            }
        } else if (internetAccess === 'Business Broadband') {
            if (businessBroadband === 'Starlink' && starlinkType) {
                const price = parseFloat(document.getElementById('price-starlink')?.textContent.replace(/[$,]/g, '')) || 0;
                const displayName = starlinkType.replace('Starlink_', '').replace(/_/g, ' ');
                configHtml += `<tr><td>Business Broadband - Starlink ${displayName}</td><td>${formatCurrency(price)}</td></tr>`;
                total += price;
                
                // Check installation
                const installCheckbox = document.querySelector(`input[name="install_${starlinkType}"]`);
                if (installCheckbox && installCheckbox.checked) {
                    hasInstallations = true;
                    const installCost = parseFloat(installCheckbox.getAttribute('data-install-cost')) || 0;
                    const label = installCheckbox.parentElement.querySelector('span')?.textContent || '';
                    installHtml += `<tr>
                        <td><span style="color: #666;">✓</span> Starlink ${displayName}</td>
                        <td style="color: #2e7d32;">${label}</td>
                        <td>${formatCurrency(installCost)}</td>
                    </tr>`;
                    installationTotal += installCost;
                }
            } else if (businessBroadband && businessBroadband !== 'Starlink') {
                const price = parseFloat(document.getElementById('price-business-broadband')?.textContent.replace(/[$,]/g, '')) || 0;
                configHtml += `<tr><td>Business Broadband - ${businessBroadband}</td><td>${formatCurrency(price)}</td></tr>`;
                total += price;
                
                // Check installation
                const installCheckbox = document.querySelector(`input[name="install_${businessBroadband}"]`);
                if (installCheckbox && installCheckbox.checked) {
                    hasInstallations = true;
                    const installCost = parseFloat(installCheckbox.getAttribute('data-install-cost')) || 0;
                    const label = installCheckbox.parentElement.querySelector('span')?.textContent || '';
                    installHtml += `<tr>
                        <td><span style="color: #666;">✓</span> Business Broadband - ${businessBroadband}</td>
                        <td style="color: #2e7d32;">${label}</td>
                        <td>${formatCurrency(installCost)}</td>
                    </tr>`;
                    installationTotal += installCost;
                }
            }
        }
    }
    
    // WAN Connectivity
    const wan = document.querySelector('select[name="wan_connectivity"]')?.value;
    if (wan && wan !== 'None') {
        const price = parseFloat(document.getElementById('price-wan')?.textContent.replace(/[$,]/g, '')) || 0;
        configHtml += `<tr><td>WAN Connectivity - ${wan}</td><td>${formatCurrency(price)}</td></tr>`;
        total += price;
        
        // Check installation
        const installCheckbox = document.querySelector(`input[name="install_${wan}"]`);
        if (installCheckbox && installCheckbox.checked) {
            hasInstallations = true;
            const installCost = parseFloat(installCheckbox.getAttribute('data-install-cost')) || 0;
            const label = installCheckbox.parentElement.querySelector('span')?.textContent || '';
            installHtml += `<tr>
                <td><span style="color: #666;">✓</span> WAN - ${wan}</td>
                <td style="color: #2e7d32;">${label}</td>
                <td>${formatCurrency(installCost)}</td>
            </tr>`;
            installationTotal += installCost;
        }
    }
    
    // VSAT
    const vsat = document.querySelector('select[name="vsat"]')?.value;
    if (vsat && vsat !== 'None') {
        const price = parseFloat(document.getElementById('price-vsat')?.textContent.replace(/[$,]/g, '')) || 0;
        configHtml += `<tr><td>VSAT - ${vsat}</td><td>${formatCurrency(price)}</td></tr>`;
        total += price;
    }
    
    // VSAT HA (High Availability Required)
    const vsatHa = document.querySelector('select[name="vsat_ha"]')?.value;
    console.log('🔍 VSAT HA Value:', vsatHa);
    if (vsatHa && vsatHa !== '' && vsatHa !== 'None' && vsatHa === 'Yes') {
        const priceElement = document.getElementById('price-vsat-ha');
        console.log('💰 VSAT HA Price Element:', priceElement);
        const priceText = priceElement?.textContent || '0';
        console.log('💵 VSAT HA Price Text:', priceText);
        const price = parseFloat(priceText.replace(/[$,]/g, '')) || 0;
        console.log('✅ VSAT HA Price Parsed:', price);
        if (price > 0) {
            configHtml += `<tr><td>High Availability Required</td><td>${formatCurrency(price)}</td></tr>`;
            total += price;
        }
    }
    
    // VSAT Service (Intended Service Usage)
    const vsatService = document.querySelector('select[name="vsat_service"]')?.value;
    console.log('🔍 VSAT Service Value:', vsatService);
    if (vsatService && vsatService !== '' && vsatService !== 'None') {
        const priceElement = document.getElementById('price-vsat-service');
        console.log('💰 VSAT Service Price Element:', priceElement);
        const priceText = priceElement?.textContent || '0';
        console.log('💵 VSAT Service Price Text:', priceText);
        const price = parseFloat(priceText.replace(/[$,]/g, '')) || 0;
        console.log('✅ VSAT Service Price Parsed:', price);
        if (price > 0) {
            configHtml += `<tr><td>Intended Service Usage - ${vsatService}</td><td>${formatCurrency(price)}</td></tr>`;
            total += price;
        }
    }
    
    if (total === 0) {
        configHtml += '<tr><td colspan="2"><div class="empty-state">No network configuration selected</div></td></tr>';
    }
    
    configHtml += '</table>';
    configContainer.innerHTML = configHtml;
    
    // Display installation services if any
    if (hasInstallations) {
        installHtml += `<tr style="background: #f1f8f4; font-weight: 600;">
            <td colspan="2" style="text-align: right; font-weight: 700; color: #2e7d32; padding: 15px;">Installation Services Subtotal:</td>
            <td style="font-weight: 700; font-size: 1.1rem; color: #2e7d32; padding: 15px;">${formatCurrency(installationTotal)}</td>
        </tr>`;
        installHtml += '</table>';
        installationListContainer.innerHTML = installHtml;
        installationServicesContainer.style.display = 'block';
    } else {
        installationServicesContainer.style.display = 'none';
    }
    
    // Grand total includes installation
    const grandTotal = total + installationTotal;
    document.getElementById('report-network-total').textContent = formatCurrency(grandTotal);
    
    console.log('📊 Network Config:', {
        monthlyTotal: total,
        installationTotal: installationTotal,
        grandTotal: grandTotal
    });
    
    return grandTotal;
}

function gatherEquipment() {
    const equipmentContainer = document.getElementById('report-equipment');
    let html = '<table class="report-table">';
    let total = 0;
    let hasItems = false;
    
    // Find visible equipment section
    const visibleSection = document.querySelector('.site-specific-fields:not(.hidden)');
    if (visibleSection) {
        const equipmentRows = visibleSection.querySelectorAll('input[type="number"]');
        equipmentRows.forEach(input => {
            const qty = parseFloat(input.value) || 0;
            if (qty > 0) {
                hasItems = true;
                const price = parseFloat(input.getAttribute('data-price')) || 0;
                const rowTotal = qty * price;
                const row = input.closest('tr');
                const fullText = row.querySelector('td:first-child').textContent.trim();
                
                // 🆕 SPLIT: Extract item name and description
                let itemName = fullText;
                let itemDescription = '';
                
                // Check if there's a description in parentheses
                const descMatch = fullText.match(/^(.*?)\s*\((.*?)\)$/);
                if (descMatch) {
                    itemName = descMatch[1].trim();
                    itemDescription = descMatch[2].trim();
                }
                
                // 🆕 3-COLUMN FORMAT: Name | Description | Cost
                html += `<tr>
                    <td>${itemName}</td>
                    <td style="color: #666; font-size: 0.9rem;">${itemDescription} <span style="color: #999;">(Qty: ${qty})</span></td>
                    <td>${formatCurrency(rowTotal)}</td>
                </tr>`;
                total += rowTotal;
            }
        });
    }
    
    if (!hasItems) {
        html += '<tr><td colspan="3"><div class="empty-state">No equipment selected</div></td></tr>';
    }
    
    html += '</table>';
    equipmentContainer.innerHTML = html;
    document.getElementById('report-equipment-total').textContent = formatCurrency(total);
    
    return total;
}

// Gather Cables & Accessories
function gatherCables() {
    const cablesContainer = document.getElementById('report-cables');
    let html = '<table class="report-table">';
    let total = 0;
    let hasItems = false;
    
    // Get accessories
    const accessoryRows = document.querySelectorAll('#general-accessories-list tr');
    accessoryRows.forEach(row => {
        const qtyInput = row.querySelector('input[name="generalAccessoryQty[]"]');
        const itemInput = row.querySelector('input[name="generalAccessoryItem[]"]');
        
        if (qtyInput && itemInput) {
            const qty = parseFloat(qtyInput.value) || 0;
            if (qty > 0) {
                hasItems = true;
                const price = parseFloat(itemInput.getAttribute('data-price')) || 0;
                const rowTotal = qty * price;
                const itemName = itemInput.value;
                
                html += `<tr><td>${itemName} <span style="color: #666; font-size: 0.9rem;">(Qty: ${qty})</span></td><td>${formatCurrency(rowTotal)}</td></tr>`;
                total += rowTotal;
            }
        }
    });
    
    // Get cables
    const cableRows = document.querySelectorAll('#detailed-cable-list tr');
    cableRows.forEach(row => {
        const qtyInput = row.querySelector('input[name="cableQty[]"]');
        const itemInput = row.querySelector('input[name="cableItem[]"]');
        
        if (qtyInput && itemInput) {
            const qty = parseFloat(qtyInput.value) || 0;
            if (qty > 0) {
                hasItems = true;
                const price = parseFloat(itemInput.getAttribute('data-price')) || 0;
                const rowTotal = qty * price;
                const itemName = itemInput.value;
                
                html += `<tr><td>${itemName} <span style="color: #666; font-size: 0.9rem;">(Qty: ${qty})</span></td><td>${formatCurrency(rowTotal)}</td></tr>`;
                total += rowTotal;
            }
        }
    });
    
    if (!hasItems) {
        html += '<tr><td colspan="2"><div class="empty-state">No cables or accessories selected</div></td></tr>';
    }
    
    html += '</table>';
    cablesContainer.innerHTML = html;
    document.getElementById('report-cables-total').textContent = formatCurrency(total);
    
    return total;
}

// Gather Additional Notes
function gatherNotes() {
    const notes = document.querySelector('textarea[name="additional_notes"]')?.value || 'No additional notes provided.';
    document.getElementById('report-notes').textContent = notes;
}

// Calculate All Totals
function calculateTotals() {
    // Get subtotals
    const networkTotal = parseFloat(document.getElementById('report-network-total').textContent.replace(/[$,]/g, '')) || 0;
    const equipmentTotal = parseFloat(document.getElementById('report-equipment-total').textContent.replace(/[$,]/g, '')) || 0;
    const cablesTotal = parseFloat(document.getElementById('report-cables-total').textContent.replace(/[$,]/g, '')) || 0;
    
    const subtotal = networkTotal + equipmentTotal + cablesTotal;
    
    // ✅ CORRECTED: Installation (5%) only applies to Equipment & Cables
    // Network Infrastructure already has individual installation checkboxes
    const installationBase = equipmentTotal + cablesTotal;
    const installation = installationBase * 0.05;
    
    // Project Management and Contingency apply to full subtotal
    const projectMgmt = subtotal * 0.10;
    const contingency = subtotal * 0.15;
    
    const grandTotal = subtotal + installation + projectMgmt + contingency;
    
    console.log('📊 Cost Breakdown:', {
        network: networkTotal,
        equipment: equipmentTotal,
        cables: cablesTotal,
        subtotal: subtotal,
        installationBase: installationBase,
        installation: installation,
        projectMgmt: projectMgmt,
        contingency: contingency,
        grandTotal: grandTotal
    });
    
    // Update summary table
    animateValue('summary-network', 0, networkTotal, 800);
    animateValue('summary-equipment', 0, equipmentTotal, 800);
    animateValue('summary-cables', 0, cablesTotal, 800);
    animateValue('summary-subtotal', 0, subtotal, 1000);
    animateValue('summary-installation', 0, installation, 1000);
    animateValue('summary-pm', 0, projectMgmt, 1000);
    animateValue('summary-contingency', 0, contingency, 1000);
    animateValue('summary-grand-total', 0, grandTotal, 1200);
}

// Animate number counting
function animateValue(elementId, start, end, duration) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const range = end - start;
    const increment = range / (duration / 16);
    let current = start;
    
    const timer = setInterval(() => {
        current += increment;
        if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
            current = end;
            clearInterval(timer);
        }
        element.textContent = formatCurrency(current);
    }, 16);
}

// Print Report
function printNetworkReport() {
    window.print();
}

// ============================================
// 📄 PDF EXPORT FUNCTION
// ============================================
function exportNetworkPDF(includePrices = true) {
    console.log('📄 Exporting Network Infrastructure Report to PDF...');
    console.log('💰 Include Prices:', includePrices);
    
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();
    
    // Load logo
    const img = new Image();
    img.src = "network_includes/js/technip_logo.png";
    
    img.onload = function() {
        const margin = 15;
        const boxWidth = 180;
        const boxX = (pageWidth - boxWidth) / 2;
        const boxY = margin;

        // Helper: draw the purple-tinted background box on any page
        function drawPageBackground() {
            doc.setFillColor(245, 245, 255);
            doc.roundedRect(boxX, boxY, boxWidth, pageHeight - 2 * margin, 5, 5, "F");
        }

        // Draw background on page 1
        drawPageBackground();
        
        // Add logo
        const maxLogoSize = 40;
        let logoWidth = img.width;
        let logoHeight = img.height;
        const ratio = Math.min(maxLogoSize / logoWidth, maxLogoSize / logoHeight);
        logoWidth *= ratio;
        logoHeight *= ratio;
        const logoX = boxX + 5;
        const logoY = boxY + 5;
        doc.addImage(img, "PNG", logoX, logoY, logoWidth, logoHeight);
        
        // Add title
        doc.setFont("helvetica", "bold");
        doc.setFontSize(17);
        const title = "Network Infrastructure Report";
        const titleY = logoY + logoHeight / 2 + 2;
        doc.text(title, boxX + boxWidth / 2, titleY, { align: "center" });
        
        let y = boxY + logoHeight + 15;
        
        // Project Information Table
        const projectName = document.getElementById('report-project-name').textContent;
        const location = document.getElementById('report-location').textContent;
        const users = document.getElementById('report-users').textContent;
        const siteType = document.getElementById('report-site-type').textContent;
        
        doc.autoTable({
            startY: y,
            head: [['Project Name', 'Location', 'Users', 'Site Type']],
            body: [[projectName, location, users, siteType]],
            styles: { font: "helvetica", halign: 'center', valign: 'middle', fontSize: 9 },
            headStyles: { fillColor: [0, 112, 239], textColor: 255 },
            theme: 'grid',
            margin: { left: boxX + 5, right: pageWidth - boxX - boxWidth + 5 },
            didAddPage: () => { drawPageBackground(); }
        });
        
        y = doc.lastAutoTable.finalY + 10;
        
        // Network Configuration Table
        doc.setFont("helvetica", "bold");
        doc.setFontSize(12);
        doc.text("Network Configuration", boxX + 5, y);
        y += 5;
        
        const networkConfigData = extractTableData('report-network-config', includePrices);
        if (networkConfigData.length > 0) {
            const headers = includePrices ? [['Configuration Item', 'Cost']] : [['Configuration Item']];
            doc.autoTable({
                startY: y,
                head: headers,
                body: networkConfigData,
                styles: { font: "helvetica", fontSize: 9 },
                headStyles: { fillColor: [35, 57, 93], textColor: 255 },
                margin: { left: boxX + 5, right: pageWidth - boxX - boxWidth + 5 },
                didAddPage: () => { drawPageBackground(); }
            });
            y = doc.lastAutoTable.finalY + 5;
        }
        
        // Installation Services (if any) - only when prices included
        if (includePrices) {
            const installationSection = document.getElementById('report-installation-services');
            const isVisible = installationSection && installationSection.style.display !== 'none';

            if (isVisible) {
                // Section label
                doc.setFont("helvetica", "bolditalic");
                doc.setFontSize(10);
                doc.setTextColor(46, 125, 50); // green
                doc.text("📦 Installation Services Included", boxX + 5, y);
                doc.setTextColor(0, 0, 0); // reset
                y += 5;

                // Extract installation rows from the modal table
                const installRows = [];
                const installTableRows = document.querySelectorAll('#installation-services-list .report-table tr');
                installTableRows.forEach(row => {
                    const cells = row.querySelectorAll('td');
                    if (cells.length === 3) {
                        const name = cells[0].textContent.trim();
                        const label = cells[1].textContent.trim();
                        const cost = cells[2].textContent.trim();
                        // Skip the subtotal summary row
                        if (!name.toLowerCase().includes('subtotal')) {
                            installRows.push([name, label, cost]);
                        }
                    }
                });

                // Extract installation subtotal
                const installSubtotalRow = document.querySelector('#installation-services-list .report-table tr:last-child');
                let installSubtotalText = '';
                if (installSubtotalRow) {
                    const lastCells = installSubtotalRow.querySelectorAll('td');
                    if (lastCells.length === 3) {
                        installSubtotalText = lastCells[2].textContent.trim();
                    }
                }

                if (installRows.length > 0) {
                    doc.autoTable({
                        startY: y,
                        head: [['Service', 'Type', 'Cost']],
                        body: installRows,
                        styles: { font: "helvetica", fontSize: 9 },
                        headStyles: { fillColor: [76, 175, 80], textColor: 255 },
                        margin: { left: boxX + 5, right: pageWidth - boxX - boxWidth + 5 },
                        didAddPage: () => { drawPageBackground(); }
                    });
                    y = doc.lastAutoTable.finalY + 3;

                    // Installation subtotal line
                    if (installSubtotalText) {
                        doc.setFont("helvetica", "bold");
                        doc.setFontSize(9);
                        doc.setTextColor(46, 125, 50);
                        doc.text(`Installation Services Subtotal: ${installSubtotalText}`, boxX + boxWidth - 5, y, { align: 'right' });
                        doc.setTextColor(0, 0, 0);
                        y += 8;
                    }
                }
            }
        }

        // Network Subtotal (only if prices included)
        if (includePrices) {
            const networkTotal = document.getElementById('report-network-total').textContent;
            doc.setFont("helvetica", "bold");
            doc.setFontSize(10);
            doc.text(`Network Subtotal: ${networkTotal}`, boxX + boxWidth - 5, y, { align: 'right' });
            y += 10;
        } else {
            y += 5;
        }
        
        // Equipment & Modules Table
        doc.setFont("helvetica", "bold");
        doc.setFontSize(12);
        doc.text("Equipment & Modules", boxX + 5, y);
        y += 5;
        
        const equipmentData = extractEquipmentTableData(includePrices);
        if (equipmentData.length > 0) {
            const headers = includePrices 
                ? [['Item Name', 'Description/Specifications', 'Cost']] 
                : [['Item Name', 'Description/Specifications']];
            
            // Dynamic column widths
            let columnStyles;
            if (includePrices) {
                columnStyles = {
                    0: { halign: 'left', cellWidth: 35 },
                    1: { halign: 'left', cellWidth: 110 },
                    2: { halign: 'left', cellWidth: 25 }
                };
            } else {
                columnStyles = {
                    0: { halign: 'left', cellWidth: 55 },
                    1: { halign: 'left', cellWidth: 115 }
                };
            }
            
            doc.autoTable({
                startY: y,
                head: headers,
                body: equipmentData,
                styles: { font: "helvetica", fontSize: 9 },
                headStyles: { fillColor: [35, 57, 93], textColor: 255 },
                columnStyles: columnStyles,
                margin: { left: boxX + 5, right: pageWidth - boxX - boxWidth + 5 },
                tableWidth: 'auto',
                didAddPage: () => { drawPageBackground(); }
            });
            y = doc.lastAutoTable.finalY + 5;
        }
        
        // Equipment Subtotal (only if prices included)
        if (includePrices) {
            const equipmentTotal = document.getElementById('report-equipment-total').textContent;
            doc.setFont("helvetica", "bold");
            doc.setFontSize(10);
            doc.text(`Equipment Subtotal: ${equipmentTotal}`, boxX + boxWidth - 5, y, { align: 'right' });
            y += 10;
        } else {
            y += 5;
        }
        
        // ─── Cables & Accessories ───────────────────────────────────────────
        // Estimate how many rows the cables table has (header + each data row ≈ 8px)
        const cablesData = extractTableData('report-cables', includePrices);
        const cablesRowHeight = 8;           // approx px per row in autoTable
        const cablesHeaderHeight = 10;
        const cablesTitleHeight = 10;        // section heading + gap
        const cablesSubtotalHeight = includePrices ? 14 : 8;
        const estimatedCablesHeight = cablesTitleHeight + cablesHeaderHeight + (cablesData.length * cablesRowHeight) + cablesSubtotalHeight;

        // If cables section won't fully fit on the current page → start a new page
        const spaceLeft = pageHeight - y - 20;  // 20px bottom margin
        if (estimatedCablesHeight > spaceLeft) {
            doc.addPage();
            drawPageBackground();
            y = 20;
        }

        // Cables & Accessories Table
        doc.setFont("helvetica", "bold");
        doc.setFontSize(12);
        doc.text("Cables & Accessories", boxX + 5, y);
        y += 5;

        if (cablesData.length > 0) {
            const cableHeaders = includePrices ? [['Item', 'Cost']] : [['Item']];
            doc.autoTable({
                startY: y,
                head: cableHeaders,
                body: cablesData,
                styles: { font: "helvetica", fontSize: 9 },
                headStyles: { fillColor: [35, 57, 93], textColor: 255 },
                margin: { left: boxX + 5, right: pageWidth - boxX - boxWidth + 5 },
                pageBreak: 'avoid',
                didAddPage: () => { drawPageBackground(); }
            });
            y = doc.lastAutoTable.finalY + 5;
        }

        // Cables Subtotal (only if prices included)
        if (includePrices) {
            const cablesTotal = document.getElementById('report-cables-total').textContent;
            doc.setFont("helvetica", "bold");
            doc.setFontSize(10);
            doc.text(`Cables Subtotal: ${cablesTotal}`, boxX + boxWidth - 5, y, { align: 'right' });
            y += 10;
        } else {
            y += 5;
        }

        // ─── Cost Summary ────────────────────────────────────────────────────
        // Estimate height of the Cost Summary table (8 rows + heading)
        const summaryRowHeight = 10;
        const summaryRows = 8;
        const summaryTitleHeight = 16;
        const estimatedSummaryHeight = summaryTitleHeight + (summaryRows * summaryRowHeight) + 10;

        // Only add a new page if Cost Summary won't fit; otherwise keep it on the same page
        const spaceAfterCables = pageHeight - y - 20;
        if (includePrices && estimatedSummaryHeight > spaceAfterCables) {
            doc.addPage();
            drawPageBackground();
            y = 20;
        }

        // Cost Summary (only if prices included)
        if (includePrices) {
            doc.setFont("helvetica", "bold");
            doc.setFontSize(14);
            doc.text("Cost Summary", pageWidth / 2, y, { align: "center" });
            y += 10;
            
            const summaryNetwork = document.getElementById('summary-network').textContent;
            const summaryEquipment = document.getElementById('summary-equipment').textContent;
            const summaryCables = document.getElementById('summary-cables').textContent;
            const summarySubtotal = document.getElementById('summary-subtotal').textContent;
            const summaryInstallation = document.getElementById('summary-installation').textContent;
            const summaryPM = document.getElementById('summary-pm').textContent;
            const summaryContingency = document.getElementById('summary-contingency').textContent;
            const summaryGrandTotal = document.getElementById('summary-grand-total').textContent;
            
            doc.autoTable({
                startY: y,
                body: [
                    ['Network Infrastructure Configuration', summaryNetwork],
                    ['Equipment & Modules', summaryEquipment],
                    ['Cables & Accessories', summaryCables],
                    ['Subtotal (Hardware & Configuration)', summarySubtotal],
                    ['Installation Service (5% on Equipment & Cables)', summaryInstallation],
                    ['Project Management (10%)', summaryPM],
                    ['Contingency Buffer (15%)', summaryContingency],
                    ['GRAND TOTAL', summaryGrandTotal]
                ],
                styles: { font: "helvetica", fontSize: 10, halign: 'left' },
                columnStyles: {
                    0: { fontStyle: 'bold', cellWidth: 130 },
                    1: { halign: 'right', fontStyle: 'bold', textColor: [0, 112, 239] }
                },
                theme: 'grid',
                margin: { left: 20, right: 20 },
                didAddPage: () => { drawPageBackground(); }
            });
            
            y = doc.lastAutoTable.finalY + 10;
        }

        // Notes section — y is already correct (set after summary table above)
        // If no prices were included, ensure y is at a reasonable start position
        if (!includePrices && y < 30) {
            y = 30;
        }
        
        const notes = document.getElementById('report-notes').textContent;
        if (notes && notes !== 'No additional notes provided.') {
            doc.setFont("helvetica", "bold");
            doc.setFontSize(12);
            doc.text("Additional Notes:", 20, y);
            y += 7;
            doc.setFont("helvetica", "normal");
            doc.setFontSize(10);
            const splitNotes = doc.splitTextToSize(notes, pageWidth - 40);
            doc.text(splitNotes, 20, y);
        }
        
        // Save PDF
        const pricesSuffix = includePrices ? '_With_Prices' : '_Without_Prices';
        const fileName = `Network_Infrastructure_Report_${projectName.replace(/\s+/g, '_')}${pricesSuffix}.pdf`;
        doc.save(fileName);
        console.log('✅ PDF exported successfully:', fileName);
    };
    
    img.onerror = function() {
        console.error('❌ Failed to load logo image');
        alert('Failed to load logo. PDF export cancelled.');
    };
}
// 🆕 Special function for equipment table with 3 columns
function extractEquipmentTableData(includePrices = true) {
    const container = document.getElementById('report-equipment');
    const rows = container.querySelectorAll('.report-table tr');
    const data = [];
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length === 3) {  // 3 columns now
            const itemName = cells[0].textContent.trim();
            const description = cells[1].textContent.trim();
            const cost = cells[2].textContent.trim();
            
            // Skip empty state rows
            if (!itemName.includes('No ') && !itemName.includes('empty-state')) {
                if (includePrices) {
                    data.push([itemName, description, cost]);
                } else {
                    data.push([itemName, description]); // No price
                }
            }
        }
    });
    
    return data;
}
// Helper function to extract table data from report sections
function extractTableData(containerId, includePrices = true) {
    const container = document.getElementById(containerId);
    const rows = container.querySelectorAll('.report-table tr');
    const data = [];
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length === 2) {
            const item = cells[0].textContent.trim();
            const cost = cells[1].textContent.trim();
            
            // Skip empty state rows
            if (!item.includes('No ') && !item.includes('empty-state')) {
                if (includePrices) {
                    data.push([item, cost]);
                } else {
                    data.push([item]); // Only item name, no price
                }
            }
        }
    });
    
    return data;
}

// Initialize on load
document.addEventListener('DOMContentLoaded', function() {
    console.log('📊 Network Report module loaded with PDF export capability');
});