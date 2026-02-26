<?php
session_start();
include 'config.php';

// Protect page
if(!isset($_SESSION['user_id']) || $_SESSION['accgroup'] != '1'){
    header("Location: login.php");
    exit;
}

// All queries here use no user input, so direct queries are safe
$totalUsers = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];

$today = date('Y-m-d');

$loginToday = $conn->query("
    SELECT COUNT(*) AS total
    FROM attendance
    WHERE typeoflog='LOGIN' AND DATE(log_time)='$today'
")->fetch_assoc()['total'];

$logoutToday = $conn->query("
    SELECT COUNT(*) AS total
    FROM attendance
    WHERE typeoflog='LOGOUT' AND DATE(log_time)='$today'
")->fetch_assoc()['total'];

// Recent attendance
$recent = $conn->query("
    SELECT u.student_name, a.typeoflog, a.log_time
    FROM attendance a
    INNER JOIN users u ON a.user_id = u.id
    ORDER BY a.log_time DESC
    LIMIT 10
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Administrator Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body { 
    font-family: Arial; 
    margin: 0; 
    background: #f2f4f8; 
}
.header { 
    background: #6c5ce7; 
    color: white; 
    padding: 15px; 
}
.container { 
    padding: 20px; 
}
.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}
.card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 3px 10px rgba(0,0,0,.1);
    text-align: center;
}
.card h2 { 
    margin: 0; 
    color: #6c5ce7; 
}
table { 
    width: 100%; 
    border-collapse: collapse; 
    margin-top: 20px; 
    background: white; 
}
th, td { 
    padding: 10px; 
    border-bottom: 
    1px solid #ddd; 
}
th { 
    background: #6c5ce7; 
    color: white; 
}
.logout { 
    float: right; 
    color: white; 
    text-decoration: none; 
}
.reset-link { 
    display:inline-block; 
    margin-top:15px; 
    color:#6c5ce7; 
    font-weight:bold; 
}
</style>
</head>
<body>

<div class="header">
    Administrator Dashboard
    <a class="logout" href="logout.php">Logout</a>
</div>

<div class="container">

    <div class="cards">
        <div class="card">
            <h2><?= $totalUsers ?></h2>
            <p>Total Employees</p>
        </div>
        <div class="card">
            <h2><?= $loginToday ?></h2>
            <p>LOGIN Today</p>
        </div>
        <div class="card">
            <h2><?= $logoutToday ?></h2>
            <p>LOGOUT Today</p>
        </div>
    </div>

    <!-- Reset password now only accessible to logged-in admins -->
    <a class="reset-link" href="forgot_password.php">Reset a User Password</a>

    <h3>Recent Attendance</h3>

    <table>
        <tr>
            <th>Employee</th>
            <th>Status</th>
            <th>Date / Time</th>
        </tr>
        <?php while($r = $recent->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($r['student_name']) ?></td>
            <td><?= htmlspecialchars($r['typeoflog']) ?></td>
            <td><?= htmlspecialchars($r['log_time']) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>

</div>

</body>
</html>
