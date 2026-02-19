<?php
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/../includes/helpers.php";

$action = $_GET["action"] ?? "";
$id = (int)($_GET["id"] ?? 0);

// ====== ADD / EDIT ======
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $title = trim($_POST["title"] ?? "");
  $author = trim($_POST["author"] ?? "");
  $isbn = trim($_POST["isbn"] ?? "");
  $quantity = (int)($_POST["quantity"] ?? 1);

  // ====== EDIT ======
  if ($action === "edit" && $id > 0) {
    $stmt = $conn->prepare("UPDATE books SET title=?, author=?, isbn=?, quantity=?, available=LEAST(available, ?) WHERE id=?");
    $stmt->bind_param("sssiii", $title, $author, $isbn, $quantity, $quantity, $id);
    $stmt->execute();
    redirect("books.php?msg=" . urlencode("Edit Books Done"));
  }
  // ====== ADD (NO ERROR IF ISBN EXISTS) ======
  else {
    $available = $quantity;

    // INSERT IGNORE يمنع خطأ Duplicate في حال ISBN موجود
    $stmt = $conn->prepare("
      INSERT IGNORE INTO books (title, author, isbn, quantity, available)
      VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssii", $title, $author, $isbn, $quantity, $available);
    $stmt->execute();

    // إذا ما تمت إضافة صف جديد -> يعني ISBN موجود
    if ($stmt->affected_rows > 0) {
      redirect("books.php?msg=" . urlencode("Book Added Successfully"));
    } else {
      redirect("books.php?msg=" . urlencode("This ISBN Already Exists"));
    }
  }
}

// ====== DELETE ======
if ($action === "delete" && $id > 0) {
  $stmt = $conn->prepare("DELETE FROM books WHERE id=?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  redirect("books.php?msg=" . urlencode("Delete Books Done"));
}

// ====== LOAD FOR EDIT ======
$editRow = null;
if ($action === "edit" && $id > 0) {
  $stmt = $conn->prepare("SELECT * FROM books WHERE id=?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $editRow = $stmt->get_result()->fetch_assoc();
}

$msg = $_GET["msg"] ?? "";

// ====== SEARCH + FILTER ======
$search = trim($_GET["search"] ?? "");
$avail  = $_GET["avail"] ?? ""; // "" all | in | out

$sql = "SELECT * FROM books WHERE 1=1";
$params = [];
$types = "";

if ($search !== "") {
  $sql .= " AND (title LIKE ? OR author LIKE ? OR isbn LIKE ?)";
  $like = "%$search%";
  $params[] = $like;
  $params[] = $like;
  $params[] = $like;
  $types .= "sss";
}

if ($avail === "in") {
  $sql .= " AND available > 0";
} elseif ($avail === "out") {
  $sql .= " AND available <= 0";
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
    <div><b>Books</b> <span class="text-muted">/ Manage</span></div>
    <a class="btn btn-outline-primary btn-sm" href="books.php">Refresh</a>
  </div>

  <?php if ($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

  <div class="cardx">
    <h6 class="mb-3"><?= $editRow ? "Edit Book" : "Add Book" ?></h6>
    <form method="post" class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Title</label>
        <input class="form-control" name="title" required value="<?= e($editRow["title"] ?? "") ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Author</label>
        <input class="form-control" name="author" required value="<?= e($editRow["author"] ?? "") ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">ISBN</label>
        <input class="form-control" name="isbn" value="<?= e($editRow["isbn"] ?? "") ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">Quantity</label>
        <input type="number" min="1" class="form-control" name="quantity" required value="<?= e($editRow["quantity"] ?? "1") ?>">
      </div>
      <div class="col-12">
        <button class="btn btn-primary"><?= $editRow ? "Save Changes" : "Add" ?></button>
        <?php if ($editRow): ?><a class="btn btn-secondary" href="books.php">Cancel</a><?php endif; ?>
      </div>
    </form>
  </div>

  <div class="cardx mt-3">
    <h6 class="mb-3">All Books</h6>

    <!-- SEARCH + FILTER FORM -->
    <form class="row g-2 mb-3" method="get">
      <div class="col-md-6">
        <input class="form-control" name="search" placeholder=" Search By Title / Author / ISBN" value="<?= e($search) ?>">
      </div>

      <div class="col-md-3">
        <select class="form-select" name="avail">
          <option value="" <?= $avail === "" ? "selected" : "" ?>>All Books</option>
          <option value="in" <?= $avail === "in" ? "selected" : "" ?>>Available Only</option>
          <option value="out" <?= $avail === "out" ? "selected" : "" ?>>Un Available</option>
        </select>
      </div>

      <div class="col-md-3 d-flex gap-2">
        <button class="btn btn-primary w-100">Filter</button>
        <a class="btn btn-outline-secondary w-100" href="books.php">Reset</a>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Title</th>
            <th>Author</th>
            <th>ISBN</th>
            <th>Qty</th>
            <th>Available</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($r = $q->fetch_assoc()): ?>
            <tr>
              <td><?= (int)$r["id"] ?></td>
              <td><?= e($r["title"]) ?></td>
              <td><?= e($r["author"]) ?></td>
              <td><?= e($r["isbn"]) ?></td>
              <td><?= (int)$r["quantity"] ?></td>
              <td><?= (int)$r["available"] ?></td>
              <td>
                <a class="btn btn-sm btn-outline-primary" href="books.php?action=edit&id=<?= (int)$r["id"] ?>">Edit</a>
                <a class="btn btn-sm btn-outline-danger" href="books.php?action=delete&id=<?= (int)$r["id"] ?>" onclick="return confirm('Delete this book?')">Delete</a>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>