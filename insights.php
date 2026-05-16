<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week'));
$seven_days_ago = date('Y-m-d', strtotime('-7 days'));

// Regenerate Cache Logic
if (isset($_GET['regenerate']) && $_GET['regenerate'] == 1) {
    $stmt = $pdo->prepare("DELETE FROM weekly_insights WHERE user_id = ? AND week_start = ?");
    $stmt->execute([$user_id, $week_start]);
    header("Location: insights.php");
    exit;
}

// 1. Fetch Metrics for the last 7 days
// Water
$stmt = $pdo->prepare("SELECT AVG(amount_ml) FROM water_logs WHERE user_id = ? AND log_date >= ?");
$stmt->execute([$user_id, $seven_days_ago]);
$avg_water = round((float)$stmt->fetchColumn());

// Calories
$stmt = $pdo->prepare("SELECT SUM(calories) as total, AVG(calories) as avg FROM calories_logs WHERE user_id = ? AND log_date >= ?");
$stmt->execute([$user_id, $seven_days_ago]);
$cal_row = $stmt->fetch();
$total_calories = (int)$cal_row['total'];
$avg_calories = round((float)$cal_row['avg']);

// Exercise
$stmt = $pdo->prepare("SELECT SUM(duration_mins) FROM exercise_logs WHERE user_id = ? AND log_date >= ?");
$stmt->execute([$user_id, $seven_days_ago]);
$total_exercise_mins = (int)$stmt->fetchColumn();

// Weight Change
$stmt = $pdo->prepare("SELECT weight FROM weight_logs WHERE user_id = ? AND log_date >= ? ORDER BY log_date ASC, id ASC");
$stmt->execute([$user_id, $seven_days_ago]);
$weights_this_week = $stmt->fetchAll(PDO::FETCH_COLUMN);

$weight_start = count($weights_this_week) > 0 ? (float)$weights_this_week[0] : 0;
$weight_end = count($weights_this_week) > 0 ? (float)end($weights_this_week) : 0;
$weight_change = count($weights_this_week) > 1 ? round($weight_end - $weight_start, 1) : 0;
$weight_display = count($weights_this_week) > 1 ? (($weight_change > 0 ? '+' : '') . $weight_change . 'kg') : 'N/A';

// Sleep & Mood
$avg_sleep = null;
$avg_mood = null;

try {
    $stmt = $pdo->prepare("SELECT AVG(hours) FROM sleep_logs WHERE user_id = ? AND log_date >= ?");
    $stmt->execute([$user_id, $seven_days_ago]);
    $avg_sleep = $stmt->fetchColumn() ? round((float)$stmt->fetchColumn(), 1) : null;
} catch (Exception $e) {}

try {
    $stmt = $pdo->prepare("SELECT AVG(mood) FROM mood_logs WHERE user_id = ? AND log_date >= ?");
    $stmt->execute([$user_id, $seven_days_ago]);
    $avg_mood = $stmt->fetchColumn() ? round((float)$stmt->fetchColumn(), 1) : null;
} catch (Exception $e) {}

// Days Logged (Any activity)
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT log_date) FROM (
        SELECT log_date FROM water_logs WHERE user_id = :uid AND log_date >= :ws
        UNION
        SELECT log_date FROM calories_logs WHERE user_id = :uid AND log_date >= :ws
        UNION
        SELECT log_date FROM exercise_logs WHERE user_id = :uid AND log_date >= :ws
        UNION
        SELECT log_date FROM weight_logs WHERE user_id = :uid AND log_date >= :ws
    )
");
$stmt->execute(['uid' => $user_id, 'ws' => $seven_days_ago]);
$days_logged = (int)$stmt->fetchColumn();

// 2. Check Cache
$stmt = $pdo->prepare("SELECT summary, generated_at FROM weekly_insights WHERE user_id = ? AND week_start = ?");
$stmt->execute([$user_id, $week_start]);
$cache = $stmt->fetch();

$summary = "";
$generated_at = "";

