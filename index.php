<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/content_helpers.php';

$issue = app_current_issue_label();
$canEditPosts = app_can_edit_posts();

$postsStmt = $pdo->query("
    SELECT posts.id, posts.title, posts.thumbnail, posts.content, posts.created_at,
           COALESCE(users.displayname, 'Unknown') AS author,
           COALESCE(users.role, 'member') AS user_role,
           COUNT(comments.id) AS comment_count
    FROM posts
    LEFT JOIN users ON posts.user_id = users.id
    LEFT JOIN comments ON posts.id = comments.post_id
    GROUP BY posts.id, posts.title, posts.thumbnail, posts.content, posts.created_at, users.displayname, users.role
    ORDER BY posts.id DESC
    LIMIT 13
");
$posts = $postsStmt->fetchAll(PDO::FETCH_ASSOC);
$heroPost = $posts[0] ?? null;
$continuePosts = array_slice($posts, 1);

$video_link = '';
$video_file = __DIR__ . '/includes/featured_video.txt';
if (file_exists($video_file)) {
    $video_link = trim(file_get_contents($video_file));
}

$magazinesStmt = $pdo->prepare("
    SELECT title, author, image_url, article_url
    FROM magazine_articles
    WHERE issue = :issue
    ORDER BY id DESC
    LIMIT 3
");
$magazinesStmt->execute(['issue' => $issue]);
$magazineArticles = $magazinesStmt->fetchAll(PDO::FETCH_ASSOC);

function render_article_tile(array $post, bool $canEditPosts): void
{
    $postId = (int)$post['id'];
    $postUrl = '/post.php?id=' . $postId;
    $roleClass = app_user_role_class($post['user_role'] ?? '');
    ?>
    <article class="article-tile" data-inline-post data-post-id="<?= $postId ?>">
        <a class="article-tile__image" href="<?= htmlspecialchars($postUrl, ENT_QUOTES, 'UTF-8') ?>" data-edit-image>
            <img src="<?= htmlspecialchars(app_post_image_src($post['thumbnail'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?>">
        </a>
        <div class="article-tile__body">
            <a class="article-tile__title" href="<?= htmlspecialchars($postUrl, ENT_QUOTES, 'UTF-8') ?>" data-edit-field="title">
                <?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?>
            </a>
            <p class="article-meta">
                By <span class="<?= htmlspecialchars($roleClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($post['author'], ENT_QUOTES, 'UTF-8') ?></span>
                <span><?= (int)$post['comment_count'] ?> Comments</span>
            </p>
            <p class="article-tile__excerpt" data-edit-field="excerpt"><?= htmlspecialchars(app_plain_excerpt($post['content'] ?? '', 130), ENT_QUOTES, 'UTF-8') ?></p>
            <div class="article-tile__actions">
                <a href="<?= htmlspecialchars($postUrl, ENT_QUOTES, 'UTF-8') ?>">Read</a>
                <?php if ($canEditPosts): ?>
                    <button type="button" class="inline-edit-button js-edit-post" data-post-id="<?= $postId ?>">Edit</button>
                <?php endif; ?>
            </div>
        </div>
    </article>
    <?php
}

include __DIR__ . '/header.php';
?>

<div class="main-container home-layout">
    <main class="home-main">
        <section class="home-intro" data-inline-create-anchor>
            <div>
                <p class="section-kicker">Divine Word Community</p>
                <h1>Teachings, studies, and reflections for the flock.</h1>
                <p>Read the newest article, then keep moving through recent studies without losing your place.</p>
            </div>
            <div class="home-actions">
                <a class="button secondary-button" href="/archive.php">All Articles</a>
                <?php if ($canEditPosts): ?>
                    <button type="button" class="button js-new-post">New Article</button>
                <?php endif; ?>
            </div>
        </section>

        <?php if ($heroPost): ?>
            <?php
            $heroId = (int)$heroPost['id'];
            $heroUrl = '/post.php?id=' . $heroId;
            ?>
            <section class="home-hero" data-inline-post data-post-id="<?= $heroId ?>">
                <a class="home-hero__media" href="<?= htmlspecialchars($heroUrl, ENT_QUOTES, 'UTF-8') ?>" data-edit-image>
                    <img src="<?= htmlspecialchars(app_post_image_src($heroPost['thumbnail'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($heroPost['title'], ENT_QUOTES, 'UTF-8') ?>">
                </a>
                <div class="home-hero__content">
                    <p class="section-kicker">Latest Article</p>
                    <h2><a href="<?= htmlspecialchars($heroUrl, ENT_QUOTES, 'UTF-8') ?>" data-edit-field="title"><?= htmlspecialchars($heroPost['title'], ENT_QUOTES, 'UTF-8') ?></a></h2>
                    <p class="article-meta">
                        By <span class="<?= htmlspecialchars(app_user_role_class($heroPost['user_role'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($heroPost['author'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span><?= (int)$heroPost['comment_count'] ?> Comments</span>
                    </p>
                    <p class="home-hero__excerpt" data-edit-field="excerpt"><?= htmlspecialchars(app_plain_excerpt($heroPost['content'] ?? '', 240), ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="home-hero__actions">
                        <a class="button" href="<?= htmlspecialchars($heroUrl, ENT_QUOTES, 'UTF-8') ?>">Read Article</a>
                        <?php if ($canEditPosts): ?>
                            <button type="button" class="button secondary-button js-edit-post" data-post-id="<?= $heroId ?>">Edit This Article</button>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <section class="empty-state">
                <h2>No articles yet</h2>
                <p>Once posts are created, the newest one will appear here as the featured story.</p>
                <?php if ($canEditPosts): ?>
                    <button type="button" class="button js-new-post">Create First Article</button>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if ($continuePosts): ?>
            <section class="continue-reading" aria-label="Continue reading">
                <div class="section-heading">
                    <div>
                        <p class="section-kicker">Continue Reading</p>
                        <h2>Recent Articles</h2>
                    </div>
                    <div class="article-carousel__controls" aria-label="Article carousel controls">
                        <button type="button" class="article-carousel__button" data-carousel-prev aria-label="Previous articles">&larr;</button>
                        <button type="button" class="article-carousel__button" data-carousel-next aria-label="Next articles">&rarr;</button>
                    </div>
                </div>
                <div class="article-carousel" data-article-carousel>
                    <div class="article-carousel__track">
                        <?php foreach ($continuePosts as $post): ?>
                            <?php render_article_tile($post, $canEditPosts); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <section class="featured-video">
            <div class="section-heading">
                <div>
                    <p class="section-kicker">Watch</p>
                    <h2>Featured Video</h2>
                </div>
                <?php if ($canEditPosts): ?>
                    <a href="/admin/edit_video.php">Edit video</a>
                <?php endif; ?>
            </div>
            <?php if (!empty($video_link)) : ?>
                <iframe src="<?= htmlspecialchars($video_link, ENT_QUOTES, 'UTF-8') ?>" title="Featured video" allowfullscreen></iframe>
            <?php else : ?>
                <p>No featured video this week. Check back later.</p>
            <?php endif; ?>
        </section>
    </main>

    <aside class="sidebar home-sidebar">
        <div class="section-heading sidebar-heading">
            <div>
                <p class="section-kicker">External Magazines</p>
                <h3><?= htmlspecialchars($issue, ENT_QUOTES, 'UTF-8') ?></h3>
            </div>
            <?php if ($canEditPosts): ?>
                <a href="/admin/manage_magazines.php">Manage</a>
            <?php endif; ?>
        </div>
        <ul>
            <?php if ($magazineArticles): ?>
                <?php foreach ($magazineArticles as $row): ?>
                    <li>
                        <img src="<?= htmlspecialchars($row['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') ?>" class="thumbnail">
                        <span>
                            <a href="<?= htmlspecialchars($row['article_url'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') ?></a>
                            <small><?= htmlspecialchars($row['author'], ENT_QUOTES, 'UTF-8') ?></small>
                        </span>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li>No articles available for this issue.</li>
            <?php endif; ?>
        </ul>
        <a href="/magazines/all_issues.php" class="view-all">View All Issues</a>
    </aside>
</div>

<?php include __DIR__ . '/footer.php'; ?>
