<?php
session_start();
require_once 'dbconnect.php'; 

$error_msg = "";

// Validate user input
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = mysqli_real_escape_string($conn, trim($_POST['password']));
    $role = mysqli_real_escape_string($conn, $_POST['role']);

    // 1. Kuhanin ang user data
    $sql = "SELECT * FROM users WHERE username='$username' AND password='$password' AND role='$role' LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        
        // 2. IMPORTANT: Save user_id sa session
        $_SESSION['user_id'] = $row['id']; 
        $_SESSION['username'] = $row['username'];
        $_SESSION['role'] = $row['role'];
        $_SESSION['fullname'] = $row['fullname'];

        // REDIRECTION logic
        if ($row['role'] == 'Admin') {
            header("Location: admin/admin_dashboard.php");
        } elseif ($row['role'] == 'Instructor') {
            header("Location: instructor/instructor_dashboard.php");
        } elseif ($row['role'] == 'Participant') {
            header("Location: participant/participant_dashboard.php");
        }
        exit();
    } else {
        $error_msg = "⚠ ACCESS DENIED: INVALID CREDENTIALS";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Q-RIOUS | Login</title>

    <style>
        /* DESIGN RETAINED - NO CHANGES MADE */
        * {
            cursor: url('https://cur.cursors-4u.net/games/gam-4/gam373.cur'), auto !important;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            background-color: #f5e6e0;
            background-image: linear-gradient(#e8d6cf 1px, transparent 1px),
                              linear-gradient(90deg, #e8d6cf 1px, transparent 1px);
            background-size: 30px 30px;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            overflow: hidden;
            position: relative;
        }

        body::before, body::after {
            content: "";
            position: absolute;
            width: 150px;
            height: 150px;
            border: 5px solid #e8d6cf;
            border-radius: 50%;
            z-index: -1;
            animation: float 6s ease-in-out infinite;
        }
        body::before { top: 10%; left: 10%; width: 80px; height: 80px; border-radius: 0; transform: rotate(45deg); }
        body::after { bottom: 15%; right: 15%; border-color: #e3a693; opacity: 0.5; }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0); }
            50% { transform: translateY(-20px) rotate(10deg); }
        }

        .login-container {
            width: 90%;
            max-width: 380px;
            background: #fdfaf9;
            border: 3px solid #c98f7a;
            box-shadow: 12px 12px 0 #c98f7a;
            text-align: center;
            border-radius: 2px;
            animation: containerFloat 4s ease-in-out infinite;
            position: relative;
            z-index: 1;
        }

        @keyframes containerFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        .login-container::before {
            content: "▲";
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            color: #c98f7a;
            font-size: 12px;
        }

        .header {
            background: #e3a693;
            padding: 20px;
            font-size: 26px;
            font-weight: bold;
            letter-spacing: 8px;
            color: #fff;
            text-shadow: 2px 2px 0px rgba(0,0,0,0.1);
            border-bottom: 4px solid #c98f7a;
        }

        .title {
            font-size: 30px;
            padding: 25px 0 10px 0;
            letter-spacing: 5px;
            color: #5d4037;
            position: relative;
        }

        .title::after {
            content: "";
            display: block;
            width: 40px;
            height: 3px;
            background: #e3a693;
            margin: 8px auto 0;
        }

        .form-box {
            padding: 25px;
        }

        input, select {
            width: 100%;
            padding: 14px;
            margin: 12px 0;
            border: 2px solid #e8d6cf;
            background: #fff;
            font-family: inherit;
            outline: none;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        input:focus, select:focus {
            border-color: #e3a693;
            box-shadow: 4px 4px 0 rgba(227, 166, 147, 0.2);
            transform: scale(1.01);
        }

        button {
            width: 100%;
            padding: 15px;
            background: #e3a693;
            color: white;
            border: none;
            font-weight: bold;
            letter-spacing: 3px;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.2s ease;
            box-shadow: 0 5px 0 #c98f7a;
            text-transform: uppercase;
        }

        button:hover {
            background: #d28d77;
            transform: translateY(-2px);
            box-shadow: 0 7px 0 #b07a68;
        }

        button:active {
            transform: translateY(4px);
            box-shadow: 0 1px 0 #b07a68;
        }

        .footer-links {
            margin-top: 25px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .register-link, .back-link {
            font-size: 11px;
            color: #5d4037;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-decoration: none;
        }

        .register-link a, .back-link {
            color: #c98f7a;
            transition: 0.3s;
        }

        .register-link a {
            text-decoration: none;
            border-bottom: 2px solid #e8d6cf;
            padding-bottom: 2px;
        }

        .register-link a:hover {
            color: #e3a693;
            border-bottom-color: #e3a693;
        }

        .back-link:hover {
            color: #e3a693;
            text-decoration: underline;
        }

        .error {
            color: #d9534f;
            font-size: 12px;
            margin-top: 15px;
            font-weight: bold;
            display: block;
            background: #ffebee;
            padding: 8px;
            border: 1px solid #d9534f;
            animation: shake 0.4s;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-4px); }
            75% { transform: translateX(4px); }
        }

        @media (max-width: 400px) {
            .login-container { width: 85%; }
            .header { font-size: 20px; letter-spacing: 4px; }
            .title { font-size: 24px; }
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="header">Q-RIOUS</div>
    <div class="title">LOGIN</div>

    <form action="login.php" method="POST" class="form-box">
        <input type="text" name="username" placeholder="USERNAME" required>
        <input type="password" name="password" placeholder="PASSWORD" required>

        <select name="role" required>
            <option value="" disabled selected>SELECT ROLE</option>
            <option value="Admin">Admin</option>
            <option value="Instructor">Instructor</option>
            <option value="Participant">Participant</option>
        </select>

        <button type="submit">ENTER SYSTEM</button>

        <?php if ($error_msg): ?>
            <span class="error"><?php echo $error_msg; ?></span>
        <?php endif; ?>

        <div class="footer-links">
            <div class="register-link">
                No account yet? <a href="register.php">Create Profile Here</a>
            </div>
            <a href="index.php" class="back-link">← BACK TO HOME</a>
        </div>
    </form>
</div>

</body>
</html>

