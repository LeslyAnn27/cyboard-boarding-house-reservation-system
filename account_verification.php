<?php
include 'conn.php';
date_default_timezone_set('Asia/Manila');

$showOTPSection = true;
$masked_phone = '';
$landlord_id = 0;
$phone_number = '';
$expiredMessage = '';
$countdown_seconds = 0;
$showCooldown = false;
$hideOTPInput = false;
$cardTitle = 'Phone Verification';
$hasValidOTP = false;
$otpCountdownSeconds = 0;
$otpExpired = false;
$otpLimitReached = false; // For OTP limit
$otpLimitMessage = ''; // Message for OTP limit
$limitResetTimestamp = 0; // Timestamp for OTP limit countdown

if (!isset($_GET['token'])) {
    $expiredMessage = "Invalid request.Please check the link or contact <a href='mailto:cyboard.reservations@gmail.com'>cyboard.reservations@gmail.com</a>";
    $hideOTPInput = true;
    $cardTitle = 'Token Issue';
} else {
    $token = $_GET['token'];
    $hashedToken = hash('sha256', $token);
    $stmt = $conn->prepare("
        SELECT f.*, l.landlord_number, l.landlord_name, l.landlord_email 
        FROM file_access_tokens f 
        JOIN landlords l ON f.landlord_id = l.landlord_id 
        WHERE f.token = ? LIMIT 1
    ");
    $stmt->bind_param("s", $hashedToken);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
        $expiredMessage = "Invalid token. Please check the link or contact <a href='mailto:cyboard.reservations@gmail.com'>cyboard.reservations@gmail.com</a>";
        $hideOTPInput = true;
        $cardTitle = 'Token Issue';
    } else {
        $current_time = date('Y-m-d H:i:s');

        // Token expired
        if ($row['expires_at'] < $current_time) {
            $expiredMessage = "This token has expired or already been used.";
            $hideOTPInput = true;
            $cardTitle = 'Token Issue';
            $stmt = $conn->prepare("
                UPDATE file_access_tokens SET status = ? 
                WHERE landlord_id = ? 
                ORDER BY token_id DESC LIMIT 1
            ");
            $landlord_id = $row['landlord_id'];
            $status = 'expired';
            $stmt->bind_param("si", $status, $landlord_id);
            $stmt->execute();
            $stmt->close();
        } 
        // Already used
        elseif ($row['status'] == 'used') {
            $expiredMessage = "This token has already been used.";
            $hideOTPInput = true;
            $cardTitle = 'Token Issue';
        } 
        // Check OTP request limit and reset
        elseif ($row['otp_requests'] >= 3) {
            $reset_time = !empty($row['request_reset_at']) ? strtotime($row['request_reset_at']) : null;
            $current_timestamp = time();
            $one_day_seconds = 24 * 60 * 60; // 24 hours in seconds
            if ($reset_time && $current_timestamp < $reset_time) {
                $otpLimitReached = true;
                $otpLimitMessage = "You have reached the daily OTP request limit (3). Please wait <span id='limitCountdown'></span> to request again.";
                $limitResetTimestamp = $reset_time;
                $hideOTPInput = true;
                $cardTitle = 'OTP Limit Reached';
            } else {
                // Reset OTP request count after 24 hours
                $new_reset_time = date('Y-m-d H:i:s', $current_timestamp + $one_day_seconds);
                $stmt = $conn->prepare("
                    UPDATE file_access_tokens 
                    SET otp_requests = 0, request_reset_at = ? 
                    WHERE landlord_id = ? 
                    ORDER BY token_id DESC LIMIT 1
                ");
                $stmt->bind_param("si", $new_reset_time, $row['landlord_id']);
                $stmt->execute();
                $stmt->close();
            }
        }
        // Cooldown after 3 failed attempts
        elseif (!empty($row['used_at'])) {
            $cooldown_seconds = 3600; // 1 hour
            $used_time = strtotime($row['used_at']);
            $time_left = ($used_time + $cooldown_seconds) - time();
            if ($time_left > 0) {
                $showCooldown = true;
                $countdown_seconds = $time_left;
                $cardTitle = 'Phone Verification';
            } else {
                // Cooldown over
                $landlord_id = $row['landlord_id'];
                $phone_number = $row['landlord_number'];
                $masked_phone = "09** *** " . substr($phone_number, -4);
                // Reset attempts after cooldown
                $stmt = $conn->prepare("
                    UPDATE file_access_tokens 
                    SET attempts = 0, used_at = NULL 
                    WHERE landlord_id = ? 
                    ORDER BY token_id DESC LIMIT 1
                ");
                $stmt->bind_param("i", $landlord_id);
                $stmt->execute();
                $stmt->close();
            }
        } 
        // Normal OTP flow
        else {
            $landlord_id = $row['landlord_id'];
            $phone_number = $row['landlord_number'];
            $masked_phone = "09** *** " . substr($phone_number, -4);
        }

        // Check for valid OTP after all token validations
        if (isset($row) && !$hideOTPInput && !$showCooldown && !$otpLimitReached) {
            if (!empty($row['otp_expires_at'])) {
                if ($row['otp_expires_at'] > $current_time) {
                    $hasValidOTP = true;
                    $otpCountdownSeconds = strtotime($row['otp_expires_at']) - time();
                } else {
                    $otpExpired = true;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $cardTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <style>
    body {
        background: linear-gradient(135deg, #3c6e71, #5e9fa3);
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .card {
        background: #fff;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        max-width: 400px;
        width: 100%;
        text-align: center;
    }

    .btn-primary {
        background-color: #3c6e71;
        border: none;
        border-radius: 5px;
        font-weight: 500;
        padding: 0.75rem;
        width: 100%;
    }

    .btn-primary:hover {
        background-color: #2c5052;
    }

    .btn-primary:disabled {
        background-color: #6c757d;
        cursor: not-allowed;
    }

    .btn-secondary {
        border-radius: 5px;
        font-weight: 500;
        padding: 0.75rem;
        width: 100%;
    }

    .btn-secondary:disabled {
        background-color: #6c757d;
        border-color: #6c757d;
        cursor: not-allowed;
    }

    .countdown {
        font-weight: bold;
        color: #d35400;
    }

    .error-message {
        color: #e74c3c;
        margin-bottom: 1rem;
    }

    .text-danger {
        color: #e74c3c !important;
    }

    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
        body {
            padding: 0.5rem;
        }

        .card {
            padding: 2.5rem 2rem;
            max-width: 100%;
        }

        .card h2, .card h3, .card h4 {
            font-size: 1.75rem;
            margin-bottom: 1.25rem;
        }

        .card p {
            font-size: 1.15rem;
            line-height: 1.6;
        }

        .btn-primary,
        .btn-secondary {
            padding: 1rem 1.25rem;
            font-size: 1.15rem;
            min-height: 52px;
        }

        .form-control {
            font-size: 1.15rem;
            padding: 1rem;
            min-height: 52px;
        }

        .error-message {
            margin-bottom: 1.5rem;
        }

        .error-message i {
            font-size: 3rem !important;
            margin-bottom: 0.75rem;
        }

        .error-message p {
            font-size: 1.15rem;
        }

        .countdown {
            font-size: 1.15rem;
            font-weight: bold;
        }

        .text-success {
            font-size: 4rem !important;
        }

        .mt-2 {
            margin-top: 1rem !important;
        }

        .mt-3 {
            margin-top: 1.5rem !important;
        }

        .mb-2 {
            margin-bottom: 1rem !important;
        }

        .mb-3 {
            margin-bottom: 1.5rem !important;
        }

        #passwordSection h4 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        #successSection h4 {
            font-size: 1.5rem;
        }
    }

    @media (max-width: 480px) {
        body {
            padding: 0.5rem;
        }

        .card {
            padding: 2rem 1.5rem;
        }

        .card h2, .card h3, .card h4 {
            font-size: 1.6rem;
        }

        .card p {
            font-size: 1.1rem;
        }

        .btn-primary,
        .btn-secondary {
            padding: 0.95rem 1rem;
            font-size: 1.1rem;
        }

        .form-control {
            font-size: 1.1rem;
            padding: 0.95rem;
        }

        .error-message i {
            font-size: 2.75rem !important;
        }

        .countdown {
            font-size: 1.1rem;
        }

        .text-success {
            font-size: 3.5rem !important;
        }
    }
</style>
</head>
<body>
    <div class="card">
        <h3><?= $cardTitle; ?></h3>
        <?php if ($expiredMessage): ?>
            <div class="error-message">
                <i class="fa-solid fa-circle-exclamation" style="font-size: 2rem; color: #e74c3c;"></i>
                <p><?= $expiredMessage; ?></p>
            </div>
        <?php elseif ($otpLimitReached): ?>
            <div class="error-message">
                <i class="fa-solid fa-circle-exclamation" style="font-size: 2rem; color: #e74c3c;"></i>
                <p id="limitMessage"><?= $otpLimitMessage; ?></p>
                <span id="limitTimestamp" data-time="<?= $limitResetTimestamp ?>" style="display: none;"></span>
            </div>
        <?php endif; ?>
        <div id="messageSection">
            <?php if ($showCooldown): ?>
                <p id="cooldownMessage">You have exceeded OTP attempts. Please wait <span id="countdown"></span> before trying again.</p>
                <span id="cooldownTimestamp" data-time="<?= time() + $countdown_seconds ?>" style="display: none;"></span>
            <?php elseif (!$hideOTPInput && !$otpLimitReached): ?>
                <p id="otpMessage">We'll send a 6-digit code to <b><?= $masked_phone ?: 'your phone number'; ?></b>.</p>
                <?php if ($hasValidOTP): ?>
                    <p class="countdown mt-2" id="otpExpireMessage">Code expires in <span id="otpCountdown"></span></p>
                    <span id="otpTimestamp" data-time="<?= time() + $otpCountdownSeconds ?>" style="display: none;"></span>
                <?php elseif ($otpExpired): ?>
                    <p class="text-danger mt-2" id="otpExpireMessage">Code has expired</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <div id="otpInputSection" style="display:<?= ($showCooldown || $hideOTPInput || !$hasValidOTP || $otpLimitReached) ? 'none' : 'block'; ?>;">
            <input type="text" id="otpCode" class="form-control mb-3 text-center" maxlength="6" placeholder="Enter OTP code" required>
            <button class="btn btn-primary" onclick="verifyOTP()">Verify</button>
            <p class="mt-2 countdown" id="countdownText"></p>
        </div>
        <button id="resendBtn" class="btn btn-secondary mt-2" style="display:<?= ($otpExpired && !$showCooldown && !$hideOTPInput && !$otpLimitReached) ? 'block' : 'none'; ?>;" onclick="sendOTP('resendBtn')">Resend OTP</button>
        <button id="sendOtpBtn" class="btn btn-primary mb-3" style="display:<?= (!$hasValidOTP && !$otpExpired && !$showCooldown && !$hideOTPInput && !$otpLimitReached) ? 'block' : 'none'; ?>;" onclick="sendOTP('sendOtpBtn')">Send OTP</button>
        <div id="passwordSection" style="display:none;">
            <h4 class="mt-3">CyBoard Reservations – Securely set your password to access your account.</h4>
            <input type="password" id="newPass" class="form-control mb-2" placeholder="Enter new password">
            <input type="password" id="confirmPass" class="form-control mb-3" placeholder="Confirm new password">
            <button class="btn btn-primary" onclick="setPassword()">Set Password</button>
        </div>
        <div id="successSection" style="display:none;">
            <div class="text-success" style="font-size:3rem;">✔</div>
            <h4>Password Set!</h4>
            <a href="login.php" class="btn btn-primary mt-3">Go to Login</a>
        </div>
    </div>

    <script>
        let otpInterval = null;
        let cooldownInterval = null;
        let limitInterval = null;

        function startOTPCountdown() {
            const timestampEl = document.getElementById('otpTimestamp');
            if (!timestampEl) return;

            const endTime = parseInt(timestampEl.dataset.time);
            const countdownSpan = document.getElementById('otpCountdown');
            const expireP = document.getElementById('otpExpireMessage');
            const resendBtn = document.getElementById('resendBtn');
            const sendBtn = document.getElementById('sendOtpBtn');
            const otpInputSection = document.getElementById('otpInputSection');

            if (!countdownSpan || !expireP) return;

            if (otpInterval) clearInterval(otpInterval);

            otpInterval = setInterval(() => {
                const now = Math.floor(Date.now() / 1000);
                let diff = endTime - now;
                if (diff <= 0) {
                    clearInterval(otpInterval);
                    otpInterval = null;
                    expireP.innerHTML = '<span class="text-danger">Code has expired</span>';
                    otpInputSection.style.display = 'none';
                    if (resendBtn) resendBtn.style.display = 'block';
                    if (sendBtn) sendBtn.style.display = 'none';
                } else {
                    const min = Math.floor(diff / 60);
                    const sec = diff % 60;
                    countdownSpan.innerText = `${min.toString().padStart(2, '0')}:${sec.toString().padStart(2, '0')}`;
                }
            }, 1000);
        }

        function startCooldownCountdown() {
            const cooldownEl = document.getElementById('cooldownTimestamp');
            if (!cooldownEl) return;

            let cooldownEnd = parseInt(cooldownEl.dataset.time);
            const sendBtn = document.getElementById('sendOtpBtn');
            const resendBtn = document.getElementById('resendBtn');
            const countdownText = document.getElementById('countdown');
            const messageSection = document.getElementById('messageSection');
            const cardTitleEl = document.querySelector('.card h3');

            if (cooldownInterval) clearInterval(cooldownInterval);

            cooldownInterval = setInterval(() => {
                let now = Math.floor(Date.now() / 1000);
                let diff = cooldownEnd - now;
                if (diff <= 0) {
                    clearInterval(cooldownInterval);
                    cooldownInterval = null;
                    countdownText.innerText = '';
                    sendBtn.style.display = 'block';
                    sendBtn.disabled = false;
                    messageSection.innerHTML = `<p id="otpMessage">We'll send a 6-digit code to <b><?= $masked_phone ?: 'your phone number'; ?></b>.</p>`;
                    cardTitleEl.innerText = 'Phone Verification';
                } else {
                    let min = Math.floor(diff / 60);
                    let sec = diff % 60;
                    countdownText.innerText = `${min}:${sec < 10 ? '0' + sec : sec}`;
                }
            }, 1000);
        }

        function startLimitCountdown() {
            const limitEl = document.getElementById('limitTimestamp');
            if (!limitEl) return;

            const endTime = parseInt(limitEl.dataset.time);
            const countdownSpan = document.getElementById('limitCountdown');
            const limitP = document.getElementById('limitMessage');
            const sendBtn = document.getElementById('sendOtpBtn');
            const resendBtn = document.getElementById('resendBtn');
            const cardTitleEl = document.querySelector('.card h3');
            const messageSection = document.getElementById('messageSection');

            if (!countdownSpan || !limitP) return;

            if (limitInterval) clearInterval(limitInterval);

            limitInterval = setInterval(() => {
                const now = Math.floor(Date.now() / 1000);
                let diff = endTime - now;
                if (diff <= 0) {
                    clearInterval(limitInterval);
                    limitInterval = null;
                    // Refresh page to reset UI after limit expires
                    window.location.reload();
                } else {
                    const hours = Math.floor(diff / 3600);
                    const min = Math.floor((diff % 3600) / 60);
                    const sec = diff % 60;
                    countdownSpan.innerText = `${hours.toString().padStart(2, '0')}:${min.toString().padStart(2, '0')}:${sec.toString().padStart(2, '0')}`;
                }
            }, 1000);
        }

        function sendOTP(buttonId) {
            const button = document.getElementById(buttonId);
            if (!button) return;

            const originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = 'Sending...';

            fetch('send_otp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ landlord_id: <?= $landlord_id ?> })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Update timestamp
                    let timestampEl = document.getElementById('otpTimestamp');
                    if (!timestampEl) {
                        timestampEl = document.createElement('span');
                        timestampEl.id = 'otpTimestamp';
                        timestampEl.style.display = 'none';
                        document.getElementById('messageSection').appendChild(timestampEl);
                    }
                    timestampEl.dataset.time = data.otp_expires_unix;

                    // Update message section
                    let expireP = document.getElementById('otpExpireMessage');
                    if (!expireP) {
                        expireP = document.createElement('p');
                        expireP.id = 'otpExpireMessage';
                        expireP.className = 'countdown mt-2';
                        document.getElementById('messageSection').appendChild(expireP);
                    }
                    expireP.innerHTML = 'Code expires in <span id="otpCountdown">05:00</span>';

                    // Show OTP input, hide buttons
                    document.getElementById('otpInputSection').style.display = 'block';
                    document.getElementById('resendBtn').style.display = 'none';
                    document.getElementById('sendOtpBtn').style.display = 'none';
                    document.getElementById('otpCode').value = '';

                    // Start countdown
                    startOTPCountdown();
                } else {
                    alert(data.message);
                    button.disabled = false;
                    button.innerHTML = originalText;
                }
            })
            .catch(error => {
                alert('Error sending OTP: ' + error.message);
                button.disabled = false;
                button.innerHTML = originalText;
            });
        }

        function verifyOTP() {
            const otpCode = document.getElementById('otpCode').value;
            if (!otpCode || otpCode.length !== 6) {
                alert("Please enter a valid 6-digit OTP code");
                return;
            }

            const verifyBtn = document.querySelector('#otpInputSection .btn-primary');
            verifyBtn.disabled = true;
            verifyBtn.innerHTML = 'Verifying...';

            fetch('verify_otp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ landlord_id: <?= $landlord_id ?>, otp: otpCode })
            })
            .then(res => res.json())
            .then(data => {
                verifyBtn.disabled = false;
                verifyBtn.innerHTML = 'Verify';
                if (data.success) {
                    // Clear OTP countdown
                    if (otpInterval) {
                        clearInterval(otpInterval);
                        otpInterval = null;
                    }
                    const expireP = document.getElementById('otpExpireMessage');
                    if (expireP) {
                        expireP.remove();
                    }
                    const timestampEl = document.getElementById('otpTimestamp');
                    if (timestampEl) {
                        timestampEl.remove();
                    }
                    // Proceed to password section
                    document.getElementById('otpInputSection').style.display = 'none';
                    document.getElementById('passwordSection').style.display = 'block';
                } else {
                    alert(data.message);
                    if (data.showCooldown) {
                        if (otpInterval) {
                            clearInterval(otpInterval);
                            otpInterval = null;
                        }
                        document.getElementById('otpInputSection').style.display = 'none';
                        document.getElementById('resendBtn').style.display = 'none';
                        document.getElementById('sendOtpBtn').style.display = 'none';
                        const messageSection = document.getElementById('messageSection');
                        messageSection.innerHTML = `
                            <p id="cooldownMessage">You have exceeded OTP attempts. Please wait <span id="countdown"></span> before trying again.</p>
                            <span id="cooldownTimestamp" data-time="${data.cooldown_end}" style="display: none;"></span>
                        `;
                        startCooldownCountdown();
                    }
                }
            })
            .catch(error => {
                alert('Error verifying OTP: ' + error.message);
                verifyBtn.disabled = false;
                verifyBtn.innerHTML = 'Verify';
            });
        }

        function setPassword() {
            const newPass = document.getElementById('newPass').value;
            const confirmPass = document.getElementById('confirmPass').value;
            if (!newPass || !confirmPass) {
                alert("Please fill in all fields");
                return;
            }
            if (newPass !== confirmPass) {
                alert("Passwords do not match");
                return;
            }
            if (newPass.length < 8) {
                alert("Password must be at least 8 characters long");
                return;
            }

            const setPassBtn = document.querySelector('#passwordSection .btn-primary');
            setPassBtn.disabled = true;
            setPassBtn.innerHTML = 'Setting Password...';

            fetch('set_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ landlord_id: <?= $landlord_id ?>, new_password: newPass })
            })
            .then(res => res.json())
            .then(data => {
                setPassBtn.disabled = false;
                setPassBtn.innerHTML = 'Set Password';
                if (data.success) {
                    document.getElementById('passwordSection').style.display = 'none';
                    document.getElementById('successSection').style.display = 'block';
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                alert('Error setting password: ' + error.message);
                setPassBtn.disabled = false;
                setPassBtn.innerHTML = 'Set Password';
            });
        }

        // Start OTP countdown if valid OTP exists on load
        if (document.getElementById('otpTimestamp')) {
            startOTPCountdown();
        }

        // Start cooldown countdown for failed attempts
        if (document.getElementById('cooldownTimestamp')) {
            startCooldownCountdown();
        }

        // Start limit countdown for OTP request limit
        if (document.getElementById('limitTimestamp')) {
            startLimitCountdown();
        }
    </script>
</body>
</html>