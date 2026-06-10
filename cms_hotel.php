<?php 
require_once 'includes/db.php'; 
require_once 'includes/auth.php'; 

// 1. SECURITY CHECK
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

// Ensure room_price exists for current schema and allow safe price updates
try {
    $columnExists = $pdo->query("SHOW COLUMNS FROM rooms LIKE 'room_price'")->fetch();
    if (!$columnExists) {
        $pdo->exec("ALTER TABLE rooms ADD COLUMN room_price DECIMAL(10,2) NOT NULL DEFAULT '0.00'");
    }
} catch (Exception $e) {
    // Ignore if DB user cannot alter schema; price support will fall back gracefully.
}

// 3. ACTION HANDLERS
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    
    // Toggle Status
    if ($_POST['action'] == 'toggle_status') {
        $target_room_id = (int)$_POST['room_id'];
        $new_status = $_POST['new_status'];
        $update = $pdo->prepare("UPDATE rooms SET status = :status WHERE id = :room_id AND hotel_id = :hotel_id");
        $update->execute(['status' => $new_status, 'room_id' => $target_room_id, 'hotel_id' => $hotel_id]);
    }

    // Add New Room
    if ($_POST['action'] == 'add_room') {
        $room_num = trim($_POST['room_number']);
        $room_type = trim($_POST['room_type']);
        $room_price = isset($_POST['room_price']) ? number_format((float)$_POST['room_price'], 2, '.', '') : $myHotel['base_price'];
        $room_image_url = trim($_POST['room_image_url']);
        $insert = $pdo->prepare("INSERT INTO rooms (hotel_id, room_number, room_type, room_price, image_url, status) VALUES (:h_id, :r_num, :r_type, :r_price, :img, 'vacant')");
        $insert->execute(['h_id' => $hotel_id, 'r_num' => $room_num, 'r_type' => $room_type, 'r_price' => $room_price, 'img' => $room_image_url]);
    }

    // Update Room
    if ($_POST['action'] == 'update_room') {
        $target_room_id = (int)$_POST['room_id'];
        $room_num = trim($_POST['room_number']);
        $room_type = trim($_POST['room_type']);
        $room_price = isset($_POST['room_price']) ? number_format((float)$_POST['room_price'], 2, '.', '') : $myHotel['base_price'];
        $room_image_url = trim($_POST['room_image_url']);
        $updateRoom = $pdo->prepare("UPDATE rooms SET room_number = :room_number, room_type = :room_type, room_price = :room_price, image_url = :img WHERE id = :room_id AND hotel_id = :hotel_id");
        $updateRoom->execute([
            'room_number' => $room_num,
            'room_type' => $room_type,
            'room_price' => $room_price,
            'img' => $room_image_url,
            'room_id' => $target_room_id,
            'hotel_id' => $hotel_id
        ]);
    }

    // Delete Room
    if ($_POST['action'] == 'delete_room') {
        $target_room_id = (int)$_POST['room_id'];
        $deleteRoom = $pdo->prepare("DELETE FROM rooms WHERE id = :room_id AND hotel_id = :hotel_id");
        $deleteRoom->execute(['room_id' => $target_room_id, 'hotel_id' => $hotel_id]);
    }

    // Update Image
    if ($_POST['action'] == 'update_image') {
        $new_image_url = trim($_POST['image_url']);
        $updateImg = $pdo->prepare("UPDATE hotels SET image_url = :img WHERE id = :id");
        $updateImg->execute(['img' => $new_image_url, 'id' => $hotel_id]);
    }

    // Save Map JSON
    if ($_POST['action'] == 'save_map') {
        $stmt = $pdo->prepare("UPDATE hotels SET floor_plan = :plan WHERE id = :id");
        $stmt->execute(['plan' => $_POST['map_json'], 'id' => $hotel_id]);
    }
    
    header("Location: cms_hotel.php");
    exit;
}

