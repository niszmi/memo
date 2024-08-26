<?php
session_start();
include '../includes/db_connect.php';


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$user_name = $_SESSION['name'];
$location_id = $_SESSION['lokasi'];

// Define INCLUDED constant to prevent direct access to sidebar.php
define('INCLUDED', true);

// Fetch counts for the different memo statuses based on the logged-in user's ID
//$query_draft_count = "SELECT COUNT(id) AS total FROM letters WHERE status = 'draft'";
//$query_completed_count = "SELECT COUNT(id) AS total FROM letters WHERE status = 'final'";
//$query_sent_count = "SELECT COUNT(id) AS total FROM letters WHERE status = 'complete'";
//$query_uploaded_count = "SELECT COUNT(id) AS total FROM old_memo";

$query_draft_count = "
    SELECT COUNT(letters.id) AS total
    FROM letters
    INNER JOIN users ON letters.user_id = users.id
    WHERE letters.status = 'draft' AND users.lokasi = ?";
$query_completed_count = "
    SELECT COUNT(letters.id) AS total 
    FROM letters 
    INNER JOIN users ON letters.user_id = users.id 
    WHERE letters.status = 'final' AND users.lokasi = ?";
$query_sent_count = "
    SELECT COUNT(letters.id) AS total 
    FROM letters 
    INNER JOIN users ON letters.user_id = users.id 
    WHERE letters.status = 'complete' AND users.lokasi = ?";
$query_uploaded_count = "
    SELECT COUNT(old_memo.id) AS total 
    FROM old_memo 
    INNER JOIN users ON old_memo.uploaded_by = users.id 
    WHERE users.lokasi = ?";

//$total_drafts = $conn->query($query_draft_count)->fetch_assoc()['total'];

//$total_completed = $conn->query($query_completed_count)->fetch_assoc()['total'];
//$total_sent = $conn->query($query_sent_count)->fetch_assoc()['total'];
//$total_uploaded = $conn->query($query_uploaded_count)->fetch_assoc()['total'];
//
// Fetch memo counts
$stmt = $conn->prepare($query_draft_count);
$stmt->bind_param('i', $location_id);
$stmt->execute();
$total_drafts = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare($query_completed_count);
$stmt->bind_param('i', $location_id);
$stmt->execute();
$total_completed = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare($query_sent_count);
$stmt->bind_param('i', $location_id);
$stmt->execute();
$total_sent = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare($query_uploaded_count);
$stmt->bind_param('i', $location_id);
$stmt->execute();
$total_uploaded = $stmt->get_result()->fetch_assoc()['total'];

// Query to fetch recent completed memos
//$recentLettersQuery = "SELECT rujukan_no, title, created_at FROM letters WHERE status = 'final' ORDER BY created_at DESC LIMIT 5";
//$recentLettersResult = mysqli_query($conn, $recentLettersQuery);

// Query to fetch recent completed memos based on location_id in users table
$recentLettersQuery = "
    SELECT letters.rujukan_no, letters.title, letters.created_at 
    FROM letters 
    INNER JOIN users ON letters.user_id = users.id 
    WHERE letters.status = 'final' AND users.lokasi = ? 
    ORDER BY letters.created_at DESC LIMIT 5";
$stmt = $conn->prepare($recentLettersQuery);
$stmt->bind_param('i', $location_id);
$stmt->execute();
$recentLettersResult = $stmt->get_result();

$recentLetters = [];
if (mysqli_num_rows($recentLettersResult) > 0) {
    while ($row = mysqli_fetch_assoc($recentLettersResult)) {
        $recentLetters[] = $row;
    }
} else {
    $noMemosMessage = "No recent completed memos found.";
}

// Query to fetch monthly memo counts
//$monthlyMemoQuery = "SELECT DATE_FORMAT(created_at, '%m-%Y') AS month, COUNT(id) AS count FROM letters GROUP BY DATE_FORMAT(created_at, '%m-%Y') ORDER BY month";
//$monthlyMemoResult = mysqli_query($conn, $monthlyMemoQuery);

// Query to fetch monthly memo counts based on location_id in users table
$monthlyMemoQuery = "
    SELECT DATE_FORMAT(letters.created_at, '%m-%Y') AS month, COUNT(letters.id) AS count 
    FROM letters 
    INNER JOIN users ON letters.user_id = users.id 
    WHERE users.lokasi = ? 
    GROUP BY DATE_FORMAT(letters.created_at, '%m-%Y') 
    ORDER BY month";
$stmt = $conn->prepare($monthlyMemoQuery);
$stmt->bind_param('i', $location_id);
$stmt->execute();
$monthlyMemoResult = $stmt->get_result();

$months = [];
$counts = [];

