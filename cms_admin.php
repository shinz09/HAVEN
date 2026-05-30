<?php 
require_once 'includes/db.php'; 
require_once 'includes/auth.php'; // Load the gatekeeper

// 1. THE ULTIMATE SECURITY CHECK
// If a regular user or hotel manager tries to access this page, they get kicked out immediately.
requireRole('admin');

// 2. RUNTIME & ANALYTICS QUERIES (Aggregate Data)
$stmtUsers = $pdo->query("SELECT COUNT(*) as count FROM accounts WHERE role = 'user'");
$totalUsers = $stmtUsers->fetch()['count'];

$stmtBookings = $pdo->query("SELECT COUNT(*) as count, SUM(total_price) as rev FROM bookings");
$bookingData = $stmtBookings->fetch();
$totalBookings = $bookingData['count'];
$totalRevenue = $bookingData['rev'] ? $bookingData['rev'] : 0.00;

// 3. MASTER HOTEL LIST QUERY
$stmtHotels = $pdo->query("
    SELECT h.*, a.email as manager_email 
    FROM hotels h 
    JOIN accounts a ON h.manager_id = a.id 
    ORDER BY h.id DESC
");
$hotels = $stmtHotels->fetchAll();

// 4. ACTION HANDLER (Approving/Denying Hotels)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hotel_id'])) {
    $target_id = (int)$_POST['hotel_id'];
    $new_status = $_POST['action'] === 'approve' ? 'approved' : 'denied';
    
    $update = $pdo->prepare("UPDATE hotels SET status = :status WHERE id = :id");
    $update->execute(['status' => $new_status, 'id' => $target_id]);
    
    // Refresh the page instantly to show the new status
    header("Location: cms_admin.php");
    exit;
}

include 'includes/header.php'; 
?>

<div class="container mt-5 mb-5" style="min-height: 70vh;">
    
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <div>
            <h2 class="fw-bold mb-0">Admin Overview</h2>
            <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?></p>
        </div>
        <span class="badge bg-success px-3 py-2 rounded-pill"><i class="bi bi-circle-fill small me-2"></i>System Online</span>
    </div>

    <div class="row mb-5">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 rounded-4 bg-primary text-white">
                <div class="card-body p-4">
                    <h6 class="text-uppercase fw-bold text-white-50">Total Revenue</h6>
                    <h2 class="display-6 fw-bold mb-0">$<?php echo number_format($totalRevenue, 2); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 rounded-4 bg-dark text-white">
                <div class="card-body p-4">
                    <h6 class="text-uppercase fw-bold text-white-50">Active Bookings</h6>
                    <h2 class="display-6 fw-bold mb-0"><?php echo $totalBookings; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 rounded-4 bg-light">
                <div class="card-body p-4">
                    <h6 class="text-uppercase fw-bold text-muted">Registered Users</h6>
                    <h2 class="display-6 fw-bold mb-0 text-dark"><?php echo $totalUsers; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <h4 class="fw-bold mb-3">Property Management</h4>
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="py-3 px-4">Property</th>
                        <th class="py-3">Manager Contact</th>
                        <th class="py-3">Base Price</th>
                        <th class="py-3">Status</th>
                        <th class="py-3 text-end px-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hotels as $hotel): ?>
                        <tr>
                            <td class="px-4 py-3 fw-bold"><?php echo htmlspecialchars($hotel['name']); ?></td>
                            <td class="py-3 text-muted"><?php echo htmlspecialchars($hotel['manager_email']); ?></td>
                            <td class="py-3">$<?php echo number_format($hotel['base_price'], 2); ?></td>
                            <td class="py-3">
                                <?php if ($hotel['status'] == 'approved'): ?>
                                    <span class="badge bg-success text-white">Approved</span>
                                <?php elseif ($hotel['status'] == 'pending'): ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php else: ?>
                                    <span class="badge bg-danger text-white">Denied</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 text-end px-4">
                                <form action="cms_admin.php" method="POST" class="d-inline">
                                    <input type="hidden" name="hotel_id" value="<?php echo $hotel['id']; ?>">
                                    
                                    <?php if ($hotel['status'] !== 'approved'): ?>
                                        <button type="submit" name="action" value="approve" class="btn btn-sm btn-outline-success fw-bold rounded-pill px-3">Approve</button>
                                    <?php endif; ?>
                                    
                                    <?php if ($hotel['status'] !== 'denied'): ?>
                                        <button type="submit" name="action" value="deny" class="btn btn-sm btn-outline-danger fw-bold rounded-pill px-3">Deny</button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5 text-end">
        <a href="login.php" class="btn btn-outline-dark rounded-pill px-4">Sign Out</a>
    </div>

</div>

<?php include 'includes/footer.php'; ?>