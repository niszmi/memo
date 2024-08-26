<?php
include '../includes/db_connect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["profile_pic"])) {
    $user_id = $_SESSION['user_id'];
    $no_staff = $_SESSION['no_staff'];

    $target_dir = "../uploads/profilepic/";
    $target_file = $target_dir . basename($_FILES["profile_pic"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Validate file type
    $allowed_types = ["jpg", "jpeg", "png", "gif"];
    if (in_array($imageFileType, $allowed_types)) {
        // Rename the file to the staff_no
        $new_file_name = $target_dir . $no_staff . '.' . $imageFileType;

        // Move the uploaded file
        if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $new_file_name)) {
            // Update the profile picture path in the database
            $profile_pic_path = 'uploads/profilepic/' . $no_staff . '.' . $imageFileType;
            $sql = "UPDATE users SET profile_pic = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $profile_pic_path, $user_id);
            $stmt->execute();

            // Update the session variable
            $_SESSION['profile_pic'] = $profile_pic_path;

            // Debug output
            echo "Profile picture updated successfully: " . $profile_pic_path;
        } else {
            // Debug output
            echo "Failed to move uploaded file.";
        }
    } else {
        // Debug output
        echo "Invalid file type.";
    }
}

// Redirect back to the dashboard or the referring page
header("Location: dashboard.php");
exit();
?>
