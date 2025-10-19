<?php
session_start();
require_once "database.php";

// Redirect if not admin
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Handle Approve / Reject actions
if (isset($_POST['action']) && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    if ($_POST['action'] == 'approve') {
        $conn->query("UPDATE users SET status='APPROVED' WHERE user_id=$user_id");
    } elseif ($_POST['action'] == 'reject') {
        $conn->query("UPDATE users SET status='REJECTED' WHERE user_id=$user_id");
    }
}

// Handle role filter
$filter = $_GET['filter'] ?? '';
$where = "WHERE status='PENDING'";
if ($filter === 'student' || $filter === 'instructor') {
    $filterSafe = $conn->real_escape_string($filter);
    $where .= " AND role='$filterSafe'";
}

// Fetch pending users
$sql = "SELECT * FROM users $where ORDER BY role, user_id";
$result = $conn->query($sql);
$users = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pending Users</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="admin_style.css">
<style>
.table-container {
    margin-left: 250px;
    padding: 20px;
    overflow-x: auto; /* allows horizontal scroll on small screens */
}

.table {
    table-layout: fixed; /* prevents layout shifting */
    width: 100%;
    border-collapse: collapse;
    word-wrap: break-word;
}

.table th, .table td {
    text-align: center;
    vertical-align: middle;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.table th {
    font-weight: 600;
    text-transform: uppercase;
    background-color: #212529 !important;
    color: white;
}

.table td:nth-child(1) { width: 1%; }    /* ID */
.table td:nth-child(2) { width: 8%; }    /* Profile */
.table td:nth-child(3) { width: 15%; }   /* Name */
.table td:nth-child(4) { width: 18%; }   /* Email */
.table td:nth-child(5) { width: 10%; }   /* Gender */
.table td:nth-child(6) { width: 10%; }   /* Role */
.table td:nth-child(7) { width: 15%; }   /* Created At */
.table td:nth-child(8) { width: 19%; }   /* Actions */

.profile-pic {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

/* Role dropdown (fixed color as header) */
.role-select {
    background-color: #212529;
    color: white;
    border: 1px solid #212529;
    padding: 3px 6px;
    font-size: 14px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    width: 100px;
    text-align: center;
}

.role-select:focus {
    outline: none;
    box-shadow: 0 0 0 0.15rem rgba(255,255,255,0.3);
}

.role-select option {
    background-color: white;
    color: black;
}

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

<!-- Main Content -->
<div class="table-container">
  <h3>Pending Users</h3>
  <table class="table table-striped table-hover shadow-sm">
    <thead class="table-dark">
      <tr>
        <th>ID</th>
        <th>Profile</th>
        <th>Full Name</th>
        <th>Email</th>
        <th>Gender</th>
        <th>
          <form method="GET" style="display:inline-block;">
            <select name="filter" onchange="this.form.submit()" class="role-select">
              <option value="" <?= $filter === '' ? 'selected' : '' ?>>All</option>
              <option value="student" <?= $filter === 'student' ? 'selected' : '' ?>>STUDENT</option>
              <option value="instructor" <?= $filter === 'instructor' ? 'selected' : '' ?>>INSTRUCTOR</option>
            </select>
          </form>
        </th>
        <th>Created At</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($users)): ?>
        <tr><td colspan="8" class="text-center text-muted">No pending users found.</td></tr>
      <?php else: ?>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><?= $u['user_id'] ?></td>
            <td><img src="<?= htmlspecialchars($u['profile_picture'] ?: 'default-profile.png') ?>" class="profile-pic"></td>
            <td><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= htmlspecialchars($u['gender']) ?></td>
            <td><?= ucfirst($u['role']) ?></td>
            <td><?= htmlspecialchars($u['created_at']) ?></td>
            <td>
              <form method="POST" class="d-flex gap-2 mb-0">
                <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">
                  <i class="bi bi-check-circle"></i> Approve
                </button>
                <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">
                  <i class="bi bi-x-circle"></i> Reject
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
