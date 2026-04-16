<?php
session_start();
require_once '../dbconnect.php'; 

// 1. Ensure the user is an Instructor before allowing deletion
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Instructor') {
    header('Location: ../login.php'); 
    exit;
}

// 2. Retrieve the Quiz ID from the URL (?id=)
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];

    // STEP 1: Delete records from 'student_submissions' first
    // This is required to prevent foreign key constraint errors in the database
    $del_submissions = "DELETE FROM student_submissions WHERE quiz_id = $id";
    mysqli_query($conn, $del_submissions);

    // STEP 2: Delete all questions associated with this quiz
    $del_questions = "DELETE FROM questions WHERE quiz_id = $id";
    mysqli_query($conn, $del_questions);

    // STEP 3: Finally, delete the main quiz record from the 'quizzes' table
    $del_quiz = "DELETE FROM quizzes WHERE id = $id";

    if (mysqli_query($conn, $del_quiz)) {
        // If successful, redirect back to the My Quizzes dashboard with a success message
        header("Location: my_quizzes.php?msg=deleted");
        exit;
    } else {
        // Error handling for database failures
        echo "Error deleting quiz: " . mysqli_error($conn);
    }
} else {
    // If no ID is found in the URL, redirect back to the dashboard
    header("Location: my_quizzes.php");
    exit;
}
?>