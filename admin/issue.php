<?php
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/../includes/helpers.php";

$msg = $_GET["msg"] ?? "";
$err = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $book_id = (int)($_POST["book_id"] ?? 0);
  $member_id = (int)($_POST["member_id"] ?? 0);
  $issue_date = $_POST["issue_date"] ?? date("Y-m-d");
  $due_date = $_POST["due_date"] ?? date("Y-m-d", strtotime("+14 days"));

  $stmt = $conn->prepare("SELECT available FROM books WHERE id=?");
  $stmt->bind_param("i", $book_id);
  $stmt->execute();
  $book = $stmt->get_result()->fetch_assoc();

  if (!$book) {
    $err = "Book not found.";
  } elseif ((int)$book["available"] <= 0) {
    $err = "No available copies for this book.";
  } else {
    $conn->begin_transaction();
    try {
      $stmt = $conn->prepare("INSERT INTO issues (book_id, member_id, issue_date, due_date) VALUES (?,?,?,?)");
      $stmt->bind_param("iiss", $book_id, $member_id, $issue_date, $due_date);
      $stmt->execute();

      $stmt = $conn->prepare("UPDATE books SET available = available - 1 WHERE id=?");
      $stmt->bind_param("i", $book_id);
      $stmt->execute();

      $conn->commit();
      redirect("issue.php?msg=" . urlencode("Issued successfully."));
    } catch (Exception $e) {
      $conn->rollback();
      $err = "Error: " . $e->getMessage();
    }
  }
}

$books = $conn->query("SELECT id, title, author, available FROM books ORDER BY title ASC");
$members = $conn->query("SELECT id, full_name FROM members ORDER BY full_name ASC");

require_once __DIR__ . "/../includes/header.php";
require_once __DIR__ . "/../includes/sidebar.php";
?>
<div class="main">
  <div class="topbar">
    <div><b>Issue Book</b> <span class="text-muted">/ New</span></div>
    <a class="btn btn-outline-primary btn-sm" href="returns.php">View Issued</a>
  </div>

  <?php if ($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>

  <div class="cardx">
    <form method="post" class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Book</label>
        <select class="form-select" name="book_id" required>
          <option value="">Choose...</option>
          <?php while ($b = $books->fetch_assoc()): ?>
            <option value="<?= (int)$b["id"] ?>">
              <?= e($b["title"]) ?> — <?= e($b["author"]) ?> (Available: <?= (int)$b["available"] ?>)
            </option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Member</label>
        <select class="form-select" name="member_id" required>
          <option value="">Choose...</option>
          <?php while ($m = $members->fetch_assoc()): ?>
            <option value="<?= (int)$m["id"] ?>"><?= e($m["full_name"]) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Issue Date</label>
        <input type="date" class="form-control" name="issue_date" value="<?= e(date("Y-m-d")) ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Due Date</label>
        <input type="date" class="form-control" name="due_date" value="<?= e(date("Y-m-d", strtotime("+14 days"))) ?>" required>
      </div>
      <div class="col-12">
        <button class="btn btn-primary">Issue</button>
      </div>
    </form>
  </div>
</div>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>