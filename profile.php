<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$class_categories = include __DIR__ . '/class_categories.php';
if (!is_array($class_categories) || empty($class_categories)) {
    $class_categories = [
        'Yoga' => 'Yoga',
        'Pilates' => 'Pilates',
        'HIIT' => 'HIIT',
        'Zumba' => 'Zumba',
        'Fitness' => 'Fitness'
    ];
}

$message = '';
$message_type = '';
$progress_message = '';
$progress_type = '';
$body_fat_message = '';
$body_fat_type = '';
$body_fat_percentage = null;
$body_fat_category = '';
$shoulder_waist_ratio = null;
$lean_mass = null;
$review_message = '';
$review_type = '';

$user_sql = "SELECT id, username, email, phone, age, gender, role, profile_photo, profile_pic FROM users WHERE id = $user_id LIMIT 1";
$user_res = mysqli_query($conn, $user_sql);
$user_row = $user_res ? mysqli_fetch_assoc($user_res) : null;
if (!$user_row) {
    header('Location: logout.php');
    exit;
}

$detectMimeType = static function (string $path): ?string {
    if (!is_readable($path)) {
        return null;
    }
    if (function_exists('finfo_open')) {
        $f = finfo_open(FILEINFO_MIME_TYPE);
        if ($f) {
            $m = finfo_file($f, $path);
            finfo_close($f);
            if ($m !== false) {
                return $m;
            }
        }
    }
    if (function_exists('mime_content_type')) {
        return mime_content_type($path);
    }
    return null;
};

if (isset($_POST['upload_profile_photo'])) {
    if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
        $message = 'Please choose an image first.';
        $message_type = 'error';
    } else {
        $tmp = $_FILES['profile_photo']['tmp_name'];
        $size = $_FILES['profile_photo']['size'];
        $mime = $detectMimeType($tmp);
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!$mime || !in_array($mime, $allowed, true)) {
            $message = 'Allowed formats: JPG, PNG, GIF, WebP.';
            $message_type = 'error';
        } elseif ($size > 5 * 1024 * 1024) {
            $message = 'Max file size is 5MB.';
            $message_type = 'error';
        } else {
            $data = file_get_contents($tmp);
            if ($data === false) {
                $message = 'Could not read file.';
                $message_type = 'error';
            } else {
                $safe = mysqli_real_escape_string($conn, $data);
                $sql = "UPDATE users SET profile_photo='$safe' WHERE id=$user_id";
                if (mysqli_query($conn, $sql)) {
                    $message = 'Profile photo updated.';
                    $message_type = 'success';
                    $user_row['profile_photo'] = $data;
                } else {
                    $message = 'Database error while saving photo.';
                    $message_type = 'error';
                }
            }
        }
    }
}

