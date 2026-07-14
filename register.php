<?php
ob_start();
require_once('db_config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$password')";
    
    if ($conn->query($sql) === TRUE) {
        header("Location: login.php?registered=true");
        exit();
    } else {
        $error = "Registration failed: " . $conn->error;
    }
}
include('header.php');
?>

<div class="fc-auth-card">
    <h2>Create Account</h2>
    <?php if(isset($error)) echo "<p style='color:red; text-align:center;'>$error</p>"; ?>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" class="fc-input" required>
        <input type="email" name="email" placeholder="Email" class="fc-input" required>
        <input type="password" name="password" placeholder="Password" class="fc-input" required>
        <button type="submit" class="fc-btn">Register</button>
    </form>
</div>

<?php include('footer.php'); ob_end_flush(); ?>