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

// Mikoa ya Tanzania na wilaya zake
$tanzania_regions = [
    'Dar es Salaam' => ['Ilala', 'Kinondoni', 'Temeke', 'Kigamboni', 'Ubungo'],
    'Arusha' => ['Arusha City', 'Arusha Rural', 'Karatu', 'Longido', 'Monduli', 'Ngorongoro'],
    'Dodoma' => ['Dodoma Urban', 'Dodoma Rural', 'Bahi', 'Chamwino', 'Chemba', 'Kondoa', 'Kongwa'],
    'Mwanza' => ['Ilemela', 'Nyamagana', 'Sengerema', 'Ukerewe', 'Magu', 'Misungwi', 'Kwimba'],
    'Mbeya' => ['Mbeya City', 'Mbeya Rural', 'Chunya', 'Ileje', 'Mbarali', 'Rungwe', 'Kyela'],
    'Morogoro' => ['Morogoro Urban', 'Morogoro Rural', 'Gairo', 'Kilombero', 'Kilosa', 'Mvomero', 'Ulanga'],
    'Tanga' => ['Tanga City', 'Tanga Rural', 'Handeni', 'Kilindi', 'Korogwe', 'Lushoto', 'Mkinga', 'Pangani'],
    'Zanzibar' => ['Mjini Magharibi', 'Kaskazini Unguja', 'Kusini Unguja', 'Mjini', 'Kaskazini Pemba', 'Kusini Pemba'],
    'Mara' => ['Musoma Urban', 'Musoma Rural', 'Bunda', 'Butiama', 'Rorya', 'Serengeti', 'Tarime'],
    'Kagera' => ['Bukoba Urban', 'Bukoba Rural', 'Biharamulo', 'Karagwe', 'Kyerwa', 'Misenyi', 'Muleba', 'Ngara'],
    'Kigoma' => ['Kigoma Urban', 'Kigoma Rural', 'Buhigwe', 'Kakonko', 'Kasulu', 'Kibondo', 'Uvinza'],
    'Kilimanjaro' => ['Moshi Urban', 'Moshi Rural', 'Hai', 'Mwanga', 'Rombo', 'Same', 'Siha'],
    'Lindi' => ['Lindi Urban', 'Lindi Rural', 'Kilwa', 'Liwale', 'Nachingwea', 'Ruangwa'],
    'Manyara' => ['Babati', 'Hanang', 'Kiteto', 'Mbulu', 'Simanjiro'],
    'Mtwara' => ['Mtwara Urban', 'Mtwara Rural', 'Masasi', 'Nanyumbu', 'Newala', 'Tandahimba'],
    'Pwani' => ['Kibaha', 'Bagamoyo', 'Kisarawe', 'Mafia', 'Mkuranga', 'Rufiji'],
    'Rukwa' => ['Sumbawanga Urban', 'Sumbawanga Rural', 'Kalambo', 'Nkasi', 'Mpanda'],
    'Ruvuma' => ['Songea Urban', 'Songea Rural', 'Mbinga', 'Namtumbo', 'Nyasa', 'Tunduru'],
    'Shinyanga' => ['Shinyanga Urban', 'Shinyanga Rural', 'Kahama', 'Kishapu', 'Meatu'],
    'Singida' => ['Singida Urban', 'Singida Rural', 'Iramba', 'Manyoni', 'Mkalama', 'Ikungi'],
    'Tabora' => ['Tabora Urban', 'Tabora Rural', 'Igunga', 'Kaliua', 'Nzega', 'Sikonge', 'Urambo'],
    'Geita' => ['Geita', 'Bukombe', 'Chato', 'Mbogwe', 'Nyang\'hwale'],
    'Katavi' => ['Mpanda', 'Mlele', 'Nsimbo'],
    'Njombe' => ['Njombe', 'Ludewa', 'Makambako', 'Makete', 'Wanging\'ombe'],
    'Simiyu' => ['Bariadi', 'Busega', 'Itilima', 'Maswa', 'Meatu']
];

