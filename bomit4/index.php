<?php
session_start();
include 'db_connect.php';

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = isset($_POST['user_id']) ? trim($_POST['user_id']) : '';
    $user_pw = isset($_POST['user_pw']) ? $_POST['user_pw'] : '';

    // Allow login by user_id OR email (case-insensitive)
    $stmt = $conn->prepare(
        "SELECT * FROM users WHERE user_id = ? OR (email IS NOT NULL AND email = ?) LIMIT 1"
    );
    $stmt->bind_param("ss", $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        // Check account is active
        if (isset($user['is_active']) && $user['is_active'] == 0) {
            $error_message = "Your account has been deactivated. Please contact your administrator (Mr Saiful Yusof).";
        }
        // Verify password
        elseif (password_verify($user_pw, $user['user_pw'])) {
            $_SESSION['isLoggedIn'] = true;
            $_SESSION['user_id']    = $user['user_id'];
            $_SESSION['role']       = $user['role'] ?? 'guest';
            $_SESSION['full_name']  = $user['full_name'] ?? '';

            // Update last_login timestamp
            if (isset($user['id'])) {
                $upd = $conn->prepare("UPDATE users SET last_login=NOW() WHERE id=?");
                $upd->bind_param("i", $user['id']);
                $upd->execute();
                $upd->close();
            }

            // Force password change if required
            if (!empty($user['must_change_password'])) {
                $_SESSION['must_change_password'] = true;
                $_SESSION['change_pw_uid']        = $user['id'];
                header("Location: change_password.php");
                exit();
            }

            // Redirect based on role
            if ($_SESSION['role'] === 'admin') {
                header("Location: admin/admin_home.php");
            } else {
                header("Location: guest/project_details.php");
            }
            exit();
        } else {
            $error_message = "Invalid username or password.";
        }
    } else {
        $error_message = "Invalid username or password.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        body { font-family: Montserrat, sans-serif; background-image: url('assets/bg.png'); background-size: 100% 100%; background-position: center; background-repeat: no-repeat; height: 100vh; margin: 0; display: flex; justify-content: center; align-items: center; }
    
        .page-header { 
            position: fixed; 
            top: 10vh; 
            left: 50%; 
            transform: translateX(15%); 
            text-align: center; 
            color: white; 
            font-size: clamp(1.2rem, 2vw, 1.8rem); 
            text-shadow: 1px 1px 5px rgba(0,0,0,0.7); 
            z-index: 10; 
        }

        .login-container {
            background-color: rgba(255, 255, 255, 0.3);
    
            -webkit-backdrop-filter: blur(8px); /* Safari */
            -moz-backdrop-filter: blur(8px);    /* Older Firefox (mostly experimental) */
            backdrop-filter: blur(8px);         /* Standard property */

            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 500px;
            top: 15vh;
            transform: translateX(50%);
}

.input-group label {
    color: white;          /* change text color */
    font-size: 1rem;         /* adjust font size */
    font-weight: 300;        /* make it slightly bold */
    display: block;           /* ensures it stays above input */
    margin-bottom: 0.5rem;   /* space between label and input */
    margin-top: 0.5rem;
    padding-left: 5px;       /* optional: move label slightly right */
}

.login-wrapper {
    display: flex;
    flex-direction: column; /* stack vertically */
    align-items: center;    /* center horizontally */
    width: 100%;
}



        input[type="text"], input[type="password"] { width:100%; padding:0.75rem; border:1px solid #ddd; border-radius:4px; box-sizing:border-box; }
        button { width:100%; padding:0.75rem; font-family: Montserrat; border:2px solid #fff; border-radius:4px; background-color:#ee7766; color:#fff; font-size:1rem; cursor:pointer; margin-top:30px; }
        button:hover { background-color:#80c7a0; border:2px solid #fff; transform: translateY(-2px); box-shadow:0 4px 10px rgba(88,166,255,0.3); }
        #message { margin-top:1rem; color:red; font-weight:bold; text-align:center; }
    </style>
</head>
<body>

<header class="page-header">
    <h1>BoMIT System</h1>
</header>

<div class="login-wrapper">
    <div class="login-container">
        <form method="POST" action="">
            <div class="input-group">
                <label for="user_id">T.EN Email:</label>
                <input type="text" id="user_id" name="user_id" required>
            </div>
            <div class="input-group">
                <label for="user_pw">Password:</label>
                <input type="password" id="user_pw" name="user_pw" required>
            </div>
            <button type="submit">Login</button>
        </form>
        <p id="message"><?php echo $error_message; ?></p>
    </div>
</div>


</body>
</html>