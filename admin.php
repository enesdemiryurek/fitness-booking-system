<?php
session_start();
include 'db.php';
include 'notification_handler.php';

$class_categories = include __DIR__ . '/class_categories.php';
if (!is_array($class_categories) || empty($class_categories)) {
    $class_categories = [
        'Yoga' => 'Yoga',
        'Pilates' => 'Pilates',
        'HIIT' => 'HIIT',
        'Zumba' => 'Zumba',
        'Fitness' => 'Fitness'
    ];
}
$category_keys = array_keys($class_categories);

function adminPanelGetInstructorId(mysqli $conn, string $username): ?int {
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? AND role = 'instructor' LIMIT 1");
    if (!$stmt) {
        return null;
    }
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $id);
    $result = mysqli_stmt_fetch($stmt) ? (int) $id : null;
    mysqli_stmt_close($stmt);
    return $result;
}

function adminPanelGetInstructorSpecialties(mysqli $conn, int $userId): array {
    $stmt = mysqli_prepare($conn, "SELECT class_type FROM instructor_specialties WHERE user_id = ? ORDER BY class_type ASC");
    if (!$stmt) {
        return [];
    }
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $categories = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row['class_type'];
        }
        mysqli_free_result($result);
    }
    mysqli_stmt_close($stmt);
    return array_values(array_unique($categories));
}

$page_title = 'Admin Panel | GYM';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'instructor')) {
    die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'><h1>Unauthorized Access</h1><p>Only administrators and instructors can access this page.</p><a href='index.php'>Back to homepage</a></div>");
}

$message = '';
$message_type = '';
$user_search_query = '';
$user_search_results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_role']) && $_SESSION['role'] === 'admin') {
        $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
        $new_role = $_POST['new_role'] ?? '';
        $user_search_query = trim($_POST['search_query'] ?? '');
        $allowed_roles = ['user', 'instructor'];

        if ($user_id > 0 && in_array($new_role, $allowed_roles, true)) {
            $stmt = mysqli_prepare($conn, 'UPDATE users SET role = ? WHERE id = ?');
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'si', $new_role, $user_id);
                if (mysqli_stmt_execute($stmt)) {
                    $message = 'User role updated.';
                    $message_type = 'success';
                } else {
                    $message = 'Role update failed.';
                    $message_type = 'error';
                }
                mysqli_stmt_close($stmt);
            } else {
                $message = 'Role update could not be prepared.';
                $message_type = 'error';
            }
        } else {
            $message = 'Invalid user or role.';
            $message_type = 'error';
        }
    } elseif (isset($_POST['create_class'])) {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $type = trim($_POST['class_type'] ?? '');
        $date = trim($_POST['date_time'] ?? '');
        $capacity = isset($_POST['capacity']) ? (int) $_POST['capacity'] : 0;
        $link = trim($_POST['video_link'] ?? '');

        $trainerUsername = '';
        $trainerId = 0;
        if ($_SESSION['role'] === 'instructor') {
            $trainerUsername = $_SESSION['username'];
            $trainerId = (int) $_SESSION['user_id'];
        } else {
            $trainerUsername = trim($_POST['trainer'] ?? '');
            $trainerId = $trainerUsername !== '' ? (int) adminPanelGetInstructorId($conn, $trainerUsername) : 0;
        }

        $errors = [];
        if ($trainerId <= 0 || $trainerUsername === '') {
            $errors[] = 'Select a valid instructor.';
        }
        if ($title === '' || $description === '' || $date === '' || $link === '') {
            $errors[] = 'All fields are required.';
        }
        if ($capacity <= 0) {
            $errors[] = 'Capacity must be at least 1.';
        }

        $allowedCategories = $trainerId > 0 ? adminPanelGetInstructorSpecialties($conn, $trainerId) : [];
        if (empty($allowedCategories)) {
            $errors[] = 'This instructor has no specialties. Assign categories first.';
        } elseif (!in_array($type, $allowedCategories, true)) {
            $errors[] = 'Category not allowed for this instructor.';
        }

        if ($type === '' || !in_array($type, $category_keys, true)) {
            $errors[] = 'Invalid category.';
        }

        if (empty($errors)) {
            $stmt = mysqli_prepare($conn, 'INSERT INTO classes (title, trainer_name, description, class_type, date_time, capacity, video_link) VALUES (?, ?, ?, ?, ?, ?, ?)');
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'sssssis', $title, $trainerUsername, $description, $type, $date, $capacity, $link);
                if (mysqli_stmt_execute($stmt)) {
                    $class_id = mysqli_insert_id($conn);
                    $notificationHandler->notifyNewClass($class_id, $title, $type, $trainerUsername, $date);
                    $message = 'Class created.';
                    $message_type = 'success';
                } else {
                    $message = 'Class could not be created.';
                    $message_type = 'error';
                }
                mysqli_stmt_close($stmt);
            } else {
                $message = 'Insert could not be prepared.';
                $message_type = 'error';
            }
        } else {
            $message = implode(' ', $errors);
            $message_type = 'error';
        }
    }
}

