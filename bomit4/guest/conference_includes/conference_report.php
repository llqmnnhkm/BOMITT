<?php
// guest/conference_includes/conference_report.php
// Conference Room Report Modal — updated to match new multi-room structure
?>

<!-- Conference Report CSS -->
<link rel="stylesheet" href="css/network_report.css">

<style>
#conference-report-modal .report-header { background: linear-gradient(135deg, #80c7a0 0%, #0070ef 100%); }
#conference-report-modal .section-header { color: #2e7d32; }
#conference-report-modal .section-header::after { background: linear-gradient(90deg, #80c7a0, #0070ef); }
#conference-report-modal .subtotal-label,
#conference-report-modal .subtotal-value { color: #2e7d32; }
#conference-report-modal .subtotal-box { background: linear-gradient(90deg, rgba(128,199,160,0.15) 0%, rgba(0,112,239,0.1) 100%); }
#conference-report-modal .info-grid { background: linear-gradient(135deg, #f1f8f4 0%, #e8f4f8 100%); box-shadow: 0 4px 12px rgba(128,199,160,0.2); }
#conference-report-modal .grand-total-row { background: linear-gradient(135deg, #80c7a0 0%, #0070ef 100%) !important; }
#conference-report-modal .btn-export-with-price { background: linear-gradient(135deg, #80c7a0 0%, #4caf50 100%); }
#conference-report-modal .btn-export-no-price   { background: linear-gradient(135deg, #6366f1 0%, #818cf8 100%); }
.cr-room-card { display:inline-flex; align-items:center; gap:8px; padding:8px 16px; border-radius:8px; font-weight:600; font-size:0.9rem; margin:4px; }
.cr-room-small  { background:#e3f2fd; color:#1565c0; border:2px solid #1976d2; }
.cr-room-medium { background:#e8f5e9; color:#2e7d32; border:2px solid #43a047; }
.cr-room-large  { background:#f3e5f5; color:#6a1b9a; border:2px solid #8e24aa; }
#conference-report-modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); backdrop-filter:blur(5px); z-index:9999; overflow-y:auto; animation:fadeIn 0.3s ease-out; }
</style>

<div id="conference-report-modal">
    <div class="report-container">

        <div class="report-header">
            <div class="report-header-content">
                <div>
                    <h2 class="report-title">Conference Room Report</h2>
                    <p class="report-subtitle">Equipment Configuration &amp; Cost Breakdown</p>
                </div>
                <button onclick="closeConferenceReport()" class="close-btn">&#215;</button>
            </div>
        </div>

        <div class="report-body">

            <!-- Project Info -->
            <section class="report-section">
                <h3 class="section-header">Project Information</h3>
                <div class="info-grid">
                    <div class="info-item"><div class="info-label">Project Name</div><div id="cr-project-name" class="info-value">-</div></div>
                    <div class="info-item"><div class="info-label">Requesting Manager</div><div id="cr-manager" class="info-value">-</div></div>
                    <div class="info-item"><div class="info-label">Deployment Date</div><div id="cr-deployment" class="info-value">-</div></div>
                    <div class="info-item"><div class="info-label">Number of Users</div><div id="cr-users" class="info-value">-</div></div>
                </div>
            </section>

            <!-- Room Selection -->
            <section class="report-section">
                <h3 class="section-header">Room Configuration</h3>
                <div class="content-box">
                    <div style="margin-bottom:10px;font-weight:600;color:#555;">Selected Rooms:</div>
                    <div id="cr-room-selection"></div>
                    <div id="cr-room-total" style="margin-top:12px;font-size:0.9rem;color:#666;"></div>
                </div>
            </section>

            <!-- AV & Connectivity -->
            <section class="report-section">
                <h3 class="section-header">AV &amp; Connectivity Preferences</h3>
                <div id="cr-av-config" class="content-box"></div>
            </section>

            <!-- Equipment -->
            <section class="report-section">
                <h3 class="section-header">Equipment &amp; Connectivity Items</h3>
                <div id="cr-equipment" class="content-box"></div>
                <div class="subtotal-box">
                    <div class="subtotal-label">Equipment Subtotal</div>
                    <div id="cr-equipment-total" class="subtotal-value">$0.00</div>
                </div>
            </section>

            <!-- Notes -->
            <section class="report-section">
                <h3 class="section-header">Additional Notes</h3>
                <div id="cr-notes" class="notes-box">No additional notes provided.</div>
            </section>

            <!-- Cost Summary -->
            <section class="report-section">
                <h3 class="section-header">Cost Summary</h3>
                <table class="summary-table">
                    <tbody>
                        <tr><td>Equipment &amp; Connectivity</td><td id="cr-summary-equipment">$0.00</td></tr>
                        <tr class="subtotal-row"><td>Subtotal (Hardware)</td><td id="cr-summary-subtotal">$0.00</td></tr>
                        <tr class="service-row"><td>Installation Service (5%)</td><td id="cr-summary-installation">$0.00</td></tr>
                        <tr class="service-row"><td>Project Management (10%)</td><td id="cr-summary-pm">$0.00</td></tr>
                        <tr class="service-row contingency-row"><td>Contingency Buffer (15%)</td><td id="cr-summary-contingency">$0.00</td></tr>
                        <tr class="grand-total-row"><td>GRAND TOTAL</td><td id="cr-summary-grand-total">$0.00</td></tr>
                    </tbody>
                </table>
            </section>

            <!-- Actions -->
            <div class="action-buttons">
                <button onclick="exportConferencePDFWithPrices()" class="btn btn-export-with-price">&#128176; Export PDF (With Prices)</button>
                <button onclick="exportConferencePDFNoPrices()" class="btn btn-export-no-price">&#128196; Export PDF (Without Prices)</button>
                <button onclick="exportConferenceExcel()" class="btn btn-export-excel" style="background:linear-gradient(90deg,#217346,#1a5c38);color:white;border:none;padding:10px 20px;border-radius:8px;cursor:pointer;font-family:Montserrat,sans-serif;font-weight:600;font-size:.9rem;">&#128202; Export Excel</button>
                <button onclick="closeConferenceReport()" class="btn btn-close">&#10005; Close</button>
            </div>

        </div>
    </div>
</div>

<script>
// ── Override crGatherRoomInfo to use confRoomState (multi-room) ──────────
function crGatherRoomInfo() {
    const labels = { small:'Small (4–6 people)', medium:'Medium (8–12 people)', large:'Large (15+ people)' };
    const emojis = { small:'', medium:'', large:'' };
    const state  = window.confRoomState || { small:0, medium:0, large:0 };
    const active = Object.keys(state).filter(s => state[s] > 0);
    const selEl  = document.getElementById('cr-room-selection');
    const totEl  = document.getElementById('cr-room-total');
    if (!selEl) return;
    if (!active.length) {
        selEl.innerHTML = '<span style="color:#999;font-style:italic;">No rooms selected</span>';
        if (totEl) totEl.textContent = '';
        return;
    }
    selEl.innerHTML = active.map(size =>
        `<span class="cr-room-card cr-room-${size}">${emojis[size]} ${labels[size]} &nbsp;&times;&nbsp; <strong>${state[size]}</strong> room${state[size]>1?'s':''}</span>`
    ).join('');
    const total = active.reduce((s,k) => s + state[k], 0);
    if (totEl) totEl.textContent = `Total: ${total} room${total>1?'s':''} across ${active.length} size${active.length>1?'s':''}`;
}

// ── Override crRenderRoomInfo for PDF (multi-room) ────────────────────────
function crRenderRoomInfo(doc, y, boxX, boxWidth, pageWidth, drawBg) {
    const labels = { small:'Small (4–6 people)', medium:'Medium (8–12 people)', large:'Large (15+ people)' };
    const state  = window.confRoomState || {};
    const active = Object.keys(state).filter(s => state[s] > 0);
    const roomText = active.length
        ? active.map(s => `${labels[s]} ×${state[s]}`).join(', ')
        : 'No rooms selected';
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(12);
    doc.text('Room Configuration', boxX + 5, y); y += 5;
    doc.autoTable({
        startY: y,
        head: [['Room Selection', 'Total Rooms']],
        body: [[ roomText, active.reduce((s,k) => s + state[k], 0) + ' room(s)' ]],
        styles:     { font:'helvetica', halign:'center', fontSize:9 },
        headStyles: { fillColor:[0,112,239], textColor:255 },
        theme: 'grid',
        margin: { left: boxX + 5, right: pageWidth - boxX - boxWidth + 5 },
        didAddPage: () => drawBg()
    });
    return doc.lastAutoTable.finalY + 8;
}

// ── AV Config: shows all selections with Required? column ────────────────
function crGatherAVConfig() {
    const container = document.getElementById('cr-av-config');
    const rows = [
        { label:'Display / Projection',           select:'av_display_type',   check:'av_display_required' },
        { label:'Video Conferencing Platform',     select:'av_vc_platform',    check:'av_vc_required' },
        { label:'Wireless Presentation',           select:'av_wireless_type',  check:'av_wireless_required' },
        { label:'Wired HDMI/USB-C Drops',          select:'av_wired_drops',    check:'av_wired_required' },
        { label:'Room Automation / Control',       select:'av_control_system', check:'av_control_required' },
        { label:'Network / Internet Connectivity', select:'av_network_type',   check:'av_network_required' },
    ];
    let html = '<table class="report-table"><thead style="background:#f5f5f5;"><tr><th style="padding:10px;text-align:left;">Requirement</th><th style="padding:10px;text-align:center;">Selection</th><th style="padding:10px;text-align:center;">Required?</th></tr></thead><tbody>';
    let hasAny = false;
    rows.forEach(r => {
        const val      = document.querySelector(`select[name="${r.select}"]`)?.value || '';
        const included = document.querySelector(`input[name="${r.check}"]`)?.checked;
        if (!val || val === '') return;
        hasAny = true;
        const display = val.replace(/_/g,' ').replace(/\b\w/g, c => c.toUpperCase());
        html += `<tr style="border-bottom:1px solid #eee;">
            <td style="padding:10px;font-weight:500;color:#2d3748;">${r.label}</td>
            <td style="padding:10px;text-align:center;color:#0070ef;font-weight:600;">${display}</td>
            <td style="padding:10px;text-align:center;">${included ? '<span style="color:#2e7d32;font-weight:700;">✔ Yes</span>' : '<span style="color:#999;">— No</span>'}</td>
        </tr>`;
    });
    if (!hasAny) html += '<tr><td colspan="3"><div class="empty-state">No AV/connectivity preferences selected</div></td></tr>';
    html += '</tbody></table>';
    container.innerHTML = html;
}

// ── Equipment: grouped by room size ──────────────────────────────────────
function crGatherEquipment() {
    const container = document.getElementById('cr-equipment');
    const state  = window.confRoomState || {};
    const labels = { small:'Small (4–6 people)', medium:'Medium (8–12 people)', large:'Large (15+ people)' };
    const emojis = { small:'🪑', medium:'👥', large:'🏛️' };
    const colors = { small:'#1565c0', medium:'#2e7d32', large:'#6a1b9a' };
    const bgs    = { small:'#e3f2fd', medium:'#e8f5e9', large:'#f3e5f5' };
    let html = '';
    let grandTotal = 0;

    ['small','medium','large'].forEach(size => {
        const qty = state[size] || 0;
        if (!qty) return;
        const section = document.getElementById('conf_equip_' + size);
        if (!section) return;
        let sectionTotal = 0, rows = '';
        section.querySelectorAll('input[type="number"]').forEach(input => {
            const iq = parseFloat(input.value) || 0;
            if (!iq) return;
            const price = parseFloat(input.getAttribute('data-price')) || 0;
            const rowTotal = iq * price;
            sectionTotal += rowTotal;
            const row = input.closest('tr');
            const fullText = row?.querySelector('td:first-child')?.textContent.trim() || '';
            let itemName = fullText, desc = '';
            const m = fullText.match(/^(.*?)\s*\((.*?)\)$/);
            if (m) { itemName = m[1].trim(); desc = m[2].trim(); }
            rows += `<tr style="border-bottom:1px solid #f0f0f0;">
                <td style="padding:9px 12px;font-weight:500;color:#2d3748;">${itemName}${desc?`<br><small style="color:#888;font-weight:400;">${desc}</small>`:''}  </td>
                <td style="padding:9px 12px;text-align:center;color:#555;">${iq}</td>
                <td class="cr-price-col" style="padding:9px 12px;text-align:right;color:#666;">$${price.toFixed(2)}</td>
                <td class="cr-total-col" style="padding:9px 12px;text-align:right;font-weight:700;color:${colors[size]};">$${rowTotal.toFixed(2)}</td>
            </tr>`;
        });
        grandTotal += sectionTotal;
        html += `<div style="margin-bottom:1.5rem;">
            <div style="font-weight:700;font-size:1rem;color:${colors[size]};padding:10px 12px;border-radius:8px;margin-bottom:8px;background:${bgs[size]};">
                ${emojis[size]} ${labels[size]} — ${qty} room${qty>1?'s':''}
            </div>
            <table style="width:100%;border-collapse:collapse;font-size:0.875rem;">
                <thead style="background:#f8f9fa;">
                    <tr>
                        <th style="padding:9px 12px;text-align:left;color:#555;font-weight:600;">Item</th>
                        <th style="padding:9px 12px;text-align:center;color:#555;font-weight:600;">Qty</th>
                        <th class="cr-price-col" style="padding:9px 12px;text-align:right;color:#555;font-weight:600;">Unit Price</th>
                        <th class="cr-total-col" style="padding:9px 12px;text-align:right;color:#555;font-weight:600;">Total</th>
                    </tr>
                </thead>
                <tbody>${rows||'<tr><td colspan="4" style="padding:12px;color:#999;text-align:center;font-style:italic;">No items with quantity &gt; 0</td></tr>'}</tbody>
                <tfoot>
                    <tr style="border-top:2px solid #e0e0e0;">
                        <td colspan="3" style="padding:9px 12px;text-align:right;font-weight:700;color:${colors[size]};">${labels[size]} Subtotal:</td>
                        <td style="padding:9px 12px;text-align:right;font-weight:800;font-size:1rem;color:${colors[size]};">$${sectionTotal.toFixed(2)}</td>
                    </tr>
                </tfoot>
            </table>
        </div>`;
    });

    if (!html) html = '<div class="empty-state">No rooms selected — please choose room sizes above</div>';
    container.innerHTML = html;
    document.getElementById('cr-equipment-total').textContent = '$' + grandTotal.toFixed(2);
    return grandTotal;
}
</script>