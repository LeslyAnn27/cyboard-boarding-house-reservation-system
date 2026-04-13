<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CyBoard - Landlord Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="/css/admin.css">
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

</head>
<body>
<?php
    require "conn.php";
    session_start();
    $landlord_id = $_SESSION['landlord_id'];

    if(isset($_SESSION['landlord_id'])){
        $landlord_id = $_SESSION['landlord_id'];

        $stmt = $conn->prepare("SELECT bh_id FROM landlords WHERE landlord_id = ?");
        $stmt->bind_param("i", $landlord_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows > 0){
            $row = $result->fetch_assoc();
            $bh_id = $row['bh_id'];
        }
    } else {
        header("Location: login.php"); // Redirect to login page if not logged in
        exit();
    }
    
$superadminId = 1; // only one superadmin

    ?>
    <header>
        <div class="header-container">
            <a href="#" class="logo">CyBoard</a>
        </div>
    </header>

    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <div class="container-fluid">
        <div class="sidebar">
            <h4>Landlord Dashboard</h4>
            <ul>
                <li><a href="#dashboard" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="#boarding-house-management" class="nav-link"><i class="fa-solid fa-house"></i> Boarding House Management</a></li>
                <li>
                    <a href="#superadmin-communication" class="nav-link" id="superadminInboxLink">
                        <i class="fas fa-envelope"></i> Contact Administrator
                        <span class="badge bg-danger ms-2" id="superadminBadge" style="display:none;">0</span>
                    </a>
                </li>
                <li><a href="#rooms" class="nav-link"><i class="fas fa-bed"></i> Rooms</a></li>
                <li><a href="#tenants" class="nav-link"><i class="fas fa-users"></i> Tenants</a></li>
                <li><a href="#pending-reservations" class="nav-link"><i class="fas fa-clock"></i> Pending Reservations</a></li>
                <li><a href="#settings" class="nav-link"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-right-from-bracket"></i> Logout</a></li>
            </ul>
        </div>
        <div class="main-content">
        <section id="dashboard" class="section active">
            <h2 class="mb-4">Dashboard</h2>
            <div class="row">
                <?php
                // Get boarding house ID using prepared statement
                $bhStmt = $conn->prepare("SELECT bh_id FROM landlords WHERE landlord_id = ?");
                $bhStmt->bind_param("i", $landlord_id);
                $bhStmt->execute();
                $bhResult = $bhStmt->get_result();
                $bhRow = $bhResult->fetch_assoc();
                $bh_id = $bhRow['bh_id'] ?? 0;
                $bhStmt->close();
            
                // Total rooms
                $roomsStmt = $conn->prepare("SELECT COUNT(*) AS total_rooms FROM rooms WHERE bh_id = ?");
                $roomsStmt->bind_param("i", $bh_id);
                $roomsStmt->execute();
                $roomsResult = $roomsStmt->get_result();
                $rooms = $roomsResult->fetch_assoc()['total_rooms'] ?? 0;
                $roomsStmt->close();
            
                // Total tenants
                $tenantsStmt = $conn->prepare("
                    SELECT COUNT(DISTINCT t.tenant_id) AS total_tenants
                    FROM tenants AS t
                    INNER JOIN reservations AS r ON t.tenant_id = r.tenant_id
                    INNER JOIN rooms AS rm ON r.room_id = rm.room_id
                    WHERE rm.bh_id = ? AND r.status = 'active'
                ");
                $tenantsStmt->bind_param("i", $bh_id);
                $tenantsStmt->execute();
                $tenantsResult = $tenantsStmt->get_result();
                $tenants = $tenantsResult->fetch_assoc()['total_tenants'] ?? 0;
                $tenantsStmt->close();
            
                // Pending reservations
                $pendingStmt = $conn->prepare("
                    SELECT COUNT(r.reservation_id) AS pending
                    FROM reservations AS r
                    INNER JOIN rooms AS rm ON r.room_id = rm.room_id
                    WHERE rm.bh_id = ? AND r.status = 'pending'
                ");
                $pendingStmt->bind_param("i", $bh_id);
                $pendingStmt->execute();
                $pendingResult = $pendingStmt->get_result();
                $pending = $pendingResult->fetch_assoc()['pending'] ?? 0;
                $pendingStmt->close();
                ?>
                
                <!-- Dashboard Cards -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">Total Rooms</div>
                        <div class="card-body">
                            <h5><?= htmlspecialchars($rooms) ?></h5>
                            <p>Across this boarding house</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">Total Tenants</div>
                        <div class="card-body">
                            <h5><?= htmlspecialchars($tenants) ?></h5>
                            <p>Currently occupying rooms</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">Pending Reservations</div>
                        <div class="card-body">
                            <h5><?= htmlspecialchars($pending) ?></h5>
                            <p>Awaiting approval</p>
                        </div>
                    </div>
                </div>
            </div>
            </section>


        <section id="rooms" class="section">
                <h2 class="mb-4">Rooms</h2>
                <?php
                    if(isset($_SESSION['room_added_success'])){
                        echo "<script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var toastElement = document.getElementById('successToast');
                                var toast = new bootstrap.Toast(toastElement, {delay: 4000});
                                toast.show();
                            });
                        </script>";
                        unset($_SESSION['room_added_success']);
                    }elseif(isset($_SESSION['room_deleted_success'])){
                        echo "<script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var toastElement = document.getElementById('deletedToast');
                                var toast = new bootstrap.Toast(toastElement, {delay: 4000});
                                toast.show();
                            });
                        </script>";
                        unset($_SESSION['room_deleted_success']);
                    }elseif(isset($_SESSION['room_updated_success'])){
                        echo "<script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var toastElement = document.getElementById('updateSuccessToast');
                                var toast = new bootstrap.Toast(toastElement, {delay: 4000});
                                toast.show();
                            });
                        </script>";
                        unset($_SESSION['room_updated_success']);
                    }elseif(isset($_SESSION['facility_added'])){
                        echo "<script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var toastElement = document.getElementById('FacilityToast');
                                var toast = new bootstrap.Toast(toastElement, {delay: 4000});
                                toast.show();
                            });
                        </script>";
                        unset($_SESSION['facility_added']);
                    }
                    ?>

                <!-- Toast Container -->

                <div class="toast-container position-fixed top-0 end-0 p-3">
                    <div id="FacilityToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="toast-header bg-success text-white">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <strong class="me-auto">Success</strong>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <div class="toast-body">
                            <strong>Amenity Added Successfully!</strong><br>
                            Amenity has been added.
                        </div>
                    </div>
                </div>

                <div class="toast-container position-fixed top-0 end-0 p-3">
                    <div id="successToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="toast-header bg-success text-white">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <strong class="me-auto">Success</strong>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <div class="toast-body">
                            <strong>Room Added Successfully!</strong><br>
                            Your new room has been added to the boarding house.
                        </div>
                    </div>
                </div>

                <div class="toast-container position-fixed top-0 end-0 p-3">
                    <div id="deletedToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="toast-header bg-success text-white">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <strong class="me-auto">Success</strong>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <div class="toast-body">
                            <strong>Room Deleted Successfully!</strong><br>
                            The room has been deleted from the boarding house.
                        </div>
                    </div>
                </div>

                <div class="toast-container position-fixed top-0 end-0 p-3">
                    <div id="updateSuccessToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="toast-header bg-success text-white">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <strong class="me-auto">Success</strong>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <div class="toast-body">
                            <strong>Room Updated Successfully!</strong><br>
                            Your room has been updated.
                        </div>
                    </div>
                </div>


                <div class="card mb-4">
                    <div class="card-header">Add New Room</div>
                    <div class="card-body">
                        <form method="POST" action="adminprocess.php" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="roomType" class="form-label">Room Type</label>
                                <select class="form-control" name="roomType" id="roomType" required>
                                    <option value="">-- Select Room Type --</option>
                                    <option value="Solo">Solo - 1 person per room</option>
                                    <option value="Double">Double - 2 persons per room</option>
                                    <option value="Dormitory style">Dormitory style - 3 or more persons per room</option>
                                </select>
                                <input type="hidden" name="bh_id" value="<?php echo $bh_id; ?>">
                            </div>
                            <div class="mb-3">
                                <label for="formFileSm" class="form-label">Room Picture</label>
                                <input class="form-control new" type="file" name="roomPicture" accept=".jpg, .jpeg, .png" required>                           
                            </div>
                            <div class="mb-3">
                                <label for="genderPolicy" class="form-label">Gender Policy</label>
                                <select class="form-control" name="genderPolicy" required>
                                    <option value="Male Only">Male Only</option>
                                    <option value="Female Only">Female Only</option>
                                    <option value="Mixed">Mixed</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="roomRate" class="form-label">Room Rate (₱/month)</label>
                                <input type="number" class="form-control" name="roomRate" placeholder="e.g., 1000" required>
                            </div>
                            <div class="mb-3">
                                <label for="downpayment" class="form-label">Downpayment (₱)</label>
                                <input type="number" class="form-control" name="downpayment" placeholder="e.g., 500" required>
                            </div>
                            <div class="mb-3">
                                <label for="roomCapacity" class="form-label">Room Capacity</label>
                                <input type="number" class="form-control" name="roomCapacity" placeholder="e.g., 1" required>
                            </div>

                            <button type="submit" name="addRoom" class="btn btn-primary">Add Room</button>
                        </form>
                    </div>
                </div>
                <div class="card mb-4">
                    <div class="card-header">Room List</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Room Type</th>
                                        <th>Room Picture</th>
                                        <th>Gender Policy</th>
                                        <th>Room Rate (₱/month)</th>
                                        <th>Downpayment (₱)</th>
                                        <th>Room Capacity</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $conn->prepare("SELECT room_id, room_type, room_picture, gender_policy, room_rate, downpayment, room_capacity FROM rooms r JOIN boarding_houses bh ON r.bh_id = bh.bh_id WHERE r.bh_id = ? AND r.room_capacity != 0");
                                    $stmt->bind_param("i", $bh_id);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    
                                    if ($result->num_rows > 0) {  
                                        while ($rowData = $result->fetch_assoc()) {
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($rowData['room_type'])?></td>
                                            <td><img src="images/roomPictures/<?php echo htmlspecialchars($rowData['room_picture']);?>" alt="Room Picture" class="room-pic" style="max-width: 100px; height: auto;"></td>
                                            <td><?php echo htmlspecialchars($rowData['gender_policy'])?></td>
                                            <td><?php echo htmlspecialchars($rowData['room_rate'])?></td>
                                            <td><?php echo htmlspecialchars($rowData['downpayment'])?></td>
                                            <td><?php echo htmlspecialchars($rowData['room_capacity'])?></td>
                                            <td>
                                                <div class="d-flex flex-column gap-2" style="width: 100%;">
                                                    <button 
                                                        class="btn btn-primary btn-sm w-100" 
                                                        data-bs-toggle="modal" 
                                                        data-id="<?php echo htmlspecialchars($rowData['room_id']); ?>" 
                                                        data-roomType="<?php echo htmlspecialchars($rowData['room_type']); ?>" 
                                                        data-genderPolicy="<?php echo htmlspecialchars($rowData['gender_policy']); ?>" 
                                                        data-roomRate="<?php echo htmlspecialchars($rowData['room_rate']); ?>" 
                                                        data-roomCapacity="<?php echo htmlspecialchars($rowData['room_capacity']); ?>" 
                                                        data-downpayment="<?php echo htmlspecialchars($rowData['downpayment']); ?>"  
                                                        data-bs-target="#updateRoomModal">
                                                        Update
                                                    </button>
                                                    <form method="POST" action="adminprocess.php" style="width: 100%;">
                                                        <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($rowData['room_id']); ?>">
                                                        <button 
                                                            class="btn btn-danger btn-sm w-100" 
                                                            type="submit" 
                                                            name="deleteroom" 
                                                            onclick="return confirm('Are you sure you want to delete this room?');">
                                                            Remove
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php
                                        }
                                    } else {
                                        echo "<tr><td colspan='9' class='text-center'>No rooms added yet.</td></tr>";
                                    }
                                    $stmt->close();
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            
            </section>
            
            <!-- Update Room Modal -->
