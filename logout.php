<?php
session_start();

$reason = $_GET['reason'] ?? '';

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page with message
if ($reason === 'device_denied') {
    header("Location: index.php?error=Device access denied. Please contact administrator.");
} elseif ($reason === 'inactive') {
    header("Location: index.php?error=Session expired after 5 minutes of inactivity.");
} else {
    header("Location: index.php?success=Logged out successfully");
}
exit();
?>