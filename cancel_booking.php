<?php
session_start();
include 'db.php';

// Güvenlik: Giriş yapmamışsa işlem yapamaz
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if (isset($_GET['id'])) {
    $booking_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    // 1. Önce bu rezervasyonun HANGİ DERSE ait olduğunu bulalım (Stoku artırmak için lazım)
    // Ayrıca güvenlik için bu rezervasyonun gerçekten bu kullanıcıya mı ait olduğuna bakıyoruz.
    $find_sql = "SELECT class_id FROM bookings WHERE id = $booking_id AND user_id = $user_id";
    $result = mysqli_query($conn, $find_sql);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $class_id = $row['class_id'];

        // 2. Rezervasyonu Sil (bookings tablosundan)
        $delete_sql = "DELETE FROM bookings WHERE id = $booking_id";
        mysqli_query($conn, $delete_sql);

        // 3. KRİTİK NOKTA: Stok Sayısını 1 Artır (+1)
        $update_sql = "UPDATE classes SET capacity = capacity + 1 WHERE id = $class_id";
        mysqli_query($conn, $update_sql);

        // Başarılı, profile geri dön
        echo "<script>
            alert('✅ Rezervasyonunuz başarıyla iptal edildi.');
            window.location.href = 'profile.php';
        </script>";
    } else {
        echo "Hata: Böyle bir rezervasyon bulunamadı veya size ait değil.";
    }
} else {
    header("Location: profile.php");
}
?>