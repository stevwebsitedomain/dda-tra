<?php
// Kuanzisha kikao na kuangalia kama mtumiaji ameingia
session_start();

// Ikiwa mtumiaji hajaingia, mpeleke kwenye ukurasa wa kuingia
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Sorry Login First");
    exit();
}

// Pata taarifa za mtumiaji kutoka kwenye kikao
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

// Pata data ya result kutoka kwenye parameters
$username = isset($_GET['username']) ? htmlspecialchars($_GET['username']) : 'N/A';
$phone = isset($_GET['phone']) ? htmlspecialchars($_GET['phone']) : 'N/A';
$bio = isset($_GET['bio']) ? htmlspecialchars($_GET['bio']) : 'N/A';
$hashtag = isset($_GET['hashtag']) ? htmlspecialchars($_GET['hashtag']) : 'N/A';
$engagement = isset($_GET['engagement']) ? htmlspecialchars($_GET['engagement']) : '0';

// Weka rangi ya engagement percentage
$engagement_color = 'percentage-low';
if ($engagement > 70) {
    $engagement_color = 'percentage-high';
} elseif ($engagement > 40) {
    $engagement_color = 'percentage-medium';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DDA-TRA - View Result</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🏦</text></svg>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Roboto', 'Google Sans', sans-serif;
        }

        body {
            background-color: #fcfaf8;
            color: #3C1E03;
            overflow-x: hidden;
        }

        /* Mobile Header */
        .mobile-header {
            display: none;
            background-color: #000;
            color: white;
            padding: 12px 15px;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .mobile-header .logo-text {
            font-size: 18px;
            font-weight: 700;
            color: white;
        }

        .menu-toggle {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
        }

        /* Sidebar Styles */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 998;
        }

        .sidebar {
            width: 220px;
            background-color: #000;
            color: #fff;
            padding: 15px 0;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            overflow-y: auto;
            position: fixed;
            height: 100vh;
            z-index: 999;
            left: 0;
            top: 0;
        }

        .profile-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0 15px 15px;
            border-bottom: 1px solid #333;
            margin-bottom: 15px;
        }

        .profile-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            margin-bottom: 12px;
            border: 3px solid rgba(211, 225, 55, 1);
        }

        .profile-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .logo-text {
            font-size: 18px;
            font-weight: 700;
            color: white;
            text-align: center;
            margin-bottom: 5px;
        }

        .logo-subtext {
            font-size: 11px;
            color: #aaa;
            text-align: center;
        }

        .nav {
            list-style: none;
            padding: 0 10px;
            width: 100%;
        }

        .nav-item {
            margin-bottom: 5px;
            position: relative;
            width: 100%;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 10px 12px;
            color: #ddd;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s;
            width: 100%;
            justify-content: space-between;
        }

        .nav-link:hover, .nav-link.active {
            background-color: #333;
            color: #fff;
        }

        .nav-link i {
            margin-right: 10px;
            font-size: 16px;
            width: 20px;
            text-align: center;
        }

        .link-text {
            flex: 1;
            font-size: 14px;
        }

        /* Main Content Styles */
        .main-content {
            margin-left: 220px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #fcfaf8;
        }

        .header {
            background-color: #fff;
            padding: 12px 20px;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: 20px;
            font-weight: 700;
            color: #3C1E03;
            text-transform: capitalize;
        }

        .page-title span {
            background: linear-gradient(135deg, #833AB4, #E1306C);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #ddd;
            margin-right: 8px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(235, 254, 24, 1), rgba(235, 254, 24, 0.7));
        }

        .user-img i {
            color: white;
            font-size: 18px;
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            font-size: 14px;
            color: #3C1E03;
        }

        .user-role {
            font-size: 11px;
            color: #777;
        }

        /* Content Area */
        .content {
            padding: 20px;
            flex: 1;
            overflow-y: auto;
            background-color: #fcfaf8;
        }

        .card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #CFBE9D;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #3C1E03;
            display: flex;
            align-items: center;
        }

        .card-title i {
            margin-right: 8px;
            color: #E1306C;
        }

        .result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .result-title {
            font-size: 22px;
            font-weight: 700;
            color: #3C1E03;
        }

        .engagement-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .percentage-high {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .percentage-medium {
            background-color: #fff3e0;
            color: #f57c00;
        }

        .percentage-low {
            background-color: #ffebee;
            color: #c62828;
        }

        .info-group {
            margin-bottom: 20px;
        }

        .info-label {
            font-size: 14px;
            color: #777;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 16px;
            color: #3C1E03;
            font-weight: 500;
        }

        .hashtag-badge {
            display: inline-block;
            padding: 5px 10px;
            background-color: #e3f2fd;
            color: #1565c0;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 500;
            margin-right: 8px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .action-btn {
            padding: 10px 20px;
            border-radius: 5px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .action-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .btn-primary {
            background: linear-gradient(135deg, #2196F3, #0D47A1);
            color: white;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #9e9e9e, #616161);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #f44336, #c62828);
            color: white;
        }

        /* Header buttons style */
        .header-buttons {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            border-radius: 0.375rem;
            border: 1px solid transparent;
            background-color: transparent;
            padding: 0.25rem;
            color: #3C1E03;
        }

        .header-btn:hover {
            background-color: #f9fafb;
            border-color: #f3f4f6;
        }

        .divider {
            background-color: #e5e7eb;
            border-radius: 0.375rem;
            width: 1px;
            height: 1.75rem;
        }

        /* Media Queries for Responsiveness */
        @media (max-width: 1200px) {
            .sidebar {
                width: 60px;
            }
            .logo-text, .logo-subtext, .link-text {
                display: none;
            }
            .profile-image {
                width: 45px;
                height: 45px;
            }
            .nav-link i {
                margin-right: 0;
                font-size: 18px;
            }
            .nav-link {
                justify-content: center;
                padding: 12px;
            }
            .main-content {
                margin-left: 60px;
            }
        }

        @media (max-width: 768px) {
            .mobile-header {
                display: flex;
            }
            
            .sidebar {
                transform: translateX(-100%);
                width: 220px;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .sidebar-overlay.show {
                display: block;
            }
            
            .logo-text, .logo-subtext, .link-text {
                display: block;
            }
            
            .profile-image {
                width: 80px;
                height: 80px;
            }
            
            .nav-link {
                justify-content: space-between;
                padding: 10px 12px;
            }
            
            .nav-link i {
                margin-right: 10px;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .header {
                padding: 12px 15px;
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .page-title {
                font-size: 20px;
            }
            
            .user-info {
                width: 100%;
                justify-content: flex-end;
            }
            
            .content {
                padding: 15px 12px;
            }
            
            .card {
                padding: 15px;
            }
            
            .result-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .page-title {
                font-size: 18px;
            }
            
            .card-title {
                font-size: 16px;
            }
            
            .user-details {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Header -->
    <div class="mobile-header">
        <div class="logo-text">DDA-KINONDONI</div>
        <button class="menu-toggle" id="menu-toggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="profile-section">
            <div class="profile-image">
                <img src="dda.jpeg" alt="Profile Image" onerror="this.src='data:image/svg+xml;charset=UTF-8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22><rect width=%22100%22 height=%22100%22 fill=%22%23ddd%22/><text x=%2250%22 y=%2250%22 font-size=%2250%22 text-anchor=%22middle%22 dominant-baseline=%22central%22>🏦</text></svg>'">
            </div>
            <div class="logo-text">DDA-KINONDONI</div>
            <div class="logo-subtext">Data Scraping Expert</div>
        </div>

        <ul class="nav">
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-home"></i>
                    <span class="link-text">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-search"></i>
                    <span class="link-text">Search</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="statistics.html">
                    <i class="fas fa-chart-bar"></i>
                    <span class="link-text">Statistics</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-database"></i>
                    <span class="link-text">Data</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="setting.php">
                    <i class="fas fa-cog"></i>
                    <span class="link-text">Settings</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="#">
                    <i class="fas fa-eye"></i>
                    <span class="link-text">View Result</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <header class="header">
            <h1 class="page-title">TRA OPEN SOURCE INTELLIGENCE <span>(TOSI)</span></h1>
            <div class="header-buttons">
                <div class="user-info">
                    <div class="user-img">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-details">
                        <div class="user-name">DIGITAL BLOCK</div>
                        <div class="user-role">Administrator</div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content">
            <div class="card">
                <h2 class="card-title"><i class="fas fa-user-circle"></i> User Details</h2>
                
                <div class="result-header">
                    <h1 class="result-title">@<?php echo $username; ?></h1>
                    <div class="engagement-badge <?php echo $engagement_color; ?>">
                        Engagement: <?php echo $engagement; ?>%
                    </div>
                </div>

                <div class="info-group">
                    <div class="info-label">Phone Number</div>
                    <div class="info-value"><?php echo $phone; ?></div>
                </div>

                <div class="info-group">
                    <div class="info-label">Bio</div>
                    <div class="info-value"><?php echo $bio; ?></div>
                </div>

                <div class="info-group">
                    <div class="info-label">Hashtag</div>
                    <div>
                        <span class="hashtag-badge">#<?php echo $hashtag; ?></span>
                    </div>
                </div>

                <div class="action-buttons">
                    <button class="action-btn btn-primary">
                        <i class="fas fa-download"></i> Download Data
                    </button>
                    <button class="action-btn btn-secondary">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <button class="action-btn btn-danger">
                        <i class="fas fa-trash"></i> Delete Record
                    </button>
                </div>
            </div>

            <div class="card">
                <h2 class="card-title"><i class="fas fa-info-circle"></i> Additional Information</h2>
                
                <div class="info-group">
                    <div class="info-label">Last Activity</div>
                    <div class="info-value">2 days ago</div>
                </div>

                <div class="info-group">
                    <div class="info-label">Posts Count</div>
                    <div class="info-value">24 posts</div>
                </div>

                <div class="info-group">
                    <div class="info-label">Followers</div>
                    <div class="info-value">1,245 followers</div>
                </div>

                <div class="info-group">
                    <div class="info-label">Following</div>
                    <div class="info-value">345 following</div>
                </div>

                <div class="info-group">
                    <div class="info-label">Account Type</div>
                    <div class="info-value">Public Account</div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mobile menu toggle
        const menuToggle = document.getElementById('menu-toggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');

        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('show');
            sidebarOverlay.classList.toggle('show');
        });

        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        });

        // Function ya kudownload data
        document.querySelector('.btn-primary').addEventListener('click', function() {
            alert('Data ya @<?php echo $username; ?> imepakuliwa kikamilifu!');
        });

        // Function ya kuchapisha
        document.querySelector('.btn-secondary').addEventListener('click', function() {
            window.print();
        });

        // Function ya kufuta record
        document.querySelector('.btn-danger').addEventListener('click', function() {
            if (confirm('Una uhakika unataka kufuta rekodi ya @<?php echo $username; ?>? Hatua hii haiwezi kutenduliwa.')) {
                alert('Rekodi imefutwa kikamilifu!');
                window.location.href = 'dashboard.php';
            }
        });
    </script>
</body>
</html>