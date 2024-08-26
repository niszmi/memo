<?php
// reset_password.php
require '../vendor/autoload.php';
require '../includes/db_connect.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<style>
    .vh-100 {
        background-color: #282d8c;
        background-image: linear-gradient(45deg, #346ecc, #40978E);
    }
    .container { padding-top: 0; }
    .row.d-flex { margin-top: 0; }
    body, html {
        height: 100%;
        margin: 0;
        align-items: center;
        justify-content: center;
        background-color: #f8f9fa;
    }
    .card {
        width: 100%;
        max-width: 700px;
        border-radius: 0.75rem;
        box-shadow: 0 4px 8px rgba(0.1, 0.1, 0.1, 0.1);
        padding: 2rem;
        background-color: white;
        text-align: center;
    }
    .card img {
        max-width: 100%;
        height: auto;
        margin-bottom: 1rem;
    }
    .card h2 {
        font-size: 1.5rem;
        font-weight: bold;
        color: #333;
        margin-bottom: 1rem;
    }
    .card p {
        font-size: 1rem;
        color: #666;
        margin-bottom: 2rem;
    }
    .form-outline {
        margin-bottom: 1.5rem;
    }
    .form-control {
        padding: 0.75rem 1rem;
        font-size: 1rem;
        border-radius: 0.5rem;
    }
    .login-button, .retry-button {
        color: white;
        padding: 0.75rem 1.5rem;
        font-size: 1rem;
        border-radius: 0.5rem;
        border: none;
        width: 100%;
        transition: background-color 0.3s;
    }
    .login-button {
        background-color: #0d6efd;
        width: auto; /* Let the width be determined by the content */
        margin: 0 auto; /* Center the button */
        display: block; /* Ensure the button is block-level for centering */

    }
    .login-button:hover {
        background-color: #0a58ca;
    }
    .retry-button {
        background-color: #dc3545;
        width: auto; /* Let the width be determined by the content */
        margin: 0 auto; /* Center the button */
        display: block; /* Ensure the button is block-level for centering */

    }
    .retry-button:hover {
        background-color: #c82333;
        transform: translateY(-5px); /* Slight lift effect on hover */

    }
    .footer {
        background-color: #333;
        color: white;
        text-align: center;
        padding: 2px 0;
        /*position: fixed;*/
        /*width: 100%;*/
        bottom: 0;
        width: 100%; /* Adjust width to account for the sidebar width */
        /*margin-left: 16%; !* Push the footer to the right by the width of the sidebar *!*/
        font-size: 10pt;
    }

</style>
<body>
<section class="vh-100">
    <div class="container py-0 h-100">
        <div class="row d-flex justify-content-center align-items-center h-100">
            <div class="col col-xl-7">
                <div class="card">
                    <div class="justify-content-center align-items-center" style="margin-top: 0">
                        <img src="../assets/images/forgot.png" class="img-fluid" alt="reset password" style="max-height: 40%; max-width: 40%;" />
                    </div>
                    <h2 style="margin-top: -10px; margin-bottom: 25px;">Reset Password</h2>

                    <!-- Display the message here -->
                    <?php
                    if (isset($_GET['message']) && isset($_GET['type'])) {
                        $message = htmlspecialchars($_GET['message']); // Prevent XSS
                        $messageType = htmlspecialchars($_GET['type']); // success or error
                        echo "<div class='alert alert-$messageType' role='alert'>$message</div>";

                        // Show appropriate button based on the type of message
                        if ($messageType == 'success') {
                            echo '<a href="index.php" class="login-button btn btn-primary btn-lg btn-block">Login</a>';
                        } else {
                            echo '<a href="forgot_password.php" class="retry-button btn btn-danger btn-lg btn-block">Try Again</a>';
                        }
                    } elseif (!isset($_GET['token']) || !isset($_GET['no_staff'])) {
                        echo '<div class="alert alert-danger" role="alert">No token or staff number provided.</div>';
                        echo '<a href="forgot_password.php" class="retry-button btn btn-danger btn-lg btn-block">Try Again</a>';
                    }

                    if (isset($_GET['token']) && isset($_GET['no_staff'])) {
                        $token = $_GET['token'];
                        $no_staff = $_GET['no_staff'];

                        // Verify the token
                        $sql = "SELECT no_staff, email, created_at FROM password_reset_tokens WHERE token = ? AND no_staff = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param('ss', $token, $no_staff);
                        $stmt->execute();
                        $stmt->store_result();
                        $stmt->bind_result($retrieved_no_staff, $email, $created_at);
                        $stmt->fetch();

                        if ($stmt->num_rows > 0) {
                            $created_at_time = strtotime($created_at);
                            $now = time();

                            if ($now < $created_at_time + 3600) { // Token is valid for 1 hour
                                // Token is valid and not expired
                                echo '<form action="update_password.php" method="post">
                                        <input type="hidden" name="token" value="'.$token.'">
                                        <input type="hidden" name="no_staff" value="'.$no_staff.'">
                                        <input type="hidden" name="email" value="'.$email.'">
                                        <div class="form-outline mb-4 shadow-sm">
                                            <label for="password" class="form-label" style="font-weight: bold">New Password:</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-lock"></i>
                                                </span>
                                                <input type="password" id="password" name="password" class="form-control" required>
                                            </div>
                                        </div>
                                        <button class="login-button btn btn-dark btn-lg btn-block" style="margin-top: 20px" type="submit">
                                            Reset Password</button>
                                      </form>';
                            } else {
                                echo '<div class="alert alert-danger" role="alert">Token has expired.</div>';
                                echo '<a href="forgot_password.php" class="retry-button btn btn-danger btn-lg btn-block">Try Again</a>';
                            }
                        } else {
                            echo '<div class="alert alert-danger" role="alert">Invalid token.</div>';
                            echo '<a href="forgot_password.php" class="retry-button btn btn-danger btn-lg btn-block">Try Again</a>';
                        }
                        $stmt->close();
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <p>&copy; 2024 Cawangan Pembangunan Sumber Manusia & HRIS FELCRA Berhad. All rights reserved.</p>
</footer>

</body>
</html>
