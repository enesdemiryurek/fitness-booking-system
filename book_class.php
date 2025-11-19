<?php
session_start();
include 'db.php';

// 1. GÜVENLİK KONTROLÜ: Kullanıcı giriş yapmış mı?
if (!isset($_SESSION['user_id'])) {
    die("Hata: Bu işlemi yapmak için giriş yapmalısınız.");
}

if (isset($_GET['id'])) {
    $class_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    // --- YENİ EKLENEN KISIM BAŞLANGICI ---
    // 2. KONTROL: Bu kullanıcı bu dersi daha önce almış mı?
    $duplicate_check_sql = "SELECT * FROM bookings WHERE user_id = $user_id AND class_id = $class_id";
    $duplicate_result = mysqli_query($conn, $duplicate_check_sql);

    if (mysqli_num_rows($duplicate_result) > 0) {
        // Kullanıcı zaten bu derse kayıtlı!
        echo "<script>
            alert('⚠️ Dikkat: Bu derse zaten kaydınız var! Tekrar alamazsınız.');
            window.location.href = 'index.php'; // Anasayfaya geri at
        </script>";
        exit; // Kodun geri kalanını çalıştırma, burada bitir.
    }
    // --- YENİ EKLENEN KISIM BİTİŞİ ---


    // 3. STOK KONTROLÜ: Kontenjan var mı?
    $check_sql = "SELECT capacity FROM classes WHERE id = $class_id";
    $result = mysqli_query($conn, $check_sql);
    $row = mysqli_fetch_assoc($result);

    if ($row['capacity'] > 0) {
        // Kontenjan var! 
        
        // A. Kaydı oluştur
        $insert_sql = "INSERT INTO bookings (user_id, class_id) VALUES ($user_id, $class_id)";
        
        if (mysqli_query($conn, $insert_sql)) {
            // B. Stoktan 1 düş
            $update_sql = "UPDATE classes SET capacity = capacity - 1 WHERE id = $class_id";
            mysqli_query($conn, $update_sql);

            echo "<script>
                alert('✅ Tebrikler! Ders başarıyla rezerve edildi.');
                window.location.href = 'profile.php';
            </script>";
        } else {
            echo "Hata: " . mysqli_error($conn);
        }

    } else {
        // Yer kalmamış
        echo "<script>
            alert('😔 Üzgünüz, bu dersin kontenjanı dolmuş!');
            window.location.href = 'index.php';
        </script>";
    }

} else {
    header("Location: index.php");
}
?>