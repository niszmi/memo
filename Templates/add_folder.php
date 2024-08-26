<?php
include '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['title']) && isset($_POST['base_rujukan_no'])) {
    $title = $_POST['title'];
    $base_rujukan_no = $_POST['base_rujukan_no'];

    $query = "INSERT INTO folders (title, base_rujukan_no) VALUES (?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $title, $base_rujukan_no);

    if ($stmt->execute()) {
        header('Location: create_memo.php');
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>
