<?php
session_start();
include 'config.php';

// Only accessible to logged-in admins
if(!isset($_SESSION['user_id']) || $_SESSION['accgroup'] != '1'){
    header("Location: login.php");
    exit;
}

$msg = "";
$msgType = "";

if(isset($_POST['reset'])){
    $username = trim($_POST['username']);
    $newpass  = $_POST['newpass'];
    $confirm  = $_POST['confirm'];

    // Minimum password length
    if(strlen($newpass) < 8){
        $msg     = "Password must be at least 8 characters.";
        $msgType = "error";
    } elseif($newpass !== $confirm){
        $msg     = "Passwords do not match.";
        $msgType = "error";
    } else {
        // Prepared statement — prevents SQL injection
        $stmt = $conn->prepare("SELECT id FROM login_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if($stmt->num_rows > 0){
            $hashed = password_hash($newpass, PASSWORD_DEFAULT);
            $stmt->close();

            $upd = $conn->prepare("UPDATE login_users SET password = ? WHERE username = ?");
            $upd->bind_param("ss", $hashed, $username);
            $upd->execute();
            $upd->close();

            $msg     = "Password updated successfully!";
            $msgType = "success";
        } else {
            $stmt->close();
            $msg     = "Username not found.";
            $msgType = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Reset Password</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
}
body{
    background:#6c5ce7;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}
.box{
    background:white;
    padding:30px;
    border-radius:10px;
    width:340px;
    text-align:center;
}
h2{
    color:#6c5ce7;
    margin-bottom:20px;
}
input{
    width:100%;
    padding:10px;
    margin:8px 0;
    border-radius:8px;
    border:1px solid #ccc;
    box-sizing:border-box;
    font-size:15px;
}
button{
    width:100%;
    padding:10px;
    background:#6c5ce7;
    color:white;
    border:none;
    border-radius:8px;
    cursor:pointer;
    margin-top:10px;
    font-size:15px;
}
button:hover{
    background:#5a4bcf;
}
.success{
    color:green;
    margin-top:10px;
}
.error{
    color:red;
    margin-top:10px;
}
a{
    display:block;
    margin-top:15px;
    color:#6c5ce7;
}
</style>
</head>
<body>

<div class="box">
    <h2>Reset Password</h2>

    <form method="POST">
        <input type="text"     name="username" placeholder="Username" required>
        <input type="password" name="newpass"  placeholder="New Password (min 8 chars)" required>
        <input type="password" name="confirm"  placeholder="Confirm New Password" required>
        <button name="reset">Reset Password</button>
    </form>

    <?php if($msg): ?>
        <p class="<?= $msgType ?>"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <a href="admin_dashboard.php">← Back to Dashboard</a>
</div>

</body>
</html>
