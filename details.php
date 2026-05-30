<?php 
// 1. Connect to the database and load the header
require_once 'includes/db.php'; 
include 'includes/header.php'; 

// 2. Catch the Hotel ID from the URL safely
$hotel_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($hotel_id === 0) {
    // If someone tries to visit this page without clicking a hotel, send them back
    header('Location: index.php');
    exit;
}

// 3. Fetch the specific Hotel data
$stmt = $pdo->prepare("SELECT * FROM hotels WHERE id = :id");
$stmt->execute(['id' => $hotel_id]);
$hotel = $stmt->fetch();

if (!$hotel) {
    echo "<div class='container mt-5'><h3>Hotel not found.</h3></div>";
    include 'includes/footer.php';
    exit;
}

// 4. Fetch the Rooms for this specific Hotel (This powers the Dynamic Map)
$stmtRooms = $pdo->prepare("SELECT * FROM rooms WHERE hotel_id = :hotel_id ORDER BY room_number ASC");
$stmtRooms->execute(['hotel_id' => $hotel_id]);
$rooms = $stmtRooms->fetchAll();
?>

<style>
    .floor-plan {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 12px;
        border: 2px solid #e9ecef;
    }
    .room-box {
        height: 100px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        font-weight: bold;
        transition: all 0.2s;
    }
    .room-vacant {
        background-color: #e0f2fe; 
        border: 2px solid #0ea5e9; 
        color: #0369a1;
        cursor: pointer;
    }
    .room-vacant:hover {
        background-color: #bae6fd;
        transform: scale(1.05);
    }
    .room-occupied {
        background-color: #f1f5f9; 
        border: 2px dashed #cbd5e1; 
        color: #94a3b8;
        cursor: not-allowed;
        opacity: 0.7;
    }
    .room-selected {
        background-color: #0ea5e9 !important;
        color: white !important;
        box-shadow: 0 4px 12px rgba(14, 165, 233, 0.4);
    }
    .preview-img {
        width: 100%;
        height: 250px;
        object-fit: cover;
        border-radius: 8px;
    }
</style>

