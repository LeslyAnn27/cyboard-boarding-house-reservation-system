    <?php
    session_start();
    $tenant_id = $_POST['tenant_id'] ?? $_SESSION['tenant_id'] ?? null;
    $bh_id = $_POST['bh_id'] ?? $_SESSION['bh_id'] ?? null;
    if ($bh_id === null) {
        header("Location: index.php");
        exit;
    }
    ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CyBoard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <script src="https://kit.fontawesome.com/14901788bc.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="/css/bhouse.css">
</head>
<body>

    <header>
        <div class="header-container">
            <a href="#" class="logo">CyBoard</a>
            <button class="menu-toggle" onclick="toggleMenu()">
                <i class="fas fa-bars"></i>
            </button>
            <nav>
                <ul>
                    <li><a href="index.php"><i class="fa-solid fa-house"></i> Home</a></li>
                    <li><a href="#"><i class="fa-solid fa-circle-info"></i> About Us</a></li>
                </ul>
            </nav>
            <div class="user-actions">
                <div class="user-profile dropdown">

                <?php
include('conn.php');

// SUPERADMIN
if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin') {
    echo '
    <div class="d-flex align-items-center">
        <i class="fa-solid fa-arrow-left me-2" 
           style="font-size: 28px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"></i>
        <span>
            <a href="superadmin.php" style="text-decoration: none; color: inherit;">Go Back to Dashboard</a>
        </span>
    </div>
    ';

// LANDLORD
} elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'landlord') {
    echo '
    <div class="d-flex align-items-center">
        <i class="fa-solid fa-arrow-left me-2" 
           style="font-size: 28px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"></i>
        <span>
            <a href="admin.php" style="text-decoration: none; color: inherit;">Go Back to Dashboard</a>
        </span>
    </div>
    ';

// NOT LOGGED IN
} elseif (!isset($_SESSION['tenant_id'])) {
    echo '
    <div class="d-flex align-items-center">
        <i class="fa-regular fa-circle-user me-2" 
           style="font-size: 28px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"></i>
        <span>
            <a href="login.php" style="text-decoration: none; color: inherit;">Sign In</a>
        </span>
    </div>
    ';
    $id = "";

// TENANT LOGGED IN
} else {
    $tenant_id = $_SESSION['tenant_id'];

    $stmt = $conn->prepare("SELECT name FROM tenants WHERE tenant_id = ?");
    $stmt->bind_param("i", $tenant_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $fullname = htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8');
        $firstName = explode(' ', trim($fullname))[0];
    }
    $stmt->close();

    echo '
    <div class="d-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer;">
        <i class="fa-regular fa-circle-user me-2" 
           style="font-size: 28px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"></i>
        <span>' . $firstName . '</span>
    </div>
    <div class="dropdown-menu">
        <a href="profile.php" class="dropdown-item"><i class="fa-sharp fa-solid fa-user"></i> My Profile</a>
        <a href="logout.php" class="dropdown-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
    ';
}
?>



                </div>
            </div>
        </div>
    </header>
    <?php
    if(isset($_SESSION['reservation_successful'])){
                        echo "<script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var toastElement = document.getElementById('successToast');
                                var toast = new bootstrap.Toast(toastElement, {delay: 4000});
                                toast.show();
                            });
                        </script>";
                        unset($_SESSION['reservation_successful']);
                    } 
    elseif(isset($_SESSION['already_reserved'])){
        echo "<script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var toastElement = document.getElementById('alreadyReservedToast');
                                var toast = new bootstrap.Toast(toastElement, {delay: 4000});
                                toast.show();
                            });
                        </script>";
                        unset($_SESSION['already_reserved']);
    }
    elseif(isset($_SESSION['active reservation'])){
        echo "<script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var toastElement = document.getElementById('activeReservedToast');
                                var toast = new bootstrap.Toast(toastElement, {delay: 4000});
                                toast.show();
                            });
                        </script>";
                        unset($_SESSION['active reservation']);
    }
    elseif(isset($_SESSION['maximum_reservations'])){
        echo "<script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var toastElement = document.getElementById('maxReservationToast');
                                var toast = new bootstrap.Toast(toastElement, {delay: 4000});
                                toast.show();
                            });
                        </script>";
                        unset($_SESSION['maximum_reservations']);
    }
    
    ?>
    <div class="toast-container position-fixed top-0 end-0 p-3">
                    <div id="successToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="toast-header bg-success text-white">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <strong class="me-auto">Reservation Submitted</strong>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <div class="toast-body">
                    <strong>Reservation Submitted Successfully!</strong><br>
                    Your room reservation request has been received and is pending approval.
                </div>
                    </div>
                </div>
                
                
    <div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="activeReservedToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-warning text-dark">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong class="me-auto">Notice</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            <strong>You already have an active or waiting reservation.</strong><br>
            You cannot reserve another room.
        </div>
    </div>
