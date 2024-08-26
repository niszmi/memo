<!-- forgot_password.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .vh-100 {
            background-color: #282d8c;
            background-image: linear-gradient(45deg, #346ecc, #40978E);
        }
        .container { padding-top: 0; }
        .row.d-flex { margin-top: 0; }

        /*.vh-100 {*/
        /*    display: flex;*/
        /*    align-items: start;*/
        /*}*/

        body, html {
            height: 100%;
            margin: 0;
            /*display: flex;*/
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
        .login-button {
            background-color: #0d6efd;
            color: white;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            border-radius: 0.5rem;
            border: none;
            width: 100%;
            transition: background-color 0.3s;
        }
        .login-button:hover {
            background-color: #0a58ca;
        }
        .message {
            margin-top: 1rem;
            margin-bottom: 1rem;
            padding: 1rem;
            border-radius: 0.5rem;
            font-size: 1rem;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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

</head>
<body>
<section class="vh-100">
    <div class="container py-0 h-100">
        <div class="row d-flex justify-content-center align-items-center h-100">
            <div class="col col-xl-7" style="align-items: center">
                <div class="card" >
<!--                    <div class="d-flex justify-content-end align-items-center flex-column">-->
<!--                        <img src="../assets/images/logo.png" alt="logo" class="img-fluid" style="max-height: 100px; max-width: 100px; margin-top: 20px;" />-->
<!--                        <h2 class="login-heading mt-2">MEMO MANAGEMENT SYSTEM</h2>-->
<!--                        <p class="subtitle">Online Memo for Felcra Berhad's Employees</p>-->
<!--                    </div>-->
<!--                    <div class="row g-0 mt-0">-->
                        <div class="justify-content-center align-items-center" style="margin-top: 0">
                            <img src="../assets/images/forgot.png" class="img-fluid" alt="login" style="max-height: 40%; max-width: 40%;" />
                        </div>
<!---->
<!--                        LEFT PANEL-->
<!--                        <div class="col-md-6 col-lg-7 d-flex align-items-center">-->
<!--                            <div class="card-body text-black">-->
<!---->
                            <h2 style="margin-top: 10px; margin-bottom: 25px;">Forgot Password</h2>

                    <!-- Display the message here -->
                    <?php
                    if (isset($_GET['message'])) {
                        $message = $_GET['message'];
                        $messageType = $_GET['type']; // success or error
                        echo "<div class='message $messageType'>$message</div>";
                    }
                    ?>

                                <form action="send_reset_link.php" method="post">

                            <div class="form-outline mb-4 shadow-sm">
                            <label for="no_staff" style="font-weight: bold" class="form-label">Enter your staff number:</label>
                                <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-id-badge"></i>
                                </span>
                            <input type="text" id="no_staff" name="no_staff" class="form-control" required>
                                </div>
                            </div>
                                    <div class="form-outline mb-4 shadow-sm">
                                        <label for="email" style="font-weight: bold" class="form-label">Enter your email address:</label>
                                        <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-envelope"></i>
                                                </span>
                                            <input type="email" id="email" name="email" class="form-control" required>
                                        </div>
                                    </div>
                                            <button class="login-button btn btn-dark btn-lg btn-block" style="margin-top: 20px" type="submit">
                                                Send Reset Link</button>
                                </form>
                </div>
            </div>
        </div>
    </div>
</section>
</body>
</html>

<!-- Footer -->
<footer class="footer">
    <p>&copy; 2024 Cawangan Pembangunan Sumber Manusia & HRIS FELCRA Berhad. All rights reserved.</p>
</footer>