<div class="container-fluid p-0" style="height: 400px; overflow: hidden; position: relative;">
    <img src="<?php echo htmlspecialchars($hotel['image_url']); ?>" style="width: 100%; height: 100%; object-fit: cover; filter: brightness(0.7);" alt="Hotel">
    <div class="position-absolute bottom-0 start-0 p-5 text-white w-100" style="background: linear-gradient(transparent, rgba(0,0,0,0.8));">
        <div class="container">
            <h1 class="display-4 fw-bold"><?php echo htmlspecialchars($hotel['name']); ?></h1>
            <p class="lead"><i class="bi bi-geo-alt-fill"></i> <?php echo htmlspecialchars($hotel['location']); ?></p>
            
            <?php if ($hotel['michelin_rating'] > 0): ?>
                <span class="badge bg-warning text-dark fs-5"><i class="bi bi-star-fill"></i> <?php echo $hotel['michelin_rating']; ?>-Star Michelin Verified</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="container mt-5 mb-5">
    <div class="row">
        <div class="col-lg-7 mb-4">
            <h3 class="fw-bold mb-3">Interactive Floor Plan</h3>
            <p class="text-muted mb-3"><?php echo htmlspecialchars($hotel['description']); ?></p>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex gap-3 small">
                    <div><span class="badge bg-primary text-white">Blue</span> = Vacant</div>
                    <div><span class="badge bg-light text-secondary border">Grey</span> = Occupied</div>
                </div>
            </div>

            <ul class="nav nav-pills mb-4 nav-fill bg-light rounded-pill p-1 border">
                <li class="nav-item">
                    <button class="nav-link active rounded-pill fw-bold floor-btn" data-target="1" onclick="changeFloor(1)">Floor 1</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link rounded-pill fw-bold floor-btn" data-target="2" onclick="changeFloor(2)">Floor 2</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link rounded-pill fw-bold floor-btn" data-target="3" onclick="changeFloor(3)">Floor 3</button>
                </li>
            </ul>

            <div class="floor-plan">
                <?php if (count($rooms) > 0): ?>
                    <?php foreach ($rooms as $room): ?>
                        
                        <?php 
                        // Automatically calculate the floor based on the first digit of the room number (e.g., 201 = Floor 2)
                        $floor_level = substr($room['room_number'], 0, 1); 
                        ?>
                        
                        <?php if ($room['status'] == 'vacant'): ?>
                            <div class="room-box room-vacant floor-group floor-<?php echo $floor_level; ?>" 
                                 data-id="<?php echo $room['id']; ?>" 
                                 data-num="<?php echo htmlspecialchars($room['room_number']); ?>"
                                 data-type="<?php echo htmlspecialchars($room['room_type']); ?>" 
                                 data-img="<?php echo htmlspecialchars($room['image_url']); ?>" 
                                 onclick="selectRoom(this)">
                                Room <?php echo htmlspecialchars($room['room_number']); ?>
                            </div>
                        <?php else: ?>
                            <div class="room-box room-occupied floor-group floor-<?php echo $floor_level; ?>">
                                Room <?php echo htmlspecialchars($room['room_number']); ?>
                                <small class="fw-normal mt-1"><i class="bi bi-lock-fill"></i> Occupied</small>
                            </div>
                        <?php endif; ?>

                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-4">
                        <p class="text-muted">No rooms currently mapped for this property.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm border-0 sticky-top" style="top: 80px;">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold">Room Details</h5>
                    
                    <img id="previewImage" src="https://images.unsplash.com/photo-1582719508461-905c673771fd?w=800" alt="Select a room" class="preview-img mb-3 mt-2">
                    
                    <h4 id="previewRoomName" class="fw-bold text-primary mb-1">Select a Room</h4>
                    <p id="previewRoomType" class="text-muted border-bottom pb-3">Tap a blue room on the map.</p>
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="text-muted">Base Rate:</span>
                        <span class="fs-4 fw-bold">$<?php echo number_format($hotel['base_price'], 2); ?> <small class="fs-6 text-muted">/ night</small></span>
                    </div>
                    
                    <form action="checkout.php" method="POST">
                        <input type="hidden" name="hotel_id" value="<?php echo $hotel['id']; ?>">
                        <input type="hidden" name="selected_room_id" id="formRoomId" value="">
                        <input type="hidden" name="hotel_price" value="<?php echo $hotel['base_price']; ?>">
                        
                        <button type="submit" id="bookBtn" class="btn btn-dark w-100 py-3 fw-bold rounded-pill" disabled>
                            Proceed to Checkout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Run this function immediately when the page loads to show Floor 1 by default
    document.addEventListener("DOMContentLoaded", function() {
        changeFloor(1);
    });

    // 1. The Floor Switching Engine
    function changeFloor(floorNumber) {
        // Hide all rooms first
        let allRooms = document.querySelectorAll('.floor-group');
        allRooms.forEach(room => room.style.display = 'none');

        // Show only the rooms belonging to the selected floor
        let targetRooms = document.querySelectorAll('.floor-' + floorNumber);
        targetRooms.forEach(room => room.style.display = 'flex');

        // Update the visual state of the tab buttons
        let allBtns = document.querySelectorAll('.floor-btn');
        allBtns.forEach(btn => {
            btn.classList.remove('active', 'bg-primary', 'text-white');
            if(btn.getAttribute('data-target') == floorNumber) {
                btn.classList.add('active', 'bg-primary', 'text-white');
            }
        });
    }

    // 2. The Room Selection Engine
    function selectRoom(element) {
        let rooms = document.querySelectorAll('.room-box');
        rooms.forEach(r => r.classList.remove('room-selected'));

        element.classList.add('room-selected');

        let roomId = element.getAttribute('data-id');
        let roomNum = element.getAttribute('data-num');
        let roomType = element.getAttribute('data-type');
        let roomImg = element.getAttribute('data-img');

        document.getElementById('previewRoomName').innerText = "Room " + roomNum;
        document.getElementById('previewRoomType').innerText = roomType;
        document.getElementById('previewImage').src = roomImg;

        document.getElementById('formRoomId').value = roomId;
        let btn = document.getElementById('bookBtn');
        btn.disabled = false;
        btn.classList.remove('btn-dark');
        btn.classList.add('btn-primary'); 
        btn.innerText = "Book Room " + roomNum;
    }
</script>

<?php include 'includes/footer.php'; ?>