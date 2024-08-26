<?php
session_start();
include '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Define INCLUDED constant to allow access to header and sidebar
define('INCLUDED', true);

// Check if folder_id is set in session or POST
if (!isset($_POST['folder_id']) && !isset($_SESSION['folder_id'])) {
    header("Location: create_memo.php");
    exit;
}

if (isset($_POST['folder_id'])) {
    $_SESSION['folder_id'] = $_POST['folder_id'];
}

$folder_id = $_SESSION['folder_id'];

// Fetch folder details if needed
$query = "SELECT * FROM folders WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $folder_id);
$stmt->execute();
$folder = $stmt->get_result()->fetch_assoc();

// Fetch users for dropdowns
$user_query = "SELECT id, name, jawatan FROM users ORDER BY jawatan ASC";
$user_result = mysqli_query($conn, $user_query);
$users = [];
while ($user_row = mysqli_fetch_assoc($user_result)) {
    $users[] = $user_row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Create Memo - Step 2</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-3-typeahead/4.0.2/bootstrap3-typeahead.min.js"></script>
<!--    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>-->
<!--    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>-->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
<!--    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>-->
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

    <style>
        .editor-toolbar button {
            margin: 5px;
            font-size: 16px;

        }

        .editor-container {
            /*border: 1px solid #ccc;*/
            min-height: 200px;
            padding: 10px;
            text-align: left; /* Default to left alignment */
            font-family: Arial, sans-serif; /* Default to Arial font */
            border: 1px solid #555; /* Darker grey border */
            border-radius: 10px;
            background-color: #f9f9f9; /* Light grey background color for better contrast */
            page-break-inside: avoid;

        }

        /* Ensure user-defined alignments override the default */
        .editor-container div[style*="text-align: center;"] {
            text-align: center !important;
        }

        .editor-container div[style*="text-align: right;"] {
            text-align: right !important;
        }

        .header-logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .form-section {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);


        }

        .input-group-text {
            border-left: 0;
            border-right: 0;
        }

        .bg-gray {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
        }

        .form-label.al {
            display: block;
            /*text-align: center;*/
        }

        table,
        th,
        td {
            border: 1px solid black;
            border-collapse: collapse;
        }
        .content{
            /*margin-left: 200px; !* same as sidebar width *!*/
            margin-left: 15%; /* Same as the width of the sidebar */
            padding: 20px;
            min-height: 100vh;
            background-color: #d8dcee; /* Set background color to white */
            border-radius: 20px; /* Add rounded corners */
        }
        .sidebar {
            background: #ffffff;
            height: 100vh; /* Full height */
            width: 15%; /* Set the width of the sidebar */
            position: fixed; /* Fixed Sidebar (stay in place on scroll) */
            padding-top: 20px;
        }
        .custom-subheading {
            font-weight: bold;
            text-align: left;
            line-height: 1.5;
        }
        .button-container { /* Added this style for the new container */
            margin-top: 20px;
        }
        .btn {
            margin: 5px;
            font-size: 16px;
            border: none;
            padding: 10px 20px;
            color: #000000;
            transition: transform 0.2s, background-color 0.2s;
            border-radius: 5px;
        }

        .btn:hover {
            transform: scale(1.05);
        }

        .btn-sign {
            background-color: #9e4ccc; /* Blue */
            color: #ffffff;
        }

        .btn-sign:hover {
            background-color: #6c1d94;
            color: #FFFFFF;

        }

        .btn-save {
            background-color: #95a5a6; /* Gray */
        }

        .btn-save:hover {
            background-color: #7f8c8d; /* Darker Gray */
        }

        .btn-send {
            background-color: #2ecc71; /* Green */
        }

        .btn-send:hover {
            background-color: #27ae60; /* Darker Green */
        }

        .btn-view {
            background-color: #f1c40f; /* Yellow */
            color: #000;
        }

        .btn-view:hover {
            background-color: #f39c12; /* Darker Yellow */
        }
        .button-container {
            display: flex;
            justify-content: center; /* Aligns buttons to the start of the container */
            gap: 0; /* No additional gap between buttons */
        }
        .btn-info{
            background-color: #2e59f1; /* Darker Yellow */
            color: #ffffff;
        }
        .btn-info:hover{
            background-color: #1f3fc2; /* Darker Yellow */
        }
        .form-sign{
            border: 1px solid #8f8e8e; /* Set border width and color */
            padding: 10px 60px;          /* Add some padding inside the border */
            border-radius: 5px;     /* Optional: Add rounded corners */
            margin-bottom: 20px;    /* Add some space below the signature section */
            margin-right: 20px;
            margin-left: 20px;
            margin-top: 20px;
        }
        /* Apply a darker grey border to all form fields */
        form input[type="text"],
        form input[type="email"],
        form input[type="number"],
        form input[type="date"],
        form input[type="file"],
        form select.form-select, /* Specifically target select elements with form-select class */
        form textarea {
            border: 1px solid #555; /* Darker grey border */
            padding: 10px;          /* Add padding inside the field */
            border-radius: 5px;     /* Optional: Add rounded corners */
            background-color: #f9f9f9; /* Light grey background color for better contrast */
            box-sizing: border-box; /* Ensure padding is included in the total width/height */
            width: 100%;            /* Make the fields fill the container width */
        }
        /* Optional: Change the focus state for better visibility */
        form input[type="text"]:focus,
        form input[type="email"]:focus,
        form input[type="number"]:focus,
        form input[type="date"]:focus,
        form input[type="file"]:focus,
        form select.form-select:focus, /* Ensure focus style is applied to select elements */
        form textarea:focus {
            border-color: #333; /* Darken the border on focus */
            outline: none;      /* Remove the default outline */
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.2); /* Add a subtle shadow */
        }
        /* Custom Dropdown Styles */
        .custom-dropdown .dropdown-menu {
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            width: 100%;
            padding: 5px 0;
        }

        .custom-dropdown .dropdown-item {
            padding: 10px 20px;
            font-weight: 500;
            color: #000000;
            transition: background-color 0.2s, color 0.2s;
        }

        .custom-dropdown .dropdown-item:hover {
            background-color: #c2cee5;
            color: #000000;
        }

        .custom-dropdown .dropdown-toggle::after {
            margin-left: 0.5rem;
            vertical-align: middle;
        }

        .custom-dropdown .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
            color: #fff;
            padding: 10px 20px;
            font-weight: 500;
        }

        .custom-dropdown .btn-info:hover {
            background-color: #138496;
            border-color: #117a8b;
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
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="content">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Create New Memo</h1>
    </div>

    <div class="form-section">
        <form id="memoForm" action="submit_draft.php" method="post" enctype="multipart/form-data">
            <div class="header-logo">
                <img src="../assets/images/download.jpeg" alt="Logo">
            </div>
            <h1 style="text-align: center; margin-top: -45px">MEMO</h1>
            <input type="hidden" name="folder_id" value="<?php echo $folder_id; ?>">
            <input type="hidden" id="colWidths" name="col_widths">

            <div class="row mb-3">
                <div class="col-md-3 bg-gray al">
                    <label for="kepada" class="form-label al">Kepada:</label>
                </div>
                <div class="col-md-9 bg-gray">
                    <select name="kepada" id="kepada" class="form-select form-control al" required>
                        <option value=""></option>
                        <?php foreach ($users as $user) : ?>
                            <option value="<?php echo $user['id']; ?>">
                                <?php echo htmlspecialchars($user['jawatan'] . ': ' . $user['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-3 bg-gray">
                    <label for="daripada" class="form-label al">Daripada:</label>
                </div>
                <div class="col-md-9 bg-gray">
                    <select name="daripada" id="daripada" class="form-select form-control al" required>
                        <option value=""></option>
                        <?php foreach ($users as $user) : ?>
                            <option value="<?php echo $user['id']; ?>">
                                <?php echo htmlspecialchars($user['jawatan'] . ': ' . $user['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-3 bg-gray">
                    <label for="rujukan_no" class="form-label al">Rujukan:</label>
                </div>
                <div class="col-md-9 bg-gray">
                    <div class="input-group">
                        <input type="text" name="base_rujukan" id="base_rujukan" class="form-control" value="<?php echo htmlspecialchars($folder['base_rujukan_no']); ?>" readonly>
                        <span class="input-group-text">(</span>
                        <input type="number" name="rujukan_no_int" id="rujukan_no_int" class="form-control" required>
                        <span class="input-group-text">)</span>
                    </div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-3 bg-gray">
                    <label for="tarikh" class="form-label al">Tarikh:</label>
                </div>
                <div class="col-md-9 bg-gray">
                    <input type="date" name="tarikh" id="tarikh" class="form-control" required>
                </div>
            </div>

<!--            <div class="form-group mb-3 col-md-2">-->
<!--                <input type="text" name="panggilan" id="panggilan" class="form-control" placeholder="Tuan/Puan" required>-->
<!--            </div>-->

                <div class="form-group mb-3 col-md-2">
                    <select name="panggilan" id="panggilan" class="form-select form-control al" required>
                        <option value="">Panggilan</option>
                        <option value="Tuan,">Tuan,</option>
                        <option value="Puan,">Puan,</option>
                        <option value="YBhg,">YBhg,</option>
                        <option value="Datuk,">Datuk,</option>
                        <option value="Dato',">Dato',</option>
                    </select>
                </div>
            <div class="form-group mb-3">
                <input type="text" name="title" id="title" class="form-control bold-input" placeholder="Title" style="font-weight: bold; font-size: 12pt;" required>
            </div>
            <div class="form-group mb-3">
                <div class="editor-toolbar">
                    <button type="button" onclick="execCmd('bold')"><i class="fa fa-bold"></i></button>
                    <button type="button" onclick="execCmd('italic')"><i class="fa fa-italic"></i></button>
                    <button type="button" onclick="execCmd('underline')"><i class="fa fa-underline"></i></button>
                    <button type="button" onclick="execCmd('justifyleft')"><i class="fa fa-align-left"></i></button>
                    <button type="button" onclick="execCmd('justifycenter')"><i class="fa fa-align-center"></i></button>
                    <button type="button" onclick="execCmd('justifyright')"><i class="fa fa-align-right"></i></button>
                    <button type="button" onclick="execCmd('justifyFull')"><i class="fa fa-align-justify"></i></button>
                    <button type="button" onclick="execCmd('insertUnorderedList')"><i class="fa fa-list-ul"></i></button>
                    <button type="button" onclick="execCmd('insertOrderedList')"><i class="fa fa-list-ol"></i></button>
                    <button type="button" onclick="execCmd('outdent')"><i class="fa fa-outdent"></i></button>
                    <button type="button" onclick="execCmd('indent')"><i class="fa fa-indent"></i></button>
                    <button type="button" onclick="insertTable()"><i class="fa fa-table"></i></button>
                    <button type="button" onclick="execCmd('fontSize', prompt('Enter font size (1-7):', '3'))">A</button>
<!--                    <button type="button" onclick="execCmd('insertImage', prompt('Enter image URL:', 'http://'))"><i class="fa fa-image"></i></button>-->
<!--                    <button type="button" onclick="setLineHeight()"><i class="fa fa-text-height"></i></button>-->
                </div>

                <input type="hidden" name="alignment" value="justifyleft">
                <div class="editor-container" contenteditable="true" > </div>
                <textarea name="contents" class="form-control" style="display:none;"></textarea>
            </div>
            <h1 class="custom-subheading" style="font-size: 12px;">"MALAYSIA MADANI"</h1>
            <h1 class="custom-subheading" style="font-size: 12px;">"BERKHIDMAT UNTUK NEGARA"</h1>
            <h1 class="custom-subheading" style="font-size: 12px;">"PEMACUAN PRESTASI - RESPONSIF - INTEGRITI - DISIPLIN - ETIKA"</h1>

            <div class="form-sign">
            <h1 class="subheading" style="font-size: 13px; font-weight: normal; margin-top: 15px">Saya yang menjalankan amanah,</h1>


            <!-- Initial signature fields -->
            <div class="form-group mb-3">
                <img id="signaturePreview_1" src="#" alt="Signature Preview" style="max-width: 200px; max-height: 100px; display: none;">
            </div>

            <div class="form-group mb-3 col-md-4">
                <input type="text" name="name_1" id="name_1" class="form-control" placeholder="NAMA" style="font-weight: bold;">
            </div>

            <div class="form-group mb-3 d-flex justify-content-between align-items-center">
            <div class="col-md-4 pr-0">
                <input type="text" name="jawatan_1" id="jawatan_1" class="form-control" placeholder="Jawatan">
            </div>
                <div id="tarikh-container" class="form-group mb-3 col-md-4" style="display:none;">
<!--                    <label for="date_1" class="form-label">Tarikh:</label>-->
                    <input type="date" name="date_1" id="date_1" class="form-control" placeholder="Date">
                </div>
            </div>

            <!-- Tarikh field (initially hidden) -->
<!--            <div id="tarikh-container" class="form-group mb-3 col-md-4" style="display:none;">-->
<!--                <label for="date_1" class="form-label">Tarikh:</label>-->
<!--                <input type="date" name="date_1" id="date_1" class="form-control" placeholder="Date">-->
<!--            </div>-->
                <!-- Additional fields hidden initially -->
                <div id="no-phone-container" class="form-group mb-3 col-md-4" style="display:none;">
                    <input type="text" name="no_phone_1" id="no_phone_1" class="form-control" placeholder="No. Phone">
                </div>

                <div id="email-container" class="form-group mb-3 col-md-4" style="display:none;">
                    <input type="email" name="email_1" id="email_1" class="form-control" placeholder="Email">
                </div>

            <!-- s.k. field (initially hidden) -->
            <div id="sk-container" class="form-group mb-3 col-md-4" style="display:none;">
<!--                <label for="sk_1" class="form-label">s.k.</label>-->
                <input type="text" name="sk_1" id="sk_1" class="form-control" placeholder="s.k.">
            </div>

            <!-- Additional DROPDOWN button -->
            <div class="form-group mb-3">
                <div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-cogs"></i> Additional
                    </button>
                    <ul class="dropdown-menu custom-dropdown">
                        <li><button type="button" id="dropdown-sk" data-label="s.k." class="dropdown-item"><i class="fas fa-plus-circle"></i> Add s.k.</button></li>
                        <li><button type="button" id="dropdown-tarikh" data-label="Tarikh" class="dropdown-item"><i class="fas fa-plus-circle"></i> Add Tarikh</button></li><!--                        <li><button type="button" id="add-signature" class="dropdown-item"><i class="fas fa-plus-circle"></i> Add More Signature</button></li>-->
                        <li><button type="button" id="dropdown-no-phone" data-label="No. Phone" class="dropdown-item"><i class="fas fa-plus-circle"></i> Add No. Phone</button></li>
                        <li><button type="button" id="dropdown-email" data-label="Email" class="dropdown-item"><i class="fas fa-plus-circle"></i> Add Email</button></li>
                    </ul>
                </div>
            </div>
            <div class="form-group mb-3 col-md-4">
                <label for="signature_1" class="form-label">Upload digital signature:</label>
                <input type="file" name="signature_1" id="signature_1" class="form-control" accept="image/*" onchange="previewSignature(this, 'signaturePreview_1');">
            </div>
            <hr style="border: 2px solid #000000;">

            <!-- Button to toggle s.k. and tarikh fields -->
<!--            <div class="form-group mb-3">-->
<!--                <button type="button" id="toggle-sk" class="btn btn-info">-->
<!--                    <i class="fas fa-plus-circle"></i> Add s.k.-->
<!--                </button>-->
<!--                <button type="button" id="toggle-tarikh" class="btn btn-info">-->
<!--                    <i class="fas fa-plus-circle"></i> Add Tarikh-->
<!--                </button>-->
<!--            </div>-->

            <!-- Container for additional signatures -->
            <div id="additional-signatures"></div>

<!--             Button to add more signatures and cancel button -->
            <div id="signature-buttons" class="d-flex">
                <button type="button" id="add-signature" class="btn btn-sign">
                    <i class="fas fa-plus-circle"></i> Add More Signature
                </button>
                <button type="button" id="cancel-signature" class="btn btn-danger ml-2" style="display: none;">
                    <i class="fas fa-minus-circle"></i> Cancel
                </button>
            </div>
            </div>

    </div>
<!--            <div class="d-flex justify-content-between">-->
            <div class="form-section button-container d-flex mt-4"> <!-- Added this container -->
                <button type="submit" name="save_draft" class="btn btn-save" >
                    <i class="fas fa-save"></i> Save as Draft
                </button>
                <button type="submit" name="save_email" class="btn btn-send">
                    <i class="fas fa-envelope"></i> Save & Email
                </button>
                <button type="button" id="previewPDF" class="btn btn-view">
                    <i class="fas fa-file-pdf"></i> Preview in PDF
                </button>
            </div>
        </form>
    </div>
    </main>
</div>
</div>

<!-- Footer -->
<footer class="footer">
    <p>&copy; 2024 Cawangan Pembangunan Sumber Manusia & HRIS FELCRA Berhad. All rights reserved.</p>
</footer>

<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>

<script>

    function execCmd(command, value = null) {
        document.execCommand(command, false, value);
    }

    function setLineHeight() {
        var lineHeight = prompt('Enter line height (e.g., 1.5, 2):', '1.5');
        if (lineHeight) {
            document.execCommand('styleWithCSS', false, true);
            document.execCommand('lineHeight', false, lineHeight);
            document.execCommand('styleWithCSS', false, false);
        }
    }

    function insertTable() {
        var rows = parseInt(prompt('Enter number of rows:', 2), 10);
        var cols = parseInt(prompt('Enter number of columns:', 2), 10);

        if (rows && cols) {
            // Ask for column widths in one prompt
            var colWidths = prompt(`Enter widths for ${cols} columns, separated by commas (e.g., 25%,25%,25%,25%):`);

            // Split the input by commas and trim spaces
            var colWidthArray = colWidths.split(',').map(function(width) {
                return width.trim();
            });

            // Validate the inputs
            var isValid = validateWidths(colWidthArray, cols);

            if (isValid) {
// Set the hidden input field's value
                document.getElementById('colWidths').value = colWidths;

                // Create the table with the specified widths
                createTable(rows, cols, colWidthArray);
            } else {
                alert("Invalid input. Ensure each width is a number and the total does not exceed 100%.");
            }
        }
    }

    function validateWidths(colWidthArray, cols) {
        if (colWidthArray.length !== cols) {
            return false;
        }

        var totalWidth = 0;
        for (var i = 0; i < colWidthArray.length; i++) {
            var width = colWidthArray[i];

            // Remove percentage sign if present and parse as number
            var numericWidth = parseFloat(width.replace('%', ''));

            if (isNaN(numericWidth) || numericWidth <= 0 || numericWidth > 100) {
                return false; // Invalid number or width exceeds 100%
            }

            totalWidth += numericWidth;
        }

        // Ensure the total width does not exceed 100%
        if (totalWidth > 100) {
            return false;
        }

        return true;
    }

    function createTable(rows, cols, colWidthArray) {
        var table = '<table style="width: 100%; border: 1px solid black; border-collapse: collapse; table-layout: fixed;">';
        for (var i = 0; i < rows; i++) {
            table += '<tr style="height: 5px;">'; // Set default row height here
            for (var j = 0; j < cols; j++) {
                table += `<td style="border: 1px solid black; padding: 2px; width: ${colWidthArray[j]};">&nbsp;</td>`;
            }
            table += '</tr>';
        }
        table += '</table>';

        // Insert the table into the editor
        execCmd('insertHTML', table);
    }

    document.querySelector('.editor-container').addEventListener('input', function () {
        document.querySelector('textarea[name="contents"]').value = this.innerHTML;
    });


    document.getElementById('previewPDF').addEventListener('click', function() {
        var form = document.getElementById('memoForm');

        var contents = document.querySelector('textarea[name=contents]');
        contents.value = document.querySelector('.editor-container').innerHTML; // Preserve HTML content

        var formData = new FormData(form);
        formData.append('save_sent', 'true');

        fetch('generate_pdf_create.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.blob())
            .then(blob => {
                var url = window.URL.createObjectURL(blob);
                window.open(url, '_blank');
            })
            .catch(error => console.error('Error:', error));
    });

    document.getElementById('memoForm').onsubmit = function(e) {
        e.preventDefault();
        var contents = document.querySelector('textarea[name=contents]');
        contents.value = document.querySelector('.editor-container').innerHTML; // Preserve HTML content

        var formData = new FormData(this);

        // Check which button was clicked
        var isEmailAction = e.submitter && e.submitter.name === 'save_email';

        if (isEmailAction) {
            formData.append('save_email', '1');
        }

        fetch('submit_draft.php', {
            method: 'POST',
            body: formData
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving the memo. Please try again.');
            });
    };


    function previewSignature(input, previewId) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById(previewId).src = e.target.result;
                document.getElementById(previewId).style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Function to handle the Add More Signature logic
    let signatureCount = 1;

    document.getElementById('add-signature').addEventListener('click', function() {
        if (signatureCount === 1) {
            // Add second signature block
            const signatureHTML = `
<!--            <hr style="border: 1px solid black;">-->

            <div class="form-group mb-3 col-md-4" id="catatan-container-1" style="display:none;">
                <input type="text" name="catatan_1" id="catatan_1" class="form-control" placeholder="Catatan">
            </div>
        <div class="form-group mb-3">
            <img id="signaturePreview_2" src="#" alt="Signature Preview" style="max-width: 200px; max-height: 100px; display: none;">
        </div>
        <div class="form-group mb-3 col-md-4">
            <input type="text" name="name_2" id="name_2" class="form-control" placeholder="NAMA" style="font-weight: bold;">
        </div>
        <div class="form-group mb-3 d-flex justify-content-between align-items-center">
            <div class="col-md-4 pr-0">
                <input type="text" name="jawatan_2" id="jawatan_2" class="form-control" placeholder="Jawatan" >
            </div>
<!--            <div class="col-md-4 pl-0 text-right d-flex align-items-center justify-content-end">-->
            <div id="tarikh-container-2" class="form-group mb-3 col-md-4" style="display:none;">
                    <input type="date" name="date_2" id="date_2" class="form-control" placeholder="Date">
            </div>
        </div>

        </div>

        <!-- Additional DROPDOWN button for each signature -->
            <div class="form-group mb-3">
                <div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-cogs"></i> Additional
                    </button>
                    <ul class="dropdown-menu custom-dropdown">
                        <li><button type="button" id="dropdown-tarikh-2" data-label="Tarikh" class="dropdown-item"><i class="fas fa-plus-circle"></i> Add Tarikh</button></li>
                        <li><button type="button" id="dropdown-catatan-2" data-label="Catatan" class="dropdown-item"><i class="fas fa-plus-circle"></i> Add Catatan</button></li><!--                        <li><button type="button" id="add-signature" class="dropdown-item"><i class="fas fa-plus-circle"></i> Add More Signature</button></li>-->
                    </ul>
                </div>
            </div>
        <div class="form-group mb-3  col-md-4">
            <label for="signature_2" class="form-label">Upload digital signature:</label>
            <input type="file" name="signature_2" id="signature_2" class="form-control" accept="image/*" onchange="previewSignature(this, 'signaturePreview_2');">
        </div>
        <hr style="border: 1px solid black;">
        `;
            document.getElementById('additional-signatures').insertAdjacentHTML('beforeend', signatureHTML);

            // Show cancel button
            document.getElementById('cancel-signature').style.display = 'inline-block';

            signatureCount++;

            // Attach event listeners for the new dropdown items
            document.getElementById(`dropdown-tarikh-2`).addEventListener('click', function() {
                toggleVisibility(`tarikh-container-2`, this);
            });

            document.getElementById(`dropdown-catatan-2`).addEventListener('click', function() {
                toggleVisibility(`catatan-container-1`, this);
            });
            
        } else if (signatureCount === 2) {
            // Add third signature block
            const signatureHTML = `
            <div class="form-group mb-3 col-md-4" id="catatan-container-2" style="display:none;">
                <input type="text" name="catatan_2" id="catatan_2" class="form-control" placeholder="Catatan">
            </div>
        <div class="form-group mb-3">
            <img id="signaturePreview_3" src="#" alt="Signature Preview" style="max-width: 200px; max-height: 100px; display: none;">
        </div>
        <div class="form-group mb-3 col-md-4">
            <input type="text" name="name_3" id="name_3" class="form-control" placeholder="NAMA" style="font-weight: bold;">
        </div>
        <div class="form-group mb-3 d-flex justify-content-between align-items-center">
            <div class="col-md-4 pr-0">
                <input type="text" name="jawatan_3" id="jawatan_3" class="form-control" placeholder="Jawatan">
            </div>
            <div id="tarikh-container-3" class="form-group mb-3 col-md-4" style="display:none;">
                    <input type="date" name="date_3" id="date_3" class="form-control" placeholder="Date">
                </div>
            </div>
        </div>

<!-- Additional DROPDOWN button for each signature -->
            <div class="form-group mb-3">
                <div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-cogs"></i> Additional
                    </button>
                    <ul class="dropdown-menu custom-dropdown">
                        <li><button type="button" id="dropdown-tarikh-3" data-label="Tarikh" class="dropdown-item"><i class="fas fa-plus-circle"></i> Add Tarikh</button></li>
                        <li><button type="button" id="dropdown-catatan-3" data-label="Catatan" class="dropdown-item"><i class="fas fa-plus-circle"></i> Add Catatan</button></li><!--                        <li><button type="button" id="add-signature" class="dropdown-item"><i class="fas fa-plus-circle"></i> Add More Signature</button></li>-->
                    </ul>
                </div>
            </div>

        <div class="form-group mb-3  col-md-4">
            <label for="signature_2" class="form-label">Upload digital signature:</label>
        <input type="file" name="signature_3" id="signature_3" class="form-control col-md-4" accept="image/*" onchange="previewSignature(this, 'signaturePreview_3');">
        </div>
        `;
            document.getElementById('additional-signatures').insertAdjacentHTML('beforeend', signatureHTML);

            // Hide add button after adding the third signature
            document.getElementById('add-signature').style.display = 'none';

            signatureCount++;
        }

        // Attach event listeners for the new dropdown items
        document.getElementById(`dropdown-tarikh-2`).addEventListener('click', function() {
            toggleVisibility(`tarikh-container-2`, this);
        });

        // Attach event listeners for the new dropdown items
        document.getElementById(`dropdown-tarikh-3`).addEventListener('click', function() {
            toggleVisibility(`tarikh-container-3`, this);
        });

        document.getElementById(`dropdown-catatan-2`).addEventListener('click', function() {
            toggleVisibility(`catatan-container-1`, this);
        });

        document.getElementById(`dropdown-catatan-3`).addEventListener('click', function() {
            toggleVisibility(`catatan-container-2`, this);
        });
    });

    document.getElementById('cancel-signature').addEventListener('click', function() {
        if (signatureCount > 1) {
            // Remove the last added signature block
            const additionalSignatures = document.getElementById('additional-signatures');
            if (signatureCount === 3) {
                additionalSignatures.lastChild.remove(); // Remove third signature block
                document.getElementById('add-signature').style.display = 'inline-block'; // Show add button
            }
            if (signatureCount === 2) {
                additionalSignatures.innerHTML = ''; // Remove all additional signature blocks
                document.getElementById('cancel-signature').style.display = 'none'; // Hide cancel button
            }

            signatureCount--;
        }
    });

    // // Toggle s.k. field
    // document.getElementById('toggle-sk').addEventListener('click', function() {
    //     var skContainer = document.getElementById('sk-container');
    //     if (skContainer.style.display === 'none') {
    //         skContainer.style.display = 'block';
    //         this.innerHTML = '<i class="fas fa-minus-circle"></i> Remove s.k.';
    //     } else {
    //         skContainer.style.display = 'none';
    //         this.innerHTML = '<i class="fas fa-plus-circle"></i> Add s.k.';
    //         document.getElementById('sk_1').value = ''; // Clear the input field
    //     }
    // });
    //
    // // Toggle Tarikh field
    // document.getElementById('toggle-tarikh').addEventListener('click', function() {
    //     var tarikhContainer = document.getElementById('tarikh-container');
    //     if (tarikhContainer.style.display === 'none') {
    //         tarikhContainer.style.display = 'block';
    //         this.innerHTML = '<i class="fas fa-minus-circle"></i> Remove Tarikh';
    //     } else {
    //         tarikhContainer.style.display = 'none';
    //         this.innerHTML = '<i class="fas fa-plus-circle"></i> Add Tarikh';
    //         document.getElementById('date_1').value = ''; // Clear the input field
    //     }
    // });

    // Function to toggle visibility of elements and update button text
    function toggleVisibility(elementId, button) {
        var element = document.getElementById(elementId);
        var label = button.getAttribute('data-label'); // Get the label from the data attribute
        if (element.style.display === "none") {
            element.style.display = "block";
            button.innerHTML = `<i class="fas fa-minus-circle"></i> Remove ${label}`;
        } else {
            element.style.display = "none";
            button.innerHTML = `<i class="fas fa-plus-circle"></i> Add ${label}`;
        }
    }

    // Attach event listeners to dropdown items
    document.getElementById('dropdown-sk').addEventListener('click', function() {
        toggleVisibility('sk-container', this);
    });

    document.getElementById('dropdown-tarikh').addEventListener('click', function() {
        toggleVisibility('tarikh-container', this);
    });

    document.getElementById('dropdown-no-phone').addEventListener('click', function() {
        toggleVisibility('no-phone-container', this);
    });

    document.getElementById('dropdown-email').addEventListener('click', function() {
        toggleVisibility('email-container', this);
    });

    // document.getElementById(`dropdown-tarikh-2`).addEventListener('click', function() {
    //     toggleVisibility(`tarikh-container-2`, this);
    // });
    //
    // // Attach event listeners for the new dropdown items
    // document.getElementById(`dropdown-tarikh-3`).addEventListener('click', function() {
    //     toggleVisibility(`tarikh-container-3`, this);
    // });
    //
    // document.getElementById(`dropdown-catatan-2`).addEventListener('click', function() {
    //     toggleVisibility(`catatan-container-1`, this);
    // });
    //
    // document.getElementById(`dropdown-catatan-3`).addEventListener('click', function() {
    //     toggleVisibility(`catatan-container-2`, this);
    // });

    // function previewSignature(input, previewId) {
    //     if (input.files && input.files[0]) {
    //         var reader = new FileReader();
    //         reader.onload = function(e) {
    //             document.getElementById(previewId).src = e.target.result;
    //             document.getElementById(previewId).style.display = 'block';
    //         }
    //         reader.readAsDataURL(input.files[0]);
    //     }
    // }
</script>
</body>

</html>