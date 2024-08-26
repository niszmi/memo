<?php
session_start();
include '../includes/db_connect.php';
require_once '../vendor/autoload.php'; // Ensure the path is correct

use Dompdf\Dompdf;

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Error logging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$memo_id = $_GET['id'];

// Fetch memo details
$query = "SELECT * FROM letters WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $memo_id);
$stmt->execute();
$memo = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Function to format the date, handling null or invalid dates
function formatDate($date, $monthNames) {
    if (empty($date) || $date === '0000-00-00') {
        return ''; // Return empty string if date is null, empty, or default invalid date
    }

    $dateObj = DateTime::createFromFormat('Y-m-d', $date);

    if ($dateObj === false || $dateObj->format('Y-m-d') !== $date) {
        return ''; // Return empty string if date format is not valid
    }

    $day = $dateObj->format('d');
    $month = (int)$dateObj->format('m');
    $year = $dateObj->format('Y');

    return "{$day} {$monthNames[$month]} {$year}";
}

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

// Fetch user details for kepada and daripada
$kepada_query = "SELECT jawatan, name FROM users WHERE id = ?";
$kepada_stmt = $conn->prepare($kepada_query);
$kepada_stmt->bind_param('i', $memo['kepada']);
$kepada_stmt->execute();
$kepada_result = $kepada_stmt->get_result()->fetch_assoc();
$kepada = $kepada_result['jawatan'];

$daripada_query = "SELECT jawatan, name FROM users WHERE id = ?";
$daripada_stmt = $conn->prepare($daripada_query);
$daripada_stmt->bind_param('i', $memo['daripada']);
$daripada_stmt->execute();
$daripada_result = $daripada_stmt->get_result()->fetch_assoc();
$daripada = $daripada_result['jawatan'];

// Convert images to base64
$logoPath = '../assets/images/download.jpg';
$logoBase64 = file_exists($logoPath) ? 'data:image/jpg;base64,' . base64_encode(file_get_contents($logoPath)) : '';

// Convert signatures to base64 if they exist
$signatures = [];
for ($i = 1; $i <= 3; $i++) {
    $signature_key = $i == 1 ? 'signature' : 'signature_' . $i;
    $name_key = $i == 1 ? 'name' : 'name_' . $i;
    $position_key = $i == 1 ? 'jawatan' : 'position_' . $i;
    $date_key = 'signature_date_' . $i;
    $catatan_key = 'catatan_' . $i;
    $sk_key = 'sk_' . $i;
    $phone_key = 'no_phone_' . $i;
    $email_key = 'email_' . $i;

    if (!empty($memo[$signature_key])) {
        $filePath = '../' . $memo[$signature_key];

        if (file_exists($filePath)) {
            $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $mimeType = $fileExtension === 'jpg' || $fileExtension === 'jpeg' ? 'image/jpeg' : ($fileExtension === 'png' ? 'image/png' : 'application/octet-stream');
            $signatureBase64 = 'data:' . $mimeType . ';base64,' . base64_encode(file_get_contents($filePath));
        } else {
            $signatureBase64 = '';
        }

        $signatures[] = [
            'signature' => $signatureBase64,
            'name' => $memo[$name_key] ?? '',
            'position' => $memo[$position_key] ?? '',
            'date' => formatDate($memo[$date_key] ?? '', $monthNames),
            'catatan' => $memo[$catatan_key] ?? '',
            'sk' => $memo[$sk_key] ?? '',
            'no_phone' => $memo[$phone_key] ?? '',
            'email' => $memo[$email_key] ?? ''
        ];
    }
}

