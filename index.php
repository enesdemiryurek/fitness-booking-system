<?php
session_start();
include 'db.php';
include 'notification_handler.php';
$page_title = "Fitness Booking | GYM";

// HER SAYFAYA GİREŞTE BİLDİRİMLERİ KONTROL ET VE GÖNDER
if(rand(1, 10) == 1) { // %10 oranında çalış (spam önleme)
    $notificationHandler->sendClassReminders();
}

// Reviews are displayed on class_details_reviews.php; no review aggregation needed here.

include 'header.php';
?>

    <div class="hero">
        <h1>Push Your Limits</h1>
        <p>Discover your potential with the best instructors. Book your place now.</p>
    </div>

    
   <!-- GRUP DERSLERİ (STICKY BÖLÜM) BAŞLANGIÇ -->
    <div class="group-classes-section">
        <div class="group-wrapper">
            
            <!-- SOL TARAF: İÇERİK -->
            <div class="group-content">
                
                <!-- YOGA -->
                <div id="yoga" class="group-item">
                    <img src="" class="group-img" onerror="this.src='https://images.unsplash.com/photo-1506126613408-eca07ce68773?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D'">
                    <h3>Yoga</h3>
                    <p>Find inner peace and strengthen your body with our yoga classes. Improve flexibility, reduce stress, and achieve mental clarity through ancient practices adapted for modern life.</p>
                  
                </div>

                <!-- ZUMBA -->
                <div id="zumba" class="group-item">
                    <img src="" class="group-img" onerror="this.src='https://plus.unsplash.com/premium_photo-1663054933667-fb307cea9aab?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D'">
                    <h3>Zumba</h3>
                    <p>The perfect blend of dance and fitness! Have fun and burn calories with Latin music. Get in shape while relieving stress with choreography suitable for all levels.</p>
                  
                </div>

                <!-- PILATES -->
                <div id="pilates" class="group-item">
                    <img src="" class="group-img" onerror="this.src='https://images.unsplash.com/photo-1518611012118-696072aa579a?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D'">
                    <h3>Pilates</h3>
                    <p>Increase your flexibility, lengthen your muscles, and improve your posture. Discover your core strength with our mat or equipment Pilates options.</p>
                   
                </div>

                <!-- HIIT -->
                <div id="hiit" class="group-item">
                    <img src="" class="group-img" onerror="this.src='https://plus.unsplash.com/premium_photo-1664910207555-fac63513e7ad?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D'">
                    <h3>HIIT</h3>
                    <p>Push your limits with High Intensity Interval Training. This class will maximize fat burning in a short time and take your fitness to the top.</p>
                   
                </div>

                <!-- FITNESS -->
                <div id="fitness" class="group-item">
                    <img src="img/default.jpg" class="group-img" onerror="this.src='https://images.unsplash.com/photo-1517836357463-d25dfeac3438?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D'">
                    <h3>Fitness</h3>
                    <p>Our gym is equipped with modern equipment and offers training programs tailored to your personal goals. Increase your muscle mass or tone your body.</p>
                   
                </div>

            </div>

            <!-- SAĞ TARAF: SABİT MENÜ -->
            <div class="group-sidebar">
                <span class="zigzag">Menu</span>
                <h2 class="sidebar-title">Group<br>Lessons</h2>
                
                <ul class="sidebar-menu">
                    <li><a href="#yoga">Yoga</a></li>
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
        <h2 class="section-title">Upcoming Lessons</h2>

        <!-- Filtreleme Butonları -->
        <div class="filter-container">
            <div class="filter-buttons">
                <button class="filter-btn active" data-filter="all">All Classes</button>
                <button class="filter-btn" data-filter="Yoga">Yoga</button>
                <button class="filter-btn" data-filter="Pilates">Pilates</button>
                <button class="filter-btn" data-filter="HIIT">HIIT</button>
                <button class="filter-btn" data-filter="Zumba">Zumba</button>
                <button class="filter-btn" data-filter="Fitness">Fitness</button>
            </div>
            <div class="filter-results">
                <span id="upcoming-count">0</span> classes found
            </div>
        </div>

        <div class="class-list" id="upcoming-classes">
            <?php
            // Sadece gelecekteki dersler
            $current_time = date("Y-m-d H:i:s");
            $sql = "SELECT * FROM classes WHERE date_time >= '$current_time' ORDER BY date_time ASC";
            $result = mysqli_query($conn, $sql);

            if ($result && mysqli_num_rows($result) > 0) {
                while($row = mysqli_fetch_assoc($result)) {
                    $type = mb_strtolower($row['class_type']);

                    if(strpos($type, 'yoga') !== false) $img_url = "img/yoga.jpg";
                    elseif(strpos($type, 'pilates') !== false) $img_url = "img/pilates.jpg";
                    elseif(strpos($type, 'hiit') !== false) $img_url = "img/hiit.jpg";
                    elseif(strpos($type, 'zumba') !== false) $img_url = "img/zumba.jpg";
                    elseif(strpos($type, 'fitness') !== false) $img_url = "img/fitness.jpg";

                    $trainer_sql = "SELECT profile_photo, username FROM users WHERE username = '" . mysqli_real_escape_string($conn, $row['trainer_name']) . "' LIMIT 1";
                    $trainer_result = mysqli_query($conn, $trainer_sql);
                    $trainer_data = $trainer_result ? mysqli_fetch_assoc($trainer_result) : null;
                    ?>
                    <div class="class-card" data-class-type="<?php echo htmlspecialchars($row['class_type']); ?>">
                        <img src="<?php echo $img_url; ?>" alt="Class Image" class="card-image" onerror="this.src='https://placehold.co/600x400?text=No+Image'">
                        <div class="card-content">
                            <h3><?php echo htmlspecialchars($row['title']); ?> <span class="badge"><?php echo htmlspecialchars($row['class_type']); ?></span></h3>
                            <div class="trainer-info-card">
                                <?php if($trainer_data && !empty($trainer_data['profile_photo'])): ?>
                                    <img src="data:image/jpeg;base64,<?php echo base64_encode($trainer_data['profile_photo']); ?>" alt="Instructor" class="trainer-avatar-small">
                                <?php else: ?>
                                    <?php $initial = !empty($trainer_data['username']) ? strtoupper(substr($trainer_data['username'], 0, 1)) : strtoupper(substr($row['trainer_name'], 0, 1)); ?>
                                    <div class="trainer-avatar-placeholder-small"><?php echo htmlspecialchars($initial); ?></div>
                                <?php endif; ?>
                                <span class="trainer-name-card"><?php echo htmlspecialchars($row['trainer_name']); ?></span>
                                <span class="trainer-time-card">Time: <?php echo date("d.m.Y H:i", strtotime($row['date_time'])); ?></span>
                            </div>
                            <p class="class-description"><?php echo htmlspecialchars($row['description']); ?></p>

                            <?php
                            $stok_color = ($row['capacity'] < 3) ? '#dc3545' : '#28a745';
                            ?>
                            <span class="stok" style="color:<?php echo $stok_color; ?>"> Remaining Place: <?php echo (int) $row['capacity']; ?></span>

                            <?php if(isset($_SESSION['user_id'])): ?>
                                <?php if ($row['capacity'] > 0): ?>
                                    <a href="book_class.php?id=<?php echo (int) $row['id']; ?>" class="btn-card">Book Now</a>
                                <?php else: ?>
                                    <button class="btn-card btn-disabled" disabled>FULL</button>
                                <?php endif; ?>
                                <a href="class_details_reviews.php?id=<?php echo (int) $row['id']; ?>" class="btn-card" style="margin-top:10px; background:#ffffff; color:#0A66C2; border:1px solid #0A66C2;">Details & Comments</a>
                            <?php else: ?>
                                <a href="login.php" class="btn-card" style="background:#0A66C2;">Login & Book</a>
                                <a href="class_details_reviews.php?id=<?php echo (int) $row['id']; ?>" class="btn-card" style="margin-top:10px; background:#ffffff; color:#0A66C2; border:1px solid #0A66C2;">Details & Comments</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                    if ($trainer_result) {
                        mysqli_free_result($trainer_result);
                    }
                }
            } else {
                echo '<div class="no-results-message">';
                echo '<p>There are no active courses yet.</p>';
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <!-- GEÇMIŞ DERSLER BÖLÜMÜ -->
    <div class="container" id="gecmis-dersler">
        <h2 class="section-title"> Past Lessons </h2>

        <!-- Filtreleme Butonları -->
        <div class="filter-container">
            <div class="filter-buttons">
                <button class="filter-btn active" data-filter="all">All Classes</button>
                <button class="filter-btn" data-filter="Yoga">Yoga</button>
                <button class="filter-btn" data-filter="Pilates">Pilates</button>
                <button class="filter-btn" data-filter="HIIT">HIIT</button>
                <button class="filter-btn" data-filter="Zumba">Zumba</button>
                <button class="filter-btn" data-filter="Fitness">Fitness</button>
            </div>
            <div class="filter-results">
                <span id="past-count">0</span> classes found
            </div>
        </div>

        <div class="class-list" id="past-classes">
            <?php
            // Son 24 saat içinde geçen dersler
            $now = time();
            $one_day_ago = date("Y-m-d H:i:s", $now - 604800); // 1 hafta zaman
            $current_time = date("Y-m-d H:i:s");

            $sql = "SELECT * FROM classes WHERE date_time < '$current_time' AND date_time >= '$one_day_ago' ORDER BY date_time DESC";
            $result = mysqli_query($conn, $sql);

            if ($result && mysqli_num_rows($result) > 0) {
                while($row = mysqli_fetch_assoc($result)) {
                    $type = mb_strtolower($row['class_type']);
                    $img_url = "img/default.jpg";

                    if(strpos($type, 'yoga') !== false) $img_url = "img/yoga.jpg";
                    elseif(strpos($type, 'pilates') !== false) $img_url = "img/pilates.jpg";
                    elseif(strpos($type, 'hiit') !== false) $img_url = "img/hiit.jpg";
                    elseif(strpos($type, 'zumba') !== false) $img_url = "img/zumba.jpg";
                    elseif(strpos($type, 'fitness') !== false) $img_url = "img/fitness.jpg";

                    $trainer_sql = "SELECT profile_photo, username FROM users WHERE username = '" . mysqli_real_escape_string($conn, $row['trainer_name']) . "' LIMIT 1";
                    $trainer_result = mysqli_query($conn, $trainer_sql);
                    $trainer_data = $trainer_result ? mysqli_fetch_assoc($trainer_result) : null;
                    ?>
                    <div class="class-card past-class" data-class-type="<?php echo htmlspecialchars($row['class_type']); ?>">
                        <img src="<?php echo $img_url; ?>" alt="Class Image" class="card-image past-image" onerror="this.src='https://placehold.co/600x400?text=No+Image'">
                        <div class="card-content">
                            <h3><?php echo htmlspecialchars($row['title']); ?> <span class="badge">Completed</span></h3>
                            <div class="trainer-info-card">
                                <?php if($trainer_data && !empty($trainer_data['profile_photo'])): ?>
                                    <img src="data:image/jpeg;base64,<?php echo base64_encode($trainer_data['profile_photo']); ?>" alt="Instructor" class="trainer-avatar-small">
                                <?php else: ?>
                                    <?php $initial = !empty($trainer_data['username']) ? strtoupper(substr($trainer_data['username'], 0, 1)) : strtoupper(substr($row['trainer_name'], 0, 1)); ?>
                                    <div class="trainer-avatar-placeholder-small"><?php echo htmlspecialchars($initial); ?></div>
                                <?php endif; ?>
                                <span class="trainer-name-card"><?php echo htmlspecialchars($row['trainer_name']); ?></span>
                                <span class="trainer-time-card">Time: <?php echo date("d.m.Y H:i", strtotime($row['date_time'])); ?></span>
                            </div>
                            <p class="class-description"><?php echo htmlspecialchars($row['description']); ?></p>

                            <a href="class_details_reviews.php?id=<?php echo (int) $row['id']; ?>" class="btn-card" style="margin-top:10px; background:#ffffff; color:#0A66C2; border:1px solid #0A66C2;">Details & Comments</a>
                            <button class="btn-card btn-disabled" disabled style="margin-top:10px;">Completed</button>
                        </div>
                    </div>
                    <?php
                    if ($trainer_result) {
                        mysqli_free_result($trainer_result);
                    }
                }
            } else {
                echo '<div class="no-results-message">';
                echo '<p>No past classes found yet.</p>';
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <script>
    // Filtreleme Fonksiyonu - Upcoming Lessons
    function initFiltering() {
        // Upcoming Lessons Filtreleme
        const upcomingFilterBtns = document.querySelectorAll('#dersler .filter-btn');
        const upcomingCards = document.querySelectorAll('#upcoming-classes .class-card');
        
        upcomingFilterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const filter = this.getAttribute('data-filter');
                
                // Aktif buton stilini güncelle
                upcomingFilterBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Kartları filtrele
                let visibleCount = 0;
                upcomingCards.forEach((card, index) => {
                    const cardType = card.getAttribute('data-class-type');
                    if(filter === 'all' || cardType === filter) {
                        card.style.opacity = '0';
                        card.style.transform = 'translateY(20px)';
                        setTimeout(() => {
                            card.style.display = 'block';
                            card.style.transition = 'all 0.3s ease';
                            setTimeout(() => {
                                card.style.opacity = '1';
                                card.style.transform = 'translateY(0)';
                            }, 10);
                        }, index * 50);
                        visibleCount++;
                    } else {
                        card.style.transition = 'all 0.3s ease';
                        card.style.opacity = '0';
                        card.style.transform = 'translateY(-20px)';
                        setTimeout(() => {
                            card.style.display = 'none';
                        }, 300);
                    }
                });
                
                // Sonuç sayısını güncelle
                document.getElementById('upcoming-count').textContent = visibleCount;
                
                // Eğer sonuç yoksa mesaj göster
                const upcomingList = document.getElementById('upcoming-classes');
                let noResultsMsg = upcomingList.querySelector('.no-results-filtered');
                if(visibleCount === 0) {
                    if(!noResultsMsg) {
                        noResultsMsg = document.createElement('div');
                        noResultsMsg.className = 'no-results-message no-results-filtered';
                        noResultsMsg.innerHTML = '<p>No classes found for this filter. Try selecting a different category.</p>';
                        upcomingList.appendChild(noResultsMsg);
                    }
                } else {
                    if(noResultsMsg) {
                        noResultsMsg.remove();
                    }
                }
            });
        });
        
        // İlk yüklemede sayıyı göster
        document.getElementById('upcoming-count').textContent = upcomingCards.length;
        
        // Past Lessons Filtreleme
        const pastFilterBtns = document.querySelectorAll('#gecmis-dersler .filter-btn');
        const pastCards = document.querySelectorAll('#past-classes .class-card');
        
        pastFilterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const filter = this.getAttribute('data-filter');
                
                // Aktif buton stilini güncelle
                pastFilterBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Kartları filtrele
                let visibleCount = 0;
                pastCards.forEach((card, index) => {
                    const cardType = card.getAttribute('data-class-type');
                    if(filter === 'all' || cardType === filter) {
                        card.style.opacity = '0';
                        card.style.transform = 'translateY(20px)';
                        setTimeout(() => {
                            card.style.display = 'block';
                            card.style.transition = 'all 0.3s ease';
                            setTimeout(() => {
                                card.style.opacity = '1';
                                card.style.transform = 'translateY(0)';
                            }, 10);
                        }, index * 50);
                        visibleCount++;
                    } else {
                        card.style.transition = 'all 0.3s ease';
                        card.style.opacity = '0';
                        card.style.transform = 'translateY(-20px)';
                        setTimeout(() => {
                            card.style.display = 'none';
                        }, 300);
                    }
                });
                
                // Sonuç sayısını güncelle
                document.getElementById('past-count').textContent = visibleCount;
                
                // Eğer sonuç yoksa mesaj göster
                const pastList = document.getElementById('past-classes');
                let noResultsMsg = pastList.querySelector('.no-results-filtered');
                if(visibleCount === 0) {
                    if(!noResultsMsg) {
                        noResultsMsg = document.createElement('div');
                        noResultsMsg.className = 'no-results-message no-results-filtered';
                        noResultsMsg.innerHTML = '<p>No classes found for this filter. Try selecting a different category.</p>';
                        pastList.appendChild(noResultsMsg);
                    }
                } else {
                    if(noResultsMsg) {
                        noResultsMsg.remove();
                    }
                }
            });
        });
        
        // İlk yüklemede sayıyı göster
        document.getElementById('past-count').textContent = pastCards.length;
    }

    document.addEventListener('DOMContentLoaded', () => {
        initFiltering();
    });
    </script>

    <?php include 'footer.php'; ?>