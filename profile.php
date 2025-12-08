<?php
session_start();
include 'db.php';


// Güvenlik: Giriş yapmayan giremez
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";
$message_type = "";
$progress_message = "";
$progress_type = "";
$body_fat_message = "";
$body_fat_type = "";
$body_fat_percentage = null;
$body_fat_category = "";
$shoulder_waist_ratio = null;
$lean_mass = null;

<<<<<<< HEAD
// --- 1. PROFIL RESMİ YÜKLEME (TÜM KULLANICILAR) ---
if (isset($_POST['upload_profile_photo']) && $_FILES['profile_photo']['size'] > 0) {
    $file_type = mime_content_type($_FILES['profile_photo']['tmp_name']);
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (!in_array($file_type, $allowed_types)) {
        $message = "❌ Only image files are allowed!";
        $message_type = "error";
    } elseif ($_FILES['profile_photo']['size'] > 5 * 1024 * 1024) { // 5MB limit
        $message = "❌ File size cannot exceed 5MB!";
        $message_type = "error";
    } else {
        $photo_data = file_get_contents($_FILES['profile_photo']['tmp_name']);
        $photo_data = mysqli_real_escape_string($conn, $photo_data);
        
        $update_photo = "UPDATE users SET profile_photo='$photo_data' WHERE id=$user_id";
        if (mysqli_query($conn, $update_photo)) {
            $message = "✅ Profile photo uploaded successfully!";
            $message_type = "success";
        } else {
            $message = "❌ An error occurred while uploading the photo!";
=======
// --- 1. PROFILE PHOTO UPLOAD (ALL USERS) ---
    if (isset($_POST['upload_profile_photo']) && $_FILES['profile_photo']['size'] > 0) {
        $file_type = mime_content_type($_FILES['profile_photo']['tmp_name']);
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file_type, $allowed_types)) {
            $message = "❌ Only image files can be uploaded!";
            $message_type = "error";
        } elseif ($_FILES['profile_photo']['size'] > 5 * 1024 * 1024) { // 5MB limit
            $message = "❌ File size cannot exceed 5MB!";
            $message_type = "error";
        } else {
            $photo_data = file_get_contents($_FILES['profile_photo']['tmp_name']);
            $photo_data = mysqli_real_escape_string($conn, $photo_data);
            
            $update_photo = "UPDATE users SET profile_photo='$photo_data' WHERE id=$user_id";
            if (mysqli_query($conn, $update_photo)) {
                $message = "✅ Profile photo uploaded successfully!";
                $message_type = "success";
            } else {
                $message = "❌ Error uploading photo!";
                $message_type = "error";
            }
        }
    }

// --- 2. PROFILE UPDATE ---
    if (isset($_POST['update_profile'])) {
        $new_username = $_POST['username'];
        $new_email    = $_POST['email'];
        $new_phone    = $_POST['phone'];
        $new_age      = $_POST['age'];
        $new_gender   = $_POST['gender'];
        $payment_method = $_POST['payment_method'] ?? 'None';
        
        $update_sql = "UPDATE users SET username='$new_username', email='$new_email', phone='$new_phone', age='$new_age', gender='$new_gender', payment_method='$payment_method' WHERE id=$user_id";
        
        if (mysqli_query($conn, $update_sql)) {
            $message = "✅ Information updated successfully!";
            $message_type = "success";
            $_SESSION['username'] = $new_username;
        } else {
            $message = "❌ Error: " . mysqli_error($conn);
>>>>>>> 09cf71a93f4d555556a5b0a16fe9f47574ffaff7
            $message_type = "error";
        }
    }

<<<<<<< HEAD
// --- 2. PROFİL GÜNCELLEME ---
if (isset($_POST['update_profile'])) {
    $new_username = $_POST['username'];
    $new_email    = $_POST['email'];
    $new_phone    = $_POST['phone'];
    $new_age      = $_POST['age'];
    $new_gender   = $_POST['gender'];
    
    $update_sql = "UPDATE users SET username='$new_username', email='$new_email', phone='$new_phone', age='$new_age', gender='$new_gender' WHERE id=$user_id";
    
    if (mysqli_query($conn, $update_sql)) {
        $message = "✅ Details updated successfully!";
        $message_type = "success";
        $_SESSION['username'] = $new_username;
    } else {
        $message = "❌ Error: " . mysqli_error($conn);
        $message_type = "error";
=======
// --- 3. PROGRESS DATA ENTRY ---
    if (isset($_POST['add_progress'])) {
        $weight = $_POST['weight'];
        $height = $_POST['height'];
        $neck_cm = $_POST['neck_cm'] ?? null;
        $waist_cm = $_POST['waist_cm'] ?? null;
        
        // BMI Calculation
        if($height > 0) {
            $height_m = $height / 100; 
            $bmi = $weight / ($height_m * $height_m);
            $bmi = number_format($bmi, 2); 
        } else { $bmi = 0; }
        
        // Body Fat Percentage Calculation (US Navy Method)
        // Formula: Body Fat % = 495 / (1.0324 - 0.19077 * log10(waist - neck) + 0.15456 * log10(height)) - 450
        $body_fat_percentage = null;
        if($neck_cm && $waist_cm && $height > 0) {
            $neck = (float)$neck_cm;
            $waist = (float)$waist_cm;
            $h = (float)$height;
            
            $diff = $waist - $neck;
            if($diff > 0) {
                $body_fat = 495 / (1.0324 - 0.19077 * log10($diff) + 0.15456 * log10($h)) - 450;
                $body_fat_percentage = number_format(max(0, $body_fat), 2);
            }
        }

        $prog_sql = "INSERT INTO user_progress (user_id, weight, height, bmi, neck_cm, waist_cm, body_fat_percentage) VALUES ($user_id, '$weight', $height, '$bmi', ";
        if($neck_cm) $prog_sql .= "'$neck_cm'";
        else $prog_sql .= "NULL";
        $prog_sql .= ", ";
        if($waist_cm) $prog_sql .= "'$waist_cm'";
        else $prog_sql .= "NULL";
        $prog_sql .= ", ";
        if($body_fat_percentage) $prog_sql .= "'$body_fat_percentage'";
        else $prog_sql .= "NULL";
        $prog_sql .= ")";
        
        if(mysqli_query($conn, $prog_sql)){
            $msg = "✅ Progress saved! BMI: $bmi";
            if($body_fat_percentage) $msg .= " | Body Fat: $body_fat_percentage%";
            $progress_message = $msg;
            $progress_type = "success";
        } else {
            $progress_message = "❌ Error: " . mysqli_error($conn);
            $progress_type = "error";
        }
>>>>>>> 09cf71a93f4d555556a5b0a16fe9f47574ffaff7
    }

<<<<<<< HEAD
// --- 3. GELİŞİM VERİSİ EKLEME ---
if (isset($_POST['add_progress'])) {
    $weight = $_POST['weight'];
    $height = $_POST['height'];
    
    // BMI Hesaplama
    if($height > 0) {
        $height_m = $height / 100; 
        $bmi = $weight / ($height_m * $height_m);
        $bmi = number_format($bmi, 2); 
    } else { $bmi = 0; }

    $prog_sql = "INSERT INTO user_progress (user_id, weight, height, bmi) VALUES ($user_id, '$weight', $height, '$bmi')";
    
    if(mysqli_query($conn, $prog_sql)){
        $progress_message = "✅ Progress saved! BMI: $bmi";
        $progress_type = "success";
    } else {
        $progress_message = "❌ Error: " . mysqli_error($conn);
        $progress_type = "error";
    }
}

// Kullanıcı Bilgisi
$user_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));

$role_labels = [
    'user' => 'Student',
    'instructor' => 'Instructor',
    'admin' => 'Administrator'
];
$role_label = $role_labels[$user_row['role']] ?? 'Member';


$latest_progress_query = mysqli_query($conn, "SELECT weight, height, bmi, record_date FROM user_progress WHERE user_id = $user_id ORDER BY record_date DESC LIMIT 1");
$latest_progress = $latest_progress_query ? mysqli_fetch_assoc($latest_progress_query) : null;

$latest_progress_date = null;
$latest_bmi_value = null;
$latest_bmi_display = '-';
$latest_progress_note = 'Add your first entry';
$latest_weight_value = null;

if ($latest_progress) {
    $latest_weight_value = isset($latest_progress['weight']) ? (float)$latest_progress['weight'] : null;
    $height_cm_latest = isset($latest_progress['height']) ? (float)$latest_progress['height'] : 0;

    if (!empty($latest_progress['bmi'])) {
        $latest_bmi_value = (float)$latest_progress['bmi'];
    } elseif ($height_cm_latest > 0) {
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
$upcoming_sql = "SELECT classes.*, bookings.booking_date, bookings.id as booking_id FROM bookings JOIN classes ON bookings.class_id = classes.id WHERE bookings.user_id = $user_id AND classes.date_time >= NOW() ORDER BY classes.date_time ASC";
$upcoming_result = mysqli_query($conn, $upcoming_sql);
if ($upcoming_result) {
    while ($row = mysqli_fetch_assoc($upcoming_result)) {
        $upcoming_classes[] = $row;
    }
}

$past_classes = [];
$past_sql = "SELECT classes.*, bookings.booking_date, bookings.id as booking_id FROM bookings JOIN classes ON bookings.class_id = classes.id WHERE bookings.user_id = $user_id AND classes.date_time < NOW() ORDER BY classes.date_time DESC";
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
    return $value;
};

$body_fat_inputs['gender'] = $normalizeGender($body_fat_inputs['gender']);

if ($body_fat_inputs['gender'] === '' || !in_array($body_fat_inputs['gender'], ['Male', 'Female'], true)) {
    $body_fat_inputs['gender'] = 'Male';
}

if (isset($_POST['calculate_body_fat'])) {
    $clean_number = static function ($value) {
        $normalized = str_replace([' ', ','], ['', '.'], $value ?? '');
        return floatval($normalized);
    };

    $neck_cm = $clean_number($body_fat_inputs['neck']);
    $waist_cm = $clean_number($body_fat_inputs['waist']);
    $shoulder_cm = $clean_number($body_fat_inputs['shoulder']);
    $height_cm = $clean_number($body_fat_inputs['height']);
    $weight_kg = $clean_number($body_fat_inputs['weight']);
    $bf_gender = $body_fat_inputs['gender'];

    if ($neck_cm <= 0 || $waist_cm <= 0 || $height_cm <= 0) {
        $body_fat_message = "❌ Please make sure neck, waist, and height values are valid.";
        $body_fat_type = "error";
    } elseif ($waist_cm <= $neck_cm) {
        $body_fat_message = "❌ Waist circumference must be greater than neck circumference.";
        $body_fat_type = "error";
    } elseif ($bf_gender === 'Female' && $shoulder_cm <= 0) {
        $body_fat_message = "❌ Shoulder circumference is required for female calculations.";
        $body_fat_type = "error";
    } else {
        $bf_gender = ($bf_gender === 'Female') ? 'Female' : 'Male';

        if ($bf_gender === 'Male') {
            $base_value = 86.010 * log10($waist_cm - $neck_cm) - 70.041 * log10($height_cm) + 36.76;
            $ratio_adjustment = 0;
            if ($shoulder_cm > 0 && $waist_cm > 0) {
                // Daha geniş omuzlar genelde daha düşük yağ yüzdesine işaret eder, küçük bir düzeltme uygula
                $shoulder_ratio = $shoulder_cm / $waist_cm;
                $shoulder_ratio = max(min($shoulder_ratio, 1.7), 0.9);
                $ratio_adjustment = ($shoulder_ratio - 1.2) * 8;
            }
            $body_fat_percentage = $base_value - $ratio_adjustment;
        } else {
            $effective_circumference = $waist_cm + $shoulder_cm - $neck_cm;
            if ($effective_circumference <= 0) {
                $body_fat_message = " Measurements seem inconsistent. Waist + shoulder should exceed neck.";
                $body_fat_type = "error";
            } else {
                $body_fat_percentage = 163.205 * log10($effective_circumference) - 97.684 * log10($height_cm) - 78.387;
            }
        }

        if ($body_fat_message === "") {
            $body_fat_percentage = max(min($body_fat_percentage, 60), 2);
            $body_fat_message = "✅ Your estimated body fat percentage is %" . number_format($body_fat_percentage, 1, ',', '.') . ".";
            $body_fat_type = "success";

            $categories = [
                'Male' => [
                    ['limit' => 6, 'label' => 'Athletic'],
                    ['limit' => 13, 'label' => 'Fit'],
                    ['limit' => 17, 'label' => 'Good'],
                    ['limit' => 24, 'label' => 'Acceptable'],
                    ['limit' => 100, 'label' => 'Caution']
                ],
                'Female' => [
                    ['limit' => 14, 'label' => 'Athletic'],
                    ['limit' => 21, 'label' => 'Fit'],
                    ['limit' => 25, 'label' => 'Good'],
                    ['limit' => 31, 'label' => 'Acceptable'],
                    ['limit' => 100, 'label' => 'Caution']
                ]
            ];

            $body_fat_category = '';
            foreach ($categories[$bf_gender] as $category) {
                if ($body_fat_percentage <= $category['limit']) {
                    $body_fat_category = $category['label'];
                    break;
                }
            }

            if ($shoulder_cm > 0) {
                $shoulder_waist_ratio = $shoulder_cm / $waist_cm;
            }

            if ($weight_kg > 0) {
                $lean_mass = $weight_kg * (1 - ($body_fat_percentage / 100));
            }
        }
    }
}

include 'header.php';
=======
// User Information
$user_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));include 'header.php';
>>>>>>> 09cf71a93f4d555556a5b0a16fe9f47574ffaff7
?>

