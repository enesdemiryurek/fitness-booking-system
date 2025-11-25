<?php
/**
 * Bildirim Sistemi Başlatıcı
 * Bu dosyayı bir kez çalıştırarak notifications tablosunu oluşturabilirsiniz
 */

session_start();
include 'db.php';

$sql = "CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    class_id INT,
    type ENUM('new_class', 'class_reminder_1h', 'class_reminder_30m', 'class_reminder_10m', 'class_cancelled') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_user_notifications ON notifications(user_id, is_read, created_at);
CREATE INDEX IF NOT EXISTS idx_class_notifications ON notifications(class_id);
";

$result = mysqli_multi_query($conn, $sql);

if($result) {
    echo "✅ Bildirim sistemi başarıyla kuruldu!<br>";
    echo "📋 Notifications tablosu oluşturuldu.<br>";
    echo "<br><a href='index.php'>Anasayfaya Dön</a>";
} else {
    echo "❌ Hata: " . mysqli_error($conn);
}

mysqli_close($conn);
?>
