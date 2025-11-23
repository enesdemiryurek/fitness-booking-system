<?php
session_start();
include 'db.php';

$message = "";
$message_type = "";
$valid_token = false;
$user_id = null;

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Token'ı veritabanından kontrol et
    $sql = "SELECT id, user_id FROM password_resets WHERE token = '$token' AND expires_at > NOW()";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $valid_token = true;
        $user_id = $row['user_id'];
        $reset_id = $row['id'];
    } else {
        $message = "Şifre sıfırlama linki geçersiz veya süresi dolmuş.";
        $message_type = "error";
    }
} else {
    $message = "Geçersiz istek.";
    $message_type = "error";
}

// Form gönderildiğinde
if ($_SERVER["REQUEST_METHOD"] == "POST" && $valid_token) {
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Şifreyi doğrula
    if (strlen($new_password) < 6) {
        $message = "Şifre en az 6 karakter olmalı.";
        $message_type = "error";
    } elseif ($new_password !== $confirm_password) {
        $message = "Şifreler eşleşmiyor.";
        $message_type = "error";
    } else {
        // Şifreyi güncelle
        $update_sql = "UPDATE users SET password = '$new_password' WHERE id = $user_id";
        
        if (mysqli_query($conn, $update_sql)) {
            // Token'ı sil (kullanıldı)
            $delete_sql = "DELETE FROM password_resets WHERE id = $reset_id";
            mysqli_query($conn, $delete_sql);

            $message = "Şifreniz başarıyla sıfırlandı! Şimdi giriş yapabilirsiniz.";
            $message_type = "success";
            $valid_token = false; // Formu gizle

            // 3 saniye sonra login sayfasına yönlendir
            header("refresh:3;url=login.php");
        } else {
            $message = "Şifre güncellenirken hata oluştu. Lütfen tekrar deneyin.";
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifremi Sıfırla | GYM</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .reset-password-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .reset-password-container h2 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
        }

        .reset-password-container p {
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

        .reset-password-container form input {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .reset-password-container form input:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.3);
        }

        .reset-password-container button {
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

        .reset-password-container button:hover {
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

        .password-requirements {
            background-color: #f0f0f0;
            padding: 12px;
            border-radius: 5px;
            font-size: 12px;
            color: #666;
            margin-bottom: 20px;
        }

        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
        }

        .password-requirements li {
            margin: 5px 0;
        }
    </style>
</head>
<body style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh;">

    <div class="reset-password-container">
        <h2>Şifremi Sıfırla</h2>

        <?php if($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
                <?php if($message_type == "success"): ?>
                    <p style="font-size: 12px; margin-top: 10px;">Giriş sayfasına yönlendiriliyorsunuz...</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if($valid_token): ?>
            <div class="password-requirements">
                <strong>Şifre Gereksinimleri:</strong>
                <ul>
                    <li>En az 6 karakter</li>
                    <li>Şifreler eşleşmeli</li>
                </ul>
            </div>

            <form method="POST" action="">
                <input 
                    type="password" 
                    name="password" 
                    placeholder="Yeni şifre" 
                    required
                >
                <input 
                    type="password" 
                    name="confirm_password" 
                    placeholder="Yeni şifreyi onayla" 
                    required
                >
                <button type="submit">Şifreyi Sıfırla</button>
            </form>

            <div class="back-link">
                <a href="login.php">Giriş sayfasına geri dön</a>
            </div>
        <?php else: ?>
            <div class="back-link" style="margin-top: 30px;">
                <a href="login.php">← Giriş sayfasına geri dön</a>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
