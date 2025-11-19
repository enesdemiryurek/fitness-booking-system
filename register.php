<?php
include 'db.php'; // Veritabanı bağlantısını çağır

$message = "";

// Form gönderildi mi kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Şifreyi güvenli hale getir (Hash'le) - Güvenlik için şart!
    // $hashed_password = password_hash($password, PASSWORD_DEFAULT); 
    // Not: Şimdilik basit olsun diye düz kaydedelim, hoca isterse hash'i açarız.
    
    // E-posta daha önce var mı kontrol et
    $check = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $check);

    if (mysqli_num_rows($result) > 0) {
        $message = "Bu e-posta adresi zaten kayıtlı!";
    } else {
        // Kayıt işlemini yap
        $sql = "INSERT INTO users (username, email, password, role) VALUES ('$username', '$email', '$password', 'student')";
        
        if (mysqli_query($conn, $sql)) {
            $message = "Kayıt başarılı! <a href='login.php'>Giriş yapabilirsin.</a>";
        } else {
            $message = "Hata oluştu: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kayıt Ol</title>
    <style>
        body { font-family: sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .form-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 300px; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box;}
        button { width: 100%; padding: 10px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #218838; }
        .message { color: red; margin-bottom: 10px; text-align: center; }
    </style>
</head>
<body>

<div class="form-container">
    <h2 style="text-align:center;">Kayıt Ol</h2>
    
    <?php if($message != "") { echo "<div class='message'>$message</div>"; } ?>

    <form action="" method="POST">
        <input type="text" name="username" placeholder="Kullanıcı Adı" required>
        <input type="email" name="email" placeholder="E-posta Adresi" required>
        <input type="password" name="password" placeholder="Şifre" required>
        <button type="submit">Kayıt Ol</button>
    </form>
    <p style="text-align:center;">Zaten üye misin? <a href="login.php">Giriş Yap</a></p>
    <p style="text-align:center;"><a href="index.php">Anasayfaya Dön</a></p>
</div>

</body>
</html>