<?php
include 'conn.php';
$landlord_id = isset($_GET['landlord_id']) ? (int)$_GET['landlord_id'] : 0;

$stmt = $conn->prepare("SELECT COUNT(*) as unread FROM messages WHERE landlord_id = ? AND sender_type = 'superadmin' AND status = 'unread'");
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
echo $result['unread'];
?>