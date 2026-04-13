<?php
require 'conn.php'; 

$utilityId = intval($_GET['utility_id']);
$excluded = [];

$stmt = $conn->prepare("SELECT room_id FROM room_excluded_utilities WHERE bh_utility_id = ?");
$stmt->bind_param("i", $utilityId);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $excluded[] = $row['room_id'];
}

echo json_encode($excluded);
