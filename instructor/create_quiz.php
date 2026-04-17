<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../dbconnect.php'; 

// Security Check - Instructor access only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Instructor') {
    header('Location: ../login.php'); 
    exit;
}

// Kunin ang ID at Name mula sa session
$userName = $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Instructor';
$userId = $_SESSION['user_id'] ?? 0;

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_proceed'])) {
    // 1. Kunin ang values mula sa form
    $title = mysqli_real_escape_string($conn, trim($_POST['quiz_title']));
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $time_limit = intval($_POST['time_limit']);
    
    // 2. Question counts
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
        // I-check muna kung may duplicate na quiz
        $check_dup = mysqli_query($conn, "SELECT id FROM quizzes WHERE quiz_title = '$title' AND created_by = '$userId' AND created_at > NOW() - INTERVAL 10 SECOND");
        
        if (mysqli_num_rows($check_dup) == 0) {
            $sql = "INSERT INTO quizzes (
                        quiz_title, category, time_limit, created_by, 
                        passing_score, mc_count, tf_count, sa_count, fb_count, created_at
                    ) VALUES (
                        '$title', '$category', $time_limit, '$userId', 
                        75, $mc, $tf, $sa, $fb, NOW()
                    )";
            
            if (mysqli_query($conn, $sql)) {
                // Get the ID of the newly created quiz record to link questions in the next step
                $new_quiz_id = mysqli_insert_id($conn);
                header("Location: add_questions_instructor.php?quiz_id=" . $new_quiz_id);
                exit();
            } else {
                $error = "Database Error: " . mysqli_error($conn);
            }
        } else {
            $row = mysqli_fetch_assoc($check_dup);
            header("Location: add_questions_instructor.php?quiz_id=" . $row['id']);
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Quiz | Q-RIOUS</title>
    <style>
        * { cursor: url('https://cur.cursors-4u.net/games/gam-4/gam373.cur'), auto !important; box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Courier New', monospace; background-color: #f5e6e0; background-image: linear-gradient(#e8d6cf 1px, transparent 1px), linear-gradient(90deg, #e8d6cf 1px, transparent 1px); background-size: 30px 30px; min-height: 100vh; display: flex; flex-direction: column; }
        .top-nav { background: #e3a693; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 4px solid #c98f7a; color: white; }
        .logo { font-size: 24px; font-weight: bold; letter-spacing: 5px; text-shadow: 2px 2px 0 #c98f7a; }
        .user-info { font-size: 11px; display: flex; align-items: center; gap: 15px; }
        .logout-btn { background: #fdfaf9; color: #c98f7a; padding: 5px 15px; text-decoration: none; font-weight: bold; border: 2px solid #c98f7a; box-shadow: 3px 3px 0 #c98f7a; }
        .main-layout { display: flex; flex: 1; }
        .sidebar { width: 250px; background: rgba(253, 250, 249, 0.9); border-right: 4px solid #c98f7a; padding: 30px 0; }
        .nav-item { padding: 15px 30px; color: #5d4037; text-decoration: none; display: block; font-weight: bold; font-size: 12px; letter-spacing: 1px; transition: 0.2s; }
        .nav-item:hover, .nav-item.active { background: #e3a693; color: white; border-right: 10px solid #c98f7a; }
        .content-area { flex: 1; padding: 40px; display: flex; justify-content: center; align-items: flex-start; }
        .floating-box { width: 100%; max-width: 850px; background: #fdfaf9; border: 3px solid #c98f7a; box-shadow: 12px 12px 0 #c98f7a; padding: 45px; }
        .section-title { text-align: center; font-size: 26px; color: #5d4037; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 4px; }
        .section-subtitle { text-align: center; font-size: 10px; color: #c98f7a; margin-bottom: 40px; text-transform: uppercase; letter-spacing: 2px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 11px; font-weight: bold; color: #5d4037; margin-bottom: 8px; }
        input, select { width: 100%; padding: 12px; border: 2px solid #e8d6cf; font-family: inherit; background: #fff; outline: none; font-size: 13px; }
        input:focus, select:focus { border-color: #e3a693; }
        .dist-panel { border: 2px dashed #e8d6cf; padding: 20px; margin-top: 20px; text-align: center; background: white; }
        .dist-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-top: 15px; }
        .dist-item label { font-size: 8px; color: #c98f7a; margin-bottom: 5px; text-transform: uppercase; }
        .dist-item input { text-align: center; font-weight: bold; padding: 8px; }
        .btn-proceed { width: 100%; padding: 18px; background: #e3a693; color: white; border: none; font-weight: bold; margin-top: 30px; cursor: pointer; text-transform: uppercase; letter-spacing: 2px; box-shadow: 6px 6px 0 #c98f7a; transition: 0.3s; }
        .btn-proceed:hover { background: #d69581; transform: translate(-2px, -2px); box-shadow: 8px 8px 0 #c98f7a; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 12px; margin-bottom: 20px; border-radius: 4px; border-left: 4px solid #dc3545; font-size: 13px; }
        .helper-text { font-size: 10px; color: #c98f7a; margin-top: 5px; text-align: center; }
    </style>
</head>
<body>
    <div class="top-nav">
        <div class="logo">Q-RIOUS</div>
        <div class="user-info">
            WELCOME, <?php echo strtoupper(htmlspecialchars($userName)); ?>!
            <a href="../logout.php" class="logout-btn">LOGOUT</a>
        </div>
    </div>
    <div class="main-layout">
        <div class="sidebar">
            <a href="instructor_dashboard.php" class="nav-item">DASHBOARD</a>
            <a href="my_quizzes.php" class="nav-item">MY QUIZZES</a>
            <a href="create_quiz.php" class="nav-item active">CREATE QUIZ</a>
            <a href="view_reports.php" class="nav-item">VIEW REPORTS</a>
            <a href="settings.php" class="nav-item">SETTINGS</a>
        </div>
        <div class="content-area">
            <div class="floating-box">
                <h2 class="section-title">QUIZ CONFIG</h2>
                <p class="section-subtitle">Build your evaluation module</p>
                <?php if ($error): ?>
                    <div class="alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-group">
                        <label>📌 QUIZ TITLE</label>
                        <input type="text" name="quiz_title" placeholder="e.g. Midterm Exam in IT101" required>
                    </div>
                    <div style="display: flex; gap: 20px;">
                        <div class="form-group" style="flex: 2;">
                            <label>📂 CATEGORY</label>
                            <select name="category">
                                <option value="IT / CS">IT / CS</option>
                                <option value="General Education">GENERAL EDUCATION</option>
                                <option value="Humanities">HUMANITIES</option>
                                <option value="Mathematics">MATHEMATICS</option>
                                <option value="Science">SCIENCE</option>
                                <option value="English">ENGLISH</option>
                            </select>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>⏱️ TIME LIMIT (MINS)</label>
                            <input type="number" name="time_limit" value="30" min="1" max="180">
                        </div>
                    </div>
                    <div class="dist-panel">
                        <p style="font-size: 9px; font-weight: bold; color: #5d4037;">📊 QUESTION DISTRIBUTION</p>
                        <div class="dist-grid">
                            <div class="dist-item"><label>MULTIPLE CHOICE</label><input type="number" name="mc_count" value="1" min="0"></div>
                            <div class="dist-item"><label>TRUE/FALSE</label><input type="number" name="tf_count" value="0" min="0"></div>
                            <div class="dist-item"><label>SHORT ANSWER</label><input type="number" name="sa_count" value="0" min="0"></div>
                            <div class="dist-item"><label>FILL IN BLANKS</label><input type="number" name="fb_count" value="0" min="0"></div>
                        </div>
                        <div class="helper-text">💡 Set at least one question type to create a quiz</div>
                    </div>
                    <button type="submit" name="btn_proceed" class="btn-proceed">🚀 GENERATE & PROCEED →</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
