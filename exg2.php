<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DDA-TRA</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>💰</text></svg>">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            background-color: #f5f8fa;
            color: #333;
            position: relative;
        }

        /* Mobile Header */
        .mobile-header {
            display: none;
            background-color: #000;
            color: white;
            padding: 15px 20px;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .mobile-header .logo-text {
            font-size: 20px;
            font-weight: 700;
            color: white;
        }

        .menu-toggle {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background-color: #000;
            color: #fff;
            padding: 20px 0;
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

        .profile-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0 20px 20px;
            border-bottom: 1px solid #333;
            margin-bottom: 20px;
        }

        .profile-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            margin-bottom: 15px;
            border: 3px solid rgba(211, 225, 55, 1);
        }

        .profile-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .logo-text {
            font-size: 22px;
            font-weight: 700;
            color: white;
            text-align: center;
            margin-bottom: 5px;
        }

        .logo-subtext {
            font-size: 12px;
            color: #aaa;
            text-align: center;
        }

        .nav {
            list-style: none;
            padding: 0 15px;
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
            padding: 12px 15px;
            color: #ddd;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
            width: 100%;
            justify-content: space-between;
        }

        .nav-link:hover, .nav-link.active {
            background-color: #333;
            color: #fff;
        }

        .nav-link i {
            margin-right: 12px;
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        .link-text {
            flex: 1;
        }

        .dropdown-toggle {
            transition: transform 0.3s;
        }

        .dropdown-toggle.rotated {
            transform: rotate(180deg);
        }

        .dropdown-menu {
            margin-left: 20px;
            display: none;
            padding-left: 10px;
            border-left: 2px solid #333;
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-item {
            padding: 10px 15px;
            color: #bbb;
            text-decoration: none;
            display: block;
            border-radius: 6px;
            transition: all 0.3s;
            font-size: 14px;
        }

        .dropdown-item:hover {
            background-color: #222;
            color: #fff;
        }

        /* Main Content Styles */
        .main-content {
            margin-left: 280px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .header {
            background-color: #fff;
            padding: 20px 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.1);
        }

        .page-title span {
            background: linear-gradient(135deg, #833AB4, #E1306C);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: #ddd;
            margin-right: 10px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(235, 254, 24, 1), rgba(235, 254, 24, 0.7));
        }

        .user-img i {
            color: white;
            font-size: 20px;
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            font-size: 16px;
        }

        .user-role {
            font-size: 12px;
            color: #777;
        }

        /* Content Area */
        .content {
            padding: 30px;
            flex: 1;
            overflow-y: auto;
        }

        .card {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 25px;
            margin-bottom: 25px;
        }

        .card-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
            display: flex;
            align-items: center;
        }

        .card-title i {
            margin-right: 10px;
            color: #E1306C;
        }

        .search-container {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 200px;
            padding: 14px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .search-input:focus {
            border-color: rgba(235, 254, 24, 1);
            outline: none;
        }

        .search-btn {
            background: linear-gradient(135deg, rgba(235, 254, 24, 1), #E1306C);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 14px 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .search-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        /* Maintenance Notice */
        .maintenance-notice {
            background: linear-gradient(135deg, #ffeb3b, #ff9800);
            border-radius: 10px;
            padding: 15px 20px;
            margin: 20px 0;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            animation: pulse 2s infinite;
        }
        
        .maintenance-icon {
            font-size: 24px;
            margin-right: 15px;
            color: #d32f2f;
            animation: spin 2s linear infinite;
        }
        
        .maintenance-text {
            color: #333;
            font-weight: 500;
        }

        .results-container {
            margin-top: 30px;
        }

        .results-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }

        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .result-card {
            background: linear-gradient(135deg, #fdfcfb, #f5f8fa);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #E1306C;
            transition: all 0.3s;
        }

        .result-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .username {
            font-weight: 600;
            font-size: 18px;
            color: #333;
            margin-bottom: 10px;
        }

        .phone {
            color: #E1306C;
            font-weight: 500;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .phone i {
            margin-right: 8px;
        }

        .bio {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }

        .loader {
            display: none;
            text-align: center;
            padding: 30px;
        }

        .loader-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #E1306C;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(255, 152, 0, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(255, 152, 0, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 152, 0, 0); }
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            background: #333;
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transform: translateX(100%);
            opacity: 0;
            transition: all 0.3s;
            z-index: 1000;
        }

        .notification.show {
            transform: translateX(0);
            opacity: 1;
        }

        /* Media Queries for Responsiveness */
        @media (max-width: 1200px) {
            .sidebar {
                width: 70px;
            }
            .logo-text, .logo-subtext, .link-text {
                display: none;
            }
            .profile-image {
                width: 50px;
                height: 50px;
            }
            .nav-link i {
                margin-right: 0;
                font-size: 20px;
            }
            .nav-link {
                justify-content: center;
                padding: 15px;
            }
            .dropdown-menu {
                position: absolute;
                left: 70px;
                background: #000;
                width: 200px;
                border-radius: 8px;
                padding: 10px;
                margin-left: 0;
                z-index: 100;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            }
            .main-content {
                margin-left: 70px;
            }
        }

        @media (max-width: 768px) {
            .mobile-header {
                display: flex;
            }
            
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
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
                width: 100px;
                height: 100px;
            }
            
            .nav-link {
                justify-content: space-between;
                padding: 12px 15px;
            }
            
            .nav-link i {
                margin-right: 12px;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .header {
                padding: 15px 20px;
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .page-title {
                font-size: 22px;
            }
            
            .user-info {
                width: 100%;
                justify-content: flex-end;
            }
            
            .content {
                padding: 20px 15px;
            }
            
            .card {
                padding: 20px;
            }
            
            .search-container {
                flex-direction: column;
            }
            
            .search-input {
                width: 100%;
            }
            
            .search-btn {
                width: 100%;
            }
            
            .results-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .page-title {
                font-size: 20px;
            }
            
            .card-title {
                font-size: 18px;
            }
            
            .user-details {
                display: none;
            }
            
            .dropdown-menu {
                width: 180px;
            }
            
            .maintenance-notice {
                flex-direction: column;
                text-align: center;
            }
            
            .maintenance-icon {
                margin-right: 0;
                margin-bottom: 10px;
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
                <a class="nav-link active" href="#">
                    <i class="fas fa-home"></i>
                    <span class="link-text">Dashboard</span>
                    <i class="fas fa-chevron-down dropdown-toggle"></i>
                </a>
                <div class="dropdown-menu">
                    <a href="#" class="dropdown-item">Overview</a>
                    <a href="#" class="dropdown-item">Analytics</a>
                    <a href="#" class="dropdown-item">Reports</a>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-search"></i>
                    <span class="link-text">Search</span>
                    <i class="fas fa-chevron-down dropdown-toggle"></i>
                </a>
                <div class="dropdown-menu">
                    <a href="#" class="dropdown-item">Basic Search</a>
                    <a href="#" class="dropdown-item">Advanced Search</a>
                    <a href="#" class="dropdown-item">Search History</a>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-chart-bar"></i>
                    <span class="link-text">Statistics</span>
                    <i class="fas fa-chevron-down dropdown-toggle"></i>
                </a>
                <div class="dropdown-menu">
                    <a href="#" class="dropdown-item">Performance</a>
                    <a href="#" class="dropdown-item">User Stats</a>
                    <a href="#" class="dropdown-item">Engagement</a>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-database"></i>
                    <span class="link-text">Data</span>
                    <i class="fas fa-chevron-down dropdown-toggle"></i>
                </a>
                <div class="dropdown-menu">
                    <a href="#" class="dropdown-item">All Data</a>
                    <a href="#" class="dropdown-item">Filter Data</a>
                    <a href="#" class="dropdown-item">Export Data</a>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-cog"></i>
                    <span class="link-text">Settings</span>
                    <i class="fas fa-chevron-down dropdown-toggle"></i>
                </a>
                <div class="dropdown-menu">
                    <a href="#" class="dropdown-item">Account Settings</a>
                    <a href="#" class="dropdown-item">Privacy</a>
                    <a href="#" class="dropdown-item">Notifications</a>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-download"></i>
                    <span class="link-text">Download Data</span>
                    <i class="fas fa-chevron-down dropdown-toggle"></i>
                </a>
                <div class="dropdown-menu">
                    <a href="#" class="dropdown-item">CSV Format</a>
                    <a href="#" class="dropdown-item">Excel Format</a>
                    <a href="#" class="dropdown-item">JSON Format</a>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-upload"></i>
                    <span class="link-text">Upload Data</span>
                    <i class="fas fa-chevron-down dropdown-toggle"></i>
                </a>
                <div class="dropdown-menu">
                    <a href="#" class="dropdown-item">From Computer</a>
                    <a href="#" class="dropdown-item">From Cloud</a>
                    <a href="#" class="dropdown-item">Import Settings</a>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-question-circle"></i>
                    <span class="link-text">Help</span>
                    <i class="fas fa-chevron-down dropdown-toggle"></i>
                </a>
                <div class="dropdown-menu">
                    <a href="#" class="dropdown-item">Documentation</a>
                    <a href="#" class="dropdown-item">Support</a>
                    <a href="#" class="dropdown-item">Contact Us</a>
                </div>
            </li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <header class="header">
            <h1 class="page-title">TRA OPEN SOURCE INTELLIGENCE <span>  (TOSI) </span></h1>
            <div class="user-info">
                <div class="user-img">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <div class="user-name">DIGITAL BLOCK </div>
                    <div class="user-role">Administrator</div>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content">
            <div class="card">
                <h2 class="card-title"><i class="fas fa-search"></i> Search Instagram Data</h2>
                <div class="search-container">
                    <input type="text" class="search-input" id="hashtag-input" placeholder="Enter hashtag to search (e.g., viatutanzania)">
                    <button class="search-btn" id="search-btn">Search</button>
                </div>
                
                <!-- Maintenance Notice -->
                <div class="maintenance-notice">
                    <div class="maintenance-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <div class="maintenance-text">
                        This system is currently under maintenance. Please wait while we improve our services.
                    </div>
                </div>
                
                <p>Enter a hashtag without the # symbol. The system will scrape Instagram for posts with this hashtag and extract user information.</p>
            </div>

            <div class="loader" id="loader">
                <div class="loader-spinner"></div>
                <p>Scraping Instagram data. This may take a few minutes...</p>
            </div>

            <div class="results-container" id="results-container">
                <h3 class="results-title">Search Results</h3>
                <div class="results-grid" id="results-grid">
                    <!-- Results will be displayed here -->
                  
                    
                  
                </div>
            </div>
        </div>
    </main>

    <div class="notification" id="notification">Search completed successfully!</div>

    <script>
    // Mobile menu toggle
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');

    menuToggle.addEventListener('click', function() {
        sidebar.classList.toggle('show');
        sidebarOverlay.classList.toggle('show');
    });

    sidebarOverlay.addEventListener('click', function() {
        sidebar.classList.remove('show');
        sidebarOverlay.classList.remove('show');
    });

    // Dropdown menu functionality
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.querySelector('.dropdown-toggle')) {
                e.preventDefault();
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    if (menu !== this.nextElementSibling) {
                        menu.classList.remove('show');
                        menu.previousElementSibling.querySelector('.dropdown-toggle').classList.remove('rotated');
                    }
                });
                const dropdown = this.nextElementSibling;
                if (dropdown && dropdown.classList.contains('dropdown-menu')) {
                    dropdown.classList.toggle('show');
                    this.querySelector('.dropdown-toggle').classList.toggle('rotated');
                }
            }
        });
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.nav-item')) {
            document.querySelectorAll('.dropdown-menu').forEach(menu => menu.classList.remove('show'));
            document.querySelectorAll('.dropdown-toggle').forEach(toggle => toggle.classList.remove('rotated'));
        }
    });

    // Search functionality with real Flask API
    document.getElementById('search-btn').addEventListener('click', async function() {
        const hashtag = document.getElementById('hashtag-input').value.trim();
        if (!hashtag) {
            showNotification('Please enter a hashtag', 'error');
            return;
        }

        // Show loader
        document.getElementById('loader').style.display = 'block';
        document.getElementById('results-container').style.opacity = '0.5';

        try {
            // Replace this URL with your deployed Flask server if online
            const apiUrl = "https://insta-render-api-3.onrender.com"; 

            const response = await fetch(apiUrl, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ hashtag })
            });

            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const data = await response.json();

            const resultsGrid = document.getElementById('results-grid');
            resultsGrid.innerHTML = ""; // Clear previous results

            data.forEach(item => {
                const newCard = document.createElement('div');
                newCard.className = 'result-card';
                newCard.innerHTML = `
                    <div class="username">@${item.Username}</div>
                    <div class="phone"><i class="fas fa-phone"></i> ${item["Phone Numbers"] || 'N/A'}</div>
                    <div class="bio">${item.Bio || 'No bio available'}</div>
                `;
                resultsGrid.appendChild(newCard);
            });

            showNotification(`Found ${data.length} results for #${hashtag}`);
        } catch (err) {
            showNotification("Error fetching data", "error");
            console.error(err);
        } finally {
            document.getElementById('loader').style.display = 'none';
            document.getElementById('results-container').style.opacity = '1';
        }
    });

    // Show notification
    function showNotification(message, type = 'success') {
        const notification = document.getElementById('notification');
        notification.textContent = message;
        notification.style.background = type === 'success' ? '#333' : '#E1306C';
        notification.classList.add('show');
        setTimeout(() => notification.classList.remove('show'), 3000);
    }

    // Enter key triggers search
    document.getElementById('hashtag-input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            document.getElementById('search-btn').click();
        }
    });
</script>

</body>
</html>