<?php 
// 1. Connect to database and start session
require_once 'includes/db.php'; 

// 2. FETCH RECOMMENDED HOTELS dynamically from the database
// We pull 3 approved hotels to feature on the front page
$stmt = $pdo->query("SELECT * FROM hotels WHERE status = 'approved' ORDER BY id DESC LIMIT 3");
$recommendedHotels = $stmt->fetchAll();

// 3. Load the top half of the HTML
include 'includes/header.php'; 
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
                            <input type="text" name="location" class="form-control border-0 shadow-none" placeholder="Where to? (Try 'Manila')" required>
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
        <h3 class="fw-bold mb-4">Recommended for You</h3>
        <div class="row">
            
            <?php if (count($recommendedHotels) > 0): ?>
                <?php foreach ($recommendedHotels as $hotel): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm border-0 h-100 rounded-4 overflow-hidden">
                            
                            <img src="<?php echo htmlspecialchars($hotel['image_url']); ?>" class="card-img-top" alt="Hotel" style="height: 250px; object-fit: cover;">
                            
                            <div class="card-body d-flex flex-column p-4">
                                <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($hotel['name']); ?></h5>
                                
                                <?php if ($hotel['michelin_rating'] > 0): ?>
                                    <p class="text-muted small mb-3"><i class="bi bi-star-fill text-warning"></i> <?php echo $hotel['michelin_rating']; ?> Star Verified</p>
                                <?php else: ?>
                                    <p class="text-muted small mb-3"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($hotel['location']); ?></p>
                                <?php endif; ?>
                                
                                <div class="mt-auto">
                                    <a href="details.php?id=<?php echo $hotel['id']; ?>" class="btn btn-outline-dark w-100 rounded-pill fw-bold">View Property</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-light text-center border-0 text-muted py-4">
                        More featured properties coming soon.
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>

</main>
<?php 
// 4. Load the bottom half of the HTML
include 'includes/footer.php'; 
?>