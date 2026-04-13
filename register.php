<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CyBoard - Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    body {
      background: linear-gradient(135deg, #3c6e71, #5e9fa3);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      margin: 0;
      position: relative;
      overflow-x: hidden;
    }
    
    body::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.05" d="M0,96L60,133.3C120,171,240,245,360,245.3C480,245,600,171,720,154.7C840,139,960,181,1080,197.3C1200,213,1320,203,1380,197.3L1440,192L1440,320L1380,320C1320,320,1200,320,1080,320C960,320,840,320,720,320C600,320,480,320,360,320C240,320,120,320,60,320L0,320Z"></path></svg>') no-repeat bottom center;
      background-size: cover;
      z-index: -1;
    }
    
    .register-container {
      background: #fff;
      padding: 2.5rem;
      border-radius: 16px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
      max-width: 600px;
      width: 100%;
      margin: 2rem 1rem;
    }
    
    .register-header {
      text-align: center;
      margin-bottom: 2rem;
    }
    
    .register-header h3 {
      font-weight: 700;
      color: #3c6e71;
      font-size: 2.2rem;
      margin-bottom: 0.5rem;
      letter-spacing: 0.5px;
      position: relative;
      display: inline-block;
    }
    
    .register-header h3::after {
      content: '';
      position: absolute;
      bottom: -5px;
      left: 50%;
      transform: translateX(-50%);
      width: 40px;
      height: 3px;
      background: #5e9fa3;
      border-radius: 2px;
    }
    
    .register-header .subtitle {
      color: #666;
      font-size: 1.1rem;
      font-weight: 500;
      margin-top: 10px;
    }
    
    .form-floating {
      margin-bottom: 1.2rem;
    }
    
    .form-control {
      border-radius: 8px;
      border: 1px solid #e0e0e0;
      font-size: 0.95rem;
      padding: 0.75rem;
      transition: all 0.3s ease;
    }
    
    .form-control:focus {
      border-color: #3c6e71;
      box-shadow: 0 0 0 3px rgba(60, 110, 113, 0.2);
    }
    
    .form-floating > .form-control:focus ~ label,
    .form-floating > .form-control:not(:placeholder-shown) ~ label {
      opacity: 0.8;
      transform: scale(0.85) translateY(-0.75rem) translateX(0.15rem);
      color: #3c6e71;
      font-weight: 500;
    }
    
    .form-control[type="file"] {
      padding: 0.6rem;
      font-size: 0.9rem;
      cursor: pointer;
    }
    
    .file-upload-wrapper {
      position: relative;
      margin-bottom: 1.2rem;
    }
    
    .form-label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 500;
      color: #3c6e71;
    }
    
    .form-control[type="file"]::-webkit-file-upload-button {
      background-color: #3c6e71;
      color: white;
      border: none;
      border-radius: 6px;
      padding: 0.5rem 1rem;
      font-size: 0.9rem;
      cursor: pointer;
      transition: background-color 0.3s ease;
      margin-right: 1rem;
    }
    
    .form-control[type="file"]::-webkit-file-upload-button:hover {
      background-color: #2c5052;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    
    .form-control[type="file"]::-moz-file-upload-button {
      background-color: #3c6e71;
      color: white;
      border: none;
      border-radius: 6px;
      padding: 0.5rem 1rem;
      font-size: 0.9rem;
      cursor: pointer;
      transition: background-color 0.3s ease;
      margin-right: 1rem;
    }
    
    .form-control[type="file"]::-moz-file-upload-button:hover {
      background-color: #2c5052;
    }
    
    /* OTP Input Group Styles */
    .input-group {
      display: flex;
      margin-bottom: 1.2rem;
    }
    
    .input-group .form-floating {
      flex: 1;
      margin-bottom: 0;
    }
    
    .input-group .form-control {
      border-radius: 8px 0 0 8px;
      border-right: none;
    }
    
    .input-group .form-control:focus {
      border-right: none;
      z-index: 2;
    }
    
    /* OTP Send Button Styles */
    .btn-outline-primary {
      background-color: transparent;
      border: 1px solid #3c6e71;
      color: #3c6e71;
      border-radius: 0 8px 8px 0;
      font-weight: 500;
      padding: 0.75rem 1rem;
      font-size: 0.9rem;
      white-space: nowrap;
      transition: all 0.3s ease;
      min-width: 90px;
      border-left: none;
    }
    
    .btn-outline-primary:hover {
      background-color: #3c6e71;
      color: white;
      border-color: #3c6e71;
      box-shadow: 0 2px 5px rgba(60, 110, 113, 0.3);
    }
    
    .btn-outline-primary:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      background-color: #f8f9fa;
      color: #6c757d;
      border-color: #dee2e6;
    }
    
    .btn-register {
      background-color: #3c6e71;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      padding: 0.9rem;
      font-size: 1rem;
      letter-spacing: 0.5px;
      color: white;
      width: 100%;
      transition: all 0.3s ease;
      box-shadow: 0 4px 10px rgba(60, 110, 113, 0.3);
      margin-top: 0.8rem;
    }
    
    .btn-register:hover {
      background-color: #2c5052;
      box-shadow: 0 6px 12px rgba(60, 110, 113, 0.4);
      transform: translateY(-2px);
    }
    
    .btn-register:active {
      transform: translateY(0);
      box-shadow: 0 2px 6px rgba(60, 110, 113, 0.4);
    }
    .error-mssg {
      color: #e74c3c;
      font-size: 14px;
      margin-top: 5px;
      font-weight: 500;
      display: block;
      text-align: center;
    }
    .login-link {
      text-align: center;
      margin-top: 1.5rem;
    }
    
    .login-link a {
      color: #3c6e71;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s ease;
      position: relative;
    }
    
    .login-link a::after {
      content: '';
      position: absolute;
      width: 0;
      height: 2px;
      bottom: -2px;
      left: 0;
      background-color: #3c6e71;
      transition: width 0.3s ease;
    }
    
    .login-link a:hover::after {
      width: 100%;
    }
    
    .login-link a:hover {
      color: #2c5052;
    }
    
    .footer-text {
      text-align: center;
      margin-top: 2rem;
      color: #777;
      font-size: 0.85rem;
      font-weight: 400;
    }
    
    /* Enhanced Mobile Responsiveness */
    @media (max-width: 768px) {
      .register-container {
        padding: 2rem;
        margin: 1.5rem;
      }
    }
    
    @media (max-width: 576px) {
      .register-container {
        padding: 1.5rem;
        margin: 1rem;
      }
      
      .register-header h3 {
        font-size: 1.8rem;
      }
      
      .register-header .subtitle {
        font-size: 1rem;
      }
      
      .form-floating {
        margin-bottom: 1rem;
      }
      
      .col-md-6 {
        width: 100%;
      }
      
      .btn-register {
        padding: 0.8rem;
        font-size: 0.95rem;
      }
      
      .form-control[type="file"]::-webkit-file-upload-button {
        padding: 0.4rem 0.8rem;
        font-size: 0.85rem;
      }
      
      .form-control[type="file"]::-moz-file-upload-button {
        padding: 0.4rem 0.8rem;
        font-size: 0.85rem;
      }
      
      /* Mobile OTP Button Styles */
      .btn-outline-primary {
        padding: 0.65rem 0.8rem;
        font-size: 0.85rem;
        min-width: 75px;
      }
    }
    
    /* Extra small screens - Stack OTP elements vertically */
    @media (max-width: 480px) {
      .input-group {
        flex-direction: column;
      }
      
      .input-group .form-control {
        border-radius: 8px;
        border-right: 1px solid #e0e0e0;
        margin-bottom: 0.5rem;
      }
      
      .btn-outline-primary {
        border-radius: 8px;
        border-left: 1px solid #3c6e71;
        width: 100%;
        min-width: auto;
      }
    }
  </style>
