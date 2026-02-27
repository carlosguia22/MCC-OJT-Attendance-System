<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$group_leader = (int)$_SESSION['user_id'];

include 'config.php';

// Prepared statement
$stmt = $conn->prepare("SELECT id, student_name FROM users WHERE group_leader = ?");
$stmt->bind_param("i", $group_leader);
$stmt->execute();
$res = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Attendance - OJT System</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #74b9ff, #a29bfe);
    margin: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}
.attendance-container {
    background-color: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
    width: 90%;
    max-width: 450px;
    text-align: center;
}
h2 { 
    color: #6c5ce7; 
    margin-bottom: 15px; 
}
select {
    width: 100%;
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 15px;
    font-size: 16px;
}
#video { 
    width: 100%; 
    border-radius: 10px; 
    margin-bottom: 10px; 
}
#snap {
    background-color: #6c5ce7;
    color: white;
    border: none;
    padding: 12px;
    width: 100%;
    border-radius: 10px;
    font-size: 16px;
    cursor: pointer;
    margin-top: 10px;
    transition: 0.3s;
}
#snap:hover { 
    background-color: #341f97; 
}
.logout { 
    display: block; 
    margin-top: 20px; 
    text-decoration: none; 
    color: #ff7675; 
    font-weight: bold; 
}
.footer { 
    margin-top: 15px; 
    font-size: 14px; 
    color: #555; 
}
@media(max-width: 480px){ .attendance-container { padding: 20px; } }
</style>
</head>
<body>

<div class="attendance-container">
    <h2>Attendance Logging</h2>

    <p><strong>Logged in as:</strong> <?= htmlspecialchars($_SESSION['fullname']) ?></p>

    <form id="attendanceForm" method="POST" action="save_attendance.php">
        <select name="user_id" required>
            <option value="">Select Employee</option>
            <?php while($row = $res->fetch_assoc()): ?>
                <option value="<?= (int)$row['id'] ?>">
                    <?= htmlspecialchars($row['student_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <select name="status" required>
            <option value="">Select Status</option>
            <option value="LOGIN">LOG-IN</option>
            <option value="LOGOUT">LOG-OUT</option>
        </select>

        <video id="video" autoplay playsinline></video>
        <canvas id="canvas" style="display:none;"></canvas>
        <input type="hidden" name="image" id="image">

        <button type="button" id="snap">Take Selfie & Submit</button>
    </form>

    <a href="logout.php" class="logout">Logout</a>

    <div class="footer">
        &copy; <?= date("Y") ?> OJT Attendance System
    </div>
</div>

<script>
const video      = document.getElementById("video");
const canvas     = document.getElementById("canvas");
const snap       = document.getElementById("snap");
const imageInput = document.getElementById("image");
const form       = document.getElementById("attendanceForm");

navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" } })
    .then(stream => { video.srcObject = stream; })
    .catch(err  => { alert("Cannot access camera: " + err); });

snap.onclick = () => {
    if(!form.user_id.value){
        alert("Please select an employee first!");
        return;
    }
    canvas.width  = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext("2d").drawImage(video, 0, 0);
    imageInput.value = canvas.toDataURL("image/jpeg");
    form.submit();
};
</script>

</body>
</html>
<?php $stmt->close(); ?>
