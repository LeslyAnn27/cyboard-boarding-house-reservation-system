
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CyBoard - Superadmin Dashboard</title>
        <!-- Leaflet CDN -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="css/superadmin.css">
    <!-- passing value to modal -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>
<body>
    <?php
    session_start();
    require "conn.php";
        if(!isset($_SESSION['superadmin_id'])){
            header("Location: login.php"); // Redirect to login page if not logged in
            exit();
        }
        if (isset($_SESSION['alert'])) {
            $alert_message = $_SESSION['alert'];
            $alert_type = $_SESSION['alert_type'];
            
            echo "<script>alert('" . addslashes($alert_message) . "');</script>";
            
            // Clear the session variables
            unset($_SESSION['alert']);
            unset($_SESSION['alert_type']);
        }
        $_SESSION['role'] = 'superadmin';
    ?>
    <header>
        <div class="header-container d-flex justify-content-between align-items-center">
            <a href="superadmin.php" class="logo">CyBoard</a>
            
        
            <a href="index.php" class="btn btn-primary" style="text-decoration: none;">
                <i class="fa-solid fa-globe me-2"></i> Visit Site
            </a>
        </div>
    </header>


    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <div class="container-fluid">
        <div class="sidebar">
            <h4>Superadmin Dashboard</h4>
            <ul>
                <li><a href="#dashboard" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="#boarding-houses" class="nav-link"><i class="fas fa-building"></i> Boarding Houses</a></li>
                <li><a href="#landlords" class="nav-link"><i class="fas fa-users"></i> Landlords</a></li>
                <li>
                    <a href="#landlord-communication" class="nav-link" id="landlordInboxLink">
            <i class="fas fa-envelope"></i> Landlord Inbox
            <span class="badge bg-danger ms-2" id="unreadBadge" style="display:none;">0</span>
        </a>
                </li>


                <li><a href="#vacancy-report" class="nav-link"><i class="fas fa-chart-bar"></i> Vacancy Report</a></li>
                <li><a href="#active-leases" class="nav-link"><i class="fas fa-file-contract"></i> Active Leases</a></li>
                <li><a href="#pending-applications" class="nav-link"><i class="fas fa-hourglass-half"></i> Pending Applications</a></li>
                <li><a href="#settings" class="nav-link"><i class="fas fa-cog"></i> Settings</a></li> 
                <li><a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
        <!-- DASHBOARD -->
        <?php
            // Total boarding houses
            $stmt_bh = $conn->prepare("SELECT COUNT(*) AS total_bh FROM boarding_houses");
            $stmt_bh->execute();
            $result_bh = $stmt_bh->get_result();
            $row_bh = $result_bh->fetch_assoc();
            $total_bh = $row_bh['total_bh'];
            $stmt_bh->close();
        
            // Total landlords
            $stmt_landlords = $conn->prepare("SELECT COUNT(*) AS total_landlords FROM landlords WHERE password_set = 1");
            $stmt_landlords->execute();
            $result_landlords = $stmt_landlords->get_result();
            $row_landlords = $result_landlords->fetch_assoc();
            $total_landlords = $row_landlords['total_landlords'];
            $stmt_landlords->close();
        
            // Total students
            $stmt_students = $conn->prepare("SELECT COUNT(*) AS total_students FROM reservations WHERE status='active'");
            $stmt_students->execute();
            $result_students = $stmt_students->get_result();
            $row_students = $result_students->fetch_assoc();
            $total_students = $row_students['total_students'];
            $stmt_students->close();
        ?>
        <section id="dashboard" class="section active">
            <h2 class="mb-4">Dashboard</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">Total Boarding Houses</div>
                        <div class="card-body">
                            <h5 id="totalBh"><?= htmlspecialchars($total_bh) ?></h5>
                            <p>Across all locations</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">Total Landlords</div>
                        <div class="card-body">
                            <h5 id="totalLandlords"><?= htmlspecialchars($total_landlords) ?></h5>
                            <p>Managing properties</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">Total Students</div>
                        <div class="card-body">
                            <h5 id="totalStudents"><?= htmlspecialchars($total_students) ?></h5>
                            <p>Across all boarding houses</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
            
            <!-- BOARDING HOUSES -->
<section id="boarding-houses" class="section">

<?php
    if(isset($_SESSION['delete_error'])){
        echo "<script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var toastElement = document.getElementById('deleteFailedToast');
                                var toast = new bootstrap.Toast(toastElement, {delay: 4000});
                                toast.show();
                            });
                        </script>";
                        unset($_SESSION['delete_error']);
    }
?>


    <h2 class="mb-4">Boarding Houses</h2>
    <div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="deleteFailedToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-warning text-dark">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong class="me-auto">Cannot Delete</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            <strong>Action Denied!</strong><br>
             A landlord is currently assigned to this boarding house.
        </div>
    </div>
</div>
    <div class="card">
        <div class="card-header">Add New Boarding House</div>
        <div class="card-body">
            <form id="addBoardingHouseForm" method="POST" action="superadminprocess.php">
                <div class="mb-3">
                    <label for="bhName" class="form-label">Boarding House Name</label>
                    <input type="text" class="form-control" name="bhName" id="bhName" placeholder="e.g., BH1" required>
                </div>
                <div class="mb-3">
                    <label for="bhAddress" class="form-label">Boarding House Address</label>
                    <input type="text" class="form-control" name="bhAddress" id="bhAddress" placeholder="e.g., 123 Main Street, AnyTown" required>
                </div>

                <p class="text-muted mb-2">
                     📍 Set the boarding house location by marking it on the map (<strong>Load Map</strong>) or by entering the coordinates manually (<strong>Enter Coordinates</strong>).
                </p>
                <button type="button" class="btn btn-outline-primary mb-3" onclick="loadMap()">
                    🗺️ Load Map
                </button>
                <button type="button" class="btn btn-outline-success mb-3 ml-2" onclick="enterCoordinates()">
                    🌐 Enter Coordinates
                </button>
                <div id="map" style="height: 400px; border-radius: 10px; margin-bottom: 15px; display: none; z-index:0;"></div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="bhLatitude" class="form-label">Latitude</label>
                        <input type="text" class="form-control" name="bhLatitude" id="bhLatitude" readonly required />
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="bhLongitude" class="form-label">Longitude</label>
                        <input type="text" class="form-control" name="bhLongitude" id="bhLongitude" readonly required />
                    </div>
                </div>
                

                <button type="submit" name="addBh" class="btn btn-primary">Add Boarding House</button>
            </form>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-header">Boarding House List</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="boardingHouseTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $superadminId = 1;
                        $stmt = $conn->prepare("SELECT * FROM boarding_houses");
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            while ($rowData = $result->fetch_assoc()) {
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($rowData['bh_name']); ?></td>
                                <td><?php echo htmlspecialchars($rowData['bh_address']); ?></td>
                                <td>
                                    <div class="d-flex flex-column gap-2">
                                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-lat="<?php echo htmlspecialchars($rowData['latitude'])?>" data-id="<?php echo htmlspecialchars($rowData['bh_id']); ?>" data-long="<?php echo htmlspecialchars($rowData['longitude'])?>" data-name="<?php echo htmlspecialchars($rowData['bh_name']); ?>" data-address="<?php echo htmlspecialchars($rowData['bh_address']); ?>" data-bs-target="#editBoardingHouseModal">Edit</button>
                                        <button class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-id="<?php echo htmlspecialchars($rowData['bh_id']); ?>" data-bs-target="#deleteBoardingHouseModal">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        <?php
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center'>No Boarding House added yet.</td></tr>";
                        }
                        $stmt->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
