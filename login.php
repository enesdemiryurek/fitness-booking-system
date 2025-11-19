<?php
session_start(); // DİKKAT: Session işlemi için bu satır EN ÜSTTE olmalı.
include 'db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Veritabanında bu email ve şifreye sahip biri var mı?
    $sql = "SELECT * FROM users WHERE email = '$email' AND password = '$password'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        // Giriş Başarılı!
        $row = mysqli_fetch_assoc($result);
        
        // SESSION'a bilgileri atıyoruz (Kimlik kartını veriyoruz)
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['role'] = $row['role']; // Öğrenci mi Admin mi?

        // Anasayfaya yönlendir
        header("Location: index.php");
        exit;
    } else {
        $message = "Hatalı e-posta veya şifre!";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Giriş Yap</title>
    <style>
        body { font-family: sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .form-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 300px; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box;}
        button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .message { color: red; margin-bottom: 10px; text-align: center; }
    </style>
</head>
<body>

<div class="form-container">
    <h2 style="text-align:center;">Giriş Yap</h2>
    
    <?php if($message != "") { echo "<div class='message'>$message</div>"; } ?>

    <form action="" method="POST">
        <input type="email" name="email" placeholder="E-posta Adresi" required>
        <input type="password" name="password" placeholder="Şifre" required>
        <button type="submit">Giriş Yap</button>
    </form>
    <p style="text-align:center;">Hesabın yok mu? <a href="register.php">Kayıt Ol</a></p>
    <p style="text-align:center;"><a href="index.php">Anasayfaya Dön</a></p>
</div>

</body>
</html>