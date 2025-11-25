<?php
session_start();
include 'db.php';
$page_title = "Profilim | GYM";

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

// --- 1. INSTRUCTOR PROFIL RESMİ YÜKLEME ---
if (isset($_POST['upload_profile_photo']) && $_FILES['profile_photo']['size'] > 0) {
    $file_type = mime_content_type($_FILES['profile_photo']['tmp_name']);
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (!in_array($file_type, $allowed_types)) {
        $message = "❌ Yalnızca resim dosyaları yüklenebilir!";
        $message_type = "error";
    } elseif ($_FILES['profile_photo']['size'] > 5 * 1024 * 1024) { // 5MB limit
        $message = "❌ Dosya boyutu 5MB'dan büyük olamaz!";
        $message_type = "error";
    } else {
        $photo_data = file_get_contents($_FILES['profile_photo']['tmp_name']);
        $photo_data = mysqli_real_escape_string($conn, $photo_data);
        
        $update_photo = "UPDATE users SET profile_photo='$photo_data' WHERE id=$user_id";
        if (mysqli_query($conn, $update_photo)) {
            $message = "✅ Profil resmi başarıyla yüklendi!";
            $message_type = "success";
        } else {
            $message = "❌ Hata: " . mysqli_error($conn);
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
        $message = "✅ Bilgiler başarıyla güncellendi!";
        $message_type = "success";
        $_SESSION['username'] = $new_username;
    } else {
        $message = "❌ Hata: " . mysqli_error($conn);
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
        $progress_message = "✅ Gelişim kaydedildi! BMI: $bmi";
        $progress_type = "success";
    } else {
        $progress_message = "❌ Hata: " . mysqli_error($conn);
        $progress_type = "error";
    }
}

// Kullanıcı Bilgisi
$user_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));

include 'header.php';
?>

<div class="profile-page">
    
    <!-- PROFILE HERO BÖLÜMÜ -->
    <div class="profile-hero-simple">
        <h1>👤 Profilim</h1>
    </div>

    <div class="profile-container">
        
        <!-- SOL KOLON: HESAP BİLGİLERİ & GELİŞİM -->
        <div class="profile-left">
            
            <!-- HESAP BİLGİLERİ -->
            <div class="profile-card">
                <div class="card-header">
                    <h2>👤 Hesap Bilgileri</h2>
                    <p>Kişisel bilgilerinizi güncelleyin</p>
                </div>

                <?php if($message): ?>
                    <div class="message-box message-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="profile-form">
                    <div class="form-group">
                        <label for="username">Ad Soyad</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user_row['username']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">E-posta</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_row['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Telefon</label>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user_row['phone']); ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="age">Yaş</label>
                            <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($user_row['age']); ?>" min="1" max="120">
                        </div>

                        <div class="form-group">
                            <label for="gender">Cinsiyet</label>
                            <select id="gender" name="gender">
                                <option value="">-- Seçiniz --</option>
                                <option value="Erkek" <?php if($user_row['gender']=='Erkek') echo 'selected'; ?>>Erkek</option>
                                <option value="Kadın" <?php if($user_row['gender']=='Kadın') echo 'selected'; ?>>Kadın</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" name="update_profile" class="btn-submit-large">💾 Bilgileri Güncelle</button>
                </form>

                <!-- INSTRUCTOR PROFIL RESMİ UPLOAD -->
                <?php if($user_row['role'] == 'instructor'): ?>
                <div style="margin-top: 30px; padding-top: 30px; border-top: 1px solid #eee;">
                    <h3 style="font-size: 1.1rem; margin-bottom: 15px;">📸 Profil Resmi (Eğitmenler İçin)</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="profile_photo">Profil Fotoğrafı Yükle</label>
                            <input type="file" id="profile_photo" name="profile_photo" accept="image/*" required>
                            <small style="color: #666; display: block; margin-top: 5px;">PNG, JPG, GIF (Max 5MB)</small>
                        </div>
                        <button type="submit" name="upload_profile_photo" class="btn-submit-large" style="background: #28a745;">📤 Resmi Yükle</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>

            <!-- GELİŞİM EKLE -->
            <div class="profile-card">
                <div class="card-header">
                    <h2>📈 Gelişim Kaydı</h2>
                    <p>Ağırlık ve boy bilgisini ekleyerek ilerlemenizi takip edin</p>
                </div>

                <?php if($progress_message): ?>
                    <div class="message-box message-<?php echo $progress_type; ?>">
                        <?php echo $progress_message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="profile-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="weight">Kilo (kg)</label>
                            <input type="number" id="weight" name="weight" step="0.1" min="0" placeholder="Örn: 75.5" required>
                        </div>

                        <div class="form-group">
                            <label for="height">Boy (cm)</label>
                            <input type="number" id="height" name="height" min="0" placeholder="Örn: 180" required>
                        </div>
                    </div>

                    <button type="submit" name="add_progress" class="btn-submit-large btn-success">➕ Kaydı Ekle</button>
                </form>
            </div>

        </div>

        <!-- ORTA KOLON: DERS PROGRAMI -->
        <div class="profile-middle">
            
            <!-- YAKLAŞAN DERSLER -->
            <div class="profile-card">
                <div class="card-header">
                    <h2>📅 Yaklaşan Derslerim</h2>
                    <p>Planlanan antrenmanlarınız</p>
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
                            echo '<div class="meta-item">🧘‍♂️ ' . htmlspecialchars($row['trainer_name']) . '</div>';
                            echo '</div>';
                            echo '<div class="lesson-actions">';
                            echo '<a href="' . htmlspecialchars($row['video_link']) . '" target="_blank" class="btn-action-small btn-watch">🎥 Yayına Git</a>';
                            echo '<a href="cancel_booking.php?id=' . $row['booking_id'] . '" onclick="return confirm(\'Bu dersi iptal etmek istediğine emin misin?\')" class="btn-action-small btn-cancel">❌ İptal</a>';
                            echo '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="empty-state">📭 Yaklaşan ders bulunmuyor</div>';
                    }
                    ?>
                </div>
            </div>

            <!-- GEÇMİŞ DERSLER -->
            <div class="profile-card past-section">
                <div class="card-header">
                    <h2>✅ Tamamlanan Dersler</h2>
                    <p>Bitirdiğiniz antrenmanları puanlayın</p>
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
                                echo '<div class="no-review-badge">💬 Henüz yorum yapılmamış</div>';
                            }
                            
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="empty-state">📭 Tamamlanan ders bulunmuyor</div>';
                    }
                    ?>
                </div>
            </div>

        </div>

        <!-- SAĞ KOLON: GELİŞİM GEÇMİŞİ -->
        <div class="profile-right">
            
            <div class="profile-card">
                <div class="card-header">
                    <h2>📊 Gelişim Geçmişi</h2>
                    <p>Son 10 kaydınız</p>
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
                        echo '<div class="empty-state">📭 Gelişim kaydı bulunmuyor. İlk kaydınızı ekleyin!</div>';
                    }
                    ?>
                </div>
            </div>

        </div>

    </div>

</div>

<?php include 'footer.php'; ?>