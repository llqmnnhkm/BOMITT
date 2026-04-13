<?php
// guest/conference_includes/conference_report.php
// Conference Room Report Modal — mirrors network_report.php structure
?>

<!-- Conference Report CSS (reuse network_report.css for shared styles) -->
<link rel="stylesheet" href="css/network_report.css">

<style>
/* Conference-specific overrides */
#conference-report-modal .report-header {
    background: linear-gradient(135deg, #80c7a0 0%, #0070ef 100%);
}
#conference-report-modal .section-header {
    color: #2e7d32;
}
#conference-report-modal .section-header::after {
    background: linear-gradient(90deg, #80c7a0, #0070ef);
}
#conference-report-modal .subtotal-label,
#conference-report-modal .subtotal-value { color: #2e7d32; }
#conference-report-modal .subtotal-box {
    background: linear-gradient(90deg, rgba(128,199,160,0.15) 0%, rgba(0,112,239,0.1) 100%);
}
#conference-report-modal .info-grid {
    background: linear-gradient(135deg, #f1f8f4 0%, #e8f4f8 100%);
    box-shadow: 0 4px 12px rgba(128,199,160,0.2);
}
#conference-report-modal .grand-total-row {
    background: linear-gradient(135deg, #80c7a0 0%, #0070ef 100%) !important;
}
#conference-report-modal .btn-export-with-price {
    background: linear-gradient(135deg, #80c7a0 0%, #4caf50 100%);
}
#conference-report-modal .btn-export-no-price {
    background: linear-gradient(135deg, #0070ef 0%, #4A90E2 100%);
}
</style>

<!-- Modal -->
<div id="conference-report-modal">
    <div class="report-container">

        <!-- Header -->
        <div class="report-header">
            <div class="report-header-content">
                <div>
                    <h2 class="report-title">Conference Room Report</h2>
                    <p class="report-subtitle">Equipment Configuration & Cost Breakdown</p>
                </div>
                <button onclick="closeConferenceReport()" class="close-btn">×</button>
            </div>
        </div>

        <!-- Body -->
        <div class="report-body">

            <!-- Project Info -->
            <section class="report-section">
                <h3 class="section-header">Project Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Project Name</div>
                        <div id="cr-project-name" class="info-value">-</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Requesting Manager</div>
                        <div id="cr-manager" class="info-value">-</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Deployment Date</div>
                        <div id="cr-deployment" class="info-value">-</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Number of Users</div>
                        <div id="cr-users" class="info-value">-</div>
                    </div>
                </div>
            </section>

            <!-- Room Configuration -->
            <section class="report-section">
                <h3 class="section-header">Room Configuration</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Room Size</div>
                        <div id="cr-room-size" class="info-value">-</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Meeting Type</div>
                        <div id="cr-meeting-type" class="info-value">-</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Setup Type</div>
                        <div id="cr-setup-type" class="info-value">-</div>
                    </div>
                </div>
            </section>

            <!-- AV & Connectivity -->
            <section class="report-section">
                <h3 class="section-header">AV & Connectivity Selections</h3>
                <div id="cr-av-config" class="content-box">
                    <!-- JS populated -->
                </div>
            </section>

            <!-- Equipment -->
            <section class="report-section">
                <h3 class="section-header">Equipment & Connectivity Items</h3>
                <div id="cr-equipment" class="content-box"><!-- JS populated --></div>
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
                        <tr>
                            <td>Equipment & Connectivity</td>
                            <td id="cr-summary-equipment">$0.00</td>
                        </tr>
                        <tr class="subtotal-row">
                            <td>Subtotal (Hardware)</td>
                            <td id="cr-summary-subtotal">$0.00</td>
                        </tr>
                        <tr class="service-row">
                            <td>Installation Service (5% on Equipment)</td>
                            <td id="cr-summary-installation">$0.00</td>
                        </tr>
                        <tr class="service-row">
                            <td>Project Management (10%)</td>
                            <td id="cr-summary-pm">$0.00</td>
                        </tr>
                        <tr class="service-row contingency-row">
                            <td>Contingency Buffer (15%)</td>
                            <td id="cr-summary-contingency">$0.00</td>
                        </tr>
                        <tr class="grand-total-row">
                            <td>GRAND TOTAL</td>
                            <td id="cr-summary-grand-total">$0.00</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <!-- Actions -->
            <div class="action-buttons">
                <button onclick="exportConferencePDFWithPrices()" class="btn btn-export-with-price">
                    💰 Export PDF (With Prices)
                </button>
                <button onclick="exportConferencePDFNoPrices()" class="btn btn-export-no-price">
                    📄 Export PDF (Without Prices)
                </button>
                <button onclick="exportConferenceExcel()" class="btn btn-export-excel"
                    style="background:linear-gradient(90deg,#217346,#1a5c38);color:white;border:none;padding:10px 20px;border-radius:8px;cursor:pointer;font-family:Montserrat,sans-serif;font-weight:600;font-size:.9rem;display:inline-flex;align-items:center;gap:8px;">
                    📊 Export Excel
                </button>
                <button onclick="printConferenceReport()" class="btn btn-print">
                    🖨️ Print Report
                </button>
                <button onclick="closeConferenceReport()" class="btn btn-close">
                    ✕ Close
                </button>
            </div>

        </div>
    </div>
</div>

<style>
#conference-report-modal {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.7);
    backdrop-filter: blur(5px);
    z-index: 9999;
    overflow-y: auto;
    animation: fadeIn 0.3s ease-out;
}
</style>