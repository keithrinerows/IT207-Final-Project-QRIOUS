<?php
session_start();
require_once '../dbconnect.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Error: Invalid Quiz ID.");
}

$quiz_id = mysqli_real_escape_string($conn, $_GET['id']);

// 1. Kunin ang impormasyon ng Quiz
$quiz_query = "SELECT * FROM quizzes WHERE id = '$quiz_id'";
$quiz_res = mysqli_query($conn, $quiz_query);
$quiz_data = mysqli_fetch_assoc($quiz_res);
$display_title = $quiz_data['quiz_title'] ?? $quiz_data['title'] ?? 'QUIZ';

// 2. ✅ BAGONG QUERY: Gamitin ang quiz_attempts table at i-join sa users
$query = "SELECT 
            qa.*,
            u.fullname as student_name,
            u.section
          FROM quiz_attempts qa
          LEFT JOIN users u ON qa.user_id = u.id
          WHERE qa.quiz_id = '$quiz_id'
          ORDER BY qa.completed_at DESC";

$result = mysqli_query($conn, $query);

$passed_count = 0;
$failed_count = 0;
$students = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
        
        // Gamitin ang status column from quiz_attempts
        if ($row['status'] == 'Passed') {
            $passed_count++;
        } else {
            $failed_count++;
        }
    }
}

$total_students = count($students);
$pass_percent = ($total_students > 0) ? round(($passed_count / $total_students) * 100) : 0;
$fail_percent = ($total_students > 0) ? round(($failed_count / $total_students) * 100) : 0;
$pass_degrees = ($pass_percent / 100) * 360;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quiz Details | Q-RIOUS</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Courier New', monospace; background-color: #f5e6e0; padding: 40px; }
        .container { max-width: 1000px; margin: auto; background: #fdfaf9; border: 2px solid #c98f7a; box-shadow: 12px 12px 0 #c98f7a; padding: 40px; }
        
        .back-link { text-decoration: none; color: #c98f7a; font-weight: bold; margin-bottom: 20px; display: inline-block; }

        .performance-summary {
            display: flex; align-items: center; justify-content: center;
            gap: 50px; margin-bottom: 40px; padding: 30px; background: #fff; border: 2px solid #e8d6cf;
        }
        .pie-chart {
            width: 200px; height: 200px; border-radius: 50%;
            background: conic-gradient(#2ecc71 0deg <?php echo $pass_degrees; ?>deg, #e74c3c <?php echo $pass_degrees; ?>deg 360deg);
            border: 5px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .stats-labels { flex: 1; max-width: 450px; color: #5d4037; }
        .stat-row { display: flex; align-items: center; margin-bottom: 15px; gap: 20px; }
        .progress-bar-bg { flex: 1; height: 30px; background: #fce4ec; border: 1px solid #e8d6cf; }
        .progress-fill { height: 100%; transition: width 0.5s ease; }

        table { width: 100%; border-collapse: collapse; background: white; margin-top: 20px; }
        th { background: #e3a693; color: white; padding: 15px; text-transform: uppercase; }
        td { padding: 12px; text-align: center; border: 1px solid #e8d6cf; color: #5d4037; }
        .status-passed { color: #2ecc71; font-weight: bold; }
        .status-failed { color: #e74c3c; font-weight: bold; }
        
        .refresh-btn {
            background: #c98f7a;
            color: white;
            border: none;
            padding: 8px 16px;
            margin-bottom: 20px;
            cursor: pointer;
            font-family: monospace;
            font-weight: bold;
            border-radius: 5px;
        }
        .refresh-btn:hover {
            background: #b07a64;
        }
        .timestamp {
            font-size: 12px;
            color: #888;
            margin-bottom: 15px;
            text-align: right;
        }
    </style>
</head>
<body>

<div class="container">
    <a href="view_reports.php" class="back-link">← BACK TO REPORTS</a>
    
    <div class="timestamp">
        🕒 Last updated: <?php echo date('M d, Y h:i:s A'); ?>
        <button class="refresh-btn" onclick="location.reload();">⟳ REFRESH</button>
    </div>

    <div class="performance-summary">
        <div class="pie-chart"></div>
        <div class="stats-labels">
            <div style="font-weight:bold; font-size:18px; margin-bottom:20px;">Total of students who answered: <?php echo $total_students; ?></div>
            <div class="stat-row">
                <div class="progress-bar-bg"><div class="progress-fill" style="width:<?php echo $pass_percent; ?>%; background:#2ecc71;"></div></div>
                <div style="width:120px; font-weight:bold;"><?php echo $pass_percent; ?>% Passed</div>
            </div>
            <div class="stat-row">
                <div class="progress-bar-bg"><div class="progress-fill" style="width:<?php echo $fail_percent; ?>%; background:#e74c3c;"></div></div>
                <div style="width:120px; font-weight:bold;"><?php echo $fail_percent; ?>% Failed</div>
            </div>
        </div>
    </div>

    <h3 style="text-transform:uppercase; margin-bottom:15px;">PERFORMANCE FOR: <?php echo htmlspecialchars($display_title); ?></h3>
    <table>
        <thead>
            <tr>
                <th>STUDENT NAME</th>
                <th>SECTION</th>
                <th>SCORE</th>
                <th>STATUS</th>
                <th>DATE COMPLETED</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($students)): ?>
                <tr><td colspan="5" style="text-align:center;">No data found for this quiz. Have students take the quiz first.</td></tr>
            <?php else: ?>
                <?php foreach ($students as $s): 
                    $percent_score = ($s['total_questions'] > 0) ? ($s['score'] / $s['total_questions']) * 100 : 0;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($s['student_name']); ?></td>
                    <td><?php echo htmlspecialchars($s['section'] ?? 'N/A'); ?></td>
                    <td><?php echo $s['score']; ?>/<?php echo $s['total_questions']; ?> (<?php echo round($percent_score); ?>%)</td>
                    <td class="<?php echo $s['status'] == 'Passed' ? 'status-passed' : 'status-failed'; ?>">
                        <?php echo $s['status']; ?>
                    </td>
                    <td><?php echo date('M d, Y h:i A', strtotime($s['completed_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    // Auto-refresh every 15 seconds
    setTimeout(function() {
        location.reload();
    }, 15000);
</script>

</body>
</html>