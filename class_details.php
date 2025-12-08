<?php
session_start();
include 'db.php';

$review_success_message = $_SESSION['review_success'] ?? '';
$review_error_message = $_SESSION['review_error'] ?? '';
unset($_SESSION['review_success'], $_SESSION['review_error']);

// Güvenlik
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";
$progress_message = "";

if (!function_exists('buildReviewKey')) {
    function buildReviewKey($trainerName, $classType) {
        return mb_strtolower(trim($trainerName)) . '|' . mb_strtolower(trim($classType));
    }
}

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
    $class_id_review = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;
    $rating = isset($_POST['rating']) ? (int) $_POST['rating'] : 0;
    $comment = trim($_POST['comment'] ?? '');
    $validation_error = '';

                                <div class="review-panel__body">
                                    <?php if(!$rev_data): ?>
                                        <form method="POST" class="review-form-trendy review-form-trendy--inline">
                                            <input type="hidden" name="class_id" value="<?php echo $c_id; ?>">
                                            <div class="form-group-review">
                                                <label>Bu dersi nasıl değerlendirirsin?</label>
                                                <div class="star-rating-input">
                                                    <?php for($i = 5; $i >= 1; $i--): ?>
                                                        <input type="radio" id="rating-<?php echo $c_id; ?>-<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                                                        <label for="rating-<?php echo $c_id; ?>-<?php echo $i; ?>">★</label>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                            <div class="form-group-review">
                                                <label>Yorumun</label>
                                                <textarea name="comment" rows="3" placeholder="Deneyimini paylaş..." required></textarea>
                                            </div>
        <div class="review-form-actions">
                                                <button type="submit" name="submit_review" class="btn-send-review">Gönder</button>
                                            </div>
                                        </form>
                                    <?php else: ?>
                                        <div class="review-badge review-badge--inline">
                                            <span class="review-badge__tag">Senin yorumun</span>
                                            <div class="review-badge__header">
                                                <?php $userWidth = max(0, min(100, ($rev_data['rating'] / 5) * 100)); ?>
                                                <span class="star-rating-display star-rating-display--sm">
                                                    <span class="star-rating-display__fill" style="width: <?php echo $userWidth; ?>%;"></span>
                                                </span>
                                                <span class="review-summary__score"><?php echo (int) $rev_data['rating']; ?>/5</span>
                                            </div>
                                            <?php if(!empty($rev_data['comment'])): ?>
                                                <p class="review-comment"><?php echo nl2br(htmlspecialchars($rev_data['comment'])); ?></p>
                                            <?php endif; ?>
                                            <?php if(!empty($rev_data['created_at'])): ?>
                                                <span class="review-item__date">Gönderim: <?php echo date('d.m.Y', strtotime($rev_data['created_at'])); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if($pastHasReviews): ?>
                                        <div class="review-panel__list">
                                            <?php foreach ($pastReviewList as $reviewItem): ?>
                                                <?php $width = max(0, min(100, ($reviewItem['rating'] / 5) * 100)); ?>
                                                <div class="review-item" data-rating="<?php echo (int) $reviewItem['rating']; ?>">
                                                    <div class="review-item__meta">
                                                        <span class="star-rating-display star-rating-display--sm">
                                                            <span class="star-rating-display__fill" style="width: <?php echo $width; ?>%;"></span>
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

                            <button class="btn-card btn-disabled" disabled>Tamamlandı</button>
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
            <h1>👋 Hoşgeldin, <?php echo htmlspecialchars($user_row['username']); ?></h1>
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
                                <option value="Male" <?php if($user_row['gender']=='Male' || $user_row['gender']=='Erkek') echo 'selected'; ?>>Male</option>
                                <option value="Female" <?php if($user_row['gender']=='Female' || $user_row['gender']=='Kadın') echo 'selected'; ?>>Female</option>
                                <option value="Prefer not to say" <?php if($user_row['gender']=='Prefer not to say' || $user_row['gender']=='Belirtmek İstemiyorum') echo 'selected'; ?>>Prefer not to say</option>
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
                        <?php
                        $upcomingReviewKey = buildReviewKey($row['trainer_name'], $row['class_type']);
                        $upcomingSummary = $trainerRatingSummary[$upcomingReviewKey] ?? null;
                        $upcomingHasReviews = $upcomingSummary && ($upcomingSummary['count'] > 0);
                        $upcomingWidth = $upcomingHasReviews ? max(0, min(100, ($upcomingSummary['avg'] / 5) * 100)) : 0;
                        ?>
                        <div class="lesson-item future">
                            <h4><?php echo htmlspecialchars($row['title']); ?> (<?php echo htmlspecialchars($row['class_type']); ?>)</h4>
                            <div class="lesson-meta">📅 <?php echo date("d.m.Y H:i", strtotime($row['date_time'])); ?> • 🧘‍♂️ <?php echo htmlspecialchars($row['trainer_name']); ?></div>

                            <?php if($upcomingHasReviews): ?>
                                <div class="review-summary review-summary--minimal review-summary--with-data">
                                    <div class="review-summary__value"><?php echo number_format($upcomingSummary['avg'], 1); ?></div>
                                    <div class="review-summary__stack">
                                        <div class="review-summary__meta">
                                            <span class="star-rating-display">
                                                <span class="star-rating-display__fill" style="width: <?php echo $upcomingWidth; ?>%;"></span>
                                            </span>
                                            <span class="review-summary__count"><?php echo $upcomingSummary['count']; ?> yorum</span>
                                        </div>
                                        <span class="review-summary__note">Önceki derslerden</span>
                                    </div>
                                </div>
                            <?php endif; ?>

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
                <?php if($review_success_message): ?>
                    <div class="review-flash review-flash--success"><?php echo htmlspecialchars($review_success_message); ?></div>
                <?php endif; ?>
                <?php if($review_error_message): ?>
                    <div class="review-flash review-flash--error"><?php echo htmlspecialchars($review_error_message); ?></div>
                <?php endif; ?>
                <?php if(count($past_classes) > 0): ?>
                    <?php foreach($past_classes as $row): ?>
                        <?php
                        $pastReviewKey = buildReviewKey($row['trainer_name'], $row['class_type']);
                        $pastSummary = $trainerRatingSummary[$pastReviewKey] ?? null;
                        $pastReviewList = $trainerReviewList[$pastReviewKey] ?? [];
                        $pastPanelId = 'review-panel-past-' . (int) $row['id'];
                        $pastHasReviews = $pastSummary && ($pastSummary['count'] > 0);
                        $pastSummaryWidth = $pastHasReviews ? max(0, min(100, ($pastSummary['avg'] / 5) * 100)) : 0;
                        $pastCounts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
                        foreach ($pastReviewList as $reviewItem) {
                            $rating = max(1, min(5, (int) $reviewItem['rating']));
                            $pastCounts[$rating]++;
                        }
                        $pastAverageText = $pastHasReviews ? number_format($pastSummary['avg'], 1) : '0.0';
                        $pastTotal = $pastSummary['count'] ?? 0;
                        $pastToggleOpen = '💬 Yorumlar (' . $pastTotal . ')';
                        $pastToggleClose = 'Paneli Kapat';

                        $c_id = (int) $row['id'];
                        $rev_data = null;
                        if ($reviewCheck = mysqli_query($conn, "SELECT rating, comment, created_at FROM reviews WHERE user_id = $user_id AND class_id = $c_id LIMIT 1")) {
                            $rev_data = mysqli_fetch_assoc($reviewCheck);
                            mysqli_free_result($reviewCheck);
                        }

                        $trainer_sql = "SELECT profile_photo, username FROM users WHERE username = '" . mysqli_real_escape_string($conn, $row['trainer_name']) . "' LIMIT 1";
                        $trainer_result = mysqli_query($conn, $trainer_sql);
                        $trainer_data = $trainer_result ? mysqli_fetch_assoc($trainer_result) : null;
                        ?>
                        <div class="lesson-item past">
                            <h4><?php echo htmlspecialchars($row['title']); ?></h4>
                            <div class="lesson-meta">✅ Tamamlandı • <?php echo date("d.m.Y H:i", strtotime($row['date_time'])); ?></div>

                            <div class="trainer-info-card">
                                <?php if($trainer_data && !empty($trainer_data['profile_photo'])): ?>
                                    <img src="data:image/jpeg;base64,<?php echo base64_encode($trainer_data['profile_photo']); ?>" alt="Eğitmen" class="trainer-avatar-small">
                                <?php else: ?>
                                    <?php $initial = !empty($trainer_data['username']) ? strtoupper(substr($trainer_data['username'], 0, 1)) : strtoupper(substr($row['trainer_name'], 0, 1)); ?>
                                    <div class="trainer-avatar-placeholder-small"><?php echo htmlspecialchars($initial); ?></div>
                                <?php endif; ?>
                                <span class="trainer-name-card"><?php echo htmlspecialchars($row['trainer_name']); ?></span>
                                <span class="trainer-time-card">Kategori: <?php echo htmlspecialchars($row['class_type']); ?></span>
                            </div>

                            <?php if(!empty($row['description'])): ?>
                                <p class="class-description"><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
                            <?php endif; ?>

                            <div class="comment-bar">
                                <?php if($pastHasReviews): ?>
                                    <div class="comment-bar__stat">
                                        <span class="comment-bar__score"><?php echo number_format($pastSummary['avg'], 1); ?></span>
                                        <span class="star-rating-display star-rating-display--sm">
                                            <span class="star-rating-display__fill" style="width: <?php echo $pastSummaryWidth; ?>%;"></span>
                                        </span>
                                        <span class="comment-bar__meta"><?php echo $pastTotal; ?> yorum</span>
                                    </div>
                                <?php else: ?>
                                    <div class="comment-bar__stat comment-bar__stat--empty">Henüz yorum yok</div>
                                <?php endif; ?>
                                <button type="button" class="comment-trigger" data-target="<?php echo $pastPanelId; ?>" data-open-text="<?php echo $pastToggleOpen; ?>" data-close-text="<?php echo $pastToggleClose; ?>" aria-expanded="false" aria-controls="<?php echo $pastPanelId; ?>"><?php echo $pastToggleOpen; ?></button>
                            </div>
                            <div class="review-panel review-panel--trendy" id="<?php echo $pastPanelId; ?>">
                                <div class="review-panel__header">
                                    <div class="review-panel__summary">
                                        <div class="review-panel__score"><?php echo $pastAverageText; ?></div>
                                        <div class="review-panel__stars">
                                            <span class="star-rating-display">
                                                <span class="star-rating-display__fill" style="width: <?php echo $pastSummaryWidth; ?>%;"></span>
                                            </span>
                                            <span class="review-panel__total"><?php echo $pastTotal; ?> yorum</span>
                                        </div>
                                    </div>
                                    <div class="review-panel__filters">
                                        <button type="button" class="review-filter active" data-target-panel="<?php echo $pastPanelId; ?>" data-filter="all">Tümü (<?php echo $pastTotal; ?>)</button>
                                        <?php for ($star = 5; $star >= 1; $star--): ?>
                                            <button type="button" class="review-filter" data-target-panel="<?php echo $pastPanelId; ?>" data-filter="<?php echo $star; ?>"><?php echo $star; ?> ★ (<?php echo $pastCounts[$star]; ?>)</button>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="review-panel__body">
                                    <?php if(!$rev_data): ?>
                                        <form method="POST" class="review-form-trendy review-form-trendy--inline">
                                            <input type="hidden" name="class_id" value="<?php echo $c_id; ?>">
                                            <div class="form-group-review">
                                                <label>Bu dersi nasıl değerlendirirsin?</label>
                                                <div class="star-rating-input">
                                                    <?php for($i = 5; $i >= 1; $i--): ?>
                                                        <input type="radio" id="rating-<?php echo $c_id; ?>-<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                                                        <label for="rating-<?php echo $c_id; ?>-<?php echo $i; ?>">★</label>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                            <div class="form-group-review">
                                                <label>Yorumun</label>
                                                <textarea name="comment" rows="3" placeholder="Deneyimini paylaş..." required></textarea>
                                            </div>
                                            <div class="review-form-actions">
                                                <button type="submit" name="submit_review" class="btn-send-review">Gönder</button>
                                            </div>
                                        </form>
                                    <?php else: ?>
                                        <div class="review-badge review-badge--inline">
                                            <span class="review-badge__tag">Senin yorumun</span>
                                            <div class="review-badge__header">
                                                <?php $userWidth = max(0, min(100, ($rev_data['rating'] / 5) * 100)); ?>
                                                <span class="star-rating-display star-rating-display--sm">
                                                    <span class="star-rating-display__fill" style="width: <?php echo $userWidth; ?>%;"></span>
                                                </span>
                                                <span class="review-summary__score"><?php echo (int) $rev_data['rating']; ?>/5</span>
                                            </div>
                                            <?php if(!empty($rev_data['comment'])): ?>
                                                <p class="review-comment"><?php echo nl2br(htmlspecialchars($rev_data['comment'])); ?></p>
                                            <?php endif; ?>
                                            <?php if(!empty($rev_data['created_at'])): ?>
                                                <span class="review-item__date">Gönderim: <?php echo date('d.m.Y', strtotime($rev_data['created_at'])); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if($pastHasReviews): ?>
                                        <div class="review-panel__list">
                                            <?php foreach ($pastReviewList as $reviewItem): ?>
                                                <?php $width = max(0, min(100, ($reviewItem['rating'] / 5) * 100)); ?>
                                                <div class="review-item" data-rating="<?php echo (int) $reviewItem['rating']; ?>">
                                                    <div class="review-item__meta">
                                                        <span class="star-rating-display star-rating-display--sm">
                                                            <span class="star-rating-display__fill" style="width: <?php echo $width; ?>%;"></span>
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

                            <button class="btn-card btn-disabled" disabled>Tamamlandı</button>
                        </div>
                        <?php if ($trainer_result) { mysqli_free_result($trainer_result); } ?>
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    function applyReviewFilter(panel, filter) {
        if (!panel) {
            return;
        }

        const list = panel.querySelector('.review-panel__list');
        const items = list ? list.querySelectorAll('.review-item') : [];
        let visible = 0;

        items.forEach(function (item) {
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

    const toggleButtons = document.querySelectorAll('.comment-trigger');
    toggleButtons.forEach(function (button) {
        const targetId = button.getAttribute('data-target');
        const openText = button.getAttribute('data-open-text') || button.textContent || 'Yorumları Gör';
        const closeText = button.getAttribute('data-close-text') || 'Paneli Kapat';
        const panel = document.getElementById(targetId);

        if (!panel) {
            return;
        }

        button.setAttribute('aria-expanded', 'false');
        panel.setAttribute('aria-hidden', 'true');

        button.addEventListener('click', function () {
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
    filterButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const panelId = button.getAttribute('data-target-panel');
            const panel = document.getElementById(panelId);
            if (!panel) {
                return;
            }

            const filtersContainer = button.closest('.review-panel__filters');
            if (filtersContainer) {
                filtersContainer.querySelectorAll('.review-filter').forEach(function (btn) {
                    btn.classList.remove('active');
                });
            }
            button.classList.add('active');

            const filterValue = button.getAttribute('data-filter') || 'all';
            applyReviewFilter(panel, filterValue);
        });
    });

});
</script>
</body>
</html>