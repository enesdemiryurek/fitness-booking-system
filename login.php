<?php
session_start();
require 'db.php';
$page_title = "Login | GYM";

$message = '';
$message_type = '';

if (isset($_SESSION['register_success'])) {
    $message = $_SESSION['register_success'];
    $message_type = 'success';
    unset($_SESSION['register_success']);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid e-mail address.';
        $message_type = 'error';
    } elseif ($password === '') {
        $message = 'Please enter your password.';
        $message_type = 'error';
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT id, username, role, password FROM users WHERE email = ? LIMIT 1');

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $db_id, $db_username, $db_role, $db_password);

            if (mysqli_stmt_fetch($stmt)) {
                $password_ok = false;
                $update_hash = false;

                if (password_verify($password, $db_password)) {
                    $password_ok = true;
                    if (password_needs_rehash($db_password, PASSWORD_DEFAULT)) {
                        $update_hash = true;
                    }
                } elseif ($password === $db_password) {
                    $password_ok = true;
                    $update_hash = true;
                }

                mysqli_stmt_close($stmt);

                if ($password_ok) {
                    if ($update_hash) {
                        $new_hash = password_hash($password, PASSWORD_DEFAULT);
                        $update_stmt = mysqli_prepare($conn, 'UPDATE users SET password = ? WHERE id = ?');
                        if ($update_stmt) {
                            mysqli_stmt_bind_param($update_stmt, 'si', $new_hash, $db_id);
                            mysqli_stmt_execute($update_stmt);
                            mysqli_stmt_close($update_stmt);
                        }
                    }

                    $_SESSION['user_id'] = $db_id;
                    $_SESSION['username'] = $db_username;
                    $_SESSION['role'] = $db_role;

                    header('Location: index.php');
                    exit;
                }

                $message = 'Incorrect email or password!';
                $message_type = 'error';
            } else {
                mysqli_stmt_close($stmt);
                $message = 'Incorrect email or password!';
                $message_type = 'error';
            }
        } else {
            $message = 'A database error prevented login. Please try again later.';
            $message_type = 'error';
        }
    }
}

include 'header.php';
?>

    <div class="split-card" style=" margin: 40px auto; max-width: 900px;">
        
        <div class="form-side">
            <div class="form-header">
                <h2>Welcome Back!</h2>
                <p>Log in to your account and continue your sports where you left off.</p>
            </div>

            <?php if ($message): ?>
                <?php
                $color = $message_type === 'success' ? '#1b5e20' : '#b71c1c';
                $background = $message_type === 'success' ? '#e8f5e9' : '#ffebee';
                ?>
                <p style="color: <?php echo $color; ?>; text-align: center; background: <?php echo $background; ?>; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                    <?php echo htmlspecialchars($message); ?>
                </p>
            <?php endif; ?>

            <form action="" method="POST">
                
                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="blue-input" placeholder="example@mail.com" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" class="blue-input" placeholder="******" required>
                </div>

                <button type="submit" class="btn-blue">Login</button>
            </form>

            <div class="back-link">
                <a href="forgot_password.php" style="color:#ff6b6b; font-weight:bold; font-size:14px;">Forgot Your Password?</a>
            </div>

            <div class="back-link">
                Don't have an account? <a href="register.php">Register Now</a>
            </div>
            <div class="back-link" style="margin-top:10px;">
                <a href="index.php" style="color:#999; font-weight:normal;">Return to Home</a>
            </div>
        </div>

        <div class="image-side" style="background-image: url('https://images.unsplash.com/photo-1599058945522-28d584b6f0ff?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');">
            <div class="image-overlay">
                <div class="testimonial-stars">5 Star Rating</div>
                <p class="testimonial-text">"Consistency is the key to success. We're here to be 1% better every day."</p>
                <p class="testimonial-author">BABA PRO GYM TEAM</p>
            </div>
        </div>

    </div>

    <?php include 'footer.php'; ?>