<?php
session_start();
include 'config.php';

// Protect page
if(!isset($_SESSION['user_id']) || $_SESSION['accgroup'] != '1'){
    header("Location: login.php");
    exit;
}

$fromDate = '';
$toDate   = '';
$attendanceLogs = [];

if(isset($_GET['search'])){
    // ✅ Sanitize date inputs — only accept valid date format
    $fromDate = isset($_GET['from_date']) ? preg_replace('/[^0-9\-]/', '', $_GET['from_date']) : '';
    $toDate   = isset($_GET['to_date'])   ? preg_replace('/[^0-9\-]/', '', $_GET['to_date'])   : '';
}

// ✅ Prepared statements for both date-filtered and unfiltered queries
if(!empty($fromDate) && !empty($toDate)){
    $stmt = $conn->prepare("
        SELECT user_id, student_name, date_log, time_log, typeoflog
        FROM final_attendance
        WHERE date_log BETWEEN ? AND ?
        ORDER BY date_log DESC, time_log DESC
    ");
    $stmt->bind_param("ss", $fromDate, $toDate);
} else {
    $stmt = $conn->prepare("
        SELECT user_id, student_name, date_log, time_log, typeoflog
        FROM final_attendance
        ORDER BY date_log DESC, time_log DESC
    ");
}

$stmt->execute();
$result = $stmt->get_result();
if($result){
    while($row = $result->fetch_assoc()) $attendanceLogs[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
<title>Attendance Logs</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{display:flex;background:#f4f6fb;}
.sidebar{width:240px;background:#1e1e2f;height:100vh;color:white;padding:20px;position:fixed;}
.sidebar h2{margin-bottom:30px;text-align:center;}
.sidebar a{display:block;padding:12px;color:#ccc;text-decoration:none;border-radius:8px;margin-bottom:10px;transition:.3s;}
.sidebar a:hover{background:#6c5ce7;color:white;}
.main{margin-left:240px;width:100%;padding:20px;}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;}
.topbar h1{color:#333;}
.logout{background:#ff4757;padding:8px 15px;border-radius:6px;color:white;text-decoration:none;}
.table-container{background:white;padding:20px;border-radius:15px;box-shadow:0 5px 20px rgba(0,0,0,.05);}
table{width:100%;border-collapse:collapse;margin-top:15px;}
th{text-align:left;padding:12px;background:#6c5ce7;color:white;}
td{padding:12px;border-bottom:1px solid #eee;vertical-align:middle;}
tr:hover{background:#f9f9ff;}
form{display:flex;gap:10px;margin-bottom:15px;flex-wrap:wrap;}
form input{padding:8px;border-radius:6px;border:1px solid #ccc;}
form button{padding:8px 15px;background:#6c5ce7;color:white;border:none;border-radius:6px;cursor:pointer;transition:.3s;}
form button:hover{background:#5a4bcf;}
.status-login{color:green;font-weight:600;}
.status-logout{color:red;font-weight:600;}
@media(max-width:768px){.sidebar{display:none;}.main{margin-left:0;}}
</style>
</head>
<body>

<div class="sidebar">
    <div style="text-align:center;margin-bottom:20px;">
        <img src="images/1.png" alt="Company Logo" style="width:120px;height:auto;">
    </div>
    <h2>Admin Panel</h2>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="add_intern.php">Intern</a>
    <a href="add_user.php">Manage Users</a>
    <a href="attendance_log.php">Attendance Logs</a>
    <a href="forgot_password.php">Reset Password</a>
</div>

<div class="main">
    <div class="topbar">
        <h1>Attendance Logs</h1>
        <a class="logout" href="logout.php">Logout</a>
    </div>

    <form method="get" action="print_dtr.php" target="_blank" style="margin-bottom:15px;display:inline-block;">
        <input type="hidden" name="from_date" value="<?= htmlspecialchars($fromDate) ?>">
        <input type="hidden" name="to_date"   value="<?= htmlspecialchars($toDate) ?>">
        <button type="submit">Print DTR</button>
    </form>

    <form method="post" action="export_attendance.php" style="margin-bottom:15px;">
        <input type="hidden" name="from_date" value="<?= htmlspecialchars($fromDate) ?>">
        <input type="hidden" name="to_date"   value="<?= htmlspecialchars($toDate) ?>">
        <button type="submit" name="export">Download Excel</button>
    </form>

    <form method="get" action="">
        <label>From:</label>
        <input type="date" name="from_date" value="<?= htmlspecialchars($fromDate) ?>">
        <label>To:</label>
        <input type="date" name="to_date" value="<?= htmlspecialchars($toDate) ?>">
        <button type="submit" name="search">Search</button>
    </form>

    <div class="table-container">
        <table>
            <tr>
                <th>User ID</th>
                <th>Student Name</th>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
            </tr>
            <?php if(count($attendanceLogs) > 0): ?>
                <?php foreach($attendanceLogs as $log): ?>
                <tr>
                    <td><?= htmlspecialchars($log['user_id']) ?></td>
                    <td><?= htmlspecialchars($log['student_name']) ?></td>
                    <td><?= htmlspecialchars($log['date_log']) ?></td>
                    <td><?= htmlspecialchars($log['time_log']) ?></td>
                    <td class="<?= strtolower($log['typeoflog']) == 'login' ? 'status-login' : 'status-logout' ?>">
                        <?= htmlspecialchars($log['typeoflog']) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align:center;">No records found</td></tr>
            <?php endif; ?>
        </table>
    </div>

    <div style="text-align:center;padding:15px;color:#777;font-size:14px;margin-top:30px;">
        This site is maintained and developed by GG EZ Computer Sales and Services
    </div>
</div>
</body>
</html>
