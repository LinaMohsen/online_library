<?php
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/../includes/helpers.php";

$action = $_GET["action"] ?? "";
$id = (int)($_GET["id"] ?? 0);
$msg = $_GET["msg"] ?? "";

// ====== ADD / EDIT ======
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $full_name = trim($_POST["full_name"] ?? "");
  $email = trim($_POST["email"] ?? "");
  $phone = trim($_POST["phone"] ?? "");
  $password = $_POST["password"] ?? "";
  $password_hash = $password ? password_hash($password, PASSWORD_BCRYPT) : null;

  // Basic validation
  if ($full_name === "" || $email === "") {
    redirect("members.php?msg=" . urlencode("UserName and Email are Demand"));
  }

  // Email format validation (Back-end)
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect("members.php?msg=" . urlencode("Email is not true"));
  }

  // Password required only when adding new user
  if ($action !== "edit" && !$password_hash) {
    redirect("members.php?msg=" . urlencode("You must add password for the UserName"));
  }

  // Check duplicate email (for add + edit)
  if ($action === "edit" && $id > 0) {
    $check = $conn->prepare("SELECT id FROM members WHERE email=? AND id<>? LIMIT 1");
    $check->bind_param("si", $email, $id);
  } else {
    $check = $conn->prepare("SELECT id FROM members WHERE email=? LIMIT 1");
    $check->bind_param("s", $email);
  }
  $check->execute();
  $dup = $check->get_result()->fetch_assoc();
  if ($dup) {
    redirect("members.php?msg=" . urlencode(" Email is already exist"));
  }

  // Execute add/edit with safe error handling
  try {
    if ($action === "edit" && $id > 0) {
      if ($password_hash) {
        $stmt = $conn->prepare("UPDATE members SET full_name=?, email=?, phone=?, password_hash=? WHERE id=?");
        $stmt->bind_param("ssssi", $full_name, $email, $phone, $password_hash, $id);
      } else {
        $stmt = $conn->prepare("UPDATE members SET full_name=?, email=?, phone=? WHERE id=?");
        $stmt->bind_param("sssi", $full_name, $email, $phone, $id);
      }
      $stmt->execute();
      redirect("members.php?msg=" . urlencode("User Edit is Done"));
    } else {
      $stmt = $conn->prepare("INSERT INTO members (full_name, email, phone, password_hash) VALUES (?,?,?,?)");
      $stmt->bind_param("ssss", $full_name, $email, $phone, $password_hash);
      $stmt->execute();
      redirect("members.php?msg=" . urlencode(" User add is Done"));
    }
  } catch (mysqli_sql_exception $e) {
    // 1062 = duplicate entry (extra safety)
    if ((int)$e->getCode() === 1062) {
      redirect("members.php?msg=" . urlencode(" Email is already exist "));
    }
    redirect("members.php?msg=" . urlencode("wrong to save "));
  }
}

