<?php
include 'conn.php';
$landlord_id = (int)$_POST['landlord_id'];
$superadmin_id = (int)$_POST['superadmin_id'];

$stmt = $conn->prepare("UPDATE messages SET status='read' WHERE landlord_id = ? AND sender_type = 'superadmin'");
$stmt->bind_param("i", $landlord_id);
$stmt->execute();

echo json_encode(['status' => 'success']);
?>