if ($_SESSION['role'] === 'admin') {
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

$instructors = [];
$instructors_result = mysqli_query($conn, "SELECT id, username FROM users WHERE role = 'instructor' ORDER BY username ASC");
if ($instructors_result) {
    while ($row = mysqli_fetch_assoc($instructors_result)) {
        $instructors[(int) $row['id']] = $row['username'];
    }
    mysqli_free_result($instructors_result);
}

$specialtiesByInstructor = [];
$specialty_result = mysqli_query($conn, 'SELECT user_id, class_type FROM instructor_specialties ORDER BY class_type ASC');
if ($specialty_result) {
    while ($row = mysqli_fetch_assoc($specialty_result)) {
        $userId = (int) $row['user_id'];
        if (!isset($specialtiesByInstructor[$userId])) {
            $specialtiesByInstructor[$userId] = [];
        }
        if (!in_array($row['class_type'], $specialtiesByInstructor[$userId], true)) {
            $specialtiesByInstructor[$userId][] = $row['class_type'];
        }
    }
    mysqli_free_result($specialty_result);
}
foreach ($specialtiesByInstructor as &$list) {
    sort($list);
}
unset($list);

$categoriesByUsername = [];
foreach ($instructors as $id => $username) {
    $categories = $specialtiesByInstructor[$id] ?? [];
    $categoriesByUsername[$username] = $categories;
}

$currentInstructorCategories = [];
if ($_SESSION['role'] === 'instructor') {
    $currentInstructorCategories = $specialtiesByInstructor[(int) $_SESSION['user_id']] ?? [];
}

$defaultTrainerUsername = '';
if ($_SESSION['role'] === 'admin' && !empty($instructors)) {
    foreach ($instructors as $username) {
        $defaultTrainerUsername = $username;
        break;
    }
}

$initialCategoriesForForm = [];
if ($_SESSION['role'] === 'admin' && $defaultTrainerUsername !== '') {
    $initialCategoriesForForm = $categoriesByUsername[$defaultTrainerUsername] ?? [];
}

$publishDisabled = false;
$publishDisabledReason = '';
if ($_SESSION['role'] === 'instructor' && empty($currentInstructorCategories)) {
    $publishDisabled = true;
    $publishDisabledReason = 'Select at least one specialty before publishing a class.';
}
if (!$publishDisabled && $_SESSION['role'] === 'admin' && empty($instructors)) {
    $publishDisabled = true;
    $publishDisabledReason = 'Add an instructor before publishing a class.';
} elseif (!$publishDisabled && $_SESSION['role'] === 'admin' && empty($initialCategoriesForForm)) {
    $publishDisabled = true;
    $publishDisabledReason = 'Assign specialties to the selected instructor.';
}

if (isset($_GET['delete_id'])) {
    if ($_SESSION['role'] === 'admin') {
        $id = (int) $_GET['delete_id'];
        $class_info_res = mysqli_query($conn, "SELECT title FROM classes WHERE id=$id LIMIT 1");
        $class_info = $class_info_res ? mysqli_fetch_assoc($class_info_res) : null;
        if ($class_info_res) {
            mysqli_free_result($class_info_res);
        }
        $notificationHandler->notifyCancelledClass($id, $class_info['title'] ?? '', 'Canceled by administrator');
        mysqli_query($conn, "DELETE FROM classes WHERE id=$id");
        header('Location: admin.php');
        exit;
    } else {
        $message = 'Only administrators can delete a class.';
        $message_type = 'error';
    }
}

include 'header.php';
?>

<style>
    .page-shell {max-width: 1100px; margin: 0 auto; padding: 24px; background: #fff;}
    .page-shell h1 {margin: 0 0 12px 0; font-size: 26px;}
    .helper {color: #666; margin-bottom: 16px;}
    .section {border: 1px solid #e0e0e0; background: #fafafa; padding: 16px; border-radius: 6px; margin-bottom: 16px;}
    .section h2 {margin: 0 0 8px 0; font-size: 20px;}
    .section p {margin: 0 0 10px 0; color: #555;}
    .stack {display: flex; flex-direction: column; gap: 10px;}
    .field {display: flex; flex-direction: column; gap: 6px;}
    label {font-weight: 600; font-size: 14px;}
    input[type="text"], input[type="email"], input[type="number"], input[type="url"], input[type="datetime-local"], select, textarea {padding: 8px; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 14px;}
    textarea {min-height: 80px;}
    .btn {padding: 10px 14px; background: #222; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;}
    .btn.secondary {background: #0b6bcb;}
    .btn.ghost {background: #f0f0f0; color: #222;}
    .note {padding: 10px; border-radius: 4px; margin-bottom: 12px;}
    .note.success {background: #e6f7e6; color: #1e6b1e; border: 1px solid #c5e6c5;}
    .note.error {background: #ffecec; color: #b80000; border: 1px solid #ffb3b3;}
    .table {width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 14px;}
    .table th, .table td {border: 1px solid #e0e0e0; padding: 8px; text-align: left;}
    .table th {background: #f3f3f3;}
    .empty {padding: 12px; color: #666;}
    .badge {display: inline-block; padding: 3px 8px; background: #eef3ff; color: #2d4fa3; border-radius: 4px; font-size: 12px; font-weight: 600;}
    .grid {display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 12px;}
    .small-text {color: #666; font-size: 12px;}
</style>

<div class="page-shell">
    <h1><?php echo $_SESSION['role'] === 'admin' ? 'Admin Panel' : 'Trainer Panel'; ?></h1>
    <div class="helper">Basitleştirilmiş admin sayfası. Rol atama, ders oluşturma ve listeleme işlemleri aynen devam.</div>

    <?php if ($message): ?>
        <div class="note <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($_SESSION['role'] === 'admin'): ?>
        <div class="section">
            <h2>User Management</h2>
            <p>Kullanıcı rolünü Member veya Instructor olarak güncelle.</p>
            <form method="GET" class="stack" style="max-width: 360px;">
                <div class="field">
                    <label for="user_search">Search username</label>
                    <input type="text" id="user_search" name="user_search" value="<?php echo htmlspecialchars($user_search_query); ?>" placeholder="username">
                </div>
                <div class="inline" style="display:flex; gap:8px;">
                    <button class="btn secondary" type="submit">Search</button>
                    <?php if ($user_search_query !== ''): ?>
                        <a class="btn ghost" href="admin.php">Reset</a>
                    <?php endif; ?>
                </div>
            </form>

            <?php if ($user_search_query !== ''): ?>
                <?php if (!empty($user_search_results)): ?>
                    <table class="table" style="margin-top:12px;">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Update</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($user_search_results as $user): ?>
                                <tr>
                                    <td>#<?php echo str_pad($user['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo $user['role'] === 'instructor' ? 'Instructor' : 'Member'; ?></td>
                                    <td>
                                        <form method="POST" class="stack" style="gap:6px;">
                                            <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                                            <input type="hidden" name="search_query" value="<?php echo htmlspecialchars($user_search_query); ?>">
                                            <select name="new_role" style="min-width:140px;">
                                                <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>Member</option>
                                                <option value="instructor" <?php echo $user['role'] === 'instructor' ? 'selected' : ''; ?>>Instructor</option>
                                            </select>
                                            <button class="btn ghost" type="submit" name="update_role">Save</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty">No results for "<?php echo htmlspecialchars($user_search_query); ?>".</div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="section">
        <h2>Create New Class</h2>
        <p>Hızlı ders ekle: eğitmen, kategori, tarih, link.</p>
        <form method="POST" class="stack">
            <input type="hidden" name="create_class" value="1">
            <div class="grid">
                <div class="field">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" placeholder="Morning Yoga" required>
                </div>
                <div class="field">
                    <label for="trainer">Instructor</label>
                    <?php if ($_SESSION['role'] === 'instructor'): ?>
                        <input type="hidden" name="trainer" value="<?php echo htmlspecialchars($_SESSION['username']); ?>">
                        <input type="text" id="trainer_display" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" readonly>
                        <span class="small-text">Your registered name</span>
                    <?php else: ?>
                        <?php if (empty($instructors)): ?>
                            <select id="trainer" name="trainer" disabled>
                                <option value="">No instructors</option>
                            </select>
                            <span class="small-text" style="color:#b80000;">Assign instructor role first.</span>
                        <?php else: ?>
                            <select id="trainer" name="trainer" required>
                                <?php foreach ($instructors as $instructorUsername): ?>
                                    <option value="<?php echo htmlspecialchars($instructorUsername); ?>" <?php echo $defaultTrainerUsername === $instructorUsername ? 'selected' : ''; ?>><?php echo htmlspecialchars($instructorUsername); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="field">
                    <label for="class_type">Category</label>
                    <?php if ($_SESSION['role'] === 'instructor'): ?>
                        <?php if (empty($currentInstructorCategories)): ?>
                            <input type="text" value="No categories" readonly>
                            <span class="small-text" style="color:#b80000;">Assign specialties in profile first.</span>
                        <?php elseif (count($currentInstructorCategories) === 1): ?>
                            <input type="hidden" name="class_type" value="<?php echo htmlspecialchars($currentInstructorCategories[0]); ?>">
                            <input type="text" value="<?php echo htmlspecialchars($currentInstructorCategories[0]); ?>" readonly>
                            <span class="small-text">Auto-selected from your specialties.</span>
                        <?php else: ?>
                            <select id="class_type" name="class_type" required>
                                <?php foreach ($currentInstructorCategories as $categoryOption): ?>
                                    <option value="<?php echo htmlspecialchars($categoryOption); ?>"><?php echo htmlspecialchars($categoryOption); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if (empty($instructors)): ?>
                            <select id="class_type" name="class_type" disabled>
                                <option value="">No categories</option>
                            </select>
                            <span id="category-select-message" class="small-text" style="color:#b80000;">Assign specialties first.</span>
                        <?php else: ?>
                            <select id="class_type" name="class_type" <?php echo empty($initialCategoriesForForm) ? 'disabled' : 'required'; ?>></select>
                            <span id="category-select-message" class="small-text"><?php echo empty($initialCategoriesForForm) ? 'Assign specialties to selected instructor.' : 'Instructor specialties only.'; ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="field">
                    <label for="capacity">Capacity</label>
                    <input type="number" id="capacity" name="capacity" value="10" min="1" max="50" required>
                </div>
                <div class="field">
                    <label for="date_time">Date & Time</label>
                    <input type="datetime-local" id="date_time" name="date_time" required>
                </div>
                <div class="field">
                    <label for="video_link">Video Link</label>
                    <input type="url" id="video_link" name="video_link" placeholder="https://zoom.us/..." required>
                </div>
            </div>
            <div class="field">
                <label for="description">Description</label>
                <textarea id="description" name="description" placeholder="Short class info" required></textarea>
            </div>
            <button class="btn" id="publish-class-button" type="submit" <?php echo $publishDisabled ? 'disabled' : ''; ?>>Publish</button>
            <?php if ($publishDisabled): ?>
                <span class="small-text" style="color:#b80000;"><?php echo htmlspecialchars($publishDisabledReason); ?></span>
            <?php endif; ?>
        </form>
    </div>

    <div class="section">
        <h2>Active Classes</h2>
        <p>Yaklaşan dersler listesi.</p>
        <?php
        $upcoming = mysqli_query($conn, "SELECT * FROM classes WHERE date_time >= NOW() ORDER BY date_time ASC");
        ?>
        <?php if ($upcoming && mysqli_num_rows($upcoming) > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Trainer</th>
                        <th>Date</th>
                        <th>Capacity</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($upcoming)): ?>
                        <?php $class_date = new DateTime($row['date_time']); ?>
                        <tr>
                            <td>#<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><span class="badge"><?php echo htmlspecialchars($row['class_type']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['trainer_name']); ?></td>
                            <td><?php echo $class_date->format('d.m.Y H:i'); ?></td>
                            <td><?php echo (int) $row['capacity']; ?></td>
                            <td>
                                <a class="small-text" href="class_edit.php?id=<?php echo (int) $row['id']; ?>">Edit</a>
                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                    | <a class="small-text" href="admin.php?delete_id=<?php echo (int) $row['id']; ?>" onclick="return confirm('Delete this class?');">Delete</a>
                                <?php else: ?>
                                    | <span class="small-text">Locked</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php mysqli_free_result($upcoming); ?>
        <?php else: ?>
            <div class="empty">No upcoming classes.</div>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>Past Classes</h2>
        <p>Tamamlanan dersler.</p>
        <?php
        $past = mysqli_query($conn, "SELECT * FROM classes WHERE date_time < NOW() ORDER BY date_time DESC");
        ?>
        <?php if ($past && mysqli_num_rows($past) > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Trainer</th>
                        <th>Date</th>
                        <th>Capacity</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($past)): ?>
                        <?php $class_date = new DateTime($row['date_time']); ?>
                        <tr>
                            <td>#<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><span class="badge"><?php echo htmlspecialchars($row['class_type']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['trainer_name']); ?></td>
                            <td><?php echo $class_date->format('d.m.Y H:i'); ?></td>
                            <td><?php echo (int) $row['capacity']; ?></td>
                            <td>
                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                    <a class="small-text" href="admin.php?delete_id=<?php echo (int) $row['id']; ?>" onclick="return confirm('Delete this class?');">Delete</a>
                                <?php else: ?>
                                    <span class="small-text">Locked</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php mysqli_free_result($past); ?>
        <?php else: ?>
            <div class="empty">No past classes.</div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var categoriesByUsername = <?php echo json_encode($categoriesByUsername, JSON_UNESCAPED_UNICODE); ?>;
    var trainerSelect = document.getElementById('trainer');
    var categorySelect = document.getElementById('class_type');
    var categoryMessage = document.getElementById('category-select-message');
    var publishButton = document.getElementById('publish-class-button');

    function syncCategoryOptions(categories) {
        if (!categorySelect) {
            return;
        }
        while (categorySelect.firstChild) {
            categorySelect.removeChild(categorySelect.firstChild);
        }
        if (!categories || categories.length === 0) {
            categorySelect.disabled = true;
            var option = document.createElement('option');
            option.value = '';
            option.textContent = 'No categories available';
            categorySelect.appendChild(option);
            if (categoryMessage) {
                categoryMessage.textContent = 'Assign specialties to the selected instructor before publishing.';
                categoryMessage.style.color = '#b80000';
            }
            if (publishButton) {
                publishButton.disabled = true;
            }
            return;
        }
        categories.forEach(function (category) {
            var option = document.createElement('option');
            option.value = category;
            option.textContent = category;
            categorySelect.appendChild(option);
        });
        categorySelect.disabled = false;
        categorySelect.value = categories[0];
        if (categoryMessage) {
            categoryMessage.textContent = 'Instructor specialties only.';
            categoryMessage.style.color = '#555';
        }
        if (publishButton) {
            publishButton.disabled = false;
        }
    }

    if (trainerSelect && categorySelect) {
        var initialCategories = categoriesByUsername[trainerSelect.value] || [];
        syncCategoryOptions(initialCategories);
        trainerSelect.addEventListener('change', function () {
            var selectedCategories = categoriesByUsername[this.value] || [];
            syncCategoryOptions(selectedCategories);
        });
    }
});
</script>

<?php include 'footer.php'; ?>
