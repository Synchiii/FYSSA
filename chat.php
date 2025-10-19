<?php
session_start();
require_once "database.php";

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user'];

// Fetch all users except current
$users = $conn->query("SELECT user_id, first_name, last_name, role FROM users WHERE user_id != $user_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Chat System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background: #f5f6fa; }
.chat-container { display: flex; height: 90vh; }
.user-list { width: 25%; background: #fff; border-right: 1px solid #ddd; overflow-y: auto; }
.user-item { padding: 12px; cursor: pointer; border-bottom: 1px solid #eee; }
.user-item:hover { background: #f1f1f1; }
.chat-box { flex: 1; display: flex; flex-direction: column; background: #fff; }
.messages { flex: 1; overflow-y: auto; padding: 20px; }
.message { margin-bottom: 10px; }
.message.you { text-align: right; }
.message.you .bubble { background: #0d6efd; color: white; }
.bubble { display: inline-block; padding: 10px 15px; border-radius: 15px; background: #e9ecef; }
.input-area { display: flex; padding: 10px; border-top: 1px solid #ddd; }
.input-area input { flex: 1; border-radius: 20px; padding: 10px 15px; border: 1px solid #ccc; }
</style>
</head>
<body>

<div class="container-fluid chat-container mt-3">
  <div class="user-list">
    <h5 class="text-center py-3 bg-dark text-white">Available Users</h5>
    <?php while ($u = $users->fetch_assoc()): ?>
      <div class="user-item" onclick="openChat(<?= $u['user_id'] ?>, '<?= $u['first_name'] . ' ' . $u['last_name'] ?>')">
        <i class="bi bi-person-circle me-2"></i>
        <?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?> 
        <small class="text-muted">(<?= $u['role'] ?>)</small>
      </div>
    <?php endwhile; ?>
  </div>

  <div class="chat-box">
    <div class="messages" id="messages">
      <p class="text-muted text-center mt-5">Select a user to start chatting 💬</p>
    </div>

    <form id="chatForm" class="input-area" onsubmit="return sendMessage(event)">
      <input type="hidden" id="receiver_id" name="receiver_id">
      <input type="text" id="message" name="message" placeholder="Type your message..." required>
      <button class="btn btn-primary ms-2" type="submit"><i class="bi bi-send-fill"></i></button>
    </form>
  </div>
</div>

<script>
let currentUser = <?= $user_id ?>;
let receiverId = null;
let chatInterval;

function openChat(id, name) {
  receiverId = id;
  document.getElementById('receiver_id').value = id;
  loadMessages();

  if (chatInterval) clearInterval(chatInterval);
  chatInterval = setInterval(loadMessages, 2000);
}

function loadMessages() {
  if (!receiverId) return;

  fetch(`fetch_messages.php?receiver_id=${receiverId}`)
    .then(res => res.text())
    .then(data => {
      document.getElementById('messages').innerHTML = data;
      const msgBox = document.getElementById('messages');
      msgBox.scrollTop = msgBox.scrollHeight;
    });
}

function sendMessage(e) {
  e.preventDefault();
  const formData = new FormData(document.getElementById('chatForm'));

  fetch('send_message.php', { method: 'POST', body: formData })
    .then(() => {
      document.getElementById('message').value = '';
      loadMessages();
    });
}
</script>

</body>
</html>