<div class="modal fade" id="updateRoomModal" tabindex="-1" aria-labelledby="updateRoomModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-secondary-emphasis" id="updateRoomModalLabel">Update Room</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="adminprocess.php" enctype="multipart/form-data">
                    <input type="hidden" class="form-control" name="id" id="updateId">
                    
                    <div class="mb-3">
                        <label for="updateRoomType" class="form-label">Room Type</label>
                        <select class="form-control" name="roomType" id="updateRoomType" required>
                            <option value="Solo">Solo - 1 person</option>
                            <option value="Double">Double - 2 persons</option>
                            <option value="Dormitory style">Dormitory style - 3+ persons</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="updateRoomPicture" class="form-label">Room Picture</label>
                        <input class="form-control" type="file" name="roomPicture" accept=".jpg, .jpeg, .png">                           
                    </div>
                    <div class="mb-3">
                        <label for="updateGenderPolicy" class="form-label">Gender Policy</label>
                        <select class="form-control" name="genderPolicy" id="updateGenderPolicy" required>
                            <option value="Male Only">Male Only</option>
                            <option value="Female Only">Female Only</option>
                            <option value="Mixed">Mixed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="updateRoomRate" class="form-label">Room Rate (₱/payment status)</label>
                        <input type="number" class="form-control" name="roomRate" id="updateRoomRate" required>
                    </div>
                    <div class="mb-3">
                        <label for="updateDownpayment" class="form-label">Downpayment (₱)</label>
                        <input type="number" class="form-control" name="downpayment" id="updateDownpayment" required>
                    </div>
                    <div class="mb-3">
                        <label for="updateRoomCapacity" class="form-label">Room Capacity</label>
                        <input type="number" class="form-control" name="roomCapacity" id="updateRoomCapacity" required>
                    </div>
                    <button type="submit" name="updateRoom" class="btn btn-primary">Save Changes</button>
                    <button type="button" class="btn btn-secondary ms-2" data-bs-dismiss="modal">Cancel</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Store the data temporarily
    let modalData = {};
    
    // When the button is clicked, store the data
    $('[data-bs-target="#updateRoomModal"]').on('click', function() {
        modalData = {
            id: $(this).data('id'),
            roomType: $(this).data('roomtype'),
            genderPolicy: $(this).data('genderpolicy'),
            roomRate: $(this).data('roomrate'),
            downpayment: $(this).data('downpayment'),
            roomCapacity: $(this).data('roomcapacity')
        };
        
        console.log('Data stored:', modalData);
    });
    
    // When the modal is shown, populate the fields
    $('#updateRoomModal').on('shown.bs.modal', function() {
        console.log('Modal shown, populating with:', modalData);
        
        $('#updateId').val(modalData.id);
        $('#updateGenderPolicy').val(modalData.genderPolicy);
        $('#updateRoomRate').val(modalData.roomRate);
        $('#updateDownpayment').val(modalData.downpayment);
        $('#updateRoomCapacity').val(modalData.roomCapacity);
        
        // Handle room type with exact match first, then partial match
        const roomType = modalData.roomType;
        console.log('Trying to set room type:', roomType);
        
        // Try exact match first
        $('#updateRoomType').val(roomType);
        
        // If no match, try partial match
        if (!$('#updateRoomType').val()) {
            console.log('Exact match failed, trying partial match...');
            $('#updateRoomType option').each(function() {
                const optionValue = $(this).val();
                const optionText = $(this).text();
                console.log('Checking option:', optionValue, optionText);
                
                if (roomType && (
                    optionValue.includes(roomType) || 
                    roomType.includes(optionValue) ||
                    optionText.includes(roomType) ||
                    roomType.toLowerCase().includes(optionValue.toLowerCase())
                )) {
                    console.log('Match found! Setting to:', optionValue);
                    $('#updateRoomType').val(optionValue);
                    return false; // break the loop
                }
            });
        }
        
        console.log('Final room type value:', $('#updateRoomType').val());
    });
});
</script>



        <section id="tenants" class="section">
            <h2 class="mb-4">Tenants</h2>

            <!-- Waiting Tenants Table -->
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">Waiting Tenants</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Number</th>
                                    <th>Room Reserved</th>
                                    <th>Expected Move-in Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $sql = "SELECT r.reservation_id, t.name, t.phone_number, rm.room_type, r.move_in
                                            FROM reservations r
                                            JOIN tenants t ON r.tenant_id = t.tenant_id
                                            JOIN rooms rm ON r.room_id = rm.room_id
                                            WHERE r.status = ? AND rm.bh_id = ?";
                                    $stmt = $conn->prepare($sql);
                                    $status = "waiting";
                                    $stmt->bind_param("si", $status, $bh_id);
                                    $stmt->execute();
                                    $result = $stmt->get_result();

                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            $reservation_id = intval($row['reservation_id']);
                                            $name = htmlspecialchars($row['name']);
                                            $number = htmlspecialchars($row['phone_number']);
                                            $room_type = htmlspecialchars($row['room_type']);
                                            $move_in = htmlspecialchars(date('F j, Y', strtotime($row['move_in'])));
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($name) ?></td>
                                                <td><?= htmlspecialchars($number) ?></td>
                                                <td><?= htmlspecialchars($room_type) ?></td>
                                                <td><?= htmlspecialchars($move_in) ?></td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <form method="post" action="adminprocess.php" style="display:inline-block;">
                                                            <input type="hidden" name="reservation_id" value="<?= $reservation_id ?>">
                                                            <button type="submit" name="mark_arrived"
                                                                    class="btn btn-success btn-sm"
                                                                    onclick="return confirm('Are you sure this tenant has arrived?');">
                                                                Mark as Arrived
                                                            </button>
                                                        </form>

                                                        <form method="post" action="adminprocess.php" style="display:inline-block;">
                                                            <input type="hidden" name="reservation_id" value="<?= $reservation_id ?>">
                                                            <button type="submit" name="cancel_reservation"
                                                                    class="btn btn-danger btn-sm"
                                                                    onclick="return confirm('Are you sure you want to cancel this tenant\'s reservation?');">
                                                                Cancel Reservation
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                    } else {
                                        echo '<tr><td colspan="4"><center>No waiting tenants</center></td></tr>';
                                    }

                                    $stmt->close();
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Active Tenants Table -->
            <div class="card">
                <div class="card-header bg-success text-white">Active Tenants</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Room</th>
                                    <th>Start Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $status = "active";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param("si", $status, $bh_id);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<tr>
                                                <td>" . htmlspecialchars($row['name']) . "</td>
                                                <td>" . htmlspecialchars($row['room_type']) . "</td>
                                                <td>" . htmlspecialchars(date("F j, Y", strtotime($row['move_in']))) . "</td>
                                                <td>
                                                    <form method='POST' action='adminprocess.php'
                                                        onsubmit=\"return confirm('Are you sure you want to end this tenant’s contract? This action cannot be undone.');\">
                                                        <input type='hidden' name='reservation_id' value='" . intval($row['reservation_id']) . "'>
                                                        <button type='submit' name='end_contract' class='btn btn-danger btn-sm'>
                                                            End Contract
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4'><center>No active tenants</center></td></tr>";
                                }
                                $stmt->close();
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>



            
            <?php
                $sql = "SELECT 
                            reservation_id,
                            t.name, 
                            t.phone_number,
                            r.room_type, 
                            r.room_picture,
                            res.move_in, 
                            res.duration, 
                            res.reserved_at,  
                            res.notes 
                        FROM reservations res
                        JOIN rooms r ON res.room_id = r.room_id 
                        JOIN tenants t ON t.tenant_id = res.tenant_id 
                        WHERE status = ? AND r.bh_id = ?
                        ORDER BY reserved_at DESC";

                $stmt = $conn->prepare($sql);
                $status = 'pending';
                $stmt->bind_param("si", $status, $bh_id);
                $stmt->execute();
                $result = $stmt->get_result();
            ?>

            <?php
                if(isset($_SESSION['reservation_approved_success'])) {
                        echo "<script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var toastElement = document.getElementById('approvalToast');
                                var toast = new bootstrap.Toast(toastElement, {delay: 4000});
                                toast.show();
                            });
                        </script>";
                        unset($_SESSION['reservation_approved_success']); // Clear the session variable
                    }
            ?>

            <!-- Pending Reservation -->
            <section id="pending-reservations" class="section">
                <h2 class="mb-4">Pending Reservations</h2>
                <div class="card">
                    <div class="toast-container position-fixed top-0 end-0 p-3">
                    <div id="approvalToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="toast-header" style="background-color: #008080; color: white;">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <strong class="me-auto">Success</strong>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <div class="toast-body">
                            <strong>Reservation Approved Successfully!</strong><br>
                            The reservation has been approved and updated.
                        </div>
                    </div>
                </div>
                    <div class="card-header">Reservation Requests</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Phone Number</th>
                                        <th>Room Type</th>
                                        <th>Room Picture</th>
                                        <th>Move-in Date</th>
                                        <th>Duration</th>
                                        <th>Date Reserved</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                                                <td><?php echo htmlspecialchars($row['room_type']); ?></td>
                                                <td><img src="images/roomPictures/<?php echo htmlspecialchars($row['room_picture']);?>" alt="Room Picture" class="room-pic" style="max-width: 100px; height: auto;"></td>
                                                <td><?php echo date('M j, Y', strtotime($row['move_in'])); ?></td>
                                                <td><?php echo htmlspecialchars($row['duration']); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($row['reserved_at'])); ?></td>
                                                <td><?php echo htmlspecialchars($row['notes']); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-primary btn-sm mb-1 me-2" data-bs-toggle="modal" data-bs-target="#approvalModal" data-reservation-id="<?php echo htmlspecialchars($row['reservation_id']); ?>">
                                                        Approve
                                                    </button>
                                                    <form method="POST" action="adminprocess.php">
                                                        <input type="hidden" name="reservation_id" value="<?php echo $row['reservation_id']; ?>">
                                                        <button class="btn btn-secondary mb-1 btn-sm" type="submit" name="reject" onclick="return confirm('Are you sure you want to reject this reservation?');">
                                                            Reject
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">
                                                No pending reservations found
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!--Approval Modal -->
             <div class="modal fade" id="approvalModal" tabindex="-1" aria-labelledby="approvalnModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                    <h1 class="modal-title text-secondary-emphasis fs-5" id="approvalModalLabel">Confirm Reservation Approval</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        
                    Are you sure you want to approve reservation?
                    </div>
                    <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="adminprocess.php" method="POST">
                        <input type="hidden" name="reservationId" id="reservationId">
                        <button type="submit" name="approval" class="btn btn-primary">Approve</button>
                    </form>
                    </div>
                </div>
                </div>
            </div>
            


            <!-- Superadmin Chat Section -->
            <section id="superadmin-communication" class="section">
                <h2 class="mb-4">Superadmin Inbox</h2>
                <div class="card">
                    <div class="card-header">Messages</div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Contact List -->
                            <div class="col-md-4 border-end">
                                <h5>Chat With</h5>
                                <ul class="list-group">
                                    <li class="list-group-item active">Superadmin</li>
                                </ul>
                            </div>
            
                            <!-- Chat Window -->
                            <div class="col-md-8">
                                <div class="mt-3">
                                    <h6>Chatting with: <span>Superadmin</span></h6>
                                </div>
                                <div id="chatWindowLandlord" class="mb-3"
                                     style="height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background-color: #f8f9fa;">
                                    <p class="text-center text-muted">Start chatting with Superadmin</p>
                                </div>
                                <form id="chatFormLandlord">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="chatMessageLandlord" placeholder="Type your message...">
                                        <button type="submit" class="btn btn-success">Send</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
             <!-- JS Logic -->
            <script src="https://kit.fontawesome.com/your-kit.js" crossorigin="anonymous"></script>
            <script>
            let landlordId = <?php echo $landlord_id; ?>;
            let superadminId = 1;
            
            $('#superadminInboxLink').on('click', function(e) {
                e.preventDefault(); // Prevent default scroll
                $.post('mark_as_read_landlord.php', {
                    landlord_id: landlordId,
                    superadmin_id: superadminId
                }, function() {
                    updateUnreadBadgeLandlord(); // Hide badge instantly
                });
            });
            
            // Fetch chat messages
            function fetchLandlordChat() {
                $.getJSON('fetch_messages.php', {
                    landlord_id: landlordId,
                    superadmin_id: superadminId
                }, function(data) {
                    const chatWindow = $('#chatWindowLandlord');
                    chatWindow.html('');
                    data.forEach(msg => {
                        const align = msg.sender_type === 'landlord' ? 'end' : 'start';
                        const bubble = msg.sender_type === 'landlord' ? 'bg-success text-white' : 'bg-light';
                        chatWindow.append(`
                            <div class="d-flex justify-content-${align} mb-2">
                                <div class="${bubble} p-2 rounded" style="max-width: 70%;">
                                    ${msg.message}<br>
                                    <small class="text-muted">${msg.timestamp}</small>
                                </div>
                            </div>
                        `);
                    });
                    chatWindow.scrollTop(chatWindow[0].scrollHeight);
                });
            }
            
            // Send message
            $('#chatFormLandlord').on('submit', function(e) {
                e.preventDefault();
                const message = $('#chatMessageLandlord').val().trim();
                if (!message) return;
            
                $.post('send_message.php', {
                    landlord_id: landlordId,
                    superadmin_id: superadminId,
                    sender_type: 'landlord',
                    message: message
                }, function(res) {
                    const result = JSON.parse(res);
                    if (result.status === 'success') {
                        $('#chatMessageLandlord').val('');
                        fetchLandlordChat();
                    }
                });
            });
            
            // Update unread badge
            function updateUnreadBadgeLandlord() {
                $.getJSON('count_unread_landlord.php', { landlord_id: landlordId }, function(count) {
                    const $badge = $('#superadminBadge');
                    if (count > 0) $badge.text(count).show();
                    else $badge.hide();
                });
            }
            
            // Initial load
            $(document).ready(function() {
                fetchLandlordChat();
                updateUnreadBadgeLandlord();
                setInterval(fetchLandlordChat, 3000); // auto refresh chat
                setInterval(updateUnreadBadgeLandlord, 5000); // refresh badge
            });
            </script>



