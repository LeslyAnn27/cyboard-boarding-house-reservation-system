<?php
include 'conn.php';
$superadmin_id = isset($_GET['superadmin_id']) ? (int)$_GET['superadmin_id'] : 1;
$stmt = $conn->prepare("
    SELECT DISTINCT l.landlord_name, l.landlord_email, 
           MAX(m.timestamp) as last_message_time
    FROM messages m 
    JOIN landlords l ON m.landlord_id = l.landlord_id 
    WHERE m.sender_type = 'landlord' AND m.superadmin_id = ?
    GROUP BY l.landlord_id 
    ORDER BY last_message_time DESC
");
$stmt->bind_param("i", $superadmin_id);
$stmt->execute();
$result = $stmt->get_result();
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
echo json_encode($data);
?>