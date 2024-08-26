<?php
session_start();
include '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Define INCLUDED constant to allow access to header and sidebar
define('INCLUDED', true);

// Fetch folders from the database
$query = "SELECT id, title, base_rujukan_no FROM folders";
$result = mysqli_query($conn, $query);

// Pagination settings
$limit = 10; // Number of entries to show in a page.
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Fetch the location_id of the authenticated user
$query_location = "SELECT lokasi FROM users WHERE id = ?";
$stmt_location = $conn->prepare($query_location);
$stmt_location->bind_param("i", $user_id);
$stmt_location->execute();
$result_location = $stmt_location->get_result();
$user_location = $result_location->fetch_assoc()['lokasi'];
$stmt_location->close();


//// Fetch total number of old memos for the authenticated user
//$query_total = "SELECT COUNT(id) AS total FROM old_memo WHERE uploaded_by = ?";
//$stmt_total = $conn->prepare($query_total);
//$stmt_total->bind_param("i", $user_id);
//$stmt_total->execute();
//$result_total = $stmt_total->get_result();
//$total = $result_total->fetch_assoc()['total'];
//$pages = ceil($total / $limit);
//$stmt_total->close();
//
//// Fetch old memos for the authenticated user from the database
//$query_memos = "SELECT id, title, rujukan_no, date_created, pdf_file_path FROM old_memo WHERE uploaded_by = ? LIMIT ?, ?";
//$stmt_memos = $conn->prepare($query_memos);
//$stmt_memos->bind_param("iii", $user_id, $start, $limit);
//$stmt_memos->execute();
//$result_memos = $stmt_memos->get_result();
//$old_memos = $result_memos->fetch_all(MYSQLI_ASSOC);
//$stmt_memos->close();

// Fetch total number of old memos for users in the same location
$query_total = "
    SELECT COUNT(old_memo.id) AS total 
    FROM old_memo 
    JOIN users ON old_memo.uploaded_by = users.id 
    WHERE users.lokasi = ?";
$stmt_total = $conn->prepare($query_total);
$stmt_total->bind_param("i", $user_location);
$stmt_total->execute();
$result_total = $stmt_total->get_result();
$total = $result_total->fetch_assoc()['total'];
$pages = ceil($total / $limit);
$stmt_total->close();

// Fetch old memos for users in the same location
$query_memos = "
    SELECT old_memo.id, old_memo.title, old_memo.rujukan_no, old_memo.date_created, old_memo.pdf_file_path 
    FROM old_memo 
    JOIN users ON old_memo.uploaded_by = users.id 
    WHERE users.lokasi = ? 
    LIMIT ?, ?";
$stmt_memos = $conn->prepare($query_memos);
$stmt_memos->bind_param("iii", $user_location, $start, $limit);
$stmt_memos->execute();
$result_memos = $stmt_memos->get_result();
$old_memos = $result_memos->fetch_all(MYSQLI_ASSOC);
$stmt_memos->close();

