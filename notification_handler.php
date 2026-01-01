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
     * Yeni ders eklendi Bildirimi
     */
    public function notifyNewClass($class_id, $class_title, $class_type, $trainer_name, $date_time) {
        $title = " New Class: $class_title";
        $message = "$class_type class with trainer $trainer_name on " . date("d.m.Y H:i", strtotime($date_time));
        
      
        $sql = "SELECT id FROM users WHERE role = 'user'";
        $result = mysqli_query($this->conn, $sql);
        
        while($user = mysqli_fetch_assoc($result)) {
            $this->createNotification($user['id'], 'new_class', $title, $message, $class_id);
        }
    }
    
    /**
     * Ders iptal edildi 
     */
    public function notifyCancelledClass($class_id, $class_title, $reason = '') {
        $title = "Course canceled: $class_title";
        $message = "Unfortunately, this course has been cancelled." . ($reason ? " Reason: $reason" : "");
        
        // Bu dersi book etmiş kullanıcıları bul
        $sql = "SELECT DISTINCT user_id FROM bookings WHERE class_id = $class_id";
        $result = mysqli_query($this->conn, $sql);
        
        while($booking = mysqli_fetch_assoc($result)) {
            $this->createNotification($booking['user_id'], 'class_cancelled', $title, $message, $class_id);
        }
    }
    
    /**
     * Ders saati güncellendi 
     */
    public function notifyClassTimeUpdate($class_id, $class_title, $old_time, $new_time) {
        $old_datetime = date("d.m.Y H:i", strtotime($old_time));
        $new_datetime = date("d.m.Y H:i", strtotime($new_time));
        
        $title = "Updated Class Time: $class_title";
        $message = "The class time has changed.\n\nPrevious time: $old_datetime\nNew time: $new_datetime";
        
        // Bu dersi book etmiş kullanıcıları bul
        $sql = "SELECT DISTINCT user_id FROM bookings WHERE class_id = $class_id";
        $result = mysqli_query($this->conn, $sql);
        
        while($booking = mysqli_fetch_assoc($result)) {
            $this->createNotification($booking['user_id'], 'class_time_updated', $title, $message, $class_id);
        }
    }
    
    /**
     * Ders hatırlatması gönder (1 saat, 30 dakika, 10 dakika öncesi)
     */
    public function sendClassReminders() {
        $now = time();
        
        // 1 saat öncesi (3600 saniye) 
        $time_1h = date('Y-m-d H:i:s', $now + 3600);
        $time_1h_range_start = date('Y-m-d H:i:s', $now + 3300); 
        $time_1h_range_end = date('Y-m-d H:i:s', $now + 3900);   
        
        // 30 dakika öncesi (1800 saniye) 
        $time_30m = date('Y-m-d H:i:s', $now + 1800);
        $time_30m_range_start = date('Y-m-d H:i:s', $now + 1500); 
        $time_30m_range_end = date('Y-m-d H:i:s', $now + 2100);   
        
        // 10 dakika öncesi (600 saniye) 
        $time_10m = date('Y-m-d H:i:s', $now + 600);
        $time_10m_range_start = date('Y-m-d H:i:s', $now + 300);  
        $time_10m_range_end = date('Y-m-d H:i:s', $now + 900);    
        
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
            $title = "Starting in 1 hour: " . $row['title'];
            $message = "Your class at " . date("H:i", strtotime($row['date_time'])) . " is starting soon!";
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
            $title = "Starting in 30 minutes: " . $row['title'];
            $message = "Time to get ready! Check the class link.";
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
            $title = "Starting in 10 minutes: " . $row['title'];
            $message = "The class starts very soon! Open your streaming link.";
            $this->createNotification($row['user_id'], 'class_reminder_10m', $title, $message, $row['class_id']);
        }
    }
    
    
    public function getUnreadNotifications($user_id) {
        $user_id = (int)$user_id;
        $sql = "SELECT * FROM notifications 
                WHERE user_id = $user_id AND is_read = FALSE 
                ORDER BY created_at DESC 
                LIMIT 20";
        
        return mysqli_query($this->conn, $sql);
    }
    
    /**
     * sayfalama yapıyom
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
            return 0; 
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

// NotificationHandler objesi oluşturdum
$notificationHandler = new NotificationHandler($conn);


if (php_sapi_name() === 'cli' && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    ob_start();
    $notificationHandler->sendClassReminders();
    ob_end_clean();
    echo "Reminders processed at " . date('Y-m-d H:i:s') . "\n";
}

} // NOTIFICATION_HANDLER_LOADED if sonu
?>
