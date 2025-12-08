<?php
session_start();
include 'db.php';
include 'notification_handler.php';
$page_title = "Admin Panel | GYM";

// 1. GÜVENLİK DUVARI: Admin VEYA Instructor girebilir
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'instructor')) {
    die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'><h1>Unauthorized Access!</h1><p>Only administrators and instructors can access this page.</p><a href='index.php'>Back Homepage</a></div>");
}

$message = "";
$message_type = ""; // success veya error

$user_search_query = '';
$user_search_results = [];

// --- KULLANICI ROLÜ GÜNCELLEME ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_role']) && $_SESSION['role'] == 'admin') {
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $new_role = $_POST['new_role'] ?? '';
    $user_search_query = trim($_POST['search_query'] ?? '');
    $allowed_roles = ['user', 'instructor'];

    if ($user_id > 0 && in_array($new_role, $allowed_roles, true)) {
        $update_sql = "UPDATE users SET role = '" . mysqli_real_escape_string($conn, $new_role) . "' WHERE id = $user_id";
        if (mysqli_query($conn, $update_sql)) {
            $message = "User role updated successfully.";
            $message_type = "success";
        } else {
            $message = "Error: " . mysqli_error($conn);
            $message_type = "error";
        }
    } else {
        $message = "Invalid user or role selection.";
        $message_type = "error";
    }
}

// --- YENİ DERS EKLEME ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['update_role'])) {
    $title = $_POST['title'];
    
    // --- EĞİTMEN ADI BELİRLEME MANTIĞI ---
    // Eğer giriş yapan kişi EĞİTMENSE: Formdan gelen veriyi yok say, Session'daki adını al.
    // Eğer giriş yapan kişi ADMİNSE: Formdan gelen veriyi al.
    if ($_SESSION['role'] == 'instructor') {
        $trainer = $_SESSION['username'];
    } else {
        $trainer = $_POST['trainer'];
    }
    // -------------------------------------

    $description = $_POST['description'];
    $type = $_POST['class_type'];
    $date = $_POST['date_time'];
    $capacity = $_POST['capacity'];
    $link = $_POST['video_link'];
    
    $sql = "INSERT INTO classes (title, trainer_name, description, class_type, date_time, capacity, video_link) 
            VALUES ('$title', '$trainer', '$description', '$type', '$date', '$capacity', '$link')";

    if (mysqli_query($conn, $sql)) {
        $class_id = mysqli_insert_id($conn);
        
        // BİLDİRİM GÖNDER: Yeni ders eklendi
        $notificationHandler->notifyNewClass($class_id, $title, $type, $trainer, $date);
        
        $message = "Course added successfully.";
        $message_type = "success";
    } else {
        $message = "Error: " . mysqli_error($conn);
        $message_type = "error";
    }
}

// --- KULLANICI ARAMA SONUÇLARI ---
if ($_SESSION['role'] == 'admin') {
    if ($user_search_query === '' && isset($_GET['user_search'])) {
        $user_search_query = trim($_GET['user_search']);
    }

    if ($user_search_query !== '') {
        $safe_query = mysqli_real_escape_string($conn, $user_search_query);
        $users_result = mysqli_query($conn, "SELECT id, username, email, role FROM users WHERE username LIKE '%$safe_query%' AND role != 'admin' ORDER BY username ASC LIMIT 25");
        if ($users_result) {
            while ($row = mysqli_fetch_assoc($users_result)) {
                $user_search_results[] = $row;
            }
            mysqli_free_result($users_result);
        }
    }
}

// --- SİLME İŞLEMİ ---
if (isset($_GET['delete_id'])) {
    // Sadece ADMIN silebilir
    if ($_SESSION['role'] == 'admin') {
        $id = $_GET['delete_id'];
        
        // Silinecek dersin bilgisini al
        $class_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT title FROM classes WHERE id=$id"));
        
        // BİLDİRİM GÖNDER: Ders iptal edildi
        $notificationHandler->notifyCancelledClass($id, $class_info['title'], 'Canceled by administrator');
        
        mysqli_query($conn, "DELETE FROM classes WHERE id=$id");
        header("Location: admin.php");
    } else {
        $message = "Error: Only administrators can delete a course.";
        $message_type = "error";
    }
}

include 'header.php';
?>