//function applyTableStyles($content) {
//    // Apply styles to all tables
//    $content = preg_replace('/<table(.*?)>/', '<table$1 style="width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1px solid black;">', $content);
//
//    // Apply styles to all th elements
//    $content = preg_replace('/<th(.*?)>/', '<th$1 style="border: 1px solid black; padding: 8px; text-align: left;">', $content);
//
//    // Apply styles to all td elements
//    $content = preg_replace('/<td(.*?)>/', '<td$1 style="border: 1px solid black; padding: 8px; text-align: left;">', $content);
//
//    return $content;
//}
//
//// Apply the styles to the memo contents
//$styledContents = applyTableStyles($memo['contents']);
//
// Apply the date format to the tarikh
$date_key = formatDate($memo['tarikh'], $monthNames);

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
    <title>MEMO {$memo['title']}</title>
    <style>
        @page {
            size: A4;
            margin: 0.5in;
        }
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
        }
        .header-logo {
            text-align: center;
            margin-bottom: 5px;
            margin-top: -35px;
        }
        .header-logo img {
            height: auto;
            width: auto;
            max-width: 100%;
        }
        .memo-title {
            font-size: 28pt;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .table-top {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            margin-top: -15px;
        }
        .table-top, .table-top th, .table-top td {
            border: 1px solid black;
        }
        .table-top th, .table-top td {
            padding: 6px;
            text-align: left;
            vertical-align: top;
        }
        .footer {
            position: absolute;
            font-size: 11pt;
            margin-top: 10px;
            text-align: left;
        }
        .footer .subheading {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .content {
            text-align: left;
            font-size: 11pt;
            word-wrap: break-word;
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
            margin-bottom: 10px;
        }
        .signature-block {
            margin-bottom: 10px;
        }
        .signature-block hr {
            margin-top: 5px;
            margin-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border: 1px solid black;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <div class="header-logo">
        <img src="$logoBase64" alt="FELCRA Berhad Logo">
    </div>

    <h1 style="text-align: center; margin-top: 5px">MEMO</h1>

    <table class="table-top">
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
            <td><strong>{$memo['rujukan_no']}</strong></td>
        </tr>
        <tr>
            <th scope="row">TARIKH</th>
            <td><strong>{$memo['tarikh']}</strong></td>
        </tr>
    </table>

    <div class="mb-3" style="text-align: left; line-height: 1.5; margin-bottom: 10px;">
        {$memo['panggilan']}
    </div>

    <div class="mb-3 custom-subheading">
        {$memo['title']}
    </div>

    <div class="mb-3" style="font-size: 11pt; line-height: 1.5; margin-top: 10px">
        {$memo['contents']}
    </div>

    <div class="footer" style="position: relative; bottom: 0; margin-top: 15px">
        <div class="subheading">"MALAYSIA MADANI"</div>
        <div class="subheading">"BERKHIDMAT UNTUK NEGARA"</div>
        <div class="subheading">"PEMACUAN PRESTASI - RESPONSIF - INTEGRITI - DISIPLIN - ETIKA"</div>
    </div>
    
    <div class="subheading" style="text-align: left; margin-top: 10px">Saya yang menjalankan amanah,</div>
EOD;

// Add signatures
foreach ($signatures as $index => $signature) {
    if ($signature['name'] && $signature['position']) {
        $formattedJawatan = ucwords(strtolower($signature['position']));

        $html .= <<<EOD
        <div class="signature-block" style="margin-top: 10px">
            <img src="{$signature['signature']}" alt="Signature" style="width: 110px;">
            <div style="font-weight: bold; text-transform: uppercase;">{$signature['name']}</div>
        <table style="width: 100%; margin-top: 5px; border-collapse: collapse;border: none;">
            <tr>
            <td style="width: 70%; text-align: left;border: none; padding: 0;">{$formattedJawatan}</td>
EOD;

        // Only display Tarikh if it exists
        if (!empty($signature['date'])) {
            $html .= <<<EOD
                    <td style="width: 30%; text-align: right;border: none;">Tarikh: {$signature['date']}</td>
EOD;
        }

        $html .= <<<EOD
            </tr>
        </table>
EOD;

        // Only display phone number if it exists
        if (!empty($signature['no_phone'])) {
            $html .= <<<EOD
            <div><span class="icon">{$phoneIcon}</span> {$signature['no_phone']}</div>
EOD;
        }

        // Only display email if it exists
        if (!empty($signature['email'])) {
            $html .= <<<EOD
            <div><span class="icon">{$emailIcon}</span> {$signature['email']}</div>
EOD;
        }

        // Only display s.k. if it exists
        if (!empty($signature['sk'])) {
            $html .= <<<EOD
            <div>s.k.: {$signature['sk']}</div>
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

//    echo $html;
//    exit();


$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Sanitize the title to make it a valid filename
$filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $memo['title']) . ".pdf";

// Stream the PDF content with the sanitized title as the filename
$dompdf->stream($filename, ["Attachment" => 0]); // 0 to open in browser

exit();
?>
