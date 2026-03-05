<?php
session_start();
include 'config.php';
date_default_timezone_set('Asia/Manila');

// Protect page
if(!isset($_SESSION['user_id']) || $_SESSION['accgroup'] != '1'){
    header("Location: login.php");
    exit;
}

// ✅ No user input in these queries — direct queries are safe here
$result = $conn->query("SELECT COUNT(*) AS total FROM users");
$totalUsers = $result ? $result->fetch_assoc()['total'] : 0;

$today = date('Y-m-d');

$resultLogin = $conn->query("SELECT COUNT(*) AS total FROM attendance WHERE typeoflog='LOGIN' AND DATE(log_time)='$today'");
$loginToday  = $resultLogin ? $resultLogin->fetch_assoc()['total'] : 0;

$resultLogout = $conn->query("SELECT COUNT(*) AS total FROM attendance WHERE typeoflog='LOGOUT' AND DATE(log_time)='$today'");
$logoutToday  = $resultLogout ? $resultLogout->fetch_assoc()['total'] : 0;

$recent = $conn->query("
    SELECT a.seq, a.user_id, u.student_name, a.typeoflog, a.log_time, a.img
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
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;margin-bottom:30px;}
.card{background:white;padding:25px;border-radius:15px;box-shadow:0 5px 20px rgba(0,0,0,.05);transition:.3s;}
.card:hover{transform:translateY(-5px);}
.card h2{font-size:30px;color:#6c5ce7;}
.card p{margin-top:8px;color:#777;}
.table-container{background:white;padding:20px;border-radius:15px;box-shadow:0 5px 20px rgba(0,0,0,.05);}
table{width:100%;border-collapse:collapse;}
th{text-align:left;padding:12px;background:#6c5ce7;color:white;}
td{padding:12px;border-bottom:1px solid #eee;vertical-align:middle;}
tr:hover{background:#f9f9ff;}
.status-login{color:green;font-weight:600;}
.status-logout{color:red;font-weight:600;}
.photo-square{width:50px;height:50px;object-fit:cover;border:2px solid #6c5ce7;transition:.3s;}
.photo-square:hover{transform:scale(1.2);cursor:pointer;}
@media(max-width:768px){.sidebar{display:none;}.main{margin-left:0;}}
</style>
<script>
function toggleSelectAll(source){
    var checkboxes = document.getElementsByName('selected[]');
    for(var i = 0; i < checkboxes.length; i++){
        checkboxes[i].checked = source.checked;
    }
}
</script>
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
    <!-- ✅ Reset password only accessible to logged-in admins -->
    <a href="forgot_password.php">Reset Password</a>
</div>

<div class="main">
    <div class="topbar">
        <h1>Administrator Dashboard</h1>
        <a class="logout" href="logout.php">Logout</a>
    </div>

    <div class="cards">
        <div class="card"><h2><?= $totalUsers ?></h2><p>Total Interns</p></div>
        <div class="card"><h2><?= $loginToday ?></h2><p>Login Today</p></div>
        <div class="card"><h2><?= $logoutToday ?></h2><p>Logout Today</p></div>
    </div>

    <div class="table-container">
        <h3>Recent Attendance</h3>
        <form method="post" action="process_selected.php">
        <table>
            <tr>
                <th><input type="checkbox" onclick="toggleSelectAll(this)"></th>
                <th>Photo</th>
                <th>User ID</th>
                <th>Intern Name</th>
                <th>Status</th>
                <th>Date / Time</th>
            </tr>
            <?php while($r = $recent->fetch_assoc()): ?>
            <tr>
                <td><input type="checkbox" name="selected[]" value="<?= (int)$r['seq'] ?>"></td>
                <td>
                    <?php if(!empty($r['img'])): ?>
                        <img src="data:image/jpeg;base64,<?= base64_encode($r['img']) ?>" class="photo-square" onclick="openModal(this)">
                    <?php else: ?>
                        <img src="upload/default.png" class="photo-square" onclick="openModal(this)">
                    <?php endif; ?>
                </td>
                <td><?= (int)$r['user_id'] ?></td>
                <td><?= htmlspecialchars($r['student_name']) ?></td>
                <td><?= htmlspecialchars($r['typeoflog']) ?></td>
                <td><?= htmlspecialchars($r['log_time']) ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
        <button type="submit" style="margin-top:15px;padding:10px 20px;background:#6c5ce7;color:white;border:none;border-radius:8px;cursor:pointer;">Save Selected</button>
        </form>
    </div>

    <div style="text-align:center;padding:15px;color:#777;font-size:14px;margin-top:30px;">
        This site is maintained and developed by GG EZ Computer Sales and Services
    </div>
</div>

<!-- Modal for zoom -->
<div id="imgModal" style="display:none;position:fixed;z-index:9999;padding-top:50px;left:0;top:0;width:100%;height:100%;overflow:auto;background-color:rgba(0,0,0,0.8);">
    <span onclick="closeModal()" style="position:absolute;top:20px;right:35px;color:#fff;font-size:40px;font-weight:bold;cursor:pointer;">&times;</span>
    <img id="modalImg" style="margin:auto;display:block;max-width:90%;max-height:90%;border-radius:15px;">
</div>
<script>
function openModal(img){ document.getElementById("imgModal").style.display="block"; document.getElementById("modalImg").src=img.src; }
function closeModal(){ document.getElementById("imgModal").style.display="none"; }
window.onclick = function(e){ if(e.target==document.getElementById("imgModal")) closeModal(); }
</script>
</body>
</html>
