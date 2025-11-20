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
    
    $update_sql = "UPDATE users SET username='$new_username', email='$new_email' WHERE id=$user_id";
    
    if (mysqli_query($conn, $update_sql)) {
        $message = "✅ Bilgilerin güncellendi!";
        $_SESSION['username'] = $new_username;
    } else {
        $message = "❌ Hata: " . mysqli_error($conn);
    }
}

// Kullanıcı Bilgileri
$user_sql = "SELECT * FROM users WHERE id = $user_id";
$user_result = mysqli_query($conn, $user_sql);
$user_row = mysqli_fetch_assoc($user_result);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Profilim | GYM</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { background-color: #f4f6f8; }
        
        .profile-container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
        }

        /* HEADER */
        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .profile-title h1 { font-size: 1.5rem; color: #333; margin-bottom: 5px; display: flex; align-items: center; gap: 10px; }
        .profile-actions a {
            text-decoration: none;
            font-weight: 600;
            margin-left: 10px;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: 0.3s;
        }
        .btn-home { background: #e2e6ea; color: #333; }
        .btn-home:hover { background: #dbe2e8; }
        .btn-logout { background: #ffebee; color: #c62828; }
        .btn-logout:hover { background: #ffcdd2; }

        /* GRID YAPISI (Sol: Form, Sağ: Dersler) */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 2fr; /* Sol 1 birim, Sağ 2 birim */
            gap: 30px;
        }

        /* SOL KART: BİLGİLER */
        .info-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            height: fit-content;
        }
        .avatar-circle {
            width: 80px; height: 80px;
            background: #e3f2fd; color: #2a5298;
            border-radius: 50%;
            display: flex; justify-content: center; align-items: center;
            font-size: 2rem; margin: 0 auto 20px auto;
        }
        .info-card h3 { text-align: center; margin-bottom: 20px; color: #333; }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #666; font-size: 0.9rem; font-weight: 600; }
        .form-group input {
            width: 100%; padding: 12px;
            border: 1px solid #eee; border-radius: 8px;
            font-size: 1rem; transition: 0.3s; outline: none;
        }
        .form-group input:focus { border-color: #2a5298; }
        
        .btn-update {
            width: 100%; padding: 12px;
            background: #2a5298; color: white;
            border: none; border-radius: 8px;
            font-weight: bold; cursor: pointer; transition: 0.3s;
        }
        .btn-update:hover { background: #1e3c72; }

        /* SAĞ KART: DERSLERİM */
        .classes-section h2 { color: #333; margin-bottom: 20px; font-size: 1.4rem; }
        
        .my-class-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            margin-bottom: 20px;
            border-left: 5px solid #2a5298; /* Sol şerit */
            display: flex;
            flex-direction: column;
            transition: transform 0.2s;
        }
        .my-class-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.08); }

        .class-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .class-title { font-size: 1.2rem; font-weight: bold; color: #333; }
        .class-date { background: #f5f5f5; padding: 5px 10px; border-radius: 20px; font-size: 0.85rem; color: #555; }

        .class-details { color: #666; margin-bottom: 15px; font-size: 0.95rem; }
        
        .class-actions {
            display: flex; gap: 10px; align-items: center; margin-top: 10px; padding-top: 15px; border-top: 1px solid #eee;
        }
        
        .btn-zoom {
            flex: 1; text-align: center; text-decoration: none;
            background: #e3f2fd; color: #1565c0; padding: 10px;
            border-radius: 6px; font-weight: 600; font-size: 0.9rem; transition: 0.3s;
        }
        .btn-zoom:hover { background: #bbdefb; }

        .btn-cancel {
            text-decoration: none; color: #d32f2f; padding: 10px 15px;
            border: 1px solid #d32f2f; border-radius: 6px;
            font-size: 0.9rem; font-weight: 600; transition: 0.3s;
        }
        .btn-cancel:hover { background: #d32f2f; color: white; }

        /* Mobil Uyum */
        @media (max-width: 768px) {
            .dashboard-grid { grid-template-columns: 1fr; }
            .profile-header { flex-direction: column; gap: 15px; text-align: center; }
        }
    </style>
</head>
<body>

<div class="profile-container">

    <div class="profile-header">
        <div class="profile-title">
            <h1>👤 Hesabım</h1>
            <p style="color:#666; font-size:0.9rem;">Hoşgeldin, <strong><?php echo $user_row['username']; ?></strong></p>
        </div>
        <div class="profile-actions">
            <a href="index.php" class="btn-home">🏠 Anasayfa</a>
            <a href="logout.php" class="btn-logout">Çıkış Yap</a>
        </div>
    </div>

    <div class="dashboard-grid">
        
        <div class="info-card">
            <div class="avatar-circle">✏️</div>
            <h3>Profil Bilgileri</h3>
            
            <?php if($message) echo "<p style='color:green; font-weight:bold; text-align:center; margin-bottom:10px;'>$message</p>"; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label>Kullanıcı Adı</label>
                    <input type="text" name="username" value="<?php echo $user_row['username']; ?>" required>
                </div>
                <div class="form-group">
                    <label>E-posta Adresi</label>
                    <input type="email" name="email" value="<?php echo $user_row['email']; ?>" required>
                </div>
                <button type="submit" class="btn-update">Bilgileri Güncelle</button>
            </form>
        </div>

        <div class="classes-section">
            <h2>🎫 Yaklaşan Derslerim</h2>

            <?php
            $my_classes_sql = "SELECT classes.*, bookings.booking_date, bookings.id as booking_id 
                               FROM bookings 
                               JOIN classes ON bookings.class_id = classes.id 
                               WHERE bookings.user_id = $user_id
                               ORDER BY classes.date_time ASC";
            
            $result = mysqli_query($conn, $my_classes_sql);

            if (mysqli_num_rows($result) > 0) {
                while($row = mysqli_fetch_assoc($result)) {
                    echo '<div class="my-class-card">';
                    
                    // Üst Kısım: Başlık ve Tarih
                    echo '<div class="class-header">';
                    echo '<span class="class-title">' . $row["title"] . ' (' . $row["class_type"] . ')</span>';
                    echo '<span class="class-date">📅 ' . date("d.m.Y H:i", strtotime($row["date_time"])) . '</span>';
                    echo '</div>';

                    // Orta Kısım: Eğitmen
                    echo '<div class="class-details">';
                    echo '🧘‍♂️ Eğitmen: <strong>' . $row["trainer_name"] . '</strong>';
                    echo '</div>';

                    // Alt Kısım: Butonlar (Zoom ve İptal)
                    echo '<div class="class-actions">';
                    echo '<a href="' . $row["video_link"] . '" target="_blank" class="btn-zoom">🎥 Derse Bağlan (Zoom)</a>';
                    echo '<a href="cancel_booking.php?id=' . $row["booking_id"] . '" class="btn-cancel" onclick="return confirm(\'İptal etmek istediğine emin misin?\')">İptal Et</a>';
                    echo '</div>';

                    echo '</div>'; // my-class-card bitiş
                }
            } else {
                echo '<div style="text-align:center; padding:40px; background:white; border-radius:15px; color:#777;">
                        <p style="font-size:1.2rem;">Henüz hiç ders rezerve etmediniz.</p>
                        <a href="index.php" style="display:inline-block; margin-top:10px; color:#2a5298; font-weight:bold; text-decoration:none;">👉 Dersleri Keşfet</a>
                      </div>';
            }
            ?>
        </div>

    </div>

</div>

</body>
</html>