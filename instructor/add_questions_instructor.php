<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../dbconnect.php'; 

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Instructor') {
    header('Location: ../login.php'); 
    exit;
}

$quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;

if ($quiz_id == 0) {
    header('Location: my_quizzes.php');
    exit;
}

$quiz_res = $conn->query("SELECT * FROM quizzes WHERE id = $quiz_id");
$quiz = $quiz_res->fetch_assoc();

if (!$quiz) {
    header('Location: my_quizzes.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_questions'])) {
    if (isset($_POST['q']) && is_array($_POST['q'])) {
        $questions = $_POST['q'];
        $types = $_POST['type'];
        $correct_answers = $_POST['correct'];
        
        $conn->begin_transaction();
        try {
            // FIX: Burahin muna ang existing questions para hindi mag-doble
            $conn->query("DELETE FROM questions WHERE quiz_id = $quiz_id");

            foreach ($questions as $index => $text) {
                if (empty(trim($text))) continue;
                $q_text = mysqli_real_escape_string($conn, $text);
                $q_type = mysqli_real_escape_string($conn, $types[$index]);
                $correct = mysqli_real_escape_string($conn, $correct_answers[$index]);
                $a = isset($_POST['a'][$index]) ? mysqli_real_escape_string($conn, $_POST['a'][$index]) : '';
                $b = isset($_POST['b'][$index]) ? mysqli_real_escape_string($conn, $_POST['b'][$index]) : '';
                $c = isset($_POST['c'][$index]) ? mysqli_real_escape_string($conn, $_POST['c'][$index]) : '';
                $d = isset($_POST['d'][$index]) ? mysqli_real_escape_string($conn, $_POST['d'][$index]) : '';
                
                $sql = "INSERT INTO questions (quiz_id, question_text, question_type, option_a, option_b, option_c, option_d, correct_answer) 
                        VALUES ($quiz_id, '$q_text', '$q_type', '$a', '$b', '$c', '$d', '$correct')";
                if (!$conn->query($sql)) {
                    throw new Exception("Error: " . $conn->error);
                }
            }
            $conn->commit();
            header('Location: my_quizzes.php?status=success');
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Failed to save: " . $e->getMessage();
        }
    }
}

