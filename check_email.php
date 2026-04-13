<?php
include 'conn.php';
header('Content-Type: application/json');

if (isset($_POST['email'])) {
    $emailToCheck = $_POST['email'];
    $exists = false;
    $passwordNotSet = false;

    // Check in tenants table
    $stmt = $conn->prepare("SELECT email FROM tenants WHERE email = ?");
    $stmt->bind_param("s", $emailToCheck);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $exists = true;
    } else {
        // Check in landlords table
        $stmt = $conn->prepare("SELECT landlord_email, password_set FROM landlords WHERE landlord_email = ?");
        $stmt->bind_param("s", $emailToCheck);
        $stmt->execute();
        $landlordResult = $stmt->get_result();

        if ($landlordResult->num_rows > 0) {
            $landlord = $landlordResult->fetch_assoc();
            $exists = true;
        }
    }

    echo json_encode(['exists' => $exists]);
}
?>
