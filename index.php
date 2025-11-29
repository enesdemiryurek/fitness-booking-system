<?php
session_start();
include 'db.php';
include 'notification_handler.php';
$page_title = "Fitness Booking | GYM";

// HER SAYFAYA GİREŞTE BİLDİRİMLERİ KONTROL ET VE GÖNDER
if(rand(1, 10) == 1) { // %10 oranında çalış (spam önleme)
    $notificationHandler->sendClassReminders();
}

include 'header.php';
?>

    <div class="hero">
        <h1>Sınırlarını Zorla</h1>
        <p>En iyi eğitmenlerle potansiyelini keşfet. Hemen yerini ayırt.</p>
    </div>

    
   <!-- GRUP DERSLERİ (STICKY BÖLÜM) BAŞLANGIÇ -->
    <div class="group-classes-section">
        <div class="group-wrapper">
            
            <!-- SOL TARAF: İÇERİK -->
            <div class="group-content">
                
                <!-- ZUMBA -->
                <div id="zumba" class="group-item">
                    <img src="img/zumba.jpg" class="group-img" onerror="this.src='https://images.unsplash.com/photo-1524594152303-9fd13543fe6e?w=800'">
                    <h3>Zumba</h3>
                    <p>Dans ve fitness'ın mükemmel uyumu! Latin müzikleri eşliğinde hem eğlen hem de kalori yak. Her seviyeye uygun koreografilerle stres atarken forma girin.</p>
                  
                </div>

                <!-- PILATES -->
                <div id="pilates" class="group-item">
                    <img src="img/pilates.jpg" class="group-img" onerror="this.src='https://images.unsplash.com/photo-1518611012118-696072aa579a?w=800'">
                    <h3>Pilates</h3>
                    <p>Vücut esnekliğini artır, kaslarını uzat ve duruşunu düzelt. Mat üzerinde veya aletli pilates seçeneklerimizle merkez (core) gücünü keşfet.</p>
                   
                </div>

                <!-- HIIT -->
                <div id="hiit" class="group-item">
                    <img src="img/hiit.jpg" class="group-img" onerror="this.src='https://images.unsplash.com/photo-1601422407692-ec4eeec1d9b3?w=800'">
                    <h3>HIIT</h3>
                    <p>Yüksek Yoğunluklu Aralıklı Antrenman ile sınırlarını zorla. Kısa sürede maksimum yağ yakımı sağlayan bu ders, kondisyonunu zirveye taşıyacak.</p>
                   
                </div>

                <!-- FITNESS -->
                <div id="fitness" class="group-item">
                    <img src="img/default.jpg" class="group-img" onerror="this.src='https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=800'">
                    <h3>Fitness</h3>
                    <p>Modern ekipmanlarla donatılmış salonumuzda, kişisel hedeflerine yönelik antrenman programları. Kas kütleni artır veya sıkılaş.</p>
                   
                </div>

            </div>

            <!-- SAĞ TARAF: SABİT MENÜ -->
            <div class="group-sidebar">
                <span class="zigzag">Menu</span>
                <h2 class="sidebar-title">Group<br>Lessons</h2>
                
                <ul class="sidebar-menu">
                    <li><a href="#zumba">Zumba</a></li>
                    <li><a href="#pilates">Pilates</a></li>
                    <li><a href="#hiit">HIIT</a></li>
                    <li><a href="#fitness">Fitness</a></li>
                </ul>
            </div>

        </div>
    </div>
    <!-- GRUP DERSLERİ BİTİŞ -->
            
   
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

    <?php include 'footer.php'; ?>