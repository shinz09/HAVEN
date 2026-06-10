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

<main class="flex-grow-1">

    <div class="container-fluid bg-light py-5 border-bottom">
        <div class="container text-center py-5">
            <h1 class="display-4 fw-bold mb-3" style="font-family: 'Times New Roman', Times, serif;">Find Your Perfect Stay</h1>
            <p class="lead text-muted mb-5">Seamless reservations. Transparent pricing. Real-time availability.</p>
            
            <div class="card shadow border-0 mx-auto" style="max-width: 800px; border-radius: 50px;">
                <div class="card-body p-2">
                    <form action="results.php" method="GET" class="d-flex align-items-center">
                        
                        <div class="input-group input-group-lg border-end border-2 px-3">
                            <span class="input-group-text bg-transparent border-0"><i class="bi bi-geo-alt"></i></span>
                            <input type="text" name="location" class="form-control border-0 shadow-none" placeholder="Where to? (Try 'Manila')" value="<?php echo htmlspecialchars($searchLocation); ?>" required>
                        </div>

                        <div class="px-2">
                            <button type="submit" class="btn btn-dark btn-lg rounded-pill px-5 fw-bold">Search</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-5 mb-5 pb-5">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold">
                <?php echo $searchLocation ? 'Stays in "' . htmlspecialchars($searchLocation) . '"' : 'All Available Stays'; ?>
            </h3>
            <span class="text-muted fw-bold"><?php echo count($hotels); ?> properties found</span>
        </div>

        <div class="row">
            <?php if (count($hotels) > 0): ?>
                
                <?php foreach ($hotels as $hotel): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm border-0 h-100 rounded-4 overflow-hidden">
                            
                            <img src="<?php echo htmlspecialchars($hotel['image_url']); ?>" class="card-img-top" alt="Hotel Image" style="height: 250px; object-fit: cover;">
                            
                            <div class="card-body d-flex flex-column p-4">
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
                                    <a href="details.php?id=<?php echo $hotel['id']; ?>" class="btn btn-dark fw-bold px-4 rounded-pill">View</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php else: ?>
                <div class="col-12 text-center py-5 mt-4">
                    <i class="bi bi-search display-1 text-muted mb-3 d-block"></i>
                    <h4 class="fw-bold">No stays found in this area.</h4>
                    <p class="text-muted">Try adjusting your search to a different location.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</main>
<?php 
// 4. Load the footer
include 'includes/footer.php'; 
?>