<div class="admin-page">
    
    <!-- HERO BÖLÜMÜ -->
    <div class="admin-hero-simple">
        <h1><?php echo ($_SESSION['role'] == 'admin') ? "Admin Panel" : "Trainer Panel"; ?></h1>
    </div>

    <div class="admin-container">

        <!-- MESAJ GÖRÜNTÜLEME -->
        <?php if($message): ?>
            <div class="message-box message-<?php echo $message_type; ?>">
                <div class="message-content">
                    <?php echo $message; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if($_SESSION['role'] == 'admin'): ?>
            <div class="form-section user-management-section">
                <div class="section-header">
                    <h2>User Management</h2>
                    <p>Search users and update their roles to <strong>Member</strong> or <strong>Instructor</strong>.</p>
                </div>

                <form method="GET" class="user-search-form">
                    <input type="text" name="user_search" placeholder="Search by username" value="<?php echo htmlspecialchars($user_search_query); ?>">
                    <button type="submit" class="btn-action-small btn-edit">Search</button>
                    <?php if($user_search_query !== ''): ?>
                        <a href="admin.php" class="btn-action-small btn-delete">Reset</a>
                    <?php endif; ?>
                </form>

                <?php if($user_search_query !== ''): ?>
                    <?php if(count($user_search_results) > 0): ?>
                        <div class="table-wrapper">
                            <table class="admin-table user-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Current Role</th>
                                        <th>Update Role</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($user_search_results as $user): ?>
                                        <tr>
                                            <td class="td-id">#<?php echo str_pad($user['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo $user['role'] === 'instructor' ? 'Instructor' : 'Member'; ?></td>
                                            <td>
                                                <form method="POST" class="user-role-form">
                                                    <input type="hidden" name="user_id" value="<?php echo intval($user['id']); ?>">
                                                    <input type="hidden" name="search_query" value="<?php echo htmlspecialchars($user_search_query); ?>">
                                                    <select name="new_role" class="role-select">
                                                        <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>Member</option>
                                                        <option value="instructor" <?php echo $user['role'] === 'instructor' ? 'selected' : ''; ?>>Instructor</option>
                                                    </select>
                                                    <button type="submit" name="update_role" value="1" class="btn-action-small btn-edit">Save</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">No results found for "<?php echo htmlspecialchars($user_search_query); ?>".</div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- YENİ DERS FORMU -->
        <div class="form-section">
            <div class="section-header">
                <h2>Create New Lesson</h2>
                <p>Get students involved by adding a new course to the system</p>
            </div>

            <form action="" method="POST" class="modern-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="title">Course Title</label>
                        <input type="text" id="title" name="title" placeholder="Ex: Morning Yoga" required>
                        <small>Example: Introduction to Pilates Basics</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="trainer">Instructor Name</label>
                        <?php if($_SESSION['role'] == 'instructor'): ?>
                            <input type="text" id="trainer" value="<?php echo $_SESSION['username']; ?>" readonly class="input-readonly">
                            <small>Your registered name in the system</small>
                        <?php else: ?>
                            <select id="trainer" name="trainer" required>
                                <option value="">-- Select Instructor --</option>
                                <?php
                                // Veritabanından instructor rolünde olan kişileri çek
                                $trainers_result = mysqli_query($conn, "SELECT username FROM users WHERE role = 'instructor' ORDER BY username ASC");
                                while($trainer_row = mysqli_fetch_assoc($trainers_result)) {
                                    echo "<option value='" . htmlspecialchars($trainer_row['username']) . "'>" . htmlspecialchars($trainer_row['username']) . "</option>";
                                }
                                ?>
                            </select>
                            <small>Select the name of the instructor who will manage the class</small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="class_type">Category</label>
                        <select id="class_type" name="class_type" required>
                            <option value="">-- Select --</option>
                            <option value="Yoga">Yoga</option>
                            <option value="Pilates">Pilates</option>
                            <option value="HIIT">HIIT</option>
                            <option value="Zumba">Zumba</option>
                            <option value="Fitness">Fitness</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="capacity">Capacity (People)</label>
                        <input type="number" id="capacity" name="capacity" value="10" min="1" max="50" required>
                        <small>How many people can attend the class</small>
                    </div>

                    <div class="form-group">
                        <label for="date_time">Date & Time</label>
                        <input type="datetime-local" id="date_time" name="date_time" required>
                    </div>

                    <div class="form-group">
                        <label for="video_link">Video Link</label>
                        <input type="url" id="video_link" name="video_link" placeholder="https://zoom.us/... or https://youtube.com/..." required>
                        <small>Zoom, Google Meet or YouTube link</small>
                    </div>

                    <div class="form-group full-width">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" placeholder="Provide detailed information about the class..." rows="4" required></textarea>
                        <small>Class purpose, content, requirements, etc.</small>
                    </div>

                    <div class="form-group full-width">
                        <button type="submit" class="btn-submit-large">Publish Class</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- DERS LİSTESİ -->
        <div class="table-section">
            <div class="section-header">
                <h2>Active Class List</h2>
                <p>Manage and edit all classes in the system</p>
            </div>

            <div class="table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Class Information</th>
                            <th>Instructor</th>
                            <th>Date & Time</th>
                            <th>Capacity</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = mysqli_query($conn, "SELECT * FROM classes WHERE date_time >= NOW() ORDER BY date_time ASC");
                        if(mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) {
                                $class_date = new DateTime($row['date_time']);
                                
                                echo "<tr>";
                                echo "<td class='td-id'>#" . str_pad($row['id'], 4, '0', STR_PAD_LEFT) . "</td>";
                                echo "<td class='td-title'>";
                                echo "<strong>" . htmlspecialchars($row['title']) . "</strong>";
                                echo "<br><span class='class-badge'>" . $row['class_type'] . "</span>";
                                echo "</td>";
                                echo "<td>" . htmlspecialchars($row['trainer_name']) . "</td>";
                                echo "<td class='td-date'>" . $class_date->format("d.m.Y H:i") . "</td>";
                                echo "<td><span class='badge-capacity'>" . $row['capacity'] . "</span></td>";
                                
                                echo "<td class='td-actions'>";
                                echo "<a href='class_edit.php?id=" . $row['id'] . "' class='btn-action-small btn-edit'>Edit</a>";
                                if ($_SESSION['role'] == 'admin') {
                                    echo "<a href='admin.php?delete_id=" . $row['id'] . "' class='btn-action-small btn-delete' onclick='return confirm(\"Are you sure you want to delete this class?\")'>Delete</a>";
                                } else {
                                    echo "<span class='btn-action-small btn-locked'>Locked</span>";
                                }
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center; padding:40px; color:#999;'>No upcoming classes</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- GEÇMİŞ DERS LİSTESİ -->
        <div class="table-section past-section">
            <div class="section-header">
                <h2>Past Classes</h2>
                <p>Previously held and archived classes</p>
            </div>

            <div class="table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Class Information</th>
                            <th>Instructor</th>
                            <th>Date & Time</th>
                            <th>Capacity</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $past_result = mysqli_query($conn, "SELECT * FROM classes WHERE date_time < NOW() ORDER BY date_time DESC");
                        if(mysqli_num_rows($past_result) > 0) {
                            while($row = mysqli_fetch_assoc($past_result)) {
                                $class_date = new DateTime($row['date_time']);
                                
                                echo "<tr>";
                                echo "<td class='td-id'>#" . str_pad($row['id'], 4, '0', STR_PAD_LEFT) . "</td>";
                                echo "<td class='td-title'>";
                                echo "<strong>" . htmlspecialchars($row['title']) . "</strong>";
                                echo "<br><span class='class-badge'>" . $row['class_type'] . "</span>";
                                echo "</td>";
                                echo "<td>" . htmlspecialchars($row['trainer_name']) . "</td>";
                                echo "<td class='td-date'>" . $class_date->format("d.m.Y H:i") . "</td>";
                                echo "<td><span class='badge-capacity'>" . $row['capacity'] . "</span></td>";
                                
                                echo "<td class='td-actions'>";
                                if ($_SESSION['role'] == 'admin') {
                                    echo "<a href='admin.php?delete_id=" . $row['id'] . "' class='btn-action-small btn-delete' onclick='return confirm(\"Are you sure you want to delete this class?\")'>Delete</a>";
                                } else {
                                    echo "<span class='btn-action-small btn-locked'>Locked</span>";
                                }
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center; padding:40px; color:#999;'>No past classes</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<?php include 'footer.php'; ?>