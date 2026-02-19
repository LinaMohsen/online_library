<?php
require_once __DIR__ . "/../includes/config.php";

// حددي الإيميل والباسورد اللي بدك تعمليهم
$email = "admin@local.test";
$newPassword = "admin123";

$hash = password_hash($newPassword, PASSWORD_BCRYPT);

// إذا موجود حدّثه، إذا مش موجود أضفه
$stmt = $conn->prepare("SELECT id FROM admins WHERE email=? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    $id = (int)$row["id"];
    $u = $conn->prepare("UPDATE admins SET password_hash=? WHERE id=?");
    $u->bind_param("si", $hash, $id);
    $u->execute();
    echo "✅ Updated password for $email to: $newPassword";
} else {
    $name = "Admin";
    $i = $conn->prepare("INSERT INTO admins (name, email, password_hash) VALUES (?,?,?)");
    $i->bind_param("sss", $name, $email, $hash);
    $i->execute();
    echo "✅ Created admin $email with password: $newPassword";
}
