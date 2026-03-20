<?php
session_start();
include 'config.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

// Sanitize date inputs — strip anything that isn't a digit or dash
$fromDate = isset($_GET['from_date']) ? preg_replace('/[^0-9\-]/', '', $_GET['from_date']) : '';
$toDate   = isset($_GET['to_date'])   ? preg_replace('/[^0-9\-]/', '', $_GET['to_date'])   : '';

if(empty($fromDate) || empty($toDate)){
    die("Please select a date range.");
}

// Prepared statement — prevents SQL injection
$stmt = $conn->prepare("
    SELECT student_name, date_log, time_log, typeoflog
    FROM final_attendance
    WHERE date_log BETWEEN ? AND ?
    ORDER BY student_name ASC, date_log ASC, time_log ASC
");
$stmt->bind_param("ss", $fromDate, $toDate);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while($row = $result->fetch_assoc()){
    $data[$row['student_name']][$row['date_log']][] = $row;
}
$stmt->close();

function timeDiffHours($start, $end){
    return ($start && $end) ? (strtotime($end) - strtotime($start)) : 0;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Daily Time Record</title>
<style>
body{
    font-family:Arial;
    padding:30px;
}
.header{
    text-align:center;
}
.logo{
    width:80px;
}
.student-section{
    page-break-after:always;
    margin-bottom:50px;
}
table{
    width:100%;
    border-collapse:collapse;
    margin-top:20px;
}
th,td{
    border:1px solid black;
    padding:6px;
    text-align:center;
    font-size:12px;
}
th{
    background:#f0f0f0;
}
.signature{
    margin-top:40px;
    display:flex;
    justify-content:space-between;
}
.signature div{
    text-align:center;
    width:45%;
}
.print-btn{
    margin-bottom:20px;
}
@media print{
    .print-btn{display:none;}
    .student-section:last-child{page-break-after:auto;}
}
</style>
</head>
<body>

<button class="print-btn" onclick="window.print()">Print Now</button>

<?php foreach($data as $student => $dates): ?>

<div class="student-section">

<div class="header">
    <img src="images/1.png" class="logo"><br>
    <strong>GG EZ Sample School</strong><br>
    <h2>DAILY TIME RECORD</h2>
</div>

<p><strong>Name:</strong> <?= htmlspecialchars($student) ?></p>
<p><strong>Period Covered:</strong> <?= htmlspecialchars($fromDate) ?> to <?= htmlspecialchars($toDate) ?></p>

<table>
<tr>
    <th>Date</th><th>Day</th>
    <th>AM In</th><th>AM Out</th>
    <th>PM In</th><th>PM Out</th>
    <th>Daily Hours</th><th>Late (mins)</th>
    <th>Undertime (mins)</th><th>Remarks</th>
</tr>

<?php
$monthlySeconds = 0;
$totalLate      = 0;
$totalUndertime = 0;

foreach($dates as $date => $logs){
    $amIn = $amOut = $pmIn = $pmOut = '';

    foreach($logs as $log){
        $time = $log['time_log'];
        $type = strtoupper($log['typeoflog']);
        $hour = date("H", strtotime($time));

        if($hour < 12  && $type == "LOGIN"  && !$amIn)  $amIn  = $time;
        elseif($hour < 12  && $type == "LOGOUT" && !$amOut) $amOut = $time;
        elseif($hour >= 12 && $type == "LOGIN"  && !$pmIn)  $pmIn  = $time;
        elseif($hour >= 12 && $type == "LOGOUT" && !$pmOut) $pmOut = $time;
    }

    $dailySeconds    = timeDiffHours($amIn, $amOut) + timeDiffHours($pmIn, $pmOut);
    $monthlySeconds += $dailySeconds;
    $dailyHours      = $dailySeconds ? round($dailySeconds / 3600, 2) : '';

    $lateMinutes = 0;
    if($amIn && strtotime($amIn) > strtotime("08:00:00")){
        $lateMinutes  = round((strtotime($amIn) - strtotime("08:00:00")) / 60);
        $totalLate   += $lateMinutes;
    }

    $undertimeMinutes = 0;
    if($pmOut && strtotime($pmOut) < strtotime("17:00:00")){
        $undertimeMinutes  = round((strtotime("17:00:00") - strtotime($pmOut)) / 60);
        $totalUndertime   += $undertimeMinutes;
    }

    $remarks = '';
    if($lateMinutes > 0)      $remarks = "Late";
    elseif($undertimeMinutes > 0) $remarks = "Undertime";

    echo "<tr>
        <td>" . htmlspecialchars($date) . "</td>
        <td>" . date('l', strtotime($date)) . "</td>
        <td>" . htmlspecialchars($amIn)  . "</td>
        <td>" . htmlspecialchars($amOut) . "</td>
        <td>" . htmlspecialchars($pmIn)  . "</td>
        <td>" . htmlspecialchars($pmOut) . "</td>
        <td>$dailyHours</td>
        <td>$lateMinutes</td>
        <td>$undertimeMinutes</td>
        <td>$remarks</td>
    </tr>";
}
?>

<tr>
    <th colspan="6">MONTHLY TOTAL</th>
    <th><?= round($monthlySeconds / 3600, 2) ?></th>
    <th><?= $totalLate ?></th>
    <th><?= $totalUndertime ?></th>
    <th></th>
</tr>
</table>

<div class="signature">
    <div>___________________________<br>Intern Signature</div>
    <div>___________________________<br>Supervisor Signature</div>
</div>

</div>

<?php endforeach; ?>

</body>
</html>
