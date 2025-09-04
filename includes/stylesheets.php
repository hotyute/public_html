<?php $current_page = basename($_SERVER['SCRIPT_NAME'], '.php'); ?>
<link rel="stylesheet" type="text/css" href="/styles/style.css">
<link rel="stylesheet" type="text/css" href="/styles/header.css">
<?php if ($current_page == 'index') : ?>
    <link rel="stylesheet" type="text/css" href="/styles/surplus.css">
    <link rel="stylesheet" type="text/css" href="/styles/featuredvid.css">
    <link rel="stylesheet" type="text/css" href="/styles/sidebar.css">
    <link rel="stylesheet" type="text/css" href="/styles/index.css">
<?php endif; ?>
<?php if ($current_page == 'login') : ?>
    <link rel="stylesheet" type="text/css" href="/styles/surplus.css">
<?php endif; ?>
<?php if (($current_page == 'admin_panel') || ($current_page ==  'edit_post') || ($current_page == 'create_post') || ($current_page == 'test_manage') || $current_page == 'user_portal') : ?>
    <link rel="stylesheet" type="text/css" href="/styles/admin.css">
    <?php if ($current_page == 'edit_post') : ?>
        <link rel="stylesheet" type="text/css" href="/styles/edit_post.css">
    <?php endif; ?>
<?php endif; ?>
<?php if ($current_page == 'post') : ?>
    <link rel="stylesheet" type="text/css" href="/styles/post.css">
<?php endif; ?>
<?php if ($current_page == 'members') : ?>
    <link rel="stylesheet" type="text/css" href="/styles/roster.css">
<?php endif; ?>
<?php if ($current_page == 'contact') : ?>
    <link rel="stylesheet" type="text/css" href="/styles/surplus.css">
    <link rel="stylesheet" type="text/css" href="/styles/contact.css">
<?php endif; ?>
<?php if ($current_page == 'archive') : ?>
    <link rel="stylesheet" type="text/css" href="/styles/archive.css">
<?php endif; ?>
<?php if ($current_page == 'manage_users') : ?>
    <link rel="stylesheet" type="text/css" href="/styles/surplus.css">
    <link rel="stylesheet" type="text/css" href="/styles/manage_users.css">
<?php endif; ?>
<?php if ($current_page == 'notifications') : ?>
    <link rel="stylesheet" type="text/css" href="/styles/notifications.css">
<?php endif; ?>
<?php if ($current_page == 'test_history') : ?>
    <link rel="stylesheet" type="text/css" href="/styles/test_history.css">
<?php endif; ?>
<?php if ($current_page == 'register') : ?>
    <link rel="stylesheet" type="text/css" href="/styles/register.css">
<?php endif; ?>
<?php if ($current_page == 'user_settings') : ?>
    <link rel="stylesheet" type="text/css" href="/styles/user_settings.css">
<?php endif; ?>
<?php if ($current_page == '404') : ?>
    <link rel="stylesheet" type="text/css" href="/styles/404.css">
<?php endif; ?>
<link rel="stylesheet" type="text/css" href="/styles/footer.css">