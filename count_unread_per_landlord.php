<?php
include 'conn.php';
$superadmin_id = 1;

$stmt = $conn->prepare("
    SELECT landlord_id, COUNT(*) as unread 
    FROM messages 
    WHERE superadmin_id = ? AND sender_type='landlord' AND status='unread'
    GROUP BY landlord_id
");
$stmt->bind_param("i", $superadmin_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[$row['landlord_id']] = $row['unread'];
}
echo json_encode($data);
?>