// ====== DELETE ======
if ($action === "delete" && $id > 0) {
  try {
    $stmt = $conn->prepare("DELETE FROM members WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    redirect("members.php?msg=" . urlencode("User Delete is Done"));
  } catch (mysqli_sql_exception $e) {
    // If FK blocks delete (in case CASCADE not enabled somewhere)
    redirect("members.php?msg=" . urlencode("You cant delete it because its related with records(Issues/Requests)."));
  }
}

// ====== LOAD FOR EDIT ======
$editRow = null;
if ($action === "edit" && $id > 0) {
  $stmt = $conn->prepare("SELECT * FROM members WHERE id=?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $editRow = $stmt->get_result()->fetch_assoc();
}

// ====== SEARCH + FILTER ======
$search  = trim($_GET["search"] ?? "");
$hasPass = $_GET["hasPass"] ?? ""; // "" all | yes | no

$sql = "SELECT id, full_name, email, phone, password_hash FROM members WHERE 1=1";
$params = [];
$types = "";

if ($search !== "") {
  $sql .= " AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
  $like = "%$search%";
  $params[] = $like;
  $params[] = $like;
  $params[] = $like;
  $types .= "sss";
}

if ($hasPass === "yes") {
  $sql .= " AND password_hash IS NOT NULL AND password_hash <> ''";
} elseif ($hasPass === "no") {
  $sql .= " AND (password_hash IS NULL OR password_hash = '')";
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$q = $stmt->get_result();

require_once __DIR__ . "/../includes/header.php";
require_once __DIR__ . "/../includes/sidebar.php";
?>
<div class="main">
  <div class="topbar">
    <div><b>Members (Users)</b> <span class="text-muted">/ Manage</span></div>
    <a class="btn btn-outline-primary btn-sm" href="members.php">Refresh</a>
  </div>

  <?php if ($msg): ?>
    <div class="alert alert-success"><?= e($msg) ?></div>
  <?php endif; ?>

  <div class="cardx">
    <h6 class="mb-3"><?= $editRow ? "Edit User" : "Add User" ?></h6>

    <form method="post" class="row g-3" autocomplete="off">
      <div class="col-md-4">
        <label class="form-label">Full Name</label>
        <input class="form-control" name="full_name" required value="<?= e($editRow["full_name"] ?? "") ?>">
      </div>

      <div class="col-md-4">
        <label class="form-label">Email</label>
        <input type="email" class="form-control" name="email" required value="<?= e($editRow["email"] ?? "") ?>">
      </div>

      <div class="col-md-4">
        <label class="form-label">Phone</label>
        <input class="form-control" name="phone" value="<?= e($editRow["phone"] ?? "") ?>">
      </div>

      <div class="col-md-4">
        <label class="form-label">Password <?= $editRow ? "(its optiona )" : "(Its demand when add )" ?></label>
        <input type="password" class="form-control" name="password"
          placeholder="<?= $editRow ? "  Leave it empty if you dont want to edit " : "Wtite the password" ?>">
        <small class="text-muted"> The user will login with it </small>
      </div>

      <div class="col-12">
        <button class="btn btn-primary"><?= $editRow ? "Save Changes" : "Add" ?></button>
        <?php if ($editRow): ?><a class="btn btn-secondary" href="members.php">Cancel</a><?php endif; ?>
      </div>
    </form>
  </div>

  <div class="cardx mt-3">
    <h6 class="mb-3">All Users</h6>

    <form class="row g-2 mb-3" method="get">
      <div class="col-md-7">
        <input class="form-control" name="search" placeholder=" Search with Name /Email/Phone " value="<?= e($search) ?>">
      </div>

      <div class="col-md-3">
        <select class="form-select" name="hasPass">
          <option value="" <?= $hasPass === "" ? "selected" : "" ?>>All Users </option>
          <option value="yes" <?= $hasPass === "yes" ? "selected" : "" ?>>Have a password </option>
          <option value="no" <?= $hasPass === "no" ? "selected" : "" ?>> Havent a password </option>
        </select>
      </div>

      <div class="col-md-2 d-flex gap-2">
        <button class="btn btn-primary w-100">Search</button>
        <a class="btn btn-outline-secondary w-100" href="members.php">Delete</a>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Has Password?</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($r = $q->fetch_assoc()): ?>
            <tr>
              <td><?= (int)$r["id"] ?></td>
              <td><?= e($r["full_name"]) ?></td>
              <td><?= e($r["email"]) ?></td>
              <td><?= e($r["phone"]) ?></td>
              <td><?= $r["password_hash"] ? "Yes" : "No" ?></td>
              <td>
                <a class="btn btn-sm btn-outline-primary" href="members.php?action=edit&id=<?= (int)$r["id"] ?>">Edit</a>
                <a class="btn btn-sm btn-outline-danger"
                  href="members.php?action=delete&id=<?= (int)$r["id"] ?>"
                  onclick="return confirm('Delete this user?')">Delete</a>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>