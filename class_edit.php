<?php
session_start();
include 'db.php';
include 'notification_handler.php';
$page_title = "Edit Class | GYM";

// GÜVENLİK: Admin veya Instructor giriş yapmalı
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'instructor')) {
    die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'><h1>⛔ Unauthorized Access!</h1><p>Only administrators and instructors can view this page.</p><a href='index.php'>Return to Home</a></div>");
}

$message = "";
$message_type = "";
$class_data = null;

// Ders ID'sini al
if (isset($_GET['id'])) {
    $class_id = (int)$_GET['id'];
    
    // Dersin bilgisini getir
    $sql = "SELECT * FROM classes WHERE id = $class_id";
    $result = mysqli_query($conn, $sql);
    $class_data = mysqli_fetch_assoc($result);
    
    // Eğitmen ise kendi derslerini mi editlemek istediğini kontrol et
    if ($_SESSION['role'] == 'instructor' && $class_data['trainer_name'] != $_SESSION['username']) {
        die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'><h1> Not Allowed!</h1><p>You can only edit your own classes.</p><a href='admin.php'>Back to Admin Panel</a></div>");
    }
    
    // Ders bulunamazsa
    if (!$class_data) {
        header("Location: admin.php");
        exit();
    }
} else {
    header("Location: admin.php");
    exit();
}

// GÜNCELLEME İŞLEMİ
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $type = mysqli_real_escape_string($conn, $_POST['class_type']);
    $date = mysqli_real_escape_string($conn, $_POST['date_time']);
    $capacity = (int)$_POST['capacity'];
    $link = mysqli_real_escape_string($conn, $_POST['video_link']);
    
    // Admin ise trainer adı değiştirilebilir
    if ($_SESSION['role'] == 'admin') {
        $trainer = mysqli_real_escape_string($conn, $_POST['trainer']);
    } else {
        $trainer = $_SESSION['username'];
    }
    
    // Eski zamanı sakla (bildirim göndermek için)
    $old_time = $class_data['date_time'];
    
    // Güncelle
    $update_sql = "UPDATE classes SET 
                    title = '$title',
                    trainer_name = '$trainer',
                    description = '$description',
                    class_type = '$type',
                    date_time = '$date',
                    capacity = $capacity,
                    video_link = '$link'
                   WHERE id = $class_id";
    
    if (mysqli_query($conn, $update_sql)) {
        // Eğer saat değiştiyse bildirim gönder
        if ($old_time != $date) {
            $notificationHandler->notifyClassTimeUpdate($class_id, $title, $old_time, $date);
        }
        
        $message = "✅ Class updated successfully!";
        $message_type = "success";
        
        // Veriyi yenile
        $class_data['title'] = $title;
        $class_data['trainer_name'] = $trainer;
        $class_data['description'] = $description;
        $class_data['class_type'] = $type;
        $class_data['date_time'] = $date;
        $class_data['capacity'] = $capacity;
        $class_data['video_link'] = $link;
    } else {
        $message = "❌ Error: " . mysqli_error($conn);
        $message_type = "error";
    }
}

include 'header.php';
?>

<div class="admin-page">
    
    <!-- HERO BÖLÜMÜ -->
    <div class="admin-hero-simple">
        <h1> Edit Class</h1>
    </div>

    <div class="admin-container">

        <!-- MESAJ GÖRÜNTÜLEME -->
        <?php if($message): ?>
            <div class="message-box message-<?php echo $message_type; ?>">
                <div class="message-content">
                    <?php echo $message; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- GERI BUTONU -->
        <div style="margin-bottom: 20px;">
            <a href="admin.php" class="btn-back">← Back to Admin Panel</a>
        </div>

        <!-- GÜNCELLEME FORMU -->
        <div class="form-section">
            <div class="section-header">
                <h2> Edit Class Details</h2>
                <p><?php echo htmlspecialchars($class_data['title']); ?> - Make your changes and save</p>
            </div>

            <form action="" method="POST" class="modern-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="title">Class Title</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($class_data['title']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="trainer">Instructor Name</label>
                        <?php if($_SESSION['role'] == 'instructor'): ?>
                            <input type="text" id="trainer" value="<?php echo $_SESSION['username']; ?>" readonly class="input-readonly">
                        <?php else: ?>
                            <select id="trainer" name="trainer" required>
                                <option value="">-- Select Instructor --</option>
                                <?php
                                $trainers_result = mysqli_query($conn, "SELECT username FROM users WHERE role = 'instructor' ORDER BY username ASC");
                                while($trainer_row = mysqli_fetch_assoc($trainers_result)) {
                                    $selected = ($trainer_row['username'] == $class_data['trainer_name']) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($trainer_row['username']) . "' $selected>" . htmlspecialchars($trainer_row['username']) . "</option>";
                                }
                                ?>
                            </select>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="class_type">Category</label>
                        <select id="class_type" name="class_type" required>
                            <option value="Yoga" <?php echo ($class_data['class_type'] == 'Yoga') ? 'selected' : ''; ?>>🧘‍♀️ Yoga</option>
                            <option value="Pilates" <?php echo ($class_data['class_type'] == 'Pilates') ? 'selected' : ''; ?>>🤸‍♀️ Pilates</option>
                            <option value="HIIT" <?php echo ($class_data['class_type'] == 'HIIT') ? 'selected' : ''; ?>>🔥 HIIT</option>
                            <option value="Zumba" <?php echo ($class_data['class_type'] == 'Zumba') ? 'selected' : ''; ?>>💃 Zumba</option>
                            <option value="Fitness" <?php echo ($class_data['class_type'] == 'Fitness') ? 'selected' : ''; ?>>💪 Fitness</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="capacity">Capacity (People)</label>
                        <input type="number" id="capacity" name="capacity" value="<?php echo $class_data['capacity']; ?>" min="1" max="50" required>
                    </div>

                    <div class="form-group">
                        <label for="date_time">Date and Time</label>
                        <input type="datetime-local" id="date_time" name="date_time" value="<?php echo date('Y-m-d\TH:i', strtotime($class_data['date_time'])); ?>" required>
                        <small> If you change this, users with a booking will be notified.</small>
                    </div>

                    <div class="form-group">
                        <label for="video_link">Video Link</label>
                        <input type="url" id="video_link" name="video_link" value="<?php echo htmlspecialchars($class_data['video_link']); ?>" required>
                    </div>

                    <div class="form-group full-width">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($class_data['description']); ?></textarea>
                    </div>

                    <div class="form-group full-width">
                        <div class="form-actions">
                            <button type="submit" class="btn-submit-large"> Save Changes</button>
                            <a href="admin.php" class="btn-cancel-large">Cancel</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- DERS BİLGİ ÖZETI -->
        <div class="info-section">
            <div class="section-header">
                <h2> Current Information</h2>
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Title:</span>
                    <span class="info-value"><?php echo htmlspecialchars($class_data['title']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Instructor:</span>
                    <span class="info-value"><?php echo htmlspecialchars($class_data['trainer_name']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Category:</span>
                    <span class="info-value"><?php echo $class_data['class_type']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Capacity:</span>
                    <span class="info-value"><?php echo $class_data['capacity']; ?> people</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Date & Time:</span>
                    <span class="info-value"><?php echo date("d.m.Y H:i", strtotime($class_data['date_time'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Video Link:</span>
                    <span class="info-value"><a href="<?php echo htmlspecialchars($class_data['video_link']); ?>" target="_blank" class="link-external">Open Link ↗️</a></span>
                </div>
            </div>
        </div>

    </div>

</div>

<?php include 'footer.php'; ?>
