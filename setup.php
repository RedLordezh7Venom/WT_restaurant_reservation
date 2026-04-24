<?php
/**
 * One-time setup script: fixes demo user passwords in the database.
 * Visit http://localhost/restaurant/setup.php ONCE, then delete this file.
 */
require_once __DIR__ . '/db.php';

$db = getDB();

$users = [
    ['admin@restaurant.com', 'admin123'],
    ['user@test.com',        'user123'],
];

foreach ($users as [$email, $pass]) {
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->execute([$hash, $email]);
    echo "✅ Updated password for <strong>$email</strong><br>";
}

echo "<br>✅ Done! <strong>Delete this file (setup.php) after running.</strong><br>";
echo "<br><a href='index.php'>→ Go to the app</a>";
?>
