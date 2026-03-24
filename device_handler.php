<?php
session_start();

// Device handler for device recognition and approval
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'recognize') {
        // Mark device as recognized and approved (for testing)
        $_SESSION['device_recognized'] = true;
        $_SESSION['device_approved'] = true;
        $_SESSION['device_pending_approval'] = false;
        $_SESSION['device_ip'] = $_POST['ip'] ?? $_SERVER['REMOTE_ADDR'];
        $_SESSION['device_recognition_time'] = date('Y-m-d H:i:s');
        
        // Redirect back to dashboard
        header("Location: dashboard.php");
        exit;
    }
    elseif ($action === 'deny') {
        // Mark device as denied and logout
        $_SESSION['device_recognized'] = false;
        $_SESSION['device_approved'] = false;
        session_destroy();
        
        // Redirect to login page
        header("Location: index.php?error=Device access denied");
        exit;
    }
}

// For GET requests, just redirect to dashboard
header("Location: dashboard.php");
exit;
?>