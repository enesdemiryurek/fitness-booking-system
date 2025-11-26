<?php
session_start();
include 'db.php';
include 'notification_handler.php';
$page_title = "Ders Düzenle | GYM";

// GÜVENLİK: Admin veya Instructor giriş yapmalı
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'instructor')) {
    die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'><h1>⛔ Yetkisiz Giriş!</h1><p>Bu sayfaya sadece yöneticiler ve eğitmenler girebilir.</p><a href='index.php'>Anasayfaya Dön</a></div>");
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
        die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'><h1>⛔ Yetki Yok!</h1><p>Sadece kendi derslerinizi düzenleyebilirsiniz.</p><a href='admin.php'>Yönetim Paneline Dön</a></div>");
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
        
        $message = "✅ Ders Başarıyla Güncellendi!";
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
        $message = "❌ Hata: " . mysqli_error($conn);
        $message_type = "error";
    }
}

include 'header.php';
?>

<div class="admin-page">
    
    <!-- HERO BÖLÜMÜ -->
    <div class="admin-hero-simple">
        <h1>✏️ Ders Düzenle</h1>
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
            <a href="admin.php" class="btn-back">← Yönetim Paneline Dön</a>
        </div>

        <!-- GÜNCELLEME FORMU -->
        <div class="form-section">
            <div class="section-header">
                <h2>🔧 Ders Bilgilerini Düzenle</h2>
                <p><?php echo htmlspecialchars($class_data['title']); ?> - Değişiklikleri yapın ve kaydedin</p>
            </div>

            <form action="" method="POST" class="modern-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="title">Ders Başlığı</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($class_data['title']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="trainer">Eğitmen Adı</label>
                        <?php if($_SESSION['role'] == 'instructor'): ?>
                            <input type="text" id="trainer" value="<?php echo $_SESSION['username']; ?>" readonly class="input-readonly">
                        <?php else: ?>
                            <select id="trainer" name="trainer" required>
                                <option value="">-- Eğitmen Seçiniz --</option>
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
                        <label for="class_type">Kategori</label>
                        <select id="class_type" name="class_type" required>
                            <option value="Yoga" <?php echo ($class_data['class_type'] == 'Yoga') ? 'selected' : ''; ?>>🧘‍♀️ Yoga</option>
                            <option value="Pilates" <?php echo ($class_data['class_type'] == 'Pilates') ? 'selected' : ''; ?>>🤸‍♀️ Pilates</option>
                            <option value="HIIT" <?php echo ($class_data['class_type'] == 'HIIT') ? 'selected' : ''; ?>>🔥 HIIT</option>
                            <option value="Zumba" <?php echo ($class_data['class_type'] == 'Zumba') ? 'selected' : ''; ?>>💃 Zumba</option>
                            <option value="Fitness" <?php echo ($class_data['class_type'] == 'Fitness') ? 'selected' : ''; ?>>💪 Fitness</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="capacity">Kontenjan (Kişi)</label>
                        <input type="number" id="capacity" name="capacity" value="<?php echo $class_data['capacity']; ?>" min="1" max="50" required>
                    </div>

                    <div class="form-group">
                        <label for="date_time">Tarih ve Saat</label>
                        <input type="datetime-local" id="date_time" name="date_time" value="<?php echo date('Y-m-d\TH:i', strtotime($class_data['date_time'])); ?>" required>
                        <small>⚠️ Bu alanı değiştirirseniz, rezerve yapan kullanıcılara bildirim gönderilecektir</small>
                    </div>

                    <div class="form-group">
                        <label for="video_link">Video Linki</label>
                        <input type="url" id="video_link" name="video_link" value="<?php echo htmlspecialchars($class_data['video_link']); ?>" required>
                    </div>

                    <div class="form-group full-width">
                        <label for="description">Açıklama</label>
                        <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($class_data['description']); ?></textarea>
                    </div>

                    <div class="form-group full-width">
                        <div class="form-actions">
                            <button type="submit" class="btn-submit-large">💾 Değişiklikleri Kaydet</button>
                            <a href="admin.php" class="btn-cancel-large">İptal</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- DERS BİLGİ ÖZETI -->
        <div class="info-section">
            <div class="section-header">
                <h2>📋 Mevcut Bilgiler</h2>
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Başlık:</span>
                    <span class="info-value"><?php echo htmlspecialchars($class_data['title']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Eğitmen:</span>
                    <span class="info-value"><?php echo htmlspecialchars($class_data['trainer_name']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Kategori:</span>
                    <span class="info-value"><?php echo $class_data['class_type']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Kontenjan:</span>
                    <span class="info-value"><?php echo $class_data['capacity']; ?> kişi</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tarih & Saat:</span>
                    <span class="info-value"><?php echo date("d.m.Y H:i", strtotime($class_data['date_time'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Video Linki:</span>
                    <span class="info-value"><a href="<?php echo htmlspecialchars($class_data['video_link']); ?>" target="_blank" class="link-external">Linki Aç ↗️</a></span>
                </div>
            </div>
        </div>

    </div>

</div>

<?php include 'footer.php'; ?>