<script>
let boardingMap = null;
let mapVisible = false;

function loadMap() {
    const mapDiv = document.getElementById('map');
    
    if (!mapVisible) {
        // SHOW MAP
        mapDiv.style.display = 'block';
        mapVisible = true;
        
        if (!boardingMap) {
            boardingMap = L.map("map").setView([10.720321, 122.562019], 12);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(boardingMap);
            
            // CLICK TO SET LOCATION
            boardingMap.on('click', function(e) {
                const lat = e.latlng.lat.toFixed(6);
                const lng = e.latlng.lng.toFixed(6);
                
                document.getElementById('bhLatitude').value = lat;
                document.getElementById('bhLongitude').value = lng;
                
                // Add marker
                boardingMap.eachLayer(function(layer) {
                    if (layer instanceof L.Marker) boardingMap.removeLayer(layer);
                });
                L.marker([lat, lng]).addTo(boardingMap)
                    .bindPopup(`✅ Selected: ${lat}, ${lng}`).openPopup();
            });
        }
        
        boardingMap.invalidateSize();
        document.querySelector('.btn-outline-primary').innerHTML = '📍 Hide Map';
        
    } else {
        // HIDE MAP
        mapDiv.style.display = 'none';
        mapVisible = false;
        
        document.getElementById('bhLatitude').value = '';
        document.getElementById('bhLongitude').value = '';
        
        document.querySelector('.btn-outline-primary').innerHTML = '🗺️ Load Map';
    }
}

// GOOGLE MAPS LINK - SMART AUTO-DETECT
function enterCoordinates() {
    // Create popup with instructions
    const coordsInput = prompt(`📍 Enter Coordinates`);

    if (coordsInput) {
        let lat, lng;
        
        if (coordsInput.includes(',')) {
            [lat, lng] = coordsInput.split(',').map(x => parseFloat(x.trim()));
        }
        else if (coordsInput.includes(' ')) {
            [lat, lng] = coordsInput.split(' ').map(x => parseFloat(x.trim()));
        }
        else if (!isNaN(coordsInput)) {
            alert('Enter: 10.6964, 122.5643');
            return;
        }
        else {
            // AUTO-SEARCH location name
            searchLocationName(coordsInput);
            return;
        }
        
        // VALIDATE
        if (!isNaN(lat) && !isNaN(lng)) {
            // AUTO-FILL FIELDS
            document.getElementById('bhLatitude').value = lat.toFixed(6);
            document.getElementById('bhLongitude').value = lng.toFixed(6);
            
            // SHOW MAP + MARKER
            loadMap();
            if (boardingMap) {
                boardingMap.setView([lat, lng], 16);
                boardingMap.eachLayer(function(layer) {
                    if (layer instanceof L.Marker) boardingMap.removeLayer(layer);
                });
                L.marker([lat, lng]).addTo(boardingMap)
                    .bindPopup(`${lat.toFixed(6)}, ${lng.toFixed(6)}`).openPopup();
            }
            
            alert(`LOADED!\n📍 Lat: ${lat.toFixed(6)}\n🌐 Lng: ${lng.toFixed(6)}`);
        } else {
            alert('Invalid! Try: 10.6964, 122.5643');
        }
    }
}
</script>
<!-- LANDLORD -->
<section id="landlords" class="section">
    <h2 class="mb-4">Landlords</h2>

    <!-- Add New Landlord -->
    <div class="card">
        <div class="card-header">Add New Landlord</div>
        <div class="card-body">
            <form method="POST" action="superadminprocess.php">
                <div class="mb-3">
                    <label for="landlordName" class="form-label">Full Name</label>
                    <input type="text" class="form-control" name="landlordName" placeholder="e.g., John Doe" required>
                </div>
                <div class="mb-3">
                    <label for="landlordEmail" class="form-label">Email</label>
                    <input type="email" class="form-control" name="landlordEmail" placeholder="e.g., john.doe@example.com" required>
                </div>
                <div class="mb-3">
                    <label for="landlordNumber" class="form-label">Phone Number</label>
                    <input type="text" class="form-control" name="landlordNumber" placeholder="e.g., 09xxxxxxxxx" pattern="09\d{9}" 
  maxlength="11" 
  required 
  title="Phone number must be exactly 11 digits">
                </div>
                <div class="mb-3">
                    <label for="landlordAssignedBh" class="form-label">Assign Boarding House</label>
                    <select class="form-control" name="boardingHouse" required>
                        <option value="" disabled selected>Select Boarding House</option>
                        <?php
                            // Show only BH without landlord
                            $stmt = $conn->prepare("
                                SELECT bh_id, bh_name
                                FROM boarding_houses
                                WHERE bh_id NOT IN (SELECT bh_id FROM landlords WHERE bh_id IS NOT NULL)");
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($result->num_rows > 0) {
                                while ($rowData = $result->fetch_assoc()) {
                                    echo '<option value="'.htmlspecialchars($rowData['bh_id']).'">'.htmlspecialchars($rowData['bh_name']).'</option>';
                                }
                            } else {
                                echo '<option disabled>No available boarding houses</option>';
                            }
                            $stmt->close();
                        ?>
                    </select>
                </div>
                <button type="submit" name="addLandlordForm" class="btn btn-primary">Add Landlord</button>
            </form>
        </div>
    </div>



<!-- Landlord List -->
<div class="card mb-4 mt-4">
    <div class="card-header">Landlord List</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="landlordTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Boarding House</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="landlordTableBody">
                    <tr><td colspan="6" class="text-center">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
</section>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        fetchLandlords();
        setInterval(fetchLandlords, 10000);
    });

    function fetchLandlords() {
        fetch('fetch_tokens.php')
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById("landlordTableBody");
                tbody.innerHTML = ""; // Clear previous rows

                if (!data || data.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center">No landlords added yet.</td>
                        </tr>`;
                    return;
                }

                data.forEach(row => {
                    // Determine landlord verification status
                    let statusText = '';
                    if (row.password_set == 1) {
                        statusText = `<span class='badge bg-success'>Verified</span>`;
                    } 
                    else if (row.token_status === 'pending') {
                        statusText = `<span class='badge bg-info text-dark'>Active</span>`; // link sent but not yet used
                    } 
                    else if (row.token_status === 'expired') {
                        statusText = `<span class='badge bg-danger'>Expired</span>`; // link expired
                    } 
                    else {
                        statusText = `<span class='badge bg-warning text-dark'>Pending</span>`; // link not yet sent
                    }


                    let actionButton = '';
                    if (row.password_set == 0 && (!row.token_status || row.token_status === 'expired')) {
                        // Not verified + no active token or expired = allow send link
                        actionButton = `
                            <form method="POST" action="superadminprocess.php" class="d-grid">
                                <input type="hidden" name="landlord_id" value="${row.landlord_id}">
                                <button type="submit" name="send_link" class="btn btn-secondary btn-sm w-100">
                                    Send Link
                                </button>
                            </form>`;
                    } else if (row.token_status === 'pending') {
                        // Link already sent but not verified yet
                        actionButton = `
                            <button class="btn btn-success btn-sm w-100" disabled>
                                Link already sent
                            </button>`;
                    } else if (row.password_set == 1) {
                        // Password already set
                        actionButton = `
                            <button class="btn btn-success btn-sm w-100" disabled>
                                Already Set
                            </button>`;
                    }
                    

                    //Create table row
                    const tr = document.createElement("tr");
                    tr.innerHTML = `
                        <td>${row.landlord_name}</td>
                        <td>${row.landlord_email}</td>
                        <td>${row.landlord_number}</td>
                        <td>${row.bh_name ?? '—'}</td>
                        <td>
                            ${statusText}
                        </td>
                        <td>
                            <div class="d-flex flex-column gap-2">

                                <!-- Edit Landlord -->
                                <button 
                                    data-bs-toggle="modal"
                                    data-bs-target="#editLandlord"
                                    data-id="${row.landlord_id}"
                                    data-name="${row.landlord_name}"
                                    data-email="${row.landlord_email}"
                                    data-number="${row.landlord_number}"
                                    data-bh="${row.bh_id ?? ''}"
                                    class="btn btn-primary btn-sm w-100">
                                    Edit Landlord
                                </button>

                                <!-- Send / Active / Expired / Verified -->
                                ${actionButton}

                                <!-- Remove Landlord -->
                                <form action="superadminprocess.php" method="POST" 
                                    onsubmit="return confirm('Are you sure you want to remove this landlord?');" 
                                    class="d-grid">
                                    <input type="hidden" name="landlord_id" value="${row.landlord_id}">
                                    <button type="submit" name="deleteLandlord" class="btn btn-danger btn-sm w-100"> 
                                        Remove Landlord 
                                    </button> 
                                </form>
                            </div>
                        </td>
                    `;

                    tbody.appendChild(tr);
                });
            })
            .catch(err => {
                console.error(err);
                document.getElementById("landlordTableBody").innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center text-danger">
                            Error loading data
                        </td>
                    </tr>`;
            });
    }
