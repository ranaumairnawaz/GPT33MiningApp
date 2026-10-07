<?php
session_start();
require_once 'includes/db.php';

// Detect AJAX request
$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || isset($_GET['ajax']);

// Simple Routing
$route = $_GET['route'] ?? 'home';
$route = rtrim($route, '/');

// Handle API routes specifically
if (strpos($route, 'api/') === 0) {
    $api_file = $route . '.php';
    if (file_exists($api_file)) {
        include $api_file;
    } else {
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['status' => 'error', 'message' => 'API endpoint not found']);
    }
    exit;
}

// Auth Guard
if (!isset($_SESSION['user_id']) && $route !== 'login' && $route !== 'register' && $route !== 'home') {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['redirect' => '/login']);
        exit;
    }
    header('Location: /login');
    exit;
}

// Auto-redirect if already logged in
if (isset($_SESSION['user_id']) && ($route === 'login' || $route === 'register' || $route === 'home')) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['redirect' => '/dashboard']);
        exit;
    }
    header('Location: /dashboard');
    exit;
}

// Define valid routes
$routes = [
    'home' => 'views/home.php',
    'login' => 'views/login.php',
    'register' => 'views/register.php',
    'dashboard' => 'views/dashboard.php',
    'invest' => 'views/invest.php',
    'deposit' => 'views/deposit.php',
    'withdraw' => 'views/withdraw.php',
    'investments' => 'views/investments.php',
    'referrals' => 'views/referrals.php',
    'transactions' => 'views/transactions.php',
    'notifications' => 'views/notifications.php',
    'profile' => 'views/profile.php',
    'support' => 'views/support.php',
    'chat' => 'views/chat.php',
    'menu' => 'views/menu.php',
    'feed' => 'views/feed.php',
    'logout' => 'api/logout.php'
];

// Handle Logout early
if ($route === 'logout') {
    session_destroy();
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['redirect' => '/login']);
        exit;
    }
    header('Location: /login');
    exit;
}

// Default to 404 if route not found
$view_file = $routes[$route] ?? 'views/404.php';

// If AJAX, just return the view
if ($is_ajax) {
    if (file_exists($view_file)) {
        include $view_file;
    } else {
        include 'views/404.php';
    }
    exit;
}

// Fetch site settings
$stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
$settings = $stmt->fetch();
$site_name = $settings['site_name'] ?? 'GPT33';

// Maintenance Mode Check
if (($settings['is_maintenance'] ?? 0) == 1 && (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1)) {
    // Check if it's an AJAX request
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'maintenance', 'message' => 'Site is under maintenance']);
        exit;
    }
    // Simple maintenance page
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <title>Maintenance - <?php echo htmlspecialchars($site_name); ?></title>
        <style>
            body {
                background: #0f172a;
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                height: 100vh;
                font-family: sans-serif;
                margin: 0;
            }

            .card {
                text-align: center;
                padding: 40px;
                background: rgba(255, 255, 255, 0.05);
                border-radius: 20px;
                border: 1px solid rgba(255, 255, 255, 0.1);
                max-width: 400px;
            }

            h1 {
                color: #3b82f6;
            }
        </style>
    </head>

    <body>
        <div class="card">
            <h1>Maintenance</h1>
            <p>Our intelligent systems are undergoing scheduled optimization. We will be back online shortly.</p>
        </div>
    </body>

    </html>
    <?php
    exit;
}

