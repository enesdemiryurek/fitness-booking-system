<?php
session_start();
include 'db.php';
include 'notification_handler.php';
$page_title = "Edit Class | GYM";

// GÜVENLİK: Admin veya Instructor giriş yapmalı
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'instructor')) {
    die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'><h1>⛔ Unauthorized Access!</h1><p>Only administrators and instructors can view this page.</p><a href='index.php'>Return to Home</a></div>");
}

$message = "";
$message_type = "";
$class_data = null;

// Ders ID'sini al
if (isset($_GET['id'])) {
    $class_id = (int)$_GET['id'];
    
    // Dersin bilgisini getir
    $sql = "SELECT * FROM classes WHERE id = $class_id";
    $result = mysqli_query($conn, $sql);
    $class_data = mysqli_fetch_assoc($result);
    
    // Eğitmen ise kendi derslerini mi editlemek istediğini kontrol et
    if ($_SESSION['role'] == 'instructor' && $class_data['trainer_name'] != $_SESSION['username']) {
        die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'><h1> Not Allowed!</h1><p>You can only edit your own classes.</p><a href='admin.php'>Back to Admin Panel</a></div>");
    }
    
    // Ders bulunamazsa
    if (!$class_data) {
        header("Location: admin.php");
        exit();
    }
} else {
    header("Location: admin.php");
    exit();
}

// GÜNCELLEME İŞLEMİ
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $type = mysqli_real_escape_string($conn, $_POST['class_type']);
    $date = mysqli_real_escape_string($conn, $_POST['date_time']);
    $capacity = (int)$_POST['capacity'];
    $link = mysqli_real_escape_string($conn, $_POST['video_link']);
    
    // Admin ise trainer adı değiştirilebilir
    if ($_SESSION['role'] == 'admin') {
        $trainer = mysqli_real_escape_string($conn, $_POST['trainer']);
    } else {
        $trainer = $_SESSION['username'];
    }
    
    // Eski zamanı sakla (bildirim göndermek için)
    $old_time = $class_data['date_time'];
    
    // Güncelle
    $update_sql = "UPDATE classes SET 
                    title = '$title',
                    trainer_name = '$trainer',
                    description = '$description',
                    class_type = '$type',
                    date_time = '$date',
                    capacity = $capacity,
                    video_link = '$link'
                   WHERE id = $class_id";
    
    if (mysqli_query($conn, $update_sql)) {
        // Eğer saat değiştiyse bildirim gönder
        if ($old_time != $date) {
            $notificationHandler->notifyClassTimeUpdate($class_id, $title, $old_time, $date);
        }
        
        $message = "✅ Class updated successfully!";
        $message_type = "success";
        
        // Veriyi yenile
        $class_data['title'] = $title;
        $class_data['trainer_name'] = $trainer;
        $class_data['description'] = $description;
        $class_data['class_type'] = $type;
        $class_data['date_time'] = $date;
        $class_data['capacity'] = $capacity;
        $class_data['video_link'] = $link;
    } else {
        $message = "❌ Error: " . mysqli_error($conn);
        $message_type = "error";
    }
}

