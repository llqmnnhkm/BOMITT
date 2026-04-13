<?php

// ----------------------
// Handle AJAX update
// ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['field'], $_POST['value'])) {
    $allowedFields = [
        'project_name','requesting_manager','project_duration','deployment_date','user_quantity',
        'storage','totalCoreCount','totalMemory','future_needs','cores_required','memory_required',
        'hosts','ftt','cores_per_host','vratio','memory_per_host','cores_provided','memory_provided',
        'memory_spare','current_requirements','total_logical','physical_optimized','rec_physical','rec'
    ];

    // Allow dynamic fields
    if (preg_match('/^(corecount_|memory_|storage_|notes_)\d+$/', $_POST['field'])) {
        $allowedFields[] = $_POST['field'];
    }

    if (in_array($_POST['field'], $allowedFields)) {
        $_SESSION[$_POST['field']] = $_POST['value'];
        echo json_encode(['status'=>'success']);
    } else {
        echo json_encode(['status'=>'error','message'=>'Invalid field']);
    }
    exit;
}

// ----------------------
// Load prefilled values from session or set defaults
// ----------------------
$project_name       = $_SESSION['project_name'] ?? '';
$requesting_manager = $_SESSION['requesting_manager'] ?? '';
$project_duration   = $_SESSION['project_duration'] ?? 0;
$deployment_date    = $_SESSION['deployment_date'] ?? '';
$user_quantity      = $_SESSION['user_quantity'] ?? 0;
$storage            = $_SESSION['storage'] ?? 0;

$totalCoreCount     = $_SESSION['totalCoreCount'] ?? 0;
$totalMemory        = $_SESSION['totalMemory'] ?? 0;
$future_needs       = $_SESSION['future_needs'] ?? 1.3;
$cores_required     = $_SESSION['cores_required'] ?? 0;
$memory_required    = $_SESSION['memory_required'] ?? 0;
$hosts              = $_SESSION['hosts'] ?? 3;
$ftt                = $_SESSION['ftt'] ?? 1;

$cores_per_host     = $_SESSION['cores_per_host'] ?? 16;
$vratio             = $_SESSION['vratio'] ?? 4;
$memory_per_host    = $_SESSION['memory_per_host'] ?? 128;
$cores_provided     = $_SESSION['cores_provided'] ?? 0;
$memory_provided    = $_SESSION['memory_provided'] ?? 0;
$memory_spare       = $_SESSION['memory_spare'] ?? 0;

$current_requirements   = $_SESSION['current_requirements'] ?? 0;
$total_logical          = $_SESSION['total_logical'] ?? 0;
$physical_optimized     = $_SESSION['physical_optimized'] ?? 0;
$rec_physical           = $_SESSION['rec_physical'] ?? 0;
$rec                    = $_SESSION['rec'] ?? '';

// ----------------------
// Compute values safely if POST exists
// ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Compute cores/memory for future needs
    $cores_required  = $totalCoreCount * $future_needs;
    $memory_required = $totalMemory * $future_needs;
    $cores_provided  = ($hosts - $ftt) * ($cores_per_host * $vratio);
    $cores_spare     = $cores_provided - $cores_required;
    $memory_provided = ($hosts - $ftt) * $memory_per_host;
    $memory_spare    = $memory_provided - $memory_required;

    // Storage sizing
    $current_requirements = ($totalOSStorage ?? 0 + $totalDataStorage ?? 0)/1000;

    $growth          = 0.10;
    $change_rate     = 0.05;
    $ret_days_policy = 7;
    $rweeks          = 5;
    $rmonths         = 6;
    $ddratio         = 20;

    $future_source      = $current_requirements * pow(1 + $growth, $project_duration);
    $full_size          = $future_source;
    $inc_size           = $future_source * $change_rate;
    $totalGFS           = $rweeks + $rmonths;
    $number_of_fulls    = max(1, $totalGFS - 1);
    $total_fulls_vol    = $number_of_fulls * $full_size;
    $total_incs_vol     = ($ret_days_policy - 1) * $inc_size;
    $total_logical      = round(($total_fulls_vol +  $total_incs_vol) * 1.03, 2);
    $physical_optimized = round($total_logical / $ddratio, 2);
    $rec_physical       = round(ceil($physical_optimized), 2);

    if ($current_requirements === 0) $rec = "Enter source data...";
    elseif ($physical_optimized < 256) $rec = "PowerProtect DD6410";
    else $rec = "PowerProtect DD9410 / DD9910";

    // Store back to session
    $_SESSION['cores_required']       = $cores_required;
    $_SESSION['memory_required']      = $memory_required;
    $_SESSION['cores_provided']       = $cores_provided;
    $_SESSION['cores_spare']          = $cores_spare;
    $_SESSION['memory_provided']      = $memory_provided;
    $_SESSION['memory_spare']         = $memory_spare;
    $_SESSION['current_requirements'] = $current_requirements;
    $_SESSION['total_logical']        = $total_logical;
    $_SESSION['physical_optimized']   = $physical_optimized;
    $_SESSION['rec_physical']         = $rec_physical;
    $_SESSION['rec']                  = $rec;
}
?>


