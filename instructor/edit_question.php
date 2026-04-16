<?php
session_start();
require_once '../dbconnect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Instructor') {
    header('Location: ../login.php');
    exit;
}

if (!isset($_GET['qid']) || empty($_GET['qid'])) {
    header("Location: my_quizzes.php?msg=MissingQuestionID");
    exit;
}

$qid = (int)$_GET['qid'];
$quiz_id = (int)$_GET['quiz_id'];

// Fetch the question
$query = "SELECT * FROM questions WHERE id = $qid";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) die("Question not found!");
$question = mysqli_fetch_assoc($result);

// Handle update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $question_text = mysqli_real_escape_string($conn, $_POST['question_text']);
    $question_type = mysqli_real_escape_string($conn, $_POST['question_type']);
    $correct = mysqli_real_escape_string($conn, $_POST['correct_answer']);

    if ($question_type == 'Multiple Choice') {
        $opt_a = mysqli_real_escape_string($conn, $_POST['option_a']);
        $opt_b = mysqli_real_escape_string($conn, $_POST['option_b']);
        $opt_c = mysqli_real_escape_string($conn, $_POST['option_c']);
        $opt_d = mysqli_real_escape_string($conn, $_POST['option_d']);

        $update = "UPDATE questions SET 
                   question_text='$question_text',
                   question_type='$question_type',
                   option_a='$opt_a',
                   option_b='$opt_b',
                   option_c='$opt_c',
                   option_d='$opt_d',
                   correct_answer='$correct'
                   WHERE id=$qid";
    } else { // Identification
        $update = "UPDATE questions SET 
                   question_text='$question_text',
                   question_type='$question_type',
                   correct_answer='$correct'
                   WHERE id=$qid";
    }

    if (mysqli_query($conn, $update)) {
        header("Location: manage_questions.php?quiz_id=$quiz_id&msg=updated");
        exit;
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}

$userName = $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Instructor';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Question | Q-RIOUS</title>
    <style>
        * { cursor: url('https://cur.cursors-4u.net/games/gam-4/gam373.cur'), auto !important; box-sizing: border-box; margin:0; padding:0; }
        body { font-family: 'Courier New', monospace; background-color: #f5e6e0; background-image: linear-gradient(#e8d6cf 1px, transparent 1px), linear-gradient(90deg, #e8d6cf 1px, transparent 1px); background-size: 30px 30px; min-height: 100vh; display: flex; flex-direction: column; }
        .top-nav { background: #e3a693; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 4px solid #c98f7a; color: white; }
        .logo { font-size: 24px; font-weight: bold; letter-spacing: 5px; }
        .user-info { font-size: 11px; display: flex; align-items: center; gap: 15px; }
        .logout-btn { background: white; color: #e3a693; padding: 5px 15px; text-decoration: none; font-weight: bold; border-radius: 3px; }
        .main-layout { display: flex; flex: 1; }
        .sidebar { width: 250px; background: rgba(253, 250, 249, 0.9); border-right: 3px solid #c98f7a; padding: 30px 0; }
        .nav-item { padding: 15px 30px; color: #5d4037; text-decoration: none; display: block; font-weight: bold; font-size: 12px; letter-spacing: 1px; transition: 0.2s; margin-bottom: 5px; }
        .nav-item:hover, .nav-item.active { background: #e3a693; color: white; border-right: 10px solid #c98f7a; }
        .content-area { flex: 1; padding: 20px; display: flex; justify-content: center; align-items: flex-start; }
        .floating-box { width: 100%; max-width: 850px; background: #fdfaf9; border: 2px solid #c98f7a; box-shadow: 12px 12px 0 #c98f7a; padding: 50px 40px; }
        .section-title { text-align: center; font-size: 26px; color: #5d4037; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 4px; }
        .section-subtitle { text-align: center; font-size: 11px; color: #c98f7a; margin-bottom: 40px; text-transform: uppercase; letter-spacing: 2px; }
        .form-group { margin-bottom: 25px; }
        label { display: block; font-size: 11px; font-weight: bold; color: #5d4037; margin-bottom: 10px; letter-spacing: 1px; }
        input, textarea, select { width: 100%; padding: 12px; font-family: inherit; border: 2px solid #e8d6cf; background: #fff; outline: none; font-size: 13px; }
        .btn-submit { width: 100%; padding: 18px; background: #e3a693; color: white; border: none; font-weight: bold; font-size: 14px; cursor: pointer; box-shadow: 6px 6px 0 #c98f7a; transition: 0.2s; margin-top: 20px; }
        .btn-submit:hover { transform: translate(-2px, -2px); box-shadow: 8px 8px 0 #c98f7a; }
        .back-link { display: block; text-align: center; margin-top: 20px; font-size: 10px; color: #c98f7a; text-decoration: none; }
        .error { color: red; background: #ffe0e0; padding: 10px; margin-bottom: 20px; }
    </style>
    <script>
        function toggleOptions() {
            var type = document.querySelector('select[name="question_type"]').value;
            var optionsDiv = document.getElementById('options_section');
            if (type === 'Multiple Choice') {
                optionsDiv.style.display = 'block';
            } else {
                optionsDiv.style.display = 'none';
            }
        }
    </script>
</head>
<body onload="toggleOptions()">

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
        <a href="my_quizzes.php" class="nav-item active">MY QUIZZES</a>
        <a href="create_quiz.php" class="nav-item">CREATE QUIZ</a>
        <a href="view_reports.php" class="nav-item">VIEW REPORTS</a>
        <a href="settings.php" class="nav-item">SETTINGS</a>
    </div>

    <div class="content-area">
        <div class="floating-box">
            <h2 class="section-title">EDIT QUESTION</h2>
            <p class="section-subtitle">Update question details</p>

            <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label>Question Type</label>
                    <select name="question_type" onchange="toggleOptions()" required>
                        <option value="Multiple Choice" <?php if($question['question_type']=='Multiple Choice') echo 'selected'; ?>>Multiple Choice</option>
                        <option value="Identification" <?php if($question['question_type']=='Identification') echo 'selected'; ?>>Identification</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Question Text</label>
                    <textarea name="question_text" rows="4" required><?php echo htmlspecialchars($question['question_text']); ?></textarea>
                </div>

                <div id="options_section" style="display: <?php echo ($question['question_type'] == 'Multiple Choice') ? 'block' : 'none'; ?>;">
                    <div class="form-group">
                        <label>Option A</label>
                        <input type="text" name="option_a" value="<?php echo htmlspecialchars($question['option_a']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Option B</label>
                        <input type="text" name="option_b" value="<?php echo htmlspecialchars($question['option_b']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Option C</label>
                        <input type="text" name="option_c" value="<?php echo htmlspecialchars($question['option_c']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Option D</label>
                        <input type="text" name="option_d" value="<?php echo htmlspecialchars($question['option_d']); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Correct Answer</label>
                    <input type="text" name="correct_answer" value="<?php echo htmlspecialchars($question['correct_answer']); ?>" required>
                </div>

                <button type="submit" class="btn-submit">UPDATE QUESTION</button>
            </form>

            <a href="manage_questions.php?quiz_id=<?php echo $quiz_id; ?>" class="back-link">← BACK TO QUESTIONS</a>
        </div>
    </div>
</div>

</body>
</html>