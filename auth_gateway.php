<?php include('header.php'); ?>
<div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; padding: 40px;">
    <div class="fc-auth-card">
        <h2>Sign In</h2>
        <form action="login.php" method="POST">
            <input type="text" name="username" placeholder="Username" class="fc-input" required>
            <input type="password" name="password" placeholder="Password" class="fc-input" required>
            <button type="submit" class="fc-btn">Login</button>
        </form>
    </div>

    <div class="fc-auth-card">
        <h2>Create Account</h2>
        <form action="register.php" method="POST">
            <input type="text" name="username" placeholder="Username" class="fc-input" required>
            <input type="email" name="email" placeholder="Email" class="fc-input" required>
            <input type="password" name="password" placeholder="Password" class="fc-input" required>
            <button type="submit" class="fc-btn">Register</button>
        </form>
    </div>
</div>
<?php include('footer.php'); ?>