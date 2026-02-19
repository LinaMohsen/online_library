<?php
require_once __DIR__ . "/../includes/config.php";

$email = "lina@admin.com";   // غيريها لايميل المستخدم
$newPassword = "123456";     // غيريها للباسورد

$hash = password_hash($newPassword, PASSWORD_BCRYPT);

$stmt = $conn->prepare("UPDATE members SET password_hash=? WHERE email=?");
$stmt->bind_param("ss", $hash, $email);
$stmt->execute();

echo "✅ Member password updated for $email to: $newPassword";
