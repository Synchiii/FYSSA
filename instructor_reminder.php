<?php
session_start();
require_once 'vendor/autoload.php';
require_once 'database.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user'];
$message = '';

// --- Handle Reminder CRUD ---
// Add Reminder
if (isset($_POST['add_reminder'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $remind_at = $_POST['remind_at'];

    $stmt = $conn->prepare("INSERT INTO reminders (user_id, title, description, remind_at) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $title, $description, $remind_at);
    if ($stmt->execute()) $message = "✅ Reminder added successfully!";
    else $message = "❌ Error: " . $stmt->error;
    $stmt->close();
}

// Edit Reminder
if (isset($_POST['edit_reminder'])) {
    $edit_id = (int)$_POST['reminder_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $remind_at = $_POST['remind_at'];

    $stmt = $conn->prepare("UPDATE reminders SET title=?, description=?, remind_at=? WHERE id=? AND user_id=?");
    $stmt->bind_param("sssii", $title, $description, $remind_at, $edit_id, $user_id);
    if ($stmt->execute()) $message = "✅ Reminder updated successfully!";
    else $message = "❌ Error updating reminder: " . $stmt->error;
    $stmt->close();
    header("Location: reminder.php");
    exit();
}

// Delete Reminder
if (isset($_GET['delete'])) {
    $reminder_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM reminders WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $reminder_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: reminder.php");
    exit();
}

// --- Fetch User Info ---
$stmt = $conn->prepare("SELECT first_name, middle_name, last_name, email, profile_picture, google_calendar_id, google_access_token FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$full_name = trim(($user['first_name'] ?? '') . ' ' . ($user['middle_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));

// --- Fetch Reminders ---
$reminders = [];
$stmt = $conn->prepare("SELECT id, title, description, remind_at FROM reminders WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result) $reminders = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// --- Fetch Google Calendar Events ---
$eventsList = [];
if (!empty($user['google_access_token']) && !empty($user['google_calendar_id'])) {
    $client = new Google\Client();
    $client->setAuthConfig('credentials.json');
    $client->setScopes(Google\Service\Calendar::CALENDAR_READONLY);
    $client->setAccessType('offline');
    $client->setPrompt('consent');
    $client->setAccessToken(json_decode($user['google_access_token'], true));

    if ($client->isAccessTokenExpired() && $client->getRefreshToken()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        $stmt = $conn->prepare("UPDATE users SET google_access_token=? WHERE id=?");
        $stmt->bind_param("si", json_encode($client->getAccessToken()), $user_id);
        $stmt->execute();
        $stmt->close();
    }

    $service = new Google\Service\Calendar($client);
    $calendarId = $user['google_calendar_id'];
    $eventsList = $service->events->listEvents($calendarId, [
        'maxResults' => 10,
        'orderBy' => 'startTime',
        'singleEvents' => true
    ])->getItems();
}

// --- Merge Reminders & Google Events ---
$mergedEvents = [];
foreach ($reminders as $r) {
    $mergedEvents[] = [
        'type' => 'reminder',
        'id' => $r['id'],
        'title' => $r['title'],
        'description' => $r['description'],
        'start' => $r['remind_at'],
        'end' => $r['remind_at']
    ];
}
foreach ($eventsList as $e) {
    $mergedEvents[] = [
        'type' => 'google',
        'id' => null,
        'title' => $e->getSummary(),
        'description' => $e->getDescription(),
        'start' => $e->start->dateTime ?? $e->start->date,
        'end' => $e->end->dateTime ?? $e->end->date
    ];
}
// Sort merged events by start time
usort($mergedEvents, function($a, $b) {
    return strtotime($a['start']) <=> strtotime($b['start']);
});
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
.card { background:white; border-radius:10px; padding:30px; box-shadow:0 4px 10px rgba(0,0,0,0.1); margin-bottom:30px; }
.event-list li { margin-bottom:10px; }
.btn-save { background:#394E25; color:white; border:none; border-radius:6px; padding:8px 20px; }
iframe { border:0; width:100%; height:800px; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
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
  <a href="reminder.php" class="active"><i class="bi bi-bell me-2"></i>Reminder</a>
  <a href="Mentor.php"><i class="bi bi-mortarboard me-2"></i>Mentor</a>
  <a href="peer_group.php"><i class="bi bi-people me-2"></i>Peer Group</a>
  <hr class="text-white mx-3">
  <a href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
</div>
<!-- Sidebar -->


<div class="content" >
<h2 class="mb-4"><i class="bi bi-calendar3"></i> Upcoming Events & Reminders</h2>
<?php if ($message) echo "<div class='alert alert-info'>$message</div>"; ?>

<!-- Add Reminder Form -->
<div class="card p-4 ">
    <h5>Add Reminder</h5>
    <form method="post">
        <div class="mb-2
        ">
            <label>Title</label>
            <input type="text" name="title" id="modalTitle" class="form-control" required>
        </div>
        <div class="mb-2
        ">
            <label>Description</label>
            <input type="text" name="description" id="modalDescription" class="form-control">
        </div>
        <div class="mb-2
        ">
            <label>Date & Time</label>
            <input type="datetime-local" name="remind_at" class="form-control" required>
        </div>
        <button type="submit" name="add_reminder" class="btn btn-save"><i class="bi bi-plus-circle"></i> Add Reminder</button>
    </form>
</div>
<!-- Merged Events Table -->
<div class="card p-4 mt-3">
    <h5>Upcoming Events & Reminders</h5>
    <table class="table table-striped mt-2">
        <thead>
            <tr>
                <th>Type</th>
                <th>Title</th>
                <th>Description</th>
                <th>Start</th>
                <th>End</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($mergedEvents)): ?>
                <tr><td colspan="6" class="text-center">No upcoming events or reminders.</td></tr>
            <?php else: ?>
                <?php foreach($mergedEvents as $ev): ?>
                    <tr>
                        <td><?= $ev['type'] === 'reminder' ? '<span class="badge bg-warning">Reminder</span>' : '<span class="badge bg-info">Google Event</span>' ?></td>
                        <td><?= htmlspecialchars($ev['title']) ?></td>
                        <td><?= htmlspecialchars($ev['description'] ?? '') ?></td>
                        <td><?= date('M d, Y h:i A', strtotime($ev['start'])) ?></td>
                        <td><?= date('M d, Y h:i A', strtotime($ev['end'])) ?></td>
                        <td>
                            <?php if($ev['type'] === 'reminder'): ?>
                                <button class="btn btn-sm btn-warning" onclick="toggleEdit(<?= $ev['id'] ?>)">Edit</button>
                                <a href="?delete=<?= $ev['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this reminder?');">Delete</a>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <?php if($ev['type']==='reminder'): ?>
                    <!-- Inline Edit Form -->
                    <tr id="edit-<?= $ev['id'] ?>" style="display:none;">
                        <form method="post">
                            <input type="hidden" name="reminder_id" value="<?= $ev['id'] ?>">
                            <td colspan="2">
                                <input type="text" name="title" class="form-control form-control-sm" value="<?= htmlspecialchars($ev['title']) ?>" required>
                            </td>
                            <td>
                                <input type="text" name="description" class="form-control form-control-sm" value="<?= htmlspecialchars($ev['description']) ?>">
                            </td>
                            <td>
                                <input type="datetime-local" name="remind_at" class="form-control form-control-sm" value="<?= date('Y-m-d\TH:i', strtotime($ev['start'])) ?>" required>
                            </td>
                            <td>
                                <input type="datetime-local" class="form-control form-control-sm" value="<?= date('Y-m-d\TH:i', strtotime($ev['end'])) ?>" disabled>
                            </td>
                            <td>
                                <button type="submit" name="edit_reminder" class="btn btn-sm btn-success">Save</button>
                            </td>
                        </form>
                    </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function toggleEdit(id) {
    const row = document.getElementById('edit-' + id);
    row.style.display = (row.style.display === 'none' || row.style.display === '') ? 'table-row' : 'none';
}
</script>
</div>
</div>          
</body>
</html>
