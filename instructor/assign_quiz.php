<?php
session_start();
require_once '../dbconnect.php'; 

// 1. Authentication check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Instructor') {
    header('Location: ../login.php'); 
    exit;
}

$instructor_id = $_SESSION['user_id'];
$instructor_name = $_SESSION['username']; 
$userName = $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Instructor';
$quiz_id = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;

// 2. Verify Quiz Ownership/Access
$quiz_query = "SELECT q.quiz_title FROM quizzes q 
               LEFT JOIN users u ON (q.created_by = u.id OR q.created_by = u.username)
               WHERE q.id = '$quiz_id' 
               AND (q.created_by = '$instructor_id' OR q.created_by = '$instructor_name' OR u.role = 'Admin')";
$quiz_res = mysqli_query($conn, $quiz_query);
$quiz = mysqli_fetch_assoc($quiz_res);

if (!$quiz) {
    header('Location: my_quizzes.php');
    exit;
}

$msg = "";

// 3. Logic para sa pag-save ng assignment (CASE-INSENSITIVE)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CASE-INSENSITIVE FIX: Ginagamit ang strtoupper() para maging ALL CAPS ang section sa DB
    $section = mysqli_real_escape_string($conn, strtoupper(trim($_POST['section'])));
    $deadline = mysqli_real_escape_string($conn, $_POST['deadline']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Check kung may existing assignment na para sa section na ito (Uppercase comparison)
    $check_sql = "SELECT id FROM quiz_assignments WHERE quiz_id = '$quiz_id' AND section_name = '$section'";
    $check_res = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_res) > 0) {
        $assign_sql = "UPDATE quiz_assignments 
                       SET deadline = '$deadline', status = '$status', assigned_at = NOW()
                       WHERE quiz_id = '$quiz_id' AND section_name = '$section'";
        $action = "updated";
    } else {
        $assign_sql = "INSERT INTO quiz_assignments (quiz_id, section_name, deadline, assigned_by, status) 
                       VALUES ('$quiz_id', '$section', '$deadline', '$instructor_id', '$status')";
        $action = "assigned";
    }
    
    if (mysqli_query($conn, $assign_sql)) {
        $msg = "<div class='alert success'>✨ Quiz successfully $action to section \"$section\"!</div>";
    } else {
        $msg = "<div class='alert error'>❌ Error: " . mysqli_error($conn) . "</div>";
    }
}

