<?php
session_start();
include 'db.php';

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    // Email'in veritabanında olup olmadığını kontrol et
    $sql = "SELECT id FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $user_id = $row['id'];

        // Benzersiz token oluştur
        $token = bin2hex(random_bytes(32));
        
        // Token'ı 1 saat geçerli olacak şekilde ayarla
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Token'ı veritabanına kaydet
        $insert_sql = "INSERT INTO password_resets (user_id, token, expires_at) VALUES ($user_id, '$token', '$expires_at')";
        
        if (mysqli_query($conn, $insert_sql)) {
            // Şifre sıfırlama linki oluştur
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/fitness-booking-system/reset_password.php?token=" . $token;

            // Email gönder
            $subject = "GYM - Şifre Sıfırlama Talebi";
            $message_body = "
Merhaba,

Şifrenizi sıfırlamak için aşağıdaki linke tıklayın:

$reset_link

Bu link 1 saat içinde geçerlidir.

Eğer siz bu talebide bulunmadıysanız bu e-postayı görmezden gelebilirsiniz.

Saygılarımızla,
GYM Ekibi
            ";

            // Test Modu: Linki ekrana yazdır ve logla
            $message = "✓ Şifre sıfırlama linki oluşturuldu! Linki aşağıda görebilirsiniz.<br><br><strong>Şifre Sıfırlama Linki:</strong><br><a href='" . $reset_link . "' style='color:#4CAF50; font-weight:bold; word-break: break-all;'>" . $reset_link . "</a><br><br><small style='color:#999;'>Linki yeni sekmede açabilir veya kopyalayıp tarayıcı adres çubuğuna yapıştırabilirsiniz.</small>";
            $message_type = "success";
            
            // Veritabanına loglayalım
            $log_message = "Password reset requested for user $user_id (email: $email) on " . date('Y-m-d H:i:s') . "\nReset Link: $reset_link\nToken: $token\nExpires At: $expires_at\n" . str_repeat("-", 80) . "\n";
            file_put_contents('password_reset_log.txt', $log_message, FILE_APPEND);
        } else {
            $message = "Veritabanı hatası. Lütfen daha sonra tekrar deneyin.";
            $message_type = "error";
        }
    } else {
        $message = "Bu e-posta adresine kayıtlı bir hesap bulunamadı.";
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifremi Unuttum | GYM</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .forgot-password-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .forgot-password-container h2 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
        }

        .forgot-password-container p {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .message {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .forgot-password-container form input {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .forgot-password-container form input:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.3);
        }

        .forgot-password-container button {
            width: 100%;
            padding: 12px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }

        .forgot-password-container button:hover {
            background-color: #45a049;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #4CAF50;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh;">

    <div class="forgot-password-container">
        <h2>Şifremi Unuttum</h2>
        <p>E-posta adresinizi girin. Size şifre sıfırlama linki göndereceğiz.</p>

        <?php if($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input 
                type="email" 
                name="email" 
                placeholder="E-posta adresini gir" 
                required
            >
            <button type="submit">Şifre Sıfırlama Linki Gönder</button>
        </form>

        <div class="back-link">
            <a href="login.php">← Giriş sayfasına geri dön</a>
        </div>
    </div>

</body>
</html>
