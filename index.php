<?php
session_start();
include 'db.php';
include 'notification_handler.php';
$page_title = "Fitness Booking | GYM";

// HER SAYFAYA GİREŞTE BİLDİRİMLERİ KONTROL ET VE GÖNDER
if(rand(1, 10) == 1) { // %10 oranında çalış (spam önleme)
    $notificationHandler->sendClassReminders();
}

if (!function_exists('buildReviewKey')) {
    function buildReviewKey($trainerName, $classType) {
        return mb_strtolower(trim($trainerName)) . '|' . mb_strtolower(trim($classType));
    }
}

$trainerRatingSummary = [];
$trainerReviewList = [];

$ratingSummaryQuery = "
    SELECT c.trainer_name, c.class_type, AVG(r.rating) AS avg_rating, COUNT(*) AS review_count
    FROM reviews r
    INNER JOIN classes c ON r.class_id = c.id
    GROUP BY c.trainer_name, c.class_type
";

if ($ratingSummaryResult = mysqli_query($conn, $ratingSummaryQuery)) {
    while ($summaryRow = mysqli_fetch_assoc($ratingSummaryResult)) {
        $summaryKey = buildReviewKey($summaryRow['trainer_name'], $summaryRow['class_type']);
        $trainerRatingSummary[$summaryKey] = [
            'avg' => round((float) $summaryRow['avg_rating'], 1),
            'count' => (int) $summaryRow['review_count']
        ];
    }
    mysqli_free_result($ratingSummaryResult);
}

$ratingDetailsQuery = "
    SELECT c.trainer_name, c.class_type, r.rating, r.comment, r.created_at, u.username
    FROM reviews r
    INNER JOIN classes c ON r.class_id = c.id
    LEFT JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC, r.id DESC
";

