<?php
session_start();
include 'db.php';
$page_title = "Sign Up | GYM";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Formdan gelen veriler
    $ad = $_POST['first_name'];
    $soyad = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone']; 
    $password = $_POST['password'];
    
    // YENİ EKLENENLER: Yaş ve Cinsiyet
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    
    // Ad ve Soyadı birleştirip username yapıyoruz
    $full_username = $ad . " " . $soyad;

    // E-posta kontrolü
    $check = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $check);

    if (mysqli_num_rows($result) > 0) {
        $message = "⚠️ This email address is already registered!";
    } else {
        // VERİTABANI KAYDI (Yaş ve Cinsiyet Sütunları Eklendi)
        $sql = "INSERT INTO users (username, email, phone, age, gender, password, role) 
                VALUES ('$full_username', '$email', '$phone', '$age', '$gender', '$password', 'user')";
        
        if (mysqli_query($conn, $sql)) {
            echo "<script>alert('✅ Registration Successful! Welcome to our community.'); window.location.href='login.php';</script>";
        } else {
            $message = "Error: " . mysqli_error($conn);
        }
    }
}

include 'header.php';
?>

    <div class="split-card" style="margin: 40px auto; max-width: 900px;">
        
        <!-- SOL TARAF: FORM -->
        <div class="form-side">
            <div class="form-header">
                <h2>Start Free</h2>
                <p>No credit card required, start your fitness journey now.</p>
            </div>

            <?php if($message) echo "<p style='color:red; text-align:center; margin-bottom:10px;'>$message</p>"; ?>

            <form action="" method="POST">
                
                <!-- Ad ve Soyad (Yan Yana) -->
                <div class="split-inputs">
                    <div class="input-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" class="blue-input" required>
                    </div>
                    <div class="input-group">
                        <label>Last Name</label>
                        <input type="text" name="last_name" class="blue-input" required>
                    </div>
                </div>

                <!-- E-posta -->
                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="blue-input" required>
                </div>

                <!-- Telefon -->
                <div class="input-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" class="blue-input" placeholder="0555 555 55 55" required>
                </div>

                <!-- YENİ: Yaş ve Cinsiyet (Yan Yana) -->
                <div class="split-inputs">
                    <div class="input-group">
                        <label>Age</label>
                        <input type="number" name="age" class="blue-input" placeholder="22" required>
                    </div>
                    <div class="input-group">
                        <label>Gender</label>
                        <select name="gender" class="blue-input" style="background-color:white;" required>
                            <option value="" disabled selected>Select</option>
                            <option value="Erkek">Male</option>
                            <option value="Kadın">Female</option>
                            <option value="Belirtmek İstemiyorum">Other</option>
                        </select>
                    </div>
                </div>

                <!-- Şifre -->
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" class="blue-input" required>
                </div>

                <button type="submit" class="btn-blue">Continue</button>
            </form>

            <div class="back-link">
                Already have an account? <a href="login.php">Login</a>
            </div>
            <div class="back-link" style="margin-top:10px;">
                <a href="index.php" style="color:#999; font-weight:normal;">← Return to Home</a>
            </div>
        </div>

        <!-- SAĞ TARAF: RESİM -->
        <div class="image-side">
            <div class="image-overlay">
                <div class="testimonial-stars">★★★★★</div>
                <p class="testimonial-text">"Since I started using this app, I never miss my workouts. The instructors are very attentive and the system works great!"</p>
                <p class="testimonial-author">Mert Yılmaz<br><small>Fitness Member</small></p>
            </div>
        </div>

    </div>

    <?php include 'footer.php'; ?>  