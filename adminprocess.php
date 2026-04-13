<?php
    session_start();
    include('conn.php');
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    // Include PHPMailer manually
    require 'PHPMailer/src/PHPMailer.php';
    require 'PHPMailer/src/SMTP.php';
    require 'PHPMailer/src/Exception.php';
    if(isset($_POST['addFacility'])) {
        $roomType =  mysqli_real_escape_string($conn, $_POST['roomType']);
        $roomId =  mysqli_real_escape_string($conn,$_POST['id']);
        $category =  mysqli_real_escape_string($conn,$_POST['category']);
        $name =  mysqli_real_escape_string($conn,$_POST['name']);
        $cost =  mysqli_real_escape_string($conn,$_POST['cost']);
        $unit =  mysqli_real_escape_string($conn,$_POST['unit']);

        $costValue = is_numeric($cost) ? $cost : "0";
        $unitValue = !empty($unit) ? "'$unit'" : "NULL";
    
        // Insert the new facility
        $statement = "INSERT INTO facilities(name, category) VALUES('$name', '$category')";
        if (mysqli_query($conn, $statement)) {
            // selecting the last inserted facility
            $sql = "SELECT * FROM facilities ORDER BY id DESC LIMIT 1;";
            $query = mysqli_query($conn, $sql);
    
            if ($query->num_rows > 0) {
                while ($rowData = mysqli_fetch_array($query)) {
                    $id = $rowData['id'];
    
                    if ($category === "amenity") {
                        $add = "INSERT INTO room_amenities(room_id, facility_id) VALUES ($roomId, $id)";
                        if (mysqli_query($conn, $add)) { 
                            $_SESSION['facility_added'] = 'facility added success';
                            header("Location: admin.php?section=rooms");
                            exit;
                        } else {
                            echo $conn->error;
                        }
                    } elseif ($category === "utility") {
                        $add = "INSERT INTO room_utilities(room_id, facility_id, additional_cost, unit) VALUES($roomId, $id, $costValue, $unitValue)";
                        if (mysqli_query($conn, $add)) { 
                            $_SESSION['facility_added'] = 'facility added success';
                            header("Location: admin.php?section=rooms");
                            exit;
                        } else {
                            echo $conn->error;
                        }
                    }
                }
            }
        }
    }
    

    //BOARDING HOUSE PIC
    if (isset($_POST['save_picture'])) {
        $bh_id = intval($_POST['bh_id']);

        if (isset($_FILES['bh_picture']) && $_FILES['bh_picture']['error'] === UPLOAD_ERR_OK) {
            $fileTmp  = $_FILES['bh_picture']['tmp_name'];
            $fileName = basename($_FILES['bh_picture']['name']);
            $fileSize = $_FILES['bh_picture']['size'];
            $fileType = mime_content_type($fileTmp);

            // Validate file type & size
            $allowedTypes = ['image/jpeg', 'image/png'];
            if (!in_array($fileType, $allowedTypes)) {
                echo "<script>alert('Invalid file type. Only JPG/PNG allowed.'); window.location.href='admin.php?section=boarding-house-management';</script>";
                exit;
            }
            if ($fileSize > 5 * 1024 * 1024) {
                echo "<script>alert('File too large. Max 5MB allowed.'); window.location.href='admin.php?section=boarding-house-management';</script>";
                exit;
            }

            // Generate unique filename
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            $newName = uniqid("bh_", true) . "." . strtolower($ext);
            $uploadDir = "uploads/";
            $uploadPath = $uploadDir . $newName;

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Check if record exists
            $checkSql = "SELECT bh_pic FROM bh_details WHERE bh_id = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("i", $bh_id);
            $checkStmt->execute();
            $checkStmt->store_result();

            $recordExists = $checkStmt->num_rows > 0;
            $oldPic = null;

            if ($recordExists) {
                $checkStmt->bind_result($oldPic);
                $checkStmt->fetch();
            }
            $checkStmt->close();

            // ove uploaded file
            if (move_uploaded_file($fileTmp, $uploadPath)) {
                if (!empty($oldPic) && file_exists($oldPic)) {
                    unlink($oldPic);
                }

                if ($recordExists) {
                    // UPDATE if exists
                    $sql = "UPDATE bh_details SET bh_pic = ? WHERE bh_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("si", $uploadPath, $bh_id);
                } else {
                    // INSERT if not exists
                    $sql = "INSERT INTO bh_details (bh_id, bh_pic) VALUES (?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("is", $bh_id, $uploadPath);
                }

                if ($stmt->execute()) {
                    echo "<script>alert('Picture updated successfully!'); window.location.href='admin.php?section=boarding-house-management';</script>";
                    exit;
                } else {
                    echo "<script>alert('Failed to update picture!'); window.location.href='admin.php?section=boarding-house-management';</script>";
                    exit;
                }
                $stmt->close();
            } else {
                echo "<script>alert('Failed to upload file.'); window.location.href='admin.php?section=boarding-house-management';</script>";
                exit;
                
            }
        } else {
            echo "<script>alert('No file uploaded.'); window.location.href='admin.php?section=boarding-house-management';</script>";
            exit;
        }
    }

    //CANCEL RESERVATION
    if(isset($_POST['cancel_reservation'])){
        $reservationId =  mysqli_real_escape_string($conn,$_POST['reservation_id']);

        $stmt = $conn->prepare("SELECT room_id FROM reservations WHERE reservation_id = ?;");
        $stmt->bind_param("i", $reservationId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row) {
            $room_id = $row['room_id'];

            // Step 2: Increment room capacity by 1
            $stmt = $conn->prepare("UPDATE rooms SET room_capacity = room_capacity + 1 WHERE room_id = ?");
            $stmt->bind_param("i", $room_id);
            $stmt->execute();

            // Step 2: Delete the reservation
            $stmt = $conn->prepare("UPDATE reservations SET status = 'cancel' WHERE reservation_id = ?");
            $stmt->bind_param("i", $reservationId);
            $stmt->execute();


            echo "<script>alert('Reservation cancelled successfully.'); window.location.href='admin.php?section=tenants';</script>";
            exit;
        }

    }
    // END CONTRACT
    if(isset($_POST['end_contract'])){
        $reservationId = $_POST['reservation_id'];

        // Get the room_id of the reservation before deleting it
        $stmt = $conn->prepare("SELECT room_id FROM reservations WHERE reservation_id = ?");
        $stmt->bind_param("i", $reservationId);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows > 0){
            $row = $result->fetch_assoc();
            $roomId = $row['room_id'];

            // Delete the reservation
            $stmt = $conn->prepare("UPDATE reservations SET status = 'ended' WHERE reservation_id = ?");
            $stmt->bind_param("i", $reservationId);
            $stmt->execute();

            // Increase room capacity by 1
            $stmt = $conn->prepare("UPDATE rooms SET room_capacity = room_capacity + 1 WHERE room_id = ?");
            $stmt->bind_param("i", $roomId);
            $stmt->execute();

            // Show alert and redirect
            echo "<script>alert('Contract ended successfully.'); window.location.href='admin.php?section=tenants';</script>";
            exit;
        } else {
            // If no reservation found
            echo "<script>alert('Reservation not found.'); window.location.href='admin.php?section=tenants';</script>";
            exit;
        }
    }


    //MARK ARRIVED
    if(isset($_POST['mark_arrived'])){
        $reservationId = mysqli_real_escape_string($conn, $_POST['reservation_id']);
        
        $stmt = $conn->prepare("UPDATE reservations SET status = 'active', move_in = NOW() WHERE reservation_id = ?");
        $stmt->bind_param("i", $reservationId);
        $stmt->execute();
        
        echo "<script>alert('Mark Arrived'); window.location.href='admin.php?section=tenants';</script>";
        exit;
    }
    
    // APPROVAL 
    if (isset($_POST['approval'])) {
        $reservationId = intval($_POST['reservationId']);

        // Step 1: Get room_id, tenant_id
        $stmt = $conn->prepare("SELECT room_id, tenant_id FROM reservations WHERE reservation_id = ?");
        $stmt->bind_param("i", $reservationId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row) {
            $room_id = $row['room_id'];
            $tenant_id = $row['tenant_id'];

            $check_stmt = $conn->prepare("
                SELECT COUNT(*) AS count
                FROM reservations 
                WHERE tenant_id = ? 
                AND (status = 'active' OR status = 'waiting')
                AND reservation_id != ?
            ");
            $check_stmt->bind_param("ii", $tenant_id, $reservationId);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $check_row = $check_result->fetch_assoc();
            $existing_count = $check_row['count'];
            $check_stmt->close();

            if ($existing_count > 0) {
                // Tenant already has active or waiting reservation
                echo "<script>alert('This tenant already has an active or pending reservation. Cannot approve another.'); window.location.href='admin.php?section=pending-reservations';</script>";
                exit;
            }

            // Step 2: Get tenant name and email
            $stmt = $conn->prepare("SELECT name, email FROM tenants WHERE tenant_id = ?");
            $stmt->bind_param("i", $tenant_id);
            $stmt->execute();
            $tenantResult = $stmt->get_result();
            $tenantRow = $tenantResult->fetch_assoc();

            $tenantName = $tenantRow['name'];
            $tenantEmail = $tenantRow['email'];

            // Step 3: Get landlord contact info and boarding house name using room_id
            $stmt = $conn->prepare("
                SELECT l.landlord_email, l.landlord_number, b.bh_name
                FROM landlords l
                INNER JOIN boarding_houses b ON b.bh_id = l.bh_id
                INNER JOIN rooms r ON r.bh_id = b.bh_id
                WHERE r.room_id = ?
            ");

            $stmt->bind_param("i", $room_id);
            $stmt->execute();
            $landlordResult = $stmt->get_result();
            $landlordRow = $landlordResult->fetch_assoc();

            $landlordEmail = $landlordRow['landlord_email'];
            $landlordPhone = $landlordRow['landlord_number'];
            $bhName = $landlordRow['bh_name'];

            // Step 4: Update reservation status to 'waiting'
            $stmt = $conn->prepare("UPDATE reservations SET status = 'waiting' WHERE reservation_id = ?");
            $stmt->bind_param("i", $reservationId);
            $stmt->execute();
            
            $stmt = $conn->prepare("DELETE FROM reservations WHERE tenant_id = ? AND reservation_id != ?");
            $stmt->bind_param("ii", $tenant_id, $reservationId);
            $stmt->execute();

            // Step 5: Decrement room capacity by 1
            $stmt = $conn->prepare("UPDATE rooms SET room_capacity = room_capacity - 1 WHERE room_id = ?");
            $stmt->bind_param("i", $room_id);
            $stmt->execute();

            // Step 6: Send email notification to tenant
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
                $mail->addAddress($tenantEmail);

                $mail->isHTML(true);
                $mail->Subject = "Your Reservation at $bhName Has Been Approved!";
                $mail->Body = "
                    <h3>Good Day $tenantName,</h3>
                    <p>Good news! Your reservation at <b>$bhName</b> has been <b>approved</b> by the landlord on <b>CyBoard</b>.</p>
                    <p>You may contact your landlord for further details or inquiries using the information below:</p>
                    <ul>
                        <li><b>Email:</b> $landlordEmail</li>
                        <li><b>Phone:</b> $landlordPhone</li>
                    </ul>
                    <p>Thank you,<br><b>CyBoard Management</b></p>
                ";

                $mail->send();
            } catch (Exception $e) {
                echo "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }

            $_SESSION['reservation_approved_success'] = "reservation_approved_success";
            header("Location: admin.php?section=pending-reservations");
            exit;
        }
    }

    if (isset($_POST['reject'])) {
    
    $reservationId = intval($_POST['reservation_id']);

    // Prepare statement to update status
    $stmt = $conn->prepare("UPDATE reservations SET status = 'rejected' WHERE reservation_id = ?");
    $stmt->bind_param("i", $reservationId);

    if ($stmt->execute()) {
        echo "<script>
                alert('Reservation has been successfully rejected.');
                window.location.href = 'admin.php?section=pending-reservations';
              </script>";
    } else {
        echo "<script>
                alert('Failed to reject the reservation. Please try again.');
                window.location.href = 'admin.php?section=pending-reservations';
              </script>";
    }

    $stmt->close();
    $conn->close();
}

    if(isset($_POST['updateRoom'])){
        // Sanitize inputs
        $id =  mysqli_real_escape_string($conn,$_POST['id']);
        $roomType =  mysqli_real_escape_string($conn,$_POST['roomType']);
        $genderPolicy =  mysqli_real_escape_string($conn,$_POST['genderPolicy']);
        $roomRate =  mysqli_real_escape_string($conn,$_POST['roomRate']);
        $downpayment = mysqli_real_escape_string($conn,$_POST['downpayment']);
        $roomCapacity =  mysqli_real_escape_string($conn,$_POST['roomCapacity']);
        
        // Additional validation
        if (empty($roomType) || empty($genderPolicy)) {
            die("<script>alert('Room type and gender policy are required'); window.history.back();</script>");
        }
        
        if ($roomRate <= 0 || $downpayment < 0 || $roomCapacity <= 0) {
            die("<script>alert('Please enter valid numbers for rate, downpayment, and capacity'); window.history.back();</script>");
        }
        
        $updatePicture = false;
        $roomPicture = '';
        
        if (isset($_FILES['roomPicture'])) {

            if ($_FILES['roomPicture']['size'] != 0) {

                $tmp_name = $_FILES['roomPicture']['tmp_name'];
                $extension = "." . pathinfo($_FILES['roomPicture']['name'], PATHINFO_EXTENSION);

                $time = time();
                $filename = $roomType . $time;

                $destination = "images/roomPictures/" . $roomType . $time . $extension;
                move_uploaded_file($tmp_name, $destination);
                $roomPicture = $filename . $extension;
                $updatePicture = true;
            }
        } 

        if($updatePicture){
            $updateQuery = "UPDATE rooms SET 
                    room_type = '$roomType', 
                    room_picture = '$roomPicture',
                    gender_policy = '$genderPolicy', 
                    room_rate = $roomRate, 
                    downpayment = $downpayment, 
                    room_capacity = $roomCapacity
                    WHERE room_id = $id";
                    if(mysqli_query($conn, $updateQuery)){
                        $_SESSION['room_updated_success'] = 'room updated success';
                    header("Location: admin.php?section=rooms");
                    exit;
                    }else{
                        echo $conn->error;
                    }
        }else{
            $updateQuery = "UPDATE rooms SET 
            room_type = '$roomType', 
            gender_policy = '$genderPolicy', 
            room_rate = $roomRate, 
            downpayment = $downpayment,
            room_capacity = $roomCapacity
            WHERE room_id = $id";
            if(mysqli_query($conn, $updateQuery)){
                $_SESSION['room_updated_success'] = 'room updated success';
            header("Location: admin.php?section=rooms");
            exit;
            }else{
                echo $conn->error;
            }
        }
    }

    if(isset($_POST['deleteroom'])){
        $room_id = $_POST['room_id'];
        $statement = "DELETE FROM rooms WHERE room_id = $room_id";
            if(mysqli_query($conn, $statement)){
                $_SESSION['room_deleted_success'] = 'room deleted success';
            header("Location: admin.php?section=rooms");
            exit;
            }
    }
    if(isset($_POST["addRoom"])){
    $bh_id =  mysqli_real_escape_string($conn,$_POST['bh_id']);
    $roomType = mysqli_real_escape_string($conn,$_POST['roomType']);
    $genderPolicy = trim($_POST['genderPolicy']);
    $roomRate =  mysqli_real_escape_string($conn,$_POST['roomRate']);
    $downpayment =  mysqli_real_escape_string($conn,$_POST['downpayment']);
    $roomCapacity =  mysqli_real_escape_string($conn,$_POST['roomCapacity']);
    
    if (strpos($roomType, "'") !== false) {
        die("<script>alert('Room type cannot contain apostrophes (\\'). Please remove them.'); window.history.back();</script>");
    }

    if (empty($roomType) || empty($genderPolicy)) {
        die("<script>alert('Room type and gender policy are required'); window.history.back();</script>");
    }
    
    if ($roomRate <= 0 || $downpayment < 0 || $roomCapacity <= 0) {
        die("<script>alert('Please enter valid numbers for rate, downpayment, and capacity'); window.history.back();</script>");
    }
    
    $roomPicture = ''; // Initialize
    
    if (isset($_FILES['roomPicture'])) {
        if ($_FILES['roomPicture']['size'] != 0) {
            $tmp_name = $_FILES['roomPicture']['tmp_name'];
            $extension = "." . pathinfo($_FILES['roomPicture']['name'], PATHINFO_EXTENSION);
            $time = time();
            $filename = $roomType . $time;
            $destination = "images/roomPictures/" . $roomType . $time . $extension;
            move_uploaded_file($tmp_name, $destination);
            $roomPicture = $filename . $extension;
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO rooms(bh_id, room_type, room_picture, gender_policy, room_rate, downpayment, room_capacity) VALUES(?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssiid", $bh_id, $roomType, $roomPicture, $genderPolicy, $roomRate, $downpayment, $roomCapacity);
    
    if($stmt->execute()){
        $_SESSION['room_added_success'] = 'Room added successfully';
        header("Location: admin.php?section=rooms");
        exit;
    } else {
        die("<script>alert('Error adding room. Please try again.'); window.history.back();</script>");
    }
}
    // ADD AMENITIES
if (isset($_POST['add_amenity'])) {
    $bh_id = intval($_POST['bh_id']);

    // 1. Get all existing bh_amenity_ids with exclusions BEFORE deletion
    $existing_exclusions = [];
    $stmt_get = $conn->prepare("
        SELECT id, amenity_id, custom_amenity 
        FROM bh_amenities 
        WHERE bh_id = ? AND amenity_id IS NOT NULL
    ");
    $stmt_get->bind_param("i", $bh_id);
    $stmt_get->execute();
    $result = $stmt_get->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $bh_amenity_id = $row['id'];
        
        // Get excluded rooms for this amenity
        $stmt_excl = $conn->prepare("SELECT room_id FROM room_excluded_amenities WHERE bh_amenity_id = ?");
        $stmt_excl->bind_param("i", $bh_amenity_id);
        $stmt_excl->execute();
        $excl_result = $stmt_excl->get_result();
        
        $excluded_rooms = [];
        while ($excl_row = $excl_result->fetch_assoc()) {
            $excluded_rooms[] = $excl_row['room_id'];
        }
        $stmt_excl->close();
        
        // Store exclusions mapped by amenity_id
        if (!empty($excluded_rooms)) {
            $existing_exclusions[$row['amenity_id']] = $excluded_rooms;
        }
    }
    $stmt_get->close();

    // 2. Delete all system-defined amenities for this BH (this will cascade delete exclusions)
    $stmt = $conn->prepare("DELETE FROM bh_amenities WHERE bh_id = ? AND amenity_id IS NOT NULL");
    $stmt->bind_param("i", $bh_id);
    $stmt->execute();
    $stmt->close();

    // 3. Re-insert selected system amenities
    if (!empty($_POST['default_amenities'])) {
        $stmt_insert = $conn->prepare("INSERT INTO bh_amenities (bh_id, amenity_id) VALUES (?, ?)");
        $stmt_excl_insert = $conn->prepare("INSERT INTO room_excluded_amenities (bh_amenity_id, room_id) VALUES (?, ?)");
        
        foreach ($_POST['default_amenities'] as $aid) {
            $aid = intval($aid);
            $stmt_insert->bind_param("ii", $bh_id, $aid);
            $stmt_insert->execute();
            
            // Get the new bh_amenity_id that was just inserted
            $new_bh_amenity_id = $conn->insert_id;
            
            // Restore excluded rooms if they existed before
            if (isset($existing_exclusions[$aid]) && !empty($existing_exclusions[$aid])) {
                foreach ($existing_exclusions[$aid] as $room_id) {
                    $stmt_excl_insert->bind_param("ii", $new_bh_amenity_id, $room_id);
                    $stmt_excl_insert->execute();
                }
            }
        }
        $stmt_insert->close();
        $stmt_excl_insert->close();
    }

    // 4. Insert new custom amenities (directly to bh_amenities)
    if (!empty($_POST['custom_amenities'])) {
        $stmt_custom = $conn->prepare("INSERT INTO bh_amenities (bh_id, custom_amenity) VALUES (?, ?)");
        foreach ($_POST['custom_amenities'] as $c) {
            $name = trim($c['name']);
            
            // Check for apostrophes
            if (strpos($name, "'") !== false) {
                echo "<script>alert('Custom amenity name cannot contain apostrophes (\\'). Please remove them.'); window.history.back();</script>";
                exit;
            }
            
            $name = htmlspecialchars($name);
            if ($name !== "") {
                $stmt_custom->bind_param("is", $bh_id, $name);
                $stmt_custom->execute();
            }
        }
        $stmt_custom->close();
    }

    echo "<script>alert('Amenities updated successfully!'); window.location.href='admin.php?section=boarding-house-management';</script>";
    exit;
}



    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_payment_status'])) {
        $bh_id = intval($_POST['bh_id']);
        $payment_status =  mysqli_real_escape_string($conn,$_POST['payment_status']) ?? '';
        $custom_payment =  mysqli_real_escape_string($conn,$_POST['custom_payment']) ?? '';

        // If landlord selected "Custom", use the custom input
        if ($payment_status === 'custom' && !empty(trim($custom_payment))) {
            $payment_status = trim($custom_payment);
        }

        // Check if payment status already exists for this BH
        $stmt = $conn->prepare("SELECT payment_status FROM bh_details WHERE bh_id = ?");
        $stmt->bind_param("i", $bh_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Update existing payment status
            $stmt = $conn->prepare("UPDATE bh_details SET payment_status = ? WHERE bh_id = ?");
            $stmt->bind_param("si", $payment_status, $bh_id);
        } else {

            // Insert new payment status
            $stmt = $conn->prepare("INSERT INTO bh_details (bh_id, payment_status) VALUES (?, ?)");
            $stmt->bind_param("is", $bh_id, $payment_status);
        }

        if ($stmt->execute()) {
            $_SESSION['update_payment'] = 'update_payment';
            header("Location: admin.php?section=boarding-house-management");
            exit;
        } else {
            echo "<script>alert('Error saving payment status.'); window.location.href='bh_management.php';</script>";
        }
    }
    //deleting amenity
    if (isset($_POST['delete_amenity'])) {
        $amenityId = intval($_POST['amenity_id']);



        // Delete from bh_amenities
        $stmt = $conn->prepare("DELETE FROM bh_amenities WHERE id = ?");
        $stmt->bind_param("i", $amenityId);

        if ($stmt->execute()) {
            $_SESSION['delete_amenity'] = 'delete_amenity';
            header("Location: admin.php?section=boarding-house-management");
            exit;
        } else {
            echo "<script>alert('Error deleting amenity: " . $conn->error . "'); window.location.href='admin.php?section=boarding-house-management';</script>";
        }
    }

// UPDATE Amenity (custom only)
if (isset($_POST['update_amenity'])) {
    $amenityId = intval($_POST['amenityId']);
    $customAmenity = mysqli_real_escape_string($conn,$_POST['amenityName']);

    if (!empty($customAmenity)) {
        $stmt = $conn->prepare("UPDATE bh_amenities SET custom_amenity = ? WHERE id = ?");
        $stmt->bind_param("si", $customAmenity, $amenityId);

        if ($stmt->execute()) {
            $_SESSION['update_amenity'] = 'update_amenity';
            header("Location: admin.php?section=boarding-house-management");
            exit;
        } else {
            echo "<script>alert('Error updating amenity: " . $conn->error . "'); window.location.href='admin.php?section=boarding-house-management';</script>";
        }
    } else {
        echo "<script>alert('Amenity name cannot be empty.'); window.location.href='admin.php?section=boarding-house-management';</script>";
    }
}
if (isset($_POST['excluded_room'])) {
    $bh_id = intval($_POST['bh_id']);
    $bhAmenityId = intval($_POST['bh_amenity_id']);
    $excludedRooms = isset($_POST['excludedRooms']) ? $_POST['excludedRooms'] : [];

    // 🧹 1. Clear old excluded room records
    $stmt = $conn->prepare("DELETE FROM room_excluded_amenities WHERE bh_amenity_id = ?");
    $stmt->bind_param("i", $bhAmenityId);
    $stmt->execute();
    $stmt->close();

    // 🏠 2. Get total rooms of this boarding house
    $stmt = $conn->prepare("SELECT COUNT(*) FROM rooms WHERE bh_id = ?");
    $stmt->bind_param("i", $bh_id);
    $stmt->execute();
    $stmt->bind_result($totalRooms);
    $stmt->fetch();
    $stmt->close();

    // 🧾 3. Insert new excluded rooms (if not empty)
    if (!empty($excludedRooms)) {
        $stmt = $conn->prepare("INSERT INTO room_excluded_amenities (bh_amenity_id, room_id) VALUES (?, ?)");
        foreach ($excludedRooms as $rid) {
            $rid = intval($rid);
            $stmt->bind_param("ii", $bhAmenityId, $rid);
            $stmt->execute();
        }
        $stmt->close();
    }

    // 🚨 4. If all rooms were excluded → delete the amenity from bh_amenities
    if (!empty($excludedRooms) && count($excludedRooms) >= $totalRooms) {
        $stmt = $conn->prepare("DELETE FROM bh_amenities WHERE id = ?");
        $stmt->bind_param("i", $bhAmenityId);
        $stmt->execute();
        $stmt->close();

        echo "<script>
                alert('All rooms excluded — amenity removed from this boarding house.');
                window.location.href='admin.php?section=boarding-house-management';
              </script>";
        exit;
    }

    // ✅ 5. Normal success
    echo "<script>
            alert('Excluded rooms updated successfully.');
            window.location.href='admin.php?section=boarding-house-management';
          </script>";
    exit;
}

//DELETE UTILITY
if (isset($_POST['delete_utility'])) {
    $bh_id = intval($_POST['bh_id']); 
    $utility_id = intval($_POST['deleteUtilityId']);
    $bh_utility_id = intval($_POST['deleteBhUtilityId']);

    // 1. Delete from bh_utilities
    $stmt1 = $conn->prepare("DELETE FROM bh_utilities WHERE bh_id = ? AND bh_utility_id = ?");
    $stmt1->bind_param("ii", $bh_id, $bh_utility_id);
    $stmt1->execute();
    $stmt1->close();

    // 2. Delete pricing if exists
    $stmt2 = $conn->prepare("DELETE FROM utility_pricing WHERE bh_id = ? AND bh_utility_id = ?");
    $stmt2->bind_param("ii", $bh_id, $bh_utility_id);
    $stmt2->execute();
    $stmt2->close();

    // 3. Check if custom (is_default = 0), then delete from utilities
    $stmt_check = $conn->prepare("SELECT is_default FROM utilities WHERE utility_id = ?");
    $stmt_check->bind_param("i", $utility_id);
    $stmt_check->execute();
    $stmt_check->bind_result($is_default);
    $stmt_check->fetch();
    $stmt_check->close();

    if ($is_default == 0) {
        $stmt3 = $conn->prepare("DELETE FROM utilities WHERE utility_id = ?");
        $stmt3->bind_param("i", $utility_id);
        $stmt3->execute();
        $stmt3->close();
    }else{
        $stmt4 = $conn->prepare("DELETE FROM bh_utilities WHERE bh_utility_id = ?");
        $stmt4->bind_param("i", $bh_utility_id);
        $stmt4->execute();
        $stmt4->close();
    }

    echo "<script>alert('Utility Deleted: " . $conn->error . "'); window.location.href='admin.php?section=boarding-house-management';</script>";
    exit;
}

//EDIT UTILITY
if (isset($_POST['edit_utility'])) {
    $bh_id = intval($_POST['bh_id']);
    $bh_utility_id = intval($_POST['bh_utility_id']);
    $utility_id = intval($_POST['utility_id']);
    $utility_name =  mysqli_real_escape_string($conn,$_POST['utility_name']);
    $is_included = ($_POST['is_included'] === 'no') ? 'no' : 'yes';
    $cost = isset($_POST['cost']) ? floatval(mysqli_real_escape_string($conn, $_POST['cost'])) : null;
    $unit = isset($_POST['unit']) ? trim(mysqli_real_escape_string($conn, $_POST['unit'])) : null;


    // 1. Check if custom utility
    $stmt = $conn->prepare("SELECT is_default FROM utilities WHERE utility_id = ?");
    $stmt->bind_param("i", $utility_id);
    $stmt->execute();
    $stmt->bind_result($is_default);
    $stmt->fetch();
    $stmt->close();

    // Update utility name if custom
    if ($is_default == 0 && !empty($utility_name)) {
        $stmt = $conn->prepare("UPDATE utilities SET utility_name = ? WHERE utility_id = ?");
        $stmt->bind_param("si", $utility_name, $utility_id);
        $stmt->execute();
        $stmt->close();
    }

    // 2. Update bh_utilities (included or not)
    $stmt = $conn->prepare("UPDATE bh_utilities SET is_included = ? WHERE bh_id = ? AND bh_utility_id = ?");
    $stmt->bind_param("sii", $is_included, $bh_id, $bh_utility_id);
    $stmt->execute();
    $stmt->close();

    // 3. Manage pricing
    if ($is_included === 'no') {
        // Save cost + unit
        if ($cost !== null && $unit !== "") {
            $stmt = $conn->prepare("
                INSERT INTO utility_pricing (bh_id, bh_utility_id, cost, unit)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE cost = VALUES(cost), unit = VALUES(unit)
            ");
            $stmt->bind_param("iids", $bh_id, $bh_utility_id, $cost, $unit);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        // Included = yes → remove pricing
        $stmt = $conn->prepare("DELETE FROM utility_pricing WHERE bh_id = ? AND bh_utility_id = ?");
        $stmt->bind_param("ii", $bh_id, $bh_utility_id);
        $stmt->execute();
        $stmt->close();
    }

    echo "<script>alert('Utility Updated successfully '); window.location.href='admin.php?section=boarding-house-management';</script>";
    exit;
}

//MANAGE ROOM FOR UTILITY

if (isset($_POST['save_excluded_rooms'])) {
    if (isset($_POST['bh_utility_id'])) {
        $utilityId = intval($_POST['bh_utility_id']);
        $excludedUtilityRooms = $_POST['excludedUtilityRooms'];

        // 1. Count total rooms for this boarding house (linked to this utility)
        $sql = "
            SELECT COUNT(*) as total 
            FROM rooms r 
            INNER JOIN boarding_houses bh ON r.bh_id = bh.bh_id
            INNER JOIN bh_utilities bu ON bu.bh_id = r.bh_id
            WHERE bu.bh_utility_id = ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $utilityId);
        $stmt->execute();
        $result = $stmt->get_result();
        $totalRooms = ($row = $result->fetch_assoc()) ? intval($row['total']) : 0;

        // 2. If all rooms are excluded → delete utility entirely
        if (!empty($excludedUtilityRooms) && count($excludedUtilityRooms) == $totalRooms) {
            // Delete utility from bh_utilities
            $stmt = $conn->prepare("DELETE FROM bh_utilities WHERE bh_utility_id = ?");
            $stmt->bind_param("i", $utilityId);
            $stmt->execute();

            echo "<script>
                    alert('All rooms excluded — utility removed from this boarding house.');
                    window.location.href='admin.php?section=boarding-house-management';
                  </script>";
            exit;
        }

        // 3. Otherwise → save exclusions
        // Delete old exclusions first
        $stmt = $conn->prepare("DELETE FROM room_excluded_utilities WHERE bh_utility_id = ?");
        $stmt->bind_param("i", $utilityId);
        $stmt->execute();

        // Insert new exclusions if any
        if (!empty($excludedUtilityRooms)) {
            $stmt = $conn->prepare("INSERT INTO room_excluded_utilities (bh_utility_id, room_id) VALUES (?, ?)");
            foreach ($excludedUtilityRooms as $roomId) {
                $roomId = intval($roomId);
                $stmt->bind_param("ii", $utilityId, $roomId);
                $stmt->execute();
            }
        }
        // 4. Redirect or notify
        echo "<script>
                alert('Room exclusions updated successfully!');
                window.location.href='admin.php?section=boarding-house-management';
              </script>";
        exit;
    }
}



//ADD UTILITY
if(isset($_POST['add_utility'])){
    $bh_id = $_POST['bh_id'];

    // 1. Add Default Utilities
    if(!empty($_POST['default_utilities'])){
        $stmt_check = $conn->prepare("SELECT COUNT(*) FROM bh_utilities WHERE bh_id=? AND utility_id=?");
        $stmt_insert = $conn->prepare("INSERT INTO bh_utilities (bh_id, utility_id, is_included) VALUES (?, ?, 'yes')");

        foreach($_POST['default_utilities'] as $uid){
            $uid = intval($uid);
            $stmt_check->bind_param("ii", $bh_id, $uid);
            $stmt_check->execute();
            $stmt_check->bind_result($count);
            $stmt_check->fetch();
            $stmt_check->reset();

            if($count == 0){
                $stmt_insert->bind_param("ii", $bh_id, $uid);
                $stmt_insert->execute();
            }
        }

        $stmt_check->close();
        $stmt_insert->close();
    }

    // 2. Add Custom Utilities
    if(!empty($_POST['custom_utilities'])){
        $stmt_util = $conn->prepare("INSERT INTO utilities (utility_name, is_default) VALUES (?, 0)");
        $stmt_bh = $conn->prepare("INSERT INTO bh_utilities (bh_id, utility_id, is_included) VALUES (?, ?, ?)");

        foreach($_POST['custom_utilities'] as $c){
            $name = htmlspecialchars(trim($c['name']));
            $included = ($c['included'] === 'yes') ? 'yes' : 'no';

            $stmt_util->bind_param("s", $name);
            $stmt_util->execute();
            $uid = $stmt_util->insert_id;

            $stmt_bh->bind_param("iis", $bh_id, $uid, $included);
            $stmt_bh->execute();
        }

        $stmt_util->close();
        $stmt_bh->close();
    }

    echo "<script>alert('Utility Added Succesfully'); window.location.href='admin.php?section=boarding-house-management';</script>";
    exit;
}
if (isset($_POST['change_password'])) {   
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Get landlord ID from session
    $landlord_id = $_SESSION['landlord_id']; // Make sure this is set during landlord login
    
    // Simple validation
    if (strlen($new_password) < 8) {
        echo "<script>
            alert('Password must be at least 8 characters');
            window.location.href = 'admin.php?section=settings';
        </script>";
        exit;
    }
    
    if ($new_password !== $confirm_password) {
        echo "<script>
            alert('Passwords do not match');
            window.location.href = 'admin.php?section=settings';
        </script>";
        exit;
    }
    
    // Get current password from database
    $stmt = $conn->prepare("SELECT landlord_password FROM landlords WHERE landlord_id = ?");
    $stmt->bind_param("i", $landlord_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        echo "<script>
            alert('Landlord not found');
            window.location.href = 'admin.php?section=settings';
        </script>";
        exit;
    }
    
    $row = $result->fetch_assoc();
    
    // Verify current password
    if (!password_verify($current_password, $row['landlord_password'])) {
        echo "<script>
            alert('Current password is incorrect');
            window.location.href = 'admin.php?section=settings';
        </script>";
        exit;
    }
    
    // Update password
    $hashed_password = password_hash($new_password, PASSWORD_ARGON2ID);
    $update = $conn->prepare("UPDATE landlords SET landlord_password = ? WHERE landlord_id = ?");
    $update->bind_param("si", $hashed_password, $landlord_id);
    
    if ($update->execute()) {
        echo "<script>
            alert('Password updated successfully!');
            window.location.href = 'admin.php?section=settings';
        </script>";
    } else {
        echo "<script>
            alert('Failed to update password');
            window.location.href = 'admin.php?section=settings';
        </script>";
    }
    
    $stmt->close();
    $update->close();
    $conn->close();
    exit;
}
?>

    