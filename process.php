<?php
session_start();
include("conn.php");
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer manually
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';
if (isset($_POST['register'])) {

    $fullname   = mysqli_real_escape_string($conn, $_POST['fullName']);
    $id_no      = mysqli_real_escape_string($conn, $_POST['id_no']);
    $email      = mysqli_real_escape_string($conn, $_POST['email']);
    $password   = password_hash($_POST['password'], PASSWORD_ARGON2ID);
    $phone      = mysqli_real_escape_string($conn, $_POST['phone']);
    $photo      = mysqli_real_escape_string($conn, $_POST['photoURL']);
    $yearLevel  = mysqli_real_escape_string($conn, $_POST['yearLevel']);   
    $program    = mysqli_real_escape_string($conn, $_POST['program']);     

    // Insert user into database
    $stmt = $conn->prepare("INSERT INTO tenants (name, stud_id, email, password, phone_number, year_level, program, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $fullname, $id_no, $email, $password, $phone, $yearLevel, $program, $photo);

    if ($stmt->execute()) {
        echo "<script>alert('Registration successful! You can now log in.'); window.location='login.php';</script>";
    } else {
        echo "<script>alert('Error registering account: " . $stmt->error . "'); window.history.back();</script>";
    }

    $stmt->close();
    $conn->close();
}

if(isset($_POST['reserve'])){
    $bh_id = mysqli_real_escape_string($conn,$_POST['bhId']);
    $tenant_id = mysqli_real_escape_string($conn, $_POST['tenantId']);
    $room_id = mysqli_real_escape_string($conn, $_POST['roomId']);
    $move_in = mysqli_real_escape_string($conn, $_POST['moveIn']);
    $duration = mysqli_real_escape_string($conn, $_POST['duration']);
    $additionalNotes = mysqli_real_escape_string($conn, $_POST['additionalNotes']);

    // Check if user already reserved this room
    $room_check_stmt = $conn->prepare("SELECT COUNT(*) as room_reserved FROM reservations WHERE tenant_id = ? AND room_id = ? AND status = 'pending'");
    $room_check_stmt->bind_param("ii", $tenant_id, $room_id);
    $room_check_stmt->execute();
    $room_result = $room_check_stmt->get_result();
    $room_row = $room_result->fetch_assoc();
    $room_reserved = $room_row['room_reserved'];
    $room_check_stmt->close();

    if($room_reserved > 0){
        $_SESSION['already_reserved'] = 'already reserved';
        $_SESSION['tenant_id'] = $tenant_id;
        $_SESSION['bh_id'] = $bh_id;
        header("Location: boardinghouse.php");
        exit;
    }

    $check_stmt = $conn->prepare("
    SELECT COUNT(*) AS reservation_count 
    FROM reservations 
    WHERE tenant_id = ? AND (status = 'waiting' or status = 'active')
    ");
    $check_stmt->bind_param("i", $tenant_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $count_row = $check_result->fetch_assoc();

    if ($count_row['reservation_count'] > 0) {
        // check if tenant already had an active reservation
        $_SESSION['active reservation'] = 'active reservation';
        $_SESSION['tenant_id'] = $tenant_id;
        $_SESSION['bh_id'] = $bh_id;
        header("Location: boardinghouse.php");
        exit;
    }

    // Check if user already has 3 reservations
    $count_stmt = $conn->prepare("SELECT COUNT(*) as reservation_count FROM reservations WHERE tenant_id = ? AND status = 'pending'");
    $count_stmt->bind_param("i", $tenant_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_row = $count_result->fetch_assoc();
    $reservation_count = $count_row['reservation_count'];
    $count_stmt->close();

    if($reservation_count >= 3){
        $_SESSION['maximum_reservations'] = 'maximum reservations';
        $_SESSION['tenant_id'] = $tenant_id;
        $_SESSION['bh_id'] = $bh_id;
        header("Location: boardinghouse.php");
        exit;
    }

    if($duration == 'custom'){
        $duration_value = $_POST['customDuration'];
    }else{
        $duration_value = $duration;
    }
    $stmt = $conn->prepare("INSERT INTO reservations (tenant_id, room_id, move_in, duration, notes, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("iisss", $tenant_id, $room_id, $move_in, $duration_value, $additionalNotes);

    if($stmt->execute()){
        // Get landlord and tenant details
        $email_query = $conn->prepare("
            SELECT 
                u.landlord_email as landlord_email, 
                u.landlord_name as landlord_fname, 
                u.landlord_number as landlord_phone, 
                t.name as tenant_fname, 
                t.email as tenant_email, 
                t.phone_number as tenant_phone, 
                bh.bh_name, 
                r.room_type 
                FROM rooms r 
                JOIN boarding_houses bh ON r.bh_id = bh.bh_id 
                JOIN landlords u ON r.bh_id = u.bh_id 
                JOIN tenants t ON t.tenant_id = ?
                WHERE r.room_id = ?
        ");
        $email_query->bind_param("ii", $tenant_id, $room_id);
        $email_query->execute();
        $email_result = $email_query->get_result();
        $email_data = $email_result->fetch_assoc();
        $email_query->close();

        if($email_data){
            // TextBee SMS notification
            $device_id = 'YOUR_DEVICE_ID'; // Replace with your actual TextBee device ID
            $api_key = 'YOUR_API_KEY';    
            $tenant_name = $email_data['tenant_fname'];
            // Format phone number to international format
            $phone_number = $email_data['landlord_phone'];
            if (substr($phone_number, 0, 1) == '0') {
                $phone_number = '+63' . substr($phone_number, 1);
            } else if (substr($phone_number, 0, 3) != '+63') {
                $phone_number = '+63' . $phone_number;
            }
            
            $message = "New Reservation Request!\n\n";
            $message .= "Room: " . $email_data['room_type'] . "\n";
            $message .= "Tenant: " . $tenant_name . "\n";
            $message .= "Move-in: " . $move_in . "\n";
            $message .= "Duration: " . $duration_value . "\n";
            if(!empty($additionalNotes)){
                $message .= "Notes: " . $additionalNotes . "\n";
            }
            $message .= "\nPlease check your account to review this request.";

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
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $sms_failed = false;
            if(!empty($curl_error) || !in_array($http_code, [200, 201, 202])){
                $sms_failed = true;
               
            }

            // Brevo SMTP email

            if($sms_failed){

                $mail = new PHPMailer(true);

                try {
                    // SMTP Configuration
                    $mail->isSMTP();
                    $mail->Host = 'YOUR_SMTP_HOST'; // e.g., smtp.gmail.com or your email provider's SMTP server
                    $mail->SMTPAuth = true;
                    $mail->Username = 'YOUR_EMAIL@DOMAIN.com';
                    $mail->Password = 'YOUR_PASSWORD';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    // Email settings
                    $mail->setFrom('YOUR_EMAIL@DOMAIN.com', 'YOUR_NAME_OR_COMPANY');
                    $mail->addAddress($email_data['landlord_email'], $email_data['landlord_fname']);

                    $mail->isHTML(true);
                    $mail->Subject = 'New Reservation Request - ' . $email_data['bh_name'];
                    
                    $landlord_name = $email_data['landlord_fname'];
                    
                    $mail->Body = "
                    <html>
                    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                        <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>
                            <h2 style='color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px;'>New Reservation Request</h2>
                            
                            <p>Dear {$landlord_name},</p>
                            
                            <p>You have received a new reservation request for your boarding house.</p>
                            
                            <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                                <h3 style='margin-top: 0; color: #2c3e50;'>Reservation Details:</h3>
                                <p><strong>Boarding House:</strong> {$email_data['bh_name']}</p>
                                <p><strong>Room Type:</strong> {$email_data['room_type']}</p>
                                <p><strong>Tenant Name:</strong> {$tenant_name}</p>
                                <p><strong>Tenant Email:</strong> {$email_data['tenant_email']}</p>
                                <p><strong>Tenant Phone:</strong> {$email_data['tenant_phone']}</p>
                                <p><strong>Move-in Date:</strong> {$move_in}</p>
                                <p><strong>Duration:</strong> {$duration_value}</p>
                                " . (!empty($additionalNotes) ? "<p><strong>Additional Notes:</strong> {$additionalNotes}</p>" : "") . "
                            </div>
                            
                            <p>Please log in to your account to review and respond to this reservation request.</p>
                            
                            <p style='margin-top: 30px;'>Thank You,<br><b>CyBoard Management</b></p>
                        </div>
                    </body>
                    </html>
                    ";

                    $mail->send();
                } catch (Exception $e) {
                    echo "Email sending failed: {$mail->ErrorInfo}";
                }
            }
        }

        $_SESSION['reservation_successful'] = 'successful reservation';
        $_SESSION['tenant_id'] = $tenant_id;
        $_SESSION['bh_id'] = $bh_id;
        header("Location: boardinghouse.php");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

if (isset($_POST['reservation_id'])) {
    $reservation_id = mysqli_real_escape_string($conn, $_POST['reservation_id']);

    $stmt = $conn->prepare("UPDATE reservations SET status = 'cancel' WHERE reservation_id = ?");
    $stmt->bind_param("i", $reservation_id);

    if ($stmt->execute()) {
        echo "<script>
                alert('Reservation cancelled successfully.');
                window.location.href = 'profile.php';
              </script>";
    } else {
        echo "<script>
                alert('Failed to cancel reservation.');
                window.location.href = 'profile.php';
              </script>";
    }

    $stmt->close();
}
if (isset($_POST['change_password'])) {
    $tenant_id = $_SESSION['tenant_id'];

    $current_password = $_POST['current_password'];
    $new_password     = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if new password and confirm match
    if ($new_password !== $confirm_password) {
        die("<script>alert('New password and confirmation do not match.'); window.history.back();</script>");
    }

    // Fetch current hashed password from database
    $stmt = $conn->prepare("SELECT password FROM tenants WHERE tenant_id = ?");
    $stmt->bind_param("i", $tenant_id);
    $stmt->execute();
    $stmt->bind_result($hashed_password);
    $stmt->fetch();
    $stmt->close();

    // Verify current password
    if (!password_verify($current_password, $hashed_password)) {
        die("<script>alert('Current password is incorrect.'); window.history.back();</script>");
    }

    // Hash the new password
    $new_hashed_password = password_hash($new_password, PASSWORD_ARGON2ID);

    // Update password in database
    $stmt = $conn->prepare("UPDATE tenants SET password = ? WHERE tenant_id = ?");
    $stmt->bind_param("si", $new_hashed_password, $tenant_id);

    if ($stmt->execute()) {
        echo "<script>
                alert('Password changed successfully.');
                window.location.href = 'profile.php';
              </script>";
    } else {
        echo "<script>
                alert('Error changing password');
                window.location.href = 'profile.php';
              </script>";
    }

    $stmt->close();
}

if (isset($_POST['update_profile'])) {
    $tenant_id = $_SESSION['tenant_id'];

    // Sanitize inputs
    $studentNumber = mysqli_real_escape_string($conn,$_POST['student_number']);
    $yearLevel     = mysqli_real_escape_string($conn, $_POST['year_level']);
    $program       = mysqli_real_escape_string($conn,$_POST['program']);
    $phone         = mysqli_real_escape_string($conn,$_POST['phone']);

    // Prepare update statement
    $stmt = $conn->prepare("UPDATE tenants SET stud_id = ?, year_level = ?, program = ?, phone_number = ? WHERE tenant_id = ?");

    $stmt->bind_param("ssssi", $studentNumber, $yearLevel, $program, $phone, $tenant_id);

    // Execute update
    if ($stmt->execute()) {
        echo "<script>
                alert('Profile updated successfully.');
                window.location.href = 'profile.php';
              </script>";
    } else {
        echo "<script>
                alert('Error updating profile');
                window.location.href = 'profile.php';
              </script>";
    }

    $stmt->close();
}

?>