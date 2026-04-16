<?php
session_start();
require_once '../dbconnect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Instructor' || !isset($_GET['id'])) {
    header('Location: my_quizzes.php');
    exit;
}

$quiz_id = intval($_GET['id']);
$userName = $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Instructor';

// --- LOGIC: DELETE QUESTION ---
if (isset($_GET['delete_qid'])) {
    $delete_id = intval($_GET['delete_qid']);
    $del_stmt = $conn->prepare("DELETE FROM questions WHERE id = ? AND quiz_id = ?");
    $del_stmt->bind_param("ii", $delete_id, $quiz_id);
    if ($del_stmt->execute()) {
        header("Location: edit_quiz.php?id=$quiz_id&msg=deleted");
        exit;
    }
}

// --- LOGIC: UPDATE QUIZ CONFIG ---
if (isset($_POST['btn_update_config'])) {
    $title = mysqli_real_escape_string($conn, $_POST['quiz_title']);
    $cat = mysqli_real_escape_string($conn, $_POST['category']);
    $time = intval($_POST['time_limit']);
    $sql = "UPDATE quizzes SET quiz_title='$title', category='$cat', time_limit=$time WHERE id=$quiz_id";
    if ($conn->query($sql)) { $msg_config = "Quiz settings updated!"; }
}

// --- LOGIC: UPDATE INDIVIDUAL QUESTION ---
if (isset($_POST['btn_update_question'])) {
    $qid = intval($_POST['question_id']);
    $qtext = mysqli_real_escape_string($conn, $_POST['question_text']);
    $correct = mysqli_real_escape_string($conn, $_POST['correct_answer']);
    $oa = isset($_POST['option_A']) ? mysqli_real_escape_string($conn, $_POST['option_A']) : "";
    $ob = isset($_POST['option_B']) ? mysqli_real_escape_string($conn, $_POST['option_B']) : "";
    $oc = isset($_POST['option_C']) ? mysqli_real_escape_string($conn, $_POST['option_C']) : "";
    $od = isset($_POST['option_D']) ? mysqli_real_escape_string($conn, $_POST['option_D']) : "";
    $upd_q = "UPDATE questions SET question_text=?, option_a=?, option_b=?, option_c=?, option_d=?, correct_answer=? WHERE id=? AND quiz_id=?";
    $stmt = $conn->prepare($upd_q);
    $stmt->bind_param("ssssssii", $qtext, $oa, $ob, $oc, $od, $correct, $qid, $quiz_id);
    if ($stmt->execute()) { $msg_q = "Question #$qid updated!"; }
}

// --- LOGIC: ADD NEW QUESTION ---
if (isset($_POST['btn_add_question'])) {
    $q_text = mysqli_real_escape_string($conn, $_POST['new_q_text']);
    $q_type = mysqli_real_escape_string($conn, $_POST['new_q_type']);
    $oa = $ob = $oc = $od = $correct = "";
    if ($q_type == 'Multiple Choice') {
        $oa = mysqli_real_escape_string($conn, $_POST['new_oa'] ?? "");
        $ob = mysqli_real_escape_string($conn, $_POST['new_ob'] ?? "");
        $oc = mysqli_real_escape_string($conn, $_POST['new_oc'] ?? "");
        $od = mysqli_real_escape_string($conn, $_POST['new_od'] ?? "");
        $correct = mysqli_real_escape_string($conn, $_POST['new_correct'] ?? "");
    } elseif ($q_type == 'True or False') { $correct = mysqli_real_escape_string($conn, $_POST['new_correct_tf']); }
    elseif ($q_type == 'Short Answer') { $correct = mysqli_real_escape_string($conn, $_POST['new_correct_sa']); }
    elseif ($q_type == 'Fill in the Blanks') { $correct = mysqli_real_escape_string($conn, $_POST['new_correct_fib']); }
    $sql_add = "INSERT INTO questions (quiz_id, question_text, question_type, option_a, option_b, option_c, option_d, correct_answer) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_add = $conn->prepare($sql_add);
    $stmt_add->bind_param("isssssss", $quiz_id, $q_text, $q_type, $oa, $ob, $oc, $od, $correct);
    if ($stmt_add->execute()) { $msg_add = "New $q_type added successfully!"; }
}

