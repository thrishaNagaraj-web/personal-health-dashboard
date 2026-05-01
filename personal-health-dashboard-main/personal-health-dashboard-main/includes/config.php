<?php
// Use RENDER_DISK_PATH if available, otherwise fall back to local directory
$db_dir = getenv('RENDER_DISK_PATH') ?: __DIR__;
$db_file = $db_dir . '/database.sqlite';
$dsn = "sqlite:$db_file";

try {
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Initialize tables if they don't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            birthdate DATE,
            height REAL,
            gender TEXT
        );
        CREATE TABLE IF NOT EXISTS weight_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            weight REAL NOT NULL,
            log_date DATE NOT NULL,
            FOREIGN KEY(user_id) REFERENCES users(id)
        );
        CREATE TABLE IF NOT EXISTS water_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            amount_ml INTEGER NOT NULL,
            log_date DATE NOT NULL,
            FOREIGN KEY(user_id) REFERENCES users(id)
        );
        CREATE TABLE IF NOT EXISTS calories_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            calories INTEGER NOT NULL,
            meal_type TEXT,
            log_date DATE NOT NULL,
            FOREIGN KEY(user_id) REFERENCES users(id)
        );
        CREATE TABLE IF NOT EXISTS exercise_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            activity TEXT NOT NULL,
            duration_mins INTEGER NOT NULL,
            calories_burned INTEGER,
            log_date DATE NOT NULL,
            FOREIGN KEY(user_id) REFERENCES users(id)
        );
    ");

} catch(PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}
?>
