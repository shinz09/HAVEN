<?php 
require_once 'includes/db.php'; 
include 'includes/header.php'; 

// 1. Catch the final checkout data
$room_id = isset($_POST['room_id']) ? (int)$_POST['room_id'] : 0;
$payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'Unknown';
$total_price = isset($_POST['total_price']) ? $_POST['total_price'] : 0.00;

// For MVP purposes, we will hardcode the buyer to our dummy user (ID: 3, Jacob)
// In a full app, this would be: $_SESSION['user_id'];
$account_id = 3; 

$booking_reference = "ERROR";

if ($room_id > 0) {
    try {
        // --- THE DATABASE MAGIC HAPPENS HERE ---

        // Step A: Save the booking receipt
        $stmt = $pdo->prepare("INSERT INTO bookings (account_id, room_id, payment_method, total_price) VALUES (:account_id, :room_id, :payment_method, :total_price)");
        $stmt->execute([
            'account_id' => $account_id,
            'room_id' => $room_id,
            'payment_method' => $payment_method,
            'total_price' => $total_price
        ]);
        
        // Generate a fake booking reference number based on the database ID
        $booking_id = $pdo->lastInsertId();
        $booking_reference = "HVN-" . str_pad($booking_id, 6, "0", STR_PAD_LEFT);

        // Step B: AUTOMATICALLY UPDATE THE DYNAMIC MAP
        // This flips the room to 'occupied' so nobody else can book it!
        $updateStmt = $pdo->prepare("UPDATE rooms SET status = 'occupied' WHERE id = :room_id");
        $updateStmt->execute(['room_id' => $room_id]);

    } catch (Exception $e) {
        die("Booking Error: " . $e->getMessage());
    }
} else {
    // Kick them out if they tried to skip the checkout page
    header("Location: index.php");
    exit;
}
?>

<div class="container mt-5 text-center" style="min-height: 60vh;">
    <div class="row justify-content-center">
        <div class="col-md-6">
            
            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
            <h2 class="fw-bold mt-3 mb-1">Booking Confirmed!</h2>
            <p class="text-muted mb-4">Your payment via <?php echo htmlspecialchars($payment_method); ?> was successful.</p>

            <div class="card shadow border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0 fw-bold">Digital Room Key</h5>
                </div>
                <div class="card-body p-4 bg-light">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo $booking_reference; ?>" alt="QR Code" class="mb-3 rounded">
                    
                    <h5 class="fw-bold tracking-widest"><?php echo $booking_reference; ?></h5>
                    <p class="small text-muted mt-3">Scan this QR code at your room door or at the express kiosk upon arrival.</p>
                </div>
            </div>

            <a href="index.php" class="btn btn-outline-dark rounded-pill px-5 mt-5">Return to Home</a>

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>