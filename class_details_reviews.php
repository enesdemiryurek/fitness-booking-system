<?php
session_start();
include 'db.php';
$page_title = "Class Details | GYM";

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$class_id = intval($_GET['id']);

// Ders bilgisini al
$class_sql = "SELECT * FROM classes WHERE id = $class_id";
$class_result = mysqli_query($conn, $class_sql);
$class_info = mysqli_fetch_assoc($class_result);

if (!$class_info) {
    header("Location: index.php");
    exit;
}

// Eğitmen bilgisini al
$trainer_sql = "SELECT * FROM users WHERE username = '{$class_info['trainer_name']}' AND role = 'instructor'";
$trainer_result = mysqli_query($conn, $trainer_sql);
$trainer_info = mysqli_fetch_assoc($trainer_result);

// Yorum ekle
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    if (!isset($_SESSION['user_id'])) {
        $error_msg = " You need to log in to add a comment!";
    } else {
        $user_id = $_SESSION['user_id'];
        $rating = intval($_POST['rating']);
        $comment = mysqli_real_escape_string($conn, $_POST['comment']);
        
        // Daha önce yorum yapmış mı?
        $check_sql = "SELECT * FROM reviews WHERE user_id = $user_id AND class_id = $class_id";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error_msg = " You have already commented on this class!";
        } else {
            $insert_sql = "INSERT INTO reviews (class_id, user_id, rating, comment) VALUES ($class_id, $user_id, $rating, '$comment')";
            if (mysqli_query($conn, $insert_sql)) {
                $success_msg = "✅ Your comment has been saved!";
            } else {
                $error_msg = " Error: " . mysqli_error($conn);
            }
        }
    }
}

// Yorumları getir
$reviews_sql = "SELECT r.*, u.username, u.profile_photo FROM reviews r 
                JOIN users u ON r.user_id = u.id 
                WHERE r.class_id = $class_id 
                ORDER BY r.created_at DESC";
$reviews_result = mysqli_query($conn, $reviews_sql);
$total_reviews = mysqli_num_rows($reviews_result);

// Ortalama rating hesapla
$avg_sql = "SELECT AVG(rating) as avg_rating FROM reviews WHERE class_id = $class_id";
$avg_result = mysqli_query($conn, $avg_sql);
$avg_data = mysqli_fetch_assoc($avg_result);
$avg_rating = $avg_data['avg_rating'] ? round($avg_data['avg_rating'], 1) : 0;

include 'header.php';
?>

