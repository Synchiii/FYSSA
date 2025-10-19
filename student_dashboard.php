<?php
session_start();
require_once "database.php";
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'STUDENT') {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION["user"];

// Fetch user data
$stmt = mysqli_prepare($conn, "SELECT first_name, middle_name, last_name, email, profile_picture FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result) ?? [];

$full_name = trim(($user['first_name'] ?? '') . ' ' . ($user['middle_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
$profileImage = !empty($user['profile_picture']) ? $user['profile_picture'] : 'default-profile.jpg';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Welcome Freshman</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="navigation.css">
<style>
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    display: flex;
}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-profile">
        <a href="profile.php" class="profile-link">
            <img src="<?= htmlspecialchars($profileImage) ?>" alt="Profile" class="profile-img">
            <div class="user-info">
                <h5><?= htmlspecialchars($full_name ?: 'User Name') ?></h5>
                <span><?= htmlspecialchars($user['email'] ?? '') ?></span>
            </div>
        </a>
    </div>
    <a href="student_dashboard.php" class="active"><i class="bi bi-house-door-fill me-2"></i>Home</a>
    <a href="student_tips.php"><i class="bi bi-lightbulb me-2"></i>Tips</a>
    <a href="calendar.php"><i class="bi bi-calendar-event me-2"></i>Reminder</a>
    <a href="student_chat.php"><i class="bi bi-mortarboard me-2"></i>Chat</a>
    <a href="instructor_settings.php"><i class="bi bi-person-circle me-2"></i>Settings</a>
    <hr class="text-white mx-3">
    <a href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
</div>

<!-- Main Content -->

</body>
</html>
