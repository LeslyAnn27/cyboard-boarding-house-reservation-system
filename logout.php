<?php
session_start();
session_unset();
session_destroy();

// Clear session cookie
setcookie(session_name(), '', time() - 3600, '/');

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to login
header("Location: login.php");
exit;
?>