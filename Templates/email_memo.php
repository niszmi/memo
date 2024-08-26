<?php

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

session_start();
include '../includes/db_connect.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', 'C:/xampp1/htdocs/anis/anis/error.log');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
        exit;
    } else {
        header("Location: ../index.php");
        exit;
    }
}

$letter_id = isset($_GET['id']) ? $_GET['id'] : (isset($_POST['letter_id']) ? $_POST['letter_id'] : null);

if (!$letter_id) {
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid request. Missing letter ID.']);
        exit;
    } else {
        header("Location: dashboard.php");
        exit;
    }
}

// Fetch letter details
$query = "SELECT * FROM letters WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $letter_id);
$stmt->execute();
$result = $stmt->get_result();
$letter = $result->fetch_assoc();

if (!$letter) {
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Letter not found.']);
        exit;
    } else {
        header("Location: dashboard.php");
        exit;
    }
}

// Fetch all user emails for dropdowns
$email_query = "SELECT id, email, name FROM users";
$email_result = mysqli_query($conn, $email_query);
$users = [];
while ($user_row = mysqli_fetch_assoc($email_result)) {
    $users[] = $user_row;
}

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    $letter_id = $_POST['letter_id'];
    $sender = $_POST['sender'];
    $recipients = $_POST['recipients'];
    $cc = isset($_POST['cc']) ? $_POST['cc'] : [];
    $subject = $_POST['subject'];
    $body = $_POST['body'];

    // Debugging: Log the processed input data
    error_log("Processed data - Recipients: " . print_r($recipients, true));
    error_log("Processed data - CCs: " . print_r($cc, true));
    error_log("Processed data - Subject: " . $subject);

    // Generate PDF
    $dompdf = new Dompdf();
    $rujukan_no = $letter['rujukan_no'];
    $tarikh = $letter['tarikh'];
    $panggilan = $letter['panggilan'];
    $title = $letter['title'];
    $contents = $letter['contents'];
    $name = $letter['name'];
    $jawatan = $letter['jawatan'];

    // Fetch user details for kepada
    $kepada_query = "SELECT jawatan, name FROM users WHERE id = ?";
    $kepada_stmt = $conn->prepare($kepada_query);
    $kepada_stmt->bind_param('i', $letter['kepada']);
    $kepada_stmt->execute();
    $kepada_result = $kepada_stmt->get_result()->fetch_assoc();
    $kepada = $kepada_result['jawatan'];

    // Fetch user details for daripada
    $daripada_query = "SELECT jawatan, name FROM users WHERE id = ?";
    $daripada_stmt = $conn->prepare($daripada_query);
    $daripada_stmt->bind_param('i', $letter['daripada']);
    $daripada_stmt->execute();
    $daripada_result = $daripada_stmt->get_result()->fetch_assoc();
    $daripada = $daripada_result['jawatan'];

    // Convert images to base64
    $logoPath = '../assets/images/download.jpeg';
    $logoData = base64_encode(file_get_contents($logoPath));
    $logoBase64 = 'data:image/jpeg;base64,' . $logoData;

    $signatureBase64 = '';
    if (!empty($letter['signature'])) {
        $signatureData = base64_encode(file_get_contents('../' . $letter['signature']));
        $signatureBase64 = 'data:image/jpeg;base64,' . $signatureData;
    }

    $html = <<<EOD
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MEMO {$title}</title>
    <style>
        @page {
            size: letter;
            margin: 0.5in; /* Adjust as per your requirement */
        }
        body {
            font-family: 'Arial', sans-serif;
            padding: 0;
            margin: 0;
            text-align: center;
            position: relative;
        }
        .header-logo {
            text-align: center;
            margin-bottom: 5px;
            margin-top: -35px;

        }
        .header-logo img {
            height: auto; /* Maintain aspect ratio */
            width: auto; /* Adjust width as needed */
            max-width: 100%; /* Ensure it is not bigger than the container */
        }
        .memo-title {
            font-size: 28pt; /* Adjust size as needed */
            margin-bottom: 5px; /* Space below the title */
            font-weight: bold;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px; /* Space below the table */
            margin-top: -15px;

        }
        .table, .table th, .table td {
            border: 1px solid black; /* Add border */
        }
        .table th, .table td {
            padding: 6px; /* Adjust padding */
            text-align: left; /* Align text to the left */
            vertical-align: top; /* Align text to the top */
        }
        .table th {
            width: 25%; /* Adjust the width of the header to be 25% */
        }
        .table td {
            width: 75%; /* Adjust the width of the content to be 75% */
        }
        .footer {
            position: absolute;
            font-size: 11pt;
            margin-top: 5px; /* Space above the footer */
            text-align: left; /* Align footer text to the left */
        }
        .footer .subheading {
            font-weight: bold;
            margin-bottom: 5px; /* Space between subheading lines */
        }
        /* Adjustments for contents */
        .content {
            margin-bottom: 20px; /* Space below the content */
            text-align: justify;
            font-size: 11pt;
            word-wrap: break-word; /* Break words that are too long to fit */
        }
        .custom-subheading {
            font-weight: bold;
            text-align: left;
            line-height: 1.5;
        }
        .signature-section {
            width: 100%;
            text-align: left;
            margin-top: 20px;
        }
        .signature-section img {
            display: block;
            margin-bottom: 10px;
        }
        .footer {
        background-color: #333;
        color: white;
        text-align: center;
        padding: 2px 0;
        /*position: fixed;*/
        /*width: 100%;*/
        bottom: 0;
        width: calc(100% - 16%); /* Adjust width to account for the sidebar width */
        margin-left: 16%; /* Push the footer to the right by the width of the sidebar */
        font-size: 10pt;
    }

        
    </style>
