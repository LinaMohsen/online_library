<?php
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/helpers.php";
require_once __DIR__ . "/auth.php";

$msg = $_GET["msg"] ?? "";
$books = $conn->query("SELECT id, title, author, isbn, available FROM books ORDER BY id DESC");

require_once __DIR__ . "/user_header.php";
require_once __DIR__ . "/user_sidebar.php";
?>
<div class="main">
    <div class="topbar">
        <div><b>Browse Books</b> <span class="text-muted">/ Library</span></div>
        <a class="btn btn-outline-primary btn-sm" href="my_requests.php">My Requests</a>
    </div>

    <?php if ($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>

    <div class="cardx">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>ISBN</th>
                        <th>Available</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($b = $books->fetch_assoc()): ?>
                        <tr>
                            <td><?= (int)$b["id"] ?></td>
                            <td><?= e($b["title"]) ?></td>
                            <td><?= e($b["author"]) ?></td>
                            <td><?= e($b["isbn"]) ?></td>
                            <td><?= (int)$b["available"] ?></td>
                            <td>
                                <a class="btn btn-sm btn-primary" href="request_book.php?book_id=<?= (int)$b["id"] ?>">
                                    Request
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
<?php require_once __DIR__ . "/user_footer.php"; ?>