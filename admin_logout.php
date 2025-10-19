<?php
session_start();
session_destroy();
header("Location: admin_login.php");
?>

<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: admin_dashboard.php");
    exit();
}