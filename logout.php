<?php
session_start();
session_destroy(); // Tüm oturum bilgilerini siler (Kimliği alır)
header("Location: login.php"); // Giriş sayfasına geri atar
exit;
?>