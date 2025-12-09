<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$review_success_message = $_SESSION['review_success'] ?? '';
$review_error_message = $_SESSION['review_error'] ?? '';
unset($_SESSION['review_success'], $_SESSION['review_error']);

$profile_message = '';
$progress_message = '';

$mbToLower = static function (string $value): string {
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($value, 'UTF-8');
    }
    return strtolower($value);
};

$buildReviewKey = static function (string $trainerName, string $classType) use ($mbToLower): string {
    return trim($mbToLower($trainerName)) . '|' . trim($mbToLower($classType));
};

$fetchUserRow = static function (mysqli $conn, int $user_id): ?array {
    $stmt = mysqli_prepare($conn, 'SELECT id, username, email, phone, age, gender FROM users WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);
    return $row ?: null;
};

$validateEmailUnique = static function (mysqli $conn, string $email, int $user_id): bool {
    $stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, 'si', $email, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $exists = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    return !$exists;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $new_username = trim($_POST['username'] ?? '');
        $new_email = trim($_POST['email'] ?? '');
        $new_phone = trim($_POST['phone'] ?? '');
        $new_age = trim($_POST['age'] ?? '');
        $new_gender = trim($_POST['gender'] ?? '');

        $errors = [];
        $age_value = null;

        if ($new_username === '') {
            $errors[] = 'Name is required.';
        }
        if ($new_email === '' || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid e-mail address.';
        }

        if ($new_age !== '') {
            $age_filter = filter_var(
                $new_age,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1, 'max_range' => 120]]
            );
            if ($age_filter === false) {
                $errors[] = 'Please enter a valid age between 1 and 120.';
            } else {
                $age_value = $age_filter;
            }
        }

        $allowed_genders = ['Male', 'Female', 'Prefer not to say', 'Erkek', 'Kadın', 'Belirtmek İstemiyorum'];
        if ($new_gender !== '' && !in_array($new_gender, $allowed_genders, true)) {
            $errors[] = 'Please choose a valid gender option.';
        }

        if (empty($errors) && !$validateEmailUnique($conn, $new_email, $user_id)) {
            $errors[] = 'This e-mail address is already registered.';
        }

        if (empty($errors)) {
            $stmt = mysqli_prepare(
                $conn,
                "UPDATE users SET username = ?, email = ?, phone = NULLIF(?, ''), age = NULLIF(?, -1), gender = NULLIF(?, '') WHERE id = ?"
            );
            if ($stmt) {
                $phone_param = $new_phone !== '' ? $new_phone : '';
                $gender_param = $new_gender !== '' ? $new_gender : '';
                $age_param = $age_value !== null ? $age_value : -1;

                mysqli_stmt_bind_param(
                    $stmt,
                    'sssisi',
                    $new_username,
                    $new_email,
                    $phone_param,
                    $age_param,
                    $gender_param,
                    $user_id
                );

                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['username'] = $new_username;
                    $profile_message = 'Profile updated successfully.';
                } else {
                    $profile_message = 'An error occurred while updating your profile.';
                }
                mysqli_stmt_close($stmt);
            } else {
                $profile_message = 'An error occurred while preparing the profile update.';
            }
        } else {
            $profile_message = $errors[0];
        }
    }

    if (isset($_POST['add_progress'])) {
        $weight_raw = str_replace(',', '.', trim($_POST['weight'] ?? ''));
        $height_raw = trim($_POST['height'] ?? '');

        $weight = filter_var($weight_raw, FILTER_VALIDATE_FLOAT);
        $height = filter_var($height_raw, FILTER_VALIDATE_FLOAT);

        if ($weight === false || $weight <= 0 || $height === false || $height <= 0) {
            $progress_message = 'Please enter valid height and weight values.';
        } else {
            $height_cm = (int) round($height);
            $bmi = $height_cm > 0 ? $weight / pow($height_cm / 100, 2) : 0;
            $bmi = round($bmi, 2);

            $stmt = mysqli_prepare(
                $conn,
                'INSERT INTO user_progress (user_id, weight, height, bmi) VALUES (?, ?, ?, ?)'
            );

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'idid', $user_id, $weight, $height_cm, $bmi);
                if (mysqli_stmt_execute($stmt)) {
                    $progress_message = 'Progress saved. BMI: ' . number_format($bmi, 2, ',', '.');
                } else {
                    $progress_message = 'Unable to save your progress right now.';
                }
                mysqli_stmt_close($stmt);
            } else {
                $progress_message = 'Unable to prepare progress entry.';
            }
        }
    }

    if (isset($_POST['submit_review'])) {
        $class_id_review = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;
        $rating = isset($_POST['rating']) ? (int) $_POST['rating'] : 0;
        $comment = trim($_POST['comment'] ?? '');

        $redirect_to = 'class_details.php';

        if ($class_id_review <= 0) {
            $_SESSION['review_error'] = 'Invalid class selected.';
            header('Location: ' . $redirect_to);
            exit;
        }

        if ($rating < 1 || $rating > 5) {
            $_SESSION['review_error'] = 'Rating must be between 1 and 5.';
            header('Location: ' . $redirect_to);
            exit;
        }

        if ($comment === '') {
            $_SESSION['review_error'] = 'Please share a short comment about the class.';
            header('Location: ' . $redirect_to);
            exit;
        }

        $booking_stmt = mysqli_prepare(
            $conn,
            'SELECT c.id FROM bookings b INNER JOIN classes c ON b.class_id = c.id WHERE b.user_id = ? AND b.class_id = ? AND c.date_time < NOW() LIMIT 1'
        );

        if (!$booking_stmt) {
            $_SESSION['review_error'] = 'Unable to validate the class booking.';
            header('Location: ' . $redirect_to);
            exit;
        }

        mysqli_stmt_bind_param($booking_stmt, 'ii', $user_id, $class_id_review);
        mysqli_stmt_execute($booking_stmt);
        mysqli_stmt_store_result($booking_stmt);

        if (mysqli_stmt_num_rows($booking_stmt) === 0) {
            mysqli_stmt_close($booking_stmt);
            $_SESSION['review_error'] = 'You can only review classes you have attended.';
            header('Location: ' . $redirect_to);
            exit;
        }
        mysqli_stmt_close($booking_stmt);

        $existing_stmt = mysqli_prepare(
            $conn,
            'SELECT id FROM reviews WHERE user_id = ? AND class_id = ? LIMIT 1'
        );

        if (!$existing_stmt) {
            $_SESSION['review_error'] = 'Unable to verify an existing review.';
            header('Location: ' . $redirect_to);
            exit;
        }

        mysqli_stmt_bind_param($existing_stmt, 'ii', $user_id, $class_id_review);
        mysqli_stmt_execute($existing_stmt);
        mysqli_stmt_store_result($existing_stmt);

        if (mysqli_stmt_num_rows($existing_stmt) > 0) {
            mysqli_stmt_close($existing_stmt);
            $_SESSION['review_error'] = 'You have already submitted a review for this class.';
            header('Location: ' . $redirect_to);
            exit;
        }
        mysqli_stmt_close($existing_stmt);

        $insert_review = mysqli_prepare(
            $conn,
            'INSERT INTO reviews (class_id, user_id, rating, comment) VALUES (?, ?, ?, ?)'
        );

        if ($insert_review) {
            mysqli_stmt_bind_param($insert_review, 'iiis', $class_id_review, $user_id, $rating, $comment);
            if (mysqli_stmt_execute($insert_review)) {
                $_SESSION['review_success'] = 'Thank you for sharing your feedback.';
            } else {
                $_SESSION['review_error'] = 'Unable to save your review right now.';
            }
            mysqli_stmt_close($insert_review);
        } else {
            $_SESSION['review_error'] = 'Unable to prepare the review statement.';
        }

        header('Location: ' . $redirect_to);
        exit;
    }
}

