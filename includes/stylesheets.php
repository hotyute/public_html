<?php
// includes/stylesheets.php
$current_page = basename($_SERVER['SCRIPT_NAME'], '.php');

function css($path) {
    $full = $_SERVER['DOCUMENT_ROOT'] . $path;
    $v = file_exists($full) ? filemtime($full) : time();
    echo '<link rel="stylesheet" type="text/css" href="' . $path . '?v=' . $v . '">' . PHP_EOL;
}
?>
<?php css('/styles/style.css'); ?>
<?php css('/styles/header.css'); ?>

<?php if ($current_page == 'index') : ?>
    <?php css('/styles/surplus.css'); ?>
    <?php css('/styles/featuredvid.css'); ?>
    <?php css('/styles/sidebar.css'); ?>
    <?php css('/styles/index.css'); ?>
<?php endif; ?>

<?php if ($current_page == 'login') : ?>
    <?php css('/styles/surplus.css'); ?>
<?php endif; ?>

<?php if (in_array($current_page, ['admin_panel','edit_post','create_post','test_manage','user_portal','manage_users','manage_magazines'])) : ?>
    <?php css('/styles/admin.css'); ?>
    <?php if ($current_page == 'edit_post') : ?>
        <?php css('/styles/edit_post.css'); ?>
    <?php endif; ?>
<?php endif; ?>

<?php if ($current_page == 'post') : ?>
    <?php css('/styles/post.css'); ?>
<?php endif; ?>

<?php if ($current_page == 'members') : ?>
    <?php css('/styles/roster.css'); ?>
<?php endif; ?>

<?php if ($current_page == 'contact') : ?>
    <?php css('/styles/surplus.css'); ?>
    <?php css('/styles/contact.css'); ?>
<?php endif; ?>

<?php if ($current_page == 'archive') : ?>
    <?php css('/styles/archive.css'); ?>
<?php endif; ?>

<?php if ($current_page == 'manage_users') : ?>
    <?php css('/styles/surplus.css'); ?>
    <?php css('/styles/manage_users.css'); ?>
<?php endif; ?>

<?php if ($current_page == 'notifications') : ?>
    <?php css('/styles/notifications.css'); ?>
<?php endif; ?>

<?php if ($current_page == 'test_history') : ?>
    <?php css('/styles/test_history.css'); ?>
<?php endif; ?>

<?php if ($current_page == 'register') : ?>
    <?php css('/styles/register.css'); ?>
<?php endif; ?>

<?php if ($current_page == 'user_settings') : ?>
    <?php css('/styles/user_settings.css'); ?>
<?php endif; ?>

<?php if ($current_page == '404') : ?>
    <?php css('/styles/404.css'); ?>
<?php endif; ?>

<?php css('/styles/footer.css'); ?>