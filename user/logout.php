<?php
require_once __DIR__ . "/../includes/config.php";
unset($_SESSION["member_id"], $_SESSION["member_name"]);
header("Location: login.php");
exit;
