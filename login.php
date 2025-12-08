<?php
session_start();
include 'db.php';
$page_title = "Login | GYM";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Veritabanı kontrolü
    $sql = "SELECT * FROM users WHERE email = '$email' AND password = '$password'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        // Giriş Başarılı!
        $row = mysqli_fetch_assoc($result);
        
        // Kimlik kartını (Session) doldur
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['role'] = $row['role'];

        // Anasayfaya yolla
        header("Location: index.php");
        exit;
    } else {
        $message = "Incorrect email or password!";
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

            <?php if($message) echo "<p style='color:red; text-align:center; background:#ffebee; padding:10px; border-radius:5px; margin-bottom:15px;'>$message</p>"; ?>

            <form action="" method="POST">
                
                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="blue-input" placeholder="example@mail.com" required>
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