// 4. Kunin ang mga active assignments (Yung hindi pa "Time's Up")
$existing_assignments = [];
$now = date('Y-m-d H:i:s');
$existing_res = $conn->query("SELECT * FROM quiz_assignments 
                             WHERE quiz_id = $quiz_id 
                             AND deadline > '$now' 
                             ORDER BY section_name ASC");

while($row = $existing_res->fetch_assoc()) {
    $existing_assignments[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Quiz | Q-RIOUS</title>
    <style>
        * { cursor: url('https://cur.cursors-4u.net/games/gam-4/gam373.cur'), auto !important; box-sizing: border-box; margin:0; padding:0; }
        body { font-family: 'Courier New', monospace; background-color: #f5e6e0; background-image: linear-gradient(#e8d6cf 1px, transparent 1px), linear-gradient(90deg, #e8d6cf 1px, transparent 1px); background-size: 30px 30px; min-height: 100vh; display: flex; flex-direction: column; }
        
        .top-nav { background: #e3a693; height: 65px; padding: 0 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 4px solid #c98f7a; color: white; flex-shrink: 0; }
        .logo { font-size: 24px; font-weight: bold; letter-spacing: 5px; }
        .logout-btn { background: white; color: #e3a693; padding: 5px 15px; text-decoration: none; font-weight: bold; border-radius: 3px; font-size: 10px; }

        .main-layout { display: flex; flex: 1; }
        .sidebar { width: 250px; background: rgba(253, 250, 249, 0.9); border-right: 3px solid #c98f7a; padding: 30px 0; }
        .nav-item { padding: 15px 30px; color: #5d4037; text-decoration: none; display: block; font-weight: bold; font-size: 12px; }
        .nav-item.active { background: #e3a693; color: white; border-right: 10px solid #c98f7a; }

        .content-area { flex: 1; padding: 40px; display: flex; flex-direction: column; align-items: center; overflow-y: auto; }
        .floating-box { width: 100%; max-width: 700px; background: #fdfaf9; border: 2px solid #c98f7a; box-shadow: 12px 12px 0 #c98f7a; padding: 40px; }
        
        .section-title { text-align: center; font-size: 24px; color: #5d4037; letter-spacing: 4px; }
        .quiz-subtitle { text-align: center; font-size: 11px; color: #c98f7a; margin-bottom: 30px; border-bottom: 2px dashed #e3a693; padding-bottom: 10px; }
        
        label { display: block; font-size: 10px; font-weight: bold; color: #c98f7a; margin-top: 20px; text-transform: uppercase; }
        input, select { width: 100%; padding: 12px; border: 2px solid #e8d6cf; margin-top: 8px; font-family: inherit; outline: none; }
        
        /* Visual cue for Case-Insensitive input */
        input[name="section"] { text-transform: uppercase; } 

        .btn-submit { background: #e3a693; color: white; border: none; padding: 15px; width: 100%; font-weight: bold; cursor: pointer; margin-top: 30px; box-shadow: 6px 6px 0 #c98f7a; text-transform: uppercase; }
        .btn-submit:hover { background: #c98f7a; transform: translateY(-2px); }

        .alert { padding: 15px; font-size: 11px; margin-bottom: 20px; text-align: center; border-radius: 2px; }
        .success { background: #8fb9a8; color: white; }
        .error { background: #d9534f; color: white; }

        .existing-assignments { margin-top: 40px; border-top: 2px solid #e8d6cf; padding-top: 20px; }
        .existing-title { font-size: 12px; font-weight: bold; color: #5d4037; margin-bottom: 15px; text-align: center; }
        
        .assignment-item { background: #fff; border: 1px solid #e8d6cf; padding: 12px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; border-radius: 5px; }
        .assignment-section { font-weight: bold; color: #e3a693; }
        .assignment-status { font-size: 9px; padding: 3px 8px; border-radius: 10px; color: white; }
        .status-active { background: #8fb9a8; }
        .status-inactive { background: #d9534f; }
        .status-draft { background: #f0ad4e; }
        
        .time-info { font-size: 9px; color: #c98f7a; margin-top: 3px; }
        .back-link { display: block; text-align: center; margin-top: 25px; font-size: 10px; color: #c98f7a; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

    <div class="top-nav">
        <div class="logo">Q-RIOUS</div>
        <div style="font-size: 11px;">
            WELCOME, <?php echo strtoupper(htmlspecialchars($userName)); ?>!
            <a href="../logout.php" class="logout-btn" style="margin-left: 15px;">LOGOUT</a>
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
            <div class="floating-box">
                <h2 class="section-title">ASSIGN QUIZ</h2>
                <p class="quiz-subtitle">Quiz: <?php echo htmlspecialchars($quiz['quiz_title']); ?></p>

                <?php echo $msg; ?>

                <form method="POST">
                    <label>🎯 Target Section (Case-Insensitive)</label>
                    <input type="text" name="section" placeholder="e.g., BSIT 3A" required>
                    <small style="font-size: 9px; color: #c98f7a;">💡 TIP: "bsit 3a" is the same as "BSIT 3A".</small>

                    <label>📅 Assignment Deadline</label>
                    <input type="datetime-local" name="deadline" required>

                    <label>🔘 Status</label>
                    <select name="status" required>
                        <option value="active">✅ Active - Students can take</option>
                        <option value="inactive">❌ Inactive - Hidden</option>
                        <option value="draft">📝 Draft - Not published</option>
                    </select>

                    <button type="submit" class="btn-submit">CONFIRM ASSIGNMENT →</button>
                </form>

                <div class="existing-assignments">
                    <div class="existing-title">📋 ACTIVE ASSIGNMENTS</div>
                    <?php if(count($existing_assignments) > 0): ?>
                        <?php foreach($existing_assignments as $ea): ?>
                            <div class="assignment-item">
                                <div>
                                    <span class="assignment-section">📌 <?php echo htmlspecialchars($ea['section_name']); ?></span>
                                    <div class="time-info">
                                        Ends: <?php echo date('M d, Y h:i A', strtotime($ea['deadline'])); ?>
                                        <?php 
                                            $diff = strtotime($ea['deadline']) - time();
                                            $hrs = round($diff / 3600, 1);
                                            echo " <span style='color: #d9534f;'>($hrs hrs left)</span>";
                                        ?>
                                    </div>
                                </div>
                                <span class="assignment-status <?php echo 'status-'.$ea['status']; ?>">
                                    <?php echo strtoupper($ea['status']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; font-size: 11px; color: #c98f7a; padding: 10px;">No active assignments found.</p>
                    <?php endif; ?>
                </div>

                <a href="my_quizzes.php" class="back-link">← BACK TO MY QUIZZES</a>
            </div>
        </div>
    </div>

</body>
</html>