// For full page load, return the shell
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <?php
    $seo_titles = [
        'home' => 'GPT33 | Intelligent AI Computing & Automated Investments',
        'login' => 'Login | Secure Access to GPT33 AI Portal',
        'register' => 'Join GPT33 | Start Your AI Computing Journey',
        'feed' => 'Broadcast Feed | GPT33 Institutional Broadcast'
    ];
    $seo_desc = [
        'home' => 'GPT33 offers high-yield AI computing investment plans. Maximize your returns with our automated intelligence platform.',
        'login' => 'Access your secure GPT33 dashboard. Manage your AI computing investments and track your earnings in real-time.',
        'register' => 'Create a free account on GPT33 and start earning from the future of AI computing today.',
        'feed' => 'Track secure institutional disbursements and announcements on the official GPT33 broadcast feed.'
    ];
    $current_title = $seo_titles[$route] ?? ($site_name . ' | Dashboard');
    $current_desc = $seo_desc[$route] ?? 'The world\'s most advanced AI computing investment platform.';
    ?>
    <title><?php echo htmlspecialchars($current_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($current_desc); ?>">
    <meta name="keywords"
        content="AI Computing, GPT33, AI Investment, Automated Trading, High Yield, Crypto Investment, Passive Income, AI Returns">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://gpt33.online/">
    <meta property="og:title" content="<?php echo htmlspecialchars($current_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($current_desc); ?>">
    <meta property="og:image" content="/assets/img/seo_og.png">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:title" content="<?php echo htmlspecialchars($current_title); ?>">
    <meta property="twitter:description" content="<?php echo htmlspecialchars($current_desc); ?>">
    <meta property="twitter:image" content="/assets/img/seo_og.png">

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#059669">
    <link rel="icon" type="image/png" href="/assets/img/logo.png">
    <link rel="apple-touch-icon" href="/assets/img/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then(reg => console.log('SW Registered'))
                    .catch(err => console.log('SW Error', err));
            });
        }
    </script>
</head>