<!-- Boarding House Management Section -->
<section id="boarding-house-management" class="section">
    <?php

    if(isset($_SESSION['amenity_added_success'])){
                        echo "<script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var toastElement = document.getElementById('AmenitySuccessToast');
                                var toast = new bootstrap.Toast(toastElement, {delay: 4000});
                                toast.show();
                            });
                        </script>";
                        unset($_SESSION['amenity_added_success']);
    }elseif(isset($_SESSION['update_payment'])){
                        echo "<script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var toastElement = document.getElementById('paymentSuccessToast');
                                var toast = new bootstrap.Toast(toastElement, {delay: 4000});
                                toast.show();
                            });
                        </script>";
                        unset($_SESSION['update_payment']);
    
    }
    elseif(isset($_SESSION['delete_amenity'])){
                        echo "<script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var toastElement = document.getElementById('delete_amenity');
                                var toast = new bootstrap.Toast(toastElement, {delay: 4000});
                                toast.show();
                            });
                        </script>";
                        unset($_SESSION['delete_amenity']);
    
    }
    elseif(isset($_SESSION['update_amenity'])){
                        echo "<script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var toastElement = document.getElementById('update_amenity');
                                var toast = new bootstrap.Toast(toastElement, {delay: 4000});
                                toast.show();
                            });
                        </script>";
                        unset($_SESSION['update_amenity']);
    
    }
    ?>
    <div class="toast-container position-fixed top-0 end-0 p-3">
                    <div id="update_amenity" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="toast-header bg-success text-white">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <strong class="me-auto">Success</strong>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <div class="toast-body">
                            <strong>Amenity Updated Successfully!</strong><br>
                            Amenity name updated successfully!
                        </div>
                    </div>
                </div>
    <div class="toast-container position-fixed top-0 end-0 p-3">
                    <div id="delete_amenity" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="toast-header bg-success text-white">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <strong class="me-auto">Success</strong>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <div class="toast-body">
                            <strong>Amenity Removed Successfully!</strong><br>
                            The amenity was successfully removed from the boarding house.
                        </div>
                    </div>
                </div>
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="AmenitySuccessToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-success text-white">
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong class="me-auto">Success</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                <strong>Amenity Added Successfully!</strong><br>
                A new amenity has been added to the boarding house.
            </div>
        </div>
    </div>
        <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="paymentSuccessToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-success text-white">
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong class="me-auto">Success</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                <strong>Payment Status Updated Successfully!</strong><br>
                Payment status has been successfully updated.
            </div>
        </div>
    </div>
    <h2 class="mb-4">Boarding House Management</h2>
    <!-- Picture -->
    <?php
    $sql = "SELECT bh_pic FROM bh_details WHERE bh_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $bh_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $currentPic = $row['bh_pic'] ?? null;
    $stmt->close();
    ?>
        <div class="card mb-4">
        <div class="card-header mb-4">Boarding House Picture</div>
        <?php if (!empty($currentPic)): ?>
            <!-- Show card if picture exists -->

                <div class="card-body text-center">
                    <img src="<?= htmlspecialchars($currentPic) ?>" 
                        alt="Boarding House Picture" 
                        style="max-width: 300px; height: auto; border-radius: 8px;">
                    
                    <div class="mt-2">
                        <button class="btn btn-primary btn-sm" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editPictureModal">
                            Edit Picture
                        </button>
                    </div>
                </div>

        <?php else: ?>
            <!-- Show upload button only if no picture -->
            <div class="card-body text-center mb-4">
                <button class="btn btn-primary btn-sm" 
                        data-bs-toggle="modal" 
                        data-bs-target="#editPictureModal">
                    Upload Picture
                </button>
            </div>
        <?php endif; ?>

    </div>
    <div class="card mb-4 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Visit Site</span>
    </div>

    <div class="card-body">
        <div class="d-flex justify-content-end">
            <a href="index.php" class="btn btn-primary">Visit Site</a>
        </div>
    </div>
