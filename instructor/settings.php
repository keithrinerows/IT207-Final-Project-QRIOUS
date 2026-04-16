<?php
session_start();
require_once '../dbconnect.php'; // Siguraduhing tama ang path patungo sa dbconnect.php 

// 1. SECURITY CHECK: Instructor lang ang pwedeng makakita nito 
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Instructor') {
    header('Location: ../login.php'); 
    exit;
}

$instructor_id = $_SESSION['user_id']; 
$userName = $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Instructor'; 
$msg = "";
$error = "";

// 2. PHP LOGIC PARA SA MGA SUBMISSIONS 
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // UPDATE PROFILE & USERNAME LOGIC 
    if (isset($_POST['update_profile'])) {
        $new_name = mysqli_real_escape_string($conn, $_POST['fullname']);
        $new_user = mysqli_real_escape_string($conn, trim($_POST['username']));
        
        // I-check muna kung ang bagong username ay ginagamit na ng iba 
        $check_user = mysqli_query($conn, "SELECT id FROM users WHERE username = '$new_user' AND id != $instructor_id");
        
        if (mysqli_num_rows($check_user) > 0) {
            $error = "❌ Username '$new_user' is already taken!";
        } else {
            $sql = "UPDATE users SET fullname = '$new_name', username = '$new_user' WHERE id = $instructor_id";
            if (mysqli_query($conn, $sql)) {
                $_SESSION['fullname'] = $new_name; // Refresh session name 
                $_SESSION['username'] = $new_user; 
                $userName = $new_name; // Update local variable para sa instant display
                $msg = "✅ Profile updated successfully!";
            } else {
                $error = "❌ Error updating profile.";
            }
        }
    }

    // CHANGE PASSWORD LOGIC 
    if (isset($_POST['change_password'])) {
        $current_pass = mysqli_real_escape_string($conn, $_POST['current_password']);
        $new_pass = mysqli_real_escape_string($conn, $_POST['new_password']);
        $confirm_pass = mysqli_real_escape_string($conn, $_POST['confirm_password']);

        $res = mysqli_query($conn, "SELECT password FROM users WHERE id = $instructor_id");
        $user_data = mysqli_fetch_assoc($res);

        if ($current_pass !== $user_data['password']) {
            $error = "❌ Current password does not match!";
        } elseif ($new_pass !== $confirm_pass) {
            $error = "❌ New passwords do not match!";
        } else {
            if (mysqli_query($conn, "UPDATE users SET password = '$new_pass' WHERE id = $instructor_id")) {
                $msg = "✅ Password changed successfully!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Settings | Q-RIOUS</title>
    <style>
        /* Q-RIOUS DESIGN SYSTEM */
        * { cursor: url('https://cur.cursors-4u.net/games/gam-4/gam373.cur'), auto !important; box-sizing: border-box; margin: 0; padding: 0; }
        
        body { 
            font-family: 'Courier New', monospace; 
            background-color: #f5e6e0; 
            background-image: linear-gradient(#e8d6cf 1px, transparent 1px), linear-gradient(90deg, #e8d6cf 1px, transparent 1px); 
            background-size: 30px 30px; 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* TOP BAR */
        .top-nav { 
            background: #e3a693; 
            height: 70px; 
            padding: 0 30px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            border-bottom: 4px solid #c98f7a; 
            color: white;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }
        .logo { font-size: 24px; font-weight: bold; letter-spacing: 5px; text-shadow: 2px 2px 0 #c98f7a; }
        .logout-btn { background: white; color: #e3a693; padding: 5px 15px; text-decoration: none; font-weight: bold; border-radius: 3px; font-size: 10px; box-shadow: 3px 3px 0 #c98f7a; }

        /* FIXED & FULL LENGTH SIDEBAR */
        .sidebar { 
            width: 250px; 
            background: #fdfaf9; 
            border-right: 4px solid #c98f7a; 
            position: fixed; 
            top: 70px; 
            bottom: 0; 
            left: 0;
            padding: 30px 0;
            z-index: 999;
        }
        .nav-item { 
            padding: 15px 30px; 
            color: #5d4037; 
            text-decoration: none; 
            display: block; 
            font-weight: bold; 
            font-size: 12px; 
            letter-spacing: 1px;
            transition: 0.2s;
        }
        .nav-item:hover, .nav-item.active { background: #e3a693; color: white; border-right: 10px solid #c98f7a; }

        /* CONTENT AREA */
        .main-content { 
            margin-top: 70px; 
            margin-left: 250px; 
            padding: 40px; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            flex: 1;
        }
        
        .settings-grid { 
            width: 100%; 
            max-width: 950px; 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); 
            gap: 30px; 
        }
        
        .card { 
            background: #fdfaf9; 
            border: 3px solid #c98f7a; 
            box-shadow: 10px 10px 0 #c98f7a; 
            padding: 30px; 
            display: flex; 
            flex-direction: column; 
        }
        
        h2 { color: #5d4037; font-size: 15px; margin-bottom: 25px; border-bottom: 2px dashed #e3a693; padding-bottom: 5px; text-transform: uppercase; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-size: 10px; font-weight: bold; color: #c98f7a; margin-bottom: 5px; text-transform: uppercase; }
        input { width: 100%; padding: 12px; border: 2px solid #e8d6cf; font-family: inherit; font-size: 13px; background: white; outline: none; }
        input:focus { border-color: #c98f7a; }
        
        .btn-save { 
            width: 100%; 
            background: #5d4037; 
            color: white; 
            border: none; 
            padding: 14px; 
            font-weight: bold; 
            cursor: pointer; 
            box-shadow: 4px 4px 0 #c98f7a; 
            font-size: 11px; 
            text-transform: uppercase; 
            margin-top: 20px; 
            transition: 0.2s;
        }
        .btn-save:hover { background: #e3a693; transform: translate(-2px, -2px); }
        .btn-save:active { transform: translate(2px, 2px); box-shadow: 2px 2px 0 #c98f7a; }
        
        .alert { width: 100%; max-width: 950px; padding: 15px; margin-bottom: 20px; font-size: 12px; font-weight: bold; text-align: center; border-left: 6px solid; }
        .success { background: #d4edda; color: #155724; border-color: #28a745; }
        .error { background: #f8d7da; color: #721c24; border-color: #dc3545; }
    </style>
</head>
<body>

    <header class="top-nav">
        <div class="logo">Q-RIOUS</div>
        <div style="font-size: 11px;">WELCOME, <strong><?php echo strtoupper(htmlspecialchars($userName)); ?></strong>! <a href="../logout.php" class="logout-btn" style="margin-left:15px;">LOGOUT</a></div>
    </header>

    <div class="main-layout">
        <nav class="sidebar">
            <a href="instructor_dashboard.php" class="nav-item">DASHBOARD</a>
            <a href="my_quizzes.php" class="nav-item">MY QUIZZES</a>
            <a href="create_quiz.php" class="nav-item">CREATE QUIZ</a>
            <a href="view_reports.php" class="nav-item">VIEW REPORTS</a>
            <a href="settings.php" class="nav-item active">SETTINGS</a>
        </nav>

        <main class="main-content">
            <?php if($msg) echo "<div class='alert success'>$msg</div>"; ?>
            <?php if($error) echo "<div class='alert error'>$error</div>"; ?>

            <div class="settings-grid">
                <div class="card">
                    <h2>👤 INSTRUCTOR PROFILE</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label>FULL NAME</label>
                            <input type="text" name="fullname" value="<?php echo htmlspecialchars($userName); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>USERNAME</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" required>
                        </div>
                        <button type="submit" name="update_profile" class="btn-save">SAVE PROFILE</button>
                    </form>
                </div>

                <div class="card">
                    <h2>🔐 SECURITY</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label>CURRENT PASSWORD</label>
                            <input type="password" name="current_password" required placeholder="••••••••">
                        </div>
                        <div class="form-group">
                            <label>NEW PASSWORD</label>
                            <input type="password" name="new_password" required placeholder="Enter new password">
                        </div>
                        <div class="form-group">
                            <label>CONFIRM NEW PASSWORD</label>
                            <input type="password" name="confirm_password" required placeholder="Repeat new password">
                        </div>
                        <button type="submit" name="change_password" class="btn-save" style="background:#c98f7a;">UPDATE PASSWORD</button>
                    </form>
                </div>
            </div>
            
            <a href="instructor_dashboard.php" style="margin-top: 30px; color:#c98f7a; font-size: 11px; text-decoration:none; font-weight:bold;">← BACK TO DASHBOARD</a>
        </main>
    </div>

</body>
</html>