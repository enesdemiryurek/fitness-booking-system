<?php
session_start();
include 'db.php';
include 'notification_handler.php';
$page_title = "Yönetim Paneli | GYM";

// 1. GÜVENLİK DUVARI: Admin VEYA Instructor girebilir
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'instructor')) {
    die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'><h1>⛔ Yetkisiz Giriş!</h1><p>Bu sayfaya sadece yöneticiler ve eğitmenler girebilir.</p><a href='index.php'>Anasayfaya Dön</a></div>");
}

$message = "";
$message_type = ""; // success veya error

// --- YENİ DERS EKLEME ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    
    // --- EĞİTMEN ADI BELİRLEME MANTIĞI ---
    // Eğer giriş yapan kişi EĞİTMENSE: Formdan gelen veriyi yok say, Session'daki adını al.
    // Eğer giriş yapan kişi ADMİNSE: Formdan gelen veriyi al.
    if ($_SESSION['role'] == 'instructor') {
        $trainer = $_SESSION['username'];
    } else {
        $trainer = $_POST['trainer'];
    }
    // -------------------------------------

    $description = $_POST['description'];
    $type = $_POST['class_type'];
    $date = $_POST['date_time'];
    $capacity = $_POST['capacity'];
    $link = $_POST['video_link'];

    $sql = "INSERT INTO classes (title, trainer_name, description, class_type, date_time, capacity, video_link) 
            VALUES ('$title', '$trainer', '$description', '$type', '$date', '$capacity', '$link')";

    if (mysqli_query($conn, $sql)) {
        $class_id = mysqli_insert_id($conn);
        
        // BİLDİRİM GÖNDER: Yeni ders eklendi
        $notificationHandler->notifyNewClass($class_id, $title, $type, $trainer, $date);
        
        $message = "✅ Ders Başarıyla Eklendi!";
        $message_type = "success";
    } else {
        $message = "❌ Hata: " . mysqli_error($conn);
        $message_type = "error";
    }
}

// --- SİLME İŞLEMİ ---
if (isset($_GET['delete_id'])) {
    // Sadece ADMIN silebilir
    if ($_SESSION['role'] == 'admin') {
        $id = $_GET['delete_id'];
        
        // Silinecek dersin bilgisini al
        $class_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT title FROM classes WHERE id=$id"));
        
        // BİLDİRİM GÖNDER: Ders iptal edildi
        $notificationHandler->notifyCancelledClass($id, $class_info['title'], 'Yönetici tarafından iptal edilmiştir');
        
        mysqli_query($conn, "DELETE FROM classes WHERE id=$id");
        header("Location: admin.php");
    } else {
        $message = "⛔ Hata: Ders silme yetkisi sadece Yöneticiye (Admin) aittir!";
        $message_type = "error";
    }
}

include 'header.php';
?>