<div id="container-server" class="question-container">
    <div class="container-header">
        <div class="container-icon server">🖥️</div>
        <div class="container-title">
            <h3>Server Infrastructure</h3>
            <p>Configure your server requirements</p>
        </div>
    </div>

    <form id="serverForm" method="POST" action="server_summary.php">
    
        <div class="top-textboxes" style="display: flex; flex-wrap: wrap; gap: 1.5rem; margin-bottom: 2rem;">
            <div class="textbox-item" style="flex: 1; min-width: 250px;">
                <label style="font-weight: 600; display: block; margin-bottom: 0.5rem;">
                    Project Name: <span class="required">*</span>
                </label>
                <input type="text" name="project_name" 
                value="<?php echo htmlspecialchars($project_name); ?>" 
                style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-family: Montserrat; font-size: 1rem;">
            </div>

            <div class="textbox-item" style="flex: 1; min-width: 250px;">
                <label style="font-weight: 600; display: block; margin-bottom: 0.5rem;">
                    Requesting Manager: <span class="required">*</span>
                </label>
                <input type="text" name="requesting_manager" 
                value="<?php echo htmlspecialchars($requesting_manager); ?>" 
                    style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-family: Montserrat; font-size: 1rem;">
            </div>

            <div class="textbox-item" style="flex: 1; min-width: 250px;">
    <label style="font-weight: 600; display: block; margin-bottom: 0.5rem;">
        Project Duration (In Months): <span class="required">*</span>
    </label>
    <input type="number" name="project_duration" 
        min="1" 
        value="<?php echo htmlspecialchars($project_duration); ?>"
        style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-family: Montserrat; font-size: 1rem;">
</div>


            <div class="textbox-item" style="flex: 1; min-width: 250px;">
                <label style="font-weight: 600; display: block; margin-bottom: 0.5rem;">
                Required Deployment Date: <span class="required">*</span>
                </label>
                <input type="date" name="deployment_date" 
                    min="<?php echo date('Y-m-d'); ?>" 
                    value="<?php echo htmlspecialchars($deployment_date); ?>" 

                    style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-family: Montserrat; font-size: 1rem;">
            </div>

        <div class="textbox-item" style="flex: 1; width: 50%;">
                <label style="font-weight: 600; display:block; margin-bottom: 0.5rem;">Number of Users:</label>

                <select name="user_quantity"
                    required
                    style="width: 220px; text-align:center; font-family: Montserrat; padding: 10px; font-size: 1rem; border-radius: 6px; border: 1px solid #ccc; background-color: #fff;">
                    <?php
                        $options = [
                            "Less than 50 users",
                            "51-150 users",
                            "151-300 users",
                            "301-400 users",
                            "More than 400 users"
                        ];

                        foreach ($options as $option) {
                            $selected = ($option == $user_quantity) ? "selected" : "";
                            echo "<option value=\"$option\" $selected>$option</option>";
                        }
                    ?>
                </select>
    </div>
</div>