</script>




    <!-- Landlord Inbox Section -->
    <section id="landlord-communication" class="section">
        <h2 class="mb-4">Landlord Inbox</h2>
        <div class="card">
            <div class="card-header">Messages</div>
            <div class="card-body">
                <div class="row">
                    <!-- Landlord List -->
                    <div class="col-md-4 border-end" style="max-height: 500px; overflow-y: auto;">
                        <h5>Select Landlord</h5>
                        <ul class="list-group" id="landlordList">
                            <?php
                            $stmt = $conn->prepare("SELECT landlord_id, landlord_name, landlord_email FROM landlords WHERE password_set = 1");
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($result->num_rows > 0) {
                                while ($rowData = $result->fetch_assoc()) {
                            ?>
                                <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                    onclick="loadChat('<?php echo htmlspecialchars($rowData['landlord_id']); ?>', '<?php echo htmlspecialchars($rowData['landlord_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($rowData['landlord_email'], ENT_QUOTES); ?>')"
                                    style="cursor: pointer;">
                                    <span><?php echo htmlspecialchars($rowData['landlord_name']); ?> (<?php echo htmlspecialchars($rowData['landlord_email']); ?>)</span>
                                    <span class="badge bg-danger rounded-pill" id="badge-<?php echo htmlspecialchars($rowData['landlord_id']); ?>" style="display:none;">0</span>
                                </li>
                            <?php
                                }
                            } else {
                                echo '<li class="list-group-item">No landlords available.</li>';
                            }
                            $stmt->close();
                            ?>
                        </ul>
                    </div>

                    <!-- Chat Window -->
                    <div class="col-md-8">
                        <div class="mt-3">
                            <h6>Chatting with: <span id="currentLandlord">None</span></h6>
                        </div>
                        <div id="chatWindow" class="mb-3"
                             style="height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background-color: #f8f9fa;">
                            <p class="text-center text-muted">Select a landlord to start chatting</p>
                        </div>
                        <form id="chatForm" onsubmit="sendMessage(event)">
                            <div class="input-group">
                                <input type="hidden" id="chatLandlordId">
                                <input type="text" class="form-control" id="chatMessage" placeholder="Type your message..." disabled>
                                <button type="submit" class="btn btn-primary" disabled>Send</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
</section>



    <script>
let selectedLandlordId = null;

// Load chat with selected landlord
function loadChat(landlordId, name, email) {
    selectedLandlordId = landlordId;
    $('#currentLandlord').text(`${name} (${email})`);
    $('#chatMessage').prop('disabled', false);
    $('#chatForm button').prop('disabled', false);

    fetchMessages();

    // Mark landlord messages as read
    $.post('mark_as_read.php', {
        landlord_id: landlordId,
        superadmin_id: <?php echo $superadminId; ?>
    }, function(){
        $(`#badge-${landlordId}`).hide();
        updateTopBadge(); // ✅ UPDATE TOP BADGE TOO!
    });
}