$user_row = $fetchUserRow($conn, $user_id);
if (!$user_row) {
    header('Location: logout.php');
    exit;
}

$upcoming_classes = [];
$upcoming_sql = "
    SELECT c.*, b.booking_date, b.id AS booking_id
    FROM bookings b
    INNER JOIN classes c ON b.class_id = c.id
    WHERE b.user_id = $user_id AND c.date_time >= NOW()
    ORDER BY c.date_time ASC
";

if ($upcoming_result = mysqli_query($conn, $upcoming_sql)) {
    while ($row = mysqli_fetch_assoc($upcoming_result)) {
        $upcoming_classes[] = $row;
    }
    mysqli_free_result($upcoming_result);
}

$past_classes = [];
$past_sql = "
    SELECT c.*, b.booking_date, b.id AS booking_id
    FROM bookings b
    INNER JOIN classes c ON b.class_id = c.id
    WHERE b.user_id = $user_id AND c.date_time < NOW()
    ORDER BY c.date_time DESC
";

if ($past_result = mysqli_query($conn, $past_sql)) {
    while ($row = mysqli_fetch_assoc($past_result)) {
        $past_classes[] = $row;
    }
    mysqli_free_result($past_result);
}

$trainerRatingSummary = [];
$trainerReviewList = [];

