<?php
session_start();
include '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

//// Start output buffering to capture any unexpected output
ob_start();


//// Enable error reporting for debugging purposes
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);


$letter_id = $_POST['id'];
$folder_id = $_POST['folder_id'];
$kepada = $_POST['kepada'];
$daripada = $_POST['daripada'];
$rujukan_no = $_POST['base_rujukan'] . "(" . $_POST['rujukan_no_int'] . ")";
$tarikh = $_POST['tarikh'];
$panggilan = $_POST['panggilan'];
$title = $_POST['title'];
$contents = $_POST['contents'];


try {
    // Handle deletions for each additional field
    if ($_POST['delete_tarikh_1'] == '1') {
        // Set signature_date_1 to NULL in the database
        $deleteTarikh1Query = "UPDATE letters SET signature_date_1 = NULL WHERE id = ?";
        $stmt = $conn->prepare($deleteTarikh1Query);
        $stmt->bind_param('i', $letter_id);
        if (!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }
    } else {
        // Update signature_date_1 with the value of date_1
        $updateDate1Query = "UPDATE letters SET signature_date_1 = ? WHERE id = ?";
        $stmt = $conn->prepare($updateDate1Query);
        $stmt->bind_param('si', $_POST['date_1'], $letter_id);
        if (!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }
    }

    // Handle signature deletions
// Handle deletion of signature 1
    if ($_POST['delete_signature_1'] == '1') {
        $deleteSignature1Query = "UPDATE letters SET signature = NULL, name = NULL, jawatan = NULL, signature_date_1 = NULL, no_phone_1 = NULL, email_1 = NULL, sk_1 = NULL WHERE id = ?";
        $stmt = $conn->prepare($deleteSignature1Query);
        $stmt->bind_param('i', $letter_id);
        if (!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }
    }

    if ($_POST['delete_signature_2'] == '1') {
        $deleteSignature2Query = "UPDATE letters SET signature_2 = NULL, name_2 = NULL, position_2 = NULL, signature_date_2 = NULL, catatan_1 = NULL WHERE id = ?";
        $stmt = $conn->prepare($deleteSignature2Query);
        $stmt->bind_param('i', $letter_id);
        if (!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }
    }

    if ($_POST['delete_signature_3'] == '1') {
        $deleteSignature3Query = "UPDATE letters SET signature_3 = NULL, name_3 = NULL, position_3 = NULL, signature_date_3 = NULL, catatan_2 = NULL WHERE id = ?";
        $stmt = $conn->prepare($deleteSignature3Query);
        $stmt->bind_param('i', $letter_id);
        if (!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }
    }


    // Handle deletion of sk_1
    if ($_POST['delete_sk_1'] == '1') {
        $deleteSK1Query = "UPDATE letters SET sk_1 = NULL WHERE id = ?";
        $stmt = $conn->prepare($deleteSK1Query);
        $stmt->bind_param('i', $letter_id);
        if (!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }
    }


// Handle deletion of no_phone_1
    if ($_POST['delete_nophone_1'] == '1') {
        $deletePhone1Query = "UPDATE letters SET no_phone_1 = NULL WHERE id = ?";
        $stmt = $conn->prepare($deletePhone1Query);
        $stmt->bind_param('i', $letter_id);
        if (!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }
    }

// Handle deletion of email_1
    if ($_POST['delete_email_1'] == '1') {
        $deleteEmail1Query = "UPDATE letters SET email_1 = NULL WHERE id = ?";
        $stmt = $conn->prepare($deleteEmail1Query);
        $stmt->bind_param('i', $letter_id);
        if (!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }
    }

    // Handle deletion of signature_date_2
    if ($_POST['delete_tarikh_2'] == '1') {
        $deleteTarikh2Query = "UPDATE letters SET signature_date_2 = NULL WHERE id = ?";
        $stmt = $conn->prepare($deleteTarikh2Query);
        $stmt->bind_param('i', $letter_id);
        if (!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }
    } else {
        $updateDate2Query = "UPDATE letters SET signature_date_2 = ? WHERE id = ?";
        $stmt = $conn->prepare($updateDate2Query);
        $stmt->bind_param('si', $_POST['date_2'], $letter_id);
        if (!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }
    }

    // Handle deletion of catatan_1
    if ($_POST['delete_catatan_1'] == '1') {
        $deleteCatatan1Query = "UPDATE letters SET catatan_1 = NULL WHERE id = ?";
        $stmt = $conn->prepare($deleteCatatan1Query);
        $stmt->bind_param('i', $letter_id);
        if (!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }
    }

    // Handle deletion of signature_date_3
    if ($_POST['delete_tarikh_3'] == '1') {
        $deleteTarikh3Query = "UPDATE letters SET signature_date_3 = NULL WHERE id = ?";
        $stmt = $conn->prepare($deleteTarikh3Query);
        $stmt->bind_param('i', $letter_id);
        if (!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }
    } else {
        $updateDate3Query = "UPDATE letters SET signature_date_3 = ? WHERE id = ?";
        $stmt = $conn->prepare($updateDate3Query);
        $stmt->bind_param('si', $_POST['date_3'], $letter_id);
        if (!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }
    }

    // Handle deletion of catatan_2
    if ($_POST['delete_catatan_2'] == '1') {
        $deleteCatatan2Query = "UPDATE letters SET catatan_2 = NULL WHERE id = ?";
        $stmt = $conn->prepare($deleteCatatan2Query);
        $stmt->bind_param('i', $letter_id);
        if (!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }
    }

    // Handle signatures
    $signatures = [];
    for ($i = 1; $i <= 3; $i++) {
        $name = $_POST["name_$i"] ?? null;

        // Use different database field names for jawatan
        $jawatan_field = ($i === 1) ? 'jawatan' : 'position_' . $i;
        $jawatan = $_POST["jawatan_$i"] ?? null;

        // Explicitly set to NULL if date is not provided
        $signature_date = isset($_POST["date_$i"]) && !empty($_POST["date_$i"]) ? $_POST["date_$i"] : null;
        $catatan = $_POST["catatan_$i"] ?? null;

        $signature = $_POST["existing_signature_$i"] ?? null;
        if (isset($_FILES["signature_$i"]) && $_FILES["signature_$i"]['error'] == 0) {
            $signature = 'signatures/' . basename($_FILES["signature_$i"]['name']);
            move_uploaded_file($_FILES["signature_$i"]['tmp_name'], '../' . $signature);
        }

        $signatures[] = [
            'name' => $name,
            'jawatan' => $jawatan,
            'signature' => $signature,
            'signature_date' => $signature_date,
            'catatan' => $catatan
        ];
    }

    $status = isset($_POST['save_email']) ? 'final' : 'draft';

    $query = "UPDATE letters SET 
              folder_id = ?, kepada = ?, daripada = ?, rujukan_no = ?, tarikh = ?, panggilan = ?, 
              title = ?, contents = ?, signature = ?, name = ?, jawatan = ?, signature_date_1 = ?, 
              catatan_1 = ?, signature_2 = ?, name_2 = ?, position_2 = ?, signature_date_2 = ?, 
              catatan_2 = ?, signature_3 = ?, name_3 = ?, position_3 = ?, signature_date_3 = ?, 
              status = ? 
              WHERE id = ?";
    $stmt = $conn->prepare($query);

    // Adjust signature date handling, use NULL if no date provided
    $stmt->bind_param('iiissssssssssssssssssssi',
        $folder_id, $kepada, $daripada, $rujukan_no, $tarikh, $panggilan,
        $title, $contents,
        $signatures[0]['signature'], $signatures[0]['name'], $signatures[0]['jawatan'],
        $signatures[0]['signature_date'],  // signature_date_1
        $signatures[0]['catatan'],
        $signatures[1]['signature'], $signatures[1]['name'], $signatures[1]['jawatan'],
        $signatures[1]['signature_date'],  // signature_date_2
        $signatures[1]['catatan'],
        $signatures[2]['signature'], $signatures[2]['name'], $signatures[2]['jawatan'],
        $signatures[2]['signature_date'],  // signature_date_3
        $status, $letter_id);


//// Final JSON Response
//    $response = [
//        'success' => true,
//        'message' => 'Draft saved successfully',
//        'redirect' => 'draft_section.php' // Update with the correct redirect URL
//    ];
//    echo json_encode($response);
//} catch (Exception $e) {
//    // Error Response
//    $response = [
//        'success' => false,
//        'message' => 'An error occurred: ' . $e->getMessage()
//    ];
//    echo json_encode($response);
//}
////// End the output buffer and flush the content
//ob_end_flush();

    if ($stmt->execute()) {
        $response = [
            'success' => true,
            'message' => $status === 'final' ? "Memo saved successfully. Redirecting to email page." : "Draft saved successfully!",
            'letter_id' => $letter_id,
            'redirect' => $status === 'final' ? "email_memo.php?id=$letter_id" : "draft_section.php"
        ];
    } else {
        throw new Exception("Error: " . $stmt->error);
    }
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => "An error occurred: " . $e->getMessage()
    ];
}

// End the output buffer and flush the content
ob_end_flush();

echo json_encode($response);
?>
