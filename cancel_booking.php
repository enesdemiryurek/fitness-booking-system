<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$booking_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$user_id = (int) $_SESSION['user_id'];

if ($booking_id <= 0) {
    header('Location: profile.php');
    exit;
}

$find_stmt = mysqli_prepare($conn, 'SELECT class_id FROM bookings WHERE id = ? AND user_id = ? LIMIT 1');
if ($find_stmt) {
    mysqli_stmt_bind_param($find_stmt, 'ii', $booking_id, $user_id);
    mysqli_stmt_execute($find_stmt);
    mysqli_stmt_bind_result($find_stmt, $class_id);
    $found = mysqli_stmt_fetch($find_stmt);
    mysqli_stmt_close($find_stmt);

    if (!$found) {
        header('Location: profile.php?msg=booking_not_found');
        exit;
    }

    $delete_stmt = mysqli_prepare($conn, 'DELETE FROM bookings WHERE id = ? AND user_id = ?');
    if ($delete_stmt) {
        mysqli_stmt_bind_param($delete_stmt, 'ii', $booking_id, $user_id);
        mysqli_stmt_execute($delete_stmt);
        mysqli_stmt_close($delete_stmt);
    }

    $update_stmt = mysqli_prepare($conn, 'UPDATE classes SET capacity = capacity + 1 WHERE id = ?');
    if ($update_stmt) {
        mysqli_stmt_bind_param($update_stmt, 'i', $class_id);
        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);
    }

    header('Location: profile.php?msg=booking_cancelled');
    exit;
}

header('Location: profile.php?msg=error');
?>