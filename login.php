<?php
session_start();
include 'db.php';
$page_title = "Giriş Yap | GYM";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Veritabanı kontrolü
    $sql = "SELECT * FROM users WHERE email = '$email' AND password = '$password'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        // Giriş Başarılı!
        $row = mysqli_fetch_assoc($result);
        
        // Kimlik kartını (Session) doldur
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['role'] = $row['role'];

        // Anasayfaya yolla
        header("Location: index.php");
        exit;
    } else {
        $message = "Hatalı e-posta veya şifre!";
    }
}

include 'header.php';
?>

    <div class="split-card" style=" margin: 40px auto; max-width: 900px;">
        
        <div class="form-side">
            <div class="form-header">
                <h2>Tekrar Hoşgeldin!</h2>
                <p>Hesabına giriş yap ve spora kaldığın yerden devam et.</p>
            </div>

            <?php if($message) echo "<p style='color:red; text-align:center; background:#ffebee; padding:10px; border-radius:5px; margin-bottom:15px;'>$message</p>"; ?>

            <form action="" method="POST">
                
                <div class="input-group">
                    <label>E-posta Adresi</label>
                    <input type="email" name="email" class="blue-input" placeholder="ornek@mail.com" required>
                </div>

                <div class="input-group">
                    <label>Şifre</label>
                    <input type="password" name="password" class="blue-input" placeholder="******" required>
                </div>

                <button type="submit" class="btn-blue">Giriş Yap</button>
            </form>

            <div class="back-link">
                <a href="forgot_password.php" style="color:#ff6b6b; font-weight:bold; font-size:14px;">Şifreni Unuttun Mu?</a>
            </div>

            <div class="back-link">
                Hesabın yok mu? <a href="register.php">Hemen Kayıt Ol</a>
            </div>
            <div class="back-link" style="margin-top:10px;">
                <a href="index.php" style="color:#999; font-weight:normal;">← Anasayfaya Dön</a>
            </div>
        </div>

        <div class="image-side" style="background-image: url('https://images.unsplash.com/photo-1599058945522-28d584b6f0ff?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');">
            <div class="image-overlay">
                <div class="testimonial-stars">★★★★★</div>
                <p class="testimonial-text">"Süreklilik, başarının anahtarıdır. Her gün %1 daha iyi olmak için buradayız."</p>
                <p class="testimonial-author">GYM Ekibi</p>
            </div>
        </div>

    </div>

    <?php include 'footer.php'; ?>