<?php
session_start();
include 'db.php';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fitness Rezervasyon</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="logo">
            GYM
        </a>

        <div class="nav-center">
            <a href="#dersler">Dersleri Keşfet</a>
        </div>

        <div class="nav-right">
            <?php if(isset($_SESSION['user_id'])): ?>
                <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'instructor'): ?>
                    <a href="admin.php" class="admin-badge"> Ders Ekle</a>
                <?php endif; ?>

                <a href="profile.php" class="btn-auth btn-login">👤 Profilim</a>
                <a href="logout.php" class="btn-auth" style="color:red;">Çıkış</a>
            <?php else: ?>
                <a href="login.php" class="btn-auth btn-login">Giriş Yap</a>
                <a href="register.php" class="btn-auth btn-register">Kayıt Ol</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="hero">
        <h1>Sınırlarını Zorla</h1>
        <p>En iyi eğitmenlerle potansiyelini keşfet. Hemen yerini ayırt.</p>
    </div>

    <div class="info-section">
        <div class="info-grid">
            <div class="info-box">
                <span class="info-icon">🧘‍♀️</span>
                <h3>Zihin ve Beden</h3>
                <p>Yoga derslerimizle esnekliğini artır, stresini azalt ve iç huzurunu keşfet.</p>
            </div>
            <div class="info-box">
                <span class="info-icon">🔥</span>
                <h3>Yüksek Yağ Yakımı</h3>
                <p>HIIT antrenmanları ile kısa sürede maksimum kalori yak.</p>
            </div>
            <div class="info-box">
                <span class="info-icon">🤸‍♀️</span>
                <h3>Güçlü Duruş</h3>
                <p>Pilates ile merkez (core) bölgeni güçlendir ve postürünü düzelt.</p>
            </div>
            <div class="info-box">
                <span class="info-icon">🏆</span>
                <h3>Uzman Eğitmenler</h3>
                <p>Alanında sertifikalı ve tecrübeli eğitmenlerimizle hedeflerine ulaş.</p>
            </div>
        </div>
    </div>

    <div class="container" id="dersler">
        <h2 class="section-title">📅 Yaklaşan Dersler</h2>

        <div class="class-list">
            <?php
            // Sadece gelecekteki dersler
            $current_time = date("Y-m-d H:i:s");
            $sql = "SELECT * FROM classes WHERE date_time >= '$current_time' ORDER BY date_time ASC";
            $result = mysqli_query($conn, $sql);

            if (mysqli_num_rows($result) > 0) {
                while($row = mysqli_fetch_assoc($result)) {
                    
                    // --- RESİM AYARLARI ---
                    $type = mb_strtolower($row['class_type']);
                    $img_url = "img/default.jpg"; 

                    if(strpos($type, 'yoga') !== false) $img_url = "img/yoga.jpg";
                    elseif(strpos($type, 'pilates') !== false) $img_url = "img/pilates.jpg";
                    elseif(strpos($type, 'hiit') !== false) $img_url = "img/hiit.jpg";
                    elseif(strpos($type, 'zumba') !== false) $img_url = "img/zumba.jpg";
                    elseif(strpos($type, 'fitness') !== false) $img_url = "img/fitness.jpg";
                    
                    echo '<div class="class-card">';
                    echo '<img src="'.$img_url.'" alt="Ders Resmi" class="card-image" onerror="this.src=\'https://placehold.co/600x400?text=Resim+Yok\'">';
                    
                    echo '<div class="card-content">';
                        echo '<h3>' . $row["title"] . ' <span class="badge">' . $row["class_type"] . '</span></h3>';
                        echo '<p style="color:#666; margin-top:5px;">🧘‍♂️ ' . $row["trainer_name"] . ' • 🕒 ' . date("d.m.Y H:i", strtotime($row["date_time"])) . '</p>';
                        echo '<p style="margin-top:10px;">' . $row["description"] . '</p>';
                        
                        // Stok Durumu
                        $stok_color = ($row["capacity"] < 3) ? "#dc3545" : "#28a745";
                        echo '<span class="stok" style="color:'.$stok_color.'">⚡ Kalan Yer: ' . $row["capacity"] . '</span>';

                        // Detay Butonu
                        echo '<a href="class_details.php?id='.$row['id'].'" style="display:block; text-align:center; color:#185ADB; font-weight:bold; margin:15px 0 10px 0; text-decoration:none;">🔍 İncele & Yorumlar</a>';

                        // Rezerve Butonları
                        if(isset($_SESSION['user_id'])) {
                            if ($row["capacity"] > 0) {
                                echo '<a href="book_class.php?id='.$row['id'].'" class="btn-card">Hemen Rezerve Et</a>';
                            } else {
                                echo '<button class="btn-card btn-disabled" disabled>DOLDU</button>';
                            }
                        } else {
                            echo '<a href="login.php" class="btn-card" style="background:#666;">Giriş Yap & Rezerve Et</a>';
                        }

                    echo '</div>'; // card-content
                    echo '</div>'; // class-card
                }
            } else {
                echo "<p style='text-align:center; width:100%;'>Henüz aktif ders bulunmuyor.</p>";
            }
            ?>
        </div>
    </div>

    <!-- GEÇMIŞ DERSLER BÖLÜMÜ -->
    <div class="container" id="gecmis-dersler">
        <h2 class="section-title"> Geçmiş Dersler </h2>

        <div class="class-list">
            <?php
            // Son 24 saat içinde geçen dersler
            $now = time();
            $one_day_ago = date("Y-m-d H:i:s", $now - 86400); // 24 saat öncesi
            $current_time = date("Y-m-d H:i:s");

            $sql = "SELECT * FROM classes WHERE date_time < '$current_time' AND date_time >= '$one_day_ago' ORDER BY date_time DESC";
            $result = mysqli_query($conn, $sql);

            if (mysqli_num_rows($result) > 0) {
                while($row = mysqli_fetch_assoc($result)) {
                    
                    // --- RESİM AYARLARI ---
                    $type = mb_strtolower($row['class_type']);
                    $img_url = "img/default.jpg"; 

                    if(strpos($type, 'yoga') !== false) $img_url = "img/yoga.jpg";
                    elseif(strpos($type, 'pilates') !== false) $img_url = "img/pilates.jpg";
                    elseif(strpos($type, 'hiit') !== false) $img_url = "img/hiit.jpg";
                    elseif(strpos($type, 'zumba') !== false) $img_url = "img/zumba.jpg";
                    elseif(strpos($type, 'fitness') !== false) $img_url = "img/fitness.jpg";
                    
                    echo '<div class="class-card past-class">';
                    echo '<img src="'.$img_url.'" alt="Ders Resmi" class="card-image past-image" onerror="this.src=\'https://placehold.co/600x400?text=Resim+Yok\'">';
                    
                    echo '<div class="card-content">';
                        echo '<h3>' . $row["title"] . ' <span class="badge">Tamamlandı</span></h3>';
                        echo '<p style="color:#666; margin-top:5px;">🧘‍♂️ ' . $row["trainer_name"] . ' • 🕒 ' . date("d.m.Y H:i", strtotime($row["date_time"])) . '</p>';
                        echo '<p style="margin-top:10px;">' . $row["description"] . '</p>';
                        
                        // Detay Butonu
                        echo '<a href="class_details.php?id='.$row['id'].'" style="display:block; text-align:center; color:#185ADB; font-weight:bold; margin:15px 0 10px 0; text-decoration:none;">🔍 İncele & Yorumlar</a>';

                        echo '<button class="btn-card btn-disabled" disabled>TAMAMLANDI</button>';

                    echo '</div>'; // card-content
                    echo '</div>'; // class-card
                }
            } else {
                echo "<p style='text-align:center; width:100%;'>Henüz geçmiş ders bulunmuyor.</p>";
            }
            ?>
        </div>
    </div>

    <!-- Script Dosyasını Bağladık -->
    <script src="script.js"></script>
</body>
</html>