<?php
session_start();
require_once '../dbconnect.php'; 

// 1. SECURITY CHECK
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Instructor') {
    header('Location: ../login.php'); 
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Kunin ang numeric ID mula sa session (Dapat ito ay integer)
    $instructor_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

    if ($instructor_id === 0) {
        die("Error: Session user_id is missing. Please log out and log in again.");
    }

    // 2. SANITIZE INPUTS
    $title = mysqli_real_escape_string($conn, $_POST['quiz_title']);
    $desc  = mysqli_real_escape_string($conn, $_POST['description']);
    $time  = mysqli_real_escape_string($conn, $_POST['time_limit']);
    $cat   = mysqli_real_escape_string($conn, $_POST['category']);
    
    // Kunin ang question counts
    $mc = isset($_POST['mc_count']) ? (int)$_POST['mc_count'] : 0;
    $tf = isset($_POST['tf_count']) ? (int)$_POST['tf_count'] : 0;
    $sa = isset($_POST['sa_count']) ? (int)$_POST['sa_count'] : 0;
    $fib = isset($_POST['fib_count']) ? (int)$_POST['fib_count'] : 0;

    // 3. INSERT QUERY
    // Siguraduhin na ang 'created_by' column sa DB ay INT type
    $sql = "INSERT INTO quizzes (quiz_title, description, time_limit, category, mc_count, tf_count, sa_count, fib_count, created_by) 
            VALUES ('$title', '$desc', '$time', '$cat', $mc, $tf, $sa, $fib, $instructor_id)";

    if (mysqli_query($conn, $sql)) {
        $last_id = mysqli_insert_id($conn);
        
        // 4. REDIRECT TO ADD QUESTIONS
        // Nilipat natin sa iisang dynamic file para mas malinis ang flow
        header("Location: add_questions_instructor.php?quiz_id=$last_id");
        exit();
    } else {
        // I-display ang error kung sakaling mag-fail ang SQL
        echo "Database Error: " . mysqli_error($conn);
    }
} else {
    // Kapag sinubukang i-access ang file nang hindi POST
    header('Location: create_quiz.php');
    exit;
}
?>