// Fetch messages
function fetchMessages() {
    if (!selectedLandlordId) return;
    $.getJSON('fetch_messages.php', {
        landlord_id: selectedLandlordId,
        superadmin_id: <?php echo $superadminId; ?>
    }, function(data) {
        const chatWindow = $('#chatWindow');
        chatWindow.html('');
        data.forEach(msg => {
            const align = msg.sender_type === 'superadmin' ? 'end' : 'start';
            const bubble = msg.sender_type === 'superadmin' ? 'bg-primary text-white' : 'bg-light';
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
function sendMessage(e) {
    e.preventDefault();
    const message = $('#chatMessage').val().trim();
    if (!message || !selectedLandlordId) return;

    $.post('send_message.php', {
        landlord_id: selectedLandlordId,
        superadmin_id: <?php echo $superadminId; ?>,
        sender_type: 'superadmin',
        message: message
    }, function(res) {
        const result = JSON.parse(res);
        if (result.status === 'success') {
            $('#chatMessage').val('');
            fetchMessages();
        }
    });
}

// *** UPDATE TOP BADGE (TOTAL UNREAD) ***
function updateTopBadge() {
    $.getJSON('count_total_unread.php', function(count) {
        const $badge = $('#unreadBadge');
        if (count > 0) {
            $badge.text(count).show();
        } else {
            $badge.hide();
        }
    });
}

// Refresh per-landlord badges
function refreshBadges() {
    $.getJSON('count_unread_per_landlord.php', function(data) {
        for (const lid in data) {
            const count = data[lid];
            const badge = $(`#badge-${lid}`);
            if (count > 0) {
                badge.text(count).show();
                console.log('🟢 BADGE-' + lid + ':', count);
            } else {
                badge.hide();
            }
        }
        updateTopBadge();
    });
}

// Initial load + auto refresh
$(document).ready(function() {
    updateTopBadge(); 
    refreshBadges(); 
    setInterval(refreshBadges, 5000); // badges
    setInterval(() => { if (selectedLandlordId) fetchMessages(); }, 3000); // chat
});
</script>


            <!-- Vacancy Report Section -->
            <section id="vacancy-report" class="section">
                <h2 class="mb-4">Vacancy Report</h2>
                <div class="card">
                    <div class="card-header">Boarding House Vacancy and Tenant Overview</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Boarding House</th>
                                        <th>Address</th>
                                        <th>Landlord</th>
                                        <th>Vacant Rooms</th>
                                        <th>Max Tenants</th>
                                        <th>Active Tenants</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
        
                                
                                    // Fetch boarding houses with landlord names
                                    $stmt = $conn->prepare("
                                        SELECT 
                                            bh.bh_id, 
                                            bh.bh_name, 
                                            bh.bh_address, 
                                            l.landlord_name,
                                            l.password_set
                                        FROM boarding_houses AS bh
                                        LEFT JOIN landlords AS l ON bh.bh_id = l.bh_id WHERE l.password_set = 1
                                    ");
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                
                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            $bh_id = intval($row['bh_id']);
                                            $bh_name = htmlspecialchars($row['bh_name']);
                                            $bh_address = htmlspecialchars($row['bh_address']);
                                            $check_landlord = intval($row['password_set']);
                                            $landlord_name = htmlspecialchars($row['landlord_name']);
                                            
                                            // Get total rooms in this boarding house
                                            $stmt_total = $conn->prepare("SELECT COUNT(*) AS total_rooms FROM rooms WHERE bh_id = ?");
                                            $stmt_total->bind_param("i", $bh_id);
                                            $stmt_total->execute();
                                            $total_rooms_result = $stmt_total->get_result()->fetch_assoc();
                                            $total_rooms = !empty($total_rooms_result['total_rooms'])
                                            ? intval($total_rooms_result['total_rooms'])
                                            : '0';
                                            $stmt_total->close();
                                
                                            // Count rooms that are vacant
                                            $stmt_vacant = $conn->prepare("
                                                SELECT COUNT(r.room_id) AS vacant_rooms
                                                FROM rooms r
                                                WHERE r.bh_id = ? 
                                                AND r.room_capacity <> 0
                                            ");
                                
                                            $stmt_vacant->bind_param("i", $bh_id);
                                            $stmt_vacant->execute();
                                            $vacant_result = $stmt_vacant->get_result()->fetch_assoc();
                                            $vacant_rooms = $vacant_result['vacant_rooms'];
                                            $stmt_vacant->close();
                                            
                                            if($vacant_rooms == 0 && $total_rooms == 0){
                                                $total_vacant = 0;
                                            }else{
                                                $total_vacant = $vacant_rooms . "/" . $total_rooms;
                                            }
                                
                                            // Count active tenants
                                            $stmt_tenants = $conn->prepare("
                                                SELECT COUNT(*) AS active_tenants
                                                FROM reservations res
                                                INNER JOIN rooms r ON res.room_id = r.room_id
                                                WHERE r.bh_id = ? AND res.status = 'Active'
                                            ");
                                            $stmt_tenants->bind_param("i", $bh_id);
                                            $stmt_tenants->execute();
                                            $tenant_result = $stmt_tenants->get_result()->fetch_assoc();
                                            $active_tenants = (isset($tenant_result['active_tenants']) && intval($tenant_result['active_tenants']) > 0)
                                            ? intval($tenant_result['active_tenants'])
                                            : '0';
                                            
                                            $stmt_tenants->close();
                                
                                            // Determine status
                                            if ($vacant_rooms > 0) {
                                                // Has vacant rooms
                                                $status = "Vacant";
                                                $status_class = "success";
                                            } elseif($vacant_rooms == 0 && $active_tenants > 0) {
                                                $status = "Full";
                                                $status_class = "danger";
                                                // All rooms occupied
                                            }elseif($total_vacant == 0){
                                                $status = "No Rooms";
                                                $status_class = "warning";
                                            }
                                
                                            $stmt_max = $conn->prepare("
                                                SELECT 
                                                    SUM(room_capacity) AS max_tenant
                                                FROM rooms
                                                WHERE bh_id = ?
                                            ");
                                            $stmt_max->bind_param("i", $bh_id);
                                            $stmt_max->execute();
                                            $result_max = $stmt_max->get_result();
                                
                                            if ($row_max = $result_max->fetch_assoc()) {
                                                $maxTenant = $row_max['max_tenant'] ?? 0;
                                
                                                if ($active_tenants > 0 ) {
                                                    // Add only if there are active tenants and landlord is verified
                                                    $max_tenant = $maxTenant + $active_tenants;
                                                }elseif($total_vacant == 0 && $maxTenant == 0 ) {
                                                    $max_tenant = 0;
                                                }else{
                                                    $max_tenant = $maxTenant;
                                                }
                                            }
                                
                                            $stmt_max->close();
                                
                                    ?>
                                    <tr>
                                        <td><?= $bh_name ?></td>
                                        <td><?= $bh_address ?></td>
                                        <td><?= $landlord_name ?></td>
                                        <td><?= $total_vacant ?></td>
                                        <td><?= $max_tenant ?></td>
                                        <td><?= $active_tenants ?></td>
                                        <td><span class="badge bg-<?= $status_class ?>"><?= $status ?></span></td>
                                    </tr>
                                    <?php
                                        }
                                    } else {
                                        echo "<tr><td colspan='6' class='text-center'>No boarding houses available.</td></tr>";
                                    }
                                    $stmt->close();
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <!-- Export Button -->
                        <div class="text-end mt-3">
                            <form action="generate_vacancy_pdf.php" method="post" target="_blank">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-file-earmark-pdf"></i> Export Vacancy Report
                                </button>
                            </form>
                        </div>

                    </div>
                </div>
            </section>
            
            <!-- Active Leases Section -->
            <section id="active-leases" class="section">
                <h2 class="mb-4">Active Leases</h2>
                <div class="card">
                    <div class="card-header">Active Leases Overview</div>
                    <div class="card-body">
                        <!-- Search Bar -->
                        <div class="mb-3">
                            <input type="text" class="form-control" id="leaseSearch" placeholder="Search by Student ID, Name, or Program..." oninput="searchLeases()">
                        </div>
                        <!-- Leases Table -->
                        <div class="table-responsive">
                            <table class="table table-striped" id="leasesTable">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Program</th>
                                        <th>Year</th>
                                        <th>Boarding House</th>
                                        <th>Address</th>
                                        <th>Action</th> 
                                    </tr>
                                </thead>
                                <tbody id="leasesBody"></tbody>
                            </table>
                        </div>
                        <!-- Pagination Controls -->
                        <div class="d-flex justify-content-between mt-3">
                            <button class="btn btn-secondary" id="backBtn" onclick="changePage(-1)" disabled>Back</button>
                            <span id="pageInfo">Page 1</span>
                            <button class="btn btn-secondary" id="nextBtn" onclick="changePage(1)">Next</button>
                        </div>
                    
                        <!-- Export Button -->
                        <div class="text-end mt-3">
                            <form action="generate_active_pdf.php" method="post" target="_blank">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-file-earmark-pdf"></i> Export Active Leases
                                </button>
                            </form>
                        </div>

                    </div>
                </div>
            </section>


            <?php
                $stmt = $conn->prepare("
                    SELECT 
                        s.tenant_id,
                        s.stud_id,
                        s.name,
                        s.program,
                        s.year_level,
                        b.bh_name,
                        b.bh_address,
                        b.latitude,
                        b.longitude
                    FROM reservations r
                    INNER JOIN tenants s ON r.tenant_id = s.tenant_id
                    INNER JOIN rooms ro ON r.room_id = ro.room_id
                    INNER JOIN boarding_houses b ON ro.bh_id = b.bh_id
                    WHERE r.status = ?
                ");
                $status = 'active';
                $stmt->bind_param("s", $status);

                $stmt->execute();
                $result = $stmt->get_result();

                $activeLeases = [];
                while ($row = $result->fetch_assoc()) {
                    
                    $activeLeases[] = [
                    'student_id' => $row['stud_id'], // ← show actual ID, not encrypted
                    'student_name' => $row['name'],
                    'program' => $row['program'],
                    'year_level' => $row['year_level'],
                    'bh_name' => $row['bh_name'],
                    'bh_address' => $row['bh_address'],
                    'latitude' => $row['latitude'],
                    'longitude' => $row['longitude']
                ];

                }

                $stmt->close();
            ?>


                <script>
                    // Load PHP data into JavaScript
                    let allLeases = <?php echo json_encode($activeLeases); ?>;

                    let filteredLeases = [...allLeases];
                    let currentPage = 1;
                    const itemsPerPage = 20;

                    // Function to render table rows
                    function renderLeases() {
                        const tbody = document.getElementById('leasesBody');
                        tbody.innerHTML = '';
                        const start = (currentPage - 1) * itemsPerPage;
                        const end = start + itemsPerPage;
                        const paginatedLeases = filteredLeases.slice(start, end);

                        if (paginatedLeases.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="7" class="text-center">No active leases found.</td></tr>';
                        } else {
                            paginatedLeases.forEach(lease => {
                                tbody.innerHTML += `
                                    <tr>
                                        <td>${lease.student_id}</td>
                                        <td>${lease.student_name}</td>
                                        <td>${lease.program}</td>
                                        <td>${lease.year_level}</td>
                                        <td>${lease.bh_name}</td>
                                        <td>${lease.bh_address}</td>
                                        <td>
                                            <button class="btn btn-primary"
                                                onclick="viewBHOnMap('${lease.bh_name.replace(/'/g, "\\'")}', ${lease.latitude}, ${lease.longitude})">
                                                View
                                            </button>
                                        </td>
                                    </tr>
                                `;
                            });

                        }

                        document.getElementById('pageInfo').textContent = `Page ${currentPage}`;
                        document.getElementById('backBtn').disabled = currentPage === 1;
                        document.getElementById('nextBtn').disabled = end >= filteredLeases.length;
                    }

                    // Function to handle search
                    function searchLeases() {
                        const searchTerm = document.getElementById('leaseSearch').value.toLowerCase();
                        filteredLeases = allLeases.filter(lease =>
                            lease.student_id.toLowerCase().includes(searchTerm) ||
                            lease.student_name.toLowerCase().includes(searchTerm) ||
                            lease.program.toLowerCase().includes(searchTerm) ||
                            lease.bh_name.toLowerCase().includes(searchTerm)
                        );
                        currentPage = 1;
                        renderLeases();
                    }

                    // Pagination
                    function changePage(direction) {
                        currentPage += direction;
                        if (currentPage < 1) currentPage = 1;
                        renderLeases();
                    }

                    // Initial render
                    renderLeases();
                </script>

                <script>
                    function viewBHOnMap(name, lat, lng) {
                        const url = `index.php?name=${encodeURIComponent(name)}&lat=${lat}&lng=${lng}`;
                        window.location.href = url; // redirect to index.php
                    }
                </script>


<!-- Pending Applications Section -->
<section id="pending-applications" class="section mt-5">
    <h2 class="mb-4">Pending Applications</h2>
    <div class="card shadow-sm">
        <div class="card-header">Pending Reservations Overview</div>
        <div class="card-body">
            <!--  Search Bar -->
            <div class="mb-3">
                <input type="text" class="form-control" id="pendingSearch" placeholder="Search by Name or ID...">
            </div>

            <!--  Pending Reservations Table -->
            <div class="table-responsive">
                <table class="table table-striped" id="pendingTable">
                    <thead class="table-light">
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Boarding House</th>
                            <th>Room Type</th>
                            <th>Date Reserved</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="pendingBody"></tbody>
                </table>
            </div>

            <!-- Pagination Controls -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <button class="btn btn-secondary" id="pendingBackBtn" disabled>Back</button>
                <span id="pendingPageInfo">Page 1</span>
                <button class="btn btn-secondary" id="pendingNextBtn">Next</button>
            </div>

            <!-- Export Button -->
                        <div class="text-end mt-3">
                            <form action="generate_pending_pdf.php" method="post" target="_blank">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-file-earmark-pdf"></i> Export Pending Applications
                                </button>
                            </form>
                        </div>
        </div>
    </div>
</section>
<?php
    $stmt = $conn->prepare("
        SELECT 
            s.stud_id,
            s.name AS student_name,
            b.bh_name,
            ro.room_type,
            r.reserved_at,
            r.status
        FROM reservations r
        INNER JOIN tenants s ON r.tenant_id = s.tenant_id
        INNER JOIN rooms ro ON r.room_id = ro.room_id
        INNER JOIN boarding_houses b ON ro.bh_id = b.bh_id
        WHERE r.status IN (?, ?)
    ");

    $status1 = 'pending';
    $status2 = 'waiting';
    $stmt->bind_param("ss", $status1, $status2);
    $stmt->execute();
    $result = $stmt->get_result();

    $pendingReservations = [];
    while ($row = $result->fetch_assoc()) {
        $pendingReservations[] = [
            'student_id' => $row['stud_id'],
            'student_name' => $row['student_name'],
            'bh_name' => $row['bh_name'],
            'room_type' => $row['room_type'],
            'date_reserved' => date("Y-m-d H:i", strtotime($row['reserved_at'])),
            'status' => ucfirst($row['status'])
        ];
    }

    $stmt->close();
?>
<script>
    (function() {
        // Load PHP data into JS
        let pendingApp = <?php echo json_encode($pendingReservations); ?>;

        let filteredPending = [...pendingApp];
        let currentPendingPage = 1;
        const itemsPerPage = 20;

        // Function to render table rows
        function renderPending() {
            const tbody = document.getElementById('pendingBody');
            tbody.innerHTML = '';

            const start = (currentPendingPage - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            const paginatedPending = filteredPending.slice(start, end);

            if (paginatedPending.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center">No pending reservations found.</td></tr>';
            } else {
                paginatedPending.forEach(reservation => {
                    tbody.innerHTML += `
                        <tr>
                            <td>${reservation.student_id}</td>
                            <td>${reservation.student_name}</td>
                            <td>${reservation.bh_name}</td>
                            <td>${reservation.room_type}</td>
                            <td>${reservation.date_reserved}</td>
                            <td>${reservation.status}</td>
                        </tr>
                    `;
                });
            }

            // Update pagination controls
            document.getElementById('pendingPageInfo').textContent = `Page ${currentPendingPage}`;
            document.getElementById('pendingBackBtn').disabled = currentPendingPage === 1;
            document.getElementById('pendingNextBtn').disabled = end >= filteredPending.length;
        }

        // Search function
        function searchPending() {
            const searchTerm = document.getElementById('pendingSearch').value.toLowerCase();
            filteredPending = pendingApp.filter(reservation =>
                reservation.student_name.toLowerCase().includes(searchTerm) ||
                reservation.student_id.toLowerCase().includes(searchTerm)
            );
            currentPendingPage = 1;
            renderPending();
        }

        // Pagination
        function changePendingPage(direction) {
            currentPendingPage += direction;
            if (currentPendingPage < 1) currentPendingPage = 1;
            renderPending();
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('pendingSearch').addEventListener('input', searchPending);
            document.getElementById('pendingBackBtn').addEventListener('click', () => changePendingPage(-1));
            document.getElementById('pendingNextBtn').addEventListener('click', () => changePendingPage(1));
            renderPending();
        });
    })();
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
                    <form method="POST" action="superadminprocess.php">
                        
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



<!-- SETTINGS SECTION -->
 
<section id="setting" class="section">
  <h2 class="mb-4">System Settings</h2>

  <div class="card mb-3">
    <div class="card-header">Map & Location Settings</div>
    <div class="card-body">
      <button class="btn btn-outline-primary mb-2" data-bs-toggle="modal" data-bs-target="#viewAllMapModal">
        🗺️ View All Boarding Houses Map
      </button>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header">Token & Access Settings</div>
    <div class="card-body">
      <button class="btn btn-outline-primary mb-2" data-bs-toggle="modal" data-bs-target="#tokenExpirationModal">
        ⏱️ Set Token Expiration
      </button>
      <button class="btn btn-outline-danger mb-2" data-bs-toggle="modal" data-bs-target="#cleanTokensModal">
        🧹 Clean Expired Tokens
      </button>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header">System Maintenance</div>
    <div class="card-body">
      <button class="btn btn-outline-warning mb-2" data-bs-toggle="modal" data-bs-target="#cleanupModal">
        🧾 Data Cleanup
      </button>
      <button class="btn btn-outline-success mb-2" data-bs-toggle="modal" data-bs-target="#backupModal">
        💾 Backup Database
      </button>
      <button class="btn btn-outline-secondary mb-2" data-bs-toggle="modal" data-bs-target="#maintenanceModeModal">
        ⚙️ Enable Maintenance Mode
      </button>
    </div>
  </div>

<div class="card mb-3">
  <div class="card-header">Notifications & Messaging</div>
  <div class="card-body">
    <button class="btn btn-outline-info mb-2" data-bs-toggle="modal" data-bs-target="#messageAllModal">
      💬 Send Message to All Landlords
    </button>
    <button class="btn btn-outline-primary mb-2" data-bs-toggle="modal" data-bs-target="#emailSmsModal">
      ✉️ Manage Email / SMS Settings
    </button>
  </div>
</div>

</section>
<!--  MODALS  -->

<!-- View All Map Modal -->
<div class="modal fade" id="viewAllMapModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">All Boarding Houses Map</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="allBHMap" style="height: 400px; border-radius: 10px;"></div>
      </div>
    </div>
  </div>
</div>

<!-- Token Expiration Modal -->
<div class="modal fade" id="tokenExpirationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Set Token Expiration</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label for="tokenDuration" class="form-label">Choose Expiration Duration:</label>
        <select class="form-select" id="tokenDuration">
          <option value="12">12 hours</option>
          <option value="24" selected>24 hours</option>
          <option value="48">48 hours</option>
          <option value="168">7 days</option>
        </select>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary">Save</button>
      </div>
    </div>
  </div>
</div>

<!-- Clean Tokens Modal -->
<div class="modal fade" id="cleanTokensModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Clean Expired Tokens</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to delete all expired tokens from the database?</p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-danger">Yes</button>
      </div>
    </div>
  </div>
</div>

<!-- Data Cleanup Modal -->
<div class="modal fade" id="cleanupModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Data Cleanup</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Warning: This will permanently remove all old records. This action cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-warning">Run Cleanup</button>
      </div>
    </div>
  </div>
</div>

<!-- Backup Database Modal -->
<div class="modal fade" id="backupModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Backup Database</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Click below to generate a full database backup.</p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-success">Download Backup</button>
      </div>
    </div>
  </div>
</div>

<!-- Maintenance Mode Modal -->
<div class="modal fade" id="maintenanceModeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Maintenance Mode</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Toggle maintenance mode to restrict access for all users temporarily.</p>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="maintenanceToggle">
          <label class="form-check-label" for="maintenanceToggle">Enable Maintenance Mode</label>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Send Message to All Landlords Modal -->
<div class="modal fade" id="messageAllModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title">Send Message to All Landlords</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <p class="text-muted mb-3">
          This message will be sent to <strong>all landlords</strong> and will appear in their inbox.
        </p>

        <div class="mb-3">
          <label class="form-label">Message</label>
          <textarea class="form-control" id="broadcastMessage" rows="5" placeholder="Type your message here..."></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-info" id="sendBroadcastBtn">Send to All Landlords</button>
      </div>
    </div>
  </div>
</div>


<!-- Email / SMS Settings Modal -->
<div class="modal fade" id="emailSmsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Manage Email / SMS Settings</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">

        <!-- SMTP SETTINGS -->
        <h6 class="fw-bold mb-3">📧 SMTP (Email) Settings</h6>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">SMTP Host</label>
            <input type="text" class="form-control" id="smtpHost" placeholder="e.g., smtp.gmail.com">
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label">SMTP Port</label>
            <input type="number" class="form-control" id="smtpPort" placeholder="465 or 587">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">SMTP Username (Email)</label>
            <input type="text" class="form-control" id="smtpUsername" placeholder="your@email.com">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">SMTP Password</label>
            <input type="password" class="form-control" id="smtpPassword" placeholder="Your app password">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Sender Name</label>
            <input type="text" class="form-control" id="smtpSenderName" placeholder="e.g., BoardingHouse Admin">
          </div>
        </div>

        <hr class="my-4">

        <!-- TEXTBEE SETTINGS -->
        <h6 class="fw-bold mb-3">📱 TextBee (SMS) Settings</h6>
        <div class="row">
          <div class="col-md-8 mb-3">
            <label class="form-label">TextBee API Key</label>
            <input type="text" class="form-control" id="textbeeApiKey" placeholder="Enter your TextBee API key">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Sender ID</label>
            <input type="text" class="form-control" id="textbeeSender" placeholder="Optional">
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-primary" id="saveNotificationSettings">Save Settings</button>
      </div>
    </div>
  </div>
</div>


            <!-- Edit Boarding House Modal -->
<div class="modal fade" id="editBoardingHouseModal" tabindex="-1" aria-labelledby="editBoardingHouseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editBoardingHouseModalLabel">Edit Boarding House</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editBoardingHouseForm" method="POST" action="superadminprocess.php">
                    <input type="hidden" name="editBhId" id="editBhId">
                    
                    <!-- NAME & CITY -->
                    <div class="mb-3">
                        <label for="name" class="form-label">Boarding House Name</label>
                        <input type="text" class="form-control" name="name" id="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">City/Address</label>
                        <input type="text" class="form-control" name="address" id="address" required>
                    </div>

                    <!-- 2-OPTION MAP BUTTONS -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">📍 Location (2 Easy Options)</label>
                        <div class="d-flex gap-2 mb-2">
                            <button type="button" class="btn btn-outline-primary" onclick="loadEditMap()">
                                🗺️ Click Map
                            </button>
                            <button type="button" class="btn btn-outline-success" onclick="enterCoordinatesEdit()">
                                🌐 Google Link
                            </button>
                        </div>
                        
                        <!-- MAP (Hidden by default) -->
                        <div id="editMap" style="height: 300px; border-radius: 10px; display: none; border: 1px solid #ddd;"></div>
                    </div>

                    <!-- LAT/LONG FIELDS -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editBhLatitude" class="form-label">Latitude</label>
                            <input type="number" class="form-control" name="editBhLatitude" id="editBhLatitude" step="any" min="-90" max="90" required readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editBhLongitude" class="form-label">Longitude</label>
                            <input type="number" class="form-control" name="editBhLongitude" id="editBhLongitude" step="any" min="-180" max="180" required readonly>
                        </div>
                    </div>

                    <button type="submit" name="editBh" class="btn btn-primary">💾 Save Changes</button>
                    <button type="button" class="btn btn-secondary ms-2" data-bs-dismiss="modal">Cancel</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>

// EDIT MODAL MAP SYSTEM ONLY

let editBoardingMap = null;  
let editMapVisible = false;


//EDIT - CLICK MAP

function loadEditMap() {
    const mapDiv = document.getElementById('editMap');
    
    if (!editMapVisible) {
        mapDiv.style.display = 'block';
        editMapVisible = true;
        
        if (!editBoardingMap) {
            editBoardingMap = L.map("editMap").setView([10.720321, 122.562019], 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(editBoardingMap);
        }
        
        // SHOW CURRENT LOCATION
        const currentLat = document.getElementById('editBhLatitude').value;
        const currentLng = document.getElementById('editBhLongitude').value;
        if (currentLat && currentLng) {
            editBoardingMap.setView([currentLat, currentLng], 16);
            L.marker([currentLat, currentLng]).addTo(editBoardingMap)
                .bindPopup(`📍 Current: ${currentLat}, ${currentLng}`).openPopup();
        }
        
        editBoardingMap.on('click', function(e) {
            const lat = e.latlng.lat.toFixed(6);
            const lng = e.latlng.lng.toFixed(6);
            
            document.getElementById('editBhLatitude').value = lat;
            document.getElementById('editBhLongitude').value = lng;
            
            editBoardingMap.eachLayer(function(layer) {
                if (layer instanceof L.Marker) editBoardingMap.removeLayer(layer);
            });
            L.marker([lat, lng]).addTo(editBoardingMap)
                .bindPopup(`Updated: ${lat}, ${lng}`).openPopup();
        });
        
        editBoardingMap.invalidateSize();
        document.querySelector('[onclick="loadEditMap()"]').innerHTML = '📍 Hide Map';
        
    } else {
        mapDiv.style.display = 'none';
        editMapVisible = false;
        document.querySelector('[onclick="loadEditMap()"]').innerHTML = '🗺️ Click Map';
    }
}


//EDIT - GOOGLE MAPS LINK

function enterCoordinatesEdit() {
    const coordsInput = prompt(`📍 Enter Coordinates `);
    if (coordsInput) {
        let lat, lng;
        
        if (coordsInput.includes(',')) [lat, lng] = coordsInput.split(',').map(x => parseFloat(x.trim()));
        else if (coordsInput.includes(' ')) [lat, lng] = coordsInput.split(' ').map(x => parseFloat(x.trim()));
        else {
            searchLocationNameEdit(coordsInput);
            return;
        }
        
        if (!isNaN(lat) && !isNaN(lng)) {
            document.getElementById('editBhLatitude').value = lat.toFixed(6);
            document.getElementById('editBhLongitude').value = lng.toFixed(6);
            
            loadEditMap();
            if (editBoardingMap) {
                editBoardingMap.setView([lat, lng], 16);
                editBoardingMap.eachLayer(function(layer) {
                    if (layer instanceof L.Marker) editBoardingMap.removeLayer(layer);
                });
                L.marker([lat, lng]).addTo(editBoardingMap)
                    .bindPopup(`🌐 Updated: ${lat.toFixed(6)}, ${lng.toFixed(6)}`).openPopup();
            }
            
            alert(`UPDATED!\nLat: ${lat.toFixed(6)}\nLng: ${lng.toFixed(6)}`);
        } else {
            alert('Invalid! Try: 10.6964, 122.5643');
        }
    }
}
</script>

            <!-- Delete Boarding House Modal -->
            <div class="modal fade" id="deleteBoardingHouseModal" tabindex="-1" aria-labelledby="deleteBoardingHouseModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deleteBoardingHouseModalLabel">Confirm Delete</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="superadminprocess.php" method="POST">
                        <div class="modal-body">
                            <p>Are you sure you want to delete this boarding house?</p>
                            <input type="hidden" name="bh_id" id="bh_id">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="deleteBh" class="btn btn-primary">Confirm</button>
                        </div>
                        </form>
                    </div>
                </div>
            </div>

                <!-- Remove Landlord Modal -->
                <div class="modal fade" id="removeLandlordModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="removeLandlordModalLabel">Confirm Remove</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="superadminprocess.php" method="POST">
                        <div class="modal-body">
                            <p>Are you sure you want to delete this boarding house?</p>
                            <input type="hidden" name="id" id="id">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="removeLandlord" class="btn btn-primary">Confirm</button>
                        </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Landlord Modal -->
            <div class="modal fade" id="editLandlord" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Landlord</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" action="superadminprocess.php">
                                <!-- Hidden ID -->
                                <input type="hidden" name="landlord_id" id="edit_landlord_id">

                                <div class="mb-3">
                                    <label class="form-label">Landlord Name</label>
                                    <input type="text" class="form-control" id="edit_landlord_name" name="landlord_name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Landlord Email</label>
                                    <input type="email" class="form-control" id="edit_landlord_email" name="landlord_email" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="text" class="form-control" id="edit_landlord_number" name="landlord_number" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Boarding House</label>
                                    <select class="form-control" name="assignBh" required>
                                        <?php
                                            $bhQuery = mysqli_query($conn, "SELECT bh_id, bh_name FROM boarding_houses");
                                            while ($bhRow = mysqli_fetch_assoc($bhQuery)) {
                                                echo '<option value="'.$bhRow['bh_id'].'">'.$bhRow['bh_name'].'</option>';
                                            }
                                        ?>
                                    </select>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="submit" name="updateLandlord" class="btn btn-primary">Save Changes</button>
                                    <button type="button" class="btn btn-secondary ms-2" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="footer-text">
                <p>© 2025 CyBoard: Boarding House Reservation System</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
    // Sidebar toggle
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
            console.error(`Section with ID ${sectionId} not found`);
            showSection('dashboard'); // Fallback
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
			// Event listener for the book now buttons
			$('[data-bs-target="#deleteBoardingHouseModal"]').on('click', function() {
                const bh_id = $(this).data('id');

                $('#bh_id').val(bh_id);
            });
        });

        $(document).ready(function() {
			// Event listener for the book now buttons
			$('[data-bs-target="#removeLandlordModal"]').on('click', function() {
                const id = $(this).data('id');

                $('#id').val(id);
            });
        });

        $(document).ready(function() {
    // Trigger when Edit Landlord button is clicked
    $(document).on('click', '[data-bs-target="#editLandlord"]', function() {
        // Get data attributes from button
        const id = $(this).data('id');
        const name = $(this).data('name');
        const email = $(this).data('email');
        const number = $(this).data('number');
        const bh = $(this).data('bh');

        // Populate modal fields
        $('#edit_landlord_id').val(id);
        $('#edit_landlord_name').val(name);
        $('#edit_landlord_email').val(email);
        $('#edit_landlord_number').val(number);
        $('#editLandlord select[name="assignBh"]').val(bh);
    });
});
    $(document).ready(function() {
        $('[data-bs-target="#editBoardingHouseModal"]').on('click', function() {
            // Get data attributes from the button
            const id = $(this).data('id');
            const name = $(this).data('name');
            const address = $(this).data('address');
            const lat = $(this).data('lat');     
            const lng = $(this).data('long');    
            
            // Populate the modal
            $('#editBhId').val(id);              
            $('#name').val(name);
            $('#address').val(address);
            $('#editBhLatitude').val(lat);       
            $('#editBhLongitude').val(lng);      
            
            // AUTO-SHOW CURRENT LOCATION ON MAP
            setTimeout(() => {
                loadEditMap();  
            }, 500);
        });
    });


</script>

</body>
</html>
                        