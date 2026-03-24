<?php
// Start session
session_start();

// Database connection details
$servername = "sql207.infinityfree.com";
$username = "if0_39864294"; // Change this to your DB username
$password = "ddatra2025"; // Add your DB password
$dbname = "if0_39864294_instagram_db"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Prepare query to fetch user
    $sql = "SELECT id, name, password, email FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verify password with password_verify
        if (password_verify($password, $user['password'])) {
            // Store user info in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];

            // Set success message
            $_SESSION['success_message'] = "Login successful! Welcome to the TRA data management system.";
            
            // Redirect to dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            // Wrong password
            header("Location: index.php?error=Incorrect password");
            exit();
        }
    } else {
        // User not found
        header("Location: index.php?error=No account found with this email");
        exit();
    }
    
    $stmt->close();
}

$conn->close();
?>