<?php
session_start();
include 'db.php';

// 1. GÜVENLİK DUVARI: Sadece 'admin' olan girebilir!
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("<center><h1>⛔ Yetkisiz Giriş!</h1><p>Bu sayfaya sadece yöneticiler girebilir.</p><a href='index.php'>Anasayfaya Dön</a></center>");
}

$message = "";

// --- YENİ DERS EKLEME İŞLEMİ ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $trainer = $_POST['trainer'];
    $description = $_POST['description'];
    $type = $_POST['class_type'];
    $date = $_POST['date_time'];
    $capacity = $_POST['capacity'];
    $link = $_POST['video_link'];

    $sql = "INSERT INTO classes (title, trainer_name, description, class_type, date_time, capacity, video_link) 
            VALUES ('$title', '$trainer', '$description', '$type', '$date', '$capacity', '$link')";

    if (mysqli_query($conn, $sql)) {
        $message = "✅ Yeni ders başarıyla eklendi!";
    } else {
        $message = "❌ Hata: " . mysqli_error($conn);
    }
}

// --- DERS SİLME İŞLEMİ (URL'den id gelirse) ---
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    mysqli_query($conn, "DELETE FROM classes WHERE id=$id");
    header("Location: admin.php"); // Sayfayı yenile
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yönetici Paneli</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Admin paneli için ufak ek stiller */
        .admin-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
        .admin-table th, .admin-table td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        .admin-table th { background-color: #333; color: white; }
        .btn-danger { background-color: #dc3545; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
        <h1>🔧 Yönetici Paneli</h1>
        <p>Hoşgeldin Admin, <strong><?php echo $_SESSION['username']; ?></strong></p>
        <a href="index.php" style="color: white;">Siteye Dön</a> | 
        <a href="logout.php" style="color: #fff;">Güvenli Çıkış</a>
    </div>

    <div class="class-card">
        <h2>➕ Yeni Ders Ekle</h2>
        <?php if($message) echo "<p style='font-weight:bold; color:green;'>$message</p>"; ?>
        
        <form action="" method="POST">
            <label>Ders Adı:</label>
            <input type="text" name="title" placeholder="Örn: Akşam Pilatesi" required>

            <label>Eğitmen:</label>
            <input type="text" name="trainer" placeholder="Örn: Mehmet Hoca" required>

            <label>Açıklama:</label>
            <input type="text" name="description" placeholder="Ders hakkında kısa bilgi..." required>

            <div style="display:flex; gap:10px;">
                <div style="flex:1;">
                    <label>Kategori:</label>
                    <select name="class_type" style="width:100%; padding:10px; border:2px solid #eee; border-radius:8px;">
                        <option value="Yoga">Yoga</option>
                        <option value="Pilates">Pilates</option>
                        <option value="HIIT">HIIT / Kardiyo</option>
                        <option value="Zumba">Zumba</option>
                        <option value="Fitness">Fitness</option>
                    </select>
                </div>
                <div style="flex:1;">
                    <label>Kontenjan (Stok):</label>
                    <input type="number" name="capacity" value="10" required>
                </div>
            </div>

            <label>Tarih ve Saat:</label>
            <input type="datetime-local" name="date_time" required>

            <label>Zoom/Video Linki:</label>
            <input type="text" name="video_link" placeholder="https://zoom.us/..." required>

            <button type="submit" class="btn">Dersi Yayınla</button>
        </form>
    </div>

    <h2 style="margin-top:30px;">📋 Aktif Ders Listesi</h2>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Ders Adı</th>
                <th>Eğitmen</th>
                <th>Tarih</th>
                <th>Kalan Stok</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $result = mysqli_query($conn, "SELECT * FROM classes ORDER BY date_time DESC");
            while($row = mysqli_fetch_assoc($result)) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . $row['title'] . " (" . $row['class_type'] . ")</td>";
                echo "<td>" . $row['trainer_name'] . "</td>";
                echo "<td>" . $row['date_time'] . "</td>";
                echo "<td><strong>" . $row['capacity'] . "</strong></td>";
                echo "<td><a href='admin.php?delete_id=" . $row['id'] . "' class='btn-danger' onclick='return confirm(\"Bu dersi silmek istediğine emin misin?\")'>Sil</a></td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>

</div>

</body>
</html>