</div>
<?php
    $_SESSION['role']= 'landlord';
?>

    <div class="card mb-4 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Payment Status</span>
    </div>

    <div class="card-body">
        <?php 
        $payment_status = "";

        if ($bh_id > 0) {
            // Fetch payment status from bh table
            $stmt = $conn->prepare("SELECT payment_status FROM bh_details WHERE bh_id = ?");
            $stmt->bind_param("i", $bh_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $payment_status = $row['payment_status'];
            }
        }
        if (!empty($payment_status)): ?>
            <p>
            
                <span class="badge bg-success fs-6 py-2 px-3"><?= ucfirst(htmlspecialchars($payment_status)) ?></span>
                <div class="mt-2">
                    <button class="btn btn-primary btn-sm" id="editPaymentBtn" data-bs-toggle="modal" data-bs-target="#editPaymentModal">Edit</button>
                </div>
        <?php endif; ?>

        <form method="post" action="adminprocess.php" id="paymentForm" class="<?= !empty($payment_status) ? 'd-none' : '' ?>">
            <input type="hidden" name="bh_id" value="<?= $bh_id ?>"> 

            <div class="mb-3">
                <label for="paymentStatus" class="form-label">Select Payment Option</label>
                <select class="form-select" id="paymentStatus" name="payment_status" onchange="toggleCustomPayment(this)">
                    <option value="">-- Select Payment Status --</option>
                    <option value="monthly" <?= ($payment_status == "monthly") ? "selected" : "" ?>>Monthly</option>
                    <option value="quarterly" <?= ($payment_status == "quarterly") ? "selected" : "" ?>>Quarterly</option>
                    <option value="semi-annual" <?= ($payment_status == "semi-annual") ? "selected" : "" ?>>Semi-Annual</option>
                    <option value="annual" <?= ($payment_status == "annual") ? "selected" : "" ?>>Annual</option>
                    <option value="custom" <?= !in_array($payment_status, ["monthly","quarterly","semi-annual","annual"]) && !empty($payment_status) ? "selected" : "" ?>>Custom</option>
                </select>
            </div>

            <div class="mb-3 <?= !in_array($payment_status, ["monthly","quarterly","semi-annual","annual"]) && !empty($payment_status) ? "" : "d-none" ?>" id="customPaymentDiv">
                <label for="customPayment" class="form-label">Custom Payment Status</label>
                <input type="text" class="form-control" id="customPayment" name="custom_payment" value="<?= !in_array($payment_status, ["monthly","quarterly","semi-annual","annual"]) ? htmlspecialchars($payment_status) : "" ?>" placeholder="Enter custom payment status">
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" name="save_payment_status" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
    </div>

    <script>
        function toggleCustomPayment(select) {
            let customDiv = document.getElementById("customPaymentDiv");
            if (select.value === "custom") {
                customDiv.classList.remove("d-none");
            } else {
                customDiv.classList.add("d-none");
            }
        }

        document.getElementById("editPaymentBtn")?.addEventListener("click", function() {
            let form = document.getElementById("paymentForm");

            if (form.classList.contains("d-none")) {
                form.classList.remove("d-none");
                this.innerHTML = "×";
            } else {
                form.classList.add("d-none");
                this.innerHTML = "Edit";
                this.classList.remove("btn-outline-danger");
            }
        });
    </script>

    <!-- Facilities -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Amenities</span>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editFacilityModal" data-index="-1">Add Amenity</button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Amenity Name</th>
                            <th>Rooms Excluded</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // fetch all amenities (system + custom) for this BH
                        $sql = "
                            SELECT 
                                ba.id,
                                a.amenity_name,
                                ba.custom_amenity
                            FROM bh_amenities ba
                            LEFT JOIN amenities a ON ba.amenity_id = a.id
                            WHERE ba.bh_id = $bh_id
                        ";
                        $res = $conn->query($sql);

                        if ($res->num_rows > 0) {
                            while ($row = $res->fetch_assoc()) {
                                $facilityId   = $row['id'];
                                $facilityName = $row['amenity_name'] ?? $row['custom_amenity'];

                                // get all rooms of this boarding house
                                $rooms_sql = "SELECT room_id, room_type FROM rooms WHERE bh_id = $bh_id";
                                $rooms_res = $conn->query($rooms_sql);

                                $rooms = [];
                                while ($r = $rooms_res->fetch_assoc()) {
                                    $rooms[$r['room_id']] = $r['room_type'];
                                }

                                

                                // get excluded rooms for this amenity
                                $excluded_sql = "SELECT room_id FROM room_excluded_amenities WHERE bh_amenity_id = $facilityId";
                                $excluded_res = $conn->query($excluded_sql);

                                $excluded = [];
                                while ($ex = $excluded_res->fetch_assoc()) {
                                    $excluded[] = $ex['room_id'];
                                }

                                // determine which rooms are excluded (not included)
                                $excludedRoomsList = [];
                                foreach ($rooms as $rid => $rnum) {
                                    if (in_array($rid, $excluded)) {
                                        $excludedRoomsList[] = $rnum;
                                    }
                                }


                                echo '<tr>';
                                echo '<td>'.htmlspecialchars($facilityName).'</td>';

                                if (count($excludedRoomsList) === count($rooms)) {
                                    echo '<td>None</td>';
                                } elseif (empty($excludedRoomsList)) {
                                    echo '<td><em>None</em></td>';
                                } else {
                                    echo '<td>'.implode(', ', $excludedRoomsList).'</td>';
                                }

                                echo '<td>';

                                // Show Edit button ONLY if it's a custom amenity
                                if (!empty($row['custom_amenity'])) {
                                    echo '<button class="btn btn-primary btn-sm me-2 edit-btn"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editFacilityNameModal"
                                            data-amenityId="'.$facilityId.'" 
                                            data-name="'.htmlspecialchars($row['custom_amenity']).'">Edit</button>
                                    ';
                                }

                                // Always show Manage Rooms button
                                echo '<button class="btn btn-secondary btn-sm me-2" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#facilityRoomsModal" 
                                            data-bh-amenity-id="'.$facilityId.'">Manage Rooms</button>';

                                // Delete form (still applies to both)
                                echo '<form action="adminprocess.php" method="POST" style="display:inline;" 
                                            onsubmit="return confirm(\'Are you sure you want to delete this amenity?\');">
                                            <input type="hidden" name="amenity_id" value="'.$facilityId.'">
                                            <button type="submit" name="delete_amenity" class="btn btn-danger btn-sm">Delete</button>
                                    </form>';

                                echo '</td></tr>';
                            }
                        } else {
                            echo '<tr><td colspan="3" class="text-center">No facilities added yet</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php

        // Fetch default utilities
        $stmt = $conn->prepare("SELECT u.utility_id, u.utility_name
                                FROM utilities u
                                WHERE u.is_default = 1
                                ORDER BY u.utility_name ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        $default_utilities = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Fetch all utilities already added to this BH
        $sql = "SELECT u.utility_id, u.utility_name, u.is_default, b.is_included, b.utility_id, p.cost, p.unit, b.bh_utility_id
                FROM bh_utilities b JOIN utilities u ON b.utility_id = u.utility_id 
                LEFT JOIN utility_pricing p ON b.bh_utility_id = p.bh_utility_id AND p.bh_id = ?
                WHERE b.bh_id = ?
                ORDER BY u.is_default DESC, u.utility_name ASC;";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $bh_id, $bh_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $utilities = $result->fetch_all(MYSQLI_ASSOC);

        // Fetch rooms for manage rooms modal
        $sql_rooms = "SELECT room_id, room_type FROM rooms WHERE bh_id = ?";
        $stmt_rooms = $conn->prepare($sql_rooms);
        $stmt_rooms->bind_param("i", $bh_id);
        $stmt_rooms->execute();
        $result_rooms = $stmt_rooms->get_result();
        $rooms = $result_rooms->fetch_all(MYSQLI_ASSOC);
    ?>

    <!-- Utilities Card -->
    <div class="card" id="utilitiesCard">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Utilities</span>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUtilityModal">Add Utilities</button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="utilitiesTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Included?</th>
                            <th>Cost / Unit</th>
                            <th>Excluded Rooms</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($utilities)): ?>
                            <?php foreach($utilities as $i => $u): ?>
                                <?php
                                // Fetch excluded rooms for this utility
                                $stmt_excl = $conn->prepare("SELECT r.room_type FROM room_excluded_utilities reu JOIN rooms r ON reu.room_id = r.room_id WHERE reu.bh_utility_id = ? AND r.bh_id = ?");
                                $stmt_excl->bind_param("ii", $u['bh_utility_id'], $bh_id);
                                $stmt_excl->execute();
                                $res_excl = $stmt_excl->get_result();
                                $excluded_rooms = array_column($res_excl->fetch_all(MYSQLI_ASSOC), 'room_type');
                                $stmt_excl->close();
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($u['utility_name']) ?></td>
                                    <td><?= ($u['is_included']=='no') ? 'No' : 'Yes' ?></td>
                                    <td>
                                        <?= ($u['is_included']=='no' && !empty($u['cost'])) ? htmlspecialchars($u['cost'].' / '.$u['unit']) : '-' ?>
                                    </td>
                                    <td><?= !empty($excluded_rooms) ? implode(", ", $excluded_rooms) : '<em>None</em>' ?></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-primary btn-sm editUtilityBtn" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editUtilityModal" 
                                                    data-index="<?= $i ?>"
                                                    data-utility='<?= json_encode($u) ?>'>
                                                Edit
                                            </button>
                                            <button 
                                                class="btn btn-secondary btn-sm manageRoomsBtn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#manageRoomsModal"
                                                data-utilityId="<?= $u['bh_utility_id'] ?>">
                                                Manage Rooms 
                                            </button>
                                            <form method="post" action="adminprocess.php" style="display:inline;">
                                                <input type="hidden" name="deleteBhUtilityId" value="<?= $u['bh_utility_id'] ?>">
                                                <input type="hidden" name="deleteUtilityId" value="<?= $u['utility_id'] ?>">
                                                <button type="submit" name="delete_utility" onclick="return confirm('Are you sure you want to delete this?')" class="btn btn-danger btn-sm">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No utilities added yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Utilities Modal -->
    <div class="modal fade" id="addUtilityModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="adminprocess.php" id="utilityForm">
                <input type="hidden" name="bh_id" value="<?= $bh_id ?>">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-secondary-emphasis">Add Utilities</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Default Utilities -->
                        <h6>Default Utilities</h6>
                        <?php foreach($default_utilities as $u): ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" 
                                    name="default_utilities[]" 
                                    value="<?= $u['utility_id'] ?>" 
                                    id="default_<?= $u['utility_id'] ?>" checked>
                                <label class="form-check-label" for="default_<?= $u['utility_id'] ?>">
                                    <?= htmlspecialchars($u['utility_name']) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>

                        <hr>

                        <!-- Custom Utilities Section -->
                        <h6>Custom Utilities</h6>
                        <div id="customUtilitiesContainer"></div>
                        <button type="button" class="btn btn-sm btn-secondary mb-2" onclick="addCustomUtility()">+ Add Custom Utility</button>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="add_utility" class="btn btn-success">Save Utilities</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Utility Modal -->
    <div class="modal fade" id="editUtilityModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="adminprocess.php" id="editUtilityForm">
                <input type="hidden" name="bh_id" value="<?= $bh_id ?>">
                <input type="hidden" name="bh_utility_id" id="editBhUtilityId">
                <input type="hidden" name="utility_id" id="editUtilityId">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-secondary-emphasis">Edit Utility</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label>Name</label>
                            <input type="text" name="utility_name" id="editUtilityName" class="form-control form-control-sm">
                        </div>
                        <div class="mb-2">
                            <label>Included?</label>
                            <select name="is_included" id="editUtilityIncluded" class="form-select form-select-sm">
                                <option value="yes">Yes</option>
                                <option value="no">No</option>
                            </select>
                        </div>
                        <div class="mb-2" id="editCostUnitDiv" style="display:none;">
                            <label>Cost</label>
                            <input type="number" name="cost" id="editCost" class="form-control form-control-sm mb-1">
                            <label>Unit</label>
                            <input type="text" name="unit" id="editUnit" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="edit_utility" class="btn btn-success">Save Changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>



    <!-- Manage Rooms Modal for Utility -->
    <div class="modal fade" id="manageRoomsModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="adminprocess.php">
                <input type="hidden" name="bh_id" value="<?= $bh_id ?>">
                <input type="hidden" name="bh_utility_id" id="utilityId">

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-secondary-emphasis">Manage Rooms for Utility</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <?php foreach ($rooms as $room): ?>
                            <div class="form-check mb-1">
                <input class="form-check-input room-checkbox"
                        type="checkbox"
                                    name="excludedUtilityRooms[]"
                                    value="<?= $room['room_id'] ?>"
                        id="room_<?= $room['room_id'] ?>">
                                <label class="form-check-label" for="room_<?= $room['room_id'] ?>">
                                    <?= htmlspecialchars($room['room_type']) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="save_excluded_rooms" class="btn btn-success">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var manageRoomsModal = document.getElementById('manageRoomsModal');
            manageRoomsModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var utilityId = button.getAttribute('data-utilityId'); 

            // Set hidden input
            document.getElementById('utilityId').value = utilityId;

            // Reset checkboxes first
            document.querySelectorAll('.room-checkbox').forEach(cb => cb.checked = false);

            // Fetch excluded rooms
            fetch('get_excluded_rooms.php?utility_id=' + utilityId)
            .then(response => response.json())
            .then(data => {
                data.forEach(roomId => {
                var cb = document.getElementById('room_' + roomId);
                if (cb) cb.checked = true;
                });
            });
            });
        });
    </script>



    <script>
        let customIndex = 1000;
        function addCustomUtility() {
            let container = document.getElementById('customUtilitiesContainer');
            let html = `
                <div class="mb-2">
                    <input type="text" name="custom_utilities[${customIndex}][name]" placeholder="Utility Name" class="form-control form-control-sm mb-1" required>
                    <select name="custom_utilities[${customIndex}][included]" class="form-select form-select-sm mb-1">
                        <option value="yes">Included</option>
                        <option value="no">Not Included</option>
                    </select>
                    <button type="button" class="btn btn-sm btn-danger" onclick="this.parentNode.remove()">Remove</button>
                </div>`;
            container.insertAdjacentHTML('beforeend', html);
            customIndex++;
        }

        // Show/hide cost/unit fields in edit modal
        document.getElementById('editUtilityIncluded').addEventListener('change', function() {
            document.getElementById('editCostUnitDiv').style.display = this.value==='no' ? 'block' : 'none';
        });

        // Populate edit modal when edit button is clicked
            document.querySelectorAll('.editUtilityBtn').forEach(btn=>{
                btn.addEventListener('click', function(){
                    let u = JSON.parse(this.dataset.utility);
                    document.getElementById('editUtilityId').value = u.utility_id;
                    document.getElementById('editBhUtilityId').value = u.bh_utility_id;
                    document.getElementById('editUtilityName').value = u.is_default==1 ? u.utility_name : u.utility_name;
                    document.getElementById('editUtilityName').disabled = u.is_default==1 ? true : false;
                    document.getElementById('editUtilityIncluded').value = u.is_included;
                    document.getElementById('editCost').value = u.cost ? u.cost : '';
                    document.getElementById('editUnit').value = u.unit ? u.unit : '';
                    document.getElementById('editCostUnitDiv').style.display = u.is_included=='no' ? 'block' : 'none';
                });
            });

        // Populate manage rooms modal with utility ID
        document.querySelectorAll('.manageRoomsBtn').forEach(btn=>{
            btn.addEventListener('click', function(){
                document.getElementById('manageRoomsUtilityId').value = this.dataset.utilityid;
                // Could add code here to pre-check excluded rooms via ajax if needed
            });
        });
    </script>



