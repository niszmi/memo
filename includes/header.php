<?php
// Ensure this file is not accessed directly
if (!defined('INCLUDED')) {
    die('Direct access not permitted');
}

$userName = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';

?>

<header class="app-header header" style="font-family: 'Arial', sans-serif;">
    <div class="container-fluid">
        <h1 class="text-center py-3" style="font-size: medium; font-weight: bold;">MEMO MANAGEMENT SYSTEM</h1>
    </div>
</header>

<!--<header class="app-header header">-->
<!--        <div class="user-info">-->
<!--            <img src="../assets/images/user%20icon.png" alt="User Icon" class="user-icon">-->
<!--            <span class="user-name">--><?php //echo htmlspecialchars($userName); ?><!--</span>-->
<!--        </div>-->
<!--    </div>-->
<!--</header>-->