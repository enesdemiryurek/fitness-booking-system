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
        alert('⚠️ You are already registered for this class!');
        window.location.href = 'index.php';
    </script>";
    exit;
}

// Kontenjan kontrolü
if ($class['capacity'] <= 0) {
    echo "<script>
        alert('😔 Sorry, this class is full!');
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
                <h1>💳 Payment Process</h1>
                <p>Complete payment for your reservation</p>
            </div>

            <div class="payment-content">
                <!-- Ders Bilgileri -->
                <div class="class-info-box">
                    <h3>📋 Reservation Details</h3>
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
                    <h3>💰 Payment Information</h3>
                    <div class="amount-display">
                        <span class="amount-label">Total Amount:</span>
                        <span class="amount-value"><?php echo number_format($amount, 2); ?> TL</span>
                    </div>
                    
                    <div class="payment-method-box">
                        <p class="payment-note">
                            <strong>ℹ️ Information:</strong> This is a simulated payment system. 
                            No real payment is processed. Your reservation will be automatically created when payment is confirmed.
                        </p>
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
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                        <small>Select your preferred payment method</small>
                    </div>

                    <div class="form-group">
                        <label>Card Number</label>
                        <input type="text" class="payment-input" placeholder="1234 5678 9012 3456" maxlength="19" 
                               pattern="[0-9\s]{13,19}" required>
                        <small>Simulated payment - you can enter any number</small>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Expiry Date</label>
                            <input type="text" class="payment-input" placeholder="MM/YY" maxlength="5" 
                                   pattern="[0-9]{2}/[0-9]{2}" required>
                        </div>
                        <div class="form-group">
                            <label>CVV</label>
                            <input type="text" class="payment-input" placeholder="123" maxlength="3" 
                                   pattern="[0-9]{3}" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Cardholder Name</label>
                        <input type="text" class="payment-input" placeholder="Full Name" required>
                    </div>

                    <div class="payment-actions">
                        <a href="index.php" class="btn-cancel">❌ Cancel</a>
                        <button type="submit" name="confirm_payment" class="btn-pay">✅ Confirm Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.payment-page {
    min-height: calc(100vh - 200px);
    padding: 40px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.payment-container {
    max-width: 600px;
    margin: 0 auto;
}

.payment-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    overflow: hidden;
}

.payment-header {
    background: linear-gradient(135deg, #185ADB 0%, #0F4C75 100%);
    color: white;
    padding: 30px;
    text-align: center;
}

.payment-header h1 {
    margin: 0 0 10px;
    font-size: 2rem;
}

.payment-header p {
    margin: 0;
    opacity: 0.9;
}

.payment-content {
    padding: 30px;
}

.class-info-box, .payment-info-box {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 25px;
}

.class-info-box h3, .payment-info-box h3 {
    margin: 0 0 15px;
    color: #333;
    font-size: 1.2rem;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #e0e0e0;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 600;
    color: #666;
}

.info-value {
    color: #333;
    font-weight: 500;
}

.amount-display {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: white;
    border-radius: 10px;
    margin-bottom: 15px;
    border: 2px solid #185ADB;
}

.amount-label {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
}

.amount-value {
    font-size: 1.8rem;
    font-weight: bold;
    color: #185ADB;
}

.payment-method-box {
    background: #e3f2fd;
    border-left: 4px solid #2196F3;
    padding: 15px;
    border-radius: 8px;
}

.payment-note {
    margin: 0;
    color: #1976D2;
    font-size: 0.9rem;
    line-height: 1.6;
}

.payment-form {
    margin-top: 25px;
}

.payment-form .form-group {
    margin-bottom: 20px;
}

.payment-form label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.payment-input {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #d0d0d0;
    border-radius: 3px;
    font-size: 1rem;
    transition: all 0.2s;
    box-sizing: border-box;
    background: #ffffff;
    color: #212121;
}

.payment-input:focus {
    outline: none;
    border-color: #ff0000;
    box-shadow: 0 0 0 2px rgba(255, 0, 0, 0.1);
}

.payment-input select {
    background: #ffffff;
    cursor: pointer;
}

.payment-form small {
    display: block;
    margin-top: 5px;
    color: #666;
    font-size: 0.85rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.payment-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}

.btn-cancel, .btn-pay {
    flex: 1;
    padding: 15px;
    border: none;
    border-radius: 10px;
    font-size: 1.1rem;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    text-align: center;
    display: inline-block;
}

.btn-cancel {
    background: #f5f5f5;
    color: #666;
}

.btn-cancel:hover {
    background: #e0e0e0;
}

.btn-pay {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.btn-pay:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
}

@media (max-width: 600px) {
    .payment-page {
        padding: 20px 10px;
    }
    
    .payment-content {
        padding: 20px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .payment-actions {
        flex-direction: column;
    }
}
</style>

<?php include 'footer.php'; ?>

