<?php
session_start();
require_once "database.php";

// Redirect if not student
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'STUDENT') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user"];

// Fetch student info
$stmt = mysqli_prepare($conn, "SELECT first_name, middle_name, last_name, email, profile_picture FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result) ?? [];

$full_name = trim(($user['first_name'] ?? '') . ' ' . ($user['middle_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
$profileImage = !empty($user['profile_picture']) ? $user['profile_picture'] : 'default-profile.jpg';

// --- Handle Like / Dislike ---
if (isset($_POST['action']) && isset($_POST['tip_id'])) {
    $tip_id = intval($_POST['tip_id']);
    $action = $_POST['action'] === 'like' ? 'like' : 'dislike';

    // Check if user has already reacted
    $check = $conn->prepare("SELECT reaction FROM study_tip_reactions WHERE user_id=? AND tip_id=?");
    $check->bind_param("ii", $user_id, $tip_id);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        $prev = $res->fetch_assoc()['reaction'];

        // If user clicked a different reaction, update it
        if ($prev !== $action) {
            $update = $conn->prepare("UPDATE study_tip_reactions SET reaction=? WHERE user_id=? AND tip_id=?");
            $update->bind_param("sii", $action, $user_id, $tip_id);
            $update->execute();

            // Adjust like/dislike counts
            if ($action === 'like') {
                $conn->query("UPDATE study_tips SET likes = likes + 1, dislikes = dislikes - 1 WHERE id=$tip_id");
            } else {
                $conn->query("UPDATE study_tips SET dislikes = dislikes + 1, likes = likes - 1 WHERE id=$tip_id");
            }
        }
    } else {
        // First reaction
        $insert = $conn->prepare("INSERT INTO study_tip_reactions (user_id, tip_id, reaction) VALUES (?, ?, ?)");
        $insert->bind_param("iis", $user_id, $tip_id, $action);
        $insert->execute();

        if ($action === 'like') {
            $conn->query("UPDATE study_tips SET likes = likes + 1 WHERE id=$tip_id");
        } else {
            $conn->query("UPDATE study_tips SET dislikes = dislikes + 1 WHERE id=$tip_id");
        }
    }

    header("Location: student_tips.php");
    exit();
}

// --- Fetch all published tips ---
$result = $conn->query("SELECT * FROM study_tips ORDER BY created_at DESC");

// --- Get student's reactions ---
$reaction_map = [];
$react_stmt = $conn->prepare("SELECT tip_id, reaction FROM study_tip_reactions WHERE user_id=?");
$react_stmt->bind_param("i", $user_id);
$react_stmt->execute();
$react_result = $react_stmt->get_result();
while ($r = $react_result->fetch_assoc()) {
    $reaction_map[$r['tip_id']] = $r['reaction'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Study Tips</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="navigation.css">
<style>
.content { margin-left:280px; padding:40px; }
.tip-card { transition: transform 0.2s ease, box-shadow 0.3s ease; }
.tip-card:hover { transform: translateY(-5px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.tip-card img { max-height: 200px; object-fit: cover; border-radius: 10px; }
.reacted { font-weight: bold; }
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


  <!-- Back/Home Link with House Icon -->
  <a href="student_dashboard.php"><i class="bi bi-house-door-fill me-2"></i>Home</a>
  <a href="student_tips.php"class=active><i class="bi bi-lightbulb me-2"></i>Tips</a>
  <a href="calendar.php"><i class="bi bi-calendar-event me-2"></i>Reminder</a>
  <a href="student_chat.php"><i class="bi bi-mortarboard me-2"></i>Chat</a>
  


  <a href="instructor_settings.php"><i class="bi bi-person-circle me-2"></i>Settings</a>
  <hr class="text-white mx-3">
  <hr class="text-white mx-3">
  <hr class="text-white mx-3">
  <hr class="text-white mx-3">
  <hr class="text-white mx-3">

    <a href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
</div>

<!-- Main Content -->
<div class="content">
  <h2 class="text-center mb-4 fw-bold">📚 STUDY TIPS BY INSTRUCTORS</h2>

  <div class="row g-4">
    <?php if ($result && $result->num_rows > 0): ?>
      <?php while ($tip = $result->fetch_assoc()): ?>
        <div class="col-md-6 col-lg-4">
          <div class="card tip-card p-3 shadow-sm border-0">
            <?php if (!empty($tip['image'])): ?>
              <img src="<?= htmlspecialchars($tip['image']) ?>" class="card-img-top mb-2" alt="Tip Image">
            <?php endif; ?>

            <h5 class="card-title"><?= htmlspecialchars($tip['title']) ?></h5>
            <p class="card-text"><?= nl2br(htmlspecialchars($tip['content'])) ?></p>

            <small class="text-muted d-block mb-2">
              👤 Posted by: <strong><?= htmlspecialchars($tip['instructor_username']) ?></strong><br>
              🕒 <?= date("M d, Y", strtotime($tip['created_at'])) ?>
            </small>

            <div class="d-flex align-items-center gap-2">
              <!-- Like -->
              <form method="POST" class="d-inline">
                <input type="hidden" name="tip_id" value="<?= $tip['id'] ?>">
                <input type="hidden" name="action" value="like">
                <button type="submit" class="btn btn-sm <?= (isset($reaction_map[$tip['id']]) && $reaction_map[$tip['id']] === 'like') ? 'btn-success reacted' : 'btn-outline-success' ?>">
                  👍 <?= $tip['likes'] ?>
                </button>
              </form>

              <!-- Dislike -->
              <form method="POST" class="d-inline">
                <input type="hidden" name="tip_id" value="<?= $tip['id'] ?>">
                <input type="hidden" name="action" value="dislike">
                <button type="submit" class="btn btn-sm <?= (isset($reaction_map[$tip['id']]) && $reaction_map[$tip['id']] === 'dislike') ? 'btn-danger reacted' : 'btn-outline-danger' ?>">
                  👎 <?= $tip['dislikes'] ?>
                </button>
              </form>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p class="text-muted text-center">No tips available yet. Please check back later!</p>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
