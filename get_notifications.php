<?php
/**
 * Bildirimleri JSON formatında getir
 */

session_start();

if(!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['notifications' => []]);
    exit;
}

include 'db.php';
include 'notification_handler.php';

$result = $notificationHandler->getAllNotifications($_SESSION['user_id'], 20);

$notifications = [];

while($row = mysqli_fetch_assoc($result)) {
    $created_at = new DateTime($row['created_at']);
    $now = new DateTime();
    $interval = $now->diff($created_at);
    
    if($interval->y > 0) {
        $time_ago = $interval->y . ' yıl önce';
    } elseif($interval->m > 0) {
        $time_ago = $interval->m . ' ay önce';
    } elseif($interval->d > 0) {
        $time_ago = $interval->d . ' gün önce';
    } elseif($interval->h > 0) {
        $time_ago = $interval->h . ' saat önce';
    } elseif($interval->i > 0) {
        $time_ago = $interval->i . ' dakika önce';
    } else {
        $time_ago = 'Az önce';
    }
    
    $notifications[] = [
        'id' => $row['id'],
        'title' => $row['title'],
        'message' => $row['message'],
        'is_read' => (bool)$row['is_read'],
        'time_ago' => $time_ago,
        'type' => $row['type']
    ];
}

header('Content-Type: application/json');
echo json_encode(['notifications' => $notifications]);
?>
