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
            $message_type = "error";
        }
    }

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
    }

// User Information
$user_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));include 'header.php';
?>

<div class="profile-page">
    
    <!-- PROFILE HERO BÖLÜMÜ - FOTOĞRAFLI -->
    <div class="profile-hero-simple" style="background: linear-gradient(135deg, #185ADB 0%, #1245a8 100%); padding: 60px 20px; text-align: center; position: relative;">
        <div style="max-width: 1200px; margin: 0 auto;">
            <!-- Profil Fotoğrafı -->
            <div style="margin-bottom: 20px;">
                <div class="profile-photo-hero" style="width: 140px; height: 140px; margin: 0 auto; border-radius: 50%; overflow: hidden; background: white; display: flex; align-items: center; justify-content: center; border: 5px solid white; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                    <?php
                    if(!empty($user_row['profile_photo'])) {
                        echo '<img src="data:image/jpeg;base64,' . base64_encode($user_row['profile_photo']) . '" style="width: 100%; height: 100%; object-fit: cover;" alt="Profil Fotoğrafı">';
                    } else {
                        echo '<span style="font-size: 80px;">👤</span>';
                    }
                    ?>
                </div>
            </div>
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
        </div>
    </div>

    <div class="profile-container">
        
        <!-- SOL KOLON: HESAP BİLGİLERİ & GELİŞİM -->
        <div class="profile-left">
            
            <!-- HESAP BİLGİLERİ -->

                <?php if($message): ?>
                    <div class="message-box message-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="profile-form">
                    <div class="form-group">
                        <label for="username">Full Name</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user_row['username']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_row['email']); ?>" required>
                    </div>

                    <div class="form-group">
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
                                <option value="Erkek" <?php if($user_row['gender']=='Erkek') echo 'selected'; ?>>Male</option>
                                <option value="Kadın" <?php if($user_row['gender']=='Kadın') echo 'selected'; ?>>Female</option>
                            </select>
                        </div>
                    </div>

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
                            <input type="file" id="profile_photo" name="profile_photo" accept="image/*" required>
                            <small style="color: #666; display: block; margin-top: 5px;">PNG, JPG, GIF, WebP (Max 5MB)</small>
                        </div>
                        <button type="submit" name="upload_profile_photo" class="btn-submit-large" style="background: #4CAF50;">📤 Update Photo</button>
                    </form>
                </div>
            </div>

            <!-- GELİŞİM EKLE -->
            <div class="profile-card">
                <div class="card-header">
                    <h2>📈 Progress Record</h2>
                    <p>Track your weight, BMI, and body fat percentage</p>
                </div>

                <?php if($progress_message): ?>
                    <div class="message-box message-<?php echo $progress_type; ?>">
                        <?php echo $progress_message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="profile-form">
                    <div class="form-row">
                        <div class="form-group">
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
                </form>
            </div>

        </div>

        <!-- ORTA KOLON: DERS PROGRAMI -->
        <div class="profile-middle">
            
            <!-- YAKLAŞAN DERSLER -->
            <div class="profile-card">
                <div class="card-header">
                    <h2>📅 Upcoming Classes</h2>
                    <p>Your scheduled workouts</p>
                </div>

                <div class="lessons-list">
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
                </div>
            </div>

            <!-- COMPLETED CLASSES -->
            <div class="profile-card past-section">
                <div class="card-header">
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
                </div>
            </div>

        </div>

        <!-- RIGHT COLUMN: PROGRESS HISTORY -->
        <div class="profile-right">
            
            <div class="profile-card">
                <div class="card-header">
                    <h2>📈 Progress History</h2>
                    <p>Your last 10 records</p>
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
                            echo '<span class="stat bmi">📊 BMI: ' . number_format($p['bmi'], 1, ',', '.') . '</span>';
                            if(!empty($p['body_fat_percentage'])) {
                                echo '<span class="stat body-fat">🔥 Fat: ' . number_format($p['body_fat_percentage'], 1, ',', '.') . '%</span>';
                            }
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="empty-state">📭 No progress records yet. Add your first record!</div>';
                    }
                    ?>
                </div>
            </div>

        </div>

    </div>

</div>

<?php include 'footer.php'; ?>