<?php
session_start();
include 'config.php';

if(!isset($_SESSION['user_id'])){
    die("User not logged in!");
}

$group_leader = $_SESSION['user_id'];

//  Validate user_id
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
if(!$user_id){
    die("No employee selected.");
}

// Verify that the selected employee actually belongs to this group leader
// (prevents a logged-in user from submitting attendance for someone else's employee)
$chk = $conn->prepare("SELECT id FROM users WHERE id = ? AND group_leader = ?");
$chk->bind_param("ii", $user_id, $group_leader);
$chk->execute();
$chk->store_result();
if($chk->num_rows === 0){
    $chk->close();
    die("Unauthorized employee selection.");
}
$chk->close();

// Validate status
$allowed_statuses = ['LOGIN', 'LOGOUT'];
$status = $_POST['status'] ?? '';
if(!in_array($status, $allowed_statuses)){
    die("Invalid status.");
}

// Validate image
$image = $_POST['image'] ?? null;
if(!$image){
    die("No selfie captured.");
}

// Strip base64 prefix and decode
$image   = preg_replace('/^data:image\/jpeg;base64,/', '', $image);
$image   = str_replace(' ', '+', $image);
$decoded = base64_decode($image, true);

if(!$decoded){
    die("Invalid image data.");
}

// Validate it is actually a JPEG by checking magic bytes
if(substr($decoded, 0, 2) !== "\xFF\xD8"){
    die("Invalid image format.");
}

// Save image to uploads folder
$filename = time() . rand(1000,9999) . ".jpg";
$uploadDir = __DIR__ . "/uploads/";
if(!is_dir($uploadDir)){
    mkdir($uploadDir, 0755, true);
}
file_put_contents($uploadDir . $filename, $decoded);

// Prepared statement insert
$stmt = $conn->prepare("INSERT INTO attendance (user_id, selfie, typeoflog) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $user_id, $filename, $status);
$stmt->execute();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Attendance Saved</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background: linear-gradient(135deg, #74b9ff, #a29bfe);
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }
    .message-box {
        background-color: white;
        padding: 30px;
        border-radius: 15px;
        text-align: center;
        box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        width: 90%;
        max-width: 400px;
    }
    .message-box h2  { 
        color: #6c5ce7; 
        margin-bottom: 20px; 
    }
    .message-box p   { 
        font-size: 16px; 
        margin-bottom: 20px; 
    }
    .message-box .redirect {
        background-color: #6c5ce7;
        color: white;
        padding: 12px 20px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: bold;
        transition: 0.3s;
    }
    .message-box .redirect:hover { background-color: #341f97; }
</style>
</head>
<body>

<div class="message-box">
    <h2>Attendance Saved!</h2>
    <p>The attendance has been successfully recorded.</p>
    <p>You will be redirected back shortly.</p>
    <a href="index.php" class="redirect">Go Back Now</a>
</div>

<script>
    setTimeout(function(){ window.location.href = 'index.php'; }, 3000);
</script>

</body>
</html>