</section>

<!-- Edit/Upload Picture Modal -->
<div class="modal fade" id="editPictureModal" tabindex="-1" aria-labelledby="editPictureModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-secondary-emphasis" id="editPictureModalLabel">
                    <?= !empty($currentPic) ? 'Edit Boarding House Picture' : 'Upload Boarding House Picture'; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="adminprocess.php" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="bhPicture" class="form-label">Select Picture (JPG, PNG only)</label>
                        <input type="file" class="form-control" id="bhPicture" name="bh_picture" accept="image/*" required>
                    </div>
                    <input type="hidden" name="bh_id" value="<?= intval($bh_id) ?>">
                    <button type="submit" class="btn btn-primary" name="save_picture">
                        <?= !empty($currentPic) ? 'Update Picture' : 'Upload Picture'; ?>
                    </button>
                    <button type="button" class="btn btn-secondary ms-2" data-bs-dismiss="modal">Cancel</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Amenity Modal -->
<div class="modal fade" id="editFacilityModal" tabindex="-1" aria-labelledby="editFacilityModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-secondary-emphasis" id="editFacilityModalLabel">Edit Amenities</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="post" action="adminprocess.php" id="editFacilityForm">
          <input type="hidden" name="bh_id" value="<?= $bh_id ?>"> 

          <?php
          // Fetch all amenities
          $allAmenities = $conn->query("SELECT * FROM amenities");

          // Fetch amenities already assigned to this BH
          $savedAmenities = [];
          $res = $conn->query("SELECT amenity_id FROM bh_amenities WHERE bh_id = $bh_id");
          while ($row = $res->fetch_assoc()) {
              $savedAmenities[] = $row['amenity_id'];
          }

          // Display grouped: Room vs Common
          echo '<h6 class="mb-2">Room Amenities</h6><div class="row mb-3">';
          while ($row = $allAmenities->fetch_assoc()) {
              $checked = in_array($row['id'], $savedAmenities) ? "checked" : "";
              if (in_array($row['amenity_name'], ["Air Conditioning","Private Bathroom","Bed","Study Table","Cabinet / Closet"])) {
                  echo '<div class="col-md-6">
                          <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="default_amenities[]" value="'.$row['id'].'" id="amenity'.$row['id'].'" '.$checked.'>
                            <label class="form-check-label" for="amenity'.$row['id'].'">'.$row['amenity_name'].'</label>
                          </div>
                        </div>';
              }
          }
          echo '</div>';

          // rewind pointer
          $allAmenities->data_seek(0);

          echo '<h6 class="mb-2">Common Amenities</h6><div class="row mb-3">';
          while ($row = $allAmenities->fetch_assoc()) {
              $checked = in_array($row['id'], $savedAmenities) ? "checked" : "";
              if (in_array($row['amenity_name'], ["WiFi","Parking Space","Laundry Area"])) {
                  echo '<div class="col-md-6">
                          <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="default_amenities[]" value="'.$row['id'].'" id="amenity'.$row['id'].'" '.$checked.'>
                            <label class="form-check-label" for="amenity'.$row['id'].'">'.$row['amenity_name'].'</label>
                          </div>
                        </div>';
              }
          }
          echo '</div>';
          ?>

          <!-- Custom Amenities -->
          <hr>
          <h6>Custom Amenities</h6>
          <div id="customAmenityContainer"></div>
          <button type="button" class="btn btn-sm btn-secondary mb-2" onclick="addCustomAmenity()">+ Add Custom Amenity</button>

          <div class="mt-4 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary" name="add_amenity">Save Amenities</button>
            <button type="button" class="btn btn-secondary ms-2" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
