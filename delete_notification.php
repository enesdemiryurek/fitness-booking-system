<?php
/**
 * Bildirimi sil
 */

session_start();
include 'db.php';
include 'notification_handler.php';

if(!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    exit;
}

$notification_id = (int)$_GET['id'];

// Güvenlik: kullanıcı sadece kendi bildirimlerini silebilir
$check_sql = "SELECT user_id FROM notifications WHERE id = $notification_id";
$check_result = mysqli_query($conn, $check_sql);
$notif = mysqli_fetch_assoc($check_result);

if($notif['user_id'] == $_SESSION['user_id']) {
    $notificationHandler->deleteNotification($notification_id);
}

header('Content-Type: application/json');
echo json_encode(['success' => true]);
?>
