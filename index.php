<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/content_helpers.php';

$issue = app_current_issue_label();
$canEditPosts = app_can_edit_posts();
$canManageMagazines = app_can_manage_magazines();

$postsStmt = $pdo->query("
    SELECT posts.id, posts.title, posts.thumbnail, posts.thumbnail_style, posts.content, posts.created_at,
           COALESCE(users.displayname, 'Unknown') AS author,
           COALESCE(users.role, 'member') AS user_role,
           COUNT(comments.id) AS comment_count
    FROM posts
    LEFT JOIN users ON posts.user_id = users.id
    LEFT JOIN comments ON posts.id = comments.post_id
    GROUP BY posts.id, posts.title, posts.thumbnail, posts.thumbnail_style, posts.content, posts.created_at, users.displayname, users.role
    ORDER BY posts.id DESC
    LIMIT 13
");
$posts = $postsStmt->fetchAll(PDO::FETCH_ASSOC);
$heroPost = $posts[0] ?? null;
$continuePosts = array_slice($posts, 1);

$videoData = app_featured_video_data();
$video_link = $videoData['url'];
$video_embed = $videoData['embed'];
$video_preview = $videoData['preview'];
$video_style = $videoData['container_style'];

$magazinesStmt = $pdo->query("
    SELECT id, title, author, image_url, article_url, issue, DATE(published_date) AS published_date
    FROM magazine_articles
    ORDER BY published_date DESC, id DESC
    LIMIT 3
");
$magazineArticles = $magazinesStmt->fetchAll(PDO::FETCH_ASSOC);

function render_editable_excerpt(string $tag, string $className, ?string $content, int $limit): void
{
    $excerpt = app_plain_excerpt_data($content, $limit);
    $tagName = in_array($tag, ['div', 'span'], true) ? $tag : 'div';
    $text = htmlspecialchars($excerpt['text'], ENT_QUOTES, 'UTF-8');
    $class = htmlspecialchars($className, ENT_QUOTES, 'UTF-8');
    $previewText = htmlspecialchars($excerpt['text'], ENT_QUOTES, 'UTF-8');
    $generatedEllipsis = $excerpt['is_truncated'] ? '1' : '0';

    echo '<' . $tagName . ' class="' . $class . '" data-edit-field="content" data-preview-text="' . $previewText . '" data-preview-generated-ellipsis="' . $generatedEllipsis . '">';
    echo $text;
    if ($excerpt['is_truncated']) {
        echo '<span class="preview-ellipsis" contenteditable="false" aria-hidden="true">...</span>';
    }
    echo '</' . $tagName . '>';
}

function render_article_tile(array $post, bool $canEditPosts): void
{
    $postId = (int)$post['id'];
    $postUrl = '/post.php?id=' . $postId;
    $roleClass = app_user_role_class($post['user_role'] ?? '');
    $thumbnailStyle = app_safe_image_style($post['thumbnail_style'] ?? '');
    ?>
    <article class="article-tile" data-inline-post data-post-id="<?= $postId ?>">
        <a class="article-tile__image" href="<?= htmlspecialchars($postUrl, ENT_QUOTES, 'UTF-8') ?>" data-edit-image>
            <img src="<?= htmlspecialchars(app_post_image_src($post['thumbnail'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?>"<?= $thumbnailStyle !== '' ? ' style="' . htmlspecialchars($thumbnailStyle, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
        </a>
        <div class="article-tile__body">
            <a class="article-tile__title" href="<?= htmlspecialchars($postUrl, ENT_QUOTES, 'UTF-8') ?>" data-edit-field="title">
                <?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?>
            </a>
            <p class="article-meta">
                By <span class="<?= htmlspecialchars($roleClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($post['author'], ENT_QUOTES, 'UTF-8') ?></span>
                <span><?= (int)$post['comment_count'] ?> Comments</span>
            </p>
            <?php render_editable_excerpt('div', 'article-tile__excerpt', $post['content'] ?? '', 130); ?>
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

function render_mobile_article_row(array $post, bool $canEditPosts): void
{
    $postId = (int)$post['id'];
    $postUrl = '/post.php?id=' . $postId;
    $thumbnailStyle = app_safe_image_style($post['thumbnail_style'] ?? '');
    ?>
    <article class="article-mobile-row" data-inline-post data-post-id="<?= $postId ?>">
        <a class="article-mobile-row__image" href="<?= htmlspecialchars($postUrl, ENT_QUOTES, 'UTF-8') ?>" data-edit-image>
            <img src="<?= htmlspecialchars(app_post_image_src($post['thumbnail'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?>"<?= $thumbnailStyle !== '' ? ' style="' . htmlspecialchars($thumbnailStyle, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
        </a>
        <div>
            <a class="article-mobile-row__title" href="<?= htmlspecialchars($postUrl, ENT_QUOTES, 'UTF-8') ?>" data-edit-field="title"><?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?></a>
            <p><?= htmlspecialchars($post['author'], ENT_QUOTES, 'UTF-8') ?> &middot; <?= (int)$post['comment_count'] ?> comments</p>
            <?php if ($canEditPosts): ?>
                <button type="button" class="inline-edit-button js-edit-post" data-post-id="<?= $postId ?>">Edit</button>
            <?php endif; ?>
            <?php render_editable_excerpt('span', 'article-mobile-row__hidden-content', $post['content'] ?? '', 90); ?>
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
            $heroThumbnailStyle = app_safe_image_style($heroPost['thumbnail_style'] ?? '');
            ?>
            <section class="home-hero" data-inline-post data-post-id="<?= $heroId ?>">
                <a class="home-hero__media" href="<?= htmlspecialchars($heroUrl, ENT_QUOTES, 'UTF-8') ?>" data-edit-image>
                    <img src="<?= htmlspecialchars(app_post_image_src($heroPost['thumbnail'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($heroPost['title'], ENT_QUOTES, 'UTF-8') ?>"<?= $heroThumbnailStyle !== '' ? ' style="' . htmlspecialchars($heroThumbnailStyle, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
                </a>
                <div class="home-hero__content">
                    <p class="section-kicker">Latest Article</p>
                    <h2><a href="<?= htmlspecialchars($heroUrl, ENT_QUOTES, 'UTF-8') ?>" data-edit-field="title"><?= htmlspecialchars($heroPost['title'], ENT_QUOTES, 'UTF-8') ?></a></h2>
                    <p class="article-meta">
                        By <span class="<?= htmlspecialchars(app_user_role_class($heroPost['user_role'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($heroPost['author'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span><?= (int)$heroPost['comment_count'] ?> Comments</span>
                    </p>
                    <?php render_editable_excerpt('div', 'home-hero__excerpt', $heroPost['content'] ?? '', 240); ?>
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
                <div class="article-mobile-list" data-mobile-article-slider aria-label="Recent articles for mobile">
                    <div class="article-mobile-list__track">
                        <?php foreach (array_chunk($continuePosts, 4) as $slideIndex => $postGroup): ?>
                            <div class="article-mobile-slide" aria-label="Recent articles group <?= (int)$slideIndex + 1 ?>">
                                <?php foreach ($postGroup as $post): ?>
                                    <?php render_mobile_article_row($post, $canEditPosts); ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($continuePosts) > 4): ?>
                        <div class="article-mobile-list__controls" aria-label="Mobile article slider controls">
                            <button type="button" class="article-mobile-list__button" data-mobile-prev aria-label="Previous article group">&larr;</button>
                            <div class="article-mobile-list__dots" aria-hidden="true">
                                <?php foreach (array_chunk($continuePosts, 4) as $slideIndex => $_): ?>
                                    <span class="<?= $slideIndex === 0 ? 'is-active' : '' ?>"></span>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="article-mobile-list__button" data-mobile-next aria-label="Next article group">&rarr;</button>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <div class="home-media-row">
        <section class="featured-video" data-video-editor data-video-url="<?= htmlspecialchars($video_link, ENT_QUOTES, 'UTF-8') ?>" data-video-style="<?= htmlspecialchars($video_style, ENT_QUOTES, 'UTF-8') ?>">
            <div class="section-heading">
                <div>
                    <p class="section-kicker">Watch</p>
                    <h2>Featured Video</h2>
                </div>
                <?php if ($canEditPosts): ?>
                    <div class="section-actions">
                        <button type="button" class="secondary-button js-video-edit">Edit video</button>
                        <a href="/admin/edit_video.php">Advanced</a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="featured-video__frame" data-video-preview<?= $video_style !== '' ? ' style="' . htmlspecialchars($video_style, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
            <?php if (!empty($video_embed)) : ?>
                <iframe src="<?= htmlspecialchars($video_embed, ENT_QUOTES, 'UTF-8') ?>" title="Featured video" allowfullscreen></iframe>
            <?php else : ?>
                <p>No featured video this week. Check back later.</p>
            <?php endif; ?>
            </div>
            <?php if ($canEditPosts): ?>
                <div class="video-inline-editor" data-video-panel hidden>
                    <label>
                        Video link
                        <input type="url" data-video-input value="<?= htmlspecialchars($video_link, ENT_QUOTES, 'UTF-8') ?>" placeholder="Paste a YouTube or Vimeo link">
                    </label>
                    <div class="video-preview-card" data-video-card>
                        <?php if ($video_preview !== ''): ?>
                            <img src="<?= htmlspecialchars($video_preview, ENT_QUOTES, 'UTF-8') ?>" alt="Featured video preview">
                        <?php endif; ?>
                        <span data-video-status><?= $video_embed !== '' ? 'Ready to embed.' : 'Paste a supported video link to preview it.' ?></span>
                    </div>
                    <div class="video-size-tools">
                        <label>Video width <input type="range" min="45" max="100" step="1" data-video-width value="100"></label>
                        <div class="inline-edit-toolbar__actions">
                            <button type="button" class="secondary-button" data-video-size="65">Compact</button>
                            <button type="button" class="secondary-button" data-video-size="82">Balanced</button>
                            <button type="button" class="secondary-button" data-video-size="100">Full</button>
                            <button type="button" class="secondary-button" data-video-ratio="1.777">Wide</button>
                            <button type="button" class="secondary-button" data-video-ratio="1.333">Classic</button>
                            <button type="button" class="secondary-button" data-video-ratio="1">Square</button>
                            <button type="button" class="secondary-button" data-video-reset-size>Reset size</button>
                        </div>
                    </div>
                    <div class="inline-edit-toolbar__actions">
                        <button type="button" data-video-save>Save Video</button>
                        <button type="button" class="secondary-button" data-video-cancel>Cancel</button>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <section class="magazine-preview" data-magazine-admin>
        <div class="section-heading">
            <div>
                <p class="section-kicker">External Magazines</p>
                <h2>Latest Issues</h2>
            </div>
            <?php if ($canManageMagazines): ?>
                <div class="section-actions">
                    <button type="button" class="secondary-button js-magazine-new">Add article</button>
                    <a href="/admin/manage_magazines.php">Manage</a>
                </div>
            <?php endif; ?>
        </div>
        <div class="magazine-preview__grid">
            <?php if ($magazineArticles): ?>
                <?php foreach ($magazineArticles as $row): ?>
                    <article class="magazine-card"
                             data-magazine-id="<?= (int)$row['id'] ?>"
                             data-title="<?= htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') ?>"
                             data-author="<?= htmlspecialchars($row['author'], ENT_QUOTES, 'UTF-8') ?>"
                             data-image-url="<?= htmlspecialchars($row['image_url'], ENT_QUOTES, 'UTF-8') ?>"
                             data-article-url="<?= htmlspecialchars($row['article_url'], ENT_QUOTES, 'UTF-8') ?>"
                             data-published-date="<?= htmlspecialchars($row['published_date'], ENT_QUOTES, 'UTF-8') ?>"
                             data-issue="<?= htmlspecialchars($row['issue'], ENT_QUOTES, 'UTF-8') ?>">
                        <a class="magazine-card__image" href="<?= htmlspecialchars($row['article_url'], ENT_QUOTES, 'UTF-8') ?>">
                            <img src="<?= htmlspecialchars($row['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') ?>">
                        </a>
                        <div>
                            <p><?= htmlspecialchars($row['issue'], ENT_QUOTES, 'UTF-8') ?></p>
                            <h3><a href="<?= htmlspecialchars($row['article_url'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') ?></a></h3>
                            <small><?= htmlspecialchars($row['author'], ENT_QUOTES, 'UTF-8') ?></small>
                            <?php if ($canManageMagazines): ?>
                                <button type="button" class="inline-edit-button js-magazine-edit">Edit</button>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="magazine-preview__empty">No external magazine articles yet.</p>
            <?php endif; ?>
        </div>
        <?php if ($canManageMagazines): ?>
            <form class="magazine-inline-form" data-magazine-form hidden>
                <input type="hidden" name="id">
                <label>Title <input type="text" name="title" maxlength="255" required></label>
                <label>Author <input type="text" name="author" maxlength="255" required></label>
                <label>Image URL <input type="url" name="image_url" required></label>
                <label>Article URL <input type="url" name="article_url" required></label>
                <label>Published <input type="date" name="published_date" value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required></label>
                <label>Issue <input type="text" name="issue" value="<?= htmlspecialchars($issue, ENT_QUOTES, 'UTF-8') ?>" required></label>
                <p class="inline-editor-message" data-magazine-message aria-live="polite"></p>
                <div class="inline-edit-toolbar__actions">
                    <button type="submit">Save Magazine Article</button>
                    <button type="button" class="secondary-button" data-magazine-cancel>Cancel</button>
                </div>
            </form>
        <?php endif; ?>
        <a href="/magazines/all_issues.php" class="view-all">View All Issues</a>
        </section>
        </div>
    </main>
</div>

<?php include __DIR__ . '/footer.php'; ?>