if ($cache && strtotime($cache['generated_at']) > strtotime('-6 hours')) {
    $summary = $cache['summary'];
    $generated_at = date('M d, g:i A', strtotime($cache['generated_at']));
} else {
    // Generate new using Groq API
    $sleep_str = $avg_sleep !== null ? "{$avg_sleep} hours/night" : "Not tracked yet";
    $mood_str = $avg_mood !== null ? "{$avg_mood}/5" : "Not tracked yet";

    $system_prompt = "You are an encouraging health coach writing a weekly review. Be specific, warm, and actionable. Use simple language.";
    $user_message = "My health stats this week:
- Average daily water: {$avg_water}ml (goal: 2000ml)
- Average daily calories: {$avg_calories}kcal (goal: 2000kcal)  
- Total exercise: {$total_exercise_mins} mins across 7 days
- Weight change: {$weight_change}kg this week
- Average sleep: {$sleep_str}
- Average mood: {$mood_str}

Write exactly 3 short paragraphs:
1. What went well this week (be specific with numbers)
2. One area to improve (be gentle, not harsh)
3. One specific actionable tip for next week";

    $api_key = $_ENV['GROQ_API_KEY'] ?? getenv('GROQ_API_KEY');
    if ($api_key) {
        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            "model" => "llama-3.1-8b-instant",
            "messages" => [
                ["role" => "system", "content" => $system_prompt],
                ["role" => "user", "content" => $user_message]
            ],
            "max_tokens" => 300,
            "temperature" => 0.75
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$api_key}",
            "Content-Type: application/json"
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err = curl_error($ch);
        curl_close($ch);
        
        if ($http_code === 200 && $response) {
            $body = json_decode($response, true);
            $summary = trim($body['choices'][0]['message']['content'] ?? '');
            
            if ($summary) {
                // Save to cache
                $stmt = $pdo->prepare("INSERT INTO weekly_insights (user_id, week_start, summary) VALUES (?, ?, ?) 
                                       ON CONFLICT(user_id, week_start) DO UPDATE SET summary = excluded.summary, generated_at = CURRENT_TIMESTAMP");
                $stmt->execute([$user_id, $week_start, $summary]);
                $generated_at = date('M d, g:i A');
            }
        } else {
            error_log("Groq API Error: HTTP $http_code. cURL Error: $curl_err. Response: $response");
        }
    } else {
        error_log("GROQ_API_KEY is not set in the environment.");
    }

    if (!$summary) {
        // Fallback static prompt
        $summary = "We couldn't reach the AI coach right now, but here is your static summary!\n\nYour average calories were {$avg_calories}kcal, and you drank an average of {$avg_water}ml of water daily. You achieved {$total_exercise_mins} minutes of exercise.\n\nKeep logging daily to build a complete picture of your health journey!";
        $generated_at = "Fallback (Not saved)";
    }
}

// 3. Radar Chart Calculation
$score_hydration = min(($avg_water / 2000) * 100, 100);
$score_nutrition = max(0, 100 - abs($avg_calories - 2000) / 20);
$score_exercise = min(($total_exercise_mins / 150) * 100, 100);
$score_sleep = $avg_sleep ? min(($avg_sleep / 8) * 100, 100) : 0;
$score_mood = $avg_mood ? ($avg_mood / 5) * 100 : 0;

require_once 'includes/header.php';
?>