while ($row = mysqli_fetch_assoc($monthlyMemoResult)) {
    $months[] = $row['month'];
    $counts[] = $row['count'];
}

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = [
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Memo Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/styles.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>
<body>

<style>
    body, html {
        height: 100%;
        font-family: 'Inter', sans-serif;
        background: #d8dcee;
    }

    .sidebar {
        background: #ffffff;
        height: 100vh; /* Full height */
        width: 16%; /* Set the width of the sidebar */
        position: fixed; /* Fixed Sidebar (stay in place on scroll) */
        padding-top: 20px;
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

    .card {
        border-radius: 25px; /* Rounded corners */
        /*background-color: #71357c; !* Teal background color *!*/
        color: #ffffff; /* Text color */
        padding: 10px; /* Padding inside the card */
        text-align: left; /* Center-align the text and icon */
        box-shadow: 0 4px 8px rgba(0.1, 0.1, 0.1, 0.1); /* Subtle shadow for depth */
        margin-bottom: 20px; /* Margin to separate from other content */
        transition: transform 0.3s; /* Smooth transform on hover */
        border: #FFFFFF;

    }

    .card-chart{
        border-radius: 30px; /* Rounded corners */
        background-color: #ffffff; /* Teal background color */
        color: #000000; /* Text color */
        padding: 10px; /* Padding inside the card */
        text-align: left; /* Center-align the text and icon */
        box-shadow: 0 4px 8px rgba(0.1, 0.1, 0.1, 0.1); /* Subtle shadow for depth */
        margin-bottom: 20px; /* Margin to separate from other content */
        margin-top: 20px;
        transition: transform 0.3s, background-color 0.3s, color 0.3s; /* Smooth transform on hover */
        height: 90%;
    }

    .card-chart:hover {
        transform: translateY(-5px); /* Slight lift effect on hover */

    }

    .card:hover {
        transform: translateY(-5px); /* Slight lift effect on hover */
        color: #ffffff;
        background-color: rgba(248, 232, 217, 0.87); /* Teal background color */

    }

    .icon {
        font-size: 30px; /* Larger icon size */
        margin-bottom: 10px; /* Space between icon and text */
    }

    .card-title {
        font-size: 33px; /* Title font size */
        margin-bottom: 2px; /* Small margin for spacing */
        font-weight: bold;
        margin-left: 10px;
    }

    .card-text {
        font-size: 20px; /* Text font size */
        margin-left: 10px;
        font-weight: bold;
    }

    .icon {
        font-size: 30px; /* Larger icon size */
        margin-left: 10px; /* Space between text and icon */
    }


    .icon-circle {
        font-size: 24px;
        color: #495057;
        width: 40px;
        height: 40px;
        background-color: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        margin-bottom: 10px;
    }

    .icon-circle i {
        color: #495057;
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
        background-color: #206f80; /* Teal background for headers */
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

    .row {
        margin-right: 0;
        margin-left: 0;
        margin-top: 0;
    }
    .card-body {
        padding: 15px; /* Reduces padding inside cards if needed */
    }

    .search-bar {
        margin-bottom: 20px;
        display: flex;
        justify-content: flex-end;
        border-radius: 20px;
    }

    .search-bar input {
        width: 250px;
        border-radius: 20px;
    }

    .calendar {
        border-radius: 10px;
        padding: 15px; /* Reduces padding inside cards if needed */
        min-height: 200px; /* Adjust height based on content */
    }

    .calendar h4 {
        text-align: center;
        margin-bottom: 20px;
    }

    .calendar .day {
        font-size: 18px;
        padding: 5px 10px;
        text-align: center;
        border-radius: 5px;
        margin-bottom: 5px;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .calendar .day:hover {
        background-color: #e7effc;
    }

    .calender-text{
        color: black;
    }

    .image-container {
        display: flex;
        justify-content: center;
        align-items: center;
        flex-shrink: 0;
        width: 80px; /* Adjust width as needed */
        height: 80px; /* Adjust height as needed */
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
<?php include '../includes/header.php'; ?>
<!--    <div class="d-flex">-->
<?php include '../includes/sidebar.php'; ?>

<div class="content">
    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="mb-4" style="margin-left: 10px">Dashboard</h2>
            <!--                    <form class="d-flex">-->
            <!--                        <input class="form-control form-control-sm me-2 rounded-pill" type="search" placeholder="Search" aria-label="Search" style="height: 30px;">-->
            <!--                        <button class="btn btn-outline-primary btn-md rounded-pill" type="submit">Search</button>-->
            <!--                    </form>-->

<!--            <div class="search-bar">-->
<!--                <form class="d-flex" action="search_memo.php" method="get" target="_blank">-->
<!--                    <input class="form-control me-2" type="search" placeholder="Search memo by title/rujukan_no" aria-label="Search" name="query">-->
<!--                    <button class="btn btn-outline-success" type="submit">Search</button>-->
<!--                </form>-->
<!--            </div>-->
        </div>

        <div class="row">
            <!-- Card for Drafts -->
            <div class="col-md-3">
                <a href="draft_section.php" class="text-decoration-none">
                    <div class="card document-card" style="background: linear-gradient(to top, #a7d0ee, #2b56c9);">
                        <div class="card-body d-flex">
                            <div style="flex: 1;">
                                <i class="fas fa-file-alt icon"></i>
                                <h5 class="card-title"><?= $total_drafts; ?></h5>
                                <p class="card-text">DRAFT</p>
                            </div>
                            <div class="image-container" ">
                                <img src="../assets/images/draft.png" class="img-fluid" alt="Image" style="max-height: 80px; max-width: 80px;">
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Card for Completed -->
            <div class="col-md-3">
                <a href="completed_section.php" class="text-decoration-none">
                    <div class="card document-card" style="background: linear-gradient(to top, #cab6fa, #895dd7);">
                        <div class="card-body d-flex">
                            <div style="flex: 1;">
                                <i class="fas fa-check-circle icon"></i>
                                <h5 class="card-title"><?= $total_completed; ?></h5>
                                <p class="card-text">COMPLETED</p>
                            </div>
                            <div class="image-container" ;">
                                <img src="../assets/images/completed.png" class="img-fluid" alt="Image" style="max-height: 110px; max-width: 110px;">
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Card for Sent -->
            <div class="col-md-3">
                <a href="sent_section.php" class="text-decoration-none">
                    <div class="card document-card" style="background: linear-gradient(to top, #eed5a3, #e76801);">
                        <div class="card-body d-flex">
                            <div style="flex: 1;">
                                <i class="fas fa-paper-plane icon"></i>
                                <h5 class="card-title"><?= $total_sent; ?></h5>
                                <p class="card-text">SENT</p>
                            </div>
                            <div class="image-container" ">
                                <img src="../assets/images/sent.png" class="img-fluid" alt="Image" style="max-height: 100px; max-width: 100px;">
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Card for Uploaded -->
            <div class="col-md-3">
                <a href="upload_section.php" class="text-decoration-none">
                    <div class="card document-card" style="background: linear-gradient(to top, #cfe8b2, #669632);">
                        <div class="card-body d-flex">
                            <div style="flex: 1;">
                                <i class="fas fa-upload icon"></i>
                                <h5 class="card-title"><?= $total_uploaded; ?></h5>
                                <p class="card-text">UPLOADED</p>
                            </div>
                            <div class="image-container" ">
                                <img src="../assets/images/upload.png" class="img-fluid" alt="Image" style="max-height: 80px; max-width: 80px;">
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <div class="row">

            <!-- Bar Chart Section -->
            <div class="col-md-8">
                <div class="card-chart">
                    <div class="card-body">
                        <h3 class="chart-title">Created Memo by Month</h3>
                        <canvas id="barChart" ></canvas>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card-chart">
                    <div class="card-body">
                        <h3 class="chart-title">Memo by Section</h3>
                        <canvas id="donutChart" ></canvas>
                        <div id="storageDetails" class="mt-3">
                            <ul class="list-unstyled" style="text-align: center; font-size: 20px">
                                <li>Total: <strong><?= $total_drafts + $total_completed + $total_sent + $total_uploaded; ?></strong></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">

            <!-- Row for Table -->
            <div class="col-8">
                <h4>Recent Completed Memos</h4>

                <div class="documents-table">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>References</th>
                            <th>Title</th>
                            <th>Last Updated</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($recentLetters)): ?>
                            <?php foreach ($recentLetters as $memo): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($memo['rujukan_no']); ?></td>
                                    <td><?php echo htmlspecialchars($memo['title']); ?></td>
                                    <td><?php echo htmlspecialchars(time_elapsed_string($memo['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3"><?php echo $noMemosMessage; ?></td>
                            </tr>
                        <?php endif; ?>

                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Calendar Section -->
            <div class="col-md-4">
                <div class="card">
                    <div class="calendar">
                        <h4 class="calender-text mb-3">Calendar</h4>
                        <div id="calendar" class="text-center" style="color: black ">
                            <!-- Calendar will be generated here -->
                        </div>
                    </div>
                </div>
            </div>
    </div>
</div>

</div>

<!-- Footer -->
<footer class="footer">
    <p>&copy; 2024 Cawangan Pembangunan Sumber Manusia & HRIS FELCRA Berhad. All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</body>
</html>

<script>
    // Data for the donut chart
    var data = {
        labels: ['Drafts', 'Completed', 'Sent', 'Uploaded'],
        datasets: [{
            label: 'Count',
            data: [<?php echo $total_drafts; ?>, <?php echo $total_completed; ?>, <?php echo $total_sent; ?>, <?php echo $total_uploaded; ?>],
            backgroundColor: [
                '#77a1df',
                '#aa8be8',
                '#eaa760',
                '#a2c37c'],
            borderColor: [
                '#ffffff', // Set border color to match background color to create spacing effect
                '#ffffff',
                '#ffffff',
                '#ffffff'
            ],
            borderWidth: 6,
            hoverOffset: 4
        }]
    };


    // Options for the DONUT chart
    var options = {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            },
            tooltip: {
                callbacks: {
                    label: function(tooltipItem) {
                        return tooltipItem.label + ': ' + tooltipItem.raw.toFixed(0);
                    }
                }
            },
            pie: {
                spacing: 10,
            },
            // Plugin to center text in doughnut chart
            doughnutCenterText: {
                display: true,
                text: '<?= $total_drafts + $total_completed + $total_sent + $total_uploaded; ?>', // Display total memos
                color: '#000000', // Text color
                font: {
                    size: '20' // Font size
                }
            }
        },
    };

    // Get the context of the canvas element we want to select
    var ctx = document.getElementById('donutChart').getContext('2d');

    // Create the donut chart
    var myDonutChart = new Chart(ctx, {
        type: 'pie',
        data: data,
        options: options
    });


    // Data for the BAR chart
    var dataBar = {
        labels: <?php echo json_encode($months); ?>,
        datasets: [{
            label: 'Created Memo',
            data: <?php echo json_encode($counts); ?>,
            backgroundColor: '#289dad', // Set bar color to match the image
            // borderColor: '#82acf3', // Set border color to match the image
            // borderWidth: 1, // Set border width
            barPercentage: 0.2, // Adjust the width of the bars
            categoryPercentage: 0.8, // Adjust the spacing between the bars
        }]
    };

    // Options for the bar chart
    var optionsBar = {
        scales: {
            x: {
                grid: {
                    display: false, // Hide the grid lines on the x-axis
                },
                ticks: {
                    font: {
                        size: 14, // Adjust the font size for the x-axis labels
                    },
                },
            },
            y: {
                beginAtZero: true,
                grid: {
                    borderDash: [5, 5], // Set the style of the grid lines on the y-axis
                },
                ticks: {
                    font: {
                        size: 14, // Adjust the font size for the y-axis labels
                    },
                },
            },
        },
        plugins: {
            legend: {
                display: false, // Hide the legend
            },
            tooltip: {
                backgroundColor: 'rgba(0,0,0,0.7)', // Set the background color of the tooltip
                titleFont: {
                    size: 16, // Adjust the font size of the tooltip title
                },
                bodyFont: {
                    size: 14, // Adjust the font size of the tooltip body
                },
                cornerRadius: 8, // Adjust the border radius of the tooltip
            },
        },
    };



    // Get the context of the canvas element we want to select
    var ctxBar = document.getElementById('barChart').getContext('2d');

    // Create the bar chart
    var myBarChart = new Chart(ctxBar, {
        type: 'bar',
        data: dataBar,
        options: optionsBar
    });


    //calendar
        function generateCalendar() {
        let calendar = document.getElementById('calendar');
        let currentDate = new Date();
        let year = currentDate.getFullYear();
        let month = currentDate.getMonth();

        let daysInMonth = new Date(year, month + 1, 0).getDate();
        let firstDay = new Date(year, month, 1).getDay();

        let monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July',
        'August', 'September', 'October', 'November', 'December'];

        // Generate calendar HTML
        let calendarHTML = `<h5>${monthNames[month]} ${year}</h5>
                           <table class="table table-bordered">
                               <thead>
                                   <tr>
                                       <th>Sun</th>
                                       <th>Mon</th>
                                       <th>Tue</th>
                                       <th>Wed</th>
                                       <th>Thu</th>
                                       <th>Fri</th>
                                       <th>Sat</th>
                                   </tr>
                               </thead>
                               <tbody>`;

        let dayCount = 1;
        // Loop through weeks (rows)
        for (let i = 0; i < 6; i++) {
        calendarHTML += '<tr>';

        // Loop through days (columns)
        for (let j = 0; j < 7; j++) {
        if (i === 0 && j < firstDay) {
        calendarHTML += '<td></td>';
    } else if (dayCount > daysInMonth) {
        break;
    } else {
        let todayClass = '';
        if (dayCount === currentDate.getDate()) {
        todayClass = 'bg-primary text-white';
    }
        calendarHTML += `<td class="${todayClass}">${dayCount}</td>`;
        dayCount++;
    }
    }

        calendarHTML += '</tr>';
    }

        calendarHTML += `</tbody>
                        </table>`;

        // Append calendar HTML to the calendar div
        calendar.innerHTML = calendarHTML;
    }

        // Call the function to generate calendar when the page loads
        generateCalendar();

</script>