$quiz_data = $conn->query("SELECT * FROM quizzes WHERE id = $quiz_id");
$quiz = $quiz_data->fetch_assoc();
if (!$quiz) die("Quiz not found!");
$questions_result = $conn->query("SELECT * FROM questions WHERE quiz_id = $quiz_id ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Quiz | Q-RIOUS</title>
    <style>
        * { cursor: url('https://cur.cursors-4u.net/games/gam-4/gam373.cur'), auto !important; box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Courier New', monospace; background-color: #f5e6e0; background-image: linear-gradient(#e8d6cf 1px, transparent 1px), linear-gradient(90deg, #e8d6cf 1px, transparent 1px); background-size: 30px 30px; min-height: 100vh; }
        
        .top-nav { background: #e3a693; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 4px solid #c98f7a; color: white; position: fixed; width: 100%; top: 0; z-index: 1000; height: 70px; }
        .logo { font-size: 24px; font-weight: bold; letter-spacing: 5px; text-shadow: 2px 2px 0 #c98f7a; }
        .user-info { font-size: 11px; display: flex; align-items: center; gap: 15px; }
        .logout-btn { background: #fdfaf9; color: #c98f7a; padding: 5px 15px; text-decoration: none; font-weight: bold; border: 2px solid #c98f7a; box-shadow: 3px 3px 0 #c98f7a; }
        
        .main-layout { display: flex; margin-top: 70px; }
        
        /* Sidebar Locked */
        .sidebar { width: 250px; background: rgba(253, 250, 249, 0.9); border-right: 4px solid #c98f7a; padding: 30px 0; position: fixed; top: 70px; bottom: 0; left: 0; z-index: 99; overflow-y: auto; }
        .nav-item { padding: 15px 30px; color: #5d4037; text-decoration: none; display: block; font-weight: bold; font-size: 12px; letter-spacing: 1px; transition: 0.2s; }
        .nav-item:hover, .nav-item.active { background: #e3a693; color: white; border-right: 10px solid #c98f7a; }
        
        /* Content Scrolls */
        .content-area { flex: 1; padding: 40px; margin-left: 250px; display: flex; flex-direction: column; align-items: center; gap: 40px; }
        
        .container { width: 100%; max-width: 850px; background: #fdfaf9; border: 3px solid #c98f7a; box-shadow: 12px 12px 0 #c98f7a; padding: 40px; }
        .section-title { text-align: center; font-size: 22px; color: #5d4037; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 4px; }
        .section-subtitle { text-align: center; font-size: 10px; color: #c98f7a; margin-bottom: 30px; text-transform: uppercase; letter-spacing: 2px; }
        
        .form-group { margin-bottom: 20px; width: 100%; }
        label { display: block; font-size: 11px; font-weight: bold; color: #5d4037; margin-bottom: 8px; text-transform: uppercase; }
        input, select, textarea { width: 100%; padding: 12px; border: 2px solid #e8d6cf; font-family: inherit; background: #fff; outline: none; font-size: 13px; }
        
        .opt-row { display: flex; align-items: center; gap: 15px; margin-bottom: 12px; width: 100%; }
        .opt-row input[type="radio"] { width: 20px !important; height: 20px !important; flex-shrink: 0; }
        .opt-row input[type="text"] { flex: 1; }

        .btn-action { padding: 12px 25px; border: none; font-weight: bold; text-transform: uppercase; cursor: pointer; transition: 0.3s; letter-spacing: 1px; }
        .btn-save { background: #e3a693; color: white; box-shadow: 4px 4px 0 #c98f7a; }
        .btn-add { background: #8fb9a8; color: white; box-shadow: 4px 4px 0 #709a89; width: 100%; margin-top: 20px; }
        
        .question-card { background: white; border: 2px solid #e8d6cf; padding: 25px; margin-bottom: 25px; width: 100%; }
        .q-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px dashed #e3a693; padding-bottom: 10px; }
        .btn-delete { color: #ff6b6b; text-decoration: none; font-size: 10px; font-weight: bold; border: 1px solid #ff6b6b; padding: 3px 8px; }
        .alert { background: #fff3f3; color: #c98f7a; padding: 12px; border: 2px solid #c98f7a; margin-bottom: 20px; font-size: 12px; text-align: center; font-weight: bold; width: 100%; }
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
            <a href="my_quizzes.php" class="nav-item active">MY QUIZZES</a>
            <a href="create_quiz.php" class="nav-item">CREATE QUIZ</a>
            <a href="view_reports.php" class="nav-item">VIEW REPORTS</a>
            <a href="settings.php" class="nav-item">SETTINGS</a>
        </div>

        <div class="content-area">
            <div class="container">
                <h2 class="section-title">QUIZ SETTINGS</h2>
                <?php if(isset($msg_config)) echo "<div class='alert'>✅ $msg_config</div>"; ?>
                <form method="POST">
                    <div class="form-group"><label>📌 QUIZ TITLE</label><input type="text" name="quiz_title" value="<?php echo htmlspecialchars($quiz['quiz_title']); ?>" required></div>
                    <div style="display: flex; gap: 20px;">
                        <div class="form-group" style="flex: 2;"><label>📂 CATEGORY</label><select name="category"><option value="IT / CS" <?php echo ($quiz['category'] == 'IT / CS') ? 'selected' : ''; ?>>IT / CS</option><option value="General Education" <?php echo ($quiz['category'] == 'General Education') ? 'selected' : ''; ?>>GENERAL EDUCATION</option><option value="Mathematics" <?php echo ($quiz['category'] == 'Mathematics') ? 'selected' : ''; ?>>MATHEMATICS</option></select></div>
                        <div class="form-group" style="flex: 1;"><label>⏱️ TIME LIMIT (MINS)</label><input type="number" name="time_limit" value="<?php echo $quiz['time_limit']; ?>" required></div>
                    </div>
                    <button type="submit" name="btn_update_config" class="btn-action btn-save">Update Settings</button>
                </form>
            </div>

            <div class="container">
                <h2 class="section-title">MANAGE QUESTIONS</h2>
                <?php if(isset($msg_q)) echo "<div class='alert' style='border-color:#8fb9a8;'>✨ $msg_q</div>"; ?>
                <?php if ($questions_result->num_rows > 0): while($q = $questions_result->fetch_assoc()): ?>
                    <div class="question-card">
                        <form method="POST">
                            <input type="hidden" name="question_id" value="<?php echo $q['id']; ?>">
                            <div class="q-header">
                                <span style="font-size: 10px; font-weight: bold; color: #c98f7a;">#<?php echo $q['id']; ?> | <?php echo strtoupper($q['question_type']); ?></span>
                                <a href="edit_quiz.php?id=<?php echo $quiz_id; ?>&delete_qid=<?php echo $q['id']; ?>" class="btn-delete" onclick="return confirm('Delete permanently?')">REMOVE</a>
                            </div>
                            <div class="form-group"><label>QUESTION TEXT</label><textarea name="question_text" rows="2" required><?php echo htmlspecialchars($q['question_text']); ?></textarea></div>
                            <?php if ($q['question_type'] == 'Multiple Choice'): foreach(['A','B','C','D'] as $opt): ?>
                                <div class="opt-row"><input type="radio" name="correct_answer" value="<?php echo $opt; ?>" <?php echo ($q['correct_answer'] == $opt) ? 'checked' : ''; ?> required><input type="text" name="option_<?php echo $opt; ?>" value="<?php echo htmlspecialchars($q['option_'.strtolower($opt)]); ?>"></div>
                            <?php endforeach; elseif ($q['question_type'] == 'True or False'): ?>
                                <select name="correct_answer"><option value="True" <?php echo ($q['correct_answer'] == 'True') ? 'selected' : ''; ?>>True</option><option value="False" <?php echo ($q['correct_answer'] == 'False') ? 'selected' : ''; ?>>False</option></select>
                            <?php else: ?><input type="text" name="correct_answer" value="<?php echo htmlspecialchars($q['correct_answer']); ?>"><?php endif; ?>
                            <button type="submit" name="btn_update_question" class="btn-action btn-save" style="font-size: 9px; margin-top: 15px;">Save Changes</button>
                        </form>
                    </div>
                <?php endwhile; else: echo "<p style='text-align:center;'>No questions found.</p>"; endif; ?>
            </div>

            <div class="container" style="border-style: dashed;">
                <h2 class="section-title">+ NEW QUESTION</h2>
                <?php if(isset($msg_add)) echo "<div class='alert' style='border-color:#8fb9a8;'>🌿 $msg_add</div>"; ?>
                <form method="POST">
                    <div class="form-group"><label>TYPE</label><select name="new_q_type" onchange="updateAddForm(this.value)"><option value="Multiple Choice">Multiple Choice</option><option value="True or False">True or False</option><option value="Short Answer">Short Answer</option><option value="Fill in the Blanks">Fill in the Blanks</option></select></div>
                    <div class="form-group"><label>QUESTION CONTENT</label><textarea name="new_q_text" rows="2" required></textarea></div>
                    <div id="add_mc_options"><?php foreach(['A','B','C','D'] as $l): ?><div class="opt-row"><input type="radio" name="new_correct" value="<?php echo $l; ?>"><input type="text" name="new_o<?php echo strtolower($l); ?>" placeholder="Option <?php echo $l; ?>"></div><?php endforeach; ?></div>
                    <div id="add_tf_options" style="display:none;"><label>CORRECT</label><select name="new_correct_tf"><option value="True">True</option><option value="False">False</option></select></div>
                    <div id="add_sa_options" style="display:none;"><label>ANSWER</label><input type="text" name="new_correct_sa"></div>
                    <div id="add_fib_options" style="display:none;"><label>BLANK</label><input type="text" name="new_correct_fib"></div>
                    <button type="submit" name="btn_add_question" class="btn-action btn-add">Add to Quiz</button>
                </form>
            </div>
            <a href="my_quizzes.php" style="color:#c98f7a; font-weight:bold; font-size:11px;">[ BACK TO LIST ]</a>
        </div>
    </div>

    <script>
        function updateAddForm(val) {
            document.getElementById('add_mc_options').style.display = val === 'Multiple Choice' ? 'block' : 'none';
            document.getElementById('add_tf_options').style.display = val === 'True or False' ? 'block' : 'none';
            document.getElementById('add_sa_options').style.display = val === 'Short Answer' ? 'block' : 'none';
            document.getElementById('add_fib_options').style.display = val === 'Fill in the Blanks' ? 'block' : 'none';
        }
    </script>
</body>
</html>