<div class="main-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="margin: 0;">Weekly Insights</h2>
            <p style="color: var(--text-muted); margin: 0.5rem 0 0 0;">Last 7 Days (Since <?= date('M d', strtotime($seven_days_ago)) ?>)</p>
        </div>
        <a href="insights.php?regenerate=1" class="btn btn-outline" style="background: var(--surface);">🔄 Regenerate</a>
    </div>

    <!-- 7 KPI Summary Cards -->
    <div style="display: flex; overflow-x: auto; gap: 1rem; padding-bottom: 1rem; margin-bottom: 1rem;">
        <div class="card" style="min-width: 150px; text-align: center; padding: 1.5rem 1rem;">
            <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">💧</div>
            <div style="font-size: 1.5rem; font-weight: bold; color: var(--primary);"><?= $avg_water ?: 0 ?>ml</div>
            <div style="font-size: 0.85rem; color: var(--text-muted);">Avg Water</div>
        </div>
        <div class="card" style="min-width: 150px; text-align: center; padding: 1.5rem 1rem;">
            <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">🔥</div>
            <div style="font-size: 1.5rem; font-weight: bold; color: #e67e22;"><?= $avg_calories ?: 0 ?>kcal</div>
            <div style="font-size: 0.85rem; color: var(--text-muted);">Avg Calories</div>
        </div>
        <div class="card" style="min-width: 150px; text-align: center; padding: 1.5rem 1rem;">
            <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">🏃</div>
            <div style="font-size: 1.5rem; font-weight: bold; color: #2ecc71;"><?= $total_exercise_mins ?: 0 ?>m</div>
            <div style="font-size: 0.85rem; color: var(--text-muted);">Total Exercise</div>
        </div>
        <div class="card" style="min-width: 150px; text-align: center; padding: 1.5rem 1rem;">
            <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">⚖️</div>
            <div style="font-size: 1.5rem; font-weight: bold; color: #8b5cf6;"><?= $weight_display ?></div>
            <div style="font-size: 0.85rem; color: var(--text-muted);">Weight Change</div>
        </div>
        <div class="card" style="min-width: 150px; text-align: center; padding: 1.5rem 1rem;">
            <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">😴</div>
            <div style="font-size: 1.5rem; font-weight: bold; color: #3b82f6;"><?= $avg_sleep !== null ? $avg_sleep.'h' : 'N/A' ?></div>
            <div style="font-size: 0.85rem; color: var(--text-muted);">Avg Sleep</div>
        </div>
        <div class="card" style="min-width: 150px; text-align: center; padding: 1.5rem 1rem;">
            <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">😊</div>
            <div style="font-size: 1.5rem; font-weight: bold; color: #eab308;"><?= $avg_mood !== null ? $avg_mood.'/5' : 'N/A' ?></div>
            <div style="font-size: 0.85rem; color: var(--text-muted);">Avg Mood</div>
        </div>
        <div class="card" style="min-width: 150px; text-align: center; padding: 1.5rem 1rem;">
            <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">📅</div>
            <div style="font-size: 1.5rem; font-weight: bold; color: #64748b;"><?= $days_logged ?>/7</div>
            <div style="font-size: 0.85rem; color: var(--text-muted);">Days Logged</div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 1rem;">
        
        <!-- AI Summary Card -->
        <div class="card" style="border-left: 6px solid #8b5cf6; padding: 2rem; display: flex; flex-direction: column;">
            <h3 style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0;">🤖 Your Weekly Health Review</h3>
            <div style="flex: 1; font-size: 1.05rem; line-height: 1.6; color: var(--text-main);">
                <?= nl2br(htmlspecialchars($summary)) ?>
            </div>
            <div style="margin-top: 2rem; font-size: 0.85rem; color: var(--text-muted); text-align: right;">
                Generated at <?= $generated_at ?>
            </div>
        </div>

        <!-- Radar Chart Card -->
        <div class="card" style="display: flex; flex-direction: column; align-items: center; padding: 2rem;">
            <h3 style="margin-top: 0; margin-bottom: 1.5rem; align-self: flex-start;">Overall Balance</h3>
            <div style="width: 100%; max-width: 400px; position: relative;">
                <canvas id="radarChart"></canvas>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
   const ctx = document.getElementById('radarChart').getContext('2d');
   new Chart(ctx, {
     type: 'radar',
     data: {
       labels: ['Hydration','Nutrition','Exercise','Sleep','Mood'],
       datasets: [{
         label: 'This Week',
         data: [<?= $score_hydration ?>, <?= $score_nutrition ?>, <?= $score_exercise ?>, <?= $score_sleep ?>, <?= $score_mood ?>],
         backgroundColor: 'rgba(139, 92, 246, 0.15)',
         borderColor: '#8b5cf6',
         borderWidth: 2,
         pointBackgroundColor: '#8b5cf6',
         pointRadius: 5
       }]
     },
     options: {
       scales: {
         r: {
           min: 0, max: 100,
           ticks: { stepSize: 25, display: false },
           grid: { color: 'rgba(139,92,246,0.1)' },
           pointLabels: { font: { size: 13 } }
         }
       },
       plugins: { legend: { display: false } }
     }
   });
});
</script>

<?php require_once 'includes/footer.php'; ?>
