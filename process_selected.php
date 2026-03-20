<?php
session_start();
include 'config.php';

// Protect page — admin only
if(!isset($_SESSION['user_id']) || $_SESSION['accgroup'] != '1'){
    header("Location: login.php");
    exit;
}

if(isset($_POST['selected']) && !empty($_POST['selected'])){
    $selected = $_POST['selected'];

    $insertStmt = $conn->prepare("INSERT INTO final_attendance (user_id, student_name, date_log, time_log, typeoflog) VALUES (?, ?, ?, ?, ?)");
    if(!$insertStmt) die("Insert prepare failed: " . $conn->error);

    foreach($selected as $seq){
        // Cast seq to integer — prevents SQL injection
        $seq = (int)$seq;
        if($seq <= 0) continue;

        // Prepared statement — fetch attendance record
        $res = $conn->prepare("
            SELECT a.user_id, u.student_name, a.log_time, a.typeoflog
            FROM attendance a
            INNER JOIN users u ON a.user_id = u.id
            WHERE a.seq = ?
            LIMIT 1
        ");
        $res->bind_param("i", $seq);
        $res->execute();
        $result = $res->get_result();
        $row    = $result->fetch_assoc();
        $res->close();

        if(!$row) continue;

        $user_id      = $row['user_id'];
        $student_name = $row['student_name'];
        $log_time     = $row['log_time'];
        $typeoflog    = $row['typeoflog'];
        $date_log     = date('Y-m-d', strtotime($log_time));
        $time_log     = date('H:i:s', strtotime($log_time));

        // Prepared statement — check for duplicate before inserting
        $checkStmt = $conn->prepare("SELECT seq FROM final_attendance WHERE user_id = ? AND date_log = ? AND time_log = ?");
        $checkStmt->bind_param("iss", $user_id, $date_log, $time_log);
        $checkStmt->execute();
        $checkStmt->store_result();

        if($checkStmt->num_rows == 0){
            $insertStmt->bind_param("issss", $user_id, $student_name, $date_log, $time_log, $typeoflog);
            $insertStmt->execute();

            // Prepared statement — delete from attendance after saving
            $deleteStmt = $conn->prepare("DELETE FROM attendance WHERE seq = ? LIMIT 1");
            $deleteStmt->bind_param("i", $seq);
            $deleteStmt->execute();
            $deleteStmt->close();
        }

        $checkStmt->close();
    }

    $insertStmt->close();
    echo "<script>alert('Selected attendance saved and removed successfully!'); window.location='admin_dashboard.php';</script>";

} else {
    echo "<script>alert('No attendance selected!'); window.location='admin_dashboard.php';</script>";
}