if (isset($_POST['update_profile'])) {
    $new_username = trim($_POST['username'] ?? '');
    $new_email = trim($_POST['email'] ?? '');
    $new_phone = trim($_POST['phone'] ?? '');
    $new_age = trim($_POST['age'] ?? '');
    $new_gender = trim($_POST['gender'] ?? '');
    $specialties_raw = isset($_POST['specialties']) && is_array($_POST['specialties']) ? $_POST['specialties'] : [];

    $errors = [];
    $age_val = null;

    if ($new_username === '' || $new_email === '') {
        $errors[] = 'Name and email are required.';
    }
    if ($new_email !== '' && !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if ($new_age !== '') {
        $age_val = filter_var($new_age, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 120]]);
        if ($age_val === false) {
            $errors[] = 'Age must be between 1 and 120.';
        }
    }
    $allowed_genders = ['Male', 'Female', 'Prefer not to say', 'Erkek', 'Kadın', 'Belirtmek İstemiyorum'];
    if ($new_gender !== '' && !in_array($new_gender, $allowed_genders, true)) {
        $errors[] = 'Choose a valid gender option.';
    }

    if (empty($errors)) {
        $safe_username = mysqli_real_escape_string($conn, $new_username);
        $safe_email = mysqli_real_escape_string($conn, $new_email);
        $safe_phone = $new_phone !== '' ? "'" . mysqli_real_escape_string($conn, $new_phone) . "'" : 'NULL';
        $safe_gender = $new_gender !== '' ? "'" . mysqli_real_escape_string($conn, $new_gender) . "'" : 'NULL';
        $age_sql = $age_val !== null ? (int) $age_val : 'NULL';

        $check_sql = "SELECT id FROM users WHERE email='$safe_email' AND id <> $user_id LIMIT 1";
        $check_res = mysqli_query($conn, $check_sql);
        if ($check_res && mysqli_num_rows($check_res) > 0) {
            $errors[] = 'This email is already registered.';
        }

        if (empty($errors)) {
            $update_sql = "UPDATE users SET username='$safe_username', email='$safe_email', phone=$safe_phone, age=$age_sql, gender=$safe_gender WHERE id=$user_id";
            if (mysqli_query($conn, $update_sql)) {
                if ($user_row['role'] === 'instructor') {
                    $valid_keys = array_keys($class_categories);
                    $selected = array_values(array_intersect($specialties_raw, $valid_keys));
                    $del = mysqli_prepare($conn, 'DELETE FROM instructor_specialties WHERE user_id = ?');
                    if ($del) {
                        mysqli_stmt_bind_param($del, 'i', $user_id);
                        mysqli_stmt_execute($del);
                        mysqli_stmt_close($del);
                    }
                    if (!empty($selected)) {
                        $ins = mysqli_prepare($conn, 'INSERT INTO instructor_specialties (user_id, class_type) VALUES (?, ?)');
                        if ($ins) {
                            foreach ($selected as $cat) {
                                mysqli_stmt_bind_param($ins, 'is', $user_id, $cat);
                                mysqli_stmt_execute($ins);
                            }
                            mysqli_stmt_close($ins);
                        }
                    }
                }

                $_SESSION['username'] = $new_username;
                $user_row['username'] = $new_username;
                $user_row['email'] = $new_email;
                $user_row['phone'] = $new_phone;
                $user_row['age'] = $age_val;
                $user_row['gender'] = $new_gender;

                $message = 'Profile updated.';
                $message_type = 'success';
            } else {
                $message = 'Profile could not be updated.';
                $message_type = 'error';
            }
        }
    }

    if (!empty($errors)) {
        $message = $errors[0];
        $message_type = 'error';
    }
}

if (isset($_POST['add_progress'])) {
    $weight_raw = str_replace(',', '.', trim($_POST['weight'] ?? ''));
    $height_raw = trim($_POST['height'] ?? '');

    $weight = filter_var($weight_raw, FILTER_VALIDATE_FLOAT);
    $height = filter_var($height_raw, FILTER_VALIDATE_FLOAT);

    if ($weight === false || $weight <= 0 || $height === false || $height <= 0) {
        $progress_message = 'Enter valid height and weight.';
        $progress_type = 'error';
    } else {
        $height_cm = (int) round($height);
        $bmi = $height_cm > 0 ? $weight / pow($height_cm / 100, 2) : 0;
        $bmi = round($bmi, 2);

        $insert_sql = sprintf(
            'INSERT INTO user_progress (user_id, weight, height, bmi) VALUES (%d, %.2f, %d, %.2f)',
            $user_id,
            $weight,
            $height_cm,
            $bmi
        );

        if (mysqli_query($conn, $insert_sql)) {
            $progress_message = 'Progress saved. BMI: ' . number_format($bmi, 2, ',', '.');
            $progress_type = 'success';
        } else {
            $progress_message = 'Progress could not be saved.';
            $progress_type = 'error';
        }
    }
}

$instructor_specialties = [];
if ($user_row['role'] === 'instructor') {
    $sp = mysqli_prepare($conn, 'SELECT class_type FROM instructor_specialties WHERE user_id = ? ORDER BY class_type ASC');
    if ($sp) {
        mysqli_stmt_bind_param($sp, 'i', $user_id);
        mysqli_stmt_execute($sp);
        $sp_res = mysqli_stmt_get_result($sp);
        if ($sp_res) {
            while ($srow = mysqli_fetch_assoc($sp_res)) {
                $instructor_specialties[] = $srow['class_type'];
            }
            mysqli_free_result($sp_res);
        }
        mysqli_stmt_close($sp);
    }
}

