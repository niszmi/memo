<?php
session_start();
include '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

function sendJsonResponse($success, $message, $letter_id = null, $redirect = null)
{
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'letter_id' => $letter_id,
        'redirect' => $redirect
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $folder_id = $_POST['folder_id'];
    $kepada = $_POST['kepada'];
    $daripada = $_POST['daripada'];
    $rujukan_no = $_POST['base_rujukan'] . "(" . $_POST['rujukan_no_int'] . ")";
    $tarikh = $_POST['tarikh'];
    $panggilan = $_POST['panggilan'];
    $title = $_POST['title'];
    $contents = $_POST['contents'];

    // Retrieve user_id from session
    $user_id = $_SESSION['user_id'];

    // Check if it's a draft or final save
    $status = isset($_POST['save_email']) ? 'final' : 'draft';

    // Initialize signature data
    $signatures = [];
    for ($i = 1; $i <= 3; $i++) {
        $name = $_POST["name_$i"] ?? null;
        $jawatan = $_POST["jawatan_$i"] ?? null;
        $catatan = $_POST["catatan_$i"] ?? null;// Retrieve catatan for each signature
        $date = $_POST["date_$i"] ?? null;
        $sk = $_POST["sk_$i"] ?? null; // Add SK for each signature
        $no_phone = $_POST["no_phone_$i"] ?? null; // Add phone number for each signature
        $email = $_POST["email_$i"] ?? null; // Add email for each signature
        $signature = null;

        if (isset($_FILES["signature_$i"]) && $_FILES["signature_$i"]['error'] == 0) {
            $signature = 'signatures/' . basename($_FILES["signature_$i"]['name']);
            move_uploaded_file($_FILES["signature_$i"]['tmp_name'], '../' . $signature);
        }

        $signatures[] = [
            'name' => $name,
            'jawatan' => $jawatan,
            'signature' => $signature,
            'catatan' => $catatan,
            'date' => $date,
            'sk' => $sk, // Store SK
            'no_phone' => $no_phone, // Store phone number
            'email' => $email // Store email
        ];
    }

    // Check if signature dates are empty and set to NULL if they are
    $signature_date_1 = empty($signatures[0]['date']) ? NULL : $signatures[0]['date'];
    $signature_date_2 = empty($signatures[1]['date']) ? NULL : $signatures[1]['date'];
    $signature_date_3 = empty($signatures[2]['date']) ? NULL : $signatures[2]['date'];

    // Save to database
    $query = "INSERT INTO letters 
                (folder_id, kepada, daripada, rujukan_no, tarikh, panggilan, title, contents, 
                signature, name, jawatan, catatan_1, 
                sk_1, no_phone_1, email_1, 
                status, user_id, 
                signature_2, name_2, position_2, catatan_2, 
                signature_3, name_3, position_3, 
                signature_date_1, signature_date_2, signature_date_3) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        'iiisssssssssssssissssssssss',
        $folder_id, $kepada, $daripada, $rujukan_no, $tarikh, $panggilan, $title, $contents,
        $signatures[0]['signature'], $signatures[0]['name'], $signatures[0]['jawatan'], $signatures[0]['catatan'], // Correct catatan_1
        $signatures[0]['sk'], $signatures[0]['no_phone'], $signatures[0]['email'],
        $status, $user_id,
        $signatures[1]['signature'], $signatures[1]['name'], $signatures[1]['jawatan'], $signatures[1]['catatan'], // Correct catatan_2
        $signatures[2]['signature'], $signatures[2]['name'], $signatures[2]['jawatan'],
        $signature_date_1, $signature_date_2, $signature_date_3
    );

    if ($stmt->execute()) {
        $letter_id = $stmt->insert_id;
        if ($status == 'final') {
            sendJsonResponse(true, "Memo saved successfully. Redirecting to email page.", $letter_id, "email_memo.php?id=$letter_id");
        } else {
            sendJsonResponse(true, "Draft saved successfully!", $letter_id, "draft_section.php");
        }
    } else {
        sendJsonResponse(false, "Error: " . $stmt->error);
    }
} else {
    sendJsonResponse(false, "Invalid request.");
}
