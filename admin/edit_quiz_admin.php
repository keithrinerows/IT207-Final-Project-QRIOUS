<?php
session_start();
require_once '../dbconnect.php';

// Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin' || !isset($_GET['id'])) {
    header('Location: manage_quizzes.php');
    exit;
}

$quiz_id = intval($_GET['id']);
$userName = $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Admin';

// --- LOGIC: DELETE QUESTION ---
if (!empty($_GET['delete_qid'])) {
    $delete_id = intval($_GET['delete_qid']);
    $del_stmt = $conn->prepare("DELETE FROM questions WHERE id = ? AND quiz_id = ?");
    $del_stmt->bind_param("ii", $delete_id, $quiz_id);
    if ($del_stmt->execute()) {
        header("Location: edit_quiz_admin.php?id=$quiz_id&msg=deleted");
        exit;
    }
}

// --- LOGIC: UPDATE QUIZ CONFIG ---
if (isset($_POST['btn_update_config'])) {
    $title = mysqli_real_escape_string($conn, $_POST['quiz_title']);
    $cat = mysqli_real_escape_string($conn, $_POST['category']);
    $time = intval($_POST['time_limit']);

    $sql = "UPDATE quizzes SET quiz_title='$title', category='$cat', time_limit=$time WHERE id=$quiz_id";
    if ($conn->query($sql)) {
        $msg_config = "Configuration Updated!";
    }
}

// --- LOGIC: UPDATE INDIVIDUAL QUESTION ---
if (isset($_POST['btn_update_question'])) {
    $qid = intval($_POST['question_id']);
    $qtext = mysqli_real_escape_string($conn, $_POST['question_text']);
    $correct = mysqli_real_escape_string($conn, $_POST['correct_answer']);
    
    $oa = isset($_POST['option_a']) ? mysqli_real_escape_string($conn, $_POST['option_a']) : "";
    $ob = isset($_POST['option_b']) ? mysqli_real_escape_string($conn, $_POST['option_b']) : "";
    $oc = isset($_POST['option_c']) ? mysqli_real_escape_string($conn, $_POST['option_c']) : "";
    $od = isset($_POST['option_d']) ? mysqli_real_escape_string($conn, $_POST['option_d']) : "";

    $upd_q = "UPDATE questions SET question_text=?, option_a=?, option_b=?, option_c=?, option_d=?, correct_answer=? WHERE id=? AND quiz_id=?";
    $stmt = $conn->prepare($upd_q);
    $stmt->bind_param("ssssssii", $qtext, $oa, $ob, $oc, $od, $correct, $qid, $quiz_id);
    
    if ($stmt->execute()) {
        $msg_q = "Question #$qid Updated!";
    }
}

