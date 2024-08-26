<?php
session_start();
include '../includes/db_connect.php';
require_once '../vendor/autoload.php'; // Make sure the path is correct

use Dompdf\Dompdf;

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Error logging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Mapping of month numbers to Malay month names
$monthNames = [
    1 => 'JANUARI',
    2 => 'FEBRUARI',
    3 => 'MAC',
    4 => 'APRIL',
    5 => 'MEI',
    6 => 'JUN',
    7 => 'JULAI',
    8 => 'OGOS',
    9 => 'SEPTEMBER',
    10 => 'OKTOBER',
    11 => 'NOVEMBER',
    12 => 'DISEMBER',
];

function formatDate($date, $monthNames) {
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    if ($dateObj === false) {
        return $date; // Return the original date if the format is not valid
    }
    $day = $dateObj->format('d');
    $month = (int)$dateObj->format('m');
    $year = $dateObj->format('Y');

    return "{$day} {$monthNames[$month]} {$year}";
}
function applyTableStyles($htmlContent, $colWidths) {
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($htmlContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    $tables = $dom->getElementsByTagName('table');
    foreach ($tables as $table) {
        $table->setAttribute('style', 'width: 100%; border: 1px solid black; border-collapse: collapse; table-layout: fixed;');

        $rows = $table->getElementsByTagName('tr');
        foreach ($rows as $row) {
            $row->setAttribute('style', 'height: 15px;'); // Set default row height
            $cells = $row->getElementsByTagName('td');
            foreach ($cells as $index => $cell) {
                $width = isset($colWidths[$index]) ? $colWidths[$index] : 'auto';
//                $cell->setAttribute('style', "border: 1px solid black; padding: 6px; width: $width;");
                $cell->setAttribute('style', "border: 1px solid black; padding: 2px; width: $width; height: 15px; vertical-align: top;text-align: center;"); // Set default row height and vertical alignment

            }
        }
    }

    return $dom->saveHTML();
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['folder_id']) && isset($_POST['kepada']) && isset($_POST['daripada']) && isset($_POST['base_rujukan']) && isset($_POST['rujukan_no_int']) && isset($_POST['tarikh']) && isset($_POST['panggilan']) && isset($_POST['title']) && isset($_POST['contents']) && isset($_POST['col_widths'])) {

    $folder_id = $_POST['folder_id'];
    $kepada_id = $_POST['kepada'];
    $daripada_id = $_POST['daripada'];
    $rujukan_no = $_POST['base_rujukan'] . "(" . $_POST['rujukan_no_int'] . ")";
    $tarikh = $_POST['tarikh'];
    $panggilan = $_POST['panggilan'];
    $title = $_POST['title'];
    $contents = $_POST['contents']; // No need to use htmlspecialchars

    // Apply the date format to the tarikh
    $tarikh = formatDate($tarikh, $monthNames);

    $colWidths = explode(',', $_POST['col_widths']); // Get the column widths from the form
    $contents = applyTableStyles($_POST['contents'], $colWidths);

    // Fetch user details for kepada
    $kepada_query = "SELECT jawatan, name FROM users WHERE id = ?";
    $kepada_stmt = $conn->prepare($kepada_query);
    $kepada_stmt->bind_param('i', $kepada_id);
    $kepada_stmt->execute();
    $kepada_result = $kepada_stmt->get_result()->fetch_assoc();
    $kepada = $kepada_result['jawatan'];

    // Fetch user details for daripada
    $daripada_query = "SELECT jawatan, name FROM users WHERE id = ?";
    $daripada_stmt = $conn->prepare($daripada_query);
    $daripada_stmt->bind_param('i', $daripada_id);
    $daripada_stmt->execute();
    $daripada_result = $daripada_stmt->get_result()->fetch_assoc();
    $daripada = $daripada_result['jawatan'];

    // Save signature files if uploaded
    $signatures = [];
    for ($i = 1; $i <= 3; $i++) {
        $name = $_POST["name_$i"] ?? null;
        $jawatan = $_POST["jawatan_$i"] ?? null;
        $date = $_POST["date_$i"] ?? null;
        $sk = $_POST["sk_$i"] ?? null; // Retrieve SK for each signature
        $no_phone = $_POST["no_phone_$i"] ?? null; // Retrieve phone number for each signature
        $email = $_POST["email_$i"] ?? null; // Retrieve email for each signature
        $signature = null;


        if (isset($_FILES['signature_' . $i]) && $_FILES['signature_' . $i]['error'] == 0) {
            $signaturePath = '../signatures/' . basename($_FILES['signature_' . $i]['name']);
            move_uploaded_file($_FILES['signature_' . $i]['tmp_name'], $signaturePath);
//            $signature = 'data:image/jpeg;base64,' . base64_encode(file_get_contents('../' . $signaturePath));
            $mimeType = mime_content_type($signaturePath);

            // Convert the image to a base64 string
            $signature = 'data:' . $mimeType . ';base64,' . base64_encode(file_get_contents($signaturePath));
        }

        if ($name && $jawatan) {
            $signatures[] = [
                'name' => $name,
                'jawatan' => $jawatan,
                'date' => $date,
                'signature' => $signature,
                'sk' => $sk, // Include SK
                'no_phone' => $no_phone, // Include phone number
                'email' => $email // Include email
            ];
        }
    }

//    foreach ($signatures as $index => $signature) {
//        // Debugging: Output the signature data to check if it's being passed correctly
//        echo "<pre>";
//        echo "Signature {$index} Data:\n";
//        print_r($signature);
//        echo "</pre>";
//    }
//    exit(); // Stop the script here to inspect the output


    // Apply the date format to the signature dates
    for ($i = 0; $i < count($signatures); $i++) {
        if (!empty($signatures[$i]['date'])) {
            $signatures[$i]['date'] = formatDate($signatures[$i]['date'], $monthNames);
        }
    }


    // Convert images to base64
    $logoPath = '../assets/images/download.jpg';
    $logoData = base64_encode(file_get_contents($logoPath));
    $logoBase64 = 'data:image/jpg;base64,' . $logoData;

    // Convert the images to base64
    $phoneIconPath = '../assets/images/phone.jpg';
    $emailIconPath = '../assets/images/email.jpg';

    $phoneIconData = base64_encode(file_get_contents($phoneIconPath));
    $emailIconData = base64_encode(file_get_contents($emailIconPath));

    $phoneIcon = '<img src="data:image/jpg;base64,' . $phoneIconData . '" width="16" height="16" />';
    $emailIcon = '<img src="data:image/jpg;base64,' . $emailIconData . '" width="14" height="14" />';


    $dompdf = new Dompdf(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

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
            /*position: relative;*/
        }
        .header-logo {
            text-align: center;
            margin-bottom: 5px;
            margin-top: -35px;
        }
        .header-logo img {
            height: auto; /* Maintain aspect ratio */
            width: auto; /* Adjust width as needed */
            max-width: 120%; /* Ensure it is not bigger than the container */
        }
        .memo-title {
            font-size: 28pt; /* Adjust size as needed */
            margin-bottom: 2px; /* Space below the title */
            font-weight: bold;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px; /* Space below the table */
            margin-top: 5px;
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
            margin-top: 10px; /* Space above the footer */
            text-align: left; /* Align footer text to the left */
        }
        .footer .subheading {
            font-weight: bold;
            margin-bottom: 3px; /* Space between subheading lines */
        }
        .content {
            text-align: left; /* Default to left alignment */            
            font-size: 11pt;
            word-wrap: break-word; /* Break words that are too long to fit */
            font-family: Arial, sans-serif; /* Default to Arial font */
            page-break-inside: avoid;
            page-break-before: always;
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
            /*display: block;*/
            margin-bottom: 10px;
        }
        .signature-block {
            margin-bottom: 10px;
        }
        .signature-block hr {
            margin-top: 5px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <div class="header-logo">
        <img src="$logoBase64" alt="FELCRA Berhad Logo">
    </div>

     <h1 style="text-align: center; margin-top: 5px">MEMO</h1>

    <table class="table table-bordered">
        <tr>
            <th scope="row">KEPADA</th>
            <td><strong>{$kepada}</strong></td>
        </tr>
        <tr>
            <th scope="row">DARIPADA</th>
            <td><strong>{$daripada}</strong></td>
        </tr>
        <tr>
            <th scope="row">RUJUKAN</th>
            <td><strong>{$rujukan_no}</strong></td>
        </tr>
        <tr>
            <th scope="row">TARIKH</th>
            <td><strong>{$tarikh}</strong></td>
        </tr>
    </table>

    <div class="mb-3" style="text-align: left; line-height: 1.5; margin-bottom: 10px;">
        {$panggilan}
    </div>

    <div class="mb-3 custom-subheading">
        {$title}
    </div>

    <div class="mb-3" style="font-size: 11pt; line-height: 1.5; margin-top: 5px">
        {$contents}
    </div>

    <div class="footer" style="position: relative; bottom: 0; margin-top: 15px">
        <div class="subheading">"MALAYSIA MADANI"</div>
        <div class="subheading">"BERKHIDMAT UNTUK NEGARA"</div>
        <div class="subheading">"PEMACUAN PRESTASI - RESPONSIF - INTEGRITI - DISIPLIN - ETIKA"</div>
    </div>
    
    <div class="subheading" style="text-align: left; margin-top: 10px">Saya yang menjalankan amanah,</div>

<!--    <div class="signature-section">-->
EOD;

    // Add signatures
    foreach ($signatures as $index => $signature) {
        if ($signature['name'] && $signature['jawatan']) {

            $formattedJawatan = ucwords(strtolower($signature['jawatan']));
            $html .= <<<EOD
        <div class="signature-block" style="margin-top: 10px">
            <img src="{$signature['signature']}" alt="Signature" style="width: 110px;">
            <div style="font-weight: bold; font-size:11pt; margin-top: 5px; text-transform: uppercase;">({$signature['name']})</div>
        <table style="width: 100%; margin-top: 5px; border-collapse: collapse;">
            <tr>
            <td style="width: 70%; text-align: left;">{$formattedJawatan}</td>
EOD;

            // Only display Tarikh if it exists
            if (!empty($signature['date'])) {
                $html .= <<<EOD
                <td style="width: 30%; text-align: right;">Tarikh: {$signature['date']}</td>
EOD;
            }

            $html .= <<<EOD
            </tr>
        </table>
EOD;

            // Only display phone number if it exists
            if (!empty($signature['no_phone'])) {
                $html .= <<<EOD
            <div style="margin-top: 5px;"><span class="icon">{$phoneIcon}</span> {$signature['no_phone']}</div>
EOD;
            }

            // Only display email if it exists
            if (!empty($signature['email'])) {
                $html .= <<<EOD
            <div style="margin-top: 5px;><span class="icon">{$emailIcon}</span> {$signature['email']}</div>
EOD;
            }

            // Only display s.k. if it exists
            if (!empty($signature['sk'])) {
                $html .= <<<EOD
    <div style="margin-top: 20px;">
    <span>s.k.</span>
    <span style="padding-left: 30px; text-transform: capitalize;">: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$signature['sk']}</span>
</div>
EOD;
            }

            // Determine whether to show an HR line or not
            $totalSignatures = count($signatures);
            if (($totalSignatures == 2 && $index == 0) || ($totalSignatures == 3 && $index < 2)) {
                // Add the line before catatan if present
                $html .= '<hr style="border: 1px solid black; margin-top: 5px;">';
            }

            // Only display "Catatan" if it exists
            if (!empty($signature['catatan'])) {
                $html .= <<<EOD
            <div class="catatan" style="font-weight: bold; margin-top: 5px;">Catatan: {$signature['catatan']}</div>
EOD;
            }

            $html .= '</div>';
        }
    }

    $html .= <<<EOD
    </div>

</div>
</body>
</html>
EOD;

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $pdfContent = $dompdf->output();

    // Sanitize the title to make it a valid filename
    $filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $title) . ".pdf";

    // Output the PDF content with the sanitized title as the filename
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    echo $pdfContent;

    exit();
} else {
    echo "Invalid form submission.";
}
?>
