<?php
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/helpers.php";
require_once __DIR__ . "/auth.php";

$member_id = (int)$_SESSION["member_id"];

$stmt = $conn->prepare("SELECT full_name, email, phone FROM members WHERE id=? LIMIT 1");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();

$q = $conn->prepare("SELECT i.id, b.title, i.issue_date, i.due_date, i.return_date, i.status
                     FROM issues i JOIN books b ON b.id=i.book_id
                     WHERE i.member_id=? ORDER BY i.id DESC");
$q->bind_param("i", $member_id);
$q->execute();
$myIssues = $q->get_result();

require_once __DIR__ . "/user_header.php";
require_once __DIR__ . "/user_sidebar.php";
?>
<div class="main">
    <div class="topbar">
        <div><b>Dashboard</b> <span class="text-muted">/ User</span></div>
        <span class="badge-soft"><?= e($_SESSION["member_name"] ?? "User") ?></span>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="cardx">
                <h6 class="mb-2">My Profile</h6>
                <div class="text-muted">Name: <?= e($me["full_name"] ?? "") ?></div>
                <div class="text-muted">Email: <?= e($me["email"] ?? "") ?></div>
                <div class="text-muted">Phone: <?= e($me["phone"] ?? "") ?></div>
                <a class="btn btn-outline-primary btn-sm mt-3" href="profile.php">Edit Profile</a>
            </div>
        </div>

        <div class="col-md-6">
            <div class="cardx">
                <h6 class="mb-2">Quick Actions</h6>
                <a class="btn btn-primary btn-sm" href="browse_books.php">Browse Books</a>
                <a class="btn btn-outline-primary btn-sm" href="my_requests.php">My Requests</a>
            </div>
        </div>
    </div>

    <div class="cardx mt-3">
        <h6 class="mb-3">My Borrowed Books (Only Mine)</h6>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Book</th>
                        <th>Issue</th>
                        <th>Due</th>
                        <th>Return</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($r = $myIssues->fetch_assoc()): ?>
                        <tr>
                            <td><?= (int)$r["id"] ?></td>
                            <td><?= e($r["title"]) ?></td>
                            <td><?= e($r["issue_date"]) ?></td>
                            <td><?= e($r["due_date"]) ?></td>
                            <td><?= e($r["return_date"]) ?></td>
                            <td>
                                <span class="badge <?= $r["status"] === 'ISSUED' ? 'text-bg-warning' : 'text-bg-success' ?>">
                                    <?= e($r["status"]) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
<?php require_once __DIR__ . "/user_footer.php"; ?>