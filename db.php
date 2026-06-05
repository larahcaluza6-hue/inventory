<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$user = "root";
$password = "";
$database = "inventory_db";

$conn = mysqli_connect($host, $user, $password, $database);

if(!$conn){
    die("Connection Failed: " . mysqli_connect_error());
}

mysqli_query(
    $conn,
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fullname VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(50) NOT NULL DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4"
);

$userRoleColumn = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");

if ($userRoleColumn && mysqli_num_rows($userRoleColumn) === 0) {
    mysqli_query($conn, "ALTER TABLE users ADD role VARCHAR(50) NOT NULL DEFAULT 'user' AFTER password");
}

$adminCountResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'admin'");
$adminCount = $adminCountResult ? mysqli_fetch_assoc($adminCountResult) : ['total' => 0];

if ((int) $adminCount['total'] === 0) {
    mysqli_query(
        $conn,
        "UPDATE users
         SET role = 'admin'
         WHERE id = (SELECT id FROM (SELECT id FROM users ORDER BY id ASC LIMIT 1) first_user)"
    );
}

mysqli_query(
    $conn,
    "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        product_name VARCHAR(255) NOT NULL,
        category VARCHAR(255) NOT NULL,
        brand VARCHAR(255) NOT NULL,
        quantity INT NOT NULL DEFAULT 0,
        market_quantity INT NOT NULL DEFAULT 0,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        image VARCHAR(255) NOT NULL DEFAULT '',
        status VARCHAR(50) NOT NULL DEFAULT 'Available',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4"
);

$marketStockColumn = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'market_quantity'");

if ($marketStockColumn && mysqli_num_rows($marketStockColumn) === 0) {
    mysqli_query($conn, "ALTER TABLE products ADD market_quantity INT NOT NULL DEFAULT 0 AFTER quantity");
}

$productUserColumn = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'user_id'");

if ($productUserColumn && mysqli_num_rows($productUserColumn) === 0) {
    mysqli_query($conn, "ALTER TABLE products ADD user_id INT NULL AFTER id");
}

mysqli_query(
    $conn,
    "CREATE TABLE IF NOT EXISTS market_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        transaction_type VARCHAR(50) NOT NULL DEFAULT 'Add Market Stock',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4"
);

$transactionUserColumn = mysqli_query($conn, "SHOW COLUMNS FROM market_transactions LIKE 'user_id'");

if ($transactionUserColumn && mysqli_num_rows($transactionUserColumn) === 0) {
    mysqli_query($conn, "ALTER TABLE market_transactions ADD user_id INT NULL AFTER id");
}

mysqli_query(
    $conn,
    "CREATE TABLE IF NOT EXISTS login_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        fullname VARCHAR(255) NOT NULL DEFAULT '',
        email VARCHAR(255) NOT NULL DEFAULT '',
        role VARCHAR(50) NOT NULL DEFAULT 'user',
        action VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4"
);

?>