<div class="class-details-container">
    
    <!-- DERS BAŞLIGI VE EĞİTMEN BİLGİSİ -->
    <div class="class-hero">
        <h1><?php echo htmlspecialchars($class_info['title']); ?></h1>
        <p><?php echo htmlspecialchars($class_info['description']); ?></p>
    </div>

    <div class="class-details-grid">
        
        <!-- SOL TARAF: DERS BİLGİLERİ -->
        <div class="class-details-left">
            <div class="detail-card">
                <h2> Class Info</h2>
                <div class="info-row">
                    <span class="info-label">Class Type:</span>
                    <span class="info-value"><?php echo htmlspecialchars($class_info['class_type']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date & Time:</span>
                    <span class="info-value"><?php echo date("d.m.Y H:i", strtotime($class_info['date_time'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Capacity:</span>
                    <span class="info-value"><?php echo $class_info['capacity']; ?> people</span>
                </div>
               
            </div>

            <!-- EĞİTMEN KARTı -->
            <div class="detail-card instructor-card">
                <h2>Trainer</h2>
                <div class="instructor-profile">
                    <?php if($trainer_info && $trainer_info['profile_photo']): ?>
                        <img src="data:image/jpeg;base64,<?php echo base64_encode($trainer_info['profile_photo']); ?>" alt="Eğitmen" class="trainer-photo">
                    <?php else: ?>
                        <div class="trainer-photo-placeholder">👤</div>
                    <?php endif; ?>
                    <div class="trainer-info">
                        <h3><?php echo htmlspecialchars($class_info['trainer_name']); ?></h3>
                        <p>Certified Instructor</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- SAĞ TARAF: YORUMLAR -->
        <div class="class-details-right">
            
            <!-- PUANLAMA ÖZETİ -->
            <div class="detail-card review-summary">
                <h2> Course Rating</h2>
                <div class="rating-display">
                    <div class="rating-score"><?php echo $avg_rating; ?></div>
                    <div class="rating-stars">
                        <?php 
                        for ($i = 1; $i <= 5; $i++) {
                            echo ($i <= round($avg_rating)) ? "⭐" : "☆";
                        }
                        ?>
                    </div>
                    <div class="rating-count"><?php echo $total_reviews; ?> comments</div>
                </div>
            </div>

            <!-- YORUM FORMU -->
            <div class="detail-card review-form-card">
                <div class="review-form-header">
                    <h3>Add Comment</h3>
                    <button type="button" class="btn-expand" onclick="toggleReviewForm(event)">▼</button>
                </div>
                
                <div id="review-form-panel" class="review-form-panel" style="display: none;">
                    <?php if(isset($success_msg)): ?>
                        <div class="alert alert-success"><?php echo $success_msg; ?></div>
                    <?php endif; ?>
                    <?php if(isset($error_msg)): ?>
                        <div class="alert alert-error"><?php echo $error_msg; ?></div>
                    <?php endif; ?>

                    <?php if(!isset($_SESSION['user_id'])): ?>
                        <div class="alert alert-warning">
                            Please <a href="login.php">log in</a> to add a comment.
                        </div>
                    <?php else: ?>
                        <form method="POST" class="review-form">
                            <div class="form-group">
                                <label for="rating">Rate:</label>
                                <select id="rating" name="rating" required class="form-control">
                                    <option value="">-- Select --</option>
                                    <option value="5">⭐⭐⭐⭐⭐ (5)</option>
                                    <option value="4">⭐⭐⭐⭐  (4)</option>
                                    <option value="3">⭐⭐⭐  (3)</option>
                                    <option value="2">⭐⭐  (2)</option>
                                    <option value="1">⭐ (1)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="comment">Comment:</label>
                                <textarea id="comment" name="comment" required placeholder="Share your thoughts about the class..." rows="3" class="form-control"></textarea>
                            </div>
                            <button type="submit" name="submit_review" class="btn-primary">Submit</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="detail-card review-form-card" style="margin-top:15px; border: 2px dashed #dce4f7; background:#f9fbff;">
                <div class="review-form-header">
                    <h3>Past Lessons - Add Comment</h3>
                    <button type="button" class="btn-expand" onclick="toggleReviewFormPast(event)">▼</button>
                </div>
                <div id="review-form-panel-past" class="review-form-panel" style="display: none;">
                    <p style="font-size:0.95rem; color:#4b5563; margin-bottom:12px;">Use the panel below to comment on classes you attended in the past.</p>
                    <a href="class_details.php" class="btn-primary" style="display:inline-block; text-decoration:none;">Past Classes and Comments</a>
                </div>
            </div>

            <!-- YORUMLAR LİSTESİ -->
            <div class="detail-card">
                <div class="reviews-header">
                    <h3>Comments</h3>
                    <span class="reviews-count"><?php echo $total_reviews; ?> comments</span>
                </div>

                <div class="reviews-container">
                    <?php if ($total_reviews > 0) { ?>
                        <?php while ($review = mysqli_fetch_assoc($reviews_result)) { ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <div class="reviewer-info">
                                        <?php if ($review['profile_photo']) { ?>
                                            <img src="data:image/jpeg;base64,<?php echo base64_encode($review['profile_photo']); ?>" alt="Kullanıcı" class="reviewer-avatar">
                                        <?php } else { ?>
                                            <div class="reviewer-avatar-placeholder">👤</div>
                                        <?php } ?>
                                        <div class="reviewer-details">
                                            <strong><?php echo htmlspecialchars($review['username']); ?></strong>
                                            <div class="review-rating">
                                                <?php for ($i = 1; $i <= 5; $i++) { ?>
                                                    <?php echo ($i <= $review['rating']) ? "⭐" : "☆"; ?>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="review-date"><?php echo date("d.m.Y", strtotime($review['created_at'])); ?></div>
                                </div>
                                <div class="review-comment">
                                    <?php echo htmlspecialchars($review['comment']); ?>
                                </div>
                            </div>
                        <?php } ?>
                    <?php } else { ?>
                        <div style="text-align: center; padding: 30px; color: #999;">
                            <p>No comments yet.</p>
                        </div>
                    <?php } ?>
                </div>
            </div>

        </div>
    </div>

</div>

<script>
function toggleReviewForm(e) {
    if (e) { e.preventDefault(); }
    const panel = document.getElementById('review-form-panel');
    if (!panel) return;
    const isHidden = panel.style.display === 'none' || panel.style.display === '';
    panel.style.display = isHidden ? 'block' : 'none';
}
</script>



<?php include 'footer.php'; ?>
