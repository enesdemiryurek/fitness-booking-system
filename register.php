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

<<<<<<< HEAD
    // E-posta kontrolü
    $check = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $check);

    if (mysqli_num_rows($result) > 0) {
        $message = "This email address is already registered.";
    } else {
        // VERİTABANI KAYDI (Yaş ve Cinsiyet Sütunları Eklendi)
        $sql = "INSERT INTO users (username, email, phone, age, gender, password, role) 
                VALUES ('$full_username', '$email', '$phone', '$age', '$gender', '$password', 'user')";
        
        if (mysqli_query($conn, $sql)) {
            echo "<script>alert('Registration successful! Welcome to our community.'); window.location.href='login.php';</script>";
=======
    // ŞIFRE VALIDASYONU
    $password_error = "";
    
    // Minimum 8 karakter
    if (strlen($password) < 8) {
        $password_error = "❌ Şifre en az 8 karakter olmalıdır!";
    }
    // En az 1 sayı var mı?
    elseif (!preg_match('/[0-9]/', $password)) {
        $password_error = "❌ Şifre en az 1 rakam (0-9) içermeli!";
    }
    // En az 1 özel karakter var mı?
    elseif (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|`~]/', $password)) {
        $password_error = "❌ Şifre en az 1 özel karakter (!@#$%^&* vb.) içermeli!";
    }
    
    if ($password_error) {
        $message = $password_error;
    } else {
        // E-posta kontrolü
        $check = "SELECT * FROM users WHERE email = '$email'";
        $result = mysqli_query($conn, $check);

        if (mysqli_num_rows($result) > 0) {
            $message = "⚠️ This email address is already registered!";
>>>>>>> 09cf71a93f4d555556a5b0a16fe9f47574ffaff7
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

            <?php if($message) echo "<p style='color:red; text-align:center; background:#ffebee; padding:10px; border-radius:5px; margin-bottom:15px;'>$message</p>"; ?>

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
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Prefer not to say">Prefer not to say</option>
                        </select>
                    </div>
                </div>

                <!-- Şifre -->
                <div class="input-group">
                    <label>Password</label>
                    <div style="position: relative;">
                        <input type="password" id="password" name="password" class="blue-input" required>
                        <span id="toggle-password" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 20px; user-select: none;">👁️</span>
                    </div>
                    <small style="color: #666; display: block; margin-top: 8px;">
                        <strong>Şifre Gereksinimleri:</strong>
                        <div style="margin-top: 5px;">
                            <span id="length-check" style="color: #d32f2f; display: block;">❌ En az 8 karakter</span>
                            <span id="number-check" style="color: #d32f2f; display: block;">❌ En az 1 rakam (0-9)</span>
                            <span id="special-check" style="color: #d32f2f; display: block;">❌ En az 1 özel karakter (!@#$%^&* vb.)</span>
                        </div>
                    </small>
                </div>

                <script>
                const passwordInput = document.getElementById('password');
                const togglePassword = document.getElementById('toggle-password');
                const lengthCheck = document.getElementById('length-check');
                const numberCheck = document.getElementById('number-check');
                const specialCheck = document.getElementById('special-check');

                // Göz ikonu tıklanması
                togglePassword.addEventListener('click', function() {
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        togglePassword.textContent = '🙈';
                    } else {
                        passwordInput.type = 'password';
                        togglePassword.textContent = '👁️';
                    }
                });

                passwordInput.addEventListener('input', function() {
                    const password = this.value;

                    // 8 karakter kontrolü
                    if (password.length >= 8) {
                        lengthCheck.style.color = '#4CAF50';
                        lengthCheck.textContent = '✅ En az 8 karakter';
                    } else {
                        lengthCheck.style.color = '#d32f2f';
                        lengthCheck.textContent = '❌ En az 8 karakter';
                    }

                    // Rakam kontrolü
                    if (/[0-9]/.test(password)) {
                        numberCheck.style.color = '#4CAF50';
                        numberCheck.textContent = '✅ En az 1 rakam (0-9)';
                    } else {
                        numberCheck.style.color = '#d32f2f';
                        numberCheck.textContent = '❌ En az 1 rakam (0-9)';
                    }

                    // Özel karakter kontrolü
                    if (/[!@#$%^&*()_+\-=\[\]{};:'"",.<>?\/\\|`~]/.test(password)) {
                        specialCheck.style.color = '#4CAF50';
                        specialCheck.textContent = '✅ En az 1 özel karakter';
                    } else {
                        specialCheck.style.color = '#d32f2f';
                        specialCheck.textContent = '❌ En az 1 özel karakter';
                    }
                });
                </script>

                <button type="submit" class="btn-blue">Continue</button>
            </form>

            <div class="back-link">
                Already have an account? <a href="login.php">Login</a>
            </div>
            <div class="back-link" style="margin-top:10px;">
                <a href="index.php" style="color:#999; font-weight:normal;">Return to Home</a>
            </div>
        </div>

        <!-- SAĞ TARAF: RESİM -->
        <div class="image-side">
            <div class="image-overlay">
                <div class="testimonial-stars">5 Star Rating</div>
                <p class="testimonial-text">"Since I started using this app, I never miss my workouts. The instructors are very attentive and the system works great!"</p>
                <p class="testimonial-author">Mert Yılmaz<br><small>Fitness Member</small></p>
            </div>
        </div>

    </div>

    <?php include 'footer.php'; ?>  