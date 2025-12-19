<?php
session_start();
include 'db.php';
$page_title = "Class Details | GYM";

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$class_id = intval($_GET['id']);

$class_sql = "SELECT * FROM classes WHERE id = $class_id";
$class_result = mysqli_query($conn, $class_sql);
$class_info = mysqli_fetch_assoc($class_result);

if (!$class_info) {
    header("Location: index.php");
    exit;
}

$trainer_sql = "SELECT * FROM users WHERE username = '{$class_info['trainer_name']}' AND role = 'instructor'";
$trainer_result = mysqli_query($conn, $trainer_sql);
$trainer_info = mysqli_fetch_assoc($trainer_result);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isset($_SESSION['user_id'])) {
        $error_msg = " You need to log in to add a comment!";
    } else {
        $user_id = $_SESSION['user_id'];
        $rating = intval($_POST['rating']);
        $comment = mysqli_real_escape_string($conn, $_POST['comment']);

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

$reviews_sql = "SELECT r.*, u.username, u.profile_photo FROM reviews r 
                JOIN users u ON r.user_id = u.id 
                WHERE r.class_id = $class_id 
                ORDER BY r.created_at DESC";
$reviews_result = mysqli_query($conn, $reviews_sql);
$total_reviews = mysqli_num_rows($reviews_result);

$avg_sql = "SELECT AVG(rating) as avg_rating FROM reviews WHERE class_id = $class_id";
$avg_result = mysqli_query($conn, $avg_sql);
$avg_data = mysqli_fetch_assoc($avg_result);
$avg_rating = $avg_data['avg_rating'] ? round($avg_data['avg_rating'], 1) : 0;

include 'header.php';
?>

<style>
    .page {max-width: 960px; margin: 0 auto; padding: 24px; background: #fff;}
    .section {border: 1px solid #e0e0e0; border-radius: 6px; padding: 16px; margin-bottom: 16px;}
    .section h2 {margin: 0 0 10px 0; font-size: 20px;}
    .title {margin: 0 0 6px 0; font-size: 26px;}
    .muted {color: #666; margin: 0 0 10px 0;}
    .info-row {display: flex; gap: 8px; margin-bottom: 6px;}
    .info-label {font-weight: 600; color: #333; min-width: 110px;}
    .badge {display: inline-block; padding: 3px 8px; background: #eef3ff; color: #264aaf; border-radius: 4px; font-size: 12px; font-weight: 600;}
    .trainer {display: flex; align-items: center; gap: 12px;}
    .avatar {width: 56px; height: 56px; border-radius: 50%; object-fit: cover; border: 1px solid #ddd;}
    .avatar-placeholder {width: 56px; height: 56px; border-radius: 50%; border: 1px solid #ddd; display: flex; align-items: center; justify-content: center; background: #f5f5f5; font-weight: 600;}
    .rating {display: flex; align-items: center; gap: 10px;}
    .rating-score {font-size: 28px; font-weight: 700;}
    .pill {padding: 3px 8px; background: #f5f5f5; border-radius: 12px; font-size: 12px; color: #555;}
    .note {padding: 10px; border-radius: 4px; margin-bottom: 12px;}
    .note.success {background: #e6f7e6; border: 1px solid #c5e6c5; color: #1e6b1e;}
    .note.error {background: #ffecec; border: 1px solid #ffb3b3; color: #b80000;}
    .note.warn {background: #fff6e5; border: 1px solid #ffe1a1; color: #9c6b00;}
    form .field {display: flex; flex-direction: column; gap: 6px; margin-bottom: 12px;}
    form input, form select, form textarea {padding: 8px; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 14px;}
    form button {padding: 10px 14px; background: #1b4cd3; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;}
    .review {border-top: 1px solid #eee; padding-top: 10px; margin-top: 10px;}
    .review-header {display: flex; justify-content: space-between; align-items: center;}
    .review-meta {display: flex; align-items: center; gap: 10px;}
    .review-name {font-weight: 600;}
    .stars {color: #f4b400;}
    .empty {color: #666; padding: 12px 0;}
</style>

<div class="page">
    <div class="section">
        <h1 class="title"><?php echo htmlspecialchars($class_info['title']); ?></h1>
        <p class="muted"><?php echo htmlspecialchars($class_info['description']); ?></p>
        <div class="info-row"><span class="info-label">Class Type</span><span class="badge"><?php echo htmlspecialchars($class_info['class_type']); ?></span></div>
        <div class="info-row"><span class="info-label">Date & Time</span><span><?php echo date("d.m.Y H:i", strtotime($class_info['date_time'])); ?></span></div>
        <div class="info-row"><span class="info-label">Capacity</span><span><?php echo (int) $class_info['capacity']; ?> people</span></div>
    </div>

    <div class="section">
        <h2>Trainer</h2>
        <div class="trainer">
            <?php if ($trainer_info && $trainer_info['profile_photo']): ?>
                <img class="avatar" src="data:image/jpeg;base64,<?php echo base64_encode($trainer_info['profile_photo']); ?>" alt="Trainer">
            <?php else: ?>
                <?php $initial = strtoupper(substr($class_info['trainer_name'], 0, 1)); ?>
                <div class="avatar-placeholder"><?php echo htmlspecialchars($initial); ?></div>
            <?php endif; ?>
            <div>
                <div class="review-name"><?php echo htmlspecialchars($class_info['trainer_name']); ?></div>
                <div class="muted">Instructor</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Course Rating</h2>
        <div class="rating">
            <div class="rating-score"><?php echo $avg_rating; ?></div>
            <div class="stars">
                <?php for ($i = 1; $i <= 5; $i++) { echo ($i <= round($avg_rating)) ? '★' : '☆'; } ?>
            </div>
            <div class="pill"><?php echo $total_reviews; ?> comments</div>
        </div>
    </div>

    <div class="section">
        <h2>Add Comment</h2>
        <?php if (isset($success_msg)): ?><div class="note success"><?php echo $success_msg; ?></div><?php endif; ?>
        <?php if (isset($error_msg)): ?><div class="note error"><?php echo $error_msg; ?></div><?php endif; ?>
        <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="note warn">Please <a href="login.php">log in</a> to add a comment.</div>
        <?php else: ?>
            <form method="POST">
                <div class="field">
                    <label for="rating">Rate this class</label>
                    <select name="rating" id="rating" required>
                        <option value="">Select</option>
                        <option value="5">5 - Excellent</option>
                        <option value="4">4 - Good</option>
                        <option value="3">3 - Average</option>
                        <option value="2">2 - Poor</option>
                        <option value="1">1 - Very Poor</option>
                    </select>
                </div>
                <div class="field">
                    <label for="comment">Your comment</label>
                    <textarea name="comment" id="comment" rows="3" required></textarea>
                </div>
                <button type="submit" name="submit_review">Submit</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>Comments</h2>
        <?php if ($total_reviews > 0): ?>
            <?php while ($review = mysqli_fetch_assoc($reviews_result)) { ?>
                <div class="review">
                    <div class="review-header">
                        <div class="review-meta">
                            <?php if ($review['profile_photo']) { ?>
                                <img class="avatar" src="data:image/jpeg;base64,<?php echo base64_encode($review['profile_photo']); ?>" alt="User">
                            <?php } else { ?>
                                <div class="avatar-placeholder">👤</div>
                            <?php } ?>
                            <div>
                                <div class="review-name"><?php echo htmlspecialchars($review['username']); ?></div>
                                <div class="stars">
                                    <?php for ($i = 1; $i <= 5; $i++) { echo ($i <= $review['rating']) ? '★' : '☆'; } ?>
                                </div>
                            </div>
                        </div>
                        <div class="muted"><?php echo date("d.m.Y", strtotime($review['created_at'])); ?></div>
                    </div>
                    <div class="muted"><?php echo htmlspecialchars($review['comment']); ?></div>
                </div>
            <?php } ?>
        <?php else: ?>
            <div class="empty">No comments yet.</div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