// Fetch Data
$quiz = $conn->query("SELECT * FROM quizzes WHERE id = $quiz_id")->fetch_assoc();
$questions_result = $conn->query("SELECT * FROM questions WHERE quiz_id = $quiz_id ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Edit Quiz | Q-RIOUS</title>
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

        /* SIDEBAR */
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
            flex-direction: column;
            align-items: center;
        }
        
        /* CONTAINER - with floating animation */
        .container {
            width: 100%;
            max-width: 950px;
            background: #fdfaf9;
            border: 3px solid #c98f7a;
            box-shadow: 12px 12px 0 #c98f7a;
            padding: 40px;
            margin-bottom: 30px;
            animation: float 4s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        
        h2 {
            color: #5d4037;
            letter-spacing: 3px;
            margin-bottom: 25px;
            border-bottom: 2px solid #e3a693;
            display: inline-block;
            font-size: 18px;
        }
        
        /* FORMS */
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        label {
            display: block;
            font-size: 11px;
            font-weight: bold;
            color: #c98f7a;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e8d6cf;
            font-family: inherit;
            font-size: 13px;
            background: white;
            outline: none;
            transition: 0.2s;
        }
        
        input:focus, select:focus, textarea:focus {
            border-color: #e3a693;
        }
        
        .row-flex {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .row-flex .form-group {
            flex: 1;
            min-width: 150px;
        }
        
        .btn-save {
            background: #e3a693;
            color: white;
            border: none;
            padding: 12px 25px;
            font-weight: bold;
            box-shadow: 4px 4px 0 #c98f7a;
            text-transform: uppercase;
            transition: 0.2s;
            cursor: pointer;
            font-family: inherit;
            font-size: 12px;
        }
        
        .btn-save:hover {
            background: #d69581;
            transform: translate(-2px, -2px);
            box-shadow: 6px 6px 0 #c98f7a;
        }
        
        /* QUESTION CARDS */
        .question-card {
            background: white;
            border: 2px solid #c98f7a;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 6px 6px 0 #e8d6cf;
            position: relative;
            transition: 0.2s;
        }
        
        .question-card:hover {
            transform: translate(-2px, -2px);
            box-shadow: 10px 10px 0 #e8d6cf;
        }
        
        .q-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #e3a693;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .q-type-badge {
            font-size: 11px;
            font-weight: bold;
            color: #5d4037;
            background: #fdfaf9;
            padding: 5px 12px;
            border: 1px solid #e8d6cf;
            border-radius: 20px;
        }
        
        .btn-delete {
            color: #ff6b6b;
            text-decoration: none;
            font-size: 11px;
            font-weight: bold;
            border: 1px solid #ff6b6b;
            padding: 5px 12px;
            transition: 0.2s;
            border-radius: 4px;
        }
        
        .btn-delete:hover {
            background: #ff6b6b;
            color: white;
        }
        
        /* FIXED: Multiple Choice Options - proper text inputs */
        .options-container {
            margin-top: 15px;
            margin-bottom: 15px;
        }
        
        .option-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        
        .option-radio {
            width: 20px;
            height: 20px;
            accent-color: #e3a693;
            cursor: pointer;
            flex-shrink: 0;
        }
        
        .option-text {
            flex: 1;
            padding: 10px 12px;
            border: 2px solid #e8d6cf;
            font-family: inherit;
            font-size: 13px;
            background: white;
            outline: none;
        }
        
        .option-text:focus {
            border-color: #e3a693;
        }
        
        .option-label {
            font-weight: bold;
            color: #c98f7a;
            width: 30px;
            flex-shrink: 0;
        }
        
        .alert {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 12px 15px;
            border-left: 4px solid #2e7d32;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: bold;
        }
        
        .alert-delete {
            background: #fff3f3;
            color: #c98f7a;
            border-left-color: #c98f7a;
        }
        
        .empty-state {
            color: #c98f7a;
            text-align: center;
            border: 2px dashed #e8d6cf;
            padding: 40px;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #c98f7a;
            text-decoration: none;
            font-weight: bold;
            font-size: 12px;
            transition: 0.2s;
        }
        
        .back-link:hover {
            text-decoration: underline;
            transform: translateX(-3px);
        }
        
        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
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
            .admin-name {
                display: none;
            }
            .container {
                padding: 25px 20px;
                animation: none;
            }
            .option-row {
                flex-wrap: wrap;
            }
            .option-label {
                width: auto;
            }
            .option-text {
                min-width: 200px;
            }
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 20px 15px;
            }
            h2 {
                font-size: 16px;
            }
            .question-card {
                padding: 18px;
            }
            .option-row {
                gap: 8px;
            }
            .option-text {
                padding: 8px 10px;
                font-size: 12px;
            }
            .btn-save {
                padding: 10px 20px;
                font-size: 11px;
            }
            .row-flex {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>

    <header class="top-bar">
        <div class="logo">Q-RIOUS</div>
        <div class="top-bar-right">
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
        <!-- QUIZ CONFIGURATION SECTION -->
        <div class="container">
            <h2>⚙️ QUIZ CONFIGURATION</h2>
            <?php if(isset($msg_config)) echo "<div class='alert'>✅ $msg_config</div>"; ?>
            <form method="POST">
                <div class="form-group">
                    <label>📝 QUIZ TITLE</label>
                    <input type="text" name="quiz_title" value="<?php echo htmlspecialchars($quiz['quiz_title']); ?>" required>
                </div>
                <div class="row-flex">
                    <div class="form-group">
                        <label>📂 CATEGORY</label>
                        <select name="category">
                            <option value="IT / CS" <?php echo ($quiz['category'] == 'IT / CS') ? 'selected' : ''; ?>>IT / CS</option>
                            <option value="General Education" <?php echo ($quiz['category'] == 'General Education') ? 'selected' : ''; ?>>GENERAL EDUCATION</option>
                            <option value="Humanities" <?php echo ($quiz['category'] == 'Humanities') ? 'selected' : ''; ?>>HUMANITIES</option>
                            <option value="Mathematics" <?php echo ($quiz['category'] == 'Mathematics') ? 'selected' : ''; ?>>MATHEMATICS</option>
                            <option value="Science" <?php echo ($quiz['category'] == 'Science') ? 'selected' : ''; ?>>SCIENCE</option>
                            <option value="English" <?php echo ($quiz['category'] == 'English') ? 'selected' : ''; ?>>ENGLISH</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>⏱️ TIME LIMIT (MINS)</label>
                        <input type="number" name="time_limit" value="<?php echo $quiz['time_limit']; ?>" required>
                    </div>
                </div>
                <button type="submit" name="btn_update_config" class="btn-save">💾 UPDATE CONFIG</button>
            </form>
        </div>

        <!-- MANAGE QUESTIONS SECTION -->
        <div class="container">
            <h2>📋 MANAGE QUESTIONS</h2>
            <?php if(isset($msg_q)) echo "<div class='alert'>✅ $msg_q</div>"; ?>
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted') echo "<div class='alert alert-delete'>🗑️ Question deleted successfully!</div>"; ?>

            <?php if ($questions_result->num_rows > 0): ?>
                <?php while($q = $questions_result->fetch_assoc()): ?>
                <div class="question-card">
                    <form method="POST">
                        <input type="hidden" name="question_id" value="<?php echo $q['id']; ?>">
                        
                        <div class="q-header">
                            <span class="q-type-badge">
                                📌 #<?php echo $q['id']; ?> — <?php echo strtoupper($q['question_type']); ?>
                            </span>
                            <a href="edit_quiz_admin.php?id=<?php echo $quiz_id; ?>&delete_qid=<?php echo $q['id']; ?>" 
                               class="btn-delete" onclick="return confirm('Permanent delete this question?')">🗑️ DELETE</a>
                        </div>

                        <div class="form-group">
                            <label>📖 QUESTION TEXT</label>
                            <textarea name="question_text" rows="3" required><?php echo htmlspecialchars($q['question_text']); ?></textarea>
                        </div>

                        <?php if ($q['question_type'] == 'Multiple Choice'): ?>
                            <div class="options-container">
                                <label>🔘 OPTIONS (Select the correct answer)</label>
                                <?php 
                                $options = ['a', 'b', 'c', 'd'];
                                foreach($options as $opt): 
                                    $opt_upper = strtoupper($opt);
                                    $field_name = "option_" . $opt;
                                ?>
                                <div class="option-row">
                                    <input type="radio" name="correct_answer" value="<?php echo $opt_upper; ?>" 
                                           class="option-radio" <?php echo ($q['correct_answer'] == $opt_upper) ? 'checked' : ''; ?> required>
                                    <span class="option-label"><?php echo $opt_upper; ?>.</span>
                                    <input type="text" name="option_<?php echo $opt; ?>" 
                                           class="option-text" 
                                           value="<?php echo htmlspecialchars($q['option_'.$opt] ?? ''); ?>" 
                                           placeholder="Option <?php echo $opt_upper; ?>">
                                </div>
                                <?php endforeach; ?>
                            </div>

                        <?php elseif ($q['question_type'] == 'True or False'): ?>
                            <div class="form-group">
                                <label>✅ CORRECT ANSWER</label>
                                <select name="correct_answer">
                                    <option value="True" <?php echo ($q['correct_answer'] == 'True') ? 'selected' : ''; ?>>True</option>
                                    <option value="False" <?php echo ($q['correct_answer'] == 'False') ? 'selected' : ''; ?>>False</option>
                                </select>
                            </div>

                        <?php else: ?>
                            <div class="form-group">
                                <label>✅ CORRECT ANSWER (EXACT TEXT)</label>
                                <input type="text" name="correct_answer" value="<?php echo htmlspecialchars($q['correct_answer']); ?>" required>
                                <p style="font-size: 10px; color: #c98f7a; margin-top: 5px;">💡 Answer will be checked case-insensitively</p>
                            </div>
                        <?php endif; ?>

                        <button type="submit" name="btn_update_question" class="btn-save" style="margin-top: 20px;">
                            💾 SAVE QUESTION #<?php echo $q['id']; ?>
                        </button>
                    </form>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>📭 No questions found for this quiz.</p>
                    <p style="margin-top: 10px; font-size: 11px;">Go back to add questions or create a new quiz.</p>
                </div>
            <?php endif; ?>

            <div style="margin-top: 30px;">
                <a href="manage_quizzes.php" class="back-link">← RETURN TO QUIZ LIST</a>
            </div>
        </div>
    </main>

</body>
</html>
