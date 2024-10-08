<?php
include 'includes/db_connect.php';
session_start();

// If the user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Memo Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .vh-100 {
            background-color: #282d8c;
            background-image: linear-gradient(45deg, #346ecc, #40978E);
        }
        .container { padding-top: 0; }
        .row.d-flex { margin-top: 0; }
        .card {
            margin-top: 0;
            border-radius: 1rem;
        }
        .vh-100 {
            display: flex;
            align-items: start;
        }
        .login-heading {
            font-family: 'Roboto', sans-serif;
            font-size: 36px;
            font-weight: bold;
            color: #333;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            text-align: center;
            margin-top: 40px;
        }
        .subtitle {
            font-size: 18px;
            color: #666;
            text-align: center;
            margin-top: 10px;
        }
        .login-button {
            background-color: #0f74a8;
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.5);
        }
        .footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 3px 0;
            position: fixed;
            width: 100%;
            bottom: 0;
            font-size: 10pt;

        }
    </style>
</head>
<body>
<section class="vh-100">
    <div class="container py-0 h-100">
        <div class="row d-flex justify-content-center align-items-center h-100">
            <div class="col col-xl-10">
                <div class="card" style="border-radius: 1rem; background-color: white; margin-top: 0">
                    <div class="d-flex justify-content-end align-items-center flex-column">
                        <img src="assets/images/logo.png" alt="logo" class="img-fluid" style="max-height: 100px; max-width: 100px; margin-top: 20px;" />
                        <h2 class="login-heading mt-2">MEMO MANAGEMENT SYSTEM</h2>
                        <p class="subtitle">Online Memo for Felcra Berhad's Employees</p>
                    </div>
                    <div class="row g-0 mt-0">
                        <div class="col-md-6 col-lg-5 d-flex justify-content-center align-items-center">
                            <img src="assets/images/login1.png" class="img-fluid" alt="login" style="max-height: 100%; max-width: 100%;" />
                        </div>

<!--                        LEFT PANEL-->
                        <div class="col-md-6 col-lg-7 d-flex align-items-center">
                            <div class="card-body text-black">

                                <form method="POST" action="Templates/login.php">
                                    <?php if (isset($_GET['error'])): ?>
                                        <div class="alert alert-danger" role="alert">
                                            <?php echo htmlspecialchars($_GET['error']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="form-outline mb-4 shadow-sm">
                                        <label for="no_staff" style="font-weight: bold" class="form-label">Staff Number</label>
                                        <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-id-badge"></i>
                                                </span>
                                            <input type="text" id="no_staff" class="form-control" name="no_staff" required autocomplete="no_staff" autofocus>
                                        </div>
                                    </div>
                                    <div class="form-outline mb-4 shadow-sm">
                                        <label for="password" style="font-weight: bold" class="form-label">Password</label>
                                        <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-lock"></i>
                                                </span>
                                            <input type="password" id="password" class="form-control" name="password" required autocomplete="current-password">
                                        </div>
                                    </div>

                                    <div class="text-right" style="text-align: right; color: black">
                                        <a class="small btn btn-link" style="color: black" href="Templates/forgot_password.php">
                                            Forgot/Reset Password
                                        </a>
                                    </div>
                                    <div class="pt-1 mb-4">
                                        <button class="login-button btn btn-dark btn-lg btn-block" type="submit">
                                            Login
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <p>&copy; 2024 Cawangan Pembangunan Sumber Manusia & HRIS FELCRA Berhad. All rights reserved.</p>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
