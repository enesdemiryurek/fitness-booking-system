<?php
// --- SİHİRLİ SATIR: TÜRKİYE SAAT AYARI ---
date_default_timezone_set('Europe/Istanbul');
setlocale(LC_TIME, 'tr_TR.UTF-8', 'tr_TR', 'tr', 'turkish');
// -----------------------------------------

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "fitness_db";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Bağlantı hatası: " . mysqli_connect_error());
}

// Türkçe karakter sorunu olmasın diye
mysqli_set_charset($conn, "utf8");

// Notifications tablosunun olup olmadığını kontrol et ve oluştur
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'notifications'");
if(mysqli_num_rows($check_table) == 0) {
    $create_table = "CREATE TABLE IF NOT EXISTS notifications (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        class_id INT,
        type ENUM('new_class', 'class_reminder_1h', 'class_reminder_30m', 'class_reminder_10m', 'class_cancelled', 'class_time_updated') NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
    )";
    mysqli_query($conn, $create_table);
    
    // İndeks oluştur
    mysqli_query($conn, "CREATE INDEX idx_user_notifications ON notifications(user_id, is_read, created_at)");
    mysqli_query($conn, "CREATE INDEX idx_class_notifications ON notifications(class_id)");
} else {
    // Varsa sütun tipini güncelle
    $check_enum = mysqli_query($conn, "SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='notifications' AND COLUMN_NAME='type'");
    if($check_enum && mysqli_num_rows($check_enum) > 0) {
        $enum_row = mysqli_fetch_assoc($check_enum);
        if(strpos($enum_row['COLUMN_TYPE'], 'class_time_updated') === false) {
            mysqli_query($conn, "ALTER TABLE notifications MODIFY COLUMN type ENUM('new_class', 'class_reminder_1h', 'class_reminder_30m', 'class_reminder_10m', 'class_cancelled', 'class_time_updated') NOT NULL");
        }
    }
}

// Users tablosuna profile_photo sütunu ekle (varsa geç)
$check_photo = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'profile_photo'");
if(mysqli_num_rows($check_photo) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN profile_photo LONGBLOB NULL");
}

// Reviews tablosu oluştur
$check_reviews = mysqli_query($conn, "SHOW TABLES LIKE 'reviews'");
if(mysqli_num_rows($check_reviews) == 0) {
    $create_reviews = "CREATE TABLE IF NOT EXISTS reviews (
        id INT PRIMARY KEY AUTO_INCREMENT,
        class_id INT NOT NULL,
        user_id INT NOT NULL,
        rating INT NOT NULL,
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_class_reviews (class_id),
        INDEX idx_user_reviews (user_id)
    )";
    mysqli_query($conn, $create_reviews);
}
?>