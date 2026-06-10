<?php 
require_once 'includes/db.php'; 
require_once 'includes/auth.php'; 

// 1. SECURITY CHECK
// Kick out regular users and Admins. Only 'hotel' roles allowed here.
requireRole('hotel');

$manager_id = $_SESSION['user_id'];

// 2. FETCH THE MANAGER'S SPECIFIC HOTEL
$stmtHotel = $pdo->prepare("SELECT * FROM hotels WHERE manager_id = :manager_id LIMIT 1");
$stmtHotel->execute(['manager_id' => $manager_id]);
$myHotel = $stmtHotel->fetch();

if (!$myHotel) {
    echo "<main class='flex-grow-1 py-5 bg-light'><div class='container mt-5 text-center'><h3>You do not have a hotel assigned to your account yet.</h3></div></main>";
    include 'includes/footer.php';
    exit;
}

$hotel_id = $myHotel['id'];

// 3. ACTION HANDLERS (Status, Rooms, Images)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    
    // Action A: Flip Room Status
    if ($_POST['action'] == 'toggle_status') {
        $target_room_id = (int)$_POST['room_id'];
        $new_status = $_POST['new_status'];
        
        $update = $pdo->prepare("UPDATE rooms SET status = :status WHERE id = :room_id AND hotel_id = :hotel_id");
        $update->execute(['status' => $new_status, 'room_id' => $target_room_id, 'hotel_id' => $hotel_id]);
    }

    // Action B: Add a New Room
    if ($_POST['action'] == 'add_room') {
        $room_num = trim($_POST['room_number']);
        $room_type = trim($_POST['room_type']); 
        
        $insert = $pdo->prepare("INSERT INTO rooms (hotel_id, room_number, room_type, status) VALUES (:h_id, :r_num, :r_type, 'vacant')");
        $insert->execute(['h_id' => $hotel_id, 'r_num' => $room_num, 'r_type' => $room_type]);
    }

    // Action C: Update Hotel Image
    if ($_POST['action'] == 'update_image') {
        $new_image_url = trim($_POST['image_url']);
        
        $updateImg = $pdo->prepare("UPDATE hotels SET image_url = :img WHERE id = :id");
        $updateImg->execute(['img' => $new_image_url, 'id' => $hotel_id]);
    }
    
    // Refresh the page to show updates instantly
    header("Location: cms_hotel.php");
    exit;
}

// 4. FETCH THE ROOMS FOR THE DYNAMIC MAP CONTROL
$stmtRooms = $pdo->prepare("SELECT * FROM rooms WHERE hotel_id = :hotel_id ORDER BY room_number ASC");
$stmtRooms->execute(['hotel_id' => $hotel_id]);
$rooms = $stmtRooms->fetchAll();

include 'includes/header.php'; 
?>

<main class="flex-grow-1 py-5 bg-light">
    <div class="container">
        
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
            <div>
                <h2 class="fw-bold mb-0" style="font-family: 'Times New Roman', Times, serif;">Property Manager</h2>
                <p class="text-muted mb-0"><?php echo htmlspecialchars($myHotel['name']); ?></p>
            </div>
            <a href="details.php?id=<?php echo $hotel_id; ?>" target="_blank" class="btn btn-outline-dark rounded-pill px-4">
                <i class="bi bi-box-arrow-up-right me-2"></i>View Live Page
            </a>
        </div>

        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    
                    <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">Live Room Inventory</h5>
                        <button class="btn btn-sm btn-light fw-bold rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addRoomModal">+ Add Room</button>
                    </div>

                    <div class="card-body p-0">
                        <table class="table table-hover align-middle mb-0 bg-white">
                            <thead class="table-light">
                                <tr>
                                    <th class="py-3 px-4">Room</th>
                                    <th class="py-3">Type</th>
                                    <th class="py-3">Current Status</th>
                                    <th class="py-3 text-end px-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rooms as $room): ?>
                                    <tr>
                                        <td class="px-4 py-3 fw-bold">Room <?php echo htmlspecialchars($room['room_number']); ?></td>
                                        <td class="py-3 text-muted"><?php echo htmlspecialchars($room['room_type']); ?></td>
                                        <td class="py-3">
                                            <?php if ($room['status'] == 'vacant'): ?>
                                                <span class="badge bg-primary text-white px-3 py-2 rounded-pill">Vacant (Available)</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary text-white px-3 py-2 rounded-pill"><i class="bi bi-lock-fill"></i> Occupied (Locked)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 text-end px-4">
                                            <form action="cms_hotel.php" method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                                
                                                <?php if ($room['status'] == 'vacant'): ?>
                                                    <input type="hidden" name="new_status" value="occupied">
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary fw-bold rounded-pill px-3">Mark Occupied</button>
                                                <?php else: ?>
                                                    <input type="hidden" name="new_status" value="vacant">
                                                    <button type="submit" class="btn btn-sm btn-outline-primary fw-bold rounded-pill px-3">Mark Vacant</button>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm border-0 rounded-4 position-relative">
                    
                    <img src="<?php echo htmlspecialchars($myHotel['image_url']); ?>" class="card-img-top" alt="Hotel" style="height: 200px; object-fit: cover;">
                    
                    <button class="btn btn-sm btn-light position-absolute top-0 end-0 m-2 rounded-circle shadow" data-bs-toggle="modal" data-bs-target="#editImageModal">
                        <i class="bi bi-pencil-fill"></i>
                    </button>

                    <div class="card-body p-4">
                        <h5 class="fw-bold"><?php echo htmlspecialchars($myHotel['name']); ?></h5>
                        <p class="text-muted small mb-3"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($myHotel['location']); ?></p>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Base Price:</span>
                            <span class="fw-bold">$<?php echo number_format($myHotel['base_price'], 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">System Status:</span>
                            <span class="text-success fw-bold">Live</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 text-end">
            <a href="logout.php" class="btn btn-outline-dark rounded-pill px-4">Sign Out</a>
        </div>

    </div>

    <div class="modal fade" id="addRoomModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Add New Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="cms_hotel.php" method="POST">
                        <input type="hidden" name="action" value="add_room">
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Room Number / Name</label>
                            <input type="text" name="room_number" class="form-control bg-light border-0" required placeholder="e.g. 101 or Presidential">
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-muted small fw-bold">Room Type</label>
                            <select name="room_type" class="form-select bg-light border-0" required>
                                <option value="Standard">Standard</option>
                                <option value="Deluxe">Deluxe</option>
                                <option value="Suite">Suite</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-dark w-100 rounded-pill fw-bold">Save Room</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editImageModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Update Property Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="cms_hotel.php" method="POST">
                        <input type="hidden" name="action" value="update_image">
                        <div class="mb-4">
                            <label class="form-label text-muted small fw-bold">Direct Image URL</label>
                            <input type="url" name="image_url" class="form-control bg-light border-0" required value="<?php echo htmlspecialchars($myHotel['image_url']); ?>">
                            <small class="text-muted mt-2 d-block">Paste a direct link to an image (e.g., from Unsplash).</small>
                        </div>
                        <button type="submit" class="btn btn-dark w-100 rounded-pill fw-bold">Update Photo</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</main>
<?php include 'includes/footer.php'; ?>