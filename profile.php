<?php
session_start();
include 'db.php';

// Güvenlik: Giriş yapmayan giremez
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";

// --- PROFİL GÜNCELLEME İŞLEMİ ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_username = $_POST['username'];
    $new_email = $_POST['email'];
    
    // Basit güncelleme sorgusu
    $update_sql = "UPDATE users SET username='$new_username', email='$new_email' WHERE id=$user_id";
    
    if (mysqli_query($conn, $update_sql)) {
        $message = "✅ Profil başarıyla güncellendi!";
        // Session bilgisini de tazeleyelim
        $_SESSION['username'] = $new_username;
    } else {
        $message = "❌ Hata: " . mysqli_error($conn);
    }
}

// Kullanıcının güncel bilgilerini çek
$user_sql = "SELECT * FROM users WHERE id = $user_id";
$user_result = mysqli_query($conn, $user_sql);
$user_row = mysqli_fetch_assoc($user_result);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Profilim</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <div class="header">
        <h1>👤 Profilim</h1>
        <a href="index.php" style="color: yellow;">Anasayfaya Dön</a> | 
        <a href="logout.php" style="color: #ff6b6b;">Çıkış Yap</a>
    </div>

    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
        
        <div class="class-card" style="flex: 1; min-width: 300px;">
            <h3>Bilgilerimi Güncelle</h3>
            <?php if($message) echo "<p style='color:green; font-weight:bold;'>$message</p>"; ?>
            
            <form action="" method="POST">
                <label>Kullanıcı Adı:</label>
                <input type="text" name="username" value="<?php echo $user_row['username']; ?>" required>
                
                <label>E-posta:</label>
                <input type="email" name="email" value="<?php echo $user_row['email']; ?>" required>
                
                <button type="submit" class="btn">Güncelle</button>
            </form>
        </div>

        <div style="flex: 2; min-width: 300px;">
            <h2>📅 Rezerve Ettiğim Dersler</h2>
            
            <?php
            // GÜNCELLEME BURADA YAPILDI:
            // 'bookings.id as booking_id' ekledik. Hangi rezervasyonu sileceğimizi bilmek için.
            $my_classes_sql = "SELECT classes.*, bookings.booking_date, bookings.id as booking_id 
                               FROM bookings 
                               JOIN classes ON bookings.class_id = classes.id 
                               WHERE bookings.user_id = $user_id";
            
            $result = mysqli_query($conn, $my_classes_sql);

            if (mysqli_num_rows($result) > 0) {
                while($row = mysqli_fetch_assoc($result)) {
                    echo '<div class="class-card" style="border-left: 5px solid #2a5298;">';
                    echo '<h3>' . $row["title"] . '</h3>';
                    echo '<p><strong>Tarih:</strong> ' . $row["date_time"] . '</p>';
                    echo '<p><strong>Eğitmen:</strong> ' . $row["trainer_name"] . '</p>';
                    
                    // Canlı Yayın Linki
                    echo '<div style="background:#eef; padding:10px; border-radius:5px; margin-top:10px; margin-bottom:10px;">';
                    echo '🔴 <strong>Canlı Yayın Linki:</strong> <br>';
                    echo '<a href="' . $row["video_link"] . '" target="_blank" style="color:blue;">Derse Bağlanmak İçin Tıkla (Zoom/Youtube)</a>';
                    echo '</div>';
                    
                    // GÜNCELLEME BURADA YAPILDI: İPTAL BUTONU EKLENDİ
                    echo '<a href="cancel_booking.php?id=' . $row["booking_id"] . '" class="btn" style="background-color:#dc3545;" onclick="return confirm(\'Bu rezervasyonu iptal etmek istediğine emin misin?\')">Rezervasyonu İptal Et</a>';
                    
                    echo '</div>';
                }
            } else {
                echo '<div class="class-card"><p>Henüz hiç ders almadınız. <a href="index.php">Hemen bir ders seç!</a></p></div>';
            }
            ?>
        </div>
    
    </div>
</div>

</body>
</html>