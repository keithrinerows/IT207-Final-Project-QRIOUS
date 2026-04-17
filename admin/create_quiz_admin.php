<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../dbconnect.php'; 

// Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../login.php'); 
    exit;
}

// Initialize user context for the header and creator identification
$userName = $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Admin';
$admin_id = $_SESSION['user_id'] ?? 0;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_proceed'])) {
    
    $title = mysqli_real_escape_string($conn, trim($_POST['quiz_title']));
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $time_limit = intval($_POST['time_limit']); 
    
    $mc = isset($_POST['mc_count']) ? intval($_POST['mc_count']) : 0;
    $tf = isset($_POST['tf_count']) ? intval($_POST['tf_count']) : 0;
    $sa = isset($_POST['sa_count']) ? intval($_POST['sa_count']) : 0;
    $fb = isset($_POST['fb_count']) ? intval($_POST['fb_count']) : 0;
    
    // Validate: Dapat may kahit isang tanong
    if ($mc == 0 && $tf == 0 && $sa == 0 && $fb == 0) {
        $error = "Please select at least one question type!";
    } elseif (empty($title)) {
        $error = "Please enter a quiz title!";
    } else {
        $sql = "INSERT INTO quizzes (
                    quiz_title, 
                    category, 
                    time_limit, 
                    created_by, 
                    passing_score, 
                    mc_count, 
                    tf_count, 
                    sa_count, 
                    fb_count, 
                    created_at
                ) VALUES (
                    '$title', 
                    '$category', 
                    $time_limit, 
                    $admin_id, 
                    75, 
                    $mc, 
                    $tf, 
                    $sa, 
                    $fb, 
                    NOW()
                )";
        
        if (mysqli_query($conn, $sql)) {
            // Retrieve the ID of the newly created quiz record
            $new_quiz_id = mysqli_insert_id($conn);
            // Redirect to add questions page
            header("Location: add_questions_admin.php?quiz_id=" . $new_quiz_id);
            exit();
        } else {
            $error = "Database Error: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Admin | Create Quiz - Q-RIOUS</title>
    <style>
        * {
            cursor: url('https://cur.cursors-4u.net/games/gam-4/gam373.cur'), auto !important;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Courier New', monospace;
            background-color: #f5e6e0;
            background-image: linear-gradient(#e8d6cf 1px, transparent 1px),
                              linear-gradient(90deg, #e8d6cf 1px, transparent 1px);
            background-size: 30px 30px;
            min-height: 100vh;
        }
        
        /* FIXED TOP BAR */
        .top-bar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 70px;
            background: #e3a693;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 30px;
            border-bottom: 4px solid #c98f7a;
            z-index: 1000;
        }
        
        .logo {
            color: white;
            font-weight: bold;
            font-size: 24px;
            letter-spacing: 5px;
            text-shadow: 2px 2px 0 #c98f7a;
        }
        
        .admin-name {
            color: white;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: bold;
        }
        
        .logout-btn {
            background: #fdfaf9;
            color: #c98f7a;
            padding: 8px 15px;
            text-decoration: none;
            font-weight: bold;
            font-size: 11px;
            border: 2px solid #c98f7a;
            box-shadow: 3px 3px 0 #c98f7a;
            margin-left: 15px;
        }
        
        /* SIDEBAR - same style as manage_users */
        .sidebar {
            width: 250px;
            background: #fdfaf9;
            border-right: 4px solid #c98f7a;
            position: fixed;
            top: 70px;
            bottom: 0;
            padding: 30px 0;
            z-index: 999;
        }
        
        .nav-item {
            display: block;
            padding: 15px 30px;
            color: #5d4037;
            text-decoration: none;
            font-weight: bold;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: 0.2s;
            border: 2px solid transparent;
        }
        
        .nav-item:hover {
            background: #fff;
            border-color: #e8d6cf;
            box-shadow: 4px 4px 0 #e8d6cf;
            transform: translate(-2px, -2px);
        }
        
        .nav-item.active {
            background: #e3a693;
            color: white;
            border-color: #c98f7a;
        }
        
        /* MAIN CONTENT */
        .main-content {
            margin-top: 70px;
            margin-left: 250px;
            padding: 40px;
            display: flex;
            justify-content: center;
        }
        
        /* FLOATING BOX - same animation as manage_users */
        .floating-box {
            width: 100%;
            max-width: 900px;
            background: #fdfaf9;
            border: 3px solid #c98f7a;
            box-shadow: 12px 12px 0 #c98f7a;
            padding: 45px;
            animation: float 4s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .form-title {
            text-align: center;
            font-size: 28px;
            color: #5d4037;
            margin-bottom: 5px;
            letter-spacing: 4px;
        }
        
        .form-subtitle {
            text-align: center;
            font-size: 10px;
            color: #c98f7a;
            margin-bottom: 40px;
            letter-spacing: 2px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            font-size: 11px;
            font-weight: bold;
            color: #5d4037;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        input, select {
            width: 100%;
            padding: 14px;
            border: 2px solid #e8d6cf;
            font-family: inherit;
            background: white;
            outline: none;
        }
        
        input:focus, select:focus {
            border-color: #e3a693;
        }
        
        /* Flex row for category and time limit - responsive */
        .row-flex {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .row-flex .form-group {
            flex: 1;
            min-width: 150px;
        }
        
        .dist-panel {
            border: 2px dashed #e8d6cf;
            padding: 25px;
            margin-top: 20px;
            text-align: center;
        }
        
        .dist-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-top: 15px;
        }
        
        .dist-item label {
            font-size: 9px;
            color: #c98f7a;
            margin-bottom: 5px;
            display: block;
            font-weight: bold;
        }
        
        .dist-item input {
            text-align: center;
            font-weight: bold;
            padding: 10px;
        }
        
        .btn-proceed {
            width: 100%;
            padding: 20px;
            background: #e3a693;
            color: white;
            border: none;
            font-weight: bold;
            margin-top: 30px;
            cursor: pointer;
            text-transform: uppercase;
            box-shadow: 4px 4px 0 #c98f7a;
            transition: 0.2s;
        }
        
        .btn-proceed:hover {
            background: #d69581;
            transform: translate(-2px, -2px);
            box-shadow: 6px 6px 0 #c98f7a;
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            font-size: 11px;
            color: #c98f7a;
            text-decoration: none;
            font-weight: bold;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 13px;
            border-left: 4px solid #dc3545;
        }
        
        .helper-text {
            font-size: 10px;
            color: #c98f7a;
            margin-top: 5px;
            text-align: center;
        }
        
        /* ========== RESPONSIVE: SAME AS MANAGE_USERS.PHP ========== */
        @media (max-width: 768px) {
            /* Sidebar becomes horizontal navigation at the top */
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                top: 70px;
                border-right: none;
                display: flex;
                overflow-x: auto;
                padding: 10px;
                white-space: nowrap;
            }
            .nav-item {
                display: inline-block;
                margin-bottom: 0;
                margin-right: 10px;
                white-space: nowrap;
                padding: 12px 20px;
            }
            .main-content {
                margin-left: 0;
                padding: 20px 15px;
                padding-top: 20px;
            }
            .logo {
                font-size: 18px;
                letter-spacing: 2px;
            }
            .top-bar {
                padding: 0 15px;
            }
            .floating-box {
                padding: 25px 20px;
            }
            .form-title {
                font-size: 22px;
            }
            /* Responsive grid for question distribution */
            .dist-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
        }
        
        /* Extra small devices */
        @media (max-width: 480px) {
            .dist-grid {
                grid-template-columns: 1fr;
            }
            .row-flex {
                flex-direction: column;
                gap: 0;
            }
            .admin-name {
                display: none;
            }
        }
    </style>
</head>
<body>

    <header class="top-bar">
        <div class="logo">Q-RIOUS</div>
        <div style="display: flex; align-items: center;">
            <div class="admin-name">ADMIN: <?php echo strtoupper(htmlspecialchars($userName)); ?></div>
            <a href="../logout.php" class="logout-btn">LOGOUT</a>
        </div>
    </header>

    <nav class="sidebar">
        <a href="admin_dashboard.php" class="nav-item">DASHBOARD</a>
        <a href="manage_users.php" class="nav-item">MANAGE USERS</a>
        <a href="manage_quizzes.php" class="nav-item active">MANAGE QUIZZES</a>
        <a href="system_settings.php" class="nav-item">SETTINGS</a>
        <a href="reports.php" class="nav-item">VIEW REPORTS</a>
    </nav>

    <main class="main-content">
        <div class="floating-box">
            <h2 class="form-title">NEW QUIZ CONFIG</h2>
            <p class="form-subtitle">ADMIN CONTROL PANEL</p>
            
            <?php if ($error): ?>
                <div class="alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label>📌 QUIZ TITLE</label>
                    <input type="text" name="quiz_title" placeholder="Enter quiz title..." required>
                </div>

                <div class="row-flex">
                    <div class="form-group">
                        <label>📂 CATEGORY</label>
                        <select name="category" required>
                            <option value="IT / CS">IT / CS</option>
                            <option value="General Education">GENERAL EDUCATION</option>
                            <option value="Humanities">HUMANITIES</option>
                            <option value="Mathematics">MATHEMATICS</option>
                            <option value="Science">SCIENCE</option>
                            <option value="English">ENGLISH</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>⏱️ TIME LIMIT (MINS)</label>
                        <input type="number" name="time_limit" value="30" min="1" max="180" required>
                    </div>
                </div>

                <div class="dist-panel">
                    <p style="font-size: 10px; font-weight: bold; color: #5d4037; letter-spacing: 1px;">📊 QUESTION DISTRIBUTION</p>
                    <div class="dist-grid">
                        <div class="dist-item">
                            <label>MULTIPLE CHOICE</label>
                            <input type="number" name="mc_count" value="1" min="0">
                        </div>
                        <div class="dist-item">
                            <label>TRUE/FALSE</label>
                            <input type="number" name="tf_count" value="0" min="0">
                        </div>
                        <div class="dist-item">
                            <label>SHORT ANSWER / IDENTIFICATION</label>
                            <input type="number" name="sa_count" value="0" min="0">
                        </div>
                        <div class="dist-item">
                            <label>FILL IN THE BLANKS</label>
                            <input type="number" name="fb_count" value="0" min="0">
                        </div>
                    </div>
                    <div class="helper-text">💡 Set at least one question type to create a quiz</div>
                </div>

                <button type="submit" name="btn_proceed" class="btn-proceed">🚀 GENERATE & PROCEED →</button>
            </form>
            
            <a href="manage_quizzes.php" class="back-link">← BACK TO MANAGE QUIZZES</a>
        </div>
    </main>

</body>
</html>
