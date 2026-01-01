<?php


session_start();
include 'db.php';
include 'notification_handler.php';

if(!isset($_SESSION['user_id'])) {
    exit;
}

$notificationHandler->markAllAsRead($_SESSION['user_id']);

header('Content-Type: application/json');
echo json_encode(['success' => true]);
?>
