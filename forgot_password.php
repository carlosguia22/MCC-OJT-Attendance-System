<?php
session_start();
include 'config.php';

// ✅ Only allow access if logged in as admin atay oy
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

    // ✅ Enforce minimum password length
    if(strlen($newpass) < 8){
        $msg     = "Password must be at least 8 characters.";
        $msgType = "error";
    } elseif($newpass !== $confirm){
        $msg     = "Passwords do not match.";
        $msgType = "error";
    } else {
        // ✅ Prepared statement — prevents SQL injection
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
<style>
body {
    font-family: Arial;
    background: #6c5ce7;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    margin: 0;
}
.box {
    background: white;
    padding: 30px;
    border-radius: 10px;
    width: 320px;
    text-align: center;
}
h2 { color: #6c5ce7; }
input, button {
    width: 100%;
    padding: 10px;
    margin: 8px 0;
    border-radius: 8px;
    border: 1px solid #ccc;
    box-sizing: border-box;
    font-size: 15px;
}
button {
    background: #6c5ce7;
    color: white;
    border: none;
    cursor: pointer;
    margin-top: 12px;
}
button:hover { background: #341f97; }
.success { color: green; margin-top: 10px; }
.error   { color: red;   margin-top: 10px; }
</style>
</head>
<body>

<div class="box">
    <h2>Reset Password</h2>

    <form method="POST">
        <input type="text"     name="username" placeholder="Username"         required>
        <input type="password" name="newpass"  placeholder="New Password (min 8 chars)" required>
        <input type="password" name="confirm"  placeholder="Confirm New Password" required>
        <button name="reset">Reset Password</button>
    </form>

    <?php if($msg): ?>
        <p class="<?= $msgType ?>"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <br><a href="admin_dashboard.php">Back to Dashboard</a>
</div>

</body>
</html>
