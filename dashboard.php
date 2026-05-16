<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

function getBadgeHtml($today_val, $yesterday_val) {
    if ($yesterday_val === false || $yesterday_val === null || $yesterday_val == 0) {
        return '<span class="badge badge-muted">No data yesterday</span>';
    }
    if ($today_val == $yesterday_val) {
        return '<span class="badge badge-muted">0% vs yesterday</span>';
    }
    $pct_change = round((($today_val - $yesterday_val) / $yesterday_val) * 100);
    if ($pct_change > 0) {
        return '<span class="badge badge-up">↑ ' . $pct_change . '% vs yesterday</span>';
    } else {
        return '<span class="badge badge-down">↓ ' . abs($pct_change) . '% vs yesterday</span>';
    }
}

// Yesterday Data
try {
    $stmt = $pdo->prepare("SELECT weight FROM weight_logs WHERE user_id = ? AND log_date = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$user_id, $yesterday]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $yesterday_weight = $res['weight'] ?? 0;
} catch (PDOException $e) { $yesterday_weight = 0; }

try {
    $stmt = $pdo->prepare("SELECT SUM(amount_ml) as total FROM water_logs WHERE user_id = ? AND log_date = ?");
    $stmt->execute([$user_id, $yesterday]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $yesterday_water = $res['total'] ?? 0;
} catch (PDOException $e) { $yesterday_water = 0; }

try {
    $stmt = $pdo->prepare("SELECT SUM(calories) as total FROM calories_logs WHERE user_id = ? AND log_date = ?");
    $stmt->execute([$user_id, $yesterday]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $yesterday_calories = $res['total'] ?? 0;
} catch (PDOException $e) { $yesterday_calories = 0; }

try {
    $stmt = $pdo->prepare("SELECT duration_mins FROM exercise_logs WHERE user_id = ? AND log_date = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$user_id, $yesterday]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $yesterday_exercise = $res['duration_mins'] ?? 0;
} catch (PDOException $e) { $yesterday_exercise = 0; }

// Streak logic
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT log_date FROM (
            SELECT log_date FROM water_logs WHERE user_id = :uid AND log_date >= date('now', '-30 days') AND log_date <= date('now')
            UNION
            SELECT log_date FROM calories_logs WHERE user_id = :uid AND log_date >= date('now', '-30 days') AND log_date <= date('now')
            UNION
            SELECT log_date FROM exercise_logs WHERE user_id = :uid AND log_date >= date('now', '-30 days') AND log_date <= date('now')
            UNION
            SELECT log_date FROM weight_logs WHERE user_id = :uid AND log_date >= date('now', '-30 days') AND log_date <= date('now')
        )
    ");
    $stmt->execute(['uid' => $user_id]);
    $active_dates = $stmt->fetchAll(PDO::FETCH_COLUMN) ?? [];
} catch (PDOException $e) { $active_dates = []; }

$streak_days = 0;
for ($i = 0; $i < 30; $i++) {
    $d = date('Y-m-d', strtotime("-$i days"));
    if (in_array($d, $active_dates)) {
        $streak_days++;
    } else if ($i === 0) {
        // Missing today doesn't break the streak
    } else {
        break;
    }
}
$streak_days = $streak_days ?? 0;
$streak = $streak_days; // For HTML compatibility

// User Details (Height for BMI)
try {
    $stmt = $pdo->prepare("SELECT height FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $height = $res['height'] ?? 0;
} catch (PDOException $e) { $height = 0; }

// Latest Weight
try {
    $stmt = $pdo->prepare("SELECT weight FROM weight_logs WHERE user_id = ? ORDER BY log_date DESC, id DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $latest_weight = $res['weight'] ?? '--';
} catch (PDOException $e) { $latest_weight = '--'; }

$bmi = null;
if ($height > 0 && $latest_weight !== '--') {
    $height_m = $height / 100;
    $bmi = round($latest_weight / ($height_m * $height_m), 1);
}

// Today's Water
try {
    $stmt = $pdo->prepare("SELECT SUM(amount_ml) as total FROM water_logs WHERE user_id = ? AND log_date = ?");
    $stmt->execute([$user_id, $today]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $today_water = $res['total'] ?? 0;
} catch (PDOException $e) { $today_water = 0; }

// Today's Calories
try {
    $stmt = $pdo->prepare("SELECT SUM(calories) as total FROM calories_logs WHERE user_id = ? AND log_date = ?");
    $stmt->execute([$user_id, $today]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $today_calories = $res['total'] ?? 0;
} catch (PDOException $e) { $today_calories = 0; }

// Latest Exercise
try {
    $stmt = $pdo->prepare("SELECT activity, duration_mins FROM exercise_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $latest_exercise = $stmt->fetch(PDO::FETCH_ASSOC) ?? [];
} catch (PDOException $e) { $latest_exercise = []; }

try {
    $stmt = $pdo->prepare("SELECT SUM(duration_mins) as total FROM exercise_logs WHERE user_id = ? AND log_date = ?");
    $stmt->execute([$user_id, $today]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $today_exercise_mins = $res['total'] ?? 0;
} catch (PDOException $e) { $today_exercise_mins = 0; }

// Query last night's sleep
try {
    $stmt = $pdo->prepare("SELECT hours, quality FROM sleep_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $last_sleep = $stmt->fetch(PDO::FETCH_ASSOC) ?? [];
} catch (PDOException $e) { $last_sleep = []; }
$sleep_hours = $last_sleep['hours'] ?? 0;

// Query today's mood
try {
    $stmt = $pdo->prepare("SELECT mood FROM mood_logs WHERE user_id = ? AND log_date = ?");
    $stmt->execute([$user_id, $today]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $today_mood = $res['mood'] ?? 0;
} catch (PDOException $e) { $today_mood = 0; }

$quality_emojis_short = [1 => '😴', 2 => '😐', 3 => '🙂', 4 => '😊', 5 => '🤩'];
$mood_emojis = [1 => '😢', 2 => '😕', 3 => '😐', 4 => '🙂', 5 => '😄'];

// --- User Goals ---
try {
    $stmt = $pdo->prepare("SELECT water_goal, calorie_goal, exercise_goal, sleep_goal FROM goals WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $db_goals = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $db_goals = false; }

$user_goals = $db_goals ?: null;
$user_goals = $user_goals ?? [
    'water_goal' => 2000, 
    'calorie_goal' => 2000,
    'exercise_goal' => 30, 
    'sleep_goal' => 8
];

$water_goal = $user_goals['water_goal'];
$calorie_goal = $user_goals['calorie_goal'];
$exercise_goal = $user_goals['exercise_goal'];
$sleep_goal = $user_goals['sleep_goal'];

// --- Health Score Calculation ---
$health_score = 0;
if ($today_water > 0 && $today_calories > 0 && $today_exercise_mins > 0 && $sleep_hours > 0) {
    $w_score = min(($today_water / max(1, $water_goal)) * 100, 100);
    $c_score = min(($today_calories / max(1, $calorie_goal)) * 100, 100);
    $e_score = min(($today_exercise_mins / max(1, $exercise_goal)) * 100, 100);
    $s_score = min(($sleep_hours / max(1, $sleep_goal)) * 100, 100);
    $health_score = round(($w_score + $c_score + $e_score + $s_score) / 4);
}

if ($health_score == 0) {
    $score_msg = "Log all 4 metrics (water, calories, exercise, sleep) to get your health score! 📊";
} else if ($health_score < 40) {
    $score_msg = "Let's get moving! 💪";
} else if ($health_score < 60) {
    $score_msg = "Good start, keep going! 🙂";
} else if ($health_score < 80) {
    $score_msg = "You're doing well! 🌟";
} else {
    $score_msg = "Excellent day! 🏆";
}

require_once 'includes/header.php';
?>

<div style="max-width: 1000px; margin: 0 auto; padding: clamp(1rem, 3vw, 2rem) 0;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: clamp(1rem, 3vw, 2rem);">
        <h2 style="margin: 0;">Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>
        <?php if ($streak > 0): ?>
            <div style="font-size: 1.25rem; font-weight: 700; background: var(--surface); padding: 0.5rem 1rem; border-radius: 999px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); color: #f97316;">
                🔥 <?= $streak ?> Day Streak
            </div>
        <?php endif; ?>
    </div>
    
    <style>
    @keyframes scoreReveal {
      from { --score: 0; }
      to   { --score: <?= $health_score ?>; }
    }
    </style>
    <div class="health-score-wrapper">
      <div class="health-score-ring" style="--score: <?= $health_score ?>;">
        <div class="score-inner">
          <span class="score-number"><?= $health_score ?></span>
          <span class="score-label">Health Score</span>
        </div>
      </div>
      <p class="score-message"><?= $score_msg ?></p>
    </div>

    <div class="dashboard-grid">
        
        <div class="card card-weight">
            <div>
                <h3>⚖️ Latest Weight</h3>
                <div class="card-kpi"><?= htmlspecialchars($latest_weight) ?></div>
                <div class="card-unit">
                    <?= $latest_weight !== '--' ? 'kg' : 'No target set' ?><br>
                    <?= getBadgeHtml($latest_weight !== '--' ? $latest_weight : 0, $yesterday_weight) ?>
                </div>
                <?php if ($bmi): ?>
                    <div style="background-color: var(--background); padding: 0.5rem; border-radius: 8px; margin-bottom: 1.5rem;">
                        <span style="font-weight: 600;">BMI: <?= htmlspecialchars($bmi) ?></span>
                    </div>
                <?php else: ?>
                    <div style="background-color: var(--background); padding: 0.5rem; border-radius: 8px; margin-bottom: 1.5rem;">
                        <a href="profile.php" style="color: var(--text-main); font-size: 0.9rem; text-decoration: underline;">Add height in Profile for BMI</a>
                    </div>
                <?php endif; ?>
            </div>
            <a href="weight-history.php" class="btn btn-outline btn-block">View History</a>
        </div>

        <div class="card card-water">
            <div>
                <h3>💧 Water Today</h3>
                <div class="card-kpi"><?= htmlspecialchars($today_water) ?></div>
                <div class="card-unit">
                    ml / <?= $water_goal ?> ml<br>
                    <?= getBadgeHtml($today_water, $yesterday_water) ?>
                </div>
                <?php $water_pct = min(100, ($water_goal > 0 ? $today_water / $water_goal : 0) * 100); ?>
                <div class="progress-bar-container">
                    <div class="progress-fill" style="width: <?= $water_pct ?>%;"></div>
                </div>
            </div>
            <a href="water-history.php" class="btn btn-outline btn-block">Drink More</a>
        </div>

        <div class="card card-calories">
            <div>
                <h3>🔥 Calories Today</h3>
                <div class="card-kpi"><?= htmlspecialchars($today_calories) ?></div>
                <div class="card-unit">
                    kcal / 2000 kcal<br>
                    <?= getBadgeHtml($today_calories, $yesterday_calories) ?>
                </div>
                <?php $cal_pct = min(100, ($calorie_goal > 0 ? $today_calories / $calorie_goal : 0) * 100); ?>
                <div class="progress-bar-container">
                    <div class="progress-fill" style="width: <?= $cal_pct ?>%;"></div>
                </div>
            </div>
            <a href="calories-history.php" class="btn btn-outline btn-block">Log Meal</a>
        </div>

        <div class="card card-exercise">
            <div>
                <h3>🏃 Latest Exercise</h3>
                <?php if ($latest_exercise): ?>
                    <div class="card-kpi"><?= htmlspecialchars($latest_exercise['activity']) ?></div>
                    <div class="card-unit">
                        <?= htmlspecialchars($latest_exercise['duration_mins']) ?> mins<br>
                        <?= getBadgeHtml($latest_exercise['duration_mins'], $yesterday_exercise) ?>
                    </div>
                    <?php $ex_pct = min(100, ($exercise_goal > 0 ? $latest_exercise['duration_mins'] / $exercise_goal : 0) * 100); ?>
                    <div class="progress-bar-container">
                        <div class="progress-fill" style="width: <?= $ex_pct ?>%;"></div>
                    </div>
                <?php else: ?>
                    <div class="card-kpi">--</div>
                    <div class="card-unit">No activity today</div>
                    <div class="progress-bar-container">
                        <div class="progress-fill" style="width: 0%;"></div>
                    </div>
                <?php endif; ?>
            </div>
            <a href="exercise-history.php" class="btn btn-outline btn-block">Add Activity</a>
        </div>

        <div class="card card-sleep">
            <div>
                <h3>🛌 Last Night's Sleep</h3>
                <?php if ($last_sleep): ?>
                    <div class="card-kpi"><?= htmlspecialchars($last_sleep['hours']) ?>h</div>
                    <div class="card-unit" style="font-size: 1.5rem;"><?= $quality_emojis_short[$last_sleep['quality']] ?></div>
                <?php else: ?>
                    <div class="card-kpi">--</div>
                    <div class="card-unit">No data</div>
                <?php endif; ?>
            </div>
            <a href="sleep.php" class="btn btn-outline btn-block">Log Sleep</a>
        </div>

        <div class="card card-mood">
            <div>
                <h3>🧠 Today's Mood</h3>
                <?php if ($today_mood): ?>
                    <div class="card-kpi" style="font-size: 4rem;"><?= $mood_emojis[$today_mood] ?></div>
                    <div class="card-unit">Logged</div>
                <?php else: ?>
                    <div class="card-kpi">--</div>
                    <div class="card-unit">How are you feeling?</div>
                <?php endif; ?>
            </div>
            <a href="mood.php" class="btn btn-outline btn-block">Log Mood</a>
        </div>

    </div>
</div>

<!-- Chatbot Widget -->
<div class="chatbot-widget">
    <div class="chatbot-toggle" id="chatbotToggle">💬</div>
    <div class="chatbot-window" id="chatbotWindow">
        <div class="chat-header">
            Health Assistant 
            <button class="chat-close" id="chatbotClose">&times;</button>
        </div>
        <div class="chat-messages" id="chatbotMessages">
            <div class="msg-bubble msg-bot">
                Hello <?= htmlspecialchars($_SESSION['username']) ?>! 👋 I'm your Health Assistant. You can text me about calories, water, weight, or exercise.
            </div>
        </div>
        <div class="chat-input-area">
            <input type="text" class="chat-input" id="chatbotInput" placeholder="Type a message..." autocomplete="off" />
            <button class="chat-send" id="chatbotSend">Send</button>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const toggleBtn = document.getElementById('chatbotToggle');
    const closeBtn = document.getElementById('chatbotClose');
    const chatWindow = document.getElementById('chatbotWindow');
    const inputField = document.getElementById('chatbotInput');
    const sendBtn = document.getElementById('chatbotSend');
    const messagesContainer = document.getElementById('chatbotMessages');

    function toggleChat() {
        chatWindow.classList.toggle('active');
        if (chatWindow.classList.contains('active')) {
            inputField.focus();
        }
    }

    toggleBtn.addEventListener('click', toggleChat);
    closeBtn.addEventListener('click', () => chatWindow.classList.remove('active'));

    function appendMessage(text, isUser) {
        const div = document.createElement('div');
        div.className = 'msg-bubble ' + (isUser ? 'msg-user' : 'msg-bot');
        div.textContent = text;
        messagesContainer.appendChild(div);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    async function handleSend() {
        const text = inputField.value.trim();
        if (!text) return;
        appendMessage(text, true);
        inputField.value = '';
        inputField.disabled = true;
        sendBtn.disabled = true;

        // Show typing indicator
        const typingId = 'typing-' + Date.now();
        const typingDiv = document.createElement('div');
        typingDiv.className = 'msg-bubble msg-bot typing-indicator';
        typingDiv.id = typingId;
        typingDiv.innerHTML = '<span></span><span></span><span></span>';
        messagesContainer.appendChild(typingDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;

        try {
            const res = await fetch('chatbot.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: text })
            });
            const data = await res.json();
            document.getElementById(typingId)?.remove();
            appendMessage(data.reply, false);
        } catch {
            document.getElementById(typingId)?.remove();
            appendMessage('I am offline right now, try again shortly!', false);
        } finally {
            inputField.disabled = false;
            sendBtn.disabled = false;
            inputField.focus();
        }
    }

    sendBtn.addEventListener('click', handleSend);
    inputField.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') handleSend();
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
