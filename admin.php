<?php
session_start();
include 'db.php';

// 1. GÜVENLİK DUVARI: Admin VEYA Instructor girebilir
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'instructor')) {
    die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'><h1>⛔ Yetkisiz Giriş!</h1><p>Bu sayfaya sadece yöneticiler ve eğitmenler girebilir.</p><a href='index.php'>Anasayfaya Dön</a></div>");
}

$message = "";

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
        $message = "✅ Ders Başarıyla Eklendi!";
    } else {
        $message = "❌ Hata: " . mysqli_error($conn);
    }
}

// --- SİLME İŞLEMİ ---
if (isset($_GET['delete_id'])) {
    // Sadece ADMIN silebilir
    if ($_SESSION['role'] == 'admin') {
        $id = $_GET['delete_id'];
        mysqli_query($conn, "DELETE FROM classes WHERE id=$id");
        header("Location: admin.php");
    } else {
        $message = "⛔ Hata: Ders silme yetkisi sadece Yöneticiye (Admin) aittir!";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yönetim Paneli</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Admin paneli stilleri */
        body { background-color: #f4f6f8; }
        .admin-container { max-width: 1100px; margin: 40px auto; padding: 0 20px; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; background: white; padding: 20px 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .admin-title h1 { font-size: 1.5rem; color: #333; margin-bottom: 5px; }
        
        .form-card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); margin-bottom: 40px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full-width { grid-column: span 2; }
        
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #e0e0e0; border-radius: 8px; }
        
        /* Readonly input stili (Eğitmenler için) */
        input[readonly] { background-color: #e9ecef; cursor: not-allowed; color: #555; }

        .btn-submit { width: 100%; padding: 15px; background: linear-gradient(135deg, #2a5298 0%, #1e3c72 100%); color: white; border: none; border-radius: 10px; font-weight: bold; cursor: pointer; }
        
        .table-card { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .admin-table { width: 100%; border-collapse: collapse; }
        .admin-table th { background: #333; color: white; padding: 15px; text-align: left; }
        .admin-table td { padding: 15px; border-bottom: 1px solid #eee; }
        
        .btn-delete { background: #c62828; color: white; text-decoration: none; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; }
        .btn-disabled-delete { background: #eee; color: #999; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; cursor: not-allowed; }
        
        .btn-site { background: #e2e6ea; color: #333; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; }
        .btn-logout { background: #ffebee; color: #c62828; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; margin-left: 10px; }
        .badge-stock { background: #e8f5e9; color: #2e7d32; padding: 5px 10px; border-radius: 20px; font-weight: bold; font-size: 0.85rem; }
    </style>
</head>
<body>

<div class="admin-container">
    
    <div class="admin-header">
        <div class="admin-title">
            <!-- Başlık role göre değişir -->
            <h1>🔧 <?php echo ($_SESSION['role'] == 'admin') ? "Yönetici Paneli" : "Eğitmen Paneli"; ?></h1>
            <p>Hoşgeldin, <strong><?php echo $_SESSION['username']; ?></strong></p>
        </div>
        <div class="admin-actions">
            <a href="index.php" class="btn-site">🏠 Siteye Dön</a>
            <a href="logout.php" class="btn-logout">Güvenli Çıkış</a>
        </div>
    </div>

    <div class="form-card">
        <h2>➕ Yeni Ders Oluştur</h2>
        <?php if($message) echo "<p style='background:#d4edda; color:#155724; padding:10px; border-radius:5px; margin-bottom:15px;'>$message</p>"; ?>

        <form action="" method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Ders Başlığı</label>
                    <input type="text" name="title" placeholder="Örn: Sabah Yogası" required>
                </div>
                
                <div class="form-group">
                    <label>Eğitmen Adı</label>
                    
                    <?php if($_SESSION['role'] == 'instructor'): ?>
                        <!-- EĞİTMEN GİRİŞİ: Kutu kilitli, kendi adı yazar -->
                        <input type="text" value="<?php echo $_SESSION['username']; ?>" readonly>
                        <!-- Not: Readonly inputlar POST edilmez ama biz zaten PHP kısmında Session'dan alıyoruz, o yüzden sorun yok. -->
                    <?php else: ?>
                        <!-- ADMİN GİRİŞİ: İstediğini yazar -->
                        <input type="text" name="trainer" placeholder="Örn: Ayşe Hoca" required>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Kategori</label>
                    <select name="class_type">
                        <option value="Yoga">🧘‍♀️ Yoga</option>
                        <option value="Pilates">🤸‍♀️ Pilates</option>
                        <option value="HIIT">🔥 HIIT</option>
                        <option value="Zumba">💃 Zumba</option>
                        <option value="Fitness">💪 Fitness</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Kontenjan</label>
                    <input type="number" name="capacity" value="10" required>
                </div>
                <div class="form-group">
                    <label>Tarih ve Saat</label>
                    <input type="datetime-local" name="date_time" required>
                </div>
                <div class="form-group">
                    <label>Video Linki</label>
                    <input type="text" name="video_link" placeholder="Zoom/Youtube Linki" required>
                </div>
                <div class="form-group full-width">
                    <label>Açıklama</label>
                    <input type="text" name="description" placeholder="Ders hakkında bilgi..." required>
                </div>
                <div class="form-group full-width">
                    <button type="submit" class="btn-submit">Dersi Yayınla</button>
                </div>
            </div>
        </form>
    </div>

    <h2 style="margin-bottom:20px; color:#333;">📋 Aktif Ders Listesi</h2>
    <div class="table-card">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ders</th>
                    <th>Eğitmen</th>
                    <th>Tarih</th>
                    <th>Stok</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = mysqli_query($conn, "SELECT * FROM classes ORDER BY date_time DESC");
                while($row = mysqli_fetch_assoc($result)) {
                    echo "<tr>";
                    echo "<td>#" . $row['id'] . "</td>";
                    echo "<td><strong>" . $row['title'] . "</strong><br><small>" . $row['class_type'] . "</small></td>";
                    echo "<td>" . $row['trainer_name'] . "</td>";
                    echo "<td>" . date("d.m.Y H:i", strtotime($row['date_time'])) . "</td>";
                    echo "<td><span class='badge-stock'>" . $row['capacity'] . "</span></td>";
                    
                    echo "<td>";
                    if ($_SESSION['role'] == 'admin') {
                        // Admin KIRMIZI SİL butonunu görür
                        echo "<a href='admin.php?delete_id=" . $row['id'] . "' class='btn-delete' onclick='return confirm(\"Silmek istediğine emin misin?\")'>Sil</a>";
                    } else {
                        // Eğitmen KİLİT işaretini görür
                        echo "<span class='btn-disabled-delete'>🔒 Silinemez</span>";
                    }
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>