$role_labels = [
    'user' => 'Student',
    'instructor' => 'Instructor',
    'admin' => 'Administrator',
];
$role_label = $role_labels[$user_row['role']] ?? 'Member';

$latest_progress = null;
$lp_res = mysqli_query($conn, "SELECT weight, height, bmi, record_date FROM user_progress WHERE user_id = $user_id ORDER BY record_date DESC LIMIT 1");
if ($lp_res) {
    $latest_progress = mysqli_fetch_assoc($lp_res);
}

$latest_weight_value = $latest_progress && isset($latest_progress['weight']) ? (float) $latest_progress['weight'] : null;
$latest_height_value = $latest_progress && isset($latest_progress['height']) ? (float) $latest_progress['height'] : null;
$latest_bmi_display = '-';
if ($latest_progress) {
    $latest_bmi_value = null;
    if (isset($latest_progress['bmi']) && $latest_progress['bmi'] !== null) {
        $latest_bmi_value = (float) $latest_progress['bmi'];
    } elseif ($latest_height_value && $latest_weight_value) {
        $hm = $latest_height_value / 100;
        if ($hm > 0) {
            $latest_bmi_value = $latest_weight_value / ($hm * $hm);
        }
    }
    if ($latest_bmi_value !== null) {
        $latest_bmi_display = number_format($latest_bmi_value, 1, ',', '.');
    }
}
$latest_weight_display = $latest_weight_value !== null ? number_format($latest_weight_value, 1, ',', '.') . ' kg' : '-';

$body_fat_inputs = [
    'weight' => isset($_POST['calculate_body_fat']) ? trim($_POST['bf_weight'] ?? '') : ($latest_progress['weight'] ?? ''),
    'height' => isset($_POST['calculate_body_fat']) ? trim($_POST['bf_height'] ?? '') : ($latest_progress['height'] ?? ''),
    'neck' => isset($_POST['calculate_body_fat']) ? trim($_POST['neck'] ?? '') : '',
    'waist' => isset($_POST['calculate_body_fat']) ? trim($_POST['waist'] ?? '') : '',
    'shoulder' => isset($_POST['calculate_body_fat']) ? trim($_POST['shoulder'] ?? '') : '',
    'gender' => isset($_POST['calculate_body_fat']) ? trim($_POST['bf_gender'] ?? '') : ($user_row['gender'] ?? ''),
];

$normalizeGender = static function ($value) {
    if ($value === 'Kadın') return 'Female';
    if ($value === 'Erkek') return 'Male';
    if ($value === 'Belirtmek İstemiyorum') return 'Prefer not to say';
    return $value;
};
$body_fat_inputs['gender'] = $normalizeGender($body_fat_inputs['gender']);
if ($body_fat_inputs['gender'] === '' || !in_array($body_fat_inputs['gender'], ['Male', 'Female'], true)) {
    $body_fat_inputs['gender'] = 'Male';
}

