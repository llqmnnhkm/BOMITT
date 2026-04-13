<?php
// admin/admin_includes/exchange_rates.php
// Admin UI for managing MYR base currency exchange rates
// Included in admin_home.php as a standalone section below the category cards

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($conn)) include dirname(__DIR__) . '/db_connect.php';
require_once __DIR__ . '/admin_utilities.php';
requireAdminAuth($conn);

// ── Auto-create table if missing ──────────────────────────────────────────
$conn->query("
    CREATE TABLE IF NOT EXISTS currency_rates (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        currency_code VARCHAR(10)    NOT NULL,
        rate          DECIMAL(10,6)  NOT NULL COMMENT '1 MYR = X of this currency',
        symbol        VARCHAR(5)     NOT NULL,
        label         VARCHAR(50)    NOT NULL,
        updated_by    VARCHAR(255)   DEFAULT NULL,
        updated_at    TIMESTAMP      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_code (currency_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Seed defaults if empty
$cnt = $conn->query("SELECT COUNT(*) as c FROM currency_rates")->fetch_assoc()['c'];
if ($cnt == 0) {
    $conn->query("INSERT INTO currency_rates (currency_code, rate, symbol, label) VALUES
        ('USD', 0.212800, '\$', 'US Dollar'),
        ('EUR', 0.196300, '€',  'Euro')
    ");
}

// ── Handle save ───────────────────────────────────────────────────────────
$save_result = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_rates'])) {
    $user_id = $_SESSION['user_id'];
    $errors  = [];

    foreach (['USD', 'EUR'] as $code) {
        $rate = floatval($_POST['rate_' . $code] ?? 0);
        if ($rate <= 0 || $rate >= 1) {
            $errors[] = "$code rate must be between 0 and 1 (e.g. 0.2128 means 1 MYR = 0.2128 USD)";
            continue;
        }
        $stmt = $conn->prepare(
            "UPDATE currency_rates SET rate=?, updated_by=? WHERE currency_code=?"
        );
        $stmt->bind_param("dss", $rate, $user_id, $code);
        $stmt->execute();
        $stmt->close();
    }

    $save_result = empty($errors) ? 'success' : implode('<br>', $errors);
}

// ── Fetch current rates ───────────────────────────────────────────────────
$rates = [];
$res   = $conn->query("SELECT * FROM currency_rates ORDER BY id");
if ($res) { while ($r = $res->fetch_assoc()) $rates[$r['currency_code']] = $r; }

// ── Helper: implied reverse rate ─────────────────────────────────────────
// e.g. if 1 MYR = 0.2128 USD → 1 USD = 1/0.2128 = 4.699 MYR
function impliedReverse($rate) {
    return $rate > 0 ? round(1 / $rate, 4) : 0;
}
?>

<!-- ── Exchange Rate Management Section ──────────────────────────────────── -->
<div id="exchange-rate-section"
     style="background:white; border-radius:16px; padding:2rem; margin-top:2rem;
            box-shadow:0 4px 12px rgba(0,0,0,0.08); border:1px solid #e0e0e0;">

    <!-- Section Header -->
    <div style="display:flex; align-items:center; justify-content:space-between;
                margin-bottom:1.5rem; padding-bottom:1rem; border-bottom:2px solid #f0f0f0;">
        <div style="display:flex; align-items:center; gap:12px;">
            <div style="width:48px; height:48px; background:linear-gradient(135deg,#0070ef,#80c7a0);
                        border-radius:12px; display:flex; align-items:center; justify-content:center;
                        font-size:1.4rem;">💱</div>
            <div>
                <h3 style="font-size:1.3rem; font-weight:700; color:#333; margin:0;">
                    Currency Exchange Rates
                </h3>
                <p style="font-size:.875rem; color:#666; margin:2px 0 0;">
                    Base currency: <strong>MYR (Malaysian Ringgit)</strong> · All DB prices stored in MYR
                </p>
            </div>
        </div>
        <span style="font-size:.8rem; color:#999;">
            Last updated: <?php
                $latest = max(array_column($rates, 'updated_at'));
                echo $latest ? date('d M Y, H:i', strtotime($latest)) : '—';
            ?>
        </span>
    </div>

    <!-- Save feedback -->
    <?php if ($save_result === 'success'): ?>
    <div style="padding:10px 16px; background:#e8f5e9; border-left:4px solid #4caf50;
                border-radius:8px; color:#2e7d32; font-weight:600; margin-bottom:1.25rem; font-size:.9rem;">
        ✅ Exchange rates updated successfully. Guest users will see new rates immediately.
    </div>
    <?php elseif ($save_result): ?>
    <div style="padding:10px 16px; background:#f8d7da; border-left:4px solid #dc3545;
                border-radius:8px; color:#721c24; font-weight:500; margin-bottom:1.25rem; font-size:.9rem;">
        ⚠️ <?php echo $save_result; ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="" id="exchange-rate-form">
        <input type="hidden" name="save_rates" value="1">

        <!-- Rate cards grid -->
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:1.25rem; margin-bottom:1.5rem;">

            <!-- MYR base (read-only, always 1) -->
            <div style="background:linear-gradient(135deg,#e3f2fd,#f0f7ff); border-radius:12px;
                        padding:1.25rem; border:1px solid #b3d4f5;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1rem;">
                    <div>
                        <div style="font-size:.75rem; font-weight:600; color:#1565c0; text-transform:uppercase; letter-spacing:.5px; margin-bottom:4px;">Base Currency</div>
                        <div style="font-size:1.25rem; font-weight:700; color:#1565c0;">🇲🇾 MYR — Malaysian Ringgit</div>
                    </div>
                    <span style="background:#1565c0; color:white; padding:4px 12px; border-radius:20px; font-size:.78rem; font-weight:600;">BASE</span>
                </div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <span style="font-size:.9rem; color:#555;">1 MYR =</span>
                    <input type="number" value="1.000000" disabled
                           style="width:100px; padding:8px 10px; border:1px solid #ccc; border-radius:6px;
                                  font-family:Montserrat; font-size:1rem; text-align:center;
                                  background:#f5f5f5; color:#888;">
                    <span style="font-size:.9rem; color:#555;">MYR</span>
                </div>
            </div>

            <!-- USD rate -->
            <?php
            $usd = $rates['USD'] ?? ['rate'=>0.2128,'symbol'=>'$','label'=>'US Dollar','updated_at'=>null];
            $eur = $rates['EUR'] ?? ['rate'=>0.1963,'symbol'=>'€','label'=>'Euro','updated_at'=>null];
            foreach ([
                'USD' => ['data'=>$usd, 'flag'=>'🇺🇸', 'color'=>'#2e7d32', 'bg'=>'#e8f5e9', 'border'=>'#a5d6a7'],
                'EUR' => ['data'=>$eur, 'flag'=>'🇪🇺', 'color'=>'#6a1b9a', 'bg'=>'#f3e5f5', 'border'=>'#ce93d8'],
            ] as $code => $info):
                $d = $info['data'];
            ?>
            <div style="background:linear-gradient(135deg,<?php echo $info['bg']; ?>,white); border-radius:12px;
                        padding:1.25rem; border:1px solid <?php echo $info['border']; ?>;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1rem;">
                    <div>
                        <div style="font-size:.75rem; font-weight:600; color:<?php echo $info['color']; ?>; text-transform:uppercase; letter-spacing:.5px; margin-bottom:4px;">
                            Exchange Rate
                        </div>
                        <div style="font-size:1.1rem; font-weight:700; color:<?php echo $info['color']; ?>;">
                            <?php echo $info['flag']; ?> <?php echo $code; ?> — <?php echo htmlspecialchars($d['label']); ?>
                        </div>
                    </div>
                    <span style="background:<?php echo $info['color']; ?>; color:white; padding:4px 12px;
                                 border-radius:20px; font-size:.78rem; font-weight:600;">
                        <?php echo htmlspecialchars($d['symbol']); ?>
                    </span>
                </div>

                <!-- Input row -->
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                    <span style="font-size:.9rem; color:#555; white-space:nowrap;">1 MYR =</span>
                    <input type="number"
                           name="rate_<?php echo $code; ?>"
                           id="rate-input-<?php echo $code; ?>"
                           value="<?php echo number_format((float)$d['rate'], 6); ?>"
                           step="0.000001" min="0.000001" max="0.999999"
                           required
                           oninput="updateImplied('<?php echo $code; ?>')"
                           style="width:130px; padding:8px 10px; border:1px solid #ccc; border-radius:6px;
                                  font-family:Montserrat; font-size:1rem; text-align:center;
                                  transition:border-color .2s;">
                    <span style="font-size:.9rem; color:#555;"><?php echo $code; ?></span>
                </div>

                <!-- Implied reverse -->
                <div style="font-size:.8rem; color:#888; padding:6px 10px; background:rgba(255,255,255,.5);
                            border-radius:6px; border:1px solid rgba(0,0,0,.06);">
                    Implies: 1 <?php echo $code; ?> =
                    <strong id="implied-<?php echo $code; ?>" style="color:<?php echo $info['color']; ?>;">
                        <?php echo number_format(impliedReverse((float)$d['rate']), 4); ?>
                    </strong> MYR
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Quick reference table -->
        <div style="background:#f8f9fa; border-radius:10px; padding:1rem 1.25rem; margin-bottom:1.5rem; border:1px solid #e9ecef;">
            <div style="font-size:.8rem; font-weight:600; color:#555; margin-bottom:.75rem; text-transform:uppercase; letter-spacing:.5px;">
                Quick Reference — Sample Conversions
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:.75rem;" id="quick-ref">
                <!-- Populated by JS -->
            </div>
        </div>

        <!-- Save button -->
        <div style="display:flex; align-items:center; gap:1rem;">
            <button type="submit" class="btn-save"
                    style="padding:.85rem 2.5rem; font-size:1rem; background:linear-gradient(90deg,#0070ef,#4A90E2);">
                💾 Save Exchange Rates
            </button>
            <span style="font-size:.8rem; color:#888;">
                Changes take effect immediately for all active users.
            </span>
        </div>
    </form>
</div>

<script>
// ── Update implied reverse rate on input ──────────────────────────────────
function updateImplied(code) {
    const input = document.getElementById('rate-input-' + code);
    const el    = document.getElementById('implied-' + code);
    const val   = parseFloat(input.value) || 0;
    el.textContent = val > 0 ? (1 / val).toFixed(4) : '—';
    buildQuickRef();
}

// ── Build quick reference table ───────────────────────────────────────────
function buildQuickRef() {
    const usdRate = parseFloat(document.getElementById('rate-input-USD')?.value) || 0.2128;
    const eurRate = parseFloat(document.getElementById('rate-input-EUR')?.value) || 0.1963;
    const container = document.getElementById('quick-ref');
    if (!container) return;

    const samples = [100, 500, 1000, 5000, 10000];
    const colors  = { MYR: '#1565c0', USD: '#2e7d32', EUR: '#6a1b9a' };

    const rows = samples.map(myr => {
        const usd = (myr * usdRate).toFixed(2);
        const eur = (myr * eurRate).toFixed(2);
        return `<div style="background:white; border-radius:8px; padding:.5rem .75rem; border:1px solid #e0e0e0; font-size:.82rem;">
            <div style="color:${colors.MYR}; font-weight:600;">RM ${myr.toLocaleString()}</div>
            <div style="color:${colors.USD}; margin-top:2px;">$ ${parseFloat(usd).toLocaleString('en-US',{minimumFractionDigits:2})}</div>
            <div style="color:${colors.EUR};">€ ${parseFloat(eur).toLocaleString('en-US',{minimumFractionDigits:2})}</div>
        </div>`;
    }).join('');

    container.innerHTML = rows;
}

// Run on load
document.addEventListener('DOMContentLoaded', buildQuickRef);
</script>
