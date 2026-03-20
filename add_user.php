<?php
session_start();
include 'config.php';

if(!isset($_SESSION['user_id']) || $_SESSION['accgroup'] != '1'){
    header("Location: login.php");
    exit;
}

$message = '';
$msgType = 'green';

// Handle Add User
if(isset($_POST['submit'])){
    $fullname     = trim($_POST['fullname']);
    $username     = trim($_POST['username']);
    $password     = $_POST['password'];
    $access_group = (int)$_POST['access_group'];

    if(empty($fullname) || empty($username) || empty($password) || empty($access_group)){
        $message = "All fields are required!";
        $msgType = 'red';
    } elseif(strlen($password) < 8){
        $message = "Password must be at least 8 characters!";
        $msgType = 'red';
    } elseif(!in_array($access_group, [1, 2])){
        $message = "Invalid access group.";
        $msgType = 'red';
    } else {
        $stmtCheck = $conn->prepare("SELECT id FROM login_users WHERE username = ?");
        $stmtCheck->bind_param("s", $username);
        $stmtCheck->execute();
        $stmtCheck->store_result();

        if($stmtCheck->num_rows > 0){
            $message = "Username already exists!";
            $msgType = 'red';
        } else {
            $stmt = $conn->prepare("INSERT INTO login_users (username, password, fullname, access_group) VALUES (?, ?, ?, ?)");
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt->bind_param("sssi", $username, $hashedPassword, $fullname, $access_group);
            if($stmt->execute()){
                $message = "User added successfully!";
            } else {
                $message = "Error: " . $stmt->error;
                $msgType = 'red';
            }
            $stmt->close();
        }
        $stmtCheck->close();
    }
}

// Handle Edit User (fullname + username + access group)
if(isset($_POST['edit_user'])){
    $edit_id       = (int)$_POST['edit_user_id'];
    $edit_fullname = trim($_POST['edit_fullname']);
    $edit_username = trim($_POST['edit_username']);
    $edit_group    = (int)$_POST['edit_access_group'];

    if($edit_id <= 0 || empty($edit_fullname) || empty($edit_username) || !in_array($edit_group, [1,2])){
        $message = "Invalid data submitted.";
        $msgType = 'red';
    } else {
        // Make sure edited username doesn't conflict with another user
        $chk = $conn->prepare("SELECT id FROM login_users WHERE username = ? AND id != ?");
        $chk->bind_param("si", $edit_username, $edit_id);
        $chk->execute();
        $chk->store_result();

        if($chk->num_rows > 0){
            $message = "Username already taken by another user.";
            $msgType = 'red';
        } else {
            $upd = $conn->prepare("UPDATE login_users SET fullname = ?, username = ?, access_group = ? WHERE id = ?");
            $upd->bind_param("ssii", $edit_fullname, $edit_username, $edit_group, $edit_id);
            if($upd->execute()){
                $message = "User updated successfully!";
            } else {
                $message = "Error updating user.";
                $msgType = 'red';
            }
            $upd->close();
        }
        $chk->close();
    }
}

