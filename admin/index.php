<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: /login');
    exit;
}

// Simple Routing
$route = $_GET['route'] ?? 'dashboard';
$route = rtrim($route, '/');

// Define valid admin routes
$routes = [
    'dashboard' => 'views/dashboard.php',
    'users' => 'views/users.php',
    'deposits' => 'views/deposits.php',
    'withdrawals' => 'views/withdrawals.php',
    'methods' => 'views/methods.php',
    'settings' => 'views/settings.php',
    'chat' => 'views/chat.php',
    'security' => 'views/security.php',
    'analytics' => 'views/analytics.php',
    'transactions' => 'views/transactions.php',
    'investments' => 'views/investments.php',
    'user_details' => 'views/user_details.php',
    'admins' => 'views/admins.php',
    'admin_logs' => 'views/admin_logs.php',
    'plans' => 'views/plans.php',
    'audit_logs' => 'views/audit_logs.php',
    'integrity_audit' => 'views/integrity_audit.php'
];

$view_file = $routes[$route] ?? 'views/404.php';

// Success/Error Message Handling
$alert = null;
if (isset($_SESSION['admin_success'])) {
    $alert = ['type' => 'success', 'message' => $_SESSION['admin_success']];
    unset($_SESSION['admin_success']);
} elseif (isset($_SESSION['admin_error'])) {
    $alert = ['type' => 'danger', 'message' => $_SESSION['admin_error']];
    unset($_SESSION['admin_error']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Executive Hub - GPT33</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/img/logo.png">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Fonts & Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Core Scripts (Deferred for performance and availability) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js" defer></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js" defer></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js" defer></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js" defer></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js" defer></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js" defer></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js" defer></script>

    <style>
        :root {
            /* SaaS Light Mode - Eye Friendly */
            --bs-body-bg: #f8fafc;
            --bs-body-color: #1e293b;
            --sidebar-bg: #ffffff;
            --card-bg: #ffffff;
            --primary-accent: #4f46e5;
            --sidebar-width: 280px;
            --border-color: #e2e8f0;
            --soft-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            --header-height: 80px;
            --nav-link-color: #64748b;
            --nav-link-active-bg: #f1f5f9;
            --text-main: #1e293b;
            --text-muted: #64748b;
        }

        [data-theme="dark"] {
            /* SaaS Dark Mode - Eye Friendly Deep Slate */
            --bs-body-bg: #0f172a;
            --bs-body-color: #e2e8f0;
            --sidebar-bg: #1e293b;
            --card-bg: #1e293b;
            --primary-accent: #6366f1;
            --border-color: rgba(148, 163, 184, 0.1);
            --soft-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
            --nav-link-color: #94a3b8;
            --nav-link-active-bg: rgba(99, 102, 241, 0.15);
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bs-body-bg);
            color: var(--bs-body-color);
            transition: background-color 0.3s ease, color 0.3s ease;
            overflow-x: hidden;
            letter-spacing: -0.01em;
        }

        /* Universal Legibility Overrides */
        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        .h1,
        .h2,
        .h3,
        .h4,
        .h5,
        .h6,
        .fw-bold,
        .fw-800,
        .fw-bolder {
            color: var(--text-main) !important;
        }

        .text-dark,
        .text-black,
        .text-secondary {
            color: var(--text-main) !important;
        }

        .text-muted {
            color: var(--text-muted) !important;
        }

        .text-primary {
            color: var(--primary-accent) !important;
        }

        .bg-light {
            background-color: var(--bs-body-bg) !important;
            border: 1px solid var(--border-color) !important;
            color: var(--text-main) !important;
        }

        [data-theme="dark"] .bg-light {
            background-color: rgba(255, 255, 255, 0.05) !important;
        }

        .modal-content,
        .card,
        .dropdown-menu {
            background-color: var(--card-bg) !important;
            border: 1px solid var(--border-color) !important;
            color: var(--text-main) !important;
            box-shadow: var(--soft-shadow) !important;
        }

        .table {
            color: var(--text-main) !important;
        }

        .table thead th {
            color: var(--text-muted) !important;
            border-bottom: 2px solid var(--border-color) !important;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }

        .table td {
            border-color: var(--border-color) !important;
        }

        .btn-dark {
            background-color: var(--primary-accent) !important;
            border-color: var(--primary-accent) !important;
            color: #ffffff !important;
            transition: all 0.3s ease !important;
        }

        .btn-dark:hover {
            filter: brightness(1.1);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .form-control,
        .form-select {
            background-color: var(--bs-body-bg) !important;
            border: 1px solid var(--border-color) !important;
            color: var(--text-main) !important;
            padding: 12px 16px;
            border-radius: 12px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-accent) !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1) !important;
        }

        #sidebar {
            width: var(--sidebar-width);
            height: calc(100vh - 40px);
            position: fixed;
            left: 20px;
            top: 20px;
            background: var(--sidebar-bg);
            border-radius: 32px;
            box-shadow: var(--soft-shadow);
            z-index: 1000;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .sidebar-brand {
            padding: 32px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-brand img {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            object-fit: contain;
        }

        .sidebar-brand span {
            font-weight: 800;
            font-size: 1.4rem;
            color: var(--text-main);
            letter-spacing: -0.04em;
        }

        .sidebar-scroll {
            flex-grow: 1;
            overflow-y: auto;
            padding-bottom: 20px;
        }

        .nav-label {
            padding: 24px 32px 8px;
            font-size: 0.7rem;
            font-weight: 800;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .nav-link {
            margin: 2px 20px;
            padding: 12px 16px;
            color: var(--nav-link-color);
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s ease;
            border-radius: 16px;
        }

        .nav-link:hover {
            color: var(--text-main);
            background: rgba(0, 0, 0, 0.03);
        }

        [data-theme="dark"] .nav-link:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .nav-link.active {
            color: #ffffff !important;
            background: var(--primary-accent) !important;
            box-shadow: 0 8px 16px -4px rgba(79, 70, 229, 0.3);
        }

        [data-theme="light"] .nav-link.active {
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
        }

        .nav-link i {
            width: 20px;
            font-size: 1.1rem;
            text-align: center;
        }

        #main-wrapper {
            margin-left: calc(var(--sidebar-width) + 40px);
            padding: 20px;
            min-height: 100vh;
            transition: all 0.4s ease;
        }

        header {
            height: var(--header-height);
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .search-pill {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 12px 24px;
            box-shadow: var(--soft-shadow);
            width: 400px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid var(--border-color);
        }

        .search-pill input {
            border: 0;
            outline: 0;
            font-size: 0.9rem;
            width: 100%;
            background: transparent;
            color: var(--text-main);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .theme-toggle {
            width: 45px;
            height: 45px;
            background: var(--card-bg);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 1px solid var(--border-color);
            box-shadow: var(--soft-shadow);
            color: var(--text-main);
            transition: all 0.2s;
        }

        .theme-toggle:hover {
            transform: translateY(-2px);
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 32px;
            box-shadow: var(--soft-shadow);
            overflow: hidden;
        }

        .table {
            color: var(--text-main);
            margin-bottom: 0;
        }

        .table thead th {
            background: rgba(0, 0, 0, 0.01);
            border-bottom: 1px solid var(--border-color);
            color: var(--text-muted);
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
            padding: 20px 24px;
        }

        [data-theme="dark"] .table thead th {
            background: rgba(255, 255, 255, 0.02);
        }

        .table tbody td {
            border-bottom: 1px solid var(--border-color);
            padding: 18px 24px;
        }

        .form-control,
        .form-select {
            background-color: var(--bs-body-bg);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            border-radius: 12px;
            padding: 12px 16px;
        }

        .form-control:focus {
            background-color: var(--bs-body-bg);
            color: var(--text-main);
            border-color: var(--primary-accent);
            box-shadow: none;
        }

        @media (max-width: 992px) {
            #sidebar {
                left: -100%;
                top: 0;
                height: 100vh;
                border-radius: 0;
                margin: 0;
            }

            #main-wrapper {
                margin-left: 0;
            }

            #sidebar.active {
                left: 0;
            }
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <aside id="sidebar">
        <div class="sidebar-brand">
            <img src="/assets/img/logo.png" alt="Logo">
            <span>GPT33 HUB</span>
        </div>

        <div class="sidebar-scroll">
            <div class="nav-label">Core Intelligence</div>
            <a href="index.php?route=dashboard" class="nav-link <?php echo $route == 'dashboard' ? 'active' : ''; ?>">
                <i class="fa-solid fa-house"></i> Dashboard
            </a>
            <a href="index.php?route=analytics" class="nav-link <?php echo $route == 'analytics' ? 'active' : ''; ?>">
                <i class="fa-solid fa-chart-pie"></i> Analytics
            </a>
            <a href="index.php?route=users" class="nav-link <?php echo $route == 'users' ? 'active' : ''; ?>">
                <i class="fa-solid fa-users-gear"></i> Investor Base
            </a>

            <div class="nav-label">Financial Flow</div>
            <a href="index.php?route=deposits" class="nav-link <?php echo $route == 'deposits' ? 'active' : ''; ?>">
                <i class="fa-solid fa-vault"></i> Inflow Queue
            </a>
            <a href="index.php?route=withdrawals"
                class="nav-link <?php echo $route == 'withdrawals' ? 'active' : ''; ?>">
                <i class="fa-solid fa-money-bill-transfer"></i> Outflow Queue
            </a>
            <a href="index.php?route=transactions"
                class="nav-link <?php echo $route == 'transactions' ? 'active' : ''; ?>">
                <i class="fa-solid fa-book"></i> Master Ledger
            </a>
            <a href="index.php?route=investments"
                class="nav-link <?php echo $route == 'investments' ? 'active' : ''; ?>">
                <i class="fa-solid fa-microchip"></i> Active Stakes
            </a>

            <div class="nav-label">Platform Architecture</div>
            <a href="index.php?route=chat" class="nav-link <?php echo $route == 'chat' ? 'active' : ''; ?>">
                <i class="fa-solid fa-comments"></i> Communication
            </a>
            <a href="index.php?route=admins" class="nav-link <?php echo $route == 'admins' ? 'active' : ''; ?>">
                <i class="fa-solid fa-user-shield"></i> Administrators
            </a>
            <a href="index.php?route=admin_logs" class="nav-link <?php echo $route == 'admin_logs' ? 'active' : ''; ?>">
                <i class="fa-solid fa-clock-rotate-left"></i> Activity Logs
            </a>
            <a href="index.php?route=methods" class="nav-link <?php echo $route == 'methods' ? 'active' : ''; ?>">
                <i class="fa-solid fa-network-wired"></i> Gateway Logic
            </a>
            <a href="index.php?route=plans" class="nav-link <?php echo $route == 'plans' ? 'active' : ''; ?>">
                <i class="fa-solid fa-microchip"></i> Investment Plans
            </a>
            <a href="index.php?route=audit_logs" class="nav-link <?php echo $route == 'audit_logs' ? 'active' : ''; ?>">
                <i class="fa-solid fa-shield-halved"></i> Audit Intelligence
            </a>
            <a href="index.php?route=integrity_audit"
                class="nav-link <?php echo $route == 'integrity_audit' ? 'active' : ''; ?>">
                <i class="fa-solid fa-user-shield"></i> Integrity Auditor
            </a>
            <a href="index.php?route=settings" class="nav-link <?php echo $route == 'settings' ? 'active' : ''; ?>">
                <i class="fa-solid fa-sliders"></i> Global Variables
            </a>
            <a href="index.php?route=notifications"
                class="nav-link <?php echo $route == 'notifications' ? 'active' : ''; ?>">
                <i class="fa-solid fa-bell"></i> System Alerts
            </a>
        </div>

        <div class="p-4 border-top border-white border-opacity-5">
            <div class="d-flex align-items-center gap-3">
                <div
                    style="width: 40px; height: 40px; background: var(--bs-body-bg); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--text-main); font-weight: 800; border: 1px solid var(--border-color);">
                    AD
                </div>
                <div class="flex-grow-1 overflow-hidden">
                    <div class="fw-bold small text-truncate">Executive Admin</div>
                    <div class="text-muted" style="font-size: 0.65rem;">Session: Active</div>
                </div>
                <a href="/logout" class="text-muted"><i class="fa-solid fa-power-off"></i></a>
            </div>
        </div>
    </aside>

    <!-- Main Wrapper -->
    <div id="main-wrapper">
        <header>
            <div class="d-flex align-items-center gap-3">
                <button id="toggle-sidebar" class="btn btn-link text-muted p-0 d-lg-none">
                    <i class="fa-solid fa-bars-staggered fs-4"></i>
                </button>
                <div class="search-pill">
                    <i class="fa-solid fa-search text-muted"></i>
                    <input type="text" placeholder="Search investor IDs, transactions or protocols...">
                </div>
            </div>

            <div class="header-actions">
                <!-- Notifications Dropdown -->
                <div class="dropdown me-3">
                    <button class="btn btn-link text-muted p-0 position-relative" data-bs-toggle="dropdown"
                        aria-expanded="false" id="notif-dropdown-btn">
                        <i class="fa-solid fa-bell fs-5"></i>
                        <span id="notif-badge"
                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none"
                            style="font-size: 0.5rem; padding: 0.25em 0.5em;">
                            0
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-4 p-0 overflow-hidden mt-3"
                        style="width: 320px;">
                        <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="fw-800 m-0 text-dark small">Intelligence Alerts</h6>
                            <a href="index.php?route=notifications"
                                class="text-primary small fw-bold text-decoration-none" style="font-size: 0.7rem;">View
                                All</a>
                        </div>
                        <div id="notif-list" class="overflow-auto" style="max-height: 350px;">
                            <div class="p-4 text-center text-muted small opacity-50">No new alerts detected.</div>
                        </div>
                        <div class="p-2 border-top bg-light bg-opacity-50 text-center">
                            <a href="index.php?route=notifications&mark_all_read=1"
                                class="text-muted small fw-bold text-decoration-none"
                                style="font-size: 0.65rem;">Synchronize All Alerts</a>
                        </div>
                    </div>
                </div>

                <div id="theme-toggle" class="theme-toggle">
                    <i class="fa-solid fa-moon"></i>
                </div>
                <div class="d-none d-md-block text-end">
                    <div class="small fw-bold"><?php echo date('l, jS F'); ?></div>
                    <div class="text-muted" style="font-size: 0.7rem;">Operational Status: Optimal</div>
                </div>
            </div>
        </header>

        <div class="content-body" id="app-content">
            <?php
            if (file_exists($view_file)) {
                include $view_file;
            } else {
                include 'views/404.php';
            }
            ?>
        </div>
    </div>

    <!-- Toast Notifications -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="adminToast" class="toast align-items-center text-white border-0 rounded-4 shadow-lg" role="alert"
            aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body fw-bold" id="toastMessage"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
        </div>
    </div>

    <style>
        /* DataTables Dark Mode Patch */
        [data-theme="dark"] .dataTables_wrapper .dataTables_length,
        [data-theme="dark"] .dataTables_wrapper .dataTables_filter,
        [data-theme="dark"] .dataTables_wrapper .dataTables_info,
        [data-theme="dark"] .dataTables_wrapper .dataTables_processing,
        [data-theme="dark"] .dataTables_wrapper .dataTables_paginate {
            color: var(--text-muted);
        }

        [data-theme="dark"] .page-link {
            background-color: var(--card-bg);
            border-color: var(--border-color);
            color: var(--text-muted);
        }

        [data-theme="dark"] .page-item.active .page-link {
            background-color: var(--primary-accent);
            border-color: var(--primary-accent);
            color: #fff;
        }

        .dataTables_filter input {
            border-radius: 12px;
            padding: 8px 16px;
            border: 1px solid var(--border-color);
            background: var(--bs-body-bg);
            color: var(--text-main);
        }

        .dt-buttons {
            gap: 8px;
            display: flex;
        }

        .dt-button-collection {
            border-radius: 12px !important;
            overflow: hidden;
            border: 0 !important;
            box-shadow: var(--soft-shadow) !important;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // DataTables Global Init
            if ($.fn.DataTable) {
                $('.datatable-basic').DataTable({
                    responsive: true,
                    order: [[0, 'desc']],
                    pageLength: 10,
                    dom: '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
                    buttons: [
                        {
                            extend: 'copy',
                            className: 'btn btn-sm btn-light border fw-bold px-3',
                            text: '<i class="fa-solid fa-copy me-1"></i> COPY'
                        },
                        {
                            extend: 'excel',
                            className: 'btn btn-sm btn-light border fw-bold px-3',
                            text: '<i class="fa-solid fa-file-excel me-1"></i> EXCEL'
                        },
                        {
                            extend: 'pdf',
                            className: 'btn btn-sm btn-light border fw-bold px-3',
                            text: '<i class="fa-solid fa-file-pdf me-1"></i> PDF'
                        }
                    ],
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Filter records...",
                        paginate: {
                            next: '<i class="fa-solid fa-chevron-right"></i>',
                            previous: '<i class="fa-solid fa-chevron-left"></i>'
                        }
                    }
                });
            }

            // Sidebar Toggle
            document.getElementById('toggle-sidebar')?.addEventListener('click', () => {
                document.getElementById('sidebar').classList.toggle('active');
            });

            // Theme Logic
            const themeToggle = document.getElementById('theme-toggle');
            if (themeToggle) {
                const icon = themeToggle.querySelector('i');

                function setTheme(theme) {
                    document.documentElement.setAttribute('data-theme', theme);
                    localStorage.setItem('admin-theme', theme);
                    if (icon) {
                        icon.className = theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
                    }
                }

                const savedTheme = localStorage.getItem('admin-theme') || 'light';
                setTheme(savedTheme);

                themeToggle.addEventListener('click', () => {
                    const currentTheme = document.documentElement.getAttribute('data-theme');
                    setTheme(currentTheme === 'dark' ? 'light' : 'dark');
                });
            }

            // Toast Logic
            const toastEl = document.getElementById('adminToast');
            if (toastEl && typeof bootstrap !== 'undefined') {
                const toast = new bootstrap.Toast(toastEl);

                window.showToast = function (message, type = 'success') {
                    toastEl.className = `toast align-items-center text-white border-0 rounded-4 shadow-lg bg-${type}`;
                    document.getElementById('toastMessage').textContent = message;
                    toast.show();
                }

                window.copyToClipboard = function (text) {
                    navigator.clipboard.writeText(text).then(() => {
                        if (window.showToast) window.showToast('Copied to operational clipboard');
                    });
                };
            }

            // Global AJAX Form Handler
            document.addEventListener('submit', async (e) => {
                const form = e.target;
                if (!form.hasAttribute('data-ajax-form')) return;

                e.preventDefault();
                const action = form.getAttribute('data-action') || form.getAttribute('action');
                const callback = form.getAttribute('data-callback');
                const fd = new FormData(form);

                try {
                    const resp = await fetch(action, {
                        method: 'POST',
                        body: fd,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const result = await resp.json();

                    if (result.status === 'success') {
                        if (callback && typeof window[callback] === 'function') {
                            window[callback](result);
                        } else if (result.message) {
                            if (window.showToast) window.showToast(result.message, 'success');
                            if (form.hasAttribute('data-reload')) setTimeout(() => location.reload(), 1000);
                        }
                    } else {
                        if (window.showToast) window.showToast(result.message || 'Operation failed', 'danger');
                    }
                } catch (err) {
                    console.error(err);
                    if (window.showToast) window.showToast('Intelligence link failure', 'danger');
                }
            });

            // Notifications Logic
            const fetchNotifications = async () => {
                try {
                    const resp = await fetch('/api/admin_actions?action=get_notifications');
                    const data = await resp.json();
                    if (data.status === 'success') {
                        updateNotifUI(data.notifications, data.unread_count);
                    }
                } catch (e) { }
            };

            const updateNotifUI = (notifs, count) => {
                const badge = document.getElementById('notif-badge');
                const list = document.getElementById('notif-list');

                if (count > 0) {
                    badge.textContent = count;
                    badge.classList.remove('d-none');
                } else {
                    badge.classList.add('d-none');
                }

                if (notifs.length > 0) {
                    list.innerHTML = notifs.map(n => `
                        <a href="${n.link}" class="dropdown-item p-3 border-bottom d-flex align-items-start gap-3">
                            <div style="width: 32px; height: 32px; background: var(--bs-primary); background-opacity: 0.1; color: var(--bs-primary); border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="fa-solid ${getNotifIcon(n.type)}"></i>
                            </div>
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="small fw-bold text-dark text-truncate">${n.message}</div>
                                <div class="text-muted" style="font-size: 0.6rem;">${new Date(n.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })} • Investor ${n.user_name || 'System'}</div>
                            </div>
                        </a>
                    `).join('');
                } else {
                    list.innerHTML = '<div class="p-4 text-center text-muted small opacity-50">No new alerts detected.</div>';
                }
            };

            const getNotifIcon = (type) => {
                switch (type) {
                    case 'deposit': return 'fa-vault';
                    case 'withdrawal': return 'fa-money-bill-transfer';
                    case 'chat': return 'fa-comments';
                    default: return 'fa-bell';
                }
            };

            // Initial fetch and polling
            fetchNotifications();
            setInterval(fetchNotifications, 10000);

            // Auto-clear notifications based on route
            const currentRoute = "<?php echo $route; ?>";
            if (['deposits', 'withdrawals', 'chat'].includes(currentRoute)) {
                let type = currentRoute.substring(0, currentRoute.length - 1); // remove 's'
                if (currentRoute === 'chat') type = 'chat';
                else if (currentRoute === 'deposits') type = 'deposit';
                else if (currentRoute === 'withdrawals') type = 'withdrawal';

                fetch(`/api/admin_actions?action=mark_notifications_read&type=${type}`);
            }

            <?php if ($alert): ?>
                if (window.showToast) window.showToast("<?php echo $alert['message']; ?>", "<?php echo $alert['type']; ?>");
            <?php endif; ?>
        });
    </script>
</body>

</html>