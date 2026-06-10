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
                    <div><span class="badge bg-secondary text-white">Grey</span> = Occupied</div>
                    <div><span class="badge bg-light text-secondary border">White</span> = Empty</div>
                </div>
            </div>

            <div id="floorTabsContainer"></div>

            <div id="floorsDisplayContainer"></div>

            <?php if (count($rooms) === 0): ?>
                <div class="col-12 text-center py-4">
                    <p class="text-muted">No rooms currently mapped for this property.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm border-0 sticky-top" style="top: 80px;">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold">Room Details</h5>
                    
                    <img id="previewImage" src="https://images.unsplash.com/photo-1582719508461-905c673771fd?w=800" alt="Select a room" class="preview-img mb-3 mt-2">
                    
                    <h4 id="previewRoomName" class="fw-bold text-primary mb-1">Select a Room</h4>
                    <p id="previewRoomType" class="text-muted border-bottom pb-3">Tap a blue room on the map.</p>
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="text-muted">Room Rate:</span>
                        <span id="previewRoomPrice" class="fs-4 fw-bold">$<?php echo number_format($hotel['base_price'], 2); ?> <small class="fs-6 text-muted">/ night</small></span>
                    </div>
                    
                    <form action="checkout.php" method="POST">
                        <input type="hidden" name="hotel_id" value="<?php echo $hotel['id']; ?>">
                        <input type="hidden" name="selected_room_id" id="formRoomId" value="">
                        <input type="hidden" name="hotel_price" id="formRoomPrice" value="<?php echo $hotel['base_price']; ?>">
                        
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
    const availableRooms = <?php echo json_encode($rooms); ?>;
    const savedFloorPlan = <?php echo ($hotel['floor_plan'] ? $hotel['floor_plan'] : "null"); ?>;
    let floorStates = {};
    let floorCount = 1;
    let currentFloor = 1;
    let scale = 1;

    // Initialize floor tabs and plan
    document.addEventListener("DOMContentLoaded", function() {
        if (savedFloorPlan && Object.keys(savedFloorPlan).length > 0) {
            floorStates = savedFloorPlan;
            floorCount = Object.keys(savedFloorPlan).length;
        } else {
            // Fallback: determine floors from room numbering
            let maxFloor = 1;
            availableRooms.forEach(room => {
                let floor = parseInt(room.room_number.substring(0, 1));
                if (floor > maxFloor) maxFloor = floor;
            });
            floorCount = maxFloor;
            // Create empty floor states
            for (let i = 1; i <= floorCount; i++) {
                floorStates[i] = Array(100).fill({type:'empty', room_id: null});
            }
        }
        
        generateFloorTabs(floorCount);
        generateFloorDisplays(floorCount);
        changeFloor(1);
    });

    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            window.location.reload();
        }
    });

    // Generate floor tabs dynamically
    function generateFloorTabs(count) {
        const tabsContainer = document.getElementById('floorTabsContainer');
        tabsContainer.innerHTML = `<ul class="nav nav-pills mb-4 nav-fill bg-light rounded-pill p-1 border"></ul>`;
        const tabsList = tabsContainer.querySelector('ul');
        
        for (let i = 1; i <= count; i++) {
            const li = document.createElement('li');
            li.className = 'nav-item';
            li.innerHTML = `<button class="nav-link ${i === 1 ? 'active' : ''} rounded-pill fw-bold floor-btn" data-target="${i}" onclick="changeFloor(${i})">Floor ${i}</button>`;
            tabsList.appendChild(li);
        }
    }

    // Generate floor display grids
    function generateFloorDisplays(count) {
        const container = document.getElementById('floorsDisplayContainer');
        container.innerHTML = '';
        
        for (let i = 1; i <= count; i++) {
            const floorDiv = document.createElement('div');
            floorDiv.className = 'floor-display';
            floorDiv.id = `floor-display-${i}`;
            floorDiv.style.display = i === 1 ? 'block' : 'none';
            
            floorDiv.innerHTML = `
                <div class="card border-0 rounded-3 shadow-sm">
                    <div class="card-body bg-light p-3">
                        <div class="d-flex justify-content-center align-items-center overflow-hidden" id="viewport-${i}" style="height: 400px; background: #eee;">
                            <div id="gridWrapper-${i}" style="transform-origin: center center;">
                                <div id="floorGrid-${i}" style="display: grid; grid-template-columns: repeat(10, 60px); grid-template-rows: repeat(10, 60px); gap: 1px; background-color: #dee2e6; border: 2px solid #343a40;"></div>
                            </div>
                        </div>
                        <p class="text-center text-muted small mt-3 mb-0">Scroll to zoom • Click rooms to book</p>
                    </div>
                </div>
            `;
            
            container.appendChild(floorDiv);
            renderFloor(i);
            
            // Add zoom functionality
            document.getElementById(`viewport-${i}`).addEventListener('wheel', e => {
                e.preventDefault();
                scale += e.deltaY * -0.001;
                scale = Math.min(Math.max(0.5, scale), 2);
                document.getElementById(`gridWrapper-${i}`).style.transform = `scale(${scale})`;
            });
        }
    }

    // Render a single floor's grid
    function renderFloor(floorNum) {
        const gridEl = document.getElementById(`floorGrid-${floorNum}`);
        if (!gridEl) return;
        
        gridEl.innerHTML = '';
        
        if (!floorStates[floorNum]) {
            floorStates[floorNum] = Array(100).fill({type:'empty', room_id: null});
        }
        
        floorStates[floorNum].forEach((cell, i) => {
            let div = document.createElement('div');
            div.style.width = '60px';
            div.style.height = '60px';
            div.className = 'border d-flex align-items-center justify-content-center fw-bold';
            div.style.border = '1px solid #dee2e6';

            let roomInfo = cell && cell.room_id ? availableRooms.find(r => r.id == cell.room_id) : null;
            let isOccupied = roomInfo && roomInfo.status === 'occupied';

            if (cell && cell.type === 'room' && roomInfo) {
                if (isOccupied) {
                    div.style.backgroundColor = '#f1f5f9';
                    div.style.color = '#6c757d';
                    div.style.cursor = 'not-allowed';
                    div.style.border = '1px dashed #cbd5e1';
                    div.title = 'Occupied';
                } else {
                    div.style.backgroundColor = '#0d6efd';
                    div.style.color = 'white';
                    div.style.cursor = 'pointer';
                    div.title = 'Vacant';
                    div.onclick = () => selectRoomFromFloor(roomInfo);
                }
            } else if (cell && cell.type === 'hallway') {
                div.style.backgroundColor = '#6c757d';
                div.style.color = '#ffffff';
                div.style.cursor = 'default';
            } else {
                div.style.backgroundColor = '#ffffff';
                div.style.color = '#343a40';
                div.style.cursor = 'default';
            }

            div.innerHTML = roomInfo ? roomInfo.room_number : '';
            div.dataset.index = i;
            div.dataset.floor = floorNum;
            gridEl.appendChild(div);
        });
    }

    // Change floor view
    function changeFloor(floorNumber) {
        // Hide all floor displays
        for (let i = 1; i <= floorCount; i++) {
            const floorDisplay = document.getElementById(`floor-display-${i}`);
            if (floorDisplay) floorDisplay.style.display = 'none';
        }
        
        // Show selected floor
        const selectedFloor = document.getElementById(`floor-display-${floorNumber}`);
        if (selectedFloor) selectedFloor.style.display = 'block';
        
        currentFloor = floorNumber;

        // Update tab styling
        let allBtns = document.querySelectorAll('.floor-btn');
        allBtns.forEach(btn => {
            btn.classList.remove('active', 'bg-primary', 'text-white');
            if(btn.getAttribute('data-target') == floorNumber) {
                btn.classList.add('active', 'bg-primary', 'text-white');
            }
        });
    }

    // Select room from floor grid
    function selectRoomFromFloor(roomData) {
        if (!roomData || roomData.status === 'occupied') {
            return;
        }

        document.getElementById('previewRoomName').innerText = "Room " + roomData.room_number;
        document.getElementById('previewRoomType').innerText = roomData.room_type;
        document.getElementById('previewImage').src = roomData.image_url;
        document.getElementById('formRoomId').value = roomData.id;
        document.getElementById('formRoomPrice').value = parseFloat(roomData.room_price || <?php echo $hotel['base_price']; ?>).toFixed(2);
        document.getElementById('previewRoomPrice').innerHTML = "$" + parseFloat(roomData.room_price || <?php echo $hotel['base_price']; ?>).toFixed(2) + " <small class='fs-6 text-muted'>/ night</small>";
        
        let btn = document.getElementById('bookBtn');
        btn.disabled = false;
        btn.classList.remove('btn-dark');
        btn.classList.add('btn-primary'); 
        btn.innerText = "Book Room " + roomData.room_number;
    }

    // Legacy function for compatibility
    function selectRoom(element) {
        if (element && element.getAttribute) {
            let roomId = element.getAttribute('data-id');
            let roomNum = element.getAttribute('data-num');
            let roomType = element.getAttribute('data-type');
            let roomImg = element.getAttribute('data-img');
            let roomData = availableRooms.find(r => r.id == roomId);
            let roomPrice = roomData ? parseFloat(roomData.room_price || <?php echo $hotel['base_price']; ?>).toFixed(2) : parseFloat(<?php echo $hotel['base_price']; ?>).toFixed(2);

            document.getElementById('previewRoomName').innerText = "Room " + roomNum;
            document.getElementById('previewRoomType').innerText = roomType;
            document.getElementById('previewImage').src = roomImg;
            document.getElementById('formRoomId').value = roomId;
            document.getElementById('formRoomPrice').value = roomPrice;
            document.getElementById('previewRoomPrice').innerHTML = "$" + roomPrice + " <small class='fs-6 text-muted'>/ night</small>";
            
            let btn = document.getElementById('bookBtn');
            btn.disabled = false;
            btn.classList.remove('btn-dark');
            btn.classList.add('btn-primary'); 
            btn.innerText = "Book Room " + roomNum;
        }
    }
</script>

<?php include 'includes/footer.php'; ?>