<body data-theme="dark"
    class="route-<?php echo $route; ?> <?php echo isset($_SESSION['user_id']) ? 'is-logged-in' : 'is-logged-out'; ?>">

    <!-- App Container -->
    <div id="app">
        <!-- Global Preloader -->
        <div id="global-preloader"
            style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(2, 6, 23, 0.8); z-index: 9999; flex-direction: column; align-items: center; justify-content: center; backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); transition: opacity 0.15s cubic-bezier(0.4, 0, 0.2, 1);">
            <div class="loader-content" style="text-align: center; animation: pulse 2s infinite;">
                <img src="/assets/img/logo.png"
                    style="height: 60px; width: auto; margin-bottom: 25px; filter: drop-shadow(0 0 15px var(--primary));">
                <div class="spinner"
                    style="width: 40px; height: 40px; border: 3px solid rgba(5, 150, 105, 0.1); border-top-color: var(--primary); border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto;">
                </div>
                <div
                    style="margin-top: 15px; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 2px; font-weight: 700;">
                    Optimizing Systems...</div>
            </div>
        </div>
        <!-- Navigation Header (Always in DOM for SPA, hidden via CSS if logged out) -->
        <header id="main-header">
            <div class="header-container">
                <div class="logo">
                    <img src="/assets/img/logo.png" alt="GPT33" style="height: 32px; width: auto; border-radius: 6px;">
                    <span>GPT33</span>
                </div>
                <div class="header-actions">
                    <a href="/support" data-link style="color: inherit; text-decoration: none;">
                        <button id="support-btn"><i class="fa-solid fa-headset"></i></button>
                    </a>
                    <a href="/notifications" data-link
                        style="color: inherit; text-decoration: none; position: relative;">
                        <button id="notify-btn"><i class="fa-regular fa-bell"></i></button>
                        <span id="notif-badge"
                            style="display: none; position: absolute; top: 0; right: 0; width: 10px; height: 10px; background: #ef4444; border-radius: 50%; border: 2px solid var(--bg-card);"></span>
                    </a>
                    <button id="profile-btn"><i class="fa-regular fa-user" onclick="navigateTo('menu')"></i></button>
                </div>
            </div>
        </header>

        <!-- Global Announcement Modal -->
        <?php if (($settings['announcement_active'] ?? 0) == 1): ?>
            <div id="announcement-modal" class="modal-overlay" style="display: none;">
                <div class="announcement-card">
                    <div class="modal-header-actions">
                        <button class="close-btn" onclick="closeAnnouncement()" aria-label="Close announcement">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="announcement-icon-badge">
                            <i class="fa-solid fa-bullhorn fa-beat"></i>
                        </div>
                        <h4>Institutional Broadcast</h4>

                        <?php if (!empty($settings['announcement_image'])): ?>
                            <img src="/<?php echo htmlspecialchars($settings['announcement_image']); ?>"
                                 alt="Announcement Media"
                                 class="announcement-img">
                        <?php endif; ?>

                        <div class="announcement-description">
                            <?php echo nl2br(htmlspecialchars($settings['announcement_text'])); ?>
                        </div>

                        <button class="announcement-btn" onclick="closeAnnouncement()">ACKNOWLEDGE</button>
                    </div>
                </div>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const lastSeen = localStorage.getItem('announcement_seen');
                    const currentHash = "<?php echo md5($settings['announcement_text'] . $settings['announcement_image']); ?>";

                    if (lastSeen !== currentHash) {
                        setTimeout(() => {
                            document.getElementById('announcement-modal').style.display = 'flex';
                        }, 1000);
                    }
                });

                function closeAnnouncement() {
                    const currentHash = "<?php echo md5($settings['announcement_text'] . $settings['announcement_image']); ?>";
                    localStorage.setItem('announcement_seen', currentHash);
                    document.getElementById('announcement-modal').style.display = 'none';
                }
            </script>
        <?php endif; ?>

        <!-- Return to Admin Panel (For Impersonated Sessions) -->
        <?php if (isset($_SESSION['original_admin_id'])): ?>
            <div style="position: fixed; bottom: 85px; left: 20px; z-index: 9999;">
                <a href="/api/admin_actions?action=restore_admin"
                    class="btn btn-dark shadow-lg border border-primary px-4 py-2 rounded-pill fw-bold"
                    style="font-size: 0.8rem;">
                    <i class="fa-solid fa-user-shield me-2"></i> RETURN TO ADMIN
                </a>
            </div>
        <?php endif; ?>

        <!-- Main Content Area -->
        <main id="app-content">
            <?php
            if (file_exists($view_file)) {
                include $view_file;
            } else {
                include 'views/404.php';
            }
            ?>
        </main>

        <!-- Bottom Navigation (Always in DOM for SPA, hidden via CSS if logged out) -->
        <nav id="bottom-nav">
            <a href="/dashboard" class="nav-item active" data-link>
                <i class="fa-solid fa-house"></i>
                <span>Home</span>
            </a>
            <a href="/transactions" class="nav-item" data-link>
                <i class="fa-solid fa-receipt"></i>
                <span>Logs</span>
            </a>
            <a href="/invest" class="nav-item" data-link>
                <i class="fa-solid fa-chart-line"></i>
                <span>Invest</span>
            </a>
            <a href="/deposit" class="nav-item" data-link>
                <i class="fa-solid fa-wallet"></i>
                <span>Deposit</span>
            </a>
            <a href="/menu" class="nav-item" data-link>
                <i class="fa-solid fa-bars"></i>
                <span>Menu</span>
            </a>
        </nav>
    </div>

    <!-- Core App JS -->
    <script src="assets/js/app.js"></script>
    <style>
        /* Full Screen View Overrides */
        .route-menu #main-header,
        .route-menu #bottom-nav,
        .route-deposit #main-header,
        .route-deposit #bottom-nav,
        .route-withdraw #main-header,
        .route-withdraw #bottom-nav,
        .route-invest #main-header,
        .route-invest #bottom-nav,
        .route-investments #main-header,
        .route-investments #bottom-nav,
        .route-transactions #main-header,
        .route-transactions #bottom-nav,
        .route-referrals #main-header,
        .route-referrals #bottom-nav,
        .route-chat #main-header,
        .route-chat #bottom-nav,
        .route-notifications #main-header,
        .route-notifications #bottom-nav,
        .route-feed #main-header,
        .route-feed #bottom-nav {
            display: none !important;
        }

        .route-menu #app-content,
        .route-deposit #app-content,
        .route-withdraw #app-content,
        .route-invest #app-content,
        .route-investments #app-content,
        .route-transactions #app-content,
        .route-referrals #app-content,
        .route-chat #app-content,
        .route-notifications #app-content,
        .route-feed #app-content {
            padding: 0 !important;
            margin: 0 !important;
            max-width: 100% !important;
        }

        /* Standard Header for Full Screen Views */
        .view-header {
            padding: 20px;
            background: var(--bg-card);
            border-bottom: 1px solid var(--glass-border);
            display: flex;
            align-items: center;
            gap: 15px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .view-header h2 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--text-main);
        }

        .view-header a {
            color: var(--text-main);
            font-size: 1.2rem;
            text-decoration: none;
        }
    </style>
    <script>
        // Theme Persistence
        const savedTheme = localStorage.getItem('user-theme') || 'dark';
        document.body.setAttribute('data-theme', savedTheme);
    </script>
</body>

</html>