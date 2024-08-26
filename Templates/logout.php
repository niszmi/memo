<?php
session_start();
include '../includes/db_connect.php';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Update logout_time in active_sessions
    $sql = "UPDATE active_sessions SET logout_time = CURRENT_TIMESTAMP WHERE user_id = ? AND logout_time IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Clear the session
    session_unset();
    session_destroy();
}

// Redirect to login page
header("Location: ../index.php");
exit();
?>