<script>
document.querySelectorAll('input, select, textarea').forEach(input => {
    input.addEventListener('change', () => {
        const field = input.name;
        const value = input.value;

        // Send the updated value to server via POST
        fetch('server_infra.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({field, value})
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                console.log(field + ' updated in session.');
            } else {
                console.error(data.message);
            }
        })
        .catch(err => console.error(err));
    });
});
</script>


        <div class="question-group" style="margin-bottom: 1.2rem;">
            <label style="font-size: 1.125rem; font-weight: 600; display:block; margin-bottom: 0.25rem;">
                General Files Server <span class="required">*</span>
            </label>

            <p style="font-size: 0.875rem; color: #666; margin-top: 0.5rem; margin-bottom: 1rem;">
                Select only one option that best matches the required user load.
            </p>

            <div id="generalTableWrapper">
                <table id="generalTable" style="width: 100%; border-collapse: collapse; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); opacity: 1;">
                    <thead style="background-color: #f0f4f8; color: #333;">
                        <tr>
                            <th style="padding: 12px; text-align: center;">Selection</th>
                            <th style="padding: 12px; text-align: center;">Estimated Users</th>
                            <th style="padding: 12px; text-align: center;">Storage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="padding: 12px; text-align: center;">
                                <input type="radio" name="storage" value="8000" required>
                            </td>
                            <td style="padding: 12px; text-align: center;">Less than 50 users</td>
                            <td style="padding: 12px; text-align: center;">8 TB</td>
                        </tr>
                        <tr>
                            <td style="padding: 12px; text-align: center;">
                                <input type="radio" name="storage" value="10000" required>
                            </td>
                            <td style="padding: 12px; text-align: center;">51-150 users</td>
                            <td style="padding: 12px; text-align: center;">10 TB</td>
                        </tr>
                        <tr>
                            <td style="padding: 12px; text-align: center;">
                                <input type="radio" name="storage" value="15000" required>
                            </td>
                            <td style="padding: 12px; text-align: center;">151-300 users</td>
                            <td style="padding: 12px; text-align: center;">15 TB</td>
                        </tr>
                        <tr>
                            <td style="padding: 12px; text-align: center;">
                                <input type="radio" name="storage" value="20000" required>
                            </td>
                            <td style="padding: 12px; text-align: center;">301-400 users</td>
                            <td style="padding: 12px; text-align: center;">20 TB</td>
                        </tr>
                        <tr>
                            <td style="padding: 12px; text-align: center;">
                                <input type="radio" name="storage" value="30000" required>
                            </td>
                            <td style="padding: 12px; text-align: center;">More than 400 users</td>
                            <td style="padding: 12px; text-align: center;">30 TB</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="question-group" style="margin-bottom: 2rem;">
            <label style="font-size: 1.125rem; font-weight: 600; display:block; margin-bottom: 0.25rem;">
                Infrastructure Server 
            </label>
            <p style="font-size: 0.875rem; color: #666; margin-top: 0; margin-bottom: 1rem;">
                Overview of server roles and specifications.
            </p>

            <table style="width: 100%; border-collapse: collapse; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                <thead style="background-color: #f0f4f8; color: #333;">
                    <tr>
                        <th style="padding: 12px; text-align: center;">Server Role</th>
                        <th style="padding: 12px; text-align: center;">Core Count</th>
                        <th style="padding: 12px; text-align: center;">Memory</th>
                        <th style="padding: 12px; text-align: center;">Storage</th>
                        <th style="padding: 12px; text-align: center;">Quantity Required</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="transition: background 0.2s;">
                        <td style="padding: 12px; text-align: center;">Print & Scan</td>
                        <td style="padding: 12px; text-align: center;">2</td>
                        <td style="padding: 12px; text-align: center;">4 GB</td>
                        <td style="padding: 12px; text-align: center;">200 GB</td>
                        <td style="padding: 12px; text-align: center;">1</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; text-align: center;">Domain Controller</td>
                        <td style="padding: 12px; text-align: center;">4</td>
                        <td style="padding: 12px; text-align: center;">12 GB</td>
                        <td style="padding: 12px; text-align: center;">100 GB</td>
                        <td style="padding: 12px; text-align: center;">1</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; text-align: center;">DHCP & DFS</td>
                        <td style="padding: 12px; text-align: center;">2</td>
                        <td style="padding: 12px; text-align: center;">4 GB</td>
                        <td style="padding: 12px; text-align: center;">100 GB</td>
                        <td style="padding: 12px; text-align: center;">1</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; text-align: center;">SCCM</td>
                        <td style="padding: 12px; text-align: center;">4</td>
                        <td style="padding: 12px; text-align: center;">8 GB</td>
                        <td style="padding: 12px; text-align: center;">1.5 TB</td>
                        <td style="padding: 12px; text-align: center;">1</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; text-align: center;">Backup Proxy</td>
                        <td style="padding: 12px; text-align: center;">4</td>
                        <td style="padding: 12px; text-align: center;">16 GB</td>
                        <td style="padding: 12px; text-align: center;">100 GB</td>
                        <td style="padding: 12px; text-align: center;">1</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="question-group" style="margin-bottom: 2rem;">
    <label style="font-size: 1.125rem; font-weight: 600; display:block; margin-bottom: 0.25rem;">
        Application Server (Custom Specification)
    </label>
    <p style="font-size: 0.875rem; color: #666; margin-top: 0; margin-bottom: 1rem;">
        Enter the required Core Count, Memory, and Storage for each application instance.
    </p>

    <table id="appServerTable" style="width: 100%; border-collapse: collapse; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
        <thead style="background-color: #f0f4f8; color: #333;">
            <tr>
                <th style="padding: 12px; text-align: center;">Application Server Role</th>
                <th style="padding: 12px; text-align: center;">Core Count</th>
                <th style="padding: 12px; text-align: center;">Memory (GB)</th>
                <th style="padding: 12px; text-align: center;">Storage (GB)</th>
                <th style="padding: 12px; text-align: center;">Notes</th>
            </tr>
        </thead>
        <tbody>
        <?php
