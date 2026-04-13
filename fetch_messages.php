<?php
include 'conn.php';

$landlord_id = intval($_GET['landlord_id']);
$superadmin_id = intval($_GET['superadmin_id']);

// Fetch all messages between landlord and superadmin
$stmt = $conn->prepare("
    SELECT message_id, landlord_id, superadmin_id, sender_type, message, timestamp, status
    FROM messages
    WHERE landlord_id = ? AND superadmin_id = ?
    ORDER BY timestamp ASC
");
$stmt->bind_param("ii", $landlord_id, $superadmin_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

echo json_encode($messages);

$stmt->close();
$conn->close();
?>
