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
    <style>
        body { background-color: #f0f2f5; font-family: 'Poppins', Arial, sans-serif; }
        .dashboard-container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .dash-header { background: #1b4cd3; color: #fff; padding: 30px; border-radius: 15px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; box-shadow: 0 10px 20px rgba(24, 90, 219, 0.2); }
        .dash-title h1 { margin: 0; font-size: 1.8rem; }
        .dash-btn { background: rgba(255,255,255,0.2); color: #fff; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; transition: 0.3s; margin-left: 10px; display: inline-block; }
        .dash-btn:hover { background: #fff; color: #1b4cd3; }
        .dash-grid { display: grid; grid-template-columns: 1fr 1.5fr 1fr; gap: 25px; }
        .dash-card { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card-head { border-bottom: 2px solid #f0f2f5; padding-bottom: 15px; margin-bottom: 20px; font-size: 1.1rem; font-weight: 800; color: #333; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: #555; margin-bottom: 5px; }
        .dash-input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; outline: none; transition: 0.3s; }
        .dash-input:focus { border-color: #1b4cd3; }
        .btn-submit { width: 100%; padding: 12px; background: #1b4cd3; color: #fff; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .btn-submit:hover { background: #153bb0; }
        .input-row { display: flex; gap: 12px; }
        .input-row .form-group { flex: 1; }
        .lesson-item { padding: 15px; border-radius: 10px; margin-bottom: 15px; border: 1px solid transparent; position: relative; }
        .lesson-item.future { background: #eef2ff; border-color: #c7d2fe; }
        .lesson-item.future h4 { color: #1b4cd3; margin: 0 0 5px; }
        .lesson-item.past { background: #f8f9fa; border-color: #e9ecef; }
        .lesson-item.past h4 { color: #333; margin: 0 0 5px; }
        .lesson-meta { font-size: 0.85rem; color: #666; margin-bottom: 10px; }
        .lesson-actions { display: flex; gap: 10px; }
        .link-btn, .cancel-btn { font-size: 0.8rem; font-weight: bold; text-decoration: none; }
        .link-btn { color: #1b4cd3; }
        .cancel-btn { color: #c0392b; }
        .trainer-info-card { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
        .trainer-avatar-small { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .trainer-avatar-placeholder-small { width: 40px; height: 40px; border-radius: 50%; background: #1b4cd3; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .trainer-name-card { font-weight: 600; color: #333; }
        .trainer-time-card { font-size: 0.8rem; color: #666; }
        .comment-bar { display: flex; justify-content: space-between; align-items: center; margin-top: 10px; }
        .comment-bar__stat { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: #444; }
        .comment-bar__stat--empty { color: #999; }
        .comment-trigger { border: none; background: transparent; color: #1b4cd3; font-weight: 600; cursor: pointer; }
        .comment-trigger.open { color: #c0392b; }
        .review-panel { border: 1px solid #dfe6ff; border-radius: 16px; margin-top: 15px; display: none; padding: 22px; background: #fdfdff; box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08); }
        .review-panel.open { display: block; }
        .review-panel__header { display: flex; flex-direction: column; gap: 12px; margin-bottom: 18px; }
        .review-panel__summary { display: flex; align-items: center; gap: 18px; }
        .review-panel__score { font-size: 2.4rem; font-weight: 800; color: #1b4cd3; }
        .review-panel__stars { display: flex; align-items: center; gap: 12px; }
        .star-rating-display { position: relative; display: inline-block; width: 96px; height: 20px; color: #d1d7f3; font-size: 20px; line-height: 20px; }
        .star-rating-display::before { content: '\2605\2605\2605\2605\2605'; position: absolute; left: 0; top: 0; width: 100%; height: 100%; color: #d1d7f3; }
        .star-rating-display__fill { position: absolute; left: 0; top: 0; height: 100%; overflow: hidden; color: #ffb400; }
        .star-rating-display__fill::before { content: '\2605\2605\2605\2605\2605'; position: absolute; left: 0; top: 0; }
        .star-rating-display--sm { width: 80px; font-size: 17px; height: 18px; line-height: 18px; }
        .review-panel__body { display: flex; flex-direction: column; gap: 22px; }
        .review-panel__filters { display: flex; flex-wrap: wrap; gap: 10px; }
        .review-filter { border: 1px solid #1b4cd3; border-radius: 30px; padding: 6px 16px; background: #fff; color: #1b4cd3; cursor: pointer; font-size: 0.75rem; font-weight: 600; transition: all 0.2s ease; }
        .review-filter:hover { background: rgba(27, 76, 211, 0.08); }
        .review-filter.active { background: #1b4cd3; color: #fff; box-shadow: 0 8px 16px rgba(27, 76, 211, 0.25); }
        .review-panel__list { display: flex; flex-direction: column; gap: 18px; }
        .review-panel__empty { font-size: 0.88rem; color: #7b82a9; text-align: center; margin-top: 10px; }
        .review-panel__empty--filtered { font-size: 0.85rem; color: #7b82a9; text-align: center; }
        .review-create { background: linear-gradient(135deg, rgba(27, 76, 211, 0.12), rgba(90, 125, 255, 0.08)); border: 1px solid #dbe2ff; border-radius: 16px; padding: 18px; box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.4); }
        .review-form-trendy { display: flex; flex-direction: column; gap: 16px; }
        .form-group-review { margin: 0; display: flex; flex-direction: column; gap: 8px; }
        .form-group-review label { font-size: 0.8rem; font-weight: 700; color: #27325a; letter-spacing: 0.02em; text-transform: uppercase; }
        .star-rating-input { display: inline-flex; flex-direction: row-reverse; gap: 6px; align-items: center; }
        .star-rating-input input { display: none; }
        .star-rating-input label { font-size: 1.8rem; color: #c7cfef; cursor: pointer; transition: color 0.2s ease, transform 0.2s ease; }
        .star-rating-input label:hover,
        .star-rating-input label:hover ~ label { color: #ffb400; transform: translateY(-1px); }
        .star-rating-input input:checked ~ label { color: #ffb400; }
        .star-rating-input input:focus-visible + label { outline: 2px solid #1b4cd3; outline-offset: 4px; border-radius: 4px; }
        .review-form-trendy textarea { width: 100%; border-radius: 14px; border: 1px solid #d5dcf7; padding: 14px 16px; font-size: 0.95rem; resize: vertical; min-height: 120px; transition: border-color 0.2s ease, box-shadow 0.2s ease; background: #fff; }
        .review-form-trendy textarea:focus { border-color: #1b4cd3; box-shadow: 0 0 0 3px rgba(27, 76, 211, 0.15); outline: none; }
        .review-form-actions { display: flex; justify-content: flex-end; }
        .btn-send-review { background: linear-gradient(135deg, #1b4cd3, #5a7dff); color: #fff; border: none; padding: 11px 26px; border-radius: 999px; font-weight: 600; cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease; letter-spacing: 0.02em; }
        .btn-send-review:hover { transform: translateY(-1px); box-shadow: 0 12px 24px rgba(27, 76, 211, 0.25); }
        .review-feed { display: flex; flex-direction: column; gap: 14px; }
        .review-card { display: flex; gap: 14px; background: #fff; border: 1px solid #ecf0ff; border-radius: 18px; padding: 16px 18px; box-shadow: 0 16px 28px rgba(15, 23, 42, 0.06); position: relative; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .review-card:hover { transform: translateY(-2px); box-shadow: 0 18px 32px rgba(15, 23, 42, 0.1); }
        .review-card--yours { border: 1px solid rgba(27, 76, 211, 0.6); background: linear-gradient(135deg, rgba(27, 76, 211, 0.15), rgba(255, 255, 255, 0.9)); }
        .review-card__avatar { flex: 0 0 48px; width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, #1b4cd3, #5a7dff); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.1rem; text-transform: uppercase; letter-spacing: 0.02em; }
        .review-card__content { flex: 1; display: flex; flex-direction: column; gap: 8px; }
        .review-card__meta { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .review-card__name { font-weight: 700; color: #1f2a4a; font-size: 0.95rem; }
        .review-card__timestamp { font-size: 0.75rem; color: #8a94b7; }
        .review-card__comment { font-size: 0.95rem; color: #2d3557; line-height: 1.55; white-space: pre-wrap; }
        .review-card__stars { display: flex; align-items: center; gap: 10px; font-size: 0.82rem; color: #4b4f68; }
        .review-card__badge { display: inline-flex; align-items: center; gap: 6px; font-size: 0.7rem; font-weight: 700; color: #1b4cd3; background: rgba(27, 76, 211, 0.12); padding: 4px 12px; border-radius: 999px; text-transform: uppercase; letter-spacing: 0.03em; }
        .review-card__divider { height: 1px; background: #e7ebff; margin: 4px 0; }
        .review-card__likes { font-size: 0.78rem; color: #8993be; display: flex; align-items: center; gap: 12px; }
        .review-summary__score { font-weight: 700; font-size: 0.9rem; margin-left: 8px; }
        .btn-card { width: 100%; padding: 10px; border-radius: 8px; border: none; background: #ccc; color: #555; font-weight: 600; cursor: not-allowed; margin-top: 10px; }
        .btn-card { width: 100%; padding: 10px; border-radius: 8px; border: none; background: #ccc; color: #555; font-weight: 600; cursor: not-allowed; margin-top: 10px; }
        .prog-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f0f0f0; font-size: 0.85rem; }
        .prog-bmi { background: #e0f2f1; color: #00695c; padding: 3px 8px; border-radius: 20px; font-weight: bold; font-size: 0.75rem; }
        .flash-success { color: #1b5e20; background: #e8f5e9; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center; }
        .flash-error { color: #b71c1c; background: #ffebee; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center; }
        @media (max-width: 1024px) { .dash-grid { grid-template-columns: 1fr; } }
    </style>
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
                                    <span><?php echo $upcomingSummary['count']; ?> reviews</span>
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

            <div class="dash-card" style="border-top: 5px solid #666;">
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
                                        <span class="comment-bar__meta"><?php echo $totalReviews; ?> reviews</span>
                                    </div>
                                <?php else: ?>
                                    <div class="comment-bar__stat comment-bar__stat--empty">No reviews yet</div>
                                <?php endif; ?>
                                <button type="button" class="comment-trigger" data-target="<?php echo $panelId; ?>" data-open-text="Reviews (<?php echo $totalReviews; ?>)" data-close-text="Close panel" aria-expanded="false" aria-controls="<?php echo $panelId; ?>">Reviews (<?php echo $totalReviews; ?>)</button>
                            </div>
                            <div class="review-panel" id="<?php echo $panelId; ?>">
                                <div class="review-panel__header">
                                    <div class="review-panel__summary">
                                        <div class="review-panel__score"><?php echo $averageText; ?></div>
                                        <div class="review-panel__stars">
                                            <span class="star-rating-display">
                                                <span class="star-rating-display__fill" style="width: <?php echo $summaryWidth; ?>%;"></span>
                                            </span>
                                            <span class="review-panel__total"><?php echo $totalReviews; ?> reviews</span>
                                        </div>
                                    </div>
                                    <div class="review-panel__filters">
                                        <button type="button" class="review-filter active" data-target-panel="<?php echo $panelId; ?>" data-filter="all">All (<?php echo $totalReviews; ?>)</button>
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
                                                    <label>Share your thoughts</label>
                                                    <textarea name="comment" rows="3" placeholder="Tell the community what stood out for you..." required></textarea>
                                                </div>
                                                <div class="review-form-actions">
                                                    <button type="submit" name="submit_review" class="btn-send-review">Send review</button>
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
                                                    <div class="review-card__meta">
                                                        <span class="review-card__name">You</span>
                                                        <?php if (!empty($rev_data['created_at'])): ?>
                                                            <span class="review-card__timestamp"><?php echo date('d.m.Y', strtotime($rev_data['created_at'])); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="review-card__stars">
                                                        <span class="star-rating-display star-rating-display--sm">
                                                            <span class="star-rating-display__fill" style="width: <?php echo $userWidth; ?>%;"></span>
                                                        </span>
                                                        <span><?php echo (int) $rev_data['rating']; ?>/5</span>
                                                        <span class="review-card__badge">Your review</span>
                                                    </div>
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
                                                        <div class="review-card__meta">
                                                            <span class="review-card__name"><?php echo htmlspecialchars($reviewerName); ?></span>
                                                            <span class="review-card__timestamp"><?php echo date('d.m.Y', strtotime($reviewItem['created_at'])); ?></span>
                                                        </div>
                                                        <div class="review-card__stars">
                                                            <span class="star-rating-display star-rating-display--sm">
                                                                <span class="star-rating-display__fill" style="width: <?php echo $width; ?>%;"></span>
                                                            </span>
                                                            <span><?php echo (int) $reviewItem['rating']; ?>/5</span>
                                                        </div>
                                                        <?php if (!empty($reviewItem['comment'])): ?>
                                                            <p class="review-card__comment"><?php echo nl2br(htmlspecialchars($reviewItem['comment'])); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </article>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="review-panel__empty review-panel__empty--filtered" style="display:none;">No reviews for this filter.</div>
                                    <?php else: ?>
                                        <div class="review-panel__empty">No reviews yet. Be the first to share your thoughts.</div>
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
        const openText = button.getAttribute('data-open-text') || 'Show reviews';
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