$rows = ['Easy Plant App', 'Easy Plant DB', 'Jobcard Server', 'License Server', 'Others (Please Specify)'];
$hints = ['-', '-', '-', '-', 'Name of Application'];

foreach ($rows as $i => $rowLabel) {
    $corecount = $_SESSION['corecount_' . $i] ?? '';
    $memory = $_SESSION['memory_' . $i] ?? '';
    $storage = $_SESSION['storage_' . $i] ?? '';
    $notes = $_SESSION['notes_' . $i] ?? '';
    $hint = $hints[$i];

    echo '<tr style="transition: background 0.2s;">';
    echo '<td style="padding: 12px; text-align: center;">' . $rowLabel . '</td>';
    echo '<td style="padding: 12px; text-align: center;">
            <input type="number" name="corecount_' . $i . '" value="' . $corecount . '" min="0" 
            style="width:70px; text-align:center; padding:4px; border-radius:6px; border:1px solid #ccc;">
          </td>';
    echo '<td style="padding: 12px; text-align: center;">
            <input type="number" name="memory_' . $i . '" value="' . $memory . '" min="0" 
            style="width:70px; text-align:center; padding:4px; border-radius:6px; border:1px solid #ccc;">
          </td>';
    echo '<td style="padding: 12px; text-align: center;">
            <input type="number" name="storage_' . $i . '" value="' . $storage . '" min="0" 
            style="width:70px; text-align:center; padding:4px; border-radius:6px; border:1px solid #ccc;">
          </td>';

    if ($i > 3) {
        echo '<td style="padding: 12px; text-align: center;">
                <input type="text" name="notes_' . $i . '" value="' . $notes . '" placeholder="' . $hint . '"
                style="width:100%; text-align:center; height:40px; font-size:1rem; font-family: Montserrat; padding:5px; border-radius:6px; border:1px solid #ccc; box-sizing:border-box;">
              </td>';
    } else {
        // static text for the first 3 rows
        echo '<td style="padding: 12px; text-align: center; color:#666; font-style:italic;">' . $hint . '</td>';
    }

    echo '</tr>';
}
?>

<tr id="addRowBtnRow">
    <td style="text-align: center; padding: 12px;">
        <button type="button" id="addAppBtn" 
            style="
                font-size: 1.5rem; 
                width: 40px; 
                height: 40px; 
                border-radius: 50%; 
                border: none; 
                background: linear-gradient(135deg, #80c7a0, #34a853); 
                color: white; 
                cursor: pointer; 
                margin-right: 12px; 
                transition: transform 0.2s, box-shadow 0.2s;
            "
            onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.2)';"
            onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none';"
        >
            +
        </button>

        <button type="button" id="cancelAppBtn" 
            style="
                font-size: 1rem; 
                font-family: Montserrat; 
                padding: 8px 16px; 
                border-radius: 6px; 
                border: 1px solid #ee7766; 
                color: white; 
                background-color: #ee7766; 
                cursor: pointer; 
                transition: background 0.2s, transform 0.2s;
            "
            onmouseover="this.style.backgroundColor='#d95b5b'; this.style.transform='scale(1.05)';"
            onmouseout="this.style.backgroundColor='#ee7766'; this.style.transform='scale(1)';"
        >
            Cancel
        </button>
    </td>
</tr>
        </tbody>
    </table>
</div>

<div class="form-actions" style="display:flex; gap:1rem; margin-top:2rem; align-items:center;">
    <button type="button" class="btn btn-secondary" onclick="hideAllContainers()"
        style="flex:0.6; background:#e2e8f0; color:#4a5568;">
        ❌ Cancel
    </button>
    <button type="button" class="btn btn-success" id="applyBtn"
        style="flex:1; background:#10b981; color:white;">
        📊 Apply &amp; View Summary
    </button>
