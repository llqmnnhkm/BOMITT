// network_includes/js/network_report_core.js
// ============================================================
// CORE: Modal control, data gathering, shared helper functions
// Shared by both PDF export variants (with & without prices)
// ============================================================

// ── Currency formatter ────────────────────────────────────────
function formatCurrency(amount) {
    // If currency.js has loaded and set window.BOMIT_CURRENCY, use it
    // Otherwise fallback to plain USD formatting
    if (window.BOMIT_CURRENCY && window.BOMIT_CURRENCY.loaded) {
        const cur    = window.BOMIT_CURRENCY.current;
        const symbol = window.BOMIT_CURRENCY.symbols[cur] || 'RM';
        const rate   = window.BOMIT_CURRENCY.rates[cur]   || 1;
        const converted = (parseFloat(amount) || 0) * rate;
        return symbol + converted.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
    // Fallback: show as MYR
    return 'RM' + (parseFloat(amount) || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// ── Show / Close Modal ────────────────────────────────────────
function showNetworkReport() {
    console.log('📊 Generating Network Report...');

    gatherProjectInfo();
    gatherNetworkConfig();
    gatherEquipment();
    gatherCables();
    gatherNotes();
    calculateTotals();

    const modal = document.getElementById('network-report-modal');
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    modal.offsetHeight; // trigger reflow for animation
}

function closeNetworkReport() {
    const modal = document.getElementById('network-report-modal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close on outside click
document.addEventListener('click', function (e) {
    const modal = document.getElementById('network-report-modal');
    if (e.target === modal) closeNetworkReport();
});

// Close on ESC
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('network-report-modal');
        if (modal && modal.style.display === 'block') closeNetworkReport();
    }
});

// ── Gather: Project Info ──────────────────────────────────────
function gatherProjectInfo() {
    const projectName   = document.querySelector('input[name="project_name"]')?.value  || 'Not specified';
    const location      = document.querySelector('input[name="location"]')?.value       || 'Not specified';
    const users         = document.querySelector('select[name="user_quantity"]')?.value || 'Not specified';
    const siteType      = document.getElementById('final-config-text')?.textContent     || 'Not configured';

    document.getElementById('report-project-name').textContent = projectName;
    document.getElementById('report-location').textContent     = location;
    document.getElementById('report-users').textContent        = users;
    document.getElementById('report-site-type').textContent    = siteType;
}

// ── Gather: Network Configuration (with installation services) ─
function gatherNetworkConfig() {
    const configContainer           = document.getElementById('report-network-config');
    const installationServicesContainer = document.getElementById('report-installation-services');
    const installationListContainer = document.getElementById('installation-services-list');

    let configHtml        = '<table class="report-table">';
    let installHtml       = '<table class="report-table">';
    let total             = 0;
    let installationTotal = 0;
    let hasInstallations  = false;

    // Internet Access
    const internetAccess = document.querySelector('select[name="internet_access"]')?.value;
    if (internetAccess && internetAccess !== 'None') {
        const dia              = document.querySelector('select[name="dia"]')?.value;
        const businessBroadband = document.querySelector('select[name="business_broadband"]')?.value;
        const starlinkType     = document.querySelector('select[name="starlink_type"]')?.value;

        if (internetAccess === 'DIA' && dia) {
            const price = parseFloat(document.getElementById('price-dia')?.textContent.replace(/[^0-9.-]/g, '')) || 0;
            configHtml += `<tr><td>Direct Internet Access - ${dia}</td><td>${formatCurrency(price)}</td></tr>`;
            total += price;

            const installCheckbox = document.querySelector(`input[name="install_${dia}"]`);
            if (installCheckbox && installCheckbox.checked) {
                hasInstallations = true;
                const installCost = parseFloat(installCheckbox.getAttribute('data-install-cost')) || 0;
                const label = installCheckbox.parentElement.querySelector('span')?.textContent || '';
                installHtml += `<tr>
                    <td><span style="color:#666;">✓</span> DIA - ${dia}</td>
                    <td style="color:#2e7d32;">${label}</td>
                    <td>${formatCurrency(installCost)}</td>
                </tr>`;
                installationTotal += installCost;
            }

        } else if (internetAccess === 'Business Broadband') {
            if (businessBroadband === 'Starlink' && starlinkType) {
                const price = parseFloat(document.getElementById('price-starlink')?.textContent.replace(/[^0-9.-]/g, '')) || 0;
                const displayName = starlinkType.replace('Starlink_', '').replace(/_/g, ' ');
                configHtml += `<tr><td>Business Broadband - Starlink ${displayName}</td><td>${formatCurrency(price)}</td></tr>`;
                total += price;

                const installCheckbox = document.querySelector(`input[name="install_${starlinkType}"]`);
                if (installCheckbox && installCheckbox.checked) {
                    hasInstallations = true;
                    const installCost = parseFloat(installCheckbox.getAttribute('data-install-cost')) || 0;
                    const label = installCheckbox.parentElement.querySelector('span')?.textContent || '';
                    installHtml += `<tr>
                        <td><span style="color:#666;">✓</span> Starlink ${displayName}</td>
                        <td style="color:#2e7d32;">${label}</td>
                        <td>${formatCurrency(installCost)}</td>
                    </tr>`;
                    installationTotal += installCost;
                }

            } else if (businessBroadband && businessBroadband !== 'Starlink') {
                const price = parseFloat(document.getElementById('price-business-broadband')?.textContent.replace(/[^0-9.-]/g, '')) || 0;
                configHtml += `<tr><td>Business Broadband - ${businessBroadband}</td><td>${formatCurrency(price)}</td></tr>`;
                total += price;

                const installCheckbox = document.querySelector(`input[name="install_${businessBroadband}"]`);
                if (installCheckbox && installCheckbox.checked) {
                    hasInstallations = true;
                    const installCost = parseFloat(installCheckbox.getAttribute('data-install-cost')) || 0;
                    const label = installCheckbox.parentElement.querySelector('span')?.textContent || '';
                    installHtml += `<tr>
                        <td><span style="color:#666;">✓</span> Business Broadband - ${businessBroadband}</td>
                        <td style="color:#2e7d32;">${label}</td>
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
        const price = parseFloat(document.getElementById('price-wan')?.textContent.replace(/[^0-9.-]/g, '')) || 0;
        configHtml += `<tr><td>WAN Connectivity - ${wan}</td><td>${formatCurrency(price)}</td></tr>`;
        total += price;

        const installCheckbox = document.querySelector(`input[name="install_${wan}"]`);
        if (installCheckbox && installCheckbox.checked) {
            hasInstallations = true;
            const installCost = parseFloat(installCheckbox.getAttribute('data-install-cost')) || 0;
            const label = installCheckbox.parentElement.querySelector('span')?.textContent || '';
            installHtml += `<tr>
                <td><span style="color:#666;">✓</span> WAN - ${wan}</td>
                <td style="color:#2e7d32;">${label}</td>
                <td>${formatCurrency(installCost)}</td>
            </tr>`;
            installationTotal += installCost;
        }
    }

    // VSAT
    const vsat = document.querySelector('select[name="vsat"]')?.value;
    if (vsat && vsat !== 'None') {
        const price = parseFloat(document.getElementById('price-vsat')?.textContent.replace(/[^0-9.-]/g, '')) || 0;
        configHtml += `<tr><td>VSAT - ${vsat}</td><td>${formatCurrency(price)}</td></tr>`;
        total += price;
    }

    // VSAT HA
    const vsatHa = document.querySelector('select[name="vsat_ha"]')?.value;
    console.log('🔍 VSAT HA Value:', vsatHa);
    if (vsatHa && vsatHa !== '' && vsatHa !== 'None' && vsatHa === 'Yes') {
        const price = parseFloat(document.getElementById('price-vsat-ha')?.textContent.replace(/[^0-9.-]/g, '')) || 0;
        console.log('✅ VSAT HA Price Parsed:', price);
        if (price > 0) {
            configHtml += `<tr><td>High Availability Required</td><td>${formatCurrency(price)}</td></tr>`;
            total += price;
        }
    }

    // VSAT Service
    const vsatService = document.querySelector('select[name="vsat_service"]')?.value;
    console.log('🔍 VSAT Service Value:', vsatService);
    if (vsatService && vsatService !== '' && vsatService !== 'None') {
        const price = parseFloat(document.getElementById('price-vsat-service')?.textContent.replace(/[^0-9.-]/g, '')) || 0;
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

    // Installation services display
    if (hasInstallations) {
        installHtml += `<tr style="background:#f1f8f4;font-weight:600;">
            <td colspan="2" style="text-align:right;font-weight:700;color:#2e7d32;padding:15px;">Installation Services Subtotal:</td>
            <td style="font-weight:700;font-size:1.1rem;color:#2e7d32;padding:15px;">${formatCurrency(installationTotal)}</td>
        </tr>`;
        installHtml += '</table>';
        installationListContainer.innerHTML = installHtml;
        installationServicesContainer.style.display = 'block';
    } else {
        installationServicesContainer.style.display = 'none';
    }

    const grandTotal = total + installationTotal;
    document.getElementById('report-network-total').textContent = formatCurrency(grandTotal);

    console.log('📊 Network Config:', { total, installationTotal, grandTotal });
    return grandTotal;
}

// ── Gather: Equipment & Modules ───────────────────────────────
function gatherEquipment() {
    const equipmentContainer = document.getElementById('report-equipment');
    let html     = '<table class="report-table">';
    let total    = 0;
    let hasItems = false;

    const visibleSection = document.querySelector('.site-specific-fields:not(.hidden)');
    if (visibleSection) {
        visibleSection.querySelectorAll('input[type="number"]').forEach(input => {
            const qty = parseFloat(input.value) || 0;
            if (qty > 0) {
                hasItems = true;
                const price    = parseFloat(input.getAttribute('data-price')) || 0;
                const rowTotal = qty * price;
                const row      = input.closest('tr');
                const fullText = row.querySelector('td:first-child').textContent.trim();

                let itemName        = fullText;
                let itemDescription = '';
                const descMatch = fullText.match(/^(.*?)\s*\((.*?)\)$/);
                if (descMatch) {
                    itemName        = descMatch[1].trim();
                    itemDescription = descMatch[2].trim();
                }

                html += `<tr>
                    <td>${itemName}</td>
                    <td style="color:#666;font-size:0.9rem;">${itemDescription} <span style="color:#999;">(Qty: ${qty})</span></td>
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

// ── Gather: Cables & Accessories ─────────────────────────────
function gatherCables() {
    const cablesContainer = document.getElementById('report-cables');
    let html     = '<table class="report-table">';
    let total    = 0;
    let hasItems = false;

    document.querySelectorAll('#general-accessories-list tr').forEach(row => {
        const qtyInput  = row.querySelector('input[name="generalAccessoryQty[]"]');
        const itemInput = row.querySelector('input[name="generalAccessoryItem[]"]');
        if (qtyInput && itemInput) {
            const qty = parseFloat(qtyInput.value) || 0;
            if (qty > 0) {
                hasItems = true;
                const price    = parseFloat(itemInput.getAttribute('data-price')) || 0;
                const rowTotal = qty * price;
                html += `<tr><td>${itemInput.value} <span style="color:#666;font-size:0.9rem;">(Qty: ${qty})</span></td><td>${formatCurrency(rowTotal)}</td></tr>`;
                total += rowTotal;
            }
        }
    });

    document.querySelectorAll('#detailed-cable-list tr').forEach(row => {
        const qtyInput  = row.querySelector('input[name="cableQty[]"]');
        const itemInput = row.querySelector('input[name="cableItem[]"]');
        if (qtyInput && itemInput) {
            const qty = parseFloat(qtyInput.value) || 0;
            if (qty > 0) {
                hasItems = true;
                const price    = parseFloat(itemInput.getAttribute('data-price')) || 0;
                const rowTotal = qty * price;
                html += `<tr><td>${itemInput.value} <span style="color:#666;font-size:0.9rem;">(Qty: ${qty})</span></td><td>${formatCurrency(rowTotal)}</td></tr>`;
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

// ── Gather: Notes ─────────────────────────────────────────────
function gatherNotes() {
    const notes = document.querySelector('textarea[name="additional_notes"]')?.value || 'No additional notes provided.';
    document.getElementById('report-notes').textContent = notes;
}

// ── Calculate Totals ──────────────────────────────────────────
function calculateTotals() {
    const networkTotal   = parseFloat(document.getElementById('report-network-total').textContent.replace(/[^0-9.-]/g, ''))   || 0;
    const equipmentTotal = parseFloat(document.getElementById('report-equipment-total').textContent.replace(/[^0-9.-]/g, '')) || 0;
    const cablesTotal    = parseFloat(document.getElementById('report-cables-total').textContent.replace(/[^0-9.-]/g, ''))    || 0;

    const subtotal        = networkTotal + equipmentTotal + cablesTotal;
    const installationBase = equipmentTotal + cablesTotal;
    const installation    = installationBase * 0.05;
    const projectMgmt     = subtotal * 0.10;
    const contingency     = subtotal * 0.15;
    const grandTotal      = subtotal + installation + projectMgmt + contingency;

    console.log('📊 Cost Breakdown:', { networkTotal, equipmentTotal, cablesTotal, subtotal, installation, projectMgmt, contingency, grandTotal });

    animateValue('summary-network',      0, networkTotal,   800);
    animateValue('summary-equipment',    0, equipmentTotal, 800);
    animateValue('summary-cables',       0, cablesTotal,    800);
    animateValue('summary-subtotal',     0, subtotal,       1000);
    animateValue('summary-installation', 0, installation,   1000);
    animateValue('summary-pm',           0, projectMgmt,    1000);
    animateValue('summary-contingency',  0, contingency,    1000);
    animateValue('summary-grand-total',  0, grandTotal,     1200);
}

// ── Animate number counting ───────────────────────────────────
function animateValue(elementId, start, end, duration) {
    const element = document.getElementById(elementId);
    if (!element) return;
    const range     = end - start;
    const increment = range / (duration / 16);
    let current     = start;
    const timer = setInterval(() => {
        current += increment;
        if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
            current = end;
            clearInterval(timer);
        }
        element.textContent = formatCurrency(current);
    }, 16);
}

// ── Print ─────────────────────────────────────────────────────
function printNetworkReport() {
    window.print();
}

// ── Shared PDF helpers ────────────────────────────────────────
// These are used by BOTH pdf_with_prices and pdf_no_prices files.

/**
 * Extracts 2-column table data (item, cost) from a report section container.
 * @param {string} containerId
 * @param {boolean} includePrices - if false, cost column is omitted
 */
function extractTableData(containerId, includePrices = true) {
    const container = document.getElementById(containerId);
    const data = [];
    container.querySelectorAll('.report-table tr').forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length === 2) {
            const item = cells[0].textContent.trim();
            const cost = cells[1].textContent.trim();
            if (!item.includes('No ') && !item.includes('empty-state')) {
                data.push(includePrices ? [item, cost] : [item]);
            }
        }
    });
    return data;
}

/**
 * Extracts 3-column equipment table data (name, description, cost).
 * @param {boolean} includePrices - if false, cost column is omitted
 */
function extractEquipmentTableData(includePrices = true) {
    const container = document.getElementById('report-equipment');
    const data = [];
    container.querySelectorAll('.report-table tr').forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length === 3) {
            const itemName    = cells[0].textContent.trim();
            const description = cells[1].textContent.trim();
            const cost        = cells[2].textContent.trim();
            if (!itemName.includes('No ') && !itemName.includes('empty-state')) {
                data.push(includePrices ? [itemName, description, cost] : [itemName, description]);
            }
        }
    });
    return data;
}

/**
 * Shared PDF page setup: creates doc, loads logo, returns a promise
 * resolving with { doc, pageWidth, pageHeight, boxX, boxY, boxWidth, drawPageBackground, startY, projectName }
 */
function initPDFDocument() {
    return new Promise((resolve, reject) => {
        const { jsPDF } = window.jspdf;
        const doc        = new jsPDF();
        const pageWidth  = doc.internal.pageSize.getWidth();
        const pageHeight = doc.internal.pageSize.getHeight();

        const img = new Image();
        img.src = "network_includes/js/technip_logo.png";

        img.onload = function () {
            const margin   = 15;
            const boxWidth = 180;
            const boxX     = (pageWidth - boxWidth) / 2;
            const boxY     = margin;

            function drawPageBackground() {
                doc.setFillColor(245, 245, 255);
                doc.roundedRect(boxX, boxY, boxWidth, pageHeight - 2 * margin, 5, 5, "F");
            }

            // Page 1 background
            drawPageBackground();

            // Logo
            const maxLogoSize = 40;
            let logoW = img.width, logoH = img.height;
            const ratio = Math.min(maxLogoSize / logoW, maxLogoSize / logoH);
            logoW *= ratio;
            logoH *= ratio;
            doc.addImage(img, "PNG", boxX + 5, boxY + 5, logoW, logoH);

            // Title
            doc.setFont("helvetica", "bold");
            doc.setFontSize(17);
            doc.text("Network Infrastructure Report", boxX + boxWidth / 2, boxY + 5 + logoH / 2 + 2, { align: "center" });

            const startY      = boxY + logoH + 15;
            const projectName = document.getElementById('report-project-name').textContent;

            resolve({ doc, pageWidth, pageHeight, boxX, boxY, boxWidth, drawPageBackground, startY, projectName });
        };

        img.onerror = () => {
            reject(new Error('Failed to load logo image'));
        };
    });
}

/**
 * Renders the project info table and returns updated y position.
 */
function renderProjectInfoTable(doc, startY, boxX, boxWidth, pageWidth, drawPageBackground) {
    const projectName = document.getElementById('report-project-name').textContent;
    const location    = document.getElementById('report-location').textContent;
    const users       = document.getElementById('report-users').textContent;
    const siteType    = document.getElementById('report-site-type').textContent;

    doc.autoTable({
        startY,
        head: [['Project Name', 'Location', 'Users', 'Site Type']],
        body: [[projectName, location, users, siteType]],
        styles:     { font: "helvetica", halign: 'center', valign: 'middle', fontSize: 9 },
        headStyles: { fillColor: [0, 112, 239], textColor: 255 },
        theme: 'grid',
        margin:     { left: boxX + 5, right: pageWidth - boxX - boxWidth + 5 },
        didAddPage: () => drawPageBackground()
    });

    return doc.lastAutoTable.finalY + 10;
}

/**
 * Renders the Network Configuration table (+ installation services) and returns updated y.
 */
function renderNetworkConfigTable(doc, y, boxX, boxWidth, pageWidth, drawPageBackground, includePrices) {
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
            styles:     { font: "helvetica", fontSize: 9 },
            headStyles: { fillColor: [35, 57, 93], textColor: 255 },
            margin:     { left: boxX + 5, right: pageWidth - boxX - boxWidth + 5 },
            didAddPage: () => drawPageBackground()
        });
        y = doc.lastAutoTable.finalY + 5;
    }

    // Installation Services (only with prices)
    if (includePrices) {
        const installationSection = document.getElementById('report-installation-services');
        const isVisible = installationSection && installationSection.style.display !== 'none';

        if (isVisible) {
            doc.setFont("helvetica", "bolditalic");
            doc.setFontSize(10);
            doc.setTextColor(46, 125, 50);
            doc.text("Installation Services Included", boxX + 5, y);
            doc.setTextColor(0, 0, 0);
            y += 5;

            const installRows = [];
            let installSubtotalText = '';

            document.querySelectorAll('#installation-services-list .report-table tr').forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length === 3) {
                    const name  = cells[0].textContent.trim();
                    const label = cells[1].textContent.trim();
                    const cost  = cells[2].textContent.trim();
                    if (!name.toLowerCase().includes('subtotal')) {
                        installRows.push([name, label, cost]);
                    } else {
                        installSubtotalText = cost;
                    }
                }
            });

            if (installRows.length > 0) {
                doc.autoTable({
                    startY: y,
                    head:       [['Service', 'Type', 'Cost']],
                    body:       installRows,
                    styles:     { font: "helvetica", fontSize: 9 },
                    headStyles: { fillColor: [76, 175, 80], textColor: 255 },
                    margin:     { left: boxX + 5, right: pageWidth - boxX - boxWidth + 5 },
                    didAddPage: () => drawPageBackground()
                });
                y = doc.lastAutoTable.finalY + 3;

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

        const networkTotal = document.getElementById('report-network-total').textContent;
        doc.setFont("helvetica", "bold");
        doc.setFontSize(10);
        doc.text(`Network Subtotal: ${networkTotal}`, boxX + boxWidth - 5, y, { align: 'right' });
        y += 10;
    } else {
        y += 5;
    }

    return y;
}

/**
 * Renders Equipment & Modules table and returns updated y.
 */
function renderEquipmentTable(doc, y, boxX, boxWidth, pageWidth, drawPageBackground, includePrices) {
    doc.setFont("helvetica", "bold");
    doc.setFontSize(12);
    doc.text("Equipment & Modules", boxX + 5, y);
    y += 5;

    const equipmentData = extractEquipmentTableData(includePrices);
    if (equipmentData.length > 0) {
        const headers = includePrices
            ? [['Item Name', 'Description/Specifications', 'Cost']]
            : [['Item Name', 'Description/Specifications']];

        const columnStyles = includePrices
            ? { 0: { halign: 'left', cellWidth: 35 }, 1: { halign: 'left', cellWidth: 110 }, 2: { halign: 'left', cellWidth: 25 } }
            : { 0: { halign: 'left', cellWidth: 55 }, 1: { halign: 'left', cellWidth: 115 } };

        doc.autoTable({
            startY: y,
            head: headers,
            body: equipmentData,
            styles:       { font: "helvetica", fontSize: 9 },
            headStyles:   { fillColor: [35, 57, 93], textColor: 255 },
            columnStyles,
            margin:       { left: boxX + 5, right: pageWidth - boxX - boxWidth + 5 },
            tableWidth:   'auto',
            didAddPage:   () => drawPageBackground()
        });
        y = doc.lastAutoTable.finalY + 5;
    }

    if (includePrices) {
        const equipmentTotal = document.getElementById('report-equipment-total').textContent;
        doc.setFont("helvetica", "bold");
        doc.setFontSize(10);
        doc.text(`Equipment Subtotal: ${equipmentTotal}`, boxX + boxWidth - 5, y, { align: 'right' });
        y += 10;
    } else {
        y += 5;
    }

    return y;
}

/**
 * Renders Cables & Accessories table (with smart page-break check) and returns updated y.
 */
function renderCablesTable(doc, y, pageHeight, boxX, boxWidth, pageWidth, drawPageBackground, includePrices) {
    const cablesData = extractTableData('report-cables', includePrices);

    // Estimate total height and force new page if it won't fit
    const estimatedHeight =
        10 +                                           // section title
        10 +                                           // table header
        (cablesData.length * 8) +                      // rows
        (includePrices ? 14 : 8);                      // subtotal line

    if (estimatedHeight > pageHeight - y - 20) {
        doc.addPage();
        drawPageBackground();
        y = 20;
    }

    doc.setFont("helvetica", "bold");
    doc.setFontSize(12);
    doc.text("Cables & Accessories", boxX + 5, y);
    y += 5;

    if (cablesData.length > 0) {
        const headers = includePrices ? [['Item', 'Cost']] : [['Item']];
        doc.autoTable({
            startY: y,
            head: headers,
            body: cablesData,
            styles:     { font: "helvetica", fontSize: 9 },
            headStyles: { fillColor: [35, 57, 93], textColor: 255 },
            margin:     { left: boxX + 5, right: pageWidth - boxX - boxWidth + 5 },
            pageBreak:  'avoid',
            didAddPage: () => drawPageBackground()
        });
        y = doc.lastAutoTable.finalY + 5;
    }

    if (includePrices) {
        const cablesTotal = document.getElementById('report-cables-total').textContent;
        doc.setFont("helvetica", "bold");
        doc.setFontSize(10);
        doc.text(`Cables Subtotal: ${cablesTotal}`, boxX + boxWidth - 5, y, { align: 'right' });
        y += 10;
    } else {
        y += 5;
    }

    return y;
}

/**
 * Renders Cost Summary table (with smart page-break check) and returns updated y.
 */
function renderCostSummary(doc, y, pageHeight, pageWidth, drawPageBackground) {
    const estimatedHeight = 16 + (8 * 10) + 10; // title + 8 rows + padding
    if (estimatedHeight > pageHeight - y - 20) {
        doc.addPage();
        drawPageBackground();
        y = 20;
    }

    doc.setFont("helvetica", "bold");
    doc.setFontSize(14);
    doc.text("Cost Summary", pageWidth / 2, y, { align: "center" });
    y += 10;

    doc.autoTable({
        startY: y,
        body: [
            ['Network Infrastructure Configuration', document.getElementById('summary-network').textContent],
            ['Equipment & Modules',                  document.getElementById('summary-equipment').textContent],
            ['Cables & Accessories',                 document.getElementById('summary-cables').textContent],
            ['Subtotal (Hardware & Configuration)',  document.getElementById('summary-subtotal').textContent],
            ['Installation Service (5% on Equipment & Cables)', document.getElementById('summary-installation').textContent],
            ['Project Management (10%)',             document.getElementById('summary-pm').textContent],
            ['Contingency Buffer (15%)',             document.getElementById('summary-contingency').textContent],
            ['GRAND TOTAL',                          document.getElementById('summary-grand-total').textContent]
        ],
        styles:       { font: "helvetica", fontSize: 10, halign: 'left' },
        columnStyles: {
            0: { fontStyle: 'bold', cellWidth: 130 },
            1: { halign: 'right', fontStyle: 'bold', textColor: [0, 112, 239] }
        },
        theme:      'grid',
        margin:     { left: 20, right: 20 },
        didAddPage: () => drawPageBackground()
    });

    return doc.lastAutoTable.finalY + 10;
}

/**
 * Renders Additional Notes section and returns updated y.
 */
function renderNotes(doc, y, pageWidth) {
    const notes = document.getElementById('report-notes').textContent;
    if (notes && notes !== 'No additional notes provided.') {
        doc.setFont("helvetica", "bold");
        doc.setFontSize(12);
        doc.text("Additional Notes:", 20, y);
        y += 7;
        doc.setFont("helvetica", "normal");
        doc.setFontSize(10);
        doc.text(doc.splitTextToSize(notes, pageWidth - 40), 20, y);
    }
    return y;
}

// ── Init ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    console.log('📊 network_report_core.js loaded');
});