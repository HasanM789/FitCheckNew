<?php
// Ensure database connection and session are active
require_once('db_config.php');

if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}
include('header.php');

$user_id = $_SESSION['user_id'];
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

$success = "";

// Handle Profile Updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $conn->query("UPDATE users SET email = '$email' WHERE id = $user_id");
    $success = "Profile updated successfully!";
    $user['email'] = $email;
}
?>

<div class="account-container">
    <!-- Sidebar Navigation -->
    <aside class="account-sidebar">
        <div class="sidebar-header"><h3>My Account</h3></div>
        <nav class="sidebar-nav">
            <a href="account.php"><span class="nav-icon">◆</span> Overview</a>
            <a href="orders.php"><span class="nav-icon">◈</span> My Orders</a>
            <a href="profile.php" class="active"><span class="nav-icon">◇</span> Profile Settings</a>
            <a href="logout.php" class="logout-link"><span class="nav-icon">◉</span> Logout</a>
        </nav>
    </aside>

    <!-- Main Profile Content -->
    <main class="account-main">
        <div class="orders-section" style="padding: 30px;">
            <h2 style="margin-bottom: 25px; font-size: 1.5rem; color: #fff;">Profile Settings</h2>
            
            <?php if ($success): ?>
                <div class="success-message" style="color: #28a745; margin-bottom: 20px;"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" style="max-width: 500px;">
                <!-- Username (Read-only) -->
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; color: #94a3b8; font-size: 0.8rem; margin-bottom: 8px;">Username</label>
                    <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled 
                           style="width: 100%; padding: 12px; background: #0f1115; border: 1px solid #2d2d2d; color: #666; border-radius: 4px;">
                </div>

                <!-- Email (Editable) -->
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; color: #94a3b8; font-size: 0.8rem; margin-bottom: 8px;">Email Address</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required 
                           style="width: 100%; padding: 12px; background: #0f1115; border: 1px solid #2d2d2d; color: #fff; border-radius: 4px;">
                </div>

                <button type="submit" name="update_profile" class="fc-btn" style="padding: 12px 24px; cursor: pointer;">Update Information</button>
            </form>

            <hr style="border: 0; border-top: 1px solid #2d2d2d; margin: 40px 0;">

            <!-- Security Section -->
            <h3 style="margin-bottom: 20px; font-size: 1.2rem; color: #fff;">Security</h3>
            <div class="info-group" style="background: #0f1115; padding: 20px; border-radius: 8px; border: 1px solid #2d2d2d;">
                <p style="color: #94a3b8; font-size: 0.9rem; margin-bottom: 15px;">Manage your account security and password.</p>
                <a href="#" class="fc-btn" style="background: transparent; border: 1px solid #dc3545; color: #dc3545; display: inline-block; padding: 10px 20px; text-decoration: none;">Change Password</a>
            </div>
        </div>
    </main>
</div>

<?php include('footer.php'); ?>