</div>
    </form>

    <script>
document.addEventListener("DOMContentLoaded", () => {
    const userSelect = document.querySelector('select[name="user_quantity"]');
    const radios = document.querySelectorAll('input[name="storage"]');

    if (!userSelect || radios.length === 0) return;

    function autoSelectStorage() {
        const selectedText = userSelect.options[userSelect.selectedIndex].text.trim();

        radios.forEach(radio => {
            const row = radio.closest("tr");
            const label = row.cells[1].textContent.trim();

            radio.checked = (label === selectedText);
        });
    }

    // Run on page load
    autoSelectStorage();

    // Run when dropdown changes
    userSelect.addEventListener("change", autoSelectStorage);
});
</script>




<script>
let addCount = 0;
const maxAdd = 2;
const tableBody = document.getElementById('appServerTable').getElementsByTagName('tbody')[0];
const addBtn = document.getElementById('addAppBtn');
const cancelBtn = document.getElementById('cancelAppBtn');

// Initially hide the cancel button
cancelBtn.style.display = 'none';

addBtn.addEventListener('click', () => {
    if(addCount >= maxAdd) return;

    const rowIndex = <?php echo count($rows); ?> + addCount;
    const tr = document.createElement('tr');

    tr.innerHTML = `
        <td style="padding: 12px; text-align: center;">Others (Please Specify)</td>
        <td style="padding: 12px; text-align: center;">
            <input type="number" name="corecount_${rowIndex}" min="0" style="width:70px; text-align:center; padding:4px; border-radius:6px; border:1px solid #ccc;">
        </td>
        <td style="padding: 12px; text-align: center;">
            <input type="number" name="memory_${rowIndex}" min="0" style="width:70px; text-align:center; padding:4px; border-radius:6px; border:1px solid #ccc;">
        </td>
        <td style="padding: 12px; text-align: center;">
            <input type="number" name="storage_${rowIndex}" min="0" style="width:70px; text-align:center; padding:4px; border-radius:6px; border:1px solid #ccc;">
        </td>
        <td style="padding: 12px; text-align: center;">
            <input type="text" name="notes_${rowIndex}" placeholder="Name of Application" style="width:100%; text-align:center; height:40px; font-size:1rem; font-family: Montserrat; padding:5px; border-radius:6px; border:1px solid #ccc; box-sizing:border-box;">
        </td>
    `;

    // Insert new row above the + button row
    tableBody.insertBefore(tr, document.getElementById('addRowBtnRow'));
    addCount++;

    // Show cancel button since at least one row exists
    cancelBtn.style.display = 'inline-block';

    // Hide + button if limit reached
    if(addCount >= maxAdd) {
        addBtn.style.display = 'none';
    }
});

cancelBtn.addEventListener('click', () => {
    if(addCount > 0) {
        const lastRowIndex = <?php echo count($rows); ?> + addCount - 1;
        const rowToRemove = tableBody.querySelector(`input[name="corecount_${lastRowIndex}"]`).closest('tr');
        tableBody.removeChild(rowToRemove);
        addCount--;

        // Hide cancel button if no dynamic rows left
        if(addCount === 0) cancelBtn.style.display = 'none';

        // Show + button again if it was hidden
        if(addCount < maxAdd) addBtn.style.display = 'inline-block';
    }
});
</script>

<script>
function fetchProjectDetails() {
    return {
        project_name: document.querySelector('input[name="project_name"]').value,
        requesting_manager: document.querySelector('input[name="requesting_manager"]').value,
        project_duration: document.querySelector('input[name="project_duration"').value,
        deployment_date: document.querySelector('input[name="deployment_date"]').value
    };
}

function fetchGeneralFiles() {
    const data = {};

    // Dropdown (Estimated Users)
    const userSelect = document.querySelector('select[name="user_quantity"]');
    data.estimated_users = userSelect 
        ? userSelect.options[userSelect.selectedIndex].text.trim() 
        : null;

    // Selected radio (storage)
    const selectedRadio = document.querySelector('input[name="storage"]:checked');

    if (!selectedRadio) {
        data.storage = null;
        return data;
    }

    // Storage comes from radio value
    data.storage = selectedRadio.value;

    return data;
}

