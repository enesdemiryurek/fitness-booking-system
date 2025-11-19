<?php
session_start();
include 'db.php';

// Filtreleme Mantığı
$filter_type = "";
if (isset($_GET['category'])) {
    $filter_type = $_GET['category'];
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fitness Rezervasyon Sistemi</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Filtre Butonları İçin Stil */
        .filter-container { text-align: center; margin: 20px 0; }
        .filter-btn {
            display: inline-block;
            padding: 8px 15px;
            margin: 5px;
            border: 1px solid #2a5298;
            border-radius: 20px;
            text-decoration: none;
            color: #2a5298;
            font-weight: 600;
            transition: all 0.3s;
        }
        .filter-btn:hover, .filter-btn.active {
            background-color: #2a5298;
            color: white;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>🏋️‍♂️ Online Fitness Dersleri</h1>
        
        <?php if(isset($_SESSION['user_id'])): ?>
            <div class="user-info">Hoşgeldin, <?php echo $_SESSION['username']; ?>!</div>
            <?php if($_SESSION['role'] == 'admin'): ?>
                <a href="admin.php" style="color: #ff9f43; font-weight:bold; border: 1px solid #ff9f43; padding: 5px 10px; border-radius: 5px; text-decoration:none;"> Ders Ekle </a> | 
            <?php endif; ?>
            <a href="profile.php" style="color: white;">Profilim</a> | 
            <a href="logout.php" style="color: #ff6b6b;">Çıkış Yap</a>
        <?php else: ?>
            <p>Ders almak için lütfen giriş yapınız.</p>
            <a href="login.php" style="color: yellow;">Giriş Yap</a> | 
            <a href="register.php" style="color: yellow;">Kayıt Ol</a>
        <?php endif; ?>
    </div>

    <div class="filter-container">
        <a href="index.php" class="filter-btn <?php if($filter_type == '') echo 'active'; ?>">Tümü</a>
        <a href="index.php?category=Yoga" class="filter-btn <?php if($filter_type == 'Yoga') echo 'active'; ?>">🧘‍♀️ Yoga</a>
        <a href="index.php?category=Pilates" class="filter-btn <?php if($filter_type == 'Pilates') echo 'active'; ?>">🤸‍♀️ Pilates</a>
        <a href="index.php?category=HIIT" class="filter-btn <?php if($filter_type == 'HIIT') echo 'active'; ?>">🔥 HIIT</a>
        <a href="index.php?category=Zumba" class="filter-btn <?php if($filter_type == 'Zumba') echo 'active'; ?>">💃 Zumba</a>
    </div>

    <h2>📅 <?php echo $filter_type ? "$filter_type Dersleri" : "Tüm Yaklaşan Dersler"; ?></h2>

    <div class="class-list"> 
    
    <?php
    // SQL Sorgusunu Filtreye Göre Değiştir
    if ($filter_type != "") {
        $sql = "SELECT * FROM classes WHERE class_type = '$filter_type' ORDER BY date_time ASC";
    } else {
        $sql = "SELECT * FROM classes ORDER BY date_time ASC";
    }
    
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            echo '<div class="class-card">';
            echo '<h3>' . $row["title"] . ' <small>(' . $row["class_type"] . ')</small></h3>';
            echo '<p><strong>Eğitmen:</strong> ' . $row["trainer_name"] . '</p>';
            echo '<p><strong>Tarih:</strong> ' . $row["date_time"] . '</p>';
            echo '<p>' . $row["description"] . '</p>';
            
            // Stok durumuna göre renk
            $stok_class = ($row["capacity"] < 3) ? "color:red;" : "color:green;";
            echo '<p class="stok" style="'.$stok_class.'">Kalan Kontenjan: ' . $row["capacity"] . ' Kişi</p>';
            
            if(isset($_SESSION['user_id'])) {
                if ($row["capacity"] > 0) {
                    echo '<a href="book_class.php?id='.$row['id'].'" class="btn">Rezerve Et</a>';
                } else {
                    echo '<button class="btn btn-disabled" disabled>KONTENJAN DOLDU</button>';
                }
            } else {
                echo '<a href="login.php" class="btn btn-disabled">Rezerve İçin Giriş Yap</a>';
            }
            
            echo '</div>';
        }
    } else {
        echo "<p style='text-align:center; width:100%;'>😔 Aradığınız kategoride şu an aktif ders bulunmuyor.</p>";
    }
    ?>
    
    </div>

</div>

</body>
</html>