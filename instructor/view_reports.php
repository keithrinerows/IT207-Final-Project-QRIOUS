<?php
session_start();
require_once '../dbconnect.php'; 

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Instructor') {
    header('Location: ../login.php'); 
    exit;
}

$userName = $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Instructor';

// ✅ BAGONG QUERY: Gamitin ang quiz_attempts table para sa bilang ng takers
$query = "SELECT 
            q.id, 
            q.quiz_title, 
            q.category,
            q.created_at,
            (SELECT COUNT(DISTINCT user_id) FROM quiz_attempts WHERE quiz_id = q.id) as total_takers
          FROM quizzes q 
          WHERE q.created_by = '{$_SESSION['user_id']}'
          ORDER BY q.created_at DESC";

$result = mysqli_query($conn, $query);

// Check kung may error sa query
if (!$result) {
    die("Query Error: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Reports | Q-RIOUS</title>
    <style>
        /* ORIGINAL CSS MULA SA DASHBOARD MO */
        * {
            cursor: url('https://cur.cursors-4u.net/games/gam-4/gam373.cur'), auto !important;
            box-sizing: border-box; margin: 0; padding: 0;
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
        }

        .logo { font-size: 24px; font-weight: bold; letter-spacing: 5px; }
        .user-info { font-size: 11px; display: flex; align-items: center; gap: 15px; text-transform: uppercase; }
        
        .logout-btn {
            background: white; color: #e3a693; padding: 5px 15px;
            text-decoration: none; font-weight: bold; border-radius: 3px;
            font-size: 10px; transition: 0.3s;
        }

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
            letter-spacing: 1px; transition: 0.2s; margin-bottom: 5px;
        }

        .nav-item:hover, .nav-item.active {
            background: #e3a693; color: white; border-right: 10px solid #c98f7a;
        }

        .content-area { flex: 1; padding: 40px; display: flex; justify-content: center; align-items: flex-start; }

        .floating-box {
            width: 100%;
            max-width: 1000px;
            background: #fdfaf9;
            border: 2px solid #c98f7a;
            box-shadow: 12px 12px 0 #c98f7a;
            padding: 60px 50px;
        }

        .section-title {
            text-align: center; font-size: 26px; color: #5d4037;
            margin-bottom: 10px; text-transform: uppercase; letter-spacing: 4px;
        }

        .section-subtitle {
            text-align: center; font-size: 11px; color: #c98f7a;
            margin-bottom: 50px; text-transform: uppercase; letter-spacing: 2px;
        }

        /* TABLE STYLING */
        .report-table {
            width: 100%; border-collapse: collapse; background: white; border: 2px solid #e8d6cf;
        }

        .report-table th {
            background: #e3a693; color: white; padding: 15px;
            text-align: left; font-size: 11px; letter-spacing: 1px; text-transform: uppercase;
        }

        .report-table td {
            padding: 15px; border-bottom: 1px solid #e8d6cf; color: #5d4037; font-size: 12px;
        }

        .view-btn {
            text-decoration: none; background: #5d4037; color: white;
            padding: 8px 15px; font-size: 10px; font-weight: bold;
            box-shadow: 3px 3px 0 #c98f7a; display: inline-block;
        }

        .view-btn:hover { background: #e3a693; }
        
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
            float: right;
        }
        .refresh-btn:hover {
            background: #b07a64;
        }
        .timestamp {
            font-size: 11px;
            color: #888;
            margin-bottom: 15px;
            text-align: right;
        }
    </style>
</head>
<body>

    <div class="top-nav">
        <div class="logo">Q-RIOUS</div>
        <div class="user-info">
            WELCOME, <?php echo htmlspecialchars($userName); ?>!
            <a href="../logout.php" class="logout-btn">LOGOUT</a>
        </div>
    </div>

    <div class="main-layout">
        <div class="sidebar">
            <a href="instructor_dashboard.php" class="nav-item">DASHBOARD</a>
            <a href="my_quizzes.php" class="nav-item">MY QUIZZES</a>
            <a href="create_quiz.php" class="nav-item">CREATE QUIZ</a>
            <a href="view_reports.php" class="nav-item active">VIEW REPORTS</a>
            <a href="settings.php" class="nav-item">SETTINGS</a>
        </div>

        <div class="content-area">
            <div class="floating-box">
                <div class="timestamp">
                    🕒 Last updated: <?php echo date('M d, Y h:i:s A'); ?>
                    <button class="refresh-btn" onclick="location.reload();">⟳ REFRESH</button>
                </div>
                <h2 class="section-title">QUIZ REPORTS</h2>
                <p class="section-subtitle">Performance tracking and analytics</p>

                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>QUIZ TITLE</th>
                                <th>CATEGORY</th>
                                <th>TAKERS</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['quiz_title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['category']); ?></td>
                                <td>
                                    <?php 
                                    $takers = $row['total_takers'];
                                    if ($takers > 0) {
                                        echo "<span style='color:#2ecc71; font-weight:bold;'>$takers</span> Student/s";
                                    } else {
                                        echo "<span style='color:#e74c3c;'>$takers</span> Student/s";
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="quiz_details.php?id=<?php echo $row['id']; ?>" class="view-btn">VIEW FULL DATA →</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 60px; border: 2px dashed #e8d6cf; color: #c98f7a;">
                        <p>NO REPORTS GENERATED YET.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh every 15 seconds
        setTimeout(function() {
            location.reload();
        }, 15000);
    </script>

</body>
</html>