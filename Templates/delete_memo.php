<?php
session_start();
include '../includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Check if ID and source are provided
if (isset($_GET['id']) && isset($_GET['source'])) {
    $memo_id = $_GET['id'];
    $source = $_GET['source'];

    // Debug: Output memo ID and source
    echo "Memo ID: " . htmlspecialchars($memo_id) . "<br>";
    echo "Source: " . htmlspecialchars($source) . "<br>";

    // Determine the table based on the source
    $table = $source === 'upload' ? 'old_memo' : 'letters';

    // Debug: Output table name
    echo "Table: " . htmlspecialchars($table) . "<br>";

    // Delete memo from the appropriate table
    $query = "DELETE FROM $table WHERE id = ?";
    $stmt = $conn->prepare($query);

    if ($stmt === false) {
        echo "Error preparing statement: " . htmlspecialchars($conn->error);
        exit();
    }

    $stmt->bind_param('i', $memo_id);

    if ($stmt->execute()) {
        // Debug: Confirm successful deletion
        echo "Memo deleted successfully.<br>";

        // Redirect based on the source
        switch ($source) {
            case 'draft':
                header("Location: draft_section.php");
                break;
            case 'completed':
                header("Location: completed_section.php");
                break;
            case 'sent':
                header("Location: sent_section.php");
                break;
            case 'upload':
                header("Location: upload_section.php");
                break;
            default:
                header("Location: dashboard.php");
                break;
        }
        exit();
    } else {
        echo "Error deleting record: " . htmlspecialchars($stmt->error);
    }

    $stmt->close();
} else {
    header("Location: dashboard.php");
}
?>
