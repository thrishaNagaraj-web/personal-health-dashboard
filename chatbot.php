<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireLogin();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$message = trim($data['message'] ?? '');
if (!$message) { echo json_encode(['reply' => 'Please type a message.']); exit; }

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Water
$stmt = $pdo->prepare("SELECT SUM(amount_ml) FROM water_logs WHERE user_id = ? AND log_date = ?");
$stmt->execute([$user_id, $today]);
$water = $stmt->fetchColumn() ?: 0;

// Calories
$stmt = $pdo->prepare("SELECT SUM(calories) FROM calories_logs WHERE user_id = ? AND log_date = ?");
$stmt->execute([$user_id, $today]);
$calories = $stmt->fetchColumn() ?: 0;

// Weight
$stmt = $pdo->prepare("SELECT weight FROM weight_logs WHERE user_id = ? ORDER BY log_date DESC, id DESC LIMIT 1");
$stmt->execute([$user_id]);
$weight = $stmt->fetchColumn() ?: 'unknown';

// Exercise
$stmt = $pdo->prepare("SELECT activity, duration_mins FROM exercise_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 1");
$stmt->execute([$user_id]);
$ex_row = $stmt->fetch();
$exercise = $ex_row ? "{$ex_row['activity']} for {$ex_row['duration_mins']} mins" : 'none';

$system_prompt = "You are a friendly personal health assistant. Today's user stats: water={$water}ml (goal 2000ml), calories={$calories}kcal (goal 2000kcal), latest weight={$weight}kg, last exercise={$exercise}. Give warm, practical advice in max 2 sentences. Never give medical diagnoses.";

$api_url = 'https://api.groq.com/openai/v1/chat/completions';
$api_key = $_ENV['GROQ_API_KEY'] ?? getenv('GROQ_API_KEY');

$payload = json_encode([
    "model" => "llama-3.1-8b-instant",
    "messages" => [
        ["role" => "system", "content" => $system_prompt],
        ["role" => "user", "content" => $message]
    ],
    "max_tokens" => 150,
    "temperature" => 0.7
]);

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$api_key}",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || !$response) {
    echo json_encode(['reply' => 'I am offline right now, try again shortly!']);
    exit;
}

$body = json_decode($response, true);
$aiReply = $body['choices'][0]['message']['content'] ?? 'I am offline right now, try again shortly!';

echo json_encode(['reply' => $aiReply]);
