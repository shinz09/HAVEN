<?php 
// 1. Connect to DB and Header
require_once 'includes/db.php'; 
require_once 'includes/auth.php';
requireLogin();

if ($_SESSION['role'] !== 'user') {
    header('Location: index.php');
    exit;
}

include 'includes/header.php'; 

// 2. Catch the data sent from the Book button on details.php
$hotel_id = isset($_POST['hotel_id']) ? (int)$_POST['hotel_id'] : 0;
$room_id = isset($_POST['selected_room_id']) ? (int)$_POST['selected_room_id'] : 0;
$total_price = isset($_POST['hotel_price']) ? $_POST['hotel_price'] : 0.00;
$account_id = $_SESSION['user_id'];

// Security check: If they didn't select a room, kick them back
if ($room_id === 0) {
    echo "<div class='container mt-5 text-center'><h3>Oops! No room selected.</h3><a href='index.php' class='btn btn-primary mt-3'>Go Back</a></div>";
    include 'includes/footer.php';
    exit;
}

// 3. Get the exact room and hotel name for the receipt summary
$stmt = $pdo->prepare("
    SELECT r.room_number, r.room_type, h.name AS hotel_name 
    FROM rooms r 
    JOIN hotels h ON r.hotel_id = h.id 
    WHERE r.id = :id
");
$stmt->execute(['id' => $room_id]);
$details = $stmt->fetch();
?>

<style>
    .payment-option { cursor: pointer; border: 2px solid #e9ecef; transition: all 0.2s; }
    .payment-option:hover { border-color: #ced4da; background-color: #f8f9fa; }
    .payment-radio:checked + .payment-option { border-color: #000; background-color: #f8f9fa; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
</style>

<div class="container mt-5 mb-5" style="max-width: 600px;">
    
    <div class="card shadow-sm border-0 mb-4 rounded-4">
        <div class="card-body p-4 bg-light rounded-4">
            <h5 class="fw-bold text-muted mb-3">Booking Summary</h5>
            <h4 class="fw-bold"><?php echo htmlspecialchars($details['hotel_name']); ?></h4>
            <p class="mb-1">Room: <strong><?php echo htmlspecialchars($details['room_number']); ?> (<?php echo htmlspecialchars($details['room_type']); ?>)</strong></p>
            <hr>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <span class="fs-5 fw-bold">Total Due:</span>
                <span class="fs-3 fw-bold text-primary">$<?php echo number_format($total_price, 2); ?></span>
            </div>
        </div>
    </div>

    <div class="card shadow border-0 rounded-4">
        <div class="card-body p-4">
            <h4 class="card-title mb-4 fw-bold">Select Payment Method</h4>
            
            <form action="success.php" method="POST">
                
                <div class="mb-3">
                    <input class="form-check-input d-none payment-radio" type="radio" name="payment_method" id="visa" value="Visa" checked>
                    <label class="form-check-label w-100 payment-option rounded-3 p-3 d-flex align-items-center justify-content-between" for="visa">
                        <div><span class="fw-bold me-3">Visa</span><span class="text-muted small">**** 4242</span></div>
                        <span class="badge bg-dark fs-6"><i class="bi bi-credit-card"></i></span>
                    </label>
                </div>

                <div class="mb-3">
                    <input class="form-check-input d-none payment-radio" type="radio" name="payment_method" id="gpay" value="Google Pay">
                    <label class="form-check-label w-100 payment-option rounded-3 p-3 d-flex align-items-center justify-content-between" for="gpay">
                        <span class="fw-bold">Google Pay</span>
                        <span class="badge bg-primary fs-6">G Pay</span>
                    </label>
                </div>

                <div class="mb-4">
                    <input class="form-check-input d-none payment-radio" type="radio" name="payment_method" id="alipay" value="Alipay">
                    <label class="form-check-label w-100 payment-option rounded-3 p-3 d-flex align-items-center justify-content-between" for="alipay">
                        <span class="fw-bold">Alipay</span>
                        <span class="badge bg-info text-white fs-6">Alipay</span>
                    </label>
                </div>

                <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
                <input type="hidden" name="total_price" value="<?php echo $total_price; ?>">
                
                <button type="submit" class="btn btn-primary w-100 py-3 fw-bold fs-5 rounded-pill">Confirm Payment</button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>