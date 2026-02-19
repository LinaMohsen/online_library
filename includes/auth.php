<?php
if (!isset($_SESSION['admin_id'])) {
  header("Location: /library/admin/login.php");
  exit;
}
