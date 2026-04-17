<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../dbconnect.php';

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Fetch Quiz ID from URL and validate
$quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;
if ($quiz_id == 0) {
    header('Location: manage_quizzes.php');
    exit;
}

// Kunin ang details ng quiz
$quiz_res = $conn->query("SELECT * FROM quizzes WHERE id = $quiz_id");
$quiz = $quiz_res->fetch_assoc();

if (!$quiz) {
    header('Location: manage_quizzes.php');
    exit;
}

// Kunin ang existing questions para hindi maulit
$existing_res = $conn->query("SELECT question_text FROM questions WHERE quiz_id = $quiz_id");
$existing_questions = [];
while($row = $existing_res->fetch_assoc()) {
    $existing_questions[] = strtolower(trim($row['question_text']));
}

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_questions'])) {
    if (isset($_POST['q']) && is_array($_POST['q'])) {
        
        $errors = [];
        $success_count = 0;
        
        foreach ($_POST['q'] as $idx => $text) {
            if (empty(trim($text))) continue;
            
            $q_text = mysqli_real_escape_string($conn, trim($text));

            // Validation: Skip if question text is already in the database for this quiz
            if (in_array(strtolower($q_text), $existing_questions)) {
                $errors[] = "Duplicate: " . htmlspecialchars(substr($q_text, 0, 50));
                continue;
            }

            // Capture options for Multiple Choice; default to empty for other types
            $q_type = isset($_POST['type'][$idx]) ? mysqli_real_escape_string($conn, $_POST['type'][$idx]) : 'Short Answer';
            $correct = isset($_POST['correct'][$idx]) ? mysqli_real_escape_string($conn, $_POST['correct'][$idx]) : '';
            
            $a = isset($_POST['a'][$idx]) ? mysqli_real_escape_string($conn, $_POST['a'][$idx]) : '';
            $b = isset($_POST['b'][$idx]) ? mysqli_real_escape_string($conn, $_POST['b'][$idx]) : '';
            $c = isset($_POST['c'][$idx]) ? mysqli_real_escape_string($conn, $_POST['c'][$idx]) : '';
            $d = isset($_POST['d'][$idx]) ? mysqli_real_escape_string($conn, $_POST['d'][$idx]) : '';
            
            $sql = "INSERT INTO questions (quiz_id, question_text, question_type, option_a, option_b, option_c, option_d, correct_answer) 
                    VALUES ($quiz_id, '$q_text', '$q_type', '$a', '$b', '$c', '$d', '$correct')";
            
            if (mysqli_query($conn, $sql)) {
                $success_count++;
                $existing_questions[] = strtolower($q_text);
            } else {
                $errors[] = "DB Error: " . htmlspecialchars(substr($q_text, 0, 50));
            }
        }
        
        if ($success_count > 0) {
            $success_msg = "$success_count question(s) saved successfully!";
        }
        if (!empty($errors)) {
            $error_msg = "Errors: " . implode(", ", $errors);
        }
        
        if ($success_count > 0) {
            header("refresh:2; url=manage_quizzes.php?msg=" . urlencode($success_msg));
        }
    }
}

