<?php
// Session başlatılması gereken yerde bunu çağırmadan önce session_start() yapılmalı
include 'notification_handler.php';
include 'language.php';
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : ($lang['nav_explore'] ?? 'Fitness'); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="logo">
            BABA PRO GYM
        </a>

        <div class="nav-center">
            <a href="index.php#dersler"><?php echo $lang['nav_explore']; ?></a>
        </div>

        <div class="nav-right">
            <!-- DİL SEÇİMİ TOGGLE -->
            <div class="language-toggle">
                <button class="lang-btn <?php echo $_SESSION['language'] == 'tr' ? 'active' : ''; ?>" onclick="changeLanguage('tr')">TR</button>
                <span class="lang-separator">/</span>
                <button class="lang-btn <?php echo $_SESSION['language'] == 'en' ? 'active' : ''; ?>" onclick="changeLanguage('en')">EN</button>
            </div>

            <?php if(isset($_SESSION['user_id'])): ?>
                <!-- BİLDİRİM BUTONU -->
                <div class="notification-container">
                    <button class="notification-btn" id="notificationBtn">
                        Bell Icon
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
                    
                    <!-- BİLDİRİM DROPDOWN -->
                    <div class="notification-panel" id="notificationPanel">
                        <div class="notification-header">
                            <h3><?php echo $lang['notif_title']; ?></h3>
                            <small class="notif-auto-read"><?php echo $lang['notif_auto_read']; ?></small>
                        </div>
                        <div class="notification-list" id="notificationList">
                            <!-- Bildirimler JS ile yüklenecek -->
                        </div>
                    </div>
                </div>

                <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'instructor'): ?>
                    <a href="admin.php" class="admin-badge"><?php echo $lang['nav_admin']; ?></a>
                <?php endif; ?>

                <a href="profile.php" class="btn-auth btn-login"><?php echo $lang['nav_profile']; ?></a>
                <a href="logout.php" class="btn-auth" style="color:red;"><?php echo $lang['nav_logout']; ?></a>
            <?php else: ?>
                <a href="login.php" class="btn-auth btn-login"><?php echo $lang['nav_login']; ?></a>
                <a href="register.php" class="btn-auth btn-register"><?php echo $lang['nav_register']; ?></a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- BİLDİRİM AYARLARI -->
    <script>
        // Dil değiştirme fonksiyonu
        function changeLanguage(lang) {
            fetch('?lang=' + lang, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(() => {
                // Sayfayı yenile
                location.reload();
            });
        }

        // Bildirim butonunu aç/kapat
        document.getElementById('notificationBtn').addEventListener('click', function() {
            const panel = document.getElementById('notificationPanel');
            if(panel.style.display === 'none' || panel.style.display === '') {
                panel.style.display = 'block';
                loadNotifications();
                // Kutu açıldığında tüm bildirimleri okundu işaretle
                markAllNotificationsAsRead();
            } else {
                panel.style.display = 'none';
            }
        });
        
        // Panel dışına tıklanınca kapat
        document.addEventListener('click', function(e) {
            const container = document.querySelector('.notification-container');
            if(!container.contains(e.target)) {
                document.getElementById('notificationPanel').style.display = 'none';
            }
        });
        
        // Bildirimleri yükle
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
                            <button onclick="deleteNotification(${notif.id})" class="notif-delete">✕</button>
                        </div>
                    `).join('');
                });
        }
        
        // Bildirimi sil
        function deleteNotification(id) {
            fetch('delete_notification.php?id=' + id)
                .then(() => loadNotifications());
        }
        
        // Tüm bildirimleri oku olarak işaretle
        function markAllNotificationsAsRead() {
            fetch('mark_all_read.php')
                .then(() => {
                    // Sayfayı yenileme, sadece bildirim badge'ini kaldır
                    const badge = document.querySelector('.notification-badge');
                    if(badge) {
                        badge.remove();
                    }
                    loadNotifications();
                });
        }
    </script>

