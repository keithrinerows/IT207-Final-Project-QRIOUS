<?php
session_start();
require_once '../dbconnect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Instructor') {
    header('Location: ../login.php');
    exit;
}

$quiz_id = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;
if ($quiz_id === 0) { header("Location: my_quizzes.php"); exit; }

$userName = $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Instructor';
$msg = "";

// --- 1. DELETE LOGIC ---
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    if (mysqli_query($conn, "DELETE FROM questions WHERE id = $delete_id AND quiz_id = $quiz_id")) {
        $msg = "<div class='alert success'>QUESTION DELETED SUCCESSFULLY!</div>";
    }
}

// --- 2. ADD / UPDATE LOGIC ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $q_text = mysqli_real_escape_string($conn, $_POST['question_text']);
    $q_type = mysqli_real_escape_string($conn, $_POST['question_type']);
    $correct = mysqli_real_escape_string($conn, $_POST['correct_answer']);
    
    $optA = mysqli_real_escape_string($conn, $_POST['option_a'] ?? '');
    $optB = mysqli_real_escape_string($conn, $_POST['option_b'] ?? '');
    $optC = mysqli_real_escape_string($conn, $_POST['option_c'] ?? '');
    $optD = mysqli_real_escape_string($conn, $_POST['option_d'] ?? '');

    if (isset($_POST['action']) && $_POST['action'] == 'update') {
        $q_id = (int)$_POST['q_id'];
        // SIGURADUHIN NA KASAMA LAHAT NG OPTIONS SA UPDATE
        $sql = "UPDATE questions SET 
                question_text='$q_text', 
                question_type='$q_type', 
                correct_answer='$correct',
                option_a='$optA', 
                option_b='$optB', 
                option_c='$optC', 
                option_d='$optD'
                WHERE id=$q_id AND quiz_id=$quiz_id";
        
        if(mysqli_query($conn, $sql)) {
            $msg = "<div class='alert success'>QUESTION UPDATED SUCCESSFULLY!</div>";
        }
    } else {
        $sql = "INSERT INTO questions (quiz_id, question_text, question_type, correct_answer, option_a, option_b, option_c, option_d) 
                VALUES ($quiz_id, '$q_text', '$q_type', '$correct', '$optA', '$optB', '$optC', '$optD')";
        
        if(mysqli_query($conn, $sql)) {
            $msg = "<div class='alert success'>NEW QUESTION ADDED!</div>";
        }
    }
}

