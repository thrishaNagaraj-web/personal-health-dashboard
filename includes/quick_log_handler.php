<?php
session_save_path('/tmp');
session_start();
require_once 'config.php';
require_once 'csrf.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$log_date = $_POST['log_date'] ?? date('Y-m-d');

try {
    switch ($action) {
        case 'log_water':
            $amount = (int)$_POST['amount'];
            if ($amount > 0) {
                $stmt = $pdo->prepare("INSERT INTO water_logs (user_id, amount_ml, log_date) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $amount, $log_date]);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid amount.']);
            }
            break;

        case 'log_calories':
            $calories = (int)$_POST['calories'];
            if ($calories > 0) {
                $stmt = $pdo->prepare("INSERT INTO calories_logs (user_id, calories, log_date) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $calories, $log_date]);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid calories.']);
            }
            break;

        case 'log_exercise':
            $activity = trim($_POST['activity'] ?? '');
            $duration = (int)$_POST['duration'];
            if (!empty($activity) && $duration > 0) {
                $stmt = $pdo->prepare("INSERT INTO exercise_logs (user_id, activity, duration_mins, log_date) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $activity, $duration, $log_date]);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid exercise data.']);
            }
            break;

        case 'log_sleep_mood':
            $hours = (float)$_POST['sleep_hours'];
            $quality = (int)$_POST['sleep_quality'];
            $mood = (int)$_POST['mood'];
            
            // Log Sleep
            if ($hours > 0) {
                $stmt = $pdo->prepare("INSERT INTO sleep_logs (user_id, hours, quality, log_date) VALUES (?, ?, ?, ?)
                                       ON CONFLICT(user_id, log_date) DO UPDATE SET hours=excluded.hours, quality=excluded.quality");
                $stmt->execute([$user_id, $hours, $quality, $log_date]);
            }
            
            // Log Mood
            if ($mood >= 1 && $mood <= 5) {
                $stmt = $pdo->prepare("INSERT INTO mood_logs (user_id, mood, log_date) VALUES (?, ?, ?)
                                       ON CONFLICT(user_id, log_date) DO UPDATE SET mood=excluded.mood");
                $stmt->execute([$user_id, $mood, $log_date]);
            }
            
            echo json_encode(['success' => true]);
            break;

        case 'log_weight':
            $weight = (float)$_POST['weight'];
            if ($weight > 0) {
                $stmt = $pdo->prepare("INSERT INTO weight_logs (user_id, weight, log_date) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $weight, $log_date]);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid weight.']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action.']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