if (isset($_POST['calculate_body_fat'])) {
    $clean_number = static function ($value) {
        $normalized = str_replace([' ', ','], ['', '.'], $value ?? '');
        return filter_var($normalized, FILTER_VALIDATE_FLOAT);
    };

    $neck_cm = $clean_number($body_fat_inputs['neck']);
    $waist_cm = $clean_number($body_fat_inputs['waist']);
    $shoulder_cm = $clean_number($body_fat_inputs['shoulder']);
    $height_cm = $clean_number($body_fat_inputs['height']);
    $weight_kg = $clean_number($body_fat_inputs['weight']);
    $bf_gender = $body_fat_inputs['gender'];

    if ($neck_cm === false || $neck_cm <= 0 || $waist_cm === false || $waist_cm <= 0 || $height_cm === false || $height_cm <= 0) {
        $body_fat_message = 'Check neck, waist, and height values.';
        $body_fat_type = 'error';
    } elseif ($waist_cm <= $neck_cm) {
        $body_fat_message = 'Waist must be larger than neck.';
        $body_fat_type = 'error';
    } elseif ($bf_gender === 'Female' && ($shoulder_cm === false || $shoulder_cm <= 0)) {
        $body_fat_message = 'Shoulder measurement required for female calculation.';
        $body_fat_type = 'error';
    } else {
        if ($bf_gender === 'Male') {
            $base = 86.010 * log10($waist_cm - $neck_cm) - 70.041 * log10($height_cm) + 36.76;
            $adjust = 0;
            if ($shoulder_cm !== false && $shoulder_cm > 0) {
                $ratio = $shoulder_cm / $waist_cm;
                $ratio = max(min($ratio, 1.7), 0.9);
                $adjust = ($ratio - 1.2) * 8;
            }
            $body_fat_percentage = $base - $adjust;
        } else {
            $effective = $waist_cm + $shoulder_cm - $neck_cm;
            if ($effective <= 0) {
                $body_fat_message = 'Waist + shoulder must be greater than neck.';
                $body_fat_type = 'error';
            } else {
                $body_fat_percentage = 163.205 * log10($effective) - 97.684 * log10($height_cm) - 78.387;
            }
        }

        if ($body_fat_type === '') {
            if ($body_fat_percentage !== null) {
                $body_fat_percentage = max(min($body_fat_percentage, 60), 2);
                $body_fat_message = 'Estimated body fat: %' . number_format($body_fat_percentage, 1, ',', '.');
                $body_fat_type = 'success';

                $categories = [
                    'Male' => [
                        ['limit' => 6, 'label' => 'Athletic'],
                        ['limit' => 13, 'label' => 'Fit'],
                        ['limit' => 17, 'label' => 'Good'],
                        ['limit' => 24, 'label' => 'Acceptable'],
                        ['limit' => 100, 'label' => 'Caution'],
                    ],
                    'Female' => [
                        ['limit' => 14, 'label' => 'Athletic'],
                        ['limit' => 21, 'label' => 'Fit'],
                        ['limit' => 25, 'label' => 'Good'],
                        ['limit' => 31, 'label' => 'Acceptable'],
                        ['limit' => 100, 'label' => 'Caution'],
                    ],
                ];
                foreach ($categories[$bf_gender] as $cat) {
                    if ($body_fat_percentage <= $cat['limit']) {
                        $body_fat_category = $cat['label'];
                        break;
                    }
                }
                if ($shoulder_cm !== false && $shoulder_cm > 0) {
                    $shoulder_waist_ratio = $shoulder_cm / $waist_cm;
                }
                if ($weight_kg !== false && $weight_kg > 0) {
                    $lean_mass = $weight_kg * (1 - ($body_fat_percentage / 100));
                }
            } else {
                $body_fat_message = 'Could not calculate with given numbers.';
                $body_fat_type = 'error';
            }
        }
    }
}

if (isset($_POST['add_review'])) {
    $class_id = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;
    $rating = isset($_POST['rating']) ? (int) $_POST['rating'] : 0;
    $comment_raw = trim($_POST['comment'] ?? '');

    if ($class_id <= 0 || $rating < 1 || $rating > 5) {
        $review_message = 'Select a class and rating between 1-5.';
        $review_type = 'error';
    } else {
        $check_rev = mysqli_query($conn, "SELECT id FROM reviews WHERE user_id=$user_id AND class_id=$class_id LIMIT 1");
        if ($check_rev && mysqli_num_rows($check_rev) > 0) {
            $review_message = 'You already rated this class.';
            $review_type = 'error';
        } else {
            $stmt = mysqli_prepare($conn, 'INSERT INTO reviews (class_id, user_id, rating, comment) VALUES (?, ?, ?, ?)');
            if ($stmt) {
                $comment_sql = $comment_raw !== '' ? $comment_raw : null;
                mysqli_stmt_bind_param($stmt, 'iiis', $class_id, $user_id, $rating, $comment_sql);
                $ok = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                if ($ok) {
                    $review_message = 'Review saved.';
                    $review_type = 'success';
                } else {
                    $review_message = 'Review could not be saved.';
                    $review_type = 'error';
                }
            } else {
                $review_message = 'Database error while saving review.';
                $review_type = 'error';
            }
        }
    }
}

