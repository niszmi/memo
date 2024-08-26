<?php
// send_reset_link.php
require '../vendor/autoload.php'; // Include PHPMailer autoload file
require '../includes/db_connect.php'; // Include your database connection file

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$no_staff = $_POST['no_staff']; // Get staff number from form
$email = $_POST['email']; // Get email from form
$token = bin2hex(random_bytes(50)); // Generate a unique token
$expiry = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expiry time

// Check if no_staff and email exist
$sql = "SELECT email FROM users WHERE no_staff = ? AND email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $no_staff, $email);
$stmt->execute();
$stmt->store_result();

echo "No staff: $no_staff<br>";
echo "Email: $email<br>";

if ($stmt->num_rows > 0) {
    // Insert token into password_reset_tokens table
    $sql = "INSERT INTO password_reset_tokens (no_staff, email, token, created_at) VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE token = VALUES(token), created_at = VALUES(created_at)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssss', $no_staff, $email, $token, $expiry);
    $stmt->execute();

//    if ($stmt->affected_rows > 0) {
        // Token insertion successful, send email with reset link
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'aumairah271@gmail.com';
            $mail->Password = 'psinjunrchbzkgrz'; // Ensure this is stored securely
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('aumairah271@gmail.com', 'Password Request');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body = "Please click on the link below to reset your password:<br><a href='http://localhost/anis/anis/Templates/reset_password.php?token=$token&no_staff=$no_staff&email=$email'>Reset Password</a>";

            $mail->send();
//            echo 'Password reset link has been sent to your email.';
            header("Location: forgot_password.php?message=Password reset link has been sent to your email.&type=success");
            exit();
        } catch (Exception $e) {
            header("Location: forgot_password.php?message=Message could not be sent. Mailer Error: {$mail->ErrorInfo}&type=error");
            exit();        }
//    } else {
//        echo 'Failed to insert token into database.';
//    }
} else {
    header("Location: forgot_password.php?message=No account found with that staff number and email address.&type=error");
    exit();
}

$stmt->close();
$conn->close();
?>