$userName = $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Instructor';
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
    <title>Add Questions | Q-RIOUS</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; cursor: url('https://cur.cursors-4u.net/games/gam-4/gam373.cur'), auto !important; }
        body { font-family: 'Courier New', monospace; background-color: #f5e6e0; background-image: linear-gradient(#e8d6cf 1px, transparent 1px), linear-gradient(90deg, #e8d6cf 1px, transparent 1px); background-size: 30px 30px; min-height: 100vh; }
        .top-bar { position: fixed; top: 0; left: 0; width: 100%; height: 70px; background: #e3a693; display: flex; justify-content: space-between; align-items: center; padding: 0 30px; border-bottom: 4px solid #c98f7a; z-index: 1000; }
        .logo { color: white; font-weight: bold; font-size: 24px; letter-spacing: 5px; text-shadow: 2px 2px 0 #c98f7a; }
        .user-info { font-size: 11px; color: white; display: flex; align-items: center; gap: 15px; }
        .logout-btn { background: #fdfaf9; color: #c98f7a; padding: 5px 15px; text-decoration: none; font-weight: bold; font-size: 10px; border: 2px solid #c98f7a; box-shadow: 3px 3px 0 #c98f7a; }
        .sidebar { width: 250px; background: #fdfaf9; border-right: 4px solid #c98f7a; position: fixed; top: 70px; bottom: 0; padding: 30px 15px; z-index: 999; }
        .nav-item { display: block; padding: 15px; color: #5d4037; text-decoration: none; font-weight: bold; margin-bottom: 10px; font-size: 13px; transition: 0.2s; }
        .nav-item:hover, .nav-item.active { background: #e3a693; color: white; border-right: 10px solid #c98f7a; }
        .main-content { margin-top: 70px; margin-left: 250px; padding: 40px 20px; display: flex; justify-content: center; }
        .container { width: 100%; max-width: 900px; background: #fdfaf9; border: 3px solid #c98f7a; padding: 40px; box-shadow: 12px 12px 0 #c98f7a; }
        .q-block { background: white; border: 2px solid #e8d6cf; padding: 25px; margin-bottom: 25px; box-shadow: 5px 5px 0 #e8d6cf; }
        .q-header { font-weight: bold; color: #5d4037; margin-bottom: 15px; display: block; border-bottom: 1px dashed #c98f7a; padding-bottom: 5px; }
        input[type="text"], select { width: 100%; padding: 12px; margin-top: 5px; border: 2px solid #e8d6cf; font-family: inherit; font-size: 13px; outline: none; }
        input[type="text"]:focus, select:focus { border-color: #e3a693; }
        .options-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px; }
        .btn-save { background: #e3a693; color: white; padding: 20px; border: none; font-weight: bold; cursor: pointer; width: 100%; box-shadow: 6px 6px 0 #c98f7a; font-size: 14px; text-transform: uppercase; letter-spacing: 2px; margin-top: 20px; }
        .btn-save:hover { transform: translate(-2px, -2px); box-shadow: 8px 8px 0 #c98f7a; }
        .warning-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin-bottom: 20px; font-size: 13px; }
        .helper-text { font-size: 10px; color: #c98f7a; margin-top: 5px; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 12px; margin-bottom: 20px; border-radius: 4px; border-left: 4px solid #dc3545; }
    </style>
</head>
<body>
    <header class="top-bar">
        <div class="logo">Q-RIOUS</div>
        <div class="user-info">
            HELLO, <?php echo strtoupper(htmlspecialchars($userName)); ?>
            <a href="../logout.php" class="logout-btn">LOGOUT</a>
        </div>
    </header>
    <nav class="sidebar">
        <a href="instructor_dashboard.php" class="nav-item">DASHBOARD</a>
        <a href="my_quizzes.php" class="nav-item active">MY QUIZZES</a>
        <a href="create_quiz.php" class="nav-item">CREATE QUIZ</a>
        <a href="view_reports.php" class="nav-item">VIEW REPORTS</a>
        <a href="settings.php" class="nav-item">SETTINGS</a>
    </nav>
    <main class="main-content">
        <div class="container">
            <h2 style="color: #5d4037;">📝 ADD QUESTIONS</h2>
            <p style="margin-bottom: 10px; color: #c98f7a; font-size: 12px;">QUIZ: <strong><?php echo strtoupper(htmlspecialchars($quiz['quiz_title'])); ?></strong></p>
            <p style="margin-bottom: 30px; color: #5d4037; font-size: 11px;">Total questions: <strong><?php echo $total_questions; ?></strong></p>
            
            <?php if (isset($error)): ?>
                <div class="alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($total_questions == 0): ?>
                <div class="warning-box">
                    <strong>⚠️ No question types selected!</strong><br>
                    Please go back and edit the quiz to add question counts.<br><br>
                    <a href="my_quizzes.php" style="color: #856404;">← Back to My Quizzes</a>
                </div>
            <?php else: ?>
                <form method="POST">
                    <?php $count = 1; $idx = 0; ?>
                    
                    <?php for($i=0; $i < $mc_count; $i++): ?>
                        <div class="q-block">
                            <span class="q-header">#<?php echo $count++; ?> MULTIPLE CHOICE</span>
                            <input type="hidden" name="type[<?php echo $idx; ?>]" value="Multiple Choice">
                            <input type="text" name="q[<?php echo $idx; ?>]" placeholder="Enter question text..." required>
                            <div class="options-grid">
                                <input type="text" name="a[<?php echo $idx; ?>]" placeholder="Option A" required>
                                <input type="text" name="b[<?php echo $idx; ?>]" placeholder="Option B" required>
                                <input type="text" name="c[<?php echo $idx; ?>]" placeholder="Option C" required>
                                <input type="text" name="d[<?php echo $idx; ?>]" placeholder="Option D" required>
                            </div>
                            <label style="font-size: 11px; margin-top: 15px; display: block; font-weight: bold;">✅ CORRECT OPTION:</label>
                            <select name="correct[<?php echo $idx; ?>]" required>
                                <option value="A">A</option><option value="B">B</option>
                                <option value="C">C</option><option value="D">D</option>
                            </select>
                        </div>
                    <?php $idx++; endfor; ?>

                    <?php for($i=0; $i < $tf_count; $i++): ?>
                        <div class="q-block">
                            <span class="q-header">#<?php echo $count++; ?> TRUE OR FALSE</span>
                            <input type="hidden" name="type[<?php echo $idx; ?>]" value="True or False">
                            <input type="text" name="q[<?php echo $idx; ?>]" placeholder="Enter statement..." required>
                            <label style="font-size: 11px; margin-top: 15px; display: block; font-weight: bold;">✅ CORRECT ANSWER:</label>
                            <select name="correct[<?php echo $idx; ?>]" required>
                                <option value="True">✅ TRUE</option>
                                <option value="False">❌ FALSE</option>
                            </select>
                        </div>
                    <?php $idx++; endfor; ?>

                    <?php for($i=0; $i < $sa_count; $i++): ?>
                        <div class="q-block">
                            <span class="q-header">#<?php echo $count++; ?> SHORT ANSWER / IDENTIFICATION</span>
                            <input type="hidden" name="type[<?php echo $idx; ?>]" value="Short Answer">
                            <input type="text" name="q[<?php echo $idx; ?>]" placeholder="Enter question..." required>
                            <label style="font-size: 11px; margin-top: 15px; display: block; font-weight: bold;">✅ CORRECT ANSWER:</label>
                            <input type="text" name="correct[<?php echo $idx; ?>]" placeholder="Enter exact correct answer" required>
                            <p class="helper-text">Case-insensitive check</p>
                        </div>
                    <?php $idx++; endfor; ?>

                    <?php for($i=0; $i < $fb_count; $i++): ?>
                        <div class="q-block">
                            <span class="q-header">#<?php echo $count++; ?> FILL IN THE BLANKS</span>
                            <input type="hidden" name="type[<?php echo $idx; ?>]" value="Fill in the Blanks">
                            <input type="text" name="q[<?php echo $idx; ?>]" placeholder="Use ____ for blank. Ex: HTML stands for ____ Markup Language" required>
                            <label style="font-size: 11px; margin-top: 15px; display: block; font-weight: bold;">✅ CORRECT ANSWER:</label>
                            <input type="text" name="correct[<?php echo $idx; ?>]" placeholder="Enter missing word/phrase" required>
                            <p class="helper-text">Case-insensitive check</p>
                        </div>
                    <?php $idx++; endfor; ?>

                    <button type="submit" name="save_questions" class="btn-save">💾 SAVE ALL QUESTIONS →</button>
                </form>
            <?php endif; ?>
            <a href="my_quizzes.php" style="display: block; text-align: center; margin-top: 30px; color: #c98f7a; text-decoration: none; font-size: 11px;">[ CANCEL AND RETURN ]</a>
        </div>
    </main>
</body>
</html>