include 'header.php';
?>
<style>
    .page-shell {max-width: 900px; margin: 0 auto; padding: 24px; background: #fff;}
    .page-shell h1 {margin: 0 0 10px 0; font-size: 26px;}
    .helper {color: #666; margin-bottom: 16px;}
    .section {border: 1px solid #e0e0e0; background: #fafafa; padding: 16px; border-radius: 6px; margin-bottom: 16px;}
    .section h2 {margin: 0 0 8px 0; font-size: 20px;}
    .section p {margin: 0 0 10px 0; color: #555;}
    .stack {display: flex; flex-direction: column; gap: 10px;}
    .field {display: flex; flex-direction: column; gap: 6px;}
    label {font-weight: 600; font-size: 14px;}
    input[type="text"], input[type="url"], input[type="datetime-local"], input[type="number"], select, textarea {padding: 8px; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 14px;}
    textarea {min-height: 90px;}
    .btn {padding: 10px 14px; background: #222; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block;}
    .btn.secondary {background: #0b6bcb;}
    .btn.ghost {background: #f0f0f0; color: #222;}
    .btn.inline {margin-right: 8px;}
    .note {padding: 10px; border-radius: 4px; margin-bottom: 12px;}
    .note.success {background: #e6f7e6; color: #1e6b1e; border: 1px solid #c5e6c5;}
    .note.error {background: #ffecec; color: #b80000; border: 1px solid #ffb3b3;}
    .grid {display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px;}
    .info {display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px;}
    .info-item {background: #fff; border: 1px solid #e0e0e0; border-radius: 6px; padding: 10px;}
    .info-label {display: block; color: #666; font-size: 12px; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.4px;}
    .info-value {font-weight: 600; color: #222; word-break: break-word;}
</style>

<div class="page-shell">
    <h1>Edit Class</h1>
    <div class="helper">Make quick edits, save, or return to the admin panel.</div>

    <?php if($message): ?>
        <div class="note <?php echo htmlspecialchars($message_type); ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <a href="admin.php" class="btn ghost inline">← Back to Admin Panel</a>

    <div class="section">
        <h2>Class Details</h2>
        <p><?php echo htmlspecialchars($class_data['title']); ?> — update and save changes.</p>
        <form action="" method="POST" class="stack">
            <div class="grid">
                <div class="field">
                    <label for="title">Class Title</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($class_data['title']); ?>" required>
                </div>

                <div class="field">
                    <label for="trainer">Instructor</label>
                    <?php if($_SESSION['role'] == 'instructor'): ?>
                        <input type="text" id="trainer" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" readonly>
                    <?php else: ?>
                        <select id="trainer" name="trainer" required>
                            <option value="">Select instructor</option>
                            <?php
                            $trainers_result = mysqli_query($conn, "SELECT username FROM users WHERE role = 'instructor' ORDER BY username ASC");
                            while($trainer_row = mysqli_fetch_assoc($trainers_result)) {
                                $selected = ($trainer_row['username'] == $class_data['trainer_name']) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($trainer_row['username']) . "' $selected>" . htmlspecialchars($trainer_row['username']) . "</option>";
                            }
                            ?>
                        </select>
                    <?php endif; ?>
                </div>

                <div class="field">
                    <label for="class_type">Category</label>
                    <select id="class_type" name="class_type" required>
                        <option value="Yoga" <?php echo ($class_data['class_type'] == 'Yoga') ? 'selected' : ''; ?>>Yoga</option>
                        <option value="Pilates" <?php echo ($class_data['class_type'] == 'Pilates') ? 'selected' : ''; ?>>Pilates</option>
                        <option value="HIIT" <?php echo ($class_data['class_type'] == 'HIIT') ? 'selected' : ''; ?>>HIIT</option>
                        <option value="Zumba" <?php echo ($class_data['class_type'] == 'Zumba') ? 'selected' : ''; ?>>Zumba</option>
                        <option value="Fitness" <?php echo ($class_data['class_type'] == 'Fitness') ? 'selected' : ''; ?>>Fitness</option>
                    </select>
                </div>

                <div class="field">
                    <label for="capacity">Capacity</label>
                    <input type="number" id="capacity" name="capacity" value="<?php echo (int) $class_data['capacity']; ?>" min="1" max="50" required>
                </div>

                <div class="field">
                    <label for="date_time">Date & Time</label>
                    <input type="datetime-local" id="date_time" name="date_time" value="<?php echo date('Y-m-d\TH:i', strtotime($class_data['date_time'])); ?>" required>
                    <span class="helper" style="margin:0; color:#777;">Changing this notifies booked users.</span>
                </div>

                <div class="field">
                    <label for="video_link">Video Link</label>
                    <input type="url" id="video_link" name="video_link" value="<?php echo htmlspecialchars($class_data['video_link']); ?>" required>
                </div>

                <div class="field" style="grid-column: 1 / -1;">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($class_data['description']); ?></textarea>
                </div>
            </div>

            <div class="inline" style="display:flex; gap:8px;">
                <button type="submit" class="btn">Save Changes</button>
                <a href="admin.php" class="btn ghost">Cancel</a>
            </div>
        </form>
    </div>

    <div class="section">
        <h2>Current Information</h2>
        <div class="info">
            <div class="info-item">
                <span class="info-label">Title</span>
                <span class="info-value"><?php echo htmlspecialchars($class_data['title']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Instructor</span>
                <span class="info-value"><?php echo htmlspecialchars($class_data['trainer_name']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Category</span>
                <span class="info-value"><?php echo htmlspecialchars($class_data['class_type']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Capacity</span>
                <span class="info-value"><?php echo (int) $class_data['capacity']; ?> people</span>
            </div>
            <div class="info-item">
                <span class="info-label">Date & Time</span>
                <span class="info-value"><?php echo date("d.m.Y H:i", strtotime($class_data['date_time'])); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Video Link</span>
                <span class="info-value"><a href="<?php echo htmlspecialchars($class_data['video_link']); ?>" target="_blank" style="color:#0b6bcb;">Open link</a></span>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
