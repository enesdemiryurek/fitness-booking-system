<?php
session_start();
include 'db.php';
include 'notification_handler.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$class_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$user_id = (int) $_SESSION['user_id'];

if ($class_id <= 0) {
    header('Location: index.php');
    exit;
}

$duplicate_stmt = mysqli_prepare($conn, 'SELECT id FROM bookings WHERE user_id = ? AND class_id = ? LIMIT 1');
if ($duplicate_stmt) {
    mysqli_stmt_bind_param($duplicate_stmt, 'ii', $user_id, $class_id);
    mysqli_stmt_execute($duplicate_stmt);
    mysqli_stmt_store_result($duplicate_stmt);
    if (mysqli_stmt_num_rows($duplicate_stmt) > 0) {
        mysqli_stmt_close($duplicate_stmt);
        header('Location: index.php?msg=already_registered');
        exit;
    }
    mysqli_stmt_close($duplicate_stmt);
}

$class_stmt = mysqli_prepare($conn, 'SELECT capacity FROM classes WHERE id = ? LIMIT 1');
if ($class_stmt) {
    mysqli_stmt_bind_param($class_stmt, 'i', $class_id);
    mysqli_stmt_execute($class_stmt);
    mysqli_stmt_bind_result($class_stmt, $capacity);
    $found = mysqli_stmt_fetch($class_stmt);
    mysqli_stmt_close($class_stmt);

    if (!$found) {
        header('Location: index.php?msg=class_not_found');
        exit;
    }

    if ((int) $capacity <= 0) {
        header('Location: index.php?msg=class_full');
        exit;
    }

    header('Location: process_payment.php?class_id=' . $class_id);
    exit;
}

header('Location: index.php?msg=error');
?>