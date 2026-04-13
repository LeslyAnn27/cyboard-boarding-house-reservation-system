<?php
include 'conn.php';
$landlord_id = intval($_POST['landlord_id']);
$superadmin_id = intval($_POST['superadmin_id']);

$conn->query("
    UPDATE messages
    SET status = 'read'
    WHERE landlord_id = $landlord_id
      AND superadmin_id = $superadmin_id
      AND sender_type = 'landlord'
      AND status = 'unread'
");
?>
