<?php
session_start();
include 'db.php';
$page_title = "My Profile | GYM";

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
$payment_message = "";
$payment_message_type = "";

// --- 1. INSTRUCTOR PROFIL RESMİ YÜKLEME ---
if (isset($_POST['upload_profile_photo']) && $_FILES['profile_photo']['size'] > 0) {
    $file_type = mime_content_type($_FILES['profile_photo']['tmp_name']);
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (!in_array($file_type, $allowed_types)) {
        $message = " Yalnızca resim dosyaları yüklenebilir!";
        $message_type = "error";
    } elseif ($_FILES['profile_photo']['size'] > 5 * 1024 * 1024) { // 5MB limit
        $message = "❌ Dosya boyutu 5MB'dan büyük olamaz!";
        $message_type = "error";
    } else {
        $photo_data = file_get_contents($_FILES['profile_photo']['tmp_name']);
        $photo_data = mysqli_real_escape_string($conn, $photo_data);
        
        $update_photo = "UPDATE users SET profile_photo='$photo_data' WHERE id=$user_id";
        if (mysqli_query($conn, $update_photo)) {
            $message = "✅ Profile photo uploaded successfully!";
            $message_type = "success";
        } else {
            $message = "❌ Error: " . mysqli_error($conn);
            $message_type = "error";
        }
    }
}

// --- 2. PROFİL GÜNCELLEME ---
if (isset($_POST['update_profile'])) {
    $new_username = $_POST['username'];
    $new_email    = $_POST['email'];
    $new_phone    = $_POST['phone'];
    $new_age      = $_POST['age'];
    $new_gender   = $_POST['gender'];
    
    $update_sql = "UPDATE users SET username='$new_username', email='$new_email', phone='$new_phone', age='$new_age', gender='$new_gender' WHERE id=$user_id";
    
    if (mysqli_query($conn, $update_sql)) {
        $message = "✅ Information updated successfully!";
        $message_type = "success";
        $_SESSION['username'] = $new_username;
    } else {
        $message = "❌ Error: " . mysqli_error($conn);
        $message_type = "error";
    }
}

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
        $progress_message = "✅ Progress recorded! BMI: $bmi";
        $progress_type = "success";
    } else {
        $progress_message = "❌ Error: " . mysqli_error($conn);
        $progress_type = "error";
    }
}

// --- 4. ÖDEME YÖNTEMİ EKLEME ---
if (isset($_POST['add_payment_method'])) {
    $payment_type = $_POST['payment_type'];
    $card_number = isset($_POST['card_number']) ? $_POST['card_number'] : '';
    $cardholder_name = isset($_POST['cardholder_name']) ? $_POST['cardholder_name'] : '';
    $expiry_date = isset($_POST['expiry_date']) ? $_POST['expiry_date'] : '';
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    
    // Eğer default seçildiyse, diğerlerini default'tan çıkar
    if($is_default) {
        mysqli_query($conn, "UPDATE user_payment_methods SET is_default = 0 WHERE user_id = $user_id");
    }
    
    // Kart numarasını maskele (sadece son 4 haneyi göster)
    $masked_card = '';
    if(!empty($card_number)) {
        $card_number_clean = preg_replace('/\s+/', '', $card_number);
        if(strlen($card_number_clean) >= 4) {
            $masked_card = '**** **** **** ' . substr($card_number_clean, -4);
        } else {
            $masked_card = $card_number;
        }
    }
    
    $payment_method_sql = "INSERT INTO user_payment_methods (user_id, payment_type, card_number, cardholder_name, expiry_date, is_default) 
                           VALUES ($user_id, '$payment_type', '$masked_card', '$cardholder_name', '$expiry_date', $is_default)";
    
    if(mysqli_query($conn, $payment_method_sql)){
        $payment_message = "✅ Payment method added successfully!";
        $payment_message_type = "success";
    } else {
        $payment_message = "❌ Error: " . mysqli_error($conn);
        $payment_message_type = "error";
    }
}

