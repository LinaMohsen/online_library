<?php
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/helpers.php";

if (isset($_SESSION['admin_id'])) {
  redirect("dashboard.php");
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = trim($_POST["email"] ?? "");
  $pass  = $_POST["password"] ?? "";

  $stmt = $conn->prepare("SELECT id, password_hash FROM admins WHERE email=? LIMIT 1");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $admin = $stmt->get_result()->fetch_assoc();

  if ($admin && password_verify($pass, $admin["password_hash"])) {
    $_SESSION["admin_id"] = (int)$admin["id"];
    redirect("dashboard.php");
  } else {
    $error = "Email or invalid password";
  }
}
?>
<!doctype html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/library/assets/css/style.css" rel="stylesheet">
</head>

<body class="login-bg">

  <div class="login-card">
    <div class="p-4 p-md-5">

      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="mb-0"> Login For Admin </h4>
          <div class="text-muted">Online Library Admin Panel</div>
        </div>
        <span class="badge-soft">ADMIN</span>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="post" autocomplete="off">
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input name="email" type="email" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Password</label>
          <input name="password" type="password" class="form-control" required>
        </div>

        <button class="btn btn-primary w-100">Login</button>
      </form>

      <div class="mt-3 d-flex justify-content-between">
        <a href="/library/user/login.php"> User Login</a>
        <a href="/library/">Home</a>
      </div>

    </div>
  </div>

</body>

</html>