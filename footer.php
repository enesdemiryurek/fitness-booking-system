    <footer class="footer">
        <div class="footer-container">
            <div class="footer-section">
                <h3>BABA PRO GYM</h3>
                <p>We're waiting for you for the best fitness experience. Push your limits, reach your goals!</p>
            </div>

            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="index.php#dersler">Lessons</a></li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li><a href="profile.php">My Profile</a></li>
                        <li><a href="logout.php">Log out</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Sign Up</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="footer-section">
                <h4>Our Lessons</h4>
                <ul>
                    <li><a href="index.php#yoga">Yoga</a></li>
                    <li><a href="index.php#pilates">Pilates</a></li>
                    <li><a href="index.php#hiit">HIIT</a></li>
                    <li><a href="index.php#zumba">Zumba</a></li>
                    <li><a href="index.php#fitness">Fitness</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h4>Communication</h4>
                <p>📧 info@gym.com</p>
                <p>📱 +90 (555) 123-4567</p>
                <p>📍 Ankara, Türkiye</p>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; 2025 GYM Fitness Center. </p>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
