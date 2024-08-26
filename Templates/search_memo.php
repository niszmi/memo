<?php
session_start();
include '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$query = $_GET['query'];

// Search in letters
$query_letters = "SELECT id FROM letters WHERE rujukan_no = ? OR title = ?";
$stmt_letters = $conn->prepare($query_letters);
$stmt_letters->bind_param('ss', $query, $query);
$stmt_letters->execute();
$result_letters = $stmt_letters->get_result();

if ($result_letters->num_rows > 0) {
    $letter = $result_letters->fetch_assoc();
    header("Location: view_memo_pdf.php?id=" . $letter['id']);
    exit();
}

// Search in old_memo
$query_old_memo = "SELECT pdf_file_path FROM old_memo WHERE rujukan_no = ? OR title = ?";
$stmt_old_memo = $conn->prepare($query_old_memo);
$stmt_old_memo->bind_param('ss', $query, $query);
$stmt_old_memo->execute();
$result_old_memo = $stmt_old_memo->get_result();

if ($result_old_memo->num_rows > 0) {
    $old_memo = $result_old_memo->fetch_assoc();
    header("Location: " . $old_memo['pdf_file_path']);
    exit();
}

// If not found, redirect back to dashboard
header("Location: dashboard.php");
exit();