$quiz_res = mysqli_query($conn, "SELECT quiz_title FROM quizzes WHERE id = $quiz_id");
$quiz = mysqli_fetch_assoc($quiz_res);
$questions = mysqli_query($conn, "SELECT * FROM questions WHERE quiz_id = $quiz_id ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Questions | Q-RIOUS</title>
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
            display: flex;
            flex-direction: column;
        }

        .top-nav {
            background: #e3a693;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 4px solid #c98f7a;
            color: white;
            z-index: 100;
        }

        .logo { font-size: 24px; font-weight: bold; letter-spacing: 5px; }
        .logout-btn { background: white; color: #e3a693; padding: 5px 15px; text-decoration: none; font-weight: bold; border-radius: 3px; font-size: 10px; }

        .main-layout { display: flex; flex: 1; }

        .sidebar {
            width: 250px;
            background: rgba(253, 250, 249, 0.9);
            border-right: 3px solid #c98f7a;
            padding: 30px 0;
        }

        .nav-item {
            padding: 15px 30px; color: #5d4037; text-decoration: none;
            display: block; font-weight: bold; font-size: 12px;
            letter-spacing: 1px; transition: 0.2s;
        }

        .nav-item:hover, .nav-item.active { background: #e3a693; color: white; border-right: 10px solid #c98f7a; }

        .content-area { flex: 1; padding: 40px; display: flex; flex-direction: column; align-items: center; }

        .floating-box {
            width: 100%;
            max-width: 1100px;
            background: #fdfaf9;
            border: 2px solid #c98f7a;
            box-shadow: 12px 12px 0 #c98f7a;
            padding: 40px;
        }

        .question-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
        .question-table th { background: #e3a693; color: white; padding: 12px; font-size: 11px; border: 1px solid #c98f7a; }
        .question-table td { padding: 12px; border: 1px solid #e8d6cf; font-size: 12px; color: #5d4037; }

        .type-badge {
            background: #5d4037;
            color: #ffffff !important;
            padding: 4px 10px;
            font-size: 10px;
            font-weight: bold;
            border-radius: 3px;
            display: inline-block;
            text-transform: uppercase;
        }

        .btn-action {
            background: #5d4037; color: white; padding: 10px 20px;
            text-decoration: none; font-weight: bold; font-size: 11px;
            border: none; box-shadow: 4px 4px 0 #c98f7a; cursor: pointer;
        }

        .modal { display: none; position: fixed; z-index: 200; left: 0; top: 0; width: 100%; height: 100%; background: rgba(93, 64, 55, 0.7); }
        .modal-content { background: #fdfaf9; margin: 5% auto; padding: 35px; border: 3px solid #c98f7a; width: 550px; box-shadow: 15px 15px 0 #5d4037; }

        .form-group { margin-bottom: 15px; }
        label { display: block; font-size: 10px; font-weight: bold; color: #c98f7a; margin-bottom: 5px; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #e3a693; font-family: 'Courier New', monospace; }
        
        .options-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px; }

        .alert { padding: 15px; margin-bottom: 20px; background: #d4edda; color: #155724; font-weight: bold; width: 100%; text-align: center; }
    </style>
</head>
<body>

    <div class="top-nav">
        <div class="logo">Q-RIOUS</div>
        <div class="user-info" style="font-size:11px;">
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
        </div>

        <div class="content-area">
            <?php if($msg) echo $msg; ?>
            
            <div class="floating-box">
                <h2 style="color:#5d4037; letter-spacing:3px;">MANAGE QUESTIONS</h2>
                <p style="color:#c98f7a; font-size:11px; margin-bottom:30px; text-transform: uppercase;">QUIZ: <?php echo htmlspecialchars($quiz['quiz_title'] ?? 'N/A'); ?></p>

                <button class="btn-action" onclick="openModal()">+ ADD NEW QUESTION</button>

                <table class="question-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>QUESTION TEXT</th>
                            <th style="text-align:center;">TYPE</th>
                            <th>CORRECT ANSWER</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($questions)): ?>
                        <tr>
                            <td style="font-weight:bold;">#<?php echo $row['id']; ?></td>
                            <td style="max-width:300px;"><?php echo htmlspecialchars($row['question_text']); ?></td>
                            <td style="text-align:center;">
                                <span class="type-badge">
                                    <?php 
                                        $t = $row['question_type'];
                                        if($t == 'MC') echo 'MULTIPLE CHOICE';
                                        elseif($t == 'TF') echo 'TRUE/FALSE';
                                        elseif($t == 'SA') echo 'SHORT ANSWER';
                                        elseif($t == 'FIB') echo 'FILL IN BLANK';
                                        else echo htmlspecialchars($t);
                                    ?>
                                </span>
                            </td>
                            <td style="color:#2ecc71; font-weight:bold;"><?php echo htmlspecialchars($row['correct_answer']); ?></td>
                            <td>
                                <a href="javascript:void(0)" style="color:#8fb9a8; font-weight:bold; text-decoration:none; margin-right:10px;" onclick='editQuestion(<?php echo json_encode($row); ?>)'>EDIT</a>
                                <a href="manage_questions.php?quiz_id=<?php echo $quiz_id; ?>&delete_id=<?php echo $row['id']; ?>" 
                                   style="color:#c98f7a; font-weight:bold; text-decoration:none;" onclick="return confirm('Delete this question?')">DELETE</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <br>
                <a href="my_quizzes.php" style="color:#c98f7a; font-size:11px; text-decoration:none; font-weight:bold;">← BACK TO QUIZZES</a>
            </div>
        </div>
    </div>

    <div id="qModal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle" style="color:#5d4037; margin-bottom:20px; border-bottom:2px solid #e3a693;">ADD QUESTION</h3>
            <form method="POST">
                <input type="hidden" name="q_id" id="q_id">
                <input type="hidden" name="action" id="form_action" value="add">
                
                <div class="form-group">
                    <label>QUESTION TEXT</label>
                    <textarea name="question_text" id="question_text" rows="3" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>QUESTION TYPE</label>
                    <select name="question_type" id="question_type" onchange="toggleOptions()">
                        <option value="MC">MULTIPLE CHOICE</option>
                        <option value="TF">TRUE / FALSE</option>
                        <option value="SA">SHORT ANSWER</option>
                        <option value="FIB">FILL IN THE BLANK</option>
                    </select>
                </div>

                <div id="options_section" class="options-grid">
                    <div><label>OPTION A</label><input type="text" name="option_a" id="option_a"></div>
                    <div><label>OPTION B</label><input type="text" name="option_b" id="option_b"></div>
                    <div><label>OPTION C</label><input type="text" name="option_c" id="option_c"></div>
                    <div><label>OPTION D</label><input type="text" name="option_d" id="option_d"></div>
                </div>

                <div class="form-group">
                    <label>CORRECT ANSWER</label>
                    <input type="text" name="correct_answer" id="correct_answer" required placeholder="For MC, put the letter (A, B, C, or D)">
                </div>
                
                <button type="submit" class="btn-action" style="width:100%;">SAVE QUESTION</button>
                <button type="button" onclick="closeModal()" style="width:100%; background:none; border:none; color:#c98f7a; font-size:10px; margin-top:10px; cursor:pointer;">[ CANCEL ]</button>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('qModal');
        const optionsSection = document.getElementById('options_section');

        function toggleOptions() {
            const type = document.getElementById('question_type').value;
            // Ipakita lang ang options kung Multiple Choice
            optionsSection.style.display = (type === 'MC') ? 'grid' : 'none';
        }

        function openModal() {
            modal.style.display = 'block';
            document.getElementById('modalTitle').innerText = 'ADD QUESTION';
            document.getElementById('form_action').value = 'add';
            document.getElementById('q_id').value = '';
            document.getElementById('question_text').value = '';
            document.getElementById('question_type').value = 'MC';
            document.getElementById('correct_answer').value = '';
            document.getElementById('option_a').value = '';
            document.getElementById('option_b').value = '';
            document.getElementById('option_c').value = '';
            document.getElementById('option_d').value = '';
            toggleOptions();
        }

        function closeModal() { modal.style.display = 'none'; }

        function editQuestion(data) {
            modal.style.display = 'block';
            document.getElementById('modalTitle').innerText = 'EDIT QUESTION';
            document.getElementById('form_action').value = 'update';
            document.getElementById('q_id').value = data.id;
            document.getElementById('question_text').value = data.question_text;
            document.getElementById('question_type').value = data.question_type;
            document.getElementById('correct_answer').value = data.correct_answer;
            document.getElementById('option_a').value = data.option_a || '';
            document.getElementById('option_b').value = data.option_b || '';
            document.getElementById('option_c').value = data.option_c || '';
            document.getElementById('option_d').value = data.option_d || '';
            toggleOptions();
        }

        window.onclick = function(event) { if (event.target == modal) closeModal(); }
    </script>
</body>
</html>