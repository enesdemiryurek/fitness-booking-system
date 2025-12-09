<?php
session_start();
include 'db.php';

// Güvenlik: Giriş yapmamışsa işlem yapamaz
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

if ($class_id == 0) {
    header("Location: index.php");
    exit;
}

// Ders bilgilerini çek
$class_sql = "SELECT * FROM classes WHERE id = $class_id";
$class_result = mysqli_query($conn, $class_sql);

if (mysqli_num_rows($class_result) == 0) {
    echo "<script>alert('Class not found!'); window.location.href='index.php';</script>";
    exit;
}

$class = mysqli_fetch_assoc($class_result);

// Kullanıcı bu derse zaten kayıtlı mı kontrol et
$duplicate_check = "SELECT * FROM bookings WHERE user_id = $user_id AND class_id = $class_id";
$duplicate_result = mysqli_query($conn, $duplicate_check);

if (mysqli_num_rows($duplicate_result) > 0) {
    echo "<script>
        alert(' You are already registered for this class!');
        window.location.href = 'index.php';
    </script>";
    exit;
}

// Kontenjan kontrolü
if ($class['capacity'] <= 0) {
    echo "<script>
        alert(' Sorry, this class is full!');
        window.location.href = 'index.php';
    </script>";
    exit;
}

// Ödeme tutarı (simüle edilmiş - sabit fiyat)
$amount = 50.00; // Her ders için 50 TL

// Ödeme işlemi POST ile geldiğinde
if (isset($_POST['confirm_payment'])) {
    // Simüle edilmiş ödeme - her zaman başarılı
    $transaction_id = 'TXN-' . time() . '-' . rand(1000, 9999);
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'Credit Card';
    
    // 1. Ödeme kaydı oluştur
    $payment_sql = "INSERT INTO payments (user_id, class_id, amount, payment_status, payment_method, transaction_id) 
                    VALUES ($user_id, $class_id, $amount, 'completed', '$payment_method', '$transaction_id')";
    
    if (mysqli_query($conn, $payment_sql)) {
        $payment_id = mysqli_insert_id($conn);
        
        // 2. Rezervasyonu oluştur
        $booking_sql = "INSERT INTO bookings (user_id, class_id) VALUES ($user_id, $class_id)";
        
        if (mysqli_query($conn, $booking_sql)) {
            $booking_id = mysqli_insert_id($conn);
            
            // 3. Ödeme kaydına booking_id'yi ekle
            mysqli_query($conn, "UPDATE payments SET booking_id = $booking_id WHERE id = $payment_id");
            
            // 4. Stoktan 1 düş
            mysqli_query($conn, "UPDATE classes SET capacity = capacity - 1 WHERE id = $class_id");
            
            // Başarılı mesajı göster
            echo "<script>
                alert('✅ Payment completed successfully!\\n\\nAmount: " . number_format($amount, 2) . " TL\\nTransaction ID: " . $transaction_id . "\\n\\nYour reservation has been created.');
                window.location.href = 'profile.php';
            </script>";
            exit;
        } else {
            echo "<script>alert('Error occurred while creating reservation!'); window.location.href='index.php';</script>";
            exit;
        }
    } else {
        echo "<script>alert('Error occurred while saving payment!'); window.location.href='index.php';</script>";
        exit;
    }
}

include 'header.php';
?>

<div class="payment-page">
    <div class="payment-container">
        <div class="payment-card">
            <div class="payment-header">
                <h1> Payment Process</h1>
                <p>Complete payment for your reservation</p>
            </div>

            <div class="payment-content">
                <!-- Ders Bilgileri -->
                <div class="class-info-box">
                    <h3> Reservation Details</h3>
                    <div class="info-row">
                        <span class="info-label">Class Name:</span>
                        <span class="info-value"><?php echo htmlspecialchars($class['title']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Category:</span>
                        <span class="info-value"><?php echo htmlspecialchars($class['class_type']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Instructor:</span>
                        <span class="info-value"><?php echo htmlspecialchars($class['trainer_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date & Time:</span>
                        <span class="info-value"><?php echo date("d.m.Y H:i", strtotime($class['date_time'])); ?></span>
                    </div>
                </div>

                <!-- Ödeme Bilgileri -->
                <div class="payment-info-box">
                    <h3> Payment Information</h3>
                    <div class="amount-display">
                        <span class="amount-label">Total Amount:</span>
                        <span class="amount-value"><?php echo number_format($amount, 2); ?> TL</span>
                    </div>
                    
                   
                </div>

                <!-- Ödeme Formu -->
                <form method="POST" class="payment-form">
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select name="payment_method" class="payment-input" required>
                            <option value="Credit Card">Credit Card</option>
                            <option value="Debit Card">Debit Card</option>
                            <option value="PayPal">PayPal</option>
                            
                        </select>
                        <small>Select your preferred payment method</small>
                    </div>

                    <div class="form-group">
                        <label>Card Number</label>
                        <input type="text" id="cardNumber" class="payment-input" placeholder="1234 5678 9012 3456" maxlength="19" required>
                        <small>Simulated payment - you can enter any number</small>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Expiry Date</label>
                            <input type="text" id="expiryDate" class="payment-input" placeholder="MM/YY" maxlength="5" required>
                        </div>
                        <div class="form-group">
                            <label>CVV</label>
                            <input type="text" id="cvv" class="payment-input" placeholder="123" maxlength="3" 
                                   pattern="[0-9]{3}" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Cardholder Name</label>
                        <input type="text" class="payment-input" placeholder="Full Name" required>
                    </div>

                    <div class="payment-actions">
                        <a href="index.php" class="btn-cancel"> Cancel</a>
                        <button type="submit" name="confirm_payment" class="btn-pay"> Confirm Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Kredi kartı numarasına otomatik boşluk ekle (4 haneden sonra)
    document.getElementById('cardNumber').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\s/g, '');
        let formattedValue = value.replace(/(\d{4})(?=\d)/g, '$1 ');
        e.target.value = formattedValue.substring(0, 19);
    });

    // Tarih alanına otomatik "/" ekle
    document.getElementById('expiryDate').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length >= 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        e.target.value = value.substring(0, 5);
    });

    // CVV sadece rakam kabul et
    document.getElementById('cvv').addEventListener('input', function(e) {
        e.target.value = e.target.value.replace(/\D/g, '').substring(0, 3);
    });
</script>

<?php include 'footer.php'; ?>

