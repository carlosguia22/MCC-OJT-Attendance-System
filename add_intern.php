<?php
session_start();
include 'config.php';

if(!isset($_SESSION['user_id']) || $_SESSION['accgroup'] != '1'){
    header("Location: login.php");
    exit;
}

$message = '';
$msgType = 'green';

// Handle Add Intern
if(isset($_POST['submit'])){
    $name  = trim($_POST['student_name']);
    $group = (int)$_POST['group_leader'];

    if(empty($name) || empty($group)){
        $message = "Both fields are required!";
        $msgType = 'red';
    } else {
        $stmtCheck = $conn->prepare("SELECT id FROM users WHERE student_name = ?");
        $stmtCheck->bind_param("s", $name);
        $stmtCheck->execute();
        $stmtCheck->store_result();

        if($stmtCheck->num_rows > 0){
            $message = "Intern with this name already exists!";
            $msgType = 'red';
        } else {
            $stmt = $conn->prepare("INSERT INTO users (student_name, group_leader) VALUES (?, ?)");
            $stmt->bind_param("si", $name, $group);
            if($stmt->execute()){
                $message = "Intern added successfully!";
            } else {
                $message = "Error: " . $conn->error;
                $msgType = 'red';
            }
            $stmt->close();
        }
        $stmtCheck->close();
    }
}

// Handle Edit Intern Name
if(isset($_POST['edit_intern'])){
    $intern_id   = (int)$_POST['edit_intern_id'];
    $new_name    = trim($_POST['edit_intern_name']);

    if(empty($new_name) || $intern_id <= 0){
        $message = "Invalid name or intern.";
        $msgType = 'red';
    } else {
        $upd = $conn->prepare("UPDATE users SET student_name = ? WHERE id = ?");
        $upd->bind_param("si", $new_name, $intern_id);
        if($upd->execute()){
            $message = "Intern name updated successfully!";
        } else {
            $message = "Error updating name.";
            $msgType = 'red';
        }
        $upd->close();
    }
}

// Handle Delete Intern
if(isset($_POST['delete_intern'])){
    $intern_id = (int)$_POST['delete_intern_id'];
    if($intern_id > 0){
        $del = $conn->prepare("DELETE FROM users WHERE id = ?");
        $del->bind_param("i", $intern_id);
        if($del->execute()){
            $message = "Intern deleted successfully!";
        } else {
            $message = "Error deleting intern.";
            $msgType = 'red';
        }
        $del->close();
    }
}

// Handle Save All Group Leader Changes
if(isset($_POST['save_all'])){
    $intern_ids  = $_POST['intern_id']  ?? [];
    $new_leaders = $_POST['new_leader'] ?? [];
    $updated = 0;

    foreach($intern_ids as $index => $intern_id){
        $intern_id  = (int)$intern_id;
        $new_leader = (int)($new_leaders[$index] ?? 0);

        if($intern_id > 0 && $new_leader > 0){
            $upd = $conn->prepare("UPDATE users SET group_leader = ? WHERE id = ?");
            $upd->bind_param("ii", $new_leader, $intern_id);
            $upd->execute();
            $upd->close();
            $updated++;
        }
    }
    $message = "Changes saved! ($updated intern(s) updated)";
}

// Fetch group leaders
$groupLeadersResult = $conn->query("SELECT id, fullname FROM login_users WHERE access_group = 2 ORDER BY fullname ASC");
$groupLeaders = [];
if($groupLeadersResult){
    while($row = $groupLeadersResult->fetch_assoc()) $groupLeaders[] = $row;
}

