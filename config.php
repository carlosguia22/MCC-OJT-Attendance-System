<?php
$conn = new mysqli("localhost","root","","attendance");

if($conn->connect_error){
    die("DB Error");
}
// liman ka balik-balik
?>