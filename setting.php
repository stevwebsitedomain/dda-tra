<?php
// setting.php
session_start();

// Hii ni mfano tu - katika mfumo wa kweli ungekuwa na utunzaji bora wa sessions na upatikanaji
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'DIGITAL BLOCK';
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'Administrator';

// Hifadhi mipangilio ikiwa fomu imewasilishwa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Katika mfumo wa kweli, ungehifadhi hizi setting kwenye database
    foreach ($_POST as $key => $value) {
        // Hifadhi thamani kwenye kumbukumbu ya muda (kwa mfano)
        $_SESSION['settings'][$key] = $value;
    }
    
    $success_message = "Mipangilio imehifadhiwa kikamilifu!";
}

// Pata thamani za sasa za mipangilio
$theme = isset($_SESSION['settings']['theme']) ? $_SESSION['settings']['theme'] : 'light';
$language = isset($_SESSION['settings']['language']) ? $_SESSION['settings']['language'] : 'sw';
$results_per_page = isset($_SESSION['settings']['results_per_page']) ? $_SESSION['settings']['results_per_page'] : '10';
$notification_sound = isset($_SESSION['settings']['notification_sound']) ? $_SESSION['settings']['notification_sound'] : 'on';
$auto_save = isset($_SESSION['settings']['auto_save']) ? $_SESSION['settings']['auto_save'] : 'off';
$data_retention = isset($_SESSION['settings']['data_retention']) ? $_SESSION['settings']['data_retention'] : '30';
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DDA-TRA - Mipangilio</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>⚙️</text></svg>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Roboto', 'Google Sans', sans-serif;
        }

        body {
            background-color: #f8f9fa;
            color: #3C1E03;
        }

        .settings-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .settings-header {
            background: linear-gradient(135deg, #833AB4, #E1306C);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .settings-card {
            background-color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
        }

        .settings-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .settings-card h3 {
            color: #3C1E03;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #E1306C;
            display: flex;
            align-items: center;
        }

        .settings-card h3 i {
            margin-right: 10px;
            color: #E1306C;
        }

        .form-label {
            font-weight: 600;
            color: #3C1E03;
        }

        .btn-primary {
            background: linear-gradient(135deg, #833AB4, #E1306C);
            border: none;
            padding: 10px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(131, 58, 180, 0.3);
        }

        .btn-outline-secondary {
            border: 2px solid #833AB4;
            color: #833AB4;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-secondary:hover {
            background-color: #833AB4;
            color: white;
        }

        .setting-item {
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 10px;
            background-color: #f8f9fa;
            transition: background-color 0.3s ease;
        }

        .setting-item:hover {
            background-color: #e9ecef;
        }

        .success-alert {
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .preview-box {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 10px;
            border-left: 4px solid #E1306C;
        }

        .theme-option {
            padding: 15px;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .theme-option:hover, .theme-option.selected {
            border-color: #833AB4;
            background-color: #f8f9fa;
        }

        .theme-option.selected {
            position: relative;
        }

        .theme-option.selected::after {
            content: '✓';
            position: absolute;
            top: 10px;
            right: 10px;
            color: #28a745;
            font-weight: bold;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            margin-bottom: 20px;
            color: #833AB4;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .back-button:hover {
            color: #E1306C;
        }
    </style>
</head>
<body>
    <div class="settings-container">
        <a href="index.php" class="back-button">
            <i class="fas fa-arrow-left me-2"></i>Rudi kwenye Dashboard
        </a>

        <div class="settings-header text-center">
            <h1><i class="fas fa-cog me-2"></i>Mipangilio ya System</h1>
            <p class="lead">Rekebisha na weka mipangilio ya dashboard kulingana na mahitaji yako</p>
        </div>

        <?php if (isset($success_message)): ?>
        <div class="alert alert-success success-alert" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="setting.php">
            <!-- Mpangilio wa Mandhari -->
            <div class="settings-card">
                <h3><i class="fas fa-palette"></i>Mandhari na Muonekano</h3>
                
                <div class="mb-3">
                    <label class="form-label">Chagua Mandhari</label>
                    <div class="theme-option <?php echo $theme === 'light' ? 'selected' : ''; ?>" onclick="selectTheme('light')">
                        <h5><i class="fas fa-sun me-2"></i>Mandhari ya Lugha</h5>
                        <p class="mb-0">Mandhari nyepesi yenye rangi za uwanga za kawaida</p>
                    </div>
                    <div class="theme-option <?php echo $theme === 'dark' ? 'selected' : ''; ?>" onclick="selectTheme('dark')">
                        <h5><i class="fas fa-moon me-2"></i>Mandhari ya Giza</h5>
                        <p class="mb-0">Mandhari ya giza inayopunguza macho na kuokoa nishati</p>
                    </div>
                    <div class="theme-option <?php echo $theme === 'blue' ? 'selected' : ''; ?>" onclick="selectTheme('blue')">
                        <h5><i class="fas fa-tint me-2"></i>Mandhari ya Bluu</h5>
                        <p class="mb-0">Mandhari ya bluu yenye utulivu na rangi za kina</p>
                    </div>
                    <input type="hidden" name="theme" id="themeInput" value="<?php echo $theme; ?>">
                </div>

                <div class="mb-3">
                    <label for="colorScheme" class="form-label">Rangi Kuu ya Dashboard</label>
                    <select class="form-select" id="colorScheme" name="colorScheme">
                        <option value="default" selected>Zambarau na Waridi (Default)</option>
                        <option value="blue">Bluu na Samawati</option>
                        <option value="green">Kijani na Dhahabu</option>
                        <option value="red">Nyekundu na Machungwa</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Onesho la Kichupo cha Huduma</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="showProgressNotice" name="showProgressNotice" checked>
                        <label class="form-check-label" for="showProgressNotice">Onyesha kichupo cha huduma kinachoonyesha maendeleo</label>
                    </div>
                </div>
            </div>

            <!-- Mpangilio wa Lugha -->
            <div class="settings-card">
                <h3><i class="fas fa-language"></i>Lugha na Kimataifa</h3>
                
                <div class="mb-3">
                    <label for="language" class="form-label">Chagua Lugha</label>
                    <select class="form-select" id="language" name="language">
                        <option value="sw" <?php echo $language === 'sw' ? 'selected' : ''; ?>>Kiswahili</option>
                        <option value="en" <?php echo $language === 'en' ? 'selected' : ''; ?>>English</option>
                        <option value="fr" <?php echo $language === 'fr' ? 'selected' : ''; ?>>Français</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="dateFormat" class="form-label">Umbo la Tarehe</label>
                    <select class="form-select" id="dateFormat" name="dateFormat">
                        <option value="dd-mm-yyyy">DD-MM-YYYY</option>
                        <option value="mm-dd-yyyy">MM-DD-YYYY</option>
                        <option value="yyyy-mm-dd">YYYY-MM-DD</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="timezone" class="form-label">Zona ya Muda</label>
                    <select class="form-select" id="timezone" name="timezone">
                        <option value="east-africa">Afrika Masharidi (EAT)</option>
                        <option value="utc">UTC</option>
                        <option value="gmt">GMT</option>
                    </select>
                </div>
            </div>

            <!-- Mpangilio wa Data -->
            <div class="settings-card">
                <h3><i class="fas fa-database"></i>Usimamizi wa Data</h3>
                
                <div class="mb-3">
                    <label for="resultsPerPage" class="form-label">Matokeo kwa Ukurasa</label>
                    <select class="form-select" id="resultsPerPage" name="results_per_page">
                        <option value="10" <?php echo $results_per_page === '10' ? 'selected' : ''; ?>>10</option>
                        <option value="25" <?php echo $results_per_page === '25' ? 'selected' : ''; ?>>25</option>
                        <option value="50" <?php echo $results_per_page === '50' ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $results_per_page === '100' ? 'selected' : ''; ?>>100</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="dataRetention" class="form-label">Uhifadhi wa Data (siku)</label>
                    <select class="form-select" id="dataRetention" name="data_retention">
                        <option value="7" <?php echo $data_retention === '7' ? 'selected' : ''; ?>>7 siku</option>
                        <option value="30" <?php echo $data_retention === '30' ? 'selected' : ''; ?>>30 siku</option>
                        <option value="90" <?php echo $data_retention === '90' ? 'selected' : ''; ?>>90 siku</option>
                        <option value="365" <?php echo $data_retention === '365' ? 'selected' : ''; ?>>Mwaka mmoja</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Hifadhi Data kiotomatiki</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="autoSave" name="auto_save" <?php echo $auto_save === 'on' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="autoSave">Hifadhi data kiotomatiki baada ya utafuti</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Aina za File za Kuweza Pakua</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="formatCsv" name="formatCsv" checked>
                        <label class="form-check-label" for="formatCsv">CSV</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="formatExcel" name="formatExcel" checked>
                        <label class="form-check-label" for="formatExcel">Excel</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="formatJson" name="formatJson">
                        <label class="form-check-label" for="formatJson">JSON</label>
                    </div>
                </div>
            </div>

            <!-- Mpangilio wa Arifa -->
            <div class="settings-card">
                <h3><i class="fas fa-bell"></i>Arifa na Mialiko</h3>
                
                <div class="mb-3">
                    <label class="form-label">Aina za Arifa</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="notifyEmail" name="notifyEmail" checked>
                        <label class="form-check-label" for="notifyEmail">Barua pepe</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="notifyBrowser" name="notifyBrowser" checked>
                        <label class="form-check-label" for="notifyBrowser">Arifa za kivinjari</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="notifySound" name="notification_sound" <?php echo $notification_sound === 'on' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="notifySound">Sauti ya arifa</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tumia Arifa Za</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="notifySuccess" name="notifySuccess" checked>
                        <label class="form-check-label" for="notifySuccess">Ufanisi wa operesheni</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="notifyError" name="notifyError" checked>
                        <label class="form-check-label" for="notifyError">Makosa ya system</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="notifyNewData" name="notifyNewData">
                        <label class="form-check-label" for="notifyNewData">Data mpya inapatikana</label>
                    </div>
                </div>
            </div>

            <!-- Mpangilio wa Usalama -->
            <div class="settings-card">
                <h3><i class="fas fa-shield-alt"></i>Usalama na Siri</h3>
                
                <div class="mb-3">
                    <label for="sessionTimeout" class="form-label">Muda wa Kikomo wa Kikao (dakika)</label>
                    <select class="form-select" id="sessionTimeout" name="sessionTimeout">
                        <option value="15">15</option>
                        <option value="30">30</option>
                        <option value="60" selected>60</option>
                        <option value="120">120</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Vipaumbele vya Usalama</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="twoFactorAuth" name="twoFactorAuth">
                        <label class="form-check-label" for="twoFactorAuth">Uthibitishaji wa Hatua Mbili</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="loginAlerts" name="loginAlerts" checked>
                        <label class="form-check-label" for="loginAlerts">Arifa za kuingia kwenye mfumo</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="encryptData" name="encryptData" checked>
                        <label class="form-check-label" for="encryptData">Ficha data kwenye hifadhi</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="passwordChange" class="form-label">Badilisha Nenosiri</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="passwordChange" placeholder="Weka nenosiri jipya">
                        <button class="btn btn-outline-secondary" type="button" id="showPassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mpangilio wa Advanced -->
            <div class="settings-card">
                <h3><i class="fas fa-sliders-h"></i>Mipangilio ya Advanced</h3>
                
                <div class="mb-3">
                    <label for="apiRequests" class="form-label">Idadi ya Maombi ya API kwa Dakika</label>
                    <input type="range" class="form-range" id="apiRequests" name="apiRequests" min="1" max="60" value="30">
                    <div class="d-flex justify-content-between">
                        <small>1</small>
                        <small>30</small>
                        <small>60</small>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="cacheLifetime" class="form-label">Muda wa Cache (dakika)</label>
                    <select class="form-select" id="cacheLifetime" name="cacheLifetime">
                        <option value="5">5</option>
                        <option value="15">15</option>
                        <option value="30" selected>30</option>
                        <option value="60">60</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Chaguo za Uboreshaji</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="enableDebug" name="enableDebug">
                        <label class="form-check-label" for="enableDebug">Wezesha Hati za Ukosefu</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="enableAnalytics" name="enableAnalytics" checked>
                        <label class="form-check-label" for="enableAnalytics">Wezesha Takwimu za Matumizi</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="autoUpdates" name="autoUpdates" checked>
                        <label class="form-check-label" for="autoUpdates">Sasishio za kiotomatiki</label>
                    </div>
                </div>
            </div>

            <!-- Vifungo vya Kusanya -->
            <div class="d-flex justify-content-between mb-5">
                <button type="reset" class="btn btn-outline-secondary px-4">
                    <i class="fas fa-undo me-2"></i>Tengua Marekebisho
                </button>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="fas fa-save me-2"></i>Hifadhi Mipangilio
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Chagua mandhari
        function selectTheme(theme) {
            document.querySelectorAll('.theme-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            event.currentTarget.classList.add('selected');
            document.getElementById('themeInput').value = theme;
            
            // Onyesha uonyeshaji wa haraka
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-info mt-2';
            alertDiv.innerHTML = `<i class="fas fa-info-circle me-2"></i>Mandhari imebadilishwa kuwa: ${theme}`;
            document.querySelector('.settings-card').appendChild(alertDiv);
            
            // Ondoa uonyeshaji baada ya sekunde 3
            setTimeout(() => {
                alertDiv.remove();
            }, 3000);
        }

        // Onesha au ficha nenosiri
        document.getElementById('showPassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('passwordChange');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                passwordInput.type = 'password';
                this.innerHTML = '<i class="fas fa-eye"></i>';
            }
        });

        // Animation ya kuingia kwa vipengele
        document.addEventListener('DOMContentLoaded', function() {
            const settingsCards = document.querySelectorAll('.settings-card');
            settingsCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100 * index);
            });
        });

        // Onyesha thamani ya API requests
        document.getElementById('apiRequests').addEventListener('input', function() {
            const value = this.value;
            // Tafuta label iwezekanavyo au unda yake mwenyewe
            let label = this.nextElementSibling.querySelector('small:nth-child(2)');
            if (label) {
                label.textContent = value;
            }
        });
    </script>
</body>
</html>