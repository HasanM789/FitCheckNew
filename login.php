<?php
ob_start();
require_once('db_config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE username = '$username'");
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid login credentials.";
    }
}
include('header.php');
?>

<div class="fc-auth-card">
    <h2>Sign In</h2>
    <?php if(isset($error)) echo "<p style='color:red; text-align:center;'>$error</p>"; ?>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" class="fc-input" required>
        <input type="password" name="password" placeholder="Password" class="fc-input" required>
        <button type="submit" class="fc-btn">Login</button>
    </form>
    <p style="text-align:center; margin-top:20px; font-size: 12px; color: #888;">
        Don't have an account? <a href="register.php" style="color:#dc3545;">Join now</a>
    </p>
</div>

<?php include('footer.php'); ob_end_flush(); ?>