<?php
session_start();
include 'db.php';
include 'notification_handler.php';
include 'language.php';
$page_title = "Fitness Booking | GYM";

// HER SAYFAYA GİREŞTE BİLDİRİMLERİ KONTROL ET VE GÖNDER
if(rand(1, 10) == 1) { // %10 oranında çalış (spam önleme)
    $notificationHandler->sendClassReminders();
}

include 'header.php';
?>

    <div class="hero">
        <h1><?php echo $lang['hero_title']; ?></h1>
        <p><?php echo $lang['hero_subtitle']; ?></p>
    </div>

    
    <!-- DERS TÜRLERİ KAROUSEL - PREMIUM DİZAYN -->
    <div class="class-types-carousel">
        <div class="carousel-container">
            <div class="carousel-container-title">
                <h2>✨ <?php echo $lang['carousel_title']; ?></h2>
                <p><?php echo $lang['carousel_subtitle']; ?></p>
            </div>

            <div class="carousel-wrapper">
                <!-- YOGA KARTI -->
                <div class="carousel-card" style="border-top: 4px solid #6366f1;">
                    <div class="card-image-wrapper" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <img src="https://images.unsplash.com/photo-1506126613408-eca07ce68773?ixlib=rb-1.2.1&auto=format&fit=crop&w=600&h=400&q=80" alt="Yoga" class="carousel-image" onerror="this.src='https://placehold.co/600x400?text=Yoga'">
                        <div class="card-badge"><?php echo $lang['course_yoga_badge']; ?></div>
                    </div>
                    <div class="carousel-content">
                        <div class="card-title-section">
                            <span class="card-icon">🧘‍♀️</span>
                            <h3><?php echo $lang['course_yoga']; ?></h3>
                        </div>
                        <p class="card-subtitle"><?php echo $lang['course_yoga_subtitle']; ?></p>
                        <div class="card-details">
                            <div class="detail-item"><span class="detail-label"><?php echo $lang['label_level']; ?></span> <?php echo $lang['course_yoga_level']; ?></div>
                            <div class="detail-item"><span class="detail-label"><?php echo $lang['label_duration']; ?></span> <?php echo $lang['course_yoga_duration']; ?></div>
                            <div class="detail-item"><span class="detail-label"><?php echo $lang['label_intensity']; ?></span> <span class="intensity-low">▮ <?php echo $lang['course_yoga_intensity']; ?></span></div>
                        </div>
                        <p class="card-description"><?php echo $lang['course_yoga_desc']; ?></p>
                        <div class="benefits-section">
                            <span class="benefit-tag"><?php echo $lang['course_yoga_benefit1']; ?></span>
                            <span class="benefit-tag"><?php echo $lang['course_yoga_benefit2']; ?></span>
                            <span class="benefit-tag"><?php echo $lang['course_yoga_benefit3']; ?></span>
                        </div>
                    </div>
                </div>

                <!-- PILATES KARTI -->
                <div class="carousel-card" style="border-top: 4px solid #10b981;">
                    <div class="card-image-wrapper" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <img src="https://images.unsplash.com/photo-1541692641-cfbc67269f43?ixlib=rb-1.2.1&auto=format&fit=crop&w=600&h=400&q=80" alt="Pilates" class="carousel-image" onerror="this.src='https://placehold.co/600x400?text=Pilates'">
                        <div class="card-badge"><?php echo $lang['course_pilates_badge']; ?></div>
                    </div>
                    <div class="carousel-content">
                        <div class="card-title-section">
                            <span class="card-icon">🤸‍♀️</span>
                            <h3><?php echo $lang['course_pilates']; ?></h3>
                        </div>
                        <p class="card-subtitle"><?php echo $lang['course_pilates_subtitle']; ?></p>
                        <div class="card-details">
                            <div class="detail-item"><span class="detail-label"><?php echo $lang['label_level']; ?></span> <?php echo $lang['course_pilates_level']; ?></div>
                            <div class="detail-item"><span class="detail-label"><?php echo $lang['label_duration']; ?></span> <?php echo $lang['course_pilates_duration']; ?></div>
                            <div class="detail-item"><span class="detail-label"><?php echo $lang['label_intensity']; ?></span> <span class="intensity-medium">▮▮ <?php echo $lang['course_pilates_intensity']; ?></span></div>
                        </div>
                        <p class="card-description"><?php echo $lang['course_pilates_desc']; ?></p>
                        <div class="benefits-section">
                            <span class="benefit-tag"><?php echo $lang['course_pilates_benefit1']; ?></span>
                            <span class="benefit-tag"><?php echo $lang['course_pilates_benefit2']; ?></span>
                            <span class="benefit-tag"><?php echo $lang['course_pilates_benefit3']; ?></span>
                        </div>
                    </div>
                </div>

                <!-- HIIT KARTI -->
                <div class="carousel-card" style="border-top: 4px solid #f59e0b;">
                    <div class="card-image-wrapper" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);">
                        <img src="https://images.unsplash.com/photo-1534438327276-14e5300c3a48?ixlib=rb-1.2.1&auto=format&fit=crop&w=600&h=400&q=80" alt="HIIT" class="carousel-image" onerror="this.src='https://placehold.co/600x400?text=HIIT'">
                        <div class="card-badge"><?php echo $lang['course_hiit_badge']; ?></div>
                    </div>
                    <div class="carousel-content">
                        <div class="card-title-section">
                            <span class="card-icon">🔥</span>
                            <h3><?php echo $lang['course_hiit']; ?></h3>
                        </div>
                        <p class="card-subtitle"><?php echo $lang['course_hiit_subtitle']; ?></p>
                        <div class="card-details">
                            <div class="detail-item"><span class="detail-label"><?php echo $lang['label_level']; ?></span> <?php echo $lang['course_hiit_level']; ?></div>
                            <div class="detail-item"><span class="detail-label"><?php echo $lang['label_duration']; ?></span> <?php echo $lang['course_hiit_duration']; ?></div>
                            <div class="detail-item"><span class="detail-label"><?php echo $lang['label_intensity']; ?></span> <span class="intensity-high">▮▮▮ <?php echo $lang['course_hiit_intensity']; ?></span></div>
                        </div>
                        <p class="card-description"><?php echo $lang['course_hiit_desc']; ?></p>
                        <div class="benefits-section">
                            <span class="benefit-tag"><?php echo $lang['course_hiit_benefit1']; ?></span>
                            <span class="benefit-tag"><?php echo $lang['course_hiit_benefit2']; ?></span>
                            <span class="benefit-tag"><?php echo $lang['course_hiit_benefit3']; ?></span>
                        </div>
                    </div>
                </div>

                <!-- ZUMBA KARTI -->
                <div class="carousel-card" style="border-top: 4px solid #ec4899;">
                    <div class="card-image-wrapper" style="background: linear-gradient(135deg, #ffa500 0%, #ff69b4 100%);">
                        <img src="https://images.unsplash.com/photo-1470225620780-dba8ba36b745?ixlib=rb-1.2.1&auto=format&fit=crop&w=600&h=400&q=80" alt="Zumba" class="carousel-image" onerror="this.src='https://placehold.co/600x400?text=Zumba'">
                        <div class="card-badge"><?php echo $lang['course_zumba_badge']; ?></div>
                    </div>
                    <div class="carousel-content">
                        <div class="card-title-section">
                            <span class="card-icon">💃</span>
                            <h3><?php echo $lang['course_zumba']; ?></h3>
                        </div>
                        <p class="card-subtitle"><?php echo $lang['course_zumba_subtitle']; ?></p>
                        <div class="card-details">
                            <div class="detail-item"><span class="detail-label"><?php echo $lang['label_level']; ?></span> <?php echo $lang['course_zumba_level']; ?></div>
                            <div class="detail-item"><span class="detail-label"><?php echo $lang['label_duration']; ?></span> <?php echo $lang['course_zumba_duration']; ?></div>
                            <div class="detail-item"><span class="detail-label"><?php echo $lang['label_intensity']; ?></span> <span class="intensity-medium">▮▮ <?php echo $lang['course_zumba_intensity']; ?></span></div>
                        </div>
                        <p class="card-description"><?php echo $lang['course_zumba_desc']; ?></p>
                        <div class="benefits-section">
                            <span class="benefit-tag"><?php echo $lang['course_zumba_benefit1']; ?></span>
                            <span class="benefit-tag"><?php echo $lang['course_zumba_benefit2']; ?></span>
                            <span class="benefit-tag"><?php echo $lang['course_zumba_benefit3']; ?></span>
                        </div>
                    </div>
                </div>

                <!-- FITNESS KARTI -->
                <div class="carousel-card" style="border-top: 4px solid #ef4444;">
                    <div class="card-image-wrapper" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <img src="https://images.unsplash.com/photo-1517836357463-d25dfeac3438?ixlib=rb-1.2.1&auto=format&fit=crop&w=600&h=400&q=80" alt="Fitness" class="carousel-image" onerror="this.src='https://placehold.co/600x400?text=Fitness'">
                        <div class="card-badge"><?php echo $lang['course_fitness_badge']; ?></div>
                    </div>
                    <div class="carousel-content">
                        <div class="card-title-section">
                            <span class="card-icon">💪</span>
                            <h3><?php echo $lang['course_fitness']; ?></h3>
                        </div>
                        <p class="card-subtitle"><?php echo $lang['course_fitness_subtitle']; ?></p>
                        <div class="card-details">
                            <div class="detail-item"><span class="detail-label"><?php echo $lang['label_level']; ?></span> <?php echo $lang['course_fitness_level']; ?></div>
                            <div class="detail-item"><span class="detail-label"><?php echo $lang['label_duration']; ?></span> <?php echo $lang['course_fitness_duration']; ?></div>
                            <div class="detail-item"><span class="detail-label"><?php echo $lang['label_intensity']; ?></span> <span class="intensity-high">▮▮▮ <?php echo $lang['course_fitness_intensity']; ?></span></div>
                        </div>
                        <p class="card-description"><?php echo $lang['course_fitness_desc']; ?></p>
                        <div class="benefits-section">
                            <span class="benefit-tag"><?php echo $lang['course_fitness_benefit1']; ?></span>
                            <span class="benefit-tag"><?php echo $lang['course_fitness_benefit2']; ?></span>
                            <span class="benefit-tag"><?php echo $lang['course_fitness_benefit3']; ?></span>
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