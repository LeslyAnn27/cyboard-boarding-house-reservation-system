<?php
include 'conn.php';
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$landlord_id = $input['landlord_id'] ?? 0;
$new_password = $input['new_password'] ?? '';

if (!$landlord_id || $landlord_id <= 0 || !$new_password) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// password length
if (strlen($new_password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
    exit;
}

// check if token is valid and not used
$stmt = $conn->prepare("
    SELECT status, expires_at 
    FROM file_access_tokens 
    WHERE landlord_id = ? 
    ORDER BY token_id DESC LIMIT 1
");
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'No valid token found']);
    $stmt->close();
    exit;
}

$current_time = date('Y-m-d H:i:s');
if ($row['status'] == 'used') {
    echo json_encode(['success' => false, 'message' => 'Token has already been used']);
    $stmt->close();
    exit;
}
if ($row['expires_at'] < $current_time) {
    echo json_encode(['success' => false, 'message' => 'Token has expired']);
    $stmt->close();
    exit;
}

// Hash the password
$hashed_password = password_hash($new_password, PASSWORD_ARGON2ID);
// Update landlord's password
$stmt = $conn->prepare("
    UPDATE landlords 
    SET landlord_password = ?, password_set = 1 
    WHERE landlord_id = ?
");
$stmt->bind_param("si", $hashed_password, $landlord_id);

if ($stmt->execute()) {
    // Mark token as used
    $stmt = $conn->prepare("
        UPDATE file_access_tokens 
        SET status = 'used', used_at = NOW() 
        WHERE landlord_id = ? 
        ORDER BY token_id DESC LIMIT 1
    ");
    $stmt->bind_param("i", $landlord_id);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Password set successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to set password']);
}

$stmt->close();
$conn->close();
?>