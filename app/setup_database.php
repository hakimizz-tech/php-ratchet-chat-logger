<?php
function setup_database() {
    $host = '127.0.0.1';
    $user = 'root';
    $pass = ''; // default: empty
    $db = 'test_db';
    $charset = 'utf8mb4';

    try {
        $pdo = new PDO("mysql:host=$host", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET `$charset` COLLATE `utf8mb4_unicode_ci`");
        $pdo->exec("USE `$db`");

        echo "Database '$db' is ready.\n";

        // Drop existing tables to ensure a clean state
        $pdo->exec("DROP TABLE IF EXISTS messages");
        $pdo->exec("DROP TABLE IF EXISTS users");
        echo "Existing tables dropped.\n";

        // Create users table
        $pdo->exec("CREATE TABLE users (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            firstname VARCHAR(255) NOT NULL,
            lastname VARCHAR(255) NOT NULL,
            username VARCHAR(255) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL UNIQUE,
            login_token VARCHAR(255) NULL,
            login_token_expires_at DATETIME NULL,
            is_connected BOOLEAN NOT NULL DEFAULT 0,
            last_seen TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;");
        echo "Table 'users' created.\n";

        // Create messages table
        $pdo->exec("CREATE TABLE messages (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        ) ENGINE=InnoDB;");
        echo "Table 'messages' created.\n";

    } catch (PDOException $e) {
        die("DB ERROR: " . $e->getMessage());
    }
}

// Run the setup
setup_database();
?>