<?php
session_start();
require 'db.php';

$page_title = 'Sign Up | GYM';

$values = [
    'first_name' => '',
    'last_name'  => '',
    'email'      => '',
    'phone'      => '',
    'age'        => '',
    'gender'     => '',
];

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['first_name'] = trim($_POST['first_name'] ?? '');
    $values['last_name']  = trim($_POST['last_name'] ?? '');
    $values['email']      = trim($_POST['email'] ?? '');
    $values['phone']      = trim($_POST['phone'] ?? '');
    $values['age']        = trim($_POST['age'] ?? '');
    $values['gender']     = trim($_POST['gender'] ?? '');
    $password             = $_POST['password'] ?? '';

    $errors = [];

    if ($values['first_name'] === '' || $values['last_name'] === '') {
        $errors[] = 'Please enter both first name and last name.';
    }

    if ($values['email'] === '' || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid e-mail address.';
    }

    if ($values['phone'] === '') {
        $errors[] = 'Please enter a phone number.';
    }

    $age_int = filter_var($values['age'], FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 120],
    ]);
    if ($age_int === false) {
        $errors[] = 'Please enter a valid age between 1 and 120.';
    }

    $allowed_genders = ['Male', 'Female', 'Prefer not to say'];
    if (!in_array($values['gender'], $allowed_genders, true)) {
        $errors[] = 'Please select a valid gender option.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must contain at least 8 characters.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must include at least one digit (0-9).';
    }
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'"\\|,.<>\/?`~]/', $password)) {
        $errors[] = 'Password must include at least one special character (!@#$%^&* etc.).';
    }

    if (empty($errors)) {
        $full_name = $values['first_name'] . ' ' . $values['last_name'];

        $check_stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? LIMIT 1');
        if ($check_stmt) {
            mysqli_stmt_bind_param($check_stmt, 's', $values['email']);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);

            if (mysqli_stmt_num_rows($check_stmt) > 0) {
                $errors[] = 'This e-mail address is already registered.';
            }
            mysqli_stmt_close($check_stmt);
        } else {
            $errors[] = 'A database error occurred while checking the e-mail address.';
        }
    }

    if (empty($errors)) {
            $insert_stmt = mysqli_prepare(
                $conn,
                'INSERT INTO users (username, email, phone, age, gender, password, role) VALUES (?, ?, ?, ?, ?, ?, \'user\')'
            );

        if ($insert_stmt) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            mysqli_stmt_bind_param(
                $insert_stmt,
                'sssiss',
                $full_name,
                $values['email'],
                $values['phone'],
                $age_int,
                $values['gender'],
                $hashed_password
            );

            if (mysqli_stmt_execute($insert_stmt)) {
                mysqli_stmt_close($insert_stmt);
                $_SESSION['register_success'] = 'Registration successful. You can now log in.';
                header('Location: login.php');
                exit;
            }

            $errors[] = 'A database error occurred while creating your account.';
            mysqli_stmt_close($insert_stmt);
        } else {
            $errors[] = 'A database error occurred while preparing the registration.';
        }
    }

    if (!empty($errors)) {
        $message = $errors[0];
        $message_type = 'error';
    }
}

