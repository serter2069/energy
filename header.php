<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? 0;
$current_page = basename($_SERVER['PHP_SELF']);

function asyncRequest($url) {
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: PHP\r\n",
            'timeout' => 1,
        ],
    ];
    $context = stream_context_create($opts);
    file_get_contents($url, false, $context);
}
function makeRequest($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

if ($user_id) {
    $url = "/create_missing_reports.php?user_id=" . $user_id;
    $result = makeRequest($url);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Energy Diary'; ?></title>
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="manifest" href="/manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="Energy Diary">
    <link rel="apple-touch-icon" href="/img/icon-192x192.png">
    <style>
    :root {
        --primary-blue: #476EF9;
        --background-color: #f4f4f4;
    }
    body {
        background-color: var(--background-color);
        min-height: 100vh;
        margin: 0;
        padding: 0;
    }
    .navbar {
        background-color: var(--primary-blue);
        padding: .5rem 0;
        width: 100%;
    }
    .container-fluid {
        width: 100%;
        padding-right: 15px;
        padding-left: 15px;
        margin-right: auto;
        margin-left: auto;
    }
    .navbar-brand img {
        height: 80px;
        width: auto;
    }
    .nav-link {
        color: #fff !important;
    }
    .nav-link:hover {
        color: #f0f0f0 !important;
    }
    .navbar-toggler {
        border-color: #fff;
    }
    .navbar-toggler-icon {
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 30 30' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath stroke='rgba(255, 255, 255, 1)' stroke-width='2' stroke-linecap='round' stroke-miterlimit='10' d='M4 7h22M4 15h22M4 23h22'/%3E%3C/svg%3E");
    }
    .navbar-nav {
        margin-left: 20px;
    }
    </style>
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/service-worker.js')
                .then(registration => {
                    console.log('Service Worker registered successfully:', registration.scope);
                })
                .catch(error => {
                    console.log('Service Worker registration failed:', error);
                });
        });
    }
    </script>
</head>
<body>
<header>
    <nav class="navbar navbar-expand-md">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php"><img src="img/logo.png" alt="Logo" loading="lazy"></a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <?php if ($user_id): ?>
                        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="settings.php">Settings</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Log Out</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php">Log In</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</header>
<div class="content-wrapper">