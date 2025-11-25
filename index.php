<?php
session_start();
include 'db.php';
include 'notification_handler.php';
$page_title = "Fitness Rezervasyon | GYM";

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

    
    <!-- DERS TÜRLERİ KAROUSEL - PREMIUM DİZAYN -->
    <div class="class-types-carousel">
        <div class="carousel-container">
            <div class="carousel-container-title">
                <h2>✨ Ders Türlerimizi Keşfet</h2>
                <p>Sağlığını geliştir, hedeflerine ulaş - Her gün yeni bir başlangıç</p>
            </div>

            <div class="carousel-wrapper">
                <!-- YOGA KARTI -->
                <div class="carousel-card" style="border-top: 4px solid #6366f1;">
                    <div class="card-image-wrapper" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <img src="https://images.unsplash.com/photo-1506126613408-eca07ce68773?ixlib=rb-1.2.1&auto=format&fit=crop&w=600&h=400&q=80" alt="Yoga" class="carousel-image" onerror="this.src='https://placehold.co/600x400?text=Yoga'">
                        <div class="card-badge">Sakinlik</div>
                    </div>
                    <div class="carousel-content">
                        <div class="card-title-section">
                            <span class="card-icon">🧘‍♀️</span>
                            <h3>Yoga</h3>
                        </div>
                        <p class="card-subtitle">Zihin ve beden dengesini bulun</p>
                        <div class="card-details">
                            <div class="detail-item"><span class="detail-label">Seviye:</span> Tüm Seviyelere Uygun</div>
                            <div class="detail-item"><span class="detail-label">Süre:</span> 60 dakika</div>
                            <div class="detail-item"><span class="detail-label">Yoğunluk:</span> <span class="intensity-low">▮ Düşük</span></div>
                        </div>
                        <p class="card-description">Esnetme, meditasyon ve nefes teknikleriyle esnekliğinizi artırın, stresinizi azaltın ve iç huzur bulun.</p>
                        <div class="benefits-section">
                            <span class="benefit-tag">🌸 Stres Azalması</span>
                            <span class="benefit-tag">🧘 Esneklik</span>
                            <span class="benefit-tag">💆 Rahatlama</span>
                        </div>
                    </div>
                </div>

                <!-- PILATES KARTI -->
                <div class="carousel-card" style="border-top: 4px solid #10b981;">
                    <div class="card-image-wrapper" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <img src="https://images.unsplash.com/photo-1541692641-cfbc67269f43?ixlib=rb-1.2.1&auto=format&fit=crop&w=600&h=400&q=80" alt="Pilates" class="carousel-image" onerror="this.src='https://placehold.co/600x400?text=Pilates'">
                        <div class="card-badge">Core Gücü</div>
                    </div>
                    <div class="carousel-content">
                        <div class="card-title-section">
                            <span class="card-icon">🤸‍♀️</span>
                            <h3>Pilates</h3>
                        </div>
                        <p class="card-subtitle">Merkez kaslarınızı güçlendirin</p>
                        <div class="card-details">
                            <div class="detail-item"><span class="detail-label">Seviye:</span> Tüm Seviyelere Uygun</div>
                            <div class="detail-item"><span class="detail-label">Süre:</span> 50 dakika</div>
                            <div class="detail-item"><span class="detail-label">Yoğunluk:</span> <span class="intensity-medium">▮▮ Orta</span></div>
                        </div>
                        <p class="card-description">Kontrollü hareketlerle merkez kaslarınızı güçlendirin, vücut dengenizi düzeltin ve postürünüzü iyileştirin.</p>
                        <div class="benefits-section">
                            <span class="benefit-tag">💪 Core Gücü</span>
                            <span class="benefit-tag">🎯 Postür</span>
                            <span class="benefit-tag">📏 Şekillendirme</span>
                        </div>
                    </div>
                </div>

                <!-- HIIT KARTI -->
                <div class="carousel-card" style="border-top: 4px solid #f59e0b;">
                    <div class="card-image-wrapper" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);">
                        <img src="https://images.unsplash.com/photo-1534438327276-14e5300c3a48?ixlib=rb-1.2.1&auto=format&fit=crop&w=600&h=400&q=80" alt="HIIT" class="carousel-image" onerror="this.src='https://placehold.co/600x400?text=HIIT'">
                        <div class="card-badge">Yüksek Enerji</div>
                    </div>
                    <div class="carousel-content">
                        <div class="card-title-section">
                            <span class="card-icon">🔥</span>
                            <h3>HIIT</h3>
                        </div>
                        <p class="card-subtitle">Maksimum kalori yakımı</p>
                        <div class="card-details">
                            <div class="detail-item"><span class="detail-label">Seviye:</span> Orta - İleri</div>
                            <div class="detail-item"><span class="detail-label">Süre:</span> 45 dakika</div>
                            <div class="detail-item"><span class="detail-label">Yoğunluk:</span> <span class="intensity-high">▮▮▮ Yüksek</span></div>
                        </div>
                        <p class="card-description">Yüksek yoğunluk egzersizler ve kısa dinlenme aralarından oluşan hızlı, etkili antrenman.</p>
                        <div class="benefits-section">
                            <span class="benefit-tag">🔥 Kalori Yakımı</span>
                            <span class="benefit-tag">⚡ Metabolizma</span>
                            <span class="benefit-tag">🏃 Dayanıklılık</span>
                        </div>
                    </div>
                </div>

                <!-- ZUMBA KARTI -->
                <div class="carousel-card" style="border-top: 4px solid #ec4899;">
                    <div class="card-image-wrapper" style="background: linear-gradient(135deg, #ffa500 0%, #ff69b4 100%);">
                        <img src="https://images.unsplash.com/photo-1470225620780-dba8ba36b745?ixlib=rb-1.2.1&auto=format&fit=crop&w=600&h=400&q=80" alt="Zumba" class="carousel-image" onerror="this.src='https://placehold.co/600x400?text=Zumba'">
                        <div class="card-badge">Eğlence Paketi</div>
                    </div>
                    <div class="carousel-content">
                        <div class="card-title-section">
                            <span class="card-icon">💃</span>
                            <h3>Zumba</h3>
                        </div>
                        <p class="card-subtitle">Müzikle dans ederek egzersiz yapın</p>
                        <div class="card-details">
                            <div class="detail-item"><span class="detail-label">Seviye:</span> Tüm Seviyelere Uygun</div>
                            <div class="detail-item"><span class="detail-label">Süre:</span> 60 dakika</div>
                            <div class="detail-item"><span class="detail-label">Yoğunluk:</span> <span class="intensity-medium">▮▮ Orta</span></div>
                        </div>
                        <p class="card-description">Latin ritimleriyle eğlenerek hareket ederek kardiyovasküler sisteminizi geliştirin.</p>
                        <div class="benefits-section">
                            <span class="benefit-tag">😊 Eğlence</span>
                            <span class="benefit-tag">🎵 Ritim</span>
                            <span class="benefit-tag">👥 Sosyal</span>
                        </div>
                    </div>
                </div>

                <!-- FITNESS KARTI -->
                <div class="carousel-card" style="border-top: 4px solid #ef4444;">
                    <div class="card-image-wrapper" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <img src="https://images.unsplash.com/photo-1517836357463-d25dfeac3438?ixlib=rb-1.2.1&auto=format&fit=crop&w=600&h=400&q=80" alt="Fitness" class="carousel-image" onerror="this.src='https://placehold.co/600x400?text=Fitness'">
                        <div class="card-badge">Güç Eğitimi</div>
                    </div>
                    <div class="carousel-content">
                        <div class="card-title-section">
                            <span class="card-icon">💪</span>
                            <h3>Fitness</h3>
                        </div>
                        <p class="card-subtitle">Vücut geliştirme ve güçlenme</p>
                        <div class="card-details">
                            <div class="detail-item"><span class="detail-label">Seviye:</span> Tüm Seviyelere Uygun</div>
                            <div class="detail-item"><span class="detail-label">Süre:</span> 55 dakika</div>
                            <div class="detail-item"><span class="detail-label">Yoğunluk:</span> <span class="intensity-high">▮▮▮ Yüksek</span></div>
                        </div>
                        <p class="card-description">Ağırlık antrenmanları, direnç egzersizleri ve fonksiyonel hareketlerle vücut şekillendirin.</p>
                        <div class="benefits-section">
                            <span class="benefit-tag">💪 Kas Gelişimi</span>
                            <span class="benefit-tag">🏋️ Güç</span>
                            <span class="benefit-tag">🔥 Şekillendirme</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- NAVİGASYON BUTONLARI -->
            <button class="carousel-nav carousel-prev" onclick="scrollCarousel(-1)">❮</button>
            <button class="carousel-nav carousel-next" onclick="scrollCarousel(1)">❯</button>

            <!-- NOKTA İNDİKATÖRLERİ -->
            <div class="carousel-dots">
                <span class="dot active" onclick="goToCarouselSlide(0)"></span>
                <span class="dot" onclick="goToCarouselSlide(1)"></span>
                <span class="dot" onclick="goToCarouselSlide(2)"></span>
                <span class="dot" onclick="goToCarouselSlide(3)"></span>
                <span class="dot" onclick="goToCarouselSlide(4)"></span>
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