<?php
require_once('db_config.php');

echo "<h1>Login Debug</h1>";

$username = 'hasan';
$password = '1234';

$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    echo "User found: " . $user['username'] . "<br>";
    echo "Stored hash: " . $user['password'] . "<br>";
    
    if (password_verify($password, $user['password'])) {
        echo "✅ Password MATCHES!<br>";
        echo "You should be able to login with: hasan / 1234";
    } else {
        echo "❌ Password does NOT match<br>";
        echo "The hash in database is for a different password.<br>";
        
        // Generate the correct hash
        $new_hash = password_hash($password, PASSWORD_DEFAULT);
        echo "Use this hash to update the database:<br>";
        echo "<code style='background:#222;padding:10px;display:block;'>UPDATE users SET password = '" . $new_hash . "' WHERE username = 'hasan';</code>";
    }
} else {
    echo "User not found!";
}
?>