<div class="profile-page">
    
    <!-- PROFILE HERO BÖLÜMÜ - SPOR UYGULAMASI STİLİ -->
    <div class="profile-hero-v2">
        <div class="profile-hero-wrapper">
            <div class="profile-avatar">
                <?php if (!empty($user_row['profile_photo'])): ?>
                    <img src="data:image/jpeg;base64,<?php echo base64_encode($user_row['profile_photo']); ?>" alt="Profile Photo">
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
                        <span class="profile-meta-item"> <?php echo htmlspecialchars($user_row['email']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($user_row['phone'])): ?>
                        <span class="profile-meta-item">📞 <?php echo htmlspecialchars($user_row['phone']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
<<<<<<< HEAD
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
=======
            <h1 style="color: white; margin: 10px 0 5px 0; font-size: 28px;"><?php echo htmlspecialchars($user_row['username']); ?></h1>
            <p style="color: rgba(255,255,255,0.9); margin: 0; font-size: 14px;">
                <?php 
                $role_text = [
                    'user' => 'Student',
                    'instructor' => 'Instructor',
                    'admin' => 'Administrator'
                ];
                echo $role_text[$user_row['role']] ?? 'User';
                ?>
            </p>
>>>>>>> 09cf71a93f4d555556a5b0a16fe9f47574ffaff7
        </div>
    </div>

    <div class="profile-container">
        
        <!-- SOL KOLON: HESAP BİLGİLERİ & GELİŞİM -->
        <div class="profile-left">
            <div class="profile-card profile-card--account">
                <div class="card-header">
                    <h2>Account Details</h2>
                    <p>Keep your profile information up to date</p>
                </div>

                <?php if($message): ?>
                    <div class="message-box message-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="profile-form profile-form--stacked">
                    <div class="form-group">
                        <label for="username">Full Name</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user_row['username']); ?>" required>
                    </div>

                    <div class="form-group">
