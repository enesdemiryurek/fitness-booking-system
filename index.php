<?php
session_start();
include 'db.php';
include 'notification_handler.php';
$page_title = "Fitness Booking | GYM";

// HER SAYFAYA GİREŞTE BİLDİRİMLERİ KONTROL ET VE GÖNDER
if(rand(1, 10) == 1) { // %10 oranında çalış (spam önleme)
    $notificationHandler->sendClassReminders();
}

include 'header.php';
?>

    <div class="hero">
        <h1>Push Your Limits</h1>
        <p>Discover your potential with the best instructors. Book your place now.</p>
    </div>

    
   <!-- GRUP DERSLERİ (STICKY BÖLÜM) BAŞLANGIÇ -->
    <div class="group-classes-section">
        <div class="group-wrapper">
            
            <!-- SOL TARAF: İÇERİK -->
            <div class="group-content">
                
                <!-- ZUMBA -->
                <div id="zumba" class="group-item">
                    <img src="" class="group-img" onerror="this.src='https://plus.unsplash.com/premium_photo-1663054933667-fb307cea9aab?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D'">
                    <h3>Zumba</h3>
                    <p>The perfect blend of dance and fitness! Have fun and burn calories with Latin music. Get in shape while relieving stress with choreography suitable for all levels.</p>
                  
                </div>

                <!-- PILATES -->
                <div id="pilates" class="group-item">
                    <img src="" class="group-img" onerror="this.src='https://images.unsplash.com/photo-1518611012118-696072aa579a?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D'">
                    <h3>Pilates</h3>
                    <p>Increase your flexibility, lengthen your muscles, and improve your posture. Discover your core strength with our mat or equipment Pilates options.</p>
                   
                </div>

                <!-- HIIT -->
                <div id="hiit" class="group-item">
                    <img src="" class="group-img" onerror="this.src='https://plus.unsplash.com/premium_photo-1664910207555-fac63513e7ad?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D'">
                    <h3>HIIT</h3>
                    <p>Push your limits with High Intensity Interval Training. This class will maximize fat burning in a short time and take your fitness to the top.</p>
                   
                </div>

                <!-- FITNESS -->
                <div id="fitness" class="group-item">
                    <img src="img/default.jpg" class="group-img" onerror="this.src='https://images.unsplash.com/photo-1517836357463-d25dfeac3438?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D'">
                    <h3>Fitness</h3>
                    <p>Our gym is equipped with modern equipment and offers training programs tailored to your personal goals. Increase your muscle mass or tone your body.</p>
                   
                </div>

            </div>

            <!-- SAĞ TARAF: SABİT MENÜ -->
            <div class="group-sidebar">
                <span class="zigzag">Menu</span>
                <h2 class="sidebar-title">Group<br>Lessons</h2>
                
                <ul class="sidebar-menu">
                    <li><a href="#zumba">Zumba</a></li>
                    <li><a href="#pilates">Pilates</a></li>
                    <li><a href="#hiit">HIIT</a></li>
                    <li><a href="#fitness">Fitness</a></li>
                </ul>
            </div>

        </div>
    </div>
    <!-- GRUP DERSLERİ BİTİŞ -->
            
   
    <div class="container" id="dersler">
        <h2 class="section-title">Upcoming Lessons</h2>

        <div class="class-list">
            <?php
            // Sadece gelecekteki dersler
            $current_time = date("Y-m-d H:i:s");
            $sql = "SELECT * FROM classes WHERE date_time >= '$current_time' ORDER BY date_time ASC";
            $result = mysqli_query($conn, $sql);

            if (mysqli_num_rows($result) > 0) {
                while($row = mysqli_fetch_assoc($result)) {
                    
                    // --- RESİM AYARLARI ---
                    $type = mb_strtolower($row['class_type']);
                    $img_url = "img/default.jpg"; 

                    if(strpos($type, 'yoga') !== false) $img_url = "img/yoga.jpg";
                    elseif(strpos($type, 'pilates') !== false) $img_url = "img/pilates.jpg";
                    elseif(strpos($type, 'hiit') !== false) $img_url = "img/hiit.jpg";
                    elseif(strpos($type, 'zumba') !== false) $img_url = "img/zumba.jpg";
                    elseif(strpos($type, 'fitness') !== false) $img_url = "img/fitness.jpg";
                    
                    echo '<div class="class-card">';
                    echo '<img src="'.$img_url.'" alt="Ders Resmi" class="card-image" onerror="this.src=\'https://placehold.co/600x400?text=Resim+Yok\'">';
                    
                    echo '<div class="card-content">';
                        echo '<h3>' . $row["title"] . ' <span class="badge">' . $row["class_type"] . '</span></h3>';
                        echo '<p style="color:#666; margin-top:5px;">🧘‍♂️ ' . $row["trainer_name"] . ' • 🕒 ' . date("d.m.Y H:i", strtotime($row["date_time"])) . '</p>';
                        echo '<p style="margin-top:10px;">' . $row["description"] . '</p>';
                        
                        // Stok Durumu
                        $stok_color = ($row["capacity"] < 3) ? "#dc3545" : "#28a745";
                        echo '<span class="stok" style="color:'.$stok_color.'"> Remaining Place: ' . $row["capacity"] . '</span>';

                        // Rezerve Butonları
                        if(isset($_SESSION['user_id'])) {
                            if ($row["capacity"] > 0) {
                                echo '<a href="book_class.php?id='.$row['id'].'" class="btn-card">Book Now</a>';
                            } else {
                                echo '<button class="btn-card btn-disabled" disabled>FULL</button>';
                            }
                        } else {
                            echo '<a href="login.php" class="btn-card" style="background:#666;">Login & Book</a>';
                        }

                    echo '</div>'; // card-content
                    echo '</div>'; // class-card
                }
            } else {
                echo "<p style='text-align:center; width:100%;'>There are no active courses yet.</p>";
            }
            ?>
        </div>
    </div>

    <!-- GEÇMIŞ DERSLER BÖLÜMÜ -->
    <div class="container" id="gecmis-dersler">
        <h2 class="section-title"> Past Lessons </h2>

        <div class="class-list">
            <?php
            // Son 24 saat içinde geçen dersler
            $now = time();
            $one_day_ago = date("Y-m-d H:i:s", $now - 604800); // 1 hafta zaman
            $current_time = date("Y-m-d H:i:s");

            $sql = "SELECT * FROM classes WHERE date_time < '$current_time' AND date_time >= '$one_day_ago' ORDER BY date_time DESC";
            $result = mysqli_query($conn, $sql);

            if (mysqli_num_rows($result) > 0) {
                while($row = mysqli_fetch_assoc($result)) {
                    
                    // --- RESİM AYARLARI ---
                    $type = mb_strtolower($row['class_type']);
                    $img_url = "img/default.jpg"; 

                    if(strpos($type, 'yoga') !== false) $img_url = "img/yoga.jpg";
                    elseif(strpos($type, 'pilates') !== false) $img_url = "img/pilates.jpg";
                    elseif(strpos($type, 'hiit') !== false) $img_url = "img/hiit.jpg";
                    elseif(strpos($type, 'zumba') !== false) $img_url = "img/zumba.jpg";
                    elseif(strpos($type, 'fitness') !== false) $img_url = "img/fitness.jpg";
                    
                    echo '<div class="class-card past-class">';
                    echo '<img src="'.$img_url.'" alt="Ders Resmi" class="card-image past-image" onerror="this.src=\'https://placehold.co/600x400?text=Resim+Yok\'">';
                    
                    echo '<div class="card-content">';
                        echo '<h3>' . $row["title"] . ' <span class="badge">Completed</span></h3>';
                        echo '<p style="color:#666; margin-top:5px;">🧘‍♂️ ' . $row["trainer_name"] . ' • 🕒 ' . date("d.m.Y H:i", strtotime($row["date_time"])) . '</p>';
                        echo '<p style="margin-top:10px;">' . $row["description"] . '</p>';
                        
                       
                        echo '<button class="btn-card btn-disabled" disabled>Completed</button>';

                    echo '</div>'; // card-content
                    echo '</div>'; // class-card
                }
            } else {
                echo "<p style='text-align:center; width:100%;'>Henüz geçmiş ders bulunmuyor.</p>";
            }
            ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>