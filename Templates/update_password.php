<?php
// update_password.php
require '../vendor/autoload.php';
require '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['token'];
    $no_staff = $_POST['no_staff'];
    $email = $_POST['email'];
    $new_password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // Verify the token again before updating the password
    $sql = "SELECT no_staff FROM password_reset_tokens WHERE token = ? AND no_staff = ? AND email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $token, $no_staff, $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($retrieved_no_staff);
    $stmt->fetch();

    if ($stmt->num_rows > 0) {
        // Update the password
        $sql = "UPDATE users SET password = ? WHERE no_staff = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $new_password, $no_staff);
        if ($stmt->execute()) {
            // Remove the token after successful password update
            $sql = "DELETE FROM password_reset_tokens WHERE no_staff = ? AND email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ss', $no_staff, $email);
            $stmt->execute();

            // Redirect with success message
            header("Location: reset_password.php?message=Your password has been updated successfully.&type=success");
            exit();
        } else {
            // Redirect with error message
            header("Location: reset_password.php?message=Failed to update password.&type=error");
            exit();
        }
    } else {
        // Redirect with error message
        header("Location: reset_password.php?message=Invalid token or expired link.&type=error");
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: reset_password.php?message=Invalid request method.&type=error");
    exit();
}
?>
