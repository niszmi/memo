<?php
// Ensure this file is not accessed directly
if (!defined('INCLUDED')) {
    die('Direct access not permitted');
}

// Include database connection
include_once 'db_connect.php';

// Determine which page is active
$current_page = basename($_SERVER['PHP_SELF']);
//$profilePic = isset($_SESSION['profile_pic']) ? '../' . $_SESSION['profile_pic'] : '../assets/images/profile.png';

// Function to get location name
function getLocationName($location_id, $conn) {
    $location_name = ''; // Initialize the variable
    $sql = "SELECT name FROM locations WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $location_id);
        $stmt->execute();
        $stmt->bind_result($location_name);
        $stmt->fetch();
        $stmt->close();
    }
    return $location_name;
}

// Get user's location name
$locationName = '';
$userName = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';
if (isset($_SESSION['lokasi'])) {
    $location_id = $_SESSION['lokasi'];
    error_log("Location ID: " . $location_id); // Debug statement
    $locationName = getLocationName($location_id, $conn);
    error_log("Location Name: " . $locationName); // Debug statement
}
?>
<style>
    .user-panel .user-name {
        font-size: 12px; /* Adjust the font size to be smaller */
        color: #6c757d; /* Optional: Change the color if needed */
        margin-top: 10px;
    }
</style>

<div class="sidebar">
    <div class="sidebar-content">
        <div class="user-panel mb-3">
            <div class="info text-center" style="padding: 5px">
                <img src="../assets/images/logo%20memo.png" alt="Profile Picture" class="img-fluid rounded-circle mb-2" style="width: 70px; height: 70px; margin-top: -20px; box-shadow: 0px 4px 4px rgba(0, 0, 0, 0.2);">
                <div style="font-size: 16px; margin-top: 10px"><?php echo htmlspecialchars($locationName ?? 'Location'); ?></div>
                <div class="user-name"><?php echo htmlspecialchars($userName); ?></div>
            </div>
        </div>

        <div class="d-flex flex-column p-2">
            <a href="dashboard.php" class="btn mb-2 <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> <span>Dashboard</span>
            </a>
            <a href="create_memo.php" class="btn mb-2 <?php echo ($current_page == 'create_memo.php' || $current_page == 'create_memo_step2.php') ? 'active' : ''; ?>">
                <i class="fas fa-plus-circle"></i> <span>Create New Memo</span>
            </a>
            <a href="draft_section.php" class="btn mb-2 <?php echo ($current_page == 'draft_section.php' || $current_page == 'edit_memo.php') ? 'active' : ''; ?>">
                <i class="fas fa-file-alt"></i> <span>Draft Section</span>
            </a>
            <a href="completed_section.php" class="btn mb-2 <?php echo $current_page == 'completed_section.php' ? 'active' : ''; ?>">
                <i class="fas fa-save"></i> <span>Completed Section</span>
            </a>
            <a href="upload_section.php" class="btn mb-2 <?php echo $current_page == 'upload_section.php' ? 'active' : ''; ?>">
                <i class="fas fa-upload"></i> <span>Upload Section</span>
            </a>
            <a href="sent_section.php" class="btn mb-2 <?php echo $current_page == 'sent_section.php' ? 'active' : ''; ?>">
                <i class="fas fa-paper-plane"></i> <span>Sent Section</span>
            </a>
        </div>
        <div class="d-flex flex-column p-2" >
        <a href="logout.php" class="btn logout-btn">
                <i class="fas fa-sign-out-alt"></i> <span>Sign Out</span>
            </a>
        </div>
        </div>
    </div>
</div>

<!--<form id="profilePicForm" action="upload_profile_pic.php" method="post" enctype="multipart/form-data" style="display:none;">-->
<!--    <input type="file" name="profile_pic" id="profilePicInput" onchange="document.getElementById('profilePicForm').submit();">-->
<!--</form>-->

<!--<script>-->
<!--    document.addEventListener('DOMContentLoaded', (event) => {-->
<!--        const sidebar = document.querySelector('.sidebar');-->
<!--        const toggleButton = document.createElement('button');-->
<!--        toggleButton.innerHTML = '&#9776;'; // Hamburger menu icon-->
<!--        toggleButton.style.position = 'fixed';-->
<!--        toggleButton.style.top = '30px';-->
<!--        toggleButton.style.left = '30px';-->
<!--        toggleButton.style.zIndex = '1001'; // Ensure the button is above other elements-->
<!--        toggleButton.style.backgroundColor = '#ffffff';-->
<!--        toggleButton.style.border = 'none';-->
<!--        toggleButton.style.cursor = 'pointer';-->
<!--        toggleButton.style.padding = '10px';-->
<!--        toggleButton.style.borderRadius = '5px';-->
<!--        toggleButton.style.boxShadow = '0 0 10px rgba(0, 0, 0, 0.1)';-->
<!---->
<!--        toggleButton.addEventListener('click', () => {-->
<!--            sidebar.classList.toggle('minimized');-->
<!--        });-->
<!---->
<!--        document.body.appendChild(toggleButton);-->
<!--    });-->
<!--</script>-->

<?php
// Close the database connection if it was opened
$conn->close();
?>
