<?php
session_start();
include '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Define INCLUDED constant to allow access to header and sidebar
define('INCLUDED', true);

// Fetch folders from the database
$query = "SELECT id, title, base_rujukan_no FROM folders";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Memo</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        .folders {
            display: flex;
            flex-wrap: wrap;
            gap: 20px; /* Space between each folder */

        }
        .folder {
            width: 100px;
            margin: 10px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.2s;
            flex: 1 1 200px; /* Flex properties to control folder size */
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
        .folder-rujukan {
            font-size: 18px;
        }
        .content{
            margin-left: 15%; /* Same as the width of the sidebar */
            padding: 20px;
            min-height: 100vh;
            background-color: #d8dcee; /* Set background color to white */
            border-radius: 20px; /* Add rounded corners */
            margin-top: 50px;
        }
        .btn-primary {
            background-color: #289ead;
            border-color: #289ead;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0.1, 0.1, 0.1, 0.1); /* Add shadow here */
            /*margin: 5px;*/
            padding: 10px;
            margin-right: 50px;

        }
        .btn-primary:hover {
            background-color: #0a5460;
            border-color: #0a5460;
        }
        .sidebar {
            background: #ffffff;
            height: 100vh; /* Full height */
            width: 16%; /* Set the width of the sidebar */
            position: fixed; /* Fixed Sidebar (stay in place on scroll) */
            padding-top: 40px;
            padding-left: 10px;
            padding-right: 10px;
        }

        .content {
            margin-left: 16%; /* Same as the width of the sidebar */
            padding: 20px;
            min-height: 100vh;
            background-color: #d8dcee; /* Set background color to white */
            border-radius: 20px; /* Add rounded corners */
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
        function selectFolder(folderId) {
            document.getElementById('folder_id').value = folderId;
            document.getElementById('folderForm').submit();
        }

    </script>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <?php include '../includes/sidebar.php'; ?>
    <div class="content mt-3">
<!--        <div class="container-fuild">-->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 mt-5 border-bottom">
                    <h1 class="h2">Create New Memo</h1>
                    <button class="btn-primary" data-bs-toggle="modal" data-bs-target="#addFolderModal">+ Add Folder</button>
                </div>
                
                <!-- Folder List -->
                <div class="folders">
                    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                        <div class="folder" onclick="selectFolder(<?php echo $row['id']; ?>)">
                            <img src="../assets/images/folder.png" class="img-fluid" alt="Folder Icon"" />

                            <div class="folder-icon"></div>
                            <div class="folder-title" style="font-weight: bold; font-size:20px;"><?php echo htmlspecialchars($row['title']); ?></div>
                            <div class="folder-rujukan"><?php echo htmlspecialchars($row['base_rujukan_no']); ?></div>
                        </div>
                    <?php } ?>
                </div>
                
                <!-- Hidden Form to Submit Folder Selection -->
                <form id="folderForm" action="create_memo_step2.php" method="post" style="display: none;">
                    <input type="hidden" name="folder_id" id="folder_id" value="">
                </form>
            </main>
        </div>
    </div>

    <!-- Add Folder Modal -->
    <div class="modal fade" id="addFolderModal" tabindex="-1" aria-labelledby="addFolderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addFolderModalLabel">Add Folder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="add_folder.php" method="post">
                        <div class="form-group mb-3">
                            <label for="folderTitle" class="form-label">Folder Title:</label>
                            <input type="text" name="title" id="folderTitle" class="form-control" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="baseRujukanNo" class="form-label">Base Rujukan No:</label>
                            <input type="text" name="base_rujukan_no" id="baseRujukanNo" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Folder</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer -->
    <footer class="footer">
        <p>&copy; 2024 Cawangan Pembangunan Sumber Manusia & HRIS FELCRA Berhad. All rights reserved.</p>
    </footer>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
