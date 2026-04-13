<?php
session_start();
require_once "conn.php";


// Force HTTPS
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit();
}



// Redirect if already logged in
if (isset($_SESSION['tenant_id'])) {
    header("Location: index.php");
    exit();
}
if (isset($_SESSION['landlord_id'])) {
    header("Location: admin.php");
    exit();
}
if (isset($_SESSION['superadmin_id'])) {
    header("Location: superadmin.php");
    exit();
}
if(isset($_POST['login'])){
    $email = strtolower(trim($_POST['email']));
    $password = $_POST['password'];

    //Check Superadmin
    $stmt = $conn->prepare("SELECT superadmin_id, password FROM superadmin WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $row = $result->fetch_assoc();
        if(password_verify($password, $row['password'])){
            $_SESSION['superadmin_id'] = $row['superadmin_id'];
            header("Location: superadmin.php");
            exit;
        }
    }

    //Check Landlord
    $stmt = $conn->prepare("SELECT landlord_id, landlord_password, password_set FROM landlords WHERE landlord_email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $row = $result->fetch_assoc();
        if($row['password_set'] == 0){
            $_SESSION['error'] = 'Please set your password first.';
            header("Location: login.php");
            exit;
        }
        if(password_verify($password, $row['landlord_password'])){
            $_SESSION['landlord_id'] = $row['landlord_id'];
            header("Location: admin.php");
            exit;
        }
    }

    //Check Tenant
    $stmt = $conn->prepare("SELECT tenant_id, password FROM tenants WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $row = $result->fetch_assoc();
        if(password_verify($password, $row['password'])){
            $_SESSION['tenant_id'] = $row['tenant_id'];
            $_SESSION['logged_in'] = true;
            header("Location: index.php");
            exit;
        }
    }

    // Not registered or wrong password
    $_SESSION['error'] = 'Invalid email or password.';
    header("Location: login.php");
    exit;
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CyBoard - Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/login.css">
</head>
<body>

<?php
if(isset($_SESSION['error'])){
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            var toastElement = document.getElementById('invalidPasswordToast');
            var toast = new bootstrap.Toast(toastElement);
            toast.show();
        });
        </script>";
    unset($_SESSION['error']);
}
?>

<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="invalidPasswordToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-danger text-white">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong class="me-auto">Login Error</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
            <strong>Login Failed!</strong><br>
            The credentials you entered are incorrect. Please try again.
        </div>
    </div>
</div>

<div class="login-container">
    <div class="login-header">
        <h3>CyBoard</h3>
        <p class="slogan">Find the Best Boarding Houses. Reserve Your Room Easily Online!</p>
        <p class="call-to-action">Check available rooms now</p>
    </div>

    <form id="loginForm" method="POST">
        <!-- Google Login -->
        <div id="googleSection" class="text-center mb-3">
            <button type="button" id="googleSignIn" class="btn btn-outline-primary w-100">
                <img src="https://www.svgrepo.com/show/355037/google.svg" width="20" class="me-2">
                Sign in with Google
            </button>
        </div>

        <!-- Traditional Login -->
        <div id="traditionalFields">
            <div class="mb-3 form-floating">
                <input type="email" class="form-control" id="email" name="email" placeholder="Email">
                <label for="email">Email address</label>
            </div>
            <div class="mb-3 form-floating">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password">
                <label for="password">Password</label>
            </div>
            <button type="submit" name="login" class="btn btn-primary">Login</button>
        </div>
    </form>

    <div class="register-link">
        <p>Don't have an account? <a href="register.php">Register here</a></p>
    </div>
    <div class="footer-text">
        <p>© 2025 CyBoard: Boarding House Reservation System</p>
    </div>
</div>

<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-auth.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>

<script>
const tenantRadio = document.getElementById('tenant');
const landlordRadio = document.getElementById('landlord');
const googleSection = document.getElementById('googleSection');
const traditionalFields = document.getElementById('traditionalFields');

// Firebase Config
const firebaseConfig = {
    apiKey: "YOUR_API_KEY",
    authDomain: "YOUR_AUTH_DOMAIN",
    projectId: "YOUR_PROJECT_ID",
    storageBucket: "YOUR_STORAGE_BUCKET",
    messagingSenderId: "YOUR_MESSAGING_SENDER_ID",
    appId: "YOUR_APP_ID",
    measurementId: "YOUR_MEASUREMENT_ID"
};
firebase.initializeApp(firebaseConfig);
const auth = firebase.auth();

// Google Login
document.getElementById("googleSignIn").addEventListener("click", function() {
    const provider = new firebase.auth.GoogleAuthProvider();
    auth.signInWithPopup(provider)
    .then((result) => {
        const user = result.user;
        const email = user.email;

        fetch("google_login.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "email=" + encodeURIComponent(email)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location = data.redirect;
            } else {
                //password not set or not registered
                alert(data.message || "Login failed. Please try again.");
            }
        });
    })
    .catch(err => alert("Error signing in: " + err.message));
});

</script>

</body>
</html>
