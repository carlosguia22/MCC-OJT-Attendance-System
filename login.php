<?php
session_start();
include 'config.php';

if(isset($_POST['login'])){

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Prepared statement — prevents SQL injection 
    $stmt = $conn->prepare("SELECT * FROM login_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if($res->num_rows > 0){

        $row = $res->fetch_assoc();

        if(password_verify($password, $row['password'])){

            // Regenerate session ID on login — prevents session fixation oten oten
            session_regenerate_id(true);

            $_SESSION['user_id']  = $row['id'];
            $_SESSION['fullname'] = $row['fullname'];
            $_SESSION['accgroup'] = $row['access_group'];

            if($_SESSION['accgroup'] == 1){
                header("Location: admin_dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit;

        } else {
            // Mag generic message nalang for extra safety
            $error = "Invalid username or password.";
        }

    } else {
        $error = "Invalid username or password.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Attendance System</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background: linear-gradient(135deg, #6c5ce7, #a29bfe);
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 0;
    }
    .login-container {
        background-color: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        width: 90%;
        max-width: 400px;
        text-align: center;
    }
    .login-container img { 
        width: 120px; 
        margin-bottom: 20px; 
    }
    h2 { 
        margin-bottom: 
        20px; 
        color: #6c5ce7; 
    }
    input[type="text"], input[type="password"] {
        width: 100%;
        padding: 12px 15px;
        margin: 8px 0 20px 0;
        border: 1px solid #ccc;
        border-radius: 10px;
        box-sizing: border-box;
        font-size: 16px;
    }
    button {
        background-color: #6c5ce7;
        color: white;
        border: none;
        padding: 12px;
        width: 100%;
        border-radius: 10px;
        font-size: 16px;
        cursor: pointer;
        transition: 0.3s;
    }
    button:hover { 
        background-color: #341f97; 
    }
    .error { 
        color: red; 
        margin-bottom: 15px; 
    }
    .footer { 
        margin-top: 15px; 
        font-size: 14px; 
        color: #555; 
    }
    @media (max-width: 480px) { .login-container { padding: 20px; } }
</style>
</head>
<body>

<div class="login-container">
    <img src="images/1.png" alt="School Logo">
    <h2>MCC OJT Attendance Login</h2>

    <?php if(isset($error)) echo "<div class='error'>" . htmlspecialchars($error) . "</div>"; ?>

    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="login">Login</button>
    </form>

    <div class="footer">
        &copy; <?=date("Y")?> GGez Computer Solutions Inc.
    </div>
</div>

</body>
</html>
