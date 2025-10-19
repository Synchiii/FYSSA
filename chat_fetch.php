<?php
session_start();
require_once "database.php";

if (!isset($_SESSION['user'])) exit("Unauthorized");

$user_id = $_SESSION['user'];
$receiver_id = $_GET['receiver_id'];

// Fetch messages
$query = "SELECT m.*, 
          s.profile_picture AS sender_pic, 
          r.profile_picture AS receiver_pic 
          FROM messages m
          LEFT JOIN users s ON s.user_id = m.sender_id
          LEFT JOIN users r ON r.user_id = m.receiver_id
          WHERE (m.sender_id = ? AND m.receiver_id = ?) 
             OR (m.sender_id = ? AND m.receiver_id = ?)
          ORDER BY m.created_at ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("iiii", $user_id, $receiver_id, $receiver_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Mark as seen
$conn->query("UPDATE messages SET is_seen = 1 
              WHERE receiver_id = $user_id 
              AND sender_id = $receiver_id 
              AND is_seen = 0");

while ($msg = $result->fetch_assoc()):
    $is_you = ($msg['sender_id'] == $user_id);
    $profile_pic = $is_you 
        ? ($msg['sender_pic'] ?: 'default-profile.jpg')
        : ($msg['sender_pic'] ?: 'default-profile.jpg');
    $time = date("h:i A", strtotime($msg['created_at']));
?>
    <div class="d-flex mb-3 <?= $is_you ? 'justify-content-end' : 'justify-content-start' ?>">
        <?php if (!$is_you): ?>
            <img src="<?= htmlspecialchars($profile_pic) ?>" class="chat-avatar me-2">
        <?php endif; ?>
        <div class="message <?= $is_you ? 'you' : '' ?>">
            <div class="bubble">
                <?php if (!empty($msg['message'])): ?>
                    <p><?= htmlspecialchars($msg['message']) ?></p>
                <?php endif; ?>

                <?php if (!empty($msg['file_path'])):
                    $ext = strtolower(pathinfo($msg['file_path'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg','jpeg','png','gif'])): ?>
                        <img src="<?= htmlspecialchars($msg['file_path']) ?>" class="chat-image mt-2">
                    <?php elseif (in_array($ext, ['mp4','webm','ogg'])): ?>
                        <video src="<?= htmlspecialchars($msg['file_path']) ?>" controls class="chat-video mt-2"></video>
                    <?php else: ?>
                        <a href="<?= htmlspecialchars($msg['file_path']) ?>" target="_blank" class="text-decoration-none">📎 View File</a>
                    <?php endif; 
                endif; ?>

                <div class="text-muted small mt-1" style="font-size: 12px;">
                    <?= $time ?>
                    <?php if ($is_you && $msg['is_seen']): ?>
                        <span class="text-primary"> • Seen</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php if ($is_you): ?>
            <img src="<?= htmlspecialchars($profile_pic) ?>" class="chat-avatar ms-2">
        <?php endif; ?>
    </div>
<?php endwhile; ?>