</head>
<body>
<div class="container mt-4">
    <div class="header-logo">
        <img src="$logoBase64" alt="FELCRA Berhad Logo">
    </div>

     <h1 style="text-align: center; margin-top: -35px">MEMO</h1>

    <table class="table table-bordered">
        <tr>
            <th scope="row">KEPADA</th>
            <td>{$kepada}</td>
        </tr>
        <tr>
            <th scope="row">DARIPADA</th>
            <td>{$daripada}</td>
        </tr>
        <tr>
            <th scope="row">RUJUKAN</th>
            <td>{$rujukan_no}</td>
        </tr>
        <tr>
            <th scope="row">TARIKH</th>
            <td>{$tarikh}</td>
        </tr>
    </table>

    <div class="mb-3" style="text-align: left; line-height: 1.5; margin-bottom: 10px;">
        {$panggilan}
    </div>

    <div class="mb-3 custom-subheading">
        {$title}
    </div>

    <div class="mb-3 content" style="font-size: 11pt; line-height: 1.5; page-break-inside: avoid;">
        {$contents}
    </div>

    <div class="footer" style="position: relative; bottom: 0;">
        <div class="subheading">"MALAYSIA MADANI"</div>
        <div class="subheading">"BERKHIDMAT UNTUK NEGARA"</div>
        <div class="subheading">"PEMACUAN PRESTASI - RESPONSIF - INTEGRITI - DISIPLIN - ETIKA"</div>
    </div>
    <div class="subheading" style="text-align: left; margin-top: 10px">Saya yang menjalankan amanah,</div>

    <div class="signature-section">
        <img src="$signatureBase64" alt="Signature" style="max-width: 200px; max-height: 100px;">
        <p style="margin: 5px 0;font-weight: bold">{$name}</p>
        <p style="margin: 5px 0;">{$jawatan}</p>
    </div>
</div>
</body>
</html>
EOD;

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $pdfContent = $dompdf->output();

//    $pdfContent = $dompdf->output();
//    $pdfFileName = '../pdfs/memo_' . time() . '.pdf';
//    file_put_contents($pdfFileName, $pdfContent);
//
    // Save the PDF temporarily
    $pdfFileName = preg_replace('/[^A-Za-z0-9\-]/', '_', $title) . '.pdf';
    $pdfPath = '../temp_memo.pdf';
    file_put_contents($pdfPath, $pdfContent);

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->SMTPDebug = 0; // Enable verbose debug output
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'aumairah271@gmail.com';
        $mail->Password = 'psinjunrchbzkgrz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Sender
        $mail->setFrom($sender, 'MEMO MANAGEMENT SYSTEM');

        // Recipients
        foreach ($recipients as $recipient) {
            $mail->addAddress($recipient);
        }

        // CC
        if (!empty($cc)) {
            foreach ($cc as $ccAddress) {
                $mail->addCC($ccAddress);
            }
        }

        // Attach PDF