// Handle Delete User
if(isset($_POST['delete_user'])){
    $del_id = (int)$_POST['delete_user_id'];

    // Prevent admin from deleting their own account
    if($del_id == (int)$_SESSION['user_id']){
        $message = "You cannot delete your own account!";
        $msgType = 'red';
    } elseif($del_id > 0){
        $del = $conn->prepare("DELETE FROM login_users WHERE id = ?");
        $del->bind_param("i", $del_id);
        if($del->execute()){
            $message = "User deleted successfully!";
        } else {
            $message = "Error deleting user.";
            $msgType = 'red';
        }
        $del->close();
    }
}

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
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
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
.add-form{
    background:white;
    padding:25px;
    border-radius:15px;
    box-shadow:0 5px 20px rgba(0,0,0,.05);
    margin-bottom:30px;
}
.add-form input,.add-form select{
    width:100%;
    padding:10px;
    margin:10px 0;
    border-radius:8px;
    border:1px solid #ccc;
}
.add-form button{
    padding:10px 20px;
    background:#6c5ce7;
    color:white;
    border:none;
    border-radius:8px;
    cursor:pointer;
    transition:.3s;
}
.add-form button:hover{
    background:#5a4bcf;
}
.message{
    margin-bottom:15px;
}
.table-container{
    background:white;
    padding:20px;
    border-radius:15px;
    box-shadow:0 5px 20px rgba(0,0,0,.05);
    margin-bottom:30px;
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
.btn-edit{
    padding:5px 12px;
    background:#fdcb6e;
    color:#333;
    border:none;
    border-radius:6px;
    cursor:pointer;
    font-size:13px;
    transition:.3s;
}
.btn-edit:hover{
    background:#e5b84d;
}
.btn-delete{
    padding:5px 12px;
    background:#ff4757;
    color:white;
    border:none;
    border-radius:6px;
    cursor:pointer;
    font-size:13px;
    transition:.3s;
}
.btn-delete:hover{
    background:#cc3344;
}
.modal-bg{
    display:none;
    position:fixed;
    z-index:999;
    left:0;
    top:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.5);
}
.modal-box{
    background:white;
    margin:10% auto;
    padding:30px;
    border-radius:15px;
    width:90%;
    max-width:420px;
}
.modal-box h3{
    margin-bottom:15px;
    color:#6c5ce7;
}
.modal-box input,.modal-box select{
    width:100%;
    padding:10px;
    border-radius:8px;
    border:1px solid #ccc;
    margin-bottom:12px;
    font-size:14px;
    box-sizing:border-box;
}
.modal-box label{
    font-size:13px;
    color:#555;
    display:block;
    margin-bottom:3px;
}
.btn-save-modal{
    padding:10px 20px;
    background:#6c5ce7;
    color:white;
    border:none;
    border-radius:8px;
    cursor:pointer;
    margin-right:8px;
}
.btn-cancel-modal{
    padding:10px 20px;
    background:#ccc;
    color:#333;
    border:none;
    border-radius:8px;
    cursor:pointer;
}
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

    <?php if($message != ''): ?>
        <div class="message" style="color:<?= $msgType ?>; background:white; padding:12px 20px; border-radius:8px; margin-bottom:15px; box-shadow:0 2px 8px rgba(0,0,0,.05);">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Add User Form -->
    <div class="add-form">
        <h3 style="margin-bottom:10px;color:#333;">Add User</h3>
        <form method="post" action="">
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
    </div>

    <!-- All Users Table -->
    <div class="table-container">
        <h3>All Users</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Username</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
            <?php foreach($users as $user): ?>
            <tr>
                <td><?= (int)$user['id'] ?></td>
                <td><?= htmlspecialchars($user['fullname']) ?></td>
                <td><?= htmlspecialchars($user['username']) ?></td>
                <td><?= $user['access_group'] == 1 ? "Administrator" : "Group Leader" ?></td>
                <td style="white-space:nowrap;">
                    <!-- Edit Button -->
                    <button type="button" class="btn-edit" onclick="openEdit(
                        <?= (int)$user['id'] ?>,
                        '<?= htmlspecialchars($user['fullname'], ENT_QUOTES) ?>',
                        '<?= htmlspecialchars($user['username'], ENT_QUOTES) ?>',
                        <?= (int)$user['access_group'] ?>
                    )">Edit</button>

                    <!-- Delete Button — disabled for own account -->
                    <?php if($user['id'] == $_SESSION['user_id']): ?>
                        <button class="btn-delete" disabled title="Cannot delete your own account" style="opacity:0.4;cursor:not-allowed;">Delete</button>
                    <?php else: ?>
                        <form method="post" action="" style="display:inline;"
                            onsubmit="return confirm('Delete user <?= htmlspecialchars($user['fullname'], ENT_QUOTES) ?>? This cannot be undone.');">
                            <input type="hidden" name="delete_user_id" value="<?= (int)$user['id'] ?>">
                            <button type="submit" name="delete_user" class="btn-delete">Delete</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div style="text-align:center;padding:15px;color:#777;font-size:14px;margin-top:30px;">
        This site is maintained and developed by GG EZ Computer Sales and Services
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal-bg" id="editModal">
    <div class="modal-box">
        <h3>Edit User</h3>
        <form method="post" action="">
            <input type="hidden" name="edit_user_id" id="editUserId">
            <label>Full Name</label>
            <input type="text" name="edit_fullname" id="editFullname" required>
            <label>Username</label>
            <input type="text" name="edit_username" id="editUsername" required>
            <label>Role</label>
            <select name="edit_access_group" id="editAccessGroup">
                <option value="1">Administrator</option>
                <option value="2">Group Leader</option>
            </select>
            <br>
            <button type="submit" name="edit_user" class="btn-save-modal">Save</button>
            <button type="button" class="btn-cancel-modal" onclick="closeEdit()">Cancel</button>
        </form>
    </div>
</div>

<script>
function openEdit(id, fullname, username, group){
    document.getElementById('editUserId').value       = id;
    document.getElementById('editFullname').value     = fullname;
    document.getElementById('editUsername').value     = username;
    document.getElementById('editAccessGroup').value  = group;
    document.getElementById('editModal').style.display = 'block';
}
function closeEdit(){
    document.getElementById('editModal').style.display = 'none';
}
window.onclick = function(e){
    if(e.target == document.getElementById('editModal')) closeEdit();
}
</script>

</body>
</html>