$upcoming_classes = [];
$up_sql = "SELECT classes.*, bookings.booking_date, bookings.id AS booking_id FROM bookings JOIN classes ON bookings.class_id = classes.id WHERE bookings.user_id = $user_id AND classes.date_time >= NOW() ORDER BY classes.date_time ASC";
$up_res = mysqli_query($conn, $up_sql);
if ($up_res) {
    while ($r = mysqli_fetch_assoc($up_res)) {
        $upcoming_classes[] = $r;
    }
}

$past_classes = [];
$past_sql = "SELECT classes.*, bookings.booking_date, bookings.id AS booking_id FROM bookings JOIN classes ON bookings.class_id = classes.id WHERE bookings.user_id = $user_id AND classes.date_time < NOW() ORDER BY classes.date_time DESC";
$past_res = mysqli_query($conn, $past_sql);
if ($past_res) {
    while ($r = mysqli_fetch_assoc($past_res)) {
        $past_classes[] = $r;
    }
}

$existing_reviews = [];
$rev_res = mysqli_query($conn, "SELECT class_id, rating, comment FROM reviews WHERE user_id = $user_id");
if ($rev_res) {
    while ($row = mysqli_fetch_assoc($rev_res)) {
        $existing_reviews[(int) $row['class_id']] = $row;
    }
}

$progress_history = [];
$prog_res = mysqli_query($conn, "SELECT weight, height, bmi, record_date FROM user_progress WHERE user_id = $user_id ORDER BY record_date DESC LIMIT 10");
if ($prog_res) {
    while ($p = mysqli_fetch_assoc($prog_res)) {
        $progress_history[] = $p;
    }
}

include 'header.php';
?>

