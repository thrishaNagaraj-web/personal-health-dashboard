<?php
session_save_path('/tmp');
session_start();
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once 'includes/config.php';

if (empty($_SESSION['user_id'])) {
    echo json_encode(['reply' => 'Please log in first.']);
    exit;
}

$api_key = $_ENV['GROQ_API_KEY'] ?? getenv('GROQ_API_KEY');
if (empty($api_key)) {
    echo json_encode(['reply' => 'AI Food Search is currently unavailable (API Key missing).']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$food_query = trim($data['food'] ?? '');

if (empty($food_query)) {
    echo json_encode(['reply' => 'Please enter a food name.']);
    exit;
}

$system_prompt = "You are a Nutrition Expert. Provide the estimated calorie count (per 100g or standard serving) and 2-3 key health benefits for the food provided. Format it clearly with a 'Calories:' line and a 'Benefits:' line. Keep it very concise (max 50 words). If the food is highly processed/unhealthy, briefly suggest one healthier alternative.";

$api_url = 'https://api.groq.com/openai/v1/chat/completions';
$payload = json_encode([
    "model" => "llama-3.1-8b-instant",
    "messages" => [
        ["role" => "system", "content" => $system_prompt],
        ["role" => "user", "content" => "Tell me about: " . $food_query]
    ],
    "max_tokens" => 150,
    "temperature" => 0.5
]);

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$api_key}",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $http_code !== 200) {
    echo json_encode(['reply' => 'Sorry, I could not fetch nutrition details right now.']);
    exit;
}

$body = json_decode($response, true);
$aiReply = $body['choices'][0]['message']['content'] ?? 'No data found for this food.';

echo json_encode(['reply' => $aiReply]);
