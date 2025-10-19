<?php
session_start();
require_once "database.php";

if (!isset($_SESSION['user'])) {
    exit("Unauthorized");
}

$sender_id = $_SESSION['user'];
$receiver_id = intval($_POST['receiver_id'] ?? 0);
$message = trim($_POST['message'] ?? '');
$file_path = null;

// Ensure a valid receiver
if ($receiver_id <= 0) {
    exit("Invalid receiver");
}

// Handle file upload (optional)
if (!empty($_FILES['file']['name'])) {
    $uploadDir = "uploads/chat_files/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = time() . "_" . basename($_FILES['file']['name']);
    $targetPath = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
        $file_path = $targetPath;
    }
}

// Prevent completely empty messages (no text, no file)
if (empty($message) && empty($file_path)) {
    exit("Message or file required");
}

// Insert into DB
$stmt = mysqli_prepare($conn, "INSERT INTO messages (sender_id, receiver_id, message, file_path, created_at) VALUES (?, ?, ?, ?, NOW())");
mysqli_stmt_bind_param($stmt, "iiss", $sender_id, $receiver_id, $message, $file_path);
mysqli_stmt_execute($stmt);

echo "Message sent";
?>