// Load data from session or initialize empty
$analyticsData = isset($_SESSION['analyticsData']) ? $_SESSION['analyticsData'] : null;
$searchResults = isset($_SESSION['searchResults']) ? $_SESSION['searchResults'] : [];
$currentHashtag = isset($_SESSION['currentHashtag']) ? $_SESSION['currentHashtag'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DDA-TRA - Filter Analytics</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🏦</text></svg>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <style>
        .success-message {
            color: #4CAF50;
            background: rgba(255, 255, 255, 0.1);
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
        }

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

        #root {
            width: 100%;
        }

        /* Layout */
        .flex {
            display: flex;
        }

        .max-h-screen {
            max-height: 100vh;
        }

        /* Sidebar Styles - REMAINS BLACK */
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

        .dropdown-toggle {
            transition: transform 0.3s;
            font-size: 12px;
        }

        .dropdown-toggle.rotated {
            transform: rotate(180deg);
        }

        .dropdown-menu {
            margin-left: 15px;
            display: none;
            padding-left: 8px;
            border-left: 2px solid #333;
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-item {
            padding: 8px 12px;
            color: #bbb;
            text-decoration: none;
            display: block;
            border-radius: 5px;
            transition: all 0.3s;
            font-size: 13px;
        }

        .dropdown-item:hover {
            background-color: #222;
            color: #fff;
        }

        /* Main Content Styles - UPDATED STYLE */
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

        /* Content Area - UPDATED STYLE */
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

        .search-container {
            display: flex;
            gap: 12px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 180px;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
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
            border-radius: 8px;
            padding: 12px 20px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        /* Analytics Dashboard Styles - UPDATED */
        .analytics-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .analytics-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            padding: 20px;
            transition: transform 0.3s ease;
            border: 1px solid #CFBE9D;
        }

        .analytics-card:hover {
            transform: translateY(-5px);
        }

        .analytics-card h3 {
            font-size: 16px;
            margin-bottom: 15px;
            color: #3C1E03;
            display: flex;
            align-items: center;
        }

        .analytics-card h3 i {
            margin-right: 8px;
            color: #E1306C;
        }

        .chart-container {
            position: relative;
            height: 250px;
            width: 100%;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        /* Updated stat cards to match Zanzibar style */
        .stat-card {
            background: #fff;
            color: #3C1E03;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            border: 1px solid #CFBE9D;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
        }

        .stat-card-content {
            width: 60%;
            text-align: left;
        }

        .stat-card-icon {
            width: 40%;
            display: flex;
            justify-content: flex-end;
        }

        .stat-card-icon div {
            background: #F2F0ED;
            padding: 12px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-card h4 {
            font-size: 14px;
            margin-bottom: 10px;
            font-weight: 500;
            color: #9E8F82;
        }

        .stat-card .value {
            font-size: 24px;
            font-weight: 700;
            color: #3C1E03;
        }

        .trend-indicator {
            display: inline-flex;
            align-items: center;
            font-size: 14px;
            margin-left: 8px;
            font-weight: 500;
        }

        .trend-up {
            color: #4caf50;
        }

        .trend-down {
            color: #f44336;
        }

        /* Header buttons style from Zanzibar */
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

        /* Filter Section Styles */
        .filter-section {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            border: 1px solid #CFBE9D;
        }

        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .filter-title {
            font-size: 18px;
            font-weight: 600;
            color: #3C1E03;
            display: flex;
            align-items: center;
        }

        .filter-title i {
            margin-right: 8px;
            color: #E1306C;
        }

        .filter-controls {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-select {
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            min-width: 150px;
        }

        .filter-select:focus {
            border-color: rgba(235, 254, 24, 1);
            outline: none;
        }

        .filter-btn {
            background: linear-gradient(135deg, rgba(235, 254, 24, 1), #E1306C);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .filter-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .filter-results {
            margin-top: 20px;
        }

        .district-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .district-item {
            background: #f9f9f9;
            padding: 10px;
            border-radius: 6px;
            border-left: 3px solid #E1306C;
        }

        .district-name {
            font-weight: 600;
            color: #3C1E03;
            margin-bottom: 5px;
        }

        .district-stats {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #666;
        }

        .trend-card {
            background: #fff;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            margin-top: 15px;
            border: 1px solid #CFBE9D;
        }

        .trend-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }

        .trend-title {
            font-size: 16px;
            font-weight: 600;
            color: #3C1E03;
        }

        .trend-value {
            font-size: 18px;
            font-weight: 700;
            display: flex;
            align-items: center;
        }

        .trend-positive {
            color: #4caf50;
        }

        .trend-negative {
            color: #f44336;
        }

        .trend-neutral {
            color: #666;
        }

        .trend-chart {
            height: 100px;
            margin-top: 10px;
        }

        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .chart-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 15px;
        }
        
        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }
        
        .download-btn {
            background: linear-gradient(135deg, #4caf50, #2e7d32);
            color: white;
        }
        
        .print-btn {
            background: linear-gradient(135deg, #2196F3, #0D47A1);
            color: white;
        }
        
        .share-btn {
            background: linear-gradient(135deg, #FF9800, #E65100);
            color: white;
        }
        
        .action-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        .animated-chart {
            animation: fadeIn 1s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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
            .dropdown-menu {
                position: absolute;
                left: 60px;
                background: #000;
                width: 180px;
                border-radius: 6px;
                padding: 8px;
                margin-left: 0;
                z-index: 100;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
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
            
            .search-container {
                flex-direction: column;
            }
            
            .search-input {
                width: 100%;
            }
            
            .search-btn {
                width: 100%;
            }
            
            .filter-controls {
                flex-direction: column;
            }

            .filter-select {
                width: 100%;
            }

            .district-list {
                grid-template-columns: 1fr;
            }

            .chart-grid {
                grid-template-columns: 1fr;
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
            
            .progress-icon-container {
                width: 50px;
                height: 50px;
            }
            
            .progress-icon {
                font-size: 20px;
            }
            
            .progress-title {
                font-size: 16px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
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
            <div class="logo-subtext">Data Filtering Expert</div>
        </div>

        <ul class="nav">
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-home"></i>
                    <span class="link-text">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="filter.php">
                    <i class="fas fa-filter"></i>
                    <span class="link-text">Filter Analytics</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="statistics.html">
                    <i class="fas fa-chart-bar"></i>
                    <span class="link-text">Statistics</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="setting.php">
                    <i class="fas fa-cog"></i>
                    <span class="link-text">Settings</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <header class="header">
            <h1 class="page-title">TRA FILTER ANALYTICS <span>(TFA)</span></h1>
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
            <!-- Current Hashtag Info -->
            <?php if (!empty($currentHashtag)): ?>
            <div class="card">
                <h2 class="card-title"><i class="fas fa-hashtag"></i> Current Hashtag: #<?php echo htmlspecialchars($currentHashtag); ?></h2>
                <p>Total Results: <?php echo count($searchResults); ?> records found</p>
            </div>
            <?php else: ?>
            <div class="card">
                <h2 class="card-title"><i class="fas fa-info-circle"></i> No Data Available</h2>
                <p>Please perform a search on the dashboard first to view analytics data.</p>
                <a href="dashboard.php" class="search-btn" style="display: inline-flex; margin-top: 10px;">
                    <i class="fas fa-arrow-left"></i> Go to Dashboard
                </a>
            </div>
            <?php endif; ?>



<!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-content">
                        <h4>Total Posts</h4>
                        <div class="value" id="total-posts"><?php echo count($searchResults); ?></div>
                    </div>
                    <div class="stat-card-icon">
                        <div>
                            <i class="fas fa-file-alt" style="color: #F07F1A; font-size: 24px;"></i>
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-content">
                        <h4>Active Users</h4>
                        <div class="value" id="active-users"><?php echo count($searchResults); ?></div>
                    </div>
                    <div class="stat-card-icon">
                        <div>
                            <i class="fas fa-users" style="color: #F07F1A; font-size: 24px;"></i>
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-content">
                        <h4>Engagement Rate</h4>
                        <div class="value" id="engagement-rate">
                            <?php 
                            $engagement = count($searchResults) > 0 ? min(100, round(count($searchResults) * 5)) : 0;
                            echo $engagement . '%';
                            ?>
                        </div>
                    </div>
                    <div class="stat-card-icon">
                        <div>
                            <i class="fas fa-heart" style="color: #F07F1A; font-size: 24px;"></i>
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-content">
                        <h4>Top Region</h4>
                        <div class="value" id="top-region">
                            <?php 
                            if (count($searchResults) > 0) {
                                $regions = array_keys($tanzania_regions);
                                echo $regions[array_rand($regions)];
                            } else {
                                echo '-';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="stat-card-icon">
                        <div>
                            <i class="fas fa-map-marker-alt" style="color: #F07F1A; font-size: 24px;"></i>
                        </div>
                    </div>
                </div>
            </div>





            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-header">
                    <h3 class="filter-title"><i class="fas fa-filter"></i> Filter Data by Region</h3>
                    <div class="filter-controls">
                        <select class="filter-select" id="filter-region">
                            <option value="">Select Region</option>
                            <?php foreach ($tanzania_regions as $region => $districts): ?>
                                <option value="<?php echo $region; ?>"><?php echo $region; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select class="filter-select" id="filter-district" disabled>
                            <option value="">Select District</option>
                        </select>
                        <button class="filter-btn" id="filter-apply">
                            <i class="fas fa-filter"></i> Apply Filter
                        </button>
                        <button class="filter-btn" id="filter-reset" style="background:linear-gradient(135deg, #666, #999);">
                            <i class="fas fa-sync"></i> Reset
                        </button>
                    </div>
                </div>

                <div class="filter-results" id="filter-results">
                    <div class="trend-card">
                        <div class="trend-header">
                            <div class="trend-title">Hashtag Trend</div>
                            <div class="trend-value" id="real-trend-value">
                                <span id="trend-percentage">0%</span>
                                <i class="fas fa-equals trend-neutral" id="trend-icon"></i>
                            </div>
                        </div>
                        <div class="trend-chart">
                            <canvas id="realTrendChart"></canvas>
                        </div>
                    </div>

                    <div id="district-results">
                        <h4 style="margin: 15px 0 10px;">Districts Data</h4>
                        <div class="district-list" id="district-list">
                            <!-- District results will be displayed here -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="chart-grid">
                <!-- Line Chart -->
                <div class="analytics-card animated-chart">
                    <h3><i class="fas fa-chart-line"></i> Engagement Trend</h3>
                    <div class="chart-container">
                        <canvas id="lineChart"></canvas>
                    </div>
                    <div class="chart-actions">
                        <button class="action-btn download-btn" onclick="downloadChart('lineChart', 'line')">
                            <i class="fas fa-download"></i> Download
                        </button>
                        <button class="action-btn print-btn" onclick="window.print()">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>

                <!-- Bar Chart -->
                <div class="analytics-card animated-chart">
                    <h3><i class="fas fa-chart-bar"></i> Regional Distribution</h3>
                    <div class="chart-container">
                        <canvas id="barChart"></canvas>
                    </div>
                    <div class="chart-actions">
                        <button class="action-btn download-btn" onclick="downloadChart('barChart', 'bar')">
                            <i class="fas fa-download"></i> Download
                        </button>
                        <button class="action-btn print-btn" onclick="window.print()">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>

                <!-- Pie Chart -->
                <div class="analytics-card animated-chart">
                    <h3><i class="fas fa-chart-pie"></i> Content Distribution</h3>
                    <div class="chart-container">
                        <canvas id="pieChart"></canvas>
                    </div>
                    <div class="chart-actions">
                        <button class="action-btn download-btn" onclick="downloadChart('pieChart', 'pie')">
                            <i class="fas fa-download"></i> Download
                        </button>
                        <button class="action-btn print-btn" onclick="window.print()">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>

                <!-- Radar Chart -->
                <div class="analytics-card animated-chart">
                    <h3><i class="fas fa-bullseye"></i> Performance Radar</h3>
                    <div class="chart-container">
                        <canvas id="radarChart"></canvas>
                    </div>
                    <div class="chart-actions">
                        <button class="action-btn download-btn" onclick="downloadChart('radarChart', 'radar')">
                            <i class="fas fa-download"></i> Download
                        </button>
                        <button class="action-btn print-btn" onclick="window.print()">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>
            </div>

            
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize charts
        let lineChart, barChart, pieChart, radarChart, realTrendChart;
        const tanzaniaRegions = <?php echo json_encode($tanzania_regions); ?>;
        const analyticsData = <?php echo json_encode($analyticsData); ?>;
        const searchResults = <?php echo json_encode($searchResults); ?>;

        function initCharts() {
            const lineCtx = document.getElementById('lineChart').getContext('2d');
            const barCtx = document.getElementById('barChart').getContext('2d');
            const pieCtx = document.getElementById('pieChart').getContext('2d');
            const radarCtx = document.getElementById('radarChart').getContext('2d');
            const realTrendCtx = document.getElementById('realTrendChart').getContext('2d');
            
            // Use real data if available, otherwise use sample data
            const hasRealData = analyticsData && Object.keys(analyticsData).length > 0;
            
            // Line Chart - Engagement Trend
            lineChart = new Chart(lineCtx, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Engagement',
                        data: hasRealData ? generateTrendData() : [65, 59, 80, 81, 56, 55, 40],
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 2000,
                        easing: 'easeOutBounce'
                    }
                }
            });
            
            // Bar Chart - Regional Distribution
            barChart = new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: hasRealData && analyticsData.regionalDistribution ? 
                        Object.keys(analyticsData.regionalDistribution).slice(0, 5) : 
                        Object.keys(tanzaniaRegions).slice(0, 5),
                    datasets: [{
                        label: 'Posts',
                        data: hasRealData && analyticsData.regionalDistribution ? 
                            Object.values(analyticsData.regionalDistribution).slice(0, 5) : 
                            [65, 59, 80, 81, 56],
                        backgroundColor: 'rgba(54, 162, 235, 0.5)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 2000,
                        easing: 'easeOutBounce'
                    }
                }
            });
            
            // Pie Chart - Content Distribution
            pieChart = new Chart(pieCtx, {
                type: 'pie',
                data: {
                    labels: ['Photos', 'Videos', 'Stories', 'Reels', 'IGTV'],
                    datasets: [{
                        data: hasRealData ? generateContentData() : [30, 25, 20, 15, 10],
                        backgroundColor: [
                            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        animateScale: true,
                        animateRotate: true
                    }
                }
            });
            
            // Radar Chart - Performance Radar
            radarChart = new Chart(radarCtx, {
                type: 'radar',
                data: {
                    labels: ['Engagement', 'Reach', 'Impressions', 'Clicks', 'Shares', 'Saves'],
                    datasets: [{
                        label: 'Performance',
                        data: hasRealData ? generateRadarData() : [65, 59, 90, 81, 56, 55],
                        fill: true,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgb(54, 162, 235)',
                        pointBackgroundColor: 'rgb(54, 162, 235)',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: 'rgb(54, 162, 235)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 2000
                    }
                }
            });

            // Real Trend Chart
            realTrendChart = new Chart(realTrendCtx, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Trend',
                        data: hasRealData && analyticsData.realTrendData ? 
                            analyticsData.realTrendData.values : 
                            generateTrendData(),
                        borderColor: 'rgb(255, 99, 132)',
                        tension: 0.1,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 2000
                    }
                }
            });

            // Update trend indicator
            if (hasRealData && analyticsData.realTrendData && analyticsData.realTrendData.values) {
                const values = analyticsData.realTrendData.values;
                if (values.length > 1) {
                    const firstValue = values[0];
                    const lastValue = values[values.length - 1];
                    const trendPercentage = ((lastValue - firstValue) / firstValue * 100).toFixed(1);
                    updateTrendIndicator(trendPercentage);
                }
            }
        }

        // Generate trend data based on search results
        function generateTrendData() {
            const baseValue = Math.max(10, Math.floor(searchResults.length / 2));
            const data = [];
            
            for (let i = 0; i < 7; i++) {
                const fluctuation = baseValue * (Math.random() * 0.5 - 0.2);
                data.push(Math.max(5, baseValue + fluctuation));
            }
            
            return data.map(v => Math.round(v));
        }

        // Generate content distribution data
        function generateContentData() {
            return [
                Math.floor(Math.random() * 50) + 20,
                Math.floor(Math.random() * 40) + 15,
                Math.floor(Math.random() * 30) + 10,
                Math.floor(Math.random() * 20) + 5,
                Math.floor(Math.random() * 10) + 1
            ];
        }

        // Generate radar data
        function generateRadarData() {
            return Array.from({length: 6}, () => Math.floor(Math.random() * 100));
        }

        // Handle region selection change
        function handleRegionChange() {
            const regionSelect = document.getElementById('filter-region');
            const districtSelect = document.getElementById('filter-district');
            const selectedRegion = regionSelect.value;
            
            // Clear district select
            districtSelect.innerHTML = '<option value="">Select District</option>';
            
            if (selectedRegion) {
                // Enable district select
                districtSelect.disabled = false;
                
                // Add districts for selected region
                tanzaniaRegions[selectedRegion].forEach(district => {
                    const option = document.createElement('option');
                    option.value = district;
                    option.textContent = district;
                    districtSelect.appendChild(option);
                });
            } else {
                // Disable district select if no region selected
                districtSelect.disabled = true;
            }
        }

        // Apply filter and update charts
        function applyFilter() {
            const region = document.getElementById('filter-region').value;
            const district = document.getElementById('filter-district').value;
            
            if (!region) {
                alert('Please select a region first');
                return;
            }
            
            // Get filtered data based on selection
            const filteredData = getFilteredData(region, district);
            
            // Update statistics
            document.getElementById('total-posts').textContent = filteredData.totalPosts.toLocaleString();
            document.getElementById('active-users').textContent = filteredData.activeUsers.toLocaleString();
            document.getElementById('engagement-rate').textContent = filteredData.engagementRate + '%';
            document.getElementById('top-region').textContent = district || region;
            
            // Update charts with filtered data
            updateCharts(filteredData);
            
            // Update district list
            updateDistrictList(region, district, filteredData);
            
            // Show notification
            showNotification(`Filter applied for ${district || 'all districts in'} ${region}`);
        }

        // Get filtered data based on region and district
        function getFilteredData(region, district) {
            // In a real app, this would be an API call
            // For demo purposes, we'll generate data based on the filter
            
            const basePosts = searchResults.length > 0 ? searchResults.length : 100;
            const regionFactor = 0.7 + Math.random() * 0.6; // 0.7 to 1.3
            const districtFactor = district ? 0.3 + Math.random() * 0.4 : 1; // 0.3 to 0.7 if district selected
            
            const totalPosts = Math.floor(basePosts * regionFactor * districtFactor);
            const activeUsers = Math.floor(totalPosts * (0.3 + Math.random() * 0.3));
            const engagementRate = Math.floor(10 + Math.random() * 20);
            
            return {
                totalPosts,
                activeUsers,
                engagementRate
            };
        }

        // Update district list with data
        function updateDistrictList(region, district, filteredData) {
            const districtList = document.getElementById('district-list');
            districtList.innerHTML = '';
            
            // Show districts for the selected region
            const districtsToShow = district ? [district] : tanzaniaRegions[region];
            
            districtsToShow.forEach(dist => {
                const posts = Math.floor(filteredData.totalPosts * (0.1 + Math.random() * 0.3));
                const users = Math.floor(posts * (0.3 + Math.random() * 0.2));
                const engagement = Math.floor(10 + Math.random() * 20);
                
                const districtItem = document.createElement('div');
                districtItem.className = 'district-item';
                districtItem.innerHTML = `
                    <div class="district-name">${dist}</div>
                    <div class="district-stats">
                        <span>${posts} posts</span>
                        <span>${users} users</span>
                        <span>${engagement}% engagement</span>
                    </div>
                `;
                
                districtList.appendChild(districtItem);
            });
        }

        // Update charts with filtered data
        function updateCharts(filteredData) {
            // Update line chart
            lineChart.data.datasets[0].data = generateTrendData();
            lineChart.update();
            
            // Update bar chart
            const regionCount = Math.min(5, Object.keys(tanzaniaRegions).length);
            barChart.data.labels = Object.keys(tanzaniaRegions).slice(0, regionCount);
            barChart.data.datasets[0].data = Array.from({length: regionCount}, () => Math.floor(Math.random() * 100));
            barChart.update();
            
            // Update pie chart
            pieChart.data.datasets[0].data = generateContentData();
            pieChart.update();
            
            // Update radar chart
            radarChart.data.datasets[0].data = generateRadarData();
            radarChart.update();
            
            // Update real trend chart
            realTrendChart.data.datasets[0].data = generateTrendData();
            realTrendChart.update();
            
            // Update trend indicator
            const values = realTrendChart.data.datasets[0].data;
            if (values.length > 1) {
                const firstValue = values[0];
                const lastValue = values[values.length - 1];
                const trendPercentage = ((lastValue - firstValue) / firstValue * 100).toFixed(1);
                updateTrendIndicator(trendPercentage);
            }
        }

        // Update trend indicator
        function updateTrendIndicator(percentage) {
            const trendPercentage = document.getElementById('trend-percentage');
            const trendIcon = document.getElementById('trend-icon');
            
            trendPercentage.textContent = Math.abs(percentage) + '%';
            
            if (percentage > 0) {
                trendIcon.className = 'fas fa-arrow-up trend-positive';
                trendPercentage.className = 'trend-positive';
            } else if (percentage < 0) {
                trendIcon.className = 'fas fa-arrow-down trend-negative';
                trendPercentage.className = 'trend-negative';
            } else {
                trendIcon.className = 'fas fa-equals trend-neutral';
                trendPercentage.className = 'trend-neutral';
            }
        }

        // Reset filter
        function resetFilter() {
            document.getElementById('filter-region').value = '';
            document.getElementById('filter-district').value = '';
            document.getElementById('filter-district').disabled = true;
            
            // Reset to original data
            document.getElementById('total-posts').textContent = searchResults.length;
            document.getElementById('active-users').textContent = searchResults.length;
            document.getElementById('engagement-rate').textContent = 
                searchResults.length > 0 ? min(100, round(searchResults.length * 5)) + '%' : '0%';
            
            if (searchResults.length > 0) {
                const regions = Object.keys(tanzaniaRegions);
                document.getElementById('top-region').textContent = regions[Math.floor(Math.random() * regions.length)];
            } else {
                document.getElementById('top-region').textContent = '-';
            }
            
            // Reset charts
            initCharts();
            
            // Clear district list
            document.getElementById('district-list').innerHTML = '';
            
            showNotification('Filter reset');
        }

        // Download chart as image
        function downloadChart(chartId, chartType) {
            const chartCanvas = document.getElementById(chartId);
            const link = document.createElement('a');
            link.href = chartCanvas.toDataURL('image/png');
            link.download = `${chartType}-chart-${new Date().toISOString().slice(0, 10)}.png`;
            link.click();
        }

        // Show notification
        function showNotification(message) {
            // Create a simple notification
            const notification = document.createElement('div');
            notification.style.position = 'fixed';
            notification.style.top = '20px';
            notification.style.right = '20px';
            notification.style.backgroundColor = '#333';
            notification.style.color = 'white';
            notification.style.padding = '12px 20px';
            notification.style.borderRadius = '6px';
            notification.style.zIndex = '1000';
            notification.style.boxShadow = '0 3px 10px rgba(0, 0, 0, 0.2)';
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Remove notification after 3 seconds
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transition = 'opacity 0.5s';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 500);
            }, 3000);
        }

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initCharts();
            
            // Bind event listeners
            document.getElementById('filter-region').addEventListener('change', handleRegionChange);
            document.getElementById('filter-apply').addEventListener('click', applyFilter);
            document.getElementById('filter-reset').addEventListener('click', resetFilter);
            
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

            // If we have data, apply default filter
            if (searchResults.length > 0) {
                // Set a default region and apply filter
                const regions = Object.keys(tanzaniaRegions);
                const defaultRegion = regions[Math.floor(Math.random() * regions.length)];
                document.getElementById('filter-region').value = defaultRegion;
                handleRegionChange();
                setTimeout(() => applyFilter(), 500);
            }
        });
    </script>
</body>
</html>