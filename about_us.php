<?php
require 'conn.php';
session_start();

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
    <title>CyBoard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <!-- font awesome kit -->
    <script src="https://kit.fontawesome.com/14901788bc.js" crossorigin="anonymous"></script>
    <!-- leaflet css -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <!-- Make sure you put this AFTER Leaflet's CSS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <link rel="stylesheet" href="css/index.css">
    <style>
/* ABOUT US PAGE */
.about-section {
    display: flex;
    justify-content: center;
    padding: 40px 20px;
}

.about-card {
    background: #ffffff;
    padding: 40px;
    max-width: 900px;
    width: 100%;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    border-left: 6px solid #3c6e71; /* teal accent */
}

.about-title {
    font-size: 2rem;
    margin-bottom: 20px;
    color: #3c6e71;
    font-weight: bold;
}

.about-text {
    margin-bottom: 18px;
    line-height: 1.7rem;
    font-size: 1rem;
    color: #444;
}

.about-features {
    margin-top: 25px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.feat-box {
    background: #f4f8f7;
    padding: 15px;
    border-radius: 10px;
    border: 1px solid #d9e6e4;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 500;
}

.feat-box i {
    color: #3c6e71;
    font-size: 1.2rem;
}

    </style>
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
                            <span><?php echo $first_name; ?></span>
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
<section class="about-section">
    <div class="about-card">
        <h2 class="about-title">About CyBoard</h2>

        <p class="about-text">
            CyBoard is an online boarding house reservation system designed to help
            students find safe, affordable, and verified boarding houses.
            Our goal is to provide a faster and more organized way of searching,
            viewing, and reserving rooms — all in one platform.
        </p>

        <p class="about-text">
            We aim to support both landlords and students by offering a simple and
            transparent reservation process. Landlords can manage rooms easily, while
            students can view detailed information, room rates, amenities, and availability
            in real-time.
        </p>

        <div class="about-features">
            <div class="feat-box">
                <i class="fas fa-map-marker-alt"></i>
                Find Boarding Houses Near Campus
            </div>
            <div class="feat-box">
                <i class="fas fa-bed"></i>
                Real-time Room Availability
            </div>
            <div class="feat-box">
                <i class="fas fa-handshake"></i>
                Hassle-free Reservations
            </div>
        </div>
    </div>
</section>



    <div class="footer-text" style="text-align: center;">
                <p>© 2025 CyBoard: Boarding House Reservation System</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.min.js" integrity="sha384-RuyvpeZCxMJCqVUGFI0Do1mQrods/hhxYlcVfGPOfQtPJh0JCw12tUAZ/Mv10S7D" crossorigin="anonymous"></script>
    <script>
        function toggleMenu() {
            const nav = document.querySelector('nav ul');
            nav.classList.toggle('active');
        }
    </script>
</body>
</html>