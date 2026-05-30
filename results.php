<?php 
// 1. Connect to the database and load the header
require_once 'includes/db.php'; 
include 'includes/header.php'; 

// 2. Catch the search word from the URL safely
$searchLocation = isset($_GET['location']) ? trim($_GET['location']) : '';
$hotels = []; // Create an empty array to hold our data

// 3. The Database Engine Logic
if ($searchLocation !== '') {
    // Search for the location, BUT ONLY show hotels that the Admin has 'approved'
    // We use PDO Prepare to prevent SQL injection hacking
    $stmt = $pdo->prepare("SELECT * FROM hotels WHERE location LIKE :location AND status = 'approved'");
    // The % signs allow partial matches (e.g. typing "manila" finds "Metro Manila")
    $stmt->execute(['location' => '%' . $searchLocation . '%']);
    $hotels = $stmt->fetchAll();
} else {
    // If they clicked search without typing anything, just show all approved hotels
    $stmt = $pdo->query("SELECT * FROM hotels WHERE status = 'approved'");
    $hotels = $stmt->fetchAll();
}
?>

<div class="container mt-5" style="min-height: 60vh;">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">
            <?php echo $searchLocation ? 'Stays in "' . htmlspecialchars($searchLocation) . '"' : 'All Available Stays'; ?>
        </h2>
        <span class="text-muted"><?php echo count($hotels); ?> properties found</span>
    </div>

    <div class="row">
        <?php if (count($hotels) > 0): ?>
            
            <?php foreach ($hotels as $hotel): ?>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm border-0 h-100 rounded-4 overflow-hidden">
                        
                        <img src="<?php echo htmlspecialchars($hotel['image_url']); ?>" class="card-img-top" alt="Hotel Image" style="height: 250px; object-fit: cover;">
                        
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title fw-bold mb-0"><?php echo htmlspecialchars($hotel['name']); ?></h5>
                                <?php if ($hotel['michelin_rating'] > 0): ?>
                                    <span class="badge bg-dark text-warning"><i class="bi bi-star-fill"></i> <?php echo $hotel['michelin_rating']; ?> Star</span>
                                <?php endif; ?>
                            </div>
                            
                            <p class="text-muted small mb-3"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($hotel['location']); ?></p>
                            <p class="card-text text-truncate"><?php echo htmlspecialchars($hotel['description']); ?></p>
                            
                            <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fs-4 fw-bold">$<?php echo number_format($hotel['base_price'], 2); ?></span>
                                    <span class="text-muted small">/ night</span>
                                </div>
                                <a href="details.php?id=<?php echo $hotel['id']; ?>" class="btn btn-primary fw-bold px-4 rounded-pill">View</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-search display-1 text-muted mb-3 d-block"></i>
                <h4 class="fw-bold">No stays found in this area.</h4>
                <p class="text-muted">Try adjusting your search to a different location.</p>
                <a href="index.php" class="btn btn-outline-dark mt-2">Go Back</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
// 4. Load the footer
include 'includes/footer.php'; 
?>