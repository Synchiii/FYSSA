<?php
require_once "database.php";
session_start();

// Redirect if admin is not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: admin_dashboard.php");
    exit();
}

// Handle role filter
$filter = $_GET['filter'] ?? '';
$whereClause = '';
if ($filter === 'student' || $filter === 'instructor') {
    $filterSafe = $conn->real_escape_string($filter);
    $whereClause = "WHERE role = '$filterSafe'";
}

// Handle update
if (isset($_POST['update_user_id'])) {
    $id = intval($_POST['update_user_id']);
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $gender = $_POST['gender'];
    $birth_month = $_POST['birth_month'];
    $birth_day = $_POST['birth_day'];
    $birth_year = $_POST['birth_year'];
    $role = $_POST['role'];
    $status = $_POST['status'];

    // Handle profile picture upload
    $profile_picture = $_POST['current_picture'];
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $targetDir = "uploads/";
        $fileName = basename($_FILES['profile_picture']['name']);
        $targetFile = $targetDir . time() . "_" . $fileName;
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFile)) {
            $profile_picture = $targetFile;
        }
    }

    $stmt = $conn->prepare("
        UPDATE users SET first_name=?, middle_name=?, last_name=?, email=?, gender=?, 
        birth_month=?, birth_day=?, birth_year=?, role=?, status=?, profile_picture=?
        WHERE user_id=?
    ");
    $stmt->bind_param(
    "sssssssssssi",
    $first_name, $middle_name, $last_name, $email, $gender,
    $birth_month, $birth_day, $birth_year, $role, $status, $profile_picture, $id
);

    $stmt->execute();
    header("Location: " . $_SERVER['PHP_SELF'] . "?filter=$filter");
    exit();
}

// Handle delete
if (isset($_POST['delete_user_id'])) {
    $id = intval($_POST['delete_user_id']);
    $conn->query("DELETE FROM users WHERE user_id=$id");
    header("Location: " . $_SERVER['PHP_SELF'] . "?filter=$filter");
    exit();
}

// Fetch users
$sql = "SELECT * FROM users $whereClause ORDER BY role, user_id";
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
<title>Users Management</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="admin_style.css">
<style>
.table-container {
    margin-left: 260px;
    padding: 20px;
    overflow-x: auto; /* allows horizontal scroll on small screens */
}

.table {
    table-layout: fixed; /* prevents layout shifting */
    width: 100px%;
    border-collapse: collapse;
    word-wrap: break-word;
}