//        $mail->addAttachment($pdfPath, 'Memo.pdf');
        $mail->addAttachment($pdfPath, $pdfFileName);


        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();

        // Delete the temporary PDF file
        unlink($pdfPath);

//        // Delete the generated PDF file after sending the email
//        unlink($pdfFileName);

        // Update letter status to 'complete'
        $updateQuery = "UPDATE letters SET status = 'complete' WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param('i', $letter_id);
        $updateStmt->execute();

        // Save email details to the database
        $stmt = $conn->prepare("INSERT INTO emails (user_id, letter_id, subject, sent_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $_SESSION['user_id'], $letter_id, $subject);
        $stmt->execute();
        $email_id = $stmt->insert_id;

        foreach ($recipients as $recipient) {
            $stmt = $conn->prepare("INSERT INTO recipients (email_id, recipient_email) VALUES (?, ?)");
            $stmt->bind_param("is", $email_id, $recipient);
            $stmt->execute();
        }

        foreach ($cc as $ccEmail) {
            $stmt = $conn->prepare("INSERT INTO ccs (email_id, cc_email) VALUES (?, ?)");
            $stmt->bind_param("is", $email_id, $ccEmail);
            $stmt->execute();
        }

            echo json_encode(['success' => true, 'message' => 'Email sent successfully and status updated to complete!']);
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => "Email could not be sent. Mailer Error: {$mail->ErrorInfo}"]);
        }


    exit;
}

