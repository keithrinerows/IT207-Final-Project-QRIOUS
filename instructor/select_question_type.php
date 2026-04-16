<?php
session_start();
require_once '../dbconnect.php';

// Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Instructor') {
    header('Location: ../login.php'); 
    exit;
}

$userName = $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Instructor';
$quiz_id = isset($_GET['quiz_id']) ? $_GET['quiz_id'] : 0;
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Q-RIOUS | Select Type</title>
    <style>
        :root {
            --top-nav-height: 70px;
            --sidebar-width: 250px;
            --main-pink: #e3a693;
            --dark-pink: #c98f7a;
            --bg-grid: #f5e6e0;
            --card-bg: #fdfaf9;
        }

        * { 
            cursor: url('https://cur.cursors-4u.net/games/gam-4/gam373.cur'), auto !important; 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
        }
        
        body { 
            font-family: 'Courier New', monospace; 
            background-color: var(--bg-grid); 
            background-image: linear-gradient(#e8d6cf 1px, transparent 1px), 
                              linear-gradient(90deg, #e8d6cf 1px, transparent 1px);
            background-size: 30px 30px;
            height: 100vh;
            display: flex; 
            flex-direction: column;
            overflow: hidden;
        }

        .top-nav { 
            background: var(--main-pink); 
            height: var(--top-nav-height); 
            padding: 0 40px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            border-bottom: 4px solid var(--dark-pink); 
            color: white;
            position: relative;
            z-index: 2000;
        }
        .logo { font-size: 24px; font-weight: bold; letter-spacing: 5px; text-decoration: none; color: white; }
        .user-info { font-size: 11px; display: flex; align-items: center; gap: 15px; font-weight: bold; }
        .logout-btn { 
            background: white; 
            color: var(--main-pink); 
            padding: 6px 15px; 
            text-decoration: none; 
            font-weight: bold; 
            border-radius: 2px;
        }

        .main-layout { 
            display: flex; 
            flex: 1; 
            height: calc(100vh - var(--top-nav-height));
        }

        .sidebar { 
            width: var(--sidebar-width); 
            background: #fdfaf9; 
            border-right: 3px solid var(--dark-pink); 
            padding-top: 30px;
            z-index: 1500;
        }
        .nav-item { 
            padding: 15px 30px; 
            color: #5d4037; 
            text-decoration: none; 
            font-weight: bold; 
            font-size: 11px; 
            letter-spacing: 2px;
            display: block;
            border-bottom: 1px solid rgba(201, 143, 122, 0.1);
        }
        .nav-item.active { 
            background: var(--main-pink); 
            color: white; 
            border-right: 12px solid var(--dark-pink); 
        }

        .content-area { 
            flex: 1; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            padding: 40px;
        }

        .selection-card { 
            background: var(--card-bg); 
            border: 3px solid var(--dark-pink); 
            padding: 60px 40px; 
            text-align: center; 
            box-shadow: 15px 15px 0 var(--dark-pink); 
            width: 100%;
            max-width: 800px;
        }

        h2 { color: #5d4037; letter-spacing: 5px; font-size: 22px; margin-bottom: 40px; }

        .btn-container { 
            display: grid; 
            grid-template-columns: repeat(2, 1fr); 
            gap: 25px; 
        }

        .choice-btn { 
            background: var(--main-pink); 
            color: white; 
            padding: 30px 15px; 
            text-decoration: none; 
            font-weight: bold; 
            font-size: 12px;
            border-radius: 4px; 
            box-shadow: 0 6px 0 var(--dark-pink);
            display: flex;
            align-items: center;
            justify-content: center;
            letter-spacing: 2px;
            transition: 0.2s;
        }
        .choice-btn:hover { transform: translateY(-4px); box-shadow: 0 10px 0 #b07a68; }

        .back-link {
            display: inline-block;
            margin-top: 40px;
            color: var(--dark-pink);
            font-size: 11px;
            font-weight: bold;
            text-decoration: none;
        }
    </style>
</head>
<body>

    <div class="top-nav">
        <a href="instructor_dashboard.php" class="logo">Q-RIOUS</a>
        <div class="user-info">
            WELCOME, <?php echo strtoupper(htmlspecialchars($userName)); ?>!
            <a href="../logout.php" class="logout-btn">LOGOUT</a>
        </div>
    </div>

    <div class="main-layout">
        <div class="sidebar">
            <a href="instructor_dashboard.php" class="nav-item <?php echo ($current_page == 'instructor_dashboard.php') ? 'active' : ''; ?>">DASHBOARD</a>
            <a href="my_quizzes.php" class="nav-item <?php echo ($current_page == 'my_quizzes.php') ? 'active' : ''; ?>">MY QUIZZES</a>
            <a href="create_quiz.php" class="nav-item active">CREATE QUIZ</a>
            <a href="view_reports.php" class="nav-item <?php echo ($current_page == 'view_reports.php') ? 'active' : ''; ?>">VIEW REPORTS</a>
            <a href="settings.php" class="nav-item <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">SETTINGS</a>
        </div>

        <div class="content-area">
            <div class="selection-card">
                <h2>CHOOSE QUESTION TYPE</h2>
                
                <div class="btn-container">
                    <a href="add_questions.php?quiz_id=<?php echo $quiz_id; ?>" class="choice-btn">MULTIPLE CHOICE</a>
                    <a href="add_true_false.php?quiz_id=<?php echo $quiz_id; ?>" class="choice-btn">TRUE OR FALSE</a>
                    <a href="add_short_answer.php?quiz_id=<?php echo $quiz_id; ?>" class="choice-btn">SHORT ANSWER</a>
                    <a href="add_fill_blank.php?quiz_id=<?php echo $quiz_id; ?>" class="choice-btn">FILL IN THE BLANKS</a>
                </div>
                
                <a href="create_quiz.php" class="back-link">← CANCEL & GO BACK TO DASHBOARD</a>
            </div>
        </div>
    </div>

</body>
</html>