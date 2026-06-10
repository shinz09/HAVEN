<?php 
require_once 'includes/db.php'; 
include 'includes/header.php'; 

$error = '';

// When the user clicks "Sign In"
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Check the database for this email
    $stmt = $pdo->prepare("SELECT * FROM accounts WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    // For this 1-week MVP, we are comparing plain text passwords. 
    // (Note: In a real production app, ALWAYS use password_hash() and password_verify()!)
    if ($user && $password === $user['password']) {
        
        // Log them in by saving their data to the browser Session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];

        // THE ROUTING LOGIC: Where do they go based on their role?
        if ($user['role'] == 'admin') {
            header("Location: cms_admin.php");
        } elseif ($user['role'] == 'hotel') {
            header("Location: cms_hotel.php");
        } else {
            // Regular users just go back home
            header("Location: index.php");
        }
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>

<div class="container mt-5" style="max-height: 80vh;">
    <div class="row justify-content-center">
        <div class="col-md-5">
            
            <div class="text-center mb-4">
                <h1 style="font-family: 'Times New Roman', Times, serif; font-weight: bold; letter-spacing: 2px;">HAVEN.</h1>
                <p class="text-muted">Portal Access</p>
            </div>

            <div class="card shadow border-0 rounded-4 p-4">
                <div class="card-body">
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger rounded-pill text-center py-2"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form action="login.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted small">Email Address</label>
                            <input type="email" name="email" class="form-control form-control-lg bg-light border-0" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold text-muted small">Password</label>
                            <input type="password" name="password" class="form-control form-control-lg bg-light border-0" required>
                        </div>
                        <button type="submit" class="btn btn-dark btn-lg w-100 rounded-pill fw-bold">Sign In</button>
                    </form>
                    
                </div>
            </div>

            <div class="mt-4 p-3 bg-light rounded text-center small text-muted text-start">
                <strong>MVP Test Accounts:</strong><br>
                Admin: admin@haven.com / password123<br>
                Hotel: manager@peninsula.com / password123<br>
                Hotel: manager@seashore.com / password123<br>
                Hotel: manager@skyview.com / password123<br>
                <hr class="my-2">
                User: jacob@user.com / password123<br>
                User: user1@haven.com / password123<br>
                User: user2@haven.com / password123<br>
                User: user3@haven.com / password123<br>
                User: user4@haven.com / password123
            </div>

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>