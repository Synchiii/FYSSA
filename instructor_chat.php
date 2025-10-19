<?php
session_start();
require_once "database.php";

// Only allow logged-in instructors
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'INSTRUCTOR') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user"];

// Fetch instructor info
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
<title>Instructor Chat</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="navigation.css">
<style>
body { background: #f8f9fa; }
.content { margin-left:280px; padding:40px; }
.chat-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
}

.chat-image {
  max-width: 200px;
  border-radius: 10px;
  display: block;
}

.chat-video {
  max-width: 200px;
  border-radius: 10px;
  display: block;
}

.chat-container { display: flex; height: 75vh; background: white; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); overflow: hidden; }
.user-list { width: 30%; border-right: 1px solid #ddd; overflow-y: auto; background: #fdfdfd; }
.user-item { padding: 12px; border-bottom: 1px solid #eee; cursor: pointer; transition: 0.3s; }
.user-item:hover { background: #f1f1f1; }

.chat-box { flex: 1; display: flex; flex-direction: column; }
.chat-header { background: #0d6efd; color: white; padding: 12px 15px; font-weight: bold; display: flex; align-items: center; gap: 10px; }
.chat-header i { font-size: 1.2rem; }

.messages { flex: 1; overflow-y: auto; padding: 20px; background: #fafafa; }
.message { margin-bottom: 10px; }
.message.you { text-align: right; }
.message.you .bubble { background: #0d6efd; color: #fff; }
.bubble { display: inline-block; padding: 10px 15px; border-radius: 15px; background: #e9ecef; word-wrap: break-word; max-width: 75%; }

.input-area { display: flex; align-items: center; padding: 10px; border-top: 1px solid #ddd; gap: 8px; }
.input-area input[type="text"] { flex: 1; border-radius: 20px; padding: 10px 15px; border: 1px solid #ccc; }
.file-btn { background: none; border: none; color: #0d6efd; font-size: 1.4rem; cursor: pointer; }
.file-btn:hover { color: #084298; }
#file { display: none; }
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
  <div class="sidebar-profile">
    <img src="<?= htmlspecialchars($profileImage) ?>" alt="Profile">
    <div class="user-info">
      <h5><?= htmlspecialchars($full_name ?: 'Instructor') ?></h5>
      <span><?= htmlspecialchars($user['email'] ?? '') ?></span>
    </div>
  </div>

  <a href="instructor_dashboard.php"><i class="bi bi-house-door-fill me-2"></i>Dashboard</a>
  <a href="instructor_students.php"><i class="bi bi-people-fill me-2"></i>Students</a>
  <a href="instructor_chat.php" class="active"><i class="bi bi-chat-dots me-2" ></i>Chat</a>
  <a href="instructor_schedule.php"><i class="bi bi-calendar-event me-2"></i>Schedule</a>
  <a href="instructor_settings.php"><i class="bi bi-person-circle me-2"></i>Settings</a>
  <hr class="text-white mx-3">
  <a href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
</div>

<!-- Chat Content -->
<div class="content">
  <h3 class="mb-4"><i class="bi bi-chat-dots me-2"></i>Chat with Students</h3>

  <div class="chat-container">
    <div class="user-list">
      <h6 class="p-3 text-center bg-dark text-white">Students</h6>
      <?php
$student = $conn->query("
  SELECT u.user_id, u.first_name, u.last_name,
  (SELECT COUNT(*) FROM messages m 
   WHERE m.sender_id = u.user_id 
     AND m.receiver_id = $user_id 
     AND m.is_seen = 0) AS unread_count
  FROM users u 
  WHERE u.role = 'STUDENT'
");

while ($inst = $student->fetch_assoc()):
  $unread = $inst['unread_count'] > 0;
?>
  <div class="user-item" onclick="openChat(<?= $inst['user_id'] ?>, '<?= htmlspecialchars($inst['first_name'].' '.$inst['last_name']) ?>')">
    <i class="bi bi-person-circle me-2"></i><?= htmlspecialchars($inst['first_name'].' '.$inst['last_name']) ?>
    <?php if ($unread): ?>
      <span class="badge bg-danger float-end">New</span>
    <?php endif; ?>
  </div>
<?php endwhile; ?>

    </div>

    <div class="chat-box">
      <div class="chat-header" id="chatHeader">
        <i class="bi bi-person-circle"></i>
        <span>Select a student to chat</span>
      </div>

      <div class="messages" id="messages">
        <p class="text-center text-muted mt-5">Select a student to start chatting 💬</p>
      </div>

      <!-- Fixed input: allow file or message or both -->
      <form id="chatForm" class="input-area" onsubmit="return sendMessage(event)" enctype="multipart/form-data">
        <input type="hidden" id="receiver_id" name="receiver_id">
        <label for="file" class="file-btn" title="Send file"><i class="bi bi-paperclip"></i></label>
        <input type="file" id="file" name="file" accept="image/*,video/*,.pdf,.doc,.docx">
        <input type="text" id="message" name="message" placeholder="Type your message...">
        <button class="btn btn-primary ms-1" type="submit"><i class="bi bi-send"></i></button>
      </form>
    </div>
  </div>
</div>

<script>
let receiverId = null;
let chatInterval;

function openChat(id, name) {
  receiverId = id;
  document.getElementById('receiver_id').value = id;
  document.getElementById('chatHeader').innerHTML = `<i class="bi bi-person-circle"></i> <span>${name}</span>`;
  loadMessages();

  if (chatInterval) clearInterval(chatInterval);
  chatInterval = setInterval(loadMessages, 2000);
}

function loadMessages() {
  if (!receiverId) return;
  fetch(`chat_fetch.php?receiver_id=${receiverId}`)
    .then(res => res.text())
    .then(data => {
      document.getElementById('messages').innerHTML = data;
      const msgBox = document.getElementById('messages');
      msgBox.scrollTop = msgBox.scrollHeight;
    });
}

function sendMessage(e) {
  e.preventDefault();
  const message = document.getElementById('message').value.trim();
  const file = document.getElementById('file').files[0];

  // Allow sending if either a message OR file exists
  if (!message && !file) return;

  const formData = new FormData(document.getElementById('chatForm'));

  fetch('chat_send.php', { method: 'POST', body: formData })
    .then(() => {
      document.getElementById('message').value = '';
      document.getElementById('file').value = '';
      loadMessages();
    });
}
</script>

</body>
</html>
