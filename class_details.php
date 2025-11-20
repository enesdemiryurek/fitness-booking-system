<?php
session_start();
include 'db.php';

// Güvenlik
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";
$progress_message = "";

// --- 1. PROFİL GÜNCELLEME ---
if (isset($_POST['update_profile'])) {
    $new_username = $_POST['username'];
    $new_email    = $_POST['email'];
    $new_phone    = $_POST['phone'];
    $new_age      = $_POST['age'];
    $new_gender   = $_POST['gender'];
    
    $update_sql = "UPDATE users SET username='$new_username', email='$new_email', phone='$new_phone', age='$new_age', gender='$new_gender' WHERE id=$user_id";
    
    if (mysqli_query($conn, $update_sql)) {
        $message = "✅ Bilgiler güncellendi!";
        $_SESSION['username'] = $new_username;
    }
}

// --- 2. GELİŞİM VERİSİ EKLEME ---
if (isset($_POST['add_progress'])) {
    $weight = $_POST['weight'];
    $height = $_POST['height'];
    
    if($height > 0) {
        $height_m = $height / 100; 
        $bmi = $weight / ($height_m * $height_m);
        $bmi = number_format($bmi, 2); 
    } else { $bmi = 0; }

    $prog_sql = "INSERT INTO user_progress (user_id, weight, height, bmi) VALUES ($user_id, '$weight', $height, '$bmi')";
    
    if(mysqli_query($conn, $prog_sql)){
        $progress_message = "✅ Gelişim kaydedildi! BMI: $bmi";
    }
}

// --- 3. YORUM KAYDETME ---
if (isset($_POST['submit_review'])) {
    $class_id_review = $_POST['class_id'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];

    $check_rev = mysqli_query($conn, "SELECT * FROM reviews WHERE user_id=$user_id AND class_id=$class_id_review");
    
    if(mysqli_num_rows($check_rev) > 0) {
        echo "<script>alert('Bu derse zaten puan verdiniz!');</script>";
    } else {
        $ins_rev = "INSERT INTO reviews (user_id, class_id, rating, comment) VALUES ($user_id, $class_id_review, '$rating', '$comment')";
        if(mysqli_query($conn, $ins_rev)) {
            echo "<script>alert('✅ Yorumunuz kaydedildi!'); window.location.href='profile.php';</script>";
        }
    }
}

// Kullanıcı Bilgisi
$user_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));

// --- DERSLERİ AYRIŞTIRMA MANTIĞI ---
// Tüm dersleri çekip PHP tarafında 'Gelecek' ve 'Geçmiş' diye iki kutuya ayırıyoruz.
$sql = "SELECT classes.*, bookings.booking_date, bookings.id as booking_id 
        FROM bookings 
        JOIN classes ON bookings.class_id = classes.id 
        WHERE bookings.user_id = $user_id 
        ORDER BY classes.date_time ASC"; // Yaklaşanlar üstte olsun diye ASC

$result = mysqli_query($conn, $sql);

$upcoming_classes = [];
$past_classes = [];