.table th, .table td {
    text-align: center;
    vertical-align: Middle;
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

<div class="table-container">
    <h3>Users Management</h3>
    <table class="table table-striped table-hover shadow-sm">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Profile</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Gender</th>
                <th>Birth Date</th>
                <th>
                    <form method="GET" style="display:inline-block;">
                        <select name="filter" onchange="this.form.submit()" class="role-select">
                            <option value="" <?= $filter === '' ? 'selected' : '' ?>>All</option>
                            <option value="student" <?= $filter === 'student' ? 'selected' : '' ?>>STUDENT</option>
                            <option value="instructor" <?= $filter === 'instructor' ? 'selected' : '' ?>>INSTRUCTOR</option>
                        </select>
                    </form>
                </th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($users as $u): ?>
            <tr>
                <td><?= $u['user_id'] ?></td>
                <td><img src="<?= htmlspecialchars($u['profile_picture'] ?: 'default-profile.png') ?>" class="profile-pic"></td>
                <td><?= htmlspecialchars($u['first_name'].' '.$u['last_name']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['gender']) ?></td>
                <td><?= htmlspecialchars($u['birth_month'].' '.$u['birth_day'].', '.$u['birth_year']) ?></td>
                <td><?= ucfirst($u['role']) ?></td>
                <td><?= htmlspecialchars($u['status']) ?></td>
                <td class="d-flex gap-2">
                    <!-- EDIT BUTTON -->
                    <button type="button" class="btn btn-sm btn-warning" 
                        data-bs-toggle="modal" 
                        data-bs-target="#editModal<?= $u['user_id'] ?>">
                        <i class="bi bi-pencil-square"></i> Edit
                    </button>

                    <!-- DELETE -->
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
                        <input type="hidden" name="delete_user_id" value="<?= $u['user_id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </form>
                </td>
            </tr>

            <!-- EDIT MODAL -->
            <div class="modal fade" id="editModal<?= $u['user_id'] ?>" tabindex="-1">
              <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                  <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header bg-dark text-white">
                      <h5 class="modal-title">Edit User - <?= htmlspecialchars($u['first_name'].' '.$u['last_name']) ?></h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <input type="hidden" name="update_user_id" value="<?= $u['user_id'] ?>">
                      <input type="hidden" name="current_picture" value="<?= htmlspecialchars($u['profile_picture']) ?>">

                      <div class="row g-2">
                        <div class="col-md-4">
                          <label>First Name</label>
                          <input type="text" name="first_name" value="<?= htmlspecialchars($u['first_name']) ?>" class="form-control">
                        </div>
                        <div class="col-md-4">
                          <label>Middle Name</label>
                          <input type="text" name="middle_name" value="<?= htmlspecialchars($u['middle_name']) ?>" class="form-control">
                        </div>
                        <div class="col-md-4">
                          <label>Last Name</label>
                          <input type="text" name="last_name" value="<?= htmlspecialchars($u['last_name']) ?>" class="form-control">
                        </div>

                        <div class="col-md-6 mt-2">
                          <label>Email</label>
                          <input type="email" name="email" value="<?= htmlspecialchars($u['email']) ?>" class="form-control">
                        </div>

                        <div class="col-md-6 mt-2">
  <label>Gender</label>
  <select name="gender" class="form-select">
    <?php 
      $genders = [
        'm' => 'Male',
        'f' => 'Female',
        'o' => 'Other'
      ];
      foreach ($genders as $key => $label): 
    ?>
      <option value="<?= $key ?>" <?= $u['gender'] === $key ? 'selected' : '' ?>><?= $label ?></option>
    <?php endforeach; ?>
  </select>
</div>


                        <div class="col-md-12 mt-2">
                          <label>Birth Date</label>
                          <div class="d-flex gap-2">
                            <select name="birth_month" class="form-select">
                              <?php
                              $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                              foreach ($months as $m) {
                                  $selected = ($u['birth_month'] === $m) ? 'selected' : '';
                                  echo "<option value='$m' $selected>$m</option>";
                              }
                              ?>
                            </select>
                            <select name="birth_day" class="form-select">
                              <?php
                              for ($d=1;$d<=31;$d++){
                                  $v=str_pad($d,2,'0',STR_PAD_LEFT);
                                  $sel=($u['birth_day']==$v)?'selected':'';
                                  echo "<option value='$v' $sel>$v</option>";
                              }
                              ?>
                            </select>
                            <select name="birth_year" class="form-select">
                              <?php
                              $cy=date("Y");
                              for ($y=1905;$y<=$cy;$y++){
                                  $sel=($u['birth_year']==$y)?'selected':'';
                                  echo "<option value='$y' $sel>$y</option>";
                              }
                              ?>
                            </select>
                          </div>
                        </div>

                        <div class="col-md-6 mt-3">
  <label>Role</label>
  <select name="role" class="form-select">
    <?php 
      $roles = [
        'STUDENT' => 'Student',
        'INSTRUCTOR' => 'Instructor',
      ];
      foreach ($roles as $key => $label): 
    ?>
      <option value="<?= $key ?>" <?= $u['role'] === $key ? 'selected' : '' ?>><?= $label ?></option>
    <?php endforeach; ?>
  </select>
</div>

                        <div class="col-md-6 mt-3">
                          <label>Status</label>
                          <select name="status" class="form-select">
                            <?php foreach(['APPROVED','PENDING','REJECTED'] as $s): ?>
                              <option value="<?= $s ?>" <?= $u['status']===$s?'selected':'' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                          </select>
                        </div>

                        <div class="col-md-12 mt-3">
  <label>Profile Picture</label><br>
  <img src="<?= htmlspecialchars($u['profile_picture'] ?: 'default-profile.png') ?>" class="profile-pic mb-2" alt="Profile Picture" width="120" height="120" style="object-fit: cover; border-radius: 50%;">
  <input type="file" name="profile_picture" class="form-control" accept="image/*">
</div>

                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="submit" class="btn btn-primary">Save Changes</button>
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
