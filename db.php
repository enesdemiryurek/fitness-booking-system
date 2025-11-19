<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "fitness_db"; 


$conn = mysqli_connect($host, $user, $pass, $dbname);


if (!$conn) {
    die("Bağlantı hatası: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8");
?>