function fetchAppServer() {
    const data = [];

    // Get all rows in the table body except the last row (buttons row)
    const table = document.getElementById('appServerTable');
    const rows = table.querySelectorAll('tbody tr:not(#addRowBtnRow)');

    rows.forEach((row, index) => {
        const rowData = {};
        // Inputs by name
        const ccInput = row.querySelector(`input[name="corecount_${index}"]`);
        const memoryInput = row.querySelector(`input[name="memory_${index}"]`);
        const storageInput = row.querySelector(`input[name="storage_${index}"]`);
        const notesInput = row.querySelector(`input[name="notes_${index}"]`);

        rowData.corecount = ccInput ? ccInput.value : null;
        rowData.memory = memoryInput ? memoryInput.value : null;
        rowData.storage = storageInput ? storageInput.value : null;
        rowData.notes = notesInput ? notesInput.value : null;

        data.push(rowData);
    });

    return data;
}

document.getElementById('applyBtn').addEventListener('click', () => {
    // Validate: a storage radio must be selected
    const selectedRadio = document.querySelector('input[name="storage"]:checked');
    if (!selectedRadio) {
        alert('Please select a storage option (General Files Server section) before proceeding.');
        return;
    }

    // Validate project name
    const projName = document.querySelector('input[name="project_name"]')?.value?.trim();
    if (!projName) {
        alert('Please enter a Project Name before proceeding.');
        return;
    }

    // Show visual feedback on button
    const btn = document.getElementById('applyBtn');
    const origText = btn.innerHTML;
    btn.innerHTML = '⏳ Processing…';
    btn.disabled = true;

    // Submit the form — server_infra form posts to server_summary.php
    const form = document.getElementById('serverForm');
    form.submit();
});
</script>

<script>
    const summaryData = {
    totalCoreCount: <?php echo json_encode($totalCoreCount); ?>,
    totalMemory: <?php echo json_encode($totalMemory); ?>,
    future_needs: <?php echo json_encode($future_needs); ?>,
    cores_required: <?php echo json_encode(round($cores_required, 1)); ?>,
    memory_required: <?php echo json_encode($memory_required); ?>,
    hosts: <?php echo json_encode($hosts); ?>,
    ftt: <?php echo json_encode($ftt); ?>,

    cores_per_host: <?php echo json_encode($cores_per_host); ?>,
    vratio: <?php echo json_encode($vratio); ?>,
    memory_per_host: <?php echo json_encode($memory_per_host); ?>,
    cores_provided: <?php echo json_encode($cores_provided); ?>,
    cores_spare: <?php echo json_encode($_SESSION['cores_spare'] ?? ''); ?>,
    memory_provided: <?php echo json_encode($memory_provided); ?>,
    memory_spare: <?php echo json_encode($memory_spare); ?>,

    current_requirements: <?php echo json_encode($current_requirements); ?>,
    total_logical: <?php echo json_encode($total_logical); ?>,
    physical_optimized: <?php echo json_encode($physical_optimized); ?>,
    rec_physical: <?php echo json_encode($rec_physical); ?>,
    rec: <?php echo json_encode($rec); ?>
};

</script>

