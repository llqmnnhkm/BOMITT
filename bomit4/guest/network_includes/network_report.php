<?php
// network_includes/network_report.php
// Network Infrastructure Report Generator
// Displays comprehensive summary with pricing breakdown

// Include CSS and JS files
?>

<!-- Network Report CSS -->
<link rel="stylesheet" href="network_includes/css/network_report.css">

<!-- Network Report JS (load order matters: core must come first) -->
<script src="network_includes/js/network_report_core.js"></script>
<script src="network_includes/js/network_report_pdf_with_prices.js"></script>
<script src="network_includes/js/network_report_pdf_no_prices.js"></script>

<!-- Modal Structure -->
<div id="network-report-modal">
    <div class="report-container">
        
        <!-- Report Header -->
        <div class="report-header">
            <div class="report-header-content">
                <div>
                    <h2 class="report-title">Network Infrastructure Report</h2>
                    <p class="report-subtitle">Comprehensive Configuration & Cost Breakdown</p>
                </div>
                <button onclick="closeNetworkReport()" class="close-btn">×</button>
            </div>
        </div>

        <!-- Report Body -->
        <div class="report-body">
            
            <!-- Project Information -->
            <section class="report-section">
                <h3 class="section-header">
                    Project Information
                </h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Project Name</div>
                        <div id="report-project-name" class="info-value">-</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Location</div>
                        <div id="report-location" class="info-value">-</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Number of Users</div>
                        <div id="report-users" class="info-value">-</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Site Configuration</div>
                        <div id="report-site-type" class="info-value">-</div>
                    </div>
                </div>
            </section>

            <!-- Network Infrastructure Configuration -->
            <section class="report-section">
                <h3 class="section-header">
                    Network Infrastructure Configuration
                </h3>
                <div id="report-network-config" class="content-box">
                    <!-- Populated by JavaScript -->
                </div>
                
                <!-- ✅ ADD: Separate Installation Services Display -->
                <div id="report-installation-services" style="margin-top: 15px; display: none;">
                    <h4 style="font-size: 1rem; font-weight: 600; color: #388e3c; margin-bottom: 10px; padding-left: 10px; border-left: 4px solid #4caf50;">
                         Installation Services Included
                    </h4>
                    <div id="installation-services-list" class="content-box" style="background: linear-gradient(135deg, #e8f5e9 0%, #f1f8f4 100%);">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>
                
                <div class="subtotal-box">
                    <div class="subtotal-label">Network Configuration Subtotal</div>
                    <div id="report-network-total" class="subtotal-value">$0.00</div>
                </div>
            </section>

            <!-- Equipment & Modules -->
            <section class="report-section">
                <h3 class="section-header">
                    Equipment & Modules
                </h3>
                <div id="report-equipment" class="content-box">
                    <!-- Populated by JavaScript -->
                </div>
                <div class="subtotal-box">
                    <div class="subtotal-label">Equipment & Modules Subtotal</div>
                    <div id="report-equipment-total" class="subtotal-value">$0.00</div>
                </div>
            </section>

            <!-- Cables & Accessories -->
            <section class="report-section">
                <h3 class="section-header">
                    Cables & Accessories
                </h3>
                <div id="report-cables" class="content-box">
                    <!-- Populated by JavaScript -->
                </div>
                <div class="subtotal-box">
                    <div class="subtotal-label">Cables & Accessories Subtotal</div>
                    <div id="report-cables-total" class="subtotal-value">$0.00</div>
                </div>
            </section>

            <!-- Additional Notes -->
            <section class="report-section">
                <h3 class="section-header">
                    Additional Notes
                </h3>
                <div id="report-notes" class="notes-box">
                    No additional notes provided.
                </div>
            </section>

            <!-- Cost Summary -->
            <section class="report-section">
                <h3 class="section-header">
                    Cost Summary
                </h3>
                <table class="summary-table">
                    <tbody>
                        <tr>
                            <td>Network Infrastructure Configuration</td>
                            <td id="summary-network">$0.00</td>
                        </tr>
                        <tr>
                            <td>Equipment & Modules</td>
                            <td id="summary-equipment">$0.00</td>
                        </tr>
                        <tr>
                            <td>Cables & Accessories</td>
                            <td id="summary-cables">$0.00</td>
                        </tr>
                        <tr class="subtotal-row">
                            <td>Subtotal (Hardware & Configuration)</td>
                            <td id="summary-subtotal">$0.00</td>
                        </tr>
                        <tr class="service-row">
                            <td>Installation Service (5% on Equipment & Cables)</td>
                            <td id="summary-installation">$0.00</td>
                        </tr>
                        <tr class="service-row">
                            <td>Project Management (10%)</td>
                            <td id="summary-pm">$0.00</td>
                        </tr>
                        <tr class="service-row contingency-row">
                            <td>Contingency Buffer (15%)</td>
                            <td id="summary-contingency">$0.00</td>
                        </tr>
                        <tr class="grand-total-row">
                            <td>GRAND TOTAL</td>
                            <td id="summary-grand-total">$0.00</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button onclick="exportNetworkPDFWithPrices()" class="btn btn-export-with-price">
                     Export PDF (With Prices)
                </button>
                <button onclick="exportNetworkPDFNoPrices()" class="btn btn-export-no-price">
                     Export PDF (Without Prices)
                </button>
                <button onclick="printNetworkReport()" class="btn btn-print">
                     Print Report
                </button>
                <button onclick="closeNetworkReport()" class="btn btn-close">
                    ✕ Close
                </button>
            </div>

        </div>
    </div>
</div>