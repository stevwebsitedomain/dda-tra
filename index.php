<?php
// Start session
session_start();

// Redirect kama tayari ameshaingia
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard_home.php");
    exit();
}

// Database connection details
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "tra-infinity-free-data"; 

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    $sql = "SELECT id, name, password, email FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['success_message'] = "Login successful! Welcome to the TRA data management system.";
            
            header("Location: dashboard_home.php");
            exit();
        } else {
            header("Location: index.php?error=Incorrect password");
            exit();
        }
    } else {
        header("Location: index.php?error=No account found with this email");
        exit();
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TRA Data Management System</title>
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/responsive-layout.css" rel="stylesheet">
        <!-- Favicon (Logo kwenye browser tab) -->
    <link rel="icon" href="dda.jpg" type="image/jpeg">
    <link rel="shortcut icon" href="dda.jpg" type="image/jpeg">
    <link rel="apple-touch-icon" href="dda.jpg">
    
    <style>
        :root {
            --tra-navy: #0b1e3b;
            --tra-gold: #c5a059;
            --bg-color: #f4f7fa;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background-color: var(--bg-color);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px 14px;
            -webkit-text-size-adjust: 100%;
        }

        .login-card {
            width: 100%;
            max-width: 400px;
            background: #ffffff;
            /* Kuondoa border radius */
            border-radius: 0 !important; 
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            /* Mstari wa dhahabu juu */
            border-top: 5px solid var(--tra-gold); 
            padding: 40px 30px;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-container img {
            width: 100px; /* Saizi ya logo */
            height: auto;
            object-fit: contain;
        }

        .system-title {
            text-align: center;
            color: var(--tra-navy);
            font-weight: 700;
            font-size: 18px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .system-subtitle {
            text-align: center;
            color: #777;
            font-size: 13px;
            margin-bottom: 30px;
        }

        .form-group label {
            font-weight: 600;
            color: var(--tra-navy);
            font-size: 14px;
        }

        .form-control {
            border-radius: 0; /* Square inputs */
            height: 45px;
            border: 1px solid #ddd;
        }

        .form-control:focus {
            border-color: var(--tra-gold);
            box-shadow: none;
        }

        .btn-login {
            background-color: var(--tra-navy);
            color: white;
            border: none;
            border-radius: 0; /* Square button */
            height: 45px;
            font-weight: 700;
            width: 100%;
            margin-top: 10px;
            transition: 0.3s;
        }

        .btn-login:hover {
            background-color: #162e55;
            color: white;
            box-shadow: 0 4px 12px rgba(11, 30, 59, 0.2);
        }

        .error-msg {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            font-size: 13px;
            text-align: center;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }

        .footer-text {
            text-align: center;
            margin-top: 25px;
            font-size: 12px;
            color: #999;
        }

        @media (max-width: 575px) {
            body { align-items: flex-start; padding-top: 24px; }
            .login-card {
                max-width: 100%;
                padding: 26px 18px;
            }
            .system-title { font-size: 16px; }
            .system-subtitle { font-size: 12px; margin-bottom: 20px; }
            .logo-container { margin-bottom: 20px; }
            .logo-container img { width: 84px; }
            .form-control,
            .btn-login {
                height: 44px;
            }
        }
    </style>
</head>
<body>

    <div class="login-card">
        <!-- Logo Katikati -->
        <div class="logo-container">
            <img src="dda.jpg" alt="TRA LOGO" onerror="this.src='https://via.placeholder.com/100?text=TRA'">
        </div>

        <div class="system-title">DDA KINONDONI Login</div>
        <div class="system-subtitle">Data Management & Analytics System</div>

        <!-- Error Handling -->
        <?php if (isset($_GET['error'])): ?>
            <div class="error-msg">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <form action="index.php" method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-white border-right-0" style="border-radius:0;"><i class="fas fa-envelope text-muted"></i></span>
                    </div>
                    <input type="email" name="email" id="email" class="form-control border-left-0" placeholder="admin@dda.go.tz" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-white border-right-0" style="border-radius:0;"><i class="fas fa-lock text-muted"></i></span>
                    </div>
                    <input type="password" name="password" id="password" class="form-control border-left-0" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn btn-login">
                SIGN IN <i class="fas fa-sign-in-alt ml-2"></i>
            </button>
        </form>

        <div class="footer-text">
            &copy; <?php echo date('Y'); ?> Tanzania Revenue Authority. All rights reserved.<br>
            Secure Institutional Access Only.
        </div>
    </div>

    <script src="js/code_protection.js?v=<?php echo time(); ?>"></script>
</body>
</html>