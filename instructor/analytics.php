<?php
session_start();
require_once '../dbconnect.php'; 

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Instructor') {
    header('Location: ../login.php'); 
    exit;
}

// Simple query para sa huling 5 submissions
$query = "SELECT student_name, score, total_questions, submitted_at 
          FROM student_submissions 
          ORDER BY submitted_at DESC LIMIT 5";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Performance Analytics | Q-RIOUS</title>
    <style>
        body { font-family: 'Courier New', monospace; background-color: #f5e6e0; padding: 40px; }
        .analytics-card { 
            max-width: 700px; margin: 0 auto; background: #fdfaf9; 
            border: 3px solid #c98f7a; box-shadow: 10px 10px 0 #c98f7a; padding: 30px; 
        }
        h2 { text-align: center; color: #5d4037; text-transform: uppercase; }
        .bar-group { margin-bottom: 20px; }
        .bar-label { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 5px; }
        .progress-track { width: 100%; background: #e8d6cf; height: 25px; border-radius: 4px; border: 1px solid #c98f7a; overflow: hidden; }
        .progress-fill { height: 100%; background: #e3a693; display: flex; align-items: center; justify-content: flex-end; padding-right: 10px; color: white; font-weight: bold; font-size: 11px; }
        .back-link { display: block; text-align: center; margin-top: 25px; color: #c98f7a; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
    <div class="analytics-card">
        <h2>Performance Tracking (Task 6)</h2>
        <hr style="border: 1px solid #e8d6cf; margin-bottom: 25px;">

        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($result)): 
                $percent = ($row['total_questions'] > 0) ? ($row['score'] / $row['total_questions']) * 100 : 0;
            ?>
                <div class="bar-group">
                    <div class="bar-label">
                        <span>STUDENT: <strong><?php echo htmlspecialchars($row['student_name']); ?></strong></span>
                        <span>SCORE: <?php echo $row['score']; ?>/<?php echo $row['total_questions']; ?></span>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill" style="width: <?php echo $percent; ?>%;">
                            <?php echo round($percent); ?>%
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align:center;">No data available yet.</p>
        <?php endif; ?>

        <a href="instructor_dashboard.php" class="back-link">← BACK TO DASHBOARD</a>
    </div>
</body>
</html>