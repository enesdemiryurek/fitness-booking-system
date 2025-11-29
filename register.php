<?php
session_start();
include 'db.php';
$page_title = "Ücretsiz Kayıt Ol | GYM";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Formdan gelen veriler
    $ad = $_POST['first_name'];
    $soyad = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone']; 
    $password = $_POST['password'];
    
    // YENİ EKLENENLER: Yaş ve Cinsiyet
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    
    // Ad ve Soyadı birleştirip username yapıyoruz
    $full_username = $ad . " " . $soyad;

    // E-posta kontrolü
    $check = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $check);

    if (mysqli_num_rows($result) > 0) {
        $message = "⚠️ Bu e-posta adresi zaten kayıtlı!";
    } else {
        // VERİTABANI KAYDI (Yaş ve Cinsiyet Sütunları Eklendi)
        $sql = "INSERT INTO users (username, email, phone, age, gender, password, role) 
                VALUES ('$full_username', '$email', '$phone', '$age', '$gender', '$password', 'user')";
        
        if (mysqli_query($conn, $sql)) {
            echo "<script>alert('✅ Kayıt Başarılı! Aramıza hoşgeldin.'); window.location.href='login.php';</script>";
        } else {
            $message = "Hata: " . mysqli_error($conn);
        }
    }
}

include 'header.php';
?>

    <div class="split-card" style="margin: 40px auto; max-width: 900px;">
        
        <!-- SOL TARAF: FORM -->
        <div class="form-side">
            <div class="form-header">
                <h2>Ücretsiz Başla</h2>
                <p>Kredi kartı gerekmez, hemen spora başla.</p>
            </div>

            <?php if($message) echo "<p style='color:red; text-align:center; margin-bottom:10px;'>$message</p>"; ?>

            <form action="" method="POST">
                
                <!-- Ad ve Soyad (Yan Yana) -->
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

                <!-- E-posta -->
                <div class="input-group">
                    <label>E-posta Adresi</label>
                    <input type="email" name="email" class="blue-input" required>
                </div>

                <!-- Telefon -->
                <div class="input-group">
                    <label>Telefon Numarası</label>
                    <input type="text" name="phone" class="blue-input" placeholder="0555 555 55 55" required>
                </div>

                <!-- YENİ: Yaş ve Cinsiyet (Yan Yana) -->
                <div class="split-inputs">
                    <div class="input-group">
                        <label>Yaşınız</label>
                        <input type="number" name="age" class="blue-input" placeholder="22" required>
                    </div>
                    <div class="input-group">
                        <label>Cinsiyet</label>
                        <select name="gender" class="blue-input" style="background-color:white;" required>
                            <option value="" disabled selected>Seçiniz</option>
                            <option value="Erkek">Erkek</option>
                            <option value="Kadın">Kadın</option>
                            <option value="Belirtmek İstemiyorum">Diğer</option>
                        </select>
                    </div>
                </div>

                <!-- Şifre -->
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

        <!-- SAĞ TARAF: RESİM -->
        <div class="image-side">
            <div class="image-overlay">
                <div class="testimonial-stars">★★★★★</div>
                <p class="testimonial-text">"Bu uygulamayı kullanmaya başladığımdan beri antrenmanlarımı asla kaçırmıyorum. Hocalar çok ilgili ve sistem harika çalışıyor!"</p>
                <p class="testimonial-author">Mert Yılmaz<br><small>Fitness Üyesi</small></p>
            </div>
        </div>

    </div>

    <?php include 'footer.php'; ?>    <?php include 'footer.php'; ?>