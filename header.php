<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/base_config.php';

if (getenv('APP_DEBUG')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    header("Location: /login.php");
    exit();
}

include_once __DIR__ . '/includes/notifications/notification_data.php';
include_once __DIR__ . '/includes/messages.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php include __DIR__ . '/includes/stylesheets.php'; ?>

    <script src="/js/script.js"></script>
    <title>Divine Word</title>
    <script>
        function toggleNotifications(e) {
            if (e && e.stopPropagation) e.stopPropagation();
            const dropdown = document.querySelector('.notifications-dropdown');
            const button = document.querySelector('.notifications-button');
            if (!dropdown) return;
            const isVisible = dropdown.style.display === 'block';
            closeAllDropdowns();
            dropdown.style.display = isVisible ? 'none' : 'block';
            if (button) button.setAttribute('aria-expanded', isVisible ? 'false' : 'true');
        }

        function closeAllDropdowns() {
            document.querySelectorAll('.notifications-dropdown').forEach(d => d.style.display = 'none');
            document.querySelectorAll('.notifications-button').forEach(b => b.setAttribute('aria-expanded', 'false'));
        }

        document.addEventListener('click', function(event) {
            const dropdown = document.querySelector('.notifications-dropdown');
            const button = document.querySelector('.notifications-button');
            if (!dropdown || !button) return;
            if (!dropdown.contains(event.target) && !button.contains(event.target)) {
                dropdown.style.display = 'none';
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.querySelector('.notifications-button');
            if (btn) btn.addEventListener('click', toggleNotifications);
        });

        function logout() {
            window.location.href = '?logout=true';
        }
    </script>
</head>

<body>
    <header style="background-image: url('/images/banner.jpg');">
        <div class="header-content">
            <div class="logo">
                <img src="/images/logo.png" alt="Logo">
            </div>
            <div class="user-info">
                <div class="search-bar">
                    <form action="/search.php" method="GET">
                        <input type="text" name="query" placeholder="Search...">
                        <button type="submit">Search</button>
                    </form>
                </div>
                <?php
                if (isset($_SESSION['username'])) {
                    $user_id = (int)$_SESSION['user_id'];
                    $notifications = get_notifications($user_id, false, false, 5);
                    $notification_count = notification_unread_count($user_id);
                    $message_count = app_message_unread_count($user_id);
                ?>
                    <div class="user-menu">
                        <button class="notifications-button" type="button" onclick="toggleNotifications(event)" aria-expanded="false">
                            <span>Hello, <?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="notification-count"><?php echo htmlspecialchars($notification_count, ENT_QUOTES, 'UTF-8'); ?></span>
                        </button>
                    <div class="notifications-dropdown">
                        <div class="notifications-dropdown__header">
                            <strong>Notifications</strong>
                            <span><?php echo (int)$notification_count; ?> unread</span>
                        </div>
                        <div class="notifications-dropdown__actions">
                            <a href="/notifications.php">View All</a>
                            <a href="/userportal/messages.php">Messages<?php echo $message_count > 0 ? ' (' . (int)$message_count . ')' : ''; ?></a>
                        </div>
                        <?php if ($notifications): ?>
                            <?php foreach ($notifications as $notification): ?>
                                <?php
                                $link = notification_link($notification);
                                $created = strtotime((string)($notification['created_at'] ?? ''));
                                $createdLabel = $created ? date('M j, H:i', $created) : '';
                                ?>
                                <a class="notification" href="<?php echo htmlspecialchars($link, ENT_QUOTES, 'UTF-8'); ?>">
                                    <span class="notification__type"><?php echo htmlspecialchars($notification['notification_type'] ?? 'general', ENT_QUOTES, 'UTF-8'); ?></span>
                                    <strong><?php echo htmlspecialchars($notification['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span><?php echo strip_tags(htmlspecialchars_decode($notification['message']), '<strong><em><span><br>'); ?></span>
                                    <?php if ($createdLabel !== ''): ?>
                                        <small><?php echo htmlspecialchars($createdLabel, ENT_QUOTES, 'UTF-8'); ?></small>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <a class="notification notification--empty" href="/notifications.php">
                                <strong>No new notifications</strong>
                                <span>You are all caught up.</span>
                            </a>
                        <?php endif; ?>
                    </div>
                    </div>
                    <button class="auth-button" onclick="logout()">Logout</button>
                <?php
                } else {
                ?>
                    <button class="auth-button" onclick="window.location.href='/login.php'">Login</button>
                <?php
                }
                ?>
            </div>
        </div>
        <div class="hamburger">☰</div> <!-- Hamburger Icon -->
        <nav>
            <ul class="nav-links">
                <li><a href="/index.php">Home</a></li>
                <?php $currentUserRole = $_SESSION['user_role'] ?? ''; ?>
                <?php if (in_array($currentUserRole, ['admin', 'editor'], true)) : ?>
                    <li><a href='/admin/admin_panel.php'>Admin</a></li>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_id']) && in_array($currentUserRole, ['admin', 'editor', 'member'], true)) : ?>
                    <li><a href='/userportal/user_portal.php'>User Portal</a></li>
                <?php endif; ?>
                <li><a href='/members.php'>Members</a></li>
                <li><a href='/contact.php'>Contact Us</a></li>
                <li><a href='/about.php'>About</a></li>
            </ul>
        </nav>
    </header>