$ratingSummaryQuery = '
    SELECT c.trainer_name, c.class_type, AVG(r.rating) AS avg_rating, COUNT(*) AS review_count
    FROM reviews r
    INNER JOIN classes c ON r.class_id = c.id
    GROUP BY c.trainer_name, c.class_type
';

if ($ratingSummaryResult = mysqli_query($conn, $ratingSummaryQuery)) {
    while ($summaryRow = mysqli_fetch_assoc($ratingSummaryResult)) {
        $summaryKey = $buildReviewKey($summaryRow['trainer_name'], $summaryRow['class_type']);
        $trainerRatingSummary[$summaryKey] = [
            'avg' => round((float) $summaryRow['avg_rating'], 1),
            'count' => (int) $summaryRow['review_count'],
        ];
    }
    mysqli_free_result($ratingSummaryResult);
}

$ratingDetailsQuery = '
    SELECT c.trainer_name, c.class_type, r.rating, r.comment, r.created_at, u.username
    FROM reviews r
    INNER JOIN classes c ON r.class_id = c.id
    LEFT JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC, r.id DESC
';

if ($ratingDetailsResult = mysqli_query($conn, $ratingDetailsQuery)) {
    while ($detailRow = mysqli_fetch_assoc($ratingDetailsResult)) {
        $detailKey = $buildReviewKey($detailRow['trainer_name'], $detailRow['class_type']);
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
            'username' => $detailRow['username'] ?? 'Member',
        ];
    }
    mysqli_free_result($ratingDetailsResult);
}

