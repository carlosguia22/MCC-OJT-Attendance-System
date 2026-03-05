<?php
session_start();
include 'config.php';

// Protect page
if(!isset($_SESSION['user_id']) || $_SESSION['accgroup'] != '1'){
    header("Location: login.php");
    exit;
}

$message = '';

if(isset($_POST['submit'])){
    $fullname     = trim($_POST['fullname']);
    $username     = trim($_POST['username']);
    $password     = $_POST['password'];
    $access_group = (int)$_POST['access_group'];

    if(empty($fullname) || empty($username) || empty($password) || empty($access_group)){
        $message = "All fields are required!";
    } elseif(strlen($password) < 8){
        // ✅ Enforce minimum password length
        $message = "Password must be at least 8 characters!";
    } elseif(!in_array($access_group, [1, 2])){
        // ✅ Whitelist valid access groups
        $message = "Invalid access group.";
    } else {
        // ✅ Prepared statement — check duplicate username
        $stmtCheck = $conn->prepare("SELECT id FROM login_users WHERE username = ?");
        $stmtCheck->bind_param("s", $username);
        $stmtCheck->execute();
        $stmtCheck->store_result();

        if($stmtCheck->num_rows > 0){
            $message = "Username already exists!";
        } else {
            // ✅ Prepared statement — insert new user with hashed password
            $stmt = $conn->prepare("INSERT INTO login_users (username, password, fullname, access_group) VALUES (?, ?, ?, ?)");
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt->bind_param("sssi", $username, $hashedPassword, $fullname, $access_group);

            if($stmt->execute()){
                $message = "User added successfully!";
            } else {
                $message = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
        $stmtCheck->close();
    }
}

// ✅ No user input — direct query is safe here
$result = $conn->query("SELECT id, username, fullname, access_group FROM login_users ORDER BY fullname ASC");
$users = [];
if($result){
    while($row = $result->fetch_assoc()) $users[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Manage Users</title>
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
form{background:white;padding:25px;border-radius:15px;box-shadow:0 5px 20px rgba(0,0,0,.05);margin-bottom:30px;}
form input, select{width:100%;padding:10px;margin:10px 0;border-radius:8px;border:1px solid #ccc;}
form button{padding:10px 20px;background:#6c5ce7;color:white;border:none;border-radius:8px;cursor:pointer;transition:.3s;}
form button:hover{background:#5a4bcf;}
.message{margin-bottom:15px;color:green;}
.table-container{background:white;padding:20px;border-radius:15px;box-shadow:0 5px 20px rgba(0,0,0,.05);margin-bottom:30px;}
table{width:100%;border-collapse:collapse;}
th{text-align:left;padding:12px;background:#6c5ce7;color:white;}
td{padding:12px;border-bottom:1px solid #eee;vertical-align:middle;}
tr:hover{background:#f9f9ff;}
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
        <h1>Manage Users</h1>
        <a class="logout" href="logout.php">Logout</a>
    </div>

    <form method="post" action="">
        <?php if($message != '') echo '<div class="message">' . htmlspecialchars($message) . '</div>'; ?>

        <label>Full Name</label>
        <input type="text" name="fullname" required>

        <label>Username</label>
        <input type="text" name="username" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <label>Access Group</label>
        <select name="access_group" required>
            <option value="">-- Select Role --</option>
            <option value="1">Administrator</option>
            <option value="2">Group Leader</option>
        </select>

        <button type="submit" name="submit">Add User</button>
    </form>

    <div class="table-container">
        <h3>All Users</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Username</th>
                <th>Access Group</th>
            </tr>
            <?php foreach($users as $user): ?>
            <tr>
                <td><?= (int)$user['id'] ?></td>
                <td><?= htmlspecialchars($user['fullname']) ?></td>
                <td><?= htmlspecialchars($user['username']) ?></td>
                <td><?= $user['access_group'] == 1 ? "Administrator" : "Group Leader" ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div style="text-align:center;padding:15px;color:#777;font-size:14px;margin-top:30px;">
        This site is maintained and developed by GG EZ Computer Sales and Services
    </div>
</div>
</body>
</html>