<style>
    .page-shell {max-width: 1200px; margin: 15px auto; padding: 24px; border-top: 5px solid #2d4fa3; background: #fff;}
    .page-shell h1 {margin: 0 0 12px 0; font-size: 26px;}
    .helper {color: #666; margin-bottom: 20px;}
   
    .grid { display: flex; flex-direction: column; gap: 16px; }
    .progress-section { order: 0; }
    .section { 
        border: 1px solid #e0e0e0; background: #fafafa; padding: 16px; border-radius: 6px;
        width: 100%;
        max-width: 900px;
        margin: 0 auto 16px auto;
    }
    .section h2 {margin: 0 0 10px 0; font-size: 20px;}
    .section p {margin: 0 0 12px 0; color: #555;}
    .stack {display: flex; flex-direction: column; gap: 10px;}
    .field {display: flex; flex-direction: column; gap: 6px;}
    label {font-weight: 600; font-size: 14px;}
    input[type="text"], input[type="email"], input[type="number"], select, textarea {padding: 8px; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 14px;}
    textarea {min-height: 70px;}
    .btn {padding: 10px 14px; background: #222; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;}
    .btn.secondary {background: #0b6bcb;}
    .btn.ghost {background: #f0f0f0; color: #222;}
    .note {padding: 10px; border-radius: 4px; margin-bottom: 12px;}
    .note.success {background: #e6f7e6; color: #1e6b1e; border: 1px solid #c5e6c5;}
    .note.error {background: #ffecec; color: #b80000; border: 1px solid #ffb3b3;}
    .avatar {width: 96px; height: 96px; border-radius: 50%; object-fit: cover; border: 1px solid #ddd;}
    .inline {display: flex; align-items: center; gap: 12px; flex-wrap: wrap;}
    .table {width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 14px;}
    .table th, .table td {border: 1px solid #e0e0e0; padding: 8px; text-align: left;}
    .table th {background: #f3f3f3;}
    .empty {padding: 12px; color: #666;}
    .badge {display: inline-block; padding: 3px 8px; background: #eef3ff; color: #2d4fa3; border-radius: 4px; font-size: 12px; font-weight: 600;}
    .small-text {color: #666; font-size: 12px;}
    .row {display: flex; flex-wrap: wrap; gap: 12px;}
</style>

<div class="page-shell">
    <h1>Profile</h1>
    <div class="helper">Simple profile page to update your details, log height/weight (BMI), calculate body fat, and track your classes.</div>

    <?php if ($message): ?>
        <div class="note <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($progress_message): ?>
        <div class="note <?php echo htmlspecialchars($progress_type); ?>"><?php echo htmlspecialchars($progress_message); ?></div>
    <?php endif; ?>
    <?php if ($body_fat_message): ?>
        <div class="note <?php echo htmlspecialchars($body_fat_type); ?>"><?php echo htmlspecialchars($body_fat_message); ?>
            <?php if ($body_fat_percentage !== null && $body_fat_type === 'success'): ?>
                <?php if ($body_fat_category): ?> | Category: <?php echo htmlspecialchars($body_fat_category); ?><?php endif; ?>
                <?php if ($shoulder_waist_ratio): ?> | Shoulder/Waist: <?php echo number_format($shoulder_waist_ratio, 2, ',', '.'); ?><?php endif; ?>
                <?php if ($lean_mass !== null): ?> | Lean Mass: <?php echo number_format($lean_mass, 1, ',', '.'); ?> kg<?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if ($review_message): ?>
        <div class="note <?php echo htmlspecialchars($review_type); ?>"><?php echo htmlspecialchars($review_message); ?></div>
    <?php endif; ?>

    <div class="grid">
        <div class="section">
            <h2>Account</h2>
            <p>Update your contact details.</p>
            <div class="inline">
                <?php if (!empty($user_row['profile_photo'])): ?>
                    <img class="avatar" src="data:image/jpeg;base64,<?php echo base64_encode($user_row['profile_photo']); ?>" alt="Profile Photo">
                <?php elseif (!empty($user_row['profile_pic']) && $user_row['profile_pic'] !== 'default.png'): ?>
                    <img class="avatar" src="<?php echo htmlspecialchars($user_row['profile_pic']); ?>" alt="Profile Photo">
                <?php else: ?>
                    <img class="avatar" src="img/defaultuser.png" alt="Profile Photo">
                <?php endif; ?>
                <div>
                    <div><strong><?php echo htmlspecialchars($user_row['username']); ?></strong></div>
                    <div class="small-text"><?php echo htmlspecialchars($role_label); ?></div>
                    <div class="small-text">Latest BMI: <?php echo htmlspecialchars($latest_bmi_display); ?> | Weight: <?php echo htmlspecialchars($latest_weight_display); ?></div>
                </div>
            </div>

            <form method="POST" class="stack" style="margin-top: 12px;">
                <div class="field">
                    <label for="username">Full Name</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user_row['username']); ?>" required>
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_row['email']); ?>" required>
                </div>
                <div class="field">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user_row['phone']); ?>">
                </div>
                <div class="row">
                    <div class="field">
                        <label for="age">Age</label>
                        <input type="number" id="age" name="age" min="1" max="120" value="<?php echo htmlspecialchars((string) ($user_row['age'] ?? '')); ?>">
                    </div>
                    <div class="field">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender">
                            <option value="">-- Select --</option>
                            <option value="Male" <?php echo ($user_row['gender'] === 'Male' || $user_row['gender'] === 'Erkek') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($user_row['gender'] === 'Female' || $user_row['gender'] === 'Kadın') ? 'selected' : ''; ?>>Female</option>
                            <option value="Prefer not to say" <?php echo ($user_row['gender'] === 'Prefer not to say' || $user_row['gender'] === 'Belirtmek İstemiyorum') ? 'selected' : ''; ?>>Prefer not to say</option>
                        </select>
                    </div>
                </div>

                <?php if ($user_row['role'] === 'instructor'): ?>
                    <div class="field">
                        <label>Instructor Specialties</label>
                        <div class="inline" style="gap: 8px;">
                            <?php foreach ($class_categories as $categoryValue => $categoryLabel): ?>
                                <label class="small-text" style="display: inline-flex; gap: 4px; align-items: center; padding: 6px 8px; border: 1px solid #ddd; border-radius: 4px; background: #fff;">
                                    <input type="checkbox" name="specialties[]" value="<?php echo htmlspecialchars($categoryValue); ?>" <?php echo in_array($categoryValue, $instructor_specialties, true) ? 'checked' : ''; ?>>
                                    <span><?php echo htmlspecialchars($categoryLabel); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <button class="btn" type="submit" name="update_profile">Save Profile</button>
            </form>

            <hr style="margin: 18px 0;">
            <form method="POST" enctype="multipart/form-data" class="stack">
                <div class="field">
                    <label for="profile_photo">Update Profile Photo</label>
                    <input type="file" id="profile_photo" name="profile_photo" accept="image/*" required>
                    <span class="small-text">JPG, PNG, GIF, WebP up to 5MB.</span>
                </div>
                <button class="btn ghost" type="submit" name="upload_profile_photo">Upload Photo</button>
            </form>
        </div>

        <div class="section progress-section">
            <h2>Progress (BMI)</h2>
            <p>Log your height and weight. BMI is calculated automatically.</p>
            <form method="POST" class="stack">
                <div class="row">
                    <div class="field">
                        <label for="weight">Weight (kg)</label>
                        <input type="number" step="0.1" min="0" id="weight" name="weight" placeholder="e.g. 75.5" required>
                    </div>
                    <div class="field">
                        <label for="height">Height (cm)</label>
                        <input type="number" min="0" id="height" name="height" placeholder="e.g. 180" required>
                    </div>
                </div>
                <button class="btn secondary" type="submit" name="add_progress">Save Progress</button>
            </form>
            <div class="small-text" style="margin-top: 10px;">Latest BMI: <?php echo htmlspecialchars($latest_bmi_display); ?> | Weight: <?php echo htmlspecialchars($latest_weight_display); ?></div>
        </div>

        <div class="section">
            <h2>Body Fat Calculator</h2>
            <p>Keep the boy/kilo calculator. Uses neck, waist, height, and optional shoulder.</p>
            <form method="POST" class="stack">
                <div class="row">
                    <div class="field">
                        <label for="bf_weight">Weight (kg)</label>
                        <input type="number" step="0.1" min="0" id="bf_weight" name="bf_weight" value="<?php echo htmlspecialchars($body_fat_inputs['weight']); ?>" placeholder="75.5">
                    </div>
                    <div class="field">
                        <label for="bf_height">Height (cm)</label>
                        <input type="number" step="0.1" min="0" id="bf_height" name="bf_height" value="<?php echo htmlspecialchars($body_fat_inputs['height']); ?>" placeholder="180" required>
                    </div>
                </div>
                <div class="row">
                    <div class="field">
                        <label for="neck">Neck (cm)</label>
                        <input type="number" step="0.1" min="0" id="neck" name="neck" value="<?php echo htmlspecialchars($body_fat_inputs['neck']); ?>" placeholder="38" required>
                    </div>
                    <div class="field">
                        <label for="waist">Waist (cm)</label>
                        <input type="number" step="0.1" min="0" id="waist" name="waist" value="<?php echo htmlspecialchars($body_fat_inputs['waist']); ?>" placeholder="82" required>
                    </div>
                    <div class="field">
                        <label for="shoulder">Shoulder (cm)</label>
                        <input type="number" step="0.1" min="0" id="shoulder" name="shoulder" value="<?php echo htmlspecialchars($body_fat_inputs['shoulder']); ?>" placeholder="115">
                        <span class="small-text">Needed for female calc, optional for male.</span>
                    </div>
                </div>
                <div class="field" style="max-width: 200px;">
                    <label for="bf_gender">Gender</label>
                    <select id="bf_gender" name="bf_gender">
                        <option value="Male" <?php echo $body_fat_inputs['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo $body_fat_inputs['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>
                <button class="btn" type="submit" name="calculate_body_fat">Calculate</button>
            </form>
        </div>
    </div>

    <div class="section" style="margin-top: 16px;">
        <h2>Upcoming Classes</h2>
        <p>Sessions you are booked into.</p>
        <?php if (!empty($upcoming_classes)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Trainer</th>
                        <th>Join</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($upcoming_classes as $row): ?>
                        <?php $class_date = new DateTime($row['date_time']); ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><span class="badge"><?php echo htmlspecialchars($row['class_type']); ?></span></td>
                            <td><?php echo $class_date->format('d.m.Y'); ?></td>
                            <td><?php echo $class_date->format('H:i'); ?></td>
                            <td><?php echo htmlspecialchars($row['trainer_name']); ?></td>
                            <td>
                                <?php if (!empty($row['video_link'])): ?>
                                    <a class="small-text" href="<?php echo htmlspecialchars($row['video_link']); ?>" target="_blank">Open Link</a>
                                <?php else: ?>
                                    <span class="small-text">-</span>
                                <?php endif; ?>
                            </td>
                            <td><a class="small-text" href="cancel_booking.php?id=<?php echo (int) $row['booking_id']; ?>" onclick="return confirm('Cancel this session?');">Cancel</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty">No upcoming sessions.</div>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>Completed Classes</h2>
        <p>Review the sessions you finished.</p>
        <?php if (!empty($past_classes)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Trainer</th>
                        <th>Your Review</th>
                        <th>Add / Update</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($past_classes as $row): ?>
                        <?php $class_date = new DateTime($row['date_time']); ?>
                        <?php $cid = (int) $row['id']; ?>
                        <?php $existing = $existing_reviews[$cid] ?? null; ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><span class="badge" style="background:#eefaf2; color:#1f7a3d;">Completed</span></td>
                            <td><?php echo $class_date->format('d.m.Y H:i'); ?></td>
                            <td><?php echo htmlspecialchars($row['trainer_name']); ?></td>
                            <td>
                                <?php if ($existing): ?>
                                    <div>Rating: <?php echo (int) $existing['rating']; ?>/5</div>
                                    <?php if (!empty($existing['comment'])): ?>
                                        <div class="small-text">"<?php echo htmlspecialchars($existing['comment']); ?>"</div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="small-text">No review yet.</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$existing): ?>
                                    <form method="POST" class="stack" style="gap: 6px;">
                                        <input type="hidden" name="class_id" value="<?php echo $cid; ?>">
                                        <div class="field">
                                            <label>Rating (1-5)</label>
                                            <select name="rating" required>
                                                <option value="">--</option>
                                                <option value="1">1</option>
                                                <option value="2">2</option>
                                                <option value="3">3</option>
                                                <option value="4">4</option>
                                                <option value="5">5</option>
                                            </select>
                                        </div>
                                        <div class="field">
                                            <label>Comment (optional)</label>
                                            <textarea name="comment" placeholder="How was the session?"></textarea>
                                        </div>
                                        <button class="btn ghost" type="submit" name="add_review">Save Review</button>
                                    </form>
                                <?php else: ?>
                                    <span class="small-text">Review saved.</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty">No completed sessions yet.</div>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>Progress History</h2>
        <p>Last 10 entries.</p>
        <?php if (!empty($progress_history)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Weight (kg)</th>
                        <th>Height (cm)</th>
                        <th>BMI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($progress_history as $p): ?>
                        <?php $record_date = new DateTime($p['record_date']); ?>
                        <tr>
                            <td><?php echo $record_date->format('d.m.Y H:i'); ?></td>
                            <td><?php echo number_format((float) $p['weight'], 1, ',', '.'); ?></td>
                            <td><?php echo number_format((float) $p['height'], 0, ',', '.'); ?></td>
                            <td><?php echo isset($p['bmi']) ? number_format((float) $p['bmi'], 1, ',', '.') : '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty">No progress entries yet.</div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
