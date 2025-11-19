<?php
// Veritabanı bağlantı dosyamızı çağırıyoruz
include 'db.php';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fitness Rezervasyon Sistemi</title>
    <style>
        /* Basit bir tasarım yapalım */
        body { font-family: sans-serif; background-color: #f4f4f4; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .header { text-align: center; background: #333; color: white; padding: 20px; border-radius: 10px; }
        .class-card { background: white; padding: 20px; margin-top: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .btn { display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; }
        .stok { font-weight: bold; color: #d9534f; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>🏋️‍♂️ Online Fitness Dersleri</h1>
        <p>Hoşgeldiniz! Hemen bir ders rezerve edin.</p>
        <a href="login.php" style="color: yellow;">Giriş Yap</a> | 
        <a href="register.php" style="color: yellow;">Kayıt Ol</a>
    </div>

    <h2>📅 Yaklaşan Dersler</h2>

    <?php
    // Veritabanından dersleri çekme kodu
    $sql = "SELECT * FROM classes";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        // Her bir ders için döngüye gir
        while($row = mysqli_fetch_assoc($result)) {
            echo '<div class="class-card">';
            echo '<h3>' . $row["title"] . ' <small>(' . $row["class_type"] . ')</small></h3>';
            echo '<p><strong>Eğitmen:</strong> ' . $row["trainer_name"] . '</p>';
            echo '<p><strong>Tarih:</strong> ' . $row["date_time"] . '</p>';
            echo '<p>' . $row["description"] . '</p>';
            
            // Stok Durumu (Hocanın İstediği Kritik Yer)
            echo '<p class="stok">Kalan Kontenjan: ' . $row["capacity"] . ' Kişi</p>';
            
            echo '<a href="#" class="btn">Rezerve Et</a>';
            echo '</div>';
        }
    } else {
        echo "<p>Şu an aktif ders bulunmuyor.</p>";
    }
    ?>

</div>

</body>
</html>