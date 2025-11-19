<?php
session_start();
include 'db.php';

// 1. GÜVENLİK DUVARI
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'><h1>⛔ Yetkisiz Giriş!</h1><p>Bu sayfaya sadece yöneticiler girebilir.</p><a href='index.php'>Anasayfaya Dön</a></div>");
}

$message = "";

// --- YENİ DERS EKLEME ---
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
        $message = "✅ Ders Başarıyla Eklendi!";
    } else {
        $message = "❌ Hata: " . mysqli_error($conn);
    }
}

// --- SİLME ---
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    mysqli_query($conn, "DELETE FROM classes WHERE id=$id");
    header("Location: admin.php");
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css"> <style>
        body { background-color: #f4f6f8; }
        
        .admin-container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
        }

        /* HEADER KISMI */
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .admin-title h1 { font-size: 1.5rem; color: #333; margin-bottom: 5px; }
        .admin-title p { color: #666; font-size: 0.9rem; }
        
        .admin-actions a {
            text-decoration: none;
            font-weight: 600;
            margin-left: 15px;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: 0.3s;
        }
        .btn-site { background: #e2e6ea; color: #333; }
        .btn-site:hover { background: #dbe2e8; }
        .btn-logout { background: #ffebee; color: #c62828; }
        .btn-logout:hover { background: #ffcdd2; }

        /* FORM KARTI */
        .form-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            margin-bottom: 40px;
        }
        .form-card h2 { margin-bottom: 20px; color: #2a5298; display: flex; align-items: center; gap: 10px; }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr; /* İki sütunlu yapı */
            gap: 20px;
        }
        .full-width { grid-column: span 2; } /* Tam genişlik kaplasın dediklerimiz */

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
            font-size: 0.9rem;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: 0.3s;
            outline: none;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: #2a5298;
            box-shadow: 0 0 0 3px rgba(42, 82, 152, 0.1);
        }

        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #2a5298 0%, #1e3c72 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(30, 60, 114, 0.3); }

        /* TABLO STİLİ */
        .table-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        .admin-table { width: 100%; border-collapse: collapse; }
        .admin-table th { background: #333; color: white; padding: 15px; text-align: left; font-weight: 500; }
        .admin-table td { padding: 15px; border-bottom: 1px solid #eee; color: #555; }
        .admin-table tr:last-child td { border-bottom: none; }
        .admin-table tr:hover { background-color: #f9fafb; }
        
        .badge-stock {
            background: #e8f5e9; color: #2e7d32; padding: 5px 10px; border-radius: 20px; font-weight: bold; font-size: 0.85rem;
        }
        .btn-delete {
            background: #c62828; color: white; text-decoration: none; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; transition: 0.3s;
        }
        .btn-delete:hover { background: #b71c1c; }
        
        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; } /* Mobilde tek sütun olsun */
            .admin-header { flex-direction: column; gap: 15px; text-align: center; }
        }
    </style>
</head>
<body>

<div class="admin-container">
    
    <div class="admin-header">
        <div class="admin-title">
            <h1>🔧 Yönetici Paneli</h1>
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
                    <input type="text" name="trainer" placeholder="Örn: Ayşe Hoca" required>
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
                    <label>Kontenjan (Stok)</label>
                    <input type="number" name="capacity" value="10" required>
                </div>

                <div class="form-group">
                    <label>Tarih ve Saat</label>
                    <input type="datetime-local" name="date_time" required>
                </div>
                
                <div class="form-group">
                    <label>Video / Canlı Yayın Linki</label>
                    <input type="text" name="video_link" placeholder="Zoom veya Youtube Linki" required>
                </div>

                <div class="form-group full-width">
                    <label>Ders Açıklaması</label>
                    <input type="text" name="description" placeholder="Ders hakkında kısa ve ilgi çekici bir açıklama..." required>
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
                    <th>Ders Bilgisi</th>
                    <th>Eğitmen</th>
                    <th>Tarih</th>
                    <th>Stok Durumu</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = mysqli_query($conn, "SELECT * FROM classes ORDER BY date_time DESC");
                while($row = mysqli_fetch_assoc($result)) {
                    echo "<tr>";
                    echo "<td>#" . $row['id'] . "</td>";
                    echo "<td><strong>" . $row['title'] . "</strong><br><small style='color:#888;'>" . $row['class_type'] . "</small></td>";
                    echo "<td>" . $row['trainer_name'] . "</td>";
                    echo "<td>" . date("d.m.Y H:i", strtotime($row['date_time'])) . "</td>";
                    echo "<td><span class='badge-stock'>" . $row['capacity'] . " Kişi</span></td>";
                    echo "<td><a href='admin.php?delete_id=" . $row['id'] . "' class='btn-delete' onclick='return confirm(\"Silmek istediğine emin misin?\")'>Sil</a></td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>