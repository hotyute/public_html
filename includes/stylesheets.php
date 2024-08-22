<?php $current_page = basename($_SERVER['SCRIPT_NAME'], '.php'); ?>
<link rel="stylesheet" type="text/css" href="/styles/style.css">
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
<?php endif; ?>
<?php if ($current_page == 'post') : ?>
    <link rel="stylesheet" type="text/css" href="/styles/post.css">
<?php endif; ?>
<?php if ($current_page == 'roster') : ?>
    <link rel="stylesheet" type="text/css" href="/styles/roster.css">
<?php endif; ?>
<?php if ($current_page == 'contact') : ?>
    <link rel="stylesheet" type="text/css" href="/styles/surplus.css">
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
<link rel="stylesheet" type="text/css" href="/styles/header.css">
<link rel="stylesheet" type="text/css" href="/styles/footer.css">