<?php
session_start();
require_once "database.php";

// Redirect if not logged in
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user"];
$errorMsg = "";

// Handle profile update (name, gender, birthday, and picture)
if (isset($_POST['save'])) {
    $firstName = trim($_POST['first_name'] ?? '');
    $middleName = trim($_POST['middle_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $birthMonth = trim($_POST['birth_month'] ?? '');
    $birthDay = intval($_POST['birth_day'] ?? 0);
    $birthYear = intval($_POST['birth_year'] ?? 0);
    $profilePic = null;

    // Validate birth date input
    $currentYear = date("Y");
    if ($birthMonth < 1 || $birthMonth > 12 || $birthDay < 1 || $birthDay > 31 || $birthYear < 1905 || $birthYear > $currentYear) {
        $errorMsg = "<div class='alert alert-danger mt-2'>Invalid birth date. Please enter valid values.</div>";
    } else {
        // Handle image upload
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = "uploads/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileTmpPath = $_FILES['profile_picture']['tmp_name'];
            $fileName = basename($_FILES['profile_picture']['name']);
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($fileExt, $allowed)) {
                $newFileName = "profile_" . $user_id . "_" . time() . "." . $fileExt;
                $dest = $uploadDir . $newFileName;
                move_uploaded_file($fileTmpPath, $dest);
                $profilePic = $dest;
            }
        }

        // Update database with or without new image
        if ($profilePic) {
            $stmt = mysqli_prepare($conn, "UPDATE users SET first_name=?, middle_name=?, last_name=?, gender=?, profile_picture=?, birth_month=?, birth_day=?, birth_year=? WHERE user_id=?");
            mysqli_stmt_bind_param($stmt, "ssssssiii", $firstName, $middleName, $lastName, $gender, $profilePic, $birthMonth, $birthDay, $birthYear, $user_id);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE users SET first_name=?, middle_name=?, last_name=?, gender=?, birth_month=?, birth_day=?, birth_year=? WHERE user_id=?");
            mysqli_stmt_bind_param($stmt, "ssssssii", $firstName, $middleName, $lastName, $gender, $birthMonth, $birthDay, $birthYear, $user_id);
        }

        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_error($stmt)) {
            $errorMsg = "<div class='alert alert-danger mt-2'>Database Error: " . mysqli_stmt_error($stmt) . "</div>";
        } else {
            $errorMsg = "<div class='alert alert-success mt-2'>Profile updated successfully!</div>";
        }

        mysqli_stmt_close($stmt);
    }
}

// Fetch user data
$stmt = mysqli_prepare($conn, "SELECT first_name, middle_name, last_name, gender, email, profile_picture, birth_month, birth_day, birth_year, created_at FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result) ?? [];

// Full name and profile image
$full_name = trim(($user['first_name'] ?? '') . ' ' . ($user['middle_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
$profileImage = !empty($user['profile_picture']) ? $user['profile_picture'] : 'default-profile.jpg';
$currentYear = date("Y");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile Page</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="navigation.css">
<style>

</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
  <div class="sidebar-profile">
    <img src="<?= htmlspecialchars($profileImage) ?>" alt="Profile">
    <div class="user-info">
      <h5><?= htmlspecialchars($full_name ?: 'User Name') ?></h5>
      <span><?= htmlspecialchars($user['email'] ?? '') ?></span>
    </div>
  </div>
  <a href="index.php"><i class="bi bi-house-door-fill me-2"></i>Home</a>
  <a href="profile.php" class="active"><i class="bi bi-person-circle me-2"></i>Profile</a>
  <a href="Wellness.php"><i class="bi bi-heart-pulse me-2"></i>Wellness</a>
  <a href="Study_tips.php"><i class="bi bi-lightbulb me-2"></i>Study Tips</a>
  <a href="calendar.php"><i class="bi bi-calendar-event me-2"></i>Calendar</a>
  <a href="reminder.php"><i class="bi bi-bell me-2"></i>Reminder</a>
  <a href="Mentor.php"><i class="bi bi-mortarboard me-2"></i>Mentor</a>
  <a href="peer_group.php"><i class="bi bi-people me-2"></i>Peer Group</a>
  <hr class="text-white mx-3">
  <a href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
</div>

<!-- Main Content -->

<div>
<div class="main-content">
  <div class="profile-card">
    <div class="profile-header">
      <img src="<?= htmlspecialchars($profileImage) ?>" alt="Profile Picture">
      <div class="info">
        <h3><?= htmlspecialchars($full_name ?: 'User Name') ?></h3>
        <p><?= htmlspecialchars($user['email'] ?? '') ?></p>
      </div>
    </div>

    <form method="POST" enctype="multipart/form-data">
      <?= $errorMsg ?>

      <div class="mb-3">
        <label class="form-label">Profile Picture</label>
        <input type="file" name="profile_picture" class="form-control" accept="image/*">
      </div>

      <div class="row">
        <div class="col-md-4">
          <label class="form-label">First Name</label>
          <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Middle Name</label>
          <input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($user['middle_name'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Last Name</label>
          <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>">
        </div>
      </div>

      <div class="mb-3 mt-3">
        <label class="form-label">Gender</label>
        <select name="gender" class="form-select">
          <option value="Male" <?= ($user['gender'] ?? '') == 'Male' ? 'selected' : '' ?>>Male</option>
          <option value="Female" <?= ($user['gender'] ?? '') == 'Female' ? 'selected' : '' ?>>Female</option>
          <option value="Other" <?= ($user['gender'] ?? '') == 'Other' ? 'selected' : '' ?>>Other</option>
        </select>
      </div>

      <!-- 🎂 Birthday Dropdowns -->
      <div class="row mb-3">
        <div class="col-md-4">
          <label class="form-label">Birth Month</label>
          <select name="birth_month" class="form-select">
            <?php for ($m = 1; $m <= 12; $m++): ?>
              <option value="<?= $m ?>" <?= ($user['birth_month'] ?? '') == $m ? 'selected' : '' ?>><?= $m ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Birth Day</label>
          <select name="birth_day" class="form-select">
            <?php for ($d = 1; $d <= 31; $d++): ?>
              <option value="<?= $d ?>" <?= ($user['birth_day'] ?? '') == $d ? 'selected' : '' ?>><?= $d ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Birth Year</label>
          <select name="birth_year" class="form-select">
            <?php for ($y = $currentYear; $y >= 1905; $y--): ?>
              <option value="<?= $y ?>" <?= ($user['birth_year'] ?? '') == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>

      <div class="text-center mt-4">
        <button type="submit" name="save" class="btn-save">Save Changes</button>
      </div>
    </form>
  </div>
</div>
</div>

</body>
</html>