$mc_count = intval($quiz['mc_count'] ?? 0);
$tf_count = intval($quiz['tf_count'] ?? 0);
$sa_count = intval($quiz['sa_count'] ?? 0);
$fb_count = intval($quiz['fb_count'] ?? 0);
$total_questions = $mc_count + $tf_count + $sa_count + $fb_count;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Add Questions | Q-RIOUS</title>
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
        
        .logout-btn {
            background: #fdfaf9;
            color: #c98f7a;
            padding: 8px 15px;
            text-decoration: none;
            font-weight: bold;
            font-size: 11px;
            border: 2px solid #c98f7a;
            box-shadow: 3px 3px 0 #c98f7a;
        }

        /* SIDEBAR - same style as manage_users */
        .sidebar {
            width: 250px;
            background: #fdfaf9;
            border-right: 4px solid #c98f7a;
            position: fixed;
            top: 70px;
            bottom: 0;
            padding: 30px 15px;
            z-index: 999;
        }
        
        .nav-item {
            display: block;
            padding: 15px;
            color: #5d4037;
            text-decoration: none;
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 13px;
            transition: 0.2s;
            border: 2px solid transparent;
        }
        
        .nav-item:hover, .nav-item.active {
            background: #fff;
            border: 2px solid #e8d6cf;
            box-shadow: 4px 4px 0 #e8d6cf;
            transform: translate(-2px, -2px);
        }
        
        .nav-item.active {
            background: #e3a693;
            color: white;
            border-color: #c98f7a;
            box-shadow: 4px 4px 0 #c98f7a;
        }

        /* MAIN CONTENT */
        .main-content {
            margin-top: 70px;
            margin-left: 250px;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
        }
        
        /* CONTAINER - with floating animation */
        .container {
            width: 100%;
            max-width: 900px;
            background: #fdfaf9;
            border: 3px solid #c98f7a;
            padding: 40px;
            box-shadow: 12px 12px 0 #c98f7a;
            animation: float 4s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        /* QUESTION BLOCKS */
        .q-block {
            background: white;
            border: 2px solid #e8d6cf;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 5px 5px 0 #e8d6cf;
            position: relative;
        }
        
        .q-header {
            font-weight: bold;
            color: #5d4037;
            margin-bottom: 15px;
            display: block;
            letter-spacing: 1px;
            border-bottom: 1px dashed #c98f7a;
            padding-bottom: 5px;
        }
        
        input[type="text"], input[type="number"] {
            width: 100%;
            padding: 12px;
            margin-top: 5px;
            border: 2px solid #e8d6cf;
            font-family: inherit;
            font-size: 13px;
            outline: none;
        }
        
        input[type="text"]:focus, input[type="number"]:focus {
            border-color: #e3a693;
        }

        /* OPTIONS GRID - responsive */
        .options-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 15px;
        }
        
        .radio-group {
            margin-top: 15px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            background: #fffaf9;
            padding: 10px;
            border: 1px solid #e8d6cf;
        }
        
        .radio-label {
            display: flex;
            align-items: center;
            font-size: 13px;
            font-weight: bold;
            color: #5d4037;
            cursor: pointer;
        }
        
        .radio-label input {
            margin-right: 8px;
            accent-color: #e3a693;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .btn-save {
            background: #e3a693;
            color: white;
            padding: 20px;
            border: none;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            box-shadow: 6px 6px 0 #c98f7a;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 20px;
        }
        
        .btn-save:active {
            transform: translate(2px, 2px);
            box-shadow: 3px 3px 0 #c98f7a;
        }
        
        .helper-text {
            font-size: 10px;
            color: #c98f7a;
            margin-top: 5px;
            font-style: italic;
        }
        
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 13px;
            border-radius: 4px;
        }
        
        .warning-box strong {
            color: #856404;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
            border-left: 4px solid #28a745;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
            border-left: 4px solid #dc3545;
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
            .container {
                padding: 25px 20px;
            }
            .q-block {
                padding: 20px 15px;
            }
            .options-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }
        }
        
        /* Extra small devices */
        @media (max-width: 480px) {
            .q-header {
                font-size: 12px;
            }
            .radio-group {
                gap: 12px;
            }
            .radio-label {
                font-size: 11px;
            }
            .btn-save {
                padding: 15px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>

    <header class="top-bar">
        <div class="logo">Q-RIOUS</div>
        <a href="../logout.php" class="logout-btn">LOGOUT</a>
    </header>

    <nav class="sidebar">
        <a href="admin_dashboard.php" class="nav-item">DASHBOARD</a>
        <a href="manage_users.php" class="nav-item">MANAGE USERS</a>
        <a href="manage_quizzes.php" class="nav-item active">MANAGE QUIZZES</a>
        <a href="system_settings.php" class="nav-item">SETTINGS</a>
        <a href="reports.php" class="nav-item">VIEW REPORTS</a>
    </nav>

    <main class="main-content">
        <div class="container">
            <h2 style="color: #5d4037;">📝 ADD QUESTIONS</h2>
            <p style="margin-bottom: 10px; color: #c98f7a; font-size: 12px;">QUIZ: <strong><?php echo strtoupper(htmlspecialchars($quiz['quiz_title'] ?? '')); ?></strong></p>
            <p style="margin-bottom: 30px; color: #5d4037; font-size: 11px;">Total questions to add: <strong><?php echo $total_questions; ?></strong></p>
            
            <?php if ($success_msg): ?>
                <div class="alert-success">✅ <?php echo htmlspecialchars($success_msg); ?></div>
            <?php endif; ?>
            
            <?php if ($error_msg): ?>
                <div class="alert-error">⚠️ <?php echo htmlspecialchars($error_msg); ?></div>
            <?php endif; ?>
            
            <?php if ($total_questions == 0): ?>
                <div class="warning-box">
                    <strong>⚠️ No question types selected!</strong> 
                    <br>Please go back and edit the quiz to add question counts.
                    <br><br>
                    <a href="manage_quizzes.php" style="color: #856404; font-weight: bold;">← Back to Manage Quizzes</a>
                </div>
            <?php else: ?>
                <form method="POST">
                    <?php 
                    $display_num = 1;
                    $idx = 0;
                    ?>

                    <!-- 1. MULTIPLE CHOICE -->
                    <?php for($i = 0; $i < $mc_count; $i++): ?>
                        <div class="q-block">
                            <span class="q-header">#<?php echo $display_num++; ?> MULTIPLE CHOICE</span>
                            <input type="hidden" name="type[<?php echo $idx; ?>]" value="Multiple Choice">
                            <input type="text" name="q[<?php echo $idx; ?>]" placeholder="Enter question text..." required>
                            <div class="options-grid">
                                <input type="text" name="a[<?php echo $idx; ?>]" placeholder="Option A" required>
                                <input type="text" name="b[<?php echo $idx; ?>]" placeholder="Option B" required>
                                <input type="text" name="c[<?php echo $idx; ?>]" placeholder="Option C" required>
                                <input type="text" name="d[<?php echo $idx; ?>]" placeholder="Option D" required>
                            </div>
                            <p style="font-size:11px; margin-top:15px; font-weight:bold;">✅ CORRECT OPTION:</p>
                            <div class="radio-group">
                                <label class="radio-label"><input type="radio" name="correct[<?php echo $idx; ?>]" value="A" required> A</label>
                                <label class="radio-label"><input type="radio" name="correct[<?php echo $idx; ?>]" value="B"> B</label>
                                <label class="radio-label"><input type="radio" name="correct[<?php echo $idx; ?>]" value="C"> C</label>
                                <label class="radio-label"><input type="radio" name="correct[<?php echo $idx; ?>]" value="D"> D</label>
                            </div>
                        </div>
                    <?php $idx++; endfor; ?>

                    <!-- 2. TRUE OR FALSE -->
                    <?php for($i = 0; $i < $tf_count; $i++): ?>
                        <div class="q-block">
                            <span class="q-header">#<?php echo $display_num++; ?> TRUE OR FALSE</span>
                            <input type="hidden" name="type[<?php echo $idx; ?>]" value="True or False">
                            <input type="text" name="q[<?php echo $idx; ?>]" placeholder="Enter statement (e.g., 'The sky is green.')" required>
                            <div class="radio-group">
                                <label class="radio-label"><input type="radio" name="correct[<?php echo $idx; ?>]" value="True" required> ✅ TRUE</label>
                                <label class="radio-label"><input type="radio" name="correct[<?php echo $idx; ?>]" value="False"> ❌ FALSE</label>
                            </div>
                        </div>
                    <?php $idx++; endfor; ?>

                    <!-- 3. SHORT ANSWER / IDENTIFICATION -->
                    <?php for($i = 0; $i < $sa_count; $i++): ?>
                        <div class="q-block">
                            <span class="q-header">#<?php echo $display_num++; ?> SHORT ANSWER / IDENTIFICATION</span>
                            <input type="hidden" name="type[<?php echo $idx; ?>]" value="Short Answer">
                            <input type="text" name="q[<?php echo $idx; ?>]" placeholder="Enter question..." required>
                            <input type="text" name="correct[<?php echo $idx; ?>]" placeholder="Correct answer" required style="margin-top:10px; border-color:#e3a693;">
                            <p class="helper-text">Answer will be checked case-insensitively (e.g., "Manila" = "manila")</p>
                        </div>
                    <?php $idx++; endfor; ?>

                    <!-- 4. FILL IN THE BLANKS -->
                    <?php for($i = 0; $i < $fb_count; $i++): ?>
                        <div class="q-block">
                            <span class="q-header">#<?php echo $display_num++; ?> FILL IN THE BLANKS</span>
                            <input type="hidden" name="type[<?php echo $idx; ?>]" value="Fill in the Blanks">
                            <input type="text" name="q[<?php echo $idx; ?>]" placeholder="Ex: HTML stands for ____ Markup Language" required>
                            <p class="helper-text">💡 Use underscores (____) to indicate the blank space.</p>
                            <input type="text" name="correct[<?php echo $idx; ?>]" placeholder="Correct word/phrase" required style="margin-top:10px; border-color:#e3a693;">
                            <p class="helper-text">Answer will be checked case-insensitively</p>
                        </div>
                    <?php $idx++; endfor; ?>

                    <button type="submit" name="save_questions" class="btn-save">💾 SAVE ALL QUESTIONS →</button>
                </form>
            <?php endif; ?>
            
            <a href="manage_quizzes.php" class="back-link">← BACK TO MANAGE QUIZZES</a>
        </div>
    </main>

</body>
</html>
