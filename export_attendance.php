<?php
session_start();
include 'config.php';

// Fixed: was checking $_SESSION['access_group'] — correct key is 'accgroup'
if(!isset($_SESSION['user_id']) || $_SESSION['accgroup'] != '1'){
    header("Location: login.php");
    exit;
}

$fromDate = trim($_POST['from_date'] ?? '');
$toDate   = trim($_POST['to_date']   ?? '');

// Prepared statement — prevents SQL injection on date inputs
if(!empty($fromDate) && !empty($toDate)){
    $stmt = $conn->prepare("
        SELECT user_id, student_name, date_log, time_log, typeoflog
        FROM final_attendance
        WHERE date_log BETWEEN ? AND ?
        ORDER BY date_log DESC, time_log DESC
    ");
    $stmt->bind_param("ss", $fromDate, $toDate);
} else {
    $stmt = $conn->prepare("
        SELECT user_id, student_name, date_log, time_log, typeoflog
        FROM final_attendance
        ORDER BY date_log DESC, time_log DESC
    ");
}

$stmt->execute();
$result = $stmt->get_result();

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=attendance_logs.xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "User ID\tStudent Name\tDate\tTime\tStatus\n";

while($row = $result->fetch_assoc()){
    // htmlspecialchars on output to prevent formula injection in Excel
    echo htmlspecialchars($row['user_id'])       . "\t"
       . htmlspecialchars($row['student_name'])  . "\t"
       . htmlspecialchars($row['date_log'])      . "\t"
       . htmlspecialchars($row['time_log'])      . "\t"
       . htmlspecialchars($row['typeoflog'])     . "\n";
}

$stmt->close();
exit;