// --- 5. ÖDEME YÖNTEMİ SİLME ---
if (isset($_GET['delete_payment_method'])) {
    $method_id = intval($_GET['delete_payment_method']);
    $delete_sql = "DELETE FROM user_payment_methods WHERE id = $method_id AND user_id = $user_id";
    
    if(mysqli_query($conn, $delete_sql)){
        $payment_message = "✅ Payment method deleted successfully!";
        $payment_message_type = "success";
    } else {
        $payment_message = "❌ Error: " . mysqli_error($conn);
        $payment_message_type = "error";
    }
}

// Kullanıcı Bilgisi
$user_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));

include 'header.php';
?>

<div class="profile-page">
    
    <!-- PROFILE HERO BÖLÜMÜ -->
    <div class="profile-hero-simple">
        <div class="profile-hero-content-with-photo">
            <?php if($user_row['profile_photo']): ?>
                <img src="data:image/jpeg;base64,<?php echo base64_encode($user_row['profile_photo']); ?>" alt="Profile Photo" class="profile-hero-photo">
            <?php else: ?>
                <div class="profile-hero-photo-placeholder">
                    <span><?php echo strtoupper(substr($user_row['username'], 0, 1)); ?></span>
                </div>
            <?php endif; ?>
            <h1>My Profile</h1>
        </div>
    </div>

    <div class="profile-container">
        
        <!-- SOL KOLON: HESAP BİLGİLERİ & GELİŞİM -->
        <div class="profile-left">
            
            <!-- HESAP BİLGİLERİ -->
            <div class="profile-card">
                <div class="card-header">
                    <h2> Account Information</h2>
                    <p>Update your personal information</p>
                </div>

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
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_row['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone</label>
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

                    <button type="submit" name="update_profile" class="btn-submit-large"> Update Information</button>
                </form>

                <!-- PROFIL RESMİ UPLOAD - TÜM KULLANICILAR İÇİN -->
                <div style="margin-top: 30px; padding-top: 30px; border-top: 1px solid #eee;">
                    <h3 style="font-size: 1.1rem; margin-bottom: 15px;"> Profile Photo </h3>
                    <?php if($user_row['profile_photo']): ?>
                        <div style="margin-bottom: 15px;">
                            <img src="data:image/jpeg;base64,<?php echo base64_encode($user_row['profile_photo']); ?>" alt="Current Profile Photo" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 2px solid #ff0000;">
                        </div>
                    <?php endif; ?>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="profile_photo">Upload Profile Photo</label>
                            <input type="file" id="profile_photo" name="profile_photo" accept="image/*" required>
                            <small style="color: #666; display: block; margin-top: 5px;">PNG, JPG, GIF (Max 5MB)</small>
                        </div>
                        <button type="submit" name="upload_profile_photo" class="btn-submit-large" style="background: #ff0000;"> Upload Photo</button>
                    </form>
                </div>
            </div>

            <!-- GELİŞİM EKLE -->
            <div class="profile-card">
                <div class="card-header">
                    <h2> Progress Record</h2>
                    <p>Track your progress by adding weight and height information</p>
                </div>

                <?php if($progress_message): ?>
                    <div class="message-box message-<?php echo $progress_type; ?>">
                        <?php echo $progress_message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="profile-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="weight">Weight (kg)</label>
                            <input type="number" id="weight" name="weight" step="0.1" min="0" placeholder="Ex: 75.5" required>
                        </div>

                        <div class="form-group">
                            <label for="height">Height (cm)</label>
                            <input type="number" id="height" name="height" min="0" placeholder="Ex: 180" required>
                        </div>
                    </div>

                    <button type="submit" name="add_progress" class="btn-submit-large btn-red"> Add Record</button>
                </form>
            </div>

            <!-- ÖDEME BİLGİLERİ -->
            <div class="profile-card">
                <div class="card-header">
                    <h2>💳 Payment Information</h2>
                    <p>Manage your payment methods and view transaction history</p>
                </div>

                <?php if($payment_message): ?>
                    <div class="message-box message-<?php echo $payment_message_type; ?>">
                        <?php echo $payment_message; ?>
                    </div>
                <?php endif; ?>

                <!-- Ödeme Yöntemi Ekleme Formu -->
                <div style="margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #e0e0e0;">
                    <h3 style="font-size: 1.1rem; margin-bottom: 15px; color: #212121;">Add Payment Method</h3>
                    <form method="POST" class="profile-form">
                        <div class="form-group">
                            <label for="payment_type">Payment Type</label>
                            <select id="payment_type" name="payment_type" required>
                                <option value="">-- Select --</option>
                                <option value="Credit Card">Credit Card</option>
                                <option value="Debit Card">Debit Card</option>
                                <option value="PayPal">PayPal</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                            </select>
                        </div>

                        <div class="form-group" id="card-fields">
                            <label for="card_number">Card Number</label>
                            <input type="text" id="card_number" name="card_number" class="payment-input-field" 
                                   placeholder="1234 5678 9012 3456" maxlength="19" pattern="[0-9\s]{13,19}">
                            <small style="color: #666; font-size: 0.85rem;">Only last 4 digits will be saved</small>
                        </div>

                        <div class="form-row" id="card-details">
                            <div class="form-group">
                                <label for="cardholder_name">Cardholder Name</label>
                                <input type="text" id="cardholder_name" name="cardholder_name" 
                                       class="payment-input-field" placeholder="Full Name">
                            </div>
                            <div class="form-group">
                                <label for="expiry_date">Expiry Date</label>
                                <input type="text" id="expiry_date" name="expiry_date" 
                                       class="payment-input-field" placeholder="MM/YY" maxlength="5" pattern="[0-9]{2}/[0-9]{2}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="is_default" value="1" style="width: auto;">
                                <span>Set as default payment method</span>
                            </label>
                        </div>

                        <button type="submit" name="add_payment_method" class="btn-submit-large btn-red">Add Payment Method</button>
                    </form>
                </div>

                <!-- Kaydedilen Ödeme Yöntemleri -->
                <div style="margin-bottom: 20px;">
                    <h3 style="font-size: 1.1rem; margin-bottom: 15px; color: #212121;">Saved Payment Methods</h3>
                    <?php
                    $saved_methods_sql = "SELECT * FROM user_payment_methods WHERE user_id = $user_id ORDER BY is_default DESC, created_at DESC";
                    $saved_methods_result = mysqli_query($conn, $saved_methods_sql);
                    
                    if(mysqli_num_rows($saved_methods_result) > 0) {
                        while($method = mysqli_fetch_assoc($saved_methods_result)) {
                            echo '<div class="saved-payment-method">';
                            echo '<div class="saved-method-header">';
                            echo '<div>';
                            echo '<strong>' . htmlspecialchars($method['payment_type']) . '</strong>';
                            if($method['is_default']) {
                                echo ' <span style="background: #ff0000; color: #ffffff; padding: 2px 8px; border-radius: 3px; font-size: 0.75rem; margin-left: 8px;">Default</span>';
                            }
                            echo '</div>';
                            echo '<a href="profile.php?delete_payment_method=' . $method['id'] . '" onclick="return confirm(\'Are you sure you want to delete this payment method?\')" style="color: #ff0000; text-decoration: none; font-size: 0.9rem;">Delete</a>';
                            echo '</div>';
                            
                            if(!empty($method['card_number'])) {
                                echo '<div class="saved-method-detail">Card: ' . htmlspecialchars($method['card_number']) . '</div>';
                            }
                            if(!empty($method['cardholder_name'])) {
                                echo '<div class="saved-method-detail">Name: ' . htmlspecialchars($method['cardholder_name']) . '</div>';
                            }
                            if(!empty($method['expiry_date'])) {
                                echo '<div class="saved-method-detail">Expiry: ' . htmlspecialchars($method['expiry_date']) . '</div>';
                            }
                            
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="empty-state" style="padding: 20px;">No saved payment methods yet</div>';
                    }
                    ?>
                </div>

                <!-- Ödeme Geçmişi -->
                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
                    <h3 style="font-size: 1.1rem; margin-bottom: 15px; color: #212121;">Payment History</h3>
                </div>

                <div class="payments-list">
                    <?php
                    $payments_sql = "SELECT payments.*, classes.title, classes.class_type 
                                    FROM payments 
                                    JOIN classes ON payments.class_id = classes.id 
                                    WHERE payments.user_id = $user_id 
                                    ORDER BY payments.created_at DESC 
                                    LIMIT 10";
                    
                    $payments_result = mysqli_query($conn, $payments_sql);
                    
                    if(mysqli_num_rows($payments_result) > 0) {
                        while($payment = mysqli_fetch_assoc($payments_result)) {
                            $payment_date = new DateTime($payment['created_at']);
                            $status_class = $payment['payment_status'] == 'completed' ? 'status-success' : 
                                          ($payment['payment_status'] == 'pending' ? 'status-pending' : 'status-failed');
                            $status_text = $payment['payment_status'] == 'completed' ? '✅ Completed' : 
                                         ($payment['payment_status'] == 'pending' ? '⏳ Pending' : '❌ Failed');
                            
                            echo '<div class="payment-item">';
                            echo '<div class="payment-header-item">';
                            echo '<div class="payment-title">' . htmlspecialchars($payment['title']) . '</div>';
                            echo '<span class="payment-status ' . $status_class . '">' . $status_text . '</span>';
                            echo '</div>';
                            echo '<div class="payment-details">';
                            echo '<div class="payment-detail-row">';
                            echo '<span class="detail-label">Category:</span>';
                            echo '<span class="detail-value">' . htmlspecialchars($payment['class_type']) . '</span>';
                            echo '</div>';
                            echo '<div class="payment-detail-row">';
                            echo '<span class="detail-label">Payment Method:</span>';
                            echo '<span class="detail-value">' . htmlspecialchars($payment['payment_method']) . '</span>';
                            echo '</div>';
                            echo '<div class="payment-detail-row">';
                            echo '<span class="detail-label">Amount:</span>';
                            echo '<span class="detail-value amount">' . number_format($payment['amount'], 2) . ' TL</span>';
                            echo '</div>';
                            echo '<div class="payment-detail-row">';
                            echo '<span class="detail-label">Transaction ID:</span>';
                            echo '<span class="detail-value transaction-id">' . htmlspecialchars($payment['transaction_id']) . '</span>';
                            echo '</div>';
                            echo '<div class="payment-detail-row">';
                            echo '<span class="detail-label">Date:</span>';
                            echo '<span class="detail-value">' . $payment_date->format("d.m.Y H:i") . '</span>';
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="empty-state">📭 No payment records found yet</div>';
                    }
                    ?>
                </div>
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
                            echo '<div class="meta-item"> ' . htmlspecialchars($row['trainer_name']) . '</div>';
                            echo '</div>';
                            echo '<div class="lesson-actions">';
                            echo '<a href="' . htmlspecialchars($row['video_link']) . '" target="_blank" class="btn-action-small btn-watch">🎥 Go to Stream</a>';
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

            <!-- GEÇMİŞ DERSLER -->
            <div class="profile-card past-section">
                <div class="card-header">
                    <h2>✅ Completed Classes</h2>
                    <p>Rate the workouts you completed</p>
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
                            
                            // Puanlama kontrolü
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
                                echo '<div class="no-review-badge">💬 No review yet</div>';
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

        <!-- SAĞ KOLON: GELİŞİM GEÇMİŞİ -->
        <div class="profile-right">
            
            <div class="profile-card">
                <div class="card-header">
                    <h2>📊 Progress History</h2>
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
                            echo '<span class="stat bmi">📈 BMI: ' . number_format($p['bmi'], 1, ',', '.') . '</span>';
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="empty-state">📭 No progress records. Add your first record!</div>';
                    }
                    ?>
                </div>
            </div>

        </div>

    </div>

</div>

<script>
// Ödeme tipine göre kart alanlarını göster/gizle
document.getElementById('payment_type').addEventListener('change', function() {
    const paymentType = this.value;
    const cardFields = document.getElementById('card-fields');
    const cardDetails = document.getElementById('card-details');
    
    if(paymentType === 'Credit Card' || paymentType === 'Debit Card') {
        cardFields.style.display = 'block';
        cardDetails.style.display = 'grid';
        document.getElementById('card_number').required = true;
        document.getElementById('cardholder_name').required = true;
        document.getElementById('expiry_date').required = true;
    } else {
        cardFields.style.display = 'none';
        cardDetails.style.display = 'none';
        document.getElementById('card_number').required = false;
        document.getElementById('cardholder_name').required = false;
        document.getElementById('expiry_date').required = false;
    }
});
</script>

<?php include 'footer.php'; ?>