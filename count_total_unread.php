<?php
include 'conn.php';
$superadmin_id = 1;

$stmt = $conn->prepare("SELECT COUNT(*) as unread FROM messages WHERE superadmin_id = ? AND sender_type='landlord' AND status='unread'");
$stmt->bind_param("i", $superadmin_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
echo $result['unread'];
?>