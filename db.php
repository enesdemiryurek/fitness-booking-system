<?php
date_default_timezone_set('Europe/Istanbul');
setlocale(LC_TIME, 'tr_TR.UTF-8', 'tr_TR', 'tr', 'turkish');
// -----------------------------------------

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "fitness_db";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("COnnecting Error: " . mysqli_connect_error());
}


mysqli_set_charset($conn, "utf8");
?>