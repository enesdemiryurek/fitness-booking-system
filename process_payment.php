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
$message = '';
$message_type = '';
$alreadyBooked = false;

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
    $alreadyBooked = true;
    $message = 'You are already registered for this class.';
    $message_type = 'error';
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
if (isset($_POST['confirm_payment']) && !$alreadyBooked) {
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
<style>
    .page-shell {max-width: 900px; margin: 0 auto; padding: 24px; background: #fff;}
    .page-shell h1 {margin: 0 0 10px 0; font-size: 26px;}
    .helper {color: #666; margin-bottom: 16px;}
    .section {border: 1px solid #e0e0e0; background: #fafafa; padding: 16px; border-radius: 6px; margin-bottom: 16px;}
    .section h2 {margin: 0 0 8px 0; font-size: 20px;}
    .section p {margin: 0 0 10px 0; color: #555;}
    .grid {display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px;}
    .field {display: flex; flex-direction: column; gap: 6px;}
    label {font-weight: 600; font-size: 14px;}
    input[type="text"], input[type="url"], select {padding: 8px; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 14px;}
    .btn {padding: 10px 14px; background: #222; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block;}
    .btn.ghost {background: #f0f0f0; color: #222;}
    .btn.secondary {background: #0b6bcb;}
    .inline {display: flex; gap: 8px; align-items: center; flex-wrap: wrap;}
    .info {display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px;}
    .info-item {background: #fff; border: 1px solid #e0e0e0; border-radius: 6px; padding: 10px;}
    .info-label {display: block; color: #666; font-size: 12px; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.4px;}
    .info-value {font-weight: 600; color: #222;}
    .amount {font-size: 22px; font-weight: 700; color: #0b6bcb;}
    .note {padding: 10px; border-radius: 4px; margin-bottom: 12px; border: 1px solid transparent;}
    .note.success {background: #e6f7e6; color: #1e6b1e; border-color: #c5e6c5;}
    .note.error {background: #ffecec; color: #b80000; border-color: #ffb3b3;}
</style>

<div class="page-shell">
    <h1>Payment Process</h1>
    <div class="helper">Complete payment to reserve your spot.</div>

    <?php if ($message): ?>
        <div class="note <?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <a href="index.php" class="btn ghost inline">← Back to Classes</a>

    <div class="section">
        <h2>Reservation Details</h2>
        <div class="info">
            <div class="info-item">
                <span class="info-label">Class</span>
                <span class="info-value"><?php echo htmlspecialchars($class['title']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Category</span>
                <span class="info-value"><?php echo htmlspecialchars($class['class_type']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Instructor</span>
                <span class="info-value"><?php echo htmlspecialchars($class['trainer_name']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Date & Time</span>
                <span class="info-value"><?php echo date("d.m.Y H:i", strtotime($class['date_time'])); ?></span>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Payment</h2>
        <p>Total amount to pay: <span class="amount"><?php echo number_format($amount, 2); ?> TL</span></p>
        <?php if ($alreadyBooked): ?>
            <div class="note error">You already have a reservation for this class. You can manage bookings from your profile.</div>
            <div class="inline">
                <a href="profile.php" class="btn secondary">Go to Profile</a>
                <a href="index.php" class="btn ghost">Back to Classes</a>
            </div>
        <?php else: ?>
            <form method="POST" class="stack">
                <div class="grid">
                    <div class="field">
                        <label>Payment Method</label>
                        <select name="payment_method" required>
                            <option value="Credit Card">Credit Card</option>
                            <option value="Debit Card">Debit Card</option>
                            <option value="PayPal">PayPal</option>
                        </select>
                        <span class="helper" style="margin:0; color:#777;">Choose your preferred method.</span>
                    </div>

                    <div class="field">
                        <label>Card Number</label>
                        <input type="text" id="cardNumber" placeholder="1234 5678 9012 3456" maxlength="19" required>
                        <span class="helper" style="margin:0; color:#777;">Simulated payment — any number works.</span>
                    </div>

                    <div class="field">
                        <label>Expiry Date</label>
                        <input type="text" id="expiryDate" placeholder="MM/YY" maxlength="5" required>
                    </div>

                    <div class="field">
                        <label>CVV</label>
                        <input type="text" id="cvv" placeholder="123" maxlength="3" pattern="[0-9]{3}" required>
                    </div>

                    <div class="field" style="grid-column: 1 / -1;">
                        <label>Cardholder Name</label>
                        <input type="text" placeholder="Full Name" required>
                    </div>
                </div>

                <div class="inline">
                    <button type="submit" name="confirm_payment" class="btn secondary">Confirm Payment</button>
                    <a href="index.php" class="btn ghost">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
    // Kredi kartı numarasına otomatik boşluk ekle (4 haneden sonra)
    document.getElementById('cardNumber').addEventListener('input', function(e) {
        var value = e.target.value.replace(/\s/g, '');
        var formattedValue = value.replace(/(\d{4})(?=\d)/g, '$1 ');
        e.target.value = formattedValue.substring(0, 19);
    });

    // Tarih alanına otomatik "/" ekle
    document.getElementById('expiryDate').addEventListener('input', function(e) {
        var value = e.target.value.replace(/\D/g, '');
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