define('INCLUDED', true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Email - Memo Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="../assets/css/styles.css" rel="stylesheet">
    <style>
        <style>
        .editor-toolbar button {
            margin: 5px;
            font-size: 16px;
        }
        .editor-container {
            border: 1px solid #ccc;
            min-height: 200px;
            padding: 10px;
        }
        .sidebar {
            background: #ffffff;
            height: 100vh; /* Full height */
            width: 15%; /* Set the width of the sidebar */
            position: fixed; /* Fixed Sidebar (stay in place on scroll) */
            padding-top: 20px;
        }
        .form-container {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
    </style>

    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="content">
<!--        <main class="col-md-8 ms-sm-auto col-lg-8 ps-3 main-content">-->
    <main class="col-md-8 col-lg-8 mx-auto ps-3 main-content">

            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Send Email</h1>
            </div>

            <div id="alertContainer"></div>

            <div class="form-container"> <!-- Start of new container -->
            <form id="emailForm" action="" method="post">
                <input type="hidden" name="letter_id" value="<?php echo htmlspecialchars($letter_id); ?>">

                <div class="mb-3">
                    <label for="sender" class="form-label">Sender:</label>
                    <select class="form-select" id="sender" name="sender" required>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo htmlspecialchars($user['email']); ?>">
                                <?php echo htmlspecialchars($user['name'] . ' (' . $user['email'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="recipients" class="form-label">Recipients:</label>
                    <select class="form-select" id="recipients" name="recipients[]" multiple required>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo htmlspecialchars($user['email']); ?>">
                                <?php echo htmlspecialchars($user['name'] . ' (' . $user['email'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="cc" class="form-label">CC:</label>
                    <select class="form-select" id="cc" name="cc[]" multiple>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo htmlspecialchars($user['email']); ?>">
                                <?php echo htmlspecialchars($user['name'] . ' (' . $user['email'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="subject" class="form-label">Subject:</label>
                    <input type="text" class="form-control" id="subject" name="subject" value=" " required>
                </div>
                <div class="mb-3">
                    <label for="body" class="form-label">Body:</label>
                    <div class="editor-toolbar">
                        <button type="button" onclick="execCmd('bold')"><i class="fa fa-bold"></i></button>
                        <button type="button" onclick="execCmd('italic')"><i class="fa fa-italic"></i></button>
                        <button type="button" onclick="execCmd('underline')"><i class="fa fa-underline"></i></button>
                        <button type="button" onclick="execCmd('justifyleft')"><i class="fa fa-align-left"></i></button>
                        <button type="button" onclick="execCmd('justifycenter')"><i class="fa fa-align-center"></i></button>
                        <button type="button" onclick="execCmd('justifyright')"><i class="fa fa-align-right"></i></button>
                        <button type="button" onclick="execCmd('insertUnorderedList')"><i class="fa fa-list-ul"></i></button>
                        <button type="button" onclick="execCmd('insertOrderedList')"><i class="fa fa-list-ol"></i></button>
                        <button type="button" onclick="execCmd('outdent')"><i class="fa fa-outdent"></i></button>
                        <button type="button" onclick="execCmd('indent')"><i class="fa fa-indent"></i></button>
                        <button type="button" onclick="insertTable()"><i class="fa fa-table"></i></button>
                        <button type="button" onclick="execCmd('fontSize', prompt('Enter font size (1-7):', '3'))">A</button>
                    </div>
                    <div class="editor-container" contenteditable="true" id="editor-container"></div>
                    <textarea name="body" id="body" style="display: none;"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Send Email</button>
            </form>
            </div>
        <!-- Confirmation Modal -->
        <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmModalLabel">Confirm Send Email</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to send this memo via email?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" id="cancelSend">No</button>
                        <button type="button" class="btn btn-primary" id="confirmSend">Yes</button>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<!-- Footer -->
<footer class="footer">
    <p>&copy; 2024 Cawangan Pembangunan Sumber Manusia & HRIS FELCRA Berhad. All rights reserved.</p>
</footer>

<!--        </main>-->
<!--    </div>-->
<!--</div>-->


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        // Show the confirmation modal as soon as the page loads
        $('#confirmModal').modal('show');

        // Handle confirmation button click
        $('#confirmSend').on('click', function() {
            $('#confirmModal').modal('hide'); // Hide the modal
        });

        // Handle cancel button click
        $('#cancelSend').on('click', function() {
            window.location.href = 'completed_section.php'; // Redirect to the completed section
        });
    });
    
    $(document).ready(function() {
        $('#sender, #recipients, #cc').select2();

        // Highlight the "Create Memo" sidebar item
        $('.sidebar .btn').removeClass('active');
        $('.sidebar .btn:contains("Create New Memo")').addClass('active');

        $('#emailForm').on('submit', function(e) {
            e.preventDefault();
            var body = document.querySelector('textarea[name=body]');
            body.value = document.querySelector('.editor-container').innerHTML;

            //SEND AJAX REQUEST
            $.ajax({
                url: 'email_memo.php',
                type: 'POST',
                data: $(this).serialize() + '&ajax=1',
                dataType: 'json',
                success: function(response) {
                    console.log('Response:', response);
                    if (response.success) {
                        alert(response.message);
                        window.location.href = 'dashboard.php'; // Redirect to dashboard
                    } else {
                        $('#alertContainer').html('<div class="alert alert-danger" role="alert">' + response.message + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('XHR:', xhr);
                    console.error('Status:', status);
                    console.error('Error:', error);

                    if (xhr.status === 200 && xhr.responseText.includes('<!DOCTYPE html>')) {
                        // Session likely expired, redirect to login page
                        alert('Your session has expired. Please log in again.');
                        window.location.href = '../index.php';
                    } else {
                        $('#alertContainer').html('<div class="alert alert-danger" role="alert">An error occurred while sending the email. Please try again later or contact support.</div>');
                    }
                }
            });
        });
    });

    function execCmd(command, value = null) {
        document.execCommand(command, false, value);
    }

    function insertTable() {
        var rows = prompt('Enter number of rows:', 2);
        var cols = prompt('Enter number of columns:', 2);
        if (rows && cols) {
            var table = '<table border="1" style="width: 100%; border: 1px solid black; border-collapse: collapse;">';
            for (var i = 0; i < rows; i++) {
                table += '<tr>';
                for (var j = 0; j < cols; j++) {
                    table += '<td style="border: 1px solid black;">&nbsp;</td>';
                }
                table += '</tr>';
            }
            table += '</table>';
            execCmd('insertHTML', table);
        }
    }
</script>
</body>
</html>
