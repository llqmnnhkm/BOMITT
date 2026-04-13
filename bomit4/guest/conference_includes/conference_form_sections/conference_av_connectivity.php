<?php
// guest/conference_includes/conference_form_sections/conference_av_connectivity.php
// Section 3: AV & Connectivity Preferences
?>

<div class="question-group" style="margin-bottom: 2rem;">
    <label style="font-size: 1.125rem; font-weight: 600; display:block; margin-bottom: 0.25rem;">
        AV & Connectivity Preferences
    </label>
    <p style="font-size: 0.875rem; color: #666; margin: 0 0 1rem 0;">
        Specify AV and connectivity requirements for the room. These inform installation scope.
    </p>

    <table style="width:100%; border-collapse:collapse; border-radius:12px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.1);">
        <thead style="background: linear-gradient(90deg, #80c7a0 0%, #0070ef 100%); color:white;">
            <tr>
                <th style="padding:12px; text-align:left; font-size:0.95rem;">Requirement</th>
                <th style="padding:12px; text-align:center; width:220px; font-size:0.95rem;">Selection</th>
                <th style="padding:12px; text-align:center; width:140px; font-size:0.95rem;">Required?</th>
            </tr>
        </thead>
        <tbody style="background:white;">

            <!-- Display / Projection -->
            <tr style="border-bottom:1px solid #e0e0e0;">
                <td style="padding:12px; font-weight:500; color:#2d3748;">
                    Display / Projection
                    <br><small style="color:#999; font-weight:400;">Primary visual output for the room</small>
                </td>
                <td style="padding:12px; text-align:center;">
                    <select name="av_display_type"
                            style="width:200px; padding:8px 10px; border:1px solid #ccc; border-radius:6px; font-family:Montserrat; font-size:0.9rem;">
                        <option value="">-- Select --</option>
                        <option value="flat_screen">Flat Screen / Smart TV</option>
                        <option value="projector">Projector + Screen</option>
                        <option value="dual_screen">Dual Screen Setup</option>
                        <option value="interactive">Interactive Whiteboard</option>
                        <option value="none">None</option>
                    </select>
                </td>
                <td style="padding:12px; text-align:center;">
                    <label style="cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px;">
                        <input type="checkbox" name="av_display_required" value="yes" style="width:18px; height:18px;">
                        <span style="font-size:0.85rem; color:#2e7d32; font-weight:600;">Include</span>
                    </label>
                </td>
            </tr>

            <!-- Video Conferencing -->
            <tr style="border-bottom:1px solid #e0e0e0; background:#fafafa;">
                <td style="padding:12px; font-weight:500; color:#2d3748;">
                    Video Conferencing System
                    <br><small style="color:#999; font-weight:400;">Camera + mic for Teams / Zoom / WebEx</small>
                </td>
                <td style="padding:12px; text-align:center;">
                    <select name="av_vc_platform"
                            style="width:200px; padding:8px 10px; border:1px solid #ccc; border-radius:6px; font-family:Montserrat; font-size:0.9rem;">
                        <option value="">-- Select Platform --</option>
                        <option value="ms_teams">Microsoft Teams Room</option>
                        <option value="zoom">Zoom Room</option>
                        <option value="webex">Cisco WebEx</option>
                        <option value="generic">Generic UC (camera + mic)</option>
                        <option value="none">None</option>
                    </select>
                </td>
                <td style="padding:12px; text-align:center;">
                    <label style="cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px;">
                        <input type="checkbox" name="av_vc_required" value="yes" style="width:18px; height:18px;">
                        <span style="font-size:0.85rem; color:#2e7d32; font-weight:600;">Include</span>
                    </label>
                </td>
            </tr>

            <!-- Wireless Presentation -->
            <tr style="border-bottom:1px solid #e0e0e0;">
                <td style="padding:12px; font-weight:500; color:#2d3748;">
                    Wireless Presentation
                    <br><small style="color:#999; font-weight:400;">Wireless screen sharing (HDMI-free)</small>
                </td>
                <td style="padding:12px; text-align:center;">
                    <select name="av_wireless_type"
                            style="width:200px; padding:8px 10px; border:1px solid #ccc; border-radius:6px; font-family:Montserrat; font-size:0.9rem;">
                        <option value="">-- Select --</option>
                        <option value="clickshare">Barco ClickShare</option>
                        <option value="airtame">Airtame</option>
                        <option value="miracast">Miracast / WiDi</option>
                        <option value="apple_tv">Apple TV / AirPlay</option>
                        <option value="none">None (HDMI only)</option>
                    </select>
                </td>
                <td style="padding:12px; text-align:center;">
                    <label style="cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px;">
                        <input type="checkbox" name="av_wireless_required" value="yes" style="width:18px; height:18px;">
                        <span style="font-size:0.85rem; color:#2e7d32; font-weight:600;">Include</span>
                    </label>
                </td>
            </tr>

            <!-- HDMI / Cable Connections -->
            <tr style="border-bottom:1px solid #e0e0e0; background:#fafafa;">
                <td style="padding:12px; font-weight:500; color:#2d3748;">
                    Wired Connections (HDMI / USB-C)
                    <br><small style="color:#999; font-weight:400;">Tabletop / wall-plate cable drops</small>
                </td>
                <td style="padding:12px; text-align:center;">
                    <select name="av_wired_drops"
                            style="width:200px; padding:8px 10px; border:1px solid #ccc; border-radius:6px; font-family:Montserrat; font-size:0.9rem;">
                        <option value="">-- Select --</option>
                        <option value="1">1 drop</option>
                        <option value="2">2 drops</option>
                        <option value="4">4 drops</option>
                        <option value="6">6 drops</option>
                        <option value="none">None</option>
                    </select>
                </td>
                <td style="padding:12px; text-align:center;">
                    <label style="cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px;">
                        <input type="checkbox" name="av_wired_required" value="yes" style="width:18px; height:18px;">
                        <span style="font-size:0.85rem; color:#2e7d32; font-weight:600;">Include</span>
                    </label>
                </td>
            </tr>

            <!-- Room Automation / Control -->
            <tr style="border-bottom:1px solid #e0e0e0;">
                <td style="padding:12px; font-weight:500; color:#2d3748;">
                    Room Automation / Control System
                    <br><small style="color:#999; font-weight:400;">One-touch AV and lighting control</small>
                </td>
                <td style="padding:12px; text-align:center;">
                    <select name="av_control_system"
                            style="width:200px; padding:8px 10px; border:1px solid #ccc; border-radius:6px; font-family:Montserrat; font-size:0.9rem;">
                        <option value="">-- Select --</option>
                        <option value="crestron">Crestron</option>
                        <option value="amx">AMX</option>
                        <option value="extron">Extron</option>
                        <option value="basic">Basic IR / RF Control</option>
                        <option value="none">None</option>
                    </select>
                </td>
                <td style="padding:12px; text-align:center;">
                    <label style="cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px;">
                        <input type="checkbox" name="av_control_required" value="yes" style="width:18px; height:18px;">
                        <span style="font-size:0.85rem; color:#2e7d32; font-weight:600;">Include</span>
                    </label>
                </td>
            </tr>

            <!-- Network / Internet -->
            <tr style="border-bottom:1px solid #e0e0e0; background:#fafafa;">
                <td style="padding:12px; font-weight:500; color:#2d3748;">
                    Network / Internet Connectivity
                    <br><small style="color:#999; font-weight:400;">Wired LAN drops and/or Wi-Fi AP</small>
                </td>
                <td style="padding:12px; text-align:center;">
                    <select name="av_network_type"
                            style="width:200px; padding:8px 10px; border:1px solid #ccc; border-radius:6px; font-family:Montserrat; font-size:0.9rem;">
                        <option value="">-- Select --</option>
                        <option value="wired_only">Wired LAN Only</option>
                        <option value="wifi_only">Wi-Fi Only</option>
                        <option value="both">Wired + Wi-Fi</option>
                        <option value="none">None</option>
                    </select>
                </td>
                <td style="padding:12px; text-align:center;">
                    <label style="cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px;">
                        <input type="checkbox" name="av_network_required" value="yes" style="width:18px; height:18px;">
                        <span style="font-size:0.85rem; color:#2e7d32; font-weight:600;">Include</span>
                    </label>
                </td>
            </tr>

        </tbody>
    </table>
</div>
