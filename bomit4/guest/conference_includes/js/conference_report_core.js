// guest/conference_includes/js/conference_report_core.js
// Conference Report: modal control, data gathering, PDF export
// Mirrors network_report_core.js structure

// ── Currency formatter ────────────────────────────────────────
function confFormatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency', currency: 'USD',
        minimumFractionDigits: 2, maximumFractionDigits: 2
    }).format(amount || 0);
}

// ── Show / Close ──────────────────────────────────────────────
function showConferenceReport() {
    console.log('📊 Generating Conference Report...');
    crGatherProjectInfo();
    crGatherRoomInfo();
    crGatherAVConfig();
    crGatherEquipment();
    crGatherNotes();
    crCalculateTotals();

    const modal = document.getElementById('conference-report-modal');
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    modal.offsetHeight; // reflow for animation
}

function closeConferenceReport() {
    document.getElementById('conference-report-modal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

document.addEventListener('click', function(e) {
    const modal = document.getElementById('conference-report-modal');
    if (e.target === modal) closeConferenceReport();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('conference-report-modal');
        if (modal && modal.style.display === 'block') closeConferenceReport();
    }
});

// ── Gather: Project Info ──────────────────────────────────────
function crGatherProjectInfo() {
    document.getElementById('cr-project-name').textContent =
        document.querySelector('input[name="project_name"]')?.value || 'Not specified';
    document.getElementById('cr-manager').textContent =
        document.querySelector('input[name="requesting_manager"]')?.value || 'Not specified';
    document.getElementById('cr-deployment').textContent =
        document.querySelector('input[name="deployment_date"]')?.value || 'Not specified';
    document.getElementById('cr-users').textContent =
        document.querySelector('select[name="user_quantity"]')?.value || 'Not specified';
}

// ── Gather: Room Info ─────────────────────────────────────────
function crGatherRoomInfo() {
    const labels = { small:'Small (4–6 people)', medium:'Medium (8–12 people)', large:'Large (15+ people)' };
    const emojis = { small:'', medium:'', large:'' };
    const state  = window.confRoomState || { small:0, medium:0, large:0 };
    const active = Object.keys(state).filter(s => state[s] > 0);

    const selEl = document.getElementById('cr-room-selection');
    const totEl = document.getElementById('cr-room-total');
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

// ── Gather: AV & Connectivity ─────────────────────────────────
function crGatherAVConfig() {
    const container = document.getElementById('cr-av-config');
    const rows = [
        { label: 'Display / Projection',          select: 'av_display_type',    check: 'av_display_required' },
        { label: 'Video Conferencing Platform',    select: 'av_vc_platform',     check: 'av_vc_required' },
        { label: 'Wireless Presentation',          select: 'av_wireless_type',   check: 'av_wireless_required' },
        { label: 'Wired HDMI/USB-C Drops',         select: 'av_wired_drops',     check: 'av_wired_required' },
        { label: 'Room Automation / Control',      select: 'av_control_system',  check: 'av_control_required' },
        { label: 'Network / Internet Connectivity',select: 'av_network_type',    check: 'av_network_required' },
    ];

    let html = '<table class="report-table">';
    let hasAny = false;

    rows.forEach(r => {
        const val      = document.querySelector(`select[name="${r.select}"]`)?.value;
        const included = document.querySelector(`input[name="${r.check}"]`)?.checked;
        if (val && val !== '' && val !== 'none' && included) {
            hasAny = true;
            html += `<tr>
                <td>${r.label}</td>
                <td style="color:#2e7d32; font-weight:600;">✓ ${val.replace(/_/g,' ')}</td>
            </tr>`;
        }
    });

    if (!hasAny) {
        html += '<tr><td colspan="2"><div class="empty-state">No AV/connectivity items selected</div></td></tr>';
    }
    html += '</table>';
    container.innerHTML = html;
}

// ── Gather: Equipment ─────────────────────────────────────────
function crGatherEquipment() {
    const container = document.getElementById('cr-equipment');
    const state   = window.confRoomState || {};
    const sizes   = ['small', 'medium', 'large'];
    const labels  = { small:'Small (4–6 people)', medium:'Medium (8–12 people)', large:'Large (15+ people)' };
    const emojis  = { small:'', medium:'', large:'' };
    const colors  = { small:'#1565c0', medium:'#2e7d32', large:'#6a1b9a' };
    const bgs     = { small:'#e3f2fd', medium:'#e8f5e9', large:'#f3e5f5' };

    let html = '';
    let grandTotal = 0;
    let hasAny = false;

    sizes.forEach(size => {
        const roomQty = state[size] || 0;
        if (!roomQty) return;
        const section = document.getElementById('conf_equip_' + size);
        if (!section) return;

        let sectionTotal = 0;
        let rows = '';

        section.querySelectorAll('input[type="number"]').forEach(input => {
            const qty = parseFloat(input.value) || 0;
            if (!qty) return;
            hasAny = true;
            const price    = parseFloat(input.getAttribute('data-price')) || 0;
            const rowTotal = qty * price;
            sectionTotal  += rowTotal;
            const row      = input.closest('tr');
            const fullText = row?.querySelector('td:first-child')?.textContent.trim() || '';
            let itemName = fullText, desc = '';
            const m = fullText.match(/^(.*?)\s*\((.*?)\)$/);
            if (m) { itemName = m[1].trim(); desc = m[2].trim(); }

            rows += `<tr style="border-bottom:1px solid #f0f0f0;">
                <td style="padding:9px 12px;font-weight:500;color:#2d3748;">${itemName}${desc ? `<br><small style="color:#888;font-weight:400;">${desc}</small>` : ''}</td>
                <td style="padding:9px 12px;text-align:center;color:#555;">${qty}</td>
                <td style="padding:9px 12px;text-align:right;color:#666;">${confFormatCurrency(price)}</td>
                <td style="padding:9px 12px;text-align:right;font-weight:700;color:${colors[size]};">${confFormatCurrency(rowTotal)}</td>
            </tr>`;
        });

        grandTotal += sectionTotal;

        html += `<div style="margin-bottom:1.5rem;">
            <div style="font-weight:700;font-size:1rem;color:${colors[size]};padding:10px 12px;
                        border-radius:8px;margin-bottom:8px;background:${bgs[size]};">
                ${emojis[size]} ${labels[size]} — ${roomQty} room${roomQty > 1 ? 's' : ''}
            </div>
            <table style="width:100%;border-collapse:collapse;font-size:0.875rem;">
                <thead style="background:#f8f9fa;">
                    <tr>
                        <th style="padding:9px 12px;text-align:left;color:#555;font-weight:600;">Item</th>
                        <th style="padding:9px 12px;text-align:center;color:#555;font-weight:600;width:50px;">Qty</th>
                        <th style="padding:9px 12px;text-align:right;color:#555;font-weight:600;width:110px;">Unit Price</th>
                        <th style="padding:9px 12px;text-align:right;color:#555;font-weight:600;width:110px;">Total</th>
                    </tr>
                </thead>
                <tbody>${rows || '<tr><td colspan="4" style="padding:12px;color:#999;text-align:center;font-style:italic;">No items with qty &gt; 0</td></tr>'}</tbody>
                <tfoot style="border-top:2px solid #e0e0e0;">
                    <tr>
                        <td colspan="3" style="padding:9px 12px;text-align:right;font-weight:700;color:${colors[size]};">${labels[size]} Subtotal:</td>
                        <td style="padding:9px 12px;text-align:right;font-weight:800;font-size:1rem;color:${colors[size]};">${confFormatCurrency(sectionTotal)}</td>
                    </tr>
                </tfoot>
            </table>
        </div>`;
    });

    if (!hasAny) {
        html = '<div style="padding:2rem;text-align:center;color:#999;font-style:italic;">No rooms selected or no items with quantity &gt; 0</div>';
    }

    container.innerHTML = html;
    document.getElementById('cr-equipment-total').textContent = confFormatCurrency(grandTotal);
    return grandTotal;
}

// ── Gather: Notes ─────────────────────────────────────────────
function crGatherNotes() {
    const notes = document.querySelector('textarea[name="conference_notes"]')?.value || '';
    document.getElementById('cr-notes').textContent = notes || 'No additional notes provided.';
}

// ── Calculate Totals ──────────────────────────────────────────
function crCalculateTotals() {
    // Strip any currency symbol (RM, $, €) before parsing
    const equipTotal = parseFloat(
        document.getElementById('cr-equipment-total').textContent.replace(/[^0-9.]/g, '')
    ) || 0;

    const subtotal    = equipTotal;
    const installation = subtotal * 0.05;
    const pm          = subtotal * 0.10;
    const contingency = subtotal * 0.15;
    const grandTotal  = subtotal + installation + pm + contingency;

    crAnimate('cr-summary-equipment',   0, equipTotal,   800);
    crAnimate('cr-summary-subtotal',    0, subtotal,     900);
    crAnimate('cr-summary-installation',0, installation, 1000);
    crAnimate('cr-summary-pm',          0, pm,           1000);
    crAnimate('cr-summary-contingency', 0, contingency,  1000);
    crAnimate('cr-summary-grand-total', 0, grandTotal,   1200);
}

function crAnimate(id, start, end, duration) {
    const el = document.getElementById(id);
    if (!el) return;
    const range = end - start;
    const step  = range / (duration / 16);
    let cur = start;
    const t = setInterval(() => {
        cur += step;
        if ((step > 0 && cur >= end) || (step < 0 && cur <= end)) {
            cur = end;
            clearInterval(t);
        }
        el.textContent = confFormatCurrency(cur);
    }, 16);
}

// ── Print ─────────────────────────────────────────────────────
function printConferenceReport() { window.print(); }

// ── PDF helpers ───────────────────────────────────────────────
function crInitPDF() {
    return new Promise((resolve, reject) => {
        const { jsPDF } = window.jspdf;
        const doc       = new jsPDF();
        const pageWidth  = doc.internal.pageSize.getWidth();
        const pageHeight = doc.internal.pageSize.getHeight();
        const margin     = 15;
        const boxWidth   = 180;
        const boxX       = (pageWidth - boxWidth) / 2;
        const boxY       = margin;

        function drawPageBackground() {
            doc.setFillColor(245, 255, 250);
            doc.roundedRect(boxX, boxY, boxWidth, pageHeight - 2 * margin, 5, 5, 'F');
        }
        drawPageBackground();

        // Title (no logo needed — keep simple)
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(17);
        doc.text('Conference Room Report', pageWidth / 2, boxY + 15, { align: 'center' });

        const startY    = boxY + 28;
        const projectName = document.getElementById('cr-project-name').textContent;

        resolve({ doc, pageWidth, pageHeight, boxX, boxWidth, drawPageBackground, startY, projectName });
    });
}

function crRenderProjectInfo(doc, y, boxX, boxWidth, pageWidth, drawBg) {
    doc.autoTable({
        startY: y,
        head: [['Project Name', 'Manager', 'Deployment Date', 'Users']],
        body: [[
            document.getElementById('cr-project-name').textContent,
            document.getElementById('cr-manager').textContent,
            document.getElementById('cr-deployment').textContent,
            document.getElementById('cr-users').textContent,
        ]],
        styles:     { font: 'helvetica', halign: 'center', fontSize: 9 },
        headStyles: { fillColor: [128, 199, 160], textColor: 255 },
        theme: 'grid',
        margin: { left: boxX + 5, right: pageWidth - boxX - boxWidth + 5 },
        didAddPage: () => drawBg()
    });
    return doc.lastAutoTable.finalY + 8;
}

function crRenderRoomInfo(doc, y, boxX, boxWidth, pageWidth, drawBg) {
    const labels = { small:'Small (4–6 people)', medium:'Medium (8–12 people)', large:'Large (15+ people)' };
    const state  = window.confRoomState || {};
    const active = Object.keys(state).filter(s => state[s] > 0);
    const roomText = active.length
        ? active.map(s => `${labels[s]} x${state[s]}`).join(', ')
        : 'No rooms selected';
    const totalRooms = active.reduce((s,k) => s + state[k], 0);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(12);
    doc.text('Room Configuration', boxX + 5, y); y += 5;
    doc.autoTable({
        startY: y,
        head: [['Room Selection', 'Total Rooms']],
        body: [[ roomText, totalRooms + ' room(s)' ]],
        styles:     { font:'helvetica', halign:'center', fontSize:9 },
        headStyles: { fillColor:[0,112,239], textColor:255 },
        theme: 'grid',
        margin: { left: boxX + 5, right: pageWidth - boxX - boxWidth + 5 },
        didAddPage: () => drawBg()
    });
    return doc.lastAutoTable.finalY + 8;
}

function crRenderAV(doc, y, boxX, boxWidth, pageWidth, drawBg) {
    const rows = [];
    document.querySelectorAll('#cr-av-config .report-table tr').forEach(tr => {
        const cells = tr.querySelectorAll('td');
        if (cells.length === 2) rows.push([cells[0].textContent.trim(), cells[1].textContent.trim()]);
    });
    if (rows.length === 0) return y;
    doc.setFont('helvetica', 'bold'); doc.setFontSize(12);
    doc.text('AV & Connectivity', boxX + 5, y); y += 5;
    doc.autoTable({
        startY: y, head: [['Requirement', 'Selection']], body: rows,
        styles: { font: 'helvetica', fontSize: 9 },
        headStyles: { fillColor: [35, 57, 93], textColor: 255 },
        margin: { left: boxX + 5, right: pageWidth - boxX - boxWidth + 5 },
        didAddPage: () => drawBg()
    });
    return doc.lastAutoTable.finalY + 8;
}

function crRenderEquipment(doc, y, boxX, boxWidth, pageWidth, drawBg, withPrices) {
    const rows = [];
    document.querySelectorAll('#cr-equipment .report-table tr').forEach(tr => {
        const cells = tr.querySelectorAll('td');
        if (cells.length === 3) {
            rows.push(withPrices
                ? [cells[0].textContent.trim(), cells[1].textContent.trim(), cells[2].textContent.trim()]
                : [cells[0].textContent.trim(), cells[1].textContent.trim()]);
        }
    });
    doc.setFont('helvetica', 'bold'); doc.setFontSize(12);
    doc.text('Equipment & Connectivity', boxX + 5, y); y += 5;
    if (rows.length > 0) {
        const head    = withPrices ? [['Item', 'Description', 'Cost']] : [['Item', 'Description']];
        const colStyles = withPrices
            ? { 0: { cellWidth: 50 }, 1: { cellWidth: 95 }, 2: { cellWidth: 25 } }
            : { 0: { cellWidth: 60 }, 1: { cellWidth: 110 } };
        doc.autoTable({
            startY: y, head, body: rows,
            styles: { font: 'helvetica', fontSize: 9 },
            headStyles: { fillColor: [128, 199, 160], textColor: 255 },
            columnStyles: colStyles,
            margin: { left: boxX + 5, right: pageWidth - boxX - boxWidth + 5 },
            didAddPage: () => drawBg()
        });
        y = doc.lastAutoTable.finalY + 5;
    }
    if (withPrices) {
        const total = document.getElementById('cr-equipment-total').textContent;
        doc.setFont('helvetica', 'bold'); doc.setFontSize(10);
        doc.text(`Equipment Subtotal: ${total}`, boxX + boxWidth - 5, y, { align: 'right' });
        y += 10;
    }
    return y;
}

function crRenderCostSummary(doc, y, pageHeight, pageWidth, drawBg) {
    if (80 > pageHeight - y - 20) { doc.addPage(); drawBg(); y = 20; }
    doc.setFont('helvetica', 'bold'); doc.setFontSize(14);
    doc.text('Cost Summary', pageWidth / 2, y, { align: 'center' }); y += 8;
    doc.autoTable({
        startY: y,
        body: [
            ['Equipment & Connectivity', document.getElementById('cr-summary-equipment').textContent],
            ['Subtotal (Hardware)',       document.getElementById('cr-summary-subtotal').textContent],
            ['Installation Service (5%)',document.getElementById('cr-summary-installation').textContent],
            ['Project Management (10%)', document.getElementById('cr-summary-pm').textContent],
            ['Contingency Buffer (15%)', document.getElementById('cr-summary-contingency').textContent],
            ['GRAND TOTAL',              document.getElementById('cr-summary-grand-total').textContent],
        ],
        styles:       { font: 'helvetica', fontSize: 10 },
        columnStyles: { 0: { fontStyle: 'bold', cellWidth: 130 }, 1: { halign: 'right', fontStyle: 'bold', textColor: [128, 199, 160] } },
        theme: 'grid',
        margin: { left: 20, right: 20 },
        didAddPage: () => drawBg()
    });
    return doc.lastAutoTable.finalY + 10;
}

function crRenderNotes(doc, y, pageWidth) {
    const notes = document.getElementById('cr-notes').textContent;
    if (notes && notes !== 'No additional notes provided.') {
        doc.setFont('helvetica', 'bold'); doc.setFontSize(12);
        doc.text('Additional Notes:', 20, y); y += 7;
        doc.setFont('helvetica', 'normal'); doc.setFontSize(10);
        doc.text(doc.splitTextToSize(notes, pageWidth - 40), 20, y);
    }
}

// ── PDF Export: WITH Prices ───────────────────────────────────
async function exportConferencePDFWithPrices() {
    const ctx = await crInitPDF();
    const { doc, pageWidth, pageHeight, boxX, boxWidth, drawPageBackground, startY, projectName } = ctx;
    let y = startY;
    y = crRenderProjectInfo(doc, y, boxX, boxWidth, pageWidth, drawPageBackground);
    y = crRenderRoomInfo(doc, y, boxX, boxWidth, pageWidth, drawPageBackground);
    y = crRenderAV(doc, y, boxX, boxWidth, pageWidth, drawPageBackground);
    y = crRenderEquipment(doc, y, boxX, boxWidth, pageWidth, drawPageBackground, true);
    y = crRenderCostSummary(doc, y, pageHeight, pageWidth, drawPageBackground);
    crRenderNotes(doc, y, pageWidth);
    doc.save(`Conference_Room_Report_${projectName.replace(/\s+/g,'_')}_With_Prices.pdf`);
}

// ── PDF Export: WITHOUT Prices ────────────────────────────────
async function exportConferencePDFNoPrices() {
    const ctx = await crInitPDF();
    const { doc, pageWidth, pageHeight, boxX, boxWidth, drawPageBackground, startY, projectName } = ctx;
    let y = startY;
    y = crRenderProjectInfo(doc, y, boxX, boxWidth, pageWidth, drawPageBackground);
    y = crRenderRoomInfo(doc, y, boxX, boxWidth, pageWidth, drawPageBackground);
    y = crRenderAV(doc, y, boxX, boxWidth, pageWidth, drawPageBackground);
    y = crRenderEquipment(doc, y, boxX, boxWidth, pageWidth, drawPageBackground, false);
    crRenderNotes(doc, y, pageWidth);
    doc.save(`Conference_Room_Report_${projectName.replace(/\s+/g,'_')}_Without_Prices.pdf`);
}

console.log('✅ conference_report_core.js loaded');