<?php
include 'conn.php';
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$landlord_id = $input['landlord_id'] ?? 0;
$otp = $input['otp'] ?? '';

if (!$landlord_id || $landlord_id <= 0 || !$otp) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$stmt = $conn->prepare("
    SELECT otp_code, otp_expires_at, attempts 
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

$attempts = $row['attempts'] ?? 0;

if ($row['otp_expires_at'] < date('Y-m-d H:i:s')) {
    echo json_encode(['success' => false, 'message' => 'OTP has expired']);
    $stmt->close();
    exit;
}

if ($row['otp_code'] !== $otp) {
    $attempts += 1;
    if ($attempts >= 3) {
        $stmt = $conn->prepare("
            UPDATE file_access_tokens 
            SET attempts = ?, used_at = NOW() 
            WHERE landlord_id = ? 
            ORDER BY token_id DESC LIMIT 1
        ");
        $stmt->bind_param("ii", $attempts, $landlord_id);
        $stmt->execute();
        echo json_encode([
            'success' => false,
            'message' => 'Invalid OTP. Maximum attempts reached. Please wait before trying again.',
            'showCooldown' => true,
            'cooldown_end' => time() + 3600
        ]);
    } else {
        $stmt = $conn->prepare("
            UPDATE file_access_tokens 
            SET attempts = ? 
            WHERE landlord_id = ? 
            ORDER BY token_id DESC LIMIT 1
        ");
        $stmt->bind_param("ii", $attempts, $landlord_id);
        $stmt->execute();
        echo json_encode([
            'success' => false,
            'message' => "Invalid OTP. Attempt $attempts of 3."
        ]);
    }
    $stmt->close();
    exit;
}

// OTP is valid
// Optionally mark token as used or clear OTP
$stmt = $conn->prepare("
    UPDATE file_access_tokens 
    SET otp_code = NULL, otp_expires_at = NULL, attempts = 0 
    WHERE landlord_id = ? 
    ORDER BY token_id DESC LIMIT 1
");
$stmt->bind_param("i", $landlord_id);
$stmt->execute();

echo json_encode(['success' => true, 'message' => 'OTP verified successfully']);
$stmt->close();
$conn->close();
?>