// 4. FETCH DATA
$stmtRooms = $pdo->prepare("SELECT * FROM rooms WHERE hotel_id = :hotel_id ORDER BY room_number ASC");
$stmtRooms->execute(['hotel_id' => $hotel_id]);
$rooms = $stmtRooms->fetchAll(PDO::FETCH_ASSOC);

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
                                    <th class="py-3">Price</th>
                                    <th class="py-3">Status</th>
                                    <th class="py-3 text-end px-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rooms as $room): ?>
                                    <tr>
                                        <td class="px-4 py-3 fw-bold">Room <?php echo htmlspecialchars($room['room_number']); ?></td>
                                        <td class="py-3 text-muted"><?php echo htmlspecialchars($room['room_type']); ?></td>
                                        <td class="py-3">
                                            $<?php echo number_format($room['room_price'] ?? $myHotel['base_price'], 2); ?>
                                        </td>
                                        <td class="py-3">
                                            <?php if ($room['status'] == 'vacant'): ?>
                                                <span class="badge bg-primary text-white px-3 py-2 rounded-pill">Vacant</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary text-white px-3 py-2 rounded-pill"><i class="bi bi-lock-fill"></i> Occupied</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3">
                                            <?php if (!empty($room['image_url'])): ?>
                                                <a href="<?php echo htmlspecialchars($room['image_url']); ?>" target="_blank" class="text-decoration-none">View</a>
                                            <?php else: ?>
                                                <span class="text-muted small">None</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 text-end px-4">
                                            <form action="cms_hotel.php" method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                                <?php if ($room['status'] == 'vacant'): ?>
                                                    <input type="hidden" name="new_status" value="occupied">
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary fw-bold rounded-pill px-3">Lock</button>
                                                <?php else: ?>
                                                    <input type="hidden" name="new_status" value="vacant">
                                                    <button type="submit" class="btn btn-sm btn-outline-primary fw-bold rounded-pill px-3">Unlock</button>
                                                <?php endif; ?>
                                            </form>
                                            <button type="button" class="btn btn-sm btn-outline-dark fw-bold rounded-pill px-3 ms-2" onclick='openEditRoomModal(<?php echo $room['id']; ?>, <?php echo json_encode($room['room_number']); ?>, <?php echo json_encode($room['room_type']); ?>, <?php echo number_format($room['room_price'] ?? $myHotel['base_price'], 2, '.', ''); ?>, <?php echo json_encode($room['image_url']); ?>)'>Edit</button>
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
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Interactive Floor Plan Editor</h5>
                <form action="cms_hotel.php" method="POST" id="mapForm">
                    <input type="hidden" name="action" value="save_map">
                    <input type="hidden" name="map_json" id="mapJsonInput">
                    <button type="button" class="btn btn-sm btn-success fw-bold rounded-pill px-4" onclick="saveMap()">Save Layout</button>
                </form>
            </div>
            <div class="card-body bg-light">
                <div class="mb-4 p-3 bg-white rounded border">
                    <label class="form-label text-muted small fw-bold mb-2">Number of Floors</label>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="number" id="floorNumberInput" min="1" max="20" value="1" class="form-control" style="max-width: 120px;">
                        <button type="button" class="btn btn-outline-dark fw-bold rounded-pill px-4" onclick="generateFloors()">Generate Floors</button>
                    </div>
                </div>
                <div id="floorsContainer"></div>
            </div>
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
                            <label class="form-label text-muted small fw-bold">Room Number</label>
                            <input type="text" name="room_number" class="form-control bg-light border-0" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-muted small fw-bold">Room Type</label>
                            <select name="room_type" class="form-select bg-light border-0" required>
                                <option value="Standard">Standard</option>
                                <option value="Deluxe">Deluxe</option>
                                <option value="Suite">Suite</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-muted small fw-bold">Room Price</label>
                            <input type="number" step="0.01" min="0" name="room_price" class="form-control bg-light border-0" required value="<?php echo number_format($myHotel['base_price'],2); ?>">
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-muted small fw-bold">Room Image URL</label>
                            <input type="url" name="room_image_url" class="form-control bg-light border-0" placeholder="https://example.com/room.jpg">
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
                        </div>
                        <button type="submit" class="btn btn-dark w-100 rounded-pill fw-bold">Update Photo</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="gridRoomModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom-0"><h5 class="modal-title fw-bold">Select Room to Place</h5></div>
                <div class="modal-body">
                    <input type="hidden" id="activeCellIndex">
                    <input type="hidden" id="activeFloor">
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold">Available Inventory</label>
                        <select id="gridRoomSelect" class="form-select bg-light border-0">
                            <?php foreach($rooms as $r): ?>
                                <option value="<?php echo $r['id']; ?>">Room <?php echo htmlspecialchars($r['room_number']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="button" class="btn btn-dark w-100 rounded-pill fw-bold" onclick="saveGridRoom()">Confirm Placement</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editRoomModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title fw-bold">Edit Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="cms_hotel.php" method="POST" id="editRoomForm">
                        <input type="hidden" name="action" value="update_room">
                        <input type="hidden" name="room_id" id="editRoomId">

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Room Number</label>
                            <input type="text" name="room_number" id="editRoomNumber" class="form-control bg-light border-0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Room Type</label>
                            <select name="room_type" id="editRoomType" class="form-select bg-light border-0" required>
                                <option value="Standard">Standard</option>
                                <option value="Deluxe">Deluxe</option>
                                <option value="Suite">Suite</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Room Price</label>
                            <input type="number" step="0.01" min="0" name="room_price" id="editRoomPrice" class="form-control bg-light border-0" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-muted small fw-bold">Room Image URL</label>
                            <input type="url" name="room_image_url" id="editRoomImageUrl" class="form-control bg-light border-0" placeholder="https://example.com/room.jpg">
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-danger w-100 rounded-pill fw-bold" onclick="confirmRoomDelete()">
                                <i class="bi bi-trash-fill"></i> Delete Room
                            </button>
                            <button type="submit" class="btn btn-dark w-100 rounded-pill fw-bold">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    const availableRooms = <?php echo json_encode($rooms); ?>;
    let floorStates = {}; // Store states for all floors
    let currentFloor = 1;
    let scale = 1;
    
    // Load saved floor plan from database
    const savedFloorPlan = <?php echo ($myHotel['floor_plan'] ? $myHotel['floor_plan'] : "null"); ?>;

    // --- GENERATE FLOOR EDITORS ---
    function generateFloors() {
        const container = document.getElementById('floorsContainer');
        const requestedCount = parseInt(document.getElementById('floorNumberInput').value) || 1;
        let floorCount = requestedCount;

        // Load saved plan when there is one, but allow the input to override after initial load.
        if (savedFloorPlan && typeof savedFloorPlan === 'object' && Object.keys(savedFloorPlan).length > 0) {
            const savedCount = Object.keys(savedFloorPlan).length;

            if (container.childElementCount === 0) {
                floorStates = savedFloorPlan;
                floorCount = savedCount;
                document.getElementById('floorNumberInput').value = floorCount;
            } else {
                if (!floorStates || Object.keys(floorStates).length === 0) {
                    floorStates = savedFloorPlan;
                }
                floorCount = requestedCount;
            }
        }

        // Initialize floor state if it doesn't exist for every requested floor.
        for (let i = 1; i <= floorCount; i++) {
            if (!floorStates[i]) {
                floorStates[i] = Array(100).fill({type:'empty', room_id: null});
            }
        }

        // Remove floors above the requested count so they do not persist after save.
        for (const key in floorStates) {
            if (Number(key) > floorCount) {
                delete floorStates[key];
            }
        }

        container.innerHTML = '';

        for (let i = 1; i <= floorCount; i++) {
            const floorDiv = document.createElement('div');
            floorDiv.className = 'card border rounded-3 mb-3';
            floorDiv.id = `floor-${i}`;
            floorDiv.style.marginBottom = '20px';
            
            floorDiv.innerHTML = `
                <div class="card-header bg-secondary text-white py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">Floor ${i}</h6>
                    <span class="text-white-50 small">Level ${i}</span>
                </div>
                <div class="card-body bg-light p-0">
                    <div class="row g-0">
                        <div class="col-md-3 border-end p-3 bg-white">
                            <h6 class="fw-bold mb-3 text-muted">Building Blocks</h6>
                            <div class="d-flex flex-column gap-2" class="palette-${i}">
                                <div class="p-3 bg-primary text-white text-center rounded fw-bold shadow-sm" draggable="true" ondragstart="drag(event, ${i})" data-type="room">
                                    <i class="bi bi-door-closed me-2"></i> Room
                                </div>
                                <div class="p-3 bg-secondary text-white text-center rounded fw-bold shadow-sm" draggable="true" ondragstart="drag(event, ${i})" data-type="hallway">
                                    <i class="bi bi-signpost-split me-2"></i> Hallway
                                </div>
                                <div class="p-3 bg-danger text-white text-center rounded fw-bold shadow-sm" draggable="true" ondragstart="drag(event, ${i})" data-type="empty">
                                    <i class="bi bi-eraser-fill me-2"></i> Eraser
                                </div>
                            </div>
                        </div>
                        <div class="col-md-9 d-flex justify-content-center align-items-center overflow-hidden" id="viewport-${i}" style="height: 350px; background: #eee;">
                            <div id="gridWrapper-${i}" style="transform-origin: center center;">
                                <div id="floorGrid-${i}" style="display: grid; grid-template-columns: repeat(10, 60px); grid-template-rows: repeat(10, 60px); gap: 1px; background-color: #dee2e6; border: 2px solid #343a40;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            container.appendChild(floorDiv);
            render(i);
            
            // Add zoom functionality for this floor
            document.getElementById(`viewport-${i}`).addEventListener('wheel', e => {
                e.preventDefault();
                scale += e.deltaY * -0.001;
                scale = Math.min(Math.max(0.5, scale), 2);
                document.getElementById(`gridWrapper-${i}`).style.transform = `scale(${scale})`;
            });
        }

        currentFloor = 1;
    }

    // --- RENDER FLOOR ---
    function render(floorNum) {
        const gridEl = document.getElementById(`floorGrid-${floorNum}`);
        if (!gridEl) return;
        
        gridEl.innerHTML = '';
        
        // Ensure floor state exists and contains proper data
        if (!floorStates[floorNum]) {
            floorStates[floorNum] = Array(100).fill({type:'empty', room_id: null});
        }
        
        floorStates[floorNum].forEach((cell, i) => {
            let div = document.createElement('div');
            div.style.width = '60px'; 
            div.style.height = '60px';
            
            if (cell && cell.type === 'room') {
                div.style.backgroundColor = '#0d6efd';
                div.style.color = 'white';
            } else if (cell && cell.type === 'hallway') {
                div.style.backgroundColor = '#6c757d';
            } else {
                div.style.backgroundColor = '#ffffff';
            }

            div.className = 'border d-flex align-items-center justify-content-center fw-bold';
            div.style.border = '1px solid #dee2e6';
            div.style.cursor = 'pointer';
            
            let roomInfo = cell && cell.room_id ? availableRooms.find(r => r.id == cell.room_id) : null;
            div.innerHTML = roomInfo ? roomInfo.room_number : ''; 
            
            div.dataset.index = i;
            div.dataset.floor = floorNum;
            div.ondragover = (e) => e.preventDefault();
            div.ondrop = (e) => drop(e, floorNum);
            gridEl.appendChild(div);
        });
    }

    // --- DRAG AND DROP ---
    function drag(e, floorNum) { 
        e.dataTransfer.setData("type", e.target.dataset.type);
        e.dataTransfer.setData("floor", floorNum);
    }
    
    function drop(e, floorNum) {
        e.preventDefault();
        let type = e.dataTransfer.getData("type");
        let index = e.target.dataset.index;
        
        if(type === 'room') {
            document.getElementById('activeCellIndex').value = index;
            document.getElementById('activeFloor').value = floorNum;
            new bootstrap.Modal(document.getElementById('gridRoomModal')).show();
        } else {
            if (!floorStates[floorNum]) {
                floorStates[floorNum] = Array(100).fill({type:'empty', room_id: null});
            }
            floorStates[floorNum][index] = {type: type, room_id: null};
            render(floorNum);
        }
    }

    function saveGridRoom() {
        let i = document.getElementById('activeCellIndex').value;
        let floorNum = document.getElementById('activeFloor').value;
        let roomId = document.getElementById('gridRoomSelect').value;
        
        if (!floorStates[floorNum]) {
            floorStates[floorNum] = Array(100).fill({type:'empty', room_id: null});
        }
        floorStates[floorNum][i] = {type: 'room', room_id: roomId};
        render(floorNum);
        bootstrap.Modal.getInstance(document.getElementById('gridRoomModal')).hide();
    }

    function saveMap() {
        const floorCount = parseInt(document.getElementById('floorNumberInput').value) || 1;
        for (const key in floorStates) {
            if (Number(key) > floorCount) {
                delete floorStates[key];
            }
        }
        document.getElementById('mapJsonInput').value = JSON.stringify(floorStates);
        document.getElementById('mapForm').submit();
    }

    function openEditRoomModal(id, number, type, price, imageUrl) {
        document.getElementById('editRoomId').value = id;
        document.getElementById('editRoomNumber').value = number;
        document.getElementById('editRoomType').value = type;
        document.getElementById('editRoomPrice').value = parseFloat(price).toFixed(2);
        document.getElementById('editRoomImageUrl').value = imageUrl || '';
        document.getElementById('editRoomForm').action = 'cms_hotel.php';
        document.querySelector('#editRoomForm input[name="action"]').value = 'update_room';
        new bootstrap.Modal(document.getElementById('editRoomModal')).show();
    }

    function confirmRoomDelete() {
        if (!confirm('Delete this room permanently?')) {
            return;
        }
        document.querySelector('#editRoomForm input[name="action"]').value = 'delete_room';
        document.getElementById('editRoomForm').submit();
    }

    // Initialize on page load
    window.addEventListener('load', generateFloors);
</script>

<?php include 'includes/footer.php'; ?>