<div class="admin-page">
    
    <!-- HERO BÖLÜMÜ -->
    <div class="admin-hero-simple">
        <h1><?php echo ($_SESSION['role'] == 'admin') ? "🔧 Yönetici Paneli" : "👨‍🏫 Eğitmen Paneli"; ?></h1>
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

        <!-- YENİ DERS FORMU -->
        <div class="form-section">
            <div class="section-header">
                <h2>➕ Yeni Ders Oluştur</h2>
                <p>Sisteme yeni bir ders ekleyerek öğrencilerin katılım göstermesini sağlayın</p>
            </div>

            <form action="" method="POST" class="modern-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="title">Ders Başlığı</label>
                        <input type="text" id="title" name="title" placeholder="Örn: Sabah Yogası" required>
                        <small>Örnek: Pilates Temellerine Giriş</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="trainer">Eğitmen Adı</label>
                        <?php if($_SESSION['role'] == 'instructor'): ?>
                            <input type="text" id="trainer" value="<?php echo $_SESSION['username']; ?>" readonly class="input-readonly">
                            <small>Sisteme kayıtlı adınız</small>
                        <?php else: ?>
                            <select id="trainer" name="trainer" required>
                                <option value="">-- Eğitmen Seçiniz --</option>
                                <?php
                                // Veritabanından instructor rolünde olan kişileri çek
                                $trainers_result = mysqli_query($conn, "SELECT username FROM users WHERE role = 'instructor' ORDER BY username ASC");
                                while($trainer_row = mysqli_fetch_assoc($trainers_result)) {
                                    echo "<option value='" . htmlspecialchars($trainer_row['username']) . "'>" . htmlspecialchars($trainer_row['username']) . "</option>";
                                }
                                ?>
                            </select>
                            <small>Dersi yönetecek eğitmenin adını seçiniz</small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="class_type">Kategori</label>
                        <select id="class_type" name="class_type" required>
                            <option value="">-- Seçiniz --</option>
                            <option value="Yoga">🧘‍♀️ Yoga</option>
                            <option value="Pilates">🤸‍♀️ Pilates</option>
                            <option value="HIIT">🔥 HIIT</option>
                            <option value="Zumba">💃 Zumba</option>
                            <option value="Fitness">💪 Fitness</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="capacity">Kontenjan (Kişi)</label>
                        <input type="number" id="capacity" name="capacity" value="10" min="1" max="50" required>
                        <small>Derse kaç kişi katılabilir</small>
                    </div>

                    <div class="form-group">
                        <label for="date_time">Tarih ve Saat</label>
                        <input type="datetime-local" id="date_time" name="date_time" required>
                    </div>

                    <div class="form-group">
                        <label for="video_link">Video Linki</label>
                        <input type="url" id="video_link" name="video_link" placeholder="https://zoom.us/... veya https://youtube.com/..." required>
                        <small>Zoom, Google Meet veya YouTube linki</small>
                    </div>

                    <div class="form-group full-width">
                        <label for="description">Açıklama</label>
                        <textarea id="description" name="description" placeholder="Ders hakkında detaylı bilgi verin..." rows="4" required></textarea>
                        <small>Dersin amacı, içeriği, gereksinimler vs.</small>
                    </div>

                    <div class="form-group full-width">
                        <button type="submit" class="btn-submit-large">📤 Dersi Yayınla</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- DERS LİSTESİ -->
        <div class="table-section">
            <div class="section-header">
                <h2>📋 Aktif Ders Listesi</h2>
                <p>Sistemdeki tüm dersleri yönetin ve düzenleyin</p>
            </div>

            <div class="table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ders Bilgisi</th>
                            <th>Eğitmen</th>
                            <th>Tarih & Saat</th>
                            <th>Kontenjan</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = mysqli_query($conn, "SELECT * FROM classes WHERE date_time >= NOW() ORDER BY date_time ASC");
                        if(mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) {
                                $class_date = new DateTime($row['date_time']);
                                
                                echo "<tr>";
                                echo "<td class='td-id'>#" . str_pad($row['id'], 4, '0', STR_PAD_LEFT) . "</td>";
                                echo "<td class='td-title'>";
                                echo "<strong>" . htmlspecialchars($row['title']) . "</strong>";
                                echo "<br><span class='class-badge'>" . $row['class_type'] . "</span>";
                                echo "</td>";
                                echo "<td>" . htmlspecialchars($row['trainer_name']) . "</td>";
                                echo "<td class='td-date'>" . $class_date->format("d.m.Y H:i") . "</td>";
                                echo "<td><span class='badge-capacity'>" . $row['capacity'] . "</span></td>";
                                
                                echo "<td class='td-actions'>";
                                if ($_SESSION['role'] == 'admin') {
                                    echo "<a href='admin.php?delete_id=" . $row['id'] . "' class='btn-action-small btn-delete' onclick='return confirm(\"Bu dersi silmek istediğine emin misin?\")'>🗑️ Sil</a>";
                                } else {
                                    echo "<span class='btn-action-small btn-locked'>🔒 Kilitli</span>";
                                }
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center; padding:40px; color:#999;'>Yaklaşan ders bulunmuyor</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- GEÇMİŞ DERS LİSTESİ -->
        <div class="table-section past-section">
            <div class="section-header">
                <h2>📚 Geçmiş Dersler</h2>
                <p>Daha önce yapılan ve arşivlenmiş dersler</p>
            </div>

            <div class="table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ders Bilgisi</th>
                            <th>Eğitmen</th>
                            <th>Tarih & Saat</th>
                            <th>Kontenjan</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $past_result = mysqli_query($conn, "SELECT * FROM classes WHERE date_time < NOW() ORDER BY date_time DESC");
                        if(mysqli_num_rows($past_result) > 0) {
                            while($row = mysqli_fetch_assoc($past_result)) {
                                $class_date = new DateTime($row['date_time']);
                                
                                echo "<tr>";
                                echo "<td class='td-id'>#" . str_pad($row['id'], 4, '0', STR_PAD_LEFT) . "</td>";
                                echo "<td class='td-title'>";
                                echo "<strong>" . htmlspecialchars($row['title']) . "</strong>";
                                echo "<br><span class='class-badge'>" . $row['class_type'] . "</span>";
                                echo "</td>";
                                echo "<td>" . htmlspecialchars($row['trainer_name']) . "</td>";
                                echo "<td class='td-date'>" . $class_date->format("d.m.Y H:i") . "</td>";
                                echo "<td><span class='badge-capacity'>" . $row['capacity'] . "</span></td>";
                                
                                echo "<td class='td-actions'>";
                                if ($_SESSION['role'] == 'admin') {
                                    echo "<a href='admin.php?delete_id=" . $row['id'] . "' class='btn-action-small btn-delete' onclick='return confirm(\"Bu dersi silmek istediğine emin misin?\")'>🗑️ Sil</a>";
                                } else {
                                    echo "<span class='btn-action-small btn-locked'>🔒 Kilitli</span>";
                                }
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center; padding:40px; color:#999;'>Geçmiş ders bulunmuyor</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<?php include 'footer.php'; ?>