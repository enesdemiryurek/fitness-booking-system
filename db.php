<?php
// --- SİHİRLİ SATIR: TÜRKİYE SAAT AYARI ---
date_default_timezone_set('Europe/Istanbul');
setlocale(LC_TIME, 'tr_TR.UTF-8', 'tr_TR', 'tr', 'turkish');
// -----------------------------------------

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "fitness_db";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Bağlantı hatası: " . mysqli_connect_error());
}

// Türkçe karakter sorunu olmasın diye
mysqli_set_charset($conn, "utf8");
?>