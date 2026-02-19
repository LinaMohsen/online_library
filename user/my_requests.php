<?php
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/helpers.php";
require_once __DIR__ . "/auth.php";

$member_id = (int)$_SESSION["member_id"];
$msg = $_GET["msg"] ?? "";

$q = $conn->prepare("SELECT r.id, r.request_date, r.status, r.note, b.title
                     FROM requests r JOIN books b ON b.id=r.book_id
                     WHERE r.member_id=? ORDER BY r.id DESC");
$q->bind_param("i", $member_id);
$q->execute();
$rows = $q->get_result();

require_once __DIR__ . "/user_header.php";
require_once __DIR__ . "/user_sidebar.php";
?>
<div class="main">
    <div class="topbar">
        <div><b>My Requests</b> <span class="text-muted">/ Status</span></div>
        <a class="btn btn-outline-primary btn-sm" href="browse_books.php">Browse Books</a>
    </div>

    <?php if ($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

    <div class="cardx">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Book</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($r = $rows->fetch_assoc()): ?>
                        <tr>
                            <td><?= (int)$r["id"] ?></td>
                            <td><?= e($r["title"]) ?></td>
                            <td><?= e($r["request_date"]) ?></td>
                            <td>
                                <?php
                                $s = $r["status"];
                                $cls = $s === "PENDING" ? "text-bg-warning" : ($s === "APPROVED" ? "text-bg-success" : "text-bg-danger");
                                ?>
                                <span class="badge <?= $cls ?>"><?= e($s) ?></span>
                            </td>
                            <td><?= e($r["note"]) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
<?php require_once __DIR__ . "/user_footer.php"; ?>