<script>
function exportPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();

    const img = new Image();
    img.src = "../assets/images/logo_blue.png";

    img.onload = function () {
        const margin = 15;
        const boxWidth = 200;
        const boxX = (pageWidth - boxWidth) / 2;
        const boxY = margin;
        const boxHeight = pageHeight - 2 * margin;

        doc.setFillColor(245, 245, 255);
        doc.roundedRect(boxX, boxY, boxWidth, boxHeight, 5, 5, "F");

        const maxLogoSize = 40;
        let logoWidth = img.width;
        let logoHeight = img.height;
        const ratio = Math.min(maxLogoSize / logoWidth, maxLogoSize / logoHeight);
        logoWidth *= ratio;
        logoHeight *= ratio;
        const logoX = boxX + 5;
        const logoY = boxY + 5;
        doc.addImage(img, "PNG", logoX, logoY, logoWidth, logoHeight);

        doc.setFont("helvetica");

        const title = "Bill Of Materials";
        doc.setFont("helvetica", "bold");
        doc.setFontSize(17);
        const titleY = logoY + logoHeight / 2 + 2;
        doc.text(title, boxX + boxWidth / 2, titleY, { align: "center" });

        let y = boxY + logoHeight + 15;

        const clientTableY = y;
        doc.autoTable({
            startY: clientTableY,
            head: [['Project Name', 'Requesting IT Project Manager', 'Project Duration', 'Required Deployment Date']],
            body: [
                    [
                        "<?php echo addslashes($project_name); ?>",
                        "<?php echo addslashes($requesting_manager); ?>",
                        "<?php echo addslashes($project_duration); ?> months",
                        "<?php echo addslashes($deployment_date); ?>"
                    ]
                ],
            styles: { font: "helvetica", halign: 'center', valign: 'middle', fontSize: 10 },
            headStyles: { fillColor: [200, 200, 200], textColor: 0 }, // light grey header
            theme: 'grid',
            margin: { left: boxX + 5, right: boxX + 5 }
        });

        y = doc.lastAutoTable.finalY + 10;

        doc.autoTable({
            startY: y,
            head: [['No', 'Model ID', 'Type','Model', 'Quantity', 'Memory', 'Disk', 'Maintenance', 'Unit Price (RM)','Total (RM)']],
            body: [
                ['1', 'BKP01', 'Backup Server','Dell PowerEdge R660xs', '1', '32GB RDIMM, 5600MT/s, Dual Rank', 'BOSS-N1 controller card + 2 M.2 480GB (RAID 1)', '3Yr vSAN Ready Node Maintenance'],
                ['2', 'SVR01', 'Server','Dell PowerEdge R660 vSAN Ready Node', '3', '32GB RDIMM, 5600MT/s, Dual Rank', '3.84TB Data Center NVMe Read Intensive AG Drive U2 Gen4 + Carrier', '3Yr vSAN Ready Node Maintenance']
            ],
            styles: { font: "helvetica", halign: 'center', valign: 'middle' },
            headStyles: { fillColor: [35, 57, 93], textColor: 255 },
            margin: { left: boxX + 5, right: boxX + 5 }
        });

        y = doc.lastAutoTable.finalY + 10;

        doc.autoTable({
            startY: y,
            head: [['No', 'Model ID', 'Type', 'Model', 'Quantity', 'Maintenance', 'Disk License', 'Total Storage Size', 'Usable Storage Size', 'Unit Price (RM)','Total (RM)']],
            body: [
                    [
                        "1",
                        "DMN01",
                        "Data Domain",
                        "PowerProtect DD6410",
                        "5",
                        "",
                        "DD6410 CPTY LIC BNDL 1TBu=CC",
                        "128",
                        "100",
                        "200.00",
                        "1000.00"
                    ]
                ],
            styles: { font: "helvetica", halign: 'center', valign: 'middle' },
            headStyles: { fillColor: [35, 57, 93], textColor: 255 },
            margin: { left: boxX + 5, right: boxX + 5 }
        });

        y = doc.lastAutoTable.finalY + 10;
        
      // --- NEW PAGE 2 ---
doc.addPage();

// Page 2 Title
doc.setFont("helvetica", "bold");
doc.setFontSize(16);
doc.text("Compute & Storage Sizing Summary", pageWidth / 2, 20, { align: "center" });

let y2 = 30;

/* ============================================
   COMPUTE SIZING – TABLE 1
============================================ */
doc.autoTable({
    startY: y2,
    head: [[
        'Current Cores', 
        'Current Memory (GB)', 
        'Future Needs',
        'Cores Required',
        'Memory Required (GB)',
        'Hosts',
        'FTT'
    ]],
    body: [
        [
            summaryData.totalCoreCount,
            summaryData.totalMemory,
            summaryData.future_needs,
            summaryData.cores_required,
            summaryData.memory_required,
            summaryData.hosts,
            summaryData.ftt
        ]
    ],
    styles: { font: "helvetica", halign: "center", fontSize: 10 },
    headStyles: { fillColor: [200, 200, 200], textColor: 0 },
    theme: "grid"
});

y2 = doc.lastAutoTable.finalY + 10;

/* ============================================
   COMPUTE SIZING – TABLE 2
============================================ */
doc.autoTable({
    startY: y2,
    head: [[
        'Cores per Host',
        'vRatio',
        'Memory per Host (GB)',
        'Cores Provided',
        'Cores Spare',
        'Memory Provided (GB)',
        'Memory Spare (GB)'
    ]],
    body: [[
        summaryData.cores_per_host,
        summaryData.vratio,
        summaryData.memory_per_host,
        summaryData.cores_provided,
        summaryData.cores_spare,
        summaryData.memory_provided,
        summaryData.memory_spare
    ]],
    styles: { font: "helvetica", halign: "center", fontSize: 10 },
    headStyles: { fillColor: [200, 200, 200], textColor: 0 },
    theme: "grid"
});

y2 = doc.lastAutoTable.finalY + 15;

/* ============================================
   STORAGE SIZING
============================================ */
doc.setFontSize(14);
doc.text("Storage Sizing", pageWidth / 2, y2, { align: "center" });

y2 += 8;

doc.autoTable({
    startY: y2,
    head: [[
        'Current Requirements (TB)',
        'Future Needs',
        'Required Storage (TB)'
    ]],
    body: [[
        summaryData.current_requirements,
        summaryData.future_needs,
        summaryData.current_requirements * summaryData.future_needs
    ]],
    styles: { font: "helvetica", halign: "center", fontSize: 10 },
    headStyles: { fillColor: [200, 200, 200], textColor: 0 },
    theme: "grid"
});

y2 = doc.lastAutoTable.finalY + 15;

/* ============================================
   REPOSITORY SIZING
============================================ */
doc.setFontSize(14);
doc.text("Repository Sizing", pageWidth / 2, y2, { align: "center" });

y2 += 8;

doc.autoTable({
    startY: y2,
    head: [[
        'Current Source Data (TB)',
        'Project Duration (Months)'
    ]],
    body: [[
        summaryData.current_requirements,
        "<?php echo addslashes($project_duration); ?> months"
    ]],
    styles: { font: "helvetica", halign: "center", fontSize: 10 },
    headStyles: { fillColor: [200, 200, 200], textColor: 0 },
    theme: "grid"
});

y2 = doc.lastAutoTable.finalY + 15;

/* ============================================
   DATA DOMAIN SIZING
============================================ */
doc.setFontSize(14);
doc.text("Data Domain Sizing", pageWidth / 2, y2, { align: "center" });

y2 += 8;

doc.autoTable({
    startY: y2,
    head: [[
        'Logical Size (TB)',
        'Physical Size (TB)',
        'Recommended Physical (TB)',
        'Recommended DELL Model'
    ]],
    body: [[
        summaryData.total_logical,
        summaryData.physical_optimized,
        summaryData.rec_physical,
        summaryData.rec
    ]],
    styles: { font: "helvetica", halign: "center", fontSize: 10 },
    headStyles: { fillColor: [200, 200, 200], textColor: 0 },
    theme: "grid"
});


        doc.save("BOM_<?php echo addslashes($project_name); ?>.pdf");
    };

    img.onerror = function () {
        alert("Logo not found! Check your path: assets/images/logo_blue.png");
    };
}
</script>

