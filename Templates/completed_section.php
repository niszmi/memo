<?php
session_start();
include '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

define('INCLUDED', true);

$user_id = $_SESSION['user_id'];
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 0; // Default to 0 if role is not set

// Pagination settings
$limit = 10; // Number of entries to show in a page.
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

//// Fetch total number of records
//$query = "SELECT COUNT(id) AS id FROM letters WHERE status = 'final' AND user_id = ?";
//$result = $conn->query($query);
//$total = $result->fetch_assoc()['id'];
//$pages = ceil($total / $limit);
//
//// Fetch completed letters from the database
//$query = "SELECT id, rujukan_no, title, tarikh as date FROM letters WHERE status = 'final' AND user_id = ? LIMIT ?, ?";
//$stmt = $conn->prepare($query);
//
//if ($stmt === false) {
//    die('Prepare failed: ' . htmlspecialchars($conn->error));
//}
//
//$stmt->bind_param("ii", $start, $limit);
//$stmt->execute();
//$result = $stmt->get_result();
//$completed = $result->fetch_all(MYSQLI_ASSOC);
//$stmt->close();
//
//// Set no memos message if there are no drafts
//$noMemosMessage = empty($complete) ? "No complete memo found." : "";


// Fetch the location ID of the logged-in user
$queryLocation = "SELECT lokasi FROM users WHERE id = ?";
$stmtLocation = $conn->prepare($queryLocation);
$stmtLocation->bind_param("i", $user_id);
$stmtLocation->execute();
$resultLocation = $stmtLocation->get_result();
$location_id = $resultLocation->fetch_assoc()['lokasi'];
$stmtLocation->close();

//// Fetch total number of records
//$query = "SELECT COUNT(id) AS total FROM letters WHERE status = 'final' AND user_id = ?";
//$stmt = $conn->prepare($query);
//$stmt->bind_param("i", $user_id);
//$stmt->execute();
//$result = $stmt->get_result();
//$total = $result->fetch_assoc()['total'];
//$pages = ceil($total / $limit);
//
//// Fetch completed letters from the database
//$query = "SELECT id, rujukan_no, title, tarikh as date FROM letters WHERE status = 'final' AND user_id = ? LIMIT ?, ?";
//$stmt = $conn->prepare($query);
//$stmt->bind_param("iii", $user_id, $start, $limit);
//$stmt->execute();
//$result = $stmt->get_result();
//$completed = $result->fetch_all(MYSQLI_ASSOC);
//$stmt->close();

// Fetch total number of records for the given location
$queryTotal = "
    SELECT COUNT(letters.id) AS total 
    FROM letters 
    INNER JOIN users ON letters.user_id = users.id 
    WHERE letters.status = 'final' AND users.lokasi = ?";
$stmtTotal = $conn->prepare($queryTotal);
$stmtTotal->bind_param("i", $location_id);
$stmtTotal->execute();
$resultTotal = $stmtTotal->get_result();
$total = $resultTotal->fetch_assoc()['total'];
$stmtTotal->close();

// Calculate total number of pages
$pages = ceil($total / $limit);

// Fetch completed letters from the database for the given location
$queryCompleted = "
    SELECT letters.id, letters.rujukan_no, letters.title, letters.tarikh AS date 
    FROM letters 
    INNER JOIN users ON letters.user_id = users.id 
    WHERE letters.status = 'final' AND users.lokasi = ? 
    LIMIT ?, ?";
$stmtCompleted = $conn->prepare($queryCompleted);
$stmtCompleted->bind_param("iii", $location_id, $start, $limit);
$stmtCompleted->execute();
$resultCompleted = $stmtCompleted->get_result();
$completed = $resultCompleted->fetch_all(MYSQLI_ASSOC);
$stmtCompleted->close();

// Set no memos message if there are no completed memos
$noMemosMessage = empty($completed) ? "No completed memos found." : "";

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>All Completed Memos</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            background: #ffffff;
            height: 100vh; /* Full height */
            width: 16%; /* Set the width of the sidebar */
            position: fixed; /* Fixed Sidebar (stay in place on scroll) */
            padding-top: 20px;
        }

        .content {
            margin-left: 16%; /* Same as the width of the sidebar */
            padding: 20px;
            min-height: 100vh;
            background-color: #d8dcee; /* Set background color to white */
            border-radius: 20px; /* Add rounded corners */
        }


        modal-header {
            background-color: #289ead;
            color: white;
        }
        .modal-header .close {
            color: white;
        }
        .modal-content {
            border-radius: 10px;
        }
        .form-control {
            border-radius: 5px;
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
            /*padding: 10px 20px;*/
            font-size: 16px;
            /*font-weight: bold;*/
            text-align: center;
            text-decoration: none;
            border-radius: 8px;
            transition: background-color 0.3s, color 0.3s;

        }

        /* View button styles */
        .btn-view {
            background-color: #1753b4; /* Light blue */
            color: white;

        }

        .btn-view:hover {
            background-color: #0d3577; /* Darker blue */
            color: white;
            transform: translateY(-3px); /* Slight lift effect on hover */

        }

        /* Edit button styles */
        .btn-edit {
            background-color: #ffc107; /* Yellow */
            color: black;
        }

        .btn-edit:hover {
            background-color: #e0a800; /* Darker yellow */
            color: black;
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
</head>

<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>
            </div>
        <div class="content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 mt-3">
            <h2>All Completed Memos</h2>
            </div>
                <div style="font-size: 24px; margin-bottom: 20px">
                    Total: <?php echo $total; ?>
                </div>

            <div class="documents-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 25%">Reference No</th>
                            <th style="width: 35%">Title</th>
                            <th style="width: 15%">Date</th>
                            <th style="width: 25%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($noMemosMessage)): ?>
                        <tr>
                            <td colspan="4">
                                <div class="text-center alert alert-info">
                                    <?php echo $noMemosMessage; ?>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($completed as $complete) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($complete['rujukan_no']); ?></td>
                                <td><?php echo htmlspecialchars($complete['title']); ?></td>
                                <td><?php echo htmlspecialchars($complete['date']); ?></td>
                                <td>
                                    <a href="view_memo_pdf.php?id=<?php echo $complete['id']; ?>" class="btn btn-view" target="_blank">
                                        <i class="fas fa-eye"></i> View</a>
                                    <a href="edit_memo.php?id=<?php echo $complete['id']; ?>" class="btn btn-edit">
                                        <i class="fas fa-edit"></i> Edit</a>
                                    <?php if ($user_role == 1) : ?>
                                        <a href="delete_memo.php?id=<?php echo $complete['id']; ?>&source=completed" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this memo?');">
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