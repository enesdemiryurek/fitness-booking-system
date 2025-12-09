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
        $error_msg = "❌ You need to log in to add a comment!";
    } else {
        $user_id = $_SESSION['user_id'];
        $rating = intval($_POST['rating']);
        $comment = mysqli_real_escape_string($conn, $_POST['comment']);
        
        // Daha önce yorum yapmış mı?
        $check_sql = "SELECT * FROM reviews WHERE user_id = $user_id AND class_id = $class_id";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error_msg = "❌ You have already commented on this class!";
        } else {
            $insert_sql = "INSERT INTO reviews (class_id, user_id, rating, comment) VALUES ($class_id, $user_id, $rating, '$comment')";
            if (mysqli_query($conn, $insert_sql)) {
                $success_msg = "✅ Your comment has been saved!";
            } else {
                $error_msg = "❌ Error: " . mysqli_error($conn);
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
                <h2>📋 Class Info</h2>
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
                <div class="info-row">
                    <span class="info-label">Video Link:</span>
                    <a href="<?php echo htmlspecialchars($class_info['video_link']); ?>" target="_blank" class="btn-small">🎥 Go to Stream</a>
                </div>
            </div>

            <!-- EĞİTMEN KARTı -->
            <div class="detail-card instructor-card">
                <h2>👨‍🏫 Instructor</h2>
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
                <h2>⭐ Course Rating</h2>
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
                    <?php if($total_reviews > 0): ?>
                        <?php while($review = mysqli_fetch_assoc($reviews_result)): ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <div class="reviewer-info">
                                        <?php if($review['profile_photo']): ?>
                                            <img src="data:image/jpeg;base64,<?php echo base64_encode($review['profile_photo']); ?>" alt="Kullanıcı" class="reviewer-avatar">
                                        <?php else: ?>
                                            <div class="reviewer-avatar-placeholder">👤</div>
                                        <?php endif; ?>
                                        <div class="reviewer-details">
                                            <strong><?php echo htmlspecialchars($review['username']); ?></strong>
                                            <div class="review-rating">
                                                <?php for($i = 1; $i <= 5; $i++): ?>
                                                    <?php echo ($i <= $review['rating']) ? "⭐" : "☆"; ?>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="review-date"><?php echo date("d.m.Y", strtotime($review['created_at'])); ?></div>
                                </div>
                                <div class="review-comment">
                                    <?php echo htmlspecialchars($review['comment']); ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    
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

<style>
/* CLASS DETAILS STYLES */
.class-details-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 30px 20px;
}

.class-hero {
    background: linear-gradient(135deg, #185ADB 0%, #2a73de 100%);
    color: white;
    padding: 40px;
    border-radius: 15px;
    margin-bottom: 40px;
    text-align: center;
}

.class-hero h1 {
    font-size: 2.2rem;
    margin-bottom: 10px;
}

.class-hero p {
    font-size: 1.05rem;
    opacity: 0.95;
}

.class-details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

.detail-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    margin-bottom: 20px;
}

.detail-card h2 {
    font-size: 1.3rem;
    margin-bottom: 20px;
    color: #222;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 15px;
}

.detail-card h3 {
    font-size: 1.1rem;
    color: #333;
    margin-bottom: 15px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f5f5f5;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 600;
    color: #555;
}

.info-value {
    color: #333;
}

/* INSTRUCTOR CARD */
.instructor-card {
    background: linear-gradient(135deg, #f5f7fa 0%, #fafbfc 100%);
    border-left: 4px solid #185ADB;
}

.instructor-profile {
    display: flex;
    gap: 20px;
    align-items: center;
}

.trainer-photo {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #185ADB;
}

.trainer-photo-placeholder {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: #e0e0e0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
}

.trainer-info h3 {
    margin: 0 0 5px 0;
    color: #185ADB;
}

.trainer-info p {
    color: #666;
    margin: 0;
}

/* REVIEW SUMMARY */
.review-summary {
    text-align: center;
    background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
    color: #333;
}

.rating-display {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
}

.rating-score {
    font-size: 3rem;
    font-weight: bold;
    color: #185ADB;
}

.rating-stars {
    font-size: 1.8rem;
    letter-spacing: 5px;
}

.rating-count {
    font-size: 0.95rem;
    color: #666;
}

/* REVIEW FORM */
.review-form-card {
    background: white;
    border: 2px solid #f0f0f0;
}

.review-form-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.review-form-header h3 {
    margin: 0;
}

.btn-expand {
    background: #185ADB;
    color: white;
    border: none;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1rem;
    transition: all 0.3s;
}

.btn-expand:hover {
    background: #1245a8;
    transform: scale(1.1);
}

.review-form-panel {
    margin-top: 15px;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-family: inherit;
    font-size: 0.95rem;
}

.form-control:focus {
    outline: none;
    border-color: #185ADB;
    box-shadow: 0 0 0 3px rgba(24, 90, 219, 0.1);
}

.btn-primary {
    background: #185ADB;
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 0.95rem;
}

.btn-primary:hover {
    background: #1245a8;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(24, 90, 219, 0.3);
}

/* REVIEWS CONTAINER */
.reviews-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.reviews-count {
    background: #f0f0f0;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    color: #666;
}

.reviews-container {
    max-height: 600px;
    overflow-y: auto;
    padding-right: 10px;
}

.reviews-container::-webkit-scrollbar {
    width: 8px;
}

.reviews-container::-webkit-scrollbar-track {
    background: #f0f0f0;
    border-radius: 10px;
}

.reviews-container::-webkit-scrollbar-thumb {
    background: #185ADB;
    border-radius: 10px;
}

.reviews-container::-webkit-scrollbar-thumb:hover {
    background: #1245a8;
}

.review-item {
    padding: 15px;
    border: 1px solid #f0f0f0;
    border-radius: 8px;
    margin-bottom: 15px;
    transition: all 0.3s;
}

.review-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    border-color: #e0e0e0;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
}

.reviewer-info {
    display: flex;
    gap: 12px;
    align-items: flex-start;
}

.reviewer-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    object-fit: cover;
}

.reviewer-avatar-placeholder {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: #e0e0e0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.reviewer-details strong {
    display: block;
    color: #333;
    margin-bottom: 4px;
}

.review-rating {
    font-size: 0.9rem;
    letter-spacing: 2px;
}

.review-date {
    color: #999;
    font-size: 0.85rem;
}

.review-comment {
    color: #555;
    line-height: 1.6;
    font-size: 0.95rem;
}

.alert {
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 15px;
    font-size: 0.95rem;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.alert-warning a {
    color: #185ADB;
    font-weight: 600;
}

.btn-small {
    background: #185ADB;
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.3s;
    display: inline-block;
}

.btn-small:hover {
    background: #1245a8;
    transform: translateY(-2px);
}

@media (max-width: 1024px) {
    .class-details-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include 'footer.php'; ?>
