<?php
session_start();
require_once "database.php";

// Redirect if not logged in
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user"];

// Fetch user data
$stmt = mysqli_prepare($conn, "SELECT first_name, middle_name, last_name, email, profile_picture FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result) ?? [];

// Full name and profile image
$full_name = trim(($user['first_name'] ?? '') . ' ' . ($user['middle_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
$profileImage = !empty($user['profile_picture']) ? $user['profile_picture'] : 'default-profile.jpg';

// Instructor username (for posts)
$instructor_username = $full_name ?: "Instructor";

// Message
$message = "";

// ADD POST
if (isset($_POST['add_post'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $image = NULL;

    if (!empty($_FILES['image']['name'])) {
        $imageName = time() . "_" . basename($_FILES['image']['name']);
        $targetPath = "uploads/" . $imageName;

        if (!is_dir("uploads")) mkdir("uploads", 0777, true);

        move_uploaded_file($_FILES['image']['tmp_name'], $targetPath);
        $image = $targetPath;
    }

    $stmt = $conn->prepare("INSERT INTO wellness_posts (instructor_username, title, content, image) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $instructor_username, $title, $content, $image);
    $stmt->execute();
    $message = "✅ wellness posts added successfully!";
}

// DELETE POST
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM wellness_posts WHERE id=? AND instructor_username=?");
    $stmt->bind_param("is", $id, $instructor_username);
    $stmt->execute();
    $message = "🗑️ Post deleted successfully!";
}

// UPDATE POST
if (isset($_POST['update_post'])) {
    $id = intval($_POST['post_id']);
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    $stmt = $conn->prepare("UPDATE wellness_posts SET title=?, content=? WHERE id=? AND instructor_username=?");
    $stmt->bind_param("ssis", $title, $content, $id, $instructor_username);
    $stmt->execute();
    $message = "✏️ Post updated successfully!";
}

// FETCH POSTS
$stmt = $conn->prepare("SELECT * FROM wellness_posts WHERE instructor_username=? ORDER BY created_at DESC");
$stmt->bind_param("s", $instructor_username);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>wellness_posts</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="navigation.css">
<style>


.content { margin-left:280px; padding:40px; }
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

  <!-- Back/Home Link with House Icon -->
  <a href="index.php"><i class="bi bi-house-door-fill me-2"></i>Home</a>
  <a href="profile.php"><i class="bi bi-person-circle me-2"></i>Profile</a>
  <a href="wellness.php" class=active><i class="bi bi-heart-pulse me-2"></i>Wellness</a>
  <a href="Study_tips.php"><i class="bi bi-lightbulb me-2"></i>Study Tips</a>
  <a href="calendar.php"><i class="bi bi-calendar-event me-2"></i>Calendar</a>
  <a href="reminder.php"><i class="bi bi-bell me-2"></i>Reminder</a>
  <a href="Mentor.php"><i class="bi bi-mortarboard me-2"></i>Mentor</a>
  <a href="peer_group.php"><i class="bi bi-people me-2"></i>Peer Group</a>

  <hr class="text-white mx-3">

  <!-- Logout -->
  <a href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
</div>

<!-- Main Content -->
<div class="main-content">
  <div class="wellness-card">
    <h2 class="text-dark text-center mb-4" style="font-weight:700; font-family: 'Poppins', sans-serif;"> WELLNESS POST!</h2>

    <?php if ($message): ?>
      <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Add Post Form -->
    <div class="card p-4 mb-5 shadow-sm border-0">
      <h5 class="mb-3" style="font-weight:600; font-family: 'Poppins', sans-serif;">➕ Add a New Tip</h5>
      <form method="POST" enctype="multipart/form-data">
        <input type="text" name="title" class="form-control mb-3" placeholder="Title" required>
        <textarea name="content" class="form-control mb-3" rows="4" placeholder="Write your tip..." required></textarea>
        <input type="file" name="image" class="form-control mb-3" accept=".jpg, .jpeg, .png">
        <button type="submit" name="add_post" class="btn btn-dark w-100">Publish Tip</button>
      </form>
    </div>

    <!-- Posts List -->
    <h4 class="mb-3" style="font-weight:600;">📢 Your Tips</h4>
    <div class="row g-3">
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <div class="col-md-6">
            <div class="card p-3 shadow-sm border-0">
              <?php if (!empty($row['image'])): ?>
                <img src="<?= htmlspecialchars($row['image']) ?>" class="card-img-top mb-2 rounded" style="max-height:200px; object-fit:cover;" alt="Tip Image">
              <?php endif; ?>
              <h5 class="card-title" style="font-weight:600;"><?= htmlspecialchars($row['title']) ?></h5>
              <p class="card-text"><?= nl2br(htmlspecialchars($row['content'])) ?></p>
              <small class="text-muted">Posted on <?= htmlspecialchars($row['created_at']) ?></small>
              <div class="mt-2 d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>">Edit</button>
                <a href="?delete=<?= $row['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
              </div>
            </div>
          </div>

          <!-- Edit Modal -->
          <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
            <div class="modal-dialog">
              <div class="modal-content">
                <form method="POST">
                  <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Edit Tip</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <input type="hidden" name="post_id" value="<?= $row['id'] ?>">
                    <input type="text" name="title" class="form-control mb-3" value="<?= htmlspecialchars($row['title']) ?>" required>
                    <textarea name="content" class="form-control" rows="4" required><?= htmlspecialchars($row['content']) ?></textarea>
                  </div>
                  <div class="modal-footer">
                    <button type="submit" name="update_post" class="btn btn-dark">Save Changes</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p class="text-muted">No tips yet. Share your first study tip!</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
