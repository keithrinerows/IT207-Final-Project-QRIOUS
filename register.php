<?php
session_start();
require_once 'dbconnect.php'; 

$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize user inputs to ensure data integrity
    $fullname = mysqli_real_escape_string($conn, trim($_POST['fullname']));
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = mysqli_real_escape_string($conn, trim($_POST['password']));
    $section  = mysqli_real_escape_string($conn, strtoupper(trim($_POST['section'])));
    
    $role = "Participant";

    $checkUser = "SELECT * FROM users WHERE username='$username'";
    $runCheck = mysqli_query($conn, $checkUser);

    if (mysqli_num_rows($runCheck) > 0) {
        $error_msg = "⚠ USERNAME ALREADY TAKEN!";
    } else {
        // Execute the insertion of the new student record
        $sql = "INSERT INTO users (fullname, username, password, section, role) 
                VALUES ('$fullname', '$username', '$password', '$section', '$role')";
        
        if (mysqli_query($conn, $sql)) {
            $new_user_id = mysqli_insert_id($conn);
            $_SESSION['user_id']  = $new_user_id;
            $_SESSION['username'] = $username;
            $_SESSION['role']     = $role;
            $_SESSION['fullname'] = $fullname;
            $_SESSION['section']  = $section;

            header("Location: participant/participant_dashboard.php");
            exit();
        } else {
            $error_msg = "⚠ REGISTRATION FAILED: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Q-RIOUS | Join Us</title>

    <style>
        /* COPIED CSS FROM LOGIN PAGE */
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

        /* FLOATING BACKGROUND DECOR */
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
            max-width: 400px; /* Slightly wider for more inputs */
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

        .header {
            background: #e3a693;
            padding: 20px;
            font-size: 26px;
            font-weight: bold;
            letter-spacing: 8px;
            color: #fff;
            text-shadow: 2px 2px 0px rgba(0,0,0,0.1);
            border-bottom: 4px solid #c98f7a;
            text-transform: uppercase;
        }

        .title {
            font-size: 30px;
            padding: 25px 0 5px 0;
            letter-spacing: 5px;
            color: #5d4037;
            position: relative;
        }

        .subtitle {
            font-size: 11px;
            color: #a67c6d;
            margin-bottom: 10px;
            text-transform: uppercase;
            font-weight: bold;
        }

        .form-box {
            padding: 25px;
        }

        input {
            width: 100%;
            padding: 14px;
            margin: 10px 0;
            border: 2px solid #e8d6cf;
            background: #fff;
            font-family: inherit;
            outline: none;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        input:focus {
            border-color: #e3a693;
            box-shadow: 4px 4px 0 rgba(227, 166, 147, 0.2);
            transform: scale(1.01);
        }

        /* AUTO-CAPS VISUAL */
        .auto-caps {
            text-transform: uppercase;
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

        .footer-links a {
            font-size: 11px;
            color: #c98f7a;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: none;
            transition: 0.3s;
        }

        .footer-links a:hover {
            color: #e3a693;
            text-decoration: underline;
        }

        .error {
            color: #d9534f;
            background: #ffebee;
            padding: 8px;
            font-size: 12px;
            border: 1px solid #d9534f;
            margin-bottom: 10px;
            font-weight: bold;
            display: block;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="header">JOIN US</div>
    <div class="title">Q-RIOUS</div>
    <p class="subtitle">PARTICIPANT REGISTRATION</p>

    <form action="register.php" method="POST" class="form-box">
        <?php if ($error_msg): ?>
            <span class="error"><?php echo $error_msg; ?></span>
        <?php endif; ?>

        <input type="text" name="fullname" placeholder="FULL NAME" required>
        
        <input type="text" name="section" placeholder="SECTION (E.G. 2H-G1)" class="auto-caps" oninput="this.value = this.value.toUpperCase()" required>
        
        <input type="text" name="username" placeholder="USERNAME" required>
        <input type="password" name="password" placeholder="PASSWORD" required>

        <button type="submit">CREATE PROFILE</button>

        <div class="footer-links">
            <a href="login.php">Already have an account? Log In</a>
            <a href="index.php">← BACK TO HOME</a>
        </div>
    </form>
</div>

</body>
</html>
