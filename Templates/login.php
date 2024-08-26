<?php
include '../includes/db_connect.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $no_staff = $_POST['no_staff'];
    $password = $_POST['password'];

    $sql = "SELECT id, no_staff, name, password, role, lokasi FROM users WHERE no_staff = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $no_staff);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Check if there are already 3 users from the same location logged in without a logout_time
            $lokasi = $user['lokasi'];
            $sql = "SELECT COUNT(*) AS user_count FROM active_sessions WHERE lokasi = ? AND logout_time IS NULL";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $lokasi);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            if ($row['user_count'] < 3) {
                // Allow login and create a new session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['no_staff'] = $user['no_staff'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['lokasi'] = $user['lokasi'];

                // Add entry to active_sessions
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
                $sql = "INSERT INTO active_sessions (user_id, lokasi, ip_address, user_agent) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isss", $user['id'], $user['lokasi'], $ip_address, $user_agent);
                $stmt->execute();

                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Maximum number of users from your location are already logged in.";
            }
        } else {
            $error = "Invalid password";
        }
    } else {
        $error = "Staff number not registered";
    }

    // Redirect back to index.php with the error message
    header("Location: ../index.php?error=" . urlencode($error));
    exit();
}

// If the user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
