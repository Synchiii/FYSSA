<?php
session_start();
session_destroy();
header("Location: login.php");
?>

<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: profile.php");
    exit();
}