<script>


function calculate() {
    const sourceInput = document.getElementById('sourceData');
    const yearsInput = document.getElementById('years');
    
    // Exit if elements don't exist
    if (!sourceInput || !yearsInput) {
        console.log('ℹ️ Backup calculator elements not found (this is normal)');
        return;
    }
    
    const source = parseFloat(sourceInput.value) || 0;
    const years = parseFloat(yearsInput.value) || 1;

    const growth = 0.10;
    const changeRate = 0.05;
    const retDaysPolicy = 7;
    const rWeeks = 5;
    const rMonths = 6;
    const compression = 0;
    const ddRatio = 20;

    const futureSource = source * Math.pow((1 + growth), years);
    const fullSize = futureSource;
    const incSize = futureSource * changeRate;

    const totalGFS = rWeeks + rMonths;
    const numberOfFulls = Math.max(1, totalGFS - 1);

    const totalFullsVol = numberOfFulls * fullSize;
    const totalIncsVol = (retDaysPolicy - 1) * incSize;

    const totalLogical = (totalFullsVol + totalIncsVol) * 1.03;
    const physicalOptimized = totalLogical / ddRatio;

    const logicalResult = document.getElementById('logicalResult');
    const physicalResult = document.getElementById('physicalResult');
    const physicalResult2 = document.getElementById('physicalResult2');
    const hardwareRec = document.getElementById('hardwareRec');

    if (logicalResult) logicalResult.innerText = totalLogical.toFixed(2);
    if (physicalResult) physicalResult.innerText = physicalOptimized.toFixed(2);
    if (physicalResult2) physicalResult2.innerText = Math.ceil(physicalOptimized);

    let rec = "";
    if (source === 0) rec = "Enter source data...";
    else if (physicalOptimized < 8) rec = "DDVE (Virtual Edition)";
    else if (physicalOptimized < 256) rec = "PowerProtect DD6410";
    else rec = "PowerProtect DD9410 / DD9910";

    if (hardwareRec) hardwareRec.innerText = rec;
}

// Only run calculate if the page has the backup calculator
if (document.getElementById('sourceData')) {
    window.onload = calculate;
}
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    calculate();
});
</script>

</body>

</div>