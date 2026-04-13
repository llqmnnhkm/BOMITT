// network_includes/js/network_report_pdf_no_prices.js
// ============================================================
// PDF EXPORT — WITHOUT PRICES
// Depends on: network_report_core.js (must be loaded first)
//
// To edit item layout, column widths, what sections appear → edit here.
// No cost columns, no subtotals, no Cost Summary section.
// ============================================================

async function exportNetworkPDFNoPrices() {
    console.log('📄 Exporting PDF WITHOUT prices...');

    let ctx;
    try {
        ctx = await initPDFDocument();
    } catch (err) {
        console.error('❌', err.message);
        alert('Failed to load logo. PDF export cancelled.');
        return;
    }

    const { doc, pageWidth, pageHeight, boxX, boxWidth, drawPageBackground, startY, projectName } = ctx;

    let y = startY;

    // 1. Project Info
    y = renderProjectInfoTable(doc, y, boxX, boxWidth, pageWidth, drawPageBackground);

    // 2. Network Configuration (no prices, no installation subtotal)
    y = renderNetworkConfigTable(doc, y, boxX, boxWidth, pageWidth, drawPageBackground, false);

    // 3. Equipment & Modules (no prices)
    y = renderEquipmentTable(doc, y, boxX, boxWidth, pageWidth, drawPageBackground, false);

    // 4. Cables & Accessories (no prices, smart page break built-in)
    y = renderCablesTable(doc, y, pageHeight, boxX, boxWidth, pageWidth, drawPageBackground, false);

    // 5. Additional Notes (no Cost Summary section)
    if (y < 30) y = 30;
    renderNotes(doc, y, pageWidth);

    // Save
    const fileName = `Network_Infrastructure_Report_${projectName.replace(/\s+/g, '_')}_Without_Prices.pdf`;
    doc.save(fileName);
    console.log('✅ PDF saved:', fileName);
}