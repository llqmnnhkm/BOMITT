<?php
// change_password.php — Force password change on first login
// Only accessible when $_SESSION['must_change_password'] is set

session_start();
include 'db_connect.php';

// Must be logged in and flagged for password change
if (empty($_SESSION['isLoggedIn']) || empty($_SESSION['must_change_password'])) {
    header('Location: index.php');
    exit();
}

$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_pw  = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $uid     = (int)($_SESSION['change_pw_uid'] ?? 0);

    if (strlen($new_pw) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($new_pw !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif ($uid <= 0) {
        $error = 'Session error. Please log in again.';
    } else {
        $hashed = password_hash($new_pw, PASSWORD_BCRYPT);
        $stmt   = $conn->prepare(
            "UPDATE users SET user_pw=?, must_change_password=0 WHERE id=?"
        );
        $stmt->bind_param("si", $hashed, $uid);
        if ($stmt->execute()) {
            unset($_SESSION['must_change_password'], $_SESSION['change_pw_uid']);
            // Redirect to correct home
            $dest = ($_SESSION['role'] === 'admin') ? 'admin/admin_home.php' : 'guest/project_details.php';
            header('Location: ' . $dest);
            exit();
        } else {
            $error = 'Database error. Please try again.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password — BoMIT</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Montserrat, sans-serif; background: linear-gradient(135deg, #0070ef 0%, #80c7a0 100%);
               min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .card { background: white; border-radius: 20px; padding: 2.5rem; max-width: 460px; width: 100%;
                box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
        .logo { text-align: center; margin-bottom: 1.75rem; }
        .logo h1 { font-size: 1.6rem; font-weight: 700; color: #0070ef; }
        .logo p  { font-size: .875rem; color: #666; margin-top: 4px; }
        h2 { font-size: 1.2rem; font-weight: 700; color: #2d3748; margin-bottom: .5rem; }
        .notice { background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 8px;
                  padding: 12px 16px; margin-bottom: 1.5rem; font-size: .875rem; color: #856404; }
        .form-group { margin-bottom: 1.1rem; }
        label { font-weight: 600; display: block; margin-bottom: .4rem; font-size: .875rem; color: #4a5568; }
        .pw-wrap { position: relative; }
        input[type="password"], input[type="text"] {
            width: 100%; padding: 11px 40px 11px 14px; border: 1.5px solid #ddd;
            border-radius: 8px; font-family: Montserrat; font-size: 1rem;
            transition: border-color .2s; }
        input:focus { outline: none; border-color: #0070ef; }
        .toggle-pw { position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
                     background: none; border: none; cursor: pointer; font-size: 1.1rem; }
        .strength-bar { height: 4px; background: #e0e0e0; border-radius: 2px; margin-top: 6px; overflow: hidden; }
        .strength-fill { height: 100%; width: 0; border-radius: 2px; transition: width .3s, background .3s; }
        .strength-label { font-size: .72rem; color: #888; margin-top: 3px; }
        .error-box { background: #ffebee; border-left: 4px solid #f44336; border-radius: 8px;
                     padding: 10px 14px; margin-bottom: 1rem; font-size: .875rem; color: #c62828; font-weight: 600; }
        .btn { width: 100%; padding: 13px; background: linear-gradient(90deg, #0070ef, #4A90E2);
               color: white; border: none; border-radius: 8px; font-family: Montserrat;
               font-size: 1rem; font-weight: 700; cursor: pointer; transition: opacity .2s; margin-top: .5rem; }
        .btn:hover { opacity: .9; }
        .btn:disabled { opacity: .6; cursor: not-allowed; }
        .requirements { background: #f7f9fc; border-radius: 8px; padding: 10px 14px; font-size: .8rem;
                        color: #555; margin-bottom: 1rem; line-height: 1.8; }
        .requirements li { margin-left: 1rem; }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">
        <h1>🔐 BoMIT System</h1>
        <p>Technip Energies IT Equipment BoM</p>
    </div>

    <h2>Set Your New Password</h2>
    <div class="notice">
        ⚠️ Your account requires a password change before you can continue. Please set a new password below.
    </div>

    <?php if ($error): ?>
    <div class="error-box">❌ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="requirements">
        Your password must:
        <ul>
            <li>Be at least 6 characters long</li>
            <li>Not be the same as your initial password</li>
        </ul>
    </div>

    <form method="POST" onsubmit="return validateForm()">
        <div class="form-group">
            <label>New Password</label>
            <div class="pw-wrap">
                <input type="password" name="new_password" id="new_pw" required
                       minlength="6" placeholder="Enter new password" oninput="checkStrength()">
                <button type="button" class="toggle-pw" onclick="togglePw('new_pw', this)">👁</button>
            </div>
            <div class="strength-bar"><div class="strength-fill" id="strength-fill"></div></div>
            <div class="strength-label" id="strength-label"></div>
        </div>

        <div class="form-group">
            <label>Confirm New Password</label>
            <div class="pw-wrap">
                <input type="password" name="confirm_password" id="confirm_pw" required
                       placeholder="Repeat new password" oninput="checkMatch()">
                <button type="button" class="toggle-pw" onclick="togglePw('confirm_pw', this)">👁</button>
            </div>
            <div id="match-label" style="font-size:.72rem;margin-top:4px;"></div>
        </div>

        <button type="submit" class="btn" id="submit-btn">✅ Set New Password &amp; Continue</button>
    </form>

    <p style="text-align:center;margin-top:1.25rem;font-size:.82rem;color:#999;">
        <a href="index.php" style="color:#0070ef;text-decoration:none;font-weight:600;">← Back to Login</a>
    </p>
</div>

<script>
function togglePw(id, btn) {
    const inp = document.getElementById(id);
    inp.type  = inp.type === 'password' ? 'text' : 'password';
    btn.textContent = inp.type === 'password' ? '👁' : '🙈';
}

function checkStrength() {
    const pw  = document.getElementById('new_pw').value;
    const bar = document.getElementById('strength-fill');
    const lbl = document.getElementById('strength-label');
    let score = 0;
    if (pw.length >= 6)  score++;
    if (pw.length >= 10) score++;
    if (/[A-Z]/.test(pw)) score++;
    if (/[0-9]/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;
    const levels = [
        {w:'0%',  c:'#e0e0e0', t:''},
        {w:'25%', c:'#f44336', t:'Weak'},
        {w:'50%', c:'#ff9800', t:'Fair'},
        {w:'75%', c:'#ffc107', t:'Good'},
        {w:'90%', c:'#4caf50', t:'Strong'},
        {w:'100%',c:'#2e7d32', t:'Very Strong'},
    ];
    const l = levels[Math.min(score, 5)];
    bar.style.width      = l.w;
    bar.style.background = l.c;
    lbl.textContent      = l.t;
    lbl.style.color      = l.c;
    checkMatch();
}

function checkMatch() {
    const pw  = document.getElementById('new_pw').value;
    const pw2 = document.getElementById('confirm_pw').value;
    const el  = document.getElementById('match-label');
    if (!pw2) { el.textContent = ''; return; }
    if (pw === pw2) el.innerHTML = '<span style="color:#2e7d32;font-weight:600;">✔ Passwords match</span>';
    else            el.innerHTML = '<span style="color:#c62828;font-weight:600;">✘ Passwords do not match</span>';
}

function validateForm() {
    const pw  = document.getElementById('new_pw').value;
    const pw2 = document.getElementById('confirm_pw').value;
    if (pw.length < 6)  { alert('Password must be at least 6 characters.'); return false; }
    if (pw !== pw2)     { alert('Passwords do not match.'); return false; }
    document.getElementById('submit-btn').disabled    = true;
    document.getElementById('submit-btn').textContent = '⏳ Saving…';
    return true;
}
</script>
</body>
</html>