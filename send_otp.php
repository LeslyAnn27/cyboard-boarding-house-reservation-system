<?php
include 'conn.php';
date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$landlord_id = isset($input['landlord_id']) ? (int)$input['landlord_id'] : 0;

if (!$landlord_id || $landlord_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid landlord ID']);
    exit;
}

// Check current OTP request count and reset time
$stmt = $conn->prepare("
    SELECT otp_requests, request_reset_at 
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

$current_time = time();
$otp_requests = $row['otp_requests'] ?? 0;
$reset_time = !empty($row['request_reset_at']) ? strtotime($row['request_reset_at']) : null;

// Check if limit reached
if ($otp_requests >= 3 && $reset_time && $current_time < $reset_time) {
    echo json_encode(['success' => false, 'message' => 'Daily OTP request limit (3) reached. Try again after ' . date('H:i:s', $reset_time)]);
    $stmt->close();
    exit;
}

// Reset count if 24 hours have passed
if (!$reset_time || $current_time >= $reset_time) {
    $otp_requests = 0;
    $new_reset_time = date('Y-m-d H:i:s', $current_time + 24 * 60 * 60);
    $stmt = $conn->prepare("
        UPDATE file_access_tokens 
        SET otp_requests = 0, request_reset_at = ? 
        WHERE landlord_id = ? 
        ORDER BY token_id DESC LIMIT 1
    ");
    $stmt->bind_param("si", $new_reset_time, $landlord_id);
    $stmt->execute();
}

// Increment OTP request count
$otp_requests++;
$stmt = $conn->prepare("
    UPDATE file_access_tokens 
    SET otp_requests = ? 
    WHERE landlord_id = ? 
    ORDER BY token_id DESC LIMIT 1
");
$stmt->bind_param("ii", $otp_requests, $landlord_id);
$stmt->execute();

// Generate 6-digit OTP
$otp = sprintf("%06d", mt_rand(0, 999999));

// Get landlord phone number
$stmt = $conn->prepare("SELECT landlord_number FROM landlords WHERE landlord_id = ? LIMIT 1");
$stmt->bind_param("i", $landlord_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$phone_number = $row['landlord_number'];
$stmt->close();

// Update the latest token for this landlord
$stmt = $conn->prepare("
    UPDATE file_access_tokens 
    SET otp_code = ?, otp_expires_at = DATE_ADD(NOW(), INTERVAL 5 MINUTE), attempts = 0, used_at = NULL 
    WHERE landlord_id = ? 
    ORDER BY token_id DESC LIMIT 1
");
$stmt->bind_param("si", $otp, $landlord_id);

if ($stmt->execute()) {
    // TextBee API integration
    $device_id = 'YOUR_DEVICE_ID'; // Replace with your actual TextBee device ID
    $api_key = 'YOUR_API_KEY';    
    $message = "Your OTP is $otp. It expires in 5 minutes.";

    $data = [
        'recipients' => [$phone_number],
        'message' => $message
    ];

    $ch = curl_init("https://api.textbee.dev/api/v1/gateway/devices/$device_id/send-sms");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-api-key: ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        echo json_encode(['success' => false, 'message' => 'Failed to send OTP: ' . $curl_error]);
        exit;
    }

    $expires_unix = time() + 300; // 5 minutes
    echo json_encode([
        'success' => true,
        'message' => 'OTP sent successfully via TextBee',
        'otp_expires_at' => date('Y-m-d H:i:s', $expires_unix),
        'otp_expires_unix' => $expires_unix
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to generate OTP']);
}

$stmt->close();
$conn->close();
?>