<?php
require_once __DIR__ . '/../includes/session.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit();
}

require_once __DIR__ . '/../base_config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/messages.php';

$userId = (int)$_SESSION['user_id'];
$users = app_message_users($userId);
$status = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $recipientId = filter_input(INPUT_POST, 'recipient_id', FILTER_VALIDATE_INT) ?: 0;
        $subject = trim(strip_tags((string)($_POST['subject'] ?? '')));
        $body = trim((string)($_POST['body'] ?? ''));

        $validRecipient = false;
        foreach ($users as $user) {
            if ((int)$user['id'] === $recipientId) {
                $validRecipient = true;
                break;
            }
        }

        if (!$validRecipient) {
            $error = 'Please choose a valid recipient.';
        } elseif ($subject === '' || $body === '') {
            $error = 'Subject and message are required.';
        } elseif (strlen($subject) > 255) {
            $error = 'Subject is too long.';
        } else {
            $messageId = app_message_send($userId, $recipientId, $subject, $body);
            header('Location: /userportal/messages.php?sent=1&message_id=' . $messageId);
            exit();
        }
    }
}

if ((filter_input(INPUT_GET, 'sent', FILTER_VALIDATE_INT) ?: 0) === 1) {
    $status = 'Message sent.';
}

$inboxStmt = $pdo->prepare("
    SELECT messages.id, messages.subject, messages.body, messages.is_read, messages.created_at,
           COALESCE(users.displayname, users.username, 'Unknown') AS sender_name
    FROM messages
    JOIN users ON users.id = messages.sender_id
    WHERE messages.recipient_id = ?
    ORDER BY messages.created_at DESC
    LIMIT 30
");
$inboxStmt->execute([$userId]);
$inbox = $inboxStmt->fetchAll(PDO::FETCH_ASSOC);

$sentStmt = $pdo->prepare("
    SELECT messages.id, messages.subject, messages.created_at,
           COALESCE(users.displayname, users.username, 'Unknown') AS recipient_name
    FROM messages
    JOIN users ON users.id = messages.recipient_id
    WHERE messages.sender_id = ?
    ORDER BY messages.created_at DESC
    LIMIT 12
");
$sentStmt->execute([$userId]);
$sentMessages = $sentStmt->fetchAll(PDO::FETCH_ASSOC);

$selectedMessageId = filter_input(INPUT_GET, 'message_id', FILTER_VALIDATE_INT) ?: (int)($inbox[0]['id'] ?? 0);
$selectedMessage = $selectedMessageId > 0 ? app_message_find($selectedMessageId, $userId) : null;
if ($selectedMessage && (int)$selectedMessage['recipient_id'] === $userId && !(bool)$selectedMessage['is_read']) {
    app_message_mark_read((int)$selectedMessage['id'], $userId);
    $selectedMessage['is_read'] = 1;
}

include __DIR__ . '/../header.php';
?>
<div class="portal-dashboard portal-dashboard--messages">
    <section class="portal-hero">
        <div>
            <p class="section-kicker">Messages</p>
            <h1>Member Inbox</h1>
            <p>Send private messages to other members and keep track of replies from the same portal area.</p>
        </div>
        <div class="portal-hero__actions">
            <a class="button" href="/userportal/user_portal.php">Portal</a>
            <a class="portal-secondary" href="/notifications.php">Notifications</a>
        </div>
    </section>

    <?php if ($status !== ''): ?>
        <p class="portal-alert portal-alert--success"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p class="portal-alert portal-alert--error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <div class="message-layout">
        <section class="portal-card message-compose">
            <div class="portal-card__heading">
                <div>
                    <p class="section-kicker">Compose</p>
                    <h2>New Message</h2>
                </div>
            </div>
            <form method="POST" class="portal-form">
                <input type="hidden" name="action" value="send">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                <label>
                    To
                    <select name="recipient_id" required>
                        <option value="">Choose recipient</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= (int)$user['id'] ?>">
                                <?= htmlspecialchars($user['displayname'] ?: $user['username'], ENT_QUOTES, 'UTF-8') ?>
                                (<?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Subject
                    <input type="text" name="subject" maxlength="255" required>
                </label>
                <label>
                    Message
                    <textarea name="body" rows="7" required></textarea>
                </label>
                <button type="submit">Send Message</button>
            </form>
        </section>

        <section class="portal-card message-inbox">
            <div class="portal-card__heading">
                <div>
                    <p class="section-kicker">Inbox</p>
                    <h2>Received</h2>
                </div>
                <span><?= number_format(app_message_unread_count($userId)) ?> unread</span>
            </div>
            <?php if ($inbox): ?>
                <div class="message-list">
                    <?php foreach ($inbox as $message): ?>
                        <a class="message-list__item<?= (int)$message['is_read'] === 0 ? ' is-unread' : '' ?><?= (int)$message['id'] === (int)($selectedMessage['id'] ?? 0) ? ' is-active' : '' ?>" href="/userportal/messages.php?message_id=<?= (int)$message['id'] ?>">
                            <strong><?= htmlspecialchars($message['subject'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <span><?= htmlspecialchars($message['sender_name'], ENT_QUOTES, 'UTF-8') ?> &middot; <?= htmlspecialchars(date('M j, H:i', strtotime($message['created_at'])), ENT_QUOTES, 'UTF-8') ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="portal-empty">No received messages yet.</p>
            <?php endif; ?>
        </section>

        <section class="portal-card message-reader">
            <div class="portal-card__heading">
                <div>
                    <p class="section-kicker">Selected Message</p>
                    <h2><?= $selectedMessage ? htmlspecialchars($selectedMessage['subject'], ENT_QUOTES, 'UTF-8') : 'No message selected' ?></h2>
                </div>
            </div>
            <?php if ($selectedMessage): ?>
                <p class="message-reader__meta">
                    From <?= htmlspecialchars($selectedMessage['sender_name'], ENT_QUOTES, 'UTF-8') ?>
                    to <?= htmlspecialchars($selectedMessage['recipient_name'], ENT_QUOTES, 'UTF-8') ?>
                    &middot;
                    <?= htmlspecialchars(date('M j, Y H:i', strtotime($selectedMessage['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                </p>
                <div class="message-reader__body">
                    <?= nl2br(htmlspecialchars($selectedMessage['body'], ENT_QUOTES, 'UTF-8')) ?>
                </div>
            <?php else: ?>
                <p class="portal-empty">Choose a message from your inbox.</p>
            <?php endif; ?>
        </section>

        <section class="portal-card message-sent">
            <div class="portal-card__heading">
                <div>
                    <p class="section-kicker">Sent</p>
                    <h2>Recent Sent</h2>
                </div>
            </div>
            <?php if ($sentMessages): ?>
                <div class="message-list message-list--compact">
                    <?php foreach ($sentMessages as $message): ?>
                        <a class="message-list__item" href="/userportal/messages.php?message_id=<?= (int)$message['id'] ?>">
                            <strong><?= htmlspecialchars($message['subject'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <span>To <?= htmlspecialchars($message['recipient_name'], ENT_QUOTES, 'UTF-8') ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="portal-empty">Sent messages will appear here.</p>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php include __DIR__ . '/../footer.php'; ?>
