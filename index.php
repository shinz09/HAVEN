<?php 
// 1. Connect to database and start session
require_once 'includes/db.php'; 
// 2. Load the top half of the HTML
include 'includes/header.php'; 
?>

<div class="container-fluid bg-light py-5 border-bottom">
    <div class="container text-center py-5">
        <h1 class="display-4 fw-bold mb-3">Find Your Perfect Stay</h1>
        <p class="lead text-muted mb-5">Seamless reservations. Transparent pricing. Real-time availability.</p>
        
        <div class="card shadow border-0 mx-auto" style="max-width: 800px; border-radius: 50px;">
            <div class="card-body p-2">
                <form action="results.php" method="GET" class="d-flex align-items-center">
                    
                    <div class="input-group input-group-lg border-end border-2 px-3">
                        <span class="input-group-text bg-transparent border-0"><i class="bi bi-geo-alt"></i></span>
                        <input type="text" name="location" class="form-control border-0 shadow-none" placeholder="Where to? (Try 'Manila')" required>
                    </div>

                    <div class="px-2">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold">Search</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="container mt-5">
    <h3 class="fw-bold mb-4">Recommended for You</h3>
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <img src="https://images.unsplash.com/photo-1566665797739-1674de7a421a?w=600" class="card-img-top" alt="Hotel">
                <div class="card-body">
                    <h5 class="fw-bold">The Peninsula Manila</h5>
                    <p class="text-muted"><i class="bi bi-star-fill text-warning"></i> Michelin Verified</p>
                    <a href="details.php?id=1" class="btn btn-outline-dark w-100">View Property</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
// 3. Load the bottom half of the HTML
include 'includes/footer.php'; 
?>