<<<<<<< HEAD
                        <label for="email">Email</label>
=======
                        <label for="email">Email Address</label>
>>>>>>> 09cf71a93f4d555556a5b0a16fe9f47574ffaff7
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_row['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user_row['phone']); ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="age">Age</label>
                            <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($user_row['age']); ?>" min="1" max="120">
                        </div>

                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender">
                                <option value="">-- Select --</option>
<<<<<<< HEAD
                                <option value="Male" <?php if($user_row['gender']=='Male' || $user_row['gender']=='Erkek') echo 'selected'; ?>>Male</option>
                                <option value="Female" <?php if($user_row['gender']=='Female' || $user_row['gender']=='Kadın') echo 'selected'; ?>>Female</option>
                                <option value="Prefer not to say" <?php if($user_row['gender']=='Prefer not to say' || $user_row['gender']=='Belirtmek İstemiyorum') echo 'selected'; ?>>Prefer not to say</option>
=======
                                <option value="Erkek" <?php if($user_row['gender']=='Erkek') echo 'selected'; ?>>Male</option>
                                <option value="Kadın" <?php if($user_row['gender']=='Kadın') echo 'selected'; ?>>Female</option>
>>>>>>> 09cf71a93f4d555556a5b0a16fe9f47574ffaff7
                            </select>
                        </div>
                    </div>

<<<<<<< HEAD
                    <div class="form-actions">
                        <button type="submit" name="update_profile" class="btn-submit-large">Update Details</button>
                    </div>
                </form>

                <div class="card-divider"></div>

                <div class="profile-photo-upload">
                    <h3> Profile Photo</h3>
                    <p class="form-hint">PNG, JPG, GIF, WebP (Max 5MB)</p>
                    <form method="POST" enctype="multipart/form-data" class="profile-form profile-form--stacked">
                        <div class="form-group">
                            <label for="profile_photo">Choose New Photo</label>
=======
                    <div class="form-group">
                        <label for="payment_method">💳 Preferred Payment Method</label>
                        <select id="payment_method" name="payment_method">
                            <option value="None" <?php if($user_row['payment_method']=='None') echo 'selected'; ?>>-- Not Set --</option>
                            <option value="Mastercard" <?php if($user_row['payment_method']=='Mastercard') echo 'selected'; ?>>🔴 Mastercard</option>
                            <option value="Visa" <?php if($user_row['payment_method']=='Visa') echo 'selected'; ?>>🔵 Visa</option>
                        </select>
                    </div>

                    <button type="submit" name="update_profile" class="btn-submit-large">💾 Update Information</button>
                </form>

                <!-- PROFILE PHOTO UPDATE (ALL USERS) -->
                <div style="margin-top: 30px; padding-top: 30px; border-top: 1px solid #eee;">
                    <h3 style="font-size: 1.1rem; margin-bottom: 15px;">📸 Change Profile Photo</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="profile_photo">Select New Photo</label>
>>>>>>> 09cf71a93f4d555556a5b0a16fe9f47574ffaff7
                            <input type="file" id="profile_photo" name="profile_photo" accept="image/*" required>
                        </div>
<<<<<<< HEAD
                        <div class="form-actions">
                            <button type="submit" name="upload_profile_photo" class="btn-submit-large btn-contrast"> Update Photo</button>
                        </div>
=======
                        <button type="submit" name="upload_profile_photo" class="btn-submit-large" style="background: #4CAF50;">📤 Update Photo</button>
>>>>>>> 09cf71a93f4d555556a5b0a16fe9f47574ffaff7
                    </form>
                </div>
            </div>

            <div class="profile-card">
                <div class="card-header">
<<<<<<< HEAD
                    <h2>Progress Entry</h2>
                    <p>Track your progress by logging weight and height</p>
=======
                    <h2>📈 Progress Record</h2>
                    <p>Track your weight, BMI, and body fat percentage</p>
>>>>>>> 09cf71a93f4d555556a5b0a16fe9f47574ffaff7
                </div>

                <?php if($progress_message): ?>
                    <div class="message-box message-<?php echo $progress_type; ?>">
                        <?php echo $progress_message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="profile-form">
                    <div class="form-row">
                        <div class="form-group">
<<<<<<< HEAD
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
=======
                            <label for="weight">Weight (kg) *</label>
                            <input type="number" id="weight" name="weight" step="0.1" min="0" placeholder="e.g: 75.5" required>
                        </div>

                        <div class="form-group">
                            <label for="height">Height (cm) *</label>
                            <input type="number" id="height" name="height" min="0" placeholder="e.g: 180" required>
                        </div>
                    </div>

                    <div style="background: #f5f5f5; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <h4 style="margin: 0 0 12px 0; color: #333;">📏 Body Fat Measurement (Optional)</h4>
                        <p style="margin: 0 0 15px 0; color: #666; font-size: 13px;">For more accurate body composition tracking, provide neck and waist measurements. We'll calculate your body fat percentage using the US Navy method.</p>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="neck_cm">Neck (cm)</label>
                                <input type="number" id="neck_cm" name="neck_cm" step="0.1" min="0" placeholder="e.g: 37.5">
                            </div>

                            <div class="form-group">
                                <label for="waist_cm">Waist (cm)</label>
                                <input type="number" id="waist_cm" name="waist_cm" step="0.1" min="0" placeholder="e.g: 80.0">
                            </div>
                        </div>
                    </div>

                    <button type="submit" name="add_progress" class="btn-submit-large btn-success">✅ Add Record</button>
>>>>>>> 09cf71a93f4d555556a5b0a16fe9f47574ffaff7
                </form>
            </div>

        </div>

        <!-- ORTA KOLON: DERS PROGRAMI -->
        <div class="profile-middle">
            
            <!-- YAKLAŞAN DERSLER -->
            <div class="profile-card">
                <div class="card-header">
<<<<<<< HEAD
                    <h2> Upcoming Sessions</h2>
=======
                    <h2>📅 Upcoming Classes</h2>
>>>>>>> 09cf71a93f4d555556a5b0a16fe9f47574ffaff7
                    <p>Your scheduled workouts</p>
                </div>

                <div class="lessons-list">
<<<<<<< HEAD
                    <?php if(!empty($upcoming_classes)): ?>
                        <?php foreach($upcoming_classes as $row): ?>
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
                                    <?php if(!empty($row['video_link'])): ?>
                                        <a href="<?php echo htmlspecialchars($row['video_link']); ?>" target="_blank" class="btn-action-small btn-watch">🎥 Join Live</a>
                                    <?php endif; ?>
                                    <a href="cancel_booking.php?id=<?php echo $row['booking_id']; ?>" onclick="return confirm('Are you sure you want to cancel this session?')" class="btn-action-small btn-cancel">❌ Cancel</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">No upcoming sessions yet</div>
                    <?php endif; ?>
=======
                    <?php
                    $upcoming_sql = "SELECT classes.*, bookings.booking_date, bookings.id as booking_id 
                                    FROM bookings 
                                    JOIN classes ON bookings.class_id = classes.id 
                                    WHERE bookings.user_id = $user_id AND classes.date_time >= NOW()
                                    ORDER BY classes.date_time ASC";
                    
                    $upcoming_result = mysqli_query($conn, $upcoming_sql);
                    
                    if(mysqli_num_rows($upcoming_result) > 0) {
                        while($row = mysqli_fetch_assoc($upcoming_result)) {
                            $class_date = new DateTime($row['date_time']);
                            echo '<div class="lesson-card upcoming">';
                            echo '<div class="lesson-header">';
                            echo '<h3>' . htmlspecialchars($row['title']) . '</h3>';
                            echo '<span class="lesson-type-badge">' . $row['class_type'] . '</span>';
                            echo '</div>';
                            echo '<div class="lesson-meta">';
                            echo '<div class="meta-item">📅 ' . $class_date->format("d.m.Y") . '</div>';
                            echo '<div class="meta-item">⏰ ' . $class_date->format("H:i") . '</div>';
                            echo '<div class="meta-item">👨‍🏫 ' . htmlspecialchars($row['trainer_name']) . '</div>';
                            echo '</div>';
                            echo '<div class="lesson-actions">';
                            echo '<a href="' . htmlspecialchars($row['video_link']) . '" target="_blank" class="btn-action-small btn-watch">🎥 Join Class</a>';
                            echo '<a href="cancel_booking.php?id=' . $row['booking_id'] . '" onclick="return confirm(\'Are you sure you want to cancel this class?\')" class="btn-action-small btn-cancel">❌ Cancel</a>';
                            echo '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="empty-state">📭 No upcoming classes</div>';
                    }
                    ?>
>>>>>>> 09cf71a93f4d555556a5b0a16fe9f47574ffaff7
                </div>
            </div>

            <!-- COMPLETED CLASSES -->
            <div class="profile-card past-section">
                <div class="card-header">
<<<<<<< HEAD
                    <h2>Completed Sessions</h2>
                    <p>Rate the workouts you have completed</p>
                </div>

                <div class="lessons-list">
                    <?php if(!empty($past_classes)): ?>
                        <?php foreach($past_classes as $row): ?>
                            <?php
                                $class_date = new DateTime($row['date_time']);
                                $c_id = $row['id'];
                                $review_query = mysqli_query($conn, "SELECT * FROM reviews WHERE user_id=$user_id AND class_id=$c_id");
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

                                <?php if($rev_data): ?>
                                    <div class="review-badge">
                                        <div class="star-rating">
                                            <?php for($i = 0; $i < $rev_data['rating']; $i++): ?>
                                                ⭐
                                            <?php endfor; ?>
                                            <span><?php echo $rev_data['rating']; ?>/5</span>
                                        </div>
                                        <?php if(!empty($rev_data['comment'])): ?>
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
=======
                    <h2>✅ Completed Classes</h2>
                    <p>Rate your completed workouts</p>
                </div>

                <div class="lessons-list">
                    <?php
                    $past_sql = "SELECT classes.*, bookings.booking_date, bookings.id as booking_id 
                                FROM bookings 
                                JOIN classes ON bookings.class_id = classes.id 
                                WHERE bookings.user_id = $user_id AND classes.date_time < NOW()
                                ORDER BY classes.date_time DESC";
                    
                    $past_result = mysqli_query($conn, $past_sql);
                    
                    if(mysqli_num_rows($past_result) > 0) {
                        while($row = mysqli_fetch_assoc($past_result)) {
                            $class_date = new DateTime($row['date_time']);
                            $c_id = $row['id'];
                            
                            // Check rating
                            $check_rev = mysqli_query($conn, "SELECT * FROM reviews WHERE user_id=$user_id AND class_id=$c_id");
                            $rev_data = mysqli_fetch_assoc($check_rev);
                            
                            echo '<div class="lesson-card past">';
                            echo '<div class="lesson-header">';
                            echo '<h3>' . htmlspecialchars($row['title']) . '</h3>';
                            echo '<span class="lesson-type-badge past-badge">' . $row['class_type'] . '</span>';
                            echo '</div>';
                            echo '<div class="lesson-meta">';
                            echo '<div class="meta-item">📅 ' . $class_date->format("d.m.Y H:i") . '</div>';
                            echo '<div class="meta-item">👨‍🏫 ' . htmlspecialchars($row['trainer_name']) . '</div>';
                            echo '</div>';
                            
                            if($rev_data) {
                                echo '<div class="review-badge">';
                                echo '<div class="star-rating">';
                                for($i = 0; $i < $rev_data['rating']; $i++) {
                                    echo '⭐';
                                }
                                echo ' ' . $rev_data['rating'] . '/5';
                                echo '</div>';
                                if(!empty($rev_data['comment'])) {
                                    echo '<p class="review-comment">"' . htmlspecialchars($rev_data['comment']) . '"</p>';
                                }
                                echo '</div>';
                            } else {
                                echo '<div class="no-review-badge">💬 No rating yet</div>';
                            }
                            
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="empty-state">📭 No completed classes</div>';
                    }
                    ?>
>>>>>>> 09cf71a93f4d555556a5b0a16fe9f47574ffaff7
                </div>
            </div>

        </div>

        <!-- RIGHT COLUMN: PROGRESS HISTORY -->
        <div class="profile-right">
            <div class="profile-card">
                <div class="card-header">
                    <h2> Body Fat Analyzer</h2>
                    <p>Estimate body fat using neck, waist, and shoulder circumferences</p>
                </div>

                <?php if($body_fat_message): ?>
                    <div class="message-box message-<?php echo $body_fat_type; ?>">
                        <div><?php echo $body_fat_message; ?></div>
                        <?php if($body_fat_percentage !== null && $body_fat_type === 'success'): ?>
                            <div class="bodyfat-summary">
                                <?php if($body_fat_category): ?>
                                    <span class="summary-pill">Category: <?php echo htmlspecialchars($body_fat_category); ?></span>
                                <?php endif; ?>
                                <?php if($shoulder_waist_ratio): ?>
                                    <span class="summary-pill">Shoulder/Waist: <?php echo number_format($shoulder_waist_ratio, 2, ',', '.'); ?></span>
                                <?php endif; ?>
                                <?php if($lean_mass !== null): ?>
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
<<<<<<< HEAD
                    <h2>Progress History</h2>
                    <p>Your last 10 entries</p>
=======
                    <h2>📈 Progress History</h2>
                    <p>Your last 10 records</p>
>>>>>>> 09cf71a93f4d555556a5b0a16fe9f47574ffaff7
                </div>

                <div class="progress-timeline">
                    <?php
                    $prog_res = mysqli_query($conn, "SELECT * FROM user_progress WHERE user_id = $user_id ORDER BY record_date DESC LIMIT 10");
                    if(mysqli_num_rows($prog_res) > 0) {
                        $counter = 0;
                        while($p = mysqli_fetch_assoc($prog_res)) {
                            $counter++;
                            $record_date = new DateTime($p['record_date']);
                            echo '<div class="progress-item">';
                            echo '<div class="progress-number">#' . $counter . '</div>';
                            echo '<div class="progress-content">';
                            echo '<div class="progress-date">' . $record_date->format("d.m.Y H:i") . '</div>';
                            echo '<div class="progress-stats">';
                            echo '<span class="stat weight">⚖️ ' . number_format($p['weight'], 1, ',', '.') . ' kg</span>';
<<<<<<< HEAD
                            echo '<span class="stat bmi"> BMI: ' . number_format($p['bmi'], 1, ',', '.') . '</span>';
=======
                            echo '<span class="stat bmi">📊 BMI: ' . number_format($p['bmi'], 1, ',', '.') . '</span>';
                            if(!empty($p['body_fat_percentage'])) {
                                echo '<span class="stat body-fat">🔥 Fat: ' . number_format($p['body_fat_percentage'], 1, ',', '.') . '%</span>';
                            }
>>>>>>> 09cf71a93f4d555556a5b0a16fe9f47574ffaff7
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                        }
                    } else {
<<<<<<< HEAD
                        echo '<div class="empty-state">No progress entries yet. Add your first one!</div>';
=======
                        echo '<div class="empty-state">📭 No progress records yet. Add your first record!</div>';
>>>>>>> 09cf71a93f4d555556a5b0a16fe9f47574ffaff7
                    }
                    ?>
                </div>
            </div>

        </div>

    </div>

</div>

<?php include 'footer.php'; ?>