<?php
error_reporting(0);
header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');

require 'conn.php';

// Update expired tokens before fetching
$current_time = date('Y-m-d H:i:s');
$update = $conn->prepare("
    UPDATE file_access_tokens 
    SET status = 'expired' 
    WHERE expires_at <= ? AND status != 'expired'
");
$update->bind_param("s", $current_time);
$update->execute();
$update->close();

// Fetch landlords + their latest token
$sql = "
    SELECT 
        l.landlord_id,
        l.landlord_name,
        l.landlord_email,
        l.landlord_number,
        l.password_set,
        bh.bh_name,
        bh.bh_id,
        fat.status AS token_status,
        fat.expires_at
    FROM landlords l
    LEFT JOIN boarding_houses bh ON l.bh_id = bh.bh_id
    LEFT JOIN file_access_tokens fat 
        ON fat.landlord_id = l.landlord_id
        AND fat.token_id = (
            SELECT MAX(fat2.token_id) 
            FROM file_access_tokens fat2 
            WHERE fat2.landlord_id = l.landlord_id
        )
    ORDER BY l.landlord_id DESC
";

$result = $conn->query($sql);
$landlords = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $landlords[] = [
            'landlord_id'    => $row['landlord_id'],
            'landlord_name'  => $row['landlord_name'],
            'landlord_email' => $row['landlord_email'],
            'landlord_number'=> $row['landlord_number'],
            'password_set'   => $row['password_set'],
            'bh_name'        => $row['bh_name'],
            'bh_id'          => $row['bh_id'],
            'token_status'   => $row['token_status'],
            'expires_at'     => $row['expires_at'],
            'current_time'   => $current_time
        ];
    }
}

echo json_encode($landlords, JSON_PRETTY_PRINT);
$conn->close();
?>