$progress_history = [];
$progress_stmt = mysqli_prepare(
    $conn,
    'SELECT weight, height, bmi, record_date FROM user_progress WHERE user_id = ? ORDER BY record_date DESC LIMIT 5'
);
if ($progress_stmt) {
    mysqli_stmt_bind_param($progress_stmt, 'i', $user_id);
    mysqli_stmt_execute($progress_stmt);
    $result = mysqli_stmt_get_result($progress_stmt);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $progress_history[] = $row;
        }
        mysqli_free_result($result);
    }
    mysqli_stmt_close($progress_stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | GYM</title>
    <link rel="stylesheet" href="style.css">
   
</head>
<body>
<div class="dashboard-container">
    <div class="dash-header">
        <div class="dash-title">
            <h1>Welcome back, <?php echo htmlspecialchars($user_row['username']); ?></h1>
            <p>Track your workouts and stay motivated.</p>
        </div>
        <div>
            <a href="index.php" class="dash-btn">Home</a>
            <a href="logout.php" class="dash-btn" style="background:#c0392b;">Log out</a>
        </div>
    </div>

    <div class="dash-grid">
        <div class="left-col">
            <div class="dash-card">
                <div class="card-head">Account Details</div>
                <?php if ($profile_message !== ''): ?>
                    <p style="color:#1b4cd3; font-size:0.9rem;"><?php echo htmlspecialchars($profile_message); ?></p>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="username" class="dash-input" value="<?php echo htmlspecialchars($user_row['username']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>E-mail</label>
                        <input type="email" name="email" class="dash-input" value="<?php echo htmlspecialchars($user_row['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" class="dash-input" value="<?php echo htmlspecialchars($user_row['phone'] ?? ''); ?>">
                    </div>
                    <div class="input-row">
                        <div class="form-group">
                            <label>Age</label>
                            <input type="number" name="age" class="dash-input" value="<?php echo htmlspecialchars((string) ($user_row['age'] ?? '')); ?>">
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender" class="dash-input">
                                <option value="">Select</option>
                                <option value="Male" <?php echo ($user_row['gender'] === 'Male' || $user_row['gender'] === 'Erkek') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($user_row['gender'] === 'Female' || $user_row['gender'] === 'Kadın') ? 'selected' : ''; ?>>Female</option>
                                <option value="Prefer not to say" <?php echo ($user_row['gender'] === 'Prefer not to say' || $user_row['gender'] === 'Belirtmek İstemiyorum') ? 'selected' : ''; ?>>Prefer not to say</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="update_profile" class="btn-submit">Update</button>
                </form>
            </div>

            <div class="dash-card">
                <div class="card-head">Add Progress</div>
                <?php if ($progress_message !== ''): ?>
                    <p style="color:#1b4cd3; font-size:0.9rem;"><?php echo htmlspecialchars($progress_message); ?></p>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-group">
                        <label>Weight (kg)</label>
                        <input type="number" step="0.1" name="weight" class="dash-input" required>
                    </div>
                    <div class="form-group">
                        <label>Height (cm)</label>
                        <input type="number" name="height" class="dash-input" required>
                    </div>
                    <button type="submit" name="add_progress" class="btn-submit" style="background:#28a745;">Save</button>
                </form>
            </div>
        </div>

        <div class="mid-col">
            <div class="dash-card" style="border-top: 5px solid #1b4cd3;">
                <div class="card-head" style="color:#1b4cd3;">Upcoming Classes</div>
                <?php if (!empty($upcoming_classes)): ?>
                    <?php foreach ($upcoming_classes as $row): ?>
                        <?php
                        $upcomingKey = $buildReviewKey($row['trainer_name'], $row['class_type']);
                        $upcomingSummary = $trainerRatingSummary[$upcomingKey] ?? null;
                        $hasReviews = $upcomingSummary && $upcomingSummary['count'] > 0;
                        $width = $hasReviews ? max(0, min(100, ($upcomingSummary['avg'] / 5) * 100)) : 0;
                        ?>
                        <div class="lesson-item future">
                            <h4><?php echo htmlspecialchars($row['title']); ?> (<?php echo htmlspecialchars($row['class_type']); ?>)</h4>
                            <div class="lesson-meta">Date: <?php echo date('d.m.Y H:i', strtotime($row['date_time'])); ?> · Trainer: <?php echo htmlspecialchars($row['trainer_name']); ?></div>
                            <?php if ($hasReviews): ?>
                                <div class="comment-bar__stat">
                                    <span><?php echo number_format($upcomingSummary['avg'], 1); ?></span>
                                    <span class="star-rating-display">
                                        <span class="star-rating-display__fill" style="width: <?php echo $width; ?>%;"></span>
                                    </span>
                                    <span><?php echo $upcomingSummary['count']; ?> comments</span>
                                </div>
                            <?php endif; ?>
                            <div class="lesson-actions">
                                <?php if (!empty($row['video_link'])): ?>
                                    <a href="<?php echo htmlspecialchars($row['video_link']); ?>" target="_blank" class="link-btn">Join Live</a>
                                <?php endif; ?>
                                <a href="cancel_booking.php?id=<?php echo (int) $row['booking_id']; ?>" onclick="return confirm('Cancel this booking?');" class="cancel-btn">Cancel</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#777; text-align:center;">You have no upcoming classes.</p>
                <?php endif; ?>
            </div>

            <div class="dash-card" style="border-top: 5px solid ;">
                <div class="card-head" style="color:#444;">Completed Classes</div>
                <?php if ($review_success_message !== ''): ?>
                    <div class="flash-success"><?php echo htmlspecialchars($review_success_message); ?></div>
                <?php endif; ?>
                <?php if ($review_error_message !== ''): ?>
                    <div class="flash-error"><?php echo htmlspecialchars($review_error_message); ?></div>
                <?php endif; ?>
                <?php if (!empty($past_classes)): ?>
                    <?php foreach ($past_classes as $row): ?>
                        <?php
                        $pastKey = $buildReviewKey($row['trainer_name'], $row['class_type']);
                        $pastSummary = $trainerRatingSummary[$pastKey] ?? null;
                        $pastReviewList = $trainerReviewList[$pastKey] ?? [];
                        $panelId = 'review-panel-' . (int) $row['id'];
                        $hasPastReviews = $pastSummary && $pastSummary['count'] > 0;
                        $summaryWidth = $hasPastReviews ? max(0, min(100, ($pastSummary['avg'] / 5) * 100)) : 0;
                        $counts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
                        foreach ($pastReviewList as $item) {
                            $ratingValue = max(1, min(5, (int) $item['rating']));
                            $counts[$ratingValue]++;
                        }
                        $averageText = $hasPastReviews ? number_format($pastSummary['avg'], 1) : '0.0';
                        $totalReviews = $pastSummary['count'] ?? 0;

                        $rev_data = null;
                        $review_stmt = mysqli_prepare(
                            $conn,
                            'SELECT rating, comment, created_at FROM reviews WHERE user_id = ? AND class_id = ? LIMIT 1'
                        );
                        if ($review_stmt) {
                            $class_id_lookup = (int) $row['id'];
                            mysqli_stmt_bind_param($review_stmt, 'ii', $user_id, $class_id_lookup);
                            mysqli_stmt_execute($review_stmt);
                            $result = mysqli_stmt_get_result($review_stmt);
                            $rev_data = $result ? mysqli_fetch_assoc($result) : null;
                            mysqli_stmt_close($review_stmt);
                        }

                        $trainer_data = null;
                        $trainer_stmt = mysqli_prepare(
                            $conn,
                            'SELECT profile_photo, username FROM users WHERE username = ? LIMIT 1'
                        );
                        if ($trainer_stmt) {
                            mysqli_stmt_bind_param($trainer_stmt, 's', $row['trainer_name']);
                            mysqli_stmt_execute($trainer_stmt);
                            $result = mysqli_stmt_get_result($trainer_stmt);
                            $trainer_data = $result ? mysqli_fetch_assoc($result) : null;
                            mysqli_stmt_close($trainer_stmt);
                        }
                        ?>
                        <div class="lesson-item past">
                            <h4><?php echo htmlspecialchars($row['title']); ?></h4>
                            <div class="lesson-meta">Completed on <?php echo date('d.m.Y H:i', strtotime($row['date_time'])); ?></div>
                            <div class="trainer-info-card">
                                <?php if ($trainer_data && !empty($trainer_data['profile_photo'])): ?>
                                    <img src="data:image/jpeg;base64,<?php echo base64_encode($trainer_data['profile_photo']); ?>" alt="Trainer" class="trainer-avatar-small">
                                <?php else: ?>
                                    <?php $initial = strtoupper(substr($row['trainer_name'], 0, 1)); ?>
                                    <div class="trainer-avatar-placeholder-small"><?php echo htmlspecialchars($initial); ?></div>
                                <?php endif; ?>
                                <span class="trainer-name-card"><?php echo htmlspecialchars($row['trainer_name']); ?></span>
                                <span class="trainer-time-card">Category: <?php echo htmlspecialchars($row['class_type']); ?></span>
                            </div>
                            <?php if (!empty($row['description'])): ?>
                                <p class="class-description" style="font-size:0.9rem; color:#555; line-height:1.5;"><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
                            <?php endif; ?>
                            <div class="comment-bar">
                                <?php if ($hasPastReviews): ?>
                                    <div class="comment-bar__stat">
                                        <span class="comment-bar__score"><?php echo number_format($pastSummary['avg'], 1); ?></span>
                                        <span class="star-rating-display">
                                            <span class="star-rating-display__fill" style="width: <?php echo $summaryWidth; ?>%;"></span>
                                        </span>
                                        <span class="comment-bar__meta"><?php echo $totalReviews; ?> comments</span>
                                    </div>
                                <?php else: ?>
                                    <div class="comment-bar__stat comment-bar__stat--empty">No comments yet</div>
                                <?php endif; ?>
                                <button type="button" class="comment-trigger" data-target="<?php echo $panelId; ?>" data-open-text="Comments (<?php echo $totalReviews; ?>)" data-close-text="Close panel" aria-expanded="false" aria-controls="<?php echo $panelId; ?>">Comments (<?php echo $totalReviews; ?>)</button>
                            </div>
                            <div class="review-panel" id="<?php echo $panelId; ?>">
                                <div class="review-panel__header">
                                    <div class="review-panel__summary">
                                        <div class="review-panel__score"><?php echo $averageText; ?></div>
                                        <div class="review-panel__stars">
                                            <span class="star-rating-display">
                                                <span class="star-rating-display__fill" style="width: <?php echo $summaryWidth; ?>%;"></span>
                                            </span>
                                            <span class="review-panel__total"><?php echo $totalReviews; ?> comments</span>
                                        </div>
                                    </div>
                                    <div class="review-panel__filters">
                                        <button type="button" class="review-filter active" data-target-panel="<?php echo $panelId; ?>" data-filter="all">All ratings (<?php echo $totalReviews; ?>)</button>
                                        <?php for ($star = 5; $star >= 1; $star--): ?>
                                            <button type="button" class="review-filter" data-target-panel="<?php echo $panelId; ?>" data-filter="<?php echo $star; ?>"><?php echo $star; ?> star (<?php echo $counts[$star]; ?>)</button>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="review-panel__body">
                                    <div class="review-create">
                                        <?php if (!$rev_data): ?>
                                            <form method="POST" class="review-form-trendy">
                                                <input type="hidden" name="class_id" value="<?php echo (int) $row['id']; ?>">
                                                <div class="form-group-review">
                                                    <label>Rate this class</label>
                                                    <div class="star-rating-input">
                                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                                            <input type="radio" id="rating-<?php echo (int) $row['id']; ?>-<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                                                            <label for="rating-<?php echo (int) $row['id']; ?>-<?php echo $i; ?>">★</label>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                                <div class="form-group-review">
                                                    <label>Write your comment</label>
                                                    <textarea name="comment" rows="3" placeholder="Share details that will help other members decide." required></textarea>
                                                </div>
                                                <div class="review-form-actions">
                                                    <button type="submit" name="submit_review" class="btn-send-review">Send comment</button>
                                                </div>
                                            </form>
                                        <?php else: ?>
                                            <?php
                                            $userInitial = strtoupper(substr(($user_row['username'] ?? 'You'), 0, 1));
                                            $userWidth = max(0, min(100, ($rev_data['rating'] / 5) * 100));
                                            ?>
                                            <div class="review-card review-card--yours">
                                                <div class="review-card__avatar"><?php echo htmlspecialchars($userInitial); ?></div>
                                                <div class="review-card__content">
                                                    <div class="review-card__header">
                                                        <span class="star-rating-display star-rating-display--sm">
                                                            <span class="star-rating-display__fill" style="width: <?php echo $userWidth; ?>%;"></span>
                                                        </span>
                                                        <span class="review-card__name">You</span>
                                                        <span class="review-card__verified">Verified attendee</span>
                                                    </div>
                                                    <?php if (!empty($rev_data['created_at'])): ?>
                                                        <div class="review-card__timestamp">Reviewed on <?php echo date('F j, Y', strtotime($rev_data['created_at'])); ?></div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($rev_data['comment'])): ?>
                                                        <div class="review-card__comment"><?php echo nl2br(htmlspecialchars($rev_data['comment'])); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($hasPastReviews): ?>
                                        <div class="review-feed review-panel__list">
                                            <?php foreach ($pastReviewList as $reviewItem): ?>
                                                <?php
                                                $width = max(0, min(100, ($reviewItem['rating'] / 5) * 100));
                                                $reviewerName = $reviewItem['username'] ?? 'Member';
                                                $avatarLetter = strtoupper(substr($reviewerName, 0, 1));
                                                ?>
                                                <article class="review-card" data-rating="<?php echo (int) $reviewItem['rating']; ?>">
                                                    <div class="review-card__avatar"><?php echo htmlspecialchars($avatarLetter); ?></div>
                                                    <div class="review-card__content">
                                                        <div class="review-card__header">
                                                            <span class="star-rating-display star-rating-display--sm">
                                                                <span class="star-rating-display__fill" style="width: <?php echo $width; ?>%;"></span>
                                                            </span>
                                                            <span class="review-card__name"><?php echo htmlspecialchars($reviewerName); ?></span>
                                                            <span class="review-card__verified">Verified attendee</span>
                                                        </div>
                                                        <div class="review-card__timestamp">Reviewed on <?php echo date('F j, Y', strtotime($reviewItem['created_at'])); ?></div>
                                                        <?php if (!empty($reviewItem['comment'])): ?>
                                                            <div class="review-card__comment"><?php echo nl2br(htmlspecialchars($reviewItem['comment'])); ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </article>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="review-panel__empty review-panel__empty--filtered" style="display:none;">No comments match this filter.</div>
                                    <?php else: ?>
                                        <div class="review-panel__empty">No comments yet. Be the first to share your experience.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <button class="btn-card" disabled>Completed</button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#777; text-align:center;">You have no completed classes yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="right-col">
            <div class="dash-card">
                <div class="card-head">Progress History</div>
                <?php if (!empty($progress_history)): ?>
                    <?php foreach ($progress_history as $entry): ?>
                        <div class="prog-row">
                            <div><strong><?php echo number_format((float) $entry['weight'], 1, '.', ''); ?> kg</strong></div>
                            <div class="prog-bmi">BMI: <?php echo number_format((float) $entry['bmi'], 1, '.', ''); ?></div>
                            <div style="font-size:0.75rem; color:#777;"><?php echo date('d.m.Y', strtotime($entry['record_date'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align:center; color:#999;">No progress entries yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    function applyReviewFilter(panel, filter) {
        if (!panel) { return; }
        const list = panel.querySelector('.review-panel__list');
        const items = list ? list.querySelectorAll('.review-card') : [];
        let visible = 0;
        items.forEach(function (item) {
            const rating = item.getAttribute('data-rating');
            const show = filter === 'all' || rating === filter;
            item.style.display = show ? 'flex' : 'none';
            if (show) { visible++; }
        });
        const emptyFiltered = panel.querySelector('.review-panel__empty--filtered');
        if (emptyFiltered) {
            emptyFiltered.style.display = visible === 0 ? 'block' : 'none';
        }
    }

    document.querySelectorAll('.comment-trigger').forEach(function (button) {
        const targetId = button.getAttribute('data-target');
        const openText = button.getAttribute('data-open-text') || 'Show comments';
        const closeText = button.getAttribute('data-close-text') || 'Close panel';
        const panel = document.getElementById(targetId);
        if (!panel) { return; }
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

    document.querySelectorAll('.review-filter').forEach(function (button) {
        button.addEventListener('click', function () {
            const panelId = button.getAttribute('data-target-panel');
            const panel = document.getElementById(panelId);
            if (!panel) { return; }
            const container = button.closest('.review-panel__filters');
            if (container) {
                container.querySelectorAll('.review-filter').forEach(function (btn) {
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
