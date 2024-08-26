<?php
session_start();
include '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

define('INCLUDED', true);

$memo_id = $_GET['id'];

// Fetch memo details
$query = "SELECT * FROM letters WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $memo_id);
$stmt->execute();
$memo = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Split rujukan_no into base and integer parts
preg_match('/^(.*)\((\d+)\)$/', $memo['rujukan_no'], $matches);
$base_rujukan_no = $matches[1];
$rujukan_no_int = $matches[2];

// Fetch users for dropdowns
$user_query = "SELECT id, name, jawatan FROM users";
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
    <title>Edit Memo</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-3-typeahead/4.0.2/bootstrap3-typeahead.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>

    <style>
        .editor-toolbar button {
            margin: 5px;
            font-size: 16px;
        }

        .editor-container {
            min-height: 200px;
            padding: 10px;
            text-align: left;
            font-family: Arial, sans-serif;
            border: 1px solid #555;
            border-radius: 10px;
            background-color: #f9f9f9;
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

        .bg-gray {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
        }

        .form-label.al {
            display: block;
        }

        .content {
            margin-left: 15%;
            padding: 20px;
            min-height: 100vh;
            background-color: #d8dcee;
            border-radius: 20px;
        }

        .custom-subheading {
            font-weight: bold;
            text-align: left;
            line-height: 1.5;
        }

        .button-container {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 0;
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
            background-color: #9e4ccc;
            color: #ffffff;
        }

        .btn-sign:hover {
            background-color: #6c1d94;
            color: #FFFFFF;
        }

        .btn-save {
            background-color: #95a5a6;
        }

        .btn-save:hover {
            background-color: #7f8c8d;
        }

        .btn-send {
            background-color: #2ecc71;
        }

        .btn-send:hover {
            background-color: #27ae60;
        }

        .btn-view {
            background-color: #f1c40f;
            color: #000;
        }

        .btn-view:hover {
            background-color: #f39c12;
        }

        .form-sign {
            border: 1px solid #8f8e8e;
            padding: 10px 60px;
            border-radius: 5px;
            margin-bottom: 20px;
            margin-right: 20px;
            margin-left: 20px;
            margin-top: 20px;
        }

        form input[type="text"],
        form input[type="email"],
        form input[type="number"],
        form input[type="date"],
        form input[type="file"],
        form select.form-select,
        form textarea {
            border: 1px solid #555;
            padding: 10px;
            border-radius: 5px;
            background-color: #f9f9f9;
            box-sizing: border-box;
            width: 100%;
        }

        form input[type="text"]:focus,
        form input[type="email"]:focus,
        form input[type="number"]:focus,
        form input[type="date"]:focus,
        form input[type="file"]:focus,
        form select.form-select:focus,
        form textarea:focus {
            border-color: #333;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
        }

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
        /*to allow resize table*/

        /*.ui-resizable-e {*/
        /*    cursor: ew-resize;*/
        /*    width: 10px;*/
        /*    right: 0;*/
        /*    top: 0;*/
        /*    bottom: 0;*/
        /*    position: absolute;*/
        /*}*/

        /*.ui-resizable-w {*/
        /*    cursor: ew-resize;*/
        /*    width: 10px;*/
        /*    left: 0;*/
        /*    top: 0;*/
        /*    bottom: 0;*/
        /*    position: absolute;*/
        /*}*/

        /*.ui-resizable-handle {*/
        /*    background: #f1f1f1;*/
        /*    border: 1px solid #ccc;*/
        /*    z-index: 90;*/
        /*}*/
        /*table, th, td {*/
        /*    border: 1px solid black !important;*/
        /*    border-collapse: collapse !important;*/
        /*    background-color: transparent !important;*/
        /*    box-shadow: none !important;*/
        /*}*/

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
        <h1 class="h2">Edit Memo</h1>
    </div>

    <div class="form-section">
        <form id="memoForm" action="update_memo.php" method="post" enctype="multipart/form-data">
            <div class="header-logo">
                <img src="../assets/images/download.jpeg" alt="Logo">
            </div>
            <h1 style="text-align: center;">Memo</h1>
            <input type="hidden" name="id" value="<?php echo $memo_id; ?>">
            <input type="hidden" name="folder_id" value="<?php echo $memo['folder_id']; ?>">
            <input type="hidden" name="existing_signature_1" value="<?php echo $memo['signature']; ?>">
            <input type="hidden" name="existing_signature_2" value="<?php echo $memo['signature_2']; ?>">
            <input type="hidden" name="existing_signature_3" value="<?php echo $memo['signature_3']; ?>">

            <input type="hidden" id="colWidths" name="col_widths">


            <div class="row mb-3">
                <div class="col-md-3 bg-gray al">
                    <label for="kepada" class="form-label al">Kepada:</label>
                </div>
                <div class="col-md-9 bg-gray">
                    <select name="kepada" id="kepada" class="form-select form-control al" required>
                        <option value="">Select a recipient</option>
                        <?php foreach ($users as $user) : ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo ($user['id'] == $memo['kepada']) ? 'selected' : ''; ?>>
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
                        <option value="">Select a sender</option>
                        <?php foreach ($users as $user) : ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo ($user['id'] == $memo['daripada']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['jawatan'] . ': ' . $user['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3 bg-gray">
                    <label for="rujukan_no_int" class="form-label al">Rujukan:</label>
                </div>
                <div class="col-md-9 bg-gray">
                    <div class="input-group">
                        <input type="text" name="base_rujukan" id="base_rujukan" class="form-control" value="<?php echo htmlspecialchars($base_rujukan_no); ?>" readonly>
                        <span class="input-group-text">(</span>
                        <input type="number" name="rujukan_no_int" id="rujukan_no_int" class="form-control" value="<?php echo htmlspecialchars($rujukan_no_int); ?>" required>
                        <span class="input-group-text">)</span>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3 bg-gray">
                    <label for="tarikh" class="form-label al">Tarikh:</label>
                </div>
                <div class="col-md-9 bg-gray">
                    <input type="date" name="tarikh" id="tarikh" class="form-control" value="<?php echo htmlspecialchars($memo['tarikh']); ?>" required>
                </div>
            </div>

            <div class="form-group mb-3 col-md-2">
                <select name="panggilan" id="panggilan" class="form-select form-control al" required>
                    <option value="">Panggilan</option>
                    <option value="Tuan," <?php echo ($memo['panggilan'] == 'Tuan,') ? 'selected' : ''; ?>>Tuan,</option>
                    <option value="Puan," <?php echo ($memo['panggilan'] == 'Puan,') ? 'selected' : ''; ?>>Puan,</option>
                    <option value="YBhg," <?php echo ($memo['panggilan'] == 'YBhg,') ? 'selected' : ''; ?>>YBhg,</option>
                    <option value="Datuk," <?php echo ($memo['panggilan'] == 'Datuk,') ? 'selected' : ''; ?>>Datuk,</option>
                    <option value="Dato'," <?php echo ($memo['panggilan'] == "Dato',") ? 'selected' : ''; ?>>Dato',</option>
                </select>
            </div>

            <div class="form-group mb-3">
                <input type="text" name="title" id="title" class="form-control bold-input" placeholder="Title" style="font-weight: bold; font-size: 12pt;" value="<?php echo htmlspecialchars($memo['title']); ?>" required>
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
                <div class="editor-container" contenteditable="true" id="editor-container"><?php echo htmlspecialchars_decode($memo['contents']); ?></div>
                <textarea name="contents" id="contents" class="form-control" style="display:none;"></textarea>
            </div>

            <h1 class="custom-subheading" style="font-size: 12px;">"MALAYSIA MADANI"</h1>
            <h1 class="custom-subheading" style="font-size: 12px;">"BERKHIDMAT UNTUK NEGARA"</h1>
            <h1 class="custom-subheading" style="font-size: 12px;">"PEMACUAN PRESTASI - RESPONSIF - INTEGRITI - DISIPLIN - ETIKA"</h1>

            <!--            SIGNATURE SECTION-->
            <div class="form-sign">
                <h1 class="subheading" style="font-size: 13px; font-weight: normal; margin-top: 15px">Saya yang menjalankan amanah,</h1>

                <!-- Hidden fields to track deletions -->
                <input type="hidden" name="delete_signature_1" id="delete_signature_1" value="0">
                <input type="hidden" name="delete_signature_2" id="delete_signature_2" value="0">
                <input type="hidden" name="delete_signature_3" id="delete_signature_3" value="0">

                <!-- Hidden fields for each additional button -->
                <input type="hidden" name="delete_tarikh_1" id="delete_tarikh_1" value="0">
                <input type="hidden" name="delete_sk_1" id="delete_sk_1" value="0">
                <input type="hidden" name="delete_nophone_1" id="delete_nophone_1" value="0">
                <input type="hidden" name="delete_email_1" id="delete_email_1" value="0">

                <input type="hidden" name="delete_tarikh_2" id="delete_tarikh_2" value="0">
                <input type="hidden" name="delete_catatan_1" id="delete_catatan_1" value="0">

                <input type="hidden" name="delete_tarikh_3" id="delete_tarikh_3" value="0">
                <input type="hidden" name="delete_catatan_2" id="delete_catatan_2" value="0">

                <!-- Signature Fields -->
                <div class="form-group mb-3">
                    <img id="signaturePreview_1"
                         src="<?php echo !empty($memo['signature']) ? '../' . $memo['signature'] : '#'; ?>"
                         alt="Signature Preview"
                         style="max-width: 200px; max-height: 100px; <?php echo empty($memo['signature']) ? 'display: none;' : ''; ?>">
                </div>
                <div class="form-group mb-3 col-md-4">
                    <input type="text" name="name_1" id="name_1" class="form-control" placeholder="NAMA"
                           style="font-weight: bold;"
                           value="<?php echo htmlspecialchars($memo['name'] ?? ''); ?>">
                </div>

                <div class="form-group mb-3 d-flex justify-content-between align-items-center">
                    <div class="col-md-4 pr-0">
                        <input type="text" name="jawatan_1" id="jawatan_1" class="form-control" placeholder="Jawatan"
                               value="<?php echo htmlspecialchars($memo['jawatan'] ?? ''); ?>">
                    </div>
                    <div id="tarikh-container-1" class="form-group mb-3 col-md-4"
                         style="<?php echo !empty($memo['signature_date_1']) ? 'display: block;' : 'display:none;'; ?>">
                        <input type="date" name="date_1" id="date_1" class="form-control" placeholder="Date"
                               value="<?php echo htmlspecialchars($memo['signature_date_1'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Additional fields for the first signature -->
                <div id="nophone-container-1" class="form-group mb-3 col-md-4"
                     style="<?php echo !empty($memo['no_phone_1']) ? 'display: block;' : 'display:none;'; ?>">
                    <input type="text" name="no_phone_1" id="no_phone_1" class="form-control" placeholder="No. Phone"
                           value="<?php echo htmlspecialchars($memo['no_phone_1'] ?? ''); ?>">
                </div>

                <div id="email-container-1" class="form-group mb-3 col-md-4"
                     style="<?php echo !empty($memo['email_1']) ? 'display: block;' : 'display:none;'; ?>">
                    <input type="email" name="email_1" id="email_1" class="form-control" placeholder="Email"
                           value="<?php echo htmlspecialchars($memo['email_1'] ?? ''); ?>">
                </div>

                <div id="sk-container-1" class="form-group mb-3 col-md-4"
                     style="<?php echo !empty($memo['sk_1']) ? 'display: block;' : 'display:none;'; ?>">
                    <input type="text" name="sk_1" id="sk_1" class="form-control" placeholder="s.k."
                           value="<?php echo htmlspecialchars($memo['sk_1'] ?? ''); ?>">
                </div>

                <div class="form-group mb-3">
                    <div class="btn-group">
                        <button type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cogs"></i> Additional
                        </button>
                        <ul class="dropdown-menu custom-dropdown">
                            <li><button type="button" id="dropdown-tarikh" data-label="Tarikh" data-initial-state="<?php echo !empty($memo['signature_date_1']) ? 'remove' : 'add'; ?>" class="dropdown-item"><i class="fas fa-<?php echo !empty($memo['signature_date_1']) ? 'minus' : 'plus'; ?>-circle"></i> <?php echo !empty($memo['signature_date_1']) ? 'Remove' : 'Add'; ?> Tarikh</button></li>
                            <li><button type="button" id="dropdown-sk" data-label="s.k." data-initial-state="<?php echo !empty($memo['sk_1']) ? 'remove' : 'add'; ?>" class="dropdown-item"><i class="fas fa-<?php echo !empty($memo['sk_1']) ? 'minus' : 'plus'; ?>-circle"></i> <?php echo !empty($memo['sk_1']) ? 'Remove' : 'Add'; ?> s.k.</button></li>
                            <li><button type="button" id="dropdown-no-phone" data-label="No. Phone" data-initial-state="<?php echo !empty($memo['no_phone_1']) ? 'remove' : 'add'; ?>" class="dropdown-item"><i class="fas fa-<?php echo !empty($memo['no_phone_1']) ? 'minus' : 'plus'; ?>-circle"></i> <?php echo !empty($memo['no_phone_1']) ? 'Remove' : 'Add'; ?> No. Phone</button></li>
                            <li><button type="button" id="dropdown-email" data-label="Email" data-initial-state="<?php echo !empty($memo['email_1']) ? 'remove' : 'add'; ?>" class="dropdown-item"><i class="fas fa-<?php echo !empty($memo['email_1']) ? 'minus' : 'plus'; ?>-circle"></i> <?php echo !empty($memo['email_1']) ? 'Remove' : 'Add'; ?> Email</button></li>
                        </ul>
                    </div>
                </div>
                <div class="form-group mb-3 col-md-4">
                    <label for="signature_1" class="form-label">Upload digital signature:</label>
                    <input type="file" name="signature_1" id="signature_1" class="form-control" accept="image/*" onchange="previewSignature(this, 'signaturePreview_1');">
                </div>
                <hr style="border: 1px solid black;">

                <!-- Container for additional signatures -->
                <div id="additional-signatures"></div>

                <!-- Button to add more signatures -->
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

    <div class="form-section button-container d-flex mt-4">
        <button type="submit" name="save_draft" class="btn btn-save">
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

<!-- Additional Signature Logic -->
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
            table += '<tr>';
            for (var j = 0; j < cols; j++) {
                table += `<td style="border: 1px solid black; padding: 6px; width: ${colWidthArray[j]};">&nbsp;</td>`;
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
        contents.value = document.querySelector('.editor-container').innerHTML;

        var formData = new FormData(form);
        formData.append('save_sent', 'true');

        // Forcefully append files to FormData and log them
        for (var i = 1; i <= 3; i++) {
            var fileInput = document.getElementById('signature_' + i);
            var existingSignatureInput = document.getElementsByName('existing_signature_' + i)[0];

            if (fileInput && fileInput.files.length > 0) {
                formData.append('signature_' + i, fileInput.files[0]);
                console.log("Signature " + i + " file: " + fileInput.files[0].name);
            } else if (existingSignatureInput && existingSignatureInput.value) {
                console.log("Signature " + i + " existing file: " + existingSignatureInput.value);
                formData.append('existing_signature_' + i, existingSignatureInput.value);
            } else {
                console.log("Signature " + i + " file: Not uploaded, no existing file");
            }
        }

        fetch('generate_pdf.php', {
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
        contents.value = document.querySelector('.editor-container').innerHTML;

        var formData = new FormData(this);

        var isEmailAction = e.submitter && e.submitter.name === 'save_email';

        if (isEmailAction) {
            formData.append('save_email', '1');
        }

        fetch('update_memo.php', {
            method: 'POST',
            body: formData
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log(data);  // Print the response data
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
                alert('An error occurred while saving the memo. Please try again. Details: ' + error.message);
            });
    };

    document.addEventListener("DOMContentLoaded", function() {

        if (<?php echo !empty($memo['signature_date_2']) || !empty($memo['catatan_1']) || !empty($memo['jawatan_2']) ? 'true' : 'false'; ?>) {
            addSignature();
        }
        if (<?php echo !empty($memo['signature_date_3']) || !empty($memo['catatan_2']) || !empty($memo['jawatan_3']) ? 'true' : 'false'; ?>) {
            addSignature();
            addSignature();
        }


        const buttonsToCheck = [
            {buttonId: 'dropdown-tarikh', containerId: 'tarikh-container-1'},
            {buttonId: 'dropdown-sk', containerId: 'sk-container-1'},
            {buttonId: 'dropdown-no-phone', containerId: 'nophone-container-1'},
            {buttonId: 'dropdown-email', containerId: 'email-container-1'}
        ];

        buttonsToCheck.forEach(({buttonId, containerId}) => {
            const button = document.getElementById(buttonId);
            const container = document.getElementById(containerId);

            if (button.getAttribute('data-initial-state') === 'remove') {
                container.style.display = 'block';
                button.innerHTML = `<i class="fas fa-minus-circle"></i> Remove ${button.getAttribute('data-label')}`;
            } else {
                container.style.display = 'none';
                button.innerHTML = `<i class="fas fa-plus-circle"></i> Add ${button.getAttribute('data-label')}`;
            }

            button.addEventListener('click', function() {
                toggleVisibility(containerId, button);
            });
        });
    });

    function previewSignature(input, previewId) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById(previewId).src = e.target.result;
                document.getElementById(previewId).style.display = 'block';

                // Log the file name to the console
                console.log("Uploaded file path: " + input.files[0].name);
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    let signatureCount = 1;

    function addSignature() {
        const additionalSignatures = document.getElementById('additional-signatures');

        if (signatureCount === 1) {
            const signatureHTML = `
            <div id="signature-block-2">
                <div class="form-group mb-3 col-md-4" id="catatan-container-1" style="<?php echo !empty($memo['catatan_1']) ? 'display: block;' : 'display:none;'; ?>">
                    <input type="text" name="catatan_1" id="catatan_1" class="form-control" placeholder="Catatan" value="<?php echo htmlspecialchars($memo['catatan_1'] ?? ''); ?>">
                </div>
                <div class="form-group mb-3">
                    <img id="signaturePreview_2"
                         src="<?php echo !empty($memo['signature_2']) ? '../' . $memo['signature_2'] : '#'; ?>"
                         alt="Signature Preview" style="max-width: 200px; max-height: 100px; <?php echo empty($memo['signature_2']) ? 'display: none;' : ''; ?>">
                </div>
                <div class="form-group mb-3 col-md-4">
                    <input type="text" name="name_2" id="name_2" class="form-control" style="font-weight: bold;" placeholder="NAMA" value="<?php echo htmlspecialchars($memo['name_2'] ?? ''); ?>">
                </div>
                <div class="form-group mb-3 d-flex justify-content-between align-items-center">
                    <div class="col-md-4 pr-0">
                        <input type="text" name="jawatan_2" id="jawatan_2" class="form-control" placeholder="Jawatan" value="<?php echo htmlspecialchars($memo['position_2'] ?? ''); ?>">
                    </div>
                    <div id="tarikh-container-2" class="form-group mb-3 col-md-4" style="<?php echo !empty($memo['signature_date_2']) ? 'display: block;' : 'display:none;'; ?>">
                        <input type="date" name="date_2" id="date_2" class="form-control" placeholder="Date" value="<?php echo htmlspecialchars($memo['signature_date_2'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-group mb-3">
                    <div class="btn-group">
                        <button type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cogs"></i> Additional
                        </button>
                        <ul class="dropdown-menu custom-dropdown">
                            <li><button type="button" id="dropdown-tarikh-2" data-label="Tarikh" data-initial-state="<?php echo !empty($memo['signature_date_2']) ? 'remove' : 'add'; ?>" class="dropdown-item"><i class="fas fa-<?php echo !empty($memo['signature_date_2']) ? 'minus' : 'plus'; ?>-circle"></i> <?php echo !empty($memo['signature_date_2']) ? 'Remove' : 'Add'; ?> Tarikh</button></li>
                            <li><button type="button" id="dropdown-catatan-2" data-label="Catatan" data-initial-state="<?php echo !empty($memo['catatan_1']) ? 'remove' : 'add'; ?>" class="dropdown-item"><i class="fas fa-<?php echo !empty($memo['catatan_1']) ? 'minus' : 'plus'; ?>-circle"></i> <?php echo !empty($memo['catatan_1']) ? 'Remove' : 'Add'; ?> Catatan</button></li>
                        </ul>
                    </div>
                </div>
                <div class="form-group mb-3 col-md-4">
                    <label for="signature_2" class="form-label">Upload digital signature:</label>
                    <input type="file" name="signature_2" id="signature_2" class="form-control col-md-4" accept="image/*" onchange="previewSignature(this, 'signaturePreview_2');">
                </div>
                <hr style="border: 1px solid black;">
            </div>
        `;
            additionalSignatures.insertAdjacentHTML('beforeend', signatureHTML);
            signatureCount++;

            document.getElementById('dropdown-tarikh-2').addEventListener('click', function () {
                toggleVisibility('tarikh-container-2', this);
            });

            document.getElementById('dropdown-catatan-2').addEventListener('click', function () {
                toggleVisibility('catatan-container-1', this);
            });

        } else if (signatureCount === 2) {
            const signatureHTML = `
            <div id="signature-block-3">
                <div class="form-group mb-3 col-md-4" id="catatan-container-2" style="<?php echo !empty($memo['catatan_2']) ? 'display: block;' : 'display:none;'; ?>">
                    <input type="text" name="catatan_2" id="catatan_2" class="form-control" placeholder="Catatan" value="<?php echo htmlspecialchars($memo['catatan_2'] ?? ''); ?>">
                </div>
                <div class="form-group mb-3">
                    <img id="signaturePreview_3"
                         src="<?php echo !empty($memo['signature_3']) ? '../' . $memo['signature_3'] : '#'; ?>"
                         alt="Signature Preview" style="max-width: 200px; max-height: 100px; <?php echo empty($memo['signature_3']) ? 'display: none;' : ''; ?>">
                </div>
                <div class="form-group mb-3 col-md-4">
                    <input type="text" name="name_3" id="name_3" class="form-control" style="font-weight: bold;" placeholder="NAMA" value="<?php echo htmlspecialchars($memo['name_3'] ?? ''); ?>">
                </div>
                <div class="form-group mb-3 d-flex justify-content-between align-items-center">
                    <div class="col-md-4 pr-0">
                        <input type="text" name="jawatan_3" id="jawatan_3" class="form-control" placeholder="Jawatan" value="<?php echo htmlspecialchars($memo['position_3'] ?? ''); ?>">
                    </div>
                    <div id="tarikh-container-3" class="form-group mb-3 col-md-4" style="<?php echo !empty($memo['signature_date_3']) ? 'display: block;' : 'display:none;'; ?>">
                        <input type="date" name="date_3" id="date_3" class="form-control" placeholder="Date" value="<?php echo htmlspecialchars($memo['signature_date_3'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-group mb-3">
                    <div class="btn-group">
                        <button type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cogs"></i> Additional
                        </button>
                        <ul class="dropdown-menu custom-dropdown">
                            <li><button type="button" id="dropdown-tarikh-3" data-label="Tarikh" data-initial-state="<?php echo !empty($memo['signature_date_3']) ? 'remove' : 'add'; ?>" class="dropdown-item"><i class="fas fa-<?php echo !empty($memo['signature_date_3']) ? 'minus' : 'plus'; ?>-circle"></i> <?php echo !empty($memo['signature_date_3']) ? 'Remove' : 'Add'; ?> Tarikh</button></li>
                            <li><button type="button" id="dropdown-catatan-3" data-label="Catatan" data-initial-state="<?php echo !empty($memo['catatan_2']) ? 'remove' : 'add'; ?>" class="dropdown-item"><i class="fas fa-<?php echo !empty($memo['catatan_2']) ? 'minus' : 'plus'; ?>-circle"></i> <?php echo !empty($memo['catatan_2']) ? 'Remove' : 'Add'; ?> Catatan</button></li>
                        </ul>
                    </div>
                </div>
                <div class="form-group mb-3 col-md-4">
                    <label for="signature_3" class="form-label">Upload digital signature:</label>
                    <input type="file" name="signature_3" id="signature_3" class="form-control col-md-4" accept="image/*" onchange="previewSignature(this, 'signaturePreview_3');">
                </div>
                <hr style="border: 1px solid black;">
            </div>
        `;
            additionalSignatures.insertAdjacentHTML('beforeend', signatureHTML);
            signatureCount++;

            document.getElementById('dropdown-tarikh-3').addEventListener('click', function () {
                toggleVisibility('tarikh-container-3', this);
            });

            document.getElementById('dropdown-catatan-3').addEventListener('click', function () {
                toggleVisibility('catatan-container-2', this);
            });
        }

        if (signatureCount > 1) {
            document.getElementById('cancel-signature').style.display = 'inline-block';
        }
        if (signatureCount >= 3) {
            document.getElementById('add-signature').style.display = 'none';
        }
    }

    document.getElementById('add-signature').addEventListener('click', function() {
        addSignature();
    });

    // document.getElementById('cancel-signature').addEventListener('click', function() {
    //     removeSignature();
    // });

    // document.getElementById('cancel-signature').addEventListener('click', function() {
    //     // if (signatureCount > 1) {
    //     //     const additionalSignatures = document.getElementById('additional-signatures');
    //     //     additionalSignatures.removeChild(additionalSignatures.lastChild);
    //     //
    //     //     signatureCount--;
    //     //
    //     //     if (signatureCount <= 1) {
    //     //         document.getElementById('cancel-signature').style.display = 'none';
    //     //     }
    //     //     document.getElementById('add-signature').style.display = 'inline-block';
    //     // }
    //
    //     if (signatureCount > 1) {
    //         const additionalSignatures = document.getElementById('additional-signatures');
    //         if (additionalSignatures.lastChild) {
    //             additionalSignatures.removeChild(additionalSignatures.lastChild);
    //         }
    //         signatureCount--;
    //     }
    //
    //     if (signatureCount < 3) {
    //         document.getElementById('add-signature').style.display = 'inline-block';
    //     }
    //     if (signatureCount <= 1) {
    //         document.getElementById('cancel-signature').style.display = 'none';
    //     }
    // });

    document.getElementById('cancel-signature').addEventListener('click', function() {
        const additionalSignatures = document.getElementById('additional-signatures');

        if (signatureCount > 1) {
            // Only remove the last signature block
            additionalSignatures.removeChild(additionalSignatures.lastElementChild);
            signatureCount--;

            if (signatureCount === 1) {
                document.getElementById('cancel-signature').style.display = 'none'; // Hide cancel button if only one signature remains
            }

            document.getElementById('add-signature').style.display = 'inline-block'; // Show add button if any signature was removed
        }
    });

    function toggleVisibility(containerId, button) {
        // Get the HTML element that corresponds to the given containerId
        const element = document.getElementById(containerId);
        const label = button.getAttribute('data-label');

        // Check if element exists
        if (!element) {
            console.error(`Element not found for containerId: ${containerId}`);
            return; // Exit if element is not found
        }

        // Split the containerId at the last hyphen to separate the base name and index
        const splitIndex = containerId.lastIndexOf('-'); // Find the last hyphen in the ID
        const baseId = containerId.substring(0, splitIndex); // Get the part before the last hyphen (e.g., 'tarikh-container')
        const index = containerId.substring(splitIndex + 1); // Get the part after the last hyphen (e.g., '1')

        // Find the hidden field that corresponds to this container
        const hiddenField = document.getElementById(`delete_${baseId.split('-')[0]}_${index}`);

        // Log the hidden field ID for debugging
        console.log(`Looking for hidden field with ID: delete_${baseId.split('-')[0]}_${index}`);

        // Check if hidden field exists
        if (!hiddenField) {
            console.error(`Hidden field not found for containerId: ${containerId}. Expected ID: delete_${baseId.split('-')[0]}_${index}`);
            return; // Exit if hidden field is not found
        }

        // Get the initial state of the button (e.g., 'add' or 'remove')
        const initialState = button.getAttribute('data-initial-state');

        // Log the details for debugging
        console.log(`Button ID: ${button.id}, Data Initial State: ${initialState}`);

        // Toggle the visibility of the container and update the hidden field value
        if (element.style.display === "none") {
            element.style.display = "block"; // Show the container
            button.innerHTML = `<i class="fas fa-minus-circle"></i> Remove ${label}`; // Update button text
            if (initialState === "remove") {
                hiddenField.value = '0'; // Mark as not deleted
            }
        } else {
            element.style.display = "none"; // Hide the container
            button.innerHTML = `<i class="fas fa-plus-circle"></i> Add ${label}`; // Update button text
            if (initialState === "remove") {
                hiddenField.value = '1'; // Mark as deleted
            }
        }

        // Log the final value of the hidden field
        console.log(`Field ${hiddenField.id} set to ${hiddenField.value}`);
    }
</script>
</body>
</html>
