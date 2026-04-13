<?php
session_start();
include('conn.php');

// Set UTF-8 encoding for the connection
mysqli_set_charset($conn, "utf8mb4");


// Force HTTPS
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit();
}

$statement = "SELECT bh.bh_name, bh.bh_id, bh.bh_address, bh.latitude, bh.longitude
              FROM boarding_houses bh
              JOIN landlords l ON bh.bh_id = l.bh_id
              WHERE l.password_set = 1";

$query = mysqli_query($conn, $statement);
$boardingHouses = [];
$shown_bh = [];

if ($query && mysqli_num_rows($query) > 0) {
    while ($rowData = mysqli_fetch_assoc($query)) {
        $bh_id = $rowData['bh_id'];

        if (in_array($bh_id, $shown_bh)) {
            continue;
        }
        $shown_bh[] = $bh_id;
        
        $payment_status = null;
        $bh_pic = null;

        $bhDetailsStmt = $conn->prepare("
            SELECT payment_status, bh_pic 
            FROM bh_details 
            WHERE bh_id = ? 
            LIMIT 1
        ");
        
        if ($bhDetailsStmt) {
            $bhDetailsStmt->bind_param("i", $bh_id);
            $bhDetailsStmt->execute();
            $bhDetailsStmt->bind_result($payment_status, $bh_pic);
            $bhDetailsStmt->fetch();
            $bhDetailsStmt->close();
        }
        
        // Available rooms
        $available_rooms = 0;
        $stmt = $conn->prepare("SELECT SUM(room_capacity) as available_rooms FROM rooms WHERE bh_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $bh_id);
            $stmt->execute();
            $stmt->bind_result($available_rooms);
            $stmt->fetch();
            $stmt->close();
        }

        
        // Check gender policies of all rooms for this boarding house
        $genderStmt = $conn->prepare("SELECT DISTINCT gender_policy FROM rooms WHERE bh_id = ? AND room_capacity > 0");
        if ($genderStmt) {
            $genderStmt->bind_param("i", $bh_id);
            $genderStmt->execute();
            $result = $genderStmt->get_result();
            
            $genderPolicies = [];
            while ($row = $result->fetch_assoc()) {
                $genderPolicies[] = $row['gender_policy'];
            }
            $genderStmt->close();
            
            // Determine the overall gender policy for display
            if (count($genderPolicies) > 1 || in_array('Mixed', $genderPolicies)) {
                $gender_policy = 'Mixed';
            } elseif (count($genderPolicies) === 1) {
                $gender_policy = $genderPolicies[0];
            } elseif(count($genderPolicies) === 0){
                $gender_policy = '';
            } else {
                $gender_policy = 'Mixed';
            }
            
            // Store the available gender policies array for filtering
            $available_gender_policies = $genderPolicies;
        }

        // Room types
        $room_types = [];
        $stmt = $conn->prepare("SELECT DISTINCT room_type FROM rooms WHERE bh_id = ? AND room_type IS NOT NULL");
        if ($stmt) {
            $stmt->bind_param("i", $bh_id);
            $stmt->execute();
            $stmt->bind_result($room_type);
            while ($stmt->fetch()) {
                if ($room_type) $room_types[] = $room_type;
            }
            $stmt->close();
        }

        // Amenities
        $facilities = [];
        $stmt = $conn->prepare("
            SELECT DISTINCT COALESCE(ba.custom_amenity, a.amenity_name) AS amenity_name
            FROM bh_amenities ba
            LEFT JOIN amenities a ON ba.amenity_id = a.id
            WHERE ba.bh_id = ?
            ORDER BY amenity_name
        ");
        if ($stmt) {
            $stmt->bind_param("i", $bh_id);
            $stmt->execute();
            $stmt->bind_result($amenity_name);
            while ($stmt->fetch()) {
                if ($amenity_name) $facilities[] = $amenity_name;
            }
            $stmt->close();
        }

        // Utilities
        $included_utilities = [];
        $utilStmt = $conn->prepare("
            SELECT DISTINCT u.utility_name
            FROM bh_utilities bu
            JOIN utilities u ON bu.utility_id = u.utility_id
            WHERE bu.bh_id = ?
            AND bu.is_included = 'yes'
            ORDER BY u.is_default DESC, u.utility_name
        ");
        if ($utilStmt) {
            $utilStmt->bind_param("i", $bh_id);
            $utilStmt->execute();
            $utilStmt->bind_result($utility_name);
            while ($utilStmt->fetch()) {
                if ($utility_name) $included_utilities[] = $utility_name;
            }
            $utilStmt->close();
        }

        // Price range
        $min_room = 0;
        $max_room = 0;
        $min_down = 0;
        $max_down = 0;
        $stmt = $conn->prepare("
            SELECT MIN(room_rate), MAX(room_rate), MIN(downpayment), MAX(downpayment)
            FROM rooms WHERE bh_id = ?
        ");
        if ($stmt) {
            $stmt->bind_param("i", $bh_id);
            $stmt->execute();
            $stmt->bind_result($min_room, $max_room, $min_down, $max_down);
            $stmt->fetch();
            $stmt->close();
        }

        // Build the boarding house array
        $boardingHouse = array(
            'id' => (int)$bh_id,
            'name' => $rowData['bh_name'] ?: '',
            'address' => $rowData['bh_address'] ?: '',
            'minPrice' => (float)($min_room ?: 0),
            'maxPrice' => (float)($max_room ?: 0),
            'paymentStatus' => $payment_status ? ucfirst($payment_status) : '',
            'bhPic' => $bh_pic ?? 'https://dummyimage.com/400x300/cccccc/666&text=No+Photo',
            'genderPolicy' => $gender_policy ?: '',
            'availableGenderPolicies' => $available_gender_policies,
            'availableRooms' => (int)($available_rooms ?: 0),
            'roomTypes' => $room_types,
            'facilities' => $facilities,
            'utilities' => $included_utilities,
            'downPaymentMin' => (float)($min_down ?: 0),
            'downPaymentMax' => (float)($max_down ?: 0),
            'latitude' => (float)($rowData['latitude'] ?: 0),
            'longitude' => (float)($rowData['longitude'] ?: 0)
        );
        
        // Append to the array
        $boardingHouses[] = $boardingHouse;
    }
}

// Encode to JSON BEFORE closing connection
$json = json_encode($boardingHouses);
if ($json === false) {
    $json = '[]';
}

$id = "";
$firstName = "";
if (isset($_SESSION['tenant_id'])) {
    $id = $_SESSION['tenant_id'];
    $stmt = $conn->prepare("SELECT name FROM tenants WHERE tenant_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $fullname = htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8');
            $firstName = explode(' ', trim($fullname))[0];
        }
        $stmt->close();
    }
}


mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CyBoard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <script src="https://kit.fontawesome.com/14901788bc.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <link rel="stylesheet" href="css/index.css">
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
                    <li><a href="about_us.php"><i class="fa-solid fa-circle-info"></i> About Us</a></li>
                </ul>
            </nav>
            <div class="user-actions">
                <div class="user-profile dropdown">
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'landlord'): ?>
                        <div class="d-flex align-items-center">
                            <i class="fa-solid fa-arrow-left me-2"></i>
                            <span><a href="admin.php" style="text-decoration: none; color: inherit;">Go Back to Dashboard</a></span>
                        </div>
                    
                    <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'): ?>
                        <div class="d-flex align-items-center">
                            <i class="fa-solid fa-arrow-left me-2"></i>
                            <span><a href="superadmin.php" style="text-decoration: none; color: inherit;">Go Back to Dashboard</a></span>
                        </div>
                    <?php elseif (!isset($_SESSION['tenant_id'])): ?>
                        <div class="d-flex align-items-center">
                            <i class="fa-regular fa-circle-user me-2" style="font-size: 28px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"></i>
                            <span>
                                <a href="login.php" style="text-decoration: none; color: inherit;">Sign In</a>
                            </span>
                        </div>
                    <?php else: ?>
                        <div class="d-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer;">
                            <i class="fa-regular fa-circle-user me-2" style="font-size: 28px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"></i>
                            <span><?php echo $firstName; ?></span>
                        </div>
                        <div class="dropdown-menu">
                            <a href="profile.php" class="dropdown-item"><i class="fa-sharp fa-solid fa-user"></i> My Profile</a>
                            <a href="logout.php" class="dropdown-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <section class="search-section">
        <div class="search-bar">
            <label for="searchQuery" class="visually-hidden">Search</label>
            <input type="text" id="searchQuery" placeholder="Search boarding houses, locations...">
            <button type="button" id="searchBtn"><i class="fas fa-search"></i></button>
        </div>
        <div class="filter-sort">
            <button class="filter-button" data-bs-toggle="modal" data-bs-target="#filterModal">
                <i class="fas fa-filter"></i> Filter
            </button>
            <button class="filter-button" id="fixedLocationBtn" onclick="toggleFixedLocation()">
                <i class="fas fa-map-marker-alt"></i> Near Campus
            </button>
            <button class="sort-button" data-bs-toggle="modal" data-bs-target="#sortModal">
                <i class="fas fa-sort"></i> Sort By
            </button>
            <button class="filter-button show-all-button" id="show-all">
                <i class="fas fa-list"></i> Show All
            </button>
        </div>
    </section>

    <div class="container">
        <div class="map" id="map"></div>
        <div class="boarding-house-list">
            <p class="boarding-house-count">Showing <span id="boarding-house-count">0</span> boarding houses</p>
            <div id="boarding-houses-container"></div>
            <div id="no-results" class="no-results" style="display: none;">
                <i class="fas fa-home fa-3x mb-3"></i>
                <h4>No boarding houses found</h4>
                <p>Try adjusting your search criteria.</p>
            </div>
        </div>
    </div>

    <!-- Filter Modal -->
    <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="filterModalLabel">Filter Boarding Houses</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="filterForm">
                        <div class="mb-3">
                            <label class="form-label" for="priceMin">Price Range</label>
                            <div class="d-flex gap-2">
                                <input type="number" class="form-control" placeholder="Min" id="priceMin" name="priceMin" min="0" />
                                <input type="number" class="form-control" placeholder="Max" id="priceMax" name="priceMax" min="0" />
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="roomType">Room Type</label>
                            <select class="form-select" id="roomType" name="roomType">
                                <option value="">Any type</option>
                                <option value="solo">Solo (1 person)</option>
                                <option value="double">Double sharing (2 people)</option>
                                <option value="dormitory">Dormitory style (3+ people)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="paymentTerms">Payment Terms</label>
                            <select class="form-select" id="paymentTerms" name="paymentTerms">
                                <option value="">Any terms</option>
                                <option value="Monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="semiannual">Semi-annual</option>
                                <option value="annual">Annual</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="genderPolicy">Gender Policy</label>
                            <select class="form-select" id="genderPolicy" name="genderPolicy">
                                <option value="">Any</option>
                                <option value="Male Only">Male only</option>
                                <option value="Female Only">Female only</option>
                                <option value="Mixed">Mixed gender</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Utilities</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="includedWater" name="utilities" value="Water" />
                                <label class="form-check-label" for="includedWater">Water included</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="includedElectricity" name="utilities" value="Electricity" />
                                <label class="form-check-label" for="includedElectricity">Electricity included</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="includedInternet" name="utilities" value="Internet" />
                                <label class="form-check-label" for="includedInternet">Internet included</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Facilities</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="privateBathRoom" name="facilities" value="Private Bathroom" />
                                <label class="form-check-label" for="privateBathRoom">Private Bathroom</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="airConditioning" name="facilities" value="Air Conditioning" />
                                <label class="form-check-label" for="airConditioning">Air Conditioning</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="bed" name="facilities" value="Bed" />
                                <label class="form-check-label" for="bed">Bed</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="wifi" name="facilities" value="WiFi" />
                                <label class="form-check-label" for="wifi">WiFi Access</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="studyTable" name="facilities" value="Study Table" />
                                <label class="form-check-label" for="studyTable">Study Table</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="parking" name="facilities" value="Parking Space" />
                                <label class="form-check-label" for="parking">Parking Spaces</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="laundry" name="facilities" value="Laundry Area" />
                                <label class="form-check-label" for="laundry">Laundry Area</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="clearFilters">Clear Filters</button>
                    <button type="button" class="btn btn-primary" id="applyFilters">Apply Filters</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sort Modal -->
    <div class="modal fade" id="sortModal" tabindex="-1" aria-labelledby="sortModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sortModalLabel">Sort Boarding Houses</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="list-group">
                        <button type="button" class="list-group-item list-group-item-action" onclick="applySorting('price-asc'); bootstrap.Modal.getInstance(document.getElementById('sortModal')).hide();">Price: Low to High</button>
                        <button type="button" class="list-group-item list-group-item-action" onclick="applySorting('price-desc'); bootstrap.Modal.getInstance(document.getElementById('sortModal')).hide();">Price: High to Low</button>
                        <button type="button" class="list-group-item list-group-item-action" onclick="applySorting('availability'); bootstrap.Modal.getInstance(document.getElementById('sortModal')).hide();">Availability: Most Rooms</button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="clearSorting">Clear Sorting</button>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="footer-text" style="text-align: center; margin-top: 20px;">
        <p>© 2025 CyBoard: Boarding House Reservation System</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.min.js" integrity="sha384-RuyvpeZCxMJCqVUGFI0Do1mQrods/hhxYlcVfGPOfQtPJh0JCw12tUAZ/Mv10S7D" crossorigin="anonymous"></script>
    
    <script>
        function toggleMenu() {
            const nav = document.querySelector('nav ul');
            nav.classList.toggle('active');
        }

        var map = L.map("map").setView([10.720321, 122.562019], 12);

        L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19,
            attribution: '© <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        }).addTo(map);

        const boardingHouses = <?php echo $json; ?>;
        let filteredHouses = [...boardingHouses];
        let currentSort = null;

        // DOM Elements
        const searchInput = document.getElementById('searchQuery');
        const searchBtn = document.getElementById('searchBtn');
        const container = document.getElementById('boarding-houses-container');
        const countSpan = document.getElementById('boarding-house-count');
        const noResults = document.getElementById('no-results');
        const showAllBtn = document.getElementById('show-all');
        const nearMeBtn = document.getElementById('fixedLocationBtn');

        // Map variables
        let currentTempMarker = null;
        let currentBHName = null;
        let markerVisible = false;
        let markers = [];
        let userMarker = null;
        let userCircle = null;
        let houseMarkers = [];
        let isShowingAllMarkers = false;
        let isFixedLocationMode = false;
        let fixedActive = false;
        let fixedMarker = null;
        let radiusCircle = null;
        let fixedHouseMarkers = [];
        let loopInterval = null;
        let fixedLocationActive = false;

        // Initialize
        document.addEventListener("DOMContentLoaded", () => {
            renderBoardingHouses(boardingHouses);
            setupEventListeners();
        });

        function setupEventListeners() {
            if (searchInput) {
                searchInput.addEventListener("input", handleSearch);
                searchInput.addEventListener("keypress", e => {
                    if (e.key === "Enter") handleSearch();
                });
            }

            if (searchBtn) searchBtn.addEventListener("click", handleSearch);
            if (showAllBtn) showAllBtn.addEventListener("click", showAll);
        }

        function showOnMap(name, lat, lng) {
            const icon = createBadgeIcon(name);
            const marker = L.marker([lat, lng], { icon })
                .addTo(map)
                .on('click', () => showBoardingHouseDetails(name));
            markers.push(marker);
        }

        function handleSearch() {
            const query = searchInput.value.toLowerCase().trim();

            if (query === '') {
                filteredHouses = [...boardingHouses];
                renderBoardingHouses(filteredHouses);

                if (markerVisible) {
                    markers.forEach(marker => map.removeLayer(marker));
                    markers = [];
                    showAllBtn.innerHTML = '<i class="fas fa-list"></i> Show All';
                    markerVisible = false;
                }
                return;
            }

            filteredHouses = boardingHouses.filter(house => {
                const name = house.name.toLowerCase();
                const address = house.address.toLowerCase();
                return name.includes(query) || address.includes(query);
            });

            if (filteredHouses.length === 0) {
                filteredHouses = [...boardingHouses];
                renderBoardingHouses(filteredHouses);

                if (markerVisible) {
                    markers.forEach(marker => map.removeLayer(marker));
                    markers = [];
                    showAllBtn.innerHTML = '<i class="fas fa-list"></i> Show All';
                    markerVisible = false;
                }
                return;
            }

            renderBoardingHouses(filteredHouses);

            if (markerVisible) {
                markers.forEach(marker => map.removeLayer(marker));
                markers = [];
            }

            filteredHouses.forEach(house => {
                if (house.latitude && house.longitude) {
                    showOnMap(house.name, house.latitude, house.longitude);
                }
            });

            showAllBtn.innerHTML = '<i class="fas fa-map-marker-alt"></i> Hide Markers';
            markerVisible = true;
        }

        function showAll() {
            if (currentTempMarker) {
                map.removeLayer(currentTempMarker);
                currentTempMarker = null;
                currentBHName = null;
            }

            if (fixedMarker) {
                map.removeLayer(fixedMarker);
                fixedMarker = null;
            }
            if (radiusCircle) {
                map.removeLayer(radiusCircle);
                radiusCircle = null;
            }
            if (fixedHouseMarkers && fixedHouseMarkers.length > 0) {
                fixedHouseMarkers.forEach(m => map.removeLayer(m));
                fixedHouseMarkers = [];
            }
            if (loopInterval) {
                clearInterval(loopInterval);
                loopInterval = null;
            }
            fixedActive = false;

            if (!markerVisible) {
                renderBoardingHouses(boardingHouses);

                markers = boardingHouses.map(h => {
                    if (!h.latitude || !h.longitude) return null;
                    const icon = createBadgeIcon(h.name);
                    const marker = L.marker([h.latitude, h.longitude], { icon })
                        .addTo(map)
                        .on('click', () => showBoardingHouseDetails(h.name));
                    return marker;
                }).filter(Boolean);

                if (markers.length > 0) {
                    const group = new L.featureGroup(markers);
                    map.fitBounds(group.getBounds().pad(0.2));
                }

                showAllBtn.innerHTML = '<i class="fas fa-times"></i> Hide All';
                markerVisible = true;
            } else {
                markers.forEach(m => map.removeLayer(m));
                markers = [];

                showAllBtn.innerHTML = '<i class="fas fa-list"></i> Show All';
                markerVisible = false;
            }
        }

        const applyFiltersBtn = document.getElementById('applyFilters');
        const clearFiltersBtn = document.getElementById('clearFilters');
        const clearSortingBtn = document.getElementById('clearSorting');

        applyFiltersBtn.addEventListener('click', applyFilters);
        clearFiltersBtn.addEventListener('click', clearFilters);
        clearSortingBtn.addEventListener('click', clearSorting);

        function applyFilters() {
            const priceMin = parseInt(document.getElementById('priceMin').value) || 0;
            const priceMax = parseInt(document.getElementById('priceMax').value) || Infinity;
            const roomType = document.getElementById('roomType').value;
            const paymentTerms = document.getElementById('paymentTerms').value;
            const genderPolicy = document.getElementById('genderPolicy').value;

            const selectedUtilities = Array.from(document.querySelectorAll('input[name="utilities"]:checked'))
                .map(cb => cb.value);
            const selectedFacilities = Array.from(document.querySelectorAll('input[name="facilities"]:checked'))
                .map(cb => cb.value);

            filteredHouses = boardingHouses.filter(house => {
                if (house.maxPrice < priceMin || house.minPrice > priceMax) {
                    return false;
                }

                if (roomType) {
                    const hasMatchingRoom = house.roomTypes.some(type => {
                        const typeLower = type.toLowerCase();
                        
                        if (roomType === 'solo') {
                            // Match "solo", "single", or "1"
                            return typeLower.includes('solo') || typeLower.includes('single') || typeLower === '1';
                        } else if (roomType === 'double') {
                            // Match "double", "shared", "sharing", or "2"
                            return typeLower.includes('double') || typeLower.includes('shared') || typeLower.includes('doble') || 
                                   typeLower.includes('sharing') || typeLower === '2';
                        } else if (roomType === 'dormitory') {
                            // Match "dorm", "dormitory", or numbers 3+
                            return typeLower.includes('dorm') || typeLower.includes('triple') || typeLower.includes('dormitory') || 
                                   parseInt(typeLower) >= 3;
                        }
                        return false;
                    });
                    
                    if (!hasMatchingRoom) {
                        return false;
                    }
                }

                if (paymentTerms && house.paymentStatus !== paymentTerms) {
                    return false;
                }
                
                if (genderPolicy) {
    // Strict matching - only show exact matches
    const hasMatchingRoom = house.availableGenderPolicies.some(policy => {
        return policy === genderPolicy;
    });
    

    if (!hasMatchingRoom) {
        return false;
    }
}

                if (selectedUtilities.length > 0) {
                    const hasAllUtilities = selectedUtilities.every(utility =>
                        house.utilities.includes(utility));
                    if (!hasAllUtilities) return false;
                }

                if (selectedFacilities.length > 0) {
                    const hasAllFacilities = selectedFacilities.every(facility =>
                        house.facilities.includes(facility));
                    if (!hasAllFacilities) return false;
                }

                return true;
            });
            

            renderBoardingHouses(filteredHouses);
            bootstrap.Modal.getInstance(document.getElementById('filterModal')).hide();
        }

        function clearFilters() {
            document.getElementById('filterForm').reset();
            filteredHouses = [...boardingHouses];
            renderBoardingHouses(filteredHouses);
        }

        function clearSorting() {
            filteredHouses = [...boardingHouses];
            renderBoardingHouses(filteredHouses);
        }

        function applySorting(sortType, render = true) {
            currentSort = sortType;

            switch (sortType) {
                case 'price-asc':
                    filteredHouses.sort((a, b) => a.minPrice - b.minPrice);
                    break;
                case 'price-desc':
                    filteredHouses.sort((a, b) => b.maxPrice - a.maxPrice);
                    break;
                case 'availability':
                    filteredHouses.sort((a, b) => b.availableRooms - a.availableRooms);
                    break;
            }

            if (render) {
                renderBoardingHouses(filteredHouses);
            }
        }

        function renderBoardingHouses(houses) {
            countSpan.textContent = houses.length;

            if (houses.length === 0) {
                container.innerHTML = '';
                noResults.style.display = 'block';
                return;
            }

            noResults.style.display = 'none';
            container.innerHTML = houses.map(house => `
                <div class="boarding-house-card">
                    <div class="boarding-house-image">
                        <img src="${house.bhPic}" alt="${house.name}">
                        ${house.availableRooms > 0 ? 
                            `<div class="available-badge">Rooms Available</div>` : 
                            `<div class="noavailable-badge">No Rooms Available</div>`
                        }
                        ${house.genderPolicy ? 
                            `<div class="gender-badge">${house.genderPolicy}</div>` : 
                            ''
                        }
                    </div>
                    <div class="boarding-house-info">
                        <h3 class="boarding-house-name">${house.name}</h3>
                        <div class="boarding-house-location">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>${house.address}</span>
                        </div>
                        <div class="price-range">
                            ${house.minPrice && house.maxPrice ? (
                                house.minPrice === house.maxPrice ?
                                    `₱${house.minPrice} ${house.paymentStatus}` :
                                    `₱${house.minPrice} - ₱${house.maxPrice} ${house.paymentStatus}`
                            ) : ''}
                        </div>
                        
                        <div class="facilities">
                            ${house.facilities && house.facilities.length > 0 ? 
                                house.facilities.map(facility => 
                                    facility ? `<span class="facility-tag">${facility}</span>` : ''
                                ).join('') : 
                                ''
                            }
                        </div>
                        
                        <div class="fw-bold">
                            ${house.roomTypes && house.roomTypes.length > 0 ? 
                                `<div>Rooms: ${house.roomTypes.join(', ')}</div>` : 
                                (house.availableRooms === 0 ? 
                                    `<div>No rooms available</div>` : 
                                    `<div>No valid room types found</div>`
                                )
                            }
                        </div>
                        
                        <div class="downpayment-info fw-bold">
                            ${house.downPaymentMin && house.downPaymentMax ? (
                                house.downPaymentMin === house.downPaymentMax ?
                                    `Downpayment Range: ₱${house.downPaymentMin}` :
                                    `Downpayment Range: ₱${house.downPaymentMin} - ₱${house.downPaymentMax}`
                            ) : ''}
                        </div>

                        <div class="button-container" style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px;">
                            <form action="boardinghouse.php" method="POST" style="flex: 1; min-width: 120px;">
                                <input type="number" name="bh_id" value="${house.id}" hidden>
                                <input type="number" name="tenant_id" value="<?php echo $id; ?>" hidden>
                                <button type="submit" class="view-button" style="width: 100%; padding: 10px 16px; font-size: 14px; white-space: nowrap;">View</button>
                            </form>
                            <button class="review-button" onclick="openGoogleMaps(${house.latitude}, ${house.longitude})" style="flex: 1; min-width: 150px; padding: 10px 16px; font-size: 14px; white-space: nowrap;">Start Navigation</button>
                            <button class="map-marker-button" onclick="showOnMap('${house.name.replace(/'/g, "\\'")}', ${house.latitude}, ${house.longitude})" style="flex: 1; min-width: 140px; padding: 10px 16px; font-size: 14px; white-space: nowrap;">View on Map</button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function openGoogleMaps(lat, lng) {
            if (!lat || !lng) {
                alert("This boarding house does not have a valid location.");
                return;
            }
            const googleMapsUrl = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}&travelmode=driving`;
            window.open(googleMapsUrl, "_blank");
        }

        function showBoardingHouseDetails(bhName) {
            const boardingHouseCards = document.querySelectorAll('.boarding-house-card');
            let found = false;

            boardingHouseCards.forEach(function(card) {
                const cardName = card.querySelector('.boarding-house-name').textContent.trim();
                if (cardName === bhName) {
                    found = true;
                    card.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });

                    card.style.border = '3px solid #3c6e71';
                    card.style.boxShadow = '0 4px 15px rgba(60, 110, 113, 0.3)';
                    card.style.transform = 'scale(1.02)';
                    card.style.transition = 'all 0.3s ease';

                    setTimeout(function() {
                        card.style.border = '';
                        card.style.boxShadow = '';
                        card.style.transform = '';
                        card.style.transition = '';
                    }, 3000);
                }
            });

           
        }

        function showOnMap(bhName, latitude, longitude) {
            if (!latitude || !longitude) {
                alert("This boarding house does not have a valid location.");
                return;
            }

            if (currentTempMarker && currentBHName === bhName) {
                map.removeLayer(currentTempMarker);
                currentTempMarker = null;
                currentBHName = null;
                return;
            }

            if (currentTempMarker) {
                map.removeLayer(currentTempMarker);
            }

            map.setView([latitude, longitude], 15);

            const tempIcon = createBadgeIcon(bhName);
            currentTempMarker = L.marker([latitude, longitude], {
                icon: tempIcon
            }).addTo(map);

            currentBHName = bhName;

            document.getElementById('map').scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });

            setTimeout(() => map.invalidateSize(), 400);
        }

        function createBadgeIcon(name) {
            return L.divIcon({
                className: 'custom-div-icon',
                html: `
            <div style="
                background: linear-gradient(135deg, #3c6e71, #2c5052);
                color: white;
                padding: 6px 10px;
                border-radius: 15px;
                font-size: 11px;
                font-weight: bold;
                border: 2px solid white;
                box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                white-space: nowrap;
                position: relative;">
                ${name}
                <div style="
                    position: absolute;
                    bottom: -6px;
                    left: 50%;
                    transform: translateX(-50%);
                    width: 0;
                    height: 0;
                    border-left: 6px solid transparent;
                    border-right: 6px solid transparent;
                    border-top: 6px solid #2c5052;">
                </div>
            </div>`,
                iconSize: [null, 20],
                iconAnchor: [50, 26],
                popupAnchor: [0, -26]
            });
        }

        function toggleFixedLocation() {
            const fixedLat = 10.6920135725996;
            const fixedLng = 122.56958306514481;
            const btn = document.getElementById("fixedLocationBtn");

            if (fixedLocationActive) {
                if (currentTempMarker) {
                    map.removeLayer(currentTempMarker);
                    currentTempMarker = null;
                }
                if (radiusCircle) {
                    map.removeLayer(radiusCircle);
                    radiusCircle = null;
                }
                if (markers.length > 0) {
                    markers.forEach(m => map.removeLayer(m));
                    markers = [];
                }

                fixedLocationActive = false;
                btn.innerHTML = `<i class="fas fa-map-marker-alt"></i> Near campus`;
                btn.style.backgroundColor = "";
                
                // Re-render all houses
                renderBoardingHouses(boardingHouses);
                return;
            }

            if (currentTempMarker) map.removeLayer(currentTempMarker);
            if (radiusCircle) map.removeLayer(radiusCircle);
            if (markers.length > 0) {
                markers.forEach(m => map.removeLayer(m));
                markers = [];
            }

            map.setView([fixedLat, fixedLng], map.getZoom());

            currentTempMarker = L.marker([fixedLat, fixedLng], {
                icon: L.divIcon({
                    className: 'fixed-icon',
                    html: `<div style="background:#e63946; width:14px; height:14px; border-radius:50%; border:2px solid white;"></div>`
                })
            }).addTo(map).bindPopup("📍 University of Iloilo").openPopup();

            radiusCircle = L.circle([fixedLat, fixedLng], {
                radius: 500,
                color: "#3c6e71",
                fillColor: "#3c6e71",
                fillOpacity: 0.1
            }).addTo(map);

            const nearby = boardingHouses.filter(h => {
                const dist = map.distance([fixedLat, fixedLng], [h.latitude, h.longitude]);
                return dist <= 500;
            });

            renderBoardingHouses(nearby);

            nearby.forEach(house => {
                const badgeIcon = createBadgeIcon(house.name);
                const marker = L.marker([house.latitude, house.longitude], {
                    icon: badgeIcon
                }).addTo(map);
                marker.on('click', () => showBoardingHouseDetails(house.name));
                markers.push(marker);
            });

            alert(`${nearby.length} boarding houses found within 500 meters from the campus.`);

            fixedLocationActive = true;
            btn.innerHTML = `<i class="fas fa-times-circle"></i> Cancel Near Campus`;
        }

        window.addEventListener("pageshow", function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
    
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                const urlParams = new URLSearchParams(window.location.search);
                const name = urlParams.get('name');
                const lat = parseFloat(urlParams.get('lat'));
                const lng = parseFloat(urlParams.get('lng'));
            
                if (name && !isNaN(lat) && !isNaN(lng)) {
                    // Auto-show the marker on map
                    showOnMap(name, lat, lng);
                }
            });
        </script>

</body>
</html>