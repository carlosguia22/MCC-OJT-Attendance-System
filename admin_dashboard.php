<?php
session_start();
include 'config.php';

if(!isset($_SESSION['user_id']) || $_SESSION['accgroup'] != '1'){
    header("Location: login.php");
    exit;
}

// Timezone fix
date_default_timezone_set('Asia/Manila');

// Date filter — use selected date or default to today
$filterDate = isset($_GET['filter_date']) && !empty($_GET['filter_date'])
    ? preg_replace('/[^0-9\-]/', '', $_GET['filter_date'])
    : date('Y-m-d');

// Total interns — always fixed
$result     = $conn->query("SELECT COUNT(*) AS total FROM users");
$totalUsers = $result ? $result->fetch_assoc()['total'] : 0;

// Login count for selected date — prepared statement
$stmtLogin = $conn->prepare("SELECT COUNT(*) AS total FROM attendance WHERE typeoflog='LOGIN' AND DATE(log_time) = ?");
$stmtLogin->bind_param("s", $filterDate);
$stmtLogin->execute();
$loginCount = $stmtLogin->get_result()->fetch_assoc()['total'];
$stmtLogin->close();

// Logout count for selected date — prepared statement
$stmtLogout = $conn->prepare("SELECT COUNT(*) AS total FROM attendance WHERE typeoflog='LOGOUT' AND DATE(log_time) = ?");
$stmtLogout->bind_param("s", $filterDate);
$stmtLogout->execute();
$logoutCount = $stmtLogout->get_result()->fetch_assoc()['total'];
$stmtLogout->close();

// Recent attendance filtered by selected date — prepared statement
$stmtRecent = $conn->prepare("
    SELECT a.seq, a.user_id, u.student_name, a.typeoflog, a.log_time, a.img
    FROM attendance a
    INNER JOIN users u ON a.user_id = u.id
    WHERE DATE(a.log_time) = ?
    ORDER BY a.log_time DESC
    LIMIT 50
");
$stmtRecent->bind_param("s", $filterDate);
$stmtRecent->execute();
$recent = $stmtRecent->get_result();

// Label for cards
$isToday    = ($filterDate === date('Y-m-d'));
$dateLabel  = $isToday ? 'Today' : date('M d, Y', strtotime($filterDate));
?>

<!DOCTYPE html>
<html>
<head>
<title>Administrator Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<style>
*{
    margin:0;
    padding:0;
    box-sizing:
    border-box;
    font-family:'Poppins',sans-serif;
}
body{
    display:flex;
    background:#f4f6fb;
}
.sidebar{
    width:240px;
    background:#1e1e2f;
    height:100vh;
    color:white;
    padding:20px;
    position:fixed;
}
.sidebar h2{
    margin-bottom:30px;
    text-align:center;
}
.sidebar a{
    display:block;
    padding:12px;
    color:#ccc;
    text-decoration:none;
    border-radius:8px;
    margin-bottom:10px;
    transition:.3s;
}
.sidebar a:hover{
    background:#6c5ce7;
    color:white;
}
.main{
    margin-left:240px;
    width:100%;
    padding:20px;
}
.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
}
.topbar h1{
    color:#333;
}
.logout{
    background:#ff4757;
    padding:8px 15px;
    border-radius:6px;
    color:white;
    text-decoration:none;
}
.cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:20px;margin-bottom:20px;
}
.card{
    background:white;
    padding:25px;
    border-radius:15px;
    box-shadow:0 5px 20px rgba(0,0,0,.05);transition:.3s;
}
.card:hover{
    transform:translateY(-5px);
}
.card h2{
    font-size:30px;
    color:#6c5ce7;
}
.card p{
    margin-top:8px;
    color:#777;
}
.filter-bar{
    background:white;
    padding:15px 20px;
    border-radius:15px;
    box-shadow:0 5px 20px rgba(0,0,0,.05);
    margin-bottom:20px;
    display:flex;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
}
.filter-bar label{
    font-weight:600;
    color:#333;
}
.filter-bar input[type="date"]{
    padding:8px 12px;
    border-radius:8px;
    border:1px solid #ccc;
    font-size:14px;
}
.filter-bar button{
    padding:8px 18px;
    background:#6c5ce7;
    color:white;
    border:none;
    border-radius:8px;
    cursor:pointer;
    font-size:14px;
    transition:.3s;
}
.filter-bar button:hover{
    background:#5a4bcf;
}
.filter-bar .reset-btn{
    background:#aaa;
}
.filter-bar .reset-btn:hover{
    background:#888;
}
.table-container{
    background:white;
    padding:20px;
    border-radius:15px;
    box-shadow:0 5px 20px rgba(0,0,0,.05);
}
table{
    width:100%;
    border-collapse:collapse;
}
th{
    text-align:left;
    padding:12px;
    background:#6c5ce7;
    color:white;
}
td{
    padding:12px;
    border-bottom:1px solid #eee;
    vertical-align:middle;
}
tr:hover{
    background:#f9f9ff;
}
.status-login{
    color:green;
    font-weight:600;
}
.status-logout{
    color:red;
    font-weight:600;
}
.photo-square{
    width:50px;
    height:50px;
    object-fit:cover;
    border:2px solid #6c5ce7;
    transition:.3s;
}
.photo-square:hover{
    transform:scale(1.2);
    cursor:pointer;
}
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
    <a href="forgot_password.php">Reset Password</a>
</div>

<div class="main">
    <div class="topbar">
        <h1>Administrator Dashboard</h1>
        <a class="logout" href="logout.php">Logout</a>
    </div>

    <!-- Date Filter Bar -->
    <div class="filter-bar">
        <label>Filter by Date:</label>
        <form method="get" action="" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin:0;">
            <input type="date" name="filter_date" value="<?= htmlspecialchars($filterDate) ?>">
            <button type="submit">Apply</button>
            <a href="admin_dashboard.php">
                <button type="button" class="reset-btn">Reset to Today</button>
            </a>
        </form>
        <?php if(!$isToday): ?>
            <span style="color:#6c5ce7;font-weight:600;font-size:14px;">
                Showing: <?= htmlspecialchars($dateLabel) ?>
            </span>
        <?php endif; ?>
    </div>

    <!-- Stat Cards -->
    <div class="cards">
        <div class="card">
            <h2><?= $totalUsers ?></h2>
            <p>Total Interns</p>
        </div>
        <div class="card">
            <h2><?= $loginCount ?></h2>
            <p>Login — <?= htmlspecialchars($dateLabel) ?></p>
        </div>
        <div class="card">
            <h2><?= $logoutCount ?></h2>
            <p>Logout — <?= htmlspecialchars($dateLabel) ?></p>
        </div>
    </div>

    <!-- Attendance Table -->
    <div class="table-container">
        <h3 style="margin-bottom:15px;">
            Attendance — <?= htmlspecialchars($dateLabel) ?>
        </h3>
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
            <?php
            $hasRows = false;
            while($r = $recent->fetch_assoc()):
                $hasRows = true;
            ?>
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
                <td class="<?= strtolower($r['typeoflog']) == 'login' ? 'status-login' : 'status-logout' ?>">
                    <?= htmlspecialchars($r['typeoflog']) ?>
                </td>
                <td><?= htmlspecialchars($r['log_time']) ?></td>
            </tr>
            <?php endwhile; ?>
            <?php if(!$hasRows): ?>
            <tr>
                <td colspan="6" style="text-align:center;color:#aaa;padding:20px;">
                    No attendance records for <?= htmlspecialchars($dateLabel) ?>
                </td>
            </tr>
            <?php endif; ?>
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
<?php $stmtRecent->close(); ?>
