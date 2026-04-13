<?php
require 'conn.php';

$amenityId = intval($_GET['bhAmenity_id']);
$excluded = [];

$stmt = $conn->prepare("SELECT room_id FROM room_excluded_amenities WHERE bh_amenity_id = ?");
$stmt->bind_param("i", $amenityId);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $excluded[] = $row['room_id'];
}

echo json_encode($excluded); 
?>