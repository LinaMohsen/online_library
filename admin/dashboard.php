<?php
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/../includes/helpers.php";

$books   = $conn->query("SELECT COUNT(*) c FROM books")->fetch_assoc()["c"];
$members = $conn->query("SELECT COUNT(*) c FROM members")->fetch_assoc()["c"];
$issued  = $conn->query("SELECT COUNT(*) c FROM issues WHERE status='ISSUED'")->fetch_assoc()["c"];

require_once __DIR__ . "/../includes/header.php";
require_once __DIR__ . "/../includes/sidebar.php";
?>
<div class="main">
  <div class="topbar">
    <div><b>Dashboard</b> <span class="text-muted">/ Library</span></div>
    <span class="badge-soft">Admin</span>
  </div>

  <div class="row g-3">
    <div class="col-md-4">
      <div class="cardx">
        <div class="text-muted">Books</div>
        <div class="fs-2 fw-bold"><?= (int)$books ?></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="cardx">
        <div class="text-muted">Members</div>
        <div class="fs-2 fw-bold"><?= (int)$members ?></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="cardx">
        <div class="text-muted">Issued</div>
        <div class="fs-2 fw-bold"><?= (int)$issued ?></div>
      </div>
    </div>
  </div>

  <div class="cardx mt-3">
    <h6 class="mb-3">Latest Issues</h6>
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Book</th>
            <th>Member</th>
            <th>Issue</th>
            <th>Due</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $q = $conn->query("SELECT i.id, b.title, m.full_name, i.issue_date, i.due_date, i.status
                             FROM issues i
                             JOIN books b ON b.id=i.book_id
                             JOIN members m ON m.id=i.member_id
                             ORDER BY i.id DESC LIMIT 8");
          while ($r = $q->fetch_assoc()):
          ?>
            <tr>
              <td><?= (int)$r["id"] ?></td>
              <td><?= e($r["title"]) ?></td>
              <td><?= e($r["full_name"]) ?></td>
              <td><?= e($r["issue_date"]) ?></td>
              <td><?= e($r["due_date"]) ?></td>
              <td><span class="badge <?= $r["status"] === 'ISSUED' ? 'text-bg-warning' : 'text-bg-success' ?>"><?= e($r["status"]) ?></span></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>