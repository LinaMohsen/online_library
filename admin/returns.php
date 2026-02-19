<?php
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/../includes/helpers.php";

$action = $_GET["action"] ?? "";
$id = (int)($_GET["id"] ?? 0);
$msg = $_GET["msg"] ?? "";
$err = "";

// ====== RETURN ACTION ======
if ($action === "return" && $id > 0) {
  $stmt = $conn->prepare("SELECT book_id, status FROM issues WHERE id=?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $issue = $stmt->get_result()->fetch_assoc();

  if (!$issue) {
    $err = "Issue record not found.";
  } elseif ($issue["status"] === "RETURNED") {
    $err = "Already returned.";
  } else {
    $book_id = (int)$issue["book_id"];
    $conn->begin_transaction();
    try {
      $today = date("Y-m-d");

      $stmt = $conn->prepare("UPDATE issues SET status='RETURNED', return_date=? WHERE id=?");
      $stmt->bind_param("si", $today, $id);
      $stmt->execute();

      $stmt = $conn->prepare("UPDATE books SET available = available + 1 WHERE id=?");
      $stmt->bind_param("i", $book_id);
      $stmt->execute();

      $conn->commit();
      redirect("returns.php?msg=" . urlencode("Returned successfully."));
    } catch (Exception $e) {
      $conn->rollback();
      $err = "Error: " . $e->getMessage();
    }
  }
}

// ====== SEARCH + FILTER ======
$search = trim($_GET["search"] ?? "");
$status = $_GET["status"] ?? ""; // "" all | ISSUED | RETURNED

$sql = "SELECT i.*, b.title, m.full_name
        FROM issues i
        JOIN books b ON b.id=i.book_id
        JOIN members m ON m.id=i.member_id
        WHERE 1=1";
$params = [];
$types = "";

if ($search !== "") {
  $sql .= " AND (b.title LIKE ? OR m.full_name LIKE ?)";
  $like = "%$search%";
  $params[] = $like;
  $params[] = $like;
  $types .= "ss";
}

if ($status !== "") {
  $sql .= " AND i.status = ?";
  $params[] = $status;
  $types .= "s";
}

$sql .= " ORDER BY i.id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$q = $stmt->get_result();

require_once __DIR__ . "/../includes/header.php";
require_once __DIR__ . "/../includes/sidebar.php";
?>
<div class="main">
  <div class="topbar">
    <div><b>Returns</b> <span class="text-muted">/ Issued List</span></div>
    <a class="btn btn-outline-primary btn-sm" href="issue.php">New Issue</a>
  </div>

  <?php if ($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>

  <div class="cardx">

    <!-- SEARCH + FILTER FORM -->
    <form class="row g-2 mb-3" method="get">
      <div class="col-md-6">
        <input class="form-control" name="search" placeholder=" Search by books or members" value="<?= e($search) ?>">
      </div>

      <div class="col-md-3">
        <select class="form-select" name="status">
          <option value="" <?= $status === "" ? "selected" : "" ?>>All</option>
          <option value="ISSUED" <?= $status === "ISSUED" ? "selected" : "" ?>>Issued</option>
          <option value="RETURNED" <?= $status === "RETURNED" ? "selected" : "" ?>>Returned</option>
        </select>
      </div>

      <div class="col-md-3 d-flex gap-2">
        <button class="btn btn-primary w-100">Filtre</button>
        <a class="btn btn-outline-secondary w-100" href="returns.php">Delete</a>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Book</th>
            <th>Member</th>
            <th>Issue</th>
            <th>Due</th>
            <th>Return</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($r = $q->fetch_assoc()): ?>
            <tr>
              <td><?= (int)$r["id"] ?></td>
              <td><?= e($r["title"]) ?></td>
              <td><?= e($r["full_name"]) ?></td>
              <td><?= e($r["issue_date"]) ?></td>
              <td><?= e($r["due_date"]) ?></td>
              <td><?= e($r["return_date"]) ?></td>
              <td><span class="badge <?= $r["status"] === 'ISSUED' ? 'text-bg-warning' : 'text-bg-success' ?>"><?= e($r["status"]) ?></span></td>
              <td>
                <?php if ($r["status"] === "ISSUED"): ?>
                  <a class="btn btn-sm btn-success"
                    href="returns.php?action=return&id=<?= (int)$r["id"] ?>"
                    onclick="return confirm('Mark as returned?')">Return</a>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>