</head>
<body>
  <div class="register-container container mt-5">
    <div class="register-header text-center mb-4">
      <h3>CyBoard</h3>
      <p class="subtitle">Create Account</p>
    </div>
    
    <div class="alert alert-warning text-center" role="alert">
        <i class="bi bi-exclamation-triangle text-danger"></i> Please use only your <strong>Phinma Account</strong>. <br>
        Your name cannot be changed later in the form.
    </div>

    <!-- Google Sign-In Button -->
    <div id="googleSection" class="text-center">
      <button id="googleSignIn" class="btn btn-outline-primary w-100 mb-3">
        <img src="https://www.svgrepo.com/show/355037/google.svg" width="20" class="me-2">
        Sign up with Google
      </button>
    </div>

    <!-- Registration Form -->
    <form id="regForm" action="process.php" method="POST" autocomplete="off" style="display: none;">
      <div class="row g-3">
        <!-- Hidden photo -->
        <input type="hidden" id="photoURL" name="photoURL">

        <div class="col-md-6">
          <div class="form-floating">
            <input type="text" class="form-control" id="fullName" name="fullName" placeholder="Full Name" required readonly>
            <label for="fullName">Full Name</label>
          </div>
        </div>

        <div class="col-md-6">
          <div class="form-floating">
            <input type="tel" class="form-control" id="id_no" name="id_no"
              placeholder="ID Number" pattern="[0-9]{2}-[0-9]{4}-[0-9]{6}"
              title="Format must be XX-XXXX-XXXXXX (e.g. 00-0000-000000)" required>
            <label for="id_no">ID Number</label>
          </div>
        </div>

        <!-- Year Level -->
        <div class="col-md-6">
          <div class="form-floating">
            <select class="form-select" id="yearLevel" name="yearLevel" required>
              <option value="" disabled selected>Select Year Level</option>
              <option value="1st Year">1st Year</option>
              <option value="2nd Year">2nd Year</option>
              <option value="3rd Year">3rd Year</option>
              <option value="4th Year">4th Year</option>
            </select>
            <label for="yearLevel">Year Level</label>
          </div>
        </div>

        <!-- Program -->
       <div class="col-md-6">
          <div class="form-floating">
            <select class="form-select" id="program" name="program" required>
              <option value="" disabled selected>Select Program</option>
              <option value="AB Psychology">AB Psychology</option>
              <option value="BS Pharmacy">BS Pharmacy</option>
              <option value="BEED">BEED</option>
              <option value="BEED English">BEED English</option>
              <option value="BEED Filipino">BEED Filipino</option>
              <option value="BS Special Needs Ed">BS Special Needs Ed</option>
              <option value="BS Mechanical Engineering">BS Mechanical Engineering</option>
              <option value="BS Marine Engineering">BS Marine Engineering</option>
              <option value="BSBA Marketing Management">BSBA Marketing Management</option>
              <option value="BSBA Financial Management">BSBA Financial Management</option>
              <option value="BS Nursing">BS Nursing</option>
              <option value="BS Information Technology">BS Information Technology</option>
              <option value="BS Civil Engineering">BS Civil Engineering</option>
              <option value="BS Criminology">BS Criminology</option>
              <option value="BS Business Administration">BS Business Administration</option>
              <option value="BS Hospitality Management<">BS Hospitality Management</option>
              <option value="BS Accountancy">BS Accountancy</option>
               <option value="BS Accountancy Info System">BS Accountancy Info System</option>
              <option value="BS Tourism Management">BS Tourism</option>
            </select>
            <label for="program">Program</label>
          </div>
        </div>

        <div class="col-md-6">
          <div class="form-floating">
            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
            <label for="password">Password</label>
          </div>
        </div>

        <div class="col-md-6">
          <div class="form-floating">
            <input type="password" class="form-control" id="confirmPassword" name="confirmPassword"
              placeholder="Confirm Password" required>
            <label for="confirmPassword">Confirm Password</label>
          </div>
        </div>

        <div class="col-md-6">
          <div class="form-floating">
            <input type="email" class="form-control" id="email" name="email"
              placeholder="Email address" required readonly>
            <label for="email">Email address</label>
          </div>
        </div>

        <div class="col-md-6">
          <div class="form-floating">
            <input type="number" class="form-control" id="phone" name="phone"
              placeholder="Phone Number" pattern="[0-9]{11}" required>
            <label for="phone">Phone Number</label>
          </div>
        </div>

        <div class="col-12 text-center">
          <button type="submit" id="submitBtn" name="register" class="btn btn-primary w-50">Register</button>
        </div>
      </div>
    </form>

    <div class="error-mssg text-center mt-3">
      <p class="mssg text-danger fw-semibold"></p>
    </div>

    <div class="login-link text-center mt-4" id="loginpage">
      <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>

    <div class="footer-text text-center mt-3">
      <p>© 2025 CyBoard: Boarding House Reservation System</p>
    </div>
  </div>

  <!-- Firebase SDK -->
  <script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>
  <script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-auth.js"></script>

  <script>
    const firebaseConfig = {
      apiKey: "AIzaSyAr1MJ6WQYTdgOhNII21tDab4YUNf01Dis",
    authDomain: "cyboard-aa598.firebaseapp.com",
    projectId: "cyboard-aa598",
    storageBucket: "cyboard-aa598.firebasestorage.app",
    messagingSenderId: "871433916041",
    appId: "1:871433916041:web:5bc2033e6ea0f9c5590058",
    measurementId: "G-6ZYLGVZ3P4"
    };
    firebase.initializeApp(firebaseConfig);
    const auth = firebase.auth();

    // Google Sign-In
    document.getElementById("googleSignIn").addEventListener("click", function () {
      const provider = new firebase.auth.GoogleAuthProvider();

      auth.signInWithPopup(provider)
        .then(result => {
          const user = result.user;
          const email = user.email;
          const photo = user.photoURL;
          const name = user.displayName;

          fetch("check_email.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "email=" + encodeURIComponent(email)
          })
          .then(res => res.json())
          .then(data => {
            if (data.exists) {
              document.getElementById("googleSection").style.display = "none";
              document.getElementById("regForm").style.display = "none";
              document.getElementById("loginpage").style.display = "none";
              document.querySelector(".mssg").innerHTML = `
                This Gmail is already registered.<br>
                <a href="login.php" class="btn btn-link">Go to Login</a>
              `;
            } else {
              document.getElementById("email").value = email;
              document.getElementById("fullName").value = name;
              document.getElementById("photoURL").value = photo;
              document.getElementById("googleSection").style.display = "none";
              document.getElementById("regForm").style.display = "block";
              document.querySelector(".mssg").textContent = "";
            }
          }).catch(err => alert("Error checking email: " + err.message));
        })
        .catch(err => alert("Error signing in: " + err.message));
    });

    // Password validation
    const pass = document.getElementById("password");
    const cpass = document.getElementById("confirmPassword");
    const message = document.querySelector(".mssg");
    const btn = document.getElementById("submitBtn");

    pass.addEventListener("input", checkPass);
    cpass.addEventListener("input", checkPass);

    function checkPass() {
      if (pass.value.length >= 8) {
        message.textContent = "";
        if (cpass.value.length > 0) {
          if (pass.value === cpass.value) {
            btn.disabled = false;
          } else {
            message.textContent = "Passwords do not match!";
            btn.disabled = true;
          }
        }
      } else {
        message.textContent = "Minimum of 8 characters!";
        btn.disabled = true;
      }
    }
  </script>
</body>

</html>