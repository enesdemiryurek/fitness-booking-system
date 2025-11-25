    <footer class="footer">
        <div class="footer-container">
            <div class="footer-section">
                <h3>GYM</h3>
                <p>En iyi fitness deneyimi için seni bekliyoruz. Sınırlarını zorla, hedefine ulaş!</p>
            </div>

            <div class="footer-section">
                <h4>Hızlı Linkler</h4>
                <ul>
                    <li><a href="index.php">Anasayfa</a></li>
                    <li><a href="index.php#dersler">Dersler</a></li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li><a href="profile.php">Profilim</a></li>
                        <li><a href="logout.php">Çıkış</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Giriş Yap</a></li>
                        <li><a href="register.php">Kayıt Ol</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="footer-section">
                <h4>Dersler</h4>
                <ul>
                    <li><a href="index.php#dersler">Yoga</a></li>
                    <li><a href="index.php#dersler">Pilates</a></li>
                    <li><a href="index.php#dersler">HIIT</a></li>
                    <li><a href="index.php#dersler">Zumba</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h4>İletişim</h4>
                <p>📧 info@gym.com</p>
                <p>📱 +90 (555) 123-4567</p>
                <p>📍 Ankara, Türkiye</p>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; 2025 GYM Fitness Center. Tüm hakları saklıdır.</p>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