if ($ratingDetailsResult = mysqli_query($conn, $ratingDetailsQuery)) {
    while ($detailRow = mysqli_fetch_assoc($ratingDetailsResult)) {
        $detailKey = buildReviewKey($detailRow['trainer_name'], $detailRow['class_type']);
        if (!isset($trainerReviewList[$detailKey])) {
            $trainerReviewList[$detailKey] = [];
        }

        if (count($trainerReviewList[$detailKey]) >= 50) {
            continue;
        }

        $trainerReviewList[$detailKey][] = [
            'rating' => (int) $detailRow['rating'],
            'comment' => $detailRow['comment'] ?? '',
            'created_at' => $detailRow['created_at'],
            'username' => $detailRow['username'] ?? 'Üye'
        ];
    }
    mysqli_free_result($ratingDetailsResult);
}

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
                    $img_url = "img/default.jpg";

                    if(strpos($type, 'yoga') !== false) $img_url = "img/yoga.jpg";
                    elseif(strpos($type, 'pilates') !== false) $img_url = "img/pilates.jpg";
                    elseif(strpos($type, 'hiit') !== false) $img_url = "img/hiit.jpg";
                    elseif(strpos($type, 'zumba') !== false) $img_url = "img/zumba.jpg";
                    elseif(strpos($type, 'fitness') !== false) $img_url = "img/fitness.jpg";

                    $reviewKey = buildReviewKey($row['trainer_name'], $row['class_type']);
                    $summary = $trainerRatingSummary[$reviewKey] ?? null;
                    $hasReviews = $summary && ($summary['count'] > 0);
                    $summaryWidth = $hasReviews ? max(0, min(100, ($summary['avg'] / 5) * 100)) : 0;

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

                            <?php if($hasReviews): ?>
                                <div class="review-summary review-summary--minimal review-summary--with-data">
                                    <div class="review-summary__value"><?php echo number_format($summary['avg'], 1); ?></div>
                                    <div class="review-summary__stack">
                                        <div class="review-summary__meta">
                                            <span class="star-rating-display">
                                                <span class="star-rating-display__fill" style="width: <?php echo $summaryWidth; ?>%;"></span>
                                            </span>
                                            <span class="review-summary__count"><?php echo $summary['count']; ?> yorum</span>
                                        </div>
                                        <span class="review-summary__note">Önceki derslerden</span>
                                    </div>
                                </div>
                            <?php endif; ?>

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
                            <?php else: ?>
                                <a href="login.php" class="btn-card" style="background:#666;">Login & Book</a>
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

                    $reviewKey = buildReviewKey($row['trainer_name'], $row['class_type']);
                    $summary = $trainerRatingSummary[$reviewKey] ?? null;
                    $reviewList = $trainerReviewList[$reviewKey] ?? [];
                    $panelId = 'reviews-past-' . (int) $row['id'];
                    $hasReviews = $summary && ($summary['count'] > 0);
                    $summaryWidth = $hasReviews ? max(0, min(100, ($summary['avg'] / 5) * 100)) : 0;
                    $ratingCounts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
                    foreach ($reviewList as $reviewItem) {
                        $rating = max(1, min(5, (int) $reviewItem['rating']));
                        $ratingCounts[$rating]++;
                    }
                    $averageText = $hasReviews ? number_format($summary['avg'], 1) : '0.0';
                    $totalReviews = $summary['count'] ?? 0;
                    $toggleOpenText = '💬 Yorumlar (' . $totalReviews . ')';
                    $toggleCloseText = 'Paneli Kapat';

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

                            <div class="comment-bar">
                                <?php if($hasReviews): ?>
                                    <div class="comment-bar__stat">
                                        <span class="comment-bar__score"><?php echo number_format($summary['avg'], 1); ?></span>
                                        <span class="star-rating-display star-rating-display--sm">
                                            <span class="star-rating-display__fill" style="width: <?php echo $summaryWidth; ?>%;"></span>
                                        </span>
                                        <span class="comment-bar__meta"><?php echo $totalReviews; ?> yorum</span>
                                    </div>
                                <?php else: ?>
                                    <div class="comment-bar__stat comment-bar__stat--empty">Henüz yorum yok</div>
                                <?php endif; ?>
                                <button type="button" class="comment-trigger" data-target="<?php echo $panelId; ?>" data-open-text="<?php echo $toggleOpenText; ?>" data-close-text="<?php echo $toggleCloseText; ?>" aria-expanded="false" aria-controls="<?php echo $panelId; ?>"><?php echo $toggleOpenText; ?></button>
                            </div>
                            <div class="review-panel review-panel--trendy" id="<?php echo $panelId; ?>">
                                <div class="review-panel__header">
                                    <div class="review-panel__summary">
                    <div class="review-panel__score"><?php echo $averageText; ?></div>
                                        <div class="review-panel__stars">
                                            <span class="star-rating-display">
                                                <span class="star-rating-display__fill" style="width: <?php echo $summaryWidth; ?>%;"></span>
                                            </span>
                                            <span class="review-panel__total"><?php echo $totalReviews; ?> yorum</span>
                                        </div>
                                    </div>
                                    <div class="review-panel__filters">
                                        <button type="button" class="review-filter active" data-target-panel="<?php echo $panelId; ?>" data-filter="all">Tümü (<?php echo $totalReviews; ?>)</button>
                                        <?php for ($star = 5; $star >= 1; $star--): ?>
                                            <button type="button" class="review-filter" data-target-panel="<?php echo $panelId; ?>" data-filter="<?php echo $star; ?>"><?php echo $star; ?> ★ (<?php echo $ratingCounts[$star]; ?>)</button>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="review-panel__body">
                                    <?php if($hasReviews): ?>
                                        <div class="review-panel__list">
                                            <?php foreach ($reviewList as $reviewItem): ?>
                                                <?php $itemWidth = max(0, min(100, ($reviewItem['rating'] / 5) * 100)); ?>
                                                <div class="review-item" data-rating="<?php echo (int) $reviewItem['rating']; ?>">
                                                    <div class="review-item__meta">
                                                        <span class="star-rating-display star-rating-display--sm">
                                                            <span class="star-rating-display__fill" style="width: <?php echo $itemWidth; ?>%;"></span>
                                                        </span>
                                                        <span class="review-item__author"><?php echo htmlspecialchars($reviewItem['username']); ?></span>
                                                        <span class="review-item__date"><?php echo date('d.m.Y', strtotime($reviewItem['created_at'])); ?></span>
                                                    </div>
                                                    <?php if(!empty($reviewItem['comment'])): ?>
                                                        <div class="review-item__comment"><?php echo nl2br(htmlspecialchars($reviewItem['comment'])); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="review-panel__empty review-panel__empty--filtered" style="display:none;">Bu filtre için yorum bulunamadı.</div>
                                    <?php else: ?>
                                        <div class="review-panel__empty">Henüz yorum yok. İlk yorumu sen paylaş!</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <button class="btn-card btn-disabled" disabled>Completed</button>
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

    function applyReviewFilter(panel, filter) {
        if (!panel) {
            return;
        }

        const list = panel.querySelector('.review-panel__list');
        const items = list ? list.querySelectorAll('.review-item') : [];
        let visible = 0;

        items.forEach((item) => {
            const rating = item.getAttribute('data-rating');
            const shouldShow = filter === 'all' || rating === filter;
            item.style.display = shouldShow ? 'block' : 'none';
            if (shouldShow) {
                visible++;
            }
        });

        const emptyFiltered = panel.querySelector('.review-panel__empty--filtered');
        if (emptyFiltered) {
            emptyFiltered.style.display = visible === 0 ? 'block' : 'none';
        }
    }

    function initReviewPanels() {
        const toggleButtons = document.querySelectorAll('.comment-trigger');
        toggleButtons.forEach((button) => {
            const targetId = button.getAttribute('data-target');
            const openText = button.getAttribute('data-open-text') || button.textContent || 'Yorumları Gör';
            const closeText = button.getAttribute('data-close-text') || 'Paneli Kapat';
            const panel = document.getElementById(targetId);

            if (!panel) {
                return;
            }

            button.setAttribute('aria-expanded', 'false');
            panel.setAttribute('aria-hidden', 'true');

            button.addEventListener('click', () => {
                const isOpen = panel.classList.toggle('open');
                button.classList.toggle('open', isOpen);
                button.textContent = isOpen ? closeText : openText;
                button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                panel.setAttribute('aria-hidden', isOpen ? 'false' : 'true');

                if (isOpen) {
                    const activeFilter = panel.querySelector('.review-filter.active');
                    const filterValue = activeFilter ? activeFilter.getAttribute('data-filter') : 'all';
                    applyReviewFilter(panel, filterValue || 'all');
                }
            });
        });

        const filterButtons = document.querySelectorAll('.review-filter');
        filterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const panelId = button.getAttribute('data-target-panel');
                const panel = document.getElementById(panelId);
                if (!panel) {
                    return;
                }

                const filtersContainer = button.closest('.review-panel__filters');
                if (filtersContainer) {
                    filtersContainer.querySelectorAll('.review-filter').forEach((btn) => btn.classList.remove('active'));
                }
                button.classList.add('active');

                const filterValue = button.getAttribute('data-filter') || 'all';
                applyReviewFilter(panel, filterValue);
            });
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        initFiltering();
        initReviewPanels();
    });
    </script>

    <?php include 'footer.php'; ?>