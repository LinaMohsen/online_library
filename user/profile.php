<?php
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/helpers.php";
require_once __DIR__ . "/auth.php";

$member_id = (int)$_SESSION["member_id"];
$msg = $_GET["msg"] ?? "";
$err = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST["full_name"] ?? "");
    $phone = trim($_POST["phone"] ?? "");

    if ($full_name === "") {
        $err = "الاسم مطلوب";
    } else {
        $stmt = $conn->prepare("UPDATE members SET full_name=?, phone=? WHERE id=?");
        $stmt->bind_param("ssi", $full_name, $phone, $member_id);
        $stmt->execute();
        $_SESSION["member_name"] = $full_name;
        redirect("profile.php?msg=" . urlencode("تم تحديث البيانات بنجاح"));
    }
}

$stmt = $conn->prepare("SELECT full_name, email, phone FROM members WHERE id=? LIMIT 1");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();

require_once __DIR__ . "/user_header.php";
require_once __DIR__ . "/user_sidebar.php";
?>
<div class="main">
    <div class="topbar">
        <div><b>My Profile</b> <span class="text-muted">/ Edit</span></div>
        <a class="btn btn-outline-primary btn-sm" href="dashboard.php">Back</a>
    </div>

    <?php if ($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>

    <div class="cardx">
        <form method="post" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Full Name</label>
                <input class="form-control" name="full_name" required value="<?= e($me["full_name"] ?? "") ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input class="form-control" name="phone" value="<?= e($me["phone"] ?? "") ?>">
            </div>
            <div class="col-md-12">
                <label class="form-label">Email (readonly)</label>
                <input class="form-control" value="<?= e($me["email"] ?? "") ?>" readonly>
            </div>
            <div class="col-12">
                <button class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . "/user_footer.php"; ?>