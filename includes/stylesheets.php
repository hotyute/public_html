<?php $current_page = basename($_SERVER['SCRIPT_NAME'], '.php'); ?>
<link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>styles/style.css">
<?php if ($current_page == 'index') : ?>
    <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>styles/surplus.css">
    <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>styles/featuredvid.css">
    <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>styles/sidebar.css">
<?php endif; ?>
<?php if ($current_page == 'login') : ?>
    <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>styles/surplus.css">
<?php endif; ?>
<?php if (($current_page == 'admin_panel') || ($current_page ==  'edit_post') || ($current_page == 'create_post')) :?>
    <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>styles/admin.css">
<?php endif; ?>
<?php if ($current_page == 'post') : ?>
    <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>styles/post.css">
    <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>styles/comments.css">
<?php endif; ?>
<?php if ($current_page == 'roster') : ?>
    <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>styles/roster.css">
    <?php endif; ?>
<?php if ($current_page == 'contact') : ?>
    <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>styles/surplus.css">
<?php endif; ?>
<?php if ($current_page == 'archive') : ?>
    <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>styles/archive.css">
<?php endif; ?>
<?php if ($current_page == 'manage_users') : ?>
    <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>styles/surplus.css">
    <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>styles/manage_users.css">
<?php endif; ?>
<link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>styles/header.css">