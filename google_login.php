<?php
session_start();
require_once "conn.php";

$email = strtolower(trim($_POST['email'] ?? ''));
if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email is required.']);
    exit;
}

//Check Superadmin 
$stmt = $conn->prepare("SELECT superadmin_id FROM superadmin WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $_SESSION['superadmin_id'] = $row['superadmin_id'];
    echo json_encode(['success' => true, 'redirect' => 'superadmin.php']);  // Change to your page
    exit;
}

//Check Landlord
$landlord = $conn->prepare("SELECT landlord_id, password_set FROM landlords WHERE landlord_email = ?");
$landlord->bind_param("s", $email);
$landlord->execute();
$l_result = $landlord->get_result();

if ($l_result->num_rows > 0) {
    $l_row = $l_result->fetch_assoc();

    if ($l_row['password_set'] == 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Password not set. Please set your password before logging in.'
        ]);
        exit;
    }

    $_SESSION['landlord_id'] = $l_row['landlord_id'];
    echo json_encode(['success' => true, 'redirect' => 'admin.php']);
    exit;
}

//Check Tenant
$tenant = $conn->prepare("SELECT tenant_id FROM tenants WHERE email = ?");
$tenant->bind_param("s", $email);
$tenant->execute();
$t_result = $tenant->get_result();

if ($t_result->num_rows > 0) {
    $t_row = $t_result->fetch_assoc();
    $_SESSION['tenant_id'] = $t_row['tenant_id'];
    $_SESSION['logged_in'] = true;
    echo json_encode(['success' => true, 'redirect' => 'index.php']);
    exit;
}

//Not registered
echo json_encode([
    'success' => false,
    'message' => 'This Gmail is not registered. Please register first.'
]);
exit;
?>