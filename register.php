<?php
include 'db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Formdan gelen ayrı ayrı veriler
    $ad = $_POST['first_name'];
    $soyad = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone']; // Telefonu aldık
    $password = $_POST['password'];
    
    // Veritabanı için Ad ve Soyadı birleştiriyoruz (Örn: Enes Demiryürek)
    $full_username = $ad . " " . $soyad;

    // E-posta kontrolü
    $check = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $check);

    if (mysqli_num_rows($result) > 0) {
        $message = "⚠️ Bu e-posta adresi zaten kayıtlı!";
    } else {
        // NOT: Telefon numarasını veritabanında sütun olmadığı için şimdilik kaydetmiyoruz.
        // Ama görsel olarak formda duruyor. İleride 'phone' sütunu açarsan buraya eklersin.
        
        $sql = "INSERT INTO users (username, email, phone, password, role) VALUES ('$full_username', '$email', '$phone', '$password', 'user')";
        
        if (mysqli_query($conn, $sql)) {
            echo "<script>alert('✅ Kayıt Başarılı! Aramıza hoşgeldin.'); window.location.href='login.php';</script>";
        } else {
            $message = "Hata: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ücretsiz Kayıt Ol | GYM</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="blue-register-body">

    <div class="split-card">
        
        <div class="form-side">
            <div class="form-header">
                <h2>Ücretsiz Başla</h2>
                <p>Kredi kartı gerekmez, hemen spora başla.</p>
            </div>

            <?php if($message) echo "<p style='color:red; text-align:center; margin-bottom:10px;'>$message</p>"; ?>

            <form action="" method="POST">
                
                <div class="split-inputs">
                    <div class="input-group">
                        <label>Adınız</label>
                        <input type="text" name="first_name" class="blue-input" required>
                    </div>
                    <div class="input-group">
                        <label>Soyadınız</label>
                        <input type="text" name="last_name" class="blue-input" required>
                    </div>
                </div>

                <div class="input-group">
                    <label>E-posta Adresi</label>
                    <input type="email" name="email" class="blue-input" required>
                </div>

                <div class="input-group">
                    <label>Telefon Numarası</label>
                    <input type="text" name="phone" class="blue-input" placeholder="0555 555 55 55" required>
                </div>

                <div class="input-group">
                    <label>Şifre</label>
                    <input type="password" name="password" class="blue-input" required>
                </div>

                <button type="submit" class="btn-blue">Devam Et</button>
            </form>

            <div class="back-link">
                Zaten hesabın var mı? <a href="login.php">Giriş Yap</a>
            </div>
            <div class="back-link" style="margin-top:10px;">
                <a href="index.php" style="color:#999; font-weight:normal;">← Anasayfaya Dön</a>
            </div>
        </div>

        <div class="image-side">
            <div class="image-overlay">
                <div class="testimonial-stars">★★★★★</div>
                <p class="testimonial-text">"Bu uygulamayı kullanmaya başladığımdan beri antrenmanlarımı asla kaçırmıyorum. Hocalar çok ilgili ve sistem harika çalışıyor!"</p>
                <p class="testimonial-author">Mert Yılmaz<br><small>Fitness Üyesi</small></p>
            </div>
        </div>

    </div>

</body>
</html>