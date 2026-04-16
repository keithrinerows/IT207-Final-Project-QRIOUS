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

// --- Search & Filter Logic ---
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$category_filter = isset($_GET['category']) ? mysqli_real_escape_string($conn, $_GET['category']) : '';

/* SQL: 
   Lalabas ang quiz kung:
   - Ikaw ang gumawa (Instructor ID/Name)
   - O Admin ang gumawa (role = 'Admin')
*/
$query = "SELECT q.*, u.role AS creator_role FROM quizzes q 
          LEFT JOIN users u ON (q.created_by = u.id OR q.created_by = u.username)
          WHERE (q.created_by = '$instructor_id' 
          OR q.created_by = '$instructor_name' 
          OR u.role = 'Admin')";

if ($search !== '') {
    $query .= " AND q.quiz_title LIKE '%$search%'";
}
if ($category_filter !== '') {
    $query .= " AND q.category = '$category_filter'";
}

$query .= " GROUP BY q.id ORDER BY q.created_at DESC";
$result = mysqli_query($conn, $query);

// --- SUCCESS MESSAGE LOGIC ---
$status_msg = "";
if (isset($_GET['status']) && $_GET['status'] == 'success') {
    $status_msg = "Operation successful!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Quizzes | Q-RIOUS</title>
    <style>
        * { cursor: url('https://cur.cursors-4u.net/games/gam-4/gam373.cur'), auto !important; box-sizing: border-box; margin:0; padding:0; }
        body { font-family: 'Courier New', monospace; background-color: #f5e6e0; background-image: linear-gradient(#e8d6cf 1px, transparent 1px), linear-gradient(90deg, #e8d6cf 1px, transparent 1px); background-size: 30px 30px; height: 100vh; display: flex; flex-direction: column; overflow: hidden; }
        .top-nav { background: #e3a693; height: 65px; padding: 0 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 4px solid #c98f7a; color: white; flex-shrink: 0; }
        .logo { font-size: 24px; font-weight: bold; letter-spacing: 5px; }
        .user-info { font-size: 11px; display: flex; align-items: center; gap: 15px; }
        .logout-btn { background: white; color: #e3a693; padding: 5px 15px; text-decoration: none; font-weight: bold; border-radius: 3px; font-size: 10px; transition: 0.3s; }
        .main-layout { display: flex; flex: 1; overflow: hidden; }
        .sidebar { width: 250px; background: rgba(253, 250, 249, 0.9); border-right: 3px solid #c98f7a; padding: 30px 0; flex-shrink: 0; }
        .nav-item { padding: 15px 30px; color: #5d4037; text-decoration: none; display: block; font-weight: bold; font-size: 12px; letter-spacing: 1px; transition: 0.2s; margin-bottom: 5px; }
        .nav-item:hover, .nav-item.active { background: #e3a693; color: white; border-right: 10px solid #c98f7a; }
        .content-area { flex: 1; padding: 40px; overflow-y: auto; display: flex; flex-direction: column; align-items: center; }
        .floating-box { width: 100%; max-width: 1100px; background: #fdfaf9; border: 2px solid #c98f7a; box-shadow: 12px 12px 0 #c98f7a; padding: 40px; margin-bottom: 40px; animation: fadeIn 0.4s ease-out; }
        .alert-success { background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; margin-bottom: 20px; font-size: 12px; text-align: center; font-weight: bold; width: 100%; }
        @keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
        .filter-bar { display: flex; gap: 10px; margin-bottom: 25px; background: rgba(227, 166, 147, 0.1); padding: 15px; border: 1px dashed #c98f7a; border-radius: 5px; width: 100%; }
        .search-input { flex: 1; padding: 10px; border: 1px solid #c98f7a; font-family: inherit; font-size: 12px; }
        .filter-select { padding: 10px; border: 1px solid #c98f7a; font-family: inherit; font-size: 12px; background: white; }
        .btn-filter { background: #5d4037; color: white; border: none; padding: 0 20px; font-weight: bold; cursor: pointer; font-size: 11px; }
        .quiz-table { width: 100%; border-collapse: collapse; }
        .quiz-table th { background: #e3a693; color: white; padding: 12px; text-align: left; font-size: 10px; letter-spacing: 1px; border: 1px solid #c98f7a; text-transform: uppercase; }
        .quiz-table td { padding: 15px 12px; border: 1px solid #e8d6cf; font-size: 11px; color: #5d4037; background: white; }
        .action-group { display: flex; gap: 5px; flex-wrap: wrap; }
        .action-btn { text-decoration: none; font-weight: bold; font-size: 9px; padding: 6px 10px; border-radius: 2px; transition: 0.2s; text-transform: uppercase; text-align: center; color: white; }
        .btn-add-q { background: #5d4037; }
        .btn-assign { background: #8fb9a8; }
        .btn-edit { background: #e3a693; }
        .btn-delete { background: #c98f7a; }
        .btn-view { background: #708090; }
        .admin-badge { font-size: 8px; background: #5d4037; color: white; padding: 2px 6px; border-radius: 3px; margin-left: 5px; vertical-align: middle; }
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
            <?php if ($status_msg): ?>
                <div class="alert-success"><?php echo $status_msg; ?></div>
            <?php endif; ?>

            <div class="floating-box">
                <h2 style="text-align: center; font-size: 26px; color: #5d4037; letter-spacing: 4px;">MY QUIZZES</h2>
                <p style="text-align: center; font-size: 11px; color: #c98f7a; margin-bottom: 25px; text-transform: uppercase; letter-spacing: 2px;">Manage your published assessments & system templates</p>

                <form method="GET" class="filter-bar">
                    <input type="text" name="search" class="search-input" placeholder="Search quiz title..." value="<?php echo htmlspecialchars($search); ?>">
                    <select name="category" class="filter-select">
                        <option value="">All Categories</option>
                        <option value="IT / CS" <?php echo ($category_filter == 'IT / CS') ? 'selected' : ''; ?>>IT / CS</option>
                        <option value="General Ed" <?php echo ($category_filter == 'General Ed') ? 'selected' : ''; ?>>General Ed</option>
                    </select>
                    <button type="submit" class="btn-filter">FILTER</button>
                    <?php if($search || $category_filter): ?>
                        <a href="my_quizzes.php" style="font-size: 10px; color: #c98f7a; align-self: center; margin-left: 10px;">Clear</a>
                    <?php endif; ?>
                </form>

                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <table class="quiz-table">
                        <thead>
                            <tr>
                                <th>QUIZ TITLE</th>
                                <th>CATEGORY</th>
                                <th>ITEMS</th>
                                <th>TIME LIMIT</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($result)): 
                                $total_items = ($row['mc_count'] + $row['tf_count'] + $row['sa_count'] + $row['fib_count']);
                                $is_admin = ($row['creator_role'] === 'Admin');
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['quiz_title']); ?></strong>
                                    <?php if($is_admin): ?>
                                        <span class="admin-badge">ADMIN</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo strtoupper(htmlspecialchars($row['category'])); ?></td>
                                <td><?php echo $total_items; ?> Qs</td>
                                <td><?php echo $row['time_limit']; ?> MINS</td>
                                <td>
                                    <div class="action-group">
                                        <a href="assign_quiz.php?quiz_id=<?php echo $row['id']; ?>" class="action-btn btn-assign">ASSIGN</a>
                                        
                                        <?php if(!$is_admin): ?>
                                            <a href="manage_questions.php?quiz_id=<?php echo $row['id']; ?>" class="action-btn btn-add-q">QUESTIONS</a>
                                            <a href="edit_quiz.php?id=<?php echo $row['id']; ?>" class="action-btn btn-edit">EDIT</a>
                                            <a href="delete_quiz_process.php?id=<?php echo $row['id']; ?>" 
                                               class="action-btn btn-delete" 
                                               onclick="return confirm('Are you sure?')">DELETE</a>
                                        <?php else: ?>
                                            <a href="manage_questions.php?quiz_id=<?php echo $row['id']; ?>&view=1" class="action-btn btn-view">VIEW QUESTIONS</a>
                                            <span style="font-size: 8px; color: #c98f7a; font-style: italic; align-self: center; margin-left: 5px;">System Template</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 60px; border: 2px dashed #e8d6cf; color: #8d6e63; font-style: italic;">
                        <p>No quizzes found.</p>
                        <br>
                        <a href="create_quiz.php" style="color: #e3a693; font-weight: bold; text-decoration: none;">+ CREATE ONE NOW</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>