// Set no memos message if there are no memos
$noMemosMessage = empty($old_memos) ? "No uploaded memos found." : "";

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $folder_id = $_POST['folder_id'];
    $title = $_POST['title'];
    $rujukan_no_int = $_POST['rujukan_no_int'];
    $date_created = date('Y-m-d');
    $uploaded_file = $_FILES['uploaded_file'];

    // Fetch base rujukan no
    $query = "SELECT base_rujukan_no FROM folders WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $folder_id);
    $stmt->execute();
    $folder = $stmt->get_result()->fetch_assoc();
    $base_rujukan_no = $folder['base_rujukan_no'];
    $stmt->close();

    $rujukan_no = $base_rujukan_no . "(" . $rujukan_no_int . ")";

    // Check for duplicate rujukan_no
    $query = "SELECT COUNT(*) as count FROM old_memo WHERE rujukan_no = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $rujukan_no);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($result['count'] > 0) {
        $error = "Duplicate rujukan_no.";
    } else {
        // Handle file upload
        $target_dir = "../uploads/";
        $target_file = $target_dir . basename($uploaded_file['name']);
        if (move_uploaded_file($uploaded_file['tmp_name'], $target_file)) {
            // Insert new memo into old_memo database
            $query = "INSERT INTO old_memo (folder_id, title, rujukan_no, date_created, pdf_file_path, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $pdf_file_path = $target_file;

            $stmt->bind_param('issssi', $folder_id, $title, $rujukan_no, $date_created, $pdf_file_path, $user_id);

            if ($stmt->execute()) {
                $success = "Memo uploaded successfully.";
                // Refresh the page to load the new memo
                echo "<script>window.location.href = 'upload_section.php';</script>";
                exit();
            } else {
                $error = "Error uploading memo.";
            }
            $stmt->close();
        } else {
            $error = "Error uploading file.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Upload Section</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            background: #ffffff;
            height: 100vh; /* Full height */
            width: 15%; /* Set the width of the sidebar */
            position: fixed; /* Fixed Sidebar (stay in place on scroll) */
            padding-top: 20px;
        }

        .content {
            margin-left: 16%; /* Same as the width of the sidebar */
            padding: 20px;
            min-height: 100vh;
            background-color: #d8dcee; /* Set background color to white */
            border-radius: 20px; /* Add rounded corners */
            margin-top: 40px;
        }
        .folders {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .folder {
            width: 100px;
            margin: 10px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .folder img {
            width: 100px;
            height: 100px;
        }
        .folder:hover {
            transform: scale(1.1);
        }
        .folder-title {
            margin-top: 10px;
            font-size: 14px;
            font-weight: bold;
        }
        modal-header {
            background-color: #289ead;
            color: white;
        }
        .modal-header .close {
            color: white;
        }
        .modal-content {
            border-radius: 20px;
        }
        .form-control {
            border-radius: 5px;
        }
        .btn-primary {
            background-color: #289ead;
            border-color: #289ead;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0.1, 0.1, 0.1, 0.1); /* Add shadow here */
            margin: 5px;
            padding: 10px;

        }
        .btn-primary:hover {
            background-color: #0a5460;
            border-color: #0a5460;
        }
        .modal-body {
            padding: 2rem;
        }
        .documents-table .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background-color: #f8f9fa;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0.3, 0.3, 0.3, 0.3); /* Add shadow here */
        }

        .documents-table th, .documents-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd; /* Light gray border for rows */
        }

        .documents-table th {
            background-color: #207080; /* Teal background for headers */
            color: white;
            font-weight: bold;
        }

        .documents-table thead tr:first-child th:first-child {
            border-top-left-radius: 10px; /* Rounded top left corner */
        }

        .documents-table thead tr:first-child th:last-child {
            border-top-right-radius: 10px; /* Rounded top right corner */
        }

        .documents-table tbody tr:last-child td:first-child {
            border-bottom-left-radius: 10px; /* Rounded bottom left corner */
        }

        .documents-table tbody tr:last-child td:last-child {
            border-bottom-right-radius: 10px; /* Rounded bottom right corner */
        }

        .documents-table tbody tr:hover {
            background-color: #e7effc; /* Light blue background on row hover */
        }

        .documents-table td {
            color: #333; /* Dark gray text color for table data */
        }

        .documents-table thead tr {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); /* Subtle shadow under header row */
        }

        .documents-table .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th, .table td {
            padding: 8px;
            border-bottom: 1px solid #ccc;
        }
        /* Common button styles */
        .btn {
            display: inline-block;
            width: 100px;
            /*padding: 10px 20px;*/
            font-size: 16px;
            /*font-weight: bold;*/
            text-align: center;
            text-decoration: none;
            border-radius: 8px;
            transition: background-color 0.3s, color 0.3s;
            margin-bottom: 5px;
        }

        /* View button styles */
        .btn-view {
            background-color: #1753b4;
            color: white;

        }

        .btn-view:hover {
            background-color: #0d3577 /* Darker blue */
            color: white;
            transform: translateY(-3px); /* Slight lift effect on hover */

        }

        /* Delete button styles */
        .btn-delete {
            background-color: #dc3545; /* Red */
            color: white;
        }

        .btn-delete:hover {
            background-color: #b91b2b; /* Darker red */
            color: white;
            transform: translateY(-3px); /* Slight lift effect on hover */
        }

        /* Icon styles */
        .btn i {
            margin-right: 8px;
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
    <script>
        function selectFolder(folderId, baseRujukanNo) {
            document.getElementById('folder_id').value = folderId;
            document.getElementById('base_rujukan_no').value = baseRujukanNo;
            document.getElementById('base_rujukan_no_label').value = baseRujukanNo;
            new bootstrap.Modal(document.getElementById('uploadModal')).show();
        }
    </script>
</head>

<body>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="content">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 mt-3">
        <h2>Uploaded Memo</h2>
        <button class="btn-primary" data-bs-toggle="modal" data-bs-target="#selectFolderModal">
            <i class="fas fa-plus-circle"></i> Add Memo
        </button>
    </div>

    <!-- Folder List Modal -->
    <div class="modal fade" id="selectFolderModal" tabindex="-1" aria-labelledby="selectFolderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="selectFolderModalLabel">Select Folder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row folders">
                        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                            <div class="col-md-3 text-center folder-card" onclick="selectFolder(<?php echo $row['id']; ?>, '<?php echo $row['base_rujukan_no']; ?>')">
                                <img src="../assets/images/folder.png" class="img-fluid" alt="Folder Icon"" />

                                <div class="folder-icon"></div>
                                <div class="folder-title" style="font-weight: bold; font-size:20px;"><?php echo htmlspecialchars($row['title']); ?></div>
                                <div class="folder-title"><?php echo htmlspecialchars($row['base_rujukan_no']); ?></div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Memo Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalLabel">Upload Memo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($error)) {
                        echo "<div class='alert alert-danger'>$error</div>";
                    } ?>
                    <?php if (isset($success)) {
                        echo "<div class='alert alert-success'>$success</div>";
                    } ?>
                    <form id="uploadForm" action="upload_section.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="folder_id" id="folder_id" value="">
                        <input type="hidden" name="base_rujukan_no" id="base_rujukan_no" value="">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title:</label>
                            <input type="text" name="title" id="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="rujukan_no_int" class="form-label">Rujukan No:</label>
                            <div class="input-group">
                                <input type="text" id="base_rujukan_no_label" class="form-control" readonly>
                                <span class="input-group-text">(</span>
                                <input type="number" name="rujukan_no_int" id="rujukan_no_int" class="form-control" required>
                                <span class="input-group-text">)</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="uploaded_file" class="form-label">Upload PDF:</label>
                            <input type="file" name="uploaded_file" id="uploaded_file" class="form-control" accept="application/pdf" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Upload Memo</button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <div style="font-size: 24px; margin-bottom: 20px;">
        Total: <?php echo $total; ?>
    </div>

    <div class="documents-table">
        <table class="table">
            <thead>
            <tr>
                <th style="width: 5%">No</th>
                <th style="width: 25%">Reference No</th>
                <th style="width: 35%">Title</th>
                <th style="width: 10%">Date</th>
                <th style="width: 25%">Action</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!empty($noMemosMessage)): ?>
                <tr>
                    <td colspan="5">
                        <div class="text-center alert alert-info">
                            <?php echo $noMemosMessage; ?>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($old_memos as $index => $old_memo) : ?>
                    <tr>
                        <td><?php echo $index + 1 + ($page - 1) * $limit; ?></td>
                        <td><?php echo htmlspecialchars($old_memo['title']); ?></td>
                        <td><?php echo htmlspecialchars($old_memo['rujukan_no']); ?></td>
                        <td><?php echo htmlspecialchars($old_memo['date_created']); ?></td>
                        <td>
                            <a href="<?php echo $old_memo['pdf_file_path']; ?>" class="btn btn-view" target="_blank">
                                <i class="fas fa-eye"></i> View</a>

                            <?php if ($user_role == 1) : ?>
                                <a href="delete_memo.php?id=<?php echo $old_memo['id']; ?>&source=upload" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this memo?');">
                                    <i class="fas fa-trash-alt"></i> Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
        <nav>
            <ul class="pagination d-flex justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="draft_section.php?page=<?php echo $page - 1; ?>">Previous</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="draft_section.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="draft_section.php?page=<?php echo $page + 1; ?>">Next</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </main>
</div>
</div>
<!-- Footer -->
<footer class="footer">
    <p>&copy; 2024 Cawangan Pembangunan Sumber Manusia & HRIS FELCRA Berhad. All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>