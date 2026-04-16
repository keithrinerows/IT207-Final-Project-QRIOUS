<?php
session_start();
require_once '../dbconnect.php'; 

// Seguridad: Siguraduhing Instructor lang ang nakakapasok
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Instructor') {
    header('Location: ../login.php'); 
    exit;
}
$userName = $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Instructor';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard | Q-RIOUS</title>
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
        .user-info { font-size: 11px; display: flex; align-items: center; gap: 15px; }
        
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
            text-transform: uppercase;
        }

        .nav-item:hover, .nav-item.active {
            background: #e3a693; color: white; border-right: 10px solid #c98f7a;
        }

        .content-area { flex: 1; padding: 20px; display: flex; justify-content: center; align-items: flex-start; }

        .floating-box {
            width: 100%;
            max-width: 900px;
            background: #fdfaf9;
            border: 2px solid #c98f7a;
            box-shadow: 12px 12px 0 #c98f7a;
            padding: 50px 40px;
            animation: fadeIn 0.4s ease-out;
            min-height: 500px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .section-title {
            text-align: center; font-size: 26px; color: #5d4037;
            margin-bottom: 5px; text-transform: uppercase; letter-spacing: 4px;
        }

        .section-subtitle {
            text-align: center; font-size: 11px; color: #c98f7a;
            margin-bottom: 50px; text-transform: uppercase; letter-spacing: 2px;
        }

        .dashboard-grid {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .card {
            background: #fff;
            border: 2px solid #e8d6cf;
            padding: 30px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 4px 4px 0 #e8d6cf;
            text-decoration: none;
            width: 100%;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        .card:hover { 
            transform: scale(1.02);
            box-shadow: 15px 15px 0 #e3a693; 
            border-color: #c98f7a;
        }

        .card-content-left { flex: 1.2; }
        .card-content-right { 
            flex: 2; 
            text-align: right; 
            border-left: 2px dotted #e8d6cf; 
            padding-left: 30px; 
        }

        .card h3 { font-size: 18px; color: #5d4037; letter-spacing: 2px; margin: 0; }
        .card p { font-size: 12px; color: #c98f7a; line-height: 1.5; }

        @media (max-width: 768px) {
            .card { flex-direction: column; text-align: center; padding: 25px; }
            .card-content-right { border-left: none; border-top: 2px dotted #e8d6cf; padding: 20px 0 0 0; margin-top: 15px; }
        }
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
            <a href="instructor_dashboard.php" class="nav-item active">DASHBOARD</a>
            <a href="my_quizzes.php" class="nav-item">MY QUIZZES</a>
            <a href="create_quiz.php" class="nav-item">CREATE QUIZ</a>
            <a href="view_reports.php" class="nav-item">VIEW REPORTS</a>
            <a href="settings.php" class="nav-item">SETTINGS</a>
        </div>

        <div class="content-area">
            <div id="dash" class="floating-box">
                <h2 class="section-title">INSTRUCTOR DASHBOARD</h2>
                <p class="section-subtitle">System Control Center</p>
                
                <div class="dashboard-grid">
                    <a href="my_quizzes.php" class="card">
                        <div class="card-content-left">
                            <h3>MY QUIZZES</h3>
                        </div>
                        <div class="card-content-right">
                            <p>Access your quiz library to update questions, change settings, or remove outdated assessments.</p>
                        </div>
                    </a>

                    <a href="create_quiz.php" class="card">
                        <div class="card-content-left">
                            <h3>CREATE QUIZ</h3>
                        </div>
                        <div class="card-content-right">
                            <p>Build professional evaluations from scratch. Supports multiple formats and time configurations.</p>
                        </div>
                    </a>

                    <a href="view_reports.php" class="card">
                        <div class="card-content-left">
                            <h3>VIEW REPORTS</h3>
                        </div>
                        <div class="card-content-right">
                            <p>Review student data, average scores, and detailed item analysis for every quiz you've published.</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

</body>
</html>