include 'header.php';
?>

    <div class="split-card" style="margin: 40px auto; max-width: 900px;">
        <div class="form-side">
            <div class="form-header">
                <h2>Start Free</h2>
                <p>No credit card required, start your fitness journey now.</p>
            </div>

            <?php if ($message): ?>
                <p style="color: #b71c1c; text-align: center; background: #ffebee; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                    <?php echo htmlspecialchars($message); ?>
                </p>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="split-inputs">
                    <div class="input-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" class="blue-input" value="<?php echo htmlspecialchars($values['first_name']); ?>" required>
                    </div>
                    <div class="input-group">
                        <label>Last Name</label>
                        <input type="text" name="last_name" class="blue-input" value="<?php echo htmlspecialchars($values['last_name']); ?>" required>
                    </div>
                </div>

                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="blue-input" value="<?php echo htmlspecialchars($values['email']); ?>" required>
                </div>

                <div class="input-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" class="blue-input" placeholder="0555 555 55 55" value="<?php echo htmlspecialchars($values['phone']); ?>" required>
                </div>

                <div class="split-inputs">
                    <div class="input-group">
                        <label>Age</label>
                        <input type="number" name="age" class="blue-input" placeholder="22" value="<?php echo htmlspecialchars($values['age']); ?>" required>
                    </div>
                    <div class="input-group">
                        <label>Gender</label>
                        <select name="gender" class="blue-input" style="background-color: white;" required>
                            <option value="" disabled <?php echo $values['gender'] === '' ? 'selected' : ''; ?>>Select</option>
                            <option value="Male" <?php echo $values['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo $values['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Prefer not to say" <?php echo $values['gender'] === 'Prefer not to say' ? 'selected' : ''; ?>>Prefer not to say</option>
                        </select>
                    </div>
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <div style="position: relative;">
                        <input type="password" id="password" name="password" class="blue-input" required>
                        <button type="button" id="toggle-password" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 13px; border: none; background: transparent; color: #1976d2;">Show</button>
                    </div>
                    <small style="color: #666; display: block; margin-top: 8px;">
                        <strong>Password Requirements:</strong>
                        <div style="margin-top: 5px;">
                            <span id="length-check" style="color: #d32f2f; display: block;">At least 8 characters</span>
                            <span id="number-check" style="color: #d32f2f; display: block;">At least one digit (0-9)</span>
                            <span id="special-check" style="color: #d32f2f; display: block;">At least one special character (!@#$%^&* etc.)</span>
                        </div>
                    </small>
                </div>

                <button type="submit" class="btn-blue">Continue</button>
            </form>

            <div class="back-link">
                Already have an account? <a href="login.php">Login</a>
            </div>
            <div class="back-link" style="margin-top: 10px;">
                <a href="index.php" style="color: #999; font-weight: normal;">Return to Home</a>
            </div>
        </div>

        <div class="image-side">
            <div class="image-overlay">
                <div class="testimonial-stars">5 Star Rating</div>
                <p class="testimonial-text">"Since I started using this app, I never miss my workouts. The instructors are very attentive and the system works great!"</p>
                <p class="testimonial-author">Mert Yılmaz<br><small>Fitness Member</small></p>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('toggle-password');
        const lengthCheck = document.getElementById('length-check');
        const numberCheck = document.getElementById('number-check');
        const specialCheck = document.getElementById('special-check');

        togglePassword.addEventListener('click', function () {
            const isHidden = passwordInput.type === 'password';
            passwordInput.type = isHidden ? 'text' : 'password';
            togglePassword.textContent = isHidden ? 'Hide' : 'Show';
        });

        passwordInput.addEventListener('input', function () {
            const value = passwordInput.value;

            if (value.length >= 8) {
                lengthCheck.style.color = '#2e7d32';
                lengthCheck.textContent = 'At least 8 characters (OK)';
            } else {
                lengthCheck.style.color = '#d32f2f';
                lengthCheck.textContent = 'At least 8 characters';
            }

            if (/[0-9]/.test(value)) {
                numberCheck.style.color = '#2e7d32';
                numberCheck.textContent = 'At least one digit (0-9) (OK)';
            } else {
                numberCheck.style.color = '#d32f2f';
                numberCheck.textContent = 'At least one digit (0-9)';
            }

            if (/[!@#$%^&*()_+\-=[\]{};:\'"\\|,.<>/?`~]/.test(value)) {
                specialCheck.style.color = '#2e7d32';
                specialCheck.textContent = 'At least one special character (!@#$%^&* etc.) (OK)';
            } else {
                specialCheck.style.color = '#d32f2f';
                specialCheck.textContent = 'At least one special character (!@#$%^&* etc.)';
            }
        });
    })();
    </script>

<?php include 'footer.php'; ?>
