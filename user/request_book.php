<?php
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/helpers.php";
require_once __DIR__ . "/auth.php";

$member_id = (int)$_SESSION["member_id"];
$book_id = (int)($_GET["book_id"] ?? 0);

if ($book_id <= 0) redirect("browse_books.php");

$stmt = $conn->prepare("SELECT id, title, author, available FROM books WHERE id=? LIMIT 1");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();

if (!$book) redirect("browse_books.php");

$msg = "";
$err = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // prevent duplicate pending request
    $chk = $conn->prepare("SELECT id FROM requests WHERE member_id=? AND book_id=? AND status='PENDING' LIMIT 1");
    $chk->bind_param("ii", $member_id, $book_id);
    $chk->execute();
    $exists = $chk->get_result()->fetch_assoc();

    if ($exists) {
        $err = "لديك طلب معلّق لهذا الكتاب بالفعل.";
    } else {
        $note = trim($_POST["note"] ?? "");
        $ins = $conn->prepare("INSERT INTO requests (member_id, book_id, note) VALUES (?,?,?)");
        $ins->bind_param("iis", $member_id, $book_id, $note);
        $ins->execute();
        redirect("my_requests.php?msg=" . urlencode("تم إرسال الطلب بنجاح ✅"));
    }
}

require_once __DIR__ . "/user_header.php";
require_once __DIR__ . "/user_sidebar.php";
?>
<div class="main">
    <div class="topbar">
        <div><b>Request Book</b> <span class="text-muted">/ Submit</span></div>
        <a class="btn btn-outline-primary btn-sm" href="browse_books.php">Back</a>
    </div>

    <?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>

    <div class="cardx">
        <h6 class="mb-2"><?= e($book["title"]) ?> — <?= e($book["author"]) ?></h6>
        <div class="text-muted mb-3">Available: <?= (int)$book["available"] ?></div>

        <form method="post" class="row g-3">
            <div class="col-12">
                <label class="form-label">Note (اختياري)</label>
                <input class="form-control" name="note" placeholder="مثلاً: أحتاجه لمادة ...">
            </div>
            <div class="col-12">
                <button class="btn btn-primary">Send Request</button>
            </div>
        </form>
    </div>

</div>
<?php require_once __DIR__ . "/user_footer.php"; ?>