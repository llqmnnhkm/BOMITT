<?php
// admin/admin_includes/account_create.php
// Form to create a new guest account

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($conn)) include dirname(__DIR__) . '/db_connect.php';
require_once __DIR__ . '/admin_utilities.php';
requireAdminAuth($conn);

$creator = $_SESSION['user_id'];
?>

<div style="max-width:680px;">
    <div style="background:linear-gradient(135deg,#e3f2fd,#f3e5f5);padding:16px 20px;border-radius:12px;
                margin-bottom:1.5rem;border-left:4px solid #0070ef;">
        <div style="font-weight:700;color:#0070ef;margin-bottom:4px;">Create New Account</div>
        <div style="font-size:.875rem;color:#666;">
            New accounts are created as <strong>Guest (IT Project Manager)</strong> by default.
            The user will be required to change their password on first login.
        </div>
    </div>

    <!-- Success/Error banner -->
    <div id="acc-create-banner" style="display:none;padding:12px 16px;border-radius:8px;margin-bottom:1rem;font-weight:600;font-size:.9rem;"></div>

    <form id="acc-create-form" onsubmit="accCreateSubmit(event)">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">

            <!-- Full Name -->
            <div class="form-group">
                <label>Full Name <span style="color:red;">*</span></label>
                <input type="text" name="full_name" id="acc-c-name" required
                       placeholder="e.g. Ahmad bin Ali"
                       style="width:100%;">
            </div>

            <!-- Department -->
            <div class="form-group">
                <label>Department</label>
                <input type="text" name="department" id="acc-c-dept"
                       placeholder="e.g. IT, Engineering, Operations"
                       style="width:100%;">
            </div>

            <!-- Login ID -->
            <div class="form-group">
                <label>Login ID (username) <span style="color:red;">*</span></label>
                <input type="text" name="user_id" id="acc-c-userid" required
                       placeholder="e.g. ahmad@ten.com or AHMAD01"
                       oninput="accCheckUserIdAvail()"
                       style="width:100%;">
                <div id="acc-userid-feedback" style="font-size:.78rem;margin-top:4px;"></div>
                <div style="font-size:.75rem;color:#888;margin-top:3px;">
                    Used to log in. Can be email or a username.
                </div>
            </div>

            <!-- Email -->
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" id="acc-c-email"
                       placeholder="ahmad@technipfmc.com"
                       style="width:100%;">
                <div style="font-size:.75rem;color:#888;margin-top:3px;">
                    Optional — used as alternative login.
                </div>
            </div>

            <!-- Phone -->
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone" id="acc-c-phone"
                       placeholder="+60 12-345 6789"
                       style="width:100%;">
            </div>

            <!-- Role -->
            <div class="form-group">
                <label>Role</label>
                <select name="role" id="acc-c-role" style="width:100%;">
                    <option value="guest" selected>Guest (IT Project Manager)</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
        </div>

        <!-- Password section -->
        <div style="background:#fff3e0;border-radius:10px;padding:1.25rem;margin:1rem 0;border-left:4px solid #e65100;">
            <div style="font-weight:700;color:#e65100;margin-bottom:.75rem;">🔑 Initial Password</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="form-group" style="margin:0;">
                    <label>Password <span style="color:red;">*</span></label>
                    <div style="position:relative;">
                        <input type="password" name="password" id="acc-c-pw" required minlength="6"
                               placeholder="Min. 6 characters"
                               oninput="accCheckPwStrength()"
                               style="width:100%;padding-right:36px;">
                        <button type="button" onclick="accTogglePw('acc-c-pw', this)"
                            style="position:absolute;right:8px;top:50%;transform:translateY(-50%);
                                   background:none;border:none;cursor:pointer;font-size:1rem;">👁</button>
                    </div>
                    <!-- Password strength bar -->
                    <div style="margin-top:6px;height:4px;background:#e0e0e0;border-radius:2px;overflow:hidden;">
                        <div id="acc-pw-strength-bar" style="height:100%;width:0%;transition:width .3s,background .3s;border-radius:2px;"></div>
                    </div>
                    <div id="acc-pw-strength-text" style="font-size:.72rem;color:#888;margin-top:2px;"></div>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Confirm Password <span style="color:red;">*</span></label>
                    <div style="position:relative;">
                        <input type="password" name="confirm_password" id="acc-c-pw2" required
                               placeholder="Repeat password"
                               oninput="accCheckPwMatch()"
                               style="width:100%;padding-right:36px;">
                        <button type="button" onclick="accTogglePw('acc-c-pw2', this)"
                            style="position:absolute;right:8px;top:50%;transform:translateY(-50%);
                                   background:none;border:none;cursor:pointer;font-size:1rem;">👁</button>
                    </div>
                    <div id="acc-pw-match-text" style="font-size:.72rem;margin-top:4px;"></div>
                </div>
            </div>
            <label style="display:flex;align-items:center;gap:8px;margin-top:.75rem;font-size:.875rem;cursor:pointer;">
                <input type="checkbox" name="must_change_password" value="1" checked id="acc-c-must-change">
                <span>Require user to change password on first login <span style="color:#888;">(recommended)</span></span>
            </label>
        </div>

        <div style="display:flex;gap:1rem;margin-top:.5rem;">
            <button type="button" onclick="accResetCreate()"
                style="flex:.5;padding:.85rem;background:#e2e8f0;color:#4a5568;border:none;border-radius:8px;
                       font-family:Montserrat;font-weight:600;cursor:pointer;">
                🔄 Reset Form
            </button>
            <button type="submit" id="acc-create-btn"
                style="flex:1;padding:.85rem;background:linear-gradient(90deg,#0070ef,#4A90E2);
                       color:white;border:none;border-radius:8px;font-family:Montserrat;
                       font-weight:700;font-size:1rem;cursor:pointer;">
                ✅ Create Account
            </button>
        </div>
    </form>