if(mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        if(strtotime($row['date_time']) > time()) {
            $upcoming_classes[] = $row; // Gelecek kutusuna at
        } else {
            $past_classes[] = $row; // Geçmiş kutusuna at
        }
    }
}
// Geçmiş dersleri en yeniden en eskiye sıralayalım (Tersine çevir)
$past_classes = array_reverse($past_classes);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Profilim | GYM</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* DASHBOARD STİLLERİ */
        body { background-color: #f0f2f5; font-family: 'Poppins', sans-serif; }
        .dashboard-container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        
        /* Header */
        .dash-header {
            background: #185ADB; color: white; padding: 30px; border-radius: 15px;
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;
            box-shadow: 0 10px 20px rgba(24, 90, 219, 0.2);
        }
        .dash-title h1 { margin: 0; font-size: 1.8rem; }
        .dash-btn { background: rgba(255,255,255,0.2); color: white; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; transition: 0.3s; margin-left: 10px; }
        .dash-btn:hover { background: white; color: #185ADB; }

        /* Grid */
        .dash-grid { display: grid; grid-template-columns: 1fr 1.5fr 1fr; gap: 25px; }
        .dash-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); height: fit-content; margin-bottom: 20px; }
        .card-head { border-bottom: 2px solid #f0f2f5; padding-bottom: 15px; margin-bottom: 20px; font-size: 1.1rem; font-weight: 800; color: #333; }

        /* Form */
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: #666; margin-bottom: 5px; }
        .dash-input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; outline: none; transition: 0.3s; }
        .dash-input:focus { border-color: #185ADB; }
        .btn-submit { width: 100%; padding: 12px; background: #185ADB; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .input-row { display: flex; gap: 10px; } .input-row .form-group { flex: 1; }

        /* Ders Kartları */
        .lesson-item { padding: 15px; border-radius: 10px; margin-bottom: 15px; border: 1px solid transparent; position: relative; }
        
        .lesson-item.future { background: #eef2ff; border-color: #c7d2fe; }
        .lesson-item.future h4 { color: #185ADB; margin: 0 0 5px; }
        
        .lesson-item.past { background: #f8f9fa; border-color: #e9ecef; }
        .lesson-item.past h4 { color: #555; margin: 0 0 5px; }
        .lesson-meta { font-size: 0.85rem; color: #666; margin-bottom: 10px; }

        .lesson-actions { display: flex; gap: 10px; }
        .link-btn { font-size: 0.8rem; font-weight: bold; text-decoration: none; color: #185ADB; }
        .cancel-btn { font-size: 0.8rem; font-weight: bold; text-decoration: none; color: #dc3545; }

        /* Yorum Alanı */
        .review-area { margin-top: 15px; padding-top: 10px; border-top: 1px solid #eee; }
        .review-form textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; font-family: inherit; margin-bottom: 5px; }
        .btn-rate { background: #fbc02d; color: #333; border: none; padding: 5px 10px; border-radius: 5px; font-weight: bold; cursor: pointer; font-size: 0.8rem; }
        .rated-badge { background: #fff9c4; color: #f9a825; padding: 5px 10px; border-radius: 5px; font-weight: bold; font-size: 0.85rem; display: inline-block; }

        .prog-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
        .prog-bmi { background: #e0f2f1; color: #00695c; padding: 3px 8px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; }

        @media (max-width: 1024px) { .dash-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="dashboard-container">

    <div class="dash-header">
        <div class="dash-title">
            <h1>👋 Hoşgeldin, <?php echo $user_row['username']; ?></h1>
            <p>Antrenmanlarını takip et ve sınırlarını zorla!</p>
        </div>
        <div>
            <a href="index.php" class="dash-btn">🏠 Anasayfa</a>
            <a href="logout.php" class="dash-btn" style="background:#ff4757;">Çıkış</a>
        </div>
    </div>

    <div class="dash-grid">
        
        <!-- SOL: BİLGİLER -->
        <div class="left-col">
            <div class="dash-card">
                <div class="card-head">👤 Hesap Bilgileri</div>
                <?php if($message) echo "<p style='color:green; font-size:0.9rem;'>$message</p>"; ?>
                <form method="POST">
                    <div class="form-group"><label>Ad Soyad</label><input type="text" name="username" class="dash-input" value="<?php echo $user_row['username']; ?>" required></div>
                    <div class="form-group"><label>E-posta</label><input type="email" name="email" class="dash-input" value="<?php echo $user_row['email']; ?>" required></div>
                    <div class="form-group"><label>Telefon</label><input type="text" name="phone" class="dash-input" value="<?php echo $user_row['phone']; ?>"></div>
                    <div class="input-row">
                        <div class="form-group"><label>Yaş</label><input type="number" name="age" class="dash-input" value="<?php echo $user_row['age']; ?>"></div>
                        <div class="form-group"><label>Cinsiyet</label>
                            <select name="gender" class="dash-input">
                                <option value="">Seçiniz</option>
                                <option value="Erkek" <?php if($user_row['gender']=='Erkek') echo 'selected'; ?>>Erkek</option>
                                <option value="Kadın" <?php if($user_row['gender']=='Kadın') echo 'selected'; ?>>Kadın</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="update_profile" class="btn-submit">Güncelle</button>
                </form>
            </div>
            
            <div class="dash-card">
                <div class="card-head">📈 Gelişim Ekle</div>
                <?php if($progress_message) echo "<p style='color:green; font-size:0.9rem;'>$progress_message</p>"; ?>
                <form method="POST">
                    <div class="form-group"><label>Kilo (kg)</label><input type="number" step="0.1" name="weight" class="dash-input" required></div>
                    <div class="form-group"><label>Boy (cm)</label><input type="number" name="height" class="dash-input" required></div>
                    <button type="submit" name="add_progress" class="btn-submit" style="background:#28a745;">Kaydet</button>
                </form>
            </div>
        </div>

        <!-- ORTA: DERSLER (İKİYE AYRILDI) -->
        <div class="mid-col">
            
            <!-- KUTU 1: YAKLAŞAN DERSLER -->
            <div class="dash-card" style="border-top: 5px solid #185ADB;">
                <div class="card-head" style="color:#185ADB;">🚀 Yaklaşan Dersler</div>
                <?php if(count($upcoming_classes) > 0): ?>
                    <?php foreach($upcoming_classes as $row): ?>
                        <div class="lesson-item future">
                            <h4><?php echo $row['title']; ?> (<?php echo $row['class_type']; ?>)</h4>
                            <div class="lesson-meta">📅 <?php echo date("d.m.Y H:i", strtotime($row['date_time'])); ?> • 🧘‍♂️ <?php echo $row['trainer_name']; ?></div>
                            <div class="lesson-actions">
                                <a href="<?php echo $row['video_link']; ?>" target="_blank" class="link-btn">🎥 Yayına Git</a>
                                <a href="cancel_booking.php?id=<?php echo $row['booking_id']; ?>" onclick="return confirm('İptal?')" class="cancel-btn">❌ İptal</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#999; text-align:center;">Yaklaşan dersiniz yok.</p>
                <?php endif; ?>
            </div>

            <!-- KUTU 2: GEÇMİŞ DERSLER (PUANLAMA BURADA) -->
            <div class="dash-card" style="border-top: 5px solid #666;">
                <div class="card-head" style="color:#555;">📜 Geçmiş Dersler</div>
                <?php if(count($past_classes) > 0): ?>
                    <?php foreach($past_classes as $row): ?>
                        <div class="lesson-item past">
                            <h4><?php echo $row['title']; ?></h4>
                            <div class="lesson-meta">✅ Tamamlandı • <?php echo date("d.m.Y H:i", strtotime($row['date_time'])); ?></div>
                            
                            <?php
                            // Puanlama Kontrolü
                            $c_id = $row['id'];
                            $check_rev = mysqli_query($conn, "SELECT * FROM reviews WHERE user_id=$user_id AND class_id=$c_id");
                            $rev_data = mysqli_fetch_assoc($check_rev);
                            ?>
                            
                            <div class="review-area">
                                <?php if($rev_data): ?>
                                    <div class="rated-badge">⭐ Puanınız: <?php echo $rev_data['rating']; ?>/5</div>
                                <?php else: ?>
                                    <form method="POST">
                                        <input type="hidden" name="class_id" value="<?php echo $c_id; ?>">
                                        <div style="display:flex; gap:5px; margin-bottom:5px;">
                                            <select name="rating" required style="padding:5px; border-radius:5px;">
                                                <option value="5">5 Yıldız</option>
                                                <option value="4">4 Yıldız</option>
                                                <option value="3">3 Yıldız</option>
                                                <option value="2">2 Yıldız</option>
                                                <option value="1">1 Yıldız</option>
                                            </select>
                                            <button type="submit" name="submit_review" class="btn-rate">Puanla</button>
                                        </div>
                                        <textarea name="comment" rows="1" placeholder="Yorumunuz..." required></textarea>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#999; text-align:center;">Geçmiş ders kaydı yok.</p>
                <?php endif; ?>
            </div>

        </div>

        <!-- SAĞ: GELİŞİM GEÇMİŞİ -->
        <div class="right-col">
            <div class="dash-card">
                <div class="card-head">📊 Gelişim Geçmişi</div>
                <?php
                $prog_res = mysqli_query($conn, "SELECT * FROM user_progress WHERE user_id = $user_id ORDER BY record_date DESC LIMIT 5");
                if(mysqli_num_rows($prog_res) > 0) {
                    while($p = mysqli_fetch_assoc($prog_res)) {
                        echo '<div class="prog-row">';
                        echo '<div><strong>'. $p['weight'] .' kg</strong></div>';
                        echo '<div class="prog-bmi">BMI: '. $p['bmi'] .'</div>';
                        echo '<div style="font-size:0.75rem; color:#999;">'. date("d.m.Y", strtotime($p['record_date'])) .'</div>';
                        echo '</div>';
                    }
                } else { echo "<div style='text-align:center; color:#999;'>Veri yok.</div>"; }
                ?>
            </div>
        </div>

    </div>
</div>

</body>
</html>