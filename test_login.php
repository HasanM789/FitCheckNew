<?php
require_once('db_config.php');

echo "<h1>Login Test</h1>";

// Check if user exists
$username = 'hasan';
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    echo "✅ User found: " . $user['username'] . "<br>";
    echo "Password hash: " . $user['password'] . "<br>";
    
    // Test password
    $test_password = 'password123';
    if (password_verify($test_password, $user['password'])) {
        echo "✅ Password 'password123' is CORRECT!<br>";
    } else {
        echo "❌ Password 'password123' is WRONG<br>";
    }
    
    // Test another password
    $test_password2 = '1234';
    if (password_verify($test_password2, $user['password'])) {
        echo "✅ Password '1234' is CORRECT!<br>";
    } else {
        echo "❌ Password '1234' is WRONG<br>";
    }
} else {
    echo "❌ User not found!";
}

// Show connection status
echo "<br><br>Database: " . $db_name;
?>