</div>

    <div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="alreadyReservedToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-warning text-dark">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong class="me-auto">Notice</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            <strong>You Already Reserved This Room!</strong><br>
            You cannot reserve the same room again.
        </div>
    </div>
</div>

<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="maxReservationToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-warning text-dark">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong class="me-auto">Notice</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            <strong>Maximum Reservation Reached!</strong><br>
            You have reached the maximum limit of 3 reservations.
        </div>
    </div>
</div>
<?php
 $stmt = $conn->prepare("SELECT payment_status, bh_pic FROM bh_details WHERE bh_id = ?");
 $stmt->bind_param("i", $bh_id);
 $stmt->execute();
 $stmt->bind_result($paymentStatus, $bh_pic);
 $stmt->fetch();
 $stmt->close(); 
  ?>

    <div class="container mt-4">
        <?php
        include('conn.php');
        
        // Get boarding house details
        $bh_query = "SELECT * FROM boarding_houses WHERE bh_id = $bh_id";
        $bh_result = mysqli_query($conn, $bh_query);
        
        if ($bh_result && $bh_result->num_rows > 0) {
            $bh_data = mysqli_fetch_assoc($bh_result);
        ?>

        <div class="bh-header">
            <div class="bh-hero-image">
                <img src="<?= $bh_pic ?: 'https://dummyimage.com/400x300/cccccc/666&text=No+Photo'; ?>">
            </div>
            <h1 class="bh-title"><?php echo htmlspecialchars($bh_data['bh_name']); ?></h1>
            <div class="bh-address">
                <i class="fa-solid fa-location-dot"></i>
                <?php echo htmlspecialchars($bh_data['bh_address']); ?>
            </div>
        
        </div>
    </div>

    <div class="section" id="room">
        <div class="container">
            <div class="row mb-5 align-items-center">
                <div class="col-lg-6">
                    <h2 class="font-weight-bold text-primary heading">Available Rooms</h2>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="property-slider-wrap">
                        <div class="property-slider" id="roomSlider">
                            <?php
                        // fetch amenities
                        $amenStmt = $conn->prepare("
                            SELECT 
                                ba.id AS bh_amenity_id,
                                COALESCE(ba.custom_amenity, a.amenity_name) AS amenity_name
                            FROM bh_amenities ba
                            LEFT JOIN amenities a ON ba.amenity_id = a.id
                            WHERE ba.bh_id = ?
                            ORDER BY a.amenity_name
                        ");
                        $amenStmt->bind_param("i", $bh_id);
                        $amenStmt->execute();
                        $amenRes = $amenStmt->get_result();
                        $amenitiesForBH = $amenRes->fetch_all(MYSQLI_ASSOC);
                        $amenStmt->close();
                            
                            // get all rooms
                            $rooms_query = "SELECT *
                                           FROM rooms
                                           WHERE bh_id = $bh_id AND room_capacity != 0
                                           ORDER BY room_rate ASC";
                            $rooms_result = mysqli_query($conn, $rooms_query);

                            if ($rooms_result && $rooms_result->num_rows > 0) {
                                while ($room = mysqli_fetch_assoc($rooms_result)) {
                                    // getting amenities for this room
                                    $amenities_query = "SELECT a.amenity_name 
                                                      FROM amenities a
                                                      JOIN bh_amenities ba ON ba.amenity_id = a.id 
                                                      WHERE ba.bh_id = {$room['bh_id']}";
                                    $amenities_result = mysqli_query($conn, $amenities_query);
                                    $amenities = [];
                                    while ($amenity = mysqli_fetch_assoc($amenities_result)) {
                                        $amenities[] = $amenity['amenity_name'];
                                    }
                            
   //get
    $utilStmt = $conn->prepare("
        SELECT 
            bu.bh_utility_id,
            bu.is_included,
            u.utility_name,
            COALESCE(up.cost, 0)   AS cost,
            up.unit
        FROM bh_utilities bu
        JOIN utilities u          ON bu.utility_id = u.utility_id
        LEFT JOIN utility_pricing up ON up.bh_utility_id = bu.bh_utility_id
        WHERE bu.bh_id = ?
        ORDER BY u.is_default DESC, u.utility_name
    ");
    $utilStmt->bind_param("i", $bh_id);
    $utilStmt->execute();
    $utilRes = $utilStmt->get_result();

    $utilitiesForBH = $utilRes->fetch_all(MYSQLI_ASSOC);
    $utilStmt->close();
    ?>

    <?php
    //excluded utilities
    $excluded = [];   // bh_utility_id => true
    $exStmt = $conn->prepare("SELECT bh_utility_id FROM room_excluded_utilities WHERE room_id = ?");
    $exStmt->bind_param("i", $room['room_id']);
    $exStmt->execute();
    $exRes = $exStmt->get_result();
    while ($row = $exRes->fetch_assoc()) {
        $excluded[$row['bh_utility_id']] = true;
    }
    $exStmt->close();
    
    $display = [];

    foreach ($utilitiesForBH as $u) {
        // skip if this room is excluded from the utility
        if (isset($excluded[$u['bh_utility_id']])) {
            continue;
        }

        $name = htmlspecialchars($u['utility_name']);

        if ($u['is_included'] == 1) {
            $display[] = "$name – Included";
        } else {
            $cost = $u['cost'] > 0 ? '₱' . number_format($u['cost']): 'Included';
            $unit = !empty($u['unit']) ? " per {$u['unit']}" : '';
            $display[] = "$name – {$cost}{$unit}";
        }
    }

    $excludedAmens = [];
                                $exAmenStmt = $conn->prepare("SELECT bh_amenity_id FROM room_excluded_amenities WHERE room_id = ?");
                                $exAmenStmt->bind_param("i", $room['room_id']);
                                $exAmenStmt->execute();
                                $exAmenRes = $exAmenStmt->get_result();
                                while ($row = $exAmenRes->fetch_assoc()) {
                                    $excludedAmens[$row['bh_amenity_id']] = true;
                                }
                                $exAmenStmt->close();

                            
                                $amenityDisplay = [];
                                foreach ($amenitiesForBH as $a) {
                                    if (isset($excludedAmens[$a['bh_amenity_id']])) {
                                        continue;
                                    }
                                    $amenityDisplay[] = htmlspecialchars($a['amenity_name']);
                                }
    ?>
                        
                            <!-- Room Item -->
                            <div class="property-item">
                                <img src="images/roomPictures/<?= $room['room_picture'] ?>" alt="Room Image" class="img-fluid room-image">
                                <div class="property-content">
                                    <div class="mb-2">
                                        <span class="price-highlight">
                                            ₱<?php echo number_format($room['room_rate']) . "/" . ucfirst(htmlspecialchars($paymentStatus)); ?>
                                        </span>
                                    </div>
                                    <div class="mb-2">
                                        <span><i class="fas fa-money-bill-wave"></i> Down Payment: ₱<?php echo number_format($room['downpayment']); ?></span>
                                    </div>

                                    <span class="city d-block mb-3">
                                        <i class="fa-solid fa-bed"></i> <?php echo $room['room_type'] ?>
                                    </span>

                                    <div class="specs d-flex mb-4">
                                        <span class="spec-item me-3">
                                            <i class="fas fa-users"></i>
                                            <?php echo ucfirst($room['gender_policy'])?>
                                        </span>
                                        <span class="spec-item me-3">
                                            <i class="fas fa-calendar"></i>
                                            <?= ucfirst(htmlspecialchars($paymentStatus)); ?>
                                        </span>

                                        <span class="spec-item d-flex align-items-center flex-wrap">
                                            <i class="fas fa-plug me-2"></i>
                                            <?php
                                            if (empty($display)) {
                                                echo '<em class="text-muted">No utilities defined</em>';
                                            } else {
                                          
                                                echo implode(' <span class="text-muted mx-1">|</span> ', array_map(function ($item) {
                                                    return '<span class="utility-badge">' . $item . '</span>';
                                                }, $display));
                                            }
                                            ?>
                                        </span>
                                        
                                    </div>

                                    <?php if (!empty($amenityDisplay)): ?>
                                            <div class="mb-3">
                                                <small class="text-muted d-flex align-items-center flex-wrap gap-1">
                                                    <i class="fas fa-check-circle text-success me-1"></i>
                                                    <?php
                                                    echo implode('', array_map(fn($item) => '<span class="amenity-badge">' . $item . '</span>', $amenityDisplay));
                                                    ?>
                                                </small>
                                            </div>
                                        <?php else: ?>
                                            <div class="mb-3">
                                                <small class="text-muted">
                                                    <i class="fas fa-info-circle text-warning"></i> No amenities listed
                                                </small>
                                            </div>
                                        <?php endif; ?>


                                    <?php if (empty($tenant_id)) { ?>
                                        <button class="btn btn-primary py-2 px-3" data-bs-toggle="modal" data-bs-target="#accountRequiredModal">
                                            <i class="fas fa-calendar-check"></i> Reserve Room
                                        </button>
                                    <?php } else { ?>
                                        <button class="btn btn-primary py-2 px-3"
                                                data-bh-id="<?php echo $bh_id; ?>"
                                                data-tenant-id="<?php echo $tenant_id; ?>"
                                                data-room-id="<?php echo $room['room_id']; ?>"
                                                data-bs-toggle="modal" data-bs-target="#reservationModal">
                                            <i class="fas fa-calendar-check"></i> Reserve Room
                                        </button>
                                    <?php } ?>
                                </div>
                            </div> <!-- .property-item -->
                            
                            
                            <?php
                                }
                            } else {
                                echo '<div class="property-item">';
                                echo '<div class="property-content text-center py-5">';
                                echo '<i class="fas fa-bed" style="font-size: 3rem; color: #ccc; margin-bottom: 20px;"></i>';
                                echo '<h4 style="color: #666;">No rooms available</h4>';
                                echo '<p style="color: #999;">This boarding house currently has no rooms listed.</p>';
                                echo '<a href="index.php" class="btn btn-primary">Back to Home</a>';
                                echo '</div>';
                                echo '</div>';
                            }
                            ?>
                        </div> <!-- .property-slider -->

                        <!-- Navigation Controls -->
                        <div id="property-nav" class="controls" tabindex="0" aria-label="Room Carousel Navigation">
                            <span class="prev" onclick="moveSlider(-1)">❮ Prev</span>
                            <span class="next" onclick="moveSlider(1)">Next ❯</span>
                        </div>
                    </div> <!-- .property-slider-wrap -->
                </div>
            </div>
        </div>
    </div>


    <!-- Room Reservation Modal -->
    <div class="modal fade" id="accountRequiredModal" tabindex="-1" aria-labelledby="reservationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="accountRequiredModalLabel">Room Reservation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-4">
                        <i class="bi bi-person-plus-fill text-primary" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="mb-3">Account Required</h5>
                    <p class="text-muted mb-4">
                        You need to create an account to make a room reservation. 
                        Join CyBoard today to find your perfect boarding house!
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Room Reservation Modal -->
    <div class="modal fade" id="reservationModal" tabindex="-1" aria-labelledby="reservationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reservationModalLabel">Room Reservation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="reservationForm" action="process.php" method="POST">
                <div class="modal-body">
                        <input type="hidden" id="getTenantId" name="tenantId" readonly>
                        <input type="hidden" id="getRoomId" name="roomId" readonly>
                        <input type="hidden" id="getBhId" name="bhId" readonly>
                        <div class="mb-3">
                            <label for="moveInDate" class="form-label">Move-in Date</label>
                            <input type="date" class="form-control" id="moveInDate" name="moveIn" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="duration" class="form-label">Duration</label>
                            <select class="form-select" id="duration" name="duration" required>
                                <option value="">Select duration</option>
                                <option value="1-semester(3-4 months)">1 Semester (3-4 months)</option>
                                <option value="2-semesters(Academic Year)">2 Semesters (Academic Year)</option>
                                <option value="summer-term(2-3 months)">Summer Term (2-3 months)</option>
                                <option value="6-months">6 Months</option>
                                <option value="1-year">1 Year</option>
                                <option value="custom">Custom Duration</option>
                            </select>
                        </div>
                        
                        <div class="mb-3" id="customDurationDiv" style="display: none;">
                            <label for="customDuration" class="form-label">Custom Duration</label>
                            <input type="text" class="form-control" id="customDuration" name="customDuration" min="1" placeholder="Enter duration (e.g., 2 years, 18 months, 365 days,)">
                        </div>
                        
                        <div class="mb-3">
                            <label for="additionalNotes" class="form-label">Additional Notes(Optional)</label>
                            <textarea class="form-control" id="additionalNotes" name="additionalNotes" rows="3" placeholder="Any notes or special needs..."></textarea>
                        </div>

                        <div class="alert alert-warning text-center" role="alert">
                            <strong>Reminder:</strong> Please ensure to visit the boarding house in person before proceeding with any payment. Verify details directly with the landlord to avoid possible scams.
                        </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" name="reserve" >Submit Reservation</button>
                </div>
                </form>
            </div>
        </div>
    </div>

    <?php
    } 
    ?>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.min.js" integrity="sha384-RuyvpeZCxMJCqVUGFI0Do1mQrods/hhxYlcVfGPOfQtPJh0JCw12tUAZ/Mv10S7D" crossorigin="anonymous"></script>
    <script>
        function toggleMenu() {
            const nav = document.querySelector('nav ul');
            nav.classList.toggle('active');
        }
                // Slider functionality
        let currentSlide = 0;
        const slider = document.getElementById('roomSlider');
        const slides = slider.children;
        const totalSlides = slides.length;
        const slidesToShow = window.innerWidth > 768 ? 3 : 1;

        function moveSlider(direction) {
            currentSlide += direction;
            
            if (currentSlide < 0) {
                currentSlide = Math.max(0, totalSlides - slidesToShow);
            } else if (currentSlide > totalSlides - slidesToShow) {
                currentSlide = 0;
            }
            
            const slideWidth = slides[0].offsetWidth + 20; // Include gap
            slider.style.transform = `translateX(-${currentSlide * slideWidth}px)`;
        }

        // Auto-hide navigation if not enough slides
        if (totalSlides <= slidesToShow) {
            document.querySelector('.controls').style.display = 'none';
        }

        // Responsive slider adjustment
        window.addEventListener('resize', function() {
            currentSlide = 0;
            slider.style.transform = 'translateX(0)';
        });

        // Show/hide custom duration field based on selection
        document.getElementById('duration').addEventListener('change', function() {
            const customDiv = document.getElementById('customDurationDiv');
            if (this.value === 'custom') {
                customDiv.style.display = 'block';
                document.getElementById('customDuration').required = true;
            } else {
                customDiv.style.display = 'none';
                document.getElementById('customDuration').required = false;
            }
        });

        // Set minimum date to today
        document.getElementById('moveInDate').min = new Date().toISOString().split('T')[0];

        // Handle modal show event to capture data attributes
        document.getElementById('reservationModal').addEventListener('show.bs.modal', function (event) {
            // Get the button that triggered the modal
            const button = event.relatedTarget;
            
            // Extract data attributes
            const bhId = button.getAttribute('data-bh-id');
            const roomId = button.getAttribute('data-room-id');
            const tenantId = button.getAttribute('data-tenant-id');
            
            // Display the values in the modal
            document.getElementById('getBhId').value = bhId;
            document.getElementById('getRoomId').value = roomId;
            document.getElementById('getTenantId').value = tenantId;
            
            // Store for later use in form submission
            this.setAttribute('data-bh-id', bhId);
            this.setAttribute('data-room-id', roomId);
            this.setAttribute('data-tenant-id', tenantId);
        });
    </script>
    <script>
    window.addEventListener("pageshow", function(event) {
        if (event.persisted) {
            // force reload if page was loaded from cache
            window.location.reload();
        }
    });
    </script>

</body>
</html>