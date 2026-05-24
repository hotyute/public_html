<?php
require_once __DIR__ . '/includes/session.php';
include_once __DIR__ . '/includes/notifications/notification_data.php';
include_once __DIR__ . '/includes/messages.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $action = $_POST['action'] ?? '';
        if ($action === 'remove') {
            $notification_id = filter_input(INPUT_POST, 'notification_id', FILTER_VALIDATE_INT);
            if ($notification_id) {
                remove_notification($notification_id, $user_id);
            }
        } elseif ($action === 'mark_all_read') {
            mark_notifications_as_read($user_id);
        } elseif ($action === 'remove_all') {
            remove_all_notifications($user_id);
        }
    }
    header('Location: /notifications.php');
    exit();
}

$notifications_list = get_notifications($user_id, true, false, 100);
$unread_count = notification_unread_count($user_id);
$message_count = app_message_unread_count($user_id);
mark_notifications_as_read($user_id);
?>

<?php include 'header.php'; ?>
<div class="notifications-page">
    <section class="notifications-hero">
        <div>
            <p class="section-kicker">Activity</p>
            <h1>Your Notifications</h1>
            <p>Review message alerts, assigned tests, account updates, and recent site activity.</p>
        </div>
        <div class="notifications-hero__actions">
            <a class="button" href="/userportal/messages.php">Messages<?= $message_count > 0 ? ' (' . (int)$message_count . ')' : '' ?></a>
            <a class="notifications-secondary" href="/userportal/user_portal.php">User Portal</a>
        </div>
    </section>

    <section class="notifications-summary" aria-label="Notification summary">
        <article>
            <span>Unread</span>
            <strong><?= number_format($unread_count) ?></strong>
        </article>
        <article>
            <span>Total</span>
            <strong><?= number_format(notification_total_count($user_id)) ?></strong>
        </article>
        <article>
            <span>Unread Messages</span>
            <strong><?= number_format($message_count) ?></strong>
        </article>
    </section>

    <section class="notifications-panel">
        <div class="notifications-panel__heading">
            <div>
                <p class="section-kicker">Timeline</p>
                <h2>Latest Activity</h2>
            </div>
            <div class="notifications-panel__actions">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="mark_all_read">
                    <button type="submit" class="secondary-button">Mark Read</button>
                </form>
                <form method="POST" data-confirm-submit="Clear all notifications?">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="remove_all">
                    <button type="submit" class="secondary-button">Clear All</button>
                </form>
            </div>
        </div>

        <?php if ($notifications_list): ?>
            <div class="notifications-list">
                <?php foreach ($notifications_list as $notification): ?>
                    <?php
                    $link = notification_link($notification);
                    $isUnread = !(bool)$notification['is_read'];
                    $created = strtotime((string)$notification['created_at']);
                    ?>
                    <article class="notification-main<?= $isUnread ? ' is-unread' : '' ?>">
                        <a href="<?= htmlspecialchars($link, ENT_QUOTES, 'UTF-8') ?>">
                            <span class="notification-main__type"><?= htmlspecialchars($notification['notification_type'] ?? 'general', ENT_QUOTES, 'UTF-8') ?></span>
                            <strong><?= htmlspecialchars($notification['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <span><?= strip_tags(htmlspecialchars_decode($notification['message']), '<a><strong><em><span><br>') ?></span>
                            <small><?= $created ? htmlspecialchars(date('M j, Y H:i', $created), ENT_QUOTES, 'UTF-8') : '' ?></small>
                        </a>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="notification_id" value="<?= (int)$notification['id'] ?>">
                            <button type="submit" class="remove-button">Delete</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="notifications-empty">No notifications yet.</p>
        <?php endif; ?>
    </section>
</div>
<script>
document.addEventListener('submit', function(event) {
    const form = event.target.closest('[data-confirm-submit]');
    if (!form) return;
    const message = form.dataset.confirmSubmit || 'Continue?';
    if (typeof window.appConfirmDialog !== 'function') {
        if (!window.confirm(message)) event.preventDefault();
        return;
    }
    if (form.dataset.confirmed === '1') {
        form.dataset.confirmed = '0';
        return;
    }
    event.preventDefault();
    window.appConfirmDialog({
        title: 'Clear Notifications?',
        message,
        confirmText: 'Clear All',
        cancelText: 'Cancel'
    }).then(confirmed => {
        if (confirmed) {
            form.dataset.confirmed = '1';
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        }
    });
});
</script>
<?php include 'footer.php'; ?>
