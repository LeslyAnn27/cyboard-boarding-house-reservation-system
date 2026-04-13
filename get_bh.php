<?php
session_start();
include('conn.php');

// Set UTF-8 encoding for the connection
mysqli_set_charset($conn, "utf8mb4");

$statement = "SELECT bh.bh_name, bh.bh_id, bh.bh_address, bh.latitude, bh.longitude 
              FROM boarding_houses bh";

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
        
        // Payment status
        $payment_status = '';
        $paymentStmt = $conn->prepare("SELECT payment_status FROM bh_details WHERE bh_id = ? LIMIT 1");
        if ($paymentStmt) {
            $paymentStmt->bind_param("i", $bh_id);
            $paymentStmt->execute();
            $paymentStmt->bind_result($payment_status);
            $paymentStmt->fetch();
            $paymentStmt->close();
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

        // Gender policy
        $gender_policy = '';
        $genderStmt = $conn->prepare("SELECT gender_policy FROM rooms WHERE bh_id = ? LIMIT 1");
        if ($genderStmt) {
            $genderStmt->bind_param("i", $bh_id);
            $genderStmt->execute();
            $genderStmt->bind_result($gender_policy);
            $genderStmt->fetch();
            $genderStmt->close();
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
            'genderPolicy' => $gender_policy ?: '',
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

// Close connection
mysqli_close($conn);

// Encode to JSON
$json = json_encode($boardingHouses);
if ($json === false) {
    $json = '[]';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boarding Houses Display</title>
    <style>
        .house-card {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .house-name {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        .house-address {
            color: #666;
            margin: 5px 0;
        }
        .house-price {
            color: #3c6e71;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div id="houses-container"></div>

<script>
    // Get the boarding houses data from PHP
    const allHouses = <?php echo $json; ?>;
    
    console.log('Total boarding houses:', allHouses.length);
    console.log('Boarding Houses Data:', allHouses);
    
    // Display the houses
    const container = document.getElementById('houses-container');
    
    if (allHouses && allHouses.length > 0) {
        allHouses.forEach(house => {
            const card = document.createElement('div');
            card.className = 'house-card';
            card.innerHTML = `
                <div class="house-name">${house.name}</div>
                <div class="house-address">📍 ${house.address}</div>
                <div class="house-price">
                    ${house.minPrice && house.maxPrice ? 
                        (house.minPrice === house.maxPrice ? 
                            `₱${house.minPrice}` : 
                            `₱${house.minPrice} - ₱${house.maxPrice}`
                        ) : 'Price not available'
                    }
                    ${house.paymentStatus ? house.paymentStatus : ''}
                </div>
                <div>Rooms Available: ${house.availableRooms}</div>
                <div>Gender Policy: ${house.genderPolicy || 'Not specified'}</div>
                ${house.facilities && house.facilities.length > 0 ? 
                    `<div>Facilities: ${house.facilities.join(', ')}</div>` : ''
                }
                ${house.roomTypes && house.roomTypes.length > 0 ? 
                    `<div>Room Types: ${house.roomTypes.join(', ')}</div>` : ''
                }
            `;
            container.appendChild(card);
        });
    } else {
        container.innerHTML = '<p>No boarding houses found.</p>';
    }
</script>

</body>
</html>