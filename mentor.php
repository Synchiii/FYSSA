<?php
session_start();
require_once 'vendor/autoload.php';
require_once "database.php"; // Make sure this connects $conn

// Redirect if not logged in
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION["user"];

// Handle profile update (name, gender, birthday, picture)
if (isset($_POST['save'])) {
    $firstName = trim($_POST['first_name'] ?? '');
    $middleName = trim($_POST['middle_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $birthMonth = trim($_POST['birth_month'] ?? '');
    $birthDay = intval($_POST['birth_day'] ?? 0);
    $birthYear = intval($_POST['birth_year'] ?? 0);
    $profilePic = null;

    // Handle image upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileTmpPath = $_FILES['profile_picture']['tmp_name'];
        $fileName = basename($_FILES['profile_picture']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif'];

        if (in_array($fileExt, $allowed)) {
            $newFileName = "profile_" . $user_id . "_" . time() . "." . $fileExt;
            $dest = $uploadDir . $newFileName;
            move_uploaded_file($fileTmpPath, $dest);
            $profilePic = $dest;
        }
    }

    // Update database
    if ($profilePic) {
        $stmt = mysqli_prepare($conn, "UPDATE users SET first_name=?, middle_name=?, last_name=?, gender=?, profile_picture=?, birth_month=?, birth_day=?, birth_year=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "ssssssiii", $firstName, $middleName, $lastName, $gender, $profilePic, $birthMonth, $birthDay, $birthYear, $user_id);
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE users SET first_name=?, middle_name=?, last_name=?, gender=?, birth_month=?, birth_day=?, birth_year=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "ssssssii", $firstName, $middleName, $lastName, $gender, $birthMonth, $birthDay, $birthYear, $user_id);
    }
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Fetch user data
$stmt = mysqli_prepare($conn, "SELECT first_name, middle_name, last_name, gender, email, profile_picture, birth_month, birth_day, birth_year, created_at FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result) ?? [];

// Prepare full name & profile image
$full_name = trim(($user['first_name'] ?? '') . ' ' . ($user['middle_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
$profileImage = !empty($user['profile_picture']) ? $user['profile_picture'] : 'default-profile.jpg';
?>

<!DOCTYPE html>
<html lang="en">


<head>
<meta charset="UTF-8">
<title>Reminders & Upcoming Events</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="navigation.css">


<style>
.content { margin-left:280px; padding:40px; }

</style>
</head>








<body>
<div class="sidebar">
  <div class="sidebar-profile">
    <img src="<?= htmlspecialchars($user['profile_picture'] ?: 'default-profile.jpg') ?>" alt="Profile">
    <div class="user-info">
      <h5><?= htmlspecialchars($full_name) ?></h5>
      <span><?= htmlspecialchars($user['email']) ?></span>
    </div>
  </div>
  <a href="index.php"><i class="bi bi-house-door-fill me-2"></i>Home</a>
  <a href="profile.php"><i class="bi bi-person-circle me-2"></i>Profile</a>
  <a href="wellness.php"><i class="bi bi-heart-pulse me-2"></i>Wellness</a>
  <a href="Study_tips.php"><i class="bi bi-lightbulb me-2"></i>Study Tips</a>
  <a href="calendar.php"><i class="bi bi-calendar-event me-2"></i>Calendar</a>
  <a href="reminder.php"><i class="bi bi-bell me-2"></i>Reminder</a>
  <a href="Mentor.php"class="active"><i class="bi bi-mortarboard me-2"></i>Mentor</a>
  <a href="peer_group.php"><i class="bi bi-people me-2"></i>Peer Group</a>
  <hr class="text-white mx-3">
  <a href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
</div>
<!-- Sidebar -->




<div class="main-content">


<div class="container">
  <div class="row">
    <div class="col-md-4">
      <div class="card">
        <img src="image1.jpg" class="card-img-top" alt="...">
        <div class="card-body text-center">
          <h5 class="card-title ">Card title</h5>
          <p class="card-text">INSTRUCTOR</p>
          <a href="#" class="btn btn-primary">Message</a>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card">
        <img src="image1.jpg" class="card-img-top" alt="...">
        <div class="card-body text-center">
          <h5 class="card-title">Card title</h5>
          <p class="card-text">INSTRUCTOR</p>
          <a href="#" class="btn btn-primary">Message</a>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card">
        <img src="image1.jpg" class="card-img-top" alt="...">
        <div class="card-body text-center">
          <h5 class="card-title">Card title</h5>
          <p class="card-text">INSTRUCTOR</p>
          <a href="#" class="btn btn-primary" >Message</a>
        </div>
      </div>
    </div>
  </div>
</div>










</div>











</body>
