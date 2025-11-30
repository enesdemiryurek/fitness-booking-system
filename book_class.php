<?php
session_start();
include 'db.php';
include 'notification_handler.php';

// 1. GÜVENLİK KONTROLÜ: Kullanıcı giriş yapmış mı?
if (!isset($_SESSION['user_id'])) {
    die("Error: You must be logged in to perform this action.");
}

if (isset($_GET['id'])) {
    $class_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    // 2. KONTROL: Bu kullanıcı bu dersi daha önce almış mı?
    $duplicate_check_sql = "SELECT * FROM bookings WHERE user_id = $user_id AND class_id = $class_id";
    $duplicate_result = mysqli_query($conn, $duplicate_check_sql);

    if (mysqli_num_rows($duplicate_result) > 0) {
        // Kullanıcı zaten bu derse kayıtlı!
        echo "<script>
            alert('⚠️ Warning: You are already registered for this class! You cannot register again.');
            window.location.href = 'index.php';
        </script>";
        exit;
    }

    // 3. STOK KONTROLÜ: Kontenjan var mı?
    $check_sql = "SELECT capacity, title, date_time FROM classes WHERE id = $class_id";
    $result = mysqli_query($conn, $check_sql);
    $row = mysqli_fetch_assoc($result);

    if ($row['capacity'] > 0) {
        // Kontenjan var! Ödeme sayfasına yönlendir
        header("Location: process_payment.php?class_id=" . $class_id);
        exit;
    } else {
        // Yer kalmamış
        echo "<script>
            alert('😔 Sorry, this class is full!');
            window.location.href = 'index.php';
        </script>";
    }

} else {
    header("Location: index.php");
}
?>