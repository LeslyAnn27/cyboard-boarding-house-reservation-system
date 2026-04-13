<?php
include 'conn.php';

$landlord_id = intval($_POST['landlord_id']);
$superadmin_id = intval($_POST['superadmin_id']);
$sender_type = $_POST['sender_type']; // "landlord" or "superadmin"
$message = trim($_POST['message']);

// New messages are always "unread" for the receiver
$status = 'unread';

$stmt = $conn->prepare("
    INSERT INTO messages (landlord_id, superadmin_id, sender_type, message, timestamp, status)
    VALUES (?, ?, ?, ?, NOW(), ?)
");
$stmt->bind_param("iisss", $landlord_id, $superadmin_id, $sender_type, $message, $status);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>
