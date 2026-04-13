<?php
  session_start();
  include("conn.php");
  use PHPMailer\PHPMailer\PHPMailer;
  use PHPMailer\PHPMailer\Exception;

  // Include PHPMailer manually
  require 'PHPMailer/src/PHPMailer.php';
  require 'PHPMailer/src/SMTP.php';
  require 'PHPMailer/src/Exception.php';


if (isset($_POST['send_link'])) {
    header('Content-Type: application/json'); // Important for AJAX
    $landlord_id = intval($_POST['landlord_id']);

    // 1. Check if a pending, unexpired token exists
    $stmt = $conn->prepare("
        SELECT token FROM file_access_tokens 
        WHERE landlord_id = ? AND status = 'pending' AND expires_at > NOW()
    ");
    $stmt->bind_param("i", $landlord_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'A password setup link has already been sent. Please wait until it expires.'
        ]);
        exit;
    }

    // 2. Fetch landlord email and name
    $stmt = $conn->prepare("SELECT landlord_email, landlord_name FROM landlords WHERE landlord_id = ?");
    $stmt->bind_param("i", $landlord_id);
    $stmt->execute();
    $landlord = $stmt->get_result()->fetch_assoc();
    $email = $landlord['landlord_email'];
    $name = $landlord['landlord_name'];

    // 3. Generate token
    $token = bin2hex(random_bytes(32));
    $hashedToken = hash('sha256', $token);
    date_default_timezone_set('Asia/Manila'); 
    $expires_at = date('Y-m-d H:i:s', strtotime('+48 hours'));
    $status = 'pending';

    // 5. Generate link
    $link = "https://cyboardreservations.online//account_verification.php?token=$token";

    // 6. Send email (PHPMailer)
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'YOUR_SMTP_HOST'; // e.g., smtp.gmail.com or your email provider's SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'YOUR_EMAIL@DOMAIN.com';
        $mail->Password = 'YOUR_PASSWORD';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('YOUR_EMAIL@DOMAIN.com', 'YOUR_NAME_OR_COMPANY');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Set Your Landlord Account Password';
        $mail->Body = "
            <h3>Hello $name,</h3>
            <p>Welcome to <b>CyBoard</b>! To get started, please set your password for your account by clicking the link below:</p>
            <p><a href='$link' style='display:inline-block;padding:10px 20px;background-color:#007bff;color:#fff;text-decoration:none;border-radius:5px;'>Set Your Password</a></p>
            <p>For your security, this link will expire in <b>48 hours</b>. If you did not request this, please ignore this email.</p>
            <p>Thank you,<br><b>CyBoard Management</b></p>
        ";

        $mail->send();

        $stmt = $conn->prepare("
            INSERT INTO file_access_tokens (landlord_id, token, status, expires_at, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("isss", $landlord_id, $hashedToken, $status, $expires_at);
        $stmt->execute();

        $_SESSION['alert'] = 'Link sent successfully!';
        $_SESSION['alert_type'] = 'success';
        
        header("Location: superadmin.php?section=landlords");
        exit;

    }catch (Exception $e) {
    // Redirect to your target page 
        $_SESSION['alert'] = 'Failed to send email. Please try again.';
        $_SESSION['alert_type'] = 'error';
        
        header("Location: superadmin.php?section=landlords");
        exit;
    }

}


    if(isset($_POST["addBh"])){
        // Sanitize inputs
        $bhName = mysqli_real_escape_string($conn, $_POST['bhName']);
        $bhAddress = mysqli_real_escape_string($conn, $_POST['bhAddress']);
        $bhLongitude = mysqli_real_escape_string($conn, $_POST['bhLongitude']);
        $bhLatitude = mysqli_real_escape_string($conn, $_POST['bhLatitude']);

        
        
        // Additional validation
        if (empty($bhName) || empty($bhAddress)) {
            die("<script>alert('Boarding house name and address are required'); window.history.back();</script>");
        }
        
        if (!is_numeric($bhLatitude) || !is_numeric($bhLongitude)) {
            die("<script>alert('Invalid coordinates. Please enter valid latitude and longitude.'); window.history.back();</script>");
        }
        
        // Convert to proper types
        $bhLatitude = (float)$bhLatitude;
        $bhLongitude = (float)$bhLongitude;
        
        // Insert with query (though prepared statements are recommended)
        $statement = "INSERT INTO boarding_houses(bh_name, latitude, longitude, bh_address) VALUES('$bhName', $bhLatitude, $bhLongitude, '$bhAddress')";
        
        if(mysqli_query($conn, $statement)){
            $_SESSION['alert'] = 'Boarding House Added successfully';
            $_SESSION['alert_type'] = 'success';
            header("Location: superadmin.php?section=boarding-houses");
            exit;
        } else {
            die("<script>alert('Error adding boarding house: " . addslashes($conn->error) . "'); window.history.back();</script>");
        }
    }
    if (isset($_POST['editBh'])) {
    $id = mysqli_real_escape_string($conn, $_POST['editBhId']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $latitude = mysqli_real_escape_string($conn, $_POST['editBhLatitude']);
    $longitude = mysqli_real_escape_string($conn, $_POST['editBhLongitude']);
    
        if (empty($name) || empty($address)) {
            die("<script>alert('Boarding house name and address are required'); window.history.back();</script>");
        }
        
        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            die("<script>alert('Invalid coordinates. Please enter valid latitude and longitude.'); window.history.back();</script>");
        }
        
        // Convert to proper types
        $latitude = (float)$latitude;
        $longitude = (float)$longitude;
    
    // UPDATE QUERY
    $updateQuery = "UPDATE boarding_houses SET 
                    bh_name = '$name',
                    bh_address = '$address', 
                    latitude = $latitude,
                    longitude = $longitude
                    WHERE bh_id = '$id'";
    
    if (mysqli_query($conn, $updateQuery)) {
        // SUCCESS!
        $_SESSION['alert'] = "Boarding House updated successfully!";
        $_SESSION['alert_type'] = 'success';
        header("Location: superadmin.php?section=boarding-houses");
        exit();
    } else {
        // ERROR
        $_SESSION['alert'] = 'Failed to update. Please try again.';
        $_SESSION['alert_type'] = 'error';
        exit();
    }
}
if (isset($_POST["deleteBh"])) {
    $bh_id =  mysqli_real_escape_string($conn, $_POST["bh_id"]);

    // 1. Validate bh_id as integer
    if (!filter_var($bh_id, FILTER_VALIDATE_INT)) {
        echo "Invalid Boarding House ID.";
        exit;
    }

    // 2. Check if any landlords are assigned to this boarding house
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM landlords WHERE bh_id = ?");
    $checkStmt->bind_param("i", $bh_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();

    if ($checkResult['count'] > 0) {
        $_SESSION['alert'] = 'Cannot delete boarding house. Please remove the assigned landlord first.';
        $_SESSION['alert_type'] = 'error';
        header("Location: superadmin.php?section=boarding-houses");
        exit;
    }

    // 3. If no landlords assigned, delete safely
    $deleteStmt = $conn->prepare("DELETE FROM boarding_houses WHERE bh_id = ?");
    $deleteStmt->bind_param("i", $bh_id);

    if ($deleteStmt->execute()) {
        $deleteStmt->close();
        $_SESSION['alert'] = 'Boarding House Deleted Successfully';
        $_SESSION['alert_type'] = 'success';
        header("Location: superadmin.php?section=boarding-houses");
        exit;
    } else {
        $_SESSION['alert'] = 'Failed to delete. Please try again.';
        $_SESSION['alert_type'] = 'error';
        header("Location: superadmin.php?section=boarding-houses");
        exit;
    }
}


if (isset($_POST['updateLandlord'])) {

    $landlord_id =  mysqli_real_escape_string($conn, $_POST['landlord_id']);
    $new_name = mysqli_real_escape_string($conn, $_POST['landlord_name']);
    $new_email =  mysqli_real_escape_string($conn, $_POST['landlord_email']);
    $new_number = mysqli_real_escape_string($conn, $_POST['landlord_number']);
    $new_bh_id =  mysqli_real_escape_string($conn, $_POST['assignBh']);

    // Get current email to compare
    $stmt = $conn->prepare("SELECT landlord_email FROM landlords WHERE landlord_id = ?");
    $stmt->bind_param("i", $landlord_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $current_email = $row['landlord_email'];

    // Determine if email changed
    $emailChanged = ($new_email !== $current_email);

    // Update landlord info
    if ($emailChanged) {
        $deleteToken = $conn->prepare("DELETE FROM file_access_tokens WHERE landlord_id = ?");
        $deleteToken->bind_param("i", $landlord_id);
        $deleteToken->execute();
        $deleteToken->close();

        // Email changed: reset password_set to 0
        $stmt = $conn->prepare("UPDATE landlords 
                                SET landlord_name = ?, landlord_email = ?, landlord_number = ?, bh_id = ?, password_set = 0 
                                WHERE landlord_id = ?");
    } else {
        // Email not changed
        $stmt = $conn->prepare("UPDATE landlords 
                                SET landlord_name = ?, landlord_email = ?, landlord_number = ?, bh_id = ? 
                                WHERE landlord_id = ?");
    }

    $stmt->bind_param("sssii", $new_name, $new_email, $new_number, $new_bh_id, $landlord_id);
    $stmt->execute();

    header("Location: superadmin.php?section=landlords");
    exit;
}


//  sanitize + ensure phone starts with 0
function formatPhoneNumber($phone) {
    // Remove all non-numeric characters
    $phone = preg_replace('/\D/', '', $phone);

    // Add leading 0 if missing
    if ($phone !== '' && $phone[0] !== '0') {
        $phone = '0' . $phone;
    }

    return $phone;
}


// ADD LANDLORD

if (isset($_POST["addLandlordForm"])) {

    // Sanitize and format inputs
    $landlordName   =  mysqli_real_escape_string($conn,$_POST['landlordName']);
    $landlordEmail  =  mysqli_real_escape_string($conn,$_POST['landlordEmail']);
    $landlordNumber = formatPhoneNumber($_POST['landlordNumber']);
    $boardingHouse  =  mysqli_real_escape_string($conn,$_POST['boardingHouse']);

    // Validate required fields
    if (empty($landlordName) || empty($landlordEmail) || empty($landlordNumber) || empty($boardingHouse)) {
        echo "All fields are required.";
        exit;
    }

    // Use prepared statement
    $stmt = $conn->prepare("INSERT INTO landlords (landlord_name, landlord_email, landlord_number, bh_id) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        echo "Prepare failed: " . $conn->error;
        exit;
    }

    $stmt->bind_param("sssi", $landlordName, $landlordEmail, $landlordNumber, $boardingHouse);

    if ($stmt->execute()) {
        header("Location: superadmin.php?section=landlords");
        exit;
    } else {
        echo "Error inserting landlord: " . $stmt->error;
        exit;
    }

    $stmt->close();
}


if (isset($_POST["deleteLandlord"])) {
    $landlord_id =  mysqli_real_escape_string($conn,$_POST["landlord_id"]);

    $stmt = $conn->prepare("DELETE FROM landlords WHERE landlord_id = ?");
    $stmt->bind_param("i", $landlord_id);

    if ($stmt->execute()) {
        header("Location: superadmin.php?section=landlords");
        exit;
    } else {
        echo "Error deleting landlord: " . $stmt->error;
    }

    $stmt->close();
}

if (isset($_POST['change_password'])) {
    // Database connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $superadmin_id = $_SESSION['superadmin_id'];
    
    // Simple validation
    if (strlen($new_password) < 8) {
        echo "<script>
            alert('Password must be at least 8 characters');
            window.location.href = 'superadmin.php?section=settings';
        </script>";
        exit;
    }
    
    if ($new_password !== $confirm_password) {
        echo "<script>
            alert('Passwords do not match');
            window.location.href = 'superadmin.php?section=settings';
        </script>";
        exit;
    }
    
    // Get current password from database
    $stmt = $conn->prepare("SELECT password FROM superadmin WHERE superadmin_id = ?");
    $stmt->bind_param("i", $superadmin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    // Verify current password
    if (!password_verify($current_password, $row['password'])) {
        echo "<script>
            alert('Current password is incorrect');
            window.location.href = 'superadmin.php?section=settings';
        </script>";
        exit;
    }
    
    // Update password
    $hashed_password = password_hash($new_password, PASSWORD_ARGON2ID);
    $update = $conn->prepare("UPDATE superadmin SET password = ? WHERE superadmin_id = ?");
    $update->bind_param("si", $hashed_password, $superadmin_id);
    
    if ($update->execute()) {
        echo "<script>
            alert('Password updated successfully!');
            window.location.href = 'superadmin.php?section=settings';
        </script>";
    } else {
        echo "<script>
            alert('Failed to update password');
            window.location.href = 'superadmin.php?section=settings';
        </script>";
    }
    
    $stmt->close();
    $update->close();
    $conn->close();
    exit;
}
?>