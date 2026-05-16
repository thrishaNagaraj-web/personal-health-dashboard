<?php

try {
    $pdo = new PDO('sqlite:' . __DIR__ . '/../database.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        birthdate DATE,
        height REAL,
        gender TEXT
    )");
} catch (PDOException $e) {
    error_log("Table creation error (users): " . $e->getMessage());
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS weight_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        weight REAL NOT NULL,
        log_date DATE NOT NULL,
        FOREIGN KEY(user_id) REFERENCES users(id)
    )");
} catch (PDOException $e) {
    error_log("Table creation error (weight_logs): " . $e->getMessage());
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS water_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        amount_ml INTEGER NOT NULL,
        log_date DATE NOT NULL,
        FOREIGN KEY(user_id) REFERENCES users(id)
    )");
} catch (PDOException $e) {
    error_log("Table creation error (water_logs): " . $e->getMessage());
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS calorie_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        calories INTEGER NOT NULL,
        meal_type TEXT,
        log_date DATE NOT NULL,
        FOREIGN KEY(user_id) REFERENCES users(id)
    )");
} catch (PDOException $e) {
    error_log("Table creation error (calorie_logs): " . $e->getMessage());
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS exercise_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        activity TEXT NOT NULL,
        duration_mins INTEGER NOT NULL,
        calories_burned INTEGER,
        log_date DATE NOT NULL,
        FOREIGN KEY(user_id) REFERENCES users(id)
    )");
} catch (PDOException $e) {
    error_log("Table creation error (exercise_logs): " . $e->getMessage());
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS sleep_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        hours REAL,
        quality INTEGER CHECK(quality >= 1 AND quality <= 5),
        log_date DATE,
        note TEXT,
        UNIQUE(user_id, log_date)
    )");
} catch (PDOException $e) {
    error_log("Table creation error (sleep_logs): " . $e->getMessage());
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS mood_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        mood INTEGER CHECK(mood >= 1 AND mood <= 5),
        log_date DATE,
        note TEXT,
        UNIQUE(user_id, log_date)
    )");
} catch (PDOException $e) {
    error_log("Table creation error (mood_logs): " . $e->getMessage());
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS weekly_insights (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        week_start DATE,
        summary TEXT,
        generated_at DATETIME,
        UNIQUE(user_id, week_start)
    )");
} catch (PDOException $e) {
    error_log("Table creation error (weekly_insights): " . $e->getMessage());
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS goals (
        user_id INTEGER PRIMARY KEY,
        water_goal INTEGER DEFAULT 2000,
        calorie_goal INTEGER DEFAULT 2000,
        exercise_goal INTEGER DEFAULT 30,
        sleep_goal REAL DEFAULT 8,
        weight_goal REAL,
        updated_at DATETIME
    )");
} catch (PDOException $e) {
    error_log("Table creation error (goals): " . $e->getMessage());
}

// Keep calories_logs as well in case they used it previously
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS calories_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        calories INTEGER NOT NULL,
        meal_type TEXT,
        log_date DATE NOT NULL,
        FOREIGN KEY(user_id) REFERENCES users(id)
    )");
} catch (PDOException $e) {
    error_log("Table creation error (calories_logs): " . $e->getMessage());
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ip_address TEXT NOT NULL,
        attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    error_log("Table creation error (login_attempts): " . $e->getMessage());
}
?>
