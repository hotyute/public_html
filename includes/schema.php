<?php

function app_table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
    ");
    $stmt->execute([$table]);
    return (int)$stmt->fetchColumn() > 0;
}

function app_column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ");
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function app_ensure_column(PDO $pdo, string $table, string $column, string $definition): void
{
    if (!app_column_exists($pdo, $table, $column)) {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN $definition");
    }
}

function app_column_data_type(PDO $pdo, string $table, string $column): ?string
{
    $stmt = $pdo->prepare("
        SELECT DATA_TYPE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ");
    $stmt->execute([$table, $column]);
    $type = $stmt->fetchColumn();
    return $type === false ? null : strtolower((string)$type);
}

function app_ensure_column_type(PDO $pdo, string $table, string $column, string $typeSql): void
{
    if (app_column_exists($pdo, $table, $column)) {
        $currentType = app_column_data_type($pdo, $table, $column);
        $expectedType = strtolower(strtok($typeSql, ' '));
        if ($currentType !== $expectedType) {
            $pdo->exec("ALTER TABLE `$table` MODIFY COLUMN `$column` $typeSql");
        }
    }
}

function app_ensure_schema(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) UNIQUE NOT NULL,
            displayname VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'editor', 'member') DEFAULT 'member'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content MEDIUMTEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            user_id INT NULL,
            thumbnail VARCHAR(255) NULL,
            views INT DEFAULT 0,
            voiceover_url VARCHAR(255) NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            user_id INT NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            parent_id INT DEFAULT NULL,
            INDEX idx_comments_post_parent (post_id, parent_id),
            INDEX idx_comments_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS roster_data (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            devotion ENUM('red', 'blue', 'yellow', 'green') DEFAULT 'red',
            UNIQUE KEY uniq_roster_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            test_name VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            num_questions INT NOT NULL DEFAULT 10
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS questions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            question TEXT NOT NULL,
            num_options TINYINT NOT NULL DEFAULT 4,
            option_struct VARCHAR(255) NOT NULL DEFAULT 'ssss',
            options JSON NOT NULL,
            correct_option VARCHAR(255) NOT NULL,
            test_ids JSON NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS scores (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            test_id INT NOT NULL,
            score INT NOT NULL,
            percent DECIMAL(5,2) NOT NULL DEFAULT 0,
            taken_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_scores_user (user_id),
            INDEX idx_scores_test (test_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_tests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            test_id INT NOT NULL,
            assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_user_test (user_id, test_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_notifications_user_read (user_id, is_read)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS magazine_articles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            author VARCHAR(255) NOT NULL,
            image_url VARCHAR(255) NOT NULL,
            article_url VARCHAR(255) NOT NULL,
            published_date DATE NOT NULL,
            issue VARCHAR(50) NOT NULL,
            INDEX idx_magazine_issue (issue),
            INDEX idx_magazine_published (published_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    app_ensure_column($pdo, 'users', 'displayname', 'displayname VARCHAR(255) NULL AFTER username');
    app_ensure_column($pdo, 'users', 'email', 'email VARCHAR(255) NULL AFTER displayname');
    app_ensure_column($pdo, 'posts', 'user_id', 'user_id INT NULL AFTER created_at');
    app_ensure_column($pdo, 'posts', 'thumbnail', 'thumbnail VARCHAR(255) NULL AFTER user_id');
    app_ensure_column($pdo, 'posts', 'views', 'views INT DEFAULT 0 AFTER thumbnail');
    app_ensure_column($pdo, 'posts', 'voiceover_url', 'voiceover_url VARCHAR(255) NULL AFTER views');
    app_ensure_column($pdo, 'comments', 'parent_id', 'parent_id INT DEFAULT NULL AFTER created_at');
    app_ensure_column($pdo, 'scores', 'percent', 'percent DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER score');
    app_ensure_column($pdo, 'tests', 'num_questions', 'num_questions INT NOT NULL DEFAULT 10 AFTER created_at');
    app_ensure_column($pdo, 'notifications', 'is_read', 'is_read BOOLEAN DEFAULT FALSE AFTER message');
    app_ensure_column($pdo, 'magazine_articles', 'issue', 'issue VARCHAR(50) NOT NULL AFTER published_date');
    app_ensure_column_type($pdo, 'posts', 'content', 'MEDIUMTEXT NOT NULL');
    app_ensure_column_type($pdo, 'notifications', 'message', 'TEXT NOT NULL');

    $pdo->exec("UPDATE users SET displayname = username WHERE displayname IS NULL OR displayname = ''");
}