</div>

<script>
let accUserIdTimer = null;

// ── Check Login ID availability (debounced) ───────────────────────────────
function accCheckUserIdAvail() {
    clearTimeout(accUserIdTimer);
    const val = document.getElementById('acc-c-userid').value.trim();
    const fb  = document.getElementById('acc-userid-feedback');
    if (val.length < 2) { fb.textContent = ''; return; }
    fb.innerHTML = '<span style="color:#888;">Checking…</span>';
    accUserIdTimer = setTimeout(async () => {
        const fd = new FormData();
        fd.append('action', 'check_userid');
        fd.append('user_id', val);
        try {
            const resp   = await fetch('admin_includes/handlers/account_handler.php', { method:'POST', body:fd });
            const result = await resp.json();
            fb.innerHTML = result.available
                ? '<span style="color:#2e7d32;font-weight:600;">✔ Available</span>'
                : '<span style="color:#c62828;font-weight:600;">✘ Already taken</span>';
        } catch(e) { fb.textContent = ''; }
    }, 500);
}

// ── Password strength ─────────────────────────────────────────────────────
function accCheckPwStrength() {
    const pw  = document.getElementById('acc-c-pw').value;
    const bar = document.getElementById('acc-pw-strength-bar');
    const txt = document.getElementById('acc-pw-strength-text');
    let score = 0;
    if (pw.length >= 6)  score++;
    if (pw.length >= 10) score++;
    if (/[A-Z]/.test(pw)) score++;
    if (/[0-9]/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;
    const levels = [
        { w:'0%',   color:'#e0e0e0', label:'' },
        { w:'25%',  color:'#f44336', label:'Weak' },
        { w:'50%',  color:'#ff9800', label:'Fair' },
        { w:'75%',  color:'#ffc107', label:'Good' },
        { w:'90%',  color:'#4caf50', label:'Strong' },
        { w:'100%', color:'#2e7d32', label:'Very Strong' },
    ];
    const l = levels[Math.min(score, 5)];
    bar.style.width    = l.w;
    bar.style.background = l.color;
    txt.textContent    = l.label;
    txt.style.color    = l.color;
    accCheckPwMatch();
}

function accCheckPwMatch() {
    const pw  = document.getElementById('acc-c-pw').value;
    const pw2 = document.getElementById('acc-c-pw2').value;
    const el  = document.getElementById('acc-pw-match-text');
    if (!pw2) { el.textContent = ''; return; }
    if (pw === pw2) { el.innerHTML = '<span style="color:#2e7d32;font-weight:600;">✔ Passwords match</span>'; }
    else            { el.innerHTML = '<span style="color:#c62828;font-weight:600;">✘ Passwords do not match</span>'; }
}

function accTogglePw(inputId, btn) {
    const inp = document.getElementById(inputId);
    inp.type  = inp.type === 'password' ? 'text' : 'password';
    btn.textContent = inp.type === 'password' ? '👁' : '';
}

function accResetCreate() {
    document.getElementById('acc-create-form').reset();
    document.getElementById('acc-userid-feedback').textContent  = '';
    document.getElementById('acc-pw-strength-bar').style.width  = '0%';
    document.getElementById('acc-pw-strength-text').textContent = '';
    document.getElementById('acc-pw-match-text').textContent    = '';
    document.getElementById('acc-create-banner').style.display  = 'none';
}

// ── Submit ────────────────────────────────────────────────────────────────
async function accCreateSubmit(event) {
    event.preventDefault();

    const pw  = document.getElementById('acc-c-pw').value;
    const pw2 = document.getElementById('acc-c-pw2').value;
    if (pw !== pw2) { alert('Passwords do not match.'); return; }
    if (pw.length < 6) { alert('Password must be at least 6 characters.'); return; }

    const btn    = document.getElementById('acc-create-btn');
    const banner = document.getElementById('acc-create-banner');
    btn.disabled = true; btn.textContent = '⏳ Creating…';

    const fd = new FormData(document.getElementById('acc-create-form'));
    fd.append('action', 'create_account');

    try {
        const resp   = await fetch('admin_includes/handlers/account_handler.php', { method:'POST', body:fd });
        const result = await resp.json();
        banner.style.display = 'block';
        if (result.success) {
            banner.style.cssText = 'display:block;padding:12px 16px;border-radius:8px;margin-bottom:1rem;font-weight:600;font-size:.9rem;background:#e8f5e9;border-left:4px solid #4caf50;color:#1b5e20;';
            banner.innerHTML = '✅ ' + result.message + ' — <a href="#" onclick="accSwitchTab(\'accounts\',event)" style="color:#1b5e20;">View all accounts →</a>';
            document.getElementById('acc-create-form').reset();
            document.getElementById('acc-pw-strength-bar').style.width = '0%';
        } else {
            banner.style.cssText = 'display:block;padding:12px 16px;border-radius:8px;margin-bottom:1rem;font-weight:600;font-size:.9rem;background:#ffebee;border-left:4px solid #f44336;color:#c62828;';
            banner.innerHTML = '❌ ' + result.message;
        }
    } catch(e) {
        banner.style.cssText = 'display:block;padding:12px 16px;border-radius:8px;margin-bottom:1rem;font-weight:600;font-size:.9rem;background:#ffebee;border-left:4px solid #f44336;color:#c62828;';
        banner.textContent = '❌ Network error: ' + e.message;
    } finally {
        btn.disabled = false; btn.textContent = '✅ Create Account';
    }
}
</script>