let customAmenityIndex = 1000;
function addCustomAmenity() {
  const container = document.getElementById('customAmenityContainer');
  const html = `
    <div class="mb-2">
      <input type="text" name="custom_amenities[${customAmenityIndex}][name]" placeholder="Amenity Name" class="form-control form-control-sm mb-1" required>
      <button type="button" class="btn btn-sm btn-danger" onclick="this.parentNode.remove()">Remove</button>
    </div>`;
  container.insertAdjacentHTML('beforeend', html);
  customAmenityIndex++;
}
</script>


<!-- Edit Facility Name Modal -->
<div class="modal fade" id="editFacilityNameModal" tabindex="-1" aria-labelledby="editFacilityNameModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      
      <div class="modal-header">
        <h5 class="modal-title" id="editFacilityNameModalLabel">Edit Amenity Name</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form action="adminprocess.php" method="POST">
        <div class="modal-body">
          <!-- Hidden ID -->
          <input type="hidden" id="editAmenityId" name="amenityId">

          <!-- Facility Name -->
          <div class="mb-3">
            <label for="editAmenityName" class="form-label">Amenity Name</label>
            <input type="text" class="form-control" id="editAmenityName" name="amenityName" required>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="update_amenity" class="btn btn-primary">Save Changes</button>
        </div>
      </form>

    </div>
  </div>
