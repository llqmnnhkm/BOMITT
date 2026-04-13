// guest/js/export_excel.js
// ============================================================
// Shared Excel (XLSX) export for all 4 BoMIT report types
// Uses SheetJS (xlsx) loaded from CDN in guest_home.php
// Applies current currency (from window.BOMIT_CURRENCY)
// ============================================================

// ── Helper: format a MYR value in current currency ────────────────────────
function xlsFmt(myrAmount) {
    const cur  = window.BOMIT_CURRENCY?.current || 'MYR';
    const sym  = window.BOMIT_CURRENCY?.symbols?.[cur] || 'RM';
    const rate = window.BOMIT_CURRENCY?.rates?.[cur] || 1;
    const val  = (parseFloat(myrAmount) || 0) * rate;
    return sym + val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function xlsNumeric(myrAmount) {
    const rate = window.BOMIT_CURRENCY?.rates?.[window.BOMIT_CURRENCY?.current] || 1;
    return Math.round((parseFloat(myrAmount) || 0) * rate * 100) / 100;
}

function xlsCurrencyLabel() {
    const cur = window.BOMIT_CURRENCY?.current || 'MYR';
    const sym = window.BOMIT_CURRENCY?.symbols?.[cur] || 'RM';
    return `${cur} (${sym})`;
}

// ── Core: build workbook and download ─────────────────────────────────────
function xlsDownload(workbook, filename) {
    if (typeof XLSX === 'undefined') {
        alert('Excel library not loaded. Please refresh the page and try again.');
        return;
    }
    XLSX.writeFile(workbook, filename + '.xlsx');
}

// ── Style helpers (SheetJS cell styles via sheet_add_aoa with formats) ────
function xlsHeaderRow(values) {
    return values.map(v => ({ v, t: 's', s: { font: { bold: true }, fill: { fgColor: { rgb: 'D9E8FF' } } } }));
}
function xlsTitleRow(text) {
    return [{ v: text, t: 's', s: { font: { bold: true, sz: 14 } } }];
}
function xlsTotalRow(values) {
    return values.map(v => ({ v, t: typeof v === 'number' ? 'n' : 's', s: { font: { bold: true } } }));
}

// ── Add project info rows at top of a sheet ───────────────────────────────
function xlsProjectInfoRows(info) {
    // info = { project_name, manager, duration, date, users }
    return [
        [{ v: 'BoMIT System — Technip Energies', t: 's', s: { font: { bold: true, sz: 13 } } }],
        [],
        [{ v: 'PROJECT INFORMATION', t: 's', s: { font: { bold: true } } }],
        ['Project Name',       info.project_name       || ''],
        ['Requesting Manager', info.manager             || ''],
        ['Project Duration',   (info.duration || '') + ' months'],
        ['Deployment Date',    info.date                || ''],
        ['Number of Users',    info.users               || ''],
        ['Currency',           xlsCurrencyLabel()],
        ['Generated',          new Date().toLocaleDateString('en-MY')],
        [],
    ];
}

// ═══════════════════════════════════════════════════════════════════════════
// 1. NETWORK INFRASTRUCTURE EXCEL EXPORT
// ═══════════════════════════════════════════════════════════════════════════
function exportNetworkExcel() {
    if (typeof XLSX === 'undefined') { alert('Excel library not loaded.'); return; }

    const projName = document.querySelector('input[name="project_name"]')?.value  || '';
    const manager  = document.querySelector('input[name="requesting_manager"]')?.value || '';
    const duration = document.querySelector('input[name="project_duration"]')?.value  || '';
    const date     = document.querySelector('input[name="deployment_date"]')?.value   || '';
    const users    = document.querySelector('select[name="user_quantity"]')?.value    || '';

    const wb   = XLSX.utils.book_new();
    const rows = xlsProjectInfoRows({ project_name: projName, manager, duration, date, users });

    // ── Network Config / Installation Services ──────────────────────────
    rows.push([{ v: 'NETWORK CONFIGURATION', t: 's', s: { font: { bold: true } } }]);
    rows.push(['Item', 'Unit Price (' + xlsCurrencyLabel() + ')']);

    const configContainer = document.getElementById('report-network-config');
    if (configContainer) {
        configContainer.querySelectorAll('tr').forEach(tr => {
            const cells = tr.querySelectorAll('td');
            if (cells.length >= 2) {
                const price = parseFloat(cells[1].textContent.replace(/[^0-9.]/g, '')) || 0;
                rows.push([cells[0].textContent.trim(), xlsFmt(price)]);
            }
        });
    }
    rows.push([]);

    // ── Equipment ────────────────────────────────────────────────────────
    rows.push([{ v: 'EQUIPMENT ITEMS', t: 's', s: { font: { bold: true } } }]);
    rows.push(['Item', 'Qty', 'Unit Price (' + xlsCurrencyLabel() + ')', 'Total (' + xlsCurrencyLabel() + ')']);

    // Walk all visible equipment inputs
    document.querySelectorAll('input[data-price][name*="equip"], input[data-price][name*="module"]').forEach(inp => {
        const row   = inp.closest('tr');
        if (!row) return;
        const cells = row.querySelectorAll('td');
        if (cells.length < 2) return;
        const name  = cells[0]?.textContent.trim() || '';
        const qty   = parseFloat(inp.value) || 0;
        const price = parseFloat(inp.getAttribute('data-price')) || 0;
        if (qty > 0 && price > 0) {
            rows.push([name, qty, xlsFmt(price), xlsFmt(qty * price)]);
        }
    });
    rows.push([]);

    // ── Cables & Accessories ─────────────────────────────────────────────
    rows.push([{ v: 'CABLES & ACCESSORIES', t: 's', s: { font: { bold: true } } }]);
    rows.push(['Item', 'Qty', 'Unit Price (' + xlsCurrencyLabel() + ')', 'Total (' + xlsCurrencyLabel() + ')']);
    document.querySelectorAll('input[data-price][name*="cable"], input[data-price][name*="access"]').forEach(inp => {
        const row = inp.closest('tr');
        if (!row) return;
        const cells = row.querySelectorAll('td');
        const name  = cells[0]?.textContent.trim() || '';
        const qty   = parseFloat(inp.value) || 0;
        const price = parseFloat(inp.getAttribute('data-price')) || 0;
        if (qty > 0 && price > 0) rows.push([name, qty, xlsFmt(price), xlsFmt(qty * price)]);
    });
    rows.push([]);

    // ── Totals ───────────────────────────────────────────────────────────
    const equipTotal    = parseFloat(document.getElementById('report-equipment-total')?.textContent?.replace(/[^0-9.]/g,'')) || 0;
    const installTotal  = parseFloat(document.getElementById('report-installation-total')?.textContent?.replace(/[^0-9.]/g,'')) || 0;
    const cableTotal    = parseFloat(document.getElementById('report-cables-total')?.textContent?.replace(/[^0-9.]/g,'')) || 0;
    const subtotal      = equipTotal + installTotal + cableTotal;
    const pm            = subtotal * 0.10;
    const contingency   = subtotal * 0.15;
    const grand         = subtotal + pm + contingency;

    rows.push([{ v: 'COST SUMMARY', t: 's', s: { font: { bold: true } } }]);
    rows.push(['Equipment Subtotal',          xlsFmt(equipTotal)]);
    rows.push(['Installation Services',       xlsFmt(installTotal)]);
    rows.push(['Cables & Accessories',        xlsFmt(cableTotal)]);
    rows.push(['Subtotal',                    xlsFmt(subtotal)]);
    rows.push(['Project Management (10%)',    xlsFmt(pm)]);
    rows.push(['Contingency (15%)',           xlsFmt(contingency)]);
    rows.push([{ v: 'GRAND TOTAL', t: 's', s: { font: { bold: true } } }, { v: xlsFmt(grand), t: 's', s: { font: { bold: true } } }]);

    const ws = XLSX.utils.aoa_to_sheet(rows);
    ws['!cols'] = [{ wch: 40 }, { wch: 10 }, { wch: 18 }, { wch: 18 }];
    XLSX.utils.book_append_sheet(wb, ws, 'Network Infrastructure');
    xlsDownload(wb, 'Network_Report_' + (projName || 'Project'));
}

// ═══════════════════════════════════════════════════════════════════════════
// 2. CONFERENCE ROOM EXCEL EXPORT
// ═══════════════════════════════════════════════════════════════════════════
function exportConferenceExcel() {
    if (typeof XLSX === 'undefined') { alert('Excel library not loaded.'); return; }

    const projName = document.querySelector('input[name="project_name"]')?.value  || '';
    const manager  = document.querySelector('input[name="requesting_manager"]')?.value || '';
    const duration = document.querySelector('input[name="project_duration"]')?.value  || '';
    const date     = document.querySelector('input[name="deployment_date"]')?.value   || '';

    const wb   = XLSX.utils.book_new();
    const roomSizes = ['small', 'medium', 'large'];
    const roomLabels = { small: 'Small (4–6 people)', medium: 'Medium (8–12 people)', large: 'Large (15+ people)' };
    let grandTotal = 0;

    // One sheet per active room size
    roomSizes.forEach(size => {
        const section = document.getElementById('conf_equip_' + size);
        if (!section || section.classList.contains('hidden')) return;

        const qty = window.confRoomState?.[size] || 1;
        const rows = xlsProjectInfoRows({ project_name: projName, manager, duration, date });

        rows.push([{ v: roomLabels[size].toUpperCase() + ' — ' + qty + ' ROOM(S)', t: 's', s: { font: { bold: true, sz: 13 } } }]);
        rows.push([]);
        rows.push(['Item', 'Qty', 'Unit Price (' + xlsCurrencyLabel() + ')', 'Total (' + xlsCurrencyLabel() + ')']);

        let sectionTotal = 0;
        section.querySelectorAll('input[type="number"][data-price]').forEach(inp => {
            const row   = inp.closest('tr');
            if (!row) return;
            const name  = row.querySelector('td')?.textContent.trim() || '';
            const itemQty = parseFloat(inp.value) || 0;
            const price = parseFloat(inp.getAttribute('data-price')) || 0;
            const total = itemQty * price;
            sectionTotal += total;
            rows.push([name, itemQty, xlsFmt(price), xlsFmt(total)]);
        });
        grandTotal += sectionTotal;

        rows.push([]);
        rows.push([{ v: roomLabels[size] + ' Subtotal', t: 's', s: { font: { bold: true } } }, '', '', { v: xlsFmt(sectionTotal), t: 's', s: { font: { bold: true } } }]);

        const ws = XLSX.utils.aoa_to_sheet(rows);
        ws['!cols'] = [{ wch: 38 }, { wch: 8 }, { wch: 18 }, { wch: 18 }];
        XLSX.utils.book_append_sheet(wb, ws, roomLabels[size].split(' ')[0]);
    });

    // Summary sheet
    const summaryRows = xlsProjectInfoRows({ project_name: projName, manager, duration, date });
    summaryRows.push([{ v: 'CONFERENCE ROOM SUMMARY', t: 's', s: { font: { bold: true, sz: 13 } } }]);
    summaryRows.push([]);
    summaryRows.push(['Room Size', 'Rooms', 'Subtotal (' + xlsCurrencyLabel() + ')']);

    roomSizes.forEach(size => {
        const section = document.getElementById('conf_equip_' + size);
        if (!section || section.classList.contains('hidden')) return;
        const qty = window.confRoomState?.[size] || 1;
        let sectionTotal = 0;
        section.querySelectorAll('input[type="number"][data-price]').forEach(inp => {
            sectionTotal += (parseFloat(inp.value)||0) * (parseFloat(inp.getAttribute('data-price'))||0);
        });
        summaryRows.push([roomLabels[size], qty, xlsFmt(sectionTotal)]);
    });

    const pm = grandTotal * 0.10;
    const ct = grandTotal * 0.15;
    summaryRows.push([]);
    summaryRows.push(['Equipment Total',           '', xlsFmt(grandTotal)]);
    summaryRows.push(['Project Management (10%)', '', xlsFmt(pm)]);
    summaryRows.push(['Contingency (15%)',         '', xlsFmt(ct)]);
    summaryRows.push([{ v: 'GRAND TOTAL', t:'s', s:{font:{bold:true}} }, '', { v: xlsFmt(grandTotal+pm+ct), t:'s', s:{font:{bold:true}} }]);

    const summWs = XLSX.utils.aoa_to_sheet(summaryRows);
    summWs['!cols'] = [{ wch: 32 }, { wch: 10 }, { wch: 20 }];
    XLSX.utils.book_append_sheet(wb, summWs, 'Summary');

    xlsDownload(wb, 'Conference_Report_' + (projName || 'Project'));
}

// ═══════════════════════════════════════════════════════════════════════════
// 3. END USER EQUIPMENT EXCEL EXPORT
// ═══════════════════════════════════════════════════════════════════════════
function exportEndUserExcel() {
    if (typeof XLSX === 'undefined') { alert('Excel library not loaded.'); return; }

    const projName = document.querySelector('input[name="project_name"]')?.value  || '';
    const manager  = document.querySelector('input[name="requesting_manager"]')?.value || '';
    const duration = document.querySelector('input[name="project_duration"]')?.value  || '';
    const date     = document.querySelector('input[name="deployment_date"]')?.value   || '';
    const users    = document.querySelector('select[name="user_quantity"]')?.value
                  || document.querySelector('input[name="user_quantity"]')?.value || '';

    const wb = XLSX.utils.book_new();
    const typeLabels = {
        general:'General User', technical:'Technical User',
        design:'Design / CAD', field:'Field / Mobile', executive:'Executive / VIP'
    };
    const catLabels = {
        workstation:'Workstation Equipment', peripherals:'Peripherals & Accessories',
        mobile:'Mobile & Communications', software:'Software & Licenses'
    };

    let combinedTotal = 0;
    const summaryRows = xlsProjectInfoRows({ project_name: projName, manager, duration, date, users });
    summaryRows.push([{ v: 'END USER EQUIPMENT SUMMARY', t:'s', s:{font:{bold:true,sz:13}} }]);
    summaryRows.push([]);
    summaryRows.push(['User Type', 'Users', 'Subtotal (' + xlsCurrencyLabel() + ')']);

    Object.keys(typeLabels).forEach(utKey => {
        const section = document.getElementById('eu-section-' + utKey);
        if (!section || section.style.display === 'none') return;

        const userCount = window.euTypeState?.[utKey] || 1;
        const rows = xlsProjectInfoRows({ project_name: projName, manager, duration, date, users });
        rows.push([{ v: typeLabels[utKey].toUpperCase() + ' (' + userCount + ' users)', t:'s', s:{font:{bold:true,sz:13}} }]);
        rows.push([]);

        let typeTotal = 0;
        Object.keys(catLabels).forEach(cat => {
            const tbody = document.getElementById('eu-tbody-' + utKey + '-' + cat);
            if (!tbody) return;
            const catItems = [];
            tbody.querySelectorAll('tr').forEach(tr => {
                const inp  = tr.querySelector('input[type="number"]');
                if (!inp) return;
                const name  = tr.querySelector('td')?.textContent.trim() || '';
                const qty   = parseFloat(inp.value) || 0;
                const price = parseFloat(inp.getAttribute('data-price')) || 0;
                const total = qty * price;
                if (qty > 0) { catItems.push([name, qty, xlsFmt(price), xlsFmt(total)]); typeTotal += total; }
            });
            if (catItems.length > 0) {
                rows.push([{ v: catLabels[cat], t:'s', s:{font:{bold:true}} }]);
                rows.push(['Item', 'Qty', 'Unit Price (' + xlsCurrencyLabel() + ')', 'Total (' + xlsCurrencyLabel() + ')']);
                catItems.forEach(r => rows.push(r));
                rows.push([]);
            }
        });

        combinedTotal += typeTotal;
        rows.push([{ v: typeLabels[utKey] + ' Total', t:'s', s:{font:{bold:true}} }, '', '', { v: xlsFmt(typeTotal), t:'s', s:{font:{bold:true}} }]);
        summaryRows.push([typeLabels[utKey], userCount, xlsFmt(typeTotal)]);

        const ws = XLSX.utils.aoa_to_sheet(rows);
        ws['!cols'] = [{ wch: 40 }, { wch: 8 }, { wch: 18 }, { wch: 18 }];
        XLSX.utils.book_append_sheet(wb, ws, typeLabels[utKey].substring(0, 31));
    });

    // Summary sheet totals
    const pm = combinedTotal * 0.10;
    const ct = combinedTotal * 0.15;
    summaryRows.push([]);
    summaryRows.push(['Equipment Total',           '', xlsFmt(combinedTotal)]);
    summaryRows.push(['Project Management (10%)', '', xlsFmt(pm)]);
    summaryRows.push(['Contingency (15%)',         '', xlsFmt(ct)]);
    summaryRows.push([{ v:'GRAND TOTAL', t:'s', s:{font:{bold:true}} }, '', { v:xlsFmt(combinedTotal+pm+ct), t:'s', s:{font:{bold:true}} }]);

    const summWs = XLSX.utils.aoa_to_sheet(summaryRows);
    summWs['!cols'] = [{ wch: 32 }, { wch: 10 }, { wch: 20 }];
    XLSX.utils.book_append_sheet(wb, summWs, 'Summary');

    xlsDownload(wb, 'EndUser_Report_' + (projName || 'Project'));
}

// ═══════════════════════════════════════════════════════════════════════════
// 4. SERVER INFRASTRUCTURE EXCEL EXPORT
// ═══════════════════════════════════════════════════════════════════════════
function exportServerExcel(summaryData) {
    if (typeof XLSX === 'undefined') { alert('Excel library not loaded.'); return; }
    if (!summaryData) {
        // Try to get from window if called from server_summary.php
        summaryData = window.serverSummaryData;
    }
    if (!summaryData) { alert('No server data available.'); return; }

    const d  = summaryData;
    const wb = XLSX.utils.book_new();

    // ── Sheet 1: VM Table ────────────────────────────────────────────────
    const vmRows = [
        [{ v: 'SERVER INFRASTRUCTURE — VM & EQUIPMENT', t:'s', s:{font:{bold:true,sz:13}} }],
        [],
        ['Project',    d.projectName       || ''],
        ['Manager',    d.requestingManager || ''],
        ['Duration',   (d.projectDuration||'') + ' months'],
        ['Deployment', d.deploymentDate    || ''],
        ['Users',      d.userQuantity      || ''],
        ['Currency',   xlsCurrencyLabel()],
        [],
        [{ v:'VM & INFRASTRUCTURE', t:'s', s:{font:{bold:true}} }],
        ['Type', 'VM / Application', 'Cores', 'Memory (GB)', 'OS Storage (GB)', 'Data Storage (GB)'],
    ];
    if (d.vmTable) {
        d.vmTable.forEach(row => vmRows.push([row[0], row[1], row[2], row[3], row[4], row[5]]));
    }
    vmRows.push([]);
    vmRows.push([{ v:'COMPUTE SIZING', t:'s', s:{font:{bold:true}} }]);
    vmRows.push(['Current Cores','Current Memory (GB)','Future Factor','Cores Required','Memory Required (GB)','Hosts','FTT']);
    vmRows.push([d.totalCoreCount, d.totalMemory, d.future_needs + '×', d.cores_required, d.memory_required, d.hosts, d.ftt]);
    vmRows.push([]);
    vmRows.push(['Cores/Host','vRatio','Mem/Host (GB)','Cores Provided','Cores Spare','Mem Provided (GB)','Mem Spare (GB)']);
    vmRows.push([d.cores_per_host, d.vratio, d.memory_per_host, d.cores_provided, d.cores_spare, d.memory_provided, d.memory_spare]);
    vmRows.push([]);
    vmRows.push([{ v:'STORAGE & DATA DOMAIN', t:'s', s:{font:{bold:true}} }]);
    vmRows.push(['Current Req (TB)','Future Factor','Required (TB)','Logical (TB)','Physical (TB)','Rec Physical (TB)','Recommended Model']);
    vmRows.push([
        d.current_requirements, d.future_needs + '×',
        Math.round(d.current_requirements * d.future_needs * 100)/100,
        d.total_logical, d.physical_optimized, d.rec_physical, d.rec
    ]);

    const vmWs = XLSX.utils.aoa_to_sheet(vmRows);
    vmWs['!cols'] = [{ wch: 24 }, { wch: 24 }, { wch: 12 }, { wch: 16 }, { wch: 18 }, { wch: 18 }, { wch: 28 }];
    XLSX.utils.book_append_sheet(wb, vmWs, 'Server Infrastructure');

    xlsDownload(wb, 'Server_Report_' + ((d.projectName || 'Project').replace(/\s+/g,'_')));
}