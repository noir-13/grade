<?php
$lifetime = 86400 * 7; // 7 days
session_set_cookie_params($lifetime);
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | KLD Grade System</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="verdantDesignSystem.css">
</head>
<body>

    <!-- Navbar -->
    <nav class="vds-navbar">
        <div class="vds-container vds-nav-content">
            <a href="index.php" class="vds-brand">
                <img src="assets/logo2.png" alt="Logo" height="40">
                KLD Grade Portal
            </a>
            <div class="vds-nav-links">
                <a href="index.php" class="vds-nav-link">Home</a>
                <a href="register.php" class="vds-btn vds-btn-secondary">Register</a>
            </div>
        </div>
    </nav>

    <div class="vds-section" style="min-height: 100vh; display: flex; align-items: center; justify-content: center;">
        <div class="vds-glass" style="width: 100%; max-width: 450px; padding: 40px; text-align: center;">
            
            <div class="mb-4">
                <i class="bi bi-person-circle" style="font-size: 3rem; color: var(--vds-forest);"></i>
                <h2 class="vds-h2 mt-2">Welcome Back</h2>
                <p class="vds-text-muted">Login to access your dashboard</p>
            </div>

            <?php if(isset($_SESSION['login_error'])): ?>
                <div class="vds-pill vds-pill-fail mb-4 w-100" style="justify-content: center;">
                    <?php 
                        echo htmlspecialchars($_SESSION['login_error']); 
                        unset($_SESSION['login_error']); 
                    ?>
                </div>
            <?php endif; ?>

            <form action="authenticate.php" method="POST">
                <div class="vds-form-group text-start">
                    <label class="vds-label">Email Address</label>
                    <input type="email" name="email" class="vds-input" placeholder="student@kld.edu.ph" value="<?php echo isset($_SESSION['login_email']) ? htmlspecialchars($_SESSION['login_email']) : ''; ?>" required>
                    <?php unset($_SESSION['login_email']); ?>
                </div>
                <div class="vds-form-group text-start">
                    <label class="vds-label">Password</label>
                    <input type="password" name="password" class="vds-input" placeholder="Enter your password" required>
                </div>

                <button type="submit" class="vds-btn vds-btn-primary w-100 mt-3">Login</button>
            </form>

            <div class="mt-4 d-flex justify-content-between" style="font-size: 0.9rem;">
                <a href="register.php" style="color: var(--vds-forest); text-decoration: none; font-weight: 600;">Create Account</a>
                <a href="forgot_password.php" style="color: var(--vds-text-muted); text-decoration: none;">Forgot Password?</a>
            </div>

            <div class="mt-4 pt-3 border-top">
                <p class="vds-text-muted" style="font-size: 0.8rem;">
                    By logging in, you agree to our <a href="#" onclick="openModal('termsModal')" style="color: var(--vds-forest);">Terms & Conditions</a>
                </p>
            </div>
        </div>
    </div>

    <?php include 'includes/legal_modals.php'; ?>

</body>
</html>
