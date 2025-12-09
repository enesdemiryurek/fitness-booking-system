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

$page_title = "Admin Panel | GYM";

// 1. GÜVENLİK DUVARI: Admin VEYA Instructor girebilir
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'instructor')) {
    die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'><h1>Unauthorized Access!</h1><p>Only administrators and instructors can access this page.</p><a href='index.php'>Back Homepage</a></div>");
}

$message = "";
$message_type = "";
$user_search_query = '';
$user_search_results = [];
$lastSpecialtyInstructorId = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['update_role']) && $_SESSION['role'] == 'admin') {
        $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
        $new_role = $_POST['new_role'] ?? '';
        $user_search_query = trim($_POST['search_query'] ?? '');
        $allowed_roles = ['user', 'instructor'];

        if ($user_id > 0 && in_array($new_role, $allowed_roles, true)) {
            $stmt = mysqli_prepare($conn, "UPDATE users SET role = ? WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'si', $new_role, $user_id);
                if (mysqli_stmt_execute($stmt)) {
                    $message = "User role updated successfully.";
                    $message_type = "success";
                } else {
                    $message = "Error: Unable to update user role.";
                    $message_type = "error";
                }
                mysqli_stmt_close($stmt);
            } else {
                $message = "Error: Unable to prepare role update.";
                $message_type = "error";
            }
        } else {
            $message = "Invalid user or role selection.";
            $message_type = "error";
        }
    } elseif (isset($_POST['update_specialties'])) {
        $targetInstructorId = $_SESSION['role'] === 'instructor'
            ? (int) $_SESSION['user_id']
            : (int) ($_POST['instructor_id'] ?? 0);

        if ($_SESSION['role'] === 'instructor' && $targetInstructorId !== (int) $_SESSION['user_id']) {
            $message = "You are not allowed to update another instructor's specialties.";
            $message_type = "error";
        } elseif ($targetInstructorId <= 0) {
            $message = "Please select a valid instructor.";
            $message_type = "error";
        } else {
            $isValidInstructor = true;
            if ($_SESSION['role'] === 'admin') {
                $verify = mysqli_prepare($conn, "SELECT id FROM users WHERE id = ? AND role = 'instructor' LIMIT 1");
                if ($verify) {
                    mysqli_stmt_bind_param($verify, 'i', $targetInstructorId);
                    mysqli_stmt_execute($verify);
                    mysqli_stmt_store_result($verify);
                    if (mysqli_stmt_num_rows($verify) === 0) {
                        $isValidInstructor = false;
                    }
                    mysqli_stmt_close($verify);
                } else {
                    $isValidInstructor = false;
                }
            }

            if (!$isValidInstructor) {
                $message = "The selected instructor could not be found.";
                $message_type = "error";
            } else {
                $selected = isset($_POST['specialties']) && is_array($_POST['specialties']) ? $_POST['specialties'] : [];
                $selected = array_values(array_intersect($selected, $category_keys));

                $updateSuccess = true;
                $delete = mysqli_prepare($conn, "DELETE FROM instructor_specialties WHERE user_id = ?");
                if ($delete) {
                    mysqli_stmt_bind_param($delete, 'i', $targetInstructorId);
                    if (!mysqli_stmt_execute($delete)) {
                        $updateSuccess = false;
                    }
                    mysqli_stmt_close($delete);
                } else {
                    $updateSuccess = false;
                }

                if ($updateSuccess && !empty($selected)) {
                    $insert = mysqli_prepare($conn, "INSERT INTO instructor_specialties (user_id, class_type) VALUES (?, ?)");
                    if ($insert) {
                        foreach ($selected as $category) {
                            mysqli_stmt_bind_param($insert, 'is', $targetInstructorId, $category);
                            if (!mysqli_stmt_execute($insert)) {
                                $updateSuccess = false;
                                break;
                            }
                        }
                        mysqli_stmt_close($insert);
                    } else {
                        $updateSuccess = false;
                    }
                }

                if ($updateSuccess) {
                    $message = "Instructor specialties updated.";
                    $message_type = "success";
                    $lastSpecialtyInstructorId = $targetInstructorId;
                } else {
                    $message = "Unable to update instructor specialties. Please try again.";
                    $message_type = "error";
                }
            }
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
            $errors[] = "Please select a valid instructor.";
        }
        if ($title === '' || $description === '' || $date === '' || $link === '') {
            $errors[] = "All course fields are required.";
        }
        if ($capacity <= 0) {
            $errors[] = "Capacity must be at least 1.";
        }

        $allowedCategories = $trainerId > 0 ? adminPanelGetInstructorSpecialties($conn, $trainerId) : [];
        if (empty($allowedCategories)) {
            $errors[] = "This instructor does not have any assigned categories. Update specialties first.";
        } elseif (!in_array($type, $allowedCategories, true)) {
            $errors[] = "Selected category is not allowed for this instructor.";
        }

        if ($type === '' || !in_array($type, $category_keys, true)) {
            $errors[] = "Invalid course category selected.";
        }

        if (empty($errors)) {
            $stmt = mysqli_prepare($conn, "INSERT INTO classes (title, trainer_name, description, class_type, date_time, capacity, video_link) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'sssssis', $title, $trainerUsername, $description, $type, $date, $capacity, $link);
                if (mysqli_stmt_execute($stmt)) {
                    $class_id = mysqli_insert_id($conn);
                    $notificationHandler->notifyNewClass($class_id, $title, $type, $trainerUsername, $date);
                    $message = "Course added successfully.";
                    $message_type = "success";
                } else {
                    $message = "Error: Unable to add the course.";
                    $message_type = "error";
                }
                mysqli_stmt_close($stmt);
            } else {
                $message = "Error: Unable to prepare course creation.";
                $message_type = "error";
            }
        } else {
            $message = implode(' ', $errors);
            $message_type = "error";
        }
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

// Eğitmen listesi ve uzmanlıkları
$instructors = [];
$instructors_result = mysqli_query($conn, "SELECT id, username FROM users WHERE role = 'instructor' ORDER BY username ASC");
if ($instructors_result) {
    while ($row = mysqli_fetch_assoc($instructors_result)) {
        $instructors[(int) $row['id']] = $row['username'];
    }
    mysqli_free_result($instructors_result);
}

$specialtiesByInstructor = [];
$specialty_result = mysqli_query($conn, "SELECT user_id, class_type FROM instructor_specialties ORDER BY class_type ASC");
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
$categoriesById = [];
foreach ($instructors as $id => $username) {
    $categories = $specialtiesByInstructor[$id] ?? [];
    $categoriesByUsername[$username] = $categories;
    $categoriesById[$id] = $categories;
}

$selectedInstructorIdForSpecialties = null;
if ($_SESSION['role'] === 'instructor') {
    $selectedInstructorIdForSpecialties = (int) $_SESSION['user_id'];
} else {
    if ($lastSpecialtyInstructorId !== null && isset($instructors[$lastSpecialtyInstructorId])) {
        $selectedInstructorIdForSpecialties = $lastSpecialtyInstructorId;
    } elseif (isset($_POST['instructor_id']) && isset($instructors[(int) $_POST['instructor_id']])) {
        $selectedInstructorIdForSpecialties = (int) $_POST['instructor_id'];
    } elseif (isset($_GET['specialty_instructor']) && isset($instructors[(int) $_GET['specialty_instructor']])) {
        $selectedInstructorIdForSpecialties = (int) $_GET['specialty_instructor'];
    } elseif (!empty($instructors)) {
        foreach ($instructors as $id => $_name) {
            $selectedInstructorIdForSpecialties = $id;
            break;
        }
    }
}

$selectedInstructorSpecialties = $selectedInstructorIdForSpecialties ? ($specialtiesByInstructor[$selectedInstructorIdForSpecialties] ?? []) : [];
$currentInstructorCategories = [];
if ($_SESSION['role'] === 'instructor') {
    $currentInstructorCategories = $specialtiesByInstructor[(int) $_SESSION['user_id']] ?? [];
}

$defaultTrainerIdForForm = null;
$defaultTrainerUsername = '';
if ($_SESSION['role'] === 'admin' && !empty($instructors)) {
    foreach ($instructors as $id => $username) {
        $defaultTrainerIdForForm = $id;
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
    $publishDisabledReason = 'Please select at least one specialty before publishing a class.';
}
if (!$publishDisabled && $_SESSION['role'] === 'admin' && empty($instructors)) {
    $publishDisabled = true;
    $publishDisabledReason = 'Add at least one instructor before publishing a class.';
} elseif (!$publishDisabled && $_SESSION['role'] === 'admin' && empty($initialCategoriesForForm)) {
    $publishDisabled = true;
    $publishDisabledReason = 'Assign specialties to the selected instructor before publishing a class.';
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

        <div class="form-section">
            <div class="section-header">
                <h2>Instructor Specialties</h2>
                <p>Select which categories each instructor is allowed to teach.</p>
            </div>

            <?php if($_SESSION['role'] == 'admin' && empty($instructors)): ?>
                <div class="empty-state">Assign the instructor role to at least one user to configure specialties.</div>
            <?php else: ?>
                <form method="POST" class="modern-form">
                    <input type="hidden" name="update_specialties" value="1">

                    <?php if($_SESSION['role'] == 'admin'): ?>
                        <div class="form-group">
                            <label for="specialty_instructor">Instructor</label>
                            <select id="specialty_instructor" name="instructor_id" required>
                                <?php foreach ($instructors as $instructorId => $instructorUsername): ?>
                                    <option value="<?php echo $instructorId; ?>" <?php echo ($selectedInstructorIdForSpecialties === $instructorId) ? 'selected' : ''; ?>><?php echo htmlspecialchars($instructorUsername); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small>Select an instructor to manage their specialties.</small>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="instructor_id" value="<?php echo (int) $_SESSION['user_id']; ?>">
                        <div class="form-group">
                            <label>Instructor</label>
                            <input type="text" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" readonly class="input-readonly">
                            <small>Choose the categories you can teach.</small>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Allowed Categories</label>
                        <div class="specialty-grid" style="display:flex; flex-wrap:wrap; gap:12px; margin-top:6px;">
                            <?php foreach ($class_categories as $categoryValue => $categoryLabel): ?>
                                <label class="specialty-checkbox" style="display:flex; align-items:center; gap:8px; padding:8px 12px; background:#f6f7fb; border-radius:6px; border:1px solid #e5e7ef;">
                                    <input type="checkbox" name="specialties[]" value="<?php echo htmlspecialchars($categoryValue); ?>" <?php echo in_array($categoryValue, $selectedInstructorSpecialties, true) ? 'checked' : ''; ?>>
                                    <span><?php echo htmlspecialchars($categoryLabel); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <small>Course categories will be restricted to this selection.</small>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn-action-small btn-edit">Save Categories</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <!-- YENİ DERS FORMU -->
        <div class="form-section">
            <div class="section-header">
                <h2>Create New Lesson</h2>
                <p>Get students involved by adding a new course to the system</p>
            </div>

            <form action="" method="POST" class="modern-form">
                <input type="hidden" name="create_class" value="1">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="title">Course Title</label>
                        <input type="text" id="title" name="title" placeholder="Ex: Morning Yoga" required>
                        <small>Example: Introduction to Pilates Basics</small>
                    </div>

                    <div class="form-group">
                        <label for="trainer">Instructor Name</label>
                        <?php if($_SESSION['role'] == 'instructor'): ?>
                            <input type="hidden" name="trainer" value="<?php echo htmlspecialchars($_SESSION['username']); ?>">
                            <input type="text" id="trainer_display" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" readonly class="input-readonly">
                            <small>Your registered name in the system</small>
                        <?php else: ?>
                            <?php if(empty($instructors)): ?>
                                <select id="trainer" name="trainer" disabled>
                                    <option value="">No instructors available</option>
                                </select>
                                <small style="color:#c0392b;">Assign the instructor role and specialties before creating a class.</small>
                            <?php else: ?>
                                <select id="trainer" name="trainer" required>
                                    <?php foreach ($instructors as $instructorId => $instructorUsername): ?>
                                        <option value="<?php echo htmlspecialchars($instructorUsername); ?>" <?php echo ($defaultTrainerUsername === $instructorUsername) ? 'selected' : ''; ?>><?php echo htmlspecialchars($instructorUsername); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small>Select the instructor who will manage the class.</small>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="class_type">Category</label>
                        <?php if($_SESSION['role'] == 'instructor'): ?>
                            <?php if(empty($currentInstructorCategories)): ?>
                                <input type="text" value="No categories available" readonly class="input-readonly">
                                <small style="color:#c0392b;">Select your teaching categories above before publishing a class.</small>
                            <?php elseif(count($currentInstructorCategories) === 1): ?>
                                <input type="hidden" name="class_type" value="<?php echo htmlspecialchars($currentInstructorCategories[0]); ?>">
                                <input type="text" value="<?php echo htmlspecialchars($currentInstructorCategories[0]); ?>" readonly class="input-readonly">
                                <small>Category automatically assigned based on your specialties.</small>
                            <?php else: ?>
                                <select id="class_type" name="class_type" required>
                                    <?php foreach ($currentInstructorCategories as $categoryOption): ?>
                                        <option value="<?php echo htmlspecialchars($categoryOption); ?>"><?php echo htmlspecialchars($categoryOption); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small>Only categories linked to your profile are available.</small>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if(empty($instructors)): ?>
                                <select id="class_type" name="class_type" disabled>
                                    <option value="">No categories available</option>
                                </select>
                                <small id="category-select-message" style="color:#c0392b;">Assign specialties to an instructor before publishing a class.</small>
                            <?php else: ?>
                                <select id="class_type" name="class_type" <?php echo empty($initialCategoriesForForm) ? 'disabled' : 'required'; ?>>
                                    <?php if(empty($initialCategoriesForForm)): ?>
                                        <option value="">No categories available</option>
                                    <?php else: ?>
                                        <?php foreach ($initialCategoriesForForm as $categoryOption): ?>
                                            <option value="<?php echo htmlspecialchars($categoryOption); ?>"><?php echo htmlspecialchars($categoryOption); ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <small id="category-select-message"><?php echo empty($initialCategoriesForForm) ? 'Assign specialties to the selected instructor before publishing a class.' : 'Category is limited to the instructor specialties.'; ?></small>
                            <?php endif; ?>
                        <?php endif; ?>
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
                        <button type="submit" id="publish-class-button" class="btn-submit-large" <?php echo $publishDisabled ? 'disabled' : ''; ?>>Publish Class</button>
                        <?php if($publishDisabled): ?>
                            <small style="color:#c0392b; display:block; margin-top:6px;">&bull; <?php echo htmlspecialchars($publishDisabledReason); ?></small>
                        <?php endif; ?>
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

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var categoriesByUsername = <?php echo json_encode($categoriesByUsername, JSON_UNESCAPED_UNICODE); ?>;
        var categoriesById = <?php echo json_encode($categoriesById, JSON_UNESCAPED_UNICODE); ?>;
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
                    categoryMessage.textContent = 'Assign specialties to the selected instructor before publishing a class.';
                    categoryMessage.style.color = '#c0392b';
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
                categoryMessage.textContent = 'Category is limited to the instructor specialties.';
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

        var specialtySelect = document.getElementById('specialty_instructor');
        if (specialtySelect) {
            var checkboxes = document.querySelectorAll('.specialty-grid input[type="checkbox"]');
            function syncSpecialtyCheckboxes(instructorId) {
                var allowed = categoriesById[String(instructorId)] || [];
                checkboxes.forEach(function (checkbox) {
                    checkbox.checked = allowed.indexOf(checkbox.value) !== -1;
                });
            }

            syncSpecialtyCheckboxes(specialtySelect.value);

            specialtySelect.addEventListener('change', function () {
                syncSpecialtyCheckboxes(this.value);
            });
        }
    });
    </script>

<?php include 'footer.php'; ?>