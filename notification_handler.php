<?php
/**
 * Bildirim Sistemi Handler
 * Tüm bildirim işlemlerini burada yönetiyoruz
 */

if(!defined('NOTIFICATION_HANDLER_LOADED')) {
    define('NOTIFICATION_HANDLER_LOADED', true);

include 'db.php';

class NotificationHandler {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Yeni bildirim oluştur
     */
    public function createNotification($user_id, $type, $title, $message, $class_id = null) {
        $user_id = (int)$user_id;
        $class_id = $class_id ? (int)$class_id : 'NULL';
        $type = mysqli_real_escape_string($this->conn, $type);
        $title = mysqli_real_escape_string($this->conn, $title);
        $message = mysqli_real_escape_string($this->conn, $message);
        
        $sql = "INSERT INTO notifications (user_id, class_id, type, title, message) 
                VALUES ($user_id, $class_id, '$type', '$title', '$message')";
        
        return mysqli_query($this->conn, $sql);
    }
    
    /**
     * Yeni ders eklendi - tüm kullanıcılara bildir
     */
    public function notifyNewClass($class_id, $class_title, $class_type, $trainer_name, $date_time) {
        $title = "🎉 Yeni Ders: $class_title";
        $message = "$class_type dersi - $trainer_name eğitmeninin rehberliğinde " . date("d.m.Y H:i", strtotime($date_time)) . " tarihinde";
        
        // Tüm aktif kullanıcılara gönder
        $sql = "SELECT id FROM users WHERE role = 'user'";
        $result = mysqli_query($this->conn, $sql);
        
        while($user = mysqli_fetch_assoc($result)) {
            $this->createNotification($user['id'], 'new_class', $title, $message, $class_id);
        }
    }
    
    /**
     * Ders iptal edildi - rezerve etmiş kullanıcılara bildir
     */
    public function notifyCancelledClass($class_id, $class_title, $reason = '') {
        $title = "❌ Ders İptal Edildi: $class_title";
        $message = "Maalesef bu ders iptal edilmiştir." . ($reason ? " Neden: $reason" : "");
        
        // Bu dersi rezerve etmiş kullanıcıları bul
        $sql = "SELECT DISTINCT user_id FROM bookings WHERE class_id = $class_id";
        $result = mysqli_query($this->conn, $sql);
        
        while($booking = mysqli_fetch_assoc($result)) {
            $this->createNotification($booking['user_id'], 'class_cancelled', $title, $message, $class_id);
        }
    }
    
    /**
     * Ders hatırlatması gönder (1 saat, 30 dakika, 10 dakika öncesi)
     */
    public function sendClassReminders() {
        $now = time();
        
        // 1 saat öncesi (3600 saniye)
        $time_1h = date('Y-m-d H:i:s', $now + 3600);
        $time_1h_range_start = date('Y-m-d H:i:s', $now + 3540); // 59 dakika
        $time_1h_range_end = date('Y-m-d H:i:s', $now + 3660);   // 61 dakika
        
        // 30 dakika öncesi (1800 saniye)
        $time_30m = date('Y-m-d H:i:s', $now + 1800);
        $time_30m_range_start = date('Y-m-d H:i:s', $now + 1740); // 29 dakika
        $time_30m_range_end = date('Y-m-d H:i:s', $now + 1860);   // 31 dakika
        
        // 10 dakika öncesi (600 saniye)
        $time_10m = date('Y-m-d H:i:s', $now + 600);
        $time_10m_range_start = date('Y-m-d H:i:s', $now + 540);  // 9 dakika
        $time_10m_range_end = date('Y-m-d H:i:s', $now + 660);    // 11 dakika
        
        // 1 SAAT ÖNCESİ
        $sql_1h = "SELECT DISTINCT b.user_id, c.title, c.date_time, c.id as class_id
                   FROM bookings b
                   JOIN classes c ON b.class_id = c.id
                   WHERE c.date_time BETWEEN '$time_1h_range_start' AND '$time_1h_range_end'
                   AND NOT EXISTS (
                       SELECT 1 FROM notifications n 
                       WHERE n.user_id = b.user_id AND n.class_id = c.id AND n.type = 'class_reminder_1h'
                   )";
        
        $result_1h = mysqli_query($this->conn, $sql_1h);
        while($row = mysqli_fetch_assoc($result_1h)) {
            $title = "⏰ 1 saat sonra: " . $row['title'];
            $message = "Dersim " . date("H:i", strtotime($row['date_time'])) . " da başlıyor!";
            $this->createNotification($row['user_id'], 'class_reminder_1h', $title, $message, $row['class_id']);
        }
        
        // 30 DAKİKA ÖNCESİ
        $sql_30m = "SELECT DISTINCT b.user_id, c.title, c.date_time, c.id as class_id
                    FROM bookings b
                    JOIN classes c ON b.class_id = c.id
                    WHERE c.date_time BETWEEN '$time_30m_range_start' AND '$time_30m_range_end'
                    AND NOT EXISTS (
                        SELECT 1 FROM notifications n 
                        WHERE n.user_id = b.user_id AND n.class_id = c.id AND n.type = 'class_reminder_30m'
                    )";
        
        $result_30m = mysqli_query($this->conn, $sql_30m);
        while($row = mysqli_fetch_assoc($result_30m)) {
            $title = "⏰ 30 dakika sonra: " . $row['title'];
            $message = "Hazırlanma zamanı! Dersin bağlantısını kontrol et.";
            $this->createNotification($row['user_id'], 'class_reminder_30m', $title, $message, $row['class_id']);
        }
        
        // 10 DAKİKA ÖNCESİ
        $sql_10m = "SELECT DISTINCT b.user_id, c.title, c.date_time, c.id as class_id
                    FROM bookings b
                    JOIN classes c ON b.class_id = c.id
                    WHERE c.date_time BETWEEN '$time_10m_range_start' AND '$time_10m_range_end'
                    AND NOT EXISTS (
                        SELECT 1 FROM notifications n 
                        WHERE n.user_id = b.user_id AND n.class_id = c.id AND n.type = 'class_reminder_10m'
                    )";
        
        $result_10m = mysqli_query($this->conn, $sql_10m);
        while($row = mysqli_fetch_assoc($result_10m)) {
            $title = "⏰ 10 dakika sonra: " . $row['title'];
            $message = "Dersin başlamasına çok az kaldı! Yayın linkinizi açın.";
            $this->createNotification($row['user_id'], 'class_reminder_10m', $title, $message, $row['class_id']);
        }
    }
    
    /**
     * Kullanıcının okunmamış bildirimlerini getir
     */
    public function getUnreadNotifications($user_id) {
        $user_id = (int)$user_id;
        $sql = "SELECT * FROM notifications 
                WHERE user_id = $user_id AND is_read = FALSE 
                ORDER BY created_at DESC 
                LIMIT 20";
        
        return mysqli_query($this->conn, $sql);
    }
    
    /**
     * Tüm bildirimleri getir (sayfalama ile)
     */
    public function getAllNotifications($user_id, $limit = 20, $offset = 0) {
        $user_id = (int)$user_id;
        $limit = (int)$limit;
        $offset = (int)$offset;
        
        $sql = "SELECT * FROM notifications 
                WHERE user_id = $user_id 
                ORDER BY created_at DESC 
                LIMIT $limit OFFSET $offset";
        
        return mysqli_query($this->conn, $sql);
    }
    
    /**
     * Bildirimi oku olarak işaretle
     */
    public function markAsRead($notification_id) {
        $notification_id = (int)$notification_id;
        $sql = "UPDATE notifications SET is_read = TRUE WHERE id = $notification_id";
        return mysqli_query($this->conn, $sql);
    }
    
    /**
     * Tüm bildirimleri oku olarak işaretle
     */
    public function markAllAsRead($user_id) {
        $user_id = (int)$user_id;
        $sql = "UPDATE notifications SET is_read = TRUE WHERE user_id = $user_id";
        return mysqli_query($this->conn, $sql);
    }
    
    /**
     * Okunmamış bildirim sayısı
     */
    public function getUnreadCount($user_id) {
        $user_id = (int)$user_id;
        $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = $user_id AND is_read = FALSE";
        $result = mysqli_query($this->conn, $sql);
        
        if(!$result) {
            return 0; // Hata durumunda 0 döndür
        }
        
        $row = mysqli_fetch_assoc($result);
        return $row ? $row['count'] : 0;
    }
    
    /**
     * Bildirimi sil
     */
    public function deleteNotification($notification_id) {
        $notification_id = (int)$notification_id;
        $sql = "DELETE FROM notifications WHERE id = $notification_id";
        return mysqli_query($this->conn, $sql);
    }
}

// Global bildirim yöneticisini oluştur
$notificationHandler = new NotificationHandler($conn);

} // NOTIFICATION_HANDLER_LOADED if sonu
?>
