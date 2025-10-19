<?php
require_once "database.php";
session_start();
// Redirect if admin is not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile Page</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="admin_style.css">
<style>
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
  <div class="sidebar-profile">
    <img src="<?= htmlspecialchars($profileImage ?? 'default-profile.png') ?>" alt="Profile">
    <div class="user-info">
      <h5><?= htmlspecialchars($full_name ?? 'Admin') ?></h5>
      <span><?= htmlspecialchars($_SESSION['admin_email'] ?? '') ?></span>
    </div>
  </div>
  <a href="admin_pending.php"><i class="bi bi-house-door-fill me-2"></i>REGISTRATION</a>
  <a href="admin_users.php"><i class="bi bi-person-circle me-2"></i>USERS SETTINGS</a>
  <a href="admin_tips.php" ><i class="bi bi-heart-pulse me-2"></i>TIPS SETTINGS</a>
  <a href="admin_calendar.php"><i class="bi bi-bell me-2"></i>CALENDAR</a>
  <a href="admin_chat.php"><i class="bi bi-mortarboard me-2"></i>CHAT MANAGEMENT</a>
  <hr class="text-white mx-3">
    <hr class="text-white mx-3">
      <hr class="text-white mx-3">
        <hr class="text-white mx-3">
          <hr class="text-white mx-3">
            <hr class="text-white mx-3">
              <hr class="text-white mx-3">
                <hr class="text-white mx-3">
                  <hr class="text-white mx-3">
                  
  <hr class="text-white mx-3">
  <a href="admin_logout.php"><i class="bi bi-box-arrow-right me-2"></i>LOGOUT</a>
</div>