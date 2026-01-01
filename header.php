<?php

include 'notification_handler.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : ($lang['nav_explore'] ?? 'Fitness'); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="logo">
            GYMGYME
        </a>

        <div class="nav-center">
            <a href="index.php#dersler">Explore Lessons</a>
        </div>

        <div class="nav-right">
            <?php if(isset($_SESSION['user_id'])): ?>
             
                <div class="notification-container">
                    <button class="notification-btn" id="notificationBtn">
                        🔔
                        <?php 
                        if(isset($notificationHandler)) {
                            $unread_count = $notificationHandler->getUnreadCount($_SESSION['user_id']);
                            if($unread_count > 0): 
                        ?>
                            <span class="notification-badge"><?php echo $unread_count; ?></span>
                        <?php 
                            endif;
                        }
                        ?>
                    </button>
                    
             
                    <div class="notification-panel" id="notificationPanel">
                        <div class="notification-header">
                            <h3></h3>
                            <small class="notif-auto-read">marked as read</small>
                        </div>
                        <div class="notification-list" id="notificationList">
                           
                        </div>
                    </div>
                </div>

                <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'instructor'): ?>
                    <a href="admin.php" class="admin-badge">Add Lessons</a>
                <?php endif; ?>

                <a href="profile.php" class="btn-auth btn-login">My Profile</a>
                <a href="logout.php" class="btn-auth" style="color:#0A66C2;">Log Out</a>
            <?php else: ?>
                <a href="login.php" class="btn-auth btn-login">Login</a>
                <a href="register.php" class="btn-auth btn-register">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>

    
    <script>
    
        document.getElementById('notificationBtn').addEventListener('click', function() {
            const panel = document.getElementById('notificationPanel');
            if(panel.style.display === 'none' || panel.style.display === '') {
                panel.style.display = 'block';
                loadNotifications();
              
                markAllNotificationsAsRead();
            } else {
                panel.style.display = 'none';
            }
        });
        
      
        document.addEventListener('click', function(e) {
            const container = document.querySelector('.notification-container');
            if(!container.contains(e.target)) {
                document.getElementById('notificationPanel').style.display = 'none';
            }
        });
        
      
        function loadNotifications() {
            fetch('get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    const list = document.getElementById('notificationList');
                    
                    if(data.notifications.length === 0) {
                        list.innerHTML = '<div class="no-notifications">No notifications</div>';
                        return;
                    }
                    
                    list.innerHTML = data.notifications.map(notif => `
                        <div class="notification-item ${notif.is_read ? 'read' : 'unread'}">
                            <div class="notif-content">
                                <div class="notif-title">${notif.title}</div>
                                <div class="notif-message">${notif.message}</div>
                                <div class="notif-time">${notif.time_ago}</div>
                            </div>
                            <button onclick="deleteNotification(${notif.id})" class="notif-delete">X</button>
                        </div>
                    `).join('');
                });
        }
        
 
        function deleteNotification(id) {
            fetch('delete_notification.php?id=' + id)
                .then(() => loadNotifications());
        }
        
       
        function markAllNotificationsAsRead() {
            fetch('mark_all_read.php')
                .then(() => {
                    
                    const badge = document.querySelector('.notification-badge');
                    if(badge) {
                        badge.remove();
                    }
                    loadNotifications();
                });
        }
    </script>

