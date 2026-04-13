<?php
require 'conn.php';
    session_start(); 
        // Check if the user is logged in
        if (!isset($_SESSION['tenant_id'])) {
            header("Location: login.php"); // Redirect to login page if not logged in
            exit();
        } 

        $tenant_id = $_SESSION['tenant_id'];
        $stmt = $conn->prepare("SELECT name, email, phone_number, year_level, program, stud_id FROM tenants WHERE tenant_id = ?");
        $stmt->bind_param("i", $tenant_id);
        // Execute
        $stmt->execute();

        // Bind all result columns
        $stmt->bind_result($fullname, $email, $phone, $year, $program, $student_number);

        // Fetch the data
        if ($stmt->fetch()) {
            // Get first name if needed
            $first_name = explode(' ', trim($fullname))[0];
        }
        $stmt->close();

		
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CyBoard - My Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <!-- font awesome kit -->
    <script src="https://kit.fontawesome.com/14901788bc.js" crossorigin="anonymous"></script>
    <!-- Vue.js -->
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background-color: #f5f7fa;
            color: #333;
        }
        header {
            background-color: #3c6e71;
            padding: 1rem;
            color: white;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }
        .user-actions {
            display: flex;
            align-items: center;
        }
        .user-profile {
            display: flex;
            align-items: center;
            cursor: pointer;
            margin-left: 1rem;
            position: relative;
        }
        .user-profile img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 0.5rem;
        }
        .user-profile:hover .dropdown-menu {
            display: block;
        }
        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background-color: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            padding: 0.5rem 0;
            width: 200px;
            z-index: 10;
        }
        .dropdown-menu a {
            display: block;
            padding: 0.75rem 1rem;
            color: #333;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .dropdown-menu a:hover {
            background-color: #f5f5f5;
        }
        .dropdown-menu a i {
            margin-right: 0.5rem;
            width: 20px;
            text-align: center;
        }
        main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.5rem;
        }
        .profile-section {
            background-color: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }
        .profile-section h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            font-size: 1rem;
        }
        .form-actions {
            display: flex;
            gap: 1rem;
        }
        .save-btn {
            background-color: #3c6e71;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
        }
        .save-btn:hover {
            background-color: #2c5052;
        }
        .reservations-section {
            background-color: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        .reservations-section h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        .reservation-card {
            border-bottom: 1px solid #e0e0e0;
            padding: 1rem 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .reservation-card:last-child {
            border-bottom: none;
        }
        .reservation-details {
            flex: 1;
        }
        .reservation-details h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }
        .reservation-details p {
            color: #666;
            font-size: 0.9rem;
        }
        .status-pending { color: #f39c12; font-weight: bold; }
        .status-waiting { color: #3498db; font-weight: bold; }
        .status-active { color: #27ae60; font-weight: bold; }
        .status-rejected { color: #e74c3c; font-weight: bold; }
        .cancel-btn {
            background-color: #d32f2f;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
        }
        .cancel-btn:hover {
            background-color: #b71c1c;
        }
        footer {
            background-color: #333;
            color: white;
            padding: 2rem 0;
            margin-top: 2rem;
        }
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
        }
        .footer-column h3 {
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        .footer-column ul {
            list-style: none;
        }
        .footer-column ul li {
            margin-bottom: 0.5rem;
        }
        .footer-column ul li a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s;
        }
        .footer-column ul li a:hover {
            color: white;
        }
        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        .social-links a {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s;
        }
        .social-links a:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        .footer-bottom {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.5rem;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 2rem;
            font-size: 0.9rem;
            color: #ccc;
        }
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                align-items: flex-start;
            }
            .user-actions {
                width: 100%;
                justify-content: flex-end;
            }
            .form-actions {
                flex-direction: column;
            }
            .reservation-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    
    <div id="app">
        <header>
            <div class="header-container">
                <a href="#" class="logo">CyBoard</a>
                <div class="user-actions">
                <div class="user-profile dropdown">
                   
                        <i class="fa-regular fa-circle-user" style="font-size: 25px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"></i>
                        <span><?= $first_name ?></span>
                    
                    <div class="dropdown-menu">
                        <a href="index.php"><i class="fa-solid fa-house"></i> Home</a>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
            </div>
        </header>

        <main>
<section class="profile-section">
    <h2>Update Profile</h2>
    <form method="POST" action="process.php">
        <!-- Full Name (readonly) -->
        <div class="form-group mb-3">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" class="form-control"
                   value="<?= htmlspecialchars($fullname) ?>" readonly>
        </div>

        <!-- Email (readonly) -->
        <div class="form-group mb-3">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control"
                   value="<?= htmlspecialchars($email) ?>" readonly>
        </div>

        <!-- Student Number -->
        <div class="form-group mb-3">
            <label for="student_number">Student Number</label>
            <input type="text" id="student_number" name="student_number" class="form-control"
                   value="<?= htmlspecialchars($student_number) ?>"
                   pattern="[0-9]{2}-[0-9]{4}-[0-9]{6}"
                   title="Format: XX-XXXX-XXXXXX" required>
        </div>

        <!-- Year Level -->
        <div class="form-group mb-3">
            <label for="year_level">Year Level</label>
            <select id="year_level" name="year_level" class="form-select" required>
                <option value="1st Year" <?= ($year == "1st Year") ? "selected" : "" ?>>1st Year</option>
                <option value="2nd Year" <?= ($year == "2nd Year") ? "selected" : "" ?>>2nd Year</option>
                <option value="3rd Year" <?= ($year == "3rd Year") ? "selected" : "" ?>>3rd Year</option>
                <option value="4th Year" <?= ($year == "4th Year") ? "selected" : "" ?>>4th Year</option>
            </select>
        </div>

        <!-- Program -->
        <div class="form-group mb-3">
            <label for="program">Program</label>
            <select id="program" name="program" class="form-select" required>
                <option value="" disabled selected>Select Program</option>
                <option value="AB Psychology" <?= ($program == "AB Psychology") ? "selected" : "" ?>>AB Psychology</option>
                <option value="BS Pharmacy" <?= ($program == "BS Pharmacy") ? "selected" : "" ?>>BS Pharmacy</option>
                <option value="BEED" <?= ($program == "BEED") ? "selected" : "" ?>>BEED</option>
                <option value="BEED English" <?= ($program == "BEED English") ? "selected" : "" ?>>BEED English</option>
                <option value="BEED Filipino" <?= ($program == "BEED Filipino") ? "selected" : "" ?>>BEED Filipino</option>
                <option value="BS Special Needs Ed" <?= ($program == "BS Special Needs Ed") ? "selected" : "" ?>>BS Special Needs Ed</option>
                <option value="BS Mechanical Engineering" <?= ($program == "BS Mechanical Engineering") ? "selected" : "" ?>>BS Mechanical Engineering</option>
                <option value="BS Marine Engineering" <?= ($program == "BS Marine Engineering") ? "selected" : "" ?>>BS Marine Engineering</option>
                <option value="BSBA Marketing Management" <?= ($program == "BSBA Marketing Management") ? "selected" : "" ?>>BSBA Marketing Management</option>
                <option value="BSBA Financial Management" <?= ($program == "BSBA Financial Management") ? "selected" : "" ?>>BSBA Financial Management</option>
                <option value="BS Nursing" <?= ($program == "BS Nursing") ? "selected" : "" ?>>BS Nursing</option>
                <option value="BS Information Technology" <?= ($program == "BS Information Technology") ? "selected" : "" ?>>BS Information Technology</option>
                <option value="BS Civil Engineering" <?= ($program == "BS Civil Engineering") ? "selected" : "" ?>>BS Civil Engineering</option>
                <option value="BS Criminology" <?= ($program == "BS Criminology") ? "selected" : "" ?>>BS Criminology</option>
                <option value="BS Business Administration" <?= ($program == "BS Business Administration") ? "selected" : "" ?>>BS Business Administration</option>
                <option value="BS Hospitality Management" <?= ($program == "BS Hospitality Management") ? "selected" : "" ?>>BS Hospitality Management</option>
                <option value="BS Accountancy" <?= ($program == "BS Accountancy") ? "selected" : "" ?>>BS Accountancy</option>
                <option value="BS Accountancy Info System" <?= ($program == "BS Accountancy Info System") ? "selected" : "" ?>>BS Accountancy Info System</option>
                <option value="BS Tourism Management" <?= ($program == "BS Tourism Management") ? "selected" : "" ?>>BS Tourism Management</option>
            </select>
        </div>

        <!-- Phone Number -->
        <div class="form-group mb-3">
            <label for="phone">Phone Number</label>
            <input type="tel" id="phone" name="phone" class="form-control"
                   value="<?= htmlspecialchars($phone) ?>" pattern="[0-9]{11}" required>
        </div>

        <!-- Submit Button -->
        <div class="form-actions text-center">
            <button type="submit" name="update_profile" class="save-btn btn w-50">Save Changes</button>
        </div>
    </form>
</section>

<!-- Change Password Section -->
<section class="profile-section">
    <h2>Change Password</h2>
    <form method="POST" action="process.php">
        <div class="form-group mb-3">
            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password" placeholder="Enter current password" required>
        </div>
        <div class="form-group mb-3">
            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
        </div>
        <div class="form-group mb-3">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
        </div>
        <div class="form-actions">
            <button class="save-btn btn btn-warning" type="submit" name="change_password">Change Password</button>
        </div>
    </form>
</section>

            <?php
                $sql = "SELECT 
                            r.reservation_id,
                            r.status,
                            b.bh_name,
                            rm.room_type,
                            DATE_FORMAT(r.reserved_at, '%M %d, %Y') AS date
                        FROM reservations r
                        INNER JOIN rooms rm ON r.room_id = rm.room_id
                        INNER JOIN boarding_houses b ON rm.bh_id = b.bh_id
                        WHERE r.tenant_id = ? AND (r.status = 'pending' OR r.status = 'waiting' OR r.status = 'active' OR r.status = 'rejected')
                        ORDER BY r.reserved_at DESC";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $tenant_id);
                $stmt->execute();
                $result = $stmt->get_result();
            ?>
            <section class="reservations-section">
                <h2>My Reservations</h2>
                <?php
                    if ($result->num_rows === 0) {
                        echo "<p>No active reservations.</p>";
                    } else {
                        while ($row = $result->fetch_assoc()) {
                            ?>
                            <div class="reservation-card">
                                <div class="reservation-details">
                                    <h3><?= htmlspecialchars($row['bh_name']) ?></h3>
                                    <p>Room Type: <?= htmlspecialchars($row['room_type']) ?></p>
                                    <p>Reserved on: <?= htmlspecialchars($row['date']) ?></p>
                                    <p>Status: <span class="status-<?= strtolower($row['status']) ?>"><?= ucfirst(htmlspecialchars($row['status'])) ?></span></p>
                                </div>
                                <?php if ($row['status'] === 'pending'): ?>
                                    <form method="POST" action="process.php" onsubmit="return confirm('Are you sure you want to cancel this reservation?');">
                                        <input type="hidden" name="reservation_id" value="<?= $row['reservation_id'] ?>">
                                        <button type="submit" name="cancel_reservation" class="cancel-btn">Cancel</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <?php
                        }
                    }
                ?>
            </section>
        </main>

    </div>
</body>
</html>