</div>


<!-- Amenity Room Exclusion Modal -->
<div class="modal fade" id="facilityRoomsModal" tabindex="-1" aria-labelledby="facilityRoomsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-secondary-emphasis" id="facilityRoomsModalLabel">Manage Amenity Rooms</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <?php
            $rooms = [];

            if ($bh_id > 0) {
                // Adjust table names if needed — example: rooms join bh_details
                $stmt = $conn->prepare("
                    SELECT r.room_id, r.room_type
                    FROM rooms r
                    INNER JOIN boarding_houses bh ON r.bh_id = bh.bh_id
                    WHERE bh.bh_id = ?
                ");
                $stmt->bind_param("i", $bh_id);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $rooms[] = $row; // store room id and name
                }
            }
            ?>

            <div class="modal-body">
                <form method="post" action="adminprocess.php" id="facilityRoomsForm">
                    <input type="hidden" name="bh_amenity_id" id="bh_amenity_id">
                    <input type="hidden" name="bh_id" value="<?= $bh_id ?>">
                    <p class="text-muted small">Check rooms to exclude from this amenity</p>
                        <?php if (!empty($rooms)): ?>
                            <?php foreach ($rooms as $room): ?>
                                <div class="form-check">

                            <input type="checkbox" class="form-check-input roomAmenity-checkbox" 
                                           id="facility-room-<?= $room['room_id'] ?>" 
                                name="excludedRooms[]" value="<?= $room['room_id'] ?>">
                                    <label class="form-check-label" for="facility-room-<?= $room['room_id'] ?>">
                                        <?= htmlspecialchars($room['room_type']) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No rooms found for this boarding house.</p>
                        <?php endif; ?>

                    <button type="submit" name="excluded_room" class="btn btn-primary">Save Room Assignments</button>
                    <button type="button" class="btn btn-secondary ms-2" data-bs-dismiss="modal">Cancel</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Facility Rooms Modal
    const facilityRoomsModal = document.getElementById('facilityRoomsModal');
    facilityRoomsModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const amenityId = button.getAttribute('data-bh-amenity-id');
        
        // Set hidden input value
        facilityRoomsModal.querySelector('#bh_amenity_id').value = amenityId;

        // Uncheck all checkboxes first
        document.querySelectorAll('.roomAmenity-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });

        // Fetch excluded rooms and check them
        fetch('get_excluded_rooms_amenity.php?bhAmenity_id=' + amenityId)
            .then(response => response.json())
            .then(data => {
                console.log('Excluded rooms:', data); // Debug log
                data.forEach(roomId => {
                    const checkbox = document.getElementById('facility-room-' + roomId);
                    if (checkbox) {
                        checkbox.checked = true;
                    } else {
                        console.log('Checkbox not found for room_id:', roomId); // Debug
                    }
                });
            })
            .catch(error => {
                console.error('Error fetching excluded rooms:', error);
            });
    });

    // Edit Facility Name Modal
    const editFacilityModal = document.getElementById('editFacilityNameModal');
    editFacilityModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const amenityId = button.getAttribute('data-amenityId');
        const amenityName = button.getAttribute('data-name');

        editFacilityModal.querySelector('#editAmenityId').value = amenityId;
        editFacilityModal.querySelector('#editAmenityName').value = amenityName;
    });
});
</script>




            <section id="settings" class="section">
                <h2 class="mb-4">Settings</h2>
                
                <div class="row">
                    <div class="col-md-6 mx-auto">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Change Password</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="adminprocess.php">
                                    <input type="hidden" name="landlord_id" value="<?= $landlord_id?>">
                                    <!-- Current Password -->
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <input type="password" class="form-control" name="current_password" required>
                                    </div>
            
                                    <!-- New Password -->
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" name="new_password" 
                                               required minlength="8">
                                        <div class="form-text">Minimum 8 characters</div>
                                    </div>
            
                                    <!-- Confirm New Password -->
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" name="confirm_password" required>
                                    </div>
            
                                    <!-- Submit Button -->
                                    <button type="submit" name="change_password" class="btn btn-primary w-100">
                                        Update Password
                                    </button>
            
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>



            <div class="footer-text">
                <p>© 2025 CyBoard: Boarding House Reservation System</p>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('active');
        }

            // Show section
    function showSection(sectionId) {
        document.querySelectorAll('.section').forEach(section => {
            section.classList.remove('active');
        });
        const sectionElement = document.querySelector(`#${sectionId}`);
        if (sectionElement) {
            sectionElement.classList.add('active');
            setActiveNav(sectionId);
        } else {
            showSection('dashboard');
        }
    }

    // Set active nav link
    function setActiveNav(sectionId) {
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
            const href = link.getAttribute('href').replace('#', '');
            if (href === sectionId) {
                link.classList.add('active');
            }
        });
    }

    // Event listeners
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const sectionId = link.getAttribute('href').replace('#', '');
            if (sectionId !== 'logout.php') {
                showSection(sectionId);
            } else {
                window.location.href = 'logout.php';
            }
        });
    });

    // Initialize
    window.onload = function() {
        const urlParams = new URLSearchParams(window.location.search);
        const section = urlParams.get('section') || 'dashboard';
        console.log('Section:', section); // Debug
        showSection(section);
    };

    $(document).ready(function() {
    $('[data-bs-target="#addFacilityModal"]').on('click', function() {
        // Get data attributes from the button
        const room_id = $(this).data('room_id');
        const room_type = $(this).data('room_type');

        // Populate the modal with the data
        $('#room_id').val(room_id);
        $('#room_type').val(room_type);
    });
});

    $(document).ready(function() {
    $('[data-bs-target="#approvalModal"]').on('click', function() {
        // Get data attributes from the button
        const reservationId = $(this).data('reservationId');
        

        // Populate the modal with the data
        $('#reservationId').val(reservationId);
        });
    });

    </script>

    
</body>
</html>