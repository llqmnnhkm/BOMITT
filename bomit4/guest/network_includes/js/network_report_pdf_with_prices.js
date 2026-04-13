// network_includes/js/network_report_pdf_with_prices.js
// ============================================================
// PDF EXPORT — WITH PRICES
// Depends on: network_report_core.js (must be loaded first)
//
// To edit pricing layout, cost summary, subtotal lines → edit here.
// ============================================================

async function exportNetworkPDFWithPrices() {
    console.log('📄 Exporting PDF WITH prices...');

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

    // 2. Network Configuration (includes installation services subtotal)
    y = renderNetworkConfigTable(doc, y, boxX, boxWidth, pageWidth, drawPageBackground, true);

    // 3. Equipment & Modules
    y = renderEquipmentTable(doc, y, boxX, boxWidth, pageWidth, drawPageBackground, true);

    // 4. Cables & Accessories (smart page break built-in)
    y = renderCablesTable(doc, y, pageHeight, boxX, boxWidth, pageWidth, drawPageBackground, true);

    // 5. Cost Summary (smart page break built-in)
    y = renderCostSummary(doc, y, pageHeight, pageWidth, drawPageBackground);

    // 6. Additional Notes
    renderNotes(doc, y, pageWidth);

    // Save
    const fileName = `Network_Infrastructure_Report_${projectName.replace(/\s+/g, '_')}_With_Prices.pdf`;
    doc.save(fileName);
    console.log('✅ PDF saved:', fileName);
}