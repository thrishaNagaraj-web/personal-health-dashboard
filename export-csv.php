<?php
session_save_path('/tmp');
session_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireLogin();

$user_id = $_SESSION['user_id'];

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="health_export.csv"');

$output = fopen('php://output', 'w');

// CSV Header Row
fputcsv($output, ['Date', 'Water (ml)', 'Calories (kcal)', 'Exercise (mins)', 'Sleep (hours)', 'Sleep Quality', 'Mood (1-5)', 'Weight (kg)']);

try {
    // Get all unique dates the user has any data
    $stmt = $pdo->prepare("
        SELECT DISTINCT log_date FROM (
            SELECT log_date FROM water_logs WHERE user_id = :uid
            UNION SELECT log_date FROM calories_logs WHERE user_id = :uid
            UNION SELECT log_date FROM exercise_logs WHERE user_id = :uid
            UNION SELECT log_date FROM sleep_logs WHERE user_id = :uid
            UNION SELECT log_date FROM mood_logs WHERE user_id = :uid
            UNION SELECT log_date FROM weight_logs WHERE user_id = :uid
        ) ORDER BY log_date DESC
    ");
    $stmt->execute(['uid' => $user_id]);
    $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($dates as $date) {
        // Water
        $stmt = $pdo->prepare("SELECT SUM(amount_ml) FROM water_logs WHERE user_id = ? AND log_date = ?");
        $stmt->execute([$user_id, $date]);
        $water = $stmt->fetchColumn() ?: 0;

        // Calories
        $stmt = $pdo->prepare("SELECT SUM(calories) FROM calories_logs WHERE user_id = ? AND log_date = ?");
        $stmt->execute([$user_id, $date]);
        $calories = $stmt->fetchColumn() ?: 0;

        // Exercise
        $stmt = $pdo->prepare("SELECT SUM(duration_mins) FROM exercise_logs WHERE user_id = ? AND log_date = ?");
        $stmt->execute([$user_id, $date]);
        $exercise = $stmt->fetchColumn() ?: 0;

        // Sleep
        $stmt = $pdo->prepare("SELECT hours, quality FROM sleep_logs WHERE user_id = ? AND log_date = ?");
        $stmt->execute([$user_id, $date]);
        $sleep_row = $stmt->fetch();
        $sleep_hours = $sleep_row ? $sleep_row['hours'] : '';
        $sleep_quality = $sleep_row ? $sleep_row['quality'] : '';

        // Mood
        $stmt = $pdo->prepare("SELECT mood FROM mood_logs WHERE user_id = ? AND log_date = ?");
        $stmt->execute([$user_id, $date]);
        $mood = $stmt->fetchColumn() ?: '';

        // Weight
        $stmt = $pdo->prepare("SELECT weight FROM weight_logs WHERE user_id = ? AND log_date = ?");
        $stmt->execute([$user_id, $date]);
        $weight = $stmt->fetchColumn() ?: '';

        fputcsv($output, [$date, $water, $calories, $exercise, $sleep_hours, $sleep_quality, $mood, $weight]);
    }
} catch (PDOException $e) {
    fputcsv($output, ['Error exporting data. Please try again.']);
}

fclose($output);
exit;
