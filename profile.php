<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

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

$detectMimeType = static function (string $path): ?string {
    if (!is_readable($path)) {
        return null;
    }

    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = finfo_file($finfo, $path);
            finfo_close($finfo);
            if ($mime !== false) {
                return $mime;
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
        $message = "Error: Please choose a valid image before uploading.";
        $message_type = "error";
    } else {
        $tmpFile = $_FILES['profile_photo']['tmp_name'];
        $size = $_FILES['profile_photo']['size'];
        $mimeType = $detectMimeType($tmpFile);
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!$mimeType || !in_array($mimeType, $allowed_types, true)) {
            $message = "Error: Only JPG, PNG, GIF, or WebP images are allowed.";
            $message_type = "error";
        } elseif ($size > 5 * 1024 * 1024) {
            $message = "Error: File size cannot exceed 5MB.";
            $message_type = "error";
        } else {
            $photo_data = file_get_contents($tmpFile);
            if ($photo_data === false) {
                $message = "Error: Unable to read the uploaded file.";
                $message_type = "error";
            } else {
                $photo_data = mysqli_real_escape_string($conn, $photo_data);
                $update_photo = "UPDATE users SET profile_photo='$photo_data' WHERE id=$user_id";
                if (mysqli_query($conn, $update_photo)) {
                    $message = "Success: Profile photo uploaded successfully.";
                    $message_type = "success";
                } else {
                    $message = "Error: A database error occurred while uploading the photo.";
                    $message_type = "error";
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

    $profile_errors = [];
    $age_value = null;

    if ($new_username === '' || $new_email === '') {
        $profile_errors[] = "Error: Name and email are required.";
    }

    if ($new_email !== '' && !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $profile_errors[] = "Error: Please enter a valid email address.";
    }

    if ($new_age !== '') {
        $age_filter = filter_var(
            $new_age,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 120]]
        );

        if ($age_filter === false) {
            $profile_errors[] = "Error: Please enter a valid age between 1 and 120.";
        } else {
            $age_value = $age_filter;
        }
    }

    $allowed_genders = ['Male', 'Female', 'Prefer not to say', 'Erkek', 'Kadın', 'Belirtmek İstemiyorum'];
    if ($new_gender !== '' && !in_array($new_gender, $allowed_genders, true)) {
        $profile_errors[] = "Error: Please choose a valid gender option.";
    }

    if (empty($profile_errors)) {
        $safe_username = mysqli_real_escape_string($conn, $new_username);
        $safe_email = mysqli_real_escape_string($conn, $new_email);
        $safe_phone = $new_phone !== '' ? "'" . mysqli_real_escape_string($conn, $new_phone) . "'" : "NULL";
        $safe_gender = $new_gender !== '' ? "'" . mysqli_real_escape_string($conn, $new_gender) . "'" : "NULL";
        $age_sql = $age_value !== null ? (int) $age_value : "NULL";

        $check_sql = "SELECT id FROM users WHERE email='$safe_email' AND id <> $user_id LIMIT 1";
        $check_result = mysqli_query($conn, $check_sql);
        if ($check_result && mysqli_num_rows($check_result) > 0) {
            $profile_errors[] = "Warning: This email address is already registered.";
        }

        if (empty($profile_errors)) {
            $update_sql = "
                UPDATE users
                SET username='$safe_username',
                    email='$safe_email',
                    phone=$safe_phone,
                    age=$age_sql,
                    gender=$safe_gender
                WHERE id=$user_id
            ";

            if (mysqli_query($conn, $update_sql)) {
                $message = "Success: Details updated successfully.";
                $message_type = "success";
                $_SESSION['username'] = $new_username;
            } else {
                $profile_errors[] = "Error: An error occurred while updating your profile.";
            }
        }
    }

    if (!empty($profile_errors)) {
        $message = $profile_errors[0];
        $message_type = "error";
    }
}

if (isset($_POST['add_progress'])) {
    $weight_raw = str_replace(',', '.', trim($_POST['weight'] ?? ''));
    $height_raw = trim($_POST['height'] ?? '');

    $weight = filter_var($weight_raw, FILTER_VALIDATE_FLOAT);
    $height = filter_var($height_raw, FILTER_VALIDATE_FLOAT);

    if ($weight === false || $weight <= 0 || $height === false || $height <= 0) {
        $progress_message = "Error: Please enter valid height and weight values.";
        $progress_type = "error";
    } else {
        $height_cm = (int) round($height);
        $bmi = $height_cm > 0 ? $weight / pow($height_cm / 100, 2) : 0;
        $bmi = round($bmi, 2);

        $insert_sql = sprintf(
            "INSERT INTO user_progress (user_id, weight, height, bmi) VALUES (%d, %.2f, %d, %.2f)",
            $user_id,
            $weight,
            $height_cm,
            $bmi
        );

        if (mysqli_query($conn, $insert_sql)) {
            $progress_message = "Progress saved! BMI: " . number_format($bmi, 2, ',', '.');
            $progress_type = "success";
        } else {
            $progress_message = "Error: Unable to save your progress.";
            $progress_type = "error";
        }
    }
}

$user_query = mysqli_query($conn, "SELECT id, username, email, phone, age, gender, role, profile_photo, profile_pic FROM users WHERE id = $user_id LIMIT 1");
$user_row = $user_query ? mysqli_fetch_assoc($user_query) : null;

if (!$user_row) {
    header('Location: logout.php');
    exit;
}

$role_labels = [
    'user' => 'Student',
    'instructor' => 'Instructor',
    'admin' => 'Administrator',
];
$role_label = $role_labels[$user_row['role']] ?? 'Member';

$latest_progress = null;
$latest_progress_query = mysqli_query($conn, "SELECT weight, height, bmi, record_date FROM user_progress WHERE user_id = $user_id ORDER BY record_date DESC LIMIT 1");
if ($latest_progress_query) {
    $latest_progress = mysqli_fetch_assoc($latest_progress_query);
}

$latest_progress_date = null;
$latest_bmi_value = null;
$latest_bmi_display = '-';
$latest_progress_note = 'Add your first entry';
$latest_weight_value = null;

if ($latest_progress) {
    if (isset($latest_progress['weight'])) {
        $latest_weight_value = (float) $latest_progress['weight'];
    }

    $height_cm_latest = isset($latest_progress['height']) ? (float) $latest_progress['height'] : 0;

    if (isset($latest_progress['bmi']) && $latest_progress['bmi'] !== null) {
        $latest_bmi_value = (float) $latest_progress['bmi'];
    } elseif ($height_cm_latest > 0 && $latest_weight_value !== null) {
        $height_m_latest = $height_cm_latest / 100;
        if ($height_m_latest > 0) {
            $latest_bmi_value = $latest_weight_value / ($height_m_latest * $height_m_latest);
        }
    }

    if (!empty($latest_progress['record_date'])) {
        $latest_progress_date = new DateTime($latest_progress['record_date']);
        $latest_progress_note = 'Last entry: ' . $latest_progress_date->format('d.m.Y');
    }

    if ($latest_bmi_value !== null) {
        $latest_bmi_display = number_format($latest_bmi_value, 1, ',', '.');
    }
}

$upcoming_classes = [];
$upcoming_sql = "SELECT classes.*, bookings.booking_date, bookings.id AS booking_id FROM bookings JOIN classes ON bookings.class_id = classes.id WHERE bookings.user_id = $user_id AND classes.date_time >= NOW() ORDER BY classes.date_time ASC";
$upcoming_result = mysqli_query($conn, $upcoming_sql);
if ($upcoming_result) {
    while ($row = mysqli_fetch_assoc($upcoming_result)) {
        $upcoming_classes[] = $row;
    }
}

$past_classes = [];
$past_sql = "SELECT classes.*, bookings.booking_date, bookings.id AS booking_id FROM bookings JOIN classes ON bookings.class_id = classes.id WHERE bookings.user_id = $user_id AND classes.date_time < NOW() ORDER BY classes.date_time DESC";
$past_result = mysqli_query($conn, $past_sql);
if ($past_result) {
    while ($row = mysqli_fetch_assoc($past_result)) {
        $past_classes[] = $row;
    }
}

$upcoming_count = count($upcoming_classes);
$completed_count = count($past_classes);

$next_class_date_text = '';
if (!empty($upcoming_classes)) {
    $next_class_date = new DateTime($upcoming_classes[0]['date_time']);
    $next_class_date_text = $next_class_date->format('d.m.Y H:i');
}

$last_completed_text = '';
if (!empty($past_classes)) {
    $last_completed_date = new DateTime($past_classes[0]['date_time']);
    $last_completed_text = $last_completed_date->format('d.m.Y H:i');
}

$progress_count = 0;
$progress_count_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM user_progress WHERE user_id = $user_id");
if ($progress_count_res) {
    $progress_count_row = mysqli_fetch_assoc($progress_count_res);
    $progress_count = (int) ($progress_count_row['total'] ?? 0);
}

$latest_weight_display = $latest_weight_value !== null ? number_format($latest_weight_value, 1, ',', '.') . ' kg' : '-';
$next_class_note = $upcoming_count > 0 ? 'Next session: ' . $next_class_date_text : 'No sessions scheduled';
$completed_note = $completed_count > 0 ? 'Last session: ' . $last_completed_text : 'No sessions completed yet';
$progress_note = $progress_count > 0 ? $latest_progress_note : 'Log your progress';

$body_fat_inputs = [
    'weight' => isset($_POST['calculate_body_fat']) ? trim($_POST['bf_weight'] ?? '') : ($latest_progress['weight'] ?? ''),
    'height' => isset($_POST['calculate_body_fat']) ? trim($_POST['bf_height'] ?? '') : ($latest_progress['height'] ?? ''),
    'neck' => isset($_POST['calculate_body_fat']) ? trim($_POST['neck'] ?? '') : '',
    'waist' => isset($_POST['calculate_body_fat']) ? trim($_POST['waist'] ?? '') : '',
    'shoulder' => isset($_POST['calculate_body_fat']) ? trim($_POST['shoulder'] ?? '') : '',
    'gender' => isset($_POST['calculate_body_fat']) ? trim($_POST['bf_gender'] ?? '') : ($user_row['gender'] ?? ''),
];

$normalizeGender = static function ($value) {
    if ($value === 'Kadın') {
        return 'Female';
    }
    if ($value === 'Erkek') {
        return 'Male';
    }
    if ($value === 'Belirtmek İstemiyorum') {
        return 'Prefer not to say';
    }
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
        $body_fat_message = "Error: Please make sure neck, waist, and height values are valid.";
        $body_fat_type = "error";
    } elseif ($waist_cm <= $neck_cm) {
        $body_fat_message = "Error: Waist circumference must be greater than neck circumference.";
        $body_fat_type = "error";
    } elseif ($bf_gender === 'Female' && ($shoulder_cm === false || $shoulder_cm <= 0)) {
        $body_fat_message = "Error: Shoulder circumference is required for female calculations.";
        $body_fat_type = "error";
    } else {
        if ($bf_gender === 'Male') {
            $base_value = 86.010 * log10($waist_cm - $neck_cm) - 70.041 * log10($height_cm) + 36.76;
            $ratio_adjustment = 0;
            if ($shoulder_cm !== false && $shoulder_cm > 0) {
                $shoulder_ratio = $shoulder_cm / $waist_cm;
                $shoulder_ratio = max(min($shoulder_ratio, 1.7), 0.9);
                $ratio_adjustment = ($shoulder_ratio - 1.2) * 8;
            }
            $body_fat_percentage = $base_value - $ratio_adjustment;
        } else {
            $effective_circumference = $waist_cm + $shoulder_cm - $neck_cm;
            if ($effective_circumference <= 0) {
                $body_fat_message = "Error: Measurements seem inconsistent. Waist plus shoulder should exceed neck.";
                $body_fat_type = "error";
            } else {
                $body_fat_percentage = 163.205 * log10($effective_circumference) - 97.684 * log10($height_cm) - 78.387;
            }
        }

        if ($body_fat_type === '') {
            if ($body_fat_percentage !== null) {
                $body_fat_percentage = max(min($body_fat_percentage, 60), 2);
                $body_fat_message = "Success: Your estimated body fat percentage is %" . number_format($body_fat_percentage, 1, ',', '.') . ".";
                $body_fat_type = "success";

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

                $body_fat_category = '';
                foreach ($categories[$bf_gender] as $category) {
                    if ($body_fat_percentage <= $category['limit']) {
                        $body_fat_category = $category['label'];
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
                $body_fat_message = "Error: Unable to calculate body fat with the provided measurements.";
                $body_fat_type = "error";
            }
        }
    }
}

include 'header.php';
?>

<div class="profile-page">
    <div class="profile-hero-v2">
        <div class="profile-hero-wrapper">
            <div class="profile-avatar">
                <?php if (!empty($user_row['profile_photo'])): ?>
                    <img src="data:image/jpeg;base64,<?php echo base64_encode($user_row['profile_photo']); ?>" alt="Profile Photo">
                <?php elseif (!empty($user_row['profile_pic']) && $user_row['profile_pic'] !== 'default.png'): ?>
                    <img src="<?php echo htmlspecialchars($user_row['profile_pic']); ?>" alt="Profile Photo">
                <?php else: ?>
                    <div class="profile-avatar-placeholder">🏋️</div>
                <?php endif; ?>
            </div>
            <div class="profile-hero-info">
                <span class="profile-hero-topline">Personal Performance Hub</span>
                <h1><?php echo htmlspecialchars($user_row['username']); ?></h1>
                <div class="profile-hero-meta">
                    <span class="profile-role-chip"><?php echo htmlspecialchars($role_label); ?></span>
                    <?php if (!empty($user_row['email'])): ?>
                        <span class="profile-meta-item"><?php echo htmlspecialchars($user_row['email']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($user_row['phone'])): ?>
                        <span class="profile-meta-item">📞 <?php echo htmlspecialchars($user_row['phone']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="profile-stat-bar">
        <div class="stat-card">
            <div class="stat-icon stat-icon--primary"></div>
            <div class="stat-content">
                <span class="stat-label">Body Score (BMI)</span>
                <span class="stat-value"><?php echo htmlspecialchars($latest_bmi_display); ?></span>
                <span class="stat-sub">Weight: <?php echo htmlspecialchars($latest_weight_display); ?></span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon--warning"></div>
            <div class="stat-content">
                <span class="stat-label">Upcoming</span>
                <span class="stat-value"><?php echo $upcoming_count; ?></span>
                <span class="stat-sub"><?php echo htmlspecialchars($next_class_note); ?></span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon--success">✅</div>
            <div class="stat-content">
                <span class="stat-label">Completed</span>
                <span class="stat-value"><?php echo $completed_count; ?></span>
                <span class="stat-sub"><?php echo htmlspecialchars($completed_note); ?></span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon--info"></div>
            <div class="stat-content">
                <span class="stat-label">Progress Logs</span>
                <span class="stat-value"><?php echo $progress_count; ?></span>
                <span class="stat-sub"><?php echo htmlspecialchars($progress_note); ?></span>
            </div>
        </div>
    </div>

    <div class="profile-container">
        <div class="profile-left">
            <div class="profile-card profile-card--account">
                <div class="card-header">
                    <h2>Account Details</h2>
                    <p>Keep your profile information up to date</p>
                </div>

                <?php if ($message): ?>
                    <div class="message-box message-<?php echo $message_type; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="profile-form profile-form--stacked">
                    <div class="form-group">
                        <label for="username">Full Name</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user_row['username']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_row['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user_row['phone']); ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="age">Age</label>
                            <input type="number" id="age" name="age" value="<?php echo htmlspecialchars((string) ($user_row['age'] ?? '')); ?>" min="1" max="120">
                        </div>

                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender">
                                <option value="">-- Select --</option>
                                <option value="Male" <?php if ($user_row['gender'] === 'Male' || $user_row['gender'] === 'Erkek') echo 'selected'; ?>>Male</option>
                                <option value="Female" <?php if ($user_row['gender'] === 'Female' || $user_row['gender'] === 'Kadın') echo 'selected'; ?>>Female</option>
                                <option value="Prefer not to say" <?php if ($user_row['gender'] === 'Prefer not to say' || $user_row['gender'] === 'Belirtmek İstemiyorum') echo 'selected'; ?>>Prefer not to say</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="update_profile" class="btn-submit-large">Update Details</button>
                    </div>
                </form>

                <div class="card-divider"></div>

                <div class="profile-photo-upload">
                    <h3>Profile Photo</h3>
                    <p class="form-hint">PNG, JPG, GIF, WebP (Max 5MB)</p>
                    <form method="POST" enctype="multipart/form-data" class="profile-form profile-form--stacked">
                        <div class="form-group">
                            <label for="profile_photo">Choose New Photo</label>
                            <input type="file" id="profile_photo" name="profile_photo" accept="image/*" required>
                        </div>
                        <div class="form-actions">
                            <button type="submit" name="upload_profile_photo" class="btn-submit-large btn-contrast">Update Photo</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="profile-card">
                <div class="card-header">
                    <h2>Progress Entry</h2>
                    <p>Track your progress by logging weight and height</p>
                </div>

                <?php if ($progress_message): ?>
                    <div class="message-box message-<?php echo $progress_type; ?>">
                        <?php echo htmlspecialchars($progress_message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="profile-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="weight">Weight (kg)</label>
                            <input type="number" id="weight" name="weight" step="0.1" min="0" placeholder="e.g. 75.5" required>
                        </div>
                        <div class="form-group">
                            <label for="height">Height (cm)</label>
                            <input type="number" id="height" name="height" min="0" placeholder="e.g. 180" required>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="add_progress" class="btn-submit-large btn-success">Add Entry</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="profile-middle">
            <div class="profile-card">
                <div class="card-header">
                    <h2>Upcoming Sessions</h2>
                    <p>Your scheduled workouts</p>
                </div>

                <div class="lessons-list">
                    <?php if (!empty($upcoming_classes)): ?>
                        <?php foreach ($upcoming_classes as $row): ?>
                            <?php $class_date = new DateTime($row['date_time']); ?>
                            <div class="lesson-card upcoming">
                                <div class="lesson-header">
                                    <div>
                                        <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                                        <span class="lesson-type-badge"><?php echo htmlspecialchars($row['class_type']); ?></span>
                                    </div>
                                    <span class="lesson-trainer"><?php echo htmlspecialchars($row['trainer_name']); ?></span>
                                </div>
                                <div class="lesson-meta">
                                    <div class="meta-item">
                                        <span><?php echo $class_date->format('d.m.Y'); ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <span><?php echo $class_date->format('H:i'); ?></span>
                                    </div>
                                </div>
                                <div class="lesson-actions">
                                    <?php if (!empty($row['video_link'])): ?>
                                        <a href="<?php echo htmlspecialchars($row['video_link']); ?>" target="_blank" class="btn-action-small btn-watch">🎥 Join Live</a>
                                    <?php endif; ?>
                                    <a href="cancel_booking.php?id=<?php echo (int) $row['booking_id']; ?>" onclick="return confirm('Are you sure you want to cancel this session?')" class="btn-action-small btn-cancel">Cancel</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">No upcoming sessions yet</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="profile-card past-section">
                <div class="card-header">
                    <h2>Completed Sessions</h2>
                    <p>Rate the workouts you have completed</p>
                </div>

                <div class="lessons-list">
                    <?php if (!empty($past_classes)): ?>
                        <?php foreach ($past_classes as $row): ?>
                            <?php
                                $class_date = new DateTime($row['date_time']);
                                $c_id = (int) $row['id'];
                                $review_query = mysqli_query($conn, "SELECT * FROM reviews WHERE user_id=$user_id AND class_id=$c_id LIMIT 1");
                                $rev_data = $review_query ? mysqli_fetch_assoc($review_query) : null;
                            ?>
                            <div class="lesson-card past">
                                <div class="lesson-header">
                                    <div>
                                        <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                                        <span class="lesson-type-badge past-badge"><?php echo htmlspecialchars($row['class_type']); ?></span>
                                    </div>
                                    <span class="lesson-trainer"><?php echo htmlspecialchars($row['trainer_name']); ?></span>
                                </div>
                                <div class="lesson-meta">
                                    <div class="meta-item">
                                        <span><?php echo $class_date->format('d.m.Y H:i'); ?></span>
                                    </div>
                                </div>

                                <?php if ($rev_data): ?>
                                    <div class="review-badge">
                                        <div class="star-rating">
                                            <?php for ($i = 0; $i < (int) $rev_data['rating']; $i++): ?>
                                                ⭐
                                            <?php endfor; ?>
                                            <span><?php echo (int) $rev_data['rating']; ?>/5</span>
                                        </div>
                                        <?php if (!empty($rev_data['comment'])): ?>
                                            <p class="review-comment">"<?php echo htmlspecialchars($rev_data['comment']); ?>"</p>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="no-review-badge">No review yet</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">No completed sessions yet</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="profile-right">
            <div class="profile-card">
                <div class="card-header">
                    <h2>Body Fat Analyzer</h2>
                    <p>Estimate body fat using neck, waist, and shoulder circumferences</p>
                </div>

                <?php if ($body_fat_message): ?>
                    <div class="message-box message-<?php echo $body_fat_type; ?>">
                        <div><?php echo htmlspecialchars($body_fat_message); ?></div>
                        <?php if ($body_fat_percentage !== null && $body_fat_type === 'success'): ?>
                            <div class="bodyfat-summary">
                                <?php if ($body_fat_category): ?>
                                    <span class="summary-pill">Category: <?php echo htmlspecialchars($body_fat_category); ?></span>
                                <?php endif; ?>
                                <?php if ($shoulder_waist_ratio): ?>
                                    <span class="summary-pill">Shoulder/Waist: <?php echo number_format($shoulder_waist_ratio, 2, ',', '.'); ?></span>
                                <?php endif; ?>
                                <?php if ($lean_mass !== null): ?>
                                    <span class="summary-pill">Lean Mass: <?php echo number_format($lean_mass, 1, ',', '.'); ?> kg</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="profile-form profile-form--stacked">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="bf_weight">Weight (kg)</label>
                            <input type="number" step="0.1" min="0" id="bf_weight" name="bf_weight" placeholder="e.g. 75.5" value="<?php echo htmlspecialchars($body_fat_inputs['weight']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="bf_height">Height (cm)</label>
                            <input type="number" step="0.1" min="0" id="bf_height" name="bf_height" placeholder="e.g. 180" value="<?php echo htmlspecialchars($body_fat_inputs['height']); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="neck">Neck Circumference (cm)</label>
                            <input type="number" step="0.1" min="0" id="neck" name="neck" placeholder="e.g. 38" value="<?php echo htmlspecialchars($body_fat_inputs['neck']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="waist">Waist Circumference (cm)</label>
                            <input type="number" step="0.1" min="0" id="waist" name="waist" placeholder="e.g. 82" value="<?php echo htmlspecialchars($body_fat_inputs['waist']); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="shoulder">Shoulder Circumference (cm)</label>
                            <input type="number" step="0.1" min="0" id="shoulder" name="shoulder" placeholder="e.g. 115" value="<?php echo htmlspecialchars($body_fat_inputs['shoulder']); ?>">
                            <span class="form-hint">Required for female calculations, recommended for males.</span>
                        </div>
                        <div class="form-group">
                            <label for="bf_gender">Gender</label>
                            <select id="bf_gender" name="bf_gender">
                                <option value="Male" <?php echo ($body_fat_inputs['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($body_fat_inputs['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="calculate_body_fat" class="btn-submit-large btn-info">Calculate Body Fat</button>
                    </div>
                </form>
            </div>

            <div class="profile-card">
                <div class="card-header">
                    <h2>Progress History</h2>
                    <p>Your last 10 entries</p>
                </div>

                <div class="progress-timeline">
                    <?php
                    $prog_res = mysqli_query($conn, "SELECT * FROM user_progress WHERE user_id = $user_id ORDER BY record_date DESC LIMIT 10");
                    if ($prog_res && mysqli_num_rows($prog_res) > 0) {
                        $counter = 0;
                        while ($p = mysqli_fetch_assoc($prog_res)) {
                            $counter++;
                            $record_date = new DateTime($p['record_date']);
                            ?>
                            <div class="progress-item">
                                <div class="progress-number">#<?php echo $counter; ?></div>
                                <div class="progress-content">
                                    <div class="progress-date"><?php echo $record_date->format('d.m.Y H:i'); ?></div>
                                    <div class="progress-stats">
                                        <span class="stat weight">⚖️ <?php echo number_format((float) $p['weight'], 1, ',', '.'); ?> kg</span>
                                        <?php if (isset($p['bmi']) && $p['bmi'] !== null): ?>
                                            <span class="stat bmi">BMI: <?php echo number_format((float) $p['bmi'], 1, ',', '.'); ?></span>
                                        <?php endif; ?>
                                        <?php if (isset($p['body_fat_percentage']) && $p['body_fat_percentage'] !== null): ?>
                                            <span class="stat body-fat">Fat: <?php echo number_format((float) $p['body_fat_percentage'], 1, ',', '.'); ?>%</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<div class="empty-state">No progress entries yet. Add your first one!</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