// Fetch all interns
$internsResult = $conn->query("
    SELECT u.id AS intern_id, u.student_name AS intern_name, u.group_leader AS leader_id, lu.fullname AS group_leader_name
    FROM users u
    INNER JOIN login_users lu ON u.group_leader = lu.id
    ORDER BY u.student_name ASC
");
$interns = [];
if($internsResult){
    while($row = $internsResult->fetch_assoc()) $interns[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Add Intern</title>
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
    color:white;padding:20px;
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
    margin-left:0;
}
.logout{
    background:#ff4757;
    padding:8px 15px;
    border-radius:6px;
    color:white;
    text-decoration:none;
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
    color:white;border:none;
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
.leader-select{
    padding:6px 10px;
    border-radius:6px;
    border:1px solid #ccc;
    font-size:14px;
    width:100%;
    max-width:180px;
}
.save-all-btn{
    margin-top:15px;
    padding:10px 25px;
    background:#6c5ce7;
    color:white;
    border:none;
    border-radius:8px;
    cursor:pointer;
    font-size:15px;
    transition:.3s;
}
.save-all-btn:hover{
    background:#5a4bcf;
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
/* Edit modal */
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
    margin:15% auto;
    padding:30px;
    border-radius:15px;
    width:90%;
    max-width:400px;
    text-align:center;
}
.modal-box h3{
    margin-bottom:15px;
    color:#6c5ce7;
}
.modal-box input{
    width:100%;
    padding:10px;
    border-radius:8px;
    border:1px solid #ccc;
    margin-bottom:15px;
    font-size:15px;
    box-sizing:border-box;
}
.modal-box button{
    padding:10px 20px;
    border:none;
    border-radius:8px;
    cursor:pointer;
    font-size:14px;
    margin:5px;
}
.btn-save-modal{
    background:#6c5ce7;
    color:white;
}
.btn-cancel-modal{
    background:#ccc;
    color:#333;
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
        <h1>Manage Interns</h1>
        <a class="logout" href="logout.php">Logout</a>
    </div>

    <?php if($message != ''): ?>
        <div class="message" style="color:<?= $msgType ?>; background:white; padding:12px 20px; border-radius:8px; margin-bottom:15px; box-shadow:0 2px 8px rgba(0,0,0,.05);">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Add Intern Form -->
    <div class="add-form">
        <h3 style="margin-bottom:10px;color:#333;">Add Intern</h3>
        <form method="post" action="">
            <label>Intern Name</label>
            <input type="text" name="student_name" required>
            <label>Group Leader</label>
            <select name="group_leader" required>
                <option value="">-- Select Group Leader --</option>
                <?php foreach($groupLeaders as $leader): ?>
                    <option value="<?= (int)$leader['id'] ?>"><?= htmlspecialchars($leader['fullname']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="submit">Add Intern</button>
        </form>
    </div>

    <!-- Group Leaders Table -->
    <div class="table-container">
        <h3>Group Leaders</h3>
        <table>
            <tr><th>Group Leader ID</th><th>Group Leader Name</th></tr>
            <?php foreach($groupLeaders as $leader): ?>
            <tr>
                <td><?= (int)$leader['id'] ?></td>
                <td><?= htmlspecialchars($leader['fullname']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- All Interns Table -->
    <div class="table-container">
        <h3>All Interns</h3>
        <form method="post" action="">
            <table>
                <tr>
                    <th>Intern Name</th>
                    <th>Group Leader</th>
                    <th>Actions</th>
                </tr>
                <?php foreach($interns as $intern): ?>
                <tr>
                    <td><?= htmlspecialchars($intern['intern_name']) ?></td>
                    <td>
                        <input type="hidden" name="intern_id[]" value="<?= (int)$intern['intern_id'] ?>">
                        <select name="new_leader[]" class="leader-select">
                            <?php foreach($groupLeaders as $leader): ?>
                                <option value="<?= (int)$leader['id'] ?>"
                                    <?= $leader['id'] == $intern['leader_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($leader['fullname']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td style="white-space:nowrap;">
                        <!-- Edit Name Button -->
                        <button type="button" class="btn-edit"
                            onclick="openEdit(<?= (int)$intern['intern_id'] ?>, '<?= htmlspecialchars($intern['intern_name'], ENT_QUOTES) ?>')">
                            Edit
                        </button>
                        <!-- Delete Button -->
                        <form method="post" action="" style="display:inline;"
                            onsubmit="return confirm('Delete <?= htmlspecialchars($intern['intern_name'], ENT_QUOTES) ?>? This cannot be undone.');">
                            <input type="hidden" name="delete_intern_id" value="<?= (int)$intern['intern_id'] ?>">
                            <button type="submit" name="delete_intern" class="btn-delete">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <button type="submit" name="save_all" class="save-all-btn">Save All Changes</button>
        </form>
    </div>

    <div style="text-align:center;padding:15px;color:#777;font-size:14px;margin-top:30px;">
        This site is maintained and developed by GG EZ Computer Sales and Services
    </div>
</div>

<!-- Edit Name Modal -->
<div class="modal-bg" id="editModal">
    <div class="modal-box">
        <h3>Edit Intern Name</h3>
        <form method="post" action="">
            <input type="hidden" name="edit_intern_id" id="editInternId">
            <input type="text"   name="edit_intern_name" id="editInternName" placeholder="New name" required>
            <br>
            <button type="submit" name="edit_intern" class="btn-save-modal">Save</button>
            <button type="button" class="btn-cancel-modal" onclick="closeEdit()">Cancel</button>
        </form>
    </div>
</div>

<script>
function openEdit(id, name){
    document.getElementById('editInternId').value   = id;
    document.getElementById('editInternName').value = name;
    document.getElementById('editModal').style.display = 'block';
}
function closeEdit(){
    document.getElementById('editModal').style.display = 'none';
}
// Click outside modal to close
window.onclick = function(e){
    if(e.target == document.getElementById('editModal')) closeEdit();
}
</script>

</body>
</html>
