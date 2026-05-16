<?php
// Ensure no raw PHP errors ever break the JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    require_once 'includes/config.php';
    require_once 'includes/auth.php';
    
    // Use custom check instead of requireLogin() to avoid HTML redirects on auth failure
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['reply' => 'Please log in to use the health assistant.']);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['reply' => 'System error. Please try again later.']);
    exit;
}

$api_key = $_ENV['GROQ_API_KEY'] ?? getenv('GROQ_API_KEY');
if (empty($api_key)) {
    echo json_encode(['reply' => 'AI unavailable. Add GROQ_API_KEY to Render environment.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$message = trim($data['message'] ?? '');
if (!$message) { 
    echo json_encode(['reply' => 'Please type a message.']); 
    exit; 
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Water
try {
    $stmt = $pdo->prepare("SELECT SUM(amount_ml) as total FROM water_logs WHERE user_id = ? AND log_date = ?");
    $stmt->execute([$user_id, $today]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $water = $res['total'] ?? 0;
} catch (PDOException $e) {
    $water = 0;
}

// Calories
try {
    $stmt = $pdo->prepare("SELECT SUM(calories) as total FROM calories_logs WHERE user_id = ? AND log_date = ?");
    $stmt->execute([$user_id, $today]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $calories = $res['total'] ?? 0;
} catch (PDOException $e) {
    $calories = 0;
}

// Weight
try {
    $stmt = $pdo->prepare("SELECT weight FROM weight_logs WHERE user_id = ? ORDER BY log_date DESC, id DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $weight = $res['weight'] ?? 'unknown';
} catch (PDOException $e) {
    $weight = 'unknown';
}

// Exercise
try {
    $stmt = $pdo->prepare("SELECT activity, duration_mins FROM exercise_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $ex_row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    if (!empty($ex_row['activity']) && !empty($ex_row['duration_mins'])) {
        $exercise = "{$ex_row['activity']} for {$ex_row['duration_mins']} mins";
    } else {
        $exercise = 'none';
    }
} catch (PDOException $e) {
    $exercise = 'none';
}

$system_prompt = "You are a friendly personal health assistant. Today's user stats: water={$water}ml (goal 2000ml), calories={$calories}kcal (goal 2000kcal), latest weight={$weight}kg, last exercise={$exercise}. Give warm, practical advice in max 2 sentences. Never give medical diagnoses.";

$api_url = 'https://api.groq.com/openai/v1/chat/completions';

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
if ($ch === false) {
    echo json_encode(['reply' => 'Failed to initialize AI connection.']);
    exit;
}

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$api_key}",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($response === false || $http_code !== 200) {
    $error_msg = $curl_error ? "Connection error: {$curl_error}" : "HTTP error {$http_code}";
    echo json_encode(['reply' => "I am having trouble connecting right now ($error_msg). Try again shortly!"]);
    exit;
}

$body = json_decode($response, true);
$aiReply = $body['choices'][0]['message']['content'] ?? 'I am offline right now, try again